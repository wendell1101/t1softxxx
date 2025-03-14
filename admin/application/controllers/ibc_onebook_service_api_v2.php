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

    const BLACKLIST_METHODS = [
        'authenticate',
        'bet',
        'balance',
    ];


    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCELLED = 'cancel';
    const TRANSACTION_UNSETTLE = 'unsettle';
    const TRANSACTION_RESETTLE = 'resettle';

    const TRANSACTION_PARLAY = 'parlay';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_ADJUST_DEBIT = 'adjust-debit';
    const TRANSACTION_ADJUST_CREDIT = 'adjust-credit';

    private $requestParams;
    private $api;
    public $currentPlayer = null;
    public $wallet_transaction_list = [];
    public function __construct() {
        parent::__construct();
    }

    public function index($api, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($api);
        if(!$this->api) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if($this->api->isMaintenance() || $this->api->isDisabled()) {
            if(in_array($method, self::BLACKLIST_METHODS)) {
                return $this->setResponse(self::ERROR_DISABLED_API);
            }
        }

        $this->CI->load->model('common_seamless_wallet_transactions', 'wallet_transactions');
        $this->requestParams = new stdClass();

        if(!method_exists($this, $method)) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }
        return $this->$method();
    }

    public function getbalance() {
        $rule_set = [
            'message.action' => 'required',
            'message.userId' => 'required',
        ];
        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $balance = 0;
        $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$balance) {
            $balance = $this->getPlayerBalance();
            if($balance === false) {
                return false;
            }
            return true;
        });
        $data = [
            'user_id' => $this->currentPlayer->game_username,
            'balance' => $balance,
            'balanceTs' => $this->api->serverTimeToGameTime($this->utils->getNowForMysql())
        ];
        return $this->setResponse(self::RETURN_OK, $data);
    }

    public function placebet() {
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
            'message.betAmount' => 'required',
            'message.actualAmount' => 'required',
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
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data) {
            $old_transaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $this->requestParams->params->message->refId, 'external_unique_id', self::TRANSACTION_DEBIT);
            if(!empty($old_transaction)) {
                $transaction_data['code'] = self::RETURN_OK;
                $transaction_data['transaction_id'] = $old_transaction->transaction_id;
                $transaction_data['external_unique_id'] = $old_transaction->external_unique_id;
                return true;
            }
            if($this->requestParams->params->message->actualAmount < 0) {
                $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                return false;
            }
            $transaction_data = $this->adjustWallet(self::TRANSACTION_DEBIT, $this->requestParams->params->message->actualAmount);
            if($transaction_data['code']!= self::RETURN_OK) {
                return false;
            }
            return true;
        });

        if($transaction_result) {
            $data = [
                'refId' => $transaction_data['external_unique_id'],
                'licenseeTxId' => $transaction_data['transaction_id']
            ];
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
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

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data) {
            $transaction_list = [];
            foreach($this->requestParams->params->message->txns as $transaction) {
                $transaction_list[$transaction->licenseeTxId] = $transaction;
            }
            $old_transactions = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), array_keys($transaction_list), 'transaction_id', self::TRANSACTION_DEBIT);

            if(!empty($old_transactions) && count($old_transactions) == count($transaction_list)) {

                $changes = [];
                $totalAmountChanges = 0;
                foreach($old_transactions as $old_transaction) {
                    $transaction_id = $old_transaction->transaction_id;
                    $bet_amount = $old_transaction->amount;
                    $confirm_amount = $transaction_list[$transaction_id]->actualAmount;

                    if($bet_amount < $confirm_amount) {
                        // deduct because on confirm odds get bigger.
                        $totalAmountChanges -= $confirm_amount - $bet_amount;
                        $changes = [
                            'transaction_type' => self::TRANSACTION_ADJUST_DEBIT,
                            'status' => 'ok',
                            'external_unique_id' => $transaction_id . '-' . self::TRANSACTION_ADJUST_DEBIT,
                            'extra_info' => [
                                'raw_request' => $this->requestParams->params,
                            ],
                            'amount' => $confirm_amount - $bet_amount,
                            'external_unique_id' => $transaction_list[$transaction_id]->txId,
                            'transaction_id' => $transaction_list[$transaction_id]->txId,
                        ];
                        $totalAmountChanges[] = $changes;
                    }
                    else if($bet_amount < $confirm_amount) {
                        // add because on confirm odds get bigger.
                        $totalAmountChanges += $bet_amount - $confirm_amount;
                        $changes[] = [
                            'transaction_type' => self::TRANSACTION_ADJUST_CREDIT,
                            'status' => 'ok',
                            'external_unique_id' => $transaction_id . '-' . self::TRANSACTION_ADJUST_CREDIT,
                            'extra_info' => [
                                'raw_request' => $this->requestParams->params,
                            ],
                            'amount' => $bet_amount - $confirm_amount,
                            'external_unique_id' => $transaction_list[$transaction_id]->txId,
                            'transaction_id' => $transaction_list[$transaction_id]->txId,
                        ];
                    }
                }

                if($totalAmountChanges > 0) {
                    $this->adjustWallet(self::TRANSACTION_CREDIT, $totalAmountChanges, $changes);
                }
                else if($totalAmountChanges < 0) {
                    $this->adjustWallet(self::TRANSACTION_DEBIT, $totalAmountChanges, $changes);
                }
                $transaction_data['code'] = self::RETURN_OK;
                $transaction_data['after_balance'] = $this->getPlayerBalance();
                return true;
            }
            else {
                $transaction_data['code'] = self::ERROR_BET_NOT_FOUND;
                return false;
            }
        });

        if($transaction_result) {
            $data = [
                'balance' => $transaction_data['after_balance'],
            ];
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
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

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data) {
            $parlay_transaction_list = [];
            foreach($this->requestParams->params->message->txns as $parlay_transaction) {
                $parlay_transaction_list[$parlay_transaction->refId] = $parlay_transaction;
            }
            $old_transactions = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), array_keys($parlay_transaction_list), 'external_unique_id', self::TRANSACTION_DEBIT);
            if(!empty($old_transactions) && count($old_transactions) == count($parlay_transaction_list)) {
                $transaction_data['code'] = self::RETURN_OK;
                foreach($old_transactions as $old_transaction) {
                    $transaction_data['transaction_id'][] = $old_transaction->transaction_id;
                    $transaction_data['external_unique_id'][] = $old_transaction->external_unique_id;
                }
                return true;
            }

            if($this->requestParams->params->message->totalBetAmount < 0) {
                $transaction_data['code'] = self::ERROR_BAD_REQUEST;
                $this->utils->debug_log("IBC ONEBOOK SEAMLESS API (adjustWallet) parlay total bet amount less then 0", $this->requestParams->params->message->totalBetAmount);
                return false;
            }
            $transaction_data = $this->adjustWallet(self::TRANSACTION_DEBIT, $this->requestParams->params->message->totalBetAmount, ['bet_type' => self::TRANSACTION_PARLAY]);
            if($transaction_data['code']!= self::RETURN_OK) {
                return false;
            }
            return true;
        });

        if($transaction_result) {
            foreach($transaction_data['transaction_id'] as $key => $transaction) {
                $data['txns'][] = [
                    'licenseeTxId' => $transaction_data['transaction_id'][$key],
                    'refId' => $transaction_data['external_unique_id'][$key],
                ];
            }
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
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

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];
        $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data) {
            $parlay_transaction_list = [];
            foreach($this->requestParams->params->message->txns as $transaction) {
                $parlay_transaction_list[$transaction->licenseeTxId] = $transaction;
            }
            $old_transactions = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), array_keys($parlay_transaction_list), 'transaction_id', self::TRANSACTION_DEBIT);
            if(!empty($old_transactions) && count($old_transactions) == count($parlay_transaction_list)) {
                $transaction_data['code'] = self::RETURN_OK;
                $transaction_data['after_balance'] = $this->getPlayerBalance();
                return true;
            }

            if(!empty($old_transactions) && count($old_transactions) == count($parlay_transaction_list)) {

                $changes = [];
                $totalAmountChanges = 0;
                foreach($old_transactions as $old_transaction) {
                    $transaction_id = $old_transaction->transaction_id;
                    $bet_amount = $old_transaction->amount;
                    $confirm_amount = $parlay_transaction_list[$transaction_id]->actualAmount;

                    if($bet_amount < $confirm_amount) {
                        // deduct because on confirm odds get bigger.
                        $totalAmountChanges -= $confirm_amount - $bet_amount;
                        $changes = [
                            'transaction_type' => self::TRANSACTION_ADJUST_DEBIT,
                            'status' => 'ok',
                            'external_unique_id' => $transaction_id . '-' . self::TRANSACTION_ADJUST_DEBIT,
                            'extra_info' => [
                                'raw_request' => $this->requestParams->params,
                            ],
                            'amount' => $confirm_amount - $bet_amount,
                            'external_unique_id' => $parlay_transaction_list[$transaction_id]->txId,
                            'transaction_id' => $parlay_transaction_list[$transaction_id]->txId,
                        ];
                        $totalAmountChanges[] = $changes;
                    }
                    else if($bet_amount < $confirm_amount) {
                        // add because on confirm odds get bigger.
                        $totalAmountChanges += $bet_amount - $confirm_amount;
                        $changes[] = [
                            'transaction_type' => self::TRANSACTION_ADJUST_CREDIT,
                            'status' => 'ok',
                            'external_unique_id' => $transaction_id . '-' . self::TRANSACTION_ADJUST_CREDIT,
                            'extra_info' => [
                                'raw_request' => $this->requestParams->params,
                            ],
                            'amount' => $bet_amount - $confirm_amount,
                            'external_unique_id' => $parlay_transaction_list[$transaction_id]->txId,
                            'transaction_id' => $parlay_transaction_list[$transaction_id]->txId,
                        ];
                    }
                }

                if($totalAmountChanges > 0) {
                    $this->adjustWallet(self::TRANSACTION_CREDIT, $totalAmountChanges, $changes);
                }
                else if($totalAmountChanges < 0) {
                    $this->adjustWallet(self::TRANSACTION_DEBIT, $totalAmountChanges, $changes);
                }
                $transaction_data['code'] = self::RETURN_OK;
                $transaction_data['after_balance'] = $this->getPlayerBalance();
                return true;
            }
            else {
                $transaction_data['code'] = self::ERROR_BET_NOT_FOUND;
            }
        });

        if($transaction_result) {
            $data = [
                'balance' => $transaction_data['after_balance'],
            ];
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
        }
    }

    public function settle() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.txns' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_list = [];
        foreach($this->requestParams->params->message->txns as $transaction) {
            $updateTime = strtotime($transaction->updateTime);
            $transaction_list["{$transaction->txId}-credit-{$updateTime}"] = $transaction;
        }
        $old_transactions = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), array_keys($transaction_list), 'external_unique_id', self::TRANSACTION_CREDIT);
        if(!empty($old_transactions) && count($old_transactions) == count($transaction_list)) {
            return $this->setResponse(self::RETURN_OK);
        }
        $transaction_result = false;
        foreach($transaction_list as $transaction) {

            $this->CI->load->model('common_token');
            $this->currentPlayer = $this->common_token->getPlayerCompleteDetailsByGameUsername($transaction->userId, $this->api->getPlatformCode());
            if(!$this->currentPlayer) {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }

            $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data, $transaction) {
                if($transaction->payout < 0) {
                    $transaction_data['code'] = self::ERROR_BET_NOT_ALLOWED;
                    return false;
                }
                $transaction_data = $this->adjustWallet(self::TRANSACTION_CREDIT, $transaction->payout, ['transaction_details' => $transaction]);
                if($transaction_data['code']!= self::RETURN_OK) {
                    return false;
                }
                return true;
            });
            if(!$transaction_result) {
                break;
            }
        }

        if($transaction_result) {
            return $this->setResponse($transaction_data['code']);
        }
        else {
            $rollback_status = $this->doManualRollback(self::TRANSACTION_DEBIT);
            if(!$rollback_status) {
                return $this->setResponse(self::ERROR_DATABASE_ERROR);
            }
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
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
        
        $this->preProcessRequest(__FUNCTION__, $rule_set);

        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data) {

            foreach($this->requestParams->params->message->txns as $transaction) 
            {
                $old_transaction = $this->wallet_transactions->getTransactionObjectByField($this->api->getPlatformCode(), $transaction->refId, 'external_unique_id');
                if(!empty($old_transaction) && $old_transaction->transaction_type == self::TRANSACTION_CANCELLED) {
                    continue;
                }
                if(empty($old_transaction)) {
                    $transaction_data['code'] = self::ERROR_NO_SUCH_TICKET;
                    return false;
                }
                $transaction_data = $this->adjustWallet(self::TRANSACTION_ROLLBACK, $old_transaction->amount);
                if($transaction_data['code']!= self::RETURN_OK) {
                    return false;
                }
                else {
                    $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $transaction->refId, ['transaction_type' => self::TRANSACTION_CANCELLED]);
                    return true;
                }
            }
            $transaction_data['after_balance'] = $this->getPlayerBalance();
            return true;
        });

        if($transaction_result) {
            $data = [
                'balance' => $transaction_data['after_balance'],
            ];
            return $this->setResponse($transaction_data['code'], $data);
        }
        else {
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
        }

    }

    public function unsettle() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.txns' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_list = [];
        foreach($this->requestParams->params->message->txns as $transaction) {
            $updateTime = strtotime($transaction->updateTime);
            $transaction_list["{$transaction->txId}-credit-{$updateTime}"] = $transaction;
        }
        $old_transactions = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), array_keys($transaction_list), 'external_unique_id', self::TRANSACTION_CANCELLED);
        if(!empty($old_transactions) && count($old_transactions) == count($transaction_list)) {
            return $this->setResponse(self::RETURN_OK);
        }
        $transaction_result = false;
        foreach($transaction_list as $transaction) {
            $old_transaction = array_filter(
                $old_transactions,
                function ($row) use ($transaction) {
                    return $row->transaction_id == $transaction->txId;
                }
            );
            if(!empty($old_transaction)) {
                continue; // exist
            }
            $this->CI->load->model('common_token');
            $this->currentPlayer = $this->common_token->getPlayerCompleteDetailsByGameUsername($transaction->userId, $this->api->getPlatformCode());
            if(!$this->currentPlayer) {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }

            $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data, $transaction, $old_transaction) {
                if($transaction->payout < 0) {
                    $transaction_data['code'] = self::ERROR_BET_NOT_ALLOWED;
                    return false;
                }
                $transaction_data = $this->adjustWallet(self::TRANSACTION_UNSETTLE, $transaction->payout, ['transaction_details' => $transaction]);
                if($transaction_data['code']!= self::RETURN_OK) {
                    return false;
                }
                $updateTime = strtotime($transaction->updateTime);
                $external_transaction_id = "{$transaction->txId}-credit-{$updateTime}";
                $this->wallet_transactions->updateTransaction($this->api->getPlatformCode(), $external_transaction_id, ['transaction_type' => self::TRANSACTION_CANCELLED, 'status' => 'cancelled']);
                return true;
            });
            if(!$transaction_result) {
                break;
            }
        }

        if($transaction_result) {
            return $this->setResponse($transaction_data['code']);
        }
        else {
            $rollback_status = $this->doManualRollback(self::TRANSACTION_CREDIT);
            if(!$rollback_status) {
                return $this->setResponse(self::ERROR_DATABASE_ERROR);
            }
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
        }
    }
    
    public function resettle() {
        $rule_set = [
            'message.action' => 'required',
            'message.operationId' => 'required',
            'message.txns' => 'required',
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);
        $controller = $this;
        $transaction_data = [
            'code' => self::RETURN_OK
        ];

        $transaction_list = [];
        foreach($this->requestParams->params->message->txns as $transaction) {
            $updateTime = strtotime($transaction->updateTime);
            $transaction_list["{$transaction->txId}-resettle-{$updateTime}"] = $transaction;
        }
        $old_transactions = $this->wallet_transactions->getTransactionObjectsByField($this->api->getPlatformCode(), array_keys($transaction_list), 'external_unique_id', self::TRANSACTION_CANCELLED);
        if(!empty($old_transactions) && count($old_transactions) == count($transaction_list)) {
            return $this->setResponse(self::RETURN_OK);
        }
        $transaction_result = false;
        foreach($transaction_list as $transaction) {
            $old_transaction = array_filter(
                $old_transactions,
                function ($row) use ($transaction) {
                    return $row->transaction_id == $transaction->txId;
                }
            );
            if(!empty($old_transaction)) {
                continue; // exist
            }
            $this->CI->load->model('common_token');
            $this->currentPlayer = $this->common_token->getPlayerCompleteDetailsByGameUsername($transaction->userId, $this->api->getPlatformCode());
            if(!$this->currentPlayer) {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }

            $transaction_result = $this->lockAndTransForPlayerBalance($this->currentPlayer->player_id, function() use(&$transaction_data, $transaction, $old_transaction) {
                if($transaction->payout < 0) {
                    $transaction_data['code'] = self::ERROR_BET_NOT_ALLOWED;
                    return false;
                }
                $transaction_data = $this->adjustWallet(self::TRANSACTION_RESETTLE, $transaction->payout, ['transaction_details' => $transaction]);
                if($transaction_data['code']!= self::RETURN_OK) {
                    return false;
                }
            });
            if(!$transaction_result) {
                break;
            }
        }

        if($transaction_result) {
            return $this->setResponse($transaction_data['code']);
        }
        else {
            $rollback_status = $this->doManualRollback(self::TRANSACTION_CREDIT);
            if(!$rollback_status) {
                return $this->setResponse(self::ERROR_DATABASE_ERROR);
            }
            if($transaction_data['code'] != self::RETURN_OK) {
                return $this->setResponse($transaction_data['code']);
            }
            return $this->setResponse(self::ERROR_DATABASE_ERROR);
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

    private function adjustWallet($transaction_type, $amount, $extra = []) {
        $return_data = [
            'code' => self::RETURN_OK
        ];
        $wallet_transaction = [];
        $return_data['before_balance'] = $this->getPlayerBalance();
        if($amount == 0) {
            $wallet_transaction['amount'] = $amount;
            $return_data['after_balance'] = $return_data['before_balance'];
        }
        else {
            if($transaction_type == self::TRANSACTION_CREDIT || $transaction_type == self::TRANSACTION_CANCELLED || $transaction_type == self::TRANSACTION_ROLLBACK) {
                $transfer_status = $this->transferGameWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), 'credit', $amount);
                if(!$transfer_status) {
                    $return_data['code']=self::ERROR_BET_NOT_ALLOWED;
                }
                else {
                    $wallet_transaction['amount'] = $amount;
                }
                $return_data['after_balance'] = $this->getPlayerBalance();
            }
            else if($transaction_type == self::TRANSACTION_DEBIT || $transaction_type == self::TRANSACTION_RESETTLE) {
                if($return_data['before_balance'] < $amount && $transaction_type != self::TRANSACTION_RESETTLE) {
                    $return_data['code'] = self::ERROR_INSUFFICIENT_BALANCE;
                    $return_data['after_balance'] = $return_data['before_balance'];
                    $this->utils->debug_log("IBC ONEBOOK SEAMLESS API (adjustWallet) insufficientBalance:", 'amount', $amount, 'before_balance', $return_data['before_balance']);
                }
                else {
                    $transfer_status = $this->transferGameWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), 'debit', $amount);
                    if(!$transfer_status) {
                        $return_data['code']=self::ERROR_BET_NOT_ALLOWED;
                    }
                    else {
                        $wallet_transaction['amount'] = $amount * -1;
                    }
                    $return_data['after_balance'] = $this->getPlayerBalance();
                }
            }
            else {
                if($amount <= 0) {
                    $transfer_status = $this->transferGameWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), 'credit', abs($amount));
                    if(!$transfer_status) {
                        $return_data['code']=self::ERROR_BET_NOT_ALLOWED;
                    }
                    else {
                        $wallet_transaction['amount'] = $amount;
                    }
                    $return_data['after_balance'] = $this->getPlayerBalance($this->currentPlayer->username, $this->currentPlayer->player_id);
                }
                else if($amount > 0) {
                    $transfer_status = $this->transferGameWallet($this->currentPlayer->player_id, $this->api->getPlatformCode(), 'debit', $amount);
                    if(!$transfer_status) {
                        $return_data['code']=self::ERROR_BET_NOT_ALLOWED;
                    }
                    else {
                        $wallet_transaction['amount'] = $amount * -1;
                    }
                    $return_data['after_balance'] = $this->getPlayerBalance($this->currentPlayer->username, $this->currentPlayer->player_id);
                }
            }
        }

        if(array_key_exists('bet_type', $extra)) { //bet parlay
            $parlay_after_balance =  $return_data['before_balance'];
            foreach($this->requestParams->params->message->txns as $parlay_transaction) {
                $wallet_transaction = [];
                $ticket_detail = null;
                foreach($parlay_transaction->detail as $detail) {
                    if(array_key_exists('matchId', (array) $detail)) {
                        $ticket_detail = $this->getTicketDetail($detail->matchId);
                        break;
                    }
                }
                $extra_info = [
                    'operation_id' => $parlay_transaction->refId,
                    'currency' => $this->requestParams->params->message->currency,
                    'match_id' => isset($parlay_transaction->detail->matchId) ? $parlay_transaction->detail->matchId : null,
                    'home_id' => isset($ticket_detail->homeId) ? $ticket_detail->homeId : null,
                    'away_id' => isset($ticket_detail->awayId) ? $ticket_detail->awayId : null,
                    'odds_id' => isset($ticket_detail->oddsId) ? $ticket_detail->oddsId : null,
                    'league_id' => isset($ticket_detail->leagueId) ? $ticket_detail->leagueId : null,
                    'update_time' => $this->requestParams->params->message->updateTime,
                    'ip' => $this->requestParams->params->message->IP,
                    'is_live' => isset($ticket_detail->isLive) ? $ticket_detail->isLive : null,
                    'bet_details' => [
                        'home_name' => isset($ticket_detail->homeName) ? $ticket_detail->homeName : null,
                        'away_name' => isset($ticket_detail->awayName) ? $ticket_detail->awayName : null,
                        'bet_type' => isset($ticket_detail->betType) ? $ticket_detail->betType : null,
                        'kick_off_time' => isset($ticket_detail->kickOffTime) ? $ticket_detail->kickOffTime : null,
                        'bet_time' => $this->requestParams->params->message->betTime,
                        'bet_amount' => $parlay_transaction->betAmount,
                        'sport_type_name' => isset($ticket_detail->sportTypeName) ? $ticket_detail->sportTypeName : null,
                        'bet_type_name' => isset($ticket_detail->betTypeName) ? $ticket_detail->betTypeName : null,
                        'odds' => isset($ticket_detail->odds) ? $ticket_detail->odds : null,
                        'bet_choice' => isset($ticket_detail->betChoice) ? $ticket_detail->betChoice : null,
                        'league_name' => isset($ticket_detail->leagueName) ? $ticket_detail->leagueName : null,
                    ],
                    'raw_request' => $this->requestParams->params
                ];

                $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
                $wallet_transaction['amount'] = $parlay_transaction->betAmount;
                $wallet_transaction['before_balance'] = $parlay_after_balance;
                $wallet_transaction['after_balance'] = $parlay_after_balance = $parlay_after_balance - $parlay_transaction->betAmount;
                $wallet_transaction['player_id'] = $this->currentPlayer->player_id;
                $wallet_transaction['game_id'] = isset($ticket_detail->sportTypeName) ? $ticket_detail->sportTypeName : null;
                $wallet_transaction['status'] = 'ok';
                $wallet_transaction['transaction_id'] = $this->api->getSecureId('common_seamless_wallet_transactions', 'transaction_id', true, '');
                $wallet_transaction['extra_info'] = json_encode($extra_info);
                $wallet_transaction['start_at'] = $this->requestParams->params->message->betTime;
                $wallet_transaction['end_at'] = $this->requestParams->params->message->betTime;
                $wallet_transaction['external_unique_id'] = $parlay_transaction->refId;
                $wallet_transaction['round_id'] = $parlay_transaction->refId;
                $wallet_transaction['transaction_type'] = $transaction_type;

                if($return_data['code'] == self::RETURN_OK) {
                    $wallet_transaction['id'] = $this->wallet_transactions->insertRow($wallet_transaction);
                }

                $return_data['external_unique_id'][] = $wallet_transaction['external_unique_id'];
                $return_data['transaction_id'][] = $wallet_transaction['transaction_id'];
            }
        }
        else if(array_key_exists('transaction_type', $extra)) { // confirm bet and confirm parlay

            if($extra['transaction_type'] == self::TRANSACTION_ADJUST_CREDIT || $extra['transaction_type'] == self::TRANSACTION_ADJUST_DEBIT) {
                $extra_info = [
                    'raw_request' => $this->requestParams->params
                ];

                $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
                $wallet_transaction['amount'] = $extra['amount'];
                $wallet_transaction['before_balance'] = $return_data['before_balance'];
                $wallet_transaction['after_balance'] = $return_data['after_balance'];
                $wallet_transaction['player_id'] = $this->currentPlayer->player_id;
                $wallet_transaction['game_id'] = null;
                $wallet_transaction['status'] = 'ok';
                $wallet_transaction['transaction_id'] = $this->api->getSecureId('common_seamless_wallet_transactions', 'transaction_id', true, '');
                $wallet_transaction['extra_info'] = json_encode($extra_info);
                $wallet_transaction['start_at'] = $this->utils->getNowForMysql();
                $wallet_transaction['end_at'] = $this->utils->getNowForMysql();
                $wallet_transaction['external_unique_id'] = $extra['transaction_id'] . '-' . $transaction_type . '-' . strtotime($extra['transaction_details']->updateTime);
                $wallet_transaction['round_id'] = $extra['transaction_id'];
                $wallet_transaction['transaction_type'] = self::TRANSACTION_ADJUST_CREDIT;
            }

            if($return_data['code'] == self::RETURN_OK) {
                $wallet_transaction['id'] = $this->wallet_transactions->insertRow($wallet_transaction);
            }

            $return_data['external_unique_id'][] = $wallet_transaction['external_unique_id'];
            $return_data['transaction_id'] = $wallet_transaction['transaction_id'];

            $this->wallet_transaction_list[] = $wallet_transaction;

        }
        else if($transaction_type == self::TRANSACTION_CREDIT || $transaction_type == self::TRANSACTION_ROLLBACK || $transaction_type == self::TRANSACTION_UNSETTLE) {

            if(array_key_exists('transaction_details', $extra)) { // settle
                $extra_info = [
                    'transaction_detail' => $extra['transaction_details'],
                    'raw_request' => $this->requestParams->params
                ];
    
                $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
                $wallet_transaction['amount'] = $extra['transaction_details']->payout;
                $wallet_transaction['before_balance'] = $return_data['before_balance'];
                $wallet_transaction['after_balance'] = $return_data['after_balance'];
                $wallet_transaction['player_id'] = $this->currentPlayer->player_id;
                $wallet_transaction['game_id'] = null;
                $wallet_transaction['status'] = 'ok';
                $wallet_transaction['transaction_id'] = $this->api->getSecureId('common_seamless_wallet_transactions', 'transaction_id', true, '');
                $wallet_transaction['extra_info'] = json_encode($extra_info);
                $wallet_transaction['start_at'] = $extra['transaction_details']->winlostDate;
                $wallet_transaction['end_at'] = $extra['transaction_details']->winlostDate;
                $wallet_transaction['external_unique_id'] = $extra['transaction_details']->txId . '-' . $transaction_type . '-' . strtotime($extra['transaction_details']->updateTime);
                $wallet_transaction['round_id'] = $extra['transaction_details']->txId;
                $wallet_transaction['transaction_type'] = $transaction_type;
    
                if($return_data['code'] == self::RETURN_OK) {
                    $wallet_transaction['id'] = $this->wallet_transactions->insertRow($wallet_transaction);
                }

                $return_data['external_unique_id'][] = $wallet_transaction['external_unique_id'];
                $return_data['transaction_id'] = $wallet_transaction['transaction_id'];

                $this->wallet_transaction_list[] = $wallet_transaction;
            }
            else { //cancel bet or unsettle

            }
        }
        else {
            $extra_info = [
                // 'operation_id' => $this->requestParams->params->message->operationId,
                // 'currency' => $this->requestParams->params->message->currency,
                // 'match_id' => $this->requestParams->params->message->matchId,
                // 'home_id' => $this->requestParams->params->message->homeId,
                // 'away_id' => $this->requestParams->params->message->awayId,
                // 'odds_id' => $this->requestParams->params->message->oddsId,
                // 'league_id' => $this->requestParams->params->message->leagueId,
                // 'odds_type' => $this->requestParams->params->message->oddsType,
                // 'sport_type' => $this->requestParams->params->message->sportType,
                // 'update_time' => $this->requestParams->params->message->updateTime,
                // 'ip' => $this->requestParams->params->message->IP,
                // 'is_live' => $this->requestParams->params->message->isLive,
                // 'bet_details' => [
                //     'home_name' => $this->requestParams->params->message->homeName,
                //     'away_name' => $this->requestParams->params->message->awayName,
                //     'bet_type' => $this->requestParams->params->message->betType,
                //     'kick_off_time' => $this->requestParams->params->message->kickOffTime,
                //     'bet_time' => $this->requestParams->params->message->betTime,
                //     'bet_amount' => $this->requestParams->params->message->betAmount,
                //     'actual_amount' => $this->requestParams->params->message->actualAmount,
                //     'sport_type_name' => $this->requestParams->params->message->sportTypeName,
                //     'bet_type_name' => $this->requestParams->params->message->betTypeName,
                //     'odds' => $this->requestParams->params->message->odds,
                //     'bet_choice' => $this->requestParams->params->message->betChoice,
                //     'league_name' => $this->requestParams->params->message->leagueName,
                // ],
                'raw_request' => $this->requestParams->params
            ];

            $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
            $wallet_transaction['amount'] = $this->requestParams->params->message->actualAmount;
            $wallet_transaction['before_balance'] = $return_data['before_balance'];
            $wallet_transaction['after_balance'] = $return_data['after_balance'];
            $wallet_transaction['player_id'] = $this->currentPlayer->player_id;
            $wallet_transaction['game_id'] = $this->requestParams->params->message->sportTypeName;
            $wallet_transaction['status'] = 'ok';
            $wallet_transaction['transaction_id'] = $this->api->getSecureId('common_seamless_wallet_transactions', 'transaction_id', true, '');
            $wallet_transaction['extra_info'] = json_encode($extra_info);
            $wallet_transaction['start_at'] = $this->requestParams->params->message->betTime;
            $wallet_transaction['end_at'] = $this->requestParams->params->message->betTime;
            $wallet_transaction['external_unique_id'] = $this->requestParams->params->message->refId;
            $wallet_transaction['round_id'] = $this->requestParams->params->message->operationId;
            $wallet_transaction['transaction_type'] = $transaction_type;

            if($return_data['code'] == self::RETURN_OK) {
                $wallet_transaction['id'] = $this->wallet_transactions->insertRow($wallet_transaction);
            }

            $return_data['external_unique_id'] = $wallet_transaction['external_unique_id'];
            $return_data['transaction_id'] = $wallet_transaction['transaction_id'];

            $this->wallet_transaction_list[] = $wallet_transaction;
        }


        return $return_data;
    }

    private function getTicketDetail($match_id) {
        $data = null;
        foreach($this->requestParams->params->TicketDetail as $ticket_detail) {
            if($ticket_detail->matchId === $match_id) {
                $data = $ticket_detail;
                break;
            }
        }
        return $data;
    }

    private function validateRequest($rule_set) {
        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if($rule == 'required' && is_null($this->getValueByKey($key, $this->requestParams->params))) {
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

    private function preProcessRequest($functionName, $rule_set = []) {
        $params = $this->input->post() ?: [];
        if(!$params) {
            $params = json_decode(file_get_contents("php://input")) ?: [];
        }
        $this->requestParams->function = $functionName;
        $this->requestParams->params = $params;
        $this->utils->debug_log('IBC ONEBOOK SEAMLESS API (preProcessRequest):', 'api', $this->api->getPlatformCode(), '$params', $params);

        if(!isset($params->key) || (isset($params->key) && $this->api->key != $params->key)) {
            return $this->setResponse(self::ERROR_INVALID_AUTHENTICATION_KEY);
        }

        $isValid = $this->validateRequest($rule_set);

        if(!$isValid) {
            return $this->setResponse(self::ERROR_BAD_REQUEST);
        }

        if(isset($this->requestParams->params->message->userId)) {
            $this->CI->load->model('common_token');
            $this->currentPlayer = $this->common_token->getPlayerCompleteDetailsByGameUsername($this->requestParams->params->message->userId, $this->api->getPlatformCode());
            if(!$this->currentPlayer) {
                return $this->setResponse(self::ERROR_NOT_FOUND_PLAYER);
            }
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

        $data = json_encode($data);

        if($this->api) {
            $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->function,
                json_encode($this->requestParams->params),
                $data,
                200,
                null,
                null
            );
        }

        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }
}