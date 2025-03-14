<?php

if(! defined('BASEPATH')){
    exit('No Direct script access allowed');
}

require_once dirname(__FILE__) . '/Abstract_seamless_service_game_api.php';
require_once dirname(__FILE__) . '/../libraries/salt.php';

use Illuminate\Support\Str;

class Ag_seamless_service_api_old extends Abstract_Seamless_Service_Game_Api
{
    /**
     * API endpoints of game provider in wallet transaction 
     * 
     * @var const
     */
    const ACTION_GET_BALANCE = 'BALANCE';
    const ACTION_WITHDRAW = 'WITHDRAW';
    const ACTION_DEPOSIT = 'DEPOSIT';
    const ACTION_ROLLBACK = 'ROLLBACK';
    const ACTION_BET = 'BET';
    const ACTION_WIN = 'WIN';
    const ACTION_LOSE = 'LOSE';
    const ACTION_REFUND = 'REFUND';
    const ACTION_REDIRECTION = 'redirection';

    /**
     * List of redirection type
     * 6 - deposit
     * 9 - register real account
     * 12 - exit game
     * 13 - customer service page
     * 
     * @var const
     */
    const REDIRECTION_TYPES = [6,9,12,13];
    const REDIRECTION_DEPOSIT = 6;
    const REDIRECTION_REGISTER = 9;
    const REDIRECTION_EXIT_GAME = 12;
    const REDIRECTION_CUSTOMER_SERVICE_PAGE = 13;

    /**
     * The game type of Game provider in our endpoint
     * 
     * @var const
     */
    const ENDPOINT_LIVE_DEALER = 'postTransfer';
    const ENDPOINT_SLOT = 'slot';
    const ENDPOINT_EVENT = 'event';

    /**
     * array list of API endpoints of game provider in wallet transaction
     * 
     * @var const API_ACTIONS
     */
    const API_ACTIONS = [
        self::ACTION_GET_BALANCE,
        self::ACTION_WITHDRAW,
        self::ACTION_DEPOSIT,
        self::ACTION_ROLLBACK,
        self::ACTION_BET,
        self::ACTION_WIN,
        self::ACTION_LOSE,
        self::ACTION_REFUND
    ];

    /**
     * Endpoint for Game Type
     */
    const API_GAME_TYPE = [
        self::ENDPOINT_LIVE_DEALER,
        self::ENDPOINT_SLOT
    ];

    /**
     * Success status Code
     */
    const STATUS_CODE_OK = 'OK';

    /** 
     * @var string $rTransactionType
     * 
    */
    protected $rTransactionType;

    /** 
     *  AG Seamless Request Data to us
     * 
     * @var object $agSeamlessRequestData
     * 
    */
    protected $agSeamlessRequestData;

    /** 
     *  AG Seamless Response Data for them
     * 
     * @var array $agSeamlessrequestData
     * 
    */
    protected $agSeamlessResponseData;

    /**
     * The current method executing in the class
     * 
     * @var string $currentMethod
     */
    protected $currentMethod;

    private $transaction_for_fast_track = null;


    public function __construct()
    {
        parent::__construct();

        /** STD class for AG seamless request data */
        $this->agSeamlessRequestData = new stdClass();
        /** STD class for AG seamless request data */
        $this->agSeamlessResponseData = new stdClass();
        $this->currentMethod = 'index';
        $this->agSeamlessResponseData->eStatusHeaderCode = 200;

        $this->loadModel(['common_seamless_wallet_transactions']);
    }

    /** 
     * Get Platform Code
     * 
     * @return int
    */
    public function getPlatformCode()
    {
        return AG_SEAMLESS_THB1_API;
    }

