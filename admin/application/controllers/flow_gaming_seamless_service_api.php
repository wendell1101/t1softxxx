<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Flow_gaming_seamless_service_api extends BaseController {
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
    protected $token_timeout_seconds;
    protected $force_refresh_token_timeout;
    protected $get_token_test_accounts;
    protected $rebuild_operator_response = true;

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
        // 'code' => 500,
        'code' => 'SE_0',
        'message' => 'Internal Server Error',
    ];

    const SYSTEM_ERROR_EMPTY_GAME_API = [
        // 'code' => 500,
        'code' => 'SE_1',
        'message' => 'Empty game platform id',
    ];

    const SYSTEM_ERROR_INITIALIZE_GAME_API = [
        // 'code' => 500,
        'code' => 'SE_2',
        'message' => 'Failed to load game API',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND = [
        // 'code' => 500,
        'code' => 'SE_3',
        'message' => 'Function method not found',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN = [
        // 'code' => 500,
        'code' => 'SE_4',
        'message' => 'Function method forbidden',
    ];

    const SYSTEM_ERROR_DECREASE_BALANCE = [
        // 'code' => 500,
        'code' => 'SE_5',
        'message' => 'Error decrease balance',
    ];

    const SYSTEM_ERROR_INCREASE_BALANCE = [
        // 'code' => 500,
        'code' => 'SE_6',
        'message' => 'Error increase balance',
    ];

    const SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT = [
        // 'code' => 500,
        'code' => 'SE_7',
        'message' => 'Error wallet adjustment',
    ];

    const SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA = [
        // 'code' => 500,
        'code' => 'SE_8',
        'message' => 'Error save transaction request data',
    ];

    const SYSTEM_ERROR_SAVE_SERVICE_LOGS = [
        // 'code' => 500,
        'code' => 'SE_9',
        'message' => 'Error save service logs',
    ];

    const SYSTEM_ERROR_REBUILD_OPERATOR_RESPONSE = [
        // 'code' => 500,
        'code' => 'SE_10',
        'message' => 'Error rebuild operator response',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_DOUBLE_UNIQUEID = [
        // 'code' => 500,
        'code' => 'SE_11',
        'message' => 'Remote wallet double unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID = [
        // 'code' => 500,
        'code' => 'SE_12',
        'message' => 'Remote wallet invalid unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INSUFFICIENT_BALANCE = [
        // 'code' => 500,
        'code' => 'SE_13',
        'message' => 'Remote wallet insufficient balance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE = [
        // 'code' => 500,
        'code' => 'SE_14',
        'message' => 'Remote wallet maintenance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN = [
        // 'code' => 500,
        'code' => 'SE_15',
        'message' => 'Remote wallet unknown error',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_ADJUSTED_IN_LOCK_BALANCE = [
        // 'code' => 500,
        'code' => 'SE_16',
        'message' => 'Remote wallet adjusted in lock balance',
    ];

    const SYSTEM_ERROR_SWITCH_DB_FAILED = [
        // 'code' => 500,
        'code' => 'SE_17',
        'message' => 'Switch DB Failed',
    ];

    # CUSTOM RESPONSE CODES HERE
    const RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED = [
        // 'code' => 400,
        'code'=> 'RE_1',
        'message' => 'IP address is not allowed'
    ];

    const RESPONSE_ERROR_GAME_DISABLED = [
        // 'code' => 400,
        'code'=> 'RE_2',
        'message' => 'Game is disabled'
    ];

    const RESPONSE_ERROR_GAME_MAINTENANCE = [
        // 'code' => 400,
        'code'=> 'RE_3',
        'message' => 'Game is maintenance'
    ];

    const RESPONSE_ERROR_INVALID_PARAMETER = [
        // 'code' => 400,
        'code'=> 'RE_4',
        'message' => 'Invalid parameter'
    ];

    const RESPONSE_ERROR_PLAYER_BLOCKED = [
        'code'=> 'RE_5',
        // 'code' => 400,
        'message' => 'Player is blocked'
    ];

    # API RESPONSE
    const RESPONSE_SUCCESS = [
        // 'code' => 200,
        'code' => 'SUCCESS',
        'message' => 'Success',
    ];

    const RESPONSE_BAD_REQUEST = [
        // 'code' => 400,
        'code' => 'BAD_REQUEST',
        'message' => 'Bad Request or invalid message format',
    ];

    const RESPONSE_INVALID_TOKEN = [
        // 'code' => 401,
        'code' => 'INVALID_TOKEN',
        'message' => 'Invalid token or expired token',
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        // 'code' => 402,
        'code' => 'INSUFFICIENT_BALANCE',
        'message' => 'Balance not enough to execute transaction',
    ];

    const RESPONSE_TRANSACTION_ALREADY_PROCESSED = [
        // 'code' => 409,
        'code' => 'TRANSACTION_ALREADY_PROCESSED',
        'message' => 'Transaction has already been processed',
    ];

    # API METHODS HERE
    const API_METHOD_AUTH = 'auth';
    const API_METHOD_BALANCE = 'balance';
    const API_METHOD_TRANSACTION = 'transaction';
    const API_METHOD_END_ROUND = 'endround';

    const API_METHODS = [
        self::API_METHOD_AUTH,
        self::API_METHOD_BALANCE,
        self::API_METHOD_TRANSACTION,
        self::API_METHOD_END_ROUND,
    ];

    const CATEGORY_WAGER = 'wager';
    const CATEGORY_PAYOUT = 'payout';
    const CATEGORY_REFUND = 'refund';

    const CATEGORIES = [
        self::CATEGORY_WAGER,
        self::CATEGORY_PAYOUT,
        self::CATEGORY_REFUND,
    ];

    const SUB_CATEGORY_WAGER = 'wager';
    const SUB_CATEGORY_POOL = 'pool';

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_TRANSACTION,
        self::API_METHOD_END_ROUND,
    ];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'get_token';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN
    ];

    # ADDITIONAL PROPERTIES HERE
    protected $req_id;
    protected $country;

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->req_id = $this->utils->getGUIDv4();
    }

    public function index($game_platform_id, $api_method, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->api_method = $api_method;
        $this->action = $action;

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
            $this->token_timeout_seconds = $this->game_api->token_timeout_seconds;
            $this->force_refresh_token_timeout = $this->game_api->force_refresh_token_timeout;
            $this->get_token_test_accounts = $this->game_api->get_token_test_accounts;

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
            $this->previous_table = $this->game_api->ymt_get_previous_year_month_table();

            // additional
            $this->country = $this->game_api->country;
        } else {
            $this->ssa_set_response(500, self::SYSTEM_ERROR_INITIALIZE_GAME_API);
            $this->ssa_set_hint(self::SYSTEM_ERROR_INITIALIZE_GAME_API['code'], self::SYSTEM_ERROR_INITIALIZE_GAME_API['message']);
            return false;
        }

        if ($this->action == self::ACTION_GET_TOKEN) {
            return true;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $this->api_method)) {
            $this->ssa_set_response(404, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            return false;
        }

        if ($this->ssa_is_api_method_allowed($this->api_method, self::API_METHODS)) {
            $this->ssa_set_response(404, self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            return false;
        }

        return true;
    }

    protected function responseConfig() {
        $this->transfer_type_api_methods = self::TRANSFER_TYPE_API_METHODS;
        $this->content_type = $this->ssa_content_type_application_json;
        $this->content_type_json_api_methods = self::API_METHODS;
        $this->content_type_xml_api_methods = [];
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
        $this->request_params['token'] = isset($data['token']) ? $data['token'] : null;
        $this->request_params['game_username'] = isset($data['account_ext_ref']) ? strtolower($data['account_ext_ref']) : null;
        $this->request_params['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->request_params['transaction_id'] = isset($data['tx_id']) ? $data['tx_id'] : null;
        $this->request_params['round_id'] = isset($data['round_id']) ? $data['round_id'] : null;
        $this->request_params['game_code'] = isset($data['item_id']) ? $data['item_id'] : null;
        $this->request_params['amount'] = !empty($data['amount']) ? $data['amount'] : 0;

        $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
            $this->api_method,
            $this->request_params['transaction_id'],
        ]);

        $this->request_params['req_id'] = isset($data['req_id']) ? $data['req_id'] : null;
        $this->request_params['timestamp'] = isset($data['timestamp']) ? $data['timestamp'] : null;
        $this->request_params['account_id'] = isset($data['account_id']) ? $data['account_id'] : null;
        $this->request_params['category'] = isset($data['category']) ? strtolower($data['category']) : null;
        $this->request_params['sub_category'] = isset($data['sub_category']) ? strtolower($data['sub_category']) : null;
        $this->request_params['tx_round_id'] = isset($data['tx_round_id']) ? $data['tx_round_id'] : null;
        $this->request_params['refund_tx_id'] = isset($data['refund_tx_id']) ? $data['refund_tx_id'] : null;
        $this->request_params['pool_amount'] = isset($data['pool_amount']) ? $data['pool_amount'] : null;
        $this->request_params['revenue'] = isset($data['revenue']) ? $data['revenue'] : null;
        $this->request_params['ctx'] = isset($data['ctx']) ? $data['ctx'] : null;
        $this->request_params['campaign_id'] = isset($data['campaign_id']) ? $data['campaign_id'] : null;
        $this->request_params['campaign_ext_ref'] = isset($data['campaign_ext_ref']) ? $data['campaign_ext_ref'] : null;
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {

            if ($get_player_by == 'token') {
                $this->ssa_set_response(401, self::RESPONSE_INVALID_TOKEN);
                $this->ssa_set_hint('userid', self::RESPONSE_INVALID_TOKEN['message']);
            } else {
                $this->ssa_set_response(400, self::RESPONSE_BAD_REQUEST);
                $this->ssa_set_hint('userid', 'User not found');
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true) {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_set_response(400, self::RESPONSE_ERROR_PLAYER_BLOCKED);
            $this->ssa_set_hint('blocked', self::RESPONSE_ERROR_PLAYER_BLOCKED['message']);

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
            $this->ssa_set_response(401, self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED);
            $this->ssa_set_hint('ipaddress', $this->ssa_get_ip_address());

            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_GAME_DISABLED);

            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_GAME_MAINTENANCE);

            return false;
        }

        // additional

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_set_response(402, self::RESPONSE_INSUFFICIENT_BALANCE);
            $this->ssa_set_hint('amount', self::RESPONSE_INSUFFICIENT_BALANCE['message']);

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
            $this->ssa_set_response(500, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID);
            $this->ssa_set_hint(self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code'], self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_set_response(402, self::RESPONSE_INSUFFICIENT_BALANCE);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_set_response(500, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE);
            $this->ssa_set_hint(self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code'], self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        $this->ssa_set_response(500, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN);
        $this->ssa_set_hint(self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['code'], self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message']);
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
        $this->ssa_set_game_provider_round_id($this->request_params['round_id']);

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
            if ($save_transaction_record_only || $adjustment_type == $this->ssa_retain) {
                $data['amount'] = 0;
                $data['wallet_adjustment_status'] = $this->ssa_retained;
                $data['is_processed'] = true;
                $success = true;
            } else {
                // decrease wallet
                if ($adjustment_type == $this->ssa_decrease && $amount != 0) {
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
                            $this->ssa_set_response(500, self::SYSTEM_ERROR_DECREASE_BALANCE);
                            $this->ssa_set_hint(self::SYSTEM_ERROR_DECREASE_BALANCE['code'], self::SYSTEM_ERROR_DECREASE_BALANCE['message']);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // increase wallet
                #ssa_increase_player_wallet should be called on 0 amount
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
                            $this->ssa_set_response(500, self::SYSTEM_ERROR_INCREASE_BALANCE);
                            $this->ssa_set_hint(self::SYSTEM_ERROR_INCREASE_BALANCE['code'], self::SYSTEM_ERROR_INCREASE_BALANCE['message']);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_set_response(500, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT);
                    $this->ssa_set_hint(self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['code'], self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message']);

                    return false;
                }
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_set_response(500, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA);
            $this->ssa_set_hint(self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['code'], self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message']);

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
            'category' => isset($this->request_params['category']) ? $this->request_params['category'] : null,
            'sub_category' => isset($this->request_params['sub_category']) ? $this->request_params['sub_category'] : null,
            'account_id' => isset($this->request_params['account_id']) ? $this->request_params['account_id'] : null,
            'tx_round_id' => isset($this->request_params['tx_round_id']) ? $this->request_params['tx_round_id'] : null,
            'refund_tx_id' => isset($this->request_params['refund_tx_id']) ? $this->request_params['refund_tx_id'] : null,
            'pool_amount' => isset($this->request_params['pool_amount']) ? $this->request_params['pool_amount'] : null,
            'revenue' => isset($this->request_params['revenue']) ? $this->request_params['revenue'] : null,
            'campaign_id' => isset($this->request_params['campaign_id']) ? $this->request_params['campaign_id'] : null,
            'campaign_ext_ref' => isset($this->request_params['campaign_ext_ref']) ? $this->request_params['campaign_ext_ref'] : null,
            'processing_time' => isset($this->request_params['processing_time']) ? $this->request_params['processing_time'] : null,
            'timestamp' => isset($this->request_params['timestamp']) ? $this->request_params['timestamp'] : null,
            'req_id' => isset($this->request_params['req_id']) ? $this->request_params['req_id'] : null,

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
            case self::CATEGORY_WAGER:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::CATEGORY_PAYOUT:
            case self::API_METHOD_END_ROUND:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::CATEGORY_REFUND:
                $result = Game_logs::STATUS_REFUND;
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
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_get_operator_response());

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

                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $external_unique_id]);

                            if (!$is_exist) {
                                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, ['external_unique_id' => $external_unique_id]);
                            }

                            if ($is_exist) {
                                $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'external_unique_id', $external_unique_id);
                            }
                        } else {
                            $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'external_unique_id', $external_unique_id);
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
            $http_response = $this->ssa_get_http_response($this->ssa_get_http_response_status_code());
            $code = isset($this->ssa_get_operator_response(0)->code) ? $this->ssa_get_operator_response(0)->code : self::RESPONSE_SUCCESS['code'];
            $message = isset($this->ssa_get_operator_response(0)->message) ? $this->ssa_get_operator_response(0)->message : self::RESPONSE_SUCCESS['message'];
            $flag = $this->ssa_get_http_response_status_code() == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_get_operator_response());

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
            $data['category'] = !empty($this->request_params['category']) ? $this->request_params['category'] : null;
            $data['sub_category'] = !empty($this->request_params['sub_category']) ? $this->request_params['sub_category'] : null;
            $data['account_id'] = !empty($this->request_params['account_id']) ? $this->request_params['account_id'] : null;
            $data['tx_round_id'] = !empty($this->request_params['tx_round_id']) ? $this->request_params['tx_round_id'] : null;
            $data['refund_tx_id'] = !empty($this->request_params['refund_tx_id']) ? $this->request_params['refund_tx_id'] : null;
            $data['pool_amount'] = !empty($this->request_params['pool_amount']) ? $this->request_params['pool_amount'] : null;
            $data['revenue'] = !empty($this->request_params['revenue']) ? $this->request_params['revenue'] : null;
            $data['campaign_id'] = !empty($this->request_params['campaign_id']) ? $this->request_params['campaign_id'] : null;
            $data['campaign_ext_ref'] = !empty($this->request_params['campaign_ext_ref']) ? $this->request_params['campaign_ext_ref'] : null;
            $data['processing_time'] = !empty($this->request_params['processing_time']) ? $this->request_params['processing_time'] : null;
            $data['timestamp'] = time();
            $data['req_id'] = $this->req_id;

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
        if (!empty($this->ssa_request_params['token'])) {
            $token = $this->ssa_request_params['token'];
        } else {
            if (!empty($this->player_details['game_username']) || !empty($this->ssa_request_params['account_ext_ref'])) {
                if (!empty($this->ssa_request_params['account_ext_ref'])) {
                    $game_username = $this->ssa_request_params['account_ext_ref'];
                } else {
                    if (!empty($this->player_details['game_username'])) {
                        $game_username = $this->player_details['game_username'];
                    } else {
                        $game_username = null;
                    }
                }
    
                $token = $this->ssa_get_player_common_token_by_player_game_username($game_username, $this->game_platform_id, $this->token_timeout_seconds);
            } else {
                $token = null;
            }
        }

        $operator_response = [
            'req_id' => $this->utils->getGUIDv4(),
            'processing_time' => $this->utils->getCostMs(),
            'token' => $token,
        ];

        if ($this->ssa_get_http_response_status_code() == 200) {
            if ($this->api_method == self::API_METHOD_AUTH) {
                $operator_response['username'] = $this->player_details['game_username'];
                $operator_response['account_ext_ref'] = $this->player_details['game_username'];
                $operator_response['balance'] = $this->player_balance;
                $operator_response['country'] = $this->country;
                $operator_response['currency'] = $this->currency;
                $operator_response['languange'] = $this->language;
            }

            if ($this->api_method == self::API_METHOD_BALANCE ||
                $this->api_method == self::API_METHOD_END_ROUND)
            {
                $operator_response['balance'] = $this->player_balance;
            }

            if ($this->api_method == self::CATEGORY_WAGER ||
                $this->api_method == self::CATEGORY_PAYOUT ||
                $this->api_method == self::CATEGORY_REFUND)
            {
                $operator_response['balance'] = $this->player_balance;
                $operator_response['ext_tx_id'] = $this->external_unique_id;
            }

            $operator_response['timestamp'] = $this->ssa_format_dateTime('Y-m-d\TH:i:s');
        } else {
            $operator_response['err_code'] = $this->ssa_get_operator_response(0)->code;
            $operator_response['err_desc'] = $this->ssa_get_operator_response(0)->message;
        }

        // for showHint
        if (!empty($this->action)) {
            if (in_array($this->action, self::ACTIONS)) {
                switch ($this->action) {
                    case self::ACTION_SHOW_HINT:
                        if ($this->show_hint) {
                            if (!empty($this->ssa_hint)) {
                                $operator_response['hint_check'] = $this->ssa_hint;
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
        $http_response_status_code = $this->ssa_get_http_response_status_code();
        $flag = $http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $operator_response = $this->ssa_get_operator_response();

        if ($this->rebuild_operator_response) {
            $operator_response = $this->rebuildOperatorResponse();
        }

        if ($this->save_data) {
            $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id);

            $this->finalizeTransactionData();
            $this->saveServiceLogs();
        }

        return $this->ssa_response_result([
            'response' => $operator_response,
            'add_origin' => true,
            'origin' => '*',
            'http_status_code' => $http_response_status_code,
            'http_status_text' => '',
            'content_type' => $this->content_type,
        ]);
    }

    public function get_token() {
        $this->ssa_set_response(400, self::RESPONSE_BAD_REQUEST);
        $this->rebuild_operator_response = $this->save_data = $success = false;
        $response = [];

        if (!empty($this->ssa_request_params['player_username'])) {
            if (in_array($this->ssa_request_params['player_username'], $this->get_token_test_accounts)) {
                $token = $this->ssa_get_player_common_token_by_player_username($this->ssa_request_params['player_username']);

                if ($this->initializePlayer(false, $this->ssa_subject_type_token, $token, $this->game_platform_id)) {
                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token']);

                    $response = [
                        'token' => $this->player_details['token'],
                    ];

                    $success = true;
                } else {
                    $response = [
                        'message' => 'Invalid Player',
                    ];
                }
            } else {
                $response = [
                    'message' => 'Player not in the list',
                ];
            }
        } else {
            $response = [
                'message' => 'Required player_username',
            ];
        }

        if ($success) {
            $this->ssa_set_http_response_status_code(200);
        }

        $this->ssa_set_operator_response($response);

        return $this->response();
    }

    protected function __if_exist_req_id_rule_value() {
        $value = [
            'table_name' => $this->transaction_table,
            'where' => [
                'req_id' => isset($this->ssa_request_params['req_id']) ? $this->ssa_request_params['req_id'] : null,
            ],
            'http_response_status_code' => 409,
            'operator_response' => [
                'code' => self::RESPONSE_TRANSACTION_ALREADY_PROCESSED['code'],
                'message' => 'req_id already exist',
            ],
            'return' => false,
            'use_monthly_transactions_table' => $this->use_monthly_transactions_table,
            'force_check_previous_transactions_table' => $this->force_check_previous_transactions_table,
            'previous_table' => $this->previous_table,
        ];

        return $value;
    }

    protected function __if_exist_tx_id_rule_value() {
        $value = [
            'table_name' => $this->transaction_table,
            'where' => [
                'transaction_id' => isset($this->ssa_request_params['tx_id']) ? $this->ssa_request_params['tx_id'] : null,
            ],
            'http_response_status_code' => 409,
            'operator_response' => self::RESPONSE_TRANSACTION_ALREADY_PROCESSED,
            'return' => false,
            'use_monthly_transactions_table' => $this->use_monthly_transactions_table,
            'force_check_previous_transactions_table' => $this->force_check_previous_transactions_table,
            'previous_table' => $this->previous_table,
        ];

        return $value;
    }

    protected function getTransactionCategory($default_method = null) {
        $method = $default_method;
        $category = !empty($this->ssa_request_params['category']) ? $this->ssa_request_params['category'] : null;

        if (in_array($category, self::CATEGORIES)) {
            $method = $category;
        }

        return $method;
    }

    protected function getTransactionRequest($where = []) {
        if (empty($where)) {
            $where = [
                'transaction_id' => $this->request_params['transaction_id'],
            ];
        }

        $get_transaction = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transaction)) {
                $get_transaction = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $get_transaction;
    }

    protected function wagerTransaction($where = []) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'category' => self::CATEGORY_WAGER,
                'round_id' => $this->request_params['round_id'],
                'is_processed' => true,
            ];
        }

        $wager_transaction = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($wager_transaction)) {
                $wager_transaction = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $wager_transaction;
    }

    protected function payoutTransaction($where = []) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'category' => self::CATEGORY_PAYOUT,
                'round_id' => $this->request_params['round_id'],
                'is_processed' => true,
            ];
        }

        $payout_transaction = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($payout_transaction)) {
                $payout_transaction = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $payout_transaction;
    }

    protected function refundTransaction($where = []) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'category' => self::CATEGORY_REFUND,
                'round_id' => $this->request_params['round_id'],
                'is_processed' => true,
            ];
        }

        $refund_transaction = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($refund_transaction)) {
                $refund_transaction = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $refund_transaction;
    }

    protected function isWagerExists($where = []) {
        return !empty($this->wagerTransaction($where));
    }

    protected function isPayoutExists($where = []) {
        return !empty($this->payoutTransaction($where));
    }

    protected function isRefundExists($where = []) {
        return !empty($this->refundTransaction($where));
    }

    protected function transactionTypeAlreadyProcessed($transaction_type = '', $where = []) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'category' => $transaction_type,
                'round_id' => $this->request_params['round_id'],
                'is_processed' => true,
            ];
        }

        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }

    protected function auth() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'req_id' => ['required'],
                'timestamp' => ['required'],
                'token' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id)) {
                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function balance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'req_id' => ['required'],
                'timestamp' => ['required'],
                'token' => ['required'],
                'account_ext_ref' => ['required'],
                'account_id' => ['required'],
                'currency' => ['required', "expected_value:{$this->currency}"],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER)) {
                if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id)) {
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
                        $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                    } else {
                        $this->ssa_set_response(500, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error: get balance failed');
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function transaction() {
        $this->api_method = $this->getTransactionCategory(__FUNCTION__);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'req_id' => [
                'required',
                'if_exist' => $this->__if_exist_req_id_rule_value(),
            ],
            'timestamp' => ['required'],
            'token' => ['required'],
            'account_ext_ref' => ['required'],
            'account_id' => ['required'],
            'category' => [
                'required',
                'expected_value_in' => self::CATEGORIES,
            ],
            'sub_category' => ['optional'],
            'tx_id' => [
                'required',
                // 'if_exist' => $this->__if_exist_tx_id_rule_value(),
            ],
            'tx_round_id' => ['optional'],
            'refund_tx_id' => ['optional'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'pool_amount' => ['optional'],
            'item_id' => ['required'],
            'round_id' => ['required'],
            'ctx' => ['required'],
            'campaign_id' => ['optional'],
            'campaign_ext_ref' => ['optional'],
        ];

        if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
            $category = strtolower($this->ssa_request_params['category']);
            $this->$category();
        } else {
            $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            $this->ssa_set_response($this->ssa_http_response_status_code, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
            $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
        }

        return $this->response();
    }

    protected function wager() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;

        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $this->rebuildRequestParams($this->ssa_request_params);

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
                if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                        // check if transction exists
                        $get_transaction = $this->getTransactionRequest();
    
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
                            $this->ssa_set_game_provider_action_type('bet');
                            $this->ssa_set_game_provider_is_end_round(false);
                            $success = $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
                        
                            if ($success) {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                            }
                        } else {
                            /* $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_TRANSACTION_ALREADY_PROCESSED;
                            $this->transaction_already_exists = $success = true; */

                            $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED);
                            $this->transaction_already_exists = $success = false;
                            $this->external_unique_id = $get_transaction['external_unique_id'];
                        }

                        return $success;
                    });

                    if ($success) {
                        $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                    }
                }
            }
        }

        return $success;
    }

    protected function payout() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;

        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $this->rebuildRequestParams($this->ssa_request_params);

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                    // check if transction exists
                    $get_transaction = $this->getTransactionRequest();
    
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
                        if ($this->isWagerExists([
                            'player_id' => $this->player_details['player_id'],
                            'category' => self::CATEGORY_WAGER,
                            'transaction_id' => $this->request_params['tx_round_id'],
                            'round_id' => $this->request_params['round_id'],
                            'is_processed' => true,
                        ])) {
                            if (!$this->isRefundExists([
                                'player_id' => $this->player_details['player_id'],
                                'category' => self::CATEGORY_REFUND,
                                'refund_tx_id' => $this->request_params['tx_round_id'],
                                'round_id' => $this->request_params['round_id'],
                                'is_processed' => true,
                            ])) {
                                $this->ssa_set_game_provider_action_type('payout');
                                $this->ssa_set_game_provider_is_end_round(true);
                                $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                if ($success) {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                }
                            } else {
                                $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED, 'Already processed by refund');
                            }
                        } else {
                            $this->ssa_set_response(400, self::RESPONSE_BAD_REQUEST, 'Wager not exists');
                        }
                    } else {
                        $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED);
                        $this->transaction_already_exists = $success = false;
                        $this->external_unique_id = $get_transaction['external_unique_id'];
                    }

                    return $success;
                });

                if ($success) {
                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                }
            }
        }

        return $success;
    }

    protected function refund() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;

        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = false;
        $allowed_negative_balance = false;

        $this->rebuildRequestParams($this->ssa_request_params);

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'refund_tx_id' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                        // check if transction exists
                        $get_transaction = $this->getTransactionRequest();

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
                            if ($this->isWagerExists([
                                'player_id' => $this->player_details['player_id'],
                                'category' => self::CATEGORY_WAGER,
                                'transaction_id' => $this->request_params['refund_tx_id'],
                                'round_id' => $this->request_params['round_id'],
                                'is_processed' => true,
                            ])) {
                                if (!$this->transactionTypeAlreadyProcessed($this->api_method, [
                                    'player_id' => $this->player_details['player_id'],
                                    'category' => $this->api_method,
                                    'refund_tx_id' => $this->request_params['refund_tx_id'],
                                    'round_id' => $this->request_params['round_id'],
                                    'is_processed' => true,
                                ])) {
                                    if (!$this->isPayoutExists([
                                        'player_id' => $this->player_details['player_id'],
                                        'category' => self::CATEGORY_PAYOUT,
                                        'tx_round_id' => $this->request_params['refund_tx_id'],
                                        'round_id' => $this->request_params['round_id'],
                                        'is_processed' => true,
                                    ])) {
                                        if ($this->request_params['sub_category'] == self::SUB_CATEGORY_POOL) {
                                            $adjustment_type = $this->ssa_decrease;
                                        } else {
                                            $adjustment_type = $this->ssa_increase;
                                        }
                                        $this->ssa_set_game_provider_action_type('refund');
                                        $this->ssa_set_game_provider_is_end_round(true);
                                        $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
    
                                        if ($success) {
                                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                        }
                                    } else {
                                        $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED, 'Already processed by payout');
                                    }
                                } else {
                                    $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED);
                                    $this->transaction_already_exists = $success = false;
                                    $this->external_unique_id = $get_transaction['external_unique_id'];
                                }
                            } else {
                                $this->ssa_set_response(400, self::RESPONSE_BAD_REQUEST, 'Wager not exists');
                            }
                        } else {
                            $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED);
                            $this->transaction_already_exists = $success = false;
                            $this->external_unique_id = $get_transaction['external_unique_id'];
                        }

                        return $success;
                    });

                    if ($success) {
                        $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $success;
    }

    protected function endround() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = null;
        $this->ssa_set_response(500, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $success = false;
        $transaction_already_exists = false;
        $is_processed = false;
        $save_only = true;
        $allowed_negative_balance = false;

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'req_id' => ['required'],
                'timestamp' => ['required'],
                'token' => ['required'],
                'account_ext_ref' => ['required'],
                'account_id' => ['required'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'tx_id' => ['required'],
                'item_id' => ['required'],
                'round_id' => ['required'],
                'txs' => ['required'],
                'start_time' => ['required'],
                'round_stats' => ['required'],
                'revenue' => ['required'],
                'context' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_INVALID_PARAMETER, false)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    // check if transction exists
                    $get_transaction = $this->getTransactionRequest();

                    if (empty($get_transaction)) {
                        if ($this->isWagerExists()) {
                            $this->ssa_set_game_provider_action_type('payout');
                            $this->ssa_set_game_provider_is_end_round(true);
                            $success = $this->walletAdjustment($this->ssa_retain, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                            if ($success) {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                            }
                        } else {
                            $this->ssa_set_response(400, self::RESPONSE_BAD_REQUEST, 'Wager not exists');
                        }
                    } else {
                        $this->ssa_set_response(409, self::RESPONSE_TRANSACTION_ALREADY_PROCESSED);
                        $this->transaction_already_exists = $success = false;
                        $this->external_unique_id = $get_transaction['external_unique_id'];
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }
}