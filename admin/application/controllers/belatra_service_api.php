<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/**
 * Belatra Single Wallet API Controller
 * OGP-32584
 *
 * @author  Jerbey Capoquian
 *
 * INTRODUCTION
    Casino is the body who works with the players.
    Game provider - is the final content provider.  
    Aggregator can act as an agent between the casino and the game provider. 
    The casino can work with the game provider directly, can work via aggregator or several aggregators at the same time.
    Universal API describes communication between either two points: Casino and Aggregator, Aggregator and Game provider or Casino and Game provider directly. So we call these two points Wallet and GCP.
* TERMS
    GCP_URL - games content provider api endpoint
    WALLET_URL - wallet server api endpoint
    AUTH_TOKEN - token used to sign messages
    CASINO_ID - casinos' identifiers
* BEFORE INTEGRATION
    For Wallet side:
    1.  Provide WALLET_URL.
    2.  Receive GCP_URL, CASINO_ID, AUTH_TOKEN and Games List from manager.
    For GCP side:
    1.  Provide GCP_URL and Games List (game titles, launch IDs, desktop/mobile, freespins: yes/no) to ‘wallet’-manager.
    2.  Receive WALLET_URL, CASINO_ID and AUTH_TOKEN from ‘wallet’-manager.
* Callback endpoint 
    WALLET_URL: <domain>/belatra_service_api/<game_platform_id>
    Round Example (bet + win) : WALLET_URL/play
    Get balance Example : WALLET_URL/play
    Rollback request : WALLET_URL/rollback 
 * 
 * Related File
     - game_api_belatra_seamless.php
 */

/*
Operator Integration APIs
    
*/

