<?php

if(! defined('BASEPATH')){
    exit('No Direct script access allowed');
}

require_once dirname(__FILE__) . '/Abstract_seamless_service_game_api.php';
require_once dirname(__FILE__) . '/../../../submodules/game-lib/game_platform/common_seamless_utils.php';

/** 
 ** Variable Prefix Meaning
 * cv - common variable/s
 * cvw - common variable/s for withdraw
 * rsp - response variable/s
 * rq - request variable/s
 * rqw - request variables/ in withdraw
 *
*/
class Netent_seamless_service_api extends Abstract_Seamless_Service_Game_Api
{

    use common_seamless_utils;

    /**  Success status Code */
    const STATUS_CODE_OK = 'OK';
    const STATUS_CODE_ERROR = 'ERROR';

    /** 
     * API Response Code in game provider
     * 
     * 0 - Success
     * 1 - Not enough money in player account. If this code is sent, then the response must contain the mandatory parameters, see 'Response Data' for each request.
     * 2 - Invalid/illegal currency. If this code is sent, then the response must contain the mandatory parameters, see 'Response Data' for each request.
     * 3 - Negative deposit. If this code is sent, then the response must contain the mandatory parameters, see 'Response Data' for each request.
     * 4 - Negative withdraw. If this code is sent, then the response must contain the mandatory parameters, see 'Response Data' for each request.
     * 6 - Player limit exceeded. If this code is sent, then the response must contain the mandatory parameters, see 'Response Data' for each request.
     * 7 - Invalid server token exception.
     * 8 - Reserved for future use.
     * 9 - Reserved for future use.
     * 99 - Retry exception. Used for deposit requests.
     * 100 - Unknown error. The message in the responseMessage parameter indicates the reason for the error.
     * 990 - Used for withdraw requests. If this code is sent, then the response must contain the mandatory parameters. No game outcome, no further gameplay is allowed, the game client is inactivated. Example use case: Fraud, session terminated, gaming limit reached, Server down. If this code is sent, then the message in the responseMessage parameter will be displayed to the player. Message format is according to ISO 8859-1 standard (Latin-1). Example message displayed to player: "Player limit is exceeded".
     * 991 - Used for withdraw requests. If this code is sent, then the response must contain the mandatory parameters. No game outcome, further gameplay is allowed, game client is still active. Example use case: The player is out of funds. If this code is sent, then the message in the responseMessage parameter will be displayed to the player. Message format is according to ISO 8859-1 standard (Latin-1). Example message displayed to player: “Please deposit to be able to play”.
     * 
    */
    const STATUS_CODE_SUCCESS = 0;
    const STATUS_CODE_NOT_ENOUGH_MONEY = 1;
    const STATUS_CODE_INVALID_CURRENCY = 2;
    const STATUS_CODE_NEGATIVE_DEPOSIT = 3;
    const STATUS_CODE_NEGATIVE_WITHDRAW = 4;
    const STATUS_CODE_PLAYER_LIMIT_EXCEEDED = 6;
    const STATUS_CODE_INVALID_SERVER_TOKEN = 7;
    const STATUS_CODE_RETRY_EXCEPTION = 99;
    const STATUS_CODE_UNKNOWN_ERROR = 100;
    const STATUS_CODE_GENERAL_ERROR_WITH_MESSAGE = 990;
    const STATUS_CODE_OUT_OF_FUNDS_WITH_MESSAGE = 991;

    /** API endpoints of game provider in wallet transaction */
    const ACTION_GET_CURRENCY = 'currency';
    const ACTION_GET_BALANCE = 'balance';
    const ACTION_WITHDRAW = 'withdraw';
    const ACTION_DEPOSIT = 'deposit';
    const ACTION_REFUND = 'refund';

    /** array list of API endpoints of game provider in wallet transaction */
    const API_ACTIONS = [
        self::ACTION_GET_CURRENCY,
        self::ACTION_GET_BALANCE,
        self::ACTION_WITHDRAW,
        self::ACTION_DEPOSIT
    ];

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

