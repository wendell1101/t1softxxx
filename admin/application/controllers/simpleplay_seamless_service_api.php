<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
/*
 * Simpleplay Single Wallet API Controller
 * OGP-33391
 *
 * @author  Jerbey Capoquian
 *
 * sample endpoint 
   http://admin.og.local/simpleplay_seamless_service_api/encrypt
   http://admin.og.local/simpleplay_seamless_service_api/Decrypt
   http://admin.og.local/simpleplay_seamless_service_api/GetUserBalance
   http://admin.og.local/simpleplay_seamless_service_api/PlaceBet
   http://admin.og.local/simpleplay_seamless_service_api/PlayerWin
   http://admin.og.local/simpleplay_seamless_service_api/PlayerLost
   http://admin.og.local/simpleplay_seamless_service_api/PlaceBetCancel

   extra : add param ?isEncoded=true if want to use encode params, this is for testing only

   ****************
   Important Notice

    It is important that you must take care of the following situations and make sure you can process the PlayerWin / PlayerLost correctly.

    *Slot Game
    PlaceBet and PlayerWin / PlayerLost are paired.

    *Table Game
    There is only 1 PlayerWin / PlayerLost for each player in the same game, no matter how many bets the player has placed.

    *Fishing Game
    In case a player played a round of the fishing game with free bullets remain and logged out. When the player logs into the fishing game later and shoots some bullet, no matter he won or not, if he logged out without transferring fund, a PlayerWin / PlayerLost will send. In this scenario, there is no PlaceBet but PlayerWin / PlayerLost send. It may void your checking for a PlayerWin / PlayerLost to match a PlaceBet by GameID.
   ****************
*/

class Simpleplay_seamless_service_api extends BaseController {

    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_USER_ACCOUNT_NOT_EXIST = 1000;
    const ERROR_CODE_INVALID_CURRENCY = 1001;
    const ERROR_CODE_INVALID_AMOUNT = 1002;
    const ERROR_CODE_LOCK_ACCOUNT = 1003;
    const ERROR_CODE_NOT_ENOUGH_BALANCE = 1004;
    const ERROR_CODE_GENERAL_ERROR = 1005;
    const ERROR_CODE_DECRYPT_ERROR = 1006;
    const ERROR_CODE_SESSION_EXPIRED = 1007;
    const ERROR_CODE_SYSTEM_ERROR = 9999;
    const ALLOWED_METHOD = ['getUserBalance', 'placeBet', 'playerWin', 'playerLost', 'placeBetCancel'];
    const METHOD_RULES_PARAMS = [
        "getUserBalance" => array(
            "username" => "Required|String",
            "currency" => "Required|String",
        ),
        "placeBet" => array(
            "username" => "Required|String",
            "currency" => "Required|String",
            "amount" => "Required|Numeric|NonNegative",
            "txnid" => "Required|String",
            "timestamp" => "Required",
            "ip" => "String",
            "gametype" => "Required|String",
            "hostid" => "Integer",
            "platform" => "Required|Integer",
            "gamecode" => "Required|String",
            "gameid" => "Required|String",
        ),
        "playerWin" => array(
            "username" => "Required|String",
            "currency" => "Required|String",
            "amount" => "Required|Numeric|NonNegative",
            "txnid" => "Required|String",
            "timestamp" => "Required",
            "ip" => "String",
            "gametype" => "Required|String",
            "hostid" => "Integer",
            "gamecode" => "Required|String",
            "gameid" => "Required|String",
            "Payouttime" => "Required",
        ),
        "playerLost" => array(
            "username" => "Required|String",
            "currency" => "Required|String",
            "txnid" => "Required|String",
            "timestamp" => "Required",
            "gametype" => "Required|String",
            "hostid" => "Integer",
            "gamecode" => "Required|String",
            "gameid" => "Required|String",
            "Payouttime" => "Required",
        ),
        "placeBetCancel" => array(
            "username" => "Required|String",
            "currency" => "Required|String",
            "amount" => "Required|Numeric|NonNegative",
            "txnid" => "Required|String",
            "timestamp" => "Required",
            "gametype" => "Required|String",
            "hostid" => "Integer",
            "gamecode" => "Required|String",
            "gameid" => "Required|String",
            "txn_reverse_id" => "Required|String",
        )
    ];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    private function getGamePlatformId(){
        return SIMPLEPLAY_SEAMLESS_GAME_API;
    }

