<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/*
The financial integration is built out of these requests:
• Authentication – Validating the player before launching the game.
• Debit – Committing a wager of the player.
• Credit – Crediting the player from a win in a game.
• EndGame – End game round.
• DebitCredit – Usually for instant-win games.
• GetBalance – Receive the balance of the player once in a while during the game.
• CancelTransaction – When a game has to refund a wager or win.
• AwardFreeRoundsPoints – Sent after the player finished all his free rounds.
• RegulationNotification - Sent every 60 minutes from the time of login.
• PortugueseNotification - Sent to the operator immediately after the round is successfully
ended. 
*/

class Pariplay_service_api extends BaseController {

    const SUCCESS = 0;
    const INSUFFICIENTFUNDS = 1; //Player doesn't have enough funds in order to make a wager.
    const INVALIDNEGATIVEAMOUNT = 3; //Amount is negative.
    const AUTHENTICATIONFAILED = 4; //The credentials provided in the API are wrong.
    const TOKENEXPIRED = 5; //Player token has expired.
    const UNKNOWNTRANSACTIONID = 6; //Transaction Id was not found.
    const INVALIDGAME = 7; //Game was not found.
    const INVALIDTOKEN = 8; //Token was not found. Note: This error should be returned if the token was not found on the operator system/DB.
    const INVALIDROUND = 9; //Round was not found.
    const INVALIDUSERID = 10; //Player Id was not found
    const TRANSACTIONALREADYSETTLED = 11; //The debit or credit was already settled. 
    const ACCOUNTISLOCKED = 12;// User account is locked Note: return this error for Authenticate, DebitAndCreditand Debit methods only. Actions like canceltransaction or credit should always pass even when locked.
    const PLAYERLIMITEXCEEDEDTURNOVER = 13;// Player responsible gaming rule.
    const PLAYERLIMITEXCEEDEDSESSIONTIME = 14;// Player responsible gaming rule.
    const PLAYERLIMITEXCEEDEDSESSIONSTAKE  = 15;// Player responsible gaming rule.
    const PLAYERLIMITEXCEEDEDSESSIONLOSS  = 16;// Player responsible gaming rule.
    const ROUNDALREADYENDED  = 17;// Round already ended and can't accept any more wagers or winnings.
    const TRANSACTIONALREADYCANCELLED = 18;// The transaction was already cancelled
    const PLAYERHAVEOPENEDROUNDS = 19;// in case the player have opened rounds for the played game (relevant only for Debit calls)
    const NOROUNDSLEFT = 20;// The player tries to make a bet and he don't have any rounds left. Relevant only when the FinancialMode = 1 (Ecommerce mode) in the AuthenticationResponse.
    const BETAMOUNTUNDERMINIMUM = 21;// The player is trying to place a bet with bet, less than the minimum.
    const DAILYTIMELIMIT = 22;// When reaching the daily time limit inside a game, a daily time limit message should be displayed
    const WEEKLYTIMELIMIT = 23;// When reaching the weekly time limit inside a game, a weekly time limit message should be displayed
    const MONTHLYTIMELIMIT = 24;// When reaching the monthly time limit inside a game, a monthly time limit message should be displayed
    const RCREGULATIONFAILED = 25;// If the regulation fails.
    const RESPONSIBLEGAMINGLIMITREACHED = 26;// Responsible gaming limit was reached.
    const SESSIONTIMELIMITREACHED = 27;// Spanish regulation -Time limit reached
    const SESSIONLOSSLIMITREACHED = 28;// Spanish regulation - Loss limit reached
    const INVALIDGAMEPLATFORM = 29;// Invalid game platform type
    const INVALIDCURRENCY = 30;// Invalid player currency
    const GEOLOCATION = 31;// To deposit and place bets on Play Alberta, you must be located within the province of Alberta. At this time, we are unable to verify that your current location is within Alberta.
    const GENERALERROR = 900;// An unexpected error occurred. 
    const INVALIDIP = 999;// An invalid ip error occurred. 
    const STATUS_CODE_INVALID_IP = 401;

   

    const ALLOWED_METHOD_PARAMS = ['authenticate', 'getBalance', 'debit', 'credit', 'endGame', 'debitAndCredit', 'awardFreeRoundsPoints', 'cancelTransaction', 'gameList', 'createToken', 'closeOpenedRounds', 'getSportRoundWithMissingEndgame', 'getAllFreeBets', 'bonusTriggerFreeBet', 'resettlement'];
    const FUNC_REQ_AMOUNT = ['debit', 'credit', 'debitAndCredit', 'awardFreeRoundsPoints', 'cancelTransaction'];
    const FUNC_REQ_ROUND = ['credit', 'endGame', 'cancelTransaction','debitAndCredit'];
    const TRANSACTION_ENDPOINT = ['credit', 'debit', 'endGame', 'debitAndCredit', 'cancelTransaction'];
    const AUTOMATED_TEST_SUIT_FUNC = ['closeOpenedRounds'];
    const FUNC_ALLOWED_LOCKED_PLAYER = ['getBalance', 'createToken'];
    const ROUND_FINISHED = 1;
    const ROUND_UNFINISHED = 0;
    const ROUND_CANCEL = 2;
    const ENTIRE_ROUND_CANCEL = 3;
    const ROUND_REOPEN = 4;

    const ALLOWNEGATIVEDEBIT = 2;
    const ALLOWCREDITWITHOUTDEBIT = 3;
    const DONTALLOWTOCANCELENDEDROUNDTRANSACTIONS = 4;
    const ALLOWALLWAGERSAFTERROUNDEND = 6;
    const ALLOWTOCANCELENDEDROUNDTRANSACTIONS = 9;
    const WALLET_MODE = 0;
    const GAMECODE_ALLOWED_HAVE_OPEN_ROUNDS_ON_DEBIT = ['BTO_BtoBetSport'];#sports
    const FUNC_ALLOWED_EXPIRED_TOKEN = ['credit', 'endGame', 'awardFreeRoundsPoints', 'cancelTransaction'];
    const GAMECODE_ALLOWED_EXPIRED_TOKEN = ['BTO_BtoBetSport'];#sports
    const FUNC_ALLOWED_ON_MAINTENANCE = ['credit', 'endGame', 'awardFreeRoundsPoints', 'cancelTransaction', 'gameList', 'createToken', 'closeOpenedRounds'];
    const GAMECODE_ALLOWED_RESETTLEMENT = ['BTO_BtoBetSport'];

    const PARENT_GAME_PLATFORM_ID = PARIPLAY_SEAMLESS_API;

    
    public function __construct() {
        parent::__construct();
        $this->load->model(array('external_common_tokens','common_seamless_wallet_transactions','common_seamless_error_logs','external_system','game_description_model', 'common_game_free_spin_campaign'));
        $this->responseResultId = $this->api = $this->playerId = $this->errorCode = null;
    }

