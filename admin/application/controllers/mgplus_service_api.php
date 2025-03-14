<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/**
 * Mgplus_service_api
 * @property Common_seamless_wallet_transactions $common_seamless_wallet_transactions
 */
class Mgplus_service_api extends BaseController {
    use Seamless_service_api_module;

    private $api;
    private $request_headers;
    // private $response_result_id;
    // private $player_details;
    private $processed_transaction_id;
    private $processed_multiple_transaction;
    // private $transaction_table;

    const SUCCESS = [
        'code' => 200,
        'message' => 'Ok.'
    ];

    const ERROR_BAD_REQUEST = [
        'code' => 400,
        'message' => 'Bad request.'
    ];

    const ERROR_TOKEN_INVALID = [
        'code' => 401,
        'message' => 'API Token Expired or not valid.'
    ];

    const ERROR_NOT_ENOUGH_BALANCE = [
        'code' => 402,
        'message' => 'Not enough available balance or invalid amount.'
    ];

    const ERROR_PLAYER_NOT_FOUND = [
        'code' => 404,
        'message' => 'Player not found.'
    ];

    const ERROR_INTERNAL_SERVER_ERROR = [
        'code' => 500,
        'message' => 'Internal server error.'
    ];

    const ERROR_INVALID_PARAM = [
        'code' => 500,
        'message' => 'Invalid parameter.'
    ];

    const ERROR_INVALID_HEADERS = [
        'code' => 500,
        'message' => 'Invalid headers.'
    ];

    const ERROR_INVALID_TOKEN = [
        'code' => 500,
        'message' => 'Invalid token.'
    ];

    const ERROR_TRANSACTION_EXIST = [
        'code' => 400,
        'message' => 'Transaction already exist.'
    ];

    const ERROR_TRANSACTION_NOT_EXIST_FOR_ROLLBACK = [
        'code' => 500,
        'message' => 'Transaction not exist for rollback.'
    ];

    const ERROR_TRANSACTION_INVALID_ROLLBACK_AMOUNT = [
        'code' => 500,
        'message' => 'Invalid rollback amount.'
    ];

    const ERROR_GAME_UNDER_MAINTENANCE = [
        'code' => 500,
        'message' => 'Game under maintenance.'
    ];

    #for failed transaction testing
    const ERROR_TRANSACTION_FORCE_FAILED= [
        'code' => 500,
        'message' => 'Forced to failed.'
    ];

    const ERROR_ROLLBACK_EXIST = [
        'code' => 500,
        'message' => 'Transaction already rollback.'
    ];

    const ERROR_UNKNOWN = [
        'code' => 500,
        'message' => 'Unknown reponse. Prossible timeout.'
    ];

    const ERROR_FAILED_PROCESS_TRANSACTION = [
        'code' => 500,
        'message' => 'Failed to process transaction.'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'code' => 401,
        'message' => 'IP address is not allowed.'
    ];

    const ERROR_INVALID_PARAMETER_CURRENCY = [
        'code' => 500,
        'message' => 'Invalid parameter currency.'
    ];

    const ERROR_BET_NOT_EXIST = [
        'code' => 400,
        'message' => 'Bet not exist.'
    ];

    const ERROR_ALREADY_PROCESSED_BY_ROLLBACK = [
        'code' => 400,
        'message' => 'Already processed by rollback.'
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'game_platform_id',
        'amount',
        'before_balance',
        'after_balance',
        'player_id',
        'game_id',
        'transaction_type',
        'status',
        'external_unique_id',
        'extra_info',
        'round_id',
        'end_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'before_balance',
        'after_balance',
        'amount'
    ];
    const ACTIVE_METHOD = ['login','getbalance','updatebalance','rollback'];
    
    public function __construct() {
        parent::__construct();
        // $this->ssa_init();
        $this->load->model(array('common_token','common_seamless_wallet_transactions','common_seamless_error_logs','external_system', 'wallet_model', 'original_seamless_wallet_transactions'));
        $this->request_headers = getallheaders();
        // $this->transaction_table = 'common_seamless_wallet_transactions';
    }

