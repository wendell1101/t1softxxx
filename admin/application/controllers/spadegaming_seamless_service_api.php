<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Spadegaming_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    // default
    private $game_platform_id;
    private $game_api;
    private $api_method;
    private $language;
    private $currency;
    private $response_result_id;
    private $player_details;
    private $player_balance;
    private $transaction_table;
    private $game_seamless_service_logs_table;
    private $save_game_seamless_service_logs;
    private $saved_transaction_id;
    private $saved_multiple_transactions;
    private $conversion;
    private $precision;
    private $arithmetic_name;
    private $adjustment_precision;
    private $adjustment_conversion;
    private $adjustment_arithmetic_name;
    private $whitelist_ip_validate_api_methods;
    private $game_api_active_validate_api_methods;
    private $game_api_maintenance_validate_api_methods;
    private $transaction_already_exists;
    private $main_params;
    private $extra_params;
    private $is_transfer_type;
    private $game_provider_gmt;
    private $game_provider_date_time_format;

    // additional
    private $token_required_api_methods;
    private $transaction_already_accepted;
    private $transaction_already_settled;
    private $transaction_already_cancelled;
    private $merchant_code;
    private $site_id;

    const SEAMLESS_GAME_API = SPADEGAMING_SEAMLESS_GAME_API;

    const API_SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];

    const API_ERROR_SYSTEM_ERROR = [
        'code' => 1,
        'message' => 'System Error',
    ];

    const API_ERROR_INVALID_REQUEST = [
        'code' => 2,
        'message' => 'Invalid Request',
    ];

    const API_ERROR_SERVICE_INACCESSIBLE = [
        'code' => 3,
        'message' => 'Service Inaccessible',
    ];

    const API_ERROR_REQUEST_TIMEOUT = [
        'code' => 100,
        'message' => 'Request Timeout',
    ];

    const API_ERROR_CALL_LIMITED = [
        'code' => 101,
        'message' => 'Call Limited',
    ];

    const API_ERROR_REQUEST_FORBIDDEN = [
        'code' => 104,
        'message' => 'Request Forbidden',
    ];

    const API_ERROR_MISSING_PARAMETERS = [
        'code' => 105,
        'message' => 'Missing Parameters',
    ];

    const API_ERROR_INVALID_PARAMETERS = [
        'code' => 106,
        'message' => 'Invalid Parameters',
    ];

    const API_ERROR_DUPLICATED_SERIAL_NUMBER = [
        'code' => 107,
        'message' => 'Duplicated Serial NO.',
    ];

    const API_ERROR_RELATED_ID_NOT_FOUND = [
        'code' => 109,
        'message' => 'Related id not found',
    ];

    const API_ERROR_RECORD_ID_NOT_FOUND = [
        'code' => 110,
        'message' => 'Record ID Not Found',
    ];

    const API_ERROR_DUPLICATED_REQUEST = [
        'code' => 111,
        'message' => 'Duplicated request',
    ];

    const API_ERROR_API_CALL_LIMITED = [
        'code' => 112,
        'message' => 'API Call Limited',
    ];

    const API_ERROR_INVALID_ACCOUNT_ID = [
        'code' => 113,
        'message' => 'Invalid Acct ID',
    ];

    const API_ERROR_INVALID_FORMAT = [
        'code' => 118,
        'message' => 'Invalid Format',
    ];

    const API_ERROR_IP_NOT_WHITELISTED = [
        'code' => 120,
        'message' => 'IP not whitelisted',
    ];

    const API_ERROR_SYSTEM_MAINTENANCE = [
        'code' => 5003,
        'message' => 'System Maintenance',
    ];

    const API_ERROR_MERCHANT_NOT_FOUND = [
        'code' => 10113,
        'message' => 'Merchant Not Found',
    ];

    const API_ERROR_MERCHANT_SUSPEND = [
        'code' => 10116,
        'message' => 'Merchant Suspend',
    ];

    const API_ERROR_ACCOUNT_EXIST = [
        'code' => 50099,
        'message' => 'Acct Exist',
    ];

    const API_ERROR_ACCOUNT_NOT_FOUND = [
        'code' => 50100,
        'message' => 'Acct Not Found',
    ];

    const API_ERROR_ACCOUNT_INACTIVE = [
        'code' => 50101,
        'message' => 'Acct Inactive',
    ];

    const API_ERROR_ACCOUNT_LOCKED = [
        'code' => 50102,
        'message' => 'Acct Locked',
    ];

    const API_ERROR_ACCOUNT_SUSPEND = [
        'code' => 50103,
        'message' => 'Acct Suspend',
    ];

    const API_ERROR_TOKEN_VALIDATION_FAILED = [
        'code' => 50104,
        'message' => 'Token Validation Failed',
    ];

    const API_ERROR_INSUFFICIENT_BALANCE = [
        'code' => 50110,
        'message' => 'Insufficient Balance',
    ];

    const API_ERROR_EXCEED_MAX_AMOUNT = [
        'code' => 50111,
        'message' => 'Exceed Max Amount',
    ];

    const API_ERROR_CURRENCY_INVALID = [
        'code' => 50112,
        'message' => 'Currency Invalid',
    ];

    const API_ERROR_AMOUNT_INVALID = [
        'code' => 50113,
        'message' => 'Amount Invalid',
    ];

    const API_ERROR_DATE_FORMAT_INVALID = [
        'code' => 50115,
        'message' => 'Date Format Invalid',
    ];

    const API_METHOD_AUTHORIZE = 'authorize';
    const API_METHOD_GET_BALANCE = 'getBalance';
    const API_METHOD_TRANSFER = 'transfer';

    const TRANSFER_TYPE_PLACE_BET = [
        'name' => 'placeBet',
        'code' => 1,
    ];

    const TRANSFER_TYPE_CANCEL_BET = [
        'name' => 'cancelBet',
        'code' => 2,
    ];

    const TRANSFER_TYPE_PAYOUT = [
        'name' => 'payout',
        'code' => 4,
    ];

    const TRANSFER_TYPE_BONUS = [
        'name' => 'bonus',
        'code' => 7,
    ];

    const ALLOWED_API_METHODS = [
        self::API_METHOD_AUTHORIZE,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_TRANSFER,
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        // default
        'game_platform_id',
        'player_id',
        'game_username',
        'language',
        'currency',
        'transaction_type',
        'transaction_id',
        'game_code',
        'round_id',
        'amount',
        'before_balance',
        'after_balance',
        'status',
        'start_at',
        'end_at',
        // optional
        'type',
        'channel',
        'ticket_id',
        'reference_id',
        'special_game',
        'ref_ticket_ids',
        'game_feature',
        'serial_no',
        'merchant_code',
        'extra_round_id',
        'site_id',
        // default
        'elapsed_time',
        'request',
        'response',
        'extra_info',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'wallet_adjustment_status',
        'external_unique_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        // default
        'before_balance',
        'after_balance',
        'amount',
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_UPDATE = [
        'transaction_type',
        'before_balance',
        'after_balance',
        'amount',
        'end_at',
        'updated_at',
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'wallet_adjustment_status',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_UPDATE = [
        'before_balance',
        'after_balance',
        'amount',
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = [];
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->player_details = $this->saved_multiple_transactions = $this->main_params = $this->extra_params = [];
        $this->game_api = $this->api_method = $this->saved_transaction_id = $this->response_result_id = $this->game_seamless_service_logs_table = null;
        $this->api_method = 'default';
        $this->player_balance = 0;
        $this->conversion = 1;
        $this->precision = 2;
        $this->transaction_already_exists = $this->transaction_already_accepted = $this->transaction_already_settled = $this->transaction_already_cancelled = $this->is_transfer_type = $this->save_game_seamless_service_logs = false;

        $this->token_required_api_methods = [
            self::API_METHOD_AUTHORIZE,
        ];

        $this->setMainParams();
    }

    public function index($game_platform_id = null) {
        $api_method = isset($this->ssa_request_headers['Api']) ? $this->ssa_request_headers['Api'] : null;

        if ($this->initialize($game_platform_id, $api_method)) {
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    private function initialize($game_platform_id, $api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if (empty($game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (initialize empty $game_platform_id)');
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($game_platform_id);

        if ($this->game_api) {
            // default
            $this->game_platform_id = $this->game_api->getPlatformCode();
            $this->transaction_table = $this->game_api->getSeamlessTransactionTable();
            $this->game_seamless_service_logs_table = $this->game_api->getGameSeamlessServiceLogsTable();
            $this->save_game_seamless_service_logs = $this->game_api->save_game_seamless_service_logs;
            $this->language = $this->game_api->language;
            $this->currency = $this->game_api->currency;
            $this->conversion = $this->game_api->conversion;
            $this->precision = $this->game_api->precision;
            $this->arithmetic_name = $this->game_api->arithmetic_name;
            $this->adjustment_precision = $this->game_api->adjustment_precision;
            $this->adjustment_conversion = $this->game_api->adjustment_conversion;
            $this->adjustment_arithmetic_name = $this->game_api->adjustment_arithmetic_name;
            $this->game_provider_gmt = $this->game_api->game_provider_gmt;
            $this->game_provider_date_time_format = $this->game_api->game_provider_date_time_format;
            // Additional
            $this->merchant_code = $this->game_api->merchant_code;
            $this->site_id = $this->game_api->site_id;
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (load_game_api)');
            return false;
        }

        if (!$this->validateRequestPlayer()) {
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Method ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    private function setMainParams() {
        $transfer_type = !empty($this->ssa_request_params['type']) ? $this->ssa_request_params['type'] : null;
        $this->main_params['external_unique_id'] = !empty($this->ssa_request_params['transferId']) ? $this->ssa_composer([$transfer_type, $this->ssa_request_params['transferId']]) : null;
        $this->main_params['token'] = !empty($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null;
        $this->main_params['game_username'] = !empty($this->ssa_request_params['acctId']) ? strtolower($this->ssa_request_params['acctId']) : null;
        $this->main_params['amount'] = !empty($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : 0;
        $this->main_params['transaction_id'] = !empty($this->ssa_request_params['transferId']) ? $this->ssa_request_params['transferId'] : null;
        $this->main_params['game_code'] = !empty($this->ssa_request_params['gameCode']) ? $this->ssa_request_params['gameCode'] : null;

        switch ($transfer_type) {
            case self::TRANSFER_TYPE_PLACE_BET['code']:
                $this->main_params['transaction_type'] = $this->api_method = self::TRANSFER_TYPE_PLACE_BET['name'];
                $this->main_params['round_id'] = !empty($this->ssa_request_params['transferId']) ? $this->ssa_request_params['transferId'] : null;
                break;
            case self::TRANSFER_TYPE_PAYOUT['code']:
                $this->main_params['transaction_type'] = $this->api_method = self::TRANSFER_TYPE_PAYOUT['name'];
                $this->main_params['round_id'] = !empty($this->ssa_request_params['referenceId']) ? $this->ssa_request_params['referenceId'] : null;
                break;
            case self::TRANSFER_TYPE_CANCEL_BET['code']:
                $this->main_params['transaction_type'] = $this->api_method = self::TRANSFER_TYPE_CANCEL_BET['name'];
                $this->main_params['round_id'] = !empty($this->ssa_request_params['referenceId']) ? $this->ssa_request_params['referenceId'] : null;
                break;
            case self::TRANSFER_TYPE_BONUS['code']:
                $this->main_params['transaction_type'] = $this->api_method = self::TRANSFER_TYPE_BONUS['name'];
                $this->main_params['round_id'] = !empty($this->ssa_request_params['transferId']) ? $this->ssa_request_params['transferId'] : null;
                break;
            default:
                $this->main_params['transaction_type'] = null;
                $this->main_params['round_id'] = null;
                break;
        }

        $this->main_params['bet_external_unique_id'] = !empty($this->ssa_request_params['referenceId']) ? self::TRANSFER_TYPE_PLACE_BET['code'] . '-' . $this->ssa_request_params['referenceId'] : null;

        $this->setExtraParams();
    }

    private function setExtraParams() {
        $this->extra_params['reference_id'] = isset($this->ssa_request_params['referenceId']) ? $this->ssa_request_params['referenceId'] : null;
    }

    private function validateRequestPlayer() {
        $token = isset($this->main_params['token']) ? $this->main_params['token'] : null;
        $game_username = isset($this->main_params['game_username']) ? $this->main_params['game_username'] : null;

        if (in_array($this->api_method, $this->token_required_api_methods)) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $token, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_TOKEN_VALIDATION_FAILED;
                return false;
            }
    
            if (!empty($game_username) && $game_username != $this->player_details['game_username']) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_ACCOUNT_NOT_FOUND;
                return false;
            }
        } else {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_ACCOUNT_NOT_FOUND;
                return false;
            }
    
            if ($this->player_details['game_username'] != $game_username) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_ACCOUNT_NOT_FOUND;
                return false;
            }
        }

        $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        return true;
    }

    private function checkPoint($features = [
        'use_ssa_is_server_ip_allowed' => 1,
        'use_ssa_is_game_api_active' => 1,
        'use_ssa_is_game_api_maintenance' => 1,
        'ssa_is_player_blocked' => 1,
    ]) {

        if ($features['use_ssa_is_server_ip_allowed']) {
            if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                $this->ssa_http_response_status_code = 401;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_IP_NOT_WHITELISTED, 'IP address is not allowed');
                return false;
            }
        }

        if ($features['use_ssa_is_game_api_active']) {
            if (!$this->ssa_is_game_api_active($this->game_api)) {
                $this->ssa_http_response_status_code = 503;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SERVICE_INACCESSIBLE, 'Game is disabled');
                return false;
            }
        }

        if ($features['use_ssa_is_game_api_maintenance']) {
            if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                $this->ssa_http_response_status_code = 503;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_MAINTENANCE, 'Game is under maintenance');
                return false;
            }
        }

        if ($features['ssa_is_player_blocked']) {
            if (isset($this->player_details['username']) && $this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
                $this->ssa_http_response_status_code = 401;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_ACCOUNT_LOCKED, 'Player is blocked');
                return false;
            }
        }

        return true;
    }

    private function additionalParamsValidation() {
        if (isset($this->ssa_request_params['language']) && $this->ssa_request_params['language'] != $this->language) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETERS, 'Invalid parameter language');
            return false;
        }

        if (isset($this->ssa_request_params['currency']) && $this->ssa_request_params['currency'] != $this->currency) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::API_ERROR_CURRENCY_INVALID;
            return false;
        }

        if (isset($this->ssa_request_params['merchantCode']) && $this->ssa_request_params['merchantCode'] != $this->merchant_code) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::API_ERROR_MERCHANT_NOT_FOUND;
            return false;
        }

        if ($this->is_transfer_type) {
            if (isset($this->ssa_request_params['serialNo'])) {
                $is_serial_no_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
                    'transaction_type' => $this->api_method,
                    'serial_no' => $this->ssa_request_params['serialNo'],
                ]);
    
                if ($is_serial_no_exist) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = self::API_SUCCESS;
                    return false;
                }
            }
        }

        return true;
    }

    private function validateTransactionRecords() {
        $this->transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->main_params['external_unique_id']]);
        $bet_external_unique_id = $this->main_params['bet_external_unique_id'];
        $is_bet_exist = $this->ssa_is_transaction_exists($this->transaction_table,
        "(external_unique_id='{$bet_external_unique_id}' AND wallet_adjustment_status IN ('{$this->ssa_decreased}', '{$this->ssa_retained}'))"
    );

        $is_payout_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSFER_TYPE_PAYOUT['name'],
            'game_username' => $this->main_params['game_username'],
            'game_code' => $this->main_params['game_code'],
            'round_id' => $this->main_params['round_id'],
            'reference_id' => $this->extra_params['reference_id'],
        ]);
        
        $is_refund_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSFER_TYPE_CANCEL_BET['name'],
            'game_username' => $this->main_params['game_username'],
            'game_code' => $this->main_params['game_code'],
            'round_id' => $this->main_params['round_id'],
            'reference_id' => $this->extra_params['reference_id'],
        ]);

        $is_round_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSFER_TYPE_PLACE_BET['name'],
            'game_username' => $this->main_params['game_username'],
            'game_code' => $this->main_params['game_code'],
            'round_id' => $this->main_params['round_id'],
        ]);

        if ($this->transaction_already_exists) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::API_SUCCESS;
            return false;
        }

        if (!$is_bet_exist && in_array($this->api_method, [
                self::TRANSFER_TYPE_PAYOUT['name'],
                self::TRANSFER_TYPE_CANCEL_BET['name'],
            ])) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::API_ERROR_RELATED_ID_NOT_FOUND;
            return false;
        }

        if ($is_payout_exist && in_array($this->api_method, [
                self::TRANSFER_TYPE_CANCEL_BET['name'],
            ])) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Already processed by payout');
            return false;
        }

        if ($is_refund_exist && in_array($this->api_method, [
                self::TRANSFER_TYPE_PAYOUT['name'],
            ])) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Already processed by cancel bet');
            return false;
        }

        if (!$is_round_exist && in_array($this->api_method, [
            self::TRANSFER_TYPE_PAYOUT['name'],
            self::TRANSFER_TYPE_CANCEL_BET['name'],
        ])) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETERS, 'Invalid parameters. Check type, gameCode or referenceId');
            return false;
        }

        return true;
    }

    private function authorize() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
            'ssa_is_player_blocked' => 1,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'acctId' => ['required'],
                'token' => ['required'],
                'language' => ['required'],
                'serialNo' => ['required'],
                'merchantCode' => ['required'],
            ]);
    
            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    $self = $this;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($self) {
                        $validate_balance = true;
                        $result = $self->ssa_get_player_wallet_balance($self->player_details['player_id'], $self->game_platform_id, $validate_balance);
    
                        if (!$result['success']) {
                            return false;
                        }
    
                        return true;
                    });
    
                    if ($success) {
                        $this->ssa_http_response_status_code = 200;
                        $this->ssa_operator_response = self::API_SUCCESS;
                    } else {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Error in wallet adjustment');
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function getBalance() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
            'ssa_is_player_blocked' => 1,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'acctId' => ['required'],
                'serialNo' => ['required'],
                'merchantCode' => ['required'],
                'gameCode' => ['optional'],
            ]);

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    $self = $this;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($self) {
                        $validate_balance = true;
                        $result = $self->ssa_get_player_wallet_balance($self->player_details['player_id'], $self->game_platform_id, $validate_balance);

                        if (!$result['success']) {
                            return false;
                        }

                        return true;
                    });

                    if ($success) {
                        $this->ssa_http_response_status_code = 200;
                        $this->ssa_operator_response = self::API_SUCCESS;
                    } else {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Error in getting balance');
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function transfer() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'type' => ['required'],
        ]);

        if ($is_valid) {
            switch ($this->ssa_request_params['type']) {
                case self::TRANSFER_TYPE_PLACE_BET['code']:
                    return $this->placeBet();
                case self::TRANSFER_TYPE_CANCEL_BET['code']:
                    return $this->cancelBet();
                case self::TRANSFER_TYPE_PAYOUT['code']:
                    return $this->payout();
                case self::TRANSFER_TYPE_BONUS['code']:
                    return $this->bonus();
                default:
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (undefined transfer type)');
                    break;
            } 
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function placeBet() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->is_transfer_type = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
            'ssa_is_player_blocked' => 1,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'transferId' => ['required'],  // transaction_id
                'acctId' => ['required'], // game_username
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric'], // amount
                'type' => ['required'],
                'channel' => ['optional'],
                'gameCode' => ['required'], //game_code
                'ticketId' => ['optional'],
                'referenceId' => ['optional'],
                'specialGame' => ['optional'],
                'refTicketIds' => ['optional'],
                'gameFeature' => ['optional'],
                'serialNo' => ['optional'],
                'merchantCode' => ['optional'],
            ]);

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    if ($this->validateTransactionRecords()) {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                            return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->main_params['amount']);
                        });
    
                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::API_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function payout() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->is_transfer_type = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
            'ssa_is_player_blocked' => 0,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'transferId' => ['required'],  // transaction_id
                'acctId' => ['required'], // game_username
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric'], // amount
                'type' => ['required'],
                'channel' => ['required'],
                'gameCode' => ['required'], //game_code
                'ticketId' => ['optional'],
                'referenceId' => ['optional'],
                'specialGame' => ['optional'],
                'refTicketIds' => ['optional'],
                'gameFeature' => ['optional'],
                'serialNo' => ['optional'],
                'merchantCode' => ['optional'],
            ]);

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    if ($this->validateTransactionRecords()) {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
                        });

                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::API_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function cancelBet() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->is_transfer_type = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
            'ssa_is_player_blocked' => 0,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'transferId' => ['required'],  // transaction_id
                'acctId' => ['required'], // game_username
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric'], // amount
                'type' => ['required'],
                'channel' => ['required'],
                'gameCode' => ['required'], //game_code
                'ticketId' => ['optional'],
                'referenceId' => ['optional'],
                'specialGame' => ['optional'],
                'refTicketIds' => ['optional'],
                'gameFeature' => ['optional'],
                'serialNo' => ['optional'],
                'merchantCode' => ['optional'],
            ]);

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    if ($this->validateTransactionRecords()) {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
                        });

                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::API_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function bonus() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->is_transfer_type = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
            'ssa_is_player_blocked' => 0,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'transferId' => ['required'],  // transaction_id
                'acctId' => ['required'], // game_username
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric'], // amount
                'type' => ['required'],
                'channel' => ['required'],
                'gameCode' => ['required'], //game_code
                'ticketId' => ['optional'],
                'referenceId' => ['optional'],
                'specialGame' => ['optional'],
                'refTicketIds' => ['optional'],
                'gameFeature' => ['optional'],
                'serialNo' => ['optional'],
                'merchantCode' => ['optional'],
                'roundId' => ['optional'], // bonus type only
                'siteId' => ['optional'], // bonus type only
            ]);

            /* For Bonus type
                Important Note: 
                1. Bonus don’t have reference Id, due to don’t placebet.
                2. RoundId and siteId will be enabled upon request
            */

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    if ($this->validateTransactionRecords()) {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
                        });

                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::API_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_MISSING_PARAMETERS, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function walletAdjustment($adjustment_type, $query_type, $amount) {
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_uniqueid_of_seamless_service($this->game_platform_id . '-' . $this->main_params['external_unique_id']);
        $this->ssa_set_external_game_id($this->main_params['game_code']);

        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance;

        $transaction_data = [
            'saved_transaction_id' => $this->saved_transaction_id,
            'amount' => $amount,
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
            'wallet_adjustment_status' => $this->ssa_preserved,
        ];

        if ($amount < 0) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::API_ERROR_AMOUNT_INVALID;
            return false;
        }

        if ($query_type == $this->ssa_insert) {
            if ($adjustment_type == $this->ssa_decrease) {
                if ($amount > $before_balance) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::API_ERROR_INSUFFICIENT_BALANCE; // Insufficient balance
                    return false;
                }
            }

            // save transaction data first
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $transaction_data);
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            $transaction_data['saved_transaction_id'] = $this->saved_transaction_id;

            if ($this->saved_transaction_id) {
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'data has been saved.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

                if ($adjustment_type == $this->ssa_decrease) {
                    $this->ssa_set_game_provider_action_type('bet');
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);
    
                    if ($success) {
                        $transaction_data['after_balance'] = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_decreased;
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = self::API_ERROR_INSUFFICIENT_BALANCE; // Insufficient balance
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;
                    }
                } elseif ($adjustment_type == $this->ssa_increase) {
                    $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['round_id' => $this->main_params['round_id']]);
                    $related_unique_id = "game-".$this->game_platform_id . '-' . $bet_transaction['external_unique_id'];
                    
                    $this->ssa_set_related_uniqueid_of_seamless_service($related_unique_id);
                    $this->ssa_set_related_action_of_seamless_service("bet");
                    $this->ssa_set_game_provider_action_type('payout');
                    $this->ssa_set_game_provider_is_end_round(true);
                    
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);
    
                    if ($success) {
                        $transaction_data['after_balance'] = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_increased;
                    } else {
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (ssa_increase_player_wallet)');
                    }
                } else {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (walletAdjustment default)');
                    return false;
                }

                array_push($this->saved_multiple_transactions, $transaction_data);
                return $success;
            } else {
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'failed to save data.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_SYSTEM_ERROR, 'Internal Server Error (saveTransactionRequestData)');
                return false;
            }
        }

        return false;
    }

    private function rebuildTransactionRequestData($query_type, $transaction_data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($query_type == $this->ssa_insert) {
            $new_transaction_data = [
                // default
                'game_platform_id' => $this->game_platform_id,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                'language' => $this->language,
                'currency' => $this->currency,
                'transaction_type' => $this->api_method,
                'transaction_id' => isset($this->main_params['transaction_id']) ? $this->main_params['transaction_id'] : null,
                'game_code' => isset($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
                'round_id' => isset($this->main_params['round_id']) ? $this->main_params['round_id'] : null,
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'status' => $this->setStatus(),
                'start_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
                'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
                // optional
                'type' => isset($this->ssa_request_params['type']) ? $this->ssa_request_params['type'] : null,
                'channel' => isset($this->ssa_request_params['channel']) ? $this->ssa_request_params['channel'] : null,
                'ticket_id' => isset($this->ssa_request_params['ticketId']) ? $this->ssa_request_params['ticketId'] : null,
                'reference_id' => $this->extra_params['reference_id'],
                'special_game' => isset($this->ssa_request_params['specialGame']) ? json_encode($this->ssa_request_params['specialGame']) : null,
                'ref_ticket_ids' => isset($this->ssa_request_params['refTicketIds']) ? json_encode($this->ssa_request_params['refTicketIds']) : null,
                'game_feature' => isset($this->ssa_request_params['gameFeature']) ? $this->ssa_request_params['gameFeature'] : null,
                'serial_no' => isset($this->ssa_request_params['serialNo']) ? $this->ssa_request_params['serialNo'] : null,
                'merchant_code' => isset($this->ssa_request_params['merchantCode']) ? $this->ssa_request_params['merchantCode'] : null,
                'extra_round_id' => isset($this->ssa_request_params['roundId']) ? $this->ssa_request_params['roundId'] : null,
                'site_id' => isset($this->ssa_request_params['siteId']) ? $this->ssa_request_params['siteId'] : null,
                // default
                'elapsed_time' => $this->utils->getCostMs(),
                'request' => json_encode($this->ssa_request_params),
                'response' => null,
                'extra_info' => null,
                'bet_amount' => isset($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : 0,
                'win_amount' => isset($this->extra_params['winlose_amount']) ? $this->extra_params['winlose_amount'] : 0,
                'result_amount' => isset($this->extra_params['result_amount']) ? $this->extra_params['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->extra_params['flag_of_updated_result']) ? $this->extra_params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
                'wallet_adjustment_status' => $transaction_data['wallet_adjustment_status'],
                'external_unique_id' => $this->main_params['external_unique_id'],
            ];

            $new_transaction_data['md5_sum'] = $this->ssa_generate_md5_sum($new_transaction_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        } else {
            $new_transaction_data = [
                'transaction_type' => $this->api_method,
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
                'updated_at' => $this->utils->getNowForMysql(),
                'status' => $this->setStatus(),
                'bet_amount' => 0,
                'win_amount' => 0,
                'result_amount' => 0,
                'flag_of_updated_result' => $this->ssa_flag_not_updated,
            ];

            $new_transaction_data['md5_sum'] = $this->ssa_generate_md5_sum($new_transaction_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
        }

        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        return $new_transaction_data;
    }

    private function saveTransactionRequestData($query_type, $transaction_data) {
        $new_transaction_data = $this->rebuildTransactionRequestData($query_type, $transaction_data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $new_transaction_data, 'external_unique_id', $this->main_params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    private function setStatus() {
        switch ($this->api_method) {
            case self::TRANSFER_TYPE_PLACE_BET['name']:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::TRANSFER_TYPE_PAYOUT['name']:
            case self::TRANSFER_TYPE_BONUS['name']:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::TRANSFER_TYPE_CANCEL_BET['name']:
                $result = Game_logs::STATUS_REFUND;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    private function saveGameSeamlessServiceLogs() {
        if (!empty($this->game_seamless_service_logs_table) && $this->save_game_seamless_service_logs) {
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::API_ERROR_SYSTEM_ERROR['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::API_ERROR_SYSTEM_ERROR['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;

            $data = [
                'game_platform_id' => $this->game_platform_id,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                'transaction_type' => $this->api_method,
                'game_code' => !empty($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
                'round_id' => !empty($this->main_params['round_id']) ? $this->main_params['round_id'] : null,
                'status_code' => isset($http_response['code']) ? $http_response['code'] : null,
                'status_text' => isset($http_response['text']) ? $http_response['text'] : null,
                'response_code' => $code,
                'response_message' => $message,
                'flag' => $flag,
                'serial_no' => !empty($this->ssa_request_params['serialNo']) ? $this->ssa_request_params['serialNo'] : null,
                'request' => json_encode($this->ssa_request_params),
                'response' => json_encode($this->ssa_operator_response),
                'extra_info' => null,
                'external_unique_id' => !empty($this->main_params['external_unique_id']) ? $this->main_params['external_unique_id'] : null,
            ];

            $data['md5_sum'] = md5(json_encode($data));
            $data['elapsed_time'] = $this->utils->getCostMs();
            $data['response_result_id'] = $this->response_result_id;
            $data['transaction_id'] = !empty($this->main_params['transaction_id']) ? $this->main_params['transaction_id'] : null;
            $data['call_count'] = 1;
 
            $is_md5_sum_logs_exist = $this->ssa_is_md5_sum_logs_exist($this->game_seamless_service_logs_table, $data['md5_sum']);

            if (!$is_md5_sum_logs_exist) {
                $this->ssa_save_failed_request($this->game_seamless_service_logs_table, $this->ssa_insert, $data);
            } else {
                $call_count = $this->ssa_get_logs_call_count($this->game_seamless_service_logs_table, $data['md5_sum']);
                $this->ssa_save_failed_request($this->game_seamless_service_logs_table, $this->ssa_update, ['call_count' => $call_count += $data['call_count']], 'md5_sum', $data['md5_sum']);
            }
        }
    }

    private function rebuildOperatorResponse($flag, $operator_response) {
        $code = isset($operator_response['code']) ? $operator_response['code'] : self::API_ERROR_SYSTEM_ERROR['code'];
        $message = isset($operator_response['message']) ? $operator_response['message'] : self::API_ERROR_SYSTEM_ERROR['message'];
        $balance = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);

        if ($flag == Response_result::FLAG_NORMAL) {
            if ($this->is_transfer_type) {
                $operator_response = [
                    'transferId' => $this->ssa_request_params['transferId'],
                    'merchantCode' => $this->merchant_code,
                    'merchantTxId' => $this->main_params['external_unique_id'],
                    'acctId' => $this->player_details['game_username'],
                    'balance' => $balance,
                    'code' => $code,
                    'msg' => $message,
                ];

                if (isset($this->ssa_request_params['serialNo'])) {
                    $this->ssa_operator_response['serialNo'] = $this->ssa_request_params['serialNo'];
                }
            } else {
                $operator_response = [
                    'acctInfo' => [
                        'acctId' => $this->player_details['game_username'],
                        'balance' => $balance,
                        'userName' => $this->player_details['game_username'],
                        'currency' => $this->currency,
                    ],
                    'merchantCode' => $this->merchant_code,
                    'code' => $code,
                    'msg' => $message,
                    'serialNo' => $this->ssa_request_params['serialNo'],
                ];

                if (!empty($this->site_id)) {
                    $this->ssa_operator_response['acctInfo']['siteId'] = $this->site_id;
                }
            }
        } else {
            $operator_response = [
                'code' => $code,
                'msg' => $message,
            ];
        }

        return $operator_response;
    }

    private function finalizeTransactionData($operator_response) {
        if ($this->is_transfer_type) {
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            if (!empty($this->saved_multiple_transactions) && is_array($this->saved_multiple_transactions)) {
                foreach ($this->saved_multiple_transactions as $transaction_data) {
                    $saved_transaction_id = isset($transaction_data['saved_transaction_id']) ? $transaction_data['saved_transaction_id'] : null;
                    $after_balance = isset($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
                    $wallet_adjustment_status = isset($transaction_data['wallet_adjustment_status']) ? $transaction_data['wallet_adjustment_status'] : $this->ssa_preserved;
    
                    $updated_data = [
                        'after_balance' => $after_balance,
                        'wallet_adjustment_status' => $wallet_adjustment_status,
                        'response' => json_encode($operator_response),
                        'response_result_id' => $this->response_result_id,
                    ];
    
                    if (!empty($saved_transaction_id)) {
                        $this->ssa_update_transaction_without_result($this->transaction_table, $updated_data, 'id', $saved_transaction_id);
                    }
                }
            }
    
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        }
    }

    private function response() {
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $operator_response = $this->rebuildOperatorResponse($flag, $this->ssa_operator_response);
        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id);

        $this->finalizeTransactionData($operator_response);
        $this->saveGameSeamlessServiceLogs();
        return $this->returnJsonResult($operator_response, true, '*', false, false, $this->ssa_http_response_status_code);
    }
}