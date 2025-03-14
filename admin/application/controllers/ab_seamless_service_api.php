<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Ab_seamless_service_api extends BaseController
{
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
    protected $game_provider_action_type = null;
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
    protected $is_end_round = false;
    protected $suffix_for_username;

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

    const SYSTEM_ERROR_BAD_REQUEST = [
        // 'code' => 500,
        'code' => 'SE_18',
        'message' => 'Bad request',
    ];

    # CUSTOM RESPONSE CODES HERE
    const RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED = [
        // 'code' => 400,
        'code' => 'RE_1',
        'message' => 'IP address is not allowed'
    ];

    const RESPONSE_ERROR_GAME_DISABLED = [
        // 'code' => 400,
        'code' => 'RE_2',
        'message' => 'Game is disabled'
    ];

    const RESPONSE_ERROR_GAME_MAINTENANCE = [
        // 'code' => 400,
        'code' => 'RE_3',
        'message' => 'Game is maintenance'
    ];

    const RESPONSE_ERROR_INVALID_PARAMETER = [
        // 'code' => 400,
        'code' => 'RE_4',
        'message' => 'Invalid parameter'
    ];

    const RESPONSE_ERROR_PLAYER_BLOCKED = [
        'code' => 'RE_5',
        // 'code' => 400,
        'message' => 'Player is blocked'
    ];

    # API RESPONSE ------------------------------------------------------------------------- start here
    const RESPONSE_SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];

    const RESPONSE_INVALID_OPERATOR_ID = [
        'code' => 10000,
        'message' => 'Invalid Operator ID',
    ];

    const RESPONSE_INVALID_SIGNATURE = [
        'code' => 10001,
        'message' => 'Invalid Signature',
    ];

    const RESPONSE_PLAYER_NOT_EXIST = [
        'code' => 10003,
        'message' => 'Player account does not exist.',
    ];

    const RESPONSE_PLAYER_DISABLED = [
        'code' => 10005,
        'message' => 'Player account is disabled or not allowed to log in.',
    ];

    const RESPONSE_TRANSACTION_NOT_EXIST = [
        'code' => 10006,
        'message' => 'Transaction not existed',
    ];

    const RESPONSE_INVALID_STATUS = [
        'code' => 10007,
        'message' => 'Invalid status',
    ];

    const RESPONSE_PLAYER_IS_OFFLINE = [
        'code' => 10008,
        'message' => 'Player is offline / logged out.',
    ];

    const RESPONSE_PROHIBIT_TO_BET = [
        'code' => 10100,
        'message' => 'Prohibit to bet.',
    ];

    const RESPONSE_CREDIT_NOT_ENOUGH = [
        'code' => 10101,
        'message' => 'Player account has insufficient credit balance.',
    ];

    const RESPONSE_SYSTEM_UNDER_MAINTENANCE = [
        'code' => 10200,
        'message' => 'System is under maintenance.',
    ];

    const RESPONSE_INVALID_REQUEST_PARAMETER = [
        'code' => 40000,
        'message' => 'Invalid request parameter',
    ];

    const RESPONSE_SERVER_ERROR = [
        'code' => 50000,
        'message' => 'Server Error',
    ];

    # API METHODS HERE
    // default
    const API_METHOD_OPERATOR_ACTION = 'operatorAction';
    // From docs
    const API_METHOD_GET_BALANCE = 'GetBalance';
    const API_METHOD_TRANSFER = 'Transfer';
    const API_METHOD_CANCEL_TRANSFER = 'CancelTransfer';

    const API_METHODS = [
        self::API_METHOD_OPERATOR_ACTION,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_TRANSFER,
        self::API_METHOD_CANCEL_TRANSFER,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_TRANSFER,
        self::API_METHOD_CANCEL_TRANSFER,
    ];

    const TRANSFER_TYPE_METHOD_BET = 'bet';
    const TRANSFER_TYPE_METHOD_SETTLE = 'settle';
    const TRANSFER_TYPE_METHOD_MANUAL_SETTLE = 'manualSettle';
    const TRANSFER_TYPE_METHOD_TRANSFER_IN = 'transferIn';
    const TRANSFER_TYPE_METHOD_TRANSFER_OUT = 'transferOut';
    const TRANSFER_TYPE_METHOD_EVENT_SETTLE = 'eventSettle';

    const TRANSFER_TYPES_METHOD_MAP = [
        10 => self::TRANSFER_TYPE_METHOD_BET,
        20 => self::TRANSFER_TYPE_METHOD_SETTLE,
        21 => self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE,
        30 => self::TRANSFER_TYPE_METHOD_TRANSFER_IN,
        31 => self::TRANSFER_TYPE_METHOD_TRANSFER_OUT,
        40 => self::TRANSFER_TYPE_METHOD_EVENT_SETTLE,
    ];

    const TRANSFER_TYPES = [10, 20, 21, 30, 31, 40];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'getToken';
    const ACTION_GENERATE_AUTHORIZATION = 'generateAuthorizationAction';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN,
        self::ACTION_GENERATE_AUTHORIZATION,
    ];

    # ADDITIONAL PROPERTIES HERE
    protected $version = 0;
    protected $stop_version = false;
    protected $operator_id;
    protected $partner_key;
    protected $path;
    protected $original_api_method;

    const API_HTTP_METHOD_MAP = [
        'GetBalance' => 'GET',
        'Transfer' => 'POST',
        'CancelTransfer' => 'POST',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ssa_init();
    }

    public function index($game_platform_id, $api_method, $action = null)
    {
        $this->game_platform_id = $game_platform_id;
        $this->original_api_method = $this->api_method = $api_method;
        $this->action = $action = isset($this->ssa_request_params['action_method']) ? $this->ssa_request_params['action_method'] : $action;
        $this->path = "/{$api_method}";

        if ($api_method == self::API_METHOD_GET_BALANCE) {
            $this->ssa_request_params['player'] = $action;
            $this->path .= "/{$action}";
        }

        if ($this->initialize()) {
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    protected function initialize($api_method = null)
    {
        if (empty($api_method)) {
            $api_method = $this->api_method;
        }

        $this->game_api = $this->ssa_initialize_game_api($this->game_platform_id);

        $this->operatorConfig();

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
            $this->operator_id = $this->game_api->operator_id;
            $this->partner_key = $this->game_api->partner_key;
            $this->suffix_for_username = $this->game_api->suffix_for_username;
        } else {
            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_INITIALIZE_GAME_API['message']);
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::API_METHODS)) {
            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            return false;
        }

        if (!empty($this->ssa_request_headers['Authorization'])) {
            $authorization = $this->ssa_request_headers['Authorization'];
            $operator_id = !empty(explode(':', ltrim($authorization, 'AB '))[0]) ? explode(':', ltrim($authorization, 'AB '))[0] : null;

            if ($operator_id != $this->operator_id) {
                $this->ssa_set_response(200, self::RESPONSE_INVALID_OPERATOR_ID);
                $this->ssa_set_hint('Authorization OperatorId', self::RESPONSE_INVALID_OPERATOR_ID['message']);
                return false;
            }

            $generated_authorization = $this->generateAuthorization();

            if ($authorization != $generated_authorization) {
                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'authorization', $authorization, 'generated_authorization', $generated_authorization);
                $this->ssa_set_response(200, self::RESPONSE_INVALID_SIGNATURE);
                $this->ssa_set_hint('Authorization OperatorId', self::RESPONSE_INVALID_SIGNATURE['message']);
                return false;
            }
        }

        return true;
    }

    protected function operatorConfig()
    {
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

    protected function operatorAction()
    {
        $action = $this->action;

        if (in_array($action, [self::ACTION_GET_TOKEN, self::ACTION_GENERATE_AUTHORIZATION])) {
            return $this->$action();
        }

        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
        $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
        return $this->response();
    }

    protected function rebuildRequestParams($data = null)
    {
        $this->request_params = $this->ssa_request_params;

        if (empty($data)) {
            $data = $this->request_params;
        }

        // default request params
        $this->request_params['token'] = isset($data['token']) ? $data['token'] : null;
        $this->request_params['game_username'] = isset($data['player']) ? rtrim($data['player'], $this->suffix_for_username) : null;
        $this->request_params['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->request_params['transaction_id'] = isset($data['tranId']) ? $data['tranId'] : null;
        $this->request_params['round_id'] = isset($data['details']['gameRoundId']) ? $data['details']['gameRoundId'] : null;
        $this->request_params['game_code'] = isset($data['details']['tableName']) ? $data['details']['tableName'] : null;
        $this->request_params['amount'] = isset($data['amount']) ? $data['amount'] : 0;
        $this->request_params['bet_transaction_id'] = isset($data['details']['betNum']) ? $data['details']['betNum'] : null;
        $this->request_params['reference_transaction_id'] = isset($data['originalTranId']) ? $data['originalTranId'] : null;
        $this->request_params['rollback_transaction_id'] = isset($data['originalTranId']) ? $data['originalTranId'] : null;

        // additional
        $this->request_params['transfer_type'] = isset($data['type']) ? $data['type'] : null;
        $this->request_params['game_type'] = isset($data['details']['gameType']) ? $data['details']['gameType'] : null;
        $this->request_params['app_type'] = isset($data['details']['appType']) ? $data['details']['appType'] : null;
        $this->request_params['bet_type'] = isset($data['details']['betType']) ? $data['details']['betType'] : null;
        $this->request_params['bet_method'] = isset($data['details']['betMethod']) ? $data['details']['betMethod'] : null;
        $this->request_params['event_type'] = isset($data['details']['eventType']) ? $data['details']['eventType'] : null;
        $this->request_params['event_code'] = isset($data['details']['eventCode']) ? $data['details']['eventCode'] : null;
        $this->request_params['event_record_num'] = isset($data['details']['eventRecordNum']) ? $data['details']['eventRecordNum'] : null;
        $this->request_params['status'] = isset($data['details']['status']) ? $data['details']['status'] : null;/* 
        $this->request_params['start_at'] = isset($data['details']['gameRoundStartTime']) ? $data['details']['gameRoundStartTime'] : null;
        $this->request_params['end_at'] = isset($data['details']['gameRoundEndTime']) ? $data['details']['gameRoundEndTime'] : null; */

        $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
            $this->api_method,
            $this->request_params['transaction_id'],
            $this->request_params['bet_transaction_id'],
        ]);
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120)
    {
        $this->ssa_external_game_id = isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null;

        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {
            if ($get_player_by == 'token') {
                $this->ssa_set_response(200, self::RESPONSE_PLAYER_NOT_EXIST);
                $this->ssa_set_hint('token', self::RESPONSE_PLAYER_NOT_EXIST['message']);
            } else {
                $this->ssa_set_response(200, self::RESPONSE_PLAYER_NOT_EXIST);
                $this->ssa_set_hint('user', self::RESPONSE_PLAYER_NOT_EXIST['message']);
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;
        $this->version = $this->getVersion();

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true)
    {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_set_response(503, self::RESPONSE_PLAYER_DISABLED);
            $this->ssa_set_hint('blocked', self::RESPONSE_PLAYER_DISABLED['message']);

            return true;
        } else {
            return false;
        }
    }

    protected function systemCheckpoint($config = [
        'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
        'USE_GAME_API_DISABLED' => true,
        'USE_GAME_API_MAINTENANCE' => true,
    ])
    {
        $this->ssa_system_checkpoint($config);

        if ($this->ssa_system_errors('SERVER_IP_ADDRESS_NOT_ALLOWED')) {
            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED['message']);
            $this->ssa_set_hint('ipaddress', $this->ssa_get_ip_address());
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_set_response(503, self::RESPONSE_SERVER_ERROR, self::RESPONSE_ERROR_GAME_DISABLED['message']);
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_set_response(503, self::RESPONSE_SERVER_ERROR, self::RESPONSE_ERROR_GAME_MAINTENANCE['message']);
            return false;
        }

        // additional

        return true;
    }

    protected function isInsufficientBalance($balance, $amount)
    {
        if ($balance < $amount) {
            $this->ssa_set_response(200, self::RESPONSE_CREDIT_NOT_ENOUGH);
            $this->ssa_set_hint('amount', self::RESPONSE_CREDIT_NOT_ENOUGH['message']);

            return true;
        }

        return false;
    }

    protected function isAllowedNegativeBalance()
    {
        if (!empty($this->allowed_negative_balance_api_methods) && is_array($this->allowed_negative_balance_api_methods)) {
            if (in_array($this->api_method, $this->allowed_negative_balance_api_methods)) {
                return true;
            }
        }

        return false;
    }

    protected function afterBalance($after_balance = null)
    {
        return !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, false, $this->request_params['game_code']);
    }

    protected function remoteWalletError(&$data)
    {
        // treat success if remote wallet return double uniqueid
        if ($this->ssa_remote_wallet_error_double_unique_id()) {
            return $data['is_processed'] = true;
        }

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $this->ssa_set_response(400, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_set_response(200, self::RESPONSE_CREDIT_NOT_ENOUGH);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_set_response(503, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message']);
        $data['wallet_adjustment_status'] = $this->ssa_failed;

        return $data['is_processed'] = false;
    }

    protected function walletAdjustment($adjustment_type, $query_type, $is_transaction_already_exists = false, $save_transaction_record_only = false, $allowed_negative_balance = false, $data = [])
    {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        // set remote wallet unique ids
        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([
            $this->game_platform_id,
            $this->request_params['external_unique_id'],
        ]);

        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->request_params['game_code']);
        $this->ssa_set_game_provider_round_id($this->request_params['round_id']);
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);
        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);

        $before_balance = $after_balance = $this->player_balance;
        $amount = $this->ssa_operate_amount(abs($this->request_params['amount']), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        if ($amount == 0 && in_array($this->api_method, [self::TRANSFER_TYPE_METHOD_SETTLE, self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE, self::TRANSFER_TYPE_METHOD_EVENT_SETTLE])) {
            if ($this->ssa_enabled_remote_wallet()) {
                $this->utils->debug_log(__METHOD__, "{$this->game_api->seamless_game_api_name}: amount 0 call remote wallet", 'request_params', $this->ssa_request_params);
                $this->ssa_increase_remote_wallet($this->player_details['player_id'], $amount, $this->game_platform_id, $after_balance);
            }
        }

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
            if ($amount == 0 || $save_transaction_record_only || $adjustment_type == $this->ssa_retain) {
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
                                $data['before_balance'] = $this->afterBalance($after_balance) + $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_DECREASE_BALANCE['message']);
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
                                $data['before_balance'] = $this->afterBalance($after_balance) - $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_INCREASE_BALANCE['message']);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                    // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message']);

                    return false;
                }
            }

            /* if ($success && $data['before_balance'] != $data['after_balance']) {
                //$this->version = $this->ssa_get_timeticks(true);
                $this->version++;
            } */

            if ($success && !$this->stop_version) {
                //$this->version = $this->ssa_get_timeticks(true);
                $this->version++;
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message']);

            return false;
        }
    }

    protected function rebuildTransactionRequestData($data)
    {
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
            'start_at' => $this->ssa_date_time_modifier(isset($this->request_params['start_at']) ? $this->ssa_format_dateTime('Y-m-d H:i:s', $this->request_params['start_at']) : $this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'end_at' => $this->ssa_date_time_modifier(isset($this->request_params['end_at']) ? $this->ssa_format_dateTime('Y-m-d H:i:s', $this->request_params['end_at']) : $this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'bet_transaction_id' => isset($this->request_params['bet_transaction_id']) ? $this->request_params['bet_transaction_id'] : null,
            'reference_transaction_id' => isset($this->request_params['reference_transaction_id']) ? $this->request_params['reference_transaction_id'] : null,
            'rollback_transaction_id' => isset($this->request_params['rollback_transaction_id']) ? $this->request_params['rollback_transaction_id'] : null,

            // addtional
            'transfer_type' => isset($this->request_params['transfer_type']) ? $this->request_params['transfer_type'] : null,
            'game_type' => isset($this->request_params['game_type']) ? $this->request_params['game_type'] : null,
            'app_type' => isset($this->request_params['app_type']) ? $this->request_params['app_type'] : null,
            'bet_type' => isset($this->request_params['bet_type']) ? $this->request_params['bet_type'] : null,
            'bet_method' => isset($this->request_params['bet_method']) ? $this->request_params['bet_method'] : null,
            'event_type' => isset($this->request_params['event_type']) ? $this->request_params['event_type'] : null,
            'event_code' => isset($this->request_params['event_code']) ? $this->request_params['event_code'] : null,
            'event_record_num' => isset($this->request_params['event_record_num']) ? $this->request_params['event_record_num'] : null,

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
            'external_transaction_id' => isset($this->request_params['external_transaction_id']) ? $this->request_params['external_transaction_id'] : null,
        ];

        $new_transaction_data['md5_sum'] = md5(json_encode($new_transaction_data));

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        return $new_transaction_data;
    }

    protected function saveTransactionRequestData($query_type, $data)
    {
        $transaction_data = $this->rebuildTransactionRequestData($data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $transaction_data, 'external_unique_id', $this->request_params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    protected function setStatus()
    {
        switch ($this->api_method) {
            case self::TRANSFER_TYPE_METHOD_SETTLE:
            case self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE:
            case self::TRANSFER_TYPE_METHOD_EVENT_SETTLE:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_CANCEL_TRANSFER:
                $result = Game_logs::STATUS_REFUND;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    protected function finalizeTransactionData()
    {
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

                            // additional
                            'version' => $this->version,
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

    protected function saveServiceLogs()
    {
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
            $data['bet_transaction_id'] = !empty($this->request_params['bet_transaction_id']) ? $this->request_params['bet_transaction_id'] : null;
            $data['reference_transaction_id'] = !empty($this->request_params['reference_transaction_id']) ? $this->request_params['reference_transaction_id'] : null;
            $data['rollback_transaction_id'] = !empty($this->request_params['rollback_transaction_id']) ? $this->request_params['rollback_transaction_id'] : null;
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

    protected function rebuildOperatorResponse($flag, $operator_response)
    {
        if (!in_array($this->action, [self::ACTION_GET_TOKEN, self::ACTION_GENERATE_AUTHORIZATION])) {
            $operator_response = [
                'resultCode' => $this->ssa_get_operator_response(0)->code,
                'message' => $this->ssa_get_operator_response(0)->message,
            ];

            if ($flag == Response_result::FLAG_NORMAL && $this->ssa_get_operator_response(0)->code == self::RESPONSE_SUCCESS['code']) {
                $operator_response['balance'] = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
                $operator_response['version'] = intval($this->version);
            }
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

    protected function response()
    {
        $http_response_status_code = $this->ssa_get_http_response_status_code();
        $operator_response = $this->ssa_get_operator_response();
        $flag = $operator_response['code'] == self::RESPONSE_SUCCESS['code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;

        $extra = [
            'raw_request' => $this->ssa_payload(),
            'generated_authorization' => $this->generateAuthorization(),
        ];

        if ($this->rebuild_operator_response) {
            $operator_response = $this->rebuildOperatorResponse($flag, $operator_response);
        }

        unset($this->ssa_request_params['action_method']);
        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id, $extra);

        if ($this->save_data) {
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

    // default action
    protected function getToken()
    {
        $this->api_method = __FUNCTION__;
        $this->ssa_set_response(400, self::SYSTEM_ERROR_BAD_REQUEST);
        $this->rebuild_operator_response = $this->save_data = $success = false;
        $response = [
            'code' => 1,
        ];

        if (!empty($this->ssa_request_params['player_username'])) {
            if (in_array($this->ssa_request_params['player_username'], $this->get_token_test_accounts)) {
                /* $token = $this->ssa_get_player_common_token_by_player_username($this->ssa_request_params['player_username']);

                if ($this->initializePlayer(false, $this->ssa_subject_type_token, $token, $this->game_platform_id)) {
                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token']);

                    $response = [
                        'code' => 0,
                        'token' => $this->player_details['token'],
                    ];

                    $success = true;
                } else {
                    $response['message'] = 'Invalid Player';
                } */

                $player_id = $this->game_api->getPlayerIdFromUsername($this->ssa_request_params['player_username']);

                if ($player_id) {
                    $token = $this->game_api->generatePlayerToken($player_id . '-' . $this->currency);

                    $response = [
                        'code' => 0,
                        'token' => $token,
                    ];
                } else {
                    $response['message'] = 'Invalid Player';
                }
            } else {
                $response['message'] = 'Player not in the list';
            }
        } else {
            $response['message'] = 'Required player_username';
        }

        if ($success) {
            $this->ssa_set_http_response_status_code(200);
        }

        $this->ssa_set_operator_response($response);

        return $this->response();
    }

    protected function generateAuthorizationAction() {
        $this->api_method = __FUNCTION__;

        $response = [
            'code' => 0,
        ];

        $response['Authorization'] = $this->game_api->generateAuthorization($this->path, self::API_HTTP_METHOD_MAP[$this->api_method], $this->partner_key, $this->ssa_payload());

        $this->ssa_set_http_response_status_code(200);
        $this->ssa_set_operator_response($response);

        return $this->response();
    }

    protected function generateAuthorization(){
        $http_method = self::API_HTTP_METHOD_MAP[$this->original_api_method];
        $date = isset($this->ssa_request_headers['Date']) ? $this->ssa_request_headers['Date'] : null;

        if (!empty($this->ssa_request_headers['Content-Md5'])) {
            $content_md5 = $this->ssa_request_headers['Content-Md5'];
            $content_type =  'application/json; charset=UTF-8';
        } else {
            $content_md5 = '';
            $content_type = '';
        }

        $string_to_sign = $http_method . "\n"
        . $content_md5 . "\n"
        . $content_type . "\n"
        . $date . "\n"
        . $this->path;

        $deKey = base64_decode($this->partner_key);
        $hash_hmac = hash_hmac("sha1", $string_to_sign, $deKey, true);
        $encrypted = base64_encode($hash_hmac);
        $authorization = "AB" . " " . $this->operator_id . ":" . $encrypted;

        return $authorization;
    }

    protected function getTransactions($where = [])
    {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'round_id' => $this->request_params['round_id'],
            ];
        }

        $get_transactions = $this->ssa_get_transactions($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transactions)) {
                $get_transactions = $this->ssa_get_transactions($this->previous_table, $where);
            }
        }

        return $get_transactions;
    }

    protected function getTransaction($where = [], $selected_columns = [], $order_by = ['field_name' => '', 'is_desc' => false])
    {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'round_id' => $this->request_params['round_id'],
            ];
        }

        $get_transactions = $this->ssa_get_transaction($this->transaction_table, $where, $selected_columns, $order_by);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transactions)) {
                $get_transactions = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $get_transactions;
    }

    protected function isTransactionExists($where = [])
    {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }

    protected function getVersion()
    {
        $order_by = ['field_name' => 'version', 'is_desc' => true];

        $result = $this->ssa_get_transaction($this->transaction_table, [
            'player_id' => $this->player_details['player_id'],
        ], ['version'], $order_by);

        return !empty($result['version']) ? $result['version'] : 0;
    }

    protected function GetBalance()
    {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'player' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_INVALID_REQUEST_PARAMETER)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(false, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                        $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true);

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
                        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error: ' . __METHOD__ . ' failed');
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response : $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function transferCheckpoint()
    {
        $rule_sets = [
            'tranId' => ['required'],
            'player' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'],
            'currency' => ['required', "expected_value:{$this->currency}"],
            'type' => [
                'required',
                'expected_value_in' => self::TRANSFER_TYPES,
            ],
            'isRetry' => ['required', 'boolean'],
            'details' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_INVALID_REQUEST_PARAMETER)) {
            $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response : $this->ssa_hint[$this->ssa_request_param_key];
            $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
            $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            return false;
        }

        $this->request_params['game_username'] = isset($this->ssa_request_params['player']) ? rtrim($this->ssa_request_params['player'], $this->suffix_for_username) : null;
        $this->request_params['currency'] = isset($this->ssa_request_params['currency']) ? $this->ssa_request_params['currency'] : null;
        $this->request_params['transaction_id'] = isset($this->ssa_request_params['tranId']) ? strval($this->ssa_request_params['tranId']) : null;
        $this->request_params['amount'] = isset($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : 0;
        $this->request_params['transfer_type'] = isset($this->ssa_request_params['type']) ? $this->ssa_request_params['type'] : null;
        $this->request_params['details'] = isset($this->ssa_request_params['details']) ? $this->ssa_request_params['details'] : [];

        if (!$this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
            return false;
        }

        if ($this->isPlayerBlocked($this->player_details['game_username'])) {
            return false;
        }

        if (isset($this->ssa_request_params['amount']) && $this->ssa_request_params['amount'] < 0) {
            if ($this->isInsufficientBalance($this->player_balance, abs($this->request_params['amount']))) {
                return false;
            }
        }

        $transaction_exist_count = 0;
        $request_params_details = isset($this->ssa_request_params['details']) ? $this->ssa_request_params['details'] : [];

        $order_by = ['field_name' => 'version', 'is_desc' => true];
        $get_transaction = $this->getTransaction([
            'transaction_id' => $this->request_params['transaction_id'],
        ], [], $order_by);

        if (!empty($get_transaction)) {
            $transaction_exist_count++;
        } else {
            foreach ($request_params_details as $details) {
                $rule_sets = [
                    'betNum' => ['required'],
                    'gameRoundId' => ['required'],
                    'status' => ['required'],
                    'tableName' => ['required'],
                ];

                if (in_array($this->api_method, [self::TRANSFER_TYPE_METHOD_EVENT_SETTLE])) {
                    $rule_sets['betNum'] = ['optional'];
                    $rule_sets['gameRoundId'] = ['optional'];
                    $rule_sets['status'] = ['optional'];
                    $rule_sets['tableName'] = ['optional'];
                }

                if (!$this->ssa_validate_request_params($details, $rule_sets, [], self::RESPONSE_INVALID_REQUEST_PARAMETER)) {
                    $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response : $this->ssa_hint[$this->ssa_request_param_key];
                    $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                    $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
                    return false;
                }

                $bet_transaction_id = isset($details['betNum']) ? strval($details['betNum']) : null;

                if ($this->api_method == self::TRANSFER_TYPE_METHOD_BET) {
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_BET,
                        'transaction_id' => $this->request_params['transaction_id'],
                        'bet_transaction_id' => $bet_transaction_id,
                    ]);
                }

                if ($this->api_method == self::TRANSFER_TYPE_METHOD_SETTLE) {
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_SETTLE,
                        'reference_transaction_id' => $bet_transaction_id,
                    ]);
                }

                if ($this->api_method == self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE) {
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE,
                        'transaction_id' => $this->request_params['transaction_id'],
                        'reference_transaction_id' => $bet_transaction_id,
                    ]);
                }

                if (!empty($get_transaction)) {
                    $transaction_exist_count++;
                }
            }
        }

        if ($transaction_exist_count > 0 && !empty($get_transaction)) {
            $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
            /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
            $this->transaction_already_exists = $success = true;
            $this->player_balance = $get_transaction['after_balance'];
            $this->version = $get_transaction['version']; */
            return false;
        }

        return true;
    }

    protected function cancelTransferCheckpoint()
    {
        $rule_sets = [
            'tranId' => ['required'],
            'player' => ['required'],
            'isRetry' => ['required', 'boolean'],
            'originalTranId' => ['required'],
            'originalDetails' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_INVALID_REQUEST_PARAMETER)) {
            $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response : $this->ssa_hint[$this->ssa_request_param_key];
            $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
            $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            return false;
        }

        $this->request_params['game_username'] = isset($this->ssa_request_params['player']) ? rtrim($this->ssa_request_params['player'], $this->suffix_for_username) : null;
        $this->request_params['transaction_id'] = isset($this->ssa_request_params['tranId']) ? strval($this->ssa_request_params['tranId']) : null;
        $this->request_params['bet_transaction_id'] = $this->request_params['rollback_transaction_id'] =  $this->request_params['reference_transaction_id'] = isset($this->ssa_request_params['originalTranId']) ? strval($this->ssa_request_params['originalTranId']) : null;
        $this->request_params['originalDetails'] = isset($this->ssa_request_params['originalDetails']) ? $this->ssa_request_params['originalDetails'] : [];

        if (!$this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
            return false;
        }

        if ($this->isPlayerBlocked($this->player_details['game_username'])) {
            return false;
        }

        if (isset($this->ssa_request_params['amount']) && $this->ssa_request_params['amount'] < 0) {
            if ($this->isInsufficientBalance($this->player_balance, abs($this->request_params['amount']))) {
                return false;
            }
        }

        $transaction_exist_count = 0;
        $request_params_details = isset($this->ssa_request_params['originalDetails']) ? $this->ssa_request_params['originalDetails'] : [];

        $order_by = ['field_name' => 'version', 'is_desc' => true];
        $get_transaction = $this->getTransaction([
            'transaction_id' => $this->request_params['transaction_id'],
        ], [], $order_by);

        if (!empty($get_transaction)) {
            $transaction_exist_count++;
        } else {
            $is_bet_exist = $this->isTransactionExists([
                'api_method' => self::TRANSFER_TYPE_METHOD_BET,
                'transaction_id' => $this->request_params['bet_transaction_id'],
            ]);

            if ($is_bet_exist) {
                $this->request_params['amount'] = 0;

                foreach ($request_params_details as $details) {
                    $rule_sets = [
                        'betNum' => ['required'],
                        'gameRoundId' => ['required'],
                        'status' => ['required'],
                        'tableName' => ['required'],
                        'betAmount' => ['required', 'nullable', 'numeric'],
                    ];
        
                    if (!$this->ssa_validate_request_params($details, $rule_sets, [], self::RESPONSE_INVALID_REQUEST_PARAMETER)) {
                        $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response : $this->ssa_hint[$this->ssa_request_param_key];
                        $this->ssa_set_response(400, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                        $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
                        return false;
                    }
        
                    $get_transaction = $this->getTransaction([
                        'rollback_transaction_id' => $this->request_params['bet_transaction_id'],
                    ]);
        
                    if (!empty($get_transaction)) {
                        $transaction_exist_count++;
                    }

                    $this->request_params['amount'] += $details['betAmount'];
                }
            } else {
                $this->ssa_set_response(200, self::RESPONSE_TRANSACTION_NOT_EXIST, 'originalTranId not exist');
                return false;
            }
        }

        if ($transaction_exist_count > 0 && !empty($get_transaction)) {
            $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
            /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
            $this->transaction_already_exists = $success = true;
            $this->player_balance = $get_transaction['after_balance'];
            $this->version = $get_transaction['version']; */
            return false;
        }

        return true;
    }

    protected function Transfer()
    {
        $this->api_method = __FUNCTION__;

        if (isset($this->ssa_request_params['type']) && !empty(self::TRANSFER_TYPES_METHOD_MAP[$this->ssa_request_params['type']])) {
            $this->api_method = $api_method = self::TRANSFER_TYPES_METHOD_MAP[$this->ssa_request_params['type']];
        }

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->transferCheckpoint()) {
            return $this->$api_method();
        }

        return $this->response();
    }

    protected function bet()
    {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->is_end_round = false;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $success = false;
            $is_batch_bet = count($this->request_params['details']) > 1;
            foreach ($this->request_params['details'] as $details) {
                if ($is_batch_bet) {
                    $this->request_params['amount'] = isset($details['betAmount']) ? $details['betAmount'] : 0;
                }

                $this->request_params['round_id'] = isset($details['gameRoundId']) ? strval($details['gameRoundId']) : null;
                $this->request_params['game_code'] = isset($details['tableName']) ? $details['tableName'] : null;
                $this->request_params['bet_transaction_id'] = isset($details['betNum']) ? strval($details['betNum']) : null;

                // additional
                $this->request_params['game_type'] = isset($details['gameType']) ? $details['gameType'] : null;
                $this->request_params['app_type'] = isset($details['appType']) ? $details['appType'] : null;
                $this->request_params['bet_type'] = isset($details['betType']) ? $details['betType'] : null;
                $this->request_params['bet_method'] = isset($details['betMethod']) ? $details['betMethod'] : null;
                $this->request_params['status'] = isset($details['status']) ? $details['status'] : null;
                /* $this->request_params['start_at'] = isset($details['gameRoundStartTime']) ? $details['gameRoundStartTime'] : null;
                $this->request_params['end_at'] = isset($details['gameRoundEndTime']) ? $details['gameRoundEndTime'] : null; */

                $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
                    $this->api_method,
                    $this->request_params['transaction_id'],
                    $this->request_params['bet_transaction_id'],
                ]);

                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($details) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

                    // check if transaction exists
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_BET,
                        'external_transaction_id' => $this->request_params['external_transaction_id'],
                    ]);

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
                        $this->transaction_type = self::DEBIT;
                        $adjustment_type = $this->ssa_decrease;
                        $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                        if ($success) {
                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        }
                    } else {
                        $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
                        /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        $this->transaction_already_exists = $success = true;
                        $this->player_balance = $get_transaction['after_balance'];
                        $this->version = $get_transaction['version']; */
                    }

                    return $success;
                });

                if (!$success) {
                    break;
                }

                $this->stop_version = true;
            }

            if ($success) {
                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
            }
        }

        return $this->response();
    }

    protected function settle()
    {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $success = false;
            foreach ($this->request_params['details'] as $details) {
                $this->request_params['round_id'] = isset($details['gameRoundId']) ? strval($details['gameRoundId']) : null;
                $this->request_params['game_code'] = isset($details['tableName']) ? $details['tableName'] : null;
                $this->request_params['reference_transaction_id'] = $this->request_params['bet_transaction_id'] = isset($details['betNum']) ? strval($details['betNum']) : null;

                // additional
                $this->request_params['game_type'] = isset($details['gameType']) ? $details['gameType'] : null;
                $this->request_params['app_type'] = isset($details['appType']) ? $details['appType'] : null;
                $this->request_params['bet_type'] = isset($details['betType']) ? $details['betType'] : null;
                $this->request_params['bet_method'] = isset($details['betMethod']) ? $details['betMethod'] : null;
                $this->request_params['status'] = isset($details['status']) ? $details['status'] : null;
                /* $this->request_params['start_at'] = isset($details['gameRoundStartTime']) ? $details['gameRoundStartTime'] : null;
                $this->request_params['end_at'] = isset($details['gameRoundEndTime']) ? $details['gameRoundEndTime'] : null; */

                $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
                    $this->api_method,
                    $this->request_params['transaction_id'],
                    $this->request_params['reference_transaction_id'],
                ]);

                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($details) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

                    // check if transaction exists
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_SETTLE,
                        'reference_transaction_id' => $this->request_params['reference_transaction_id'],
                    ]);

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
                        $bet_transactions = $this->getTransactions([
                            'api_method' => self::TRANSFER_TYPE_METHOD_BET,
                            'bet_transaction_id' => $this->request_params['bet_transaction_id'],
                            'round_id' => $this->request_params['round_id'],
                        ]);

                        if (!empty($bet_transactions)) {
                            $is_already_cancelled = $this->isTransactionExists([
                                'api_method' => self::API_METHOD_CANCEL_TRANSFER,
                                'round_id' => $this->request_params['round_id'],
                            ]);

                            // multiple bets with same betnum and round id. Don't check cancel transaction
                            if (count($bet_transactions) > 1) {
                                $is_already_cancelled = false;
                            }

                            if (!$is_already_cancelled) {
                                $is_already_settled = $this->isTransactionExists([
                                    'reference_transaction_id' => $this->request_params['reference_transaction_id'],
                                ]);

                                if (!$is_already_settled) {
                                    $this->transaction_type = self::CREDIT;
                                    $adjustment_type = $this->ssa_increase;
                                    $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
    
                                    if ($success) {
                                        $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                    }
                                } else {
                                    $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS, 'Transaction already manual settled');
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS, 'Transaction already cancelled');
                            }
                        } else {
                            $this->ssa_set_response(200, self::RESPONSE_TRANSACTION_NOT_EXIST, 'Bet not exist');
                        }
                    } else {
                        $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
                        /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        $this->transaction_already_exists = $success = true;
                        $this->player_balance = $get_transaction['after_balance'];
                        $this->version = $get_transaction['version']; */
                    }

                    return $success;
                });

                if (!$success) {
                    break;
                }
            }

            if ($success) {
                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
            }
        }

        return $this->response();
    }

    protected function manualSettle()
    {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $success = false;
            foreach ($this->request_params['details'] as $details) {
                $this->request_params['round_id'] = isset($details['gameRoundId']) ? strval($details['gameRoundId']) : null;
                $this->request_params['game_code'] = isset($details['tableName']) ? $details['tableName'] : null;
                $this->request_params['reference_transaction_id'] = $this->request_params['bet_transaction_id'] = isset($details['betNum']) ? strval($details['betNum']) : null;

                // additional
                $this->request_params['game_type'] = isset($details['gameType']) ? $details['gameType'] : null;
                $this->request_params['app_type'] = isset($details['appType']) ? $details['appType'] : null;
                $this->request_params['bet_type'] = isset($details['betType']) ? $details['betType'] : null;
                $this->request_params['bet_method'] = isset($details['betMethod']) ? $details['betMethod'] : null;
                $this->request_params['status'] = isset($details['status']) ? $details['status'] : null;
                /* $this->request_params['start_at'] = isset($details['gameRoundStartTime']) ? $details['gameRoundStartTime'] : null;
                $this->request_params['end_at'] = isset($details['gameRoundEndTime']) ? $details['gameRoundEndTime'] : null; */

                $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
                    $this->api_method,
                    $this->request_params['transaction_id'],
                    $this->request_params['reference_transaction_id'],
                ]);

                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($details) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

                    // check if transaction exists
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE,
                        'external_transaction_id' => $this->request_params['external_transaction_id'],
                    ]);

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
                        $bet_transactions = $this->getTransactions([
                            'api_method' => self::TRANSFER_TYPE_METHOD_BET,
                            'bet_transaction_id' => $this->request_params['bet_transaction_id'],
                            'round_id' => $this->request_params['round_id'],
                        ]);

                        if (!empty($bet_transactions)) {
                            $is_already_cancelled = $this->isTransactionExists([
                                'api_method' => self::API_METHOD_CANCEL_TRANSFER,
                                'round_id' => $this->request_params['round_id'],
                            ]);

                            // multiple bets with same betnum and round id. Don't check cancel transaction
                            if (count($bet_transactions) > 1) {
                                $is_already_cancelled = false;
                            }

                            if (!$is_already_cancelled) {
                                $order_by = ['field_name' => 'version', 'is_desc' => true];
                                $settled_transaction = $this->getTransaction("(api_method IN ('settle', 'manualSettle') AND reference_transaction_id='{$this->request_params['reference_transaction_id']}')", [], $order_by);

                                if (!empty($settled_transaction)) {
                                    $settled_request = json_decode($settled_transaction['request'], true);
                                    if ($settled_transaction['api_method'] == self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE) {
                                        if ($settled_request['amount'] != 0) {
                                            $this->request_params['amount'] -= $settled_transaction['amount'];
                                        }
                                    } else {
                                        $this->request_params['amount'] -= $settled_transaction['amount'];
                                    }
                                }
                                if ($this->request_params['amount'] < 0) {
                                    $this->transaction_type = self::DEBIT;
                                    $adjustment_type = $this->ssa_decrease;
                                } else {
                                    $this->transaction_type = self::CREDIT;
                                    $adjustment_type = $this->ssa_increase;
                                }

                                $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                if ($success) {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS, 'Transaction already cancelled');
                            }
                        } else {
                            $this->ssa_set_response(200, self::RESPONSE_TRANSACTION_NOT_EXIST, 'Bet not exist');
                        }
                    } else {
                        $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
                        /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        $this->transaction_already_exists = $success = true;
                        $this->player_balance = $get_transaction['after_balance'];
                        $this->version = $get_transaction['version']; */
                    }

                    return $success;
                });

                if (!$success) {
                    break;
                }
            }

            if ($success) {
                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
            }
        }

        return $this->response();
    }

    protected function eventSettle()
    {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $success = false;
            foreach ($this->request_params['details'] as $details) {
                // $this->request_params['round_id'] = isset($details['gameRoundId']) ? strval($details['gameRoundId']) : null;
                $this->request_params['game_code'] = isset($details['tableName']) ? $details['tableName'] : null;
                $this->request_params['bet_transaction_id'] = isset($details['betNum']) ? strval($details['betNum']) : null;

                // additional
                $this->request_params['game_type'] = isset($details['gameType']) ? $details['gameType'] : null;
                $this->request_params['app_type'] = isset($details['appType']) ? $details['appType'] : null;
                $this->request_params['bet_type'] = isset($details['betType']) ? $details['betType'] : null;
                $this->request_params['bet_method'] = isset($details['betMethod']) ? $details['betMethod'] : null;
                $this->request_params['status'] = isset($details['status']) ? $details['status'] : null;
                /* $this->request_params['start_at'] = isset($details['gameRoundStartTime']) ? $details['gameRoundStartTime'] : null;
                $this->request_params['end_at'] = isset($details['gameRoundEndTime']) ? $details['gameRoundEndTime'] : null; */
                $this->request_params['event_type'] = isset($details['eventType']) ? $details['eventType'] : null;
                $this->request_params['event_code'] = isset($details['eventCode']) ? $details['eventCode'] : null;
                $this->request_params['event_record_num'] = $this->request_params['round_id'] = isset($details['eventRecordNum']) ? strval($details['eventRecordNum']) : null;

                $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
                    $this->api_method,
                    $this->request_params['transaction_id'],
                    $this->request_params['event_record_num'],
                ]);

                if (empty($this->request_params['game_code'])) {
                    $this->request_params['game_code'] = $this->request_params['event_code'];
                }

                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($details) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

                    // check if transaction exists
                    $get_transaction = $this->getTransaction([
                        'api_method' => self::TRANSFER_TYPE_METHOD_EVENT_SETTLE,
                        'event_record_num' => $this->request_params['event_record_num'],
                    ]);

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
                        $this->transaction_type = self::CREDIT;
                        $adjustment_type = $this->ssa_increase;
                        $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                        if ($success) {
                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        }
                    } else {
                        $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
                        /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        $this->transaction_already_exists = $success = true;
                        $this->player_balance = $get_transaction['after_balance'];
                        $this->version = $get_transaction['version']; */
                    }

                    return $success;
                });

                if (!$success) {
                    break;
                }
            }

            if ($success) {
                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
            }
        }

        return $this->response();
    }

    protected function CancelTransfer()
    {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->is_end_round = false;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            if ($this->cancelTransferCheckpoint()) {
                $success = false;
                $is_batch_cancel = count($this->request_params['originalDetails']) > 1;
                foreach ($this->request_params['originalDetails'] as $details) {
                    if (!$is_batch_cancel) {
                        $this->request_params['amount'] = isset($details['betAmount']) ? $details['betAmount'] : 0;
                    }

                    $this->request_params['round_id'] = isset($details['gameRoundId']) ? strval($details['gameRoundId']) : null;
                    $this->request_params['game_code'] = isset($details['tableName']) ? $details['tableName'] : null;

                    // additional
                    $this->request_params['game_type'] = isset($details['gameType']) ? $details['gameType'] : null;
                    $this->request_params['app_type'] = isset($details['appType']) ? $details['appType'] : null;
                    $this->request_params['bet_type'] = isset($details['betType']) ? $details['betType'] : null;
                    $this->request_params['bet_method'] = isset($details['betMethod']) ? $details['betMethod'] : null;
                    $this->request_params['status'] = isset($details['status']) ? $details['status'] : null;

                    $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
                        $this->api_method,
                        $this->request_params['transaction_id'],
                        $this->request_params['bet_transaction_id'],
                    ]);

                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($details, $is_batch_cancel) {
                        $success = false;
                        $transaction_already_exists = false;
                        $is_processed = false;
                        $save_only = false;
                        $allowed_negative_balance = false;

                        // check if transaction exists
                        $get_transaction = $this->getTransaction([
                            'api_method' => self::API_METHOD_CANCEL_TRANSFER,
                            'rollback_transaction_id' => $this->request_params['rollback_transaction_id'],
                        ]);

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
                            $is_already_settled = $this->isTransactionExists("(api_method IN ('settle', 'manualSettle')) AND round_id='{$this->request_params['round_id']}'");

                            if (!$is_already_settled) {
                                $bet_transaction = $this->getTransaction([
                                    'api_method' => self::TRANSFER_TYPE_METHOD_BET,
                                    'transaction_id' => $this->request_params['rollback_transaction_id'],
                                ]);

                                if (!$is_batch_cancel) {
                                    $this->request_params['amount'] = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                                }

                                if ($bet_transaction) {
                                    $this->transaction_type = self::CREDIT;
                                    $adjustment_type = $this->ssa_increase;
                                    $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
        
                                    if ($success) {
                                        $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                    }
                                } else {
                                    $this->ssa_set_response(200, self::RESPONSE_TRANSACTION_NOT_EXIST, 'Bet not exist');
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS, 'Transaction already settled');
                            }
                        } else {
                            $this->ssa_set_response(200, self::RESPONSE_INVALID_STATUS);
                            /* $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                            $this->transaction_already_exists = $success = true;
                            $this->player_balance = $get_transaction['after_balance'];
                            $this->version = $get_transaction['version']; */
                        }

                        return $success;
                    });

                    break;
                }

                if ($success) {
                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                }
            }
        }

        return $this->response();
    }
}
