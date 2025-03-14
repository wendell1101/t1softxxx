<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Bng_seamless_service_api extends BaseController {
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
    protected $remote_wallet_status = null;
    protected $use_remote_wallet_failed_transaction_monthly_table = false;

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
        'code' => 0,
        'message' => 'Success',
    ];

    const RESPONSE_INVALID_TOKEN = [
        'code' => 'INVALID_TOKEN',
        'message' => 'Passed token was not generated by the Operator',
    ];

    const RESPONSE_EXPIRED_TOKEN = [
        'code' => 'EXPIRED_TOKEN',
        'message' => 'The token is expired',
    ];

    const RESPONSE_GAME_NOT_ALLOWED = [
        'code' => 'GAME_NOT_ALLOWED',
        'message' => 'The player is not allowed to play this game',
    ];

    const RESPONSE_TIME_EXCEED = [
        'code' => 'TIME_EXCEED',
        'message' => 'Time limit for a given game is exceeded',
    ];

    const RESPONSE_LOSS_EXCEED = [
        'code' => 'LOSS_EXCEED',
        'message' => 'Loss limit exceeded',
    ];

    const RESPONSE_BET_EXCEED = [
        'code' => 'BET_EXCEED',
        'message' => 'Insufficient funds',
    ];

    const RESPONSE_FUNDS_EXCEED = [
        'code' => 'FUNDS_EXCEED',
        'message' => 'Insufficient funds',
    ];

    const RESPONSE_OTHER_EXCEED = [
        'code' => 'OTHER_EXCEED',
        'message' => 'Unknown error',
    ];

    const RESPONSE_SESSION_CLOSED = [
        'code' => 'SESSION_CLOSED',
        'message' => 'Session closed',
    ];

    const RESPONSE_PLAYER_DISCONNECTED = [
        'code' => 'PLAYER_DISCONNECTED',
        'message' => 'A player disconnected',
    ];

    const RESPONSE_GAME_REOPENED = [
        'code' => 'GAME_REOPENED',
        'message' => 'A game with a same currency is opened in a new window tab',
    ];

    # API METHODS HERE
    const API_METHOD_LOGIN = 'login';
    const API_METHOD_GET_BALANCE = 'getbalance';
    const API_METHOD_TRANSACTION = 'transaction';
    const API_METHOD_ROLLBACK = 'rollback';
    const API_METHOD_LOGOUT = 'logout';

    const API_METHODS = [
        self::API_METHOD_LOGIN,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_TRANSACTION,
        self::API_METHOD_ROLLBACK,
        self::API_METHOD_LOGOUT,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_TRANSACTION,
        self::API_METHOD_ROLLBACK,
    ];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'show_hint';
    const ACTION_GET_TOKEN = 'get_token';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN
    ];

    # ADDITIONAL PROPERTIES HERE
    protected $brand;
    protected $provider_id;
    protected $version = 0;
    protected $test_players;

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
    }

    public function index($game_platform_id, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->api_method = $api_method = isset($this->ssa_request_params['name']) ? $this->ssa_request_params['name'] : null;
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
        $this->game_api = $this->ssa_initialize_game_api($this->game_platform_id);

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
            $this->use_remote_wallet_failed_transaction_monthly_table = $this->game_api->use_remote_wallet_failed_transaction_monthly_table;

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
            $this->previous_table = $this->game_api->ymt_get_previous_year_month_table();

            // additional
            $this->brand = $this->game_api->brand;
            $this->provider_id = $this->game_api->provider_id;
            $this->test_players = $this->game_api->test_players;
        } else {
            $this->ssa_set_response(500, self::SYSTEM_ERROR_INITIALIZE_GAME_API, self::SYSTEM_ERROR_INITIALIZE_GAME_API['message']);
            return false;
        }

        if ($this->action == self::ACTION_GET_TOKEN) {
            return true;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $this->api_method)) {
            $this->ssa_set_response(500, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            return false;
        }

        if ($this->ssa_is_api_method_allowed($this->api_method, self::API_METHODS)) {
            $this->ssa_set_response(500, self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            return false;
        }

        /* if (isset($this->ssa_request_headers['Security-Hash'])) {
            if (!$this->ssa_validate_basic_auth_request($this->api_username, $this->api_password)) {
                $this->ssa_set_response(404, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Invalid Authorization');
                $this->ssa_set_hint('Authorization', 'Check api_username and api_password');
                return false;
            }
        } */

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
        $this->request_params = $this->ssa_request_params;

        if (empty($data)) {
            $data = $this->request_params;
        }

        $this->request_params['args'] = $args = !empty($data['args']) ? $data['args'] : [];
        $this->request_params['player'] = $player = !empty($args['player']) ? $args['player'] : [];
        $this->request_params['bonus'] = $bonus = !empty($args['bonus']) ? $args['bonus'] : [];

        // default request params
        $this->request_params['token'] = isset($data['token']) ? $data['token'] : null;
        $this->request_params['game_username'] = isset($player['id']) ? strtolower($player['id']) : null;
        $this->request_params['currency'] = isset($player['currency']) ? $player['currency'] : null;
        $this->request_params['transaction_id'] = isset($data['uid']) ? $data['uid'] : null;
        $this->request_params['round_id'] = isset($args['round_id']) ? $args['round_id'] : null;
        $this->request_params['game_code'] = isset($data['game_id']) ? $data['game_id'] : null;

        $this->extra_params['bet_amount'] = $this->request_params['bet_amount'] = !empty($args['bet']) ? $args['bet'] : 0;
        $this->extra_params['win_amount'] = $this->request_params['win_amount'] = !empty($args['win']) ? $args['win'] : 0;
        $this->request_params['amount'] = $this->request_params['win_amount'] - $this->request_params['bet_amount'];

        if (!empty($this->request_params['bonus'])) {
            $this->request_params['amount'] = $this->request_params['win_amount'];
        }

        if ($this->api_method == self::API_METHOD_ROLLBACK) {
            $this->request_params['amount'] = $this->request_params['bet_amount'];
        }

        // additional
        $this->request_params['provider_id'] = isset($data['provider_id']) ? $data['provider_id'] : null;
        $this->request_params['provider_name'] = isset($data['provider_name']) ? $data['provider_name'] : null;
        $this->request_params['session'] = isset($data['session']) ? $data['session'] : null;
        $this->request_params['game_name'] = isset($data['game_name']) ? $data['game_name'] : null;
        $this->request_params['c_at'] = isset($data['c_at']) ? $data['c_at'] : null;
        $this->request_params['sent_at'] = isset($data['sent_at']) ? $data['sent_at'] : null;
        $this->request_params['tag'] = isset($args['tag']) ? $args['tag'] : null;
        $this->request_params['campaign'] = isset($bonus['campaign']) ? $bonus['campaign'] : null;
        $this->request_params['source'] = isset($bonus['source']) ? $bonus['source'] : null;
        $this->request_params['bonus_id'] = isset($bonus['bonus_id']) ? $bonus['bonus_id'] : null;
        $this->request_params['ext_bonus_id'] = isset($bonus['ext_bonus_id']) ? $bonus['ext_bonus_id'] : null;
        $this->request_params['bonus_type'] = isset($bonus['bonus_type']) ? $bonus['bonus_type'] : null;
        $this->request_params['event'] = isset($bonus['event']) ? $bonus['event'] : null;
        $this->request_params['transaction_uid'] = isset($args['transaction_uid']) ? $args['transaction_uid'] : null;
        $this->request_params['round_finished'] = isset($args['round_finished']) && $args['round_finished'] ? $args['round_finished'] : false;

        $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
            $this->api_method,
            $this->request_params['transaction_id'],
        ]);
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        $this->ssa_external_game_id = isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null;

        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {

            if ($get_player_by == 'token') {
                $this->ssa_set_response(200, self::RESPONSE_INVALID_TOKEN);
                $this->ssa_set_hint('token', 'Invalid Token');
            } else {
                $this->ssa_set_response(500, self::RESPONSE_ERROR_INVALID_PARAMETER, "player id not found");
                $this->ssa_set_hint('game username', "player id not found");
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;
        $this->version = $this->getVersion();

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true) {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_set_response(503, self::RESPONSE_ERROR_PLAYER_BLOCKED);
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
            $this->ssa_set_response(500, self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED);
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
            $this->ssa_set_response(200, self::RESPONSE_FUNDS_EXCEED);
            $this->ssa_set_hint('amount', self::RESPONSE_FUNDS_EXCEED['message']);

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
            $this->ssa_set_response(503, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_set_response(200, self::RESPONSE_FUNDS_EXCEED);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_set_response(503, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        $this->ssa_set_response(503, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN);
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

        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id, $this->request_params['game_code']);
        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);
        $this->ssa_set_game_provider_is_end_round($this->request_params['round_finished']);

        // $before_balance = $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, false, $this->request_params['game_code']);
        $before_balance = $after_balance = $this->player_balance;
        $amount = $this->ssa_operate_amount(abs($this->request_params['amount']), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        // if ($adjustment_type == $this->ssa_decrease) {
            // if negative balance not allowed, check if insufficient
            if (!$allowed_negative_balance && empty($this->request_params['bonus'])) {
                $check_bet_amount = $this->request_params['bet_amount'];

                if ($this->isInsufficientBalance($this->player_balance, $check_bet_amount)) {
                    return false;
                }
            }
        // }

        $data['external_unique_id'] = $this->request_params['external_unique_id'];
        $data['amount'] = $amount;
        $data['before_balance'] = $before_balance;
        $data['after_balance'] = $after_balance;
        $data['wallet_adjustment_status'] = $this->ssa_preserved;
        $data['is_processed'] = false;
        $data['adjustment_type'] = $adjustment_type;

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
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if ($success) {
                        $data['wallet_adjustment_status'] = $this->ssa_decreased;
                        $data['is_processed'] = true;
                    } else {
                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                            $data['wallet_adjustment_status'] = $this->ssa_remote_wallet_decreased;
                            $success = $this->remoteWalletError($data);

                            if ($success) {
                                $data['before_balance'] = $this->afterBalance($after_balance) + $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(503, self::SYSTEM_ERROR_DECREASE_BALANCE);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // increase wallet
                } elseif ($adjustment_type == $this->ssa_increase) {
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if ($success) {
                        $data['wallet_adjustment_status'] = $this->ssa_increased;
                        $data['is_processed'] = true;
                    } else {
                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                            $data['wallet_adjustment_status'] = $this->ssa_remote_wallet_increased;
                            $success = $this->remoteWalletError($data);

                            if ($success) {
                                $data['before_balance'] = $this->afterBalance($after_balance) - $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(503, self::SYSTEM_ERROR_INCREASE_BALANCE);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_set_response(503, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT);

                    return false;
                }
            }

            if ($success && $data['before_balance'] != $data['after_balance']) {
                $this->version = $this->ssa_get_timeticks(true);
            }

            if (!empty($this->remote_wallet_status)) {
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $data);
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_set_response(503, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA);

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
            'brand' => $this->brand,
            'version' => $this->version,
            'provider_id' => isset($this->request_params['provider_id']) ? $this->request_params['provider_id'] : null,
            'provider_name' => isset($this->request_params['provider_name']) ? $this->request_params['provider_name'] : null,
            'session' => isset($this->request_params['session']) ? $this->request_params['session'] : null,
            'game_name' => isset($this->request_params['game_name']) ? $this->request_params['game_name'] : null,
            'c_at' => isset($this->request_params['c_at']) ? $this->request_params['c_at'] : null,
            'sent_at' => isset($this->request_params['sent_at']) ? $this->request_params['sent_at'] : null,
            'tag' => isset($this->request_params['tag']) ? $this->request_params['tag'] : null,
            'campaign' => isset($this->request_params['campaign']) ? $this->request_params['campaign'] : null,
            'source' => isset($this->request_params['source']) ? $this->request_params['source'] : null,
            'bonus_id' => isset($this->request_params['bonus_id']) ? $this->request_params['bonus_id'] : null,
            'ext_bonus_id' => isset($this->request_params['ext_bonus_id']) ? $this->request_params['ext_bonus_id'] : null,
            'bonus_type' => isset($this->request_params['bonus_type']) ? $this->request_params['bonus_type'] : null,
            'event' => isset($this->request_params['event']) ? $this->request_params['event'] : null,
            'transaction_uid' => isset($this->request_params['transaction_uid']) ? $this->request_params['transaction_uid'] : null,

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
            case self::API_METHOD_TRANSACTION:
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
            $data['call_count'] = 1;

            // additional
            $data['brand'] = $this->brand;
            $data['provider_id'] = isset($this->request_params['provider_id']) ? $this->request_params['provider_id'] : null;
            $data['provider_name'] = isset($this->request_params['provider_name']) ? $this->request_params['provider_name'] : null;
            $data['session'] = isset($this->request_params['session']) ? $this->request_params['session'] : null;
            $data['game_name'] = isset($this->request_params['game_name']) ? $this->request_params['game_name'] : null;
            $data['tag'] = isset($this->request_params['tag']) ? $this->request_params['tag'] : null;
            $data['campaign'] = isset($this->request_params['campaign']) ? $this->request_params['campaign'] : null;
            $data['source'] = isset($this->request_params['source']) ? $this->request_params['source'] : null;
            $data['bonus_id'] = isset($this->request_params['bonus_id']) ? $this->request_params['bonus_id'] : null;
            $data['ext_bonus_id'] = isset($this->request_params['ext_bonus_id']) ? $this->request_params['ext_bonus_id'] : null;
            $data['bonus_type'] = isset($this->request_params['bonus_type']) ? $this->request_params['bonus_type'] : null;
            $data['event'] = isset($this->request_params['event']) ? $this->request_params['event'] : null;
            $data['transaction_uid'] = isset($this->request_params['transaction_uid']) ? $this->request_params['transaction_uid'] : null;

            $is_md5_sum_logs_exist = $this->ssa_is_md5_sum_logs_exist($this->game_seamless_service_logs_table, $data['md5_sum']);

            if (!$is_md5_sum_logs_exist) {
                $this->ssa_save_failed_request($this->game_seamless_service_logs_table, $this->ssa_insert, $data);
            } else {
                $call_count = $this->ssa_get_logs_call_count($this->game_seamless_service_logs_table, $data['md5_sum']);
                $this->ssa_save_failed_request($this->game_seamless_service_logs_table, $this->ssa_update, ['call_count' => $call_count += $data['call_count']], 'md5_sum', $data['md5_sum']);
            }
        }
    }

    protected function rebuildOperatorResponse($flag) {
        $operator_response['uid'] = $this->ssa_request_params['uid'];
        $balance = strval($this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name));

        if ($flag == Response_result::FLAG_NORMAL && ($this->ssa_get_operator_response(0)->code === self::RESPONSE_SUCCESS['code'])) {
            if (in_array($this->api_method, [self::API_METHOD_LOGIN])) {
                $operator_response['player'] = [
                    'id' => $this->player_details['game_username'],
                    'brand' => $this->brand,
                    'currency' => $this->currency,
                    'mode' => 'REAL',
                    'is_test' => $this->isTest($this->player_details['game_username']),
                ];

                $operator_response['tag'] = '';
            }

            if (!in_array($this->api_method, [self::API_METHOD_LOGOUT])) {
                $operator_response['balance'] = [
                    'value' => $balance,
                    'version' => intval($this->version),
                ];
            }
        } else {
            if (in_array($this->api_method, [self::API_METHOD_TRANSACTION])) {
                if (!empty($this->player_details['game_username'])) {
                    $operator_response['balance'] = [
                        'value' => $balance,
                        'version' => intval($this->version),
                    ];
                }
            }

            $operator_response['error'] = [
                'code' => $this->ssa_get_operator_response(0)->code,
                'message' => $this->ssa_get_operator_response(0)->message,
            ];
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

        if ($this->rebuild_operator_response) {
            $operator_response = $this->rebuildOperatorResponse($flag);
        }

        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id);

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
    public function get_token() {
        $this->ssa_set_response(400, self::SYSTEM_ERROR_BAD_REQUEST);
        $this->rebuild_operator_response = $this->save_data = $success = false;
        $response = [
            'code' => 1,
        ];

        if (!empty($this->ssa_request_params['player_username'])) {
            if (in_array($this->ssa_request_params['player_username'], $this->get_token_test_accounts)) {
                $token = $this->ssa_get_player_common_token_by_player_username($this->ssa_request_params['player_username']);

                if ($this->initializePlayer(false, $this->ssa_subject_type_token, $token, $this->game_platform_id)) {
                    $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token']);

                    $response = [
                        'code' => 0,
                        'token' => $this->player_details['token'],
                    ];

                    $success = true;
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

    protected function isTransactionExists($where = []) {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }

    protected function isTest($game_username) {
        if (in_array($game_username, $this->test_players)) {
            return true;
        } else {
            return false;
        }
    }

    protected function getVersion() {
        $order_by = [
            'column_name' => 'version',
            'direction' => 'desc',
        ];

        $result = $this->ssa_get_transaction($this->transaction_table, [
            'player_id' => $this->player_details['player_id'],
            'brand' => $this->brand,
        ], ['version'], ['field_name' => 'version', 'is_desc' => true]);

        return !empty($result['version']) ? $result['version'] : 0;
    }

    protected function login() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'name' => ['required'],
                'uid' => ['required'],
                'token' => ['required'],
                'session' => ['required'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'provider_id' => ['required'],
                'provider_name' => ['required'],
                'c_at' => ['required'],
                'sent_at' => ['required'],
                'args' => ['required'],
            ];

            if (!empty($this->provider_id)) {
                $rule_sets['provider_id'] = ['required', "expected_value:{$this->provider_id}"];
            }

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::SYSTEM_ERROR_BAD_REQUEST)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->request_params['token'], $this->game_platform_id)) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                        $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true, false, $this->request_params['game_code']);

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
                        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error: login failed');
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(503, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function getbalance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'name' => ['required'],
                'uid' => ['required'],
                'token' => ['required'],
                'session' => ['required'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'provider_id' => ['required'],
                'provider_name' => ['required'],
                'c_at' => ['required'],
                'sent_at' => ['required'],
                'args' => ['required'],
                'args.player.id' => ['required'],
                'args.player.currency' => ['required', "expected_value:{$this->currency}"],
                'args.player.mode' => ['required'],
                'args.player.brand' => ['required', "expected_value:{$this->brand}"],
            ];

            if (!empty($this->provider_id)) {
                $rule_sets['provider_id'] = ['required', "expected_value:{$this->provider_id}"];
            }

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::SYSTEM_ERROR_BAD_REQUEST)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(false, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                        $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true, false, $this->request_params['game_code']);

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
                        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error: get balance failed');
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(503, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function transaction() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'name' => ['required'],
                'uid' => ['required'],
                'token' => ['required'],
                'session' => ['required'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'provider_id' => ['required'],
                'provider_name' => ['required'],
                'c_at' => ['required'],
                'sent_at' => ['required'],
                'args' => ['required'],
                'args.bet' => ['required', 'nullable', 'numeric', 'min:0'],
                'args.win' => ['required', 'nullable', 'numeric', 'min:0'],
                'args.round_id' => ['required'],
                'args.player.id' => ['required'],
                'args.player.currency' => ['required', "expected_value:{$this->currency}"],
                'args.player.mode' => ['required'],
                'args.player.brand' => ['required', "expected_value:{$this->brand}"],
            ];

            if (!empty($this->provider_id)) {
                $rule_sets['provider_id'] = ['required', "expected_value:{$this->provider_id}"];
            }

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::SYSTEM_ERROR_BAD_REQUEST)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = false;
                        $transaction_already_exists = false;
                        $is_processed = false;
                        $save_only = false;
                        $allowed_negative_balance = false;

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                            // check if transaction exists
                            $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                'player_id' => $this->player_details['player_id'],
                                'transaction_id' => $this->request_params['transaction_id'],
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
                                if ($this->request_params['amount'] == 0) {
                                    $this->transaction_type = $adjustment_type = $this->ssa_retain;
                                } else {
                                    if ($this->request_params['amount'] < 0) {
                                        $this->transaction_type = self::DEBIT;
                                        $adjustment_type = $this->ssa_decrease;
                                    } else {
                                        $this->transaction_type = self::CREDIT;
                                        $adjustment_type = $this->ssa_increase;
                                    }
                                }

                                $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                if ($success) {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->version = $get_transaction['version'];
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
                $this->ssa_set_response(503, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function rollback() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'name' => ['required'],
                'uid' => ['required'],
                'token' => ['required'],
                'session' => ['required'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'provider_id' => ['required'],
                'provider_name' => ['required'],
                'c_at' => ['required'],
                'sent_at' => ['required'],
                'args' => ['required'],
                'args.transaction_uid' => ['required'],
                'args.bet' => ['required', 'nullable', 'numeric', 'min:0'],
                'args.win' => ['required', 'nullable', 'numeric', 'min:0'],
                'args.round_id' => ['required'],
                'args.round_finished' => ['optional'],
                'args.player.id' => ['required'],
                'args.player.currency' => ['required', "expected_value:{$this->currency}"],
                'args.player.mode' => ['required'],
                'args.player.brand' => ['required', "expected_value:{$this->brand}"],
            ];

            if (!empty($this->provider_id)) {
                $rule_sets['provider_id'] = ['required', "expected_value:{$this->provider_id}"];
            }

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::SYSTEM_ERROR_BAD_REQUEST)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = false;
                        $transaction_already_exists = false;
                        $is_processed = false;
                        $save_only = false;
                        $allowed_negative_balance = false;

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                            // check if transaction exists
                            $get_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                'player_id' => $this->player_details['player_id'],
                                'transaction_id' => $this->request_params['transaction_id'],
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
                                if ($this->isTransactionExists([
                                    'transaction_id' => $this->request_params['transaction_uid'],
                                ])) {
                                    $adjustment_type = $this->ssa_increase;
                                    $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                    if ($success) {
                                        $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                    }
                                } else {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                    $this->transaction_already_exists = $success = true;
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->version = $get_transaction['version'];
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
                $this->ssa_set_response(503, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function logout() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(503, self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'name' => ['required'],
                'uid' => ['required'],
                'token' => ['required'],
                'session' => ['required'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'provider_id' => ['required'],
                'provider_name' => ['required'],
                'c_at' => ['required'],
                'sent_at' => ['required'],
                'args' => ['required'],
                'args.reason' => ['required'],
                'args.player.id' => ['required'],
                'args.player.currency' => ['required', "expected_value:{$this->currency}"],
                'args.player.mode' => ['required'],
                'args.player.brand' => ['required', "expected_value:{$this->brand}"],
            ];

            if (!empty($this->provider_id)) {
                $rule_sets['provider_id'] = ['required', "expected_value:{$this->provider_id}"];
            }

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::SYSTEM_ERROR_BAD_REQUEST)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(false, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(503, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => isset($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null,
            'round_id' => isset($this->request_params['round_id']) ? $this->request_params['round_id'] : null,
            'external_game_id' => isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $this->api_method,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->ssa_original_request_params),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => isset($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($this->use_remote_wallet_failed_transaction_monthly_table) {
            $year_month = $this->utils->getThisYearMonth();
            $table_name = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$year_month}";
        } else {
            $table_name = $this->ssa_failed_remote_common_seamless_transactions_table;
        }

        if ($this->ssa_is_transaction_exists($table_name, ['external_uniqueid' => $save_data['external_uniqueid']])) {
            $query_type = $this->ssa_update;

            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $save_data['external_uniqueid'],
                ];
            }
        }

        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);
    }
}