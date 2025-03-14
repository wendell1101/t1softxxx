<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/*
 * Mascot Single Wallet API Controller
 * OGP-33037
 *
 * @author  Jerbey Capoquian
 *
 4.2 Seamless V2 API
    The server on the operator side should be ready to receive API calls from the game provider. JSON-RPC 2.0(https://www.simple-is-better.org/json-rpc/transport_http.html)
    over HTTPS is used to implement the server.
    The game provider sends requests to the operator server and expects to receive a corresponding response.
    If the response is broken or failed for some reason, the system will keep trying to request it again with some
    delay.
    To make it secure the Seamless API handler should expect using a client certificate to authorize requests.
    Here’s the certificate authority to check it: seamless-ca.pem.
    ```
        -----BEGIN CERTIFICATE-----
    MIIBqjCCAVCgAwIBAgIUCQLg6ziPP6IgB9Qm2hD6w0vyjgkwCgYIKoZIzj0EAwIw
    ITEfMB0GA1UEAxMWY2FsbGJhY2sgQVBJIGF1dGhvcml0eTAeFw0xNzA5MjEwOTA5
    MDBaFw00NzA5MTQwOTA5MDBaMCExHzAdBgNVBAMTFmNhbGxiYWNrIEFQSSBhdXRo
    b3JpdHkwWTATBgcqhkjOPQIBBggqhkjOPQMBBwNCAAT2RwBzQ1zN+5Zyzt/5KXrg
    ZTiBqvcsyd8RB+7shuFaeCyIfD7vpCjXLODdQeXfZwrneAdqtWWReTVDMjwVesJW
    o2YwZDAOBgNVHQ8BAf8EBAMCAQYwEgYDVR0TAQH/BAgwBgEB/wIBAjAdBgNVHQ4E
    FgQU/b1mlPij+20vRqIMXFKqPNMQangwHwYDVR0jBBgwFoAU/b1mlPij+20vRqIM
    XFKqPNMQangwCgYIKoZIzj0EAwIDSAAwRQIhAIhn77uGtsMWePl7Wi1e9PcryMXJ
    k13KuuFA/ZAWLA76AiBUhtS6hkGD6iT1iOku3zMMIfw1XWfHJGqUxORo+7AUag==
    -----END CERTIFICATE-----
    ```
    Here is the example of Nginx config for TLS termination of traffic and authorization requests:

    --------------------------------------------------------------------------------------------------------
    server {
        listen 443 ssl;
        server_name "seamless.example.com";

        ssl_certificate YOUR_OWN_CERTIFICATE;
        ssl_certificate_key YOUR_OWN_CERTIFICATE_KEY;

        ssl_verify_client on;
        ssl_client_certificate /path/to/seamless-ca.pem;

        location / {
            proxy_pass http://YOUR_JSONRPC_SERVER;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-for $remote_addr;
        }
    }
    --------------------------------------------------------------------------------------------------------
*/