    public function index($gameType)
    {
        if (!$this->gameApiSysLibObj->validateWhiteIP()) {
            $this->currentMethod = 'validateWhiteIP';
            $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
            $this->agSeamlessResponseData->eStatusHeaderCode = 404;
            return $this->outputStandardResponse();
        }

        if(in_array((string) $gameType,self::API_GAME_TYPE)){

            $rData = $this->agSeamlessRequestData = $this->requestObjectFromXml();
            $rawData = $this->rawRequest();

            $rTransactionType = $this->rTransactionType = property_exists($rData->Record,"transactionType") ? $rData->Record->transactionType : null;

            if(! is_null($this->rTransactionType)){
                if(in_array($this->rTransactionType,self::API_ACTIONS)){
                    $lrTransactionType = Str::lower($rTransactionType);

                    try{
                        $this->CI->utils->debug_log("AG_SEAMLESS provider access method: ", $lrTransactionType,' with raw XML request data of: ',$rawData);
                        return $this->$lrTransactionType();
                    }catch(\Exception $e){
                        # transaction type is not exist
                        $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                        $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                        return $this->outputStandardResponse();
                    }

                }
                # transaction type is not exist
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_DATA';
                $this->agSeamlessResponseData->eStatusHeaderCode = 400;
                return $this->outputStandardResponse();
            }
            # transaction type is null
            $this->agSeamlessResponseData->eStatusCode = 'INVALID_DATA';
            $this->agSeamlessResponseData->eStatusHeaderCode = 400;
            return $this->outputStandardResponse();

        }

        # game type endpoint not exist
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_DATA';
        $this->agSeamlessResponseData->eStatusHeaderCode = 400;
        return $this->outputStandardResponse();
    }

    /** 
     * 
    */
    public function balance()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;
        // TODO add additional checkpoint
        if($this->isTokenValid($token)){
            $this->agSeamlessResponseData->eStatusCode = 'OK';
            return $this->outputStandardResponse($this->agSeamlessResponseData);
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /** 
     * Post Transfer for Withdraw (EGame API v2)
    */
    public function withdraw()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;

        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $amount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'amount');
            $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');

            if($isPlayerExist){

                if(is_array($transactionId) && count($transactionId) > 0){

                    foreach($transactionId as $transaction){
                        $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$transaction,true);

                        if($validityCheckpoint){
                            return $this->outputStandardResponse();
                        }
                    }

                }else{
                    $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$transactionId,true);