class Belatra_service_api extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    const TRANSACTION_TABLE = 'belatra_seamless_wallet_transactions';
    const ALLOWED_ACTION = ['play', 'rollback'];
    const ACTION_LIST = ['bet', 'win'];
    const ACTION_ROLLBACK = ['rollback'];
    const STATUS_CODE_SUCCESS = 0;
    CONST STATUS_CODE_ERROR = 412;
    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_100 = 100;
    const ERROR_CODE_101 = 101;
    const ERROR_CODE_105 = 105;
    const ERROR_CODE_106 = 106;
    const ERROR_CODE_107 = 107;
    const ERROR_CODE_110 = 110;
    const ERROR_CODE_153 = 153;
    const ERROR_CODE_400 = 400;
    const ERROR_CODE_403 = 403;
    const ERROR_CODE_404 = 404;
    const ERROR_CODE_405 = 405;
    const ERROR_CODE_500 = 500;
    const ERROR_CODE_600 = 600;
    const ERROR_CODE_601 = 601;
    const ERROR_CODE_602 = 602;
    const ERROR_CODE_603 = 603;
    const ERROR_CODE_605 = 605;
    const ERROR_CODE_606 = 606;
    const ERROR_CODE_607 = 607;
    const ERROR_CODE_611 = 611;
    const ERROR_CODE_620 = 620;

    const ERROR_MESSAGE = [
          0 => "Success",
        100 => "Player has not enough funds to process an action",
        101 => "Player is invalid",
        105 => "Player reached customized bet limit",
        106 => "Bet exceeded max bet limit",
        107 => "Game is forbidden to the player",
        110 => "Player is disabled",
        153 => "Game is not available in Player's country",
        400 => "Bad request. (Bad formatted json)",
        403 => "Forbidden. (Request sign doesn't match)",
        404 => "Not found",
        405 => "Game is not available to casino",
        500 => "Unknown error",
        600 => "Game provider doesn't provide freespins",
        601 => "Impossible to issue freespins in requested game",
        602 => "Should provide at least one game to issue freespins",
        603 => "Bad expiration date. Expiration date should be in future and freespins shouldn't be active for more that 1 month",
        605 => "Can't change issue state from its current to requested",
        606 => "Can't change issue state when issue status is not synced",
        607 => "Can't issue one freespin issue at different game providers",
        611 => "Freespins issue has already expired",
        620 => "Freespins issue can't be canceled",
    ];
    

    private function getExternalGameId(){
        return BELATRA_SEAMLESS_GAME_API;
    }

    //Only public function used on routes mapping
    public function api($gamePlatformId, $action = null){
        $this->gamePlatformId = $gamePlatformId;
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->action = $action;
        $this->requestBody = file_get_contents("php://input");
        $this->requestHeaders = $this->input->request_headers();
        $this->statusCode = self::STATUS_CODE_SUCCESS;
        $this->api = null;
        $this->playerId = null;
        $this->checkPreviousMonth = false;

        $params = json_decode($this->requestBody, true);
        $response = [];
        $errorMessage = null;
        
        
        try {
            $currency = isset($params['currency']) ? $params['currency'] : null;
            if(is_null($currency)){
                throw new Exception(__LINE__.":Invalid currency.", self::ERROR_CODE_500);
            }

            $is_valid = $this->getCurrencyAndValidateDB($currency);
            if(!$is_valid) {
                throw new Exception(__LINE__.":Failed to switch currency.", self::ERROR_CODE_500);
            }

            $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
            if(!$this->api) {
                throw new Exception(__LINE__.":Invalid API.", self::ERROR_CODE_500);
            }

                if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
                $this->checkPreviousMonth = true;
            }

            if($this->external_system->isGameApiMaintenance($this->gamePlatformId)){
                throw new Exception(__LINE__.":The game is on maintenance.", self::ERROR_CODE_500);   
            }

            if(!$this->api->validateWhiteIP()){
                throw new Exception(__LINE__.":Invalid IP.", self::ERROR_CODE_500);
            }

            if(!method_exists($this, $this->action) || empty($this->requestBody)) {
                throw new Exception(__LINE__.":Invalid url or empty request.", self::ERROR_CODE_404);
            }

            if(!in_array($this->action, self::ALLOWED_ACTION)) {
                throw new Exception(__LINE__.":Request not allowed.", self::ERROR_CODE_403);
            }

            if(in_array($this->action, $this->api->list_of_method_for_force_error)){
                throw new Exception(__LINE__.":Force error.", self::ERROR_CODE_400);
            }

            $this->validateParams($params);
            $this->authenticate($params);

            list($errorCode, $response) = $this->$action($params);

        } catch (Exception $e) {
            $this->statusCode = self::STATUS_CODE_ERROR;
            $this->utils->debug_log('belatra encounter error at line and message', $e->getMessage());
            $messageArray = explode(":", $e->getMessage());
            $errorMessage = isset($messageArray[1]) ? $messageArray[1] : null;
            $balance = isset($messageArray[2]) ? $messageArray[2] : null;
            $errorCode = $e->getCode();
            if(!is_null($balance)){
                $response = array("balance_currency" => (float)$balance);
            }
        }

        return $this->setResponse($errorCode, $response, $errorMessage);
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

    #Function for player authentication
    private function authenticate($params){
        $gameUsername = isset($params['user_id']) ? $params['user_id'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty user id.", self::ERROR_CODE_101); 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            throw new Exception(__LINE__.":Player is invalid.", self::ERROR_CODE_101); 
        }

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            throw new Exception(__LINE__.":Player is invalid.", self::ERROR_CODE_101); 
        }
        $this->playerId = $playerId;

        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__.":Player is blocked.", self::ERROR_CODE_110);
        }
        return $playerId;
    }

    #Function to validate params
    private function validateParams($params){
        $requestSign = isset($this->requestHeaders['X-Request-Sign']) ? $this->requestHeaders['X-Request-Sign'] : null;
        $sign = hash_hmac('sha256', $this->requestBody, $this->api->auth_token);
        if($sign != $requestSign){
            throw new Exception(__LINE__.":The request sign doesn't match.", self::ERROR_CODE_403);
        }

        $currency = isset($params['currency']) ? $params['currency'] : null;
        if($currency != $this->api->currency){
            throw new Exception(__LINE__.":Invalid currency request.", self::ERROR_CODE_400);
        }

        if(isset($params['actions']) && !empty($params['actions'])){
            $actions = $params['actions'];
            foreach ($actions as $key => $action) {
                $amount = isset($action['amount']) ? $action['amount'] : null;
                $amountCurrency = isset($action['amount_currency']) ? $action['amount_currency'] : null;
                if(!is_null($amount) && !$this->isValidAmount($amount)){
                    throw new Exception(__LINE__.":Invalid amount request.", self::ERROR_CODE_400);
                }
                if(!is_null($amountCurrency) && !$this->isValidAmount($amountCurrency)){
                    throw new Exception(__LINE__.":Invalid amount currency request.", self::ERROR_CODE_400);
                }
            }
        }

        return true;
    }

    private function rollback($params){
        list($errorCode, $response) = $this->balance($params);
        $beforeBalance = $afterBalance = $response['balance_currency'];
        if(isset($params['actions']) && !empty($params['actions'])){
            $params['before_balance'] = $beforeBalance; 
            list($errorCode, $response) = $this->processRollbackActions($params);
        }
        return [$errorCode, $response];
    }

    private function processRollbackActions($params){
        $transactions = [];
        $afterBalance = $params['before_balance'];
        $beforeBalance = $params['before_balance'];
        if(isset($params['actions']) && !empty($params['actions'])){
            $actions = $params['actions'];
            foreach ($actions as $key => $action) {
                if(!$this->checkRequiredFields($action, ['action', 'action_id', 'original_action_id'])){
                    throw new Exception(__LINE__.":Required fields missing.", self::ERROR_CODE_400); 
                }
                $actionMethod = $action['action'];
                if(!in_array($actionMethod, self::ACTION_ROLLBACK)){
                    throw new Exception(__LINE__.":Invalid rollback action.", self::ERROR_CODE_400);
                }
                $action['after_balance'] = $afterBalance;
                $action['before_balance'] = $afterBalance;
                $data = array_merge($params, $action);
                list($errorCode, $actionResponse) = $this->rollbackAction($data);
                if($errorCode !== self::ERROR_CODE_SUCCESS){
                    throw new Exception(__LINE__, $errorCode);
                }
                $afterBalance = $actionResponse['after_balance'];
                unset($actionResponse['after_balance']);
                if(!empty($actionResponse)){
                    $transactions[] = $actionResponse;
                }
            }
        }

        $response = array(
            "balance_currency" => $afterBalance,
            "transactions" => $transactions
        );
        if(isset($params['game_id'])){
            $response['game_id'] = $params['game_id'];
        }
        return [$errorCode, $response];
    }

    private function rollbackAction($params){
        $uniqueid = isset($params['action_id']) ? $params['action_id'] : null;
        $originalActionId = isset($params['original_action_id']) ? $params['original_action_id'] : null;
        $actionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id']);
        if(!empty($actionDetails)){
            return [self::ERROR_CODE_SUCCESS, [
                "after_balance" => $params['before_balance'],
                "action_id" => $uniqueid,
                "tx_id" => (string)$actionDetails['id'],
                ]
            ];
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $actionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id']);
                if(!empty($actionDetails)){
                    return [self::ERROR_CODE_SUCCESS, [
                        "after_balance" => $params['before_balance'],
                        "action_id" => $uniqueid,
                        "tx_id" => (string)$actionDetails['id'],
                        ]
                    ];
                }
            }
        }

        $originalActionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $originalActionId],['id', 'amount_currency', 'action']);
        if(empty($originalActionDetails)){
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $originalActionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $originalActionId],['id', 'amount_currency','action']);
            }
        }
        $params['original_action_details'] = $originalActionDetails;

        $errorCode = self::ERROR_CODE_500;
        $response = ['after_balance' => $params['before_balance']];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode) {
            $success = false;
            $originalAmountCurrency = isset($params['original_action_details']['amount_currency']) ? $params['original_action_details']['amount_currency'] : null;
            $originalAction = isset($params['original_action_details']['action']) ? $params['original_action_details']['action'] : null;

            $params['amount_currency'] = $originalAmountCurrency;
            $uniqueid = isset($params['action_id']) ? $params['action_id'] : null;
            $beforeBalance = isset($params['before_balance']) ? $params['before_balance'] : null;
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            $actionType = $this->getActionType($params['action']);
            $params['before_balance'] = $beforeBalance;
            $params['after_balance'] = $beforeBalance;
            $transId = $this->processRequestData($params);
            // if($transId){
            //     $success = true;
            //     $errorCode = self::ERROR_CODE_SUCCESS;
            //     $response = [
            //         "after_balance" => $afterBalance, 
            //         "action_id" => $uniqueid,
            //         "tx_id" => $transId
            //     ];
            // }
            if($transId && !empty($originalAmountCurrency)){
                if($this->utils->compareResultFloat($originalAmountCurrency, '>', 0)) {
                    $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                    if(!empty($configEnabled)){
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                        $this->wallet_model->setGameProviderActionType($actionType);
                        $this->wallet_model->setGameProviderIsEndRound(true);
                        if(isset($params['game_id'])){
                            $this->wallet_model->setGameProviderRoundId($params['game_id']);

                        }
                    } 
                    if($originalAction == 'win' || $originalAction == 'payout'){
                        $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $originalAmountCurrency, $afterBalance);
                    } else if($originalAction == 'bet'){
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $originalAmountCurrency, $afterBalance);
                    }
                    
                    if(!$success){
                        return false;
                    }
     
                } elseif ($this->utils->compareResultFloat($originalAmountCurrency, '=', 0)) {
                    $success = true;#allowed amount_currency 0
                } else { #default error
                    $success = false;
                }
            } else if($transId && empty($originalAmountCurrency)){
                $success = true;
            }

            if($success){
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        return false;
                    }
                }
                if($transId){
                    $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['external_uniqueid' => $uniqueid], ['after_balance' => $afterBalance]);
                    $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['external_uniqueid' => $params['original_action_id']], ['sbe_status' => GAME_LOGS::STATUS_REFUND]);
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = [
                        "after_balance" => $afterBalance, 
                        "action_id" => $uniqueid,
                        "tx_id" => (string)$transId
                    ];
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    private function play($params){
        list($errorCode, $response) = $this->balance($params);
        if(isset($params['actions']) && !empty($params['actions'])){
            $this->action = "betWin";
            $params['before_balance'] = $response['balance_currency']; 
            list($errorCode, $response) = $this->processActions($params);
        }
        return [$errorCode, $response];
    }

    //Function to get player balance
    private function balance($params){
        if(!$this->checkRequiredFields($params, ['user_id', 'currency', 'game'])){
            throw new Exception(__LINE__.":Required fields missing.", self::ERROR_CODE_400); 
        }
        return [self::ERROR_CODE_SUCCESS, ["balance_currency" => $this->getPlayerBalance()]];
    }

    
    private function processActions($params){
        $transactions = [];
        $afterBalance = $params['before_balance'];
        $beforeBalance = $params['before_balance'];
        if(isset($params['actions']) && !empty($params['actions'])){
            $actions = $params['actions'];
            foreach ($actions as $key => $action) {
                if(!$this->checkRequiredFields($action, ['action', 'amount', 'amount_currency', 'action_id'])){
                    throw new Exception(__LINE__.":Required fields missing.", self::ERROR_CODE_400); 
                }
                $actionMethod = $action['action'];
                if(!in_array($actionMethod, self::ACTION_LIST)){
                    throw new Exception(__LINE__.":Invalid action.", self::ERROR_CODE_400);
                }
                $action['after_balance'] = $afterBalance;
                $action['before_balance'] = $afterBalance;
                $data = array_merge($params, $action);
                list($errorCode, $actionResponse) = $this->betWin($data);
                if($errorCode !== self::ERROR_CODE_SUCCESS){
                    if($errorCode == self::ERROR_CODE_100){
                        throw new Exception(__LINE__."::{$afterBalance}", $errorCode);
                    }
                    throw new Exception(__LINE__, $errorCode);
                }
                $afterBalance = $actionResponse['after_balance'];
                unset($actionResponse['after_balance']);
                if(!empty($actionResponse)){
                    $transactions[] = $actionResponse;
                }
            }
        }

        $response = array(
            "balance_currency" => $afterBalance,
            "transactions" => $transactions
        );
        if(isset($params['game_id'])){
            $response['game_id'] = $params['game_id'];
        }
        return [$errorCode, $response];
    }

    private function betWin($params){
        $amount = isset($params['amount']) ? $params['amount'] : null;
        $amountCurrency = isset($params['amount_currency']) ? $params['amount_currency'] : null;
        $uniqueid = isset($params['action_id']) ? $params['action_id'] : null;
        if($amount < 0 || $amountCurrency < 0){
            throw new Exception(__LINE__.":Negative amount or amount currency.", self::ERROR_CODE_400); 
        }

        $actionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id']);
        if(!empty($actionDetails)){
            return [self::ERROR_CODE_SUCCESS, [
                "after_balance" => $params['before_balance'],
                "action_id" => $uniqueid,
                "tx_id" => $actionDetails['id'],
                ]
            ];
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $actionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id']);
                if(!empty($actionDetails)){
                    return [self::ERROR_CODE_SUCCESS, [
                        "after_balance" => $params['before_balance'],
                        "action_id" => $uniqueid,
                        "tx_id" => $actionDetails['id'],
                        ]
                    ];
                }
            }
        }

        $rollbackActionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['original_action_id'=> $uniqueid],['id']);
        if(!empty($rollbackActionDetails)){
            throw new Exception(__LINE__.":Action ID already rollback.", self::ERROR_CODE_400);
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $rollbackActionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['original_action_id'=> $uniqueid],['id']);
                if(!empty($actionDetails)){
                    throw new Exception(__LINE__.":Action ID already rollback.", self::ERROR_CODE_400);
                }
            }
        }

        $errorCode = self::ERROR_CODE_500;
        $response = ['after_balance' => $params['before_balance']];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode) {
            $success = false;
            $amountCurrency = isset($params['amount_currency']) ? $this->api->gameAmountToDBTruncateNumber($params['amount_currency']) : null;
            $uniqueid = isset($params['action_id']) ? $params['action_id'] : null;
            $beforeBalance = isset($params['before_balance']) ? $params['before_balance'] : null;
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            $actionType = $this->getActionType($params['action']);
            $params['before_balance'] = $beforeBalance;
            $params['after_balance'] = $afterBalance;
            $transId = $this->processRequestData($params);
            if($transId){
                if($this->utils->compareResultFloat($amountCurrency, '>', 0)) {
                    if($actionType == 'bet'){
                        if($this->utils->compareResultFloat($amountCurrency, '>', $beforeBalance)) {
                            $errorCode = self::ERROR_CODE_100;
                            return false;
                        }
                    }
                    
                    $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                    if(!empty($configEnabled)){
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                        $this->wallet_model->setGameProviderActionType($actionType);
                        if(isset($params['game_id'])){
                            $this->wallet_model->setGameProviderRoundId($params['game_id']);

                        }
                        if(isset($params['finished'])){
                            $this->wallet_model->setGameProviderIsEndRound($params['finished']);

                        } else {
                            $this->wallet_model->setGameProviderIsEndRound(true);
                        }
                        
                    } 
                    if($actionType == 'bet'){
                        $this->wallet_model->setGameProviderBetAmount($amountCurrency);
                        $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amountCurrency, $afterBalance);
                    } else if($actionType == 'payout'){
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountCurrency, $afterBalance);
                    }
                    
                    if(!$success){
                        return false;
                    }
     
                } elseif ($this->utils->compareResultFloat($amountCurrency, '=', 0)) {
                    $success = true;#allowed amount_currency 0
                } else { #default error
                    $success = false;
                }
            }

            if($success){
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance();
                    if($afterBalance === false){
                        return false;
                    }
                }
                if($transId){
                    $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['external_uniqueid' => $uniqueid], ['after_balance' => $afterBalance]);
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = [
                        "after_balance" => $afterBalance, 
                        "action_id" => $uniqueid,
                        "tx_id" => (string)$transId
                    ];
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    private function getActionType($action){
        $action = strtolower($action);
        switch ($action) {
            case 'win':
                $type = 'payout';
                break;
            
            default:
                $type = $action;
                break;
        }
        return $type;
    }


    // Function to get error message by code
    private function getErrorMessage($errorCode) {
        $errorMessage = self::ERROR_MESSAGE;
        $errorMessage =  isset($errorMessage[$errorCode]) ? $errorMessage[$errorCode] : "Unknown Error Code";
        return $errorMessage;
    }

    //Function to check if required params are exist
    private function checkRequiredFields($params, $requiredParams) {
        $keys = array_keys($params);
        $valid = true;
        if(!empty($requiredParams)){
            foreach ($requiredParams as $field) {
                if(!in_array($field, $keys)){
                   $valid = false;
                   break;
                } 
            }
        }
        return $valid;
    }

    //Function to check if amount is value number
    private function isValidAmount($amount){
        $amount= trim($amount);
        if(!is_numeric($amount)) {
            return false;
        } else {
            return true;
        }
    }

    private function processRequestData($params){
        $dataToInsert = array(
            #params data
            "user_id" => isset($params['user_id']) ? $params['user_id'] : NULL, 
            "currency" => isset($params['currency']) ? $params['currency'] : NULL, 
            "game" => isset($params['game']) ? $params['game'] : NULL, 
            "game_id" => isset($params['game_id']) ? $params['game_id'] : NULL, 
            "finished" => isset($params['finished']) ? $params['finished'] : NULL, 
            "session_id" => isset($params['session_id']) ? $params['session_id'] : NULL,
            "actions" => isset($params['actions']) ? json_encode($params['actions']) : NULL, 
            #action data
            "action" => isset($params['action']) ? $params['action'] : NULL, 
            "amount" => isset($params['amount']) ? $params['amount'] : NULL, 
            "amount_currency" => isset($params['amount_currency']) ? $params['amount_currency'] : NULL, 
            "action_id" => isset($params['action_id']) ? $params['action_id'] : NULL, 
            "original_action_id" => isset($params['original_action_id']) ? $params['original_action_id'] : NULL, 
            #sbe default
            "sbe_status" => isset($params['sbe_status']) ? $params['sbe_status'] : NULL, 
            "before_balance" => isset($params['before_balance']) ? $params['before_balance'] : NULL, 
            "after_balance" => isset($params['after_balance']) ? $params['after_balance'] : NULL, 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            "request_id" => $this->utils->getRequestId(), 
            "md5_sum" => md5($this->requestHeaders['X-Request-Sign']),
            "request" => $this->requestBody,
            "player_id" => $this->playerId,
            "external_game_id" => isset($params['game']) ? $params['game'] : NULL, 
            "external_uniqueid" => isset($params['action_id']) ? $params['action_id'] : NULL, 
        );
        if(isset($params['action'])){
            $dataToInsert['sbe_status'] = $this->getSbeStatusByAction($params['action']);
        }

        $transId = $this->original_seamless_wallet_transactions->insertTransactionData($this->api->getTransactionsTable(), $dataToInsert);
        return $transId;
    }

    private function getSbeStatusByAction($action){
        switch (strtolower($action)) {
            case 'rollback': #Rollback
                return GAME_LOGS::STATUS_REFUND;
                break;
            default:
                return GAME_LOGS::STATUS_SETTLED;
                break;
        }
    }

    //Function to get balance of exist player
    private function getPlayerBalance(){
        if($this->playerId){
            $enabled_remote_wallet_client_on_currency=$this->utils->getConfig('enabled_remote_wallet_client_on_currency');
            if(!empty($enabled_remote_wallet_client_on_currency)){
                if($this->utils->isEnabledRemoteWalletClient()){
                    $useReadonly = true;
                    return $this->player_model->getPlayerSubWalletBalance($this->playerId, $this->gamePlatformId, $useReadonly);
                }
            }
            return $this->wallet_model->readonlyMainWalletFromDB($this->playerId);
        } else {
            return false;
        }
    }

    //Function to merge default ouput and response
    private function setResponse($errorCode, $response = [], $errorMessage = null) {
        $defaultResponse = [
            "code" => $errorCode,
            "message" => $this->getErrorMessage($errorCode),
            "balance_currency" => 0.00
        ];
        if(!empty($errorMessage)){
            $defaultResponse['message'] = $errorMessage;
        }
        if($errorCode == self::ERROR_CODE_SUCCESS){
            unset($defaultResponse['code']);
            unset($defaultResponse['message']);
        }
        $output = array_merge($defaultResponse, (array)$response);
        return $this->setOutput($errorCode, $output);
    }

    //Function to return output and save response and request
    private function setOutput($errorCode, $response = []){
        $extraFields = [
            "full_url" => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI'])
        ];
        
        if($this->playerId){
            $extraFields = [
                'player_id'=> $this->playerId
            ];
        }

        $flag = $errorCode == self::ERROR_CODE_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $responseResultId = $this->response_result->saveResponseResult(
            $this->gamePlatformId,
            $flag,
            $this->action,
            $this->requestBody,
            $response,
            200,
            null,
            is_array($this->requestHeaders) ? json_encode($this->requestHeaders) : $this->requestHeaders,
            $extraFields
        );

        $result = $response;
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partial_output_on_error = false;
        $http_status_code = $this->statusCode;
        return $this->returnJsonResult($result, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code);
    }
}