    public function encrypt(){
        $this->gamePlatformId = $this->getGamePlatformId();
        $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
        if($this->api){
            $encryptKey = $this->api->encryptKey;
            $requestBody = file_get_contents("php://input");
            $this->load->library('simpleplay_des', array('key' => $encryptKey, 'iv' => 0));
            return $this->simpleplay_des->encrypt($requestBody);
        } else {
            return "invalid";
        }
    }

    public function decrypt(){
        $this->gamePlatformId = $this->getGamePlatformId();
        $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
        if($this->api){
            $encryptKey = $this->api->encryptKey;
            $requestBody = file_get_contents("php://input");
            $this->load->library('simpleplay_des', array('key' => $encryptKey, 'iv' => 0));
            return $this->simpleplay_des->decrypt($requestBody);
        } else {
            return "invalid";
        }
    }

    //Initial callback request
    public function index($method = null){
        $method = lcfirst($method);
        if($method == 'encrypt' || $method == 'decrypt'){
            $msg = $this->$method();
            return $this->returnText($msg);
        }

        $this->isEncoded = $isEncoded = false;
        if(isset($_GET['isEncoded'])) {
            $this->isEncoded = $isEncoded = ($_GET['isEncoded'] == 1 || $_GET['isEncoded'] == 'true');
        }

        $this->requestBody = urldecode(file_get_contents("php://input"));
        $this->requestHeaders = $this->input->request_headers();
        $this->gamePlatformId = $this->getGamePlatformId();
        $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
        $this->playerId = null;
        $this->checkPreviousMonth = false;
        $this->gameUsername = null;
        $this->currency = null;
        $this->requestString = null;
        if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
            $this->checkPreviousMonth = true;
        }

