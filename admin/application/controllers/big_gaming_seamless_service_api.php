<?php

if(! defined('BASEPATH')){
    exit('No Direct script access allowed');
}

require_once dirname(__FILE__) . '/Abstract_seamless_service_game_api.php';
require_once dirname(__FILE__) . '/../../../submodules/game-lib/game_platform/common_seamless_utils.php';

/** 
 ** Variable Prefix Meaning
 *
 *
 * rsp - response variable/s
 * rq - request variable/s
 *
 *
*/
class Big_gaming_seamless_service_api extends Abstract_Seamless_Service_Game_Api
{

    use common_seamless_utils;

    /**  Success status Code */
    const STATUS_CODE_OK = 'OK';
    const STATUS_CODE_ERROR = 'ERROR';
    const STATUS_CODE_INVALID_ACTION_ERROR = 'ERROR';

    const STATUS_CODE_SUCCESS = "1";
    const STATUS_CODE_FAILED = "0";


    const ORDER_STATUS_NOT_EXIST = 0;
    const ORDER_STATUS_NEW_ORDER = 1;
    const ORDER_STATUS_WIN = 2;
    const ORDER_STATUS_TIE = 3;
    const ORDER_STATUS_LOST = 4;


    /** API endpoints of game provider in wallet transaction */
    const ACTION_GET_BALANCE = 'open.operator.user.balance';
    const ACTION_WITHDRAW = 'open.operator.order.transfer';
    const ACTION_DEPOSIT = 'open.operator.calc.transfer';
    const ACTION_OPERATOR_PING = 'open.operator.ping';
    const ACTION_OPERATOR_USER_TRANSFER= 'open.operator.user.transfer';
    const ACTION_OPERATOR_ORDER_STATUS = 'open.operator.order.status';
    

    /** array list of API endpoints of game provider in wallet transaction */
    const API_ACTIONS = [
        self::ACTION_GET_BALANCE,
        self::ACTION_WITHDRAW,
        self::ACTION_OPERATOR_PING,
        self::ACTION_DEPOSIT,
        self::ACTION_OPERATOR_USER_TRANSFER,
        self::ACTION_OPERATOR_ORDER_STATUS
    ];

    const METHOD_LIST_ALLOWED_IN_MAINTENANCE = ['credit', 'operatorOrderStatus'];

    private $transaction_for_fast_track = null;

    public function __construct()
    {
        parent::__construct();

        /** STD class for netent seamless request data */
        $this->rqSeamlessRequestData = new stdClass();
        /** STD class for netent seamless request data */
        $this->rspSeamlessResponseData = new stdClass();
        $this->currentMethod = 'index';
        /** for refreshing token */
        $this->newTokenValidity = $this->gameApiSysLibObj->getSystemInfo('newTokenValidity','+2 hours');
        $this->tokenTimeComparison = $this->gameApiSysLibObj->getSystemInfo('tokenTimeComparison','-10 minutes');
        $this->delay_response = $this->gameApiSysLibObj->getSystemInfo('delay_response',0);
        $this->requestHeaders = [];
        $this->loadModel(['common_seamless_wallet_transactions','common_token','external_system','response_result']);
    }