class mascot_service_api extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    const ALLOWED_ACTION = ['getBalance', 'withdrawAndDeposit', 'rollbackTransaction'];
    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_001 = 1;
    const ERROR_CODE_002 = 2;
    const ERROR_CODE_003 = 3;
    const ERROR_CODE_004 = 4;
    const ERROR_CODE_005 = 5;
    const ERROR_CODE_006 = 6;
    const ERROR_CODE_007 = 7;

    const ERROR_MESSAGE = [
          0 => "Success",
        1 => "ErrNotEnoughMoneyCode",
        2 => "ErrIllegalCurrencyCode",
        3 => "ErrNegativeDepositCode",
        4 => "ErrNegativeWithdrawalCode",
        5 => "ErrSpendingBudgetExceeded",
        6 => "ErrMaxBetLimitExceededCode",
        7 => "ErrInternalErrorCode",
    ];

    const METHOD_RULES_PARAMS = [
        "getBalance" => array(
            "callerId" => "Required|Integer",
            "playerName" => "Required|String",
            "currency" => "Required|String",
            "gameId" => "String",
            "sessionId" => "String",
            "sessionAlternativeId" => "String",
            "bonusId" => "String"
        ),
        "withdrawAndDeposit" => array(
            "callerId" => "Required|Integer",
            "playerName" => "Required|String",
            "withdraw" => "Required|Integer|NonNegative",
            "deposit" => "Required|Integer|NonNegative",
            "currency" => "Required|String",
            "transactionRef" => "Required|String",
            "gameRoundRef" => "String",
            "gameId" => "String",
            "source" => "String",
            "reason" => "String",
            "sessionId" => "String",
            "sessionAlternativeId" => "String",
            "spinDetails" => "Array",
            "bonusId" => "String",
            "chargeFreerounds" => "Integer"
        ),
        "rollbackTransaction" => array(
            "callerId" => "Required|Integer",
            "playerName" => "Required|String",
            "transactionRef" => "Required|String",
            "gameRoundRef" => "String",
            "gameId" => "String",
            "sessionId" => "String",
            "sessionAlternativeId" => "String",
        )
    ];
    
    private function getGamePlatformId(){
        return MASCOT_SEAMLESS_GAME_API;
    }

    //Initial callback request
    public function initial($apiId = null){
        $this->requestBody = file_get_contents("php://input");
        $this->requestHeaders = $this->input->request_headers();
        $this->gamePlatformId = !empty($apiId) ? $apiId : $this->getGamePlatformId();
        $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);

        $this->playerId = null;
        $this->checkPreviousMonth = false;
        if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
            $this->checkPreviousMonth = true;
        }

        $requestArray = json_decode($this->requestBody, true);
        $response = [];
        $errorMessage = null;
        $this->requestId = isset($requestArray['id']) ? $requestArray['id'] : null;
        $this->method = $method = isset($requestArray['method']) ? $requestArray['method'] : null;
        try {
            if(!$this->api) {
                throw new Exception(__LINE__.":Invalid API.", self::ERROR_CODE_007);
            }

            if(is_null($this->requestId)){
                throw new Exception(__LINE__.":Empty id.", self::ERROR_CODE_007);
            }

            if(empty($this->method)){
                throw new Exception(__LINE__.":Empty method.", self::ERROR_CODE_007);
            }

            if($this->external_system->isGameApiMaintenance($this->gamePlatformId)){
                throw new Exception(__LINE__.":The game is on maintenance.", self::ERROR_CODE_007);   
            }

            if(!$this->api->validateWhiteIP()){
                throw new Exception(__LINE__.":Invalid IP.", self::ERROR_CODE_007);
            }

            if(!method_exists($this, $this->method)) {
                throw new Exception(__LINE__.":Invalid method.", self::ERROR_CODE_007);
            }

            if(empty($this->requestBody)){
                throw new Exception(__LINE__.":Empty request body.", self::ERROR_CODE_007);
            }

            if(!in_array($this->method, self::ALLOWED_ACTION)) {
                throw new Exception(__LINE__.":Request method not allowed.", self::ERROR_CODE_007);
            }

            if(in_array($this->method, $this->api->list_of_method_for_force_error)){
                throw new Exception(__LINE__.":Force error.", self::ERROR_CODE_007);
            }

            $this->validateParams($requestArray);
            $this->authenticate($requestArray);

            list($errorCode, $response) = $this->$method($requestArray);

        } catch (Exception $e) {
            $this->utils->debug_log('mascot encounter error at line and message', $e->getMessage());
            $messageArray = explode(":", $e->getMessage());
            $errorMessage = isset($messageArray[1]) ? $messageArray[1] : null;
            $errorCode = $e->getCode();
        }

        return $this->setResponse($errorCode, $response, $errorMessage);
    }

    private function rollbackTransaction($requestArray){
        $params = isset($requestArray['params']) ? $requestArray['params'] : [];
        $uniqueid = isset($params['transactionRef']) ? "R_".$params['transactionRef'] : null;
        $transactionRef = isset($params['transactionRef']) ? $params['transactionRef'] : null;

        $isRollbackExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ["external_uniqueid"=> $uniqueid]);
        if($isRollbackExist){
            return [self::ERROR_CODE_SUCCESS, array(
                "result" => (object)[]
                )
            ];
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $isRollbackExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ["external_uniqueid"=> $uniqueid]);
                if($isRollbackExist){
                    return [self::ERROR_CODE_SUCCESS, array(
                        "result" => (object)[]
                        )
                    ];
                }
            }
        }

        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $transactionRef],['id', 'after_balance', 'deposit', 'withdraw', 'game_round_ref']);
        if(empty($transactionDetails)){
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $transactionRef],['id', 'after_balance', 'deposit', 'withdraw', 'game_round_ref']);
            }
        }

        $errorCode = self::ERROR_CODE_007;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode, $transactionDetails, $uniqueid) {
            $success = false;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['beforeBalance'] = $beforeBalance;
            $params['afterBalance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['status'] = GAME_LOGS::STATUS_REFUND;
            $params['gameRoundRef'] = isset($transactionDetails['game_round_ref']) ? $transactionDetails['game_round_ref'] : null;

            $transId = $this->processRequestData($params);
            if($transId){
                // $errorCode = self::ERROR_CODE_SUCCESS;
                // $response = array(
                //     "result" => []
                // );
                // return true;
            }
            if($transId){
                if(!empty($transactionDetails)){
                    $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                    if(!empty($configEnabled)){
                        $isEnd = $params['status'] == GAME_LOGS::STATUS_SETTLED ? true : false;
                        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;   
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                        $this->wallet_model->setGameProviderActionType('refund');
                        $this->wallet_model->setGameProviderRoundId($params['gameRoundRef']);
                        $this->wallet_model->setGameProviderBetAmount($withdraw);
                        $this->wallet_model->setGameProviderIsEndRound($isEnd);
                    } 

                    $deposit = isset($transactionDetails['deposit']) ? $transactionDetails['deposit'] : 0;
                    $withdraw = isset($transactionDetails['withdraw']) ? $transactionDetails['withdraw'] : 0;
                    $resultAmount = $deposit - $withdraw;
                    if($resultAmount > 0){ #decrease
                        $amountToDeduct = abs($resultAmount);
                        $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToDeduct, $afterBalance);
                    } else if($resultAmount < 0 ) { #increase
                        $amountToAdd = abs($resultAmount);
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToAdd, $afterBalance);
                    } else if($resultAmount == 0 ){
                        $success = true;
                    }

                    if($success){
                        if(is_null($afterBalance)){
                            $afterBalance = $this->getPlayerBalance();
                            if($afterBalance === false){
                                return false;
                            }
                        }

                        $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['external_uniqueid' => $uniqueid], ['after_balance' => $afterBalance]);
                        $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['id' => $transactionDetails['id']], ['sbe_status' => GAME_LOGS::STATUS_REFUND]);
                        $errorCode = self::ERROR_CODE_SUCCESS;
                        $response = array(
                            "result" => (object)[]
                        );
                    }
                } else {
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                        $response = array(
                            "result" => (object)[]
                        );
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    /*
    withdrawAndDeposit provides a more efficient way of making deposits and withdraws as it requires one call rather than two.
    In case of a bonus free round game, this method should be processed as follows:
    If the value of the withdraw field > 0 and the number of bonus free rounds remaining >= the value of the chargeFreerounds field, then reduce the number of bonus free rounds remaining by the value of the chargeFreerounds
    field and ignore the debiting of the amount from the player’s balance specified in the withdraw field.
    If the condition described above is not met, the player’s balance should be decreased on the amount from the
    withdraw field.

     */
    private function withdrawAndDeposit($requestArray){
        $params = isset($requestArray['params']) ? $requestArray['params'] : [];
        $uniqueid = isset($params['transactionRef']) ? $params['transactionRef'] : null;
        $rollbackid = isset($params['transactionRef']) ? "R_".$params['transactionRef'] : null;
        $isRollbackExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ["external_uniqueid"=> $rollbackid]);
        if($isRollbackExist){
            throw new Exception(__LINE__.":Transaction already rollback.", self::ERROR_CODE_007);
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $isRollbackExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ["external_uniqueid"=> $rollbackid]);
                if($isRollbackExist){
                    throw new Exception(__LINE__.":Transaction already rollback.", self::ERROR_CODE_007);
                }
            }
        }

        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            return [self::ERROR_CODE_SUCCESS, array(
                "result" => array(
                        "newBalance" => $this->api->dBtoGameAmount($transactionDetails['after_balance']),
                        "transactionId" => (string)$transactionDetails['id']
                    )
                )
            ];
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
                if(!empty($transactionDetails)){
                    return [self::ERROR_CODE_SUCCESS, array(
                        "result" => array(
                                "newBalance" => $this->api->dBtoGameAmount($transactionDetails['after_balance']),
                                "transactionId" => (string)$transactionDetails['id']
                            )
                        )
                    ];
                }
            }
        }

        $errorCode = self::ERROR_CODE_007;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode) {
            $success = false;
            $deposit = isset($params['deposit']) ? $this->api->gameAmountToDBTruncateNumber($params['deposit']) : null;
            $withdraw = isset($params['withdraw']) ? $this->api->gameAmountToDBTruncateNumber($params['withdraw']) : null;
            $uniqueid = isset($params['transactionRef']) ? $params['transactionRef'] : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['beforeBalance'] = $beforeBalance;
            $params['afterBalance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['status'] = $this->getStatusByReason($params['reason']);

            $transId = $this->processRequestData($params);
            if($transId){
                if($this->utils->compareResultFloat($withdraw, '>', 0)) {
                    if($this->utils->compareResultFloat($withdraw, '>', $beforeBalance)) {
                        $errorCode = self::ERROR_CODE_001;
                        return false;
                    }
                }

                $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($configEnabled)){
                    $isEnd = $params['status'] == GAME_LOGS::STATUS_SETTLED ? true : false;
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;   
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('bet-payout');
                    $this->wallet_model->setGameProviderRoundId($params['gameRoundRef']);
                    $this->wallet_model->setGameProviderBetAmount($withdraw);
                    $this->wallet_model->setGameProviderIsEndRound($isEnd);
                } 

                $resultAmount = $deposit - $withdraw;
                if($resultAmount > 0){ #increase
                    $amountToAdd = $resultAmount;
                    $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToAdd, $afterBalance);
                } else if($resultAmount < 0 ) { #decrease
                    $amountToDeduct = abs($resultAmount);
                    $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amountToDeduct, $afterBalance);
                } else if($resultAmount == 0 ){
                    $success = true;
                }

                if($success){
                    if(is_null($afterBalance)){
                        $afterBalance = $this->getPlayerBalance();
                        if($afterBalance === false){
                            return false;
                        }
                    }

                    $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['external_uniqueid' => $uniqueid], ['after_balance' => $afterBalance]);
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = array(
                        "result" => array(
                            "newBalance" => $this->api->dBtoGameAmount($afterBalance),
                            "transactionId" => (string)$transId
                        )
                    );
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    private function getStatusByReason($reason){
        switch (strtoupper($reason)) {
            case 'GAME_PLAY_FINAL':
                return GAME_LOGS::STATUS_SETTLED;
                break;
            case 'GAME_PLAY':
                return GAME_LOGS::STATUS_PENDING;
                break;
            default:
                return GAME_LOGS::STATUS_PENDING;
                break;
        }
    }

    private function processRequestData($params){
        $dataToInsert = array(
            "player_id" => $this->playerId,
            "game_platform_id" => $this->getGamePlatformId(),
            "method" => $this->method,
            #params data
            "caller_id" => isset($params['callerId']) ? $params['callerId'] : NULL, 
            "player_name" => isset($params['playerName']) ? $params['playerName'] : NULL, 
            "withdraw_in_cent" => isset($params['withdraw']) ? $params['withdraw'] : NULL, 
            "deposit_in_cent" => isset($params['deposit']) ? $params['deposit'] : NULL, 
            "withdraw" => isset($params['withdraw']) ? $this->api->gameAmountToDBTruncateNumber($params['withdraw']) : NULL, 
            "deposit" => isset($params['deposit']) ? $this->api->gameAmountToDBTruncateNumber($params['deposit']) : NULL, 
            "currency" => isset($params['currency']) ? $params['currency'] : NULL,
            "transaction_ref" => isset($params['transactionRef']) ? $params['transactionRef'] : NULL,
            "game_round_ref" => isset($params['gameRoundRef']) ? $params['gameRoundRef'] : NULL,
            "game_id" => isset($params['gameId']) ? $params['gameId'] : NULL,
            "source" => isset($params['source']) ? $params['source'] : NULL,
            "reason" => isset($params['reason']) ? $params['reason'] : NULL,
            "session_id" => isset($params['sessionId']) ? $params['sessionId'] : NULL,
            "session_alternative_id" => isset($params['sessionAlternativeId']) ? $params['sessionAlternativeId'] : NULL,
            "spin_details" => isset($params['spinDetails']) ? json_encode($params['spinDetails']) : NULL, 
            "bonus_id" => isset($params['bonusId']) ? $params['bonusId'] : NULL,
            "bonus_free_rounds" => isset($params['bonusFreeRounds']) ? $params['bonusFreeRounds'] : NULL,
            "charge_free_rounds" => isset($params['chargeFreerounds']) ? $params['chargeFreerounds'] : NULL,
            "json_request" => $this->requestBody, 
            #sbe default
            "sbe_status" => isset($params['status']) ? $params['status'] : NULL, 
            "before_balance" => isset($params['beforeBalance']) ? $params['beforeBalance'] : NULL, 
            "after_balance" => isset($params['afterBalance']) ? $params['afterBalance'] : NULL, 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
            "request_id" => $this->utils->getRequestId(), 
            "md5_sum" => null,
            "external_uniqueid" => isset($params['external_uniqueid']) ? $params['external_uniqueid'] : NULL, 
        );

        $transId = $this->original_seamless_wallet_transactions->insertTransactionData($this->api->getTransactionsTable(), $dataToInsert);
        return $transId;
    }

    private function getBalance($requestArray){
        #note conversion rate should be 1000, need convert to cents
        $response = array(
            "result" => array(
                "balance" => $this->api->dBtoGameAmount($this->getPlayerBalance())
            )
        );

        return [self::ERROR_CODE_SUCCESS, $response];
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

    #Function for player authentication
    private function authenticate($requestArray){
        $params = isset($requestArray['params']) ? $requestArray['params'] : [];
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_007);
        }

        $gameUsername = isset($params['playerName']) ? $params['playerName'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty playerName.", self::ERROR_CODE_007); 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_101); 
        }

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_101); 
        }
        $this->playerId = $playerId;

        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__.":Player is blocked.", self::ERROR_CODE_110);
        }
        return $playerId;
    }

    #Function to validate params
    private function validateParams($requestArray){
        $params = isset($requestArray['params']) ? $requestArray['params'] : [];
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_007);
        }

        $rules = self::METHOD_RULES_PARAMS;
        $rules = isset($rules[$this->method]) ? $rules[$this->method] : [];

        if(!empty($rules)){
            foreach($rules as $key => $rule){
                $key_rules = explode("|", $rule);
                if(!empty($key_rules)){
                    foreach ($key_rules as $keyi => $key_rule) {
                        if($key_rule == 'Required' && !isset($params[$key])){
                            $this->utils->error_log("MASCOT SEAMLESS SERVICE: (validateParams) Missing Parameter: ". $key, $params, $rules);   
                            throw new Exception(__LINE__.":Required param({$key}).", self::ERROR_CODE_007);
                        } else if($key_rule == 'Required' && isset($params[$key])){
                            if($key == "callerId"){
                                $callerId = isset($params[$key]) ? $params[$key] : null;
                                if($callerId != $this->api->caller_id){
                                    throw new Exception(__LINE__.":Invalid callerId request.", self::ERROR_CODE_007);
                                }
                            } else if($key == "currency"){
                                $currency = isset($params[$key]) ? $params[$key] : null;
                                if($currency != $this->api->currency){
                                    throw new Exception(__LINE__.":Invalid currency request.", self::ERROR_CODE_002);
                                }
                            }

                        }

                        if($key_rule == 'Integer'  && isset($params[$key]) && !is_int($params[$key])){
                            $this->utils->error_log("MASCOT SEAMLESS SERVICE: (validateParams) Parameters is not integer: ". $key . '=' . $params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be integer.", self::ERROR_CODE_007);
                        }

                        if($key_rule == 'String'  && isset($params[$key]) && !is_string($params[$key])){
                            $this->utils->error_log("MASCOT SEAMLESS SERVICE: (validateParams) Parameters is not string: ". $key . '=' . $params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be string.", self::ERROR_CODE_007);
                        }

                        if($key_rule == 'Array'  && isset($params[$key]) && !is_array($params[$key])){
                            $this->utils->error_log("MASCOT SEAMLESS SERVICE: (validateParams) Parameters is not array: ". $key ,$params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be object.", self::ERROR_CODE_007);
                        }

                        if($key_rule=='NonNegative' && isset($params[$key]) && $params[$key] < 0){
                        $this->utils->error_log("MASCOT SEAMLESS SERVICE: (validateParams) Parameters is less than 0: ". $key . '=' . $params[$key], $params, $rules); 
                            if(strtolower($key) == "deposit"){
                                $error_code = self::ERROR_CODE_003;
                            } else if (strtolower($key) == "withdraw"){
                                $error_code = self::ERROR_CODE_004;
                            } else {
                                $error_code = self::ERROR_CODE_007;
                            }
                        throw new Exception(__LINE__.":Param({$key}) should be >= 0.", $error_code);
                    }
                    }
                } else {
                    throw new Exception(__LINE__.":Empty rules.", self::ERROR_CODE_007);
                }
            }  
        } else {
            throw new Exception(__LINE__.":Empty rules.", self::ERROR_CODE_007);
        }

        return true;
    }

    // Function to get error message by code
    private function getErrorMessage($errorCode) {
        $errorMessage = self::ERROR_MESSAGE;
        $errorMessage =  isset($errorMessage[$errorCode]) ? $errorMessage[$errorCode] : "Unknown Error Code";
        return $errorMessage;
    }

    //Function to merge default ouput and response
    private function setResponse($errorCode, $response = [], $errorMessage = null) {
        $defaultResponse = [
            "jsonrpc" => $this->api->getSystemInfo('jsonrpc_version', '2.0'),
            "id" => $this->requestId,
            "error" => array(
                "code" => $errorCode,
                "message" => $this->getErrorMessage($errorCode)
            )
        ];

        if(!empty($errorMessage)){
            $defaultResponse['error']['message'] = $errorMessage;
        }

        if($errorCode == self::ERROR_CODE_SUCCESS){
            unset($defaultResponse['error']);
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
            $this->method,
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
        $http_status_code = 0;
        return $this->returnJsonResult($result, $addOrigin, $origin, $pretty, $partial_output_on_error, $http_status_code);
    }
}

