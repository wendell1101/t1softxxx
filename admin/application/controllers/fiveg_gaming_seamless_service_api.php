<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/*
    ENDPOINT: 
    Athenticate : /5ggaming/api/authenticate
    Get Balance : /5ggaming/api/getbalance
    Bet : /5ggaming/api/bet

    EXTRA ENDPOINT:
    Get token for testing : /5ggaming/api/gettoken
 */

class fiveg_gaming_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_INVALID_TOKEN = 1;
    const ERROR_CODE_INVALID_TXN_ID = 2;
    const ERROR_CODE_INSUFFICIENT_BALANCE = 3;
    const ERROR_CODE_INVALID_GAME_USERNAME = 900;
    const ERROR_CODE_TRANS_ALREADY_HAVE_RESULT = 901;
    const ERROR_CODE_TRANS_ALREADY_HAVE_REFUND = 902;
    const ERROR_CODE_INVALID_CURRENCY = 903;
    const ERROR_CODE_INVALID_IP = 904;
    const ERROR_CODE_EMPTY_PARAMS = 905;
    const ERROR_CODE_REQUEST_NOT_ALLOWED = 906;
    const ERROR_CODE_INVALID_AMOUNT = 907;
    const ERROR_CODE_INVALID_BONUS_ID = 908;
    const ERROR_CODE_TRANS_ALREADY_HAVE_BONUS_REWARD = 909;
    const ERROR_CODE_SYSTEM_ERROR = 999;
    const ALLOWED_METHOD = ['authenticate', 'getbalance', 'gettoken', 'bet', 'result','refund', 'bonusaward'];
    const FUNCTION_IGNORE_TOKEN_VALIDATION = ['gettoken'];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    private function getGamePlatformId(){
        return FIVEG_GAMING_SEAMLESS_API;
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

    public function api($segment1 = null)
    {
        $this->method = $method = lcfirst($segment1);

        // Initialize request data
        $this->initializeRequestData();

        // Initialize variables
        $response = array();
        $errorMessage = "";
        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $params = $this->input->get();

        try {
            // Handle token validation and manipulation
            $token = isset($params['access_token']) ? $params['access_token'] : null;
            if (!empty($token) && strpos($token, "-") !== false) {
                $tokenParts = explode("-", $token);
                $currency = isset($tokenParts[0]) ? $tokenParts[0] : "";

                if (!$this->getCurrencyAndValidateDB($currency)) {
                    throw new Exception(__LINE__ . ": Invalid Currency.", self::ERROR_CODE_INVALID_CURRENCY);
                }

                // Remove currency prefix from token
                $params['access_token'] = substr($token, strlen($currency) + 1);
            }

            // Load API object
            $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
            if (!$this->api) {
                throw new Exception(__LINE__ . ": Invalid API.", self::ERROR_CODE_INVALID_IP);
            }

            if (empty($method) || !method_exists($this, $method) || !in_array($method, self::ALLOWED_METHOD) || empty($params)) {
                throw new Exception(__LINE__ . ": Request not allowed..", self::ERROR_CODE_REQUEST_NOT_ALLOWED);
            }
            
            $this->checkGameStatus();

            if (empty($params)) {
                throw new Exception(__LINE__ . ":Empty params.", self::ERROR_CODE_EMPTY_PARAMS);
            }

            $playerDetails = [];
            if (in_array($this->method, self::FUNCTION_IGNORE_TOKEN_VALIDATION)){
                $gameUsername = isset($params['username']) ? $params['username'] : null;
                if(in_array($gameUsername, $this->automation_tester_game_account)){
                    $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername(
                        $gameUsername,
                        $this->gamePlatformId
                    );

                    if (empty($playerDetails)) {
                        throw new Exception(__LINE__ . ":Invalid Gameusername.", self::ERROR_CODE_INVALID_GAME_USERNAME);
                    }
                }
            } else {
                $accessToken = isset($params['access_token']) ? $params['access_token'] : null;
                if (empty($accessToken)) {
                    throw new Exception(__LINE__ . ":Invalid Token.", self::ERROR_CODE_INVALID_TOKEN);
                }

                $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByToken(
                    $accessToken,
                    $this->gamePlatformId
                );

                if (empty($playerDetails)) {
                    throw new Exception(__LINE__ . ":Invalid Token.", self::ERROR_CODE_INVALID_TOKEN);
                }
            }
            

            $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
            if (empty($playerId)) {
                throw new Exception(__LINE__ . ":Invalid Token.", self::ERROR_CODE_INVALID_TOKEN);
            }

            if ($this->player_model->isBlocked($playerId)) {
                throw new Exception(__LINE__ . ":Player is blocked.", self::ERROR_CODE_INVALID_TOKEN);
            }

            $this->playerDetails = $playerDetails;
            $this->playerId = $playerId;

            $this->validateParams($params);

            list($errorCode, $response, $errorMessage) = call_user_func(array($this, $method), $params);

        } catch (Exception $e) {
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
        $this->query_string = '?' . $_SERVER['QUERY_STRING'];
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

    private function sanitizeAmount($amount)
    {
        return $amount !== null ? $this->api->gameAmountToDBTruncateNumber($amount) : null;
    }


    private function gettoken($request)
    {
        $gameUsername = isset($request['username']) ? $request['username'] : null;

        if (empty($gameUsername)) {
            throw new Exception(__LINE__ . ":Empty username.", self::ERROR_CODE_OTHER);
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
            ["data" => ["access_token" => $token]],
            ""
        ];
    }

    private function isValidAmount($amount){
        return is_numeric(trim($amount)) && $amount >= 0;
    }

    private function validateParams($request)
    {
        foreach (['total_bet', 'total_win', 'bonus_reward'] as $key) {
            $value = $this->getNestedValue($request, $key);
            if (!is_null($value) && !$this->isValidAmount($value)) {
                throw new Exception(__LINE__ . ":Invalid {$key}.", self::ERROR_CODE_INVALID_AMOUNT);
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

    private function searchTransactionByType(array $transactions, $type)
    {
        foreach ($transactions as $key => $transaction) {
            if (isset($transaction['trans_type']) && $transaction['trans_type'] === $type) {
                return $key;
            }
        }
        return false;
    }

    private function getTransactionDetails($txnId, array $columns)
    {
        $criteria = array('txn_id' => $txnId, 'player_id' => $this->playerId);
        $details = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom(
            $this->api->getTransactionsTable(),
            $criteria,
            $columns
        );
        if ($this->checkPreviousMonth) {
            $prevDetails = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom(
                $this->api->getTransactionsPreviousTable(),
                $criteria,
                $columns
            );
            $details = array_values(array_merge($prevDetails, $details));
        }
        return $details;
    }

    private function applyRemoteWalletSettings(
        &$params,
        $actionType,
        $isEndRound,
        $roundId,
        $gameId,
        $relatedUniqueId = null,
        $relatedActionType = null
    ) {
        if ($this->remoteWalletEnabled) {
            $uniqueId = $this->api->getPlatformCode() . '-' . $params['external_uniqueid'];
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueId);
            $params['seamless_service_unique_id'] = $uniqueId;

            if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                $this->wallet_model->setGameProviderRoundId($roundId);
            }
            if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                $this->wallet_model->setGameProviderIsEndRound($isEndRound);
            }
            if (method_exists($this->wallet_model, 'setExternalGameId')) {
                $this->wallet_model->setExternalGameId($gameId);
            }
            if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                $this->wallet_model->setGameProviderActionType($actionType);
            }
            if ($relatedUniqueId && method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                $this->wallet_model->setRelatedUniqueidOfSeamlessService($relatedUniqueId);
            }
            if ($relatedActionType && method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                $this->wallet_model->setRelatedActionOfSeamlessService($relatedActionType);
            }
        }
    }

    private function adjustWalletBalance($playerId, $operation, $amount, &$afterBalance)
    {
        if ($this->utils->compareResultFloat($amount, '>', 0)) {
            if ($operation == 'inc') {
                return $this->wallet_model->incSubWallet(
                    $playerId,
                    $this->api->getPlatformCode(),
                    $amount,
                    $afterBalance
                );
            } else {
                return $this->wallet_model->decSubWallet(
                    $playerId,
                    $this->api->getPlatformCode(),
                    $amount,
                    $afterBalance
                );
            }
        } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
            // Even if zero, call remote wallet if enabled.
            if ($this->remoteWalletEnabled) {
                if ($operation == 'inc') {
                    return $this->wallet_model->incSubWallet(
                        $playerId,
                        $this->api->getPlatformCode(),
                        $amount,
                        $afterBalance
                    );
                } else {
                    return $this->wallet_model->decSubWallet(
                        $playerId,
                        $this->api->getPlatformCode(),
                        $amount,
                        $afterBalance
                    );
                }
            }
            return true;
        }
        return false;
    }

    private function bonusaward($params)
    {
        if (empty($params['txn_id'])) {
            throw new Exception(__LINE__ . ": Empty txn_id.", self::ERROR_CODE_INVALID_TXN_ID);
        }

        if (empty($params['bonus_id'])) {
            throw new Exception(__LINE__ . ": Empty bonus_id.", self::ERROR_CODE_INVALID_BONUS_ID);
        }
        $txnId   = $params['txn_id'];
        $bonusId = $params['bonus_id'];
        $params['ts'] = $this->utils->getTimestampNow();

        $columns = array('id', 'txn_id', 'trans_type', 'after_balance', 'external_uniqueid', 'bet_amount', 'bonus_id', 'payout_amount', 'ts');
        $transactions = $this->getTransactionDetails($txnId, $columns);

        $betKey = $this->searchTransactionByType($transactions, 'bet');
        if ($betKey === false) {
            throw new Exception(__LINE__ . ": Bet not found for this transaction.", self::ERROR_CODE_INVALID_TXN_ID);
        }
        $bet = $transactions[$betKey];
        if (isset($bet['bet_amount'])) {
            $params['bet_amount'] = $bet['bet_amount'];
        }

        if (isset($bet['external_uniqueid'])) {
            $params['bet_external_unique_id'] = "game-{$this->api->getPlatformCode()}-{$bet['external_uniqueid']}";
        }

        if (isset($bet['ts'])) {
            $params['ts'] = $bet['ts'];//override default ts
        }

        $resultKey = $this->searchTransactionByType($transactions, 'result');
        if ($resultKey !== false) {
            $result = $transactions[$resultKey];
            if (isset($result['payout_amount'])) {
                $params['payout_amount'] = $result['payout_amount'];
            }

            if (isset($result['ts'])) {
                $params['ts'] = $result['ts'];//override bet ts
            }
        }

        $bonusKey = $this->searchTransactionByType($transactions, 'bonusreward');
        if ($bonusKey !== false) {
            $bonusDetails = $transactions[$bonusKey];
            if ($bonusDetails['bonus_id'] == $bonusId) {
                return array(
                    self::ERROR_CODE_SUCCESS,
                    array("balance" => (float)$this->api->dBtoGameAmount($bonusDetails['after_balance'])),
                    ""
                );
            } else {
                throw new Exception(__LINE__ . ": Transaction already awarded jackpot.", self::ERROR_CODE_TRANS_ALREADY_HAVE_BONUS_REWARD);
            }
        }

        $refundKey = $this->searchTransactionByType($transactions, 'refund');
        if ($refundKey !== false) {
            throw new Exception(__LINE__ . ": Transaction already refunded.", self::ERROR_CODE_TRANS_ALREADY_HAVE_REFUND);
        }

        $response    = array();
        $errorCode   = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function () use (&$params, &$response, &$errorCode, &$errorMessage) {
            $jackpotAmount = $this->sanitizeAmount($params['bonus_reward']);

            // Set default parameters for bonusaward.
            $params['balance_adjustment_type'] = 'increase';
            $params['sbe_status']              = GAME_LOGS::STATUS_SETTLED;
            $params['trans_type']              = "bonusreward";
            $params['amount_adjustment']       = $params['jackpot_amount'] = abs($jackpotAmount);
            $params['external_uniqueid']       = $params['bonus_id'];

            $beforeBalance = $this->getPlayerBalance($this->playerId);
            $afterBalance = false;
            if ($beforeBalance === false) {
                $errorMessage = "Unable to fetch balance.";
                return false;
            }

            if ($this->remoteWalletEnabled) {
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT,
                    true,
                    $params['txn_id'],
                    $params['game_id'],
                    $params['bet_external_unique_id'],
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET
                );
            }

            if ($this->utils->compareResultFloat($jackpotAmount, '>', 0)) {
                $success = $this->adjustWalletBalance($this->playerId, 'inc', $jackpotAmount, $afterBalance);
                if (!$success) {
                    $errorMessage = "Increase balance encountered error. Amount: {$jackpotAmount}.";
                }
            } elseif ($this->utils->compareResultFloat($jackpotAmount, '=', 0)) {
                $success = true;
                if ($this->remoteWalletEnabled) {
                    $success = $this->adjustWalletBalance($this->playerId, 'inc', $jackpotAmount, $afterBalance);
                    if (!$success) {
                        $errorMessage = "Increase balance encountered error. Amount: 0.";
                    }
                }
            } else {
                $errorMessage = "Total payout < 0.";
                $success = false;
            }

            if ($afterBalance === false) {
                $afterBalance = $this->getPlayerBalance($this->playerId);
                if ($afterBalance === false) {
                    $errorMessage = "Unable to fetch after balance.";
                    return false;
                }
            }

            $params['before_balance'] = $beforeBalance;
            $params['after_balance']  = $afterBalance;
            $transId = $this->processRequestData($params);
            if ($transId) {
                $response  = array("balance" => (float)$this->api->dBtoGameAmount($afterBalance));
                $errorCode = self::ERROR_CODE_SUCCESS;
                return true;
            }

            $errorMessage = "Transaction processing failed.";
            return false;
        });

        if (!$success) {
            $this->saveFailedTransactions($params);
        }

        return array($errorCode, $response, $errorMessage);
    }

    private function refund($params)
    {
        if (empty($params['txn_id'])) {
            throw new Exception(__LINE__ . ": Empty txn_id.", self::ERROR_CODE_INVALID_TXN_ID);
        }
        $txnId = $params['txn_id'];
        $columns = array('id', 'access_token', 'txn_id', 'trans_type', 'after_balance', 'external_uniqueid',
                         'bet_amount', 'game_id', 'bonus_id', 'subgame_id', 'ts', 'total_bet');
        $transactions = $this->getTransactionDetails($txnId, $columns);

        $betKey = $this->searchTransactionByType($transactions, 'bet');
        if ($betKey === false) {
            throw new Exception(__LINE__ . ": Bet not found for this transaction.", self::ERROR_CODE_INVALID_TXN_ID);
        }
        $bet = $transactions[$betKey];
        if (isset($bet['external_uniqueid'])) {
            $bet['bet_external_unique_id'] = "game-{$this->api->getPlatformCode()}-{$bet['external_uniqueid']}";
        }

        $refundKey = $this->searchTransactionByType($transactions, 'refund');
        if ($refundKey !== false) {
            $refundDetails = $transactions[$refundKey];
            if ($refundDetails['txn_id'] == $txnId) {
                return array(
                    self::ERROR_CODE_SUCCESS,
                    array("balance" => (float)$this->api->dBtoGameAmount($refundDetails['after_balance'])),
                    ""
                );
            }
        }

        $resultKey = $this->searchTransactionByType($transactions, 'result');
        if ($resultKey !== false) {
            throw new Exception(__LINE__ . ": Transaction already have result.", self::ERROR_CODE_TRANS_ALREADY_HAVE_RESULT);
        }

        $response    = array();
        $errorCode   = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";

        // Use bet details as basis for refund.
        $params = $bet;
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function () use (&$params, &$response, &$errorCode, &$errorMessage) {
            $refundAmount = $this->sanitizeAmount($params['total_bet']);
            $params['balance_adjustment_type'] = 'increase';
            $params['sbe_status']              = GAME_LOGS::STATUS_REFUND;
            $params['trans_type']              = "refund";
            $params['amount_adjustment']       = $params['payout_amount'] = $params['bet_amount']= abs($refundAmount);
            $params['external_uniqueid']       = "refund-{$params['txn_id']}";

            $beforeBalance = $this->getPlayerBalance($this->playerId);
            $afterBalance = false;
            if ($beforeBalance === false) {
                $errorMessage = "Unable to fetch balance.";
                return false;
            }
            if ($this->remoteWalletEnabled) {
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND,
                    true,
                    $params['txn_id'],
                    $params['game_id'],
                    $params['bet_external_unique_id'],
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET
                );
            }
            if ($this->utils->compareResultFloat($refundAmount, '>', 0)) {
                $success = $this->adjustWalletBalance($this->playerId, 'inc', $refundAmount, $afterBalance);
                if (!$success) {
                    $errorMessage = "Increase balance encountered error. Amount: {$refundAmount}.";
                }
            } elseif ($this->utils->compareResultFloat($refundAmount, '=', 0)) {
                $success = true;
                if ($this->remoteWalletEnabled) {
                    $success = $this->adjustWalletBalance($this->playerId, 'inc', $refundAmount, $afterBalance);
                    if (!$success) {
                        $errorMessage = "Increase balance encountered error. Amount: 0.";
                    }
                }
            } else {
                $errorMessage = "Total refund < 0.";
                $success = false;
            }

            if ($afterBalance === false) {
                $afterBalance = $this->getPlayerBalance($this->playerId);
                if ($afterBalance === false) {
                    $errorMessage = "Unable to fetch after balance.";
                    return false;
                }
            }
            $params['before_balance'] = $beforeBalance;
            $params['after_balance']  = $afterBalance;
            $transId = $this->processRequestData($params);
            if ($transId) {
                $response  = array("balance" => (float)$this->api->dBtoGameAmount($afterBalance));
                $errorCode = self::ERROR_CODE_SUCCESS;
                return true;
            }
            $errorMessage = "Transaction processing failed.";
            return false;
        });
        if (!$success) {
            $this->saveFailedTransactions($params);
        }
        return array($errorCode, $response, $errorMessage);
    }

    private function result($params)
    {
        if (empty($params['txn_id'])) {
            throw new Exception(__LINE__ . ": Empty txn_id.", self::ERROR_CODE_INVALID_TXN_ID);
        }
        $txnId   = $params['txn_id'];

        $columns = array('id', 'txn_id', 'trans_type', 'after_balance', 'external_uniqueid', 'bet_amount', 'bonus_id');
        $transactions = $this->getTransactionDetails($txnId, $columns);

        // Find the bet transaction and set bet details.
        $betKey = $this->searchTransactionByType($transactions, 'bet');
        if ($betKey === false) {
            throw new Exception(__LINE__ . ": Bet not found for this transaction.", self::ERROR_CODE_INVALID_TXN_ID);
        }

        $bet = $transactions[$betKey];
        if (isset($bet['bet_amount'])) {
            $params['bet_amount'] = $bet['bet_amount'];
        }

        if (isset($bet['external_uniqueid'])) {
            $params['bet_external_unique_id'] = "game-{$this->api->getPlatformCode()}-{$bet['external_uniqueid']}";
        }

        $resultKey = $this->searchTransactionByType($transactions, 'result');
        if ($resultKey !== false) {
            $resultDetails = $transactions[$resultKey];
            if (!empty($resultDetails)) {
                return array(
                    self::ERROR_CODE_SUCCESS,
                    array("balance" => (float)$this->api->dBtoGameAmount($resultDetails['after_balance'])),
                    ""
                );
            }
        }

        $refundKey = $this->searchTransactionByType($transactions, 'refund');
        if ($refundKey !== false) {
            throw new Exception(__LINE__ . ": Transaction already refunded.", self::ERROR_CODE_TRANS_ALREADY_HAVE_REFUND);
        }

        $response    = array();
        $errorCode   = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function () use (&$params, &$response, &$errorCode, &$errorMessage) {
            $payoutAmount = $this->sanitizeAmount($params['total_win']);
            $params['balance_adjustment_type'] = 'increase';
            $params['sbe_status']              = GAME_LOGS::STATUS_SETTLED;
            $params['trans_type']              = "result";
            $params['amount_adjustment']       = $params['payout_amount'] = abs($payoutAmount);
            $params['external_uniqueid']       = "result-{$params['txn_id']}";

            $beforeBalance = $this->getPlayerBalance($this->playerId);
            $afterBalance = false;
            if ($beforeBalance === false) {
                $errorMessage = "Unable to fetch balance.";
                return false;
            }

            if ($this->remoteWalletEnabled) {
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT,
                    true,
                    $params['txn_id'],
                    $params['game_id'],
                    $params['bet_external_unique_id'],
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET
                );
            }

            if ($this->utils->compareResultFloat($payoutAmount, '>', 0)) {

                $success = $this->adjustWalletBalance($this->playerId, 'inc', $payoutAmount, $afterBalance);
                if (!$success) {
                    $errorMessage = "Increase balance encountered error. Amount: {$payoutAmount}.";
                }
            } elseif ($this->utils->compareResultFloat($payoutAmount, '=', 0)) {
                $success = true;
                if ($this->remoteWalletEnabled) {
                    $success = $this->adjustWalletBalance($this->playerId, 'inc', $payoutAmount, $afterBalance);
                    if (!$success) {
                        $errorMessage = "Increase balance encountered error. Amount: 0.";
                    }
                }
            } else {
                $errorMessage = "Total payout < 0.";
                $success = false;
            }

            if ($afterBalance === false) {
                $afterBalance = $this->getPlayerBalance($this->playerId);
                if ($afterBalance === false) {
                    $errorMessage = "Unable to fetch after balance.";
                    return false;
                }
            }

            $params['before_balance'] = $beforeBalance;
            $params['after_balance']  = $afterBalance;
            $transId = $this->processRequestData($params);
            if ($transId) {
                $response  = array("balance" => (float)$this->api->dBtoGameAmount($afterBalance));
                $errorCode = self::ERROR_CODE_SUCCESS;
                return true;
            }
            $errorMessage = "Transaction processing failed.";
            return false;
        });

        if (!$success) {
            $this->saveFailedTransactions($params);
        }

        return array($errorCode, $response, $errorMessage);
    }

    private function bet($params)
    {
        if (empty($params['txn_id'])) {
            throw new Exception(__LINE__ . ": Empty txn_id.", self::ERROR_CODE_INVALID_TXN_ID);
        }
        $txnId   = $params['txn_id'];
        $columns = array('id', 'access_token', 'txn_id', 'trans_type', 'after_balance', 'external_uniqueid',
                         'bet_amount', 'game_id', 'bonus_id', 'subgame_id', 'ts', 'total_bet');
        $transaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(
            $this->api->getTransactionsTable(),
            array('txn_id' => $txnId, 'player_id' => $this->playerId, 'trans_type' => 'bet'),
            $columns
        );
        if (empty($transaction) && $this->checkPreviousMonth) {
            $transaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom(
                $this->api->getTransactionsPreviousTable(),
                array('txn_id' => $txnId, 'player_id' => $this->playerId, 'trans_type' => 'bet'),
                $columns
            );
        }
        if (!empty($transaction)) {
            return array(
                self::ERROR_CODE_SUCCESS,
                array("balance" => (float)$this->api->dBtoGameAmount($transaction['after_balance'])),
                ""
            );
        }

        $response    = array();
        $errorCode   = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function () use (&$params, &$response, &$errorCode, &$errorMessage) {
            $betAmount = $this->sanitizeAmount($params['total_bet']);
            $params['balance_adjustment_type'] = 'decrease';
            $params['sbe_status']              = GAME_LOGS::STATUS_PENDING;
            $params['trans_type']              = "bet";
            $params['amount_adjustment']       = $params['bet_amount'] = abs($betAmount);
            $params['payout_amount']           = 0;
            $params['external_uniqueid']       = "bet-{$params['txn_id']}";

            $beforeBalance = $this->getPlayerBalance($this->playerId);
            $afterBalance = false;
            if ($beforeBalance === false) {
                $errorMessage = "Unable to fetch balance.";
                return false;
            }

            if ($this->remoteWalletEnabled) {
                $this->applyRemoteWalletSettings(
                    $params,
                    Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET,
                    false,
                    $params['txn_id'],
                    $params['game_id']
                );
            }

            if ($this->utils->compareResultFloat($betAmount, '>', 0)) {
                // Check for sufficient balance.
                if ($this->utils->compareResultFloat($betAmount, '>', $beforeBalance)) {
                    $errorCode   = self::ERROR_CODE_INSUFFICIENT_BALANCE;
                    $errorMessage = "Insufficient balance.";
                    return false;
                }
                
                $success = $this->adjustWalletBalance($this->playerId, 'dec', $betAmount, $afterBalance);
                if (!$success) {
                    $errorMessage = "Decrease balance encountered error. Amount: {$betAmount}.";
                }
            } elseif ($this->utils->compareResultFloat($betAmount, '=', 0)) {
                $success = true;
                if ($this->remoteWalletEnabled) {
                    $success = $this->adjustWalletBalance($this->playerId, 'dec', $betAmount, $afterBalance);
                    if (!$success) {
                        $errorMessage = "Decrease balance encountered error. Amount: 0.";
                    }
                }
            } else {
                $errorMessage = "Total bet < 0.";
                $success = false;
            }

            if ($afterBalance === false) {
                $afterBalance = $this->getPlayerBalance($this->playerId);
                if ($afterBalance === false) {
                    $errorMessage = "Unable to fetch after balance.";
                    return false;
                }
            }

            $params['before_balance'] = $beforeBalance;
            $params['after_balance']  = $afterBalance;
            $transId = $this->processRequestData($params);
            if ($transId) {
                $response  = array("balance" => (float)$this->api->dBtoGameAmount($afterBalance));
                $errorCode = self::ERROR_CODE_SUCCESS;
                return true;
            }
            $errorMessage = "Transaction processing failed.";
            return false;
        });

        if (!$success) {
            $this->saveFailedTransactions($params);
        }

        return array($errorCode, $response, $errorMessage);
    }

    private function processRequestData($params)
    {
        $dataToInsert = array(
            // Default fields
            "game_platform_id" => $this->gamePlatformId,
            "player_id"        => $this->playerId,
            "trans_type"       => isset($params['trans_type']) ? $params['trans_type'] : null,

            // Params data
            "access_token"     => isset($params['access_token']) ? $params['access_token'] : null,
            "txn_id"           => isset($params['txn_id']) ? $params['txn_id'] : null,
            "total_bet"        => isset($params['total_bet']) ? $params['total_bet'] : 0,
            "total_win"        => isset($params['total_win']) ? $params['total_win'] : 0,
            "bonus_win"        => isset($params['bonus_win']) ? $params['bonus_win'] : 0,
            "game_id"          => isset($params['game_id']) ? $params['game_id'] : null,
            "subgame_id"       => isset($params['subgame_id']) ? $params['subgame_id'] : null,
            "ts"               => isset($params['ts']) ? $params['ts'] : null,
            "round_start_time" => isset($params['ts']) ? date('Y-m-d H:i:s', $params['ts']) : null,
            "bonus_id"         => isset($params['bonus_id']) ? $params['bonus_id'] : null,
            "bonus_reward"     => isset($params['bonus_reward']) ? $params['bonus_reward'] : null,
            "bonus_type"       => isset($params['bonus_type']) ? $params['bonus_type'] : null,

            // SBE default values
            "bet_amount"       => isset($params['bet_amount']) ? $params['bet_amount'] : 0,
            "payout_amount"    => isset($params['payout_amount']) ? $params['payout_amount'] : 0,
            "jackpot_amount"   => isset($params['jackpot_amount']) ? $params['jackpot_amount'] : 0,
            "full_url"         => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            "sbe_status"       => isset($params['sbe_status']) ? $params['sbe_status'] : null,
            "before_balance"   => isset($params['before_balance']) ? $params['before_balance'] : null,
            "after_balance"    => isset($params['after_balance']) ? $params['after_balance'] : null,
            "elapsed_time"     => intval($this->utils->getExecutionTimeToNow() * 1000),
            "request_id"       => $this->utils->getRequestId(),
            "external_uniqueid"=> isset($params['external_uniqueid']) ? $params['external_uniqueid'] : null,
            "md5_sum"          => $this->original_seamless_wallet_transactions->generateMD5SumOneRow(
                                        $params,
                                        array('txn_id', 'ts', 'trans_type'),
                                        array('bet_amount', 'payout_amount')
                                    ),

            // Remote wallet fields
            "remote_wallet_status"         => $this->remote_wallet_status,
            "seamless_service_unique_id"   => isset($params['seamless_service_uniqueid']) ? $params['seamless_service_uniqueid'] : null,
        );

        return $this->original_seamless_wallet_transactions->insertTransactionData(
            $this->api->getTransactionsTable(),
            $dataToInsert
        );
    }

    private function saveFailedTransactions($data) {

        $this->load->model(array('failed_seamless_transactions'));
        $failedTrans = array(
            'transaction_id' => isset($data['txn_id']) ? $data['txn_id'] : null,
            'round_id' => isset($data['txn_id']) ? $data['txn_id'] : null,
            'external_game_id' => isset($data['game_id']) ? $data['game_id'] : null,
            'player_id' => $this->playerId,
            'game_username' => isset($this->playerDetails['game_username']) ? $this->playerDetails['game_username'] : null,
            'amount' => isset($data['amount_adjustment']) ? $data['amount_adjustment'] : null,
            'balance_adjustment_type' => isset($data['balance_adjustment_type']) ? $data['balance_adjustment_type'] : null,
            'action' => $this->method,
            'game_platform_id' => $this->gamePlatformId,
            'transaction_raw_data' => $this->query_string,
            'remote_raw_data' => $this->wallet_model->getRemoteApiParams(),
            'external_uniqueid' => isset($data['external_uniqueid']) ? $data['external_uniqueid'] : null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => isset($data['ts']) ? date('Y-m-d H:i:s', $data['ts']) : null,
            'created_at' => $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'updated_at' => $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'request_id' => $this->utils->getRequestId(),
            'headers' => json_encode($this->requestHeaders),
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
        );

        $monthStr = date('Ym');
        $failedTransSave = $this->failed_seamless_transactions->insertTransaction($failedTrans, "failed_remote_common_seamless_transactions_{$monthStr}");
        $this->utils->debug_log("5GSS: ({$this->method})", "failedTransSave", $failedTransSave, "failedTrans", $failedTrans);
    }

    private function getbalance($params)
    {
        $balance = $this->getPlayerBalance($this->playerId, true);

        return [
            self::ERROR_CODE_SUCCESS,
            [
                "balance" => (float)$this->api->dBtoGameAmount($balance)
            ],
            ""
        ];
    }

    private function authenticate($params)
    {
        $balance = $this->getPlayerBalance($this->playerId, true);

        return [
            self::ERROR_CODE_SUCCESS,
            [
                "member_id" => isset($this->playerDetails['game_username']) ? $this->playerDetails['game_username'] : null,
                "balance" => (float)$this->api->dBtoGameAmount($balance)
            ],
            ""
        ];
    }

    private function setResponse($errorCode, $response = [], $errorMessage = "")
    {
        $defaultResponse = [
            "status_code" => $errorCode,
        ];
        $this->utils->debug_log("==> setResponse errorMessage for request id {$this->utils->getRequestId()}", $errorMessage);
        if ($errorCode == self::ERROR_CODE_SUCCESS) {
            if(!empty($response)){
                $defaultResponse = array_merge($defaultResponse, $response);
            }
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
            json_encode($this->query_string),
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

