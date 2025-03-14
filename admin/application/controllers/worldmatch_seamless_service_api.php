<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Worldmatch_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const ERROR_CODE_SUCCESS = 0;
    const ERROR_CODE_PLAYER_BLOCKED = 40302;
    const ERROR_CODE_PLAYER_NOT_FOUND = 40410;
    const ERROR_CODE_PLAYER_TOKEN_NOT_FOUND = 40411;
    const ERROR_USER_BALANCE_INSUFFICIENT = 49001;
    const ERROR_CODE_FAIL = 46000;
    const ERROR_CODE_SYSTEM_ERROR = 999;
    const ALLOWED_METHOD = ['auth', 'balance', 'debit', 'credit', 'cancel'];
    const FUNCTION_IGNORE_TOKEN_VALIDATION = ['credit', 'cancel'];

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'original_seamless_wallet_transactions', 'external_system', 'player_model', 'game_description_model', 'multiple_db_model'));
    }

    private function getGamePlatformId(){
        return WORLDMATCH_CASINO_SEAMLESS_API;
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

    //Initial callback request
    public function index($method = null){
        $this->requestBody = file_get_contents("php://input");
        $this->requestHeaders = $this->input->request_headers();
        $this->gamePlatformId = $this->getGamePlatformId();
        $this->remoteWalletEnabled = $this->ssa_enabled_remote_wallet();
        $this->playerDetails = [];
        $this->playerId = null;
        $this->method = $method;
        $this->checkPreviousMonth = false;
        $this->remote_wallet_status = null;
        $this->automation_tester_game_account = [];

        $response = [];
        $errorMessage = "";
        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $requestArray = json_decode($this->requestBody, true);
    
        try {
            $token = isset($requestArray['token']) ? $requestArray['token'] : null;
            if(!empty($token)){
                if(strpos($token, "-") !== false){
                    list($currency) = explode("-", $token);
                    $isDBswitchSuccess = $this->getCurrencyAndValidateDB($currency);
                    if(!$isDBswitchSuccess){
                        throw new Exception(__LINE__.":Invalid Currency.", self::ERROR_CODE_SYSTEM_ERROR);
                    }

                    $prefix = "{$currency}-";
                    if(substr($token, 0, strlen($prefix)) == $prefix) {
                        $requestArray['token'] = substr($token, strlen($prefix));
                    }
                }
            }

            $this->api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
            if(!$this->api) {
                throw new Exception(__LINE__.":Invalid API.", self::ERROR_CODE_SYSTEM_ERROR);
            }

            if(empty($this->method) 
                || !method_exists($this, $this->method) 
                || !in_array($this->method, self::ALLOWED_METHOD) 
                || empty($this->requestBody)
            ){
                throw new Exception(__LINE__.":Invalid Params.", self::ERROR_CODE_FAIL);
            }

            if($this->external_system->isGameApiMaintenance($this->gamePlatformId)){
                throw new Exception(__LINE__.":The game is on maintenance.", self::ERROR_CODE_SYSTEM_ERROR);   
            }

            if(!$this->external_system->isGameApiActive($this->gamePlatformId)){
                throw new Exception(__LINE__.":The game is disabled.", self::ERROR_CODE_SYSTEM_ERROR);   
            }

            if(!$this->api->validateWhiteIP()){
                throw new Exception(__LINE__.":Invalid IP.", self::ERROR_CODE_FAIL);
            }

            if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
                $this->checkPreviousMonth = true;
            }

            $this->automation_tester_game_account = $this->api->getSystemInfo('automation_tester_game_account', []);
 
            $this->authenticate($requestArray);
            $this->validateParams($requestArray);
            list($errorCode, $response, $errorMessage) = $this->$method($requestArray);
            

        } catch (Exception $e) {
            $this->utils->debug_log('==> worldmatch_seamless_service_api encounter error at line and message', $e->getMessage());
            $messageArray = explode(":", $e->getMessage());
            $errorMessage = isset($messageArray[1]) ? $messageArray[1] : "";
            $errorCode = $e->getCode();
        }

        return $this->setResponse($errorCode, $response, $errorMessage);
    }

    private function processRequestData($params){
        $dataToInsert = array(
            #default
            "game_platform_id" => $this->api->getPlatformCode(), 
            "player_id" => $this->playerId, 
            "trans_type" => $this->method,

            #params data
            "userid" => isset($params['userid']) ? $params['userid'] : NULL,
            "usertoken" => isset($params['usertoken']) ? $params['usertoken'] : NULL, 
            "sessiontoken" => isset($params['sessiontoken']) ? $params['sessiontoken'] : NULL, 
            "gameidentity" => isset($params['gameidentity']) ? $params['gameidentity'] : NULL, 
            "transactionid" => isset($params['transactionid']) ? $params['transactionid'] : NULL, 
            "roundid" => isset($params['roundid']) ? $params['roundid'] : NULL, 
            "amount" => isset($params['amount']) ? $params['amount'] : NULL, 
            "jackpot" => isset($params['jackpot']) ? $params['jackpot'] : NULL, 
            "refund" => isset($params['refund']) ? $params['refund'] : NULL, 
            "currency" => isset($params['currency']) ? $params['currency'] : NULL, 
            "bonuscount" => isset($params['bonuscount']) ? $params['bonuscount'] : NULL, 

            #sbe default
            "json_request" => $this->requestBody,
            "sbe_status" => isset($params['sbe_status']) ? $params['sbe_status'] : NULL, 
            "before_balance" => isset($params['before_balance']) ? $params['before_balance'] : NULL, 
            "after_balance" => isset($params['after_balance']) ? $params['after_balance'] : NULL, 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000), 
            "request_id" => $this->utils->getRequestId(), 
            "external_uniqueid" => isset($params['transactionid']) ? $params['transactionid'] : NULL,
            "md5_sum" => $this->original_seamless_wallet_transactions->generateMD5SumOneRow($params, ['roundid', 'transactionid', 'sbe_status'], ['amount']),
            "bet_amount" => isset($params['bet_amount']) ? $params['bet_amount'] : NULL, 
            "payout_amount" => isset($params['payout_amount']) ? $params['payout_amount'] : NULL, 

            #remote wallet
            "remote_wallet_status" => $this->remote_wallet_status,
            "is_failed" => isset($params['is_failed']) ? $params['is_failed'] : NULL,
            "seamless_service_unique_id" => isset($params['seamless_service_unique_id']) ? $params['seamless_service_unique_id'] : NULL,
        );
        if(isset($params['external_uniqueid'])){
            $dataToInsert['external_uniqueid'] = $params['external_uniqueid'];
        }
        $transId = $this->original_seamless_wallet_transactions->insertTransactionData($this->api->getTransactionsTable(), $dataToInsert);
        return $transId;
    }

    private function modifyTransactionId($id, $created_at = null){
        $monthStr = date('Ym');
        if(!empty($created_at)){
           $monthStr =date('Ym', strtotime($created_at));
        }
        return "{$monthStr}-{$id}";
    }

    #GP Debit
    private function debit($request){
        $userid = isset($request['auth']['userid']) ? $request['auth']['userid'] : null;
        $data = isset($request['data']) ? $request['data'] : [];
        if(empty($data)){
            throw new Exception(__LINE__.":Empty data.", self::ERROR_CODE_FAIL);
        }

        $transactionid = isset($data['transactionid']) ? $data['transactionid'] : null;
        if(empty($transactionid)){
            throw new Exception(__LINE__.":Empty transactionid.", self::ERROR_CODE_FAIL);
        }

        $roundid = isset($data['roundid']) ? $data['roundid'] : null;
        if(empty($roundid)){
            throw new Exception(__LINE__.":Empty roundid.", self::ERROR_CODE_FAIL);
        }

        $roundDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['roundid'=> $roundid, 'player_id' => $this->playerId, 'trans_type' => 'debit'],['id', 'transactionid', 'after_balance', 'currency', 'created_at']);
        if(empty($roundDetails) && $this->checkPreviousMonth){
            $roundDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['roundid'=> $roundid, 'player_id' => $this->playerId, 'trans_type' => 'debit'],['id', 'transactionid', 'after_balance', 'currency', 'created_at']);
        }

        if(!empty($roundDetails) && $transactionid == $roundDetails['transactionid']){
            return [self::ERROR_CODE_SUCCESS, array("data" => [
                "transactionid" => $this->modifyTransactionId($roundDetails['id'], $roundDetails['created_at']),
                "balance" => (float)$this->api->dBtoGameAmount($roundDetails['after_balance']),
                "currency" => $roundDetails['currency'],
                ]),
                null
            ];  
        }

        if(!empty($roundDetails) && $transactionid != $roundDetails['transactionid']){
            throw new Exception(__LINE__.":Debit exist for this round.", self::ERROR_CODE_FAIL);  
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";
        $response = [];
        $data['userid'] = $userid;

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($data, &$response, &$errorCode, &$errorMessage) {
            $amount = isset($data['amount']) ? $this->api->gameAmountToDB($data['amount']) : null;
            $uniqueid = isset($data['transactionid']) ? $data['transactionid'] : null;
            $roundid = isset($data['roundid']) ? $data['roundid'] : null;
            $useReadonly = false;
            $beforeBalance = $this->getPlayerBalance($this->playerId, $useReadonly);
            $afterBalance = null;
            if($beforeBalance === false){
                $errorMessage = "Before balance false.";
                return false;
            }

            if($this->remoteWalletEnabled){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $data['seamless_service_unique_id'] = $uniqueIdOfSeamlessService;

                if(method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType("bet");
                }

                if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                    $this->wallet_model->setGameProviderRoundId($roundid);
                }

                if(method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
                    $this->wallet_model->setGameProviderIsEndRound(false);
                }

                if (method_exists($this->wallet_model, 'setExternalGameId')) {
                    if(isset($data['gameidentity'])){
                        $gameIdentity = $data['gameidentity'];
                        $gameIdentityArray = explode('-', $gameIdentity);
                        $gameId = isset($gameIdentityArray[1]) ? $gameIdentityArray[1] : null;
                        $this->wallet_model->setExternalGameId($gameId);
                    }
                }
            }

            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $amount = abs($amount);
                if($this->utils->compareResultFloat($amount, '>', $beforeBalance)) {
                    $errorCode = self::ERROR_USER_BALANCE_INSUFFICIENT;
                    $errorMessage = "Insufficient balance.";
                    return false;
                }

                $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                if(!$success){
                    $errorMessage = "Decrease balance encounter error. Amount equal to {$amount}.";
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
                if($this->remoteWalletEnabled){
                    $success = $this->wallet_model->decSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    if(!$success){
                        $errorMessage = "Decrease balance encounter error. Amount equal to 0.";
                    }
                }
            } else { #default error
                $errorMessage = "Amount < 0.";
                $success = false;
            }

            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
            if(!$success){
                if($this->ssa_remote_wallet_error_double_unique_id()){
                    $success =  true;
                } else {
                    $success = false;
                }
                // $data['is_failed'] = true;
                // return false;
            }
 
            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance($this->playerId, $useReadonly);
                    if($afterBalance === false){
                        $errorMessage = "After balance false.";
                        return false;
                    }
                }

                $data['before_balance'] = $beforeBalance;
                $data['after_balance'] = $afterBalance;
                $data['sbe_status'] = GAME_LOGS::STATUS_PENDING;
                $data['bet_amount'] = $amount;
                $data['payout_amount'] = 0;
                $transId = $this->processRequestData($data);
                if($transId){
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = array( "data" => [
                        "transactionid" => $this->modifyTransactionId($transId), 
                        "balance" => (float)$afterBalance,
                        "currency" => $data['currency']
                    ]);
                }
            }
            return $success;
        });

        if(!$success){
            $this->saveFailedTransactions($data);
        }

        return [$errorCode, $response, $errorMessage];
    }

    #GP Credit
    private function credit($request){
        $userid = isset($request['auth']['userid']) ? $request['auth']['userid'] : null;
        $data = isset($request['data']) ? $request['data'] : [];
        if(empty($data)){
            throw new Exception(__LINE__.":Empty data.", self::ERROR_CODE_FAIL);
        }

        $transactionid = isset($data['transactionid']) ? $data['transactionid'] : null;
        if(empty($transactionid)){
            throw new Exception(__LINE__.":Empty transactionid.", self::ERROR_CODE_FAIL);
        }

        $roundid = isset($data['roundid']) ? $data['roundid'] : null;
        if(empty($roundid)){
            throw new Exception(__LINE__.":Empty roundid.", self::ERROR_CODE_FAIL);
        }

        #get all round data for current month
        $roundDetails = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->api->getTransactionsTable(), ['roundid'=> $roundid, 'player_id' => $this->playerId],['id', 'transactionid', 'trans_type', 'after_balance', 'currency', 'created_at', 'external_uniqueid', 'amount']);
        if($this->checkPreviousMonth){
            #get all round data for previous month
            $previousRoundDetails = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->api->getTransactionsPreviousTable(), ['roundid'=> $roundid, 'player_id' => $this->playerId],['id', 'transactionid', 'trans_type', 'after_balance', 'currency', 'created_at', 'external_uniqueid', 'amount']);
            $roundDetails = array_values(array_merge($previousRoundDetails, $roundDetails));
        }

        $debitKey = array_search('debit', array_column($roundDetails, 'trans_type'));
        if($debitKey === false){
            throw new Exception(__LINE__.":Debit not found for this round.", self::ERROR_CODE_FAIL);
        }

        $betAmount = null;
        if(isset($roundDetails[$debitKey]['amount'])){
            $betAmount = $roundDetails[$debitKey]['amount'];
        }

        $betExternalUniqueId = null;
        if(isset($roundDetails[$debitKey]['external_uniqueid'])){
            $betExternalUniqueId = "game-{$this->api->getPlatformCode()}-{$roundDetails[$debitKey]['external_uniqueid']}";
        }

        $creditKey = array_search('credit', array_column($roundDetails, 'trans_type'));
        if($creditKey !== false && isset($roundDetails[$creditKey])){
            $creditDetails = $roundDetails[$creditKey];
            if($creditDetails['transactionid'] == $transactionid){
                return [
                    self::ERROR_CODE_SUCCESS, 
                    array("data" => [
                        "transactionid" => $this->modifyTransactionId($creditDetails['id'], $creditDetails['created_at']),
                        "balance" => (float)$this->api->dBtoGameAmount($creditDetails['after_balance']),
                        "currency" => $creditDetails['currency'],
                    ]),
                    null
                ];
            } else {
                throw new Exception(__LINE__.":Credit exist for this round.", self::ERROR_CODE_FAIL);
            }
        }

        $cancelKey = array_search('cancel', array_column($roundDetails, 'trans_type'));
        if($cancelKey !== false && isset($roundDetails[$cancelKey])){
            throw new Exception(__LINE__.":Round already canceled.", self::ERROR_CODE_FAIL);
        }

        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";
        $response = [];
        $data['userid'] = $userid;
        
        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($data, &$response, &$errorCode, &$errorMessage, $betExternalUniqueId, $betAmount) {
            $amount = isset($data['amount']) ? $this->api->gameAmountToDB($data['amount']) : null;
            $uniqueid = isset($data['transactionid']) ? $data['transactionid'] : null;
            $roundid = isset($data['roundid']) ? $data['roundid'] : null;
            $useReadonly = false;
            $beforeBalance = $this->getPlayerBalance($this->playerId, $useReadonly);
            $afterBalance = null;
            if($beforeBalance === false){
                $errorMessage = "Before balance false.";
                return false;
            }

            if($this->remoteWalletEnabled){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $data['seamless_service_unique_id'] = $uniqueIdOfSeamlessService;

                if(method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
                }

                if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                    $this->wallet_model->setGameProviderRoundId($roundid);
                }

                if(method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
                    $this->wallet_model->setGameProviderIsEndRound(true);
                }

                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($betExternalUniqueId);
                }

                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                }

                if (method_exists($this->wallet_model, 'setExternalGameId')) {
                    if(isset($data['gameidentity'])){
                        $gameIdentity = $data['gameidentity'];
                        $gameIdentityArray = explode('-', $gameIdentity);
                        $gameId = isset($gameIdentityArray[1]) ? $gameIdentityArray[1] : null;
                        $this->wallet_model->setExternalGameId($gameId);
                    }
                }
            }

            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $amount = abs($amount);
                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                if(!$success){
                    $errorMessage = "Increase balance encounter error. Amount equal to {$amount}.";
                }
            } elseif ($this->utils->compareResultFloat($amount, '=', 0)) {
                $success = true;#allowed amount 0
                if($this->remoteWalletEnabled){
                    $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                    if(!$success){
                        $errorMessage = "Increase balance encounter error. Amount equal to 0.";
                    }
                }
            } else { #default error
                $errorMessage = "Amount < 0.";
                $success = false;
            }

            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
            if(!$success){
                if($this->ssa_remote_wallet_error_double_unique_id()){
                    $success =  true;
                } else {
                    $success = false;
                }
                // $data['is_failed'] = true;
                // return false;
            }
 
            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance($this->playerId, $useReadonly);
                    if($afterBalance === false){
                        $errorMessage = "After balance false.";
                        return false;
                    }
                }

                $data['before_balance'] = $beforeBalance;
                $data['after_balance'] = $afterBalance;
                $data['sbe_status'] = GAME_LOGS::STATUS_SETTLED;
                $data['bet_amount'] = $betAmount;
                $data['payout_amount'] = $amount;
                $transId = $this->processRequestData($data);
                if($transId){
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = array( "data" => [
                        "transactionid" => $this->modifyTransactionId($transId), 
                        "balance" => (float)$afterBalance,
                        "currency" => $data['currency']
                    ]);
                }
            }
            return $success;
        });

        if(!$success){
            $this->saveFailedTransactions($data);
        }

        return [$errorCode, $response, $errorMessage];
    }

    #GP Cancel
    private function cancel($request){
        $userid = isset($request['auth']['userid']) ? $request['auth']['userid'] : null;
        $data = isset($request['data']) ? $request['data'] : [];
        if(empty($data)){
            throw new Exception(__LINE__.":Empty data.", self::ERROR_CODE_FAIL);
        }

        $transactionid = isset($data['transactionid']) ? $data['transactionid'] : null;
        if(empty($transactionid)){
            throw new Exception(__LINE__.":Empty transactionid.", self::ERROR_CODE_FAIL);
        }

        $transDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsTable(), ['external_uniqueid'=> $transactionid, 'trans_type' => 'debit'],['userid', 'transactionid', 'roundid ', 'amount', 'currency', 'after_balance', 'id', 'created_at', 'gameidentity']);
        if(empty($transDetails) && $this->checkPreviousMonth){
            $transDetails = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->api->getTransactionsPreviousTable(), ['external_uniqueid'=> $transactionid, 'trans_type' => 'debit'],['userid', 'transactionid', 'roundid ', 'amount', 'currency', 'after_balance', 'id', 'created_at', 'gameidentity']);
            if(empty($transDetails)){
                throw new Exception(__LINE__.":Invalid transaction.", self::ERROR_CODE_FAIL);
            }
        }

        if(empty($transDetails)){
            throw new Exception(__LINE__.":Invalid transaction.", self::ERROR_CODE_FAIL);
        }

        $roundid = isset($transDetails['roundid']) ? $transDetails['roundid'] : null;
        #get all round data for current month
        $roundDetails = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->api->getTransactionsTable(), ['roundid'=> $roundid, 'player_id' => $this->playerId],['id', 'transactionid', 'trans_type', 'after_balance', 'currency', 'created_at']);
        if($this->checkPreviousMonth){
            #get all round data for previous month
            $previousRoundDetails = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->api->getTransactionsPreviousTable(), ['roundid'=> $roundid, 'player_id' => $this->playerId],['id', 'transactionid', 'trans_type', 'after_balance', 'currency', 'created_at']);
            $roundDetails = array_values(array_merge($previousRoundDetails, $roundDetails));
        }

        $cancelKey = array_search('cancel', array_column($roundDetails, 'trans_type'));
        if($cancelKey !== false && isset($roundDetails[$cancelKey])){
            $cancelDetails = $roundDetails[$cancelKey];
            return [
                self::ERROR_CODE_SUCCESS, 
                array("data" => [
                    "transactionid" => $this->modifyTransactionId($cancelDetails['id'], $cancelDetails['created_at']),
                    "balance" => (float)$this->api->dBtoGameAmount($cancelDetails['after_balance']),
                    "currency" => $cancelDetails['currency'],
                ]),
                null
            ];
        }

        $creditKey = array_search('credit', array_column($roundDetails, 'trans_type'));
        if($creditKey !== false && isset($roundDetails[$creditKey])){
            throw new Exception(__LINE__.":Round already settled.", self::ERROR_CODE_FAIL);
        }



        $errorCode = self::ERROR_CODE_SYSTEM_ERROR;
        $errorMessage = "Internal System Error";
        $response = [];
        $data = $transDetails;
        $data['userid'] = $userid;
        $data['external_uniqueid'] = 'cancel-'.$transactionid;
        $amount = isset($data['amount']) ? $data['amount'] : null;

        if($this->utils->compareResultFloat($amount, '<=', 0)){
            throw new Exception(__LINE__.":Invalid debit amount for cancel.", self::ERROR_CODE_FAIL);
        }

        $success = $this->lockAndTransForPlayerBalance($this->playerId, function() use($data, &$response, &$errorCode, &$errorMessage) {
            $amount = isset($data['amount']) ? $data['amount'] : null;
            $uniqueid = isset($data['external_uniqueid']) ? $data['external_uniqueid'] : null;
            $transactionid = isset($data['transactionid']) ? $data['transactionid'] : null;
            $betExternalUniqueId = "game-{$this->api->getPlatformCode()}-{$transactionid}";
            $useReadonly = false;
            $beforeBalance = $this->getPlayerBalance($this->playerId, $useReadonly);
            $afterBalance = null;
            if($beforeBalance === false){
                $errorMessage = "Before balance false.";
                return false;
            }

            if($this->remoteWalletEnabled){
                $uniqueIdOfSeamlessService=$this->api->getPlatformCode().'-'.$uniqueid;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueIdOfSeamlessService);
                $data['seamless_service_unique_id'] = $uniqueIdOfSeamlessService;

                if(method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType("payout");
                }

                if(method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                    $this->wallet_model->setGameProviderRoundId($roundid);
                }

                if(method_exists($this->wallet_model, 'setGameProviderIsEndRound') ) {
                    $this->wallet_model->setGameProviderIsEndRound(true);
                }

                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($betExternalUniqueId);
                }
                
                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                }

                if (method_exists($this->wallet_model, 'setExternalGameId')) {
                    if(isset($data['gameidentity'])){
                        $gameIdentity = $data['gameidentity'];
                        $gameIdentityArray = explode('-', $gameIdentity);
                        $gameId = isset($gameIdentityArray[1]) ? $gameIdentityArray[1] : null;
                        $this->wallet_model->setExternalGameId($gameId);
                    }
                }
            }

            if($this->utils->compareResultFloat($amount, '>', 0)) {
                $amount = abs($amount);
                $success = $this->wallet_model->incSubWallet($this->playerId, $this->api->getPlatformCode(), $amount, $afterBalance);
                if(!$success){
                    $errorMessage = "Increase balance encounter error. Amount equal to {$amount}.";
                }
            } else { #default error
                $errorMessage = "Invalid debit amount for cancel.";
                $errorCode = self::ERROR_CODE_FAIL;
                $success = false;
            }

            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
            if(!$success){
                if($this->ssa_remote_wallet_error_double_unique_id()){
                    $success =  true;
                } else {
                    $success = false;
                }
                // $data['is_failed'] = true;
                // return false;
            }
 
            if($success){
                $success = false; #reset $success
                if(is_null($afterBalance)){
                    $afterBalance = $this->getPlayerBalance($this->playerId, $useReadonly);
                    if($afterBalance === false){
                        $errorMessage = "After balance false.";
                        return false;
                    }
                }

                $data['before_balance'] = $beforeBalance;
                $data['after_balance'] = $afterBalance;
                $data['sbe_status'] = GAME_LOGS::STATUS_CANCELLED;
                $data['bet_amount'] = $amount;
                $data['payout_amount'] = $amount;
                $transId = $this->processRequestData($data);
                if($transId){
                    $success = true;
                    $errorCode = self::ERROR_CODE_SUCCESS;
                    $response = array( "data" => [
                        "transactionid" => $this->modifyTransactionId($transId), 
                        "balance" => (float)$afterBalance,
                        "currency" => $data['currency']
                    ]);
                }
            }
            return $success;
        });

        if(!$success){
            $this->saveFailedTransactions($data);
        }

        return [$errorCode, $response, $errorMessage];
    }

    private function saveFailedTransactions($data){
        #save failed transaction
        $this->load->model(['failed_seamless_transactions']);
        $failedTrans = [
            'transaction_id'=> isset($data['transactionid']) ? $data['transactionid'] : null,
            'round_id'=> isset($data['roundid']) ?$data['roundid'] : null,
            'external_game_id'=> isset($data['gameidentity']) ? $data['gameidentity'] : null,
            'player_id'=> $this->playerId,
            'game_username'=> isset($this->playerDetails['game_username']) ? $this->playerDetails['game_username'] : null,
            'amount'=> isset($data['amount']) ? abs($data['amount']) : null,
            'balance_adjustment_type'=> $this->method == 'debit' ? 'decrease' : 'increase',
            'action'=> $this->method,
            'game_platform_id'=> $this->gamePlatformId,
            'transaction_raw_data'=> $this->requestBody,
            'remote_raw_data'=> $this->wallet_model->getRemoteApiParams(),
            'external_uniqueid'=> isset($data['external_uniqueid']) ? $data['external_uniqueid'] : $data['transactionid'],
            'remote_wallet_status'=> $this->remote_wallet_status,
            'transaction_date'=> $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'created_at'=> $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'updated_at'=> $this->utils->getNowDateTime()->format('Y-m-d H:i:s'),
            'request_id'=> $this->utils->getRequestId(),
            'headers'=> json_encode($this->requestHeaders),
            'full_url'=> $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
        ];
        $monthStr = date('Ym');
        $failedTransSave = $this->failed_seamless_transactions->insertTransaction($failedTrans, "failed_remote_common_seamless_transactions_{$monthStr}");
        $this->utils->debug_log("WMSS: ({$this->method})", "failedTransSave", $failedTransSave, "failedTrans", $failedTrans); 
    }

    #GP Cash balance
    private function balance($request){
        return[
                self::ERROR_CODE_SUCCESS,
                array(
                    "data" => array(
                        "amount" => $this->api->dBtoGameAmount($this->getPlayerBalance()),
                        "currency" => $this->api->currency,
                    )
                ),
                null
            ];
    }

    #GP token validation
    private  function auth($request){
        if(!empty($this->playerDetails)){
            return[
                self::ERROR_CODE_SUCCESS,
                array(
                    "data" => array(
                        "userid" => $this->playerDetails['game_username'],
                        "token" => isset($this->playerDetails['token']) ? $this->playerDetails['token'] : null,
                        "username" => $this->playerDetails['game_username'],
                        "licensee" => $this->api->licensee_token,
                        "skin" => $this->api->skin,
                        "language" => $this->api->language,
                        "currency" => $this->api->currency,
                    )
                ),
                null
            ];
        }
        return [self::ERROR_CODE_FAIL, []];
    }

    #Function to validate params
    private function validateParams($request){
        if(isset($request['data']['currency'])){
            $currency = strtolower($request['data']['currency']);
            if(strtolower($this->api->currency) != $currency){
                throw new Exception(__LINE__.":Invalid data(currency).", self::ERROR_CODE_FAIL);
            }
        }

        if(isset($request['data']['amount'])){
            $amount = $request['data']['amount'];
            if(!is_null($amount) && !$this->isValidAmount($amount)){
                throw new Exception(__LINE__.":Invalid data(amount).", self::ERROR_CODE_FAIL);
            }
        }

        if(isset($request['data']['jackpot'])){
            $jackpot = $request['data']['jackpot'];
            if(!is_null($jackpot) && !$this->isValidAmount($jackpot)){
                throw new Exception(__LINE__.":Invalid data(jackpot).", self::ERROR_CODE_FAIL);
            }
        }
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

    #Function for player authentication
    private function authenticate($request){
        if(empty($request)){
            throw new Exception(__LINE__.":Empty request.", self::ERROR_CODE_FAIL);
        }

        $authRequest = isset($request['auth']) ? $request['auth'] : $request;

        $gameUsername = isset($authRequest['userid']) ? $authRequest['userid'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty userid.", self::ERROR_CODE_FAIL); 
        }

        $token = isset($authRequest['token']) ? $authRequest['token'] : null;
        if(empty($gameUsername)){
            throw new Exception(__LINE__.":Empty token.", self::ERROR_CODE_FAIL); 
        }

        $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsernameAndToken($gameUsername, $token, $this->getGamePlatformId());
        if(in_array($this->method, self::FUNCTION_IGNORE_TOKEN_VALIDATION) || in_array($gameUsername, $this->automation_tester_game_account)){
            #ignore token validation on cancel and credit
            $playerDetails = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->getGamePlatformId());
            if(empty($playerDetails)){
                throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_PLAYER_NOT_FOUND); 
            }
        }

        if(empty($playerDetails)){
            throw new Exception(__LINE__.":Player token not found.", self::ERROR_CODE_PLAYER_TOKEN_NOT_FOUND); 
        }
        $this->playerDetails = $playerDetails;

        $playerId = isset($playerDetails['player_id']) ? $playerDetails['player_id'] : null;
        if(empty($playerId)){
            throw new Exception(__LINE__.":Player not found.", self::ERROR_CODE_PLAYER_NOT_FOUND); 
        }
        $this->playerId = $playerId;

        if($this->api->isBlockedUsernameInDB($gameUsername) || $this->player_model->isBlocked($this->playerId)){
            throw new Exception(__LINE__.":Player is blocked.", self::ERROR_CODE_PLAYER_BLOCKED);
        }
        return $playerId;
    }

    //Function to merge default ouput and response
    private function setResponse($errorCode, $response = [], $errorMessage = "") {
        $defaultResponse = [
            "result" => $errorCode,
            "message" => $errorMessage,
        ];

        if(isset($response['data'])){
            $defaultResponse['data'] = $response['data'];
        }

        if($errorCode == self::ERROR_CODE_SUCCESS){
            $defaultResponse['message'] = "OK";
        }

        return $this->setOutput($errorCode, $defaultResponse);
    }

    //Function to return output and save response and request
    private function setOutput($errorCode, $response = []){
        $addOrigin = true;
        $origin = "*";
        $pretty = false;
        $partialOutputOnError = false;
        $statusCode = 0;

        $extraFields = [
            "full_url" => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI'])
        ];
        
        if($this->playerId){
            $extraFields = [
                'player_id'=> $this->playerId
            ];
        }

        $flag = $errorCode == self::ERROR_CODE_SUCCESS ?  Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $dontSaveResponseInApi = false;
        $externalRequestId = null;
        $costMs = intval($this->utils->getExecutionTimeToNow()*1000);
        
        $responseResultId = $this->response_result->saveResponseResult(
            $this->gamePlatformId,
            $flag,
            $this->method,
            json_encode($this->requestBody),
            $response,
            200,
            null,
            is_array($this->requestHeaders) ? json_encode($this->requestHeaders) : $this->requestHeaders,
            $extraFields,
            $dontSaveResponseInApi,
            $externalRequestId,
            $costMs
        );
        return $this->returnJsonResult((object)$response, $addOrigin, $origin, $pretty, $partialOutputOnError, $statusCode);
    }

    //Function to get balance of exist player
    private function getPlayerBalance($playerId = null, $useReadonly = true){
        $playerId = $playerId ? $playerId : $this->playerId;
        if($playerId){
            return $this->player_model->getPlayerSubWalletBalance($playerId, $this->gamePlatformId, $useReadonly);
        } else {
            return false;
        }
    }
}

