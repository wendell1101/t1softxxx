<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class ibc_onebook_service_api extends BaseController {

    const RETURN_OK = [
        'status' => 0
    ];

    const ERROR_BET_NOT_FOUND = [
        'status' => 309,
        'msg' => 'Invalid Transaction Status'
    ];

    const ERROR_INSUFFICIENT_BALANCE = [
        'status' => 502,
        'msg' => 'Player Has Insufficient Funds'
    ];

    const ERROR_NOT_FOUND_PLAYER = [
        'status' => 203,
        'msg' => 'Account Is Not Exist'
    ];

    const ERROR_BET_NOT_ALLOWED = [
        'status' => 307,
        'msg' => 'Invalid Amount'
    ];

    const ERROR_NO_SUCH_TICKET = [
        'status' => 504,
        'msg' => 'No Such Ticket'
    ];

    const ERROR_INVALID_AUTHENTICATION_KEY = [
        'status' => 311,
        'msg' => 'Invalid Authentication Key'
    ];

    const ERROR_DATABASE_ERROR = [
        'status' => 901,
        'msg' => 'Database Error'
    ];

    const ERROR_BAD_REQUEST = [
        'status' => 101,
        'msg' => 'Parameter(s) Incorrect'
    ];

    const ERROR_DISABLED_API = [
        'status' => 903,
        'msg' => 'System Is Under Maintenance'
    ];

    const SYSTEM_ERROR = [
        'status' => 999,
        'msg' => 'System Error'
    ];

    const ERROR_ACCOUNT_LOCKED = [
        'status' => 202,
        'msg' => 'Account Is Locked'
    ];

    const ERROR_IP_ADDRESS_NOT_ALLOWED = [
        'status' => 503,
        'msg' => 'The IP address Is Restricted'
    ];

    const WHITELIST_METHODS = [
        'settle',
    ];

    private $requestParams;
    private $api;
    public $currentPlayer = null;
    public $errorCode = null;
    public $wallet_transaction_list = [];
    private $headers;
    private $http_status_code;

    public function __construct() {
        parent::__construct();
        $this->load->model(array('common_token', 'common_seamless_wallet_transactions', 'original_seamless_wallet_transactions', 'game_logs'));
        $this->headers = getallheaders();
        $this->http_status_code = 200;
    }

    public function index($api = null, $method = null) {
        $params = file_get_contents("php://input");
        if(is_string($params) && is_array(json_decode($params, true))){
            $params = json_decode(file_get_contents("php://input")) ?: [];
        } else {
            $decode = gzdecode($params);
            $params = json_decode($decode);
        }
        $this->utils->debug_log('IBC ONEBOOK SEAMLESS API (preProcessRequest)', $params);
        $this->requestParams = new stdClass();
        $this->requestParams->function = $method;
        $this->requestParams->params = $params;
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->returnJsonResult(self::ERROR_BAD_REQUEST);
        }

        if (!$this->api->validateWhiteIP()) {
            $this->http_status_code = 401;
            $this->responseResultId = $this->setResponseResult();
            return $this->setResponse(self::ERROR_IP_ADDRESS_NOT_ALLOWED);
        }

        $this->responseResultId = $this->setResponseResult();
        if(!$this->responseResultId){
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
        }

        if($this->api->isMaintenance() || $this->api->isDisabled()) {
            if(!in_array($method, self::WHITELIST_METHODS)) {
                return $this->setResponse(self::ERROR_DISABLED_API);
            }
        }

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        return $this->$method();
    }

    private function preProcessRequest($functionName, $rule_set = []) {
        $this->requestParams->function = $functionName;
        $params = $this->requestParams->params;
        if(!isset($params->key) || (isset($params->key) && $this->api->key != $params->key)) {
            $this->errorCode = self::ERROR_INVALID_AUTHENTICATION_KEY;
            return false;
        }

        $isValid = $this->validateRequest($rule_set);
        if(!$isValid) {
            $this->errorCode = self::ERROR_BAD_REQUEST;
            return false;
        }

        if(isset($params->message->userId)) {
            $this->currentPlayer = $this->common_token->getPlayerCompleteDetailsByGameUsername($params->message->userId, $this->api->getPlatformCode());
            if(!$this->currentPlayer) {
                $this->errorCode = self::ERROR_NOT_FOUND_PLAYER;
                return false;
            }

            if($this->api->isBlockedUsernameInDB($params->message->userId)){
                $this->errorCode = self::ERROR_ACCOUNT_LOCKED;
                return false;
            }
        }

        return true;
    }

    private function processRequestData($transData){
        $request = $this->requestParams->params;
        $uniqueId = $transData['uniqueId'];
        $dataToInsert = array(
            ### data generated from SBE(converted amount)
            "game_platform_id" => $this->api->getPlatformCode(),
            "amount" => isset($transData['amount']) ? $transData['amount'] : NULL,
            "before_balance" => isset($transData['beforeBalance']) ? $transData['beforeBalance'] : NULL,
            "after_balance" => isset($transData['afterbalance']) ? $transData['afterbalance'] : NULL,
            "player_id" => isset($transData['player_id']) ? $transData['player_id'] : NULL,
            "transaction_type" => isset($transData['transactionType']) ? $transData['transactionType'] : NULL,
            "response_result_id" => $this->responseResultId,
            #for transaction
            "bet_amount" => isset($transData['betAmount']) ? $transData['betAmount'] : NULL,
            "result_amount" => isset($transData['resultAmount']) ? $transData['resultAmount'] : NULL,
            "status" => isset($transData['status']) ? $transData['status'] : NULL,

            ##request data from provider
            "game_id" => isset($request->message->sportType) ? $request->message->sportType : NULL,
            "external_unique_id" => $uniqueId,
            "extra_info" => json_encode($request), #actual request
            "round_id" => isset($request->message->refId) ? $request->message->refId : NULL,
            "transaction_id" => isset($request->message->refId) ? $request->message->refId : NULL, 

            "start_at" => isset($request->message->betTime) ? $this->api->gameTimeToServerTime($request->message->betTime) : null, 
            "end_at" => isset($request->message->betTime) ? $this->api->gameTimeToServerTime($request->message->betTime) : null, 
            "elapsed_time" => intval($this->utils->getExecutionTimeToNow()*1000),
        );

        if(isset($transData['transaction_id'])){
            $dataToInsert['transaction_id'] = $transData['transaction_id'];
            $dataToInsert['round_id'] = $transData['transaction_id'];
        }

        if(isset($transData['start_at'])){
            $dataToInsert['start_at'] = $transData['start_at'];
        }

        if(isset($transData['end_at'])){
            $dataToInsert['end_at'] = $transData['end_at'];
        }

        if(isset($transData['game_id'])){
            $dataToInsert['game_id'] = $transData['game_id'];
        }

        $dataToInsert['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5Transaction($dataToInsert);
        $transId = $this->common_seamless_wallet_transactions->insertData('common_seamless_wallet_transactions',$dataToInsert);
        return $transId;
    }


    /*
    Get Balance
    Description:
        • Game provider is able to call this function that will be provided by operator.
        • Game provider need to check player latest balance regularly and display on the game site. 

    Method URL
    Provide by Operator.
        For Example: https://<Operator_API_SERVICE_URL>/getbalance
    */

    private function getbalance() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $balance = 0;
            $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$balance) {
                $balance = $this->getPlayerBalance();
                if($balance === false) {
                    $balance = 0;
                    return false;
                }
                return true;
            });

            if (!$success) {
                return $this->setResponse(self::SYSTEM_ERROR);
            }

            $data = [
                'user_id' => $this->currentPlayer->game_username,
                'balance' => $this->api->dBtoGameAmount($balance),
                'balanceTs' => $this->api->serverTimeToGameTime($this->utils->getNowForMysql())
            ];
            return $this->setResponse(self::RETURN_OK, $data);
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    /*
    Place Bet
    Description:
        Game provider is able to call this function that will be provided by operator, and send out the bet details to operator
        • Whenever the failed to place bet on Saba system, game provider will call the Cancel Bet API to cancel the reserve bet.
        • If doesn’t receive response, game provider will also call the Cancel Bet API to cancel the reserve bet.
        • The current status of the bet is unsettle.

    Method URL
    Provide by Operator.
        For Example: https://<Operator_API_SERVICE_URL>/placebet
    */
    private function placebet() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.userId' => 'required',
            'message.currency' => 'required',
            'message.matchId' => 'required',
            'message.homeId' => 'required',
            'message.awayId' => 'required',
            'message.homeName' => 'required',
            'message.awayName' => 'required',
            'message.kickOffTime' => 'required',
            'message.betTime' => 'required',
            'message.betAmount' => 'required|numeric>0',
            'message.actualAmount' => 'required|numeric>0',
            'message.sportType' => 'required',
            'message.sportTypeName' => 'required',
            'message.betType' => 'required',
            'message.betTypeName' => 'required',
            'message.oddsType' => 'required',
            'message.oddsId' => 'required',
            'message.odds' => 'required',
            'message.betChoice' => 'required',
            'message.updateTime' => 'required',
            'message.leagueId' => 'required',
            'message.leagueName' => 'required',
            'message.IP' => 'required',
            'message.isLive' => 'required',
            'message.refId' => 'required',
            'message.tsId' => 'required',
            'message.creditAmount' => 'required|numeric>0',
            'message.debitAmount' => 'required|numeric>0',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->requestParams->params->message->refId, 'external_unique_id');
            if($transactions){
                $response = array(
                    "refId" => $transactions->transaction_id,
                    "licenseeTxId" => (int)$transactions->id, 
                );
                return $this->setResponse(self::RETURN_OK, $response);
            }

            $errorCode = self::SYSTEM_ERROR;
            $response = [];
            $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, &$response) {
                
                $uniqueidForRemote=$this->api->getPlatformCode().'-'.$this->requestParams->params->message->refId;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                
                $success = false; #default
                $debitAmount = $this->api->gameAmountToDB($this->requestParams->params->message->debitAmount);
                $beforeBalance = $this->getPlayerBalance();
                $afterBalance = null;
                if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                    if($this->utils->compareResultFloat($debitAmount, '>', 0)) {
                        $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                        $this->wallet_model->setGameProviderRoundId($this->requestParams->params->message->refId);
                        $this->wallet_model->setGameProviderBetAmount($debitAmount);
                        $this->wallet_model->setGameProviderIsEndRound(false);
                        $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                        $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $debitAmount, $afterBalance);
                        if(!$success){
                            $errorCode = self::SYSTEM_ERROR;
                        }
                    } elseif ($this->utils->compareResultFloat($debitAmount, '=', 0)) {
                        $success = true;#allowed amount 0
                        $afterBalance = $beforeBalance;
                    } else { #default error
                        $success = false;
                    }
                } else {
                    $success = false;
                    $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                }
                
                // $success = true;
                if($success){
                    $success = false; #reset $success
                    if(is_null($afterBalance)){
                        $afterBalance = $this->getPlayerBalance();
                        if($afterBalance === false){
                            return false;
                        }
                    }
                    $transData['beforeBalance'] = $beforeBalance;
                    $transData['afterbalance'] = $afterBalance;
                    $transData['transactionType'] = "placebet";
                    $transData['player_id'] = $this->currentPlayer->player_id;
                    $transData['amount'] = $debitAmount;
                    $transData['betAmount'] = $debitAmount;
                    $transData['resultAmount'] = -$debitAmount;
                    $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                    $transData['uniqueId'] = $this->requestParams->params->message->refId;

                    $transId = $this->processRequestData($transData);
                    if($transId){
                        $success = true;
                        $errorCode = self::RETURN_OK;
                        $response = array(
                            "refId" => $this->requestParams->params->message->refId,
                            "licenseeTxId" => $transId, 
                        );
                    }
                }
                return$success;
            });
            if($success){
                return $this->setResponse($errorCode, $response);
            } else {
                return $this->setResponse($errorCode);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function confirmbet() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.userId' => 'required',
            'message.updateTime' => 'required',
            'message.txns' => 'required',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $ids = array_column($txns, 'licenseeTxId');
            $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $ids, 'id');
            $transactions = json_decode(json_encode($transactions), true);
            if(!empty($transactions) && count($transactions) == count($ids)) {
                $txnsOddsChanged = array_values(array_filter($txns, function ($row) { return $row['isOddsChanged'];}));
                if(!empty($txnsOddsChanged)){ #process txn with odds changed
                    $errorCode = self::SYSTEM_ERROR;
                    $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $txnsOddsChanged, $transactions) {
                        foreach ($txnsOddsChanged as $key => $txnOddsChanged) {
                            $success = false;
                            $transKey = array_search($txnOddsChanged['refId'], array_column($transactions, 'external_unique_id'));
                            if(!is_int($transKey)){
                                $errorCode = self::ERROR_BET_NOT_FOUND;
                                break;
                            }
                            if($txnOddsChanged['creditAmount'] == 0 && $txnOddsChanged['debitAmount'] == 0){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                continue;
                            }
                            $operationId = $this->requestParams->params->message->operationId;
                            $uniqueId = $txnOddsChanged['refId'].'-'.$operationId;
                            $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
                            if($exist){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                continue;
                            } 
                            
                            $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                            $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT);
                            $this->wallet_model->setGameProviderRoundId($txnOddsChanged['refId']);
                            $this->wallet_model->setGameProviderIsEndRound(false);
                            $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);
                            
                            $creditAmount =  $this->api->gameAmountToDB($txnOddsChanged['creditAmount']);
                            $debitAmount = $this->api->gameAmountToDB($txnOddsChanged['debitAmount']);
                            $beforeBalance = $this->getPlayerBalance();
                            $afterBalance = null;
                            if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                                if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                                    $resultAmount = $creditAmount - $debitAmount;
                                    $amount = abs($resultAmount);
                                    if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                                        $success = $this->wallet_model->incSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                                        $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                        if(!$success){
                                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                        }
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                        $success = true;#allowed amount 0
                                        $afterBalance = $beforeBalance;
                                    } else { #default error
                                        $success = false;
                                    }
                                } else {
                                    $success = false;
                                    $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                }
                            }
                            
                            if($success){
                                $success = false; #reset $success
                                // $afterBalance = $this->getPlayerBalance();
                                if(is_null($afterBalance)){
                                    $afterBalance = $this->getPlayerBalance();
                                    if($afterBalance === false){
                                        return false;
                                    }
                                }
                                $transData['beforeBalance'] = $beforeBalance;
                                $transData['afterbalance'] = $afterBalance;
                                $transData['transactionType'] = "confirmbet";
                                $transData['player_id'] = $this->currentPlayer->player_id;
                                $transData['amount'] = $amount;
                                $transData['betAmount'] = $this->api->gameAmountToDB($txnOddsChanged['actualAmount']);
                                $transData['resultAmount'] = $this->api->gameAmountToDB(-$txnOddsChanged['actualAmount']);
                                $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                                $transData['uniqueId'] = $uniqueId;
                                $transData['start_at'] = $this->api->gameTimeToServerTime($this->requestParams->params->message->transactionTime);
                                $transData['end_at'] = $this->api->gameTimeToServerTime($this->requestParams->params->message->updateTime);
                                $transData['transaction_id'] = $txnOddsChanged['refId'];

                                $transId = $this->processRequestData($transData);
                                if($transId){
                                    $success = true;
                                    $errorCode = self::RETURN_OK;
                                }
                            }
                        }
                        return $success;
                    });
                    if($success){
                        return $this->setResponse($errorCode, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]);
                    } else {
                        return $this->setResponse($errorCode);
                    }
                } else {
                    return $this->setResponse(self::RETURN_OK, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]);
                }
            }
            else {
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function placebetparlay() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
            'message.currency' => 'required',
            'message.betTime' => 'required',
            'message.updateTime' => 'required',
            'message.totalBetAmount' => 'required',
            'message.IP' => 'required',
            'message.tsId' => 'required',
            'message.txns' => 'required',
            'message.ticketDetail' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $response = [];
            if(!empty($txns)){
                $success = false;
                // foreach ($txns as $key => $txn) {
                //     $errorCode = self::SYSTEM_ERROR;
                //     $success = false;
                //     $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $txn['refId'], 'external_unique_id');
                //     if($transactions){
                //         $response[] = array(
                //             "refId" => $transactions->transaction_id,
                //             "licenseeTxId" => (int)$transactions->id, 
                //         );
                //         $success = true;
                //         $errorCode = self::RETURN_OK;
                //         continue;
                //     } 

                //     $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $txn, &$response){
                //         $debitAmount = isset($txn['debitAmount']) ? ($txn['debitAmount']) : 0;
                //         $beforeBalance = $this->getPlayerBalance();
                //         $success = false;
                //         if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                //             if($this->utils->compareResultFloat($debitAmount, '>', 0)) {
                //                 $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $debitAmount);
                //                 if(!$success){
                //                     $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                //                 }
                //             } elseif ($this->utils->compareResultFloat($debitAmount, '=', 0)) {
                //                 $success = true;#allowed amount 0
                //             } else { #default error
                //                 $success = false;
                //             }
                //         } else {
                //             $success = false;
                //             $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                //         }
                //         if($success){
                //             $success = false; #reset $success
                //             $afterBalance = $this->getPlayerBalance();
                //             $transData['beforeBalance'] = $beforeBalance;
                //             $transData['afterbalance'] = $afterBalance;
                //             $transData['transactionType'] = "placebetparlay";
                //             $transData['player_id'] = $this->currentPlayer->player_id;
                //             $transData['amount'] = $debitAmount;
                //             $transData['betAmount'] = $debitAmount;
                //             $transData['resultAmount'] = -$debitAmount;
                //             $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                //             $transData['uniqueId'] = $txn['refId'];
                //             $transData['transaction_id'] = $txn['refId'];
                //             $transData['game_id'] = $txn['parlayType'];
                            

                //             $transId = $this->processRequestData($transData);
                //             if($transId){
                //                 $success = true;
                //                 $errorCode = self::RETURN_OK;
                //                 $response[] = array(
                //                     "refId" => $txn['refId'],
                //                     "licenseeTxId" => $transId, 
                //                 );
                //             }
                //         }
                //         return $success;
                //     });
                // }
                $errorCode = self::SYSTEM_ERROR;
                $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $txns, &$response){
                    foreach ($txns as $key => $txn) {
                        $errorCode = self::SYSTEM_ERROR;
                        $success = false;
                        $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $txn['refId'], 'external_unique_id');
                        if($transactions){
                            $response[] = array(
                                "refId" => $transactions->transaction_id,
                                "licenseeTxId" => (int)$transactions->id, 
                            );
                            $success = true;
                            $errorCode = self::RETURN_OK;
                            continue;
                        } 

                        
                        $debitAmount = isset($txn['debitAmount']) ? $this->api->gameAmountToDB($txn['debitAmount']) : 0;
                        $beforeBalance = $this->getPlayerBalance();
                        $afterBalance = null;
                        $success = false;
                        if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                            if($this->utils->compareResultFloat($debitAmount, '>', 0)) {
                                
                                $uniqueidForRemote=$this->api->getPlatformCode().'-'.$txn['refId'];       
                                $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                                $this->wallet_model->setGameProviderRoundId($txn['refId']);
                                $this->wallet_model->setGameProviderIsEndRound(false);
                                $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $debitAmount, $afterBalance);
                                if(!$success){
                                    $errorCode = self::SYSTEM_ERROR;
                                }
                            } elseif ($this->utils->compareResultFloat($debitAmount, '=', 0)) {
                                $success = true;#allowed amount 0
                                $afterBalance = $beforeBalance;
                            } else { #default error
                                $success = false;
                            }
                        } else {
                            $success = false;
                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                        }
                        if($success){
                            $success = false; #reset $success
                            // $afterBalance = $this->getPlayerBalance();
                            if(is_null($afterBalance)){
                                $afterBalance = $this->getPlayerBalance();
                                if($afterBalance === false){
                                    return false;
                                }
                            }
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = "placebetparlay";
                            $transData['player_id'] = $this->currentPlayer->player_id;
                            $transData['amount'] = $debitAmount;
                            $transData['betAmount'] = $debitAmount;
                            $transData['resultAmount'] = -$debitAmount;
                            $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                            $transData['uniqueId'] = $txn['refId'];
                            $transData['transaction_id'] = $txn['refId'];
                            $transData['game_id'] = $txn['parlayType'];
                            

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                $response[] = array(
                                    "refId" => $txn['refId'],
                                    "licenseeTxId" => $transId, 
                                );
                            }
                        } 
                    }
                    return $success;
                });
                if($success){
                    return $this->setResponse($errorCode, ["txns" => $response]);
                } else {
                    return $this->setResponse($errorCode);
                }
            } else {
                return $this->setResponse(self::SYSTEM_ERROR);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function confirmbetparlay() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.userId' => 'required',
            'message.updateTime' => 'required',
            'message.txns' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $ids = array_column($txns, 'licenseeTxId');
            $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $ids, 'id');
            $transactions = json_decode(json_encode($transactions), true);
            if(!empty($transactions) && count($transactions) == count($ids)) {
                $txnsOddsChanged = array_values(array_filter($txns, function ($row) { return $row['isOddsChanged'];}));
                if(!empty($txnsOddsChanged)){ #process txn with odds changed
                    $errorCode = self::SYSTEM_ERROR;
                    $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $txnsOddsChanged, $transactions) {
                        foreach ($txnsOddsChanged as $key => $txnOddsChanged) {
                            $success = false;
                            $transKey = array_search($txnOddsChanged['refId'], array_column($transactions, 'external_unique_id'));
                            if(!is_int($transKey)){
                                $errorCode = self::ERROR_BET_NOT_FOUND;
                                break;
                            }
                            if($txnOddsChanged['creditAmount'] == 0 && $txnOddsChanged['debitAmount'] == 0){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                continue;
                            }
                            $operationId = $this->requestParams->params->message->operationId;
                            $uniqueId = $txnOddsChanged['refId'].'-'.$operationId;
                            $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
                            if($exist){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                continue;
                            } 

                            $creditAmount =  $this->api->gameAmountToDB($txnOddsChanged['creditAmount']);
                            $debitAmount = $this->api->gameAmountToDB($txnOddsChanged['debitAmount']);
                            $beforeBalance = $this->getPlayerBalance();
                            $afterBalance = null;
                            if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                                if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                                    $resultAmount = $creditAmount - $debitAmount;
                                    $amount = abs($resultAmount);
                                    
                                    $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT);
                                    $this->wallet_model->setGameProviderRoundId($txnOddsChanged['refId']);
                                    $this->wallet_model->setGameProviderIsEndRound(false);
                                    $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                    if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                                        $success = $this->wallet_model->incSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                                        $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                        if(!$success){
                                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                        }
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                        $success = true;#allowed amount 0
                                        $afterBalance = $beforeBalance;
                                    } else { #default error
                                        $success = false;
                                    }
                                } else {
                                    $success = false;
                                    $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                }
                            }
                            
                            if($success){
                                $success = false; #reset $success
                                // $afterBalance = $this->getPlayerBalance();
                                if(is_null($afterBalance)){
                                    $afterBalance = $this->getPlayerBalance();
                                    if($afterBalance === false){
                                        return false;
                                    }
                                }
                                $transData['beforeBalance'] = $beforeBalance;
                                $transData['afterbalance'] = $afterBalance;
                                $transData['transactionType'] = "confirmbetparlay";
                                $transData['player_id'] = $this->currentPlayer->player_id;
                                $transData['amount'] = $amount;
                                $transData['betAmount'] = $this->api->gameAmountToDB($txnOddsChanged['actualAmount']);
                                $transData['resultAmount'] = $this->api->gameAmountToDB(-$txnOddsChanged['actualAmount']);
                                $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                                $transData['uniqueId'] = $uniqueId;
                                $transData['start_at'] = $this->api->gameTimeToServerTime($this->requestParams->params->message->updateTime);
                                $transData['end_at'] = $this->api->gameTimeToServerTime($this->requestParams->params->message->updateTime);
                                $transData['transaction_id'] = $txnOddsChanged['refId'];

                                $transId = $this->processRequestData($transData);
                                if($transId){
                                    $success = true;
                                    $errorCode = self::RETURN_OK;
                                }
                            }
                        }
                        return $success;
                    });
                    if($success){
                        return $this->setResponse($errorCode, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]);
                    } else {
                        return $this->setResponse($errorCode);
                    }
                } else {
                    return $this->setResponse(self::RETURN_OK, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]);
                }
            }
            else {
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    #not implemented yet
    public function placebetent() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
            'message.currency' => 'required',
            'message.productId' => 'required',
            'message.gameId' => 'required',
            'message.ticketList' => 'required',
            'message.IP' => 'required',
            'message.productName_en' => 'required',
            'message.gameName_en' => 'required',
            'message.betFrom' => 'required',
            'message.roundId' => 'required',
            'message.betTime' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $tickets = json_decode(json_encode($this->requestParams->params->message->ticketList),true);
            $ticketList = [];
            if(!empty($tickets)){
                $success = false;
                foreach ($tickets as $key => $ticket) {
                    $errorCode = self::SYSTEM_ERROR;
                    $success = false;
                    $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $ticket['refId'], 'external_unique_id');
                    if($transactions){
                        $ticketList[] = array(
                            "refId" => $transactions->transaction_id,
                            "licenseeTxId" => (int)$transactions->id, 
                        );
                        $success = true;
                        $errorCode = self::RETURN_OK;
                        continue;
                    } 

                    $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $ticket, &$ticketList){
                        $actualStake = isset($ticket['actualStake']) ? $this->api->gameAmountToDB($ticket['actualStake']) : 0;
                        $beforeBalance = $this->getPlayerBalance();
                        $afterBalance = null;
                        $success = false;
                        if($this->utils->compareResultFloat($actualStake, '<=', $beforeBalance)) {
                            if($this->utils->compareResultFloat($actualStake, '>', 0)) {
                                $uniqueidForRemote=$this->api->getPlatformCode().'-'.$ticket['refId'];       
                                $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                                $this->wallet_model->setGameProviderRoundId($ticket['refId']);
                                $this->wallet_model->setGameProviderIsEndRound(false);
                                $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $actualStake, $afterBalance);
                                if(!$success){
                                    $errorCode = self::SYSTEM_ERROR;
                                }
                            } elseif ($this->utils->compareResultFloat($actualStake, '=', 0)) {
                                $success = true;#allowed amount 0
                                $afterBalance = $beforeBalance;
                            } else { #default error
                                $success = false;
                            }
                        } else {
                            $success = false;
                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                        }
                        if($success){
                            $success = false; #reset $success
                            // $afterBalance = $this->getPlayerBalance();
                            if(is_null($afterBalance)){
                                $afterBalance = $this->getPlayerBalance();
                                if($afterBalance === false){
                                    return false;
                                }
                            }
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = "placebetent";
                            $transData['player_id'] = $this->currentPlayer->player_id;
                            $transData['amount'] = $actualStake;
                            $transData['betAmount'] = $actualStake;
                            $transData['resultAmount'] = -$actualStake;
                            $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                            $transData['uniqueId'] = $ticket['refId'];
                            $transData['transaction_id'] = $ticket['refId'];
                            

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                $ticketList[] = array(
                                    "refId" => $ticket['refId'],
                                    "licenseeTxId" => $transId, 
                                );
                            }
                        }
                        return $success;
                    });
                }
                if($success){
                    $response = array(
                        "userId" => $this->requestParams->params->message->userId,
                        "balance" => $this->api->dBtoGameAmount($this->getPlayerBalance()),
                        "ticketList" => $ticketList
                    );
                    return $this->setResponse($errorCode, $response);
                } else {
                    return $this->setResponse($errorCode);
                }
            } else {
                return $this->setResponse(self::SYSTEM_ERROR);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    #not implemented yet
    public function settleent() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
            'message.refId' => 'required',
            'message.status' => 'required',
            'message.actualStake' => 'required',
            'message.stake' => 'required',
            'message.netStake' => 'required',
            // 'message.winLostDate' => 'required',
            'message.creditAmount' => 'required',
            'message.debitAmount' => 'required',
            'message.winlostAmount' => 'required',
            'message.txIds' => 'required',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $refId = $this->requestParams->params->message->refId;
            $bet = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $refId, 'external_unique_id');
            if(empty($bet)){
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }

            $uniqueId = "settleent-{$refId}";
            $settleTransInfo = (array)$this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $uniqueId, 'external_unique_id');
            if(!empty($settleTransInfo)){
                return $this->setResponse(self::RETURN_OK);
            }

            $requestData = (array)$this->requestParams->params->message;
            $errorCode = self::SYSTEM_ERROR;
            $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $requestData, $uniqueId){
                $creditAmount =  isset($requestData['creditAmount']) ? $this->api->gameAmountToDB($requestData['creditAmount']) : 0;
                $debitAmount = isset($requestData['debitAmount']) ? $this->api->gameAmountToDB($requestData['debitAmount']) : 0;
                $beforeBalance = $this->getPlayerBalance($this->currentPlayer->username);
                $afterBalance = null;
                $success = false;
                if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                    if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                        $resultAmount = $creditAmount - $debitAmount;
                        $amount = abs($resultAmount);
                        $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                        $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
                        $this->wallet_model->setGameProviderRoundId($this->requestParams->params->message->refId);
                        $this->wallet_model->setGameProviderIsEndRound(true);
                        $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                        if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                            $success = $this->wallet_model->incSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                        } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                            $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                            if(!$success){
                                $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                            }
                        } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                            $success = true;#allowed amount 0
                            $afterBalance = $beforeBalance;
                        } else { #default error
                            $success = false;
                        }
                    } else {
                        $success = false;
                        $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                    }
                    if($success){
                        $success = false; #reset $success
                        // $afterBalance = $this->getPlayerBalance($this->currentPlayer->username);
                        if(is_null($afterBalance)){
                            $afterBalance = $this->getPlayerBalance($this->currentPlayer->username);
                            if($afterBalance === false){
                                return false;
                            }
                        }
                        $transData['beforeBalance'] = $beforeBalance;
                        $transData['afterbalance'] = $afterBalance;
                        $transData['transactionType'] = "settleent";
                        $transData['player_id'] = $this->currentPlayer->player_id;
                        $transData['amount'] = $amount;
                        $transData['betAmount'] = 0;
                        $transData['resultAmount'] = $resultAmount;
                        $transData['status'] = $this->getGameRecordsStatus($requestData['status']);
                        $transData['uniqueId'] = $uniqueId;
                        // $transData['start_at'] = $this->api->gameTimeToServerTime($requestData['updateTime']);
                        // $transData['end_at'] = $this->api->gameTimeToServerTime($requestData['updateTime']);
                        $transData['start_at'] = $transData['end_at'] = $this->utils->getNowForMysql();
                        $transData['transaction_id'] = $requestData['refId'];

                        $transId = $this->processRequestData($transData);
                        if($transId){
                            $success = true;
                            $errorCode = self::RETURN_OK;
                        }
                    }
                }
                return $success;
            });
            if($success){
                return $this->setResponse($errorCode);
            } else {
                return $this->setResponse($errorCode);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function healthcheck() {
        $rule_set = [
            'message.action' => 'required',
            'message.time' => 'required',
        ];
        $errorCode = self::RETURN_OK;
        $params = $this->requestParams->params;
        if(!isset($params->key) || (isset($params->key) && $this->api->key != $params->key)) {
            $errorCode = self::ERROR_INVALID_AUTHENTICATION_KEY;
        }

        $isValid = $this->validateRequest($rule_set);
        if(!$isValid) {
            $errorCode = self::ERROR_BAD_REQUEST;
        }
        return $this->setResponse($errorCode);
    }

    public function checkticketstatus(){
        $rule_set = [
            'refId' => 'required',
        ];
        $params = $this->requestParams->params;
        $isValid = $this->validateRequest($rule_set);
        if(!$isValid) {
            $errorCode = self::ERROR_BAD_REQUEST;
            return $this->setResponse($errorCode);
        }
        
        $refId = $params->refId;
        $result = $this->api->checkTicketStatus($refId);
        return $this->setResponse(self::RETURN_OK, $result);
    }

    public function retryoperation(){
        $rule_set = [
            'operationId' => 'required',
        ];
        $params = $this->requestParams->params;
        $isValid = $this->validateRequest($rule_set);
        if(!$isValid) {
            $errorCode = self::ERROR_BAD_REQUEST;
            return $this->setResponse($errorCode);
        }
        
        $operationId = $params->operationId;
        $result = $this->api->retryOperation($operationId);
        return $this->setResponse(self::RETURN_OK, $result);
    }

    private function getGameRecordsStatus($status) {
        $status = strtolower($status);

        switch ($status) {
        case 'running':
            $status = Game_logs::STATUS_ACCEPTED;
            break;
        case 'reject':
            $status = Game_logs::STATUS_REJECTED;
            break;
        case 'void':
            $status = Game_logs::STATUS_VOID;
            break;
        case 'refund':
            $status = Game_logs::STATUS_REFUND;
            break;
        case 'won':
        case 'draw':
        case 'lose':
        case 'half won':
        case 'half lose':
            $status = Game_logs::STATUS_SETTLED;
            break;
        }
        return $status;
    }

    public function settle() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.txns' => 'required',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $ids = array_column($txns, 'refId');
            if(!empty($ids)){
                $bets = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $ids, 'external_unique_id');
                $bets = json_decode(json_encode($bets), true);
                if(!empty($bets) && count($bets) == count($txns)){
                    foreach ($bets as $key => $bet) {
                        $errorCode = self::SYSTEM_ERROR;
                        $success = false;
                        $txnKey = array_search($bet['external_unique_id'], array_column($txns, 'refId'));
                        if(!isset($txns[$txnKey])){
                            $errorCode = self::ERROR_BET_NOT_FOUND;
                            break;
                        }
                        $settleTransInfo = $txns[$txnKey];
                        $userInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($settleTransInfo['userId'], $this->api->getPlatformCode());
                        if(empty($userInfo)){
                            $errorCode = self::ERROR_NOT_FOUND_PLAYER;
                            break;
                        }
                        $operationId = $this->requestParams->params->message->operationId;
                        $uniqueId = $bet['external_unique_id'].'-'.$operationId;
                        $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
                        if($exist){
                            $success = true;
                            $errorCode = self::RETURN_OK;
                            continue;
                        } 
                        
                        $success = $this->lockAndTransForPlayerBalance($userInfo->player_id, function() use(&$errorCode, $settleTransInfo, $userInfo, $uniqueId, $bet){
                            $creditAmount =  isset($settleTransInfo['creditAmount']) ? $this->api->gameAmountToDB($settleTransInfo['creditAmount']) : 0;
                            $debitAmount = isset($settleTransInfo['debitAmount']) ? $this->api->gameAmountToDB($settleTransInfo['debitAmount']) : 0;
                            $beforeBalance = $this->getPlayerBalance($userInfo->username);
                            $afterBalance = null;
                            $success = false;
                            if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                                if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                                    $resultAmount = $creditAmount - $debitAmount;
                                    $amount = abs($resultAmount);
                                    $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
                                    $this->wallet_model->setGameProviderRoundId($bet['external_unique_id']);
                                    $this->wallet_model->setGameProviderIsEndRound(true);
                                    $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                    if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                                        $success = $this->wallet_model->incSubWallet($userInfo->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                                        $success = $this->wallet_model->decSubWallet($userInfo->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                        if(!$success){
                                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                        }
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                        $success = true;#allowed amount 0
                                        $afterBalance = $beforeBalance;
                                    } else { #default error
                                        $success = false;
                                    }
                                } else {
                                    $success = false;
                                    $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                }
                                if($success){
                                    $success = false; #reset $success
                                    // $afterBalance = $this->getPlayerBalance($userInfo->username);
                                    if(is_null($afterBalance)){
                                        $afterBalance = $this->getPlayerBalance($userInfo->username);
                                        if($afterBalance === false){
                                            return false;
                                        }
                                    }
                                    $transData['beforeBalance'] = $beforeBalance;
                                    $transData['afterbalance'] = $afterBalance;
                                    $transData['transactionType'] = "settle";
                                    $transData['player_id'] = $userInfo->player_id;
                                    $transData['amount'] = $amount;
                                    $transData['betAmount'] = 0;
                                    $transData['resultAmount'] = $resultAmount;
                                    $transData['status'] = $this->getGameRecordsStatus($settleTransInfo['status']);
                                    $transData['uniqueId'] = $uniqueId;
                                    $transData['start_at'] = $this->api->gameTimeToServerTime($settleTransInfo['updateTime']);
                                    $transData['end_at'] = $this->api->gameTimeToServerTime($settleTransInfo['updateTime']);
                                    $transData['transaction_id'] = $settleTransInfo['refId'];

                                    $transId = $this->processRequestData($transData);
                                    if($transId){
                                        $success = true;
                                        $errorCode = self::RETURN_OK;
                                    }
                                }
                            }
                            return $success;
                        });
                    }
                    if($success){
                        return $this->setResponse($errorCode);
                    } else {
                        return $this->setResponse($errorCode);
                    }
                } else {
                    return $this->setResponse(self::ERROR_BET_NOT_FOUND);
                }
            } else {
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function cancelbet() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.userId' => 'required',
            'message.updateTime' => 'required',
            'message.txns' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $ids = array_column($txns, 'refId');
            if(!empty($ids)){
                $bets = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $ids, 'external_unique_id');
                $bets = json_decode(json_encode($bets), true);
                if(!empty($bets) && count($bets) == count($txns)){
                    $errorCode = self::SYSTEM_ERROR;
                    $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $bets, $txns) {
                        $success = false;
                        foreach ($bets as $key => $bet) {
                            $txnKey = array_search($bet['external_unique_id'], array_column($txns, 'refId'));
                            if(!isset($txns[$txnKey])){
                                $errorCode = self::ERROR_BET_NOT_FOUND;
                                break;
                            }
                            $cancelTransInfo = $txns[$txnKey];
                            $operationId = $this->requestParams->params->message->operationId;
                            $uniqueId = $bet['external_unique_id'].'-'.$operationId;
                            $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
                            if($exist){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                continue;
                            }

                            $creditAmount = isset($cancelTransInfo['creditAmount']) ? $this->api->gameAmountToDB($cancelTransInfo['creditAmount']) : 0;
                            $beforeBalance = $this->getPlayerBalance();
                            $afterBalance = null;
                            if($this->utils->compareResultFloat($creditAmount, '>', 0)) {
                                $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                                $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                $this->wallet_model->setGameProviderIsEndRound(true);
                                $this->wallet_model->setGameProviderRoundId($bet['external_unique_id']);
                                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
                                $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                $success = $this->wallet_model->incSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $creditAmount, $afterBalance);
                            } elseif ($this->utils->compareResultFloat($creditAmount, '=', 0)) {
                                $success = true;#allowed amount 0
                                $afterBalance = $beforeBalance;
                            } else { #default error
                                $success = false;
                            }
                            
                            if($success){
                                $success = false; #reset $success
                                // $afterBalance = $this->getPlayerBalance();
                                if(is_null($afterBalance)){
                                    $afterBalance = $this->getPlayerBalance();
                                    if($afterBalance === false){
                                        return false;
                                    }
                                }
                                $transData['beforeBalance'] = $beforeBalance;
                                $transData['afterbalance'] = $afterBalance;
                                $transData['transactionType'] = "cancelbet";
                                $transData['player_id'] = $this->currentPlayer->player_id;
                                $transData['amount'] = $creditAmount;
                                $transData['betAmount'] = 0;
                                $transData['resultAmount'] = $creditAmount;
                                $transData['status'] = GAME_LOGS::STATUS_CANCELLED;
                                $transData['uniqueId'] = $uniqueId;
                                $transData['transaction_id'] = $cancelTransInfo['refId'];

                                $transId = $this->processRequestData($transData);
                                if($transId){
                                    $success = true;
                                    $errorCode = self::RETURN_OK;
                                }
                            }
                            return$success;
                        }
                        return $success;
                    });
                    if($success){
                        return $this->setResponse($errorCode, ['balance' => $this->api->dBtoGameAmount($this->getPlayerBalance())]);
                    } else {
                        return $this->setResponse($errorCode);
                    }
                } else {
                    return $this->setResponse(self::ERROR_BET_NOT_FOUND);
                }
            } else {
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function unsettle() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.txns' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $ids = array_column($txns, 'refId');
            if(!empty($ids)){
                $bets = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $ids, 'external_unique_id');
                $bets = json_decode(json_encode($bets), true);
                if(!empty($bets) && count($bets) == count($txns)){
                    foreach ($bets as $key => $bet) {
                        $errorCode = self::SYSTEM_ERROR;
                        $success = false;
                        $txnKey = array_search($bet['external_unique_id'], array_column($txns, 'refId'));
                        if(!isset($txns[$txnKey])){
                            $errorCode = self::ERROR_BET_NOT_FOUND;
                            break;
                        }
                        $unsettleTransInfo = $txns[$txnKey];
                        $userInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($unsettleTransInfo['userId'], $this->api->getPlatformCode());
                        if(empty($userInfo)){
                            $errorCode = self::ERROR_NOT_FOUND_PLAYER;
                            break;
                        }
                        $operationId = $this->requestParams->params->message->operationId;
                        $uniqueId = $bet['external_unique_id'].'-'.$operationId;
                        $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
                        if($exist){
                            $success = true;
                            $errorCode = self::RETURN_OK;
                            continue;
                        } 
                        
                        $success = $this->lockAndTransForPlayerBalance($userInfo->player_id, function() use(&$errorCode, $unsettleTransInfo, $userInfo, $uniqueId, $bet){
                            $creditAmount =  isset($unsettleTransInfo['creditAmount']) ? $this->api->gameAmountToDB($unsettleTransInfo['creditAmount']) : 0;
                            $debitAmount = isset($unsettleTransInfo['debitAmount']) ? $this->api->gameAmountToDB($unsettleTransInfo['debitAmount']) : 0;
                            $beforeBalance = $this->getPlayerBalance($userInfo->username);
                            $afterBalance = null;
                            $success = false;
                            if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                                if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                                    $resultAmount = $creditAmount - $debitAmount;
                                    $amount = abs($resultAmount);
                                    $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                    $this->wallet_model->setGameProviderIsEndRound(false);
                                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT);
                                    $this->wallet_model->setGameProviderRoundId($bet['external_unique_id']);
                                    $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                    if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                                        $success = $this->wallet_model->incSubWallet($userInfo->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                                        $success = $this->wallet_model->decSubWallet($userInfo->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                        if(!$success){
                                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                        }
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                        $success = true;#allowed amount 0
                                        $afterBalance = $beforeBalance;
                                    } else { #default error
                                        $success = false;
                                    }
                                } else {
                                    $success = false;
                                    $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                }
                                if($success){
                                    $success = false; #reset $success
                                    // $afterBalance = $this->getPlayerBalance($userInfo->username);
                                    if(is_null($afterBalance)){
                                        $afterBalance = $this->getPlayerBalance($userInfo->username);
                                        if($afterBalance === false){
                                            return false;
                                        }
                                    }
                                    $transData['beforeBalance'] = $beforeBalance;
                                    $transData['afterbalance'] = $afterBalance;
                                    $transData['transactionType'] = "unsettle";
                                    $transData['player_id'] = $userInfo->player_id;
                                    $transData['amount'] = $amount;
                                    $transData['betAmount'] = 0;
                                    $transData['resultAmount'] = $resultAmount;
                                    $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                                    $transData['uniqueId'] = $uniqueId;
                                    $transData['start_at'] = $this->api->gameTimeToServerTime($unsettleTransInfo['updateTime']);
                                    $transData['end_at'] = $this->api->gameTimeToServerTime($unsettleTransInfo['updateTime']);
                                    $transData['transaction_id'] = $unsettleTransInfo['refId'];

                                    $transId = $this->processRequestData($transData);
                                    if($transId){
                                        $success = true;
                                        $errorCode = self::RETURN_OK;
                                    }
                                }
                            }
                            return $success;
                        });
                    }
                    if($success){
                        return $this->setResponse($errorCode);
                    } else {
                        return $this->setResponse($errorCode);
                    }
                } else {
                    return $this->setResponse(self::ERROR_BET_NOT_FOUND);
                }
            } else {
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function resettle() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.txns' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $txns = json_decode(json_encode($this->requestParams->params->message->txns),true);
            $ids = array_column($txns, 'refId');
            if(!empty($ids)){
                $bets = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $ids, 'external_unique_id');
                $bets = json_decode(json_encode($bets), true);
                if(!empty($bets) && count($bets) == count($txns)){
                    foreach ($bets as $key => $bet) {
                        $errorCode = self::SYSTEM_ERROR;
                        $success = false;
                        $txnKey = array_search($bet['external_unique_id'], array_column($txns, 'refId'));
                        if(!isset($txns[$txnKey])){
                            $errorCode = self::ERROR_BET_NOT_FOUND;
                            break;
                        }
                        $resettleTransInfo = $txns[$txnKey];
                        $userInfo = $this->common_token->getPlayerCompleteDetailsByGameUsername($resettleTransInfo['userId'], $this->api->getPlatformCode());
                        if(empty($userInfo)){
                            $errorCode = self::ERROR_NOT_FOUND_PLAYER;
                            break;
                        }
                        $operationId = $this->requestParams->params->message->operationId;
                        $uniqueId = $bet['external_unique_id'].'-'.$operationId;
                        $exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->api->getPlatformCode(), $uniqueId);
                        if($exist){
                            $success = true;
                            $errorCode = self::RETURN_OK;
                            continue;
                        } 
                        
                        $success = $this->lockAndTransForPlayerBalance($userInfo->player_id, function() use(&$errorCode, $resettleTransInfo, $userInfo, $uniqueId, $bet){
                            $creditAmount =  isset($resettleTransInfo['creditAmount']) ? $this->api->gameAmountToDB($resettleTransInfo['creditAmount']) : 0;
                            $debitAmount = isset($resettleTransInfo['debitAmount']) ? $this->api->gameAmountToDB($resettleTransInfo['debitAmount']) : 0;
                            $beforeBalance = $this->getPlayerBalance($userInfo->username);
                            $afterBalance = null;
                            $success = false;
                            if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                                if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                                    $resultAmount = $creditAmount - $debitAmount;
                                    $amount = abs($resultAmount);
                                    $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                    $this->wallet_model->setGameProviderIsEndRound(true);
                                    $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT);
                                    $this->wallet_model->setGameProviderRoundId($bet['external_unique_id']);
                                    $this->wallet_model->setExternalGameId(isset($this->requestParams->params->message->sportTypeName_en) ? $this->requestParams->params->message->sportTypeName_en : null);

                                    if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                                        $success = $this->wallet_model->incSubWallet($userInfo->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                                        $success = $this->wallet_model->decSubWallet($userInfo->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                                        if(!$success){
                                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                        }
                                    } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                                        $success = true;#allowed amount 0
                                        $afterBalance = $beforeBalance;
                                    } else { #default error
                                        $success = false;
                                    }
                                } else {
                                    $success = false;
                                    $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                                }
                                if($success){
                                    $success = false; #reset $success
                                    // $afterBalance = $this->getPlayerBalance($userInfo->username);
                                    if(is_null($afterBalance)){
                                        $afterBalance = $this->getPlayerBalance($userInfo->username);
                                        if($afterBalance === false){
                                            return false;
                                        }
                                    }
                                    $transData['beforeBalance'] = $beforeBalance;
                                    $transData['afterbalance'] = $afterBalance;
                                    $transData['transactionType'] = "resettle";
                                    $transData['player_id'] = $userInfo->player_id;
                                    $transData['amount'] = $amount;
                                    $transData['betAmount'] = 0;
                                    $transData['resultAmount'] = $resultAmount;
                                    $transData['status'] = $this->getGameRecordsStatus($resettleTransInfo['status']);
                                    $transData['uniqueId'] = $uniqueId;
                                    $transData['start_at'] = $this->api->gameTimeToServerTime($resettleTransInfo['updateTime']);
                                    $transData['end_at'] = $this->api->gameTimeToServerTime($resettleTransInfo['updateTime']);
                                    $transData['transaction_id'] = $resettleTransInfo['refId'];

                                    $transId = $this->processRequestData($transData);
                                    if($transId){
                                        $success = true;
                                        $errorCode = self::RETURN_OK;
                                    }
                                }
                            }
                            return $success;
                        });
                    }
                    if($success){
                        return $this->setResponse($errorCode);
                    } else {
                        return $this->setResponse($errorCode);
                    }
                } else {
                    return $this->setResponse(self::ERROR_BET_NOT_FOUND);
                }
            } else {
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    private function transferGameWallet($player_id, $game_platform_id, $mode, $amount) {
        $success = false;

        if($amount == 0) {
            return true;
        }

        if($mode=='debit') {
            $success = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);
        } elseif($mode=='credit') {
            $success = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
        }

        return $success;
    }

    private function doManualRollback($action) {
        $transaction_result = false;
        foreach($this->wallet_transaction_list as $wallet_transaction) {
            $this->CI->load->model('common_token');
            $this->currentPlayer = $this->common_token->getPlayerCompleteDetailsByGameUsername($wallet_transaction->userId, $this->api->getPlatformCode());
            $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use ($wallet_transaction, $action) {
                $success = $this->wallet_transactions->delete('id', $wallet_transaction['id']);
                if($success) {
                    $transfer_status = $this->transferGameWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $action, $wallet_transaction['amount']);
                    if(!$transfer_status) {
                        return false;
                    }
                }
            });
            if(!$transaction_result) {
                $this->utils->debug_log("IBC ONEBOOK SEAMLESS API ALERT!!!!!!!!!!!!!!!!!!!!!!! ROLLBACK FAILED", $wallet_transaction);
                $this->sendMatterMostMessage('IBC ONEBOOK SEAMLESS Manual Rollback Failed', "```{$this->requestParams->params}```");
            }
        }
        return $transaction_result;
    }

    public function sendMatterMostMessage($caption, $body){
        $message = [
            $caption,
            $body,
        ];

        $channel = $this->utils->getConfig('solid_gaming_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $channel = 'db_high_level_error_monitor';
        $user = 'IBC ONEBOOK SEAMLESS Manual Rollback';

        sendNotificationToMattermost($user, $channel, [], $message);

    }

    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                $value = $this->getValueByKey($key, $this->requestParams->params);
                // echo $value;exit();
                if($rule == 'required' && is_null($value)) {
                    $is_valid = false;
                    $this->utils->debug_log('IBC ONEBOOK SEAMLESS API (validateRequest):', $key);
                    break;
                }
                if($rule == 'numeric>0' && ($value < 0 || !is_numeric($value)) ) {
                    $is_valid = false;
                    $this->utils->debug_log('IBC ONEBOOK SEAMLESS API (validateRequest):', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    private function getPlayerBalance($playerName = null) {
        if(empty($playerName)) {
            $playerName = $this->currentPlayer->username;
        }
        $response = $this->api->queryPlayerBalance($playerName);
        if($response['success']) {
            return $response['balance'];
        }
        else {
            return false;
        }
    }

    private function getValueByKey($key, $object, $separator='.') {
        $keys = explode($separator, $key);
        foreach ($keys as $key) {
            if(array_key_exists($key, (array) $object)) {
                $object = $object->$key;
            }
            else {
                return null;
            }
        }
        return $object;
    }

    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['status'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if(array_key_exists('balance', $data)) {
            $data['balance'] = number_format($data['balance'], 2, '.', '');
        }
        $response = $data;
        $playerId = isset( $this->currentPlayer->player_id) ? $this->currentPlayer->player_id : null;
        if($this->responseResultId) {
            if($flag == Response_result::FLAG_ERROR){
                $this->response_result->setResponseResultToError($this->responseResultId);
            }
            $response_result = $this->response_result->getResponseResultById($this->responseResultId);
            $result   = $this->response_result->getRespResultByTableField($response_result->filepath);
            $content = json_decode($result['content'], true);
            $content['resultText'] = $response;
            $content['costMs'] = intval($this->utils->getExecutionTimeToNow()*1000);
            $content = json_encode($content);
            $this->response_result->updateResponseResultCommonData($this->responseResultId, null, $playerId, $flag);
            $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
        } else {
            if($this->api) {
                $costMs = intval($this->utils->getExecutionTimeToNow()*1000);
                $fields = [];
                $dont_save_response_in_api = false;
                $external_request_id = null;
                $this->response_result->saveResponseResult(
                    $this->api->getPlatformCode(),
                    $flag,
                    $this->requestParams->function,
                    json_encode($this->requestParams->params),
                    $response,
                    $this->http_status_code,
                    null,
                    is_array($this->headers) ? json_encode($this->headers) : $this->headers,
                    $fields,
                    $dont_save_response_in_api,
                    $external_request_id,
                    $costMs
                );
            }
        }

        return $this->returnJsonResult($response, true, '*', false, false, $this->http_status_code);
    }

    private function setResponseResult(){
        $response_result_id = $this->response_result->saveResponseResult(
            $this->api->getPlatformCode(),
            Response_result::FLAG_NORMAL,
            $this->requestParams->function,
            json_encode($this->requestParams->params),
            [],#default empty response
            $this->http_status_code,
            null,
            is_array($this->headers) ? json_encode($this->headers) : $this->headers
        );
        return $response_result_id;
    }

    /*
    Adjust Balance
    Description
        Game provider is able to call this function that will be provided by operator.
         This method supports promotions or any actions that will impact player’s balance.
         Will repeatedly call Adjust Balance until it is successfully implemented or reach the limit in retry
        progress if it is failed to call in the first attempt. Can refer Appendix: Retry Mechanism for further
        detail.
    Method URL
    Provide by Operator.
        For Example: https://<Operator_API_SERVICE_URL>/adjustbalance
    */
    private function adjustbalance() {
        $rule_set = [
            'message.action' => 'required',
            'message.time' => 'required',
            'message.userId' => 'required',
            'message.currency' => 'required',
            'message.txId' => 'required',
            'message.refId' => 'required',
            'message.operationId' => 'required',
            'message.betType' => 'required',
            'message.betTypeName' => 'required',
            'message.balanceInfo.creditAmount' => 'required|numeric>0',
            'message.balanceInfo.debitAmount' => 'required|numeric>0',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $uniqueId = $this->requestParams->params->message->refId . "-" . $this->requestParams->params->message->operationId;
            $adjustTransaction = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $uniqueId, 'external_unique_id');
            if($adjustTransaction){
                return $this->setResponse(self::RETURN_OK);
            }

            $errorCode = self::SYSTEM_ERROR;
            $response = [];
            $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, &$response, $uniqueId) {
                
                $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                
                $success = false; #default
                $debitAmount = $this->api->gameAmountToDB($this->requestParams->params->message->balanceInfo->debitAmount);
                $creditAmount =  $this->api->gameAmountToDB($this->requestParams->params->message->balanceInfo->creditAmount);
                $beforeBalance = $this->getPlayerBalance();
                $afterBalance = null;
                if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                    if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance) || $this->utils->compareResultFloat($debitAmount, '=', 0)) {
                        $resultAmount = $creditAmount - $debitAmount;
                        $amount = abs($resultAmount);
                        if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                            $success = $this->wallet_model->incSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                        } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                            $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount, $afterBalance);
                            if(!$success){
                                $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                            }
                        } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }
                    } else {
                        $success = false;
                        $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                    }
                }
                
                if($success){
                    if(is_null($afterBalance)){
                        $afterBalance = $this->getPlayerBalance();
                        if($afterBalance === false){
                            $errorCode = self::SYSTEM_ERROR;
                            return false;
                        }
                    }
                    $success = false; #reset $success
                    $afterBalance = $this->getPlayerBalance();
                    $transData['beforeBalance'] = $beforeBalance;
                    $transData['afterbalance'] = $afterBalance;
                    $transData['transactionType'] = "adjustbalance";
                    $transData['player_id'] = $this->currentPlayer->player_id;
                    $transData['amount'] = $amount;
                    $transData['betAmount'] = 0;
                    $transData['resultAmount'] = 0;
                    $transData['uniqueId'] = $this->requestParams->params->message->refId;

                    $transId = $this->processRequestData($transData);
                    if($transId){
                        $success = true;
                        $errorCode = self::RETURN_OK;
                    }
                }
                return$success;
            });
            if($success){
                return $this->setResponse($errorCode, $response);
            } else {
                return $this->setResponse($errorCode);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    /*
    Description
        Operator is able to call this function that will be provided by game provider.
        i. To get all tickets which had reached the limit in retry progress.
        ii. Only can get transactions within 30 days.
        iii. It is recommended to implement a daily job in triggering "GetReachLimitTrans" to get the ticket list
            which has reached the maximum retry limit, and to further verify (CheckTicketStatus) and retry
            (RetryOperation) to ensure intact ticket process..
    Method URL
        Provide by Operator API.
        For Example: https://<Operator_API_SERVICE_URL>/getreachlimittrans
     */
    public function getreachlimittrans(){
        $rule_set = [
            'start_Time' => 'required',
        ];
        $params = $this->requestParams->params;
        $isValid = $this->validateRequest($rule_set);
        if(!$isValid) {
            $errorCode = self::ERROR_BAD_REQUEST;
            return $this->setResponse($errorCode);
        }
        
        $dateTime = $params->start_Time;
        $result = $this->api->getreachlimittrans($dateTime);
        return $this->setResponse(self::RETURN_OK, $result);
    }

    public function placebet3rd() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.userId' => 'required',
            'message.currency' => 'required',
            'message.productId' => 'required',
            'message.gameId' => 'required',
            'message.ticketList' => 'required',
            'message.betTime' => 'required',
            'message.IP' => 'required',
            'message.tsId' => 'required',
            'message.productName_en' => 'required',
            'message.gameName_en' => 'required',
            'message.betFrom' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $tickets = json_decode(json_encode($this->requestParams->params->message->ticketList),true);
            $ticketList = [];
            if(!empty($tickets)){
                $success = false;
                foreach ($tickets as $key => $ticket) {
                    $errorCode = self::SYSTEM_ERROR;
                    $success = false;
                    $transactions = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $ticket['refId'], 'external_unique_id');
                    if($transactions){
                        $ticketList[] = array(
                            "refId" => $transactions->transaction_id,
                            "licenseeTxId" => (int)$transactions->id, 
                        );
                        $success = true;
                        $errorCode = self::RETURN_OK;
                        continue;
                    } 

                    $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $ticket, &$ticketList){
                        $debitAmount = isset($ticket['debitAmount']) ? $this->api->gameAmountToDB($ticket['debitAmount']) : null;
                        $beforeBalance = $this->getPlayerBalance();
                        $success = false;
                        if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                            if($this->utils->compareResultFloat($debitAmount, '>', 0)) {
                                $uniqueidForRemote=$this->api->getPlatformCode().'-'.$ticket['refId'];       
                                $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                                $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $debitAmount);
                                if(!$success){
                                    $errorCode = self::SYSTEM_ERROR;
                                }
                            } elseif ($this->utils->compareResultFloat($debitAmount, '=', 0)) {
                                $success = true;#allowed amount 0
                            } else { #default error
                                $success = false;
                            }
                        } else {
                            $success = false;
                            $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                        }
                        if($success){
                            $success = false; #reset $success
                            $afterBalance = $this->getPlayerBalance();
                            $transData['beforeBalance'] = $beforeBalance;
                            $transData['afterbalance'] = $afterBalance;
                            $transData['transactionType'] = "placebet3rd";
                            $transData['player_id'] = $this->currentPlayer->player_id;
                            $transData['amount'] = $debitAmount;
                            $transData['betAmount'] = $debitAmount;
                            $transData['resultAmount'] = -$debitAmount;
                            $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                            $transData['uniqueId'] = $ticket['refId'];
                            $transData['transaction_id'] = $ticket['refId'];
                            

                            $transId = $this->processRequestData($transData);
                            if($transId){
                                $success = true;
                                $errorCode = self::RETURN_OK;
                                $ticketList[] = array(
                                    "refId" => $ticket['refId'],
                                    "licenseeTxId" => $transId, 
                                );
                            }
                        }
                        return $success;
                    });
                }
                if($success){
                    $response = array(
                        "userId" => $this->requestParams->params->message->userId,
                        "balance" => $this->api->dBtoGameAmount($this->getPlayerBalance()),
                        "txns" => $ticketList
                    );
                    return $this->setResponse($errorCode, $response);
                } else {
                    return $this->setResponse($errorCode);
                }
            } else {
                return $this->setResponse(self::SYSTEM_ERROR);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function confirmbet3rd() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
            'message.operationId' => 'required',
            'message.updateTime' => 'required',
            'message.txns' => 'required',
            'message.transactionTime' => 'required',
        ];
        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $tickets = json_decode(json_encode($this->requestParams->params->message->txns),true);
            if(!empty($tickets)){
                $success = false;
                foreach ($tickets as $key => $ticket) {
                    $errorCode = self::SYSTEM_ERROR;
                    $success = false;
                    $licenseeTxId = isset($ticket['licenseeTxId']) ? $ticket['licenseeTxId'] : null;
                    $refId = isset($ticket['refId']) ? $ticket['refId'] : null;
                    $operationId = $this->requestParams->params->message->operationId;
                    $uniqueId = $refId.'-'.$operationId;
                    $uniqueData = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $uniqueId, 'external_unique_id');
                    if(!empty($uniqueData)){
                        $success = true;
                        $errorCode = self::RETURN_OK;
                        continue;
                    }

                    $trans = $this->common_seamless_wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $licenseeTxId, 'id');
                    if(empty($trans)){
                        $errorCode = self::ERROR_BET_NOT_FOUND;
                        break;
                    }

                    if($refId != $trans->transaction_id) {
                        $errorCode = self::ERROR_BET_NOT_FOUND;
                        break;
                    }

                    $balance = $this->getPlayerBalance();
                    $transData['beforeBalance'] = $balance;
                    $transData['afterbalance'] = $balance;
                    $transData['transactionType'] = "confirmbet3rd";
                    $transData['player_id'] = $this->currentPlayer->player_id;
                    $transData['amount'] = 0;
                    $transData['betAmount'] = 0;
                    $transData['resultAmount'] = 0;
                    $transData['status'] = GAME_LOGS::STATUS_ACCEPTED;
                    $transData['uniqueId'] = $uniqueId;
                    $transData['transaction_id'] = $ticket['refId'];
                    $transId = $this->processRequestData($transData);
                    if($transId){
                        $success = true;
                        $errorCode = self::RETURN_OK;
                    }
                }
                if($success){
                    $response = array(
                        "userId" => $this->requestParams->params->message->userId,
                        "balance" => $this->api->dBtoGameAmount($this->getPlayerBalance()),
                    );
                    return $this->setResponse($errorCode, $response);
                } else {
                    return $this->setResponse($errorCode);
                }
            } else {
                return $this->setResponse(self::SYSTEM_ERROR);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function cancelbetent() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
            'message.refId' => 'required',
            'message.actualStake' => 'required',
            'message.creditAmount' => 'required',
            'message.debitAmount' => 'required',
            'message.winlostAmount' => 'required',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $refId = $this->requestParams->params->message->refId;
            $bet = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $refId, 'external_unique_id');
            if(empty($bet)){
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }

            $uniqueId = "cancelbetent-{$refId}";
            $settleTransInfo = (array)$this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $uniqueId, 'external_unique_id');
            if(!empty($settleTransInfo)){
                return $this->setResponse(self::RETURN_OK);
            }

            $requestData = (array)$this->requestParams->params->message;
            $errorCode = self::SYSTEM_ERROR;
            $success = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$errorCode, $requestData, $uniqueId){
                $creditAmount =  isset($requestData['creditAmount']) ? $this->api->gameAmountToDB($requestData['creditAmount']) : 0;
                $debitAmount = isset($requestData['debitAmount']) ? $this->api->gameAmountToDB($requestData['debitAmount']) : 0;
                $beforeBalance = $this->getPlayerBalance($this->currentPlayer->username);
                $success = false;
                if($this->utils->compareResultFloat($debitAmount, '>=', 0) && $this->utils->compareResultFloat($creditAmount, '>=', 0)){
                    if($this->utils->compareResultFloat($debitAmount, '<=', $beforeBalance)) {
                        $resultAmount = $creditAmount - $debitAmount;
                        $amount = abs($resultAmount);
                        $uniqueidForRemote=$this->api->getPlatformCode().'-'.$uniqueId;       
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueidForRemote);
                        if($this->utils->compareResultFloat($resultAmount, '>', 0)) {
                            $success = $this->wallet_model->incSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount);
                        } elseif ($this->utils->compareResultFloat($resultAmount, '<', 0)) {
                            $success = $this->wallet_model->decSubWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), $amount);
                            if(!$success){
                                $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                            }
                        } elseif ($this->utils->compareResultFloat($resultAmount, '=', 0)) {
                            $success = true;#allowed amount 0
                        } else { #default error
                            $success = false;
                        }
                    } else {
                        $success = false;
                        $errorCode = self::ERROR_INSUFFICIENT_BALANCE;
                    }
                    if($success){
                        $success = false; #reset $success
                        $afterBalance = $this->getPlayerBalance($this->currentPlayer->username);
                        $transData['beforeBalance'] = $beforeBalance;
                        $transData['afterbalance'] = $afterBalance;
                        $transData['transactionType'] = "cancelbetent";
                        $transData['player_id'] = $this->currentPlayer->player_id;
                        $transData['amount'] = $amount;
                        $transData['betAmount'] = 0;
                        $transData['resultAmount'] = $resultAmount;
                        $transData['status'] = GAME_LOGS::STATUS_CANCELLED;
                        $transData['uniqueId'] = $uniqueId;
                        // $transData['start_at'] = $this->api->gameTimeToServerTime($requestData['updateTime']);
                        // $transData['end_at'] = $this->api->gameTimeToServerTime($requestData['updateTime']);
                        $transData['start_at'] = $transData['end_at'] = $this->utils->getNowForMysql();
                        $transData['transaction_id'] = $requestData['refId'];

                        $transId = $this->processRequestData($transData);
                        if($transId){
                            $success = true;
                            $errorCode = self::RETURN_OK;
                        }
                    }
                }
                return $success;
            });
            if($success){
                return $this->setResponse($errorCode);
            } else {
                return $this->setResponse($errorCode);
            }
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }

    public function getticketinfo() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
            'message.refId' => 'required',
        ];

        if($this->preProcessRequest(__FUNCTION__, $rule_set)){
            $refId = $this->requestParams->params->message->refId;
            $bet = $this->common_seamless_wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), $refId, 'external_unique_id');
            if(empty($bet)){
                return $this->setResponse(self::ERROR_BET_NOT_FOUND);
            }

            $rows = $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom('common_seamless_wallet_transactions', array (
                    'game_platform_id' => $this->api->getPlatformCode(),
                    'transaction_id' => $refId,
                )
            );
            $lastRow = end($rows);
            $totalBetAmount = array_sum(array_column($rows, 'bet_amount'));
            $totalResultAmount = array_sum(array_column($rows, 'result_amount'));

            

            switch ($lastRow['status']) {
                case GAME_LOGS::STATUS_SETTLED:
                    $extrainfo = isset($lastRow['extra_info']) ? json_decode($lastRow['extra_info'], true) : null;
                    $ticketStatus = isset($extrainfo['message']['status']) ? strtolower($extrainfo['message']['status']) : null;
                    break;
                case GAME_LOGS::STATUS_CANCELLED:
                    $ticketStatus = "reject";
                    break;
                default:
                    $ticketStatus = "running";
                    break;
            }

            $response = array(
                "actualStake" => $this->api->dBtoGameAmount($totalBetAmount),
                "winlostAmount" => $this->api->dBtoGameAmount($totalResultAmount),
                "ticketStatus" => $ticketStatus
            );
            return $this->setResponse(self::RETURN_OK, $response);
        } else {
            if($this->errorCode){
                return $this->setResponse($this->errorCode);
            }
            return $this->setResponse(self::SYSTEM_ERROR);
        }
    }
}