    private function setOutputResponse($errorCode, $response = []) {
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;
        $http_status_code = 0;
        $flag = $errorCode == self::SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($flag == Response_result::FLAG_ERROR){
            if($this->isResettlement){
                $response =  array(
                    "IsSuccessful" => false,
                    "Errors" => array_filter(array(
                        "Description" => isset($response['message']) ? $response['message'] : null,
                        "ErrorNo" => $errorCode,
                    )),
                );
            } else {
                $response =  array(
                    "Error" => array_filter(array(
                         "ErrorCode" => $errorCode,
                         "Balance" => $this->playerId ? $this->getPlayerBalance($lock = false) : null,
                         "Message" => isset($response['message']) ? $response['message'] : null
                    )),
                );
            }

            if($this->isFreeBet){
                $response['Error']['BonusBalance'] = (float)$this->getPlayerFreeBetBalance($lock = false);
            }

            if($this->api){
                $requesId = $this->utils->getRequestId();
                $now = $this->utils->getNowForMysql();
                $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
                $commonSeamlessErrorDetails = json_encode($response);
                $errorLogInsertData = [
                    'game_platform_id' => $this->api->getPlatformCode(),
                    'response_result_id' => $this->responseResultId,
                    'request_id' => $requesId,
                    'elapsed_time' => $elapsed,
                    'error_date' => $now,
                    'extra_info' => $commonSeamlessErrorDetails
                ];
                $this->common_seamless_error_logs->insertTransaction($errorLogInsertData);
            }
        }

        if($this->responseResultId) {
            $disableResponseResultsTableOnly=$this->utils->getConfig('disabled_response_results_table_only');
            if($disableResponseResultsTableOnly){
                $respRlt = $this->response_result->readNewResponseById($this->responseResultId);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->requestHeaders;
                if(isset($response['Error']['ErrorCode']) && $response['Error']['ErrorCode'] == self::INVALIDIP){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                    $response['Error']['ErrorCode'] = self::GENERALERROR;
                }
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
                $respRlt['player_id'] = $this->playerId;
                $this->response_result->updateNewResponse($respRlt);
            } else {
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->responseResultId);
                }
    
                $response_result = $this->response_result->getResponseResultById($this->responseResultId);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
    
                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->requestHeaders;
                if(isset($response['Error']['ErrorCode']) && $response['Error']['ErrorCode'] == self::INVALIDIP){
                    $http_status_code = $content['status_code'] = self::STATUS_CODE_INVALID_IP;
                    $response['Error']['ErrorCode'] = self::GENERALERROR;
                }
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->responseResultId, null, $this->playerId, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        }
        return $this->returnJsonResult((object)$response, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code);
    }

    private function setResponseResult(){
        $response_result_id = $this->response_result->saveResponseResult(
            $this->gamePlatformId,
            Response_result::FLAG_NORMAL,
            $this->requestMethod,
            json_encode($this->request),
            [],#default empty response
            200,
            null,
            null
        );

        return $response_result_id;
    }

    private function getPlatformIdByGameCodePrefix($request){
        /*
        
            R2R Pariplay
            AMT Amatic
            HSW Hacksaw 
            BFE BeeFee
            OTG 1X2 Gaming
            H5 High5
            PS Playson
            ORX Oryx 
        */
        $defaultPlaformId = PARIPLAY_SEAMLESS_API;
        $gameCode = isset($request['GameCode']) ? $request['GameCode'] : null;
        if(!empty($gameCode)){
            $array = explode('_', $gameCode);
            $prefix = isset($array[0]) ? $array[0] : "UNKNOWN";

            switch (strtoupper($prefix)) {
                case 'HSW':
                    $defaultPlaformId = HACKSAW_PARIPLAY_SEAMLESS_API;
                    break;
                case 'AMT':
                    $defaultPlaformId = AMATIC_PARIPLAY_SEAMLESS_API;
                    break;
                case 'BFE':
                    $defaultPlaformId = BEFEE_PARIPLAY_SEAMLESS_API;
                    break;
                case 'OTG':
                    $defaultPlaformId = OTG_GAMING_PARIPLAY_SEAMLESS_API;
                    break;
                case 'PS':
                    $defaultPlaformId = PLAYSON_PARIPLAY_SEAMLESS_API;
                    break;
                case 'H5':
                    $defaultPlaformId = HIGH5_PARIPLAY_SEAMLESS_API;
                    break;
                case 'ORX':
                    $defaultPlaformId = ORYX_PARIPLAY_SEAMLESS_API;
                    break;
                case 'FBM':
                    $defaultPlaformId = FBM_PARIPLAY_SEAMLESS_API;
                    break;
                case 'BMG':
                    $defaultPlaformId = BOOMING_PARIPLAY_SEAMLESS_API;
                    break;
                case 'TCH':
                    $defaultPlaformId = TRIPLECHERRY_PARIPLAY_SEAMLESS_API;
                    break;
                case 'DWI':
                    $defaultPlaformId = DARWIN_PARIPLAY_SEAMLESS_API;
                    break;
                case 'SPE':
                    $defaultPlaformId = SPINOMENAL_PARIPLAY_SEAMLESS_API;
                    break;
                case 'SMS':
                    $defaultPlaformId = SMARTSOFT_PARIPLAY_SEAMLESS_API;
                    break;
                case 'SPB':
                    $defaultPlaformId = SPRIBE_PARIPLAY_SEAMLESS_API;
                    break;
                case 'SM':
                    $defaultPlaformId = SPINMATIC_PARIPLAY_SEAMLESS_API;
                    break;
                case 'PP':
                    $defaultPlaformId = WIZARD_PARIPLAY_SEAMLESS_API;
                    break;
                default:
                    $defaultPlaformId = PARIPLAY_SEAMLESS_API;
                    break;
            }
        }
        return $defaultPlaformId;
    }

    public function index($method = null){
        $this->requestMethod = lcfirst($method);
        $this->request = json_decode(file_get_contents('php://input'), true);
        $this->requestHeaders = $this->input->request_headers();
        $this->gamePlatformId = $this->getPlatformIdByGameCodePrefix($this->request);
        $this->responseResultId = $this->setResponseResult();
        $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);

        $this->cancelEntireRound = isset($this->request['CancelEntireRound']) ? $this->request['CancelEntireRound'] : false;
        $this->allowNegativeDebit = isset($this->request['TransactionConfiguration']) && in_array(self::ALLOWNEGATIVEDEBIT, $this->request['TransactionConfiguration']) ? true : false;
        $this->allowCreditWithoutDebit = isset($this->request['TransactionConfiguration']) && in_array(self::ALLOWCREDITWITHOUTDEBIT, $this->request['TransactionConfiguration']) ? true : false;
        $this->dontAllowToCancelEndedRoundTransactions = isset($this->request['TransactionConfiguration']) && in_array(self::DONTALLOWTOCANCELENDEDROUNDTRANSACTIONS, $this->request['TransactionConfiguration']) ? true : false;
        $this->allowToCancelEndedRoundTransactions = isset($this->request['TransactionConfiguration']) && in_array(self::ALLOWTOCANCELENDEDROUNDTRANSACTIONS, $this->request['TransactionConfiguration']) ? true : false;
        $this->allowAllWagersAfterRoundEnd = isset($this->request['TransactionConfiguration']) && in_array(self::ALLOWALLWAGERSAFTERROUNDEND, $this->request['TransactionConfiguration']) ? true : false;

        $this->allowExpiredOrMissingToken = false;
        if(isset($this->request['GameCode']) && in_array($this->request['GameCode'], self::GAMECODE_ALLOWED_EXPIRED_TOKEN) && in_array($this->requestMethod, self::FUNC_ALLOWED_EXPIRED_TOKEN)){
            $this->allowExpiredOrMissingToken = true;
            $this->allowAllWagersAfterRoundEnd = true;
        }

        $this->isFreeBet = false;
        $this->isResettlement = $this->requestMethod == "resettlement";
        $feature= isset($this->request['Feature']) ? $this->request['Feature'] : null;
        $gameCode = isset($this->request['GameCode']) ? $this->request['GameCode'] : null;
        $bonusId = isset($this->request['FeatureId']) ? $this->request['FeatureId'] : null;
        if($feature  == "BonusWin" && $gameCode == "BTO_BtoBetSport"){
            $this->isFreeBet = true;
            $campaign = $this->common_game_free_spin_campaign->getCampaignDetailsById($bonusId, $this->gamePlatformId);
            if(!$campaign){
                return $this->setOutputResponse(self::GENERALERROR, array("message" => "Bonus not exist."));
            }
        }

        if(empty($this->requestMethod) || 
            !in_array($this->requestMethod, self::ALLOWED_METHOD_PARAMS)
        ){
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            return $this->setOutputResponse(self::INVALIDIP,['message' => "Forbidden: IP address rejected.({$ip})"]);
        }

        switch ( $this->requestMethod ) {
            case 'authenticate':
            case 'getBalance':
                return $this->authenticate();
                break;
            case 'debit':
                if($this->allowNegativeDebit){
                    return $this->processTransactionConfiguration();
                }
                return $this->debit();
                break;
            case 'credit':
                if($this->allowCreditWithoutDebit){
                    return $this->processTransactionConfiguration();
                }
                return $this->credit();
                break;
            case 'endGame':
                return $this->endGame();
                break;
            case 'debitAndCredit':
                return $this->debitAndCredit();
                break;
            case 'awardFreeRoundsPoints':
                return $this->awardFreeRoundsPoints();
                break;
            case 'cancelTransaction':
                if($this->dontAllowToCancelEndedRoundTransactions || $this->allowToCancelEndedRoundTransactions){
                    return $this->processTransactionConfiguration();
                }
                return $this->cancelTransaction();
                break;
            case 'gameList':
                return $this->gameList();
                break;
            case 'createToken':
                return $this->createToken();
                break;
            case 'closeOpenedRounds':
                return $this->closeOpenedRounds();
                break;
            case 'getSportRoundWithMissingEndgame':
                return $this->getSportRoundWithMissingEndgame();
                break;
            case 'getAllFreeBets':
                return $this->getAllFreeBets();
                break;
            case 'bonusTriggerFreeBet':
                return $this->bonusTriggerFreeBet();
                break;
            case 'resettlement':
                return $this->resettlement();
                break;
            
            default:
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                return $this->setOutputResponse(self::GENERALERROR);
                break;
        }
    }

    private function validateRequest() {
        $this->utils->debug_log('pariplay service request_headers', $this->requestHeaders);
        $this->utils->debug_log('pariplay service method', $this->requestMethod);
        $this->utils->debug_log('pariplay service request', $this->request);
        
        if(!$this->responseResultId || 
            !$this->api ||
            !$this->external_system->isGameApiActive($this->gamePlatformId)
        ){
            $this->errorCode = self::GENERALERROR;
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return false;
        }

        if(!in_array($this->requestMethod, self::FUNC_ALLOWED_ON_MAINTENANCE) && $this->external_system->isGameApiMaintenance($this->gamePlatformId)){
            $this->errorCode = self::GENERALERROR;
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return false;    
        }

        if(!isset($this->request['Account']) ||
           !isset($this->request['Account']['UserName']) || 
           !isset($this->request['Account']['Password'])
        ){
            $this->errorCode = self::AUTHENTICATIONFAILED;
            return false;
        }

        if($this->request['Account']['UserName'] !== $this->api->account_username || 
           $this->request['Account']['Password'] !== $this->api->account_password
        ){
            $this->errorCode = self::AUTHENTICATIONFAILED;
            return false;
        }

        $this->requestToken = isset($this->request['Token']) ? $this->request['Token'] : null;
        if(empty($this->requestToken)){
            if(!in_array($this->requestMethod, self::AUTOMATED_TEST_SUIT_FUNC)){
                $this->errorCode = self::INVALIDTOKEN;
                return false;
            }
        }

        $this->gameUsername = isset($this->request['PlayerId']) ? $this->request['PlayerId'] : null;
        $this->playerId = $this->api->getPlayerIdByGameUsername($this->gameUsername);
        if(empty($this->gameUsername) || !$this->playerId){
            $this->errorCode = self::INVALIDUSERID;
            return false;
        }

        $playerId = $this->external_common_tokens->getPlayerIdByExternalToken($this->requestToken, self::PARENT_GAME_PLATFORM_ID);
        if(in_array($this->requestMethod, self::TRANSACTION_ENDPOINT)){
            if(isset($this->request['RoundId']) && ($this->requestMethod == "credit" || $this->requestMethod == "cancelTransaction")){
                $roundPlayerId = $this->common_seamless_wallet_transactions->getPlayerIdByRound($this->api->getPlatformCode(), $this->request['RoundId']); 
                if(empty($roundPlayerId)){
                    $this->errorCode = self::INVALIDROUND;
                    return false;
                }
                if($roundPlayerId != $this->playerId){
                    $this->errorCode = self::INVALIDUSERID;
                    return false;
                }
            }

            if(empty($playerId) && !$this->allowExpiredOrMissingToken){
                $this->errorCode = self::INVALIDTOKEN;
                return false;
            }

            if($playerId != $this->playerId && !$this->allowExpiredOrMissingToken){
                $this->errorCode = self::INVALIDUSERID;
                return false;
            }
        }

        if($this->api->isBlockedUsernameInDB($this->gameUsername) && !in_array($this->requestMethod, self::FUNC_ALLOWED_LOCKED_PLAYER)){
            $this->errorCode = self::ACCOUNTISLOCKED;
            return false;
        }

        if(in_array($this->requestMethod, self::FUNC_REQ_AMOUNT)){
            if($this->requestMethod == "debit" || $this->requestMethod == "credit" || (!$this->cancelEntireRound && $this->requestMethod == "cancelTransaction")){
                if(!isset($this->request['Amount'])){
                    $this->errorCode = self::GENERALERROR;
                    $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                    return false;
                }
                else{
                    if(!is_numeric($this->request['Amount'])){
                        $this->errorCode = self::GENERALERROR;
                        $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                        return false;
                    }
                    if($this->utils->compareResultFloat($this->request['Amount'], '<', 0) && !$this->allowNegativeDebit){
                        $this->errorCode = self::INVALIDNEGATIVEAMOUNT;
                        return false;
                    }
                }
            }

            if($this->requestMethod == "debitAndCredit"){
                if(!isset($this->request['DebitAmount']) || !isset($this->request['CreditAmount'])){
                    $this->errorCode = self::GENERALERROR;
                    $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                    return false;
                }
                else{
                    if(!is_numeric($this->request['DebitAmount']) || !is_numeric($this->request['CreditAmount'])){
                        $this->errorCode = self::GENERALERROR;
                        $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                        return false;
                    }
                    if($this->utils->compareResultFloat($this->request['DebitAmount'], '<', 0) || $this->utils->compareResultFloat($this->request['CreditAmount'], '<', 0)){
                        $this->errorCode = self::INVALIDNEGATIVEAMOUNT;
                        return false;
                    }
                }
            }   

            if($this->requestMethod == "awardFreeRoundsPoints"){
                if(!isset($this->request['Points'])){
                    $this->errorCode = self::GENERALERROR;
                    $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                    return false;
                }
                else{
                    if(!is_numeric($this->request['Points'])){
                        $this->errorCode = self::GENERALERROR;
                        $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                        return false;
                    }
                    if($this->utils->compareResultFloat($this->request['Points'], '<', 0)){
                        $this->errorCode = self::INVALIDNEGATIVEAMOUNT;
                        return false;
                    }
                }
            }
        }

        
        if(in_array($this->requestMethod, self::TRANSACTION_ENDPOINT)){
            if(isset($this->request['TransactionId'], $this->request['RoundId'], $this->request['GameCode']) || 
                $this->dontAllowToCancelEndedRoundTransactions || 
                $this->allowToCancelEndedRoundTransactions
            ){
                if(!$this->cancelEntireRound && $this->requestMethod == "cancelTransaction"){
                    $referenceId = isset($this->request['RefTransactionId']) ? $this->request['RefTransactionId'] : null;
                    if(empty($referenceId)){
                        $referenceId = isset($this->request['DebitTransactionId']) ? $this->request['DebitTransactionId'] : null;
                    }

                    if(!empty($referenceId)){
                        $isReferenceExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $referenceId);
                        if(!$isReferenceExist){
                            $this->errorCode = self::UNKNOWNTRANSACTIONID;
                            return false;
                        }
                    } else{
                        $this->errorCode = self::UNKNOWNTRANSACTIONID;
                        return false;
                    }
                }

                $existGameCode = $this->game_description_model->checkIfGameCodeExist($this->api->getPlatformCode(), $this->request['GameCode']);
                if(!$existGameCode){
                    $this->errorCode = self::INVALIDGAME;
                    return false;
                } else {
                    if($this->requestMethod == "cancelTransaction" || $this->requestMethod == "credit"){
                        $gameCode = $this->common_seamless_wallet_transactions->getGameIdByRound($this->api->getPlatformCode(), $this->request['RoundId']);
                        if($gameCode != $this->request['GameCode']){
                            $this->errorCode = self::INVALIDGAME;
                            return false;
                        }
                    }
                }

                $transactionId = $this->request['TransactionId'];
                $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId); 
                if($isTransactionExist){
                    $roundStatus = $this->common_seamless_wallet_transactions->getLastStatusOfRound($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']); 
                    if($roundStatus == self::ROUND_CANCEL || $roundStatus == self::ENTIRE_ROUND_CANCEL){
                        $this->errorCode = self::TRANSACTIONALREADYCANCELLED;
                        return false;
                    }
                    $this->errorCode = self::TRANSACTIONALREADYSETTLED;
                    return false;
                } else {
                    if(in_array($this->requestMethod, self::FUNC_REQ_ROUND) && 
                        !$this->allowCreditWithoutDebit && 
                        !$this->dontAllowToCancelEndedRoundTransactions &&
                        !$this->allowToCancelEndedRoundTransactions
                    ){
                        if($this->requestMethod != "debitAndCredit"){
                            $isRoundExist = $this->common_seamless_wallet_transactions->isPlayerRoundGameCodeExist($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']);
                            if(!$isRoundExist){
                                $this->errorCode = self::INVALIDROUND;
                                return false;
                            }
                        }

                        if($this->requestMethod != "cancelTransaction"){
                            $isRoundEnded = $this->common_seamless_wallet_transactions->isRoundEnded($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']);
                            if($isRoundEnded && !$this->allowAllWagersAfterRoundEnd){
                                $this->errorCode = self::ROUNDALREADYENDED;
                                return false;
                            }
                        }
                        
                        if($this->requestMethod == "cancelTransaction" || $this->requestMethod == "endGame" || $this->requestMethod == "credit"){
                            $roundStatus = $this->common_seamless_wallet_transactions->getLastStatusOfRound($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']); 
                            if(($roundStatus == self::ROUND_CANCEL || $roundStatus == self::ENTIRE_ROUND_CANCEL) && $this->requestMethod == "cancelTransaction"){
                                $this->errorCode = self::TRANSACTIONALREADYSETTLED;
                                return false;
                            }
                            if($this->requestMethod == 'endGame' && $roundStatus == self::ENTIRE_ROUND_CANCEL){
                                $this->errorCode = self::INVALIDROUND;
                                return false;
                            }

                            if($this->requestMethod == 'credit' && $roundStatus == self::ENTIRE_ROUND_CANCEL){
                                $this->errorCode = self::TRANSACTIONALREADYCANCELLED;
                                return false;
                            }
                        }
                    }

                    if($this->requestMethod == "debit"){
                        $isRoundEnded = $this->common_seamless_wallet_transactions->isRoundEnded($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']);
                        if($isRoundEnded){
                            $this->errorCode = self::ROUNDALREADYENDED;
                            return false;
                        }

                        if(!in_array($this->api->getPlatformCode(), $this->api->getSystemInfo('platform_allow_multiple_bets', [DARWIN_PARIPLAY_SEAMLESS_API, SPINOMENAL_PARIPLAY_SEAMLESS_API]))){
                            if(!in_array($this->request['GameCode'], self::GAMECODE_ALLOWED_HAVE_OPEN_ROUNDS_ON_DEBIT)){
                                $playerLastRound = $this->common_seamless_wallet_transactions->getPlayerLastRound($this->api->getPlatformCode(), $this->playerId, self::GAMECODE_ALLOWED_HAVE_OPEN_ROUNDS_ON_DEBIT);
                                if(!empty($playerLastRound)){
                                    $lastRoundStatus = $this->common_seamless_wallet_transactions->getLastStatusOfRound($this->api->getPlatformCode(), $this->playerId, $playerLastRound); 
                                    if($lastRoundStatus == self::ROUND_UNFINISHED){
                                        $this->errorCode = self::PLAYERHAVEOPENEDROUNDS;
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }   
            } else {
                $this->errorCode = self::GENERALERROR;
                $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                return false;
            }
        }

        return true;
    }

    private function getPlayerBalance($isLocked = true){
        if($this->playerId){
            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                $playerId= $this->playerId;
                $balance = 0;
                $reasonId = null;
                if(!$isLocked){
                    $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, &$balance, &$reasonId) {
                        return  $this->wallet_model->querySeamlessSingleWallet($playerId, $balance, $reasonId);
                    });
                } else {
                    $this->wallet_model->querySeamlessSingleWallet($playerId, $balance, $reasonId);
                }
                return $balance;

            } else {
                $playerInfo = (array)$this->api->getPlayerInfo($this->playerId);
                $playerName = $playerInfo['username'];
                $get_bal_req = $this->api->queryPlayerBalance($playerName);
                if($get_bal_req['success']) {
                    return $get_bal_req['balance'];
                }
                else {
                    return false;
                }
            } 
        } else {
            return false;
        }
    }

    private function getPlayerFreeBetBalance($isLocked = true){
        if($this->playerId){
            $playerId= $this->playerId;
            $balance = 0;
            $reasonId = null;
            if(!$isLocked){
                $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, &$balance, &$reasonId) {
                    return  $this->wallet_model->queryGameBetOnlyWallet($playerId, $balance, $reasonId);
                });
            } else {
                $this->wallet_model->queryGameBetOnlyWallet($playerId, $balance, $reasonId);
            }
            return $balance;
        } else {
            return false;
        }
    }

    private function authenticate(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens)){
                    $balance = 0;
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$balance) {
                        $balance = $this->getPlayerBalance();
                        if($balance === false) {
                            $balance = 0;
                            return false;
                        }
                        return true;
                    });

                    if($success){
                        $response = array(
                            "Balance" => $this->api->dBtoGameAmount($balance),
                        );
                        return $this->setOutputResponse(self::SUCCESS, $response);
                    } else {
                        $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                        return $this->setOutputResponse(self::GENERALERROR);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        } 
    }

    private function processRequestData($transData){
        $request = $this->request;
        if($this->isResettlement){
            $request['TransactionId'] = $request['RoundId'] . "-" . $this->utils->getTimestampNow();
        }
        $uniqueId = $request['TransactionId'];
        if($this->isFreeBet){
            $request['freebet_beforeBalance'] = isset($transData['freebet_beforeBalance']) ? $transData['freebet_beforeBalance'] : NULL;
            $request['freebet_afterbalance'] = isset($transData['freebet_afterbalance']) ? $transData['freebet_afterbalance'] : NULL;
        }
        if($uniqueId){
            $dataToInsert = array(
                ### data generated from SBE(converted amount)
                "game_platform_id" => $this->api->getPlatformCode(),
                "amount" => isset($transData['amount']) ? $transData['amount'] : NULL,
                "before_balance" => isset($transData['beforeBalance']) ? $transData['beforeBalance'] : NULL,
                "after_balance" => isset($transData['afterbalance']) ? $transData['afterbalance'] : NULL,
                "player_id" => $this->playerId,
                "transaction_type" => isset($transData['transactionType']) ? $transData['transactionType'] : NULL,
                "response_result_id" => $this->responseResultId,
                #for transaction
                "bet_amount" => isset($transData['betAmount']) ? $transData['betAmount'] : NULL,
                "result_amount" => isset($transData['resultAmount']) ? $transData['resultAmount'] : NULL,

                ##request data from provider
                "game_id" => isset($request['GameCode']) ? $request['GameCode'] : NULL,
                "status" => isset($request['EndGame']) && $request['EndGame'] == true ? self::ROUND_FINISHED : self::ROUND_UNFINISHED,
                "external_unique_id" => $uniqueId,
                "extra_info" => json_encode($request), #actual request
                "round_id" => isset($request['RoundId']) ? $request['RoundId'] : NULL,
                "transaction_id" => isset($request['TransactionId']) ? $request['TransactionId'] : NULL, #mark as bet id

                "start_at" => $this->utils->getNowForMysql(), 
                "end_at" => $this->utils->getNowForMysql(), 
                "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            );
            if(isset($transData['status'])){
                $dataToInsert['status'] = $transData['status']; #override status
            }

            if(isset($request['BonusId'])){
                $dataToInsert['round_id'] = $request['BonusId']; #for award or free spin, mark bonus id as round id
                $dataToInsert['transaction_id'] = $request['BonusId']; #for award or free spin, mark bonus id as trans id
            }

            if(isset($request['RefTransactionId'])){
                $dataToInsert['transaction_id'] = $request['RefTransactionId']; #for award or free spin, mark bonus id as round id
            }

            $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
            $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
            if($transId){
                return $uniqueId;
            } 
        }
        return false;
    }

    private function debit(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens)){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                        $amountToDeduct = isset($this->request['Amount']) ? $this->api->gameAmountToDB($this->request['Amount']) : null;
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false; #default
                        if($this->utils->compareResultFloat($amountToDeduct, '>', 0)) {
                            if($this->isFreeBet){
                                $transData['freebet_beforeBalance'] = $this->getPlayerFreeBetBalance();
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferGameBetOnlyWallet($this->playerId, Wallet_model::TRANSFER_TYPE_OUT, $amountToDeduct, $reason_id);
                            } else {
                                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                    $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_OUT, $amountToDeduct, $reason_id);
                                } else {
                                    $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToDeduct);
                                }
                            }
                        } elseif ($this->utils->compareResultFloat($amountToDeduct, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }

                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = 'debit';
                            $transData['amount'] = $amountToDeduct;
                            $transData['betAmount'] = $amountToDeduct;
                            $transData['resultAmount'] = -$amountToDeduct;
                            if($this->isFreeBet){
                                $transData['freebet_afterbalance'] = $this->getPlayerFreeBetBalance();
                                $transData['amount'] = 0;
                                $transData['resultAmount'] = 0;
                            }

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::SUCCESS;
                                $response = array(
                                    "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                    "TransactionId" => $transId, 
                                );
                                if($this->isFreeBet){
                                    $response['BonusBalance'] = (float)$transData['freebet_afterbalance'];
                                }
                            }
                        } else {
                            $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                        }

                        return$success;
                    });

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }

    private function credit(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens) || $this->allowExpiredOrMissingToken){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                        $amountToAdd = isset($this->request['Amount']) ? $this->api->gameAmountToDB($this->request['Amount']) : null;
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false; #default
                        if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_IN, $amountToAdd, $reason_id);
                            } else {
                                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToAdd);
                            }
                        } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }

                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = 'credit';
                            $transData['amount'] = $amountToAdd;
                            $transData['betAmount'] = 0;
                            $transData['resultAmount'] = $amountToAdd;

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::SUCCESS;
                                $response = array(
                                    "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                    "TransactionId" => $transId, 
                                );
                            }
                        } else {
                            $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                        }

                        return$success;
                    });

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }

    private function endGame(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens) || $this->allowExpiredOrMissingToken){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = false; 

                    $balance = $this->getPlayerBalance();
                    $transData['beforeBalance'] = $balance;
                    $transData['afterbalance'] = $balance;
                    $transData['transactionType'] = 'endGame';
                    $transData['status'] = self::ROUND_FINISHED;
                    $transId = $this->processRequestData($transData);
                    if($transId){
                        $success = true;
                        $errorCode = self::SUCCESS;
                        $response = array(
                            "Balance" => $this->api->dBtoGameAmount($balance),
                            "TransactionId" => $transId, 
                        );
                    }

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }
    private function debitAndCredit(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens)){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                        $debitAmount = isset($this->request['DebitAmount']) ? $this->api->gameAmountToDB($this->request['DebitAmount']) : null;
                        $creditAmount = isset($this->request['CreditAmount']) ? $this->api->gameAmountToDB($this->request['CreditAmount']) : null;
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false; #default

                        if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                            if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                                $resultAmount = $creditAmount - $debitAmount;
                                $amount = abs($resultAmount);
                                if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                        $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                                    } else {
                                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount);
                                    }
                                } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                        $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                                    } else {
                                        $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amount);
                                    }
                                } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                    $success = true;#allowed amount 0
                                } else { #default error
                                    $success = false;
                                }
                            } else {
                                $success = false;
                            }
                        }

                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = 'debitAndCredit';
                            $transData['amount'] = $amount;
                            $transData['betAmount'] = $debitAmount;
                            $transData['resultAmount'] = $resultAmount;
                            $transData['status'] = self::ROUND_FINISHED;

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::SUCCESS;
                                $response = array(
                                    "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                    "TransactionId" => $transId, 
                                );
                            }
                        } else {
                            $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                        }

                        return$success;
                    });

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }


    private function awardFreeRoundsPoints(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens) || $this->allowExpiredOrMissingToken){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                        $amountToAdd = isset($this->request['Points']) ? $this->api->gameAmountToDB($this->request['Points']) : null;
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false; #default
                        if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_IN, $amountToAdd, $reason_id);
                            } else {
                                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToAdd);
                            }
                        } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }

                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = 'awardFreeRoundsPoints';
                            $transData['amount'] = $amountToAdd;
                            $transData['betAmount'] = 0;
                            $transData['resultAmount'] = $amountToAdd;
                            $transData['status'] = self::ROUND_FINISHED;

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::SUCCESS;
                                $response = array(
                                    "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                    "TransactionId" => $transId, 
                                );
                            }
                        } else {
                            $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                        }

                        return$success;
                    });

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }

    private function cancelTransaction(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens) || $this->allowExpiredOrMissingToken){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = false;
                    if($this->cancelEntireRound){
                        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                            $resultAmount = $this->common_seamless_wallet_transactions->getResultAmountOfRound($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']);
                            $beforeBalance = $this->getPlayerBalance();
                            $success = false; #default
                            $amount = abs($resultAmount);

                            if($this->utils->compareResultFloat($resultAmount, '<', 0)) { #player lose then return lose amount
                                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                    $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                                } else {
                                    $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount);
                                }
                            } elseif ($this->utils->compareResultFloat($resultAmount, '>', 0)) { #player win then deduct win amount
                                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                    $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                                } else {
                                    $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amount);
                                }
                            } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                $success = true;#allowed amount 0
                            } else { #default error
                                $success = false;
                            }
                            

                            if($success){
                                $success = false; #reset $success
                                $afterBalance = $this->getPlayerBalance();
                                $transData['beforeBalance'] = $beforeBalance;
                                $transData['afterbalance'] = $afterBalance;
                                $transData['transactionType'] = 'cancelTransaction';
                                $transData['amount'] = $amount;
                                $transData['status'] = self::ENTIRE_ROUND_CANCEL;

                                $transId = $this->processRequestData($transData);
                                if($transId){
                                    $success = true;
                                    $errorCode = self::SUCCESS;
                                    $response = array(
                                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                        "TransactionId" => $transId, 
                                    );
                                }
                            } else {
                                $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                            }

                            return$success;
                        });
                    } else {
                        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                            $amountToAdd = isset($this->request['Amount']) ? $this->api->gameAmountToDB($this->request['Amount']) : null;
                            $beforeBalance = $this->getPlayerBalance();
                            $success = false; #default
                            if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                                if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                    $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                    $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_IN, $amountToAdd, $reason_id);
                                } else {
                                    $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToAdd);
                                }
                            } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                                $success = true;#allowed amount 0
                            } else { #default error
                                $success = false;
                            }

                            if($success){
                                $success = false; #reset $success
                                $afterBalance = $this->getPlayerBalance();
                                $transData['beforeBalance'] = $beforeBalance;
                                $transData['afterbalance'] = $afterBalance;
                                $transData['transactionType'] = 'cancelTransaction';
                                $transData['amount'] = $amountToAdd;
                                $transData['status'] = self::ROUND_CANCEL;

                                $transId = $this->processRequestData($transData);
                                if($transId){
                                    $success = true;
                                    $errorCode = self::SUCCESS;
                                    $response = array(
                                        "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                        "TransactionId" => $transId, 
                                    );
                                }
                            } else {
                                $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                            }

                            return$success;
                        });
                    }

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }

    private function processTransactionConfiguration(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens)){
                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response) {
                        $amountToAdd = isset($this->request['Amount']) ? $this->api->gameAmountToDB($this->request['Amount']) : null;
                        if($this->allowNegativeDebit && $this->utils->compareResultFloat($this->request['Amount'], '<', 0)){
                            $amountToAdd = isset($this->request['Amount']) ? abs($this->api->gameAmountToDB($this->request['Amount'])) : null;
                        } 
                        
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false; #default
                        if($this->utils->compareResultFloat($amountToAdd, '>', 0)) {
                            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_IN, $amountToAdd, $reason_id);
                            } else {
                                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToAdd);
                            }
                        } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }

                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = $this->requestMethod;
                            $transData['amount'] = $amountToAdd;
                            $transData['betAmount'] = 0;
                            $transData['resultAmount'] = $amountToAdd;
                            // $transData['status'] = self::ROUND_FINISHED;
                            if($this->allowToCancelEndedRoundTransactions || $this->allowToCancelEndedRoundTransactions){
                                $transData['status'] = self::ROUND_CANCEL;
                            }

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::SUCCESS;
                                $response = array(
                                    "Balance" => $this->api->dBtoGameAmount($afterBalance),
                                    "TransactionId" => $transId, 
                                );
                            }
                        } else {
                            $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                        }

                        return$success;
                    }); 

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }

    private function gameList(){
        $list = [];
        if($this->api){
            $result = $this->api->queryGameList();
            if($result['success'] && isset($result['games'])){
                $list['games'] = $result['games'];
            }
        }
        return $this->returnJsonResult($list);
    }

    private function createToken(){

        if($this->validateRequest()){
            if($this->playerId){
                if(isset($this->request['FinancialMode']) && $this->request['FinancialMode'] == self::WALLET_MODE){
                    $this->external_common_tokens->addPlayerTokenWithExtraInfo($this->playerId,
                        $this->requestToken,
                        json_encode($this->request),
                        // $this->api->getPlatformCode(),
                        self::PARENT_GAME_PLATFORM_ID,
                        $this->api->currency_code
                    ); 
                }

                return $this->setOutputResponse(self::SUCCESS, []);
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }

    private function closeOpenedRounds(){
        if($this->validateRequest()){
            if($this->playerId){
                if(isset($this->request['GameCode'])){
                    $success = $this->common_seamless_wallet_transactions->updatePlayerGameRoundStatus($this->api->getPlatformCode(), $this->playerId, $this->request['GameCode']);
                    if($success){
                        return $this->setOutputResponse(self::SUCCESS, []);
                    }
                }
                $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                return $this->setOutputResponse(self::GENERALERROR);
                
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }    
    }

    private function getSportRoundWithMissingEndgame(){
        $dateFrom = isset($this->request['dateFrom']) ? $this->request['dateFrom'] : null;
        $dateTo = isset($this->request['dateTo']) ? $this->request['dateTo'] : null;

        if(!$this->validateDate($dateFrom) || !$this->validateDate($dateTo)){
            return $this->returnJsonResult('Pass date only');
        }

        $rounds = [];
        if($this->api){
            $rounds = $this->api->get_btobet_rounds_missing_endgame($dateFrom, $dateTo);
        }
        else {
            return $this->returnJsonResult('Invalid API');
        }
        return $this->returnJsonResult($rounds);
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    private function getAllFreeBets(){
        if(!$this->responseResultId || 
            !$this->api ||
            !$this->external_system->isGameApiActive(PARIPLAY_SEAMLESS_API)
        ){
            return $this->setOutputResponse(self::GENERALERROR);
        }

        if(!isset($this->request['Account']) ||
           !isset($this->request['Account']['UserName']) || 
           !isset($this->request['Account']['Password'])
        ){
            return $this->setOutputResponse(self::AUTHENTICATIONFAILED);
        }

        if($this->request['Account']['UserName'] !== $this->api->account_username || 
           $this->request['Account']['Password'] !== $this->api->account_password
        ){
            return $this->setOutputResponse(self::AUTHENTICATIONFAILED);
        }
        $campaigns = $this->common_game_free_spin_campaign->getCampaigns(PARIPLAY_SEAMLESS_API);
        if(!empty($campaigns)){
            array_walk($campaigns, function($row, $key) use(&$campaigns){
                $extra = json_decode($row['extra'], true);
                $bonus_campaign_details['BonusId'] = $row['id'];
                $bonus_campaign_details['Name'] = $row['name'];
                $bonus_campaign_details['Trigger'] = $extra['bonus_trigger'];
                $bonus_campaign_details['AppliedOn'] = $row['created_at'];
                $bonus_campaign_details['ActivatesOn'] = $extra['bonus_activate_on'];
                $bonus_campaign_details['ExpiresOn'] = $extra['bonus_expired_on'];
                $bonus_campaign_details['MinAmount'] = $extra['bonus_min_amount'];
                $bonus_campaign_details['MaxAmount'] = $extra['bonus_max_amount'];
                $bonus_campaign_details['Percentage'] = $extra['bonus_percentage'];
                $bonus_campaign_details['Turnover'] = $extra['bonus_turnover'];
                $bonus_campaign_details['ExpiryDaysAfterAwarding'] = $extra['bonus_edaa'];
                
                $campaigns[$key] = $bonus_campaign_details;
            });
        }
        $freeBets = $campaigns;
        $response = ["FreeBets" => $freeBets];
        return $this->setOutputResponse(self::SUCCESS, $response);
    }

    private function bonusTriggerFreeBet(){
        if(!$this->responseResultId || 
            !$this->api ||
            !$this->external_system->isGameApiActive(PARIPLAY_SEAMLESS_API)
        ){
            $error_message = array("message" => "Game Disabled.");
            return $this->setOutputResponse(self::GENERALERROR, $error_message);
        }

        if(!isset($this->request['Account']) ||
           !isset($this->request['Account']['UserName']) || 
           !isset($this->request['Account']['Password'])
        ){
            $error_message = array("message" => "Authentication Failed.");
            return $this->setOutputResponse(self::AUTHENTICATIONFAILED, $error_message);
        }

        if($this->request['Account']['UserName'] !== $this->api->account_username || 
           $this->request['Account']['Password'] !== $this->api->account_password
        ){
            $error_message = array("message" => "Authentication Failed.");
            return $this->setOutputResponse(self::AUTHENTICATIONFAILED);
        }

        $request = $this->request;
        $amount = isset($request['Amount']) ? $request['Amount'] : 0;
        $amount = $this->convertBonusAmount($amount);

        $turnover = isset($request['Turnover']) ? $request['Turnover'] : null;
        $gameUsername = isset($request['UserId']) ? $request['UserId'] : null;
        $this->playerId = $playerId = $this->api->getPlayerIdByGameUsername($gameUsername);
        if(empty($gameUsername) || !$playerId){
            $error_message = array("message" => "Invalid User Id.");
            return $this->setOutputResponse(self::INVALIDUSERID, $error_message);
        }

        $bonusId = isset($request['BonusId']) ? $request['BonusId'] : null;
        if(empty($bonusId)){
            $error_message = array("message" => "Parameter {BonusId} missing or empty value.");
            return $this->setOutputResponse(self::GENERALERROR, $error_message);
        }

        $uniqueid = PARIPLAY_SEAMLESS_API . "-" . $bonusId . "-" . $gameUsername; 
        $bonusExist = $this->common_game_free_spin_campaign->isFreeBetBonusExist($uniqueid);
        if($bonusExist){
            $error_message = array("message" => "User bonus already exist");
            return $this->setOutputResponse(self::GENERALERROR, $error_message);
        }

        $gameplatformid = PARIPLAY_SEAMLESS_API;
        $campaign = $this->common_game_free_spin_campaign->getCampaignDetailsById($bonusId, $gameplatformid);
        $extra = isset($campaign['extra']) ? json_decode($campaign['extra'], true) :[] ;
        if(!$campaign){
            $error_message = array("message" => "Bonus not exist.");
            return $this->setOutputResponse(self::GENERALERROR, $error_message);
        }

        $expiry = strtotime($campaign['end_time']);
        if($expiry < time()){
            $error_message = array("message" => "Bonus aleady expired.");
            return $this->setOutputResponse(self::GENERALERROR, $error_message);
        }

        if($amount && $extra){
            $bonus_max_amount = isset($extra['bonus_max_amount']) ? $extra['bonus_max_amount'] : null;
            $bonus_min_amount = isset($extra['bonus_min_amount']) ? $extra['bonus_min_amount'] : null;
            if($this->utils->compareResultFloat($amount, '<', $bonus_min_amount)){
                $error_message = array("message" => "Bonus is less than minimum amount.");
                return $this->setOutputResponse(self::GENERALERROR, $error_message);
            }

            if($this->utils->compareResultFloat($amount, '>', $bonus_max_amount)){
                $error_message = array("message" => "Bonus is greater than maximum amount.");
                return $this->setOutputResponse(self::GENERALERROR, $error_message);
            }
        }

        $success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerId, $uniqueid, $amount, $turnover, $request) {
            $amountToAdd = $amount;
            $beforeFreeBetBalance = $this->getPlayerFreeBetBalance();
            $success = false; #default
            if($this->utils->compareResultFloat($amountToAdd, '>', 0)) { 
                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                $success = $this->wallet_model->transferGameBetOnlyWallet($playerId, Wallet_model::TRANSFER_TYPE_IN, $amountToAdd, $reason_id);   
            } elseif ($this->utils->compareResultFloat($amountToAdd, '=', 0)) {
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; #reset $success
                $afterFreeBetBalance = $this->getPlayerFreeBetBalance();
                $dataToInsert = array(
                    ### data generated from SBE(converted amount)
                    "game_platform_id" => $this->api->getPlatformCode(),
                    "player_id" => $playerId,
                    "unique_id" => $uniqueid,
                    "amount" => $amount,
                    "turnover" => $turnover,
                    "response_result_id" => $this->responseResultId,
                    "request" => json_encode($request), #actual request,
                    "after_balance" => $afterFreeBetBalance,
                    "before_balance" => $beforeFreeBetBalance,
                    "created_at" => $this->utils->getNowForMysql(), 
                    "updated_at" => $this->utils->getNowForMysql(), 
                );
                $success = $this->common_game_free_spin_campaign->insertData('seamless_free_bet_record',$dataToInsert);
            }
            return$success;
        });

        if(!$success){
            $error_message = array("message" => "Failed to process user bonus.");
            return $this->setOutputResponse(self::GENERALERROR, $error_message);
        } 

        $response = ["bonusInstanceID" => $bonusId];
        return $this->setOutputResponse(self::SUCCESS, $response);
    }

    public function bonus($method = null){
        $method = lcfirst($method);
        $request = json_decode(file_get_contents('php://input'), true);
        switch ( $method ) {
            case 'create':
                return $this->createBonus($request);
                break;
            case 'list':
                return $this->getAllBonusCampaign($request);
                break;
            
            default:
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
                $this->returnJsonResult((object)selff::GENERALERROR);
                break;
        }
    }

    private function createBonus($request){
        $timestamp = $this->utils->getTimestampNow();
        $dataToInsert = array(
            "game_platform_id" => PARIPLAY_SEAMLESS_API,
            "campaign_id" => $timestamp,
            "name" => isset($request['bonus_campaign_name']) ? $request['bonus_campaign_name'] : null,
            "start_time" => isset($request['bonus_activate_on']) ? $request['bonus_activate_on'] : null,
            "end_time" => isset($request['bonus_expired_on']) ? $request['bonus_expired_on'] : null,
            "extra" => json_encode($request),
            "external_uniqueid" => isset($request['bonus_campaign_name']) ? $request['bonus_campaign_name'] : null,
        );
        $success = $this->common_game_free_spin_campaign->insertData('common_game_free_spin_campaign',$dataToInsert);
        $output = array(
            "success" => $success ? true : false,
            "desc" => $success ? "Success" : "Failed. Something went wrong.",
            "result" => $request
        );
        $this->returnJsonResult($output);
    }

    private function getAllBonusCampaign(){
        $campaigns = $this->common_game_free_spin_campaign->getCampaigns(PARIPLAY_SEAMLESS_API);
        if(!empty($campaigns)){
            array_walk($campaigns, function($row, $key) use(&$campaigns){
                $bonus_campaign_details = json_decode($row['extra'], true);
                $bonus_campaign_details['bonus_id'] = $row['id'];
                $bonus_campaign_details['bonus_created_at'] = $row['created_at'];
                $campaigns[$key] = $bonus_campaign_details;
            });
        }
        $result['data'] = $campaigns;
        $this->returnJsonResult($result);
    }

    private function convertBonusAmount($amount) {
        if($amount==0){
            return $amount;
        }

        $conversion_rate = floatval($this->api->getSystemInfo('bonus_conversion_rate', 100));
        $precision = intval($this->api->getSystemInfo('bonus_conversion_precision', 3));

        //compute amount with conversion rate
        $value = floatval($amount / $conversion_rate);

        return bcdiv($value, 1, $precision);
    }

    private function resettlement(){
        if($this->validateRequest()){
            if($this->playerId){
                $playerGameTokens = $this->external_common_tokens->getPlayerActiveExternalTokens($this->playerId, self::PARENT_GAME_PLATFORM_ID);
                if(in_array($this->requestToken, $playerGameTokens) || $this->allowExpiredOrMissingToken){
                    if(!in_array($this->request['GameCode'], self::GAMECODE_ALLOWED_RESETTLEMENT)){
                        $error_message = array("message" => "Resettlement is available only for sports.");
                        return $this->setOutputResponse(self::GENERALERROR, $error_message);
                    }
                    $roundStatus = $this->common_seamless_wallet_transactions->getLastStatusOfRound($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId']); 
                    if($roundStatus != self::ROUND_FINISHED){
                        $error_message = array("message" => "The round is not yet finished.");
                        return $this->setOutputResponse(self::GENERALERROR, $error_message);
                    }

                    $lastResettlement = $this->common_seamless_wallet_transactions->getCustomLastRecord($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId'], 'resettlement', ['created_at']);
                    if(!empty($lastResettlement)){
                        $lastResettlementDate = $lastResettlement->created_at;
                        $totalCredit = $this->common_seamless_wallet_transactions->getSumAmountByRoundId($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId'], 'credit', $lastResettlementDate);
                    } else {
                        $totalCredit = $this->common_seamless_wallet_transactions->getSumAmountByRoundId($this->api->getPlatformCode(), $this->playerId, $this->request['RoundId'], 'credit');
                    }

                    $errorCode = self::GENERALERROR;
                    $response = [];
                    $amountToDeduct = $totalCredit;
                    $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use(&$errorCode, &$response, $amountToDeduct) {
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false; #default
                        if($this->utils->compareResultFloat($amountToDeduct, '>', 0)) {
                            if($this->utils->getConfig('enable_seamless_single_wallet')) {
                                $reason_id=Abstract_game_api::REASON_UNKNOWN;
                                $success = $this->wallet_model->transferSeamlessSingleWallet($this->playerId, Wallet_model::TRANSFER_TYPE_OUT, $amountToDeduct, $reason_id);
                            } else {
                                $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToDeduct);
                            }
                        } elseif ($this->utils->compareResultFloat($amountToDeduct, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }

                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = 'resettlement';
                            $transData['amount'] = $amountToDeduct;
                            $transData['betAmount'] = 0;
                            $transData['resultAmount'] = -$amountToDeduct;
                            $transData['status'] = self::ROUND_REOPEN;

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::SUCCESS;
                                $response = array(
                                    "IsSuccessful" => true, 
                                );
                            }
                        } else {
                            $errorCode = self::INSUFFICIENTFUNDS; #not enough balance or invalid amount
                        }

                        return$success;
                    });

                    if($success){
                        return $this->setOutputResponse($errorCode, $response);
                    } else {
                        return $this->setOutputResponse($errorCode);
                    }
                } else {
                    return $this->setOutputResponse(self::INVALIDTOKEN);
                }
            } else {
                return $this->setOutputResponse(self::INVALIDUSERID);
            }
        } else {
            if($this->errorCode){
                return $this->setOutputResponse($this->errorCode);
            }
            $this->utils->debug_log('PARIPLAY GENERAL error line', __LINE__);
            return $this->setOutputResponse(self::GENERALERROR);
        }
    }
}

