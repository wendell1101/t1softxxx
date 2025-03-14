<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class DG_service_api extends BaseController {

    const RETURN_OK = [
        'codeId' => 0,
        'message' => 'Operation Successful'
    ];

    const ERROR_COMMAND_NOT_FOUND = [
        'codeId' => 3,
        'message' => 'Command Not Find'
    ];

    const WRONG_TOKEN = [
        'codeId' => 2,
        'message' => 'Token Verification Failed'
    ];

    const RETURN_NOT_ENOUGH_BALANCE = [
        'codeId' => 120,
        'message' => 'Insufficient balance'
    ];

    const RETURN_FAILED = [
        'codeId' => 98,
        'message' => 'Operation failed'
    ];

    const RETURN_IP_RESTRICTED = [
        'codeId' => 400,
        'message' => 'Client IP Restricted'
    ];

    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_NORMAL = 'normal';
    const TRANSACTION_CANCELLED = 'cancel';

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';

    const REASON_NOT_ENOUGH_BALANCE=2;

    private $transaction_for_fast_track = null;
    private $http_status_code;

    public function __construct() {

        parent::__construct();


        $this->api = $this->utils->loadExternalSystemLibObject(DG_SEAMLESS_API);
        $this->token = md5($this->api->agent_name . $this->api->api_key);
        $this->CI->load->model('dg_seamless_wallet_transactions', 'dg_transactions');
        $this->transactionId = null;
        $this->requestParams = new stdClass();
        $this->http_status_code = 200;

        if (!$this->api->validateWhiteIP()) {
            $this->requestParams->function = 'validateWhiteIP';
            $this->http_status_code = self::RETURN_IP_RESTRICTED['codeId'];
            return $this->setOutput(self::RETURN_IP_RESTRICTED);
        }
    }

    private function isAgentValid($agentName) {
        if ($agentName != $this->api->agent_name) {
            $data = [
                'message' => 'Can not find the specified Agent',
                'codeId' => 118
            ];

            $this->setOutput($data);

            return false;
        }
        return true;
    }

    private function isMethodExist($function) {
        if (!method_exists($this, $function)) {
            $data = [
                'message' => 'Command Not Find',
                'codeId' => 3
            ];

            $this->setOutput($data);

            return false;
        }
        return true;
    }

    public function account($function, $agentName) {
        $this->requestParams->function = $function;
        $params = file_get_contents('php://input');
        $params = json_decode($params);
        $this->requestParams->params = $params;
        $function = 'rgAccount' . ucfirst($function);
        if(!$this->isAgentValid($agentName)) {
            return;
        }
        
        if(!$this->isMethodExist($function)) {
            return;
        }

        if($params->token != $this->token) {
            $data = [
                'message' => 'Token Verification Failed',
                'codeId' => 2
            ];

            $this->setOutput($data);
            return;
        }

        $this->$function($params);
        return;
    }

    public function user($function, $agentName) {
        $this->requestParams->function = $function;
        $params = file_get_contents('php://input');
        $params = json_decode($params);
        $this->requestParams->params = $params;
        $function = 'rgUser' . ucfirst($function);
        if(!$this->isAgentValid($agentName)) {
            return;
        }
        
        if(!$this->isMethodExist($function)) {
            return;
        }

        if(!$this->token && ($params->token != $this->token)) {
            $data = [
                'message' => 'Token Verification Failed',
                'codeId' => 2
            ];

            $this->setOutput($data);
            return;
        }

        $this->$function($params);
        return;
    }

    private function rgUserGetBalance($params) {

        $userName = $this->api->getPlayerUsernameByGameUsername($params->member->username);

        $return = [];

        if($userName) {
            $balance = $this->api->queryPlayerBalance($userName)['balance'];

            $return = [
                'codeId' => 0,
                'token' => $this->token,
                'member'=> [
                    'username' => $params->member->username,
                    'balance' => $balance
                ]
            ];
        }
        else {
            $return = [
                'codeId' => 114,
                'message' => 'Member not found'
            ];
        }
        $this->setOutput($return);
        return;
    }

    private function rgAccountTransfer($params) {

        $rules = [
            'token' => 'required',
            'ticketId' => 'required',
            'data' => 'required',
            'member.username' => 'required',
            'member.amount' => 'required',
        ];

        $badRequest = !$this->validateRequest($params, $rules);

        //TODO: badRequest

        $params->member->real_username = $userName = $this->api->getPlayerUsernameByGameUsername($params->member->username);
        $amount = $params->member->amount;

        $return = [];

        if($userName) {
            $params->member->player_id = $playerId = $this->api->getPlayerIdInPlayer($userName);

            // TODO: add to logs
            // $params->ticketId
            // $params->data

            $controller = $this;
            $transaction = [];
            if($amount != 0) {
                $this->lockAndTransForPlayerBalance($playerId, function() use($userName, $controller, $playerId, $params, $amount, &$transaction) {
                    if($amount < 0) {
                        $result = $this->doDebitTransaction($params);
                    }
                    else {
                        $result = $this->doCreditTransaction($params);
                    }
                    if($result['success']) {
                        $transaction = [
                            'success' => $result['success'],
                            'beforeBalance' => $result['params']->before_balance,
                            'afterBalance' => $result['params']->after_balance,
                            'reason' => $result['reason'],
                        ];
                    }
                    else {
                        $transaction = [
                            'success' => $result['success'],
                            'reason' => $result['reason'],
                        ];
                    }

                    return $result['success'];
                });
            }
            else {
                $transaction['success'] = self::RETURN_OK;
                $transaction['beforeBalance'] = $this->api->queryPlayerBalance($userName)['balance'];
            }

            if($transaction['success']) {
                $return = [
                    'codeId' => 0,
                    'token' => $this->token,
                    'data' => $params->data,
                    'member'=> [
                        'username' => $params->member->username,
                        'amount' => $params->member->amount,
                        'balance' => $transaction['beforeBalance']
                    ]
                ];
            }
            else {
                $return = $transaction;
                $return = [
                    'codeId' => $transaction['reason']['codeId'],
                    'message' => $transaction['reason']['message'],
                ];
            }
        }
        else {
            $return = [
                'codeId' => 114,
                'message' => 'Member not found'
            ];
        }
        $this->setOutput($return);
        return;
    }

    private function doCreditTransaction($params, $transaction_type = self::TRANSACTION_CREDIT, $allow_duplicate_trans_id = false) {

        $success = [
            'success' => false,
            'reason' => self::RETURN_FAILED,
            'params' => $params
        ];

        $params->transaction_type = $transaction_type;
        $params->before_balance = $before_balance = $this->api->queryPlayerBalance($params->member->real_username)['balance'];

        $oldTransaction = $this->dg_transactions->searchByTransactionIdAndType($params->data, $transaction_type, $allow_duplicate_trans_id);

        if(!$oldTransaction || ($oldTransaction && $oldTransaction['transaction_type'] == self::TRANSACTION_CANCELLED && $allow_duplicate_trans_id)) {
            $wallet_result = $this->wallet_model->incSubWallet($params->member->player_id, $this->api->getPlatformCode(), $params->member->amount);

            $params->after_balance = $after_balance = $this->api->queryPlayerBalance($params->member->real_username)['balance'];
            $this->utils->debug_log('DG Seamless subwallet credit change', 'params', $params);
            if($wallet_result) {
                $success = [
                    'success' => true,
                    'reason' => self::RETURN_OK,
                    'params' => $params
                ];
                $this->insertTransactionLog($params);
            }
        }
        else {
            $params->after_balance = $params->before_balance;
            $success = [
                'success' => true,
                'reason' => self::RETURN_FAILED,
                'params' => $params
            ];
        }

        return $success;
    }

    private function doDebitTransaction($params, $transaction_type = self::TRANSACTION_DEBIT, $allow_duplicate_trans_id = false) {

        $success = [
            'success' => false,
            'reason' => self::RETURN_FAILED,
            'params' => $params
        ];

        $params->transaction_type = self::TRANSACTION_DEBIT;
        $params->before_balance = $before_balance = $this->api->queryPlayerBalance($params->member->real_username)['balance'];

        $oldTransaction = $this->dg_transactions->searchByTransactionIdAndType($params->data, $transaction_type, $allow_duplicate_trans_id);

        if(!$oldTransaction) {

            if($params->before_balance >= abs($params->member->amount)) {
                $wallet_result = $this->wallet_model->decSubWallet($params->member->player_id, $this->api->getPlatformCode(), abs($params->member->amount));
    
                $params->after_balance = $after_balance = $this->api->queryPlayerBalance($params->member->real_username)['balance'];
                $this->utils->debug_log('DG Seamless subwallet debig change', 'params', $params);
                if($wallet_result) {
                    $success = [
                        'success' => true,
                        'reason' => self::RETURN_OK,
                        'params' => $params
                    ];
                    $this->insertTransactionLog($params);
                }
            }
            else {
                $params->after_balance = $params->before_balance;
                $success = [
                    'success' => false,
                    'reason' => self::RETURN_NOT_ENOUGH_BALANCE,
                    'params' => $params
                ];
            }
        }
        else {
            $params->after_balance = $params->before_balance;
            $success = [
                'success' => true,
                'reason' => self::RETURN_OK,
                'params' => $params
            ];
        }

        return $success;
    }

    private function doCancelTransaction($params) {

        $success = [
            'success' => false,
            'reason' => self::RETURN_FAILED
        ];

        $params->transaction_type = self::TRANSACTION_ROLLBACK;
        $params->before_balance = $before_balance = $this->api->queryPlayerBalance($params->member->real_username)['balance'];

        $oldTransaction = $this->dg_transactions->searchByTransactionIdAndType($params->data, $params->transaction_type);

        if($params->member->amount < 0) {
            // cancel old
            if($oldTransaction) {
                $this->utils->debug_log('DG Seamless cancel old transaction', 'oldTransaction', $oldTransaction);
                $this->dg_transactions->cancelTransaction($params->data);

                $params->member->amount = abs($params->member->amount);
                $this->doCreditTransaction($params, $params->transaction_type, true);
                $params->after_balance = $after_balance = $this->api->queryPlayerBalance($params->member->real_username)['balance'];
            }
            else {
                $params->after_balance = $before_balance;
            }
            $success = [
                'success' => true,
                'reason' => self::RETURN_OK,
                'params' => $params
            ];
        }
        else {
            if(!$oldTransaction) {
                $success = $this->doCreditTransaction($params, $params->transaction_type);
            }
            else {
                $params->after_balance = $params->before_balance;
                $success = [
                    'success' => true,
                    'reason' => self::RETURN_OK,
                    'params' => $params
                ];
            }
        }

        return $success;
    }

    private function insertTransactionLog($params) {
        $inserted = $this->dg_transactions->insertTransaction($params);
        if($inserted) {
            $this->transaction_for_fast_track = null;
            $this->transaction_for_fast_track = $params;
            $this->transaction_for_fast_track->id = $this->CI->dg_transactions->getLastInsertedId();
            $this->utils->debug_log('DG Seamless insert transaction log success');
        }
        else {
            $this->utils->debug_log('DG Seamless insert transaction log failed');
        }
    }

    private function rgAccountCheckTransfer($params) {

        $rules = [
            'token' => 'required',
            'data' => 'required',
        ];

        $badRequest = !$this->validateRequest($params, $rules);


        $transaction = $this->dg_transactions->searchByExternalTransactionId($params->data);

        if($transaction) {
            $return = [
                'codeId' => 0,
                'token' => $this->token
            ];
        }
        else {
            $return = [
                'codeId' => 98,
                'message' => 'Operation failed',
            ];
        }
        $this->setOutput($return);
        return;
    }

    private function rgAccountInform($params) {

        $rules = [
            'token' => 'required',
            'ticketId' => 'required',
            'data' => 'required',
            'member.username' => 'required',
            'member.amount' => 'required',
        ];

        $badRequest = !$this->validateRequest($params, $rules);

        //TODO: badRequest

        $params->member->real_username = $userName = $this->api->getPlayerUsernameByGameUsername($params->member->username);
        $amount = $params->member->amount;

        $return = [];

        if($userName) {
            $params->member->player_id = $playerId = $this->api->getPlayerIdInPlayer($userName);

            $controller = $this;
            $transaction = [];
            if($amount != 0) {
                $this->lockAndTransForPlayerBalance($playerId, function() use($userName, $controller, $playerId, $params, $amount, &$transaction) {
                    if($amount < 0) {
                        $isCancelled = $this->dg_transactions->isCancelledTransaction($params->data);

                        if($isCancelled) {
                            $params->before_balance = $this->api->queryPlayerBalance($userName)['balance'];
                            $params->after_balance = $this->api->queryPlayerBalance($userName)['balance'];
                            $result = [
                                'success' => true,
                                'reason' => self::RETURN_OK,
                                'params' => $params
                            ];
                        }
                        else {
                            $result = $this->doCancelTransaction($params);
                        }
                    }
                    else {
                        $result = $this->doCreditTransaction($params);
                    }
                    if($result['success']) {
                        $transaction = [
                            'success' => $result['success'],
                            'beforeBalance' => $result['params']->before_balance,
                            'afterBalance' => $result['params']->after_balance,
                            'reason' => $result['reason'],
                        ];
                    }
                    else {
                        $transaction = [
                            'success' => $result['success'],
                            'reason' => $result['reason'],
                        ];
                    }
                    // print_r($result);
                    // exit;
                    return $result['success'];
                });
            }
            else {
                $transaction['success'] = self::RETURN_OK;
                $transaction['beforeBalance'] = $this->api->queryPlayerBalance($userName)['balance'];
            }

            if($transaction['success']) {
                $return = [
                    'codeId' => 0,
                    'token' => $this->token,
                    'data' => $params->data,
                    'member'=> [
                        'username' => $params->member->username,
                        'amount' => $params->member->amount,
                        'balance' => $transaction['beforeBalance']
                    ]
                ];
            }
            else {
                $return = [
                    'codeId' => $transaction['reason']['codeId'],
                    'message' => $transaction['reason']['message'],
                ];
            }
        }
        else {
            $return = [
                'codeId' => 114,
                'message' => 'Member not found'
            ];
        }
        $this->setOutput($return);
        return;
    }

    private function rgAccountOrder($params) {

        $rules = [
            'token' => 'required',
            'ticketId' => 'required'
        ];

        $badRequest = !$this->validateRequest($params, $rules);
        //TODO: badRequest
        $transactions = $this->dg_transactions->searchTicketTransactions($params->ticketId);

        $list = [];

        foreach($transactions as $transaction) {
            $list[] = [
                'username' => $this->api->getGameUsernameByPlayerId($transaction->player_id),
                'ticketId' => $transaction->ticket_id,
                'serial' => $transaction->external_transaction_id,
                'amount' => $transaction->amount,
            ];
        }

        $return = [
            'codeId' => 0,
            'token' => $this->token,
            'ticketId' => $params->ticketId,
            'list' => $list,
        ];
        
        $this->setOutput($return);
        return;

    }

    private function validateRequest($params, $rules) {
        // foreach($rules as $key => $rule) {
        //     if($rule == 'required') {
        //         if(strpos($key, '.') !== false) {
        //             $pieces = explode('.', $key);

        //             print_r($pieces);
        //             exit;
        //             if(!isset($params->$pieces[0]->$pieces[1])) {
        //                 return false;
        //             }
        //         }
        //         else {
        //             if(!isset($params->$key)) {
        //                 return false;
        //             }
        //         }
        //     }
        // }
        // return true;
        // TODO: rewrite this block
        return true;
    }

    private function setOutput($data = [], $player_id = NULL) {

        if(isset($data['member']['balance'])) {
            $data['member']['balance'] = (int) $data['member']['balance'];
        }

        $flag = $data['codeId'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $flag == Response_result::FLAG_NORMAL) {
            $this->sendToFastTrack();
        }

        $data = json_encode($data);
        $headers = getallheaders();

        $this->CI->response_result->saveResponseResult(
            $this->api->getPlatformCode(),
            $flag,
            $this->requestParams->function,
            json_encode($this->requestParams->params),
            $data,
            $this->http_status_code,
            null,
            is_array($headers) ? json_encode($headers) : $headers
        );

        return $this->output->set_status_header($this->http_status_code)->set_content_type('application/json')->set_output($data);
    }

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $betType = null;
        switch($this->transaction_for_fast_track->transaction_type) {
            case 'debit':
                $betType = 'Bet';
                break;
            case 'credit':
                $betType = 'Win';
                break;
            case 'cancel':
                $betType = 'Refund';
                break;
            default:
                $betType = null;
                break;
        }

        if ($betType == null) {
            return;
        }

        $data = [
            "activity_id" =>  strval($this->transaction_for_fast_track->id),
            "amount" => (float) abs($this->transaction_for_fast_track->member->amount),
            "balance_after" =>  $this->transaction_for_fast_track->before_balance,
            "balance_before" =>  $this->transaction_for_fast_track->after_balance,
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->api->currency,
            "exchange_rate" =>  1,
            "game_id" => "unknown",
            "game_name" => "unknown",
            "game_type" => "unknown",
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" => strval($this->transaction_for_fast_track->ticketId),
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->transaction_for_fast_track->member->player_id,
            "vendor_id" =>  strval($this->api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track->member->amount) : 0,
        ];

        $this->utils->debug_log("DG SERVICE: (sendToFastTrack)", $data);


        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

}

///END OF FILE////////////
