<?php

if(! defined('BASEPATH')){
    exit('No Direct script access allowed');
}

require_once dirname(__FILE__) . '/Abstract_seamless_service_game_api.php';
require_once dirname(__FILE__) . '/../../../submodules/game-lib/game_platform/hawk_authentication_module.php';

class Lucky_streak_seamless_service_api extends Abstract_Seamless_Service_Game_Api
{
    use Hawk_authentication_module;
    /**
     * API endpoints of game provider in wallet transaction 
     * 
     * @var const
     */
    const ACTION_GET_BALANCE = 'getBalance';
    const ACTION_VALIDATE = 'validate';
    const ACTION_MOVE_FUNDS = 'moveFunds';
    const ACTION_ABORT_FUNDS = 'abortMoveFunds';

    /**
     * method to use for wallet transaction
     * 
     * @var const
     */
    const METHOD_GET_BALANCE = 'balance';
    const METHOD_VALIDATE = 'validate';
    const METHOD_CREDIT = 'credit';
    const METHOD_DEBIT = 'debit';
    const METHOD_REFUND = 'refund';

    /** 
     * Values of title and details response
    */
    const RESPONSE_TITLE_DUPLICATE = 'Duplicate Transaction';
    const RESPONSE_DETAILS_DUPLICATE = 'The Transaction is already Exist';
    const RESPONSE_TITLE_ALREADY_REFUNDED = 'Already refunded';
    const RESPONSE_DETAILS_ALREADY_REFUNDED = 'The Transaction is already refunded';
    const RESPONSE_TITLE_BET_BALANCE_ERROR = 'Not enough balance';
    const RESPONSE_DETAILS_BET_BALANC_ERROR = 'The Balance is not enough to bet';
    const RESPONSE_TITLE_OPERATOR_NOT_FOUND = 'Operator not found';
    const RESPONSE_DETAILS_OPERATOR_NOT_FOUND = 'This Operator not found';
    const RESPONSE_TITLE_PARAM_NOT_VALID = 'Parameter Not Valid';
    const RESPONSE_DETAILS_PARAM_NOT_VALID = 'Parameter Not Valid';
    const RESPONSE_TITLE_HTTP_VERB_NOT_VALID = 'HTTP VERB Not Valid';
    const RESPONSE_DETAILS_HTTP_VERB_NOT_VALID = 'HTTP VERB Not Valid';
    const RESPONSE_TITLE_PLAYER_NOT_EXIST = 'Player is not exist';
    const RESPONSE_DETAILS_PLAYER_NOT_EXIST = 'Player is not exist';
    const RESPONSE_TITLE_TRANSACTION_NOT_EXIST = 'Transaction is not exist';
    const RESPONSE_DETAILS_TRANSACTION_NOT_EXIST = 'Transaction is not exist';
    const RESPONSE_TITLE_AUTH_CODE_ERROR = 'Authentication Error';
    const RESPONSE_DETAILS_AUTH_CODE_ERROR = 'The authentication Code is Expired';
    const RESPONSE_TITLE_AUTH_OR_PARAMETER_ERROR = 'Authentication or Parameter Error';
    const RESPONSE_DETAILS_AUTH_OR_PARAMETER_ERROR = 'Authentication or Parameter Error';
    const RESPONSE_TITLE_AUTH_ERROR = 'Authentication Error';
    const RESPONSE_DETAILS_AUTH_ERROR = 'Authentication  Error';
    const RESPONSE_TITLE_IP_ADDRESS_NOT_ALLOWED = 'IP Address not allowed.';
    const RESPONSE_DETAILS_IP_ADDRESS_NOT_ALLOWED = 'IP Address not allowed.';

    /**
     * array list of API endpoints of game provider in wallet transaction
     * 
     * @var const API_ACTIONS
     */
    const API_ACTIONS = [
        self::ACTION_GET_BALANCE,
        self::ACTION_VALIDATE,
        self::ACTION_MOVE_FUNDS,
        self::ACTION_ABORT_FUNDS
    ];


    /**
     * Success status Code
     */
    const STATUS_CODE_OK = 'OK';
    const STATUS_CODE_SUCCES = 0;

    /** 
     * @var string $rTransactionType
     * 
    */
    protected $rTransactionType;