                    if($validityCheckpoint){
                        return $this->outputStandardResponse();
                    }
                }

                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                $isDeductSuccess = $this->doDeduct($playerId,$amount,$data);

                if($isDeductSuccess){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }else{
                    $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                    $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }

            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /** 
     * Post Transfer for Deposit (EGame API v2)
    */
    public function deposit()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;

        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $amount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'amount');
            $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');


            if($isPlayerExist){

                if(is_array($transactionId) && count($transactionId) > 0){

                    foreach($transactionId as $transaction){
                        $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$transaction);

                        if($validityCheckpoint){
                            return $this->outputStandardResponse();
                        }
                    }
                }else{
                    $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$transactionId);

                    if($validityCheckpoint){
                        return $this->outputStandardResponse();
                    }
                }

                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                $isIncrementSuccess = $this->doIncrement($playerInfo->playerId,$amount,$data);

                if($isIncrementSuccess){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }else{
                    $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                    $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }

            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /**
     * 
     */
    public function rollback()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;

        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $amount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'amount');
            $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');

            if($isPlayerExist){
                $transactionType = $this->generateTransactionType($this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionType'));
                if(is_array($transactionId) && count($transactionId) > 0){
                    $external_unique_id = null;
                    foreach($transactionId as $transaction){
                        $external_unique_id .= $transaction . "_";
                    }
                }else{
                    $external_unique_id = $transactionId;
                }

                $external_unique_id = rtrim($external_unique_id,'_')."-".$transactionType;

                $roundId =  $this->getAttributeInObject($this->agSeamlessRequestData->Record,'roundId');

                $external_unique_id = $roundId .'-'.$external_unique_id;

                $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,null,false);

                if($validityCheckpoint){
                    return $this->outputStandardResponse();
                }

                $isAlreadyRefunded = $this->common_seamless_wallet_transactions->isTransactionAlreadyRefunded($external_unique_id);
                # check if already refunded
                if($isAlreadyRefunded){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }


                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                $isIncrementSuccess = $this->doIncrement($playerInfo->playerId,$amount,$data);

                $this->common_seamless_wallet_transactions->updateRefundedTransaction($external_unique_id);

                if($isIncrementSuccess){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }else{
                    $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                    $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }

            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /**
     * Debit or Deduct in Player Wallet in our Database
     * 
     * @param
     * 
     * @return
     */
    public function debit()
    {
        return $this->withdraw();
    }


    /**
     * Credit or Add in Player Wallet in our Database
     * 
     * @param
     * 
     * @return
     */
    public function credit()
    {
        return $this->deposit();
    }


    /** 
     * Refund the Player Bet or Win in the Player Wallet
     * 
     * @param
     * 
     * @return
    */
    public function refund()
    {
        if($this->rTransactionType == self::ACTION_ROLLBACK){
            return $this->rollback();
        }elseif($this->rTransactionType == self::ACTION_REFUND){
            return $this->betRefund();
        }

    }

    /**
     * Post Transfer Bet (Live Game)
     * 
     * @param
     * 
     * @return
     */
    public function bet()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;

        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $amount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'value');
            $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');
            if($isPlayerExist){

                $transactionType = $this->generateTransactionType($this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionType'));
                if(is_array($transactionId) && count($transactionId) > 0){
                    $external_unique_id = null;
                    foreach($transactionId as $transaction){
                        $external_unique_id .= $transaction . "_";
                    }
                }else{
                    $external_unique_id = $transactionId;
                }

                $external_unique_id = rtrim($external_unique_id,'_')."-".$transactionType;
                
                $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$external_unique_id,true);

                if($validityCheckpoint){
                    return $this->outputStandardResponse();
                }

                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                $isDeductSuccess = $this->doDeduct($playerId,$amount,$data);

                if($isDeductSuccess){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }else{
                    $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                    $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }

            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /**
     * Post Transfer Win (Live Game)
     */
    public function win()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;

        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $validBetAmount =  $this->getAttributeInObject($this->agSeamlessRequestData->Record,'validBetAmount');
            $netAmount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'netAmount');
            $amount = $netAmount + $validBetAmount;

            if($isPlayerExist){

                $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');
                $billNo = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'billNo');
                $transactionType = $this->generateTransactionType($this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionType'));
                if(is_array($transactionId) && count($transactionId) > 0){
                    $external_unique_id = null;
                    foreach($transactionId as $transaction){
                        $external_unique_id .= $transaction . "_";
                    }
                }else{
                    $external_unique_id = $transactionId;
                }

                $external_unique_id = rtrim($external_unique_id,'_').'-'.$billNo."-".$transactionType;
                $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$external_unique_id);

                if($validityCheckpoint){
                    return $this->outputStandardResponse();
                }

                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                $isIncrementSuccess = $this->doIncrement($playerInfo->playerId,$amount,$data);

                if($isIncrementSuccess){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }else{
                    $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                    $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }

            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /**
     * Post Transfer Lose (Live Game)
     * 
     * *When calculating the payoff for Live Game, payoff amount = netAmount + validBetAmount.
     * 
     * @param
     * 
     * @return
     */
    public function lose()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;
        // TODO add additional checkpoint
        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $validBetAmount =  $this->getAttributeInObject($this->agSeamlessRequestData->Record,'validBetAmount');
            $netAmount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'netAmount');
            $amount = $netAmount + $validBetAmount;
            
            if($isPlayerExist){

                $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');
                $transactionType = $this->generateTransactionType($this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionType'));
                if(is_array($transactionId) && count($transactionId) > 0){
                    $external_unique_id = null;
                    foreach($transactionId as $transaction){
                        $external_unique_id .= $transaction . "_";
                    }
                }else{
                    $external_unique_id = $transactionId;
                }

                $external_unique_id = rtrim($external_unique_id,'_')."-".$transactionType;
                $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,$external_unique_id);

                if($validityCheckpoint){
                    return $this->outputStandardResponse();
                }

                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                # if $amount > 0, we need to increment player balance, if $amount == 0, we do nothing because we already deducted player in bet() method
                if($amount >= 0){
                    $isIncrementSuccess = $this->doIncrement($playerInfo->playerId,$amount,$data);
                    if($isIncrementSuccess){
                        $this->agSeamlessResponseData->eStatusCode = 'OK';
                        return $this->outputStandardResponse($this->agSeamlessResponseData);
                    }else{
                        $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                        $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                        return $this->outputStandardResponse($this->agSeamlessResponseData);
                    }
                }else{
                    $requestId = $this->CI->utils->getRequestId();
                    $this->CI->utils->debug_log("AG_SEAMLESS POST TRANSFER LOSE: the payoff amount is negative: with request id of: ",$requestId);
                    // as per game provider this will not happen, we do nothing, just respond with success
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }
            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /**
     * Post Transfer Refund (Live Game)
     */
    public function betRefund()
    {
        $this->currentMethod = __FUNCTION__;
        $token = $this->agSeamlessRequestData->Record->sessionToken;
        // TODO add additional checkpoint
        if($this->isTokenValid($token)){
            $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
            $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
            $playername = $playerInfo->username;
            $playerId = $playerInfo->playerId;
            $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
            $balance = isset($playerBalance['balance']) ? $playerBalance['balance'] : null;
            $isPlayerExist = $this->isPlayerExistInProvider($playerInfo->username,$gamePlatformCode);
            $amount = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'value');
            $transactionId = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'transactionID');

            if($isPlayerExist){
                if(is_array($transactionId) && count($transactionId) > 0){
                    $external_unique_id = null;
                    foreach($transactionId as $transaction){
                        $external_unique_id .= $transaction . "_";
                    }
                }else{
                    $external_unique_id = $transactionId;
                }

                $external_unique_id = rtrim($external_unique_id,'_')."-". self::ACTION_BET;

                $validityCheckpoint = $this->validityCheckpoint($balance,$amount,$playername,$playerId,null,false);

                if($validityCheckpoint){
                    return $this->outputStandardResponse();
                }

                $isAlreadyRefunded = $this->common_seamless_wallet_transactions->isTransactionAlreadyRefunded($external_unique_id);
                # check if already refunded
                if($isAlreadyRefunded){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }


                $data = $this->generateInsertDataForAg($this->agSeamlessRequestData);

                $isIncrementSuccess = $this->doIncrement($playerInfo->playerId,$amount,$data);

                $this->common_seamless_wallet_transactions->updateRefundedTransaction($external_unique_id);

                if($isIncrementSuccess){
                    $this->agSeamlessResponseData->eStatusCode = 'OK';
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }else{
                    $this->agSeamlessResponseData->eStatusCode = 'ERROR';
                    $this->agSeamlessResponseData->eStatusHeaderCode = 500;
                    return $this->outputStandardResponse($this->agSeamlessResponseData);
                }

            }else{
                # player is not registered
                $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
                $this->agSeamlessResponseData->eStatusHeaderCode = 404;
                return $this->outputStandardResponse();
            }
        }
        # token is expired
        $this->agSeamlessResponseData->eStatusCode = 'INVALID_SESSION';
        $this->agSeamlessResponseData->eStatusHeaderCode = 404;
        return $this->outputStandardResponse();
    }

    /** 
     * Returning API to operator website (API provided by operator)
     * 
     * By providing http://<server>/xxxx.xx? to game platform, we can invoke this API with parameters to
     * return to the website from the game. It is only available for web game.
     * 
     * @param string $method
     * 
     * @return void
    */
    public function redirection($method)
    {
        $id = $this->input->get('id');
        $type = $this->input->get('type');
        $stamp = $this->input->get('stamp');
        $feature = $this->input->get('feature');
        $flag = Response_result::FLAG_NORMAL;
        $requestData = json_encode([
            'method'=>$method,
            'id' => $id,
            'type' => $type,
            'stamp' => $stamp,
            'feature' => $feature
        ]);

        if($this->gameApiSysLibObj->implementGameRedirectionEvent){
            if($method == self::ACTION_REDIRECTION){
                # check if md5 is match with the request first
                $md5 = md5($id . $type. $stamp . $this->gameApiSysLibObj->md5EncryptionKey);
                if($md5 == $feature){
                    if(in_array($type,self::REDIRECTION_TYPES)){
                        $homeLink = $this->gameApiSysLibObj->getHomeLink();
                        if($type == self::REDIRECTION_DEPOSIT){
                            return $this->triggerRedirection($homeLink,'depositPage',$id,$requestData,[],$flag);
                        }elseif($type == self::REDIRECTION_REGISTER){
                            return $this->triggerRedirection($homeLink,'registerPage',$id,$requestData,[],$flag);
                        }elseif($type == self::REDIRECTION_EXIT_GAME){
                            return $this->triggerRedirection($homeLink,null,$id,$requestData,[],$flag);
                        }elseif($type == self::REDIRECTION_CUSTOMER_SERVICE_PAGE){
                            return $this->triggerRedirection($homeLink,'customerServicePage',$id,$requestData,[],$flag);
                        }
                    }
                }
            }
        }
        $flag = Response_result::FLAG_ERROR;
        $this->saveToResponseResult('redirection',$requestData,null,[
            'flag' => $flag,
            'player_id' => $id
            ]);
    }

    /**
     * Process Redirect
     * 
     * @param string $url
     * @param string $defPage
     * @param string $id
     * @param array $requestData
     * @param array $jContent
     * @param boolean|true $flag
     * @return void;
     */
    public function triggerRedirection($url,$defPage,$id,$requestData=[],$jContent=[],$flag=true)
    {
        $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
        $playerName = $this->gameApiSysLibObj->getPlayerUsernameByGameUsername($id);
        $isPlayerExist = $this->isPlayerExistInProvider($playerName,$gamePlatformCode);
        $responseUrl = !empty($this->gameApiSysLibObj->$defPage) ? $this->gameApiSysLibObj->$defPage : $url;

        #check if player exist
        if($isPlayerExist){
            $this->saveToResponseResult('redirection',$requestData,null,[
                'content'=>json_encode(['url'=>$responseUrl]),
                'flag' => $flag,
                'player_id' => $id
                ]);
    
            redirect($responseUrl);
        }
        $flag = $flag = Response_result::FLAG_ERROR;
        $this->saveToResponseResult('redirection',$requestData,null,[
            'content'=>json_encode(['url'=>$responseUrl]),
            'flag' => $flag,
            'player_id' => $id
            ]);
    }


    /**
     * Output Standard Response
     * 
     * @param object $aGObject
     * 
     * @return object
     * 
     */
    public function outputStandardResponse($aGObject=null)
    {
        $aGObject = is_null($aGObject) ? $this->agSeamlessResponseData : $aGObject;
        
        $token = $this->getAttributeInObject($this->agSeamlessRequestData->Record,'sessionToken');
        $playerInfo = (object) $this->gameApiSysLibObj->getPlayerInfoByToken($token);
        $playername = $playerInfo->username;
        $playerBalance = $this->gameApiSysLibObj->queryPlayerBalance($playername);
        $statusCode = $this->getAttributeInObject($this->agSeamlessResponseData,'eStatusCode','ERROR');
        $statusHeaderCode = $this->getAttributeInObject($this->agSeamlessResponseData,'eStatusHeaderCode',200);
        $flag = Response_result::FLAG_NORMAL;

        $this->utils->debug_log('SONY' ,$playerBalance);
        if($statusCode != self::STATUS_CODE_OK){
            $flag = Response_result::FLAG_ERROR;
            $this->agSeamlessResponseData->responseData = [
                'TransferResponse' => [
                    'ResponseCode' => $statusCode
                ]
            ];
        }else{
            $this->agSeamlessResponseData->responseData = [
                'TransferResponse' => [
                    'ResponseCode' => $statusCode,
                    'Balance' => $playerBalance['balance']
                ]
            ];
        }

        $jContent = is_array($aGObject->responseData) ? json_encode($aGObject->responseData) : $aGObject->responseData;

        $rId = $this->saveToResponseResult($this->currentMethod, (array)$this->agSeamlessRequestData, null, ['content' => $jContent, 'flag' => $flag], $this->agSeamlessResponseData->eStatusHeaderCode);

        # if error, insert it in error transaction seamless log
        if($statusCode != self::STATUS_CODE_OK){
            $this->load->model('common_seamless_error_logs');

            $request_id = $this->utils->getRequestId();
            $now = $this->utils->getNowForMysql();
            $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
            $extraDetails = [
                'request' => $this->agSeamlessRequestData,
                'response' => $this->agSeamlessResponseData
            ];

            $commonSeamlessErrorDetails = json_encode($extraDetails);

            $errorLogInsertData = [
                'game_platform_id' => $this->gameApiSysLibObj->getPlatformCode(),
                'response_result_id' => $rId,
                'request_id' => $request_id,
                'elapsed_time' => $elapsed,
                'error_date' => $now,
                'extra_info' => $commonSeamlessErrorDetails
            ];

            try{
                $this->common_seamless_error_logs->insertTransaction($errorLogInsertData);
            }catch(\Exception $e){
                $this->utils->error_log(__METHOD__.' error inserting into common_seamless_error_log',$e->getMessage());
            }
        }

        $this->utils->debug_log(__METHOD__.' with data of: >>>>>>>>',$jContent);
        
        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration')) {
            $this->sendToFastTrack();
        }

        return $this->outputXmlResponse($aGObject->responseData,$statusHeaderCode);
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
     * @return false
    */
    public function validityCheckpoint($before_balance,$betAmount,$playerName,$playerId,$transactionId = null,$is_bet=false,$is_not_refund=true)
    {

        $isPlayerBlockedAll = $this->player_model->isBlocked($playerId); # block in player table

        if($this->gameApiSysLibObj->isBlocked($playerName) || $isPlayerBlockedAll){

            $this->agSeamlessResponseData->eStatusCode = 'INVALID_TRANSACTION';
            $this->agSeamlessResponseData->eStatusHeaderCode = 404;
            return $this->agSeamlessResponseData->eStatusCode;
        }

        if($transactionId != null && $this->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(),$transactionId) && $is_not_refund){

            $this->agSeamlessResponseData->eStatusCode = 'OK';
            return $this->agSeamlessResponseData->eStatusCode;
        }

        $isAllowedToBet = $this->utils->compareResultFloat($before_balance,">=",$betAmount); # check if current balance is greater than bet

        if(! $isAllowedToBet && $is_bet){

            $this->agSeamlessResponseData->eStatusCode = 'INSUFFICIENT_FUNDS';
            $this->agSeamlessResponseData->eStatusHeaderCode = 409;
            return $this->agSeamlessResponseData->eStatusCode;
        }

        return false;
    }

    /**
     * Generate Array Data for inserting into common wallet table
     * 
     * @param object $requestData
     * 
     * @return array
     */
    public function generateInsertDataForAg($requestData)
    {
        $apiId = !empty($this->getSysObjectGamePlatformId()) ? $this->getSysObjectGamePlatformId() : null;
        $netAmount = $this->getAttributeInObject($requestData->Record,'netAmount');
        $validBetAmount = $this->getAttributeInObject($requestData->Record,'validBetAmount');
        $netBet = $netAmount + $validBetAmount;
        $value = $this->getAttributeInObject($requestData->Record,'value',$netBet);
        $amount = $this->getAttributeInObject($requestData->Record,'amount',$value);
        $sessionToken = $this->getAttributeInObject($requestData->Record,'sessionToken');
        $gameCode = $this->getAttributeInObject($requestData->Record,'gameCode');
        $gameType = $this->getAttributeInObject($requestData->Record,'gametype');
        $gameId = $this->getAttributeInObject($requestData->Record,'gameId',$gameType);
        $transactionType = $this->generateTransactionType($this->getAttributeInObject($requestData->Record,'transactionType'));        
        $roundId =  $this->getAttributeInObject($requestData->Record,'roundId');
        $roundId =  !empty($roundId) ? $roundId : $gameCode;
        $externalUniqueId =  $this->getAttributeInObject($requestData->Record,'transactionID');
        $external_unique_id = null;

        if(is_array($externalUniqueId) && count($externalUniqueId) > 0){
            foreach($externalUniqueId as $externalUniqueIdVal){
                $external_unique_id .= $externalUniqueIdVal . "_";
            }
        }else{
            $external_unique_id = $externalUniqueId;
        }
        if($transactionType == self::TRANSACTION_REFUND){
            $external_unique_id = $roundId .'-'.$external_unique_id;
        }

        $playname =  $this->getAttributeInObject($requestData->Record,'playname');
        $currency =  $this->getAttributeInObject($requestData->Record,'currency');
        $remark =  $this->getAttributeInObject($requestData->Record,'remark');
        $settletime =  $this->getAttributeInObject($requestData->Record,'settletime');
        $betTime =  $this->getAttributeInObject($requestData->Record,'betTime',$settletime);
        $time = $this->getAttributeInObject($requestData->Record,'time',$betTime);
        $seTime = is_null($time) ? null : ((new DateTime($time))->format('Y-m-d H:i:s'));
        $agentCode = $this->getAttributeInObject($requestData->Record,'agentCode');
        $platformType = $this->getAttributeInObject($requestData->Record,'platformType');
        $round = $this->getAttributeInObject($requestData->Record,'round');
        $tableCode = $this->getAttributeInObject($requestData->Record,'tableCode');
        $transactionCode = $this->getAttributeInObject($requestData->Record,'transactionCode');
        $deviceType = $this->getAttributeInObject($requestData->Record,'deviceType');
        $playtype = $this->getAttributeInObject($requestData->Record,'playtype');
        $billNo = $this->getAttributeInObject($requestData->Record,'billNo');
        $ticketStatus = $this->getAttributeInObject($requestData->Record,'ticketStatus');
        $gameResult = $this->getAttributeInObject($requestData->Record,'gameResult');
        $finish = $this->getAttributeInObject($requestData->Record,'finish');


        $eIData = [
            'sessionToken' => $sessionToken,
            'playname' => $playname,
            'currency' => $currency,
            'roundId' => $roundId,
            'remark' => $remark,
            'isRefunded' => false,
            'agentCode' => $agentCode,
            'platformType' => $platformType,
            'round' => $round,
            'gameType' => $gameType,
            'tableCode' => $tableCode,
            'transactionCode' => $transactionCode,
            'deviceType' => $deviceType,
            'playtype' => $playtype,
            'value' => $value,
            'amount' => $amount,
            'netAmount' => $netAmount,
            'validBetAmount' => $validBetAmount,
            'netBet' => $netBet,
            'agentCode' => $agentCode,
            'billNo' => $billNo,
            'ticketStatus' => $ticketStatus,
            'gameResult' => $gameResult,
            'finish' => $finish
        ];
        $extraInfo = json_encode($eIData);

        $final_external_unique_id = rtrim($external_unique_id,'_')."-".$transactionType;
        if($transactionType == self::TRANSACTION_WIN){
            $final_external_unique_id = rtrim($external_unique_id,'_')."-".$billNo."-".$transactionType;
        }        

        $gameRecords = [
            [
                'game_platform_id' => $apiId,
                'amount' => $amount,
                'game_id' => $gameId,
                'transaction_type' => $transactionType,
                'status' => self::STATUS_OK,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' => $final_external_unique_id,
                'extra_info' => $extraInfo,
                'start_at' => $seTime,
                'end_at' => $seTime,
                'transaction_id' => rtrim($external_unique_id,'_'),
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
            case self::ACTION_ROLLBACK:
                $type = self::TRANSACTION_REFUND;
            break;
            case self::ACTION_BET:
                $type = self::TRANSACTION_BET;
            break;
            case self::ACTION_WIN:
                $type = self::TRANSACTION_WIN;
            break;
            case self::ACTION_LOSE:
                $type = self::TRANSACTION_LOSE;
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
    
    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);

        $game_description = $this->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->gameApiSysLibObj->getPlatformCode(), $this->transaction_for_fast_track['game_id']);
        $betType = null;
        switch($this->transaction_for_fast_track['transaction_type']) {
            case 'bet':
                $betType = 'Bet';
                break;
            case 'win':
            case 'lose':
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
        $this->utils->debug_log("AG SEAMLESS SERVICE: (sendToFastTrack)", $data);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }
    
    public function doInsertToGameTransactions($data)
    {
        $lastInsertId = parent::doInsertToGameTransactions($data);

        $this->transaction_for_fast_track = null;
        if($lastInsertId) {
            $this->transaction_for_fast_track = $data[0];
            $this->transaction_for_fast_track['id'] = $this->common_seamless_wallet_transactions->getLastInsertedId();
        }

        return $lastInsertId;
    }

}