        $this->loadModel(['common_seamless_wallet_transactions','common_token','external_system','response_result']);
    }

    /**
     * Entry point
     * 
     *
     * @param string $method
     * @param int $apiId
     * @param string $player
     * 
     * @return class::CI_Output
    */
    public function index($method,$apiId,$player)
    {
        # add raw data request to the logs first
        $rawData = $this->rawRequest();
        $requestHeaders = $this->input->request_headers();
        $this->utils->debug_log(__METHOD__." RAW REQUEST",$rawData,'headers',$requestHeaders,'method',$method,'apiId',$apiId,'player',$player);
        $header = null;
        $this->setBasicAuthorization($header);
        $isBasicAuthCorrect = $this->checkBasicAuth($requestHeaders,$header);

        if(! empty($this->request())){
            $this->rqSeamlessRequestData = $this->request();
        }

        # init it
        $this->rspSeamlessResponseData->responseData = [];

        # set Common Variables
        $this->setCommonVar($apiId,$player);

        # get player id
        $jContent = json_encode($this->rspSeamlessResponseData->responseData);
        $playerId = $this->gameApiSysLibObj->getPlayerIdByGameUsername($player);

        # save to response result first
        $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);

        if ($this->external_system->isGameApiActive($this->getPlatformCode())) {
            #check basic Auth in header first
            $this->utils->debug_log(__METHOD__." IS BASIC AUTH CORRECT",$isBasicAuthCorrect,'our header',$header);

            if($isBasicAuthCorrect){
                if(in_array((string)$method,self::API_ACTIONS)){
                    try{
                        return $this->$method();
                    }catch(\Exception $e){
                        return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,'Method not exist');
                    }
                }

                return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"Api Action not exist");
            }

            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"Basic Authentication is not correct");
        }

        return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"Game Api is not active");
    }

    /** 
     * Get Platform Code
     * 
     * @return int
    */
    public function getPlatformCode()
    {
        return NETENT_SEAMLESS_GAME_THB1_API;
    }

    /**
     * Debit or Deduct in Player Wallet in our Database
     * 
     * @return class::CI_Output
     */
    public function debit()
    {
        return $this->deposit();
    }

    /**
     * Credit or Add in Player Wallet in our Database
     * 
     * @return class::CI_Output
     */
    public function credit()
    {
       return $this->withdraw();
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
     * This request gets the currency of the player's account.
     * 
     * The currency is fetched only once; the first time a player is logged in. 
     * So, any later change in player currency will not be honored by the Client.
     * 
    */
    protected function currency()
    {
        $this->currentMethod = __FUNCTION__;

        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;
        $this->rspSeamlessResponseData->responseData['responseCode'] = self::STATUS_CODE_SUCCESS;
        $this->rspSeamlessResponseData->responseData['currencyISOCode'] = $this->gameApiSysLibObj->currency;

        # get player id
        $cv = $this->getCommonVar();
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;
        $playerId = $this->gameApiSysLibObj->getPlayerIdByGameUsername($cv->cvplayer);

        # save to response result first
        $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);

        return $this->outputStandardResponse(null,$rId);
    }

    /** 
     * This method is used to get the balance of the player's account.
    */
    protected function balance()
    {
            $this->currentMethod = __FUNCTION__;
            # all common variable is in here
            $cv = $this->getCommonVar();
            # get player id
            $playerId = !is_null($cv->cvplayer) ? $this->gameApiSysLibObj->getPlayerIdByGameUsername($cv->cvplayer) : null;

            $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;

            # save to response result first
            $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_NORMAL]);

            if(! is_null($playerId)){
                $playerCurrentToken = $this->gameApiSysLibObj->getPlayerTokenByGameUsername($cv->cvplayer);

                $balance = $this->getPlayerBalanceWithLock($playerId);
                $formattedBalance = $this->getProperBalance($balance);

                # for internal
                $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_OK;
                $this->rspSeamlessResponseData->rspStatusHeaderCode = 200;

                 # to be response in game provider
                $this->rspSeamlessResponseData->responseData['responseCode'] = self::STATUS_CODE_SUCCESS;
                $this->rspSeamlessResponseData->responseData['serverToken'] = $playerCurrentToken;
                $this->rspSeamlessResponseData->responseData['balance'] = $formattedBalance;

                return $this->outputStandardResponse(null,$rId);
            }

            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");
    }

    /**
     * * FOR WITHDRAW
     * It is important that the response contains the mandatory parameters even if the parameters are irrelevant for the response code, otherwise the response code is ignored by the Client. And even if code 1 is returned (not enough money in player account)* , the Server must return the balance.
     * 
     * * FOR ROLLBACK WITHDRAW TRANSACTION
     * This request is used to roll back a withdraw transaction that was made earlier on a player's account. If the Server does not find a transaction that is being requested to be rolled back, it should just indicate it as a success (a non existing transaction on Server is equivalent to a rolled back one from system perspective).
     * 
     * 
     */
    protected function withdraw()
    {
        $this->currentMethod = __FUNCTION__;
        # all common variable is in here
        $cv = $this->getCommonVar();

        # get player id
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;
        $playerId = $this->gameApiSysLibObj->getPlayerIdByGameUsername($cv->cvplayer);

        # save to response result first, flag default to error
        $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_ERROR]);
        $this->rqSeamlessRequestData->request_id = $rId;

        # refresh token here, if 10 minutes before current timeout_at
        if($this->isDeleteMethod()){
            $serverToken = $this->gameApiSysLibObj->getPlayerTokenByGameUsername($cv->cvplayer);
        }else{
            $serverToken = $this->getAttributeInObject($this->rqSeamlessRequestData,'serverToken');
        }

        $playerInfo = $this->common_token->getPlayerInfoByToken($serverToken,true,true,$this->tokenTimeComparison,$this->newTokenValidity);
        $playerCurrentActivetoken = isset($playerInfo['token']) ? $playerInfo['token'] : null;
        $playerId = isset($playerInfo['player_id']) ? $playerInfo['player_id'] : null;
        $beforeBalance = $this->getPlayerBalanceWithLock($playerId);
        $formattedBalance = $this->getProperBalance($beforeBalance);

        ## ROLLBACK START HERE ##
        # check first if DELETED method, if so, meaning need to rollback withdraw request before
        if($this->isDeleteMethod() && ! empty($playerInfo)){
            $isRefundSuccess = false;
            $this->currentMethod = 'refund';
            $amountToBeRefunded = $this->common_seamless_wallet_transactions->getAmountBasedInTransactionId($cv->cvrtransactionref);
            $this->rqSeamlessRequestData->amountToDeposit = $amountToBeRefunded;

            # if the refund value is null, means the request is not about bet, and we only accept bet request for refund
            if(is_null($amountToBeRefunded)){
                return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"transaction type is not bet for refund");
            }

            # save to response result first, flag default to error
            $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>Response_result::FLAG_ERROR]);

            $requestId = $this->utils->getRequestId();

            if($amountToBeRefunded >= 0){
                $data = $this->generateInsertDataForNt($this->rqSeamlessRequestData);
                $extra = ['transaction_id'=>$cv->cvrtransactionref,'is_refund'=>true];
                $incrementResult = $this->doIncrementWithTransactionChecking($playerId,$amountToBeRefunded,$data,$extra);


                if(array_key_exists('is_trans_success',$incrementResult) && $incrementResult['is_trans_success']){
                    # if sucess and the withdraw status has been updated, we can return success
                    if((array_key_exists('is_refund_transaction_status_updated',$incrementResult) && $incrementResult['is_refund_transaction_status_updated'])
                    ){
                        # key for success refund response
                        $isRefundSuccess = true;
                    }else{
                        # error
                        $this->utils->error_log("REFUND ERROR, transaction id: ",$cv->cvrtransactionref,'requestId',$requestId);
                    }
                }else{
                    # even error, need to check if reason is valid to return a success response
                    # make sure if transaction to be refunded is not exist or is already refund, sucess is return, for idempotent request
                    if((array_key_exists('reason_refund_transaction_not_exist',$incrementResult) && $incrementResult['reason_refund_transaction_not_exist']) || (array_key_exists('reason_refunded_already',$incrementResult) && $incrementResult['reason_refunded_already'])){
                        # for internal
                        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
                        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

                        # to be response in game provider
                        $this->rspSeamlessResponseData->responseData['responseCode'] = self::STATUS_CODE_SUCCESS;
                        $this->rspSeamlessResponseData->responseData['serverTransactionRef'] = $requestId;
                        $this->rspSeamlessResponseData->responseData['balance'] = $formattedBalance;

                        return $this->outputStandardResponse(null,$rId);
                    }
                }
            }

            if($isRefundSuccess){
                $statusCode = self::STATUS_CODE_OK;
                $statusHeaderCode = 200;
                $responseCode = self::STATUS_CODE_SUCCESS;
            }else{
                 $statusCode = self::STATUS_CODE_ERROR;
                 $statusHeaderCode = 200;
                 $responseCode = self::STATUS_CODE_RETRY_EXCEPTION;
                 $this->rspSeamlessResponseData->responseData['responseMessage'] = "Have problem in processing the refund, try again";
            }

            $afterBalance = array_key_exists('after_balance',$incrementResult) ? $incrementResult['after_balance'] : 0;
            $formattedBalance = $this->getProperBalance($afterBalance);
             # for internal
             $this->rspSeamlessResponseData->rspStatusCode = $statusCode;
             $this->rspSeamlessResponseData->rspStatusHeaderCode = $statusHeaderCode;

             # to be response in game provider
             $this->rspSeamlessResponseData->responseData['responseCode'] = $responseCode;
             $this->rspSeamlessResponseData->responseData['serverTransactionRef'] = $requestId;
             $this->rspSeamlessResponseData->responseData['balance'] = $formattedBalance;

             return $this->outputStandardResponse(null,$rId);
        }
        ## ROLLBACK END HERE ##

        # check if method is POST in the request
        if(! $this->isPostMethod()){
            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"http method is not correct");
        }

        if(! empty($playerInfo)){
            $playerName = isset($playerInfo['username']) ? $playerInfo['username'] : null;

            # check if the token have player id
            if($playerId){

                $checkpoint = $this->validityCheckpoint($formattedBalance,$cv->cvwamounttowithdraw,$playerName,$playerCurrentActivetoken,$playerId,$cv->cvwtransactionref,$rId,true);

                # request have error
                if($checkpoint){
                    return $this->outputStandardResponse(null,$rId);
                }

                #decrement player balance
                $data = $this->generateInsertDataForNt($this->rqSeamlessRequestData);
                $extra = ['transaction_id'=>$cv->cvwtransactionref];
                $decrementResult = $this->doDeductWithTransactionChecking($playerId,$cv->cvwamounttowithdraw,$data,$extra);

                # after balance here
                $afterBalance = array_key_exists('after_balance',$decrementResult) ? $decrementResult['after_balance'] : 0;
                $formattedBalance = $this->getProperBalance($afterBalance);

                if(array_key_exists('is_trans_success',$decrementResult) && $decrementResult['is_trans_success']){
                    return $this->returnSucess($playerCurrentActivetoken,$formattedBalance,$rId);
                }else{
                    # even error, we need to check if the reason for failing it about transaction already exist
                    if(array_key_exists('is_transaction_already_exist',$decrementResult) && $decrementResult['is_transaction_already_exist']){
                        # for internal
                        $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
                        $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

                        # to be response in game provider
                        $this->rspSeamlessResponseData->responseData['responseMessage'] = "duplicate transaction reference withdraw not accepted";
                        $this->setMandatoryResponseAttr(self::STATUS_CODE_UNKNOWN_ERROR,$playerCurrentActivetoken,$formattedBalance);

                        return $this->outputStandardResponse(null,$rId);
                    }else{
                        # game provider can retry
                        $this->setMandatoryResponseAttr(self::STATUS_CODE_RETRY_EXCEPTION,$playerCurrentActivetoken,$formattedBalance);
                        return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_RETRY_EXCEPTION,'Have problem in processing the bet, try again');
                    }
                }
            }
            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");

        }
        return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");
    }

    /**
     * * This request is used to deposit money to player's account.
     * 
     * All parameters containing monetary amounts are rounded off to a maximum of 6 decimals.
     * It is vital that your system considers all these decimals without further rounding the amounts, to avoid scenarios in some games where players may win more over time than they bet (>100% RTP).
     * 
     *
     */
    protected function deposit()
    {
        $this->currentMethod = __FUNCTION__;

        # all common variable is in here
        $cv = $this->getCommonVar();
        
        # get player id
        $jContent = is_array($this->rspSeamlessResponseData->responseData) ? json_encode( $this->rspSeamlessResponseData->responseData) : $this->rspSeamlessResponseData->responseData;
        $playerId = $this->gameApiSysLibObj->getPlayerIdByGameUsername($cv->cvplayer);

        # save to response result first, flag default to error
        $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->rqSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>2]);

        # check if method is POST in the request
        if(! $this->isPostMethod()){
            $this->rspSeamlessResponseData->responseData['serverTransactionRef'] = $this->utils->getRequestId();
            $this->rspSeamlessResponseData->responseData['responseCode'] = self::STATUS_CODE_UNKNOWN_ERROR;
            $this->rspSeamlessResponseData->responseData['responseMessage'] = "http method is not correct";
            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"http method is not correct");
        }

        $playerCurrentToken = $this->gameApiSysLibObj->getPlayerTokenByGameUsername($cv->cvplayer);
        # refresh token here, if 10 minutes before current timeout_at
        $playerInfo = $this->common_token->getPlayerInfoByToken($playerCurrentToken,true,true,$this->tokenTimeComparison,$this->newTokenValidity);

        if(! empty($playerInfo)){
            $playerCurrentActivetoken = isset($playerInfo['token']) ? $playerInfo['token'] : null;
            $playerId = isset($playerInfo['player_id']) ? $playerInfo['player_id'] : null;
            $beforeBalance = $this->getPlayerBalanceWithLock($playerId);
            $formattedBalance = $this->getProperBalance($beforeBalance);

            $playerName = isset($playerInfo['username']) ? $playerInfo['username'] : null;

            # check if the token have player id
            if($playerId){

                $checkpoint = $this->validityCheckpoint($formattedBalance,$cv->cvwamounttodeposit,$playerName,$playerCurrentActivetoken,$playerId,$cv->cvwtransactionref,$rId,false);

                # request have error
                if($checkpoint){
                    return $this->outputStandardResponse(null,$rId);
                }

                #decrement player balance
                $data = $this->generateInsertDataForNt($this->rqSeamlessRequestData);
                $extra = ['transaction_id'=>$cv->cvwtransactionref];
                $incrementResult = $this->doIncrementWithTransactionChecking($playerId,$cv->cvwamounttodeposit,$data,$extra);

                # after balance here
                $afterBalance = array_key_exists('after_balance',$incrementResult) ? $incrementResult['after_balance'] : 0;
                $formattedBalance = $this->getProperBalance($afterBalance);

                if(array_key_exists('is_trans_success',$incrementResult) && $incrementResult['is_trans_success']){
                    return $this->returnSucess($playerCurrentActivetoken,$formattedBalance,$rId);
                }else{
                    # even it's error, we still need to check if the deposit request is already exist
                    if(array_key_exists('is_transaction_already_exist',$incrementResult) && $incrementResult['is_transaction_already_exist']){
                        return $this->returnSucess($playerCurrentActivetoken,$formattedBalance,$rId,'ERROR',403);
                    }else{
                        # game provider can retry
                        $this->setMandatoryResponseAttr(self::STATUS_CODE_RETRY_EXCEPTION,$playerCurrentActivetoken,$formattedBalance);
                        return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_RETRY_EXCEPTION,'Have problem in processing the bet, try again');
                    }
                }
            }

            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");

        }

        return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"token is not valid");
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
     * Set Basic Authorization
     * 
     * @param array $header
     * 
     * @return array
     */
    public function setBasicAuthorization(&$header)
    {
        $authUser = $this->getAttributeInObject($this->gameApiSysLibObj,'callerId');
        $authPassword = $this->getAttributeInObject($this->gameApiSysLibObj,'callerPassword');
        $auth = $this->generateBasicAuth($authUser,$authPassword,[]);
        $header["Authorization"] = "Basic ".$auth;
    }

    /**
     *  Check Basic auth is correct
     * 
     * @param array $headers game provider request header
     * @param array $header our header
     * 
     * @return boolean $auth
     */
    public function checkBasicAuth($headers,$header)
    {
        $requestAuth = isset($headers['Authorization']) ? $headers['Authorization'] : null;
        $ourHeader = isset($header['Authorization']) ? $header['Authorization'] : null;

        if(($ourHeader === $requestAuth) && (!is_null($requestAuth) && !is_null($ourHeader))){
            return true;
        }

        return false;
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
        $player = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqPlayer');
        $currency = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqCurrency');
        $game = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqGame');
        $session = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqSession');
        $apiId = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqApiId');

        # for withdraw
        $cvwAmountToWithdraw = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqwAmountToWithdraw');

        # for withdraw
        $cvwAmountToDeposit = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqwAmountToDeposit');

        # for rollback
        $cvrGame = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqrGame');
        $cvrGameRoundRef = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqrGameRoundRef');
        $cvrTransactionRef = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqrTransactionRef');
        $cvrSession = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqrSession');

        # common variable for withdraw,deposit and rollback request
        $cvwTransactionRef = $this->getAttributeInObject($this->rqSeamlessRequestData,'rqwTransactionRef');

        $data = new stdClass();
        $data->cvplayer = $player;
        $data->cvcurrency = $currency;
        $data->cvgame = $game;
        $data->cvapiid = $apiId;
        $data->cvsession = $session;
        $data->cvwamounttowithdraw = $cvwAmountToWithdraw;
        $data->cvwtransactionref = $cvwTransactionRef;
        $data->cvwamounttodeposit = $cvwAmountToDeposit;
        $data->cvrgame = $cvrGame;
        $data->cvrgameroundref = $cvrGameRoundRef;
        $data->cvrtransactionref = $cvrTransactionRef;
        $data->cvrsession = $cvrSession;

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
            $this->rqSeamlessRequestData->rqApiId = !empty($apiId) ? $apiId : null;
            $this->rqSeamlessRequestData->rqPlayer = !empty($player) ? $player : null;
            $this->rqSeamlessRequestData->rqSession  = $this->input->get('session');
            $this->rqSeamlessRequestData->rqCurrency  = $this->input->get('currency');
            $this->rqSeamlessRequestData->rqGame  = $this->input->get('game');

            # for withdraw
            $this->rqSeamlessRequestData->rqwAmountToWithdraw = $this->getAttributeInObject($this->rqSeamlessRequestData,'amountToWithdraw');

            # for deposit
            $this->rqSeamlessRequestData->rqwAmountToDeposit = $this->getAttributeInObject($this->rqSeamlessRequestData,'amountToDeposit');

            # for rollback
            $this->rqSeamlessRequestData->rqrGame  = $this->input->get('game');
            $this->rqSeamlessRequestData->rqrGameRoundRef  = $this->input->get('gameRoundRef');
            $this->rqSeamlessRequestData->rqrTransactionRef  = $this->input->get('transactionRef');
            $this->rqSeamlessRequestData->rqrSession  = $this->input->get('session');

            # common variable for withdraw,deposit and rollback request
            $this->rqSeamlessRequestData->rqwTransactionRef = $this->getAttributeInObject($this->rqSeamlessRequestData,'transactionRef');
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

        $jContent = is_array($ntObject->responseData) ? json_encode($ntObject->responseData) : $ntObject->responseData;

        # if error occur, save it to common_seamless_error_logs table
        $lastInsertIdOfSe = false;
        if($statusCode != self::STATUS_CODE_OK){
            $this->rqSeamlessRequestData->request_id = $rId;
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

            $lastInsertIdOfSe = $this->saveSeamlessError($insertData);
        }

        $this->utils->debug_log(__METHOD__.' with response data of: >>>>>>>>',$jContent,'last insert id of seamless error, if false, meaning no error in this request',$lastInsertIdOfSe,'response result id to be updated',$rId);

        # basic authorization
        $header = [];
        $this->setBasicAuthorization($header);

        return $this->outputHttpResponse($ntObject->responseData,$statusHeaderCode,$header,$rId,$playerId,$flag);
    }

    /**
     * Generate Array Data for inserting into common wallet table
     * 
     * @param object $requestData
     * 
     * @return array
     */
    public function generateInsertDataForNt($requestData)
    {
        # all common variable is in here
        $cv = $this->getCommonVar();
        $apiId = $cv->cvapiid;
        $amountToDeposit = $this->getAttributeInObject($requestData,'amountToDeposit');
        $amount = $this->getAttributeInObject($requestData,'amountToWithdraw',$amountToDeposit);
        $gameId = $this->getAttributeInObject($requestData,'game');
        $transactionType = $this->generateTransactionType($this->currentMethod);
        $transactionDate =  $this->getAttributeInObject($requestData,'transactionDate');
        $externalUniqueId = $this->getAttributeInObject($requestData,'transactionRef');;
        $rollbackRoundId = $this->input->get('gameRoundRef');
        $roundId = $this->getAttributeInObject($requestData,'gameRoundRef',$rollbackRoundId);

        if($transactionType == self::TRANSACTION_REFUND){
            $externalUniqueId = $cv->cvrtransactionref .'-'.$cv->cvrgameroundref;
        }

        $now = (new DateTime())->format('Y-m-d H:i:s');
        $startTime = is_null($transactionDate) ? $now : $this->gameApiSysLibObj->gameTimeToServerTime((new DateTime($transactionDate))->format('Y-m-d H:i:s'));
        
        $extraInfo = json_encode([]);
        if(is_object($requestData)){
            $requestData->isRefunded =  property_exists($requestData,'isRefunded') ? true : false;
            $extraInfo = json_encode($requestData);
        }elseif(is_array($requestData)){
            $requestData['isRefunded'] =  property_exists($requestData,'isRefunded') ? true : false;
            $extraInfo = json_encode($requestData);
        }


        $gameRecords = [
            [
                'game_platform_id' => $apiId,
                'amount' => $amount,
                'game_id' => $gameId,
                'transaction_type' => $transactionType,
                'status' => self::STATUS_OK,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' => $externalUniqueId,
                'extra_info' => $extraInfo,
                'start_at' => $startTime,
                'end_at' => $now,
                'transaction_id' => $externalUniqueId,
                'round_id' => $roundId
            ]
        ];

        $this->processGameRecords($gameRecords);

        return $gameRecords;
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
            case self::ACTION_WITHDRAW:
                $type = self::TRANSACTION_BET;
            break;
            case self::ACTION_DEPOSIT:
                $type = self::TRANSACTION_WIN;
            break;
            case self::ACTION_REFUND:
                $type = self::TRANSACTION_REFUND;
            break;
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
     * @param int $transactionId
     * @param boolean|false $is_bet
     * @param int $rId the response result id to be updated
     * 
     * @return mixed
    */
    public function validityCheckpoint($before_balance,$betAmount,$playerName,$playerCurrentActivetoken,$playerId,$transactionId,$rId,$is_bet=false)
    {
        # for BET
        if($is_bet){

            $isAllowedToBet = $this->utils->compareResultFloat($before_balance,">=",$betAmount);
            # checkpoint 1.)  check if current balance is greater than bet
            if(! $isAllowedToBet){

                # for internal
                $this->rspSeamlessResponseData->rspStatusCode = self::STATUS_CODE_ERROR;
                $this->rspSeamlessResponseData->rspStatusHeaderCode = 403;

                # to be response in game provider
                $this->setMandatoryResponseAttr(self::STATUS_CODE_NOT_ENOUGH_MONEY,$playerCurrentActivetoken,$before_balance);

                return $this->rspSeamlessResponseData->responseData['responseCode'];
            }
        }

        $isPlayerBlockedAll = $this->player_model->isBlocked($playerId); # block in player table

        if($this->gameApiSysLibObj->isBlocked($playerName) || $isPlayerBlockedAll){
            return $this->outputError('ERROR',$rId,400,self::STATUS_CODE_UNKNOWN_ERROR,"Player is blocked");
        }

        return false;
    }

    /**
     * Set Mandatory property in Response Object
     * 
     * @param int $statusCode
     * @param string $playerCurrentActivetoken
     * @param int $balance
     * 
     * @return void
     */
    public function setMandatoryResponseAttr($statusCode,$playerCurrentActivetoken,$balance)
    {
        $this->rspSeamlessResponseData->responseData['responseCode'] = $statusCode;
        $this->rspSeamlessResponseData->responseData['serverTransactionRef'] = $this->utils->getRequestId();
        $this->rspSeamlessResponseData->responseData['serverToken'] = $playerCurrentActivetoken;
        $this->rspSeamlessResponseData->responseData['balance'] = $balance;
    }

    /**
     * Return Success with mandatory response
     * 
     * @param string $playerCurrentActivetoken
     * @param int $formattedBalance
     * @param int $rId the response result id to be updated
     * @param string $code
     * $param int $headerCode
     * 
     * @return class::CI_Output
     */
    protected function returnSucess($playerCurrentActivetoken,$formattedBalance,$rId,$code="OK",$headerCode=200)
    {
        # for internal
        $this->rspSeamlessResponseData->rspStatusCode = $code;
        $this->rspSeamlessResponseData->rspStatusHeaderCode = $headerCode;

        # to be response in game provider
        $this->setMandatoryResponseAttr(self::STATUS_CODE_SUCCESS,$playerCurrentActivetoken,$formattedBalance);

        return $this->outputStandardResponse(null,$rId);
    }
}