    public function index($api, $method) {
        $request_json = file_get_contents('php://input');
        $this->utils->debug_log('mgplus service request_json', $request_json);
        // $this->currencyCode = $currency;
        $this->request = json_decode($request_json, true);
        $this->request_method = $method;
        // $this->api = $this->utils->loadExternalSystemLibObject($api);
        $request_headers = $this->input->request_headers();
        $this->request_id = isset($request_headers['X-Mgp-Req-Id']) ? $request_headers['X-Mgp-Req-Id'] : null;
        $this->request_token = isset($request_headers['X-Mgp-Token']) ? $request_headers['X-Mgp-Token'] : null;
        $this->request_time = isset($request_headers['X-Mgp-Request-Timems']) ? $request_headers['X-Mgp-Request-Timems'] : null;
        $this->request_headers = array(
            "X-Mgp-Req-Id" => $this->request_id,
            "X-Mgp-Token" => $this->request_token,
            "X-Mgp-Request-Timems" => $this->request_time,
        );
        $this->response_result_id = null;
        $this->api = $this->player_details = null;
        $this->processed_multiple_transaction = [];

        $this->utils->debug_log('mgplus service request_headers', $request_headers);

        if (!$this->request_id){
            return $this->setResponse(self::ERROR_INVALID_HEADERS);
        }

        // $is_valid=$this->getCurrencyAndValidateDB();
        // if( !$is_valid ) {
        //     return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        // }

        # Check if your variable is an integer
        if ( strval($api) !== strval(intval($api)) ) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if( !$this->api ) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        $this->common_seamless_wallet_transactions->tableName = $this->api->original_transactions_table;

        if(!$this->api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $error_response = self::ERROR_IP_NOT_ALLOWED;
            $error_response['message'] = "IP not allowed({$ip})";
            return $this->setResponse($error_response);
        }

        if (!$this->external_system->isGameApiActive($api) || $this->external_system->isGameApiMaintenance($api)) {
            return $this->setResponse(self::ERROR_GAME_UNDER_MAINTENANCE);
        }

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if(!in_array($method, self::ACTIVE_METHOD)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if($this->api->enabled_token_verification){
            if(!$this->request_token ){
                return $this->setResponse(self::ERROR_INVALID_HEADERS);
            }

            if($this->api->token_verification !== $this->request_token){
                return $this->setResponse(self::ERROR_INVALID_TOKEN);
            }
        }

        if (!$this->ssa_is_server_ip_allowed($this->api)) {
            return $this->setResponse(self::ERROR_IP_NOT_ALLOWED);
        }

        $this->utils->debug_log('MGPLUS_SEAMLESS_API', 'Currency Validation',
                                'default currency', !empty($this->api->currency) ? $this->api->currency: null,
                                'request currency', isset($this->request['currency']) ? $this->request['currency'] : null, 
                                'other info', $this->utils->getCurrentCurrency(),
                                'currency key', $this->utils->getActiveCurrencyKey());

        if (isset($this->request['currency']) && !empty($this->api->currency) && $this->request['currency'] != $this->api->currency) {
            return $this->setResponse(self::ERROR_INVALID_PARAMETER_CURRENCY);
        }

        /* $this->response_result_id = $this->setResponseResult();
        if(!$this->response_result_id){
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        } */

        return $this->$method();
    }

    /**
     * getCurrencyAndValidateDB
     * @param  array $reqParams
     * @return [type]            [description]
     */
    private function getCurrencyAndValidateDB() {
        if(isset($this->currencyCode) && !empty($this->currencyCode)) {
            # Get Currency Code for switching of currency and db forMDB
            $is_valid=$this->validateCurrencyAndSwitchDB($this->currencyCode);

            return $is_valid;
        } else {
            return false;
        }
    }

    protected function validateCurrencyAndSwitchDB(){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($this->currencyCode)){
            return false;
        }else{
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($this->currencyCode)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($this->currencyCode);
                return true;
            }
        }
    }

    const DEBIT = "DEBIT";
    const CREDIT = "CREDIT";
    const ROLLBACK_DEBIT = "ROLLBACK_DEBIT";
    const ROLLBACK_CREDIT = "ROLLBACK_CREDIT";

    public function updatebalance(){
        $checkPreviousMonth = false;
        $extra = [];
        if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_day_to_check_monthly_table', '1')) {
            $checkPreviousMonth = true;
        }

        if($this->api->force_failed_transaction){
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }
        $txnId = isset($request['txnId']) ? $request['txnId'] :null;
        
        if(strlen($txnId) > 150){
            return $this->setResponse(self::ERROR_TRANSACTION_FORCE_FAILED);
        }

        $request = $this->request;
        $is_end_round = isset($request['completed']) && $request['completed'];
        
        if(isset($request['amount'])) {
            if(is_numeric($request['amount'])) {
                $request['amount'] = $this->api->gameAmountToDB($request['amount']);
            }
        }
        $gameUsername = isset($request['playerId']) ? $request['playerId'] : null;
        if(empty($gameUsername)){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $this->player_details = $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $transactionId = isset($request['txnId']) ? $request['txnId'] : null;
        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId, $this->api->getTransactionsTable());
        $extra['isTransactionExist'] = $isTransactionExist;

        //check if insufficient balance
        $playerName = isset($playerDetails['username']) ? $playerDetails['username'] : null;
        $currentPlayerBalance = $this->getPlayerBalance($playerName);

        if ($this->utils->compareResultFloat($request['amount'], '>', $currentPlayerBalance)) {
            return $this->setResponse(self::ERROR_NOT_ENOUGH_BALANCE);
        }

        if(!in_array($request['txnType'], [self::DEBIT, self::CREDIT])){
            if($isTransactionExist){
                return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
            } else {
                if($this->api->use_monthly_transactions_table && $checkPreviousMonth){
                    $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $transactionId, $this->api->getTransactionsPreviousTable());
                    $extra['isTransactionExist'] = $isTransactionExist;
                    if($isTransactionExist){
                        return $this->setResponse(self::ERROR_TRANSACTION_EXIST);
                    }
                }
            }
        }
        
        if( isset($request['txnType']) ){
            if( $request['txnType'] === self::DEBIT || $request['txnType'] === self::CREDIT ){
                $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
                $response = array();

                if(isset($request['amount'])) {
                    if(!is_numeric($request['amount'])) {
                        return $this->setResponse(self::ERROR_BAD_REQUEST);
                    }
                }

                if (method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()) {
                    if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                        $this->wallet_model->setGameProviderIsEndRound($is_end_round);
                    }
                }

                if ($request['txnType'] === self::DEBIT){
                    $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                }

                if ($request['txnType'] === self::CREDIT) {
                    $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;

                    /* if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                        if($this->utils->isEnabledRemoteWalletClient()){
                            if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                                $this->wallet_model->setGameProviderIsEndRound(true);
                            }
                        }
                    } */

                    // check if bet transaction exists
                    $bet_transaction = $this->ssa_get_transaction($this->api->getTransactionsTable(), [
                        'game_platform_id' => $this->api->getPlatformCode(),
                        'transaction_id' => isset($request['betId']) ? $request['betId'] : null
                    ]);

                    $this->utils->debug_log("MGPLUS_SERVICE_API @updatebalance:credit  - related bet transaction ", $bet_transaction);


                    if (empty($bet_transaction)) {
                        // return $this->setResponse(self::ERROR_BET_NOT_EXIST);
                        if($this->api->use_monthly_transactions_table && $checkPreviousMonth){
                            $bet_transaction = $this->ssa_get_transaction($this->api->getTransactionsPreviousTable(), [
                                'game_platform_id' => $this->api->getPlatformCode(),
                                'transaction_type' => self::DEBIT,
                                'transaction_id' => $request['betId'],
                            ]);

                            if (empty($bet_transaction)) {
                                return $this->setResponse(self::ERROR_BET_NOT_EXIST);
                            } 
                        } else {
                            return $this->setResponse(self::ERROR_BET_NOT_EXIST);
                        } 
                    }

                    $bet_external_unique_id = isset($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null;
                    $betUniqueIdOfSeamlessService='game-'.$this->api->getPlatformCode().'-'.$bet_external_unique_id; 
                    $this->utils->debug_log("MGPLUS_SERVICE_API @updatebalance:credit  - betUniqueIdOfSeamlessService ", $betUniqueIdOfSeamlessService);
                    if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                        if($this->utils->isEnabledRemoteWalletClient()){
                            $this->utils->debug_log("MGPLUS_SERVICE_API @updatebalance:credit  - enabled remote wallet ", $betUniqueIdOfSeamlessService);
                            if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                                $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                            }
                            if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                                $this->wallet_model->setRelatedUniqueidOfSeamlessService($betUniqueIdOfSeamlessService);
                            }
                        }
                    }

                    // check if rollback transaction exists
                    $is_rollback_exists = $this->ssa_is_transaction_exists($this->api->getTransactionsTable(), [
                        'game_platform_id' => $this->api->getPlatformCode(),
                        'external_unique_id' => 'ROLLBACK_' . $bet_transaction['external_unique_id'],
                    ]);

                    if ($is_rollback_exists) {
                        return $this->setResponse(self::ERROR_ALREADY_PROCESSED_BY_ROLLBACK);
                    } else {
                        if($this->api->use_monthly_transactions_table && $checkPreviousMonth){
                            $is_rollback_exists = $this->ssa_is_transaction_exists($this->api->getTransactionsPreviousTable(), [
                                'game_platform_id' => $this->api->getPlatformCode(),
                                'external_unique_id' => 'ROLLBACK_' . $bet_transaction['external_unique_id'],
                            ]);

                            if ($is_rollback_exists) {
                                return $this->setResponse(self::ERROR_ALREADY_PROCESSED_BY_ROLLBACK);
                            }
                        }
                    }
                }

                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode, $remoteActionType, $extra) {
                    $success = $controller->adjustWallet($playerDetails, $request, $response, $errorCode, $remoteActionType, $extra);
                    return $success;
                });

                if($success){
                    return $this->setResponse(self::SUCCESS, $response);
                } else {
                    // $uniqueIdOfSeamlessService = $this->wallet_model->getUniqueidOfSeamlessService();
                    // $this->wallet_model->rollbackRemoteWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $uniqueIdOfSeamlessService);
                    // return $this->setResponse($errorCode);
                }
            } else {
                #error
                return $this->setResponse(self::ERROR_INVALID_PARAM);
            }
        }
        return $this->setResponse(self::ERROR_INVALID_PARAM);
    }

    public function adjustWallet($playerDetails, $request, &$response, &$errorCode, $remoteActionType=Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET, $extra=[]){
        $isTransactionExist = isset($extra['isTransactionExist']) ? $extra['isTransactionExist'] : false;
        if($isTransactionExist){
            // return exact same response on first successful response;
            $retryTrans = $this->ssa_get_transaction($this->api->getTransactionsTable(), [
                'game_platform_id' => $this->api->getPlatformCode(),
                'external_unique_id' => isset($request['txnId']) ? strval($request['txnId']) : null
            ]);

            $errorCode = self::SUCCESS;
            $response['message'] = lang("Success");
            $response['extTxnId'] = isset($retryTrans['id']) ? intval($retryTrans['id']) : null;
            $response['balance'] = isset($retryTrans['after_balance']) ? $this->api->dBtoGameAmount($retryTrans['after_balance']) : null;
            $response['currency'] = $this->api->currency;
            
            if (isset($request['creationTimeMs'])) {
                $response['extCreationTimeMs'] = $request['creationTimeMs'];
            }
            $this->utils->debug_log("MGPLUS_SERVICE_API @updatebalance:adjustwallet  - transaction already exists return exact same response on first successful response", $response);
            return true;
        }
        $amount = isset($request['amount']) ? $request['amount'] : null;
        $txnType = isset($request['txnType']) ? $request['txnType'] : null;
        $external_game_unique_id = isset($request['contentCode']) ? $request['contentCode'] : null;
        $playerName = $playerDetails['username'];
        $afterBalance = $beforeBalance = $this->getPlayerBalance($playerName);
        // $request = $this->request;
        if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
            if($this->utils->isEnabledRemoteWalletClient()){
                if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType($remoteActionType);
                }
            }
        }

        $uniqueid = isset($request['txnId']) ? $request['txnId'] : null;
        $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid; 
        if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
            if($this->utils->isEnabledRemoteWalletClient()){
                if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService, $external_game_unique_id);
                }
            }
        }

        if ($request['txnType'] === self::CREDIT && strtoupper($request['txnEventType']) == "GAME") {
            $bet_transaction = $this->ssa_get_transaction($this->api->getTransactionsTable(), [
                'game_platform_id' => $this->api->getPlatformCode(),
                'transaction_id' => $request['betId'],
            ]);
            $bet_external_unique_id = isset($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null;
            $betUniqueIdOfSeamlessService='game-'.$this->api->getPlatformCode().'-'.$bet_external_unique_id; 
            $this->utils->debug_log("MGPLUS_SERVICE_API @updatebalance:credit  - betUniqueIdOfSeamlessService ", $betUniqueIdOfSeamlessService);
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                if($this->utils->isEnabledRemoteWalletClient()){
                    $this->utils->debug_log("MGPLUS_SERVICE_API @updatebalance:credit  - enabled remote wallet ", $betUniqueIdOfSeamlessService);
                    if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                        $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                    }
                    if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                        $this->wallet_model->setRelatedUniqueidOfSeamlessService($betUniqueIdOfSeamlessService);
                    }
                }
            }
        }


        if( $txnType === self::DEBIT || $txnType === self::CREDIT || $txnType === self::ROLLBACK_DEBIT || $txnType === self::ROLLBACK_CREDIT ){
            if ($this->utils->compareResultFloat($amount, '>', 0)) {        
                if($txnType === self::DEBIT || $txnType === self::ROLLBACK_CREDIT){
                    // $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount);
                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
                    } else {
                        $success = $this->wallet_model->decSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                        
                        # check if remote wallet already processed
                        if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                            if($this->utils->isEnabledRemoteWalletClient()){
                                $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
                                    $success = true;
                                }
                            }
                        }
                        
                    }
                } elseif ( $txnType === self::CREDIT || $txnType === self::ROLLBACK_DEBIT) {
                    // $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount);
                    if($this->utils->getConfig('enable_seamless_single_wallet')) {
                        $reason_id=Abstract_game_api::REASON_UNKNOWN;
                        $success = $this->wallet_model->transferSeamlessSingleWallet($playerDetails['player_id'], Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
                    } else {
                        $success = $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                        
                        # check if remote wallet already processed
                        if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                            if($this->utils->isEnabledRemoteWalletClient()){
                                $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
                                    $success = true;
                                }
                            }
                        }
                       
                    }
                } else { #default error
                    $success = false;
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $this->wallet_model->incSubWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $amount, $afterBalance);
                $success = true;#allowed amount 0
            } else { #default error
                $success = false;
            }

            if($success){
                $success = false; 
                
                if($afterBalance===null){
                    $afterBalance = $this->getPlayerBalance($playerName);
                }
                
                $request['before_balance'] = $beforeBalance;
                $request['player_id'] = $playerDetails['player_id'];
                // $request['response_result_id'] = $this->response_result_id;
                $request['after_balance'] = $afterBalance;

                $this->processed_transaction_id = $transId = $this->processRequestData($request);

                if($transId){
                    $success = true;
                    $errorCode = self::SUCCESS;
                    $response['message'] = lang("Success");
                    $response['extTxnId'] = $transId;
                    $response['balance'] = $this->api->dBtoGameAmount($afterBalance);
                    $response['currency'] = $this->api->currency;
                    
                    if (isset($request['creationTimeMs'])) {
                        $response['extCreationTimeMs'] = $request['creationTimeMs'];
                    }

                    array_push($this->processed_multiple_transaction, $this->processed_transaction_id);
                } else {
                    $success = false;
                    $errorCode = self::ERROR_FAILED_PROCESS_TRANSACTION; #Failed to save 
                }
            } else {
                $errorCode = self::ERROR_NOT_ENOUGH_BALANCE; #not enough balance or invalid amount
            }

            return $success;
        } else {
            return false;
        }

    }

    public function processRequestData($request){
        # creationTime is deprecated, will use creationTimeMs now
        /* if(isset($request['creationTime'])){ #convert ticks to timestamp
            $ticks = $request['creationTime'];
            $timestamp = ($ticks - 621355968000000000) / 10000000;
            $request['creationTime'] = $timestamp;
        } */
        $dataToInsert = array(
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($request['amount']) ? $request['amount'] : NULL,
            "before_balance" => isset($request['before_balance']) ? $request['before_balance'] : NULL,
            "after_balance" => isset($request['after_balance']) ? $request['after_balance'] : NULL,
            "player_id" => isset($request['player_id']) ? $request['player_id'] : NULL,
            "game_id" => isset($request['contentCode']) ? $request['contentCode'] : NULL,
            "transaction_type" => isset($request['txnType']) ? $request['txnType'] : NULL,
            "status" => isset($request['completed']) ? $request['completed'] : NULL,
            // "response_result_id" => isset($request['response_result_id']) ? $request['response_result_id'] : NULL,
            "external_unique_id" => isset($request['txnId']) ? $request['txnId'] : NULL,
            "extra_info" => json_encode($request),
            // "start_at" => isset($request['creationTimeMs']) ? date("Y-m-d H:i:s", $request['creationTimeMs']) : NULL,
            // "end_at" => isset($request['creationTimeMs']) ? date("Y-m-d H:i:s", $request['creationTimeMs']) : NULL,
            "start_at" => $this->utils->getNowForMysql(),
            "end_at" => $this->utils->getNowForMysql(),
            "round_id" => isset($request['roundId']) ? $request['roundId'] : NULL,
            "transaction_id" => isset($request['betId']) ? $request['betId'] : NULL, #mark as bet id
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
        );
        if(isset($request['txnEventType']) && strtoupper($request['txnEventType']) != "GAME"){
            $dataToInsert['game_id'] = $request['txnEventType'];
        }
        $dataToInsert['md5_sum'] = $this->ssa_generate_md5_sum($dataToInsert, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        $transId = $this->common_seamless_wallet_transactions->insertData($this->api->getTransactionsTable(), $dataToInsert);
        return $transId;
    }

    public function rollback(){
        $checkPreviousMonth = false;
        if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_day_to_check_monthly_table', '1')) {
            $checkPreviousMonth = true;
        }

        $request = $this->request;
        if(isset($request['amount'])) {
            if(is_numeric($request['amount'])) {
                $request['amount'] = $this->api->gameAmountToDB($request['amount']);
            }
        }
        $gameUsername = isset($request['playerId']) ? $request['playerId'] : null;
        if(empty($gameUsername)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $this->player_details = $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $transactionId = isset($request['txnId']) ? $request['txnId'] : null;
        $transaction = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $transactionId, $this->api->getTransactionsTable());
        if(empty($transaction)){
            // return $this->setResponse(self::ERROR_TRANSACTION_NOT_EXIST_FOR_ROLLBACK);
            if($this->api->use_monthly_transactions_table && $checkPreviousMonth){
                $transaction = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->api->getPlatformCode(), $transactionId, $this->api->getTransactionsPreviousTable());
            }
            if(empty($transaction)){
                return $this->setResponse(self::ERROR_TRANSACTION_NOT_EXIST_FOR_ROLLBACK);
            }
        }
        if(isset($request['amount'])){
            
            if(!is_numeric($request['amount'])) {
                return $this->setResponse(self::ERROR_TRANSACTION_INVALID_ROLLBACK_AMOUNT);
            }

            if( $request['amount'] != $transaction['amount'] ){
                return $this->setResponse(self::ERROR_TRANSACTION_INVALID_ROLLBACK_AMOUNT);
            }
        } else {
           $request['amount'] = $transaction['amount']; 
        }

        $errorCode = self::ERROR_INTERNAL_SERVER_ERROR; #default
        $response = array();
        $controller = $this;
        $request['txnType'] = "ROLLBACK_".$transaction['transaction_type'];
        $request['txnId'] = "ROLLBACK_".$transaction['external_unique_id'];
        $request['betId'] = $transaction['betId'];

        $this->utils->debug_log('mgplus rollback', $request);

        $isRollbackExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $request['txnId'], $this->api->getTransactionsTable());
        if($isRollbackExist){
            return $this->setResponse(self::ERROR_ROLLBACK_EXIST);
        } else {
            if($this->api->use_monthly_transactions_table && $checkPreviousMonth){
                $isRollbackExist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $request['txnId'], $this->api->getTransactionsPreviousTable());
                    if($isRollbackExist){
                        return $this->setResponse(self::ERROR_ROLLBACK_EXIST);
                    }
            }
        }
        $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;

        if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
            if($this->utils->isEnabledRemoteWalletClient()){
                if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                    $this->wallet_model->setGameProviderIsEndRound(true);
                }
            }
        }
        
        $bet_transaction = $this->ssa_get_transaction($this->api->getTransactionsTable(), [
            'game_platform_id' => $this->api->getPlatformCode(),
            'transaction_id' => $request['betId'],
        ]);

        $bet_external_unique_id = isset($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null;
        $betUniqueIdOfSeamlessService='game-'.$this->api->getPlatformCode().'-'.$bet_external_unique_id; 
        
        if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
            if($this->utils->isEnabledRemoteWalletClient()){
                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                }
                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($betUniqueIdOfSeamlessService);
                }
            }
        }
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use($controller, $playerDetails, $request, &$response, &$errorCode, $remoteActionType) {
            $success = $controller->adjustWallet($playerDetails, $request, $response, $errorCode, $remoteActionType);
            return $success;
        });

        if($success){
            return $this->setResponse($errorCode, $response);
        } else {
            // $uniqueIdOfSeamlessService = $this->wallet_model->getUniqueidOfSeamlessService();
            // $this->wallet_model->rollbackRemoteWallet($playerDetails['player_id'], $this->api->getPlatformCode(), $uniqueIdOfSeamlessService);
            // return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(){
        $request = $this->request;
        $gameUsername = isset($request['playerId']) ? $request['playerId'] : null;
        if(empty($gameUsername)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $this->player_details = $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $playername = $playerDetails['username'];
        $balance = 0;
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use(&$balance, $playername) {
            $balance = $this->getPlayerBalance($playername);
            if($balance === false) {
                $balance = 0;
                return false;
            }
            return true;
        });

        $response = [
            'currency' => $this->api->currency,
            'balance' => $this->api->dBtoGameAmount($balance),
        ];
        if($success){
            return $this->setResponse(self::SUCCESS, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    public function getbalance(){
        $request = $this->request;
        $gameUsername = isset($request['playerId']) ? $request['playerId'] : null;
        if(empty($gameUsername)){
            return $this->setResponse(self::ERROR_INVALID_PARAM);
        }

        $this->player_details = $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->api->getPlatformCode());
        if(empty($playerDetails)){
            return $this->setResponse(self::ERROR_PLAYER_NOT_FOUND);
        }

        $playerValidToken = $this->common_token->getValidPlayerToken($playerDetails['player_id']);
        if(empty($playerValidToken) && $this->api->enabled_player_token_checking){
            return $this->setResponse(self::ERROR_TOKEN_INVALID);
        }

        $playername = $playerDetails['username'];
        $balance = 0;
        $success = $this->lockAndTransForPlayerBalance($playerDetails['player_id'], function() use(&$balance, $playername) {
            $balance = $this->getPlayerBalance($playername);
            if($balance === false) {
                $balance = 0;
                return false;
            }
            return true;
        });

        $response = [
            'currency' => $this->api->currency,
            'balance' => $this->api->dBtoGameAmount($balance),
        ];

        if($success){
            return $this->setResponse(self::SUCCESS, $response);
        } else {
            return $this->setResponse(self::ERROR_INTERNAL_SERVER_ERROR);
        }
    }

    private function getPlayerBalance($playerName){
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $player_id = $this->api->getPlayerIdFromUsername($playerName);
            $seamless_balance = 0;
            $seamless_reason_id = null;

            $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            return $seamless_balance;
        }
        else {
            $get_bal_req = $this->api->queryPlayerBalance($playerName);
            if($get_bal_req['success']) {
                return $get_bal_req['balance'];
            }
            else {
                return false;
            }
        }
    }

    private function setResponse($returnCode, $response = []) {
        return $this->setOutput($returnCode, $response);
    }

    private function setOutput($returnCode, $response = []) {
        $flag = $returnCode['code'] == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if($flag == Response_result::FLAG_ERROR){
            $response = $returnCode;
        }

        /* if($this->response_result_id) {
            $disabled_response_results_table_only=$this->utils->getConfig('disabled_response_results_table_only');
            if($disabled_response_results_table_only){
                $respRlt = $this->response_result->readNewResponseById($this->response_result_id);
                $content = json_decode($respRlt['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                $respRlt['content'] = json_encode($content);
                $respRlt['status'] = $flag;
                $this->response_result->updateNewResponse($respRlt);
            } else {
                if($flag == Response_result::FLAG_ERROR){
                    $this->response_result->setResponseResultToError($this->response_result_id);
                }
    
                $response_result = $this->response_result->getResponseResultById($this->response_result_id);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
    
                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content['headers'] = $this->request_headers;
                $content = json_encode($content);
                $this->response_result->updateResponseResultCommonData($this->response_result_id, $content, null, $flag);
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            } 
        } */
        // remove any string that could create an invalid JSON 
        // such as PHP Notice, Warning, logs...
        // if(ob_get_length() > 0) {
        //     ob_clean();
        // }

        // this will clean up any previously added headers, to start clean
        // header_remove(); 

        $executionTime = intval($this->utils->getExecutionTimeToNow()*1000);
        if($flag == Response_result::FLAG_ERROR){
            if($this->api){
                $request_id = $this->utils->getRequestId();
                $now = $this->utils->getNowForMysql();
                $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
                $commonSeamlessErrorDetails = json_encode($response);
                $errorLogInsertData = [
                    'game_platform_id' => $this->api->getPlatformCode(),
                    'response_result_id' => $this->response_result_id,
                    'request_id' => $request_id,
                    'elapsed_time' => $elapsed,
                    'error_date' => $now,
                    'extra_info' => $commonSeamlessErrorDetails
                ];
                $this->common_seamless_error_logs->insertTransaction($errorLogInsertData);
            }
        }
        $jsonOutput = json_encode($response);
        $statusHeader = $returnCode['code'];

        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $http_response = $this->ssa_get_http_response($statusHeader);
        $this->response_result_id = $this->ssa_save_response_result($this->api->getPlatformCode(), $flag, $this->request_method, $this->request, $response, $http_response, $player_id);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];

                $this->ssa_update_transaction_without_result($this->common_seamless_wallet_transactions->tableName, $updated_data, 'id', $processed_transaction_id);
            }
        }

        $output = $this->output->set_content_type('application/json')
                        ->set_status_header($statusHeader)
                        ->set_output($jsonOutput);
        $output->set_header("X-MGP-REQ-ID: {$this->request_id}");
        $output->set_header("X-MGP-RESPONSE-TIME: {$executionTime}");
        $this->utils->debug_log('mgplus service output_sent', (array)$output);
        return $output;
        // return $this->returnJsonResult($response);
    }

    private function setResponseResult(){
        $response_result_id = $this->response_result->saveResponseResult(
            $this->api->getPlatformCode(),
            Response_result::FLAG_NORMAL,#default normal  flag
            $this->request_method,
            json_encode($this->request),
            self::ERROR_UNKNOWN,#default unknown response
            200,
            null,
            is_array($this->request_headers) ? json_encode($this->request_headers) : $this->request_headers
        );

        return $response_result_id;
    }
}