    /** 
     *  Lucky Streak Request Data to us
     * 
     * @var object $lsSeamlessRequestData
     * 
    */
    protected $lsSeamlessRequestData;

    /** 
     *  Lucky Streak Response Data for them
     * 
     * @var object $agSeamlessrequestData
     * 
    */
    protected $lsSeamlessResponseData;

    /**
     * The current method executing in the class
     * 
     * @var string $currentMethod
     */
    protected $currentMethod;

    /**
     * determine if auth hawk is authenticated
     * 
     * @var boolern $auth
     */
    protected $auth = false;

    /**
     * header to pass in the response
     * 
     * @var boolern $lsHeader
     */
    protected $lsHeader = null;

    private $transaction_for_fast_track = null;
    private $http_status_code;

    public function __construct()
    {
        parent::__construct();

        /** STD class for Lucky streak seamless request data */
        $this->lsSeamlessRequestData = new stdClass();
        /** STD class for Lucky streak seamless request data */
        $this->lsSeamlessResponseData = new stdClass();
        $this->currentMethod = 'index';
        $this->http_status_code = 200;
        $this->loadModel(['common_seamless_wallet_transactions','common_token']);
    }

    /** 
     * Get Platform Code
     * 
     * @return int
    */
    public function getPlatformCode()
    {
        return LUCKY_STREAK_SEAMLESS_THB1_API;
    }

    public function index($m)
    {
        if (!$this->gameApiSysLibObj->validateWhiteIP()) {
            $this->currentMethod = 'validateWhiteIP';
            $this->http_status_code = 401;
            return $this->outputError('ERR-FUND-001', $this->http_status_code, self::RESPONSE_TITLE_IP_ADDRESS_NOT_ALLOWED, self::RESPONSE_DETAILS_IP_ADDRESS_NOT_ALLOWED);
        }

        # check if method is POST in the request
        if(! $this->isPostMethod()){
            return $this->outputError('ERR-FUND-001',409,self::RESPONSE_TITLE_HTTP_VERB_NOT_VALID,self::RESPONSE_DETAILS_HTTP_VERB_NOT_VALID);
        }

        if(in_array((string) $m,self::API_ACTIONS)){
            $this->lsSeamlessRequestData = $this->request();
            try{
                $hawkAuthentication = $this->gameApiSysLibObj->hawkAuthentication;

                if($hawkAuthentication){
                    $hmackKey =  $this->gameApiSysLibObj->hmacKey;
                    $rltHawk = $this->requestAuthorization($hmackKey);

                    if(isset($rltHawk['status']) && $rltHawk['status']){
                        $this->auth = true;
                        $this->lsHeader = isset($rltHawk['header']) ? $rltHawk['header'] : null;
                        $rm = $this->generateExecuteMethod($m);
                        return $this->$rm();
                    }else{
                        # authentication error
                        return $this->outputError('ERR-GNRL-999',403,self::RESPONSE_TITLE_AUTH_ERROR,self::RESPONSE_DETAILS_AUTH_ERROR);
                    }
                }else{
                    $rm = $this->generateExecuteMethod($m);
                    return $this->$rm();
                }
            }catch(\Exception $e){
                # transaction type is not exist or authentication error
                return $this->outputError('ERR-GNRL-999',500,self::RESPONSE_TITLE_AUTH_OR_PARAMETER_ERROR,self::RESPONSE_DETAILS_AUTH_OR_PARAMETER_ERROR);
            }
        }
        return $this->outputError('ERR-FUND-001',409,self::RESPONSE_TITLE_PARAM_NOT_VALID,self::RESPONSE_DETAILS_PARAM_NOT_VALID);
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
        $this->currentMethod = __FUNCTION__;
        $playerName = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'username');
        $playerCurrentToken = $this->gameApiSysLibObj->getPlayerTokenByUsername($playerName);
        $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
        $isPlayerExist = $this->isPlayerExistInProvider($playerName,$gamePlatformCode);