        $response = [];
        $errorMessage = null;
        $this->method = $method;
        try {
            if(!$this->api) {
                throw new Exception(__LINE__.":Invalid API.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if(!$isEncoded){
                $this->load->library('simpleplay_des', array('key' => $this->api->encryptKey, 'iv' => 0));
                $this->requestString = $requestString = $this->simpleplay_des->decrypt($this->requestBody);
            } else {
                $this->requestString = $requestString = $this->requestBody;
            }
            
            parse_str($requestString, $requestArray);

            if(empty($this->method)){
                throw new Exception(__LINE__.":Empty method.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if($this->external_system->isGameApiMaintenance($this->gamePlatformId)){
                throw new Exception(__LINE__.":The game is on maintenance.", self::ERROR_CODE_GENERAL_ERROR);   
            }

            if(!$this->api->validateWhiteIP()){
                throw new Exception(__LINE__.":Invalid IP.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if(!method_exists($this, $this->method)) {
                throw new Exception(__LINE__.":Invalid method.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if(empty($this->requestBody)){
                throw new Exception(__LINE__.":Empty request body.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if(!in_array($this->method, self::ALLOWED_METHOD)) {
                throw new Exception(__LINE__.":Request method not allowed.", self::ERROR_CODE_GENERAL_ERROR);
            }

            $this->validateParams($requestArray);
            $this->authenticate($requestArray);

            list($errorCode, $response) = $this->$method($requestArray);

        } catch (Exception $e) {
            $this->utils->debug_log('SIMPLEPLAY encounter error at line and message', $e->getMessage());
            $messageArray = explode(":", $e->getMessage());
            $errorMessage = isset($messageArray[1]) ? $messageArray[1] : null;
            $errorCode = $e->getCode();
        }

        return $this->setResponse($errorCode, $response, $errorMessage);
    }

    //Function to merge default ouput and response
    private function setResponse($errorCode, $response = [], $errorMessage = null) {
        $gameUsername = isset($response['gameUsername']) ? $response['gameUsername'] : $this->gameUsername;
        $currency = isset($response['currency']) ? $response['currency'] : $this->currency;
        $balance = isset($response['balance']) ? $response['balance'] : 0;
        if($errorCode != self::ERROR_CODE_SUCCESS && $this->playerId){
            $balance = $this->getPlayerBalance();
        }

        $defaultResponse = [
            "username" => $gameUsername,
            "currency" => $currency,
            "amount" => $balance,
            "error" => $errorCode
        ];

        if(!empty($errorMessage) && $this->isEncoded){
            $defaultResponse['message'] = $errorMessage;
        }
        return $this->setOutput($errorCode, $defaultResponse);
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
        $output = $this->arrayToXml($response, '<RequestResponse/>');
        $responseResultId = $this->response_result->saveResponseResult(
            $this->gamePlatformId,
            $flag,
            $this->method,
            json_encode($this->requestBody),
            $output,
            200,
            null,
            is_array($this->requestHeaders) ? json_encode($this->requestHeaders) : $this->requestHeaders,
            $extraFields
        );
        return $this->returnXml($output);
    }

    private function arrayToXml($array, $rootElement = null, $xml = null) {
        $_xml = $xml;
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }
        foreach ($array as $k => $v) {
            if (is_array($v)) { 
                arrayToXml($v, $k, $_xml->addChild($k));
                }
            else {
                $_xml->addChild($k, $v);
            }
        }
        return $_xml->asXML();
    }

    #Function to validate params
    private function validateParams($params){
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_GENERAL_ERROR);
        }
        $this->gameUsername = isset($params['username']) ? $params['username'] : null;
        $this->currency = isset($params['currency']) ? $params['currency'] : null;
        $rules = self::METHOD_RULES_PARAMS;
        $rules = isset($rules[$this->method]) ? $rules[$this->method] : [];
        if(!empty($rules)){
            foreach($rules as $key => $rule){
                $key_rules = explode("|", $rule);
                if(!empty($key_rules)){
                    foreach ($key_rules as $keyi => $key_rule) {
                        if($key_rule == 'Required' && !isset($params[$key])){
                            $this->utils->error_log("SIMPLEPLAY SEAMLESS SERVICE: (validateParams) Missing Parameter: ". $key, $params, $rules);   
                            throw new Exception(__LINE__.":Required param({$key}).", self::ERROR_CODE_GENERAL_ERROR);
                        } else if($key_rule == 'Required' && isset($params[$key])){
                            if($key == "currency"){
                                $currency = isset($params[$key]) ? $params[$key] : null;
                                if(strtolower($currency) != strtolower($this->api->currency)){
                                    throw new Exception(__LINE__.":Invalid currency request.", self::ERROR_CODE_INVALID_CURRENCY);
                                }
                            }
                        }
                        
                        if($key_rule == 'Numeric'  && isset($params[$key]) && !is_numeric($params[$key])){
                            $this->utils->error_log("SIMPLEPLAY SEAMLESS SERVICE: (validateParams) Parameters is not Numeric: ". $key . '=' . $params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be Numeric.", self::ERROR_CODE_GENERAL_ERROR);
                        }

                        if($key_rule == 'String'  && isset($params[$key]) && !is_string($params[$key])){
                            $this->utils->error_log("SIMPLEPLAY SEAMLESS SERVICE: (validateParams) Parameters is not string: ". $key . '=' . $params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be string.", self::ERROR_CODE_GENERAL_ERROR);
                        }

                        if($key_rule == 'Array'  && isset($params[$key]) && !is_array($params[$key])){
                            $this->utils->error_log("SIMPLEPLAY SEAMLESS SERVICE: (validateParams) Parameters is not array: ". $key ,$params[$key], $params, $rules);   
                            throw new Exception(__LINE__.":Param({$key}) should be object.", self::ERROR_CODE_GENERAL_ERROR);
                        }

                        if($key_rule=='NonNegative' && isset($params[$key]) && $params[$key] < 0){
                            $this->utils->error_log("SIMPLEPLAY SEAMLESS SERVICE: (validateParams) Parameters is less than 0: ". $key . '=' . $params[$key], $params, $rules); 
                            throw new Exception(__LINE__.":Param({$key}) should be >= 0.", self::ERROR_CODE_GENERAL_ERROR);
                        }
                    }
                } else {
                    throw new Exception(__LINE__.":Empty rules.", self::ERROR_CODE_GENERAL_ERROR);
                }
            }  
        } else {
            throw new Exception(__LINE__.":Empty rules.", self::ERROR_CODE_GENERAL_ERROR);
        }

        return true;
    }

    #Function for player authentication
    private function authenticate($params){
        if(empty($params)){
            throw new Exception(__LINE__.":Empty params.", self::ERROR_CODE_GENERAL_ERROR);
        }

        $gameUsername = isset($params['username']) ? $params['username'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty username.", self::ERROR_CODE_USER_ACCOUNT_NOT_EXIST); 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_USER_ACCOUNT_NOT_EXIST); 
        }

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_USER_ACCOUNT_NOT_EXIST); 
        }
        $this->playerId = $playerId;

        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__.":Player is blocked.", self::ERROR_CODE_LOCK_ACCOUNT);
        }
        return $playerId;
    }

    public function getUserBalance(){
        return [self::ERROR_CODE_SUCCESS, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]];
    }

    public function placeBet($params){
        $uniqueid = isset($params['txnid']) ? $params['txnid'] : null;
        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
                if(!empty($transactionDetails)){
                    throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
                }
            }
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode, $uniqueid) {

            $success = false;
            $amount = isset($params['amount']) ? $this->api->gameAmountToDBTruncateNumber($params['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['beforeBalance'] = $beforeBalance;
            $params['afterBalance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['sbe_status'] = GAME_LOGS::STATUS_PENDING;

            $transId = $this->processRequestData($params);
            if($transId){
                if($this->utils->compareResultFloat($amount, '>', 0)) {
                    if($this->utils->compareResultFloat($amount, '>', $beforeBalance)) {
                        $errorCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE;
                        return false;
                    }
                }

                $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($configEnabled)){
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;   
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('bet');
                    $this->wallet_model->setGameProviderRoundId($params['gameid']);
                    $this->wallet_model->setGameProviderBetAmount($amount);
                    $this->wallet_model->setGameProviderIsEndRound(false);
                } 

                if($amount > 0){ 
                    $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                } else if($amount == 0 ){
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
                        "balance" => $this->api->dBtoGameAmount($afterBalance)
                    );
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    public function playerWin($params){
        $uniqueid = isset($params['txnid']) ? $params['txnid'] : null;
        $gametype = isset($params['gametype']) ? $params['gametype'] : null;
        $gameid = isset($params['gameid']) ? $params['gameid'] : null;
        $jackpotWin = isset($params['JackpotWin']) ? $params['JackpotWin'] == 'true' : false;
        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
                if(!empty($transactionDetails)){
                    throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
                }
            }
        }

        if(($gametype == "slot" || $gametype == "table") && !$jackpotWin){
            list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["gameid" =>$gameid, "player_id" => $this->playerId]);
            if(empty($lastId) && $this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["gameid" =>$gameid, "player_id" => $this->playerId]);
            }

            if(empty($lastId)){
                throw new Exception(__LINE__.":Cant find last gameid.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if($lastStatus == GAME_LOGS::STATUS_SETTLED || $lastStatus == GAME_LOGS::STATUS_REFUND || $lastStatus == GAME_LOGS::STATUS_CANCELLED){
                throw new Exception(__LINE__.":Cant settled lost.", self::ERROR_CODE_GENERAL_ERROR);
            }
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode, $uniqueid) {

            $success = false;
            $amount = isset($params['amount']) ? $this->api->gameAmountToDBTruncateNumber($params['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['beforeBalance'] = $beforeBalance;
            $params['afterBalance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['sbe_status'] = GAME_LOGS::STATUS_SETTLED;

            $transId = $this->processRequestData($params);
            if($transId){
                $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($configEnabled)){
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;   
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('payout');
                    $this->wallet_model->setGameProviderRoundId($params['gameid']);
                    $this->wallet_model->setGameProviderBetAmount($amount);
                    $this->wallet_model->setGameProviderIsEndRound(true);


                    if($this->utils->compareResultFloat($amount, '>=', 0)){ 
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else {
                        $success = false;
                    }
                } else {
                    if($this->utils->compareResultFloat($amount, '>', 0)){ 
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else if($amount == 0 ){
                        $success = true;
                    }
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
                        "balance" => $this->api->dBtoGameAmount($afterBalance)
                    );
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    public function playerLost($params){
        $uniqueid = isset($params['txnid']) ? $params['txnid'] : null;
        $gameid = isset($params['gameid']) ? $params['gameid'] : null;
        $gametype = isset($params['gametype']) ? $params['gametype'] : null;
        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
                if(!empty($transactionDetails)){
                    throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
                }
            }
        }

        if($gametype == "slot" || $gametype == "table"){
            list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["gameid" =>$gameid, "player_id" => $this->playerId]);
            if(empty($lastId) && $this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["gameid" =>$gameid, "player_id" => $this->playerId]);
            }

            if(empty($lastId)){
                throw new Exception(__LINE__.":Cant find last gameid.", self::ERROR_CODE_GENERAL_ERROR);
            }

            if($lastStatus == GAME_LOGS::STATUS_SETTLED || $lastStatus == GAME_LOGS::STATUS_REFUND || $lastStatus == GAME_LOGS::STATUS_CANCELLED){
                throw new Exception(__LINE__.":Cant settled lost.", self::ERROR_CODE_GENERAL_ERROR);
            }
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode, $uniqueid) {

            $success = false;
            $amount = 0;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['beforeBalance'] = $beforeBalance;
            $params['afterBalance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['amount'] = 0;
            $params['sbe_status'] = GAME_LOGS::STATUS_SETTLED;

            $transId = $this->processRequestData($params);
            if($transId){
                $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($configEnabled)){
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;   
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('payout');
                    $this->wallet_model->setGameProviderRoundId($params['gameid']);
                    $this->wallet_model->setGameProviderBetAmount($amount);
                    $this->wallet_model->setGameProviderIsEndRound(true);


                    if($this->utils->compareResultFloat($amount, '>=', 0)){ 
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else {
                        $success = false;
                    }
                } else {
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
                        "balance" => $this->api->dBtoGameAmount($afterBalance)
                    );
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    public function placeBetCancel($params){
        $uniqueid = isset($params['txnid']) ? $params['txnid']: null;
        $betid = isset($params['txn_reverse_id']) ? $params['txn_reverse_id'] : null;
        $gameid = isset($params['gameid']) ? $params['gameid'] : null;
        $gametype = isset($params['gametype']) ? $params['gametype'] : null;
        $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
        if(!empty($transactionDetails)){
            throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
        } else {
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $transactionDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $uniqueid],['id', 'after_balance']);
                if(!empty($transactionDetails)){
                    throw new Exception(__LINE__.":Duplicate request.", self::ERROR_CODE_GENERAL_ERROR);
                }
            }
        }

        $betExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $betid]);
        $updatePrevTable = false;
        if(!$betExist){
            if($this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                $betExist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $betid]);
                if($betExist){
                    $updatePrevTable = true;
                }
            }
        }

        if(!$betExist){
            throw new Exception(__LINE__.":Bet not exist", self::ERROR_CODE_GENERAL_ERROR);
        } else {
            if(($gametype == "slot")){
                list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["gameid" =>$gameid, "player_id" => $this->playerId]);
                if(empty($lastId) && $this->api->use_monthly_transactions_table && $this->checkPreviousMonth){
                    list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->api->getTransactionsTable(), ["gameid" =>$gameid, "player_id" => $this->playerId]);
                }

                if(empty($lastId)){
                    throw new Exception(__LINE__.":Cant find last gameid.", self::ERROR_CODE_GENERAL_ERROR);
                }

                if($lastStatus == GAME_LOGS::STATUS_SETTLED || $lastStatus == GAME_LOGS::STATUS_REFUND || $lastStatus == GAME_LOGS::STATUS_CANCELLED){
                    throw new Exception(__LINE__.":Gameid already settled.", self::ERROR_CODE_GENERAL_ERROR);
                }
            }

            if($updatePrevTable){
                $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $betid],['sbe_status']);
                if($betDetails['sbe_status'] == GAME_LOGS::STATUS_CANCELLED){
                    throw new Exception(__LINE__.":Bet already canceled.", self::ERROR_CODE_GENERAL_ERROR);
                }

                $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid' => $betid], ['sbe_status' => GAME_LOGS::STATUS_CANCELLED]); 
            } else {
                $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $betid],['sbe_status']);
                if($betDetails['sbe_status'] == GAME_LOGS::STATUS_CANCELLED){
                    throw new Exception(__LINE__.":Bet already canceled.", self::ERROR_CODE_GENERAL_ERROR);
                }

               $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->api->getTransactionsTable(), ['external_uniqueid' => $betid], ['sbe_status' => GAME_LOGS::STATUS_CANCELLED]); 
           }
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $response = [];
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($params, &$response, &$errorCode, $uniqueid) {

            $success = false;
            $amount = isset($params['amount']) ? $this->api->gameAmountToDBTruncateNumber($params['amount']) : null;
            $beforeBalance = $this->getPlayerBalance();
            $afterBalance =  null;
            if($beforeBalance === false){
                return false;
            }
            
            $params['beforeBalance'] = $beforeBalance;
            $params['afterBalance'] = $beforeBalance;
            $params['external_uniqueid'] = $uniqueid;
            $params['sbe_status'] = GAME_LOGS::STATUS_CANCELLED;

            $transId = $this->processRequestData($params);
            if($transId){
                $configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
                if(!empty($configEnabled)){
                    $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;   
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                    $this->wallet_model->setGameProviderActionType('cancel');
                    $this->wallet_model->setGameProviderRoundId($params['gameid']);
                    $this->wallet_model->setGameProviderBetAmount($amount);
                    $this->wallet_model->setGameProviderIsEndRound(true);


                    if($this->utils->compareResultFloat($amount, '>=', 0)){ 
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else {
                        $success = false;
                    }
                } else {
                    if($this->utils->compareResultFloat($amount, '>', 0)){ 
                        $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    } else if($amount == 0 ){
                        $success = true;
                    }
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
                        "balance" => $this->api->dBtoGameAmount($afterBalance)
                    );
                }
            }
            return $success;
        });

        return [$errorCode, $response];
    }

    private function processRequestData($params){
        $dataToInsert = array(
            "player_id" => $this->playerId,
            "game_platform_id" => $this->getGamePlatformId(),
            "method" => $this->method,
            #params data
            "username" => isset($params['username']) ? $params['username'] : NULL, 
            "currency" => isset($params['currency']) ? $params['currency'] : NULL, 
            "amount" => isset($params['amount']) ? $this->api->gameAmountToDBTruncateNumber($params['amount']) : NULL, 
            "txnid" => isset($params['txnid']) ? $params['txnid'] : NULL,
            "txn_reverse_id" => isset($params['txn_reverse_id']) ? $params['txn_reverse_id'] : NULL,
            "timestamp" => isset($params['timestamp']) ? $params['timestamp'] : NULL,
            "Payouttime" => isset($params['Payouttime']) ? $params['Payouttime'] : NULL,
            "ip" => isset($params['ip']) ? $params['ip'] : NULL,
            "gametype" => isset($params['gametype']) ? $params['gametype'] : NULL,
            "hostid" => isset($params['hostid']) ? $params['hostid'] : NULL,
            "platform" => isset($params['platform']) ? $params['platform'] : NULL,
            "gamecode" => isset($params['gamecode']) ? $params['gamecode'] : NULL,
            "gameid" => isset($params['gameid']) ? $params['gameid'] : NULL,
            "JackpotWin" => isset($params['JackpotWin']) ? $params['JackpotWin'] : NULL,
            "JackpotContribution" => isset($params['JackpotContribution']) ? $params['JackpotContribution'] : NULL,
            "JackpotType" => isset($params['JackpotType']) ? $params['JackpotType'] : NULL,
            "betdetails" => isset($params['betdetails']) ? json_encode($params['betdetails']) : NULL,
            "payoutdetails" => isset($params['payoutdetails']) ? json_encode($params['payoutdetails']) : NULL,
            "request" => $this->requestBody,
            "request_encoded" => $this->requestString, 
            #sbe default
            "sbe_status" => isset($params['sbe_status']) ? $params['sbe_status'] : NULL, 
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
}