    /**
     * Entry point
     * 
     *
     * @param int $apiId
     * 
     * @return class::CI_Output
    */
    public function index($apiId)
    {
        # add raw data request to the logs first
        $rawData = $this->rawRequest();
        $this->requestHeaders = $requestHeaders = $this->input->request_headers();        
        $this->utils->debug_log('BIGGAMING '.__METHOD__.' RAW REQUEST ',$rawData,'headers',$requestHeaders,'apiId',$apiId);
        $this->setPlatformCode($apiId);
        if(! empty($this->request())){
            $this->rqSeamlessRequestData = $this->request();
        }

        # check IP address
        if(! $this->gameApiSysLibObj->validateWhiteIP()){
            $response = [
                "status" => 'ERROR_INVALID_IP',
                "error" => "IP Not allowed"                
            ];
            $response['result'] = [];
            $response['result']['orderResult'] = self::STATUS_CODE_FAILED;
            # set current method executing
            $sMethod = @$this->getAttributeInObject($this->rqSeamlessRequestData,'method');
            $this->currentMethod = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'method',$sMethod);
            $response_result_id = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData, null, [], $response, 401, 0);            
            return $this->outputHttpResponse($response, 401, [], $response_result_id);
        }

        # set seamless wallet transaction table
        $monthStr = date('Ym');
        $transTable = $this->gameApiSysLibObj->getTransactionsTable($monthStr);
        $this->common_seamless_wallet_transactions->setTransactionTable($transTable);
        

        # init it
        $player = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'loginId');
        $this->rspSeamlessResponseData->responseData = [];

        # set Common Variables
        $this->setCommonVar($apiId,$player);

        # get player id
        $jContent = json_encode($this->rspSeamlessResponseData->responseData);
        $playerId = $this->gameApiSysLibObj->getPlayerIdByGameUsername($player);

        # game maintenance checking
        $isMaintenance = $this->external_system->isGameApiMaintenance($this->getPlatformCode());
        if($isMaintenance){
            $sMethod = @$this->getAttributeInObject($this->rqSeamlessRequestData,'method');
            $method =  @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'method',$sMethod);
            $m = $this->generateStringMethod($method);
            $this->currentMethod = $m;
            # override maintenance if in the method whitelist
            if(in_array($this->currentMethod,self::METHOD_LIST_ALLOWED_IN_MAINTENANCE)){
                $isMaintenance = false;
            }
        }

        # save to response result first
        //$rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);
        $error = 'Game Api is not active';
        if ($this->external_system->isGameApiActive($this->getPlatformCode()) && !$isMaintenance) {
            $sMethod = @$this->getAttributeInObject($this->rqSeamlessRequestData,'method');
            $method =  @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'method',$sMethod);
            
                if(in_array((string)$method,self::API_ACTIONS)){
                    try{
                        $m = $this->generateStringMethod($method);
                        $this->currentMethod = $m;
                        return $this->$m();
                    }catch(\Exception $e){                
                        $error = 'Method not exist';                        
                    }
                }                
                $error = 'Invalid action';            
        }       

        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

        # to be response in game provider
        $cv = $this->getCommonVar();
        $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
        $this->rspSeamlessResponseData->responseData['result']['userId'] = null;
        $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
        $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = 0;
        $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_FAILED;
        $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
        $this->rspSeamlessResponseData->responseData['error'] = $error;
        $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

        return $this->outputStandardResponse(null,null);
    }

    /** 
     * Get Platform Code
     * 
     * @return int
    */
    public function getPlatformCode()
    {
        return BG_SEAMLESS_GAME_API;
    }
    public function setPlatformCode($apiId)
    {
        $this->game_platform_id = $apiId;
    }

    /**
     * Debit or Deduct in Player Wallet in our Database
     * 
     * @return class::CI_Output
     */
    public function debit()
    {
        $this->utils->debug_log(__METHOD__." BIGGAMING debit");

        $this->currentMethod = 'debit';

        # all common variable is in here
        $cv = $this->getCommonVar();
        $insufficient_balance = false;
        $isAlreadyExists = false;

        # get player details
        list($player_status, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($cv->cvplayer, $cv->cvapiid);
        $playerId = isset($player->player_id)?$player->player_id:null;
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;        
        
        # save to response result first, flag default to error
        $rId = $this->saveToResponseResult($this->currentMethod.'_initial',(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);
        $this->rqSeamlessRequestData->request_id = $rId;

        # get before balance
        $after_balance = $previous_balance = 0;
        $formattedBalance = $this->getProperBalance($previous_balance);

        if(! empty($player) || $playerId){

            //prepare data
            $order = $this->generateOrderData($this->rqSeamlessRequestData);
            
            $subOrders = $this->generateOrderDataByItem($this->rqSeamlessRequestData);
            if(empty($subOrders)){
                $this->utils->error_log("BIGGAMING SEAMLESS SERVICE: debit EMPTY SUBORDERS", 'subOrders', $subOrders,
                'rawData', $this->rqSeamlessRequestData);
            }
            
            //update balance, insert trans
            $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId,
                $player,
                $order, 
                $subOrders,
                &$insufficient_balance, 
                &$previous_balance, 
                &$after_balance, 
                &$isAlreadyExists,                     
                &$additionalResponse) {
                
                if(empty($order)){
                    return false;
                }

                # check if there is bet
                foreach($subOrders as $subOrder){
                    $isBetExist = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->game_platform_id, $subOrder['transaction_id'], 'bet');
                    if($isBetExist){
                        $betExist = false;
                        $this->utils->error_log("BIGGAMING SEAMLESS SERVICE: bet ORDER already EXIST", 'subOrder', $subOrder,
                        'isBetExist', $isBetExist
                        );
                        $isBetExist = true;
                        $isAlreadyExists = true;
                        return false;
                    }
                }

                $order['player_name'] = $player->username;
                list($success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($order, $previous_balance, $after_balance);
                $this->utils->debug_log('bermar debit', 
                'previous_balance', $previous_balance, 
                'after_balance', $after_balance, 
                'insufficient_balance', $insufficient_balance, 
                'isAlreadyExists', $isAlreadyExists, 
                'order', $order);
                
                if($isAlreadyExists){
                    return false;
                }

                if($insufficient_balance){
                    return false;
                }

                if(!$success){
                    return false;
                }	

                if(!$isTransactionAdded){
                    return false;
                }
                
                //save sub orders
                foreach($subOrders as $item){
                    $isAdded = $this->insertIgnoreTransactionRecordItem($item, $after_balance, $after_balance);
                    
                    if($isAdded===false){
                        $this->utils->error_log(__METHOD__.' ERROR SAVING SUBORDER',$item);
                        return false;
                    }
                }
                
                return true;				
                
            });

            if($trans_success){
                $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
                $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;
                $formattedBalance = $this->getProperBalance($after_balance);

                # to be response in game provider
                $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
                $this->rspSeamlessResponseData->responseData['result']['userId'] = $cv->cvuserid;
                $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
                $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = $formattedBalance;
                $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_SUCCESS;
                $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
                $this->rspSeamlessResponseData->responseData['error'] = null;
                $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;
    
                return $this->outputStandardResponse(null,$rId);
            }

            $error = 'ERROR please try again';
            if($insufficient_balance){
                $error = 'ERROR Insufficient balance.';
            }elseif($isAlreadyExists){
                $error = 'ERROR transaction is already exist';
            }
        }

        $formattedBalance = $this->getProperBalance($previous_balance);

        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

        # to be response in game provider
        $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
        $this->rspSeamlessResponseData->responseData['result']['userId'] = $cv->cvuserid;
        $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
        $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = 0;
        $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_FAILED;
        $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
        $this->rspSeamlessResponseData->responseData['error'] = $error;
        $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

        return $this->outputStandardResponse(null,$rId);
    }

    /**
     * Credit or Add in Player Wallet in our Database
     * 
     * @return class::CI_Output
     */
    public function credit()
    {
        $this->utils->debug_log(__METHOD__." BIGGAMING credit");
        sleep($this->delay_response);
        $this->currentMethod = 'credit';
        //$sMethod = @$this->getAttributeInObject($this->rqSeamlessRequestData,'method');
        //$method =  @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'method',$sMethod);
        
        # all common variable is in here
        $cv = $this->getCommonVar();
        $insufficient_balance = false;
        $isAlreadyExists = false;

        # get player details
        list($player_status, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($cv->cvplayer, $cv->cvapiid);
        $playerId = isset($player->player_id)?$player->player_id:null;
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;

        # save to response result first, flag default to error
        //$rId = $this->saveToResponseResult($this->currentMethod.'_initial',(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);
        //$this->rqSeamlessRequestData->request_id = $rId;
        $rId = null;
        
        # get before balance
        $after_balance = $previous_balance = 0;
        $formattedBalance = $this->getProperBalance($previous_balance);

        $betExist = true;

        if(! empty($playerInfo) || $playerId){

            //prepare data
            //$gameRecords = $this->generateInsertDataForNt($this->rqSeamlessRequestData, 'credit');      
            //$rawData = $this->rqSeamlessRequestData;

            //process refund excess initial deposit
            $order = $this->generateOrderData($this->rqSeamlessRequestData);

            $subOrders = $this->generateOrderDataByItem($this->rqSeamlessRequestData);
            if(empty($subOrders)){
                $this->utils->error_log("BIGGAMING SEAMLESS SERVICE: credit EMPTY SUBORDERS", 'subOrders', $subOrders,
                'rawData', $this->rqSeamlessRequestData);
            }
            
            //update balance, insert trans
            $trans_success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId,
                $player,
                $order, 
                $subOrders,
                &$insufficient_balance, 
                &$previous_balance, 
                &$after_balance, 
                &$isAlreadyExists, 
                &$betExist,                    
                &$additionalResponse) {
                
                if(empty($order)){
                    return false;
                }

                # check if there is bet
                foreach($subOrders as $subOrder){
                    $isBetExist = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->game_platform_id, $subOrder['transaction_id'], 'bet');
                    if(!$isBetExist){
                        $betExist = false;
                        $this->utils->error_log("BIGGAMING SEAMLESS SERVICE: credit ORDER DOES NOT EXIST", 'subOrder', $subOrder,
                        'isBetExist', $isBetExist
                        );
                        $isBetExist = false;
                        return false;
                    }
                }

                $order['player_name'] = $player->username;

                # credit needs to be processed per order, coz when they retry credit it could be per item
                foreach($subOrders as $subOrder){
                    $subOrder['player_name'] = $player->username;
                    list($success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($subOrder, $previous_balance, $after_balance);
                    $this->utils->debug_log('bermar credit', 
                    'previous_balance', $previous_balance, 
                    'after_balance', $after_balance, 
                    'insufficient_balance', $insufficient_balance, 
                    'isAlreadyExists', $isAlreadyExists, 
                    'subOrder', $subOrder);
                    if(isset($additionalResponse['bet_dont_exist']) && $additionalResponse['bet_dont_exist']==true){                    
                        $betExist = false;
                        return false;
                    }
                    
                    if($isAlreadyExists){
                        return false;
                    }
    
                    if(!$success){
                        return false;
                    }
    
                    if(!$isTransactionAdded){
                        return false;
                    }
                }

                /*
                list($success, $previous_balance, $after_balance, $insufficient_balance, $isAlreadyExists, $additionalResponse, $isTransactionAdded) = $this->debitCreditAmountToWallet($order, $previous_balance, $after_balance);
                $this->utils->debug_log('bermar credit', 
                'previous_balance', $previous_balance, 
                'after_balance', $after_balance, 
                'insufficient_balance', $insufficient_balance, 
                'isAlreadyExists', $isAlreadyExists, 
                'order', $order);

                if(isset($additionalResponse['bet_dont_exist']) && $additionalResponse['bet_dont_exist']==true){                    
                    $betExist = false;
                    return false;
                }
                
                if($isAlreadyExists){
                    return false;
                }

                if(!$success){
                    return false;
                }

                if(!$isTransactionAdded){
                    return false;
                }

                //save sub orders                
                foreach($subOrders as $item){
                    $isAdded = $this->insertIgnoreTransactionRecordItem($item, $after_balance, $after_balance);
                    
                    if($isAdded===false){
                        $this->utils->error_log(__METHOD__.' ERROR SAVING SUBORDER',$item);
                        return false;
                    }
                }*/
                
                return true;				
                
            });

            if($trans_success){
                $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
                $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;
                $formattedBalance = $this->getProperBalance($after_balance);

                # to be response in game provider
                $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
                $this->rspSeamlessResponseData->responseData['result']['userId'] = $cv->cvuserid;
                $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
                $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = $formattedBalance;
                $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_SUCCESS;
                $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
                $this->rspSeamlessResponseData->responseData['error'] = null;
                $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;
                return $this->outputStandardResponse(null,$rId);
            }

            $error = 'ERROR please try again';
            if($insufficient_balance){
                $error = 'ERROR Insufficient balance.';
            }elseif($isAlreadyExists){
                $error = 'ERROR transaction is already exist';
            }

            if(!$betExist){
                $error = 'ERROR Bet does not exist';
            }

        }       


        $formattedBalance = $this->getProperBalance($after_balance);

        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

        # to be response in game provider
        $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
        $this->rspSeamlessResponseData->responseData['result']['userId'] = $cv->cvuserid;
        $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
        $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = 0;
        $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_FAILED;
        $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
        $this->rspSeamlessResponseData->responseData['error'] = $error;
        $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

        return $this->outputStandardResponse(null,$rId);
    }

    /**
     * ADD/DEDUCT in Player Wallet in our Database, use for fishing
     * 
     * @return class::CI_Output
     */
    public function transfer()
    {
        $this->currentMethod = 'transfer';
        $sMethod = @$this->getAttributeInObject($this->rqSeamlessRequestData,'method');
        $method =  @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'method',$sMethod);
        # all common variable is in here
        $cv = $this->getCommonVar();

        # get player id
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;

        $playerId = $this->gameApiSysLibObj->getPlayerIdByGameUsername($cv->cvplayer);

        # save to response result first, flag default to error
        $rId = $this->saveToResponseResult($this->currentMethod.'_initial',(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_ERROR]);
        $this->rqSeamlessRequestData->request_id = $rId;

        $serverToken = $this->gameApiSysLibObj->getPlayerTokenByGameUsername($cv->cvplayer);
        $playerInfo = $this->common_token->getPlayerInfoByToken($serverToken,true,true,$this->tokenTimeComparison,$this->newTokenValidity);
        $playerCurrentActivetoken = isset($playerInfo['token']) ? $playerInfo['token'] : null;
        $beforeBalance = $this->getPlayerBalanceWithLock($playerId);
        $formattedBalance = $this->getProperBalance($beforeBalance);

        sleep($this->gameApiSysLibObj->callbackSleep);

        # check if method is POST in the request
        if(! $this->isPostMethod()){
            // TODO
            //return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"http method is not correct");
        }

        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

        # to be response in game provider
        $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
        $this->rspSeamlessResponseData->responseData['result']['userId'] = $cv->cvuserid;
        $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
        $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = 0;
        $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_FAILED;
        $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
        $this->rspSeamlessResponseData->responseData['error'] = 'ERROR currently not available';
        $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

        return $this->outputStandardResponse(null,$rId);

        if(! empty($playerInfo)){
            $playerName = isset($playerInfo['username']) ? $playerInfo['username'] : null;

            # check if the token have player id
            if($playerId){
                // TODO
                // $checkpoint = $this->validityCheckpoint($formattedBalance,$cv->cvwamounttowithdraw,$playerName,$playerCurrentActivetoken,$playerId,$cv->cvorders,$rId,true);

                # request have error
                // if($checkpoint){
                //     return $this->outputStandardResponse(null,$rId);
                // }

                #decrement player balance
                $data = $this->generateInsertDataForNt($this->rqSeamlessRequestData, 'transfer');
                $this->CI->utils->debug_log('BIGGAMING SEAMLESS '. __METHOD__, 'data', $data);
                $extra = ['transaction_id'=>@$data[0]['transaction_id']];
                $amount = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'amount');
                if($amount < 0){
                    $decrementResult = $this->doDeductWithTransactionChecking($playerId,abs($amount),$data,$extra);
                }else{
                    $decrementResult = $this->doIncrementWithTransactionChecking($playerId,abs($amount),$data,$extra);
                }

                # after balance here
                $afterBalance = array_key_exists('after_balance',$decrementResult) ? $decrementResult['after_balance'] : 0;
                $formattedBalance = $this->getProperBalance($afterBalance);

                if(array_key_exists('is_trans_success',$decrementResult) && $decrementResult['is_trans_success']){
                    $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
                    $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;

                    $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
                    $this->rspSeamlessResponseData->responseData['result'] = $formattedBalance;
                    $this->rspSeamlessResponseData->responseData['error'] = null;
                    $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;
                    
                    return $this->outputStandardResponse(null,$rId);
                }else{
                        # even it's error, we still need to check if the deposit request is already exist
                        if(array_key_exists('is_transaction_already_exist',$decrementResult) && $decrementResult['is_transaction_already_exist']){
                            # for internal
                            $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
                            $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

                            $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
                            $this->rspSeamlessResponseData->responseData['result'] = $formattedBalance;
                            $this->rspSeamlessResponseData->responseData['error'] = null;
                            $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

                            return $this->outputStandardResponse(null,$rId);
                        }else{
                            # game provider can retry
                            # for internal
                            $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
                            $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

                            $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
                            $this->rspSeamlessResponseData->responseData['result'] = $formattedBalance;
                            $this->rspSeamlessResponseData->responseData['error'] = null;
                            $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

                            return $this->outputStandardResponse(null,$rId);
                        }
                }
            }
            // TODO
            //return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");

        }
        // TODO
        //return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");
    }

    /** 
     * Refund the Player Bet or Win in the Player Wallet
     * 
     * 
     * @return boolean
    */
    public function refund()
    {
        return false;
    }

    /** 
     * This method is used to get the balance of the player's account.
    */
    protected function balance()
    {
        $this->currentMethod = 'balance';
        # all common variable is in here
        $cv = $this->getCommonVar();
        # get player id
        $playerId = !is_null($cv->cvplayer) ? $this->gameApiSysLibObj->getPlayerIdByGameUsername($cv->cvplayer) : null;

        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;

        # save to response result first
        $rId = $this->saveToResponseResult($this->currentMethod.'_initial',(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);        
    
        if(! is_null($playerId)){
            //$playerCurrentToken = $this->gameApiSysLibObj->getPlayerTokenByGameUsername($cv->cvplayer);

            $balance = $this->getPlayerBalanceWithLock($playerId);
            $formattedBalance = $this->getProperBalance($balance);

            # for internal
            $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
            $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;

                # to be response in game provider
            $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
            $this->rspSeamlessResponseData->responseData['result'] = $formattedBalance;
            $this->rspSeamlessResponseData->responseData['error'] = null;
            $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

            return $this->outputStandardResponse(null,$rId);
        }

        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

        # to be response in game provider
        $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
        $this->rspSeamlessResponseData->responseData['result']['userId'] = $cv->cvuserid;
        $this->rspSeamlessResponseData->responseData['result']['sn'] = $this->gameApiSysLibObj->sn;
        $this->rspSeamlessResponseData->responseData['result']['availableAmount'] = 0;
        $this->rspSeamlessResponseData->responseData['result']['orderResult'] = self::STATUS_CODE_FAILED;
        $this->rspSeamlessResponseData->responseData['result']['tranId'] = null;
        $this->rspSeamlessResponseData->responseData['error'] = "Invalid player";
        $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

        return $this->outputStandardResponse(null,null);
    }

    protected function operatorOrderStatus()
    {
        $this->currentMethod = 'operatorOrderStatus';

        $this->utils->debug_log(__METHOD__." BIGGAMING ");

        $sn = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'sn');
        $params = @$this->getAttributeInObject($this->rqSeamlessRequestData,'params');

        # all common variable is in here
        $cv = $this->getCommonVar();
        $apiId = $cv->cvapiid;

        # get player details
        list($player_status, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($cv->cvplayer, $cv->cvapiid);
        $playerId = isset($player->player_id)?$player->player_id:null;
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;        

        # save to response result first
        $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);

        $orderIdsStatus = [];
        if($sn ==  $this->gameApiSysLibObj->sn){
            # for internal
            $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
            $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;
            $result = [];
            if(isset($params->orderIds) && !empty($params->orderIds) && is_array($params->orderIds)){

                /* foreach orderid check the status of bet and settlement
                * Order status
                * 0: Order does not exist
                * 1: new order(Unsettlement)
                * 2: Win(Settled)
                * 3: Tie(Settled)
                * 4: Lost(Settled)                
                */
                $this->utils->debug_log(__METHOD__." BIGGAMING orderIds", $params->orderIds);
                foreach($params->orderIds as $orderId){
                    $debitUniqueId = $this->generateUniqueFromOrderId($orderId, 'debit');
                    $creditUniqueId = $this->generateUniqueFromOrderId($orderId, 'credit');
                    $bet = $this->common_seamless_wallet_transactions->getTransactionRowArray($apiId,$debitUniqueId);
                    $settlement = $this->common_seamless_wallet_transactions->getTransactionRowArray($apiId,$creditUniqueId);
                    $this->utils->debug_log(__METHOD__." BIGGAMING", 'bet', $bet, 'settlement', $settlement);
                    

                    //no settlement records means new
                    if(!empty($settlement)){
                        //analyze settlement datas
                        $extra = isset($settlement['extra_info'])?json_decode($settlement['extra_info'],true):null;
                        $this->utils->debug_log(__METHOD__." BIGGAMING", 'extra', $extra);
                        $orderStat = self::ORDER_STATUS_NEW_ORDER;
                        if(!empty($extra) || !isset($extra['params']['orders']) || is_array($extra['params']['orders'])){
                            foreach($extra['params']['orders'] as $key => $settlementOrder){
                                if($orderId==$settlementOrder['orderId']){
                                    $orderStat = $settlementOrder['orderStatus'];
                                }
                            }
                        }

                        if($orderStat>=0){                            
                            $orderIdsStatus[] = ['orderId'=>$orderId, 'status'=>$orderStat];                            
                        }
                        
                        continue;
                    }else{
                        //no transaction records means does not exist
                        if(empty($bet)){                       
                            $orderIdsStatus[] = ['orderId'=>$orderId, 'status'=>self::ORDER_STATUS_NOT_EXIST];
                        }else{
                            $orderIdsStatus[] = ['orderId'=>$orderId, 'status'=>self::ORDER_STATUS_NEW_ORDER];
                        }
                        continue;
                    }
                }


            }

            # to be response in game provider
            $this->utils->debug_log(__METHOD__." BIGGAMING orderIdsStatus", $orderIdsStatus);
            $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
            $this->rspSeamlessResponseData->responseData['result'] = $orderIdsStatus;
            $this->rspSeamlessResponseData->responseData['error'] = null;
            if(empty($orderIdsStatus)){
                $this->rspSeamlessResponseData->responseData['error'] = 'ERROR';
            }            
            $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

            return $this->outputStandardResponse(null,$rId);
        }else{
            # for internal
            $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
            $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

            # to be response in game provider
            $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
            $this->rspSeamlessResponseData->responseData['result'] = [];
            $this->rspSeamlessResponseData->responseData['error'] = "ERROR invalid customer identity";
            $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

            return $this->outputStandardResponse(null,$rId);
        }
    }

    /** 
     * Only Available in Chinese Document 
     * 接口说明
     * 该接口用于我方自助检查免转接入方的回调服务可用性，访问频率为每3秒钟执行一次，如果连续10次得不到预期的结果或者请求超时，我方游戏会自动进入维护状态
     * 请求参数: 无
     * 响应参数
    */
    protected function operatorPing()
    {
        $this->currentMethod = 'operatorPing';
            # all common variable is in here
            $cv = $this->getCommonVar();

            # for internal
            $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
            $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;

            # to be response in game provider
            $this->rspSeamlessResponseData->responseData['id'] = $cv->cvid;
            $this->rspSeamlessResponseData->responseData['result'] = strtotime("now") * 1000;;
            $this->rspSeamlessResponseData->responseData['error'] = null;
            $this->rspSeamlessResponseData->responseData['jsonrpc'] = $this->gameApiSysLibObj->jsonrpc;

            return $this->outputStandardResponse(null,null);
    }



    /** UTILS */

    /** 
     * Get Proper format of balance
     * 
     * @param int $balance
     * 
     * @return int
    */
    public function getProperBalance($balance)
    {
        $playerBalance =  floatval(number_format($this->gameApiSysLibObj->round_down($balance),6,'.',''));

        return $playerBalance;
    }


    /**
     * Output error
     * 
     * prefix with "rsp" meaning response, to avoid property conflict
     * 
     * @param string $statusCode
     * @param int $rId the response result to be updated
     * @param int $headerCode
     * @param int $responseCode
     * @param string $responseMessage
     * 
     * @return object
     */
    public function outputError($statusCode,$rId,$headerCode=400,$responseCode=null,$responseMessage=null)
    {
        $this->rspSeamlessResponseData->rspStatusCode = $statusCode;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = $headerCode;

        $this->rspSeamlessResponseData->responseData['serverTransactionRef'] = $this->utils->getRequestId();

        if(! isset($this->rspSeamlessResponseData->responseData['responseCode']) && is_null($responseCode)){
            $this->rspSeamlessResponseData->responseData['responseCode'] = 100;
        }else{
            $this->rspSeamlessResponseData->responseData['responseCode'] = $responseCode;
        }

        if(! isset($this->rspSeamlessResponseData->responseData['responseMessage']) && is_null($responseMessage)){
            $this->rspSeamlessResponseData->responseData['responseMessage'] = "Unknown Error";
        }else{
            $this->rspSeamlessResponseData->responseData['responseMessage'] = $responseMessage;
        }

        return $this->outputStandardResponse(null,$rId);
    }

    /** 
     * Get Common Variable From game provider Request
     * 
     * prefixed with "cv" meaning common variable
     * 
     * returned Data
     * player - player game name
     * currency - the currency of player
     * game - the game playing by the player
     * session - the current session of player
     * 
     * 
     * @return object $data
    */
    public function getCommonVar()
    {

        $this->utils->debug_log(__METHOD__." BIGGAMING");

        $player = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqPlayer');
        $id = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqId');
        $orders = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqOrders');
        $userid = $this->rqSeamlessRequestData->rqUserId;
        $apiId = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqApiId');

        // # for withdraw
        $cvwAmountToWithdraw = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqwAmountToWithdraw');

        $data = new stdClass();
        $data->cvplayer = $player;
        $data->cvid = $id;
        $data->cvwamounttowithdraw = $cvwAmountToWithdraw;
        $data->cvorders = $orders;
        $data->cvapiid = $apiId;
        $data->cvuserid = $userid;

        return $data;
    }

    /**
     * Set Common Variable from game provider Request
     * 
     * prefix with "nt" meaning netent, to avoid property conflict
     * 
     * @param int $apiId
     * @param sting $player
     * 
     * @return void
     */
    public function setCommonVar($apiId,$player)
    {
            $this->rqSeamlessRequestData->rqPlayer = !empty($player) ? $player : null;
            $this->rqSeamlessRequestData->rqId =  @$this->getAttributeInObject($this->rqSeamlessRequestData,'id');
            $this->rqSeamlessRequestData->rqOrders = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'orders');
            $this->rqSeamlessRequestData->rqUserId = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'userId');
            $this->game_platform_id =  $this->rqSeamlessRequestData->rqApiId = !empty($apiId) ? $apiId : null;

            // # for withdraw
            $this->rqSeamlessRequestData->rqwAmountToWithdraw = @$this->getAttributeInObject($this->rqSeamlessRequestData->params,'amount');
    }

    /**
     * Output Standard Response
     * 
     * @param object $ntObject
     * @param int $rId the response result id to be updated
     * 
     * @return object
     * 
     */
    public function outputStandardResponse($ntObject=null,$rId=null)
    {
        $this->loadModel(['common_seamless_error_logs']);
        $ntObject = is_null($ntObject) ? $this->rspSeamlessResponseData : $ntObject;
        $statusHeaderCode = $this->getAttributeInObject($ntObject,'rspStatusHeaderCode',400);
        $statusCode = $this->getAttributeInObject($ntObject,'rspStatusCode');

        # all common variable is in here
        $cv = $this->getCommonVar();
        $cvUsername = $cv->cvplayer;
        $playerId = !is_null($cvUsername) ? $this->gameApiSysLibObj->getPlayerIdByGameUsername($cvUsername) : null;

        # if all is good
        if($statusCode == self::STATUS_CODE_OK){
            $flag = Response_result::FLAG_NORMAL;
        }else{
            $flag = Response_result::FLAG_ERROR;
        }
        $this->utils->debug_log(__METHOD__.' BIGGAMING outputStandardResponse',$ntObject->responseData);

        $jContent = is_array($ntObject->responseData) ? json_encode($ntObject->responseData) : $ntObject->responseData;

        # if error occur, save it to common_seamless_error_logs table
        $lastInsertIdOfSe = false;
        if($statusCode != self::STATUS_CODE_OK){
            /*$this->rqSeamlessRequestData->request_id = $rId;
            $gamePlatformId = $this->getPlatformCode();
            $response_result_id = $rId;
            $request_id = $this->utils->getRequestId();
            $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
            $now = $this->utils->getNowForMysql();
            $commonSeamlessErrorDetails = json_encode([
                'response' => $ntObject->responseData,
                'request' => $this->rqSeamlessRequestData
            ]);

            $insertData = [
                'game_platform_id' => $gamePlatformId,
                'response_result_id' => $response_result_id,
                'request_id' => $request_id,
                'elapsed_time' => $elapsed,
                'error_date' => $now,
                'extra_info' => $commonSeamlessErrorDetails
            ];

            $lastInsertIdOfSe = $this->saveSeamlessError($insertData);*/
        }
        else {
            if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration')) {
                $this->sendToFastTrack();
            }
        }

        $this->utils->debug_log(__METHOD__.' with response data of: >>>>>>>>',$jContent,'last insert id of seamless error, if false, meaning no error in this request',$lastInsertIdOfSe,'response result id to be updated',$rId);

        $header = [];

        $this->utils->debug_log(__METHOD__.' $ntObject->responseData',$ntObject->responseData);

        //save new response result
		$fields = [
			'player_id'		=> $playerId,
		];
        $cost = intval($this->utils->getExecutionTimeToNow()*1000);
        $extra = $this->requestHeaders;
        $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id, 
        	$flag, 
        	$this->currentMethod, 
        	json_encode($this->rqSeamlessRequestData), 
        	json_encode($ntObject->responseData), 
        	$statusHeaderCode, 
        	null, 
			is_array($extra)?json_encode($extra):$extra,
			$fields,
			false,
			null,
			$cost
        );

        $rId = null;

        return $this->outputHttpResponse($ntObject->responseData,$statusHeaderCode,$header,$rId,$playerId,$flag);
    }

    /**
     * Generate Array Data for inserting into common wallet table
     * 
     * @param object $requestData
     * 
     * @return array
     */
    public function generateInsertDataForNt($requestData, $method)
    {
        $this->utils->debug_log('BIGGAMING '.__METHOD__,$requestData,$method);

        //initialize variable
        $gameRecords = [];
        $extraInfo = json_encode([]);
        if(is_object($requestData)){
            $requestData->isRefunded =  property_exists($requestData,'isRefunded') ? true : false;
            $extraInfo = json_encode($requestData);
        }elseif(is_array($requestData)){
            $requestData['isRefunded'] =  property_exists($requestData,'isRefunded') ? true : false;
            $extraInfo = json_encode($requestData);
        }

        # all common variable is in here
        $cv = $this->getCommonVar();
        $apiId = $cv->cvapiid;
        $amount = @$this->getAttributeInObject($requestData->params,'amount');
        $transactionType = $this->generateTransactionType($this->currentMethod);

        $orders = @$this->getAttributeInObject($requestData,'rqOrders');
        if(is_array($orders) && count($orders) > 0){

            foreach($orders as $order){
                $externalUniqueId = $this->generateUniqueFromOrderId((string)$order->orderId,(string)$method);
                $now = (new DateTime())->format('Y-m-d H:i:s');
                $tempOrderItem = [
                    'game_platform_id' => $apiId,
                    'amount' => $order->amount,
                    'game_id' => $order->gameId,
                    'transaction_type' => $transactionType,
                    'status' => self::STATUS_OK,
                    'response_result_id' => $this->utils->getRequestId(),
                    'external_unique_id' => $externalUniqueId,
                    'extra_info' => $extraInfo,
                    'start_at' => $now,
                    'end_at' => $now,
                    'transaction_id' => $externalUniqueId,
                    'round_id' => $order->orderId,
                    'player_id' => $this->player->player_id
                ];

                $gameRecords[] = $tempOrderItem;

            }
        }

        $this->processGameRecords($gameRecords);
        $this->utils->debug_log('BIGGAMING '.__METHOD__,$gameRecords);
        return $gameRecords;
    }

    public function generateOrderData($rawData){
        # all common variable is in here
        $cv = $this->getCommonVar();
        $apiId = $cv->cvapiid;

        $transactionType = $this->generateTransactionType($this->currentMethod);        

        $extraInfo = json_encode($rawData);

        $totalValidBetAmount = 0;
        $totalSecurityDepositAmount = 0;

        $gameId = '';
        $gameId = '';
        $dateObj = new DateTime();
        $startDataTime = $endDataTime = $dateObj->format('Y-m-d H:i:s');                
        $orderIds = [];
        
        foreach($rawData->params->orders as $row){
            $orderIds[] = $row->orderId;
            $gameId =  $row->gameId;
            if($this->currentMethod=='debit'){
                if(isset($row->orderTime)){
                    if($startDataTime > $row->orderTime){
                        $startDataTime = $row->orderTime;
                    }
                    if($endDataTime < $row->orderTime){
                        $endDataTime = $row->orderTime;
                    }
                }                
            }else{
                if(isset($row->lastUpdateTime)){
                    if($startDataTime > $row->lastUpdateTime){
                        $startDataTime = $row->lastUpdateTime;
                    }
                    if($endDataTime < $row->lastUpdateTime){
                        $endDataTime = $row->lastUpdateTime;
                    }
                }
                $totalValidBetAmount += (isset($row->validAmount)?$row->validAmount:0);
                $totalSecurityDepositAmount += (isset($row->orderAmount)?$row->orderAmount:0);
            }
            
        }
        if(empty($orderIds)){
            return false;
        }
        //$playerId = $this->player->player_id;
        $md5Id = $this->generateMd5FromOrderId($orderIds);
        $externalUniqueId = $this->currentMethod.'-'.$md5Id;        

        $tempOrderItem = [
            'game_platform_id' => $apiId,
            'amount' => $rawData->params->amount,
            'game_id' => $gameId,
            'transaction_type' => $transactionType,
            'status' => self::STATUS_OK,
            'response_result_id' => $this->utils->getRequestId(),
            'external_unique_id' => $externalUniqueId,
            'extra_info' => $extraInfo,
            'start_at' => $startDataTime,
            'end_at' => $endDataTime,
            'transaction_id' => $md5Id,
            'round_id' => $md5Id,
            'player_id' => $this->player->player_id
        ];

        return $tempOrderItem;
    }  
    
    private function generateOrderDataByItem($rawData){
        $orders = [];
    
        # all common variable is in here
        $cv = $this->getCommonVar();
        $apiId = $cv->cvapiid;
    
        $orderIds = [];
        
        foreach($rawData->params->orders as $row){
            $orderIds[] = $row->orderId;            
        }
        
        //$playerId = $this->player->player_id;
        $dateObj = new DateTime();
        $startDataTime = $endDataTime = $dateObj->format('Y-m-d H:i:s');        
        $transactionType = $this->generateTransactionType($this->currentMethod);  
        $md5Id = $this->generateMd5FromOrderId($orderIds);
        $externalUniqueId = $this->currentMethod.'-'.$md5Id;        
        $extraInfo = json_encode($rawData);
    
        foreach($rawData->params->orders as $row){
            if($this->currentMethod=='debit'){
                if(isset($row->orderTime)){
                    $startDataTime = $row->orderTime;
                    $endDataTime = $row->orderTime;
                }
            }else{
                if(isset($row->lastUpdateTime)){
                    $startDataTime = $row->lastUpdateTime;
                    $endDataTime = $row->lastUpdateTime;
                }
            }

            $amount = isset($row->amount)?$row->amount:0;
            $orderAmount = isset($row->orderAmount)?$row->orderAmount:0;

            $orderUniqueId = $this->currentMethod.'-'.$row->orderId;

            $tempOrderItem = [
                'game_platform_id' => $apiId,
                'amount' => $row->amount,
                'game_id' => $row->gameId,
                'transaction_type' => $transactionType,
                'status' => self::STATUS_OK,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' => $orderUniqueId,
                'extra_info' => $extraInfo,
                'start_at' => $startDataTime,
                'end_at' => $endDataTime,
                'transaction_id' => $row->orderId,
                'round_id' => $md5Id,
                'player_id' => $this->player->player_id,
                //'bet_amount' => $orderAmount,
                //'result_amount' => $orderAmount-$amount,
            ];            

            if($this->currentMethod=='debit'){
                $tempOrderItem['bet_amount'] = abs($amount);
                $tempOrderItem['result_amount'] = $amount;
            }else{
                $tempOrderItem['bet_amount'] = abs($orderAmount);
                $tempOrderItem['result_amount'] = $amount-abs($orderAmount);
                $tempOrderItem['round_id'] = $row->orderId;
            }
    
            $orders[] = $tempOrderItem;
        }
    
        return $orders;
    }    
    
    private function generateMd5FromOrderId($orderIds){
        
        $this->utils->debug_log(__METHOD__.' BIGGAMING $orderIds',$orderIds);

        $md5 = null;
        asort($orderIds);
        $orderIdsString = implode('-', $orderIds);        
        $md5 = md5($orderIdsString);        
        return $md5;
    }
    
    private function generateUniqueFromOrderId(String $orderId, String $method){
        return $method.'-'.$orderId;
    }

    private function generateGameId(Array $orders){        
        $gameIds = '';
        foreach($orders as $order){
            $gameIds.= $order->gameId.'_';
        }

        if(empty($gameIds)){
            return false;
        }
        $gameIds = trim($gameIds,'_');
        return $gameIds;
    }

    /**
     * Generate transaction type
     * 
     * @param string $type
     * @return string
     */
    public function generateTransactionType($type)
    {
        switch($type)
        {
            case "debit":
                $type = self::TRANSACTION_BET;
            break;
            case "credit":
                $type = self::TRANSACTION_WIN;
            break;
            // case self::ACTION_REFUND:
            //     $type = self::TRANSACTION_REFUND;
           // break;
            default:
                $type = null;
            break;
        }

        return $type;
    }

     /** 
     * Check if Player is Allowed to bet
     * - if bet is greater than player balance
     * - if player is blocked
     * 
     * * Note: if the return is false, meaning no error, the request is passed in validity checkpoint
     * 
     * 
     * @param int $before_balance
     * @param int $betAmount
     * @param string $playerName
     * @param string $playerCurrentActivetoken
     * @param int $playerId
     * @param int $transactionId check if ARRAY
     * @param boolean|false $is_bet
     * @param int $rId the response result id to be updated
     * 
     * @return mixed
    */
    public function validityCheckpoint($before_balance,$betAmount,$playerName,$playerCurrentActivetoken,$playerId,$transactionId,$rId,$is_bet=false)
    {
        # for BET
        // if($is_bet){

        //     $isAllowedToBet = $this->utils->compareResultFloat($before_balance,">=",$betAmount);
        //     # checkpoint 1.)  check if current balance is greater than bet
        //     if(! $isAllowedToBet){

        //         # for internal
        //         $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
        //         $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

        //         # to be response in game provider
        //         $this->setMandatoryResponseAttr(self::STATUS_CODE_NOT_ENOUGH_MONEY,$playerCurrentActivetoken,$before_balance);

        //         return $this->rspSeamlessResponseData->responseData['responseCode'];
        //     }
        // }

        // $isPlayerBlockedAll = $this->player_model->isBlocked($playerId); # block in player table

        // if($this->gameApiSysLibObj->isBlocked($playerName) || $isPlayerBlockedAll){
        //     return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"Player is blocked");
        // }

        return false;
    }

    /**
     * Generate method string for the request
     * 
     * @param string $action
     * 
     * @return string $method
     */
    public function generateStringMethod($action)
    {

        switch($action){
            case self::ACTION_GET_BALANCE:
                $method = 'balance';
            break;
            case self::ACTION_WITHDRAW:
                $method = 'debit';
            break;
            case self::ACTION_OPERATOR_PING:
                $method = 'operatorPing';
            break;
            case self::ACTION_DEPOSIT:
                $method = 'credit';
            break;
            case self::ACTION_OPERATOR_USER_TRANSFER:
                //$method = 'transfer';
                $method = null;
            break;
            case self::ACTION_OPERATOR_ORDER_STATUS:
                $method = 'operatorOrderStatus';
            break;
            default:
                $method = null;
             break;
        }

        return $method;
    }

	public function getPlayerByGameUsername($gameUsername, $apiId){        
        $this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (getPlayerByGameUsername)", $gameUsername, $apiId);

		$player = $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $apiId);		 
		
		if(!$player){		
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
    }

	public function debitCreditAmountToWallet($request, &$previousBalance, &$afterBalance){

		$this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet)", $request);

		//initialize params
		$player_id			= $request['player_id'];		
		$transfer_id 		= $request['transaction_id'];
		$amount 			= abs($request['amount']);
		
		//initialize response
		$success = false;
		$isValidAmount = true;		
		$insufficientBalance = false;
		$isAlreadyExists = false;
		
		$isTransactionAdded = false;
		$additionalResponse	= [];

		$isValidAmount 		= $this->isValidAmount($request['amount']);

		$mode = 'debit';
		if($request['amount']>=0){
			$mode = 'credit';
		}

		$this->transfer_ids[] = $transfer_id;

        if(!$isValidAmount){
            $this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) isValidAmount", $isValidAmount);
            $success = false;
            $isValidAmount = false;
            return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
        }

        //get and process balance
        $get_balance = $this->getPlayerBalance($request['player_name']);

		if($amount<>0){			
			
			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}
				
			}else{
				$this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}	

            # check if already exist
            $externalUniqueId = isset($request['external_unique_id'])?$request['external_unique_id']:null;            
            if(empty($externalUniqueId) || $this->common_seamless_wallet_transactions->isTransactionExist($this->game_platform_id,$externalUniqueId)){
                return array(false, $previousBalance, $afterBalance, $insufficientBalance, true, $additionalResponse, $isTransactionAdded);
            }
            
			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($request, $previousBalance, $afterBalance);

			if($isAdded===false){
				$this->utils->error_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) isAdded already", $isAdded, $request);
				$isAlreadyExists = true;		
                $isTransactionAdded = true;			
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}				
			
			if($mode=='debit'){
				$success = $this->wallet_model->decSubWallet($player_id, $this->game_platform_id, $amount);	
			}else{
				$success = $this->wallet_model->incSubWallet($player_id, $this->game_platform_id, $amount);
			}	

		}else{
			$this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) amount=0", $request, $amount);
			
            if($get_balance!==false){
                $previousBalance = $afterBalance = $get_balance;
                $success = true;

                //insert transaction
                $isAdded = $this->insertIgnoreTransactionRecord($request, $previousBalance, $afterBalance);
                if($isAdded===false){
                    $success = false;
                    $this->utils->error_log("BIGGAMING SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->params);
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
                $isTransactionAdded = true;
            }else{
                $success = false;
            }
		}	

		return array($success, 
						$previousBalance, 
						$afterBalance, 
						$insufficientBalance, 
						$isAlreadyExists, 						
						$additionalResponse,
						$isTransactionAdded);
	}

	public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
        $result = false;
        unset($data['player_name']);
        $data['before_balance'] = $previous_balance;
        $data['after_balance'] = $after_balance;
        $this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (insertIgnoreTransactionRecord)", $data);
		$result = $this->common_seamless_wallet_transactions->insertIgnoreRow($data);

        $this->transaction_for_fast_track = null;
        if($result) {
            $this->transaction_for_fast_track = $data;
            $this->transaction_for_fast_track['id'] = $this->CI->common_seamless_wallet_transactions->getLastInsertedId();
        }
		return $result;
	}

	public function insertIgnoreTransactionRecordItem($data, $previous_balance, $after_balance){
        $result = false;
        unset($data['player_name']);
        $data['before_balance'] = $previous_balance;
        $data['after_balance'] = $after_balance;
        $this->utils->debug_log("BIGGAMING SEAMLESS SERVICE: (insertIgnoreTransactionRecord)", $data);
		$result = $this->common_seamless_wallet_transactions->insertIgnoreRow($data);
		return $result;
	}

	public function getPlayerBalance($playerName){
		$get_bal_req = $this->gameApiSysLibObj->queryPlayerBalance($playerName);
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

	public function isValidAmount($amount){
		return is_numeric($amount);
	}

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $this->utils->debug_log("BG: (sendToFastTrack) transaction_for_fast_track", $this->transaction_for_fast_track);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->gameApiSysLibObj->getPlatformCode(), $this->transaction_for_fast_track['game_id']);
        $betType = null;
        switch($this->transaction_for_fast_track['transaction_type']) {
            case 'bet':
                $betType = 'Bet';
                break;
            case 'lose':
            case 'win':
                $betType = 'Win';
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
            "amount" => (float) abs($this->transaction_for_fast_track['amount']),
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  'THB',
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  strval($this->transaction_for_fast_track['round_id']),
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->transaction_for_fast_track['player_id'],
            "vendor_id" =>  strval($this->gameApiSysLibObj->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->gameApiSysLibObj->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amount']) : 0,
        ];

        $this->utils->debug_log("BG: (sendToFastTrack) data", $data);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

    /** 
     * Save Request to Response result
     * 
     * @return int the last insert id
    */
    protected function saveToResponseResult($request_method, $request_params = null, $extra = null, $fields = [], $response = [], $http_status_code = 200, $flag = 1)
    {
        $headers = getallheaders();

        if (empty($extra)) {
            $extra = is_array($headers) ? json_encode($headers) : $headers;
        }

        $data = is_array($request_params) ? json_encode($request_params) : $request_params;
        $response = is_array($response)?json_encode($response):$response;

        $lastInsertId = $this->CI->response_result->saveResponseResult(
            $this->getSysObjectGamePlatformId(), #1
            $flag,#2
            $request_method,#3
            $data,#4
            $response,#5 response
            $http_status_code,#6
            null,#7
            $extra,#8
            $fields #9
        );

        $this->generatedResponseResultId = $lastInsertId;

        return $lastInsertId;
    }


}