        if($this->isTokenValid($playerCurrentToken)){
            $transactionId = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'transactionRequestId');
            $amount = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'amount');
            $direction = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'direction');
            $currency = $this->gameApiSysLibObj->gameCurrency();
            $playerId = $this->common_token->getPlayerIdFromAuthToken($playerCurrentToken);
            $this->lsSeamlessRequestData->lsPlayerid = $playerId;
            $bt = $this->generateMicroDateTimeSecondsNow();
            $pb = $this->gameApiSysLibObj->queryPlayerBalance($playerName);
            $playerBalance = $this->getProperBalance($pb);

            if($isPlayerExist){
                $isBet = ($direction == ucfirst(self::METHOD_DEBIT));
                $checkpoint = $this->validityCheckpoint($playerBalance,$amount,$playerName,$playerId,$transactionId,$isBet);

                if($checkpoint && $checkpoint != self::RESPONSE_TITLE_ALREADY_REFUNDED){
                    return $this->outputStandardResponse();
                }

                $this->lsSeamlessResponseData->refTransactionId = $uniqId = uniqId();
                $data = $this->generateInsertDataForLs($this->lsSeamlessRequestData);

                if($checkpoint && $checkpoint == self::RESPONSE_TITLE_ALREADY_REFUNDED){
                    # already refunded
                    $isSucess = true;

                    try{
                        $this->lsSeamlessRequestData->lsRefunded = true;
                        $data = $this->generateInsertDataForLs($this->lsSeamlessRequestData);
                        $data[0]['after_balance'] = $playerBalance;
                        $data[0]['before_balance'] = $playerBalance;
                        $data[0]['player_id'] = $playerId;
                        $this->doInsertToGameTransactions($data);
                    }catch(\Exception $e){
                        $isSucess = false;
                    }
                }else{

                    if($direction == ucfirst(self::METHOD_DEBIT)){
                        $isSucess = $this->doDeduct($playerId,$amount,$data);
                    }elseif($direction == ucfirst(self::METHOD_CREDIT)){
                        $this->currentMethod = 'credit';
                        $isSucess = $this->doIncrement($playerId,$amount,$data);
                    }
                    else{
                        $isSucess = false;
                        # will not happen, because we have filter in entry method
                        return $this->outputError('ERR-FUND-001',409,self::RESPONSE_TITLE_PARAM_NOT_VALID,self::RESPONSE_DETAILS_PARAM_NOT_VALID);
                    }

                }

                if($isSucess){
                    $this->lsSeamlessResponseData->lsStatusCode = 'OK';
                    $this->lsSeamlessResponseData->refTransactionId = $uniqId;
                    $this->lsSeamlessResponseData->currency = $currency;
                    $this->lsSeamlessResponseData->balanceTimestamp = $bt;
                    $this->lsSeamlessResponseData->ubalance = true;
                    return $this->outputStandardResponse();
                }else{
                    # retry it
                    return $this->outputError('ERR-FUND-003',409,null,null);
                }
            }
        }
        # Authentication Code Validation Failure
        return $this->outputError('ERR-AUTH-001',409,self::RESPONSE_TITLE_AUTH_CODE_ERROR,self::RESPONSE_DETAILS_AUTH_CODE_ERROR);
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
        # do debit but increment
        $this->debit();
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
        $this->currentMethod = __FUNCTION__;
        $direction = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'direction');
        $playerName = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'username');
        $amount = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'amount');
        $transactionId = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'transactionRequestId');
        $abortedTransactionRequestId = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'abortedTransactionRequestId');
        $currency = $this->gameApiSysLibObj->gameCurrency();
        $playerCurrentToken = $this->gameApiSysLibObj->getPlayerTokenByUsername($playerName);
        $playerId = $this->common_token->getPlayerIdFromAuthToken($playerCurrentToken);
        $this->lsSeamlessRequestData->lsPlayerid = $playerId;
        $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
        $pb = $this->gameApiSysLibObj->queryPlayerBalance($playerName);
        $playerBalance = $this->getProperBalance($pb);
        $isPlayerExist = $this->isPlayerExistInProvider($playerName,$gamePlatformCode);
        $bt = $this->generateMicroDateTimeSecondsNow();

        if($this->isTokenValid($playerCurrentToken)){

            if($isPlayerExist){

                $checkpoint = $this->validityCheckpoint($playerBalance,$amount,$playerName,$playerId,null,false,false);

                if($checkpoint && $checkpoint != self::RESPONSE_TITLE_ALREADY_REFUNDED){
                    return $this->outputStandardResponse();
                }

                $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(),$abortedTransactionRequestId);

                # check if valid method
                if($direction != ucfirst(self::METHOD_CREDIT) && $direction != ucfirst(self::METHOD_DEBIT)){
                    return $this->outputError('ERR-FUND-001',409,self::RESPONSE_TITLE_PARAM_NOT_VALID,self::RESPONSE_DETAILS_PARAM_NOT_VALID);
                }

                $isAlreadyRefunded = $this->common_seamless_wallet_transactions->isTransactionAlreadyRefunded($abortedTransactionRequestId);

                # check if already refunded
                if($isAlreadyRefunded){
                    return $this->outputError('ERR-FUND-001',404,self::RESPONSE_TITLE_ALREADY_REFUNDED,self::RESPONSE_DETAILS_ALREADY_REFUNDED);
                }else{
                    if($isTransactionExist){
                        # update status of transaction to refunded
                        $this->common_seamless_wallet_transactions->updateRefundedTransaction($abortedTransactionRequestId);
                    }
                }

                $uniqId = uniqId();
                $data = $this->generateInsertDataForLs($this->lsSeamlessRequestData);
                $isSucess = false;

                # credit means the transaction to be aborted is credit same logic if debit
                if($direction == ucfirst(self::METHOD_CREDIT)){
                    $isSucess = $this->doDeduct($playerId,$amount,$data);
                }elseif($direction == ucfirst(self::METHOD_DEBIT)){
                    $isSucess = $this->doIncrement($playerId,$amount,$data);
                }else{
                    $isSucess = false;
                }

                if($isSucess){
                    $this->lsSeamlessResponseData->lsStatusCode = 'OK';
                    $this->lsSeamlessResponseData->refAbortTransactionId = $uniqId;
                    $this->lsSeamlessResponseData->currency = $currency;
                    $this->lsSeamlessResponseData->balanceTimestamp = $bt;
                    $this->lsSeamlessResponseData->ubalance = true;
                    return $this->outputStandardResponse();
                }else{
                    # retry it
                    return $this->outputError('ERR-FUND-003',409,null,null);
                }
            }
        }

        # Authentication Code Validation Failure
        return $this->outputError('ERR-AUTH-001',409,self::RESPONSE_TITLE_AUTH_CODE_ERROR,self::RESPONSE_DETAILS_AUTH_CODE_ERROR);
    }

    /**
     * Get Balance of Player
     * TODO add checking of operator ID request param
     */
    public function balance(){
        $this->currentMethod = __FUNCTION__;
        $currency = $this->gameApiSysLibObj->gameCurrency();
        $playerName = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'username');
        $pb = $this->gameApiSysLibObj->queryPlayerBalance($playerName);
        $playerBalance = $this->getProperBalance($pb);
        $playerCurrentToken = $this->gameApiSysLibObj->getPlayerTokenByUsername($playerName);
        $playerId = $this->common_token->getPlayerIdFromAuthToken($playerCurrentToken);
        $this->lsSeamlessRequestData->lsPlayerid = $playerId;
        $isTokenValid = $this->isTokenValid($playerCurrentToken);
        $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
        $isPlayerExist = $this->isPlayerExistInProvider($playerName,$gamePlatformCode);
        $bt = $this->generateMicroDateTimeSecondsNow();

        if($isTokenValid){

            if($isPlayerExist){
                $this->lsSeamlessResponseData->lsStatusCode = 'OK';
                $this->lsSeamlessResponseData->currency = $currency;
                $this->lsSeamlessResponseData->balance = $playerBalance;
                $this->lsSeamlessResponseData->balanceTimestamp = $bt;
                $this->lsSeamlessResponseData->errorCode = self::STATUS_CODE_SUCCES;

                return $this->outputStandardResponse();
            }
                # Authentication Code Validation Failure
                return $this->outputError('ERR-FUND-004',404,self::RESPONSE_TITLE_PLAYER_NOT_EXIST,self::RESPONSE_DETAILS_PLAYER_NOT_EXIST);
        }

        # Authentication Code Validation Failure
        return $this->outputError('ERR-AUTH-001',409,self::RESPONSE_TITLE_AUTH_CODE_ERROR,self::RESPONSE_DETAILS_AUTH_CODE_ERROR);
    }

    /** 
     * 
    */
    public function validate()
    {
        $this->currentMethod = __FUNCTION__;
        $operatorName = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'OperatorName');

        $isOperatorExist = $this->isOperatorExist($operatorName);

        if(!$isOperatorExist){
            return $this->outputStandardResponse();
        }

        $this->loadModel(['common_token','player_model','game_provider_auth']);
        $token = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'AuthorizationCode');
        $gamePlatformCode = $this->gameApiSysLibObj->getPlatformCode();
        $currency = $this->gameApiSysLibObj->gameCurrency();
        $language = $this->getAttributeInObject($this->gameApiSysLibObj,'language');
        $playerId = $this->common_token->getPlayerIdFromAuthToken($token);
        $this->lsSeamlessRequestData->lsPlayerid = $playerId;
        $username = $this->player_model->getUsernameById($playerId);
        $isPlayerExist = $this->isPlayerExistInProvider($username,$gamePlatformCode);
        $bt = $this->generateMicroDateTimeSecondsNow();
        $pb = $this->gameApiSysLibObj->queryPlayerBalance($username);
        $playerBalance = $this->getProperBalance($pb);

        if($this->isTokenValid($token)){

            if($isPlayerExist){
                $this->lsSeamlessResponseData->lsStatusCode = 'OK';
                $this->lsSeamlessResponseData->authorizationCode = $token;
                $this->lsSeamlessResponseData->username = $username;
                $this->lsSeamlessResponseData->currency = $currency;
                $this->lsSeamlessResponseData->language = $language;
                $this->lsSeamlessResponseData->nickname = $username;
                $this->lsSeamlessResponseData->lastUpdateDate = $bt;
                $this->lsSeamlessResponseData->balance = $playerBalance;
                $this->lsSeamlessResponseData->balanceTimestamp = $bt;
                $this->lsSeamlessResponseData->additionalFields = null;
                $this->lsSeamlessResponseData->errorMessage = null;
                $this->lsSeamlessResponseData->isError = false;
                return $this->outputStandardResponse();
            }

            # Authentication Code Validation Failure
            return $this->outputError('ERR-FUND-004',404,self::RESPONSE_TITLE_PLAYER_NOT_EXIST,self::RESPONSE_DETAILS_PLAYER_NOT_EXIST);
        }

        # Authentication Code Validation Failure
        return $this->outputError('ERR-AUTH-001',409,self::RESPONSE_TITLE_AUTH_CODE_ERROR,self::RESPONSE_DETAILS_AUTH_CODE_ERROR);
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
    public function validityCheckpoint($before_balance,$betAmount,$playerName,$playerId,$transactionId,$is_bet=false,$is_not_refund=true)
    {
        $isAllowedToBet = $this->utils->compareResultFloat($before_balance,">=",$betAmount); # check if current balance is greater than bet
        $operatorId = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'operatorId');
        $apiOperatorid = $this->getAttributeInObject($this->gameApiSysLibObj,'operatorID');

        if(! $isAllowedToBet && $is_bet){

            $this->lsSeamlessResponseData->lsStatusCode = 'ERR-FUND-001';
            $this->lsSeamlessResponseData->lsStatusHeaderCode = 409;
            $this->lsSeamlessResponseData->lsTitle = self::RESPONSE_TITLE_BET_BALANCE_ERROR;
            $this->lsSeamlessResponseData->lsDetail = self::RESPONSE_DETAILS_BET_BALANC_ERROR;

            return $this->lsSeamlessResponseData->lsStatusCode;
        }
        # we check here if refund came first before the BET
        if($is_bet){
            $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(),$transactionId);
            if(! $exist){
                $isRefundedAlready = $this->common_seamless_wallet_transactions->checkIfBetHaveRefund($this->getPlatformCode(),
                $transactionId);

                if($isRefundedAlready){
                    return self::RESPONSE_TITLE_ALREADY_REFUNDED;
                }
            }
        }

        if(! is_null($operatorId)){
            if($operatorId != $apiOperatorid){
                $this->lsSeamlessResponseData->lsStatusCode = 'ERR-FUND-001';
                $this->lsSeamlessResponseData->lsStatusHeaderCode = 409;
                $this->lsSeamlessResponseData->lsTitle = self::RESPONSE_TITLE_OPERATOR_NOT_FOUND;
                $this->lsSeamlessResponseData->lsDetail = self::RESPONSE_DETAILS_OPERATOR_NOT_FOUND;

                return $this->lsSeamlessResponseData->lsStatusCode;
            }
        }

        $isPlayerBlockedAll = $this->player_model->isBlocked($playerId); # block in player table

        if($this->gameApiSysLibObj->isBlocked($playerName) || $isPlayerBlockedAll){

            $this->lsSeamlessResponseData->lsStatusCode = 'ERR-FUND-004';
            $this->lsSeamlessResponseData->lsStatusHeaderCode = 409;

            return $this->lsSeamlessResponseData->lsStatusCode;
        }

        if($this->common_seamless_wallet_transactions->isTransactionExist($this->getPlatformCode(),$transactionId) && $is_not_refund){

            $this->lsSeamlessResponseData->lsStatusCode = 'ERR-FUND-001';
            $this->lsSeamlessResponseData->lsStatusHeaderCode = 409;
            $this->lsSeamlessResponseData->lsTitle = self::RESPONSE_TITLE_DUPLICATE;
            $this->lsSeamlessResponseData->lsDetail = self::RESPONSE_DETAILS_DUPLICATE;

            return $this->lsSeamlessResponseData->lsStatusCode;
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
    public function generateInsertDataForLs($requestData)
    {
        $apiId = !empty($this->getSysObjectGamePlatformId()) ? $this->getSysObjectGamePlatformId() : null;
        $amount = $this->getAttributeInObject($requestData->data,'amount');
        $gameId = $this->getAttributeInObject($requestData->data,'gameId');
        $transactionType = $this->generateTransactionType($this->getAttributeInObject($requestData->data,'direction'));
        $abortedTransactionRequestId =  $this->getAttributeInObject($requestData->data,'abortedTransactionRequestId');

        if(! is_null($abortedTransactionRequestId)){
            $transactionType = self::TRANSACTION_REFUND;
        }

        $roundId =  $this->getAttributeInObject($requestData->data,'roundId');
        $operatorId =  $this->getAttributeInObject($requestData->data,'operatorId');
        $externalUniqueId =  $this->getAttributeInObject($requestData->data,'transactionRequestId');
        $username = $this->getAttributeInObject($requestData->data,'username');
        $eventType = $this->getAttributeInObject($requestData->data,'eventType');
        $eventSubType = $this->getAttributeInObject($requestData->data,'eventSubType');
        $eventId = $this->getAttributeInObject($requestData->data,'eventId');
        $eventTime = $this->getAttributeInObject($requestData->data,'eventTime');
        $currency = $this->getAttributeInObject($requestData->data,'currency');
        $eventDetails = $this->getAttributeInObject($requestData->data,'eventDetails');
        $gameType = $this->getAttributeInObject($requestData->data,'gameType');
        $refTransactionId = $this->getAttributeInObject($this->lsSeamlessResponseData,'refTransactionId');
        if($transactionType == self::TRANSACTION_REFUND){
            $externalUniqueId = $externalUniqueId .'-'.$abortedTransactionRequestId;
        }
        $abortTime = $this->getAttributeInObject($requestData->data,'abortTime');
        $eventTime = $this->getAttributeInObject($requestData->data,'eventTime',$abortTime);
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $startTime = is_null($eventTime) ? null : $this->gameApiSysLibObj->gameTimeToServerTime((new DateTime($eventTime))->format('Y-m-d H:i:s'));
        $isRefunded = property_exists($this->lsSeamlessRequestData,'lsRefunded') ? true : false;


        $eIData = [
            'roundId' => $roundId,
            'operatorId' => $operatorId,
            'username' => $username,
            'eventType' => $eventType,
            'eventSubType' => $eventSubType,
            'eventId' => $eventId,
            'currency' => $currency,
            'eventDetails' => $eventDetails,
            'gameType' => $gameType,
            'refTransactionId' => $refTransactionId,
            'isRefunded' => $isRefunded,
            'abortedTransactionRequestId' => $abortedTransactionRequestId,
            'eventTime' => $eventTime
        ];

        $extraInfo = json_encode($eIData);

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
                'transaction_id' => $externalUniqueId
            ]
        ];

        $this->processGameRecords($gameRecords);

        return $gameRecords;
    }

    /**
     * Generate Method to be executed
     * 
     * @param string $type
     * @return string
     */
    public function generateExecuteMethod($method)
    {
        switch($method)
        {
            case self::ACTION_GET_BALANCE:
                $method = self::METHOD_GET_BALANCE;
            break;
            case self::ACTION_VALIDATE:
                $method = self::METHOD_VALIDATE;
            break;
            case self::ACTION_MOVE_FUNDS:
                if(property_exists($this->lsSeamlessRequestData->data,'direction')){
                    $t = $this->lsSeamlessRequestData->data->direction;
                    if($t == ucfirst(self::METHOD_DEBIT)){
                        $method = self::METHOD_DEBIT;
                    }elseif($t == ucfirst(self::METHOD_CREDIT)){
                        $method = self::METHOD_CREDIT;
                    }
                }
            break;
            case self::ACTION_ABORT_FUNDS:
                $method = self::METHOD_REFUND;
            break;
        }

        return $method;
    }

    /**
     * Output Standard Response
     * 
     * @param object $lSObject
     * 
     * @return object
     * 
     */
    public function outputStandardResponse($lSObject=null)
    {
        $this->loadModel(['common_seamless_error_logs']);
        $unsetR = ['lsStatusCode','responseData'];
        $lSObject = is_null($lSObject) ? $this->lsSeamlessResponseData : $lSObject;
        $statusHeaderCode = $this->getAttributeInObject($this->lsSeamlessResponseData,'lsStatusHeaderCode',200);
        $statusCode = $this->getAttributeInObject($this->lsSeamlessResponseData,'lsStatusCode','ERROR');
        $title = $this->getAttributeInObject($this->lsSeamlessResponseData,'lsTitle',null);
        $detail = $this->getAttributeInObject($this->lsSeamlessResponseData,'lsDetail',null);
        $additionalFields = $this->getAttributeInObject($this->lsSeamlessResponseData,'lsAdditionalFields',null);

        foreach($this->lsSeamlessResponseData as $key => $val){
            if(! in_array($key,$unsetR)){
                $this->lsSeamlessResponseData->responseData['data'][$key] = $val;
            }
        }
        $this->lsSeamlessResponseData->responseData['errors'] = null;
        if(property_exists($this->lsSeamlessResponseData,'ubalance') && $this->lsSeamlessResponseData->ubalance){
            $playerName = $this->getAttributeInObject($this->lsSeamlessRequestData->data,'username');
            if(! empty($playerName)){
                $pb = $this->gameApiSysLibObj->queryPlayerBalance($playerName);
                $playerBalance = $this->getProperBalance($pb);
                $this->lsSeamlessResponseData->responseData['data']['balance'] = $playerBalance;
            }
        }
        unset($this->lsSeamlessResponseData->responseData['data']['ubalance']);
        $playerId = null;
        $flag = Response_result::FLAG_NORMAL;
        if(property_exists($this->lsSeamlessRequestData,'lsPlayerid')){
            $playerId = $this->lsSeamlessRequestData->lsPlayerid;
            unset($this->lsSeamlessRequestData->lsPlayerid);
        }

        if($statusCode != self::STATUS_CODE_OK){
            $this->lsSeamlessResponseData->responseData['data'] = null;
            $this->lsSeamlessResponseData->responseData['errors']['code'] = $statusCode;
            $this->lsSeamlessResponseData->responseData['errors']['title'] = $title;
            $this->lsSeamlessResponseData->responseData['errors']['detail'] = $detail;
            $this->lsSeamlessResponseData->responseData['errors']['additional_fields'] = $additionalFields;
            $flag = Response_result::FLAG_ERROR;
        }

        $jContent = is_array($lSObject->responseData) ? json_encode($lSObject->responseData) : $lSObject->responseData;

        $rId = $this->saveToResponseResult($this->currentMethod,(array)$this->lsSeamlessRequestData,null,['content'=>$jContent,'player_id'=>$playerId,'flag'=>$flag], $this->http_status_code, $flag);

        if($statusCode != self::STATUS_CODE_OK){
            $gamePlatformId = $this->getPlatformCode();
            $response_result_id = $rId;
            $request_id = $this->utils->getRequestId();
            $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
            $now = $this->utils->getNowForMysql();
            $commonSeamlessErrorDetails = json_encode($this->lsSeamlessResponseData->responseData);
            $insertData = [
                'game_platform_id' => $gamePlatformId,
                'response_result_id' => $response_result_id,
                'request_id' => $request_id,
                'elapsed_time' => $elapsed,
                'error_date' => $now,
                'extra_info' => $commonSeamlessErrorDetails
            ];
            try{
                $this->common_seamless_error_logs->insertTransaction($insertData);
            }catch(\Exception $e){
                $this->utils->error_log(__METHOD__.' error inserting into common_seamless_error_log',$e->getMessage());
            }
        }

        $this->utils->debug_log(__METHOD__.' with data of: >>>>>>>>',$jContent);
        $header = is_null($this->lsHeader) ? [] : ['Server-Authorization: '.$this->lsHeader];
        return $this->outputHttpResponse($lSObject->responseData,$statusHeaderCode,$header);
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
            case ucfirst(self::METHOD_DEBIT):
                $type = self::TRANSACTION_BET;
            break;
            case ucfirst(self::METHOD_CREDIT):
                $type = self::TRANSACTION_WIN;
            break;
            case self::METHOD_REFUND:
                $type = self::TRANSACTION_REFUND;
            break;
            default:
                $type = null;
            break;
        }

        return $type;
    }

    /** 
     * Get Proper format of balance
     * @param mixed $balance
     * 
     * @return int
    */
    public function getProperBalance($balance)
    {
        if(! isset($balance['balance'])){
            $playerBalance = null;
        }else{
            $playerBalance = number_format($this->gameApiSysLibObj->round_down($balance['balance']),4,'.','');
        }

        return $playerBalance;
    }

    /** 
     * Validate if Operator name is right
     * @param string|null $operatorName
     * @return boolean
    */
    public function isOperatorExist($operatorName=null)
    {
        $exist = false;
        $apiOperatorName = $this->getAttributeInObject($this->gameApiSysLibObj,'operatorName');
        if(! is_null($operatorName)){
            if($operatorName != $apiOperatorName){
                $exist = false;
                $this->lsSeamlessResponseData->lsStatusCode = 'ERR-FUND-001';
                $this->lsSeamlessResponseData->lsStatusHeaderCode = 409;
                $this->lsSeamlessResponseData->lsTitle = self::RESPONSE_TITLE_OPERATOR_NOT_FOUND;
                $this->lsSeamlessResponseData->lsDetail = self::RESPONSE_DETAILS_OPERATOR_NOT_FOUND;
            }else{
                $exist = true;
            }
        }

        return $exist;
    }

    /**
     * Output error
     * @param string $statusCode
     * @param int $headerCode
     * @param string $title
     * @param string $detail
     * 
     * @return object
     */
    public function outputError($statusCode,$headerCode=200,$title=null,$detail=null)
    {
        $this->lsSeamlessResponseData->lsStatusCode = $statusCode;
        $this->lsSeamlessResponseData->lsStatusHeaderCode = $headerCode;
        $this->lsSeamlessResponseData->lsTitle = $title;
        $this->lsSeamlessResponseData->lsDetail = $detail;

        return $this->outputStandardResponse();
    }

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);

        $this->utils->debug_log("Lucky Streak: (sendToFastTrack) transaction_for_fast_track", $this->transaction_for_fast_track);
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
            case 'refund':
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

        $this->utils->debug_log("Lucky Streak: (sendToFastTrack) data", $data);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

    public function doInsertToGameTransactions($data)
    {
        $lastInsertId = parent::doInsertToGameTransactions($data);

        $this->transaction_for_fast_track = null;
        if($lastInsertId) {
            $this->transaction_for_fast_track = $data[0];
            $this->transaction_for_fast_track['id'] = $this->CI->common_seamless_wallet_transactions->getLastInsertedId();
        }

        return $lastInsertId;
    }

    public function outputHttpResponse($data=[],$statusHeader=400,$header=[],$responseResultId=null,$playerId=null,$flag=2)
    {
        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration')) {
            $this->sendToFastTrack();
        }
        return parent::outputHttpResponse($data, $statusHeader, $header, $responseResultId, $playerId, $flag);
    }

}
