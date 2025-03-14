<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Sa_gaming_common_service_api extends BaseController
{
    use Seamless_service_api_module;

    private $APIauth;
    private $player_id;
    private $currencyCode;
    public $transaction_id;
    public $gamePlatformId = SA_GAMING_SEAMLESS_API;
    public $request;
    public $player;
    public $game_api;
    public $currency;
    public $headers;
    public $tableName;

    const STATUS_CODES = [
        'SUCCESS' => 0,
        'ACCOUNT_NOT_EXIST' => 1000,
        'INVALID_CURRENCY' => 1001,
        'INVALID_AMOUNT' => 1002,
        'LOCKED_ACCOUNT' => 1003,
        'INSUFFICIENT_BALANCE' => 1004,
        'GENERAL_ERROR' => 1005,
        'DECRYPTION_ERROR' => 1006,
        'SESSION_EXPIRED' => 1007,
        'SYSTEM_ERROR' => 9999,
        'PARAMETERS_ERROR' => 142,
        'IP_NOT_AUTHORIZES' => 401,
        'GAME_MAINTENANCE' => 503,
        'TRANSACTION_ID_NOT_FOUND' => 152,
        'ORDER_ID_ALREADY_EXISTS' => 122,
    ];

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_CANCEL = 'cancel';

    public $requestParams;

    private $transaction_for_fast_track = null;

    function __construct()
    {
        parent::__construct();

        $this->load->model(array('wallet_model', 'game_provider_auth', 'common_token', 'player_model'/*, 'sa_seamless_game_logs'*/, 'common_seamless_wallet_transactions', 'sa_gaming_seamless_wallet_transactions', 'original_seamless_wallet_transactions'));

        $this->host_name =  $_SERVER['HTTP_HOST'];

        $this->retrieveHeaders();

        $this->getValidPlatformId();

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
        $this->encrypt_key = $this->game_api->getSystemInfo('encrypt_key');
        $this->currency = $this->game_api->getSystemInfo('currency');

        $this->status_code = null;
        $this->valid = null;
        $this->requestParams = new stdClass();

        $this->raw_request = null;

        $this->parseRequest();


        $this->tableName = $this->game_api->original_transaction_table;
    }

    public function retrieveHeaders()
    {
        $this->headers = getallheaders();
    }

    public function parseRequest()
    {
        $temp = '';
        $this->raw_request = $request_string = file_get_contents('php://input');
        $this->utils->debug_log('SA_GAMING SEAMLESS parseRequest request_string', $request_string);
        $temp = urldecode($this->game_api->strDecrypt($request_string));
        $this->utils->debug_log('SA_GAMING SEAMLESS parseRequest request', $temp);

        parse_str($temp, $this->request);

        if (!is_array($this->request)) {
            $this->request = (array)$this->request;
        }

        $this->utils->debug_log('SA_GAMING SEAMLESS parseRequest parse_str', $this->request);

        // list($player_status, $player, $game_username, $player_name) = $this->getPlayerByGameUsername($this->request['username']);
        $game_username = isset($this->request['username']) ? $this->request['username'] : null;
        list($player_status, $player, $game_username, $player_name) = $this->getPlayerByGameUsername($game_username);

        if (!is_array($player)) {
            $player = (array)$player;
        }

        $this->player = $player;

        $this->utils->debug_log('SA_GAMING SEAMLESS parseRequest player', $this->player);
        return;
    }

    /**
     * Decimal format and max. 2 decimal places based on game provider docs
     */
    public function formatAmount($amount)
    {
        $amount = $this->roundDownAmount($amount);
        return $amount;
    }

    public function roundDownAmount($number)
    {
        $conversion_precision = floatval($this->game_api->getSystemInfo('game_amount_conversion_precision', 2));
        $fig = (int) str_pad('1', $conversion_precision + 1, '0');
        return (floor($number * $fig) / $fig);
    }

    //for testing of encryption
    public function EncryptMe()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $encrypted_request = $this->game_api->strEncrypt($payload);

        $response = [
            'encrypted_request' => $encrypted_request,
        ];

        return $this->output->set_content_type('application/json')->set_status_header(200)->set_output(json_encode($response));
    }

    //for testing of decryption
    public function DecryptMe()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $encrypted_request = $payload['encrypted_request'];
        $temp = urldecode($this->game_api->strDecrypt($encrypted_request));
        parse_str($temp, $request);
        $json_request = json_encode($request);

        return $this->output->set_content_type('application/json')->set_status_header(200)->set_output($json_request);
    }

    public function GetUserBalance()
    {
        $callname = 'GetUserBalance';

        if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
            $response = array(
                "username" => $this->request['username'],
                "currency" => $this->request['currency'],
                "amount" => $this->formatAmount(0),
                "error" => self::STATUS_CODES['GAME_MAINTENANCE']
            );

            return $this->handleExternalResponse($response, self::STATUS_CODES['GAME_MAINTENANCE'], $callbackName, $this->request);
        }

        $this->status_code = self::STATUS_CODES['SUCCESS'];

        $rule_set = array(
            'username' => 'required',
            'currency' => 'required',
        );

        // check params passed
        $this->checkParams($this->request, $rule_set);

        if (!$this->game_api->validateWhiteIP()) {
            $response = array(
                "username" => $this->request['username'],
                "currency" => $this->request['currency'],
                "amount" => $this->formatAmount(0),
                "error" => self::STATUS_CODES['IP_NOT_AUTHORIZES']
            );

            return $this->handleExternalResponse($response, self::STATUS_CODES['IP_NOT_AUTHORIZES'], $callname, $this->request);
        }

        if ($this->status_code == self::STATUS_CODES['SUCCESS']) {

            $username = $this->request['username'];
            $currency = $this->request['currency'];

            $playerBalance = $this->getPlayerBalance($this->player['username']);
            if ($playerBalance === false) {
                $this->utils->debug_log("SA_GAMING SEAMLESS {$callname} ERROR GET BALANCE request/player", $this->request, $this->player);
            }

            $amount = $playerBalance;
        } else {
            // for incorrect params
            $username = "";
            $currency = "";
            $amount = 0;
        }

        $response = array(
            "username" => $username,
            "currency" => $this->currency,
            "amount" => $this->formatAmount($amount),
            "error" => $this->status_code
        );
        $this->utils->debug_log("SA_GAMING SEAMLESS {$callname} response", $response);

        return $this->handleExternalResponse($response, $this->status_code, $callname, $this->request);
    }

    private function checkParams($params, $rule_set)
    {
        if ($this->external_system->isGameApiActive($this->gamePlatformId)) {
            // check if the string is successfully decrypted

            if (!empty($params) && !empty($rule_set)) {
                // check all required parameter 
                $valid = $this->isRequired($params, $rule_set);

                if (!$valid) {
                    $this->status_code = self::STATUS_CODES['PARAMETERS_ERROR'];
                } else {

                    if ($this->request && isset($this->request['amount']) && !is_numeric($this->request['amount'])) {
                        $this->status_code = self::STATUS_CODES['PARAMETERS_ERROR'];
                    }

                    //check account if exist
                    if (empty($this->player)) {
                        $this->status_code = self::STATUS_CODES['ACCOUNT_NOT_EXIST'];
                    }

                    // check currency
                    if (!isset($this->status_code) && $this->request['currency'] <> $this->currency) {
                        $this->status_code = self::STATUS_CODES['INVALID_CURRENCY'];
                    }
                }
            } else {
                #for decryption error
                $this->status_code = self::STATUS_CODES['DECRYPTION_ERROR'];
            }
        } else {
            // for SYStem error
            $this->status_code = self::STATUS_CODES['SYSTEM_ERROR'];
        }
    }

    public function PlaceBet()
    {
        $callbackName = 'PlaceBet';

        if($this->game_api->isMaintenance() || $this->game_api->isDisabled()) {
            $response = array(
                "username" => $this->request['username'],
                "currency" => $this->request['currency'],
                "amount" => $this->formatAmount(0),
                "error" => self::STATUS_CODES['GAME_MAINTENANCE']
            );

            return $this->handleExternalResponse($response, self::STATUS_CODES['GAME_MAINTENANCE'], $callbackName, $this->request);
        }

        if (!empty($this->request)) {
            $rule_set = array(
                'username' => 'required',
                'currency' => 'required',
                'amount' => 'required',
                'txnid' => 'required',
                'timestamp' => 'required',
                'ip' => 'required',
                'gametype' => 'required',
                'platform' => 'required',
                'gameid' => 'required',
                'betdetails' => 'required',
            );

            $res = $this->request;

            //validate params
            $this->checkParams($res, $rule_set);

            if (!$this->game_api->validateWhiteIP()) {
                $response = array(
                    "username" => $this->request['username'],
                    "currency" => $this->request['currency'],
                    "amount" => $this->formatAmount(0),
                    "error" => self::STATUS_CODES['IP_NOT_AUTHORIZES']
                );

                return $this->handleExternalResponse($response, self::STATUS_CODES['IP_NOT_AUTHORIZES'], $callbackName, $this->request);
            }

            //invalid params
            if (!$this->status_code == self::STATUS_CODES['SUCCESS']) {
                $response = array(
                    "username" => $res['username'],
                    "currency" => $this->currency,
                    "amount" => $this->formatAmount(0),
                    "error" => $this->status_code
                );
                return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
            }

            //do debit credit
            $debitCreditAmountParams = ['player_id' => $this->player['playerId'], 'player_name' => $this->player['username'], 'amount' => $res['amount'], 'external_unique_id' => $res['txnid'], 'request' => $res, 'callType' => 'bet', 'timestamp' => $res['timestamp']];
            $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
            list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isTransactionExist, $findAndProcessCancelBet) = $this->debitCreditAmount($debitCreditAmountParams, 'debit', $remoteActionType);

            //not successful debit credit
            if (!$trans_success) {
                $this->status_code = self::STATUS_CODES['SYSTEM_ERROR'];
                $response = array(
                    "username" => $res['username'],
                    "currency" => $res['currency'],
                    "amount" => $this->formatAmount($after_balance),
                    "error" => $this->status_code
                );
                return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
            }

            //insufficient balance
            if ($insufficient_balance) {
                $this->status_code = self::STATUS_CODES['INSUFFICIENT_BALANCE'];

                $response = array(
                    "username" => $res['username'],
                    "currency" => $res['currency'],
                    "amount" => $this->formatAmount($after_balance),
                    "error" => $this->status_code
                );

                return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
            }

            $response = array(
                "username" => $res['username'],
                "currency" => $res['currency'],
                "amount" => $this->formatAmount($after_balance),
                "error" => (int) $this->status_code
            );

            return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
        } else {
            #for decryption error
            $response = array(
                "error" => self::STATUS_CODES['DECRYPTION_ERROR']
            );

            return $this->handleExternalResponse($response, self::STATUS_CODES['DECRYPTION_ERROR'], $callbackName, $this->request);
        }
    }

    public function PlayerWin()
    {
        $callbackName = 'PlayerWin';

        if ($this->CI->utils->setNotActiveOrMaintenance($this->gamePlatformId)) {
            $response = array(
                "username" => $this->request['username'],
                "currency" => $this->request['currency'],
                "amount" => $this->formatAmount(0),
                "error" => self::STATUS_CODES['GAME_MAINTENANCE']
            );

            return $this->handleExternalResponse($response, self::STATUS_CODES['GAME_MAINTENANCE'], $callbackName, $this->request);
        }

        if ($this->external_system->isGameApiActive($this->gamePlatformId)) {

            if (!empty($this->request)) {
                $rule_set = array(
                    'username' => 'required',
                    'currency' => 'required',
                    'amount' => 'required',
                    'txnid' => 'required',
                    'timestamp' => 'required',
                    'gametype' => 'required',
                    'Payouttime' => 'required',
                    'gameid' => 'required',
                    'payoutdetails' => 'required',
                );

                $res = $this->request;

                //validate params
                $this->checkParams($res, $rule_set);

                //Checked IP if authorized
                if (!$this->game_api->validateWhiteIP()) {
                    $response = array(
                        "username" => $this->request['username'],
                        "currency" => $this->request['currency'],
                        "amount" => $this->formatAmount(0),
                        "error" => self::STATUS_CODES['IP_NOT_AUTHORIZES']
                    );

                    return $this->handleExternalResponse($response, self::STATUS_CODES['IP_NOT_AUTHORIZES'], $callbackName, $this->request);
                }

                //invalid params
                if (!$this->status_code == self::STATUS_CODES['SUCCESS']) {
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $this->currency,
                        "amount" => $this->formatAmount(0),
                        "error" => $this->status_code
                    );
                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                $payout_details = json_decode($this->request['payoutdetails'], true);
                $reference_transaction_ids = [];

                if (!empty($payout_details['betlist'])) {
                    foreach ($payout_details['betlist'] as $bet_list) {
                        array_push($reference_transaction_ids, $bet_list['txnid']);

                        $where = [
                            'transaction_type' => 'bet',
                            'player_id' => $this->player['playerId'],
                            'round_id' => $this->request['gameid'],
                            'transaction_id' => $bet_list['txnid'],
                        ];

                        $bet_transaction = $this->ssa_get_transaction($this->tableName, $where);
        
                        if (empty($bet_transaction)) {
                            $response = array(
                                "username" => $this->request['username'],
                                "currency" => $this->request['currency'],
                                "amount" => $this->formatAmount(0),
                                "error" => self::STATUS_CODES['TRANSACTION_ID_NOT_FOUND'],
                            );
        
                            return $this->handleExternalResponse($response, self::STATUS_CODES['TRANSACTION_ID_NOT_FOUND'], $callbackName, $this->request);
                        } else {
                            if (isset($bet_transaction['status']) && $bet_transaction['status'] == 'cancelled') {
                                $response = array(
                                    "username" => $this->request['username'],
                                    "currency" => $this->request['currency'],
                                    "amount" => $this->formatAmount(0),
                                    "error" => self::STATUS_CODES['ORDER_ID_ALREADY_EXISTS'],
                                );
        
                                return $this->handleExternalResponse($response, self::STATUS_CODES['ORDER_ID_ALREADY_EXISTS'], $callbackName, $this->request);
                            }
                        }
                    }
                }

                $reference_transaction_id = !empty($reference_transaction_ids) ? $this->utils->mergeArrayValues($reference_transaction_ids) : null;

                //do debit credit
                $debitCreditAmountParams = ['player_id' => $this->player['playerId'], 'player_name' => $this->player['username'], 'amount' => $res['amount'], 'external_unique_id' => $res['txnid'], 'request' => $res, 'callType' => 'win', 'timestamp' => $res['timestamp'], 'reference_transaction_id' => $reference_transaction_id];
                $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isTransactionExist, $findAndProcessCancelBet) = $this->debitCreditAmount($debitCreditAmountParams, 'credit', $remoteActionType);

                //not successful debit credit
                if (!$trans_success) {
                    $this->status_code = self::STATUS_CODES['SYSTEM_ERROR'];
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $res['currency'],
                        "amount" => $this->formatAmount($after_balance),
                        "error" => $this->status_code
                    );
                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                //existing transaction
                if (!empty($isTransactionExist)) {
                    # check if transaction params is same on existing one.
                    $this->status_code = self::STATUS_CODES['SUCCESS'];

                    # if same transaction return same value as berfore.
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $res['currency'],
                        "amount" => $this->formatAmount($isTransactionExist[0]['after_balance']),
                        "error" => $this->status_code
                    );

                    $this->utils->debug_log(">>>>>>>>>>>>>> SA_GAMING SEAMLESS PlayerWin DUPLICATE TRANSACTION request/response", $this->request, $response);

                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                $response = array(
                    "username" => $res['username'],
                    "currency" => $res['currency'],
                    "amount" => $this->formatAmount($after_balance),
                    "error" => (int) $this->status_code
                );

                return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
            } else {
                #for decryption error
                $response = array(
                    "error" => self::STATUS_CODES['DECRYPTION_ERROR']
                );

                return $this->handleExternalResponse($response, self::STATUS_CODES['DECRYPTION_ERROR'], $callbackName, $this->request);
            }
        }
    }

    public function PlayerLost()
    {
        $callbackName = 'PlayerLost';

        if ($this->external_system->isGameApiActive($this->gamePlatformId)) {

            if (!empty($this->request)) {
                $rule_set = array(
                    'username' => 'required',
                    'currency' => 'required',
                    'txnid' => 'required',
                    'timestamp' => 'required',
                    'gametype' => 'required',
                    'Payouttime' => 'required',
                    'gameid' => 'required',
                    'payoutdetails' => 'required',
                );

                $res = $this->request;

                //validate params
                $this->checkParams($res, $rule_set);

                //Checked IP if authorized
                if (!$this->game_api->validateWhiteIP()) {
                    $response = array(
                        "username" => $this->request['username'],
                        "currency" => $this->request['currency'],
                        "amount" => $this->formatAmount(0),
                        "error" => self::STATUS_CODES['IP_NOT_AUTHORIZES']
                    );

                    return $this->handleExternalResponse($response, self::STATUS_CODES['IP_NOT_AUTHORIZES'], $callbackName, $this->request);
                }

                //invalid params
                if (!$this->status_code == self::STATUS_CODES['SUCCESS']) {
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $this->currency,
                        "amount" => $this->formatAmount(0),
                        "error" => $this->status_code
                    );
                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                $payout_details = json_decode($this->request['payoutdetails'], true);
                $reference_transaction_ids = [];

                if (!empty($payout_details['betlist'])) {
                    foreach ($payout_details['betlist'] as $bet_list) {
                        array_push($reference_transaction_ids, $bet_list['txnid']);

                        $where = [
                            'transaction_type' => 'bet',
                            'player_id' => $this->player['playerId'],
                            'round_id' => $this->request['gameid'],
                            'transaction_id' => $bet_list['txnid'],
                        ];

                        $bet_transaction = $this->ssa_get_transaction($this->tableName, $where);
        
                        if (empty($bet_transaction)) {
                            $response = array(
                                "username" => $this->request['username'],
                                "currency" => $this->request['currency'],
                                "amount" => $this->formatAmount(0),
                                "error" => self::STATUS_CODES['TRANSACTION_ID_NOT_FOUND'],
                            );
        
                            return $this->handleExternalResponse($response, self::STATUS_CODES['TRANSACTION_ID_NOT_FOUND'], $callbackName, $this->request);
                        } else {
                            if (isset($bet_transaction['status']) && $bet_transaction['status'] == 'cancelled') {
                                $response = array(
                                    "username" => $this->request['username'],
                                    "currency" => $this->request['currency'],
                                    "amount" => $this->formatAmount(0),
                                    "error" => self::STATUS_CODES['ORDER_ID_ALREADY_EXISTS'],
                                );
        
                                return $this->handleExternalResponse($response, self::STATUS_CODES['ORDER_ID_ALREADY_EXISTS'], $callbackName, $this->request);
                            }
                        }
                    }
                }

                $reference_transaction_id = !empty($reference_transaction_ids) ? $this->utils->mergeArrayValues($reference_transaction_ids) : null;
                
                $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;

                //do debit credit, amount = 0 will not update the balance just return before and after balance
                $debitCreditAmountParams = ['player_id' => $this->player['playerId'], 'player_name' => $this->player['username'], 'amount' => 0, 'external_unique_id' => $res['txnid'], 'request' => $res, 'callType' => 'lose', 'timestamp' => $res['timestamp'], 'reference_transaction_id' => $reference_transaction_id];
                list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isTransactionExist, $findAndProcessCancelBet) = $this->debitCreditAmount($debitCreditAmountParams, 'credit', $remoteActionType);

                //not successful debit credit
                if (!$trans_success) {
                    $this->status_code = self::STATUS_CODES['SYSTEM_ERROR'];
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $res['currency'],
                        "amount" => $this->formatAmount($after_balance),
                        "error" => $this->status_code
                    );
                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                //existing transaction
                if (!empty($isTransactionExist)) {
                    # check if transaction params is same on existing one.
                    $this->status_code = self::STATUS_CODES['SUCCESS'];

                    # if same transaction return same value as berfore.
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $res['currency'],
                        "amount" => $this->formatAmount($isTransactionExist[0]['after_balance']),
                        "error" => $this->status_code
                    );

                    $this->utils->debug_log(">>>>>>>>>>>>>> SA_GAMING SEAMLESS PlayerLose DUPLICATE TRANSACTION request/response", $this->request, $response);

                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                $response = array(
                    "username" => $res['username'],
                    "currency" => $res['currency'],
                    "amount" => $this->formatAmount($after_balance),
                    "error" => (int) $this->status_code
                );

                return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
            } else {
                #for decryption error
                $response = array(
                    "error" => self::STATUS_CODES['DECRYPTION_ERROR']
                );

                return $this->handleExternalResponse($response, self::STATUS_CODES['DECRYPTION_ERROR'], $callbackName, $this->request);
            }
        }
    }

    public function PlaceBetCancel()
    {
        $this->utils->debug_log(">>>>>>>>>>>>>> SA_GAMING SEAMLESS ENTERED PlayerBetCancel");

        $callbackName = 'PlaceBetCancel';

        if ($this->external_system->isGameApiActive($this->gamePlatformId)) {

            if (!empty($this->request)) {
                $rule_set = array(
                    'username' => 'required',
                    'currency' => 'required',
                    'amount' => 'required',
                    'txnid' => 'required',
                    'timestamp' => 'required',
                    'gametype' => 'required',
                    'gameid' => 'required',
                    'txn_reverse_id' => 'required',
                    'gamecancel' => 'required'
                );

                $res = $this->request;

                $this->utils->debug_log(">>>>>>>>>>>>>> SA_GAMING SEAMLESS PlayerBetCancel PARENT TRANSACTION NOT EXIST request", $this->request);

                //validate params
                $this->checkParams($res, $rule_set);

                //Checked IP if authorized
                if (!$this->game_api->validateWhiteIP()) {
                    $response = array(
                        "username" => $this->request['username'],
                        "currency" => $this->request['currency'],
                        "amount" => $this->formatAmount(0),
                        "error" => self::STATUS_CODES['IP_NOT_AUTHORIZES']
                    );

                    return $this->handleExternalResponse($response, self::STATUS_CODES['IP_NOT_AUTHORIZES'], $callbackName, $this->request);
                }

                //invalid params
                if (!$this->status_code == self::STATUS_CODES['SUCCESS']) {
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $this->currency,
                        "amount" => $this->formatAmount(0),
                        "error" => $this->status_code
                    );
                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                //check first if parent transaction exists
                /*$parentTransaction = $this->common_seamless_wallet_transactions->getTransactionRowArray($this->gamePlatformId, $res['txn_reverse_id']);	
				
				if(empty($parentTransaction)){
					# check if transaction params is same on existing one.
					$this->status_code = self::STATUS_CODES['SUCCESS'];

					# if same transaction return same value as berfore. 
					$response = array(
						"username" => $res['username'], 
						"currency" => $res['currency'],
						"amount" => $this->formatAmount($parentTransaction['after_balance']),
						"error" => $this->status_code
					);
						
					$this->utils->debug_log(">>>>>>>>>>>>>> SA_GAMING SEAMLESS PlayerBetCancel PARENT TRANSACTION NOT EXIST request/response", $this->request, $response);
					
					return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
				}*/

                $reference_transaction_id = $this->request['txn_reverse_id'];

                $bet_transaction = $this->ssa_get_transaction($this->tableName, [
                    'transaction_type' => 'bet',
                    'player_id' => $this->player['playerId'],
                    'transaction_id' => $reference_transaction_id,
                ]);

                // possible to receive refund request before bet request
                if (empty($bet_transaction)) {
                    /* $response = array(
                        "username" => $this->request['username'],
                        "currency" => $this->request['currency'],
                        "amount" => $this->formatAmount(0),
                        "error" => self::STATUS_CODES['TRANSACTION_ID_NOT_FOUND'],
                    );

                    return $this->handleExternalResponse($response, self::STATUS_CODES['TRANSACTION_ID_NOT_FOUND'], $callbackName, $this->request); */
                } else {
                    $is_payout_exists = $this->ssa_get_transaction($this->tableName, "(transaction_type IN ('win', 'lose') AND player_id='{$this->player['playerId']}' AND round_id='{$this->request['gameid']}' AND reference_transaction_id='{$reference_transaction_id}')");
    
                    if ($is_payout_exists) {
                        $response = array(
                            "username" => $this->request['username'],
                            "currency" => $this->request['currency'],
                            "amount" => $this->formatAmount(0),
                            "error" => self::STATUS_CODES['ORDER_ID_ALREADY_EXISTS'],
                        );
    
                        return $this->handleExternalResponse($response, self::STATUS_CODES['ORDER_ID_ALREADY_EXISTS'], $callbackName, $this->request);
                    }
                }

                //do debit credit
                $debitCreditAmountParams = ['player_id' => $this->player['playerId'], 'player_name' => $this->player['username'], 'amount' => $res['amount'], 'external_unique_id' => $res['txnid'], 'request' => $res, 'callType' => 'cancel', 'txn_reverse_id' => $res['txn_reverse_id'], 'timestamp' => $res['timestamp'], 'reference_transaction_id' => $reference_transaction_id];
                $remoteActionType = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
                list($trans_success, $previous_balance, $after_balance, $insufficient_balance, $isTransactionExist, $findAndProcessCancelBet) = $this->debitCreditAmount($debitCreditAmountParams, 'credit', $remoteActionType);

                //not successful debit credit
                if (!$trans_success) {
                    $this->status_code = self::STATUS_CODES['SYSTEM_ERROR'];
                    $response = array(
                        "username" => $res['username'],
                        "currency" => $res['currency'],
                        "amount" => $this->formatAmount($after_balance),
                        "error" => $this->status_code
                    );
                    return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
                }

                $this->status_code = self::STATUS_CODES['SUCCESS'];
                $response = array(
                    "username" => $res['username'],
                    "currency" => $res['currency'],
                    "amount" => $this->formatAmount($after_balance),
                    "error" => (int) $this->status_code
                );

                return $this->handleExternalResponse($response, $this->status_code, $callbackName, $this->request);
            } else {
                #for decryption error
                $response = array(
                    "error" => self::STATUS_CODES['DECRYPTION_ERROR']
                );

                return $this->handleExternalResponse($response, self::STATUS_CODES['DECRYPTION_ERROR'], $callbackName, $this->request);
            }
        }
    }

    public function validateRequest($rule_set)
    {
        $is_valid = true;
        foreach ($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach ($rules as $rule) {
                if ($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                    $is_valid = false;
                    break;
                }
            }
            if (!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    public function preProcessRequest($functionName, $rule_set = [])
    {
        $params = $this->input->post();
        $this->requestParams->function = $functionName;
        $this->requestParams->params = $params;
        $is_valid = $this->validateRequest($rule_set);

        if (!$is_valid) {
            print_r(self::STATUS_CODES['PARAMETERS_ERROR']);

            return self::STATUS_CODES['PARAMETERS_ERROR'];
        }

        // $hash = $this->generateHash($params);
        // if($hash != $params['hash']) {
        //     return $this->setResponse(self::ERROR_INVALID_HASH);
        // }

        // if($params['providerId'] != $this->api->provider_id) {
        //     return $this->setResponse(self::ERROR_BAD_REQUEST);
        // }
    }

    public function testbermar()
    {
        $response = array(
            "username" => 'username value',
            "currency" => 'currency value',
            "amount" => 00,
            "error" => 1
        );
        $xml = $this->arrayToPlainXml($response);

        return $this->output->set_content_type('application/xml')
            ->set_status_header(200)
            ->set_output($xml);
    }

    private function arrayToXml($array)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><RequestResponse/>');
        $array = array_flip($array);
        array_walk_recursive($array, array($xml, 'addChild'));

        return $xml->asXML();
    }

    private function saveResponseResult($statusCode, $callMethod, $params, $response, $statusText = null, $extra = null)
    {
        $fields = [];
        $this->player = (array)$this->player;
        $fields['player_id'] = isset($this->player['playerId']) ? $this->player['playerId'] : null;

        //save to db
        $this->CI->load->model("response_result");
        $flag = $statusCode == self::STATUS_CODES['SUCCESS'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $extra = array_merge((array)$extra, (array)$this->headers);

        return $this->CI->response_result->saveResponseResult(
            $this->gamePlatformId,
            $flag,
            $callMethod,
            json_encode($params),
            $response,
            200,
            $statusText,
            is_array($extra) ? json_encode($extra) : $extra,
            $fields,
            false,
            null,
            intval($this->utils->getExecutionTimeToNow() * 1000) //costMs
        );
    }

    private function isRequired($params, $rule_set)
    {
        $is_valid = true;
        foreach ($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach ($rules as $rule) {
                if ($rule == 'required' && !array_key_exists($key, $params)) {
                    $is_valid = false;
                    break;
                }
            }
            if (!$is_valid) {
                break;
            }
        }

        return $is_valid;
    }

    private function debitCreditAmount($params, $mode = 'credit', $remoteActionType=Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET)
    {
        $this->utils->debug_log(">>>>>>>>>>>>>> SA_GAMING SEAMLESS (debitCreditAmount)", $params, $mode);

        $player_id            = $params['player_id'];
        $amount                = abs($params['amount']); //apply conversion rate
        $external_unique_id                = abs($params['external_unique_id']);
        $controller         = $this;
        $previous_balance     = 0;
        $after_balance         = 0;
        $success             = false;
        $insufficient_balance = false;
        $isAlreadyExists     = false;

        $findAndProcessCancelBet     = false;

        $insertData = array(
            "hostid" => $params['request']['hostid'],
            "gameid" => $params['request']['gameid'],
            "txnid" => $params['request']['txnid'],
            "amount" => $amount,
            "callType" => $params['callType'],
            "before_balance" => $previous_balance,
            "after_balance" => $after_balance,
            "player_id" => $params['player_id'],
            "start_at" => date('Y-m-d H:i:s', strtotime($params['timestamp'])),
            "end_at" => date('Y-m-d H:i:s', strtotime($params['timestamp'])),
            "extra_info" => $params['request'],
            "reference_transaction_id" => !empty($params['reference_transaction_id']) ? $params['reference_transaction_id'] : null,
        );

        if ($amount <> 0 || ( $mode == 'credit' && $amount == 0 )) {
            $success = $this->lockAndTransForPlayerBalance($player_id, function () use (
                $mode,
                $params,
                $controller,
                $player_id,
                $amount,
                $insertData,
                $remoteActionType,
                &$insufficient_balance,
                &$previous_balance,
                &$after_balance,
                &$isAlreadyExists,
                &$findAndProcessCancelBet,
                &$isTransactionExist) {

                try {

                    $get_balance = $controller->getPlayerBalance($params['player_name']);

                    if ($get_balance !== false) {
                        $after_balance = $previous_balance = $get_balance;
                        if ($mode == 'debit') {
                            $after_balance = $after_balance - $amount;
                        } else {
                            $after_balance = $after_balance + $amount;
                        }
                    } else {
                        return false;
                    }

                    if ($mode == 'debit' && $previous_balance < $amount) {
                        $after_balance = $previous_balance;
                        $insufficient_balance = true;
                        return false;
                    }

                    //check if exists
                    $this->utils->debug_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) isTransactionExist ", $controller->gamePlatformId, $params['external_unique_id']);
                    if ($this->game_api->use_sa_gaming_wallet_transaction) {
                        $isTransactionExist = $controller->sa_gaming_seamless_wallet_transactions->isTransactionExist($params['external_unique_id']);
                    } else {
                        $isTransactionExist = $controller->common_seamless_wallet_transactions->isTransactionExist($controller->gamePlatformId, $params['external_unique_id']);
                    }

                    if (!empty($isTransactionExist)) {
                        $this->utils->debug_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) external_unique_id", $params['external_unique_id']);
                        $isAlreadyExists = true;
                        $after_balance = $previous_balance;
                        return true;
                    }

                    //OGP-20182
                    $isBetMissing = false;

                    //set bet transaction to cancelled	
                    if ($insertData['callType'] == 'cancel') {
                        $insertData['status'] = 'cancelled';

                        if ($this->game_api->use_sa_gaming_wallet_transaction) {
                            $status = $controller->sa_gaming_seamless_wallet_transactions->setTransactionStatus($params['txn_reverse_id'], 'external_unique_id', $insertData['status']);
                            $this->utils->debug_log("SA_GAMING_TAL_DEBUG: (setTransactionStatus)", $status);
                        } else {
                            $status = $controller->common_seamless_wallet_transactions->setTransactionStatus($controller->gamePlatformId, $params['txn_reverse_id'], 'external_unique_id', $insertData['status']);
                        }

                        if (!$status) {
                            $isBetMissing = true;
                            $after_balance = $previous_balance;
                            $insertData['status'] = 'waiting'; //OGP-20182 will put to waiting do not add balance but save transactions

                            $this->utils->debug_log("SA_GAMING_TAL_DEBUG: (status is false)  betMissing is", $isBetMissing);
                        }

                        if ($this->game_api->use_sa_gaming_wallet_transaction) {
                            $where = array(
                                'transaction_id' => $params['txn_reverse_id'],
                                'transaction_type' => self::TRANSACTION_TYPE_BET,
                                'round_id' => $params['request']['gameid'],
                            );

                            $isBetTransactionExist = $controller->original_seamless_wallet_transactions->isTransactionExistCustom($this->tableName, $where);

                            $this->utils->debug_log("SA_GAMING_TAL_DEBUG: (isBetTransactionExist)", $isBetTransactionExist);

                            if (!$isBetTransactionExist) {
                                $isBetMissing = true;
                                $after_balance = $previous_balance;
                                $insertData['status'] = 'waiting'; //OGP-20182 will put to waiting do not add balance but save transactions

                                $this->utils->debug_log("SA_GAMING_TAL_DEBUG: betMissing is", $isBetMissing);
                            }
                        }
                    }

                    $insertData['before_balance'] = $previous_balance;
                    $insertData['after_balance'] = $after_balance;

                    //insert transaction
                    if ($this->game_api->use_sa_gaming_wallet_transaction) {
                        $isAdded = $controller->sa_gaming_seamless_wallet_transactions->insertTransactionRecord($insertData);

                        $this->utils->debug_log("SA_GAMING SEAMLESS SERVICE: (insertTransactionRecord)", $isAdded);

                        $trans_id = $isAdded;
                        $trans_record = $this->makeTransactionRecord($insertData);

                        $this->transaction_for_fast_track = null;
                        if ($isAdded) {
                            $this->transaction_for_fast_track = $trans_record;
                            $this->transaction_for_fast_track['id'] = $trans_id;
                        }
                    } else {
                        $isAdded = $controller->insertTransactionRecord($insertData);
                    }

                    if (!$isAdded) {
                        return false;
                    }

                    //if cancel bet and bet transaction is missing then save transaction, but do not move balance
                    if ($insertData['callType'] == 'cancel' && $isBetMissing == true) {
                        $this->utils->debug_log("SA_GAMING_TAL_DEBUG: callType is cancel and isBetMissing is TRUE", $isBetMissing);
                        $success = true;
                        $after_balance = $previous_balance;
                        return true;
                    }

                    //OGP-28649
                    $external_unique_id = (isset($params['external_unique_id']) ? $params['external_unique_id'] : null);
                    $this->utils->error_log(__METHOD__, ' bermar external_unique_id sa_gaming', $external_unique_id, 'params', $params);

                    $this->wallet_model->setGameProviderActionType($remoteActionType);
                    $uniqueid_of_seamless_service = $controller->gamePlatformId.'-'.$external_unique_id;
                    $external_game_id = isset($params['request']['hostid']) ? $params['request']['hostid'] : $params['request']['gameid'];
                    $controller->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);

                    //cancel bet that dont have bet yet not allowed to pass here
                    
                    if ($mode == 'debit') {
                        $success = $controller->wallet_model->decSubWallet($player_id, $controller->gamePlatformId, $amount);
                    } else {
                        $result_details = $controller->original_seamless_wallet_transactions->querySingleTransactionCustom($this->tableName, [
							'round_id' => $params['request']['gameid'],
							'transaction_type' => 'bet',
						]);

						$this->utils->debug_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) ", [
							'$result_details' => $result_details,
							'$setRelatedUniqueidOfSeamlessService' => $result_details['game_platform_id'].'-'.$result_details['external_unique_id'],
							'$setRelatedActionOfSeamlessService' => $result_details['transaction_type'],
						]);
	
                        $related_unique_id = "game-".$result_details['game_platform_id'].'-'.$result_details['external_unique_id'];
						$controller->wallet_model->setRelatedUniqueidOfSeamlessService($related_unique_id);  
						$controller->wallet_model->setRelatedActionOfSeamlessService($result_details['transaction_type']);  
						$controller->wallet_model->setGameProviderIsEndRound(true);  

                        if ($amount == 0 && $insertData['callType'] == 'lose') {
                            $success = true;

                            if ($this->ssa_enabled_remote_wallet()) {
                                $this->utils->debug_log(__METHOD__, "sa_gaming_common_service_api: amount 0 call remote wallet", 'request_params', $params);
                                $this->ssa_increase_remote_wallet($player_id, $amount, $controller->gamePlatformId, $after_balance);
                            }
                        } else {
                            $success = $controller->wallet_model->incSubWallet($player_id, $controller->gamePlatformId, $amount);
                        }
                    }


                    //if bet check if there is cancel bet to process
                    if ($insertData['callType'] == 'bet') {
                        //check if cancel is waiting to action
                        if ($this->game_api->use_sa_gaming_wallet_transaction) {
                            $findAndProcessCancelBet = $this->sa_gaming_seamless_wallet_transactions->getCancelRecordWithTxnReverseId($insertData['gameid'], $insertData['txnid']);
                        } else {
                            $findAndProcessCancelBet = $this->getCancelRecordWithTxnReverseId($insertData['gameid'], $insertData['txnid']);
                        }


                        if ($findAndProcessCancelBet) {
                            //process credit balance based on the cancel bet record
                            $this->utils->debug_log(__METHOD__ , ' bermar cancel external_unique_id sa_gaming', $findAndProcessCancelBet['external_unique_id'], 'findAndProcessCancelBet', $findAndProcessCancelBet);
                            $uniqueid_of_seamless_service_cancelbet = $controller->gamePlatformId.'-'.$findAndProcessCancelBet['external_unique_id'];
                            $external_game_id_cancelbet = isset($params['request']['hostid']) ? $params['request']['hostid'] : $params['request']['gameid'];
                            $controller->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service_cancelbet, $external_game_id_cancelbet );

                            $success = $controller->wallet_model->incSubWallet($player_id, $controller->gamePlatformId, $findAndProcessCancelBet['amount']);

                            if ($success) {
                                //update cancel trnsaction status to ok
                                $cancelAfterbalance = $after_balance + $findAndProcessCancelBet['amount'];
                                $newCancelData = ['status' => 'ok', 'before_balance' => $after_balance, 'after_balance' => $cancelAfterbalance];
                                //update cancel transaction status
                                $after_balance = $previous_balance;

                                if ($this->game_api->use_sa_gaming_wallet_transaction) {
                                    $isupdated = $controller->sa_gaming_seamless_wallet_transactions->updateTransaction(
                                        $findAndProcessCancelBet['external_unique_id'],
                                        $newCancelData
                                    );
                                } else {
                                    $isupdated = $controller->common_seamless_wallet_transactions->updateTransaction(
                                        $controller->gamePlatformId,
                                        $findAndProcessCancelBet['external_unique_id'],
                                        $newCancelData
                                    );
                                }

                                if ($isupdated) {
                                    $success = true;
                                } else {
                                    $success = false;
                                }
                            }
                        }
                    }

                    return $success;
                } catch (Exception $e) {
                    $this->utils->debug_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) catched an error external_unique_id", $params['external_unique_id']);
                    return false;
                }
            });
        } else {
            $success = $this->lockAndTransForPlayerBalance($player_id, function () use (
                $mode,
                $params,
                $controller,
                $player_id,
                $amount,
                $insertData,
                &$insufficient_balance,
                &$previous_balance,
                &$after_balance,
                &$isAlreadyExists,
                &$isTransactionExist) {
                $get_balance = $this->getPlayerBalance($params['player_name']);

                if ($get_balance !== false) {
                    $after_balance = $previous_balance = $get_balance;
                    $success = true;
                } else {
                    return false;
                }

                //insert transaction
                $insertData['before_balance'] = $previous_balance;
                $insertData['after_balance'] = $after_balance;

                //check if exists
                $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) isTransactionExist ", $controller->gamePlatformId, $params['external_unique_id']);

                if ($this->game_api->use_sa_gaming_wallet_transaction) {
                    $isTransactionExist = $controller->sa_gaming_seamless_wallet_transactions->isTransactionExist($params['external_unique_id']);

                    $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) (isTransactionExist) using sag_wallet_transaction", $isTransactionExist);

                    if (empty($isTransactionExist)) {

                        $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) (isTransactionExist) insertData", $insertData);

                        $isAdded = $controller->sa_gaming_seamless_wallet_transactions->insertTransactionRecord($insertData);

                        $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) using sag_wallet_transaction", $isAdded);

                        $trans_id = $isAdded;
                        $trans_record = $this->makeTransactionRecord($insertData);

                        $this->transaction_for_fast_track = null;
                        if ($isAdded) {
                            $this->transaction_for_fast_track = $trans_record;
                            $this->transaction_for_fast_track['id'] = $trans_id;
                        }

                        $success = true;
                    }
                } else {
                    $this->utils->debug_log("SA_GAMING SEAMLESS SERVICE: (debitCreditAmount) isTransactionExist ", $controller->gamePlatformId, $params['external_unique_id']);

                    $isTransactionExist = $controller->common_seamless_wallet_transactions->isTransactionExist($controller->gamePlatformId, $params['external_unique_id']);

                    if (!$isTransactionExist) {
                        if ($this->insertTransactionRecord($insertData)) {
                            $success = true;
                        } else {
                            $success = false;
                        }
                    }
                }

                return $success;
            });
        }

        return array($success, $previous_balance, $after_balance, $insufficient_balance, $isTransactionExist, $findAndProcessCancelBet, $isAlreadyExists);
    }

    public function getPlayerBalance($playerName)
    {
        $get_bal_req = $this->game_api->queryPlayerBalance($playerName);
        if ($get_bal_req['success']) {
            return $get_bal_req['balance'];
        } else {
            return false;
        }
    }

    public function getPlayerByGameUsername($gameUsername)
    {
        $username = $this->game_provider_auth->getPlayerUsernameByGameUsername($gameUsername, $this->gamePlatformId);
        $player = $this->game_api->getPlayerInfoByUsername($username);

        if (!$player) {
            return [false, null, null, null];
        }

        return [true, $player, $gameUsername, $username];
    }

    public function getValidPlatformId()
    {

        $this->gamePlatformId = SA_GAMING_SEAMLESS_API;

        $multiple_currency_domain_mapping = (array)@$this->utils->getConfig('sa_gaming_multiple_currency_domain_mapping');
        if (array_key_exists($this->host_name, $multiple_currency_domain_mapping) && !empty($multiple_currency_domain_mapping)) {
            $this->gamePlatformId  = $multiple_currency_domain_mapping[$this->host_name];
        }

        return $this->gamePlatformId;
    }

    public function insertTransactionRecord($data)
    {

        $trans_id = false;

        $trans_record = $this->makeTransactionRecord($data);

        $trans_id = $this->CI->common_seamless_wallet_transactions->insertRow($trans_record);

        $this->transaction_for_fast_track = null;
        if ($trans_id) {
            $this->transaction_for_fast_track = $trans_record;
            $this->transaction_for_fast_track['id'] = $this->CI->common_seamless_wallet_transactions->getLastInsertedId();
        }
        return $trans_id;
    }

    public function generateHash($data)
    {
        $hash = implode('', $data);
        return md5($hash);
    }

    public function makeTransactionRecord($raw_data)
    {
        $data = [];

        if ($raw_data['extra_info']) {
            $raw_data['extra_info']['raw_request'] = $this->raw_request;
        }

        $data['game_platform_id']     = $this->gamePlatformId;
        $data['transaction_id']     = isset($raw_data['txnid']) ? $raw_data['txnid'] : null;
        $data['amount']             = isset($raw_data['amount']) ? floatVal($raw_data['amount']) : 0;
        $data['before_balance']     = isset($raw_data['before_balance']) ? floatVal($raw_data['before_balance']) : 0;
        $data['after_balance']         = isset($raw_data['after_balance']) ? floatVal($raw_data['after_balance']) : 0;
        $data['player_id']             = isset($raw_data['player_id']) ? $raw_data['player_id'] : null;
        $data['round_id']             = isset($raw_data['gameid']) ? $raw_data['gameid'] : null;
        $data['game_id']             = isset($this->request['hostid']) ? $this->request['hostid'] : null;
        $data['transaction_type']     = isset($raw_data['callType']) ? $raw_data['callType'] : null;
        $data['status']             = isset($raw_data['status']) ? $raw_data['status'] : 'ok';
        $data['response_result_id'] = isset($raw_data['response_result_id']) ? $raw_data['response_result_id'] : null;
        $data['extra_info']         = @json_encode($raw_data['extra_info']);
        $data['start_at']             = isset($raw_data['start_at']) ? $raw_data['start_at'] : null;
        $data['end_at']             = isset($raw_data['end_at']) ? $raw_data['end_at'] : null;

        $data['external_unique_id'] = $raw_data['txnid'];

        $data['elapsed_time']         = intval($this->utils->getExecutionTimeToNow() * 1000);

        $generatedHash = $this->generateHash([
            $data['before_balance'],
            $data['after_balance'],
            $data['game_id'],
            $data['amount'],
            $data['game_platform_id']
        ]);

        $data['md5_sum'] = $generatedHash;

        return $data;
    }

    public function handleExternalResponse($response, $statusCode, $callname, $request)
    {
        $response = (array)$response;
        $content = json_encode($response);
        $xmlData = $this->arrayToPlainXml($response);

        //save response result
        $this->saveResponseResult(
            $statusCode,
            $callname,
            $request,
            $xmlData
        );

        if ($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $this->status_code == self::STATUS_CODES['SUCCESS']) {
            $this->sendToFastTrack();
        }

        return $this->output->set_content_type('application/xml')
            ->set_status_header(200)
            ->set_output($xmlData);
    }

    public function arrayToPlainXml($array, $xml = false)
    {
        //var_dump($array);exit;
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><RequestResponse/>');
        //$array = $array[key($array)];
        foreach ($array as $key => $value) {

            if (is_array($value)) {
                $this->arrayToPlainXml($value, $xml->addChild($key));
            } else {
                $xml->addChild($key, $value);
            }
        }
        return $xml->asXML();
    }

    public function getCancelRecordWithTxnReverseId($gameId, $txnReverseId)
    {
        $this->db->from($this->common_seamless_wallet_transactions->getTableName())
            ->where('game_platform_id', $this->gamePlatformId)
            ->where('transaction_id !=', $txnReverseId)
            ->where('transaction_type', 'cancel')
            ->where('status', 'waiting')
            ->where("game_id", $gameId);
        $query = $this->db->get();

        $data = $query->result_array();
        $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (getCancelRecordWithTxnReverseId) last db query: ", $this->db->last_query());
        $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (getCancelRecordWithTxnReverseId) data: ", $data);


        foreach ($data as $row) {
            $extraInfoDecoded = json_decode($row['extra_info'], true);
            $this->utils->error_log("SA_GAMING SEAMLESS SERVICE: (getCancelRecordWithTxnReverseId) extraInfoDecoded: ", $extraInfoDecoded);
            if (isset($extraInfoDecoded['txn_reverse_id']) && $extraInfoDecoded['txn_reverse_id'] == $txnReverseId) {
                //found cancel
                return $row;
            }
        }

        return false;
    }

    private function sendToFastTrack()
    {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->game_api->getPlatformCode(), $this->transaction_for_fast_track['game_id']);
        $betType = null;
        switch ($this->transaction_for_fast_track['transaction_type']) {
            case 'bet':
                $betType = 'Bet';
                break;
            case 'lose':
            case 'win':
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
            "activity_id" =>  strval($this->transaction_for_fast_track['id']),
            "amount" => (float) abs($this->transaction_for_fast_track['amount']),
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->currency,
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  strval($this->transaction_for_fast_track['round_id']),
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->transaction_for_fast_track['player_id'],
            "vendor_id" =>  strval($this->game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amount']) : 0,
        ];

        $this->utils->debug_log("SA GAMING: (sendToFastTrack)", $data);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

    public function GetUnsuccessfulBetDetails() {
        $request_params = json_decode(file_get_contents('php://input'), true);
        $response = $this->game_api->GetUnsuccessfulBetDetails($request_params);

        return $this->output->set_content_type('application/json')->set_status_header(200)->set_output(json_encode($response));
    }
}

///END OF FILE////////////
