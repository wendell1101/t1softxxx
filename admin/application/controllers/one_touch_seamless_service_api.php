<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class One_touch_seamless_service_api extends BaseController {
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
    protected $seamless_service_unique_id_with_game_prefix = null;
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
    protected $seamless_service_related_unique_id = null;
    protected $seamless_service_related_action = null;

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

    # API RESPONSE ------------------------------------------------------------------------- start here
    const RESPONSE_SUCCESS = [
        'code' => 'RS_OK',
        'message' => 'Success',
    ];

    const RESPONSE_ERROR_UNKNOWN = [
        'code' => 'RS_ERROR_UNKNOWN',
        'message' => 'Unknown error',
    ];

    const RESPONSE_ERROR_INVALID_PARTNER = [
        'code' => 'RS_ERROR_INVALID_PARTNER',
        'message' => 'Unknown error',
    ];

    const RESPONSE_ERROR_INVALID_TOKEN = [
        'code' => 'RS_ERROR_INVALID_TOKEN',
        'message' => 'Invalid token',
    ];

    const RESPONSE_ERROR_INVALID_GAME = [
        'code' => 'RS_ERROR_INVALID_GAME',
        'message' => 'Invalid game',
    ];

    const RESPONSE_ERROR_INVALID_USER = [
        'code' => 'RS_ERROR_INVALID_USER',
        'message' => 'Invalid user',
    ];

    const RESPONSE_ERROR_WRONG_CURRENCY = [
        'code' => 'RS_ERROR_WRONG_CURRENCY',
        'message' => 'Wrong currency',
    ];

    const RESPONSE_ERROR_NOT_ENOUGH_MONEY = [
        'code' => 'RS_ERROR_NOT_ENOUGH_MONEY',
        'message' => 'Not enough money',
    ];

    const RESPONSE_ERROR_USER_DISABLED = [
        'code' => 'RS_ERROR_USER_DISABLED',
        'message' => 'User disabled',
    ];

    const RESPONSE_ERROR_INVALID_SIGNATURE = [
        'code' => 'RS_ERROR_INVALID_SIGNATURE',
        'message' => 'Invalid signature',
    ];

    const RESPONSE_ERROR_TOKEN_EXPIRED = [
        'code' => 'RS_ERROR_TOKEN_EXPIRED',
        'message' => 'Token expired',
    ];

    const RESPONSE_ERROR_WRONG_SYNTAX = [
        'code' => 'RS_ERROR_WRONG_SYNTAX',
        'message' => 'Wrong syntax',
    ];

    const RESPONSE_ERROR_WRONG_TYPES = [
        'code' => 'RS_ERROR_WRONG_TYPES',
        'message' => 'Wrong types',
    ];

    const RESPONSE_ERROR_WRONG_PROTO = [
        'code' => 'RS_ERROR_WRONG_PROTO',
        'message' => 'Wrong proto',
    ];

    const RESPONSE_ERROR_DUPLICATE_TRANSACTION = [
        'code' => 'RS_ERROR_DUPLICATE_TRANSACTION',
        'message' => 'Duplicate transaction',
    ];

    const RESPONSE_ERROR_DUPLICATE_REQUEST = [
        'code' => 'RS_ERROR_DUPLICATE_REQUEST',
        'message' => 'Duplicate request',
    ];

    const RESPONSE_ERROR_REQUEST_QUEUE_OVERFLOW = [
        'code' => 'RS_ERROR_REQUEST_QUEUE_OVERFLOW',
        'message' => 'Request queue overflow',
    ];

    const RESPONSE_ERROR_PLAYERS_NUMBER_LIMIT = [
        'code' => 'RS_ERROR_PLAYERS_NUMBER_LIMIT',
        'message' => 'Players number limit',
    ];

    const RESPONSE_ERROR_MAXBET_SINGLE = [
        'code' => 'RS_ERROR_MAXBET_SINGLE',
        'message' => 'Error maxbet single',
    ];

    const RESPONSE_ERROR_MINBET_SINGLE = [
        'code' => 'RS_ERROR_MINBET_SINGLE',
        'message' => 'Error minbet single',
    ];

    const RESPONSE_ERROR_MAXBET_ACC = [
        'code' => 'RS_ERROR_MAXBET_ACC',
        'message' => 'Error maxbet acc',
    ];

    const RESPONSE_ERROR_TRANSACTION_DOES_NOT_EXIST = [
        'code' => 'RS_ERROR_TRANSACTION_DOES_NOT_EXIST',
        'message' => 'Transaction does not exist',
    ];

    const RESPONSE_ERROR_FEATURE_IS_NOT_SUPPORTED = [
        'code' => 'RS_ERROR_FEATURE_IS_NOT_SUPPORTED',
        'message' => 'Feature is not supported',
    ];

    const RESPONSE_ERROR_OPERATOR_API = [
        'code' => 'RS_ERROR_OPERATOR_API',
        'message' => 'Error operator API',
    ];

    # API METHODS HERE
    // default
    const API_METHOD_OPERATOR_ACTION = 'operatorAction';
    // From docs
    const API_METHOD_BALANCE = 'balance';
    const API_METHOD_BET = 'bet';
    const API_METHOD_WIN = 'win';
    const API_METHOD_ROLLBACK = 'rollback';
    const API_METHOD_END_ROUND = 'endRound';

    const API_METHODS = [
        self::API_METHOD_OPERATOR_ACTION,
        self::API_METHOD_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_WIN,
        self::API_METHOD_ROLLBACK,
        self::API_METHOD_END_ROUND,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_BET,
        self::API_METHOD_WIN,
        self::API_METHOD_ROLLBACK,
        self::API_METHOD_END_ROUND,
    ];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'getToken';
    const ACTION_GENERATE_SIGNATURE = 'generateSignature';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN,
        self::ACTION_GENERATE_SIGNATURE,
    ];

    # ADDITIONAL PROPERTIES HERE
    // protected $request_uuid;

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->ssa_use_token_from = 'external_common_tokens';
        // $this->request_uuid = $this->utils->getGUIDv4();
    }

    public function index($game_platform_id, $api_method, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->api_method = $api_method;
        $this->action = $action = isset($this->ssa_request_params['action_method']) ? $this->ssa_request_params['action_method'] : $action;

        if ($api_method == 'end-round') {
            $this->api_method = $api_method = self::API_METHOD_END_ROUND;
        }

        if ($this->initialize()) {
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    protected function initialize() {
        $this->game_api = $this->ssa_initialize_game_api($this->game_platform_id);

        $this->responseConfig();

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
        } else {
            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_INITIALIZE_GAME_API['message']);
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $this->api_method)) {
            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            return false;
        }

        if ($this->ssa_is_api_method_allowed($this->api_method, self::API_METHODS)) {
            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            return false;
        }

        if (!empty($this->ssa_request_headers['X-Signature']) && $this->game_api->verify_signature) {
            if (!$this->game_api->verifySignature($this->ssa_payload(), $this->ssa_request_headers['X-Signature'])) {
                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'X-Signature', ['request_signature' => $this->ssa_request_headers['X-Signature'], 'generated_signature' => $this->game_api->generateSignature($this->ssa_payload()), 'raw_request' => $this->ssa_payload()]);
                $this->ssa_set_response(404, self::RESPONSE_ERROR_INVALID_SIGNATURE);
                $this->ssa_set_hint('X-Signature', self::RESPONSE_ERROR_INVALID_SIGNATURE['message']);
                return false;
            }
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

    protected function operatorAction() {
        $action = $this->action;

        if (in_array($action, [self::ACTION_GET_TOKEN, self::ACTION_GENERATE_SIGNATURE])) {
            return $this->$action();
        }

        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
        $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
        return $this->response();
    }

    protected function rebuildRequestParams($data = null) {
        $this->request_params = $this->ssa_request_params;

        if (empty($data)) {
            $data = $this->request_params;
        }

        // default request params
        $this->request_params['token'] = isset($data['token']) ? $data['token'] : null;
        $this->request_params['game_username'] = isset($data['game_username']) ? $data['game_username'] : null;
        /* $decrypt_token = $this->game_api->decryptGeneratedPlayerToken($this->request_params['token']);
        $this->request_params['game_username'] = !empty(explode('-', $decrypt_token)[0]) ? $this->game_api->getGameUsernameByPlayerId(explode('-', $decrypt_token)[0]) : null; */
        /* $player_id = $this->ssa_get_specific_column('external_common_tokens', 'player_id', ['token' => $this->request_params['token'], 'currency' => $this->currency]);
        $this->request_params['game_username'] = !empty($player_id) ? $this->game_api->getGameUsernameByPlayerId($player_id) : null; */
        $this->request_params['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->request_params['transaction_id'] = isset($data['transaction_uuid']) ? $data['transaction_uuid'] : null;
        $this->request_params['round_id'] = isset($data['round']) ? $data['round'] : null;
        $this->request_params['game_code'] = isset($data['game_id']) ? $data['game_id'] : null;
        $this->request_params['amount'] = isset($data['amount']) ? $data['amount'] : 0;
        $this->request_params['reference_transaction_id'] = isset($data['reference_transaction_uuid']) ? $data['reference_transaction_uuid'] : null;
        $this->request_params['rollback_transaction_id'] = null;

        // additional
        $this->request_params['request_uuid'] = isset($data['request_uuid']) ? $data['request_uuid'] : null;
        $this->request_params['offer_id'] = isset($data['offer_id']) ? $data['offer_id'] : null;

        if (empty($this->request_params['transaction_id'])) {
            $this->request_params['transaction_id'] = $this->request_params['request_uuid'];
        }

        $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
            $this->api_method,
            $this->request_params['transaction_id'],
        ]);
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        $this->ssa_external_game_id = isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null;

        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {
            if ($get_player_by == 'token') {
                $this->ssa_set_response(400, self::RESPONSE_ERROR_INVALID_TOKEN);
                $this->ssa_set_hint('token', self::RESPONSE_ERROR_INVALID_TOKEN['message']);
            } else {
                $this->ssa_set_response(400, self::RESPONSE_ERROR_INVALID_USER);
                $this->ssa_set_hint('user', self::RESPONSE_ERROR_INVALID_USER['message']);
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true) {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_USER_DISABLED);
            $this->ssa_set_hint('blocked', self::RESPONSE_ERROR_USER_DISABLED['message']);

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
            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED['message']);
            $this->ssa_set_hint('ipaddress', $this->ssa_get_ip_address());
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_UNKNOWN, self::RESPONSE_ERROR_GAME_DISABLED['message']);
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_UNKNOWN, self::RESPONSE_ERROR_GAME_MAINTENANCE['message']);
            return false;
        }

        // additional

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_set_response(200, self::RESPONSE_ERROR_NOT_ENOUGH_MONEY);
            $this->ssa_set_hint('amount', self::RESPONSE_ERROR_NOT_ENOUGH_MONEY['message']);

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
        return !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, false, $this->request_params['game_code']);
    }

    protected function remoteWalletError(&$data) {
        // treat success if remote wallet return double uniqueid
        if ($this->ssa_remote_wallet_error_double_unique_id()) {
            return $data['is_processed'] = true;
        }

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $this->ssa_set_response(400, self::RESPONSE_ERROR_OPERATOR_API, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_set_response(200, self::RESPONSE_ERROR_NOT_ENOUGH_MONEY);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_OPERATOR_API, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        $this->ssa_set_response(500, self::RESPONSE_ERROR_OPERATOR_API, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message']);
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

        $this->seamless_service_unique_id_with_game_prefix = $this->utils->mergeArrayValues([
            'game',
            $this->seamless_service_unique_id,
        ]);

        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->request_params['game_code']);
        $this->ssa_set_game_provider_round_id($this->request_params['round_id']);
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);
        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        $before_balance = $after_balance = $this->player_balance;
        $amount = $this->ssa_operate_amount(abs($this->request_params['amount']), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        if ($amount == 0 && $this->api_method == self::API_METHOD_WIN) {
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
                            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_DECREASE_BALANCE['message']);
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
                            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_INCREASE_BALANCE['message']);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message']);

                    return false;
                }
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message']);

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
            'reference_transaction_id' => isset($this->request_params['reference_transaction_id']) ? $this->request_params['reference_transaction_id'] : null,
            'rollback_transaction_id' => isset($this->request_params['rollback_transaction_id']) ? $this->request_params['rollback_transaction_id'] : null,

            // addtional
            'request_uuid' => isset($this->request_params['request_uuid']) ? $this->request_params['request_uuid'] : null,
            'offer_id' => isset($this->request_params['offer_id']) ? $this->request_params['offer_id'] : null,

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
            'seamless_service_unique_id' => $this->seamless_service_unique_id_with_game_prefix,
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
            case self::API_METHOD_WIN:
            case self::API_METHOD_END_ROUND:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_ROLLBACK:
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
                'seamless_service_unique_id' => $this->seamless_service_unique_id_with_game_prefix,
                'external_game_id' => !empty($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
                'external_unique_id' => !empty($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
            ];

            unset($md5_data['response'], $md5_data['extra_info']);

            $data['md5_sum'] = md5(json_encode($md5_data));
            $data['elapsed_time'] = $this->utils->getCostMs();
            $data['response_result_id'] = $this->response_result_id;
            $data['transaction_id'] = !empty($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null;
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

    protected function rebuildOperatorResponse($flag, $operator_response) {
        if (!in_array($this->action, [self::ACTION_GET_TOKEN, self::ACTION_GENERATE_SIGNATURE])) {
            $operator_response = [
                'status' => $this->ssa_get_operator_response(0)->code,
            ];
    
            if (!empty($this->ssa_request_params['request_uuid'])) {
                $operator_response['request_uuid'] = $this->ssa_request_params['request_uuid'];
            }
    
            if ($flag == Response_result::FLAG_NORMAL && $this->ssa_get_operator_response(0)->code == self::RESPONSE_SUCCESS['code']) {
                if (in_array($this->api_method, [self::API_METHOD_BALANCE, self::API_METHOD_BET, self::API_METHOD_WIN, self::API_METHOD_ROLLBACK])) {
                    $operator_response['user'] = $this->player_details['game_username'];
                    $operator_response['currency'] = $this->currency;
                    $operator_response['balance'] = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
                }
            } else {
                $operator_response['message'] = $this->ssa_get_operator_response(0)->message;
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

    protected function response() {
        $http_response_status_code = $this->ssa_get_http_response_status_code();
        $operator_response = $this->ssa_get_operator_response();
        $flag = $operator_response['code'] == self::RESPONSE_SUCCESS['code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $extra = [
            'raw_request' => $this->ssa_payload(),
        ];

        if ($this->rebuild_operator_response) {
            $operator_response = $this->rebuildOperatorResponse($flag, $operator_response);
        }

        if ($this->game_api && !empty($this->ssa_request_headers['X-Signature'])) {
            $extra['request_signature'] = $this->ssa_request_headers['X-Signature'];
            $extra['generated_signature'] = $this->game_api->generateSignature($this->ssa_payload());
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
    protected function getToken() {
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

    protected function generateSignature() {
        $this->api_method = __FUNCTION__;

        $response = [
            'code' => 0,
            'X-Signature' => $this->game_api->generateSignature($this->ssa_payload()),
        ];

        $this->ssa_set_response(200, $response);
        return $this->response();
    }

    protected function getTransactions($where = []) {
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

    protected function getTransaction($where = []) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'round_id' => $this->request_params['round_id'],
            ];
        }

        $get_transactions = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transactions)) {
                $get_transactions = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $get_transactions;
    }

    protected function isTransactionExists($where = []) {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }

    protected function balance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'token' => ['required'],
                'request_uuid' => ['required'],
                'game_id' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_WRONG_SYNTAX)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(false, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
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
                        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, 'Internal Server Error: '. __METHOD__ .' failed');
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

    protected function bet() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->is_end_round = false;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'transaction_uuid' => ['required'],
                'token' => ['required'],
                'round' => ['required'],
                'request_uuid' => ['required'],
                'game_id' => ['required'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'offer_id' => ['optional'],
                'amount' => ['required', 'numeric', 'positive'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [
                'currency' => [
                    'rules' => ['expected_value'],
                    'operator_response' => self::RESPONSE_ERROR_WRONG_CURRENCY
                ],
            ], self::RESPONSE_ERROR_WRONG_SYNTAX)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                            $success = false;
                            $transaction_already_exists = false;
                            $is_processed = false;
                            $save_only = false;
                            $allowed_negative_balance = false;

                            // check if transaction exists
                            $get_transaction = $this->getTransaction("(transaction_id='{$this->request_params['transaction_id']}' OR request_uuid='{$this->ssa_request_params['request_uuid']}')");

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
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->request_params['request_uuid'] = $get_transaction['request_uuid'];
                            }

                            return $success;
                        });

                        if ($success) {
                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        }
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

    protected function win() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'transaction_uuid' => ['required'],
                'token' => ['required'],
                'request_uuid' => ['required'],
                'reference_transaction_uuid' => ['required'],
                'round' => ['required'],
                'game_id' => ['required'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'offer_id' => ['optional'],
                'amount' => ['required', 'nullable', 'numeric', 'positive'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [
                'currency' => [
                    'rules' => ['expected_value'],
                    'operator_response' => self::RESPONSE_ERROR_WRONG_CURRENCY
                ],
            ], self::RESPONSE_ERROR_WRONG_SYNTAX)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                            $success = false;
                            $transaction_already_exists = false;
                            $is_processed = false;
                            $save_only = false;
                            $allowed_negative_balance = false;

                            // check if transaction exists
                            $get_transaction = $this->getTransaction("(transaction_id='{$this->request_params['transaction_id']}' OR request_uuid='{$this->ssa_request_params['request_uuid']}')");

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
                                $bet_transaction = $this->getTransaction([
                                    'api_method' => self::API_METHOD_BET,
                                    'game_username' => $this->player_details['game_username'],
                                    'transaction_id' => $this->request_params['reference_transaction_id'],
                                    'round_id' => $this->request_params['round_id'],
                                ]);

                                if ($bet_transaction) {
                                    if (!empty($bet_transaction['seamless_service_unique_id'])) {
                                        $this->seamless_service_related_unique_id = $bet_transaction['seamless_service_unique_id'];
                                        $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                                    }

                                    $is_already_rollback = $this->isTransactionExists([
                                        'api_method' => self::API_METHOD_ROLLBACK,
                                        'game_username' => $this->player_details['game_username'],
                                        'round_id' => $this->request_params['round_id'],
                                        'reference_transaction_id' => $this->request_params['reference_transaction_id'],
                                    ]);
    
                                    if (!$is_already_rollback) {
                                        $this->transaction_type = self::CREDIT;
                                        $adjustment_type = $this->ssa_increase;
                                        $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                        if ($success) {
                                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                        }
                                    } else {
                                        $this->ssa_set_response(400, self::RESPONSE_ERROR_WRONG_PROTO, 'The transaction has already been canceled');
                                    }
                                } else {
                                    $this->ssa_set_response(400, self::RESPONSE_ERROR_TRANSACTION_DOES_NOT_EXIST, 'Bet not exist');
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->request_params['request_uuid'] = $get_transaction['request_uuid'];
                            }

                            return $success;
                        });

                        if ($success) {
                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        }
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

    protected function rollback() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'transaction_uuid' => ['required'],
                'token' => ['required'],
                'request_uuid' => ['required'],
                'reference_transaction_uuid' => ['required'],
                'game_id' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_WRONG_SYNTAX)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                            $success = false;
                            $transaction_already_exists = false;
                            $is_processed = false;
                            $save_only = false;
                            $allowed_negative_balance = false;

                            // check if transaction exists
                            $get_transaction = $this->getTransaction("(transaction_id='{$this->request_params['transaction_id']}' OR request_uuid='{$this->ssa_request_params['request_uuid']}')");

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
                                $bet_transaction = $this->getTransaction([
                                    'api_method' => self::API_METHOD_BET,
                                    'transaction_id' => $this->request_params['reference_transaction_id'],
                                ]);

                                $this->request_params['round_id'] = isset($bet_transaction['round_id']) ? $bet_transaction['round_id'] : null;
                                $amount = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                                $this->request_params['amount'] = $this->ssa_operate_amount($amount, $this->precision, $this->conversion, $this->arithmetic_name);

                                if ($bet_transaction) {
                                    if (!empty($bet_transaction['seamless_service_unique_id'])) {
                                        $this->seamless_service_related_unique_id = $bet_transaction['seamless_service_unique_id'];
                                        $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                                    }

                                    $is_already_settled = $this->isTransactionExists([
                                        'api_method' => self::API_METHOD_WIN,
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
                                        $this->ssa_set_response(400, self::RESPONSE_ERROR_WRONG_PROTO, 'The transaction has already been settled');
                                    }
                                } else {
                                    $this->ssa_set_response(400, self::RESPONSE_ERROR_TRANSACTION_DOES_NOT_EXIST, 'Bet not exist');
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->request_params['request_uuid'] = $get_transaction['request_uuid'];
                            }

                            return $success;
                        });

                        if ($success) {
                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        }
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

    protected function endRound() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_ERROR_UNKNOWN, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'game_id' => ['required'],
                'round' => ['required'],
                'request_uuid' => ['required'],
                'token' => ['required'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'offer_id' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_ERROR_WRONG_SYNTAX)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $is_exists = $this->isTransactionExists([
                            'request_uuid' => $this->request_params['request_uuid'],
                        ]);

                        if (!$is_exists) {
                            $bet_transaction = $this->getTransaction([
                                'api_method' => self::API_METHOD_BET,
                                'game_username' => $this->player_details['game_username'],
                                'game_code' => $this->request_params['game_code'],
                                'round_id' => $this->request_params['round_id'],
                            ]);
    
                            $this->request_params['amount'] =  0;
    
                            if ($bet_transaction) {
                                $this->transaction_type = self::CREDIT;
                                $adjustment_type = $this->ssa_increase;
                                $success = false;
                                $transaction_already_exists = false;
                                $is_processed = false;
                                $save_only = false;
                                $allowed_negative_balance = false;

                                if (!empty($bet_transaction['seamless_service_unique_id'])) {
                                    $this->seamless_service_related_unique_id = $bet_transaction['seamless_service_unique_id'];
                                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                                }
    
                                $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);
    
                                if ($success) {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                }
                            } else {
                                $this->ssa_set_response(400, self::RESPONSE_ERROR_TRANSACTION_DOES_NOT_EXIST, 'Bet not exist');
                            }
                        } else {
                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                        }
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