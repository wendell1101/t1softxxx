<?php

/**
 * Top Trend Gaming Integration Service API
 * v0.7
 **/
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Ttg_seamless_service_api extends BaseController
{
    use Seamless_service_api_module;

    #default params
    private $currency;
    private $language;

    private $game_platform_id;
    private $game_api;

    private $params;
    private $player_details;
    private $player_balance;
    private $before_balance;
    private $after_balance;
    private $bet_amount;
    private $win_amount;

    private $conversion;
    private $precision;
    private $arithmetic_name;
    private $adjustment_precision;
    private $adjustment_conversion;
    private $adjustment_arithmetic_name;
    private $game_provider_gmt = '+0 hours';
    private $game_provider_date_time_format = 'Y-m-d H:i:s';

    private $transaction_type;
    private $game_logs_status;
    private $transaction_table;
    private $external_unique_id;
    private $seamless_service_unique_id;
    private $headers;

    const SEAMLESS_GAME_API = TTG_SEAMLESS_GAME_API;  

    #allowed functions
    const ALLOWED_API_METHODS_TEST = 'test';
    const ALLOWED_API_METHODS_PING = 'ping';
    const ALLOWED_API_METHODS_GET_BALANCE = 'getBalanceReq';
    const ALLOWED_API_METHODS_FUND_TRANSFER = 'fundTransferReq';

    #transaction types
    const TRANSACTION_TYPE_BET = 400;
    const TRANSACTION_TYPE_WIN = 410;

    #seamless transaction types
    const SEAMLESS_TRANSACTION_TYPE_BET = 'Bet';
    const SEAMLESS_TRANSACTION_TYPE_PAYOUT = 'Payout';
    const SEAMLESS_TRANSACTION_TYPE_REFUND = 'Refund';

    const ALLOWED_API_METHODS = [
        self::ALLOWED_API_METHODS_TEST,
        self::ALLOWED_API_METHODS_PING,
        self::ALLOWED_API_METHODS_GET_BALANCE,
        self::ALLOWED_API_METHODS_FUND_TRANSFER,
    ];


    public function __construct()
    {
        parent::__construct();
        $this->ssa_init();
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);
        $this->retrieveHeaders();

        if ($this->game_api) {
            $this->currency = $this->game_api->currency;
        } else {
            $response = array(
                'type' => "API Error",
                'err' => "9999"
            );

            $response_result_additional_message = ':Game API is not loaded: '. $this->game_platform_id;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        $this->player_balance = 0;
        $this->language = $this->game_api->language;
        $this->game_platform_id = $this->game_api->getPlatformCode();
        $this->transaction_table = $this->game_api->original_seamless_wallet_transactions_table;
        $this->precision = $this->game_api->precision;
        $this->conversion = $this->game_api->conversion;
        $this->arithmetic_name = $this->game_api->arithmetic_name;
        $this->adjustment_precision = $this->game_api->adjustment_precision;
        $this->adjustment_conversion = $this->game_api->adjustment_conversion;
        $this->adjustment_arithmetic_name = $this->game_api->adjustment_arithmetic_name;
        $this->game_provider_gmt = $this->game_api->game_provider_gmt;
        $this->game_provider_date_time_format = $this->game_api->game_provider_date_time_format;
    }


    public function index($api)
    {
        $this->CI->load->model('external_system');
        
        #get request parameters
        $this->ssa_request_params = !empty($this->ssa_request_params()) ? $this->ssa_request_params() : $this->requestArrayFromXml();
        $this->params = $this->ssa_request_params['@attributes'];
        $api_method_type = $this->params['type'];

        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . ':Parameters:', $this->params);

        #check and validate api
        if ($api != $this->game_platform_id) {
            $response = array(
                'type' => $api_method_type,
                'err' => 9999
            );
        
            $response_result_additional_message = ':API METHOD is incorrect: '. $api;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        $isGameApiMaintenance = $this->CI->external_system->isGameApiMaintenance(self::SEAMLESS_GAME_API);
        if ($isGameApiMaintenance){
            $response = array(
                'type' => $api_method_type,
                'err' => 9999
            );

            $response_result_additional_message = ':Game is under Maintenance: '. $api;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        #check if IP address is allowed
        if(!$this->game_api->validateWhiteIP()){
            $ip = $this->input->ip_address();
            if($ip=='0.0.0.0'){
                $ip=$this->input->getRemoteAddr();
            }
            $response = array(
                'type' => $api_method_type,
                'err' => 9999
            );

            $response_result_additional_message = ':IP is not allowed: '. $ip;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        if (in_array($api_method_type, self::ALLOWED_API_METHODS)) {
            return $this->$api_method_type();
        } else {
            $response = array(
                'type' => $api_method_type,
                'err' => 9999
            );

            $response_result_additional_message = ':API METHOD is incorrect: '. $this->$api_method_type;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }
    }

    private function ping()
    {
        $response = array("type" => "ping", "timestamp" => $this->utils->getTimestampNow(), "err" => 0);
        return $this->rebuildResponsefromArrayToXml($response, null);
    }

    public function getBalanceReq()
    {
        #check currency
        if ($this->currency != $this->params['cur']) {
            $response = array(
                'type' => 'getBalanceResp',
                'err' => '1001'
            );

            $response_result_additional_message = ':Invalid Currency: '. $this->params['cur'];
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        #get player info
        $game_username = $this->params['acctid'];
        $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);

        #check existence and get balance
        if (!empty($this->player_details['player_id'])) {
            $player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

            $response = array(
                'type' => 'getBalanceResp',
                'cur' => $this->currency,
                'amt' => $player_balance,
                'err' => '0'
            );
            $response_result_additional_message = 'Success';
        } else {
            $response = array(
                'type' => 'getBalanceResp',
                'err' => '1000'
            );

            $response_result_additional_message = ':Player not found: '. $game_username;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
        }

        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
        return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
    }

    public function fundTransferReq()
    {

        #check currency
        if ($this->currency != $this->params['cur']) {
            $response = array(
                'type' => 'fundTransferResp', //to refactor, to be dynamic when checking currecny
                'err' => '1001'
            );

            $response_result_additional_message = ':Invalid Currency: '. $this->params['cur'];
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        #validate request parameters
        $is_valid = $this->ssa_validate_request_params($this->params, [
            'acctid'            => ['required', 'maximum_size:32'],
            'amt'               => ['required', 'numeric'],
            'txnid'             => ['required', 'numeric', 'maximum_size:20'],
            'gameid'            => ['optional', 'numeric', 'maximum_size:10'],
            'txnsubtypeid'      => ['required', 'numeric', 'maximum_size:10'],
            'handid'            => ['required', 'numeric', 'maximum_size:10'],
            'playerhandle'      => ['required', 'maximum_size:36'],
            'gameplayid'        => ['optional', 'numeric', 'maximum_size:20'],
            'transactions'      => ['optional', 'numeric', 'maximum_size:10'],
            'canceltxnid'       => ['optional', 'numeric', 'maximum_size:20'],
        ]);

        #exit if invalid request parameters
        if (!$is_valid) {
            $response = array(
                'type' => 'fundTransferResp',
                'err' => '9999'
            );

            $response_result_additional_message = ':Invalid Parameters: '. $this->params['txnid'];
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        #validate amount, up to $adjustment_precision decimal places
        $pattern = '/^-?\d+(\.\d{1,' . $this->adjustment_precision . '})?$/';
        if (!(preg_match($pattern, $this->params['amt']))) {
            $response = array(
                'type' => 'fundTransferResp',
                'err' => '9999'
            );

            $response_result_additional_message = ':Invalid Amount: '. $this->params['amt'];
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        #check player existence, else return player not found
        $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $this->params['acctid'], $this->game_platform_id);
        if (empty($this->player_details['player_id'])) {
            $response = array(
                'type' => 'fundTransferResp',
                'err' => '1000'
            );

            $response_result_additional_message = ':Player not found: '. $this->params['acctid'];
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        #get player before balance
        $this->before_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, true);

        #set and check if transaction exist, else return processed
        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->params['gameid'], $this->params['txnid']]);
        $is_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->params['txnid']]);
        if ($is_transaction_exist) {
            $response = array(
                'type' => 'fundTransferResp',
                'cur' => $this->currency,
                'amt' => $this->before_balance,
                'err' => '0'
            );

            $response_result_additional_message = ':Transaction was already processed: '. $this->params['txnid'];
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }


        $controller = $this;
        switch ($this->params['txnsubtypeid']) {

            case self::TRANSACTION_TYPE_BET:
                #bet
                if ($this->params['amt'] < 0 && $this->params['canceltxnid'] == 0) {

                    #check if enough balance, then deduct, else exit
                    if ($this->before_balance >= abs($this->params['amt'])) {
                        $this->game_logs_status = Game_logs::STATUS_PENDING;
                        $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_BET;

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                            return $controller->walletAdjustment($this->ssa_decrease, $this->ssa_insert, abs($this->params['amt']));
                        });
                    } else {
                        $response = array(
                            'type' => 'fundTransferResp',
                            'err' => '1004'
                        );

                        $response_result_additional_message = ':Player balance not enough: '. $this->before_balance;
                        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
                        return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
                    }

                #refund
                } elseif ($this->params['amt'] > 0 && !empty($this->params['canceltxnid'])) {
                    #check if bet transaction exists
                    $is_bet_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->params['canceltxnid'], 'txnsubtypeid' => self::TRANSACTION_TYPE_BET, 'status' => Game_logs::STATUS_PENDING, 'round_id' => $this->params['handid']]);
                    if ($is_bet_transaction_exist) {

                        $this->game_logs_status = Game_logs::STATUS_REFUND;
                        $this->transaction_type = Self::SEAMLESS_TRANSACTION_TYPE_REFUND;
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                            return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, abs($this->params['amt']));
                        });

                        #update bet transactions to cancelled
                        $data = [
                            'status' => Game_logs::STATUS_CANCELLED,
                        ];
                        $where = [
                            'transaction_id' => $this->params['canceltxnid'],
                        ];
                        $this->ssa_update_transaction_with_result_custom($this->transaction_table, $data, $where);
                        
                    } else {
                        $response = array(
                            'type' => 'fundTransferResp',
                            'err' => '9999'
                        );

                        $response_result_additional_message = ':Error Refund: '. $this->params['canceltxnid'];
                        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
                        return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
                    }
                    
                } else {
                    $response = array(
                        'type' => 'fundTransferResp',
                        'err' => '9999'
                    );

                    $response_result_additional_message = ':Incorrect parameters: ';
                    $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
                    return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
                }

                break;

            case self::TRANSACTION_TYPE_WIN:
                #do not allow negative on win
                if ($this->params['amt'] < 0) {
                    $response = array(
                        'type' => 'fundTransferResp',
                        'err' => '9999'
                    );

                    $response_result_additional_message = ':Invalid win amount: ' . $this->params['amt'];
                    $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
                    return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
                }

                $this->game_logs_status = Game_logs::STATUS_SETTLED;
                $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_PAYOUT;
                #check if bet exists before payout
                $is_bet_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['round_id' => $this->params['handid'], 'txnsubtypeid' => self::TRANSACTION_TYPE_BET, 'status' => Game_logs::STATUS_PENDING]);
                if ($is_bet_transaction_exist) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                        return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->params['amt']);
                    });

                    #update bet transactions to settled
                    $data = [
                        'status' => Game_logs::STATUS_SETTLED,
                    ];
                    $where = [
                        'round_id' => $this->params['handid'],
                        'txnsubtypeid' => self::TRANSACTION_TYPE_BET,
                        'status' => Game_logs::STATUS_PENDING
                    ];
                    $this->ssa_update_transaction_with_result_custom($this->transaction_table, $data, $where);

                    #set after balance to before balance if win amount is 0
                    if ($this->params['amt'] == '0.00') {
                        $this->after_balance = $this->before_balance;
                    }

                } else {
                    $response = array(
                        'type' => 'fundTransferResp',
                        'err' => '9999'
                    );

                    $response_result_additional_message = ':Error payout, round_id: '. $this->params['handid'];
                    $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
                    return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
                }
                break;

            default:
                $response = array(
                    'type' => 'fundTransferResp',
                    'err' => '9999'
                );

                $response_result_additional_message = ':Invalid Transaction Subtype: '. $this->params['txnsubtypeid'];
                $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        if (!$success) {
            $response = array(
                'type' => 'fundTransferResp',
                'err' => '1004'
            );

            $response_result_additional_message = ':Transaction failed: '. $this->params['txnid'] . ' Before Balance:'. $this->before_balance . ' After Balance:'. $this->after_balance;
            $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
        }

        $response = array(
            'type' => 'fundTransferResp',
            'cur' => $this->currency,
            'amt' => $this->after_balance,
            'err' => '0'
        );

        $response_result_additional_message = ':Transaction success: '. $this->params['txnid'] . ' Before Balance:'. $this->before_balance . ' After Balance:'. $this->after_balance;
        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . $response_result_additional_message);
        return $this->rebuildResponsefromArrayToXml($response, $response_result_additional_message);
    }

    private function walletAdjustment($adjustment_type, $query_type, $amount)
    {
        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . ': adjustment, query, amount', $adjustment_type, $query_type, $amount);
        $this->bet_amount = $this->win_amount = $after_balance = 0;


        #set amount to be deducted
        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        if ($adjustment_type == $this->ssa_decrease) {
            $this->bet_amount = $amount;
            $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
            $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        } elseif ($adjustment_type == $this->ssa_increase) {
            $this->win_amount = $amount;
            $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
            $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        } else {
            return false;
        }

        $this->after_balance = $after_balance;
        $transaction_data = [
            'amount' => $this->params['amt'],
            'before_balance' => $this->before_balance,
            'after_balance' => $after_balance,
        ];

        $wallet_transaction = $this->rebuildTransactionRequestData($transaction_data);
        $saved_transaction_id = $this->saveTransactionRequestData($wallet_transaction);

        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . ': adjustment, query, amount', $adjustment_type, $query_type, $amount, $wallet_transaction, $saved_transaction_id);
        return true;
    }

    private function rebuildTransactionRequestData($transaction_data)
    {
        $this->utils->debug_log('TTG_SEAMLESS_SERVICE_API:' . __METHOD__ . '-LINE-' . __LINE__ . ': adjustment, query, amount', $transaction_data);
        $transaction_time = $this->ssa_date_time_modifier(null, $this->game_provider_gmt, $this->game_provider_date_time_format);

        $extra_info = [];

        $wallet_transaction = [
            #default
            'game_platform_id' => $this->game_platform_id,
            'player_id' => $this->player_details['player_id'],
            'game_username' => $this->params['acctid'],
            'language' => $this->language,
            'currency' => $this->currency,
            'transaction_type' => $this->transaction_type,
            'transaction_id' => $this->params['txnid'],
            'game_code' => $this->params['gameid'],
            'round_id' => $this->params['handid'],
            'amount' => $transaction_data['amount'],
            'before_balance' => $transaction_data['before_balance'],
            'after_balance' => $transaction_data['after_balance'],
            'status' => $this->game_logs_status,
            'start_at' => $transaction_time,
            'end_at' => $transaction_time,

            #additional
            'txnsubtypeid' => $this->params['txnsubtypeid'],
            'handid' => $this->params['handid'],
            'playerhandle' => $this->params['playerhandle'],
            'gameplayid' => !empty($this->params['gameplayid']) ? $this->params['gameplayid'] : null,
            'transactions' => !empty($this->params['transactions']) ? $this->params['transactions'] : null,
            'canceltxnid' => !empty($this->params['canceltxnid']) ? $this->params['canceltxnid'] : null,

            #default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => json_encode($this->params),
            'response' => null,
            'extra_info' => json_encode($extra_info),
            'bet_amount' => $this->bet_amount,
            'win_amount' => $this->win_amount,

            'result_amount' => 0,
            'flag_of_updated_result' => $this->ssa_flag_not_updated,
            'wallet_adjustment_status' => null,
            'external_unique_id' => $this->params['txnid'],
            'seamless_service_unique_id' => $this->seamless_service_unique_id,
            'external_game_id' => $this->params['gameid'],

        ];
        return $wallet_transaction;
    }


    private function saveTransactionRequestData($wallet_transaction)
    {
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $this->ssa_insert, $wallet_transaction, 'external_unique_id', $this->params['txnid'], false);
        return $saved_transaction_id;
    }



    #build functions
    protected function rebuildResponsefromArrayToXml($arrayData, $response_result_additional_message)
    {
        #save response result

        $status = $arrayData['err'] == 0 ? true : false;
        $this->saveResponseResult($status, $this->params['type'], $this->params, $arrayData, 200, $response_result_additional_message);

        #append _attr to keys
        foreach ($arrayData as $key => $value) {
            $newKey = $key . '_attr';
            $newResponse[$newKey] = $value;
        }

        $response = array(
            'cw' => $newResponse
        );

        return $this->ssa_response_result([
            'response' => $response,
            'content_type' => 'application/xml',
        ]);
    }

    public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

    private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $statusText = null, $extra = null, $fields = [], $cost = null){
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		if(is_array($response)){
			$response = json_encode($response);
		}
		if(is_array($params)){
			$params = json_encode($params);
		}
		$extra = array_merge((array)$extra,(array)$this->headers);
        return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id,
        	$flag,
        	$callMethod,
        	$params,
        	$response,
        	$httpStatusCode,
        	$statusText,
			is_array($extra)?json_encode($extra):$extra,
			$fields,
			false,
			null,
			$cost
        );
	}
}
