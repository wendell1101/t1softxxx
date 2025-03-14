<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/*
    ENDPOINT: 
    Player bets : /jgameworksapi/Cash/Bet
    Get player balance : /jgameworksapi/Cash/Get
    Player Authentication : /jgameworksapi/VerifySession

    EXTRA ENDPOINT:
    Get token for testing : /jgameworksapi/GetToken
 */

class Jgameworks_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_PLAYER_NOT_FOUND = 2000;
    const ERROR_CODE_FAILED_BET = 2010;
    const ERROR_CODE_OTHER = 3000;
    const ALLOWED_METHOD = ['getToken', 'verifySession', 'cashGet', 'cashBet', 'cashRollback'];
    const FUNCTION_IGNORE_TOKEN_VALIDATION = ['getToken', 'cashGet', 'cashRollback'];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    private function getGamePlatformId(){
        return JGAMEWORKS_SEAMLESS_API;
    }
    

    private function getCurrencyAndValidateDB($currency) {
        return !empty($currency) && $this->validateCurrencyAndSwitchDB($currency);
    }

    protected function validateCurrencyAndSwitchDB($currency) {
        if (!$this->utils->isEnabledMDB() || empty($currency)) {
            return false;
        }

        $currency = strtolower($currency);

        // Validate currency name and switch to target DB if valid
        if ($this->utils->isAvailableCurrencyKey($currency)) {
            $_multiple_db = Multiple_db::getSingletonInstance();
            $_multiple_db->switchCIDatabase($currency);
            return true;
        }

        return false;
    }

    public function index($segment1 = null, $segment2 = "")
    {
        // Concatenate segments and prepare method
        $this->method = $method = lcfirst($segment1 . $segment2);

        // Initialize request data
        $this->initializeRequestData();

        // Initialize variables
        $response = array();
        $errorMessage = "";
        $errorCode = self::ERROR_CODE_OTHER;
        $requestArray = json_decode($this->requestBody, true);

        try {
            // Handle token validation and manipulation
            $token = isset($requestArray['token']) ? $requestArray['token'] : null;
            if (!empty($token) && strpos($token, "-") !== false) {
                $tokenParts = explode("-", $token);
                $currency = isset($tokenParts[0]) ? $tokenParts[0] : "";

                if (!$this->getCurrencyAndValidateDB($currency)) {
                    throw new Exception(__LINE__ . ": Invalid Currency.", self::ERROR_CODE_OTHER);
                }

                // Remove currency prefix from token
                $requestArray['token'] = substr($token, strlen($currency) + 1);
            }

            // Handle game ID and determine platform
            if (isset($requestArray['gameid'])) {
                $gameIdArray = explode('/', $requestArray['gameid']);
                $provider = isset($gameIdArray[0]) ? strtolower($gameIdArray[0]) : "unknown";

                switch ($provider) {
                    case 'pp':
                        $this->gamePlatformId = PP_JGAMEWORKS_SEAMLESS_API;
                        break;
                    case 'jili':
                        $this->gamePlatformId = JILI_JGAMEWORKS_SEAMLESS_API;
                        break;
                    case 'pg':
                        $this->gamePlatformId = PG_JGAMEWORKS_SEAMLESS_API;
                        break;
                    default:
                        throw new Exception(__LINE__ . ": Invalid game ID.", self::ERROR_CODE_OTHER);
                }
            }

            // Load API object
            $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
            if (!$this->api) {
                throw new Exception(__LINE__ . ": Invalid API.", self::ERROR_CODE_OTHER);
            }

            // Validate headers, method, game status, authentication, and parameters
            $this->validateHeaders();
            $this->validateMethod($method, $requestArray);
            $this->checkGameStatus();
            $this->authenticate($requestArray);
            $this->validateParams($requestArray);

            // Call the API method dynamically
            list($errorCode, $response, $errorMessage) = call_user_func(array($this, $method), $requestArray);

            // Check response validity
            // if (empty($response)) {
            //     throw new Exception(__LINE__ . ": Response empty.", self::ERROR_CODE_OTHER);
            // }
        } catch (Exception $e) {
            // Handle exceptions
            $this->handleException($e, $errorMessage, $errorCode);
        }

        // Set and return the response
        return $this->setResponse($errorCode, $response, $errorMessage);
    }


    private function initializeRequestData() {
        $this->requestBody = file_get_contents("php://input");
        $this->requestHeaders = $this->input->request_headers();
        $this->gamePlatformId = $this->getGamePlatformId();
        $this->remoteWalletEnabled = $this->ssa_enabled_remote_wallet();
        $this->playerDetails = array();
        $this->playerId = null;
        $this->checkPreviousMonth = false;
        $this->remote_wallet_status = null;
        $this->automation_tester_game_account = array();
    }

    private function validateHeaders()
    {
        $headers = $this->requestHeaders;
        $body = $this->requestBody;
        $signKey = $this->api->mchkey;
        $merchantId = $this->api->mchid;

        // Validate required header keys
        $this->validateRequiredHeaders($headers);

        // Validate merchant ID
        $this->validateMerchantId($merchantId, $headers["X-Atgame-Mchid"]);

        // Validate the signature
        $this->validateSignature($body, $headers, $signKey);
    }

    private function validateRequiredHeaders($headers)
    {
        if (empty($headers["X-Atgame-Timestamp"]) || empty($headers["X-Atgame-Sign"]) || empty($headers["X-Atgame-Mchid"])) {
            throw new Exception(__LINE__ . ": Missing required headers.", self::ERROR_CODE_OTHER);
        }
    }

    private function validateMerchantId($merchantId, $headerMerchantId)
    {
        if ($merchantId !== $headerMerchantId) {
            throw new Exception(__LINE__ . ": Invalid merchant.", self::ERROR_CODE_OTHER);
        }
    }

    private function validateSignature($body, $headers, $signKey)
    {
        // Generate hash
        $signString = $body . $headers["X-Atgame-Timestamp"] . $signKey;
        $calculatedHash = strtoupper(md5($signString));

        // Validate signature
        if ($calculatedHash !== $headers["X-Atgame-Sign"]) {
            throw new Exception(__LINE__ . ": Invalid signature.", self::ERROR_CODE_OTHER);
        }
    }

    private function validateMethod($method, $requestArray) {
        if (empty($method) || !method_exists($this, $method) || !in_array($method, self::ALLOWED_METHOD) || empty($this->requestBody)) {
            throw new Exception(__LINE__ . ": Invalid Params.", self::ERROR_CODE_OTHER);
        }
    }

    private function checkGameStatus() {
        if ($this->external_system->isGameApiMaintenance($this->gamePlatformId)) {
            throw new Exception(__LINE__ . ": The game is on maintenance.", self::ERROR_CODE_OTHER);
        }

        if (!$this->external_system->isGameApiActive($this->gamePlatformId)) {
            throw new Exception(__LINE__ . ": The game is disabled.", self::ERROR_CODE_OTHER);
        }

        if (!$this->api->validateWhiteIP()) {
            throw new Exception(__LINE__ . ": Invalid IP.", self::ERROR_CODE_OTHER);
        }

        if (date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
            $this->checkPreviousMonth = true;
        }

        $this->automation_tester_game_account = $this->api->getSystemInfo('automation_tester_game_account', array());
    }

    private function handleException(Exception $e, &$errorMessage, &$errorCode) {
        $this->utils->debug_log('==> jgameworks_seamless_service_api encounter error at line and message', $e->getMessage());
        $messageArray = explode(":", $e->getMessage());
        $errorMessage = isset($messageArray[1]) ? $messageArray[1] : "";
        $errorCode = $e->getCode();
    }

    private function processRequestData($params) {
        $dataToInsert = array_merge(array(
            // Default fields
            "game_platform_id" => $this->gamePlatformId,
            "player_id" => $this->playerId,
            "trans_type" => isset($params['trans_type']) ? $params['trans_type'] : null,

            // Params data
            "u_name" => isset($params['uname']) ? $params['uname'] : null,
            "token" => isset($params['token']) ? $params['token'] : null,
            "bet_id" => isset($params['betid']) ? $params['betid'] : null,
            "session_id" => isset($params['sessionid']) ? $params['sessionid'] : null,
            "game_id" => isset($params['gameid']) ? $params['gameid'] : null,
            "bet" => isset($params['bet']) ? $params['bet'] : null,
            "award" => isset($params['award']) ? $params['award'] : null,
            "is_end_round" => isset($params['is_end_round']) ? $params['is_end_round'] : null,
            "c_time" => isset($params['ctime']) ? $params['ctime'] : null,
            "betting_time" => isset($params['ctime']) ? date('Y-m-d H:i:s', $params['ctime']) : null,

            // SBE default
            "result_amount" => isset($params['result_amount']) ? $params['result_amount'] : null,
            "amount_adjustment" => isset($params['amount_adjustment']) ? $params['amount_adjustment'] : null,
            "json_request" => $this->requestBody,
            "sbe_status" => isset($params['sbe_status']) ? $params['sbe_status'] : null,
            "before_balance" => isset($params['before_balance']) ? $params['before_balance'] : null,
            "after_balance" => isset($params['after_balance']) ? $params['after_balance'] : null,
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow() * 1000),
            "request_id" => $this->utils->getRequestId(),
            "external_uniqueid" => isset($params['betid']) ? $params['betid'] : null,
            "md5_sum" => $this->original_seamless_wallet_transactions->generateMD5SumOneRow($params, array('sessionid', 'betid', 'is_end_round', 'ctime'), array('bet', 'award')),

            // Remote wallet
            "remote_wallet_status" => $this->remote_wallet_status,
            "is_failed" => isset($params['is_failed']) ? $params['is_failed'] : null,
            "seamless_service_unique_id" => isset($params['seamless_service_unique_id']) ? $params['seamless_service_unique_id'] : null,
        ), isset($params['external_uniqueid']) ? array('external_uniqueid' => $params['external_uniqueid']) : array());

        return $this->original_seamless_wallet_transactions->insertTransactionData($this->api->getTransactionsTable(), $dataToInsert);
    }

    private function saveFailedTransactions($data) {
        $betAmount = isset($data['bet']) ? $this->api->gameAmountToDBTruncateNumber($data['bet']) : null;
        $payoutAmount = isset($data['award']) ? $this->api->gameAmountToDBTruncateNumber($data['award']) : null;
        $resultAmount = $payoutAmount - $betAmount;

        $this->load->model(array('failed_seamless_transactions'));
        $failedTrans = array(
            'transaction_id' => isset($data['betid']) ? $data['betid'] : null,
            'round_id' => isset($data['sessionid']) ? $data['sessionid'] : null,
            'external_game_id' => isset($data['gameid']) ? $data['gameid'] : null,
            'player_id' => $this->playerId,
            'game_username' => isset($this->playerDetails['game_username']) ? $this->playerDetails['game_username'] : null,
            'amount' => $resultAmount,
            'balance_adjustment_type' => ($resultAmount > 0) ? 'increase' : 'decrease',
            'action' => $this->method,
            'game_platform_id' => $this->gamePlatformId,
            'transaction_raw_data' => $this->requestBody,
            'remote_raw_data' => $this->wallet_model->getRemoteApiParams(),
            'external_uniqueid' => isset($data['betid']) ? $data['betid'] : null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => isset($data['ctime']) ? date('Y-m-d H:i:s', $data['ctime']) : null,
            'created_at' => $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'updated_at' => $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'request_id' => $this->utils->getRequestId(),
            'headers' => json_encode($this->requestHeaders),
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
        );

        $monthStr = date('Ym');
        $failedTransSave = $this->failed_seamless_transactions->insertTransaction($failedTrans, "failed_remote_common_seamless_transactions_{$monthStr}");
        $this->utils->debug_log("WMSS: ({$this->method})", "failedTransSave", $failedTransSave, "failedTrans", $failedTrans);
    }

    #custom function to rollback specific bet incase we already process and we response too long and GP already mark it as failed
    #for json params, just copy json body request for castBet
    private function cashRollback($request){
        if (empty($this->playerDetails)) {
            throw new Exception(__LINE__ . ":Player not found.", self::ERROR_CODE_PLAYER_NOT_FOUND);
        }

        $betId = isset($request['betid']) ? $request['betid'] : null;
        if (empty($betId)) {
            throw new Exception(__LINE__ . ":Empty betid.", self::ERROR_CODE_OTHER);
        }

        $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(
            $this->api->getTransactionsTable(),
            ['player_id' => $this->playerId, 'bet_id' => $betId],
            ['id', 'bet_id', 'after_balance', 'u_name', 'result_amount', 'sbe_status', 'created_at']
        );

        if (empty($betDetails)) { #if empty current month, mandatory check previous month
            $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(
                $this->api->getTransactionsPreviousTable(),
                ['player_id' => $this->playerId, 'bet_id' => $betId],
                ['id', 'bet_id', 'after_balance', 'u_name', 'result_amount', 'sbe_status', 'created_at']
            );
        }

        if(empty($betDetails)){
            throw new Exception(__LINE__ . ":Bet id not found.", self::ERROR_CODE_OTHER);
        }

        if($betDetails['sbe_status'] == GAME_LOGS::STATUS_REFUND){
            throw new Exception(__LINE__ . ":Bet id already refunded..", self::ERROR_CODE_OTHER);
        }

        $response = [];
        $errorCode = self::ERROR_CODE_OTHER;
        $errorMessage = "Internal System Error";

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function () use ($betDetails, &$response, &$errorCode, &$errorMessage) {
            $resultAmount = isset($betDetails['result_amount']) ? $betDetails['result_amount'] : null;
            $beforeBalance = $this->getPlayerBalance($this->playerId);
            if ($beforeBalance === false) {
                $errorMessage = "Unable to fetch balance.";
                return false;
            }

            if(is_null($resultAmount)){
                $errorMessage = "Null result amount.";
                return false;
            }

            $resultAmount = -($resultAmount);#negate
            if($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                if ($this->utils->compareResultFloat(abs($resultAmount), '>', $beforeBalance)) {
                    $errorCode = self::ERROR_CODE_OTHER;
                    $errorMessage = "Insufficient balance for rollback win amount.(DEDUCT MAIN WALLET)";
                    return false;
                }
            } else {

            }

            $afterBalance = $this->updateWalletBalance($resultAmount, $beforeBalance, $errorMessage);
            if ($afterBalance === false) {
                return false;
            }

            $request['uname'] = $betDetails['u_name'];
            $request['betid'] = $betDetails['bet_id'];
            $request['bet'] = $request['award'] = $request['sessionid'] = null;
            $request['is_end_round'] = true;
            $request['ctime'] = $this->utils->getTimestampNow();
            $request['before_balance'] = $beforeBalance;
            $request['after_balance'] = $afterBalance;
            $request['sbe_status'] = GAME_LOGS::STATUS_REFUND;
            $request['trans_type'] = $resultAmount > 0 ? 'rollbacklose' : 'rollbackwin';
            $request['result_amount'] = $resultAmount;
            $request['amount_adjustment'] = abs($resultAmount);
            $request['external_uniqueid'] = "{$request['trans_type']}-{$betDetails['bet_id']}";
            

            $transId = $this->processRequestData($request);
            if ($transId) {
                $dateString = $betDetails['created_at'];
                $date = new DateTime($dateString);
                $yearMonth = $date->format("Ym");
                $tableForUpdate = $this->api->initGameTransactionsMonthlyTableByDate($yearMonth);
                $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($tableForUpdate, ['id' => $betDetails['id']], ['sbe_status' => $request['sbe_status']]);
                $response = [
                    "data" => [
                        "uname" => $request['uname'],
                        "betid" => $request['betid'],
                        "balance" => (float)$afterBalance,
                    ]
                ];
                $errorCode = self::ERROR_CODE_SUCCESS;
                return true;
            }

            $errorMessage = "Transaction processing failed.";
            return false;
        });

        if (!$success) {
            $this->saveFailedTransactions($request);
        }

        return [$errorCode, $response, $errorMessage];
    }

    private function cashBet($request)
    {
        if (empty($this->playerDetails)) {
            throw new Exception(__LINE__ . ":Player not found.", self::ERROR_CODE_PLAYER_NOT_FOUND);
        }

        $betId = isset($request['betid']) ? $request['betid'] : null;
        if (empty($betId)) {
            throw new Exception(__LINE__ . ":Empty betid.", self::ERROR_CODE_OTHER);
        }

        $betDetails = $this->fetchBetDetails($betId);
        if ($betDetails) {
            return [
                self::ERROR_CODE_SUCCESS,
                [
                    "data" => [
                        "uname" => $betDetails['u_name'],
                        "betid" => $betDetails['bet_id'],
                        "balance" => (float)$betDetails['after_balance'],
                    ]
                ],
                ""
            ];
        }

        $response = [];
        $errorCode = self::ERROR_CODE_OTHER;
        $errorMessage = "Internal System Error";

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function () use ($request, &$response, &$errorCode, &$errorMessage) {
            $bet = isset($request['bet']) ? $request['bet'] : null;
            $award = isset($request['award']) ? $request['award'] : null;
            $betAmount = $this->sanitizeAmount($bet);
            $payoutAmount = $this->sanitizeAmount($award);

            $beforeBalance = $this->getPlayerBalance($this->playerId);
            if ($beforeBalance === false) {
                $errorMessage = "Unable to fetch balance.";
                return false;
            }

            if ($this->utils->compareResultFloat($betAmount, '>', $beforeBalance)) {
                $errorCode = self::ERROR_CODE_FAILED_BET;
                $errorMessage = "Insufficient balance.";
                return false;
            }

            $resultAmount = $payoutAmount - $betAmount;
            $afterBalance = $this->updateWalletBalance($resultAmount, $beforeBalance, $errorMessage);

            if ($afterBalance === false) {
                return false;
            }

            $request['before_balance'] = $beforeBalance;
            $request['after_balance'] = $afterBalance;
            $request['sbe_status'] = GAME_LOGS::STATUS_SETTLED;
            $request['trans_type'] = $resultAmount > 0 ? 'credit' : 'debit';
            $request['result_amount'] = $resultAmount;
            $request['amount_adjustment'] = abs($resultAmount);

            $transId = $this->processRequestData($request);
            if ($transId) {
                $response = [
                    "data" => [
                        "uname" => $request['uname'],
                        "betid" => $request['betid'],
                        "balance" => (float)$afterBalance,
                    ]
                ];
                $errorCode = self::ERROR_CODE_SUCCESS;
                return true;
            }

            $errorMessage = "Transaction processing failed.";
            return false;
        });

        if (!$success) {
            $this->saveFailedTransactions($request);
        }

        return [$errorCode, $response, $errorMessage];
    }

    private function fetchBetDetails($betId)
    {
        $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(
            $this->api->getTransactionsTable(),
            ['player_id' => $this->playerId, 'bet_id' => $betId],
            ['id', 'bet_id', 'after_balance', 'u_name']
        );

        if (empty($betDetails) && $this->checkPreviousMonth) {
            $betDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(
                $this->api->getTransactionsPreviousTable(),
                ['player_id' => $this->playerId, 'bet_id' => $betId],
                ['id', 'bet_id', 'after_balance', 'u_name']
            );
        }

        return $betDetails;
    }

    private function sanitizeAmount($amount)
    {
        return $amount !== null ? $this->api->gameAmountToDBTruncateNumber($amount) : null;
    }

    private function updateWalletBalance($resultAmount, $beforeBalance, &$errorMessage)
    {
        $afterBalance = null;

        if ($this->utils->compareResultFloat($resultAmount, '>', 0)) {
            $incAmount = abs($resultAmount);
            if (!$this->wallet_model->incSubWallet($this->playerId, $this->gamePlatformId, $incAmount, $afterBalance)) {
                $errorMessage = "Failed to increase balance by {$incAmount}.";
                return false;
            }
        } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
            $decAmount = abs($resultAmount);
            if (!$this->wallet_model->decSubWallet($this->playerId, $this->gamePlatformId, $decAmount, $afterBalance)) {
                $errorMessage = "Failed to decrease balance by {$decAmount}.";
                return false;
            }
        } else {
            $afterBalance = $beforeBalance; // No change for zero result amount
        }

        return $afterBalance;
    }

    private function cashGet($request)
    {
        $balance = $this->getPlayerBalance($this->playerId, true);

        return [
            self::ERROR_CODE_SUCCESS,
            [
                "data" => [
                    "uname" => $this->playerDetails['game_username'],
                    "balance" => $balance,
                ]
            ],
            ""
        ];
    }

    private function verifySession($request)
    {
        $balance = $this->getPlayerBalance($this->playerId, true);

        return [
            self::ERROR_CODE_SUCCESS,
            [
                "data" => [
                    "uname" => $this->playerDetails['game_username'],
                    "balance" => $balance,
                ]
            ],
            ""
        ];
    }

    private function getToken($request)
    {
        $gameUsername = isset($request['uname']) ? $request['uname'] : null;

        if (empty($gameUsername)) {
            throw new Exception(__LINE__ . ":Empty uname.", self::ERROR_CODE_OTHER);
        }

        $automationTesterAccounts = $this->api->getSystemInfo('automation_tester_game_account', []);
        if (!in_array($gameUsername, $automationTesterAccounts)) {
            throw new Exception(__LINE__ . ":Uname not allowed.", self::ERROR_CODE_OTHER);
        }

        $tokenTimeout = 3600;
        $token = $this->ssa_get_player_common_token_by_player_game_username(
            $gameUsername,
            $this->gamePlatformId,
            $tokenTimeout
        );

        return [
            self::ERROR_CODE_SUCCESS,
            ["data" => ["token" => $token]],
            ""
        ];
    }

    private function isValidAmount($amount){
        return is_numeric(trim($amount)) && $amount >= 0;
    }

    private function validateParams($request)
    {
        foreach (['bet', 'award'] as $key) {
            $value = $this->getNestedValue($request, $key);
            if (!is_null($value) && !$this->isValidAmount($value)) {
                throw new Exception(__LINE__ . ":Invalid {$key}.", self::ERROR_CODE_OTHER);
            }
        }
    }

    private function getNestedValue($array, $key)
    {
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (!isset($array[$k])) {
                return null;
            }
            $array = $array[$k];
        }
        return $array;
    }

    private function authenticate($request)
    {
        if (empty($request)) {
            throw new Exception(__LINE__ . ":Empty request.", self::ERROR_CODE_OTHER);
        }

        $gameUsername = isset($request['uname']) ? $request['uname'] : null;
        if (empty($gameUsername)) {
            throw new Exception(__LINE__ . ":Empty uname.", self::ERROR_CODE_OTHER);
        }

        $playerDetails = $this->fetchPlayerDetails($gameUsername);
        $this->validatePlayerToken($request, $gameUsername);

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if (empty($playerId)) {
            throw new Exception(__LINE__ . ":Player not found.", self::ERROR_CODE_PLAYER_NOT_FOUND);
        }

        if ($this->isPlayerBlocked($gameUsername, $playerId)) {
            throw new Exception(__LINE__ . ":Player is blocked.", self::ERROR_CODE_OTHER);
        }

        $this->playerDetails = $playerDetails;
        $this->playerId = $playerId;

        return $playerId;
    }

    private function fetchPlayerDetails($gameUsername)
    {
        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername(
            $gameUsername,
            $this->gamePlatformId
        );

        if (empty($playerDetails)) {
            throw new Exception(__LINE__ . ":Player not found.", self::ERROR_CODE_PLAYER_NOT_FOUND);
        }

        return $playerDetails;
    }

    private function validatePlayerToken($request, $gameUsername)
    {
        if (in_array($this->method, self::FUNCTION_IGNORE_TOKEN_VALIDATION) || 
            in_array($gameUsername, $this->automation_tester_game_account)) {
            return;
        }

        $token = isset($request['token']) ? $request['token'] : null;
        if (empty($token)) {
            throw new Exception(__LINE__ . ":Empty token.", self::ERROR_CODE_OTHER);
        }

        $playerTokenDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsernameAndToken(
            $gameUsername,
            $token,
            $this->gamePlatformId
        );

        if (empty($playerTokenDetails)) {
            throw new Exception(__LINE__ . ":Invalid token.", self::ERROR_CODE_PLAYER_NOT_FOUND);
        }
    }

    private function isPlayerBlocked($gameUsername, $playerId)
    {
        return $this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($playerId);
    }

    private function setResponse($errorCode, $response = [], $errorMessage = "")
    {
        $defaultResponse = [
            "code" => $errorCode,
            "msg" => $errorMessage,
            "data" => isset($response['data']) ? $response['data'] : null
        ];

        if ($errorCode == self::ERROR_CODE_SUCCESS) {
            $defaultResponse['msg'] = "";
        }

        return $this->setOutput($errorCode, $defaultResponse);
    }

    private function setOutput($errorCode, $response = [])
    {
        $statusCode = $errorCode == self::ERROR_CODE_SUCCESS ? 200 : 400;
        $extraFields = $this->playerId ? ['player_id' => $this->playerId] : [];

        $this->response_result->saveResponseResult(
            $this->gamePlatformId,
            $statusCode === 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR,
            $this->method,
            json_encode($this->requestBody),
            $response,
            $statusCode,
            null,
            json_encode($this->requestHeaders),
            $extraFields,
            false,
            null,
            intval($this->utils->getExecutionTimeToNow() * 1000)
        );

        return $this->returnJsonResult((object)$response, true, "*", false, false, $statusCode);
    }

    private function getPlayerBalance($playerId = null, $useReadonly = true)
    {
        $playerId = isset($playerId) ? $playerId : $this->playerId;
        return $playerId 
            ? $this->player_model->getPlayerSubWalletBalance($playerId, $this->gamePlatformId, $useReadonly) 
            : false;
    }
}

