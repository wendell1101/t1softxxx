<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Betgames_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    #START: default_local_properties ------------------------------------------------------------------------------------->
    protected $game_platform_id = null;
    protected $game_api = null;
    protected $api_method = 'default';
    protected $language = null;
    protected $currency = null;
    protected $response_result_id = null;
    protected $player_details = [];
    protected $player_balance = 0;
    protected $transaction_table = null;
    protected $game_seamless_service_logs_table = null;
    protected $save_game_seamless_service_logs = false;
    protected $saved_transaction_id = null;
    protected $saved_multiple_transactions = [];

    // conversions
    protected $conversion = 1;
    protected $precision = 2;
    protected $arithmetic_name = '';
    protected $adjustment_conversion = 1;
    protected $adjustment_precision = 2;
    protected $adjustment_arithmetic_name = '';

    protected $transaction_already_exists = false;
    protected $main_params = [];
    protected $extra_params = [];
    protected $params = [];
    protected $request_params = [];
    protected $game_provider_gmt = '+0 hours';
    protected $game_provider_date_time_format = 'Y-m-d H:i:s';
    protected $whitelist_ip_validate_api_methods = [];
    protected $game_api_active_validate_api_methods = [];
    protected $game_api_maintenance_validate_api_methods = [];
    protected $required_token_api_methods = [];
    protected $validate_player_api_methods = [];
    protected $validate_block_player_api_methods = [];
    protected $transfer_type_api_methods = [];
    protected $content_type_json_api_methods = [];
    protected $content_type_xml_api_methods = [];
    protected $content_type_plain_text_api_methods = [];
    protected $save_data_api_methods = [];
    protected $encrypt_response_api_methods = [];
    protected $is_transfer_type = false;
    protected $response_type = 'json';
    protected $content_type = 'application/json';
    protected $save_data = false;
    protected $encrypt_response = false;
    protected $seamless_service_unique_id = null;
    protected $external_game_id = null;
    protected $rebuilded_operator_response = [];
    protected $get_usec = false;
    protected $use_strip_slashes = true;
    protected $allowed_negative_balance_api_methods = [];
    protected $action = null;
    protected $show_hint = true;
    protected $external_unique_id;
    protected $transaction_type = 'unknown';
    protected $external_transaction_id = null;
    protected $prefix_for_external_transaction_id = 'T';
    protected $additional_operator_response = [];

    // monthly transaction table
    protected $use_monthly_transactions_table = false;
    protected $force_check_previous_transactions_table = false;
    protected $force_check_other_transactions_table = false;
    protected $previous_table = null;

    const DEBIT = 'debit';
    const CREDIT = 'credit';
    #END: default_local_properties ------------------------------------------------------------------------------------->

    # Default system error code ---------------
    const SYSTEM_ERROR_INTERNAL_SERVER_ERROR = [
        // 'code' => 'SE_0',
        'code' => 500,
        'message' => 'Internal Server Error',
    ];

    const SYSTEM_ERROR_EMPTY_GAME_API = [
        // 'code' => 'SE_1',
        'code' => 500,
        'message' => 'Empty game platform id',
    ];

    const SYSTEM_ERROR_INITIALIZE_GAME_API = [
        // 'code' => 'SE_2',
        'code' => 500,
        'message' => 'Failed to load game API',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND = [
        // 'code' => 'SE_3',
        'code' => 500,
        'message' => 'Function method not found',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN = [
        // 'code' => 'SE_4',
        'code' => 500,
        'message' => 'Function method forbidden',
    ];

    const SYSTEM_ERROR_DECREASE_BALANCE = [
        // 'code' => 'SE_5',
        'code' => 500,
        'message' => 'Error decrease balance',
    ];

    const SYSTEM_ERROR_INCREASE_BALANCE = [
        // 'code' => 'SE_6',
        'code' => 500,
        'message' => 'Error increase balance',
    ];

    const SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT = [
        // 'code' => 'SE_7',
        'code' => 500,
        'message' => 'Error wallet adjustment',
    ];

    const SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA = [
        // 'code' => 'SE_8',
        'code' => 500,
        'message' => 'Error save transaction request data',
    ];

    const SYSTEM_ERROR_SAVE_SERVICE_LOGS = [
        // 'code' => 'SE_9',
        'code' => 500,
        'message' => 'Error save service logs',
    ];

    const SYSTEM_ERROR_REBUILD_OPERATOR_RESPONSE = [
        // 'code' => 'SE_10',
        'code' => 500,
        'message' => 'Error rebuild operator response',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_DOUBLE_UNIQUEID = [
        // 'code' => 'SE_11',
        'code' => 500,
        'message' => 'Remote wallet double unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID = [
        // 'code' => 'SE_12',
        'code' => 500,
        'message' => 'Remote wallet invalid unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INSUFFICIENT_BALANCE = [
        // 'code' => 'SE_13',
        'code' => 500,
        'message' => 'Remote wallet insufficient balance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE = [
        // 'code' => 'SE_14',
        'code' => 500,
        'message' => 'Remote wallet maintenance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN = [
        // 'code' => 'SE_15',
        'code' => 500,
        'message' => 'Remote wallet unknown error',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_ADJUSTED_IN_LOCK_BALANCE = [
        // 'code' => 'SE_16',
        'code' => 500,
        'message' => 'Remote wallet adjusted in lock balance',
    ];

    const SYSTEM_ERROR_SWITCH_DB_FAILED = [
        // 'code' => 'SE_17',
        'code' => 500,
        'message' => 'Switch DB Failed',
    ];

    # CUSTOM RESPONSE CODES HERE
    const RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED = [
        // 'code'=> 'RE_1',
        'code' => 400,
        'message' => 'IP address is not allowed'
    ];

    const RESPONSE_ERROR_GAME_DISABLED = [
        // 'code'=> 'RE_2',
        'code' => 400,
        'message' => 'Game is disabled'
    ];

    const RESPONSE_ERROR_GAME_MAINTENANCE = [
        // 'code'=> 'RE_3',
        'code' => 400,
        'message' => 'Game is maintenance'
    ];

    const RESPONSE_ERROR_INVALID_PARAMETER = [
        // 'code'=> 'RE_4',
        'code' => 400,
        'message' => 'Invalid parameter'
    ];

    const RESPONSE_ERROR_PLAYER_BLOCKED = [
        // 'code'=> 'RE_5',
        'code' => 400,
        'message' => 'Player is blocked'
    ];

    # API RESPONSE
    const RESPONSE_SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];

    const RESPONSE_WRONG_SIGNATURE = [
        'code' => 1,
        'message' => 'Wrong signature',
    ];

    const RESPONSE_REQUEST_EXPIRED = [
        'code' => 2,
        'message' => 'Request is expired',
    ];

    const RESPONSE_INVALID_TOKEN = [
        'code' => 3,
        'message' => 'Invalid token',
    ];

    const RESPONSE_BET_TRANSACTION_NOT_EXIST = [
        'code' => 700,
        'message' => 'There is no PAYIN with provided bet_id',
    ];

    const RESPONSE_INVALID_PLAYER = [
        'code' => 702,
        'message' => 'Invalid Player',
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        'code' => 703,
        'message' => 'Insufficient balance',
    ];

    # API METHODS HERE
    const API_METHOD_PING = 'ping';
    const API_METHOD_GET_ACCOUNT_DETAILS = 'get_account_details';
    const API_METHOD_REFRESH_TOKEN = 'refresh_token';
    const API_METHOD_REQUEST_NEW_TOKEN = 'request_new_token';
    const API_METHOD_GET_BALANCE = 'get_balance';
    const API_METHOD_TRANSACTION_BET_PAYIN = 'transaction_bet_payin';
    const API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN = 'transaction_bet_subscription_payin';
    const API_METHOD_TRANSACTION_BET_PAYOUT = 'transaction_bet_payout';
    const API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN = 'transaction_bet_combination_payin';
    const API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT = 'transaction_bet_combination_payout';
    const API_METHOD_TRANSACTION_PROMO_PAYOUT = 'transaction_promo_payout';
    const API_METHOD_TRANSACTION_BET_MULTI_PAYIN = 'transaction_bet_multi_payin';

    const API_METHODS = [
        self::API_METHOD_PING,
        self::API_METHOD_GET_ACCOUNT_DETAILS,
        self::API_METHOD_REFRESH_TOKEN,
        self::API_METHOD_REQUEST_NEW_TOKEN,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_TRANSACTION_BET_PAYIN,
        self::API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN,
        self::API_METHOD_TRANSACTION_BET_PAYOUT,
        self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN,
        self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT,
        self::API_METHOD_TRANSACTION_PROMO_PAYOUT,
        self::API_METHOD_TRANSACTION_BET_MULTI_PAYIN,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_TRANSACTION_BET_PAYIN,
        self::API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN,
        self::API_METHOD_TRANSACTION_BET_PAYOUT,
        self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN,
        self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT,
        self::API_METHOD_TRANSACTION_PROMO_PAYOUT,
        self::API_METHOD_TRANSACTION_BET_MULTI_PAYIN,
    ];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'get_token';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN
    ];

    # ADDITIONAL PROPERTIES HERE
    protected $provider;
    protected $response_id;
    protected $time;
    protected $secret_key;
    protected $token_timeout_seconds;
    protected $force_refresh_token_timeout;
    protected $get_token_test_accounts;

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->response_id = $this->utils->getGUIDv4();
        $this->time = time();
    }

    public function index($game_platform_id, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->action = $action;
        $this->api_method = $api_method = !empty($this->ssa_request_params['method']) ? $this->ssa_request_params['method'] : null;

        if ($this->initialize()) {
            if ($action == self::ACTION_GET_TOKEN) {
                return $this->$action();
            }

            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    protected function initialize() {
        $this->ssa_initialize_game_api($this->game_platform_id);
        $this->game_api = $this->ssa_game_api;

        $this->responseConfig();

        if (!empty($this->game_api)) {
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
            $this->get_usec = $this->game_api->get_usec;
            $this->show_hint = $this->game_api->show_hint;

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
            $this->previous_table = $this->game_api->ymt_get_previous_year_month_table();

            // additional
            $this->provider = $this->game_api->provider;
            $this->secret_key = $this->game_api->secret_key;
            $this->get_token_test_accounts = $this->game_api->get_token_test_accounts;
            $this->token_timeout_seconds = $this->game_api->token_timeout_seconds;
            $this->force_refresh_token_timeout = $this->game_api->force_refresh_token_timeout;
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = self::SYSTEM_ERROR_INITIALIZE_GAME_API;
            $this->ssa_hint[self::SYSTEM_ERROR_INITIALIZE_GAME_API['code']] = self::SYSTEM_ERROR_INITIALIZE_GAME_API['message'];
            return false;
        }

        if ($this->action == self::ACTION_GET_TOKEN) {
            return true;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $this->api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND;
            $this->ssa_hint[self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code']] = self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message'];
            return false;
        }

        if ($this->ssa_is_api_method_allowed($this->api_method, self::API_METHODS)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN;
            $this->ssa_hint[self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code']] = self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message'];
            return false;
        }

        return true;
    }

    protected function responseConfig() {
        $this->transfer_type_api_methods = self::TRANSFER_TYPE_API_METHODS;
        $this->content_type = $this->ssa_content_type_application_xml;
        $this->content_type_json_api_methods = [];
        $this->content_type_xml_api_methods = self::API_METHODS;
        $this->content_type_plain_text_api_methods = [];
        $this->save_data_api_methods = self::API_METHODS;
        $this->encrypt_response_api_methods = [];
        $this->allowed_negative_balance_api_methods = [];

        if (in_array($this->api_method, $this->transfer_type_api_methods)) {
            $this->is_transfer_type = true;
        }

        if (in_array($this->api_method, $this->content_type_json_api_methods)) {
            $this->content_type = $this->ssa_content_type_application_json;
        }

        if (in_array($this->api_method, $this->content_type_xml_api_methods)) {
            $this->content_type = $this->ssa_content_type_application_xml;
        }

        if (in_array($this->api_method, $this->content_type_plain_text_api_methods)) {
            $this->content_type = $this->ssa_content_type_text_plain;
        }

        if (in_array($this->api_method, $this->save_data_api_methods)) {
            $this->save_data = true;
        }

        if (in_array($this->api_method, $this->encrypt_response_api_methods)) {
            $this->encrypt_response = true;
        }
    }

    protected function rebuildRequestParams($data = null) {
        // request params
        $this->request_params['token'] = isset($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null;
        $this->request_params['request_id'] = isset($this->ssa_request_params['request_id']) ? $this->ssa_request_params['request_id'] : null;
        $this->request_params['time'] = isset($this->ssa_request_params['time']) ? $this->ssa_request_params['time'] : null;
        $this->request_params['signature'] = isset($this->ssa_request_params['signature']) ? $this->ssa_request_params['signature'] : null;

        $this->request_params['type'] = isset($data['type']) ? $data['type'] : null;
        $this->request_params['bet_type'] = isset($data['bet_type']) ? $data['bet_type'] : null;
        $this->request_params['promo_type'] = isset($data['promo_type']) ? $data['promo_type'] : null;
        $this->request_params['bet_id'] = isset($data['bet_id']) ? $data['bet_id'] : null;
        $this->request_params['subscription_id'] = isset($data['subscription_id']) ? $data['subscription_id'] : null;
        $this->request_params['combination_id'] = isset($data['combination_id']) ? $data['combination_id'] : null;
        $this->request_params['promo_transaction_id'] = isset($data['promo_transaction_id']) ? $data['promo_transaction_id'] : null;
        $this->request_params['bet_time'] = isset($data['bet_time']) ? $data['bet_time'] : null;
        $this->request_params['subscription_time'] = isset($data['subscription_time']) ? $data['subscription_time'] : null;
        $this->request_params['combination_time'] = isset($data['combination_time']) ? $data['combination_time'] : null;
        $this->request_params['draw_time'] = isset($data['draw_time']) ? $data['draw_time'] : null;
        $this->request_params['draw_code'] = isset($data['draw_code']) ? $data['draw_code'] : null;
        $this->request_params['draw'] = isset($data['draw']) ? $data['draw'] : null;
        $this->request_params['odd_extra'] = isset($data['odd_extra']) ? $data['odd_extra'] : null;
        $this->request_params['retrying'] = isset($data['retrying']) ? $data['retrying'] : 0;
        $this->request_params['is_mobile'] = isset($data['is_mobile']) ? $data['is_mobile'] : 0;
        $this->request_params['bet'] = isset($data['bet']) ? $data['bet'] : null;
        $this->request_params['odd'] = isset($data['odd']) ? $data['odd'] : null;

        $this->request_params['game_username'] = isset($data['player_id']) ? strtolower($data['player_id']) : null;
        $this->request_params['transaction_id'] = isset($data['transaction_id']) ?$data['transaction_id'] : null;
        $this->request_params['round_id'] = isset($data['bet_id']) ? $data['bet_id'] : null;
        $this->request_params['game_code'] = isset($data['game_code']) ? $data['game_code'] : null;
        $this->request_params['amount'] = !empty($data['amount']) ? $data['amount'] : 0;

        if ($this->api_method == self::API_METHOD_TRANSACTION_PROMO_PAYOUT) {
            $this->request_params['external_unique_id'] = $this->utils->mergeArrayValues([
                $this->api_method,
                $this->request_params['promo_transaction_id'],
            ]);
        } else {
            $this->request_params['external_unique_id'] = $this->utils->mergeArrayValues([
                $this->api_method,
                $this->request_params['transaction_id'],
            ]);
        }
        

        if ($this->api_method == self::API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN) {

        }
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {
            $this->ssa_http_response_status_code = 400;
            
            if ($get_player_by == 'token') {
                $this->ssa_operator_response = self::RESPONSE_INVALID_TOKEN;
                $this->ssa_hint['userid'] = 'Token not found';
            } else {
                $this->ssa_operator_response = self::RESPONSE_INVALID_PLAYER;
                $this->ssa_hint['userid'] = 'User not found';
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true) {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_ERROR_PLAYER_BLOCKED;
            $this->ssa_hint['blocked'] = 'player is blocked';
            return true;
        } else {
            return false;
        }
    }

    protected function isTimeout($timeout_at) {
        if ($this->utils->isTimeout($timeout_at)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INVALID_TOKEN;
            $this->ssa_hint['invalid_token'] = self::RESPONSE_INVALID_TOKEN['message'];
            return true;
        } else {
            return false;
        }
    }

    protected function systemCheckpoint($config = [
        'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
        'USE_GAME_API_DISABLED' => true,
        'USE_GAME_API_MAINTENANCE' => true,
    ]) {
        $this->ssa_system_checkpoint($config);

        if ($this->ssa_system_errors('SERVER_IP_ADDRESS_NOT_ALLOWED')) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED;
            $this->ssa_hint['ipaddress'] = $this->ssa_get_ip_address();
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = self::RESPONSE_ERROR_GAME_DISABLED;
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = self::RESPONSE_ERROR_GAME_MAINTENANCE;
            return false;
        }

        // additional

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INSUFFICIENT_BALANCE;
            $this->ssa_hint['amount'] = self::RESPONSE_INSUFFICIENT_BALANCE['message'];
            return true;
        }

        return false;
    }

    protected function isAllowedNegativeBalance() {
        if (!empty($this->allowed_negative_balance_api_methods) && is_array($this->allowed_negative_balance_api_methods)) {
            if (in_array($this->api_method, $this->allowed_negative_balance_api_methods)) {
                return true;
            }
        }

        return false;
    }

    protected function afterBalance($after_balance = null) {
        return !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
    }

    protected function remoteWalletError(&$data) {
        // treat success if remote wallet return double uniqueid
        if ($this->ssa_remote_wallet_error_double_unique_id()) {
            return $data['is_processed'] = true;
        }

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID;
            $this->ssa_hint[self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']] = self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message'];
            $data['wallet_adjustment_status'] = $this->ssa_failed;
            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INSUFFICIENT_BALANCE;
            $data['wallet_adjustment_status'] = $this->ssa_failed;
            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE;
            $this->ssa_hint[self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']] = self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message'];
            $data['wallet_adjustment_status'] = $this->ssa_failed;
            return $data['is_processed'] = false;
        }

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN;
        $this->ssa_hint[self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['code']] = self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message'];
        $data['wallet_adjustment_status'] = $this->ssa_failed;
        return $data['is_processed'] = false;
    }

    protected function walletAdjustment($adjustment_type, $query_type, $is_transaction_already_exists = false, $save_transaction_record_only = false, $allowed_negative_balance = false, $data = []) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        // set remote wallet unique ids
        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([
            $this->game_platform_id,
            $this->request_params['external_unique_id'],
        ]);

        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->request_params['game_code']);

        $before_balance = $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
        $amount = $this->ssa_operate_amount(abs($this->request_params['amount']), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        if ($adjustment_type == $this->ssa_decrease) {
            // if negative balance not allowed, check if insufficient
            if (!$allowed_negative_balance) {
                if ($this->isInsufficientBalance($this->player_balance, $amount)) {
                    return false;
                }
            }
        }

        $data['external_unique_id'] = $this->request_params['external_unique_id'];
        $data['amount'] = $amount;
        $data['before_balance'] = $before_balance;
        $data['after_balance'] = $after_balance;
        $data['wallet_adjustment_status'] = $this->ssa_preserved;
        $data['is_processed'] = false;

        if (!$is_transaction_already_exists) {
            // save transaction record first
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            $is_transaction_already_exists = $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $data);

            if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (!$is_transaction_already_exists) {
                    $is_transaction_already_exists = $this->ssa_is_transaction_exists($this->previous_table, ['external_unique_id' => $this->request_params['external_unique_id']]);
                }
            }

            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        }

        // if saved successfully, process transaction.
        if ($is_transaction_already_exists) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'data has been saved, process transaction.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            $success = false;
            $after_balance = null;

            // retain wallet
            if ($amount == 0 || $save_transaction_record_only) {
                $data['amount'] = 0;
                $data['wallet_adjustment_status'] = $this->ssa_retained;
                $data['is_processed'] = true;
                $success = true;
            } else {
                // decrease wallet
                if ($adjustment_type == $this->ssa_decrease) {
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance, $allowed_negative_balance);

                    if ($success) {
                        $data['wallet_adjustment_status'] = $this->ssa_decreased;
                        $data['is_processed'] = true;
                    } else {
                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->ssa_get_remote_wallet_error_code())) {
                            $data['wallet_adjustment_status'] = $this->ssa_remote_wallet_decreased;
                            $success = $this->remoteWalletError($data);

                            if ($success) {
                                $after_balance = $this->afterBalance($after_balance) - $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = self::SYSTEM_ERROR_DECREASE_BALANCE;
                            $this->ssa_hint[self::SYSTEM_ERROR_DECREASE_BALANCE['code']] = self::SYSTEM_ERROR_DECREASE_BALANCE['message'];
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // increase wallet
                } elseif ($adjustment_type == $this->ssa_increase) {
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);

                    if ($success) {
                        $data['wallet_adjustment_status'] = $this->ssa_increased;
                        $data['is_processed'] = true;
                    } else {
                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->ssa_get_remote_wallet_error_code())) {
                            $data['wallet_adjustment_status'] = $this->ssa_remote_wallet_increased;
                            $success = $this->remoteWalletError($data);

                            if ($success) {
                                $after_balance = $this->afterBalance($after_balance) + $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = self::SYSTEM_ERROR_INCREASE_BALANCE;
                            $this->ssa_hint[self::SYSTEM_ERROR_INCREASE_BALANCE['code']] = self::SYSTEM_ERROR_INCREASE_BALANCE['message'];
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT;
                    $this->ssa_hint[self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['code']] = self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message'];

                    return false;
                }
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA;
            $this->ssa_hint[self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['code']] = self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message'];
            return false;
        }
    }

    protected function rebuildTransactionRequestData($data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $extra_info = [];

        $new_transaction_data = [
            // default
            'game_platform_id' => $this->game_platform_id,
            'token' => isset($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'language' => $this->language,
            'currency' => $this->currency,
            'api_method' => $this->api_method,
            'transaction_type' => $this->transaction_type,
            'transaction_id' => isset($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null,
            'round_id' => isset($this->request_params['round_id']) ? $this->request_params['round_id'] : null,
            'game_code' => isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
            'wallet_adjustment_status' => !empty($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : null,
            'amount' => !empty($this->request_params['amount']) ? $this->request_params['amount'] : 0,
            'before_balance' => !empty($data['before_balance']) ? $data['before_balance'] : 0,
            'after_balance' => !empty($data['after_balance']) ? $data['after_balance'] : 0,
            'status' => $this->setStatus(),
            'start_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),

            // addtional
            'provider' => $this->provider,
            'type' => isset($this->request_params['type']) ? $this->request_params['type'] : null,
            'bet_type' => isset($this->request_params['bet_type']) ? $this->request_params['bet_type'] : null,
            'promo_type' => isset($this->request_params['promo_type']) ? $this->request_params['promo_type'] : null,
            'bet_id' => isset($this->request_params['bet_id']) ? $this->request_params['bet_id'] : null,
            'subscription_id' => isset($this->request_params['subscription_id']) ? $this->request_params['subscription_id'] : null,
            'combination_id' => isset($this->request_params['combination_id']) ? $this->request_params['combination_id'] : null,
            'promo_transaction_id' => isset($this->request_params['promo_transaction_id']) ? $this->request_params['promo_transaction_id'] : null,
            'bet_time' => isset($this->request_params['bet_time']) ? $this->request_params['bet_time'] : null,
            'subscription_time' => isset($this->request_params['subscription_time']) ? $this->request_params['subscription_time'] : null,
            'combination_time' => isset($this->request_params['combination_time']) ? $this->request_params['combination_time'] : null,
            'draw_time' => isset($this->request_params['draw_time']) ? $this->request_params['draw_time'] : null,
            'draw_code' => isset($this->request_params['draw_code']) ? $this->request_params['draw_code'] : null,
            'draw' => isset($this->request_params['draw']) ? json_encode($this->request_params['draw']) : null,
            'odd_extra' => isset($this->request_params['odd_extra']) ? json_encode($this->request_params['odd_extra']) : null,
            'retrying' => isset($this->request_params['retrying']) ? $this->request_params['retrying'] : null,
            'is_mobile' => isset($this->request_params['is_mobile']) ? $this->request_params['is_mobile'] : null,
            'request_id' => isset($this->request_params['request_id']) ? $this->request_params['request_id'] : null,
            'response_id' => $this->response_id,

            // default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => !empty($this->ssa_request_params) && is_array($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
            'response' => null,
            'extra_info' => !empty($extra_info) && is_array($extra_info) ? json_encode($extra_info) : null,
            'bet_amount' => isset($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : 0,
            'win_amount' => isset($this->extra_params['win_amount']) ? $this->extra_params['win_amount'] : 0,
            'result_amount' => isset($this->extra_params['result_amount']) ? $this->extra_params['result_amount'] : 0,
            'flag_of_updated_result' => isset($this->extra_params['flag_of_updated_result']) ? $this->extra_params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
            'is_processed' => isset($data['is_processed']) ? $data['is_processed'] : 0,
            'external_unique_id' => isset($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
            'seamless_service_unique_id' => $this->seamless_service_unique_id,
            'external_game_id' => isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
            'external_transaction_id' => isset($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null,
        ];

        $new_transaction_data['md5_sum'] = md5(json_encode($new_transaction_data));

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        return $new_transaction_data;
    }

    protected function saveTransactionRequestData($query_type, $data) {
        $transaction_data = $this->rebuildTransactionRequestData($data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $transaction_data, 'external_unique_id', $this->request_params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    protected function setStatus() {
        switch ($this->api_method) {
            case self::API_METHOD_TRANSACTION_BET_PAYIN:
            case self::API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN:
            case self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN:
            case self::API_METHOD_TRANSACTION_BET_MULTI_PAYIN:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::API_METHOD_TRANSACTION_BET_PAYOUT:
            case self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT:
            case self::API_METHOD_TRANSACTION_PROMO_PAYOUT:
                if ((!empty($this->request_params['bet_type']) && $this->request_params['bet_type'] == 'return') ||
                    (!empty($this->request_params['type']) && $this->request_params['type'] == 'return')) {
                    $result = Game_logs::STATUS_REFUND;
                } else {
                    $result = Game_logs::STATUS_SETTLED;
                }
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    protected function finalizeTransactionData() {
        if ($this->is_transfer_type) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            if (!empty($this->saved_multiple_transactions) && is_array($this->saved_multiple_transactions)) {
                foreach ($this->saved_multiple_transactions as $data) {
                    $external_unique_id = isset($data['external_unique_id']) ? $data['external_unique_id'] : null;
                    $wallet_adjustment_status = isset($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : $this->ssa_preserved;
                    $amount = isset($data['amount']) ? $data['amount'] : 0;
                    $before_balance = isset($data['before_balance']) ? $data['before_balance'] : 0;
                    $after_balance = isset($data['after_balance']) ? $data['after_balance'] : 0;
                    $is_processed = isset($data['is_processed']) ? $data['is_processed'] : false;
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

                    if (!empty($external_unique_id)) {
                        $data = [
                            'wallet_adjustment_status' => $wallet_adjustment_status,
                            'amount' => $amount,
                            'before_balance' => $before_balance,
                            'after_balance' => $after_balance,
                            'response' => $operator_response,
                            'is_processed' => $is_processed,
                            'response_result_id' => $this->response_result_id,
                        ];

                        $is_updated = $this->ssa_update_transaction_with_result($this->transaction_table, $data, 'external_unique_id', $external_unique_id);

                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (!$is_updated) {
                                $is_updated = $this->ssa_update_transaction_with_result($this->previous_table, $data, 'external_unique_id', $external_unique_id);
                            }
                        }
                    }
                }
            }

            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        }
    }

    protected function saveServiceLogs() {
        if (!empty($this->game_seamless_service_logs_table) && $this->save_game_seamless_service_logs) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::RESPONSE_SUCCESS['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::RESPONSE_SUCCESS['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

            $extra_info = [];

            $md5_data = $data = [
                'game_platform_id' => $this->game_platform_id,
                'token' => isset($this->request_params['token']) ? $this->request_params['token'] : null,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                'api_method' => $this->api_method,
                'transaction_type' => $this->transaction_type,
                'round_id' => !empty($this->request_params['round_id']) ? $this->request_params['round_id'] : null,
                'game_code' => !empty($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
                'status_code' => isset($http_response['code']) ? $http_response['code'] : null,
                'status_text' => isset($http_response['text']) ? $http_response['text'] : null,
                'response_code' => $code,
                'response_message' => $message,
                'flag' => $flag,
                'request' => !empty($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
                'response' => $operator_response,
                'extra_info' => json_encode($extra_info),
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
                'external_unique_id' => !empty($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
            ];

            unset($md5_data['response'], $md5_data['extra_info']);

            $data['md5_sum'] = md5(json_encode($md5_data));
            $data['elapsed_time'] = $this->utils->getCostMs();
            $data['response_result_id'] = $this->response_result_id;
            $data['transaction_id'] = !empty($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null;
            $data['call_count'] = 1;

            // additional
            $data['bet_id'] = !empty($this->request_params['bet_id']) ? $this->request_params['bet_id'] : null;
            $data['subscription_id'] = !empty($this->request_params['subscription_id']) ? $this->request_params['subscription_id'] : null;
            $data['combination_id'] = !empty($this->request_params['combination_id']) ? $this->request_params['combination_id'] : null;
            $data['promo_transaction_id'] = !empty($this->request_params['promo_transaction_id']) ? $this->request_params['promo_transaction_id'] : null;
            $data['request_id'] = !empty($this->request_params['request_id']) ? $this->request_params['request_id'] : null;
            $data['response_id'] = $this->response_id;

            $is_md5_sum_logs_exist = $this->ssa_is_md5_sum_logs_exist($this->game_seamless_service_logs_table, $data['md5_sum']);

            if (!$is_md5_sum_logs_exist) {
                $this->ssa_save_failed_request($this->game_seamless_service_logs_table, $this->ssa_insert, $data);
            } else {
                $call_count = $this->ssa_get_logs_call_count($this->game_seamless_service_logs_table, $data['md5_sum']);
                $this->ssa_save_failed_request($this->game_seamless_service_logs_table, $this->ssa_update, ['call_count' => $call_count += $data['call_count']], 'md5_sum', $data['md5_sum']);
            }
        }
    }

    protected function rebuildOperatorResponse() {
        if ($this->api_method == self::API_METHOD_TRANSACTION_BET_MULTI_PAYIN &&
            $this->ssa_operator_response['code'] == self::RESPONSE_INSUFFICIENT_BALANCE['code']) {
            $this->ssa_http_response_status_code = 200;
        }

        $operator_response['root'] = [
            'method' => $this->api_method,
            'token' => !empty($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null,
        ];

        if ($this->ssa_http_response_status_code == 200) {
            $operator_response['root']['success'] = 1;
            $operator_response['root']['error_code'] = self::RESPONSE_SUCCESS['code'];
            $operator_response['root']['error_text'] = '';

            if ($this->api_method == self::API_METHOD_GET_ACCOUNT_DETAILS) {
                $operator_response['root']['params'] = [
                    'user_id' => $this->player_details['game_username'],
                    'username' => $this->player_details['game_username'],
                    'currency' => $this->currency,
                    'info' => '-',
                ];
            }

            if ($this->api_method == self::API_METHOD_REQUEST_NEW_TOKEN) {
                $operator_response['root']['params'] = [
                    'new_token' => !empty($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null,
                ];
            }

            if ($this->api_method == self::API_METHOD_GET_BALANCE) {
                $operator_response['root']['params'] = [
                    'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                ];
            }

            if ($this->api_method == self::API_METHOD_TRANSACTION_BET_PAYIN ||
                $this->api_method == self::API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN ||
                $this->api_method == self::API_METHOD_TRANSACTION_BET_PAYOUT ||
                $this->api_method == self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN ||
                $this->api_method == self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT ||
                $this->api_method == self::API_METHOD_TRANSACTION_PROMO_PAYOUT
                ) {
                $operator_response['root']['params'] = [
                    'balance_after' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    'already_processed' => $this->transaction_already_exists ? 1 : 0,
                ];
            }

            if ($this->api_method == self::API_METHOD_TRANSACTION_BET_MULTI_PAYIN) {
                $operator_response['root']['params'] = [
                    'balance_after' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    'bet' => $this->additional_operator_response,
                ];
            }
        } else {
            $operator_response['root']['success'] = 0;
            $operator_response['root']['error_code'] = $this->ssa_operator_response['code'];
            $operator_response['root']['error_text'] = $this->ssa_operator_response['message'];
        }

        $operator_response['root']['response_id'] = $this->response_id;
        $operator_response['root']['time'] = $this->time;
        $operator_response['root']['signature'] = $this->generateSignature($this->response_id, $this->secret_key);

        // for showHint
        if (!empty($this->action)) {
            if (in_array($this->action, self::ACTIONS)) {
                switch ($this->action) {
                    case self::ACTION_SHOW_HINT:
                        if ($this->show_hint) {
                            if (!empty($this->ssa_hint)) {
                                $operator_response['root']['hint_check'] = $this->ssa_hint;
                            }
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        $this->rebuilded_operator_response = $operator_response;

        return $operator_response;
    }

    protected function response() {
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $operator_response = $this->rebuildOperatorResponse();

        if ($this->save_data) {
            $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id);

            $this->finalizeTransactionData();
            $this->saveServiceLogs();
        }

        // All responses should be with HTTP status 200 OK
        $this->ssa_http_response_status_code = 200;

        return $this->ssa_response_result([
            'response' => $operator_response,
            'add_origin' => true,
            'origin' => '*',
            'http_status_code' => $this->ssa_http_response_status_code,
            'http_status_text' => '',
            'content_type' => $this->content_type,
        ]);
    }

    protected function generateSignature($data, $secret_key) {
        return hash_hmac('sha256', $data, $secret_key);
    }

    protected function validateSignature($data, $secret_key, $request_signature) {
        $generated_signature = $this->generateSignature($data, $secret_key);

        if ($generated_signature == $request_signature) {
            return true;
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_WRONG_SIGNATURE;
            $this->ssa_hint['correct_signature'] = $this->generateSignature($this->ssa_request_params['request_id'], $this->secret_key);
            return false;
        }
    }

    protected function validateTime($request_time) {
        $time = $this->time - $request_time;

        if ($time <= 60) {
            return true;
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_REQUEST_EXPIRED;
            $this->ssa_hint['current_timestamp'] = $this->time;
            return false;
        }
    }

    public function get_token() {
        if (!empty($this->ssa_request_params['player_username'])) {
            if (in_array($this->ssa_request_params['player_username'], $this->get_token_test_accounts)) {
                $token = $this->ssa_get_player_common_token_by_player_username($this->ssa_request_params['player_username'], $this->token_timeout_seconds);
                if ($this->initializePlayer(false, $this->ssa_subject_type_token, $token, $this->game_platform_id)) {
                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);

                    echo "Token: {$this->player_details['token']}";
                } else {
                    echo 'invalid token';
                }
            } else {
                echo 'not allowed';
            }
            

        } else {
            echo 'required player_username';
        }
    }

    protected function ping() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if (!empty($this->ssa_request_params['token']) && $this->ssa_request_params['token'] != '-') {
                            if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            }
                        } else {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function get_account_details() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);

                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function refresh_token() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);

                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function request_new_token() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id)) {
                            $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);

                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function get_balance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);

                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                                $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true, true);

                                if (isset($get_player_wallet['success']) && $get_player_wallet['success'] && isset($get_player_wallet['balance'])) {
                                    $this->player_balance = $get_player_wallet['balance'];
                                    return true;
                                } else {
                                    return false;
                                }
                            });

                            if ($success) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            } else {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR;
                            }
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function transaction_bet_payin() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                                $success = $this->processTransactionBetPayinRequest($this->ssa_request_params['params']);

                                if ($success) {
                                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                }
                            }
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionBetPayinRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'bet_id' => ['required'],
            'transaction_id' => ['required'],
            'retrying' => ['optional'],
            'bet' => ['optional'],
            'odd' => ['optional'],
            'bet_time' => ['optional'],
            'game' => ['optional'],
            'draw_code' => ['optional'],
            'draw_time' => ['optional'],
            'draw' => ['optional'],
            'odd_extra' => ['optional'],
            'is_mobile' => ['optional'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            if (is_array($params['game']) && isset($params['game']['id'])) {
                $params['game_code'] = $params['game']['id'];
            } else {
                $params['game_code'] = $params['game'];
            }

            if (is_array($params['draw']) && isset($params['draw']['code'])) {
                $params['draw_code'] = $params['draw']['code'];
            }

            if (is_array($params['draw']) && isset($params['draw']['time'])) {
                $params['draw_time'] = $params['draw']['time'];
            }

            $this->rebuildRequestParams($params);

            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                    'transaction_id' => $this->request_params['transaction_id'],
                ]);

                if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                    if (empty($get_transaction)) {
                        $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                            'transaction_id' => $this->request_params['transaction_id'],
                        ]);
                    }
                }

                if (!empty($get_transaction)) {
                    $transaction_already_exists = true;

                    // check if already save but not adjusted
                    if ($get_transaction['is_processed']) {
                        $is_processed = true;
                    } else {
                        $save_only = true;
                    }
                } else {
                    $transaction_already_exists = false;
                }

                if (!$is_processed) {
                    $success = $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
                
                    if ($success) {
                        $this->ssa_http_response_status_code = 200;
                        $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                    }
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                    $this->transaction_already_exists = $success = true;
                    $this->player_balance = $get_transaction['after_balance'];
                    $this->response_id = $get_transaction['response_id'];
                }

                return $success;
            });
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }

    protected function transaction_bet_subscription_payin() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                                $success = $this->processTransactionBetSubscriptionPayinRequest($this->ssa_request_params['params']);

                                if ($success) {
                                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                }
                            }
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionBetSubscriptionPayinRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'subscription_id' => ['required'],
            'subscription_time' => ['required'],
            'odd' => ['required'],
            'game' => ['optional'],
            'bet' => ['required'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance, $params) {
                    foreach ($params['bet'] as $bet) {
                        $rule_sets = [
                            'bet_id' => ['required'],
                            'transaction_id' => ['required'],
                            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                            'draw' => ['required'],
                        ];

                        if ($this->ssa_validate_request_params($bet, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                            if (is_array($params['game']) && isset($params['game']['id'])) {
                                $params['game_code'] = $params['game']['id'];
                            } else {
                                $params['game_code'] = $params['game'];
                            }

                            $this->rebuildRequestParams(array_merge($params, $bet));

                            $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                'transaction_id' => $this->request_params['transaction_id'],
                                'subscription_id' => $this->request_params['subscription_id'],
                            ]);

                            if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                if (empty($get_transaction)) {
                                    $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                        'transaction_id' => $this->request_params['transaction_id'],
                                        'subscription_id' => $this->request_params['subscription_id'],
                                    ]);
                                }
                            }

                            if (!empty($get_transaction)) {
                                $transaction_already_exists = true;
    
                                // check if already save but not adjusted
                                if ($get_transaction['is_processed']) {
                                    $is_processed = true;
                                } else {
                                    $save_only = true;
                                }
                            } else {
                                $transaction_already_exists = false;
                            }

                            if (!$is_processed) {
                                $balance = $this->ssa_operate_amount(abs($this->player_balance), $this->precision, $this->conversion, $this->arithmetic_name);

                                if ($this->isInsufficientBalance($balance, $params['amount'])) {
                                    return false;
                                }
    
                                $success = $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
                            
                                if ($success) {
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                } else {
                                    break;
                                }
                            } else {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->response_id = $get_transaction['response_id'];
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                            $success = false;
                            break;
                        }
                    } // end of foreach

                return $success;
            });
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }

    protected function transaction_bet_payout() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        $success = $this->processTransactionBetPayoutRequest($this->ssa_request_params['params']);

                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionBetPayoutRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'player_id' => ['required'],
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'bet_id' => ['required'],
            'transaction_id' => ['required'],
            'retrying' => ['optional'],
            'bet_type' => ['required'],
            'game_id' => ['required'],
            'bet_option' => ['optional'],
            'final_odd' => ['optional'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            $params['game_code'] = $params['game_id'];

            $this->rebuildRequestParams($params);

            if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                    $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                        'transaction_id' => $this->request_params['transaction_id'],
                    ]);

                    if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        if (empty($get_transaction)) {
                            $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                'transaction_id' => $this->request_params['transaction_id'],
                            ]);
                        }
                    }

                    if (!empty($get_transaction)) {
                        $transaction_already_exists = true;

                        // check if already save but not adjusted
                        if ($get_transaction['is_processed']) {
                            $is_processed = true;
                        } else {
                            $save_only = true;
                        }
                    } else {
                        $transaction_already_exists = false;
                    }

                    if (!$is_processed) {
                        // check if bet exists
                        $bet_transaction = $this->ssa_get_transaction($this->transaction_table, [
                            'bet_id' => $this->request_params['bet_id'],
                            'is_processed' => true,
                        ]);

                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (empty($bet_transaction)) {
                                $bet_transaction = $this->ssa_get_transaction($this->previous_table, [
                                    'bet_id' => $this->request_params['bet_id'],
                                    'is_processed' => true,
                                ]);
                            }
                        }

                        if (!empty($bet_transaction)) {
                            if ($bet_transaction['transaction_id'] != $this->request_params['transaction_id']) {
                                $payout_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                    'api_method' => self::API_METHOD_TRANSACTION_BET_PAYOUT,
                                    'bet_id' => $this->request_params['bet_id'],
                                    'is_processed' => true,
                                ]);

                                if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                    if (empty($payout_transaction)) {
                                        $payout_transaction = $this->ssa_get_transaction($this->previous_table, [
                                            'api_method' => self::API_METHOD_TRANSACTION_BET_PAYOUT,
                                            'bet_id' => $this->request_params['bet_id'],
                                            'is_processed' => true,
                                        ]);
                                    }
                                }

                                if (!$payout_transaction) {
                                    $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
    
                                    if ($success) {
                                        $this->ssa_http_response_status_code = 200;
                                        $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                    }
                                } else {
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                    $this->transaction_already_exists = $success = true;
                                    $this->player_balance = $payout_transaction['after_balance'];
                                    $this->response_id = $payout_transaction['response_id'];
                                }
                            } else {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $bet_transaction['after_balance'];
                                $this->response_id = $bet_transaction['response_id'];
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = self::RESPONSE_BET_TRANSACTION_NOT_EXIST;
                            $success = false;
                        }
                    } else {
                        $this->ssa_http_response_status_code = 200;
                        $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        $this->transaction_already_exists = $success = true;
                        $this->player_balance = $get_transaction['after_balance'];
                        $this->response_id = $get_transaction['response_id'];
                    }

                    return $success;
                });
            }
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }

    protected function transaction_bet_combination_payin() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                                $success = $this->processTransactionBetCombinationPayinRequest($this->ssa_request_params['params']);

                                if ($success) {
                                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                }
                            }
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionBetCombinationPayinRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'combination_id' => ['required'],
            'combination_time' => ['required'],
            'bet' => ['required'],
            'is_mobile' => ['optional'],
            'odd_value' => ['optional'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance, $params) {
                foreach ($params['bet'] as $bet) {
                    $rule_sets = [
                        'bet_id' => ['required'],
                        'transaction_id' => ['required'],
                        'draw' => ['required'],
                        'game' => ['required'],
                    ];
    
                    if ($this->ssa_validate_request_params($bet, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                        if (is_array($bet['game']) && isset($bet['game']['id'])) {
                            $params['game_code'] = $bet['game']['id'];
                        } else {
                            $params['game_code'] = $bet['game'];
                        }

                        $this->rebuildRequestParams(array_merge($params, $bet));

                        $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                            'transaction_id' => $this->request_params['transaction_id'],
                            'combination_id' => $params['combination_id'],
                        ]);
    
                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (empty($get_transaction)) {
                                $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                    'transaction_id' => $this->request_params['transaction_id'],
                                    'combination_id' => $params['combination_id'],
                                ]);
                            }
                        }

                        if (!empty($get_transaction)) {
                            $transaction_already_exists = true;

                            // check if already save but not adjusted
                            if ($get_transaction['is_processed']) {
                                $is_processed = true;
                            } else {
                                $save_only = true;
                            }
                        } else {
                            $transaction_already_exists = false;
                        }

                        if (!$is_processed) {
                            $success = $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                            if ($success) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                // set $save_only to true after process first transaction to skip deduction of the following transactions
                                $save_only = true;
                            } else {
                                break;
                            }
                        } else {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            $this->transaction_already_exists = $success = true;
                            $this->player_balance = $get_transaction['after_balance'];
                            $this->response_id = $get_transaction['response_id'];
                            $success = false;
                            break;
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                        $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                        $success = false;
                        break;
                    }
                } // end of foreach

                return $success;
            });
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }

    protected function transaction_bet_combination_payout() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        $success = $this->processTransactionBetCombinationPayoutRequest($this->ssa_request_params['params']);

                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionBetCombinationPayoutRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'player_id' => ['required'],
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'type' => ['required'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'combination_id' => ['required'],
            'bet' => ['required'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $params['player_id'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance, $params) {
                    foreach ($params['bet'] as $bet) {
                        $rule_sets = [
                            'bet_id' => ['required'],
                            'game_id' => ['required'],
                            'transaction_id' => ['required'],
                            'type' => ['required'],
                            'bet_option' => ['optional'],
                            'final_odd' => ['optional'],
                        ];

                        if ($this->ssa_validate_request_params($bet, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                            $params['game_code'] = $bet['game_id'];

                            $this->rebuildRequestParams(array_merge($params, $bet));

                            $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                'transaction_id' => $this->request_params['transaction_id'],
                                'combination_id' => $this->request_params['combination_id'],
                            ]);

                            if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                if (empty($get_transaction)) {
                                    $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                        'transaction_id' => $this->request_params['transaction_id'],
                                        'combination_id' => $this->request_params['combination_id'],
                                    ]);
                                }
                            }

                            if (!empty($get_transaction)) {
                                $transaction_already_exists = true;

                                // check if already save but not adjusted
                                if ($get_transaction['is_processed']) {
                                    $is_processed = true;
                                } else {
                                    $save_only = true;
                                }
                            } else {
                                $transaction_already_exists = false;
                            }

                            if (!$is_processed) {
                                $bet_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                    'bet_id' => $this->request_params['bet_id'],
                                    'combination_id' => $this->request_params['combination_id'],
                                ]);
    
                                if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                    if (empty($bet_transaction)) {
                                        $bet_transaction = $this->ssa_get_transaction($this->previous_table, [
                                            'bet_id' => $this->request_params['bet_id'],
                                            'combination_id' => $this->request_params['combination_id'],
                                        ]);
                                    }
                                }
    
                                if (!empty($bet_transaction)) {
                                    if ($bet_transaction['transaction_id'] != $this->request_params['transaction_id']) {
                                        $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
            
                                        if ($success) {
                                            $this->ssa_http_response_status_code = 200;
                                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                            // set $save_only to true after process first transaction to skip deduction of the following transactions
                                            $save_only = true;
                                        } else {
                                            break;
                                        }
                                    } else {
                                        $this->ssa_http_response_status_code = 200;
                                        $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                        $this->transaction_already_exists = $success = true;
                                        $this->player_balance = $bet_transaction['after_balance'];
                                        $this->response_id = $bet_transaction['response_id'];
                                        $success = false;
                                        break;
                                    }
                                } else {
                                    $this->ssa_http_response_status_code = 400;
                                    $this->ssa_operator_response = self::RESPONSE_BET_TRANSACTION_NOT_EXIST;
                                    $success = false;
                                    break;
                                }
                            } else {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->response_id = $get_transaction['response_id'];
                                $success = false;
                                break;
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                            $success = false;
                            break;
                        }
                    } // end of foreach

                    return $success;
                });
            }
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }

    protected function transaction_promo_payout() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        $success = $this->processTransactionPromoPayoutRequest($this->ssa_request_params['params']);

                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionPromoPayoutRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'player_id' => ['required'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'promo_transaction_id' => ['required'],
            'bet_id' => ['required'],
            'game_id' => ['required'],
            'promo_type' => ['required'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $params['player_id'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                $params['game_code'] = $params['game_id'];

                $this->rebuildRequestParams($params);

                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                    $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                        'promo_transaction_id' => $this->request_params['promo_transaction_id'],
                    ]);
    
                    if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                        if (empty($get_transaction)) {
                            $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                'promo_transaction_id' => $this->request_params['promo_transaction_id'],
                            ]);
                        }
                    }

                    if (!empty($get_transaction)) {
                        $transaction_already_exists = true;

                        // check if already save but not adjusted
                        if ($get_transaction['is_processed']) {
                            $is_processed = true;
                        } else {
                            $save_only = true;
                        }
                    } else {
                        $transaction_already_exists = false;
                    }

                    if (!$is_processed) {
                        // check if bet exists
                        $bet_transaction = $this->ssa_get_transaction($this->transaction_table, [
                            'bet_id' => $this->request_params['bet_id'],
                            'is_processed' => true,
                        ]);
    
                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (empty($bet_transaction)) {
                                $bet_transaction = $this->ssa_get_transaction($this->previous_table, [
                                    'bet_id' => $this->request_params['bet_id'],
                                    'is_processed' => true,
                                ]);
                            }
                        }

                        if (!empty($bet_transaction)) {
                            // check if promo exists
                            $promo_payout_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                'api_method' => self::API_METHOD_TRANSACTION_PROMO_PAYOUT,
                                'bet_id' => $this->request_params['bet_id'],
                                'is_processed' => true,
                            ]);

                            if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                                if (empty($promo_payout_transaction)) {
                                    $promo_payout_transaction = $this->ssa_get_transaction($this->previous_table, [
                                        'api_method' => self::API_METHOD_TRANSACTION_PROMO_PAYOUT,
                                        'bet_id' => $this->request_params['bet_id'],
                                        'is_processed' => true,
                                    ]);
                                }
                            }

                            if (!empty($promo_payout_transaction)) {
                                if (isset($promo_payout_transaction['promo_type']) && $promo_payout_transaction['promo_type'] != $this->request_params['promo_type']) {
                                    $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
    
                                    if ($success) {
                                        $this->ssa_http_response_status_code = 200;
                                        $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                    }
                                } else {
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                    $this->transaction_already_exists = $success = true;
                                    $this->player_balance = $promo_payout_transaction['after_balance'];
                                    $this->response_id = $promo_payout_transaction['response_id'];
                                }
                            } else {
                                $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                if ($success) {
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                }
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = self::RESPONSE_BET_TRANSACTION_NOT_EXIST;
                            $success = false;
                        }
                    } else {
                        $this->ssa_http_response_status_code = 200;
                        $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        $this->transaction_already_exists = $success = true;
                        $this->player_balance = $get_transaction['after_balance'];
                        $this->response_id = $get_transaction['response_id'];
                    }

                    return $success;
                });
            }
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }

    protected function transaction_bet_multi_payin() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'method' => ['required'],
                'token' => ['required'],
                'request_id' => ['required'],
                'time' => ['required'],
                'signature' => ['required'],
                'params' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->validateSignature($this->ssa_request_params['request_id'], $this->secret_key, $this->ssa_request_params['signature'])) {
                    if ($this->validateTime($this->ssa_request_params['time'])) {
                        if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id, $this->force_refresh_token_timeout)) {
                            if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                                $success = $this->processTransactionBetMultiPayinRequest($this->ssa_request_params['params']);

                                if ($success) {
                                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token'], $this->token_timeout_seconds);
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                }
                            }
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        return $this->response();
    }

    protected function processTransactionBetMultiPayinRequest($params) {
        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $rule_sets = [
            'currency' => ['required', "expected_value:{$this->currency}"],
            'is_mobile' => ['optional'],
            'bet' => ['required'],
        ];

        if ($this->ssa_validate_request_params($params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            $rule_sets = [
                'bet_id' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'transaction_id' => ['required'],
                'draw' => ['optional'],
                'game' => ['required'],
                'odd' => ['optional'],
            ];

            if (!$this->ssa_is_multidimensional_array($params['bet'])) {
                $bet = $params['bet'];

                if ($this->ssa_validate_request_params($bet, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                    if (is_array($bet['game']) && isset($bet['game']['id'])) {
                        $params['game_code'] = $bet['game']['id'];
                    } else {
                        $params['game_code'] = $bet['game'];
                    }

                    if (is_array($bet['draw']) && isset($bet['draw']['code'])) {
                        $params['draw_code'] = $bet['draw']['code'];
                    } else {
                        $params['draw_code'] = $bet['draw_code'];
                    }

                    if (is_array($bet['draw']) && isset($bet['draw']['time'])) {
                        $params['draw_time'] = $bet['draw']['time'];
                    } else {
                        $params['draw_time'] = $bet['draw_time'];
                    }

                    $this->rebuildRequestParams(array_merge($params, $bet));

                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                        $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                            'transaction_id' => $this->request_params['transaction_id'],
                        ]);
    
                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (empty($get_transaction)) {
                                $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                    'transaction_id' => $this->request_params['transaction_id'],
                                ]);
                            }
                        }

                        if (!empty($get_transaction)) {
                            $transaction_already_exists = true;

                            // check if already save but not adjusted
                            if ($get_transaction['is_processed']) {
                                $is_processed = true;
                            } else {
                                $save_only = true;
                            }
                        } else {
                            $transaction_already_exists = false;
                        }

                        if (!$is_processed) {
                            $success = $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
    
                            if ($success) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            }
                        } else {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            $this->transaction_already_exists = $success = true;
                            $this->player_balance = $get_transaction['after_balance'];
                            $this->response_id = $get_transaction['response_id'];
                        }

                        return $success;
                    });
                } else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                    $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                }

                $response = [
                    'transaction_id' => isset($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null,
                    'success' => $this->ssa_http_response_status_code == 200 ? 1 : 0,
                    'error_code' => $this->ssa_operator_response['code'],
                    'error_text' => $this->ssa_http_response_status_code == 200 ? '' : $this->ssa_operator_response['message'],
                ];

                array_push($this->additional_operator_response, $response);
            } else {
                foreach ($params['bet'] as $bet) {
                    if ($this->ssa_validate_request_params($bet, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                        if (is_array($bet['game']) && isset($bet['game']['id'])) {
                            $params['game_code'] = $bet['game']['id'];
                        } else {
                            $params['game_code'] = $bet['game'];
                        }

                        if (is_array($bet['draw']) && isset($bet['draw']['code'])) {
                            $params['draw_code'] = $bet['draw']['code'];
                        } else {
                            $params['draw_code'] = $bet['draw_code'];
                        }

                        if (is_array($bet['draw']) && isset($bet['draw']['time'])) {
                            $params['draw_time'] = $bet['draw']['time'];
                        } else {
                            $params['draw_time'] = $bet['draw_time'];
                        }

                        $this->rebuildRequestParams(array_merge($params, $bet));

                        $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                            'transaction_id' => $this->request_params['transaction_id'],
                        ]);
    
                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (empty($get_transaction)) {
                                $get_transaction = $this->ssa_get_transaction($this->previous_table, [
                                    'transaction_id' => $this->request_params['transaction_id'],
                                ]);
                            }
                        }

                        if (!empty($get_transaction)) {
                            $transaction_already_exists = true;

                            // check if already save but not adjusted
                            if ($get_transaction['is_processed']) {
                                $is_processed = true;
                            } else {
                                $save_only = true;
                            }
                        } else {
                            $transaction_already_exists = false;
                        }

                        if (!$is_processed) {
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($transaction_already_exists, $save_only, $allowed_negative_balance) {
                                return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
                            });

                            if ($success) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            }
                        } else {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                            $this->transaction_already_exists = $success = true;
                            $this->player_balance = $get_transaction['after_balance'];
                            $this->response_id = $get_transaction['response_id'];
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                        $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                    }

                    $response = [
                        'transaction_id' => $this->request_params['transaction_id'],
                        'success' => $this->ssa_http_response_status_code == 200 ? 1 : 0,
                        'error_code' => $this->ssa_operator_response['code'],
                        'error_text' => $this->ssa_http_response_status_code == 200 ? '' : $this->ssa_operator_response['message'],
                    ];

                    array_push($this->additional_operator_response, $response);

                    $is_processed = false;
                } // end of foreach
            }
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
        }

        return $success;
    }
}