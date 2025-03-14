<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Mpoker_seamless_service_api extends BaseController {
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
    const RESPONSE_NO_DATA = [
        'code' => '-1',
        'message' => 'No data',
    ];

    const RESPONSE_SUCCESS = [
        'code' => '0',
        'message' => 'Success',
    ];

    const RESPONSE_CHANNEL_NOT_EXIST = [
        'code' => '2',
        'message' => 'Channel not exist (Please check whether the channel ID is correct)',
    ];

    const RESPONSE_PLAYER_NOT_FOUND = [
        'code' => '11',
        'message' => 'Player not found',
    ];

    const RESPONSE_PROHIBITED_ACCOUNT = [
        'code' => '20',
        'message' => 'The account is prohibited',
    ];

    const RESPONSE_ORDER_NOT_EXIST = [
        'code' => '26',
        'message' => 'Order does not exist',
    ];

    const RESPONSE_IP_ADDRESS_NOT_ALLOWED = [
        'code' => '28',
        'message' => 'INVALID_IP_ADDRESS',
    ];

    const RESPONSE_ORDER_NUMBER_NOT_MATCH_ORDER_RULES = [
        'code' => '29',
        'message' => 'Order number does not match order rules',
    ];

    const RESPONSE_REQUEST_AMOUNT_LESS_THAN_0 = [
        'code' => '31',
        'message' => 'requestAmount is less than 0',
    ];

    const RESPONSE_TRANSACTION_ALREADY_EXISTS = [
        'code' => '34',
        'message' => 'Duplicate Order',
    ];

    const RESPONSE_TOTAL_WITHDRAW_NOT_EQUAL = [
        'code' => '36',
        'message' => 'totalWithdraw not equal to total withdraw amount',
    ];

    const RESPONSE_WALLET_OPERATION_FAILED = [
        'code' => '137',
        'message' => 'Wallet operation failed',
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        'code' => '138',
        'message' => 'Insufficient balance',
    ];

    const RESPONSE_INVALID_ARGUMENTS = [
        'code' => '139',
        'message' => 'Invalid arguments',
    ];

    const RESPONSE_WALLET_REQUEST_TOO_FREQUENT = [
        'code' => '146',
        'message' => 'Wallet request too frequent',
    ];


    # API METHODS HERE
    // default
    const API_METHOD_OPERATOR_ACTION = 'operatorAction';
    // From docs
    const API_METHOD_GET_BALANCE1 = 'getBalance1';
    const API_METHOD_WITHDRAW = 'withdraw';
    const API_METHOD_DEPOSIT = 'deposit';
    const API_METHOD_CANCEL = 'cancel';

    const SUB_API_METHOD_END_ROUND = 'endRound';
    const SUB_API_METHOD_DEPOSIT_END_ROUND = 'deposit-endRound';

    const API_METHODS = [
        self::API_METHOD_OPERATOR_ACTION,
        self::API_METHOD_GET_BALANCE1,
        self::API_METHOD_WITHDRAW,
        self::API_METHOD_DEPOSIT,
        self::API_METHOD_CANCEL,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_WITHDRAW,
        self::API_METHOD_DEPOSIT,
        self::API_METHOD_CANCEL,
    ];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'getToken';
    const ACTION_ENCRYPT_PARAM = 'encryptParam';
    const ACTION_DECRYPT_PARAM = 'decryptParam';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN,
        self::ACTION_ENCRYPT_PARAM,
        self::ACTION_DECRYPT_PARAM,
    ];

    const DEPOSIT_MODE_DEPOSIT = 1;
    const DEPOSIT_MODE_END_ROUND = 2;
    const DEPOSIT_MODE_DEPOSIT_END_ROUND = 3;

    # ADDITIONAL PROPERTIES HERE
    // public $agent;
    protected $is_canceled = false;
    protected $sub_conversion = 100;

    public function __construct() {
        parent::__construct();
        $this->ssa_request_headers = $this->ssa_request_headers();
        //$this->ssa_request_params = $this->ssa_original_request_params = $this->ssa_request_params();
        $this->ssa_success_wallet_actions = [$this->ssa_decreased, $this->ssa_increased, $this->ssa_retained];
        $this->ssa_failed_wallet_actions = [$this->ssa_preserved, $this->ssa_failed];
        $this->ssa_request_params = $_GET;

        $raw_request = $this->ssa_payload();
        if (empty($this->ssa_request_params['param']) && !empty($raw_request)) {
            $this->ssa_request_params['param'] = $raw_request;
        }

        $this->ssa_original_request_params = $this->ssa_request_params;
    }

    public function index($game_platform_id, $api_method, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->api_method = $api_method;
        $this->action = $action = isset($this->ssa_request_params['action_method']) ? $this->ssa_request_params['action_method'] : $action;
        unset($this->ssa_original_request_params['action_method']);

        if ($this->initialize()) {
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    protected function initialize($api_method = null) {
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
            $this->sub_conversion = $this->game_api->sub_conversion;

            if (isset($this->ssa_request_params['param']) && !is_array($this->ssa_request_params['param'])) {
                if ($this->utils->isUrlencoded($this->ssa_request_params['param'])) {
                    $param = rawurldecode($this->ssa_request_params['param']);
                } else {
                    $param = rawurldecode(urlencode($this->ssa_request_params['param']));
                }

                $this->ssa_request_params['param'] = json_decode($this->game_api->decrypt($param), true);
            }
        } else {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_INITIALIZE_GAME_API['message']);
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::API_METHODS)) {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            return false;
        }

        $this->ssa_dev_authorization($this->game_api->dev_auth_username, $this->game_api->dev_auth_password);

        if (isset($this->ssa_request_params['signature']) && $this->ssa_header_x_check_signature == 1) {
            $param = $this->ssa_original_request_params['param'];
            $timestamp = $this->ssa_request_params['timestamp'];

            $signature = $this->game_api->generateSignature($param, $timestamp);

            if ($this->ssa_request_params['signature'] != $signature) {
                $this->ssa_set_response(401, self::RESPONSE_INVALID_ARGUMENTS, 'Invalid signature');
                $this->ssa_set_hint(self::RESPONSE_INVALID_ARGUMENTS['code'], 'Invalid signature: correct signature: ' .$signature);
                return false;
            }
        }

        return true;
    }

    protected function operatorConfig() {
        $this->transfer_type_api_methods = self::TRANSFER_TYPE_API_METHODS;
        $this->content_type = $this->ssa_content_type_application_json;
        $this->content_type_json_api_methods = [];
        $this->content_type_xml_api_methods = [];
        $this->save_data_api_methods = self::API_METHODS;
        $this->allowed_negative_balance_api_methods = [];

        $this->encrypt_response_api_methods = $this->content_type_plain_text_api_methods = [
            self::API_METHOD_GET_BALANCE1,
            self::API_METHOD_WITHDRAW,
            self::API_METHOD_DEPOSIT,
            self::API_METHOD_CANCEL,
        ];

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

        if (in_array($action, [self::ACTION_GET_TOKEN, self::ACTION_ENCRYPT_PARAM, self::ACTION_DECRYPT_PARAM])) {
            return $this->$action();
        }

        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
        $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
        return $this->response();
    }

    protected function rebuildRequestParams($data = null) {
        $this->request_params = $this->ssa_request_params;

        if (empty($data)) {
            $data = $this->request_params;
        }

        $param = !empty($data['param']) ? $data['param'] : [];

        // default request params
        $this->request_params['token'] = isset($data['token']) ? $data['token'] : null;
        $this->request_params['game_username'] = isset($param['playerId']) ? $param['playerId'] : null;
        $this->request_params['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->request_params['transaction_id'] = isset($param['orderId']) ? strval($param['orderId']) : null;
        $this->request_params['round_id'] = isset($param['roundId']) ? strval($param['roundId']) : null;
        $this->request_params['game_code'] = isset($param['gameId']) ? $param['gameId'] : null;
        $this->request_params['amount'] = isset($param['requestAmount']) ? $param['requestAmount'] : 0;
        $this->request_params['bet_transaction_id'] = $this->request_params['round_id'];
        $this->request_params['reference_transaction_id'] = $this->request_params['round_id'];

        if ($this->api_method == self::API_METHOD_CANCEL) {
            $this->request_params['reference_transaction_id'] = $this->request_params['transaction_id'];
        }

        // additional params
        $this->request_params['channel_id'] = isset($data['channelId']) ? $data['channelId'] : null;
        $this->request_params['total_bet'] = !empty($param['totalBet']) ? $param['totalBet'] / $this->sub_conversion : 0;
        $this->request_params['valid_bet_amount'] = !empty($param['validBet']) ? $param['validBet'] / $this->sub_conversion : 0;
        $this->request_params['total_withdraw'] = !empty($param['totalWithdraw']) ? $param['totalWithdraw'] / $this->sub_conversion : 0;
        $this->request_params['revenue'] = !empty($param['revenue']) ? $param['revenue'] / $this->sub_conversion : 0;
        $this->request_params['bet_count'] = isset($param['betCount']) ? $param['betCount'] : null;
        $this->request_params['deposit_mode'] = isset($param['depositMode']) ? $param['depositMode'] : null;
        $this->request_params['timestamp'] = isset($data['timestamp']) ? $data['timestamp'] : null;

        if ($this->api_method == self::API_METHOD_DEPOSIT) {
            if ($this->request_params['deposit_mode'] == self::DEPOSIT_MODE_END_ROUND) {
                $this->api_method = self::SUB_API_METHOD_END_ROUND;
            }

            if ($this->request_params['deposit_mode'] == self::DEPOSIT_MODE_DEPOSIT_END_ROUND) {
                $this->api_method = self::SUB_API_METHOD_DEPOSIT_END_ROUND;
            }
        }

        $external_id = [
            $this->api_method,
            $this->request_params['transaction_id'],
        ];

        $this->request_params['external_transaction_id'] = $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues($external_id);
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        $this->ssa_external_game_id = isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null;

        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {
            if ($get_player_by == 'token') {
                $this->ssa_set_response(400, self::RESPONSE_PLAYER_NOT_FOUND);
                $this->ssa_set_hint('token', self::RESPONSE_PLAYER_NOT_FOUND['message']);
            } else {
                $this->ssa_set_response(400, self::RESPONSE_PLAYER_NOT_FOUND);
                $this->ssa_set_hint('user', self::RESPONSE_PLAYER_NOT_FOUND['message']);
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true) {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_set_response(500, self::RESPONSE_PROHIBITED_ACCOUNT);
            $this->ssa_set_hint('blocked', self::RESPONSE_PROHIBITED_ACCOUNT['message']);

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
            $this->ssa_set_response(500, self::RESPONSE_IP_ADDRESS_NOT_ALLOWED, self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED['message']);
            $this->ssa_set_hint('ipaddress', $this->ssa_get_ip_address());
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::RESPONSE_ERROR_GAME_DISABLED['message']);
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::RESPONSE_ERROR_GAME_MAINTENANCE['message']);
            return false;
        }

        // additional

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_set_response(400, self::RESPONSE_INSUFFICIENT_BALANCE);
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
        return !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, false, $this->request_params['game_code']);
    }

    protected function remoteWalletError(&$data) {
        // treat success if remote wallet return double uniqueid
        if ($this->ssa_remote_wallet_error_double_unique_id()) {
            return $data['is_processed'] = true;
        }

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_set_response(400, self::RESPONSE_INSUFFICIENT_BALANCE);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message']);
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
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);
        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);

        $before_balance = $after_balance = $this->player_balance;
        $amount = $this->ssa_operate_amount(abs($this->request_params['amount']), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        if ($amount == 0 && in_array($this->api_method, [self::API_METHOD_DEPOSIT])) {
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

        $data['transaction_id'] = $this->request_params['transaction_id'];
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
                                $data['update_before_balance'] = true;
                                $data['before_balance'] = $this->afterBalance($after_balance) + $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_DECREASE_BALANCE['message']);
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
                                $data['update_before_balance'] = true;
                                $data['before_balance'] = $this->afterBalance($after_balance) - $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_INCREASE_BALANCE['message']);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                    // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message']);

                    return false;
                }
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message']);

            return false;
        }
    }

    protected function rebuildTransactionRequestData($data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $extra_info = [
            'decrypted_request_param' => $this->ssa_request_params,
        ];

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

            // addtional
            'channel_id' => isset($this->request_params['channel_id']) ? $this->request_params['channel_id'] : $this->game_api->agent,
            'total_bet' => isset($this->request_params['total_bet']) ? $this->request_params['total_bet'] : null,
            'total_withdraw' => isset($this->request_params['total_withdraw']) ? $this->request_params['total_withdraw'] : null,
            'revenue' => isset($this->request_params['revenue']) ? $this->request_params['revenue'] : null,
            'bet_count' => isset($this->request_params['bet_count']) ? $this->request_params['bet_count'] : null,
            'deposit_mode' => isset($this->request_params['deposit_mode']) ? $this->request_params['deposit_mode'] : null,
            'timestamp' => isset($this->request_params['timestamp']) ? $this->request_params['timestamp'] : null,

            // default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => !empty($this->ssa_original_request_params) && is_array($this->ssa_original_request_params) ? json_encode($this->ssa_original_request_params) : null,
            'response' => null,
            'extra_info' => !empty($extra_info) && is_array($extra_info) ? json_encode($extra_info) : null,
            'bet_amount' => isset($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : 0,
            'valid_bet_amount' => isset($this->request_params['valid_bet_amount']) ? $this->request_params['valid_bet_amount'] : 0,
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
            case self::API_METHOD_DEPOSIT:
            case self::SUB_API_METHOD_END_ROUND:
            case self::SUB_API_METHOD_DEPOSIT_END_ROUND:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_CANCEL:
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
                    $transaction_id = isset($data['transaction_id']) ? $data['transaction_id'] : null;
                    $external_unique_id = isset($data['external_unique_id']) ? $data['external_unique_id'] : null;
                    $wallet_adjustment_status = isset($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : $this->ssa_preserved;
                    $amount = isset($data['amount']) ? $data['amount'] : 0;
                    $before_balance = isset($data['before_balance']) ? $data['before_balance'] : 0;
                    $after_balance = isset($data['after_balance']) ? $data['after_balance'] : 0;
                    $is_processed = isset($data['is_processed']) ? $data['is_processed'] : false;
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_get_operator_response());
                    $update_before_balance = isset($data['update_before_balance']) && $data['update_before_balance'];

                    if (!empty($external_unique_id)) {
                        $update_data = [
                            'wallet_adjustment_status' => $wallet_adjustment_status,
                            'amount' => $amount,
                            'after_balance' => $after_balance,
                            'response' => $operator_response,
                            'is_processed' => $is_processed,
                            'response_result_id' => $this->response_result_id,

                            // additional
                        ];

                        if ($update_before_balance) {
                            $update_data['before_balance'] = $before_balance;
                        }

                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $external_unique_id]);

                            if (!$is_exist) {
                                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, ['external_unique_id' => $external_unique_id]);
                            }

                            if ($is_exist) {
                                $this->ssa_update_transaction_without_result($this->transaction_table, $update_data, 'external_unique_id', $external_unique_id);
                            }

                            if ($this->api_method == self::API_METHOD_CANCEL) {
                                $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
                                    'api_method' => self::API_METHOD_WITHDRAW,
                                    'transaction_id' => $transaction_id,
                                ]);

                                if (!$is_exist) {
                                    $is_exist = $this->ssa_is_transaction_exists($this->previous_table, [
                                        'api_method' => self::API_METHOD_WITHDRAW,
                                        'transaction_id' => $transaction_id,
                                    ]);
                                }

                                if ($is_exist) {
                                    $this->ssa_update_transaction_without_result($this->transaction_table, ['status' => Game_logs::STATUS_REFUND], 'transaction_id', $transaction_id);
                                }
                            }
                        } else {
                            $this->ssa_update_transaction_without_result($this->transaction_table, $update_data, 'external_unique_id', $external_unique_id);

                            if ($this->api_method == self::API_METHOD_CANCEL) {
                                $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
                                    'api_method' => self::API_METHOD_WITHDRAW,
                                    'transaction_id' => $transaction_id,
                                ]);

                                if ($is_exist) {
                                    $this->ssa_update_transaction_without_result($this->transaction_table, ['status' => Game_logs::STATUS_REFUND], 'transaction_id', $transaction_id);
                                }
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
            $http_response = $this->ssa_get_http_response($this->ssa_get_http_response_status_code());
            $code = isset($this->ssa_get_operator_response(0)->code) ? $this->ssa_get_operator_response(0)->code : self::RESPONSE_SUCCESS['code'];
            $message = isset($this->ssa_get_operator_response(0)->message) ? $this->ssa_get_operator_response(0)->message : self::RESPONSE_SUCCESS['message'];
            $flag = $this->ssa_get_http_response_status_code() == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_get_operator_response());

            $extra_info = [
                'decrypted_request_param' => $this->ssa_request_params,
            ];

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
                'request' => !empty($this->ssa_original_request_params) ? json_encode($this->ssa_original_request_params) : null,
                'response' => $operator_response,
                'extra_info' => json_encode($extra_info),
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
                'external_unique_id' => !empty($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
            ];

            unset($md5_data['request'], $md5_data['response'], $md5_data['extra_info']);

            $data['md5_sum'] = md5(json_encode($md5_data));
            $data['elapsed_time'] = $this->utils->getCostMs();
            $data['response_result_id'] = $this->response_result_id;
            $data['transaction_id'] = !empty($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null;
            $data['bet_transaction_id'] = !empty($this->request_params['bet_transaction_id']) ? $this->request_params['bet_transaction_id'] : null;
            $data['reference_transaction_id'] = !empty($this->request_params['reference_transaction_id']) ? $this->request_params['reference_transaction_id'] : null;
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
        if (!in_array($this->action, [self::ACTION_GET_TOKEN])) {
            $operator_response = [];

            if ($flag == Response_result::FLAG_NORMAL && $this->ssa_get_operator_response(0)->code == self::RESPONSE_SUCCESS['code']) {
                if ($this->is_canceled) {
                    $operator_response['isCanceled'] = $this->is_canceled;
                }

                if (!empty($this->player_details['external_account_id'])) {
                     $account = $this->player_details['external_account_id'];
                } else {
                    if (!empty($this->player_details['game_username'])) {
                        $account = $this->game_api->account_prefix . $this->player_details['game_username'];
                    }
                }

                $operator_response['channelId'] = $this->game_api->agent;
                $operator_response['account'] = $account;
                $operator_response['balance'] = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
            } else {
                $operator_response['code'] = intval($this->ssa_get_operator_response(0)->code);
                $operator_response['message'] = $this->ssa_get_operator_response(0)->message;

                if ($this->ssa_get_operator_response(0)->code == self::RESPONSE_TRANSACTION_ALREADY_EXISTS['code']) {
                    $operator_response['balance'] = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
                }

                if ($this->api_method == self::API_METHOD_CANCEL && $this->ssa_get_operator_response(0)->code == self::RESPONSE_ORDER_NOT_EXIST['code']) {
                    $operator_response['balance'] = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
                }
            }
        }

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
                    case self::ACTION_DECRYPT_PARAM:
                        $this->content_type = $this->ssa_content_type_application_json;
                        $this->encrypt_response = false;
                        break;
                    default:
                        break;
                }
            }
        }

        if ($this->ssa_header_x_show_hint == 1) {
            if (!empty($this->ssa_hint)) {
                $operator_response['hint_check'] = $this->ssa_hint;
            }
        }

        if ($this->encrypt_response) {
            if ($this->ssa_header_x_decrypt_response == 1) {
                $this->content_type = $this->ssa_content_type_application_json;
            } else {
                $operator_response = $this->game_api->encrypt($operator_response);

                if ($this->ssa_get_http_response_status_code() == 401) {
                    $operator_response = null;
                }
            }
        }

        $this->rebuilded_operator_response = $operator_response;

        return $operator_response;
    }

    protected function rebuildHttpStatusCodeResponse() {
        $http_response_status_code = $this->ssa_get_http_response_status_code();

        if (in_array($this->ssa_get_operator_response(0)->code, [
            self::RESPONSE_REQUEST_AMOUNT_LESS_THAN_0['code'],
            self::RESPONSE_PLAYER_NOT_FOUND['code'],
            self::RESPONSE_TRANSACTION_ALREADY_EXISTS['code'],
            self::RESPONSE_INSUFFICIENT_BALANCE['code'],
            self::RESPONSE_WALLET_REQUEST_TOO_FREQUENT['code'],
            self::RESPONSE_TOTAL_WITHDRAW_NOT_EQUAL['code'],
        ])) {
            $http_response_status_code = 400;
        }

        return $http_response_status_code;
    }

    protected function response() {
        $http_response_status_code = $this->ssa_set_http_response_status_code($this->rebuildHttpStatusCodeResponse());
        $operator_response = $this->ssa_get_operator_response();
        $flag = $operator_response['code'] == self::RESPONSE_SUCCESS['code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;

        $extra = [
            'raw_request' => $this->ssa_original_request_params,
        ];

        if ($this->rebuild_operator_response) {
            $operator_response = $this->rebuildOperatorResponse($flag, $operator_response);
        }

        if (is_string($operator_response)) {
            if ($this->utils->isUrlencoded($operator_response)) {
                $param = rawurldecode($operator_response);
            } else {
                $param = $operator_response;
            }

            $extra['decrypted_response'] = json_decode($this->game_api->decrypt($param), true);
        }

        unset($this->ssa_original_request_params['action_method']);
        $save_request = $this->ssa_original_request_params;
        $save_request['decrypted_param'] = $this->ssa_request_params;

        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $save_request, $operator_response, $http_response, $player_id, $extra);

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

    protected function encryptParam() {
        $this->api_method = __FUNCTION__;
        $this->ssa_set_response(400, self::SYSTEM_ERROR_BAD_REQUEST);
        $this->rebuild_operator_response = $this->save_data = $success = false;
        $this->ssa_original_request_params = $this->ssa_request_params();

        $response = [
            'code' => 1,
        ];

        if (!empty($this->ssa_original_request_params['param'])) {
            $response = [
                'code' => 0,
                'param' => $this->game_api->encrypt(json_encode($this->ssa_original_request_params['param'])),
            ];

            $success = true;
        } else {
            $response['message'] = 'Required param';
        }

        if ($success) {
            $this->ssa_set_http_response_status_code(200);
        }

        $this->ssa_set_operator_response($response);

        return $this->response();
    }

    protected function decryptParam() {
        $this->api_method = __FUNCTION__;
        $this->ssa_set_response(400, self::SYSTEM_ERROR_BAD_REQUEST);
        $this->rebuild_operator_response = $this->save_data = $success = false;
        $this->ssa_original_request_params = $this->ssa_request_params();

        $response = [
            'code' => 1,
        ];

        if (!empty($this->ssa_original_request_params['param'])) {
            if ($this->utils->isUrlencoded($this->ssa_original_request_params['param'])) {
                $param = rawurldecode($this->ssa_original_request_params['param']);
            } else {
                $param = $this->ssa_original_request_params['param'];
            }

            $response = [
                'code' => 0,
                'param' => json_decode($this->game_api->decrypt($param)),
            ];

            $success = true;
        } else {
            $response['message'] = 'Required param';
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

    protected function getTransaction($where = [], $selected_columns = [], $order_by = ['field_name' => '', 'is_desc' => false]) {
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

    protected function isTransactionExists($where = []) {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }

    protected function getBalance1() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'param' => ['required'],
                'param.account' => ['required'],
                'param.playerId' => ['required'],
                'param.channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'signature' => ['required'],
                'timestamp' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_INVALID_ARGUMENTS)) {
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
                        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'Internal Server Error: ' . __METHOD__ . ' failed');
                    }
                }
            } else {
                $hint_message = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response : $this->ssa_hint[$this->ssa_request_param_key];
                $this->ssa_set_response(401, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function withdraw() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->is_end_round = false;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'param' => ['required'],
                'param.channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'param.playerId' => ['required'],
                'param.account' => ['required'],
                'param.requestAmount' => ['required', 'nullable', 'numeric', 'positive'],
                'param.gameId' => ['required'],
                'param.roundId' => ['required'],
                'param.gameNo' => ['optional'],
                'param.orderId' => ['required'],
                'channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'signature' => ['required'],
                'timestamp' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [
                'param.requestAmount' => [
                    'positive' => [
                        'http_response_status_code' => 400,
                        'operator_response' => self::RESPONSE_REQUEST_AMOUNT_LESS_THAN_0,
                    ],
                ],
            ], self::RESPONSE_INVALID_ARGUMENTS)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                            $success = false;
                            $transaction_already_exists = false;
                            $is_processed = false;
                            $save_only = false;
                            $allowed_negative_balance = false;

                            // check if transaction exists
                            $get_transaction = $this->getTransaction([
                                'external_unique_id' => $this->request_params['external_transaction_id'],
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
                                $success = false;
                                $this->ssa_set_response(400, self::RESPONSE_TRANSACTION_ALREADY_EXISTS);
                                $this->transaction_already_exists = true;
                                $this->player_balance = $get_transaction['after_balance'];
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
                $this->ssa_set_response(401, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function deposit() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'param' => ['required'],
                'param.channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'param.playerId' => ['required'],
                'param.account' => ['required'],
                'param.requestAmount' => ['required', 'nullable', 'numeric', 'positive'],
                'param.gameId' => ['required'],
                'param.roundId' => ['required'],
                'param.gameNo' => ['optional'],
                'param.orderId' => ['required'],
                'channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'signature' => ['required'],
                'timestamp' => ['required'],

                // required fields, ask GP to enable.
                /* 'param.totalBet' => ['required'],
                'param.validBet' => ['required'],
                'param.betCount' => ['required'],
                'param.totalWithdraw' => ['required'],
                'param.revenue' => ['required'],
                'param.depositMode' => ['required'], */
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [
                'param.requestAmount' => [
                    'positive' => [
                        'http_response_status_code' => 400,
                        'operator_response' => self::RESPONSE_REQUEST_AMOUNT_LESS_THAN_0,
                    ],
                ],
            ], self::RESPONSE_INVALID_ARGUMENTS)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                            $success = false;
                            $transaction_already_exists = false;
                            $is_processed = false;
                            $save_only = false;
                            $allowed_negative_balance = false;

                            // check if transaction exists
                            $get_transaction = $this->getTransaction([
                                'external_unique_id' => $this->request_params['external_transaction_id'],
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

                            if ($this->request_params['deposit_mode'] == self::DEPOSIT_MODE_END_ROUND) {
                                $save_only = true;
                            }

                            if (!$is_processed) {
                                if ($this->request_params['deposit_mode'] == self::DEPOSIT_MODE_DEPOSIT_END_ROUND) {
                                    $is_processed = $this->isTransactionExists([
                                        'api_method' => self::API_METHOD_DEPOSIT,
                                        'game_username' => $this->player_details['game_username'],
                                        'transaction_id' => $this->request_params['transaction_id'],
                                        'deposit_mode' => self::DEPOSIT_MODE_DEPOSIT,
                                    ]);

                                    if ($is_processed) {
                                        $save_only = true;
                                    }
                                }

                                $bet_transactions = $this->getTransactions([
                                    'api_method' => self::API_METHOD_WITHDRAW,
                                    'game_username' => $this->player_details['game_username'],
                                    'round_id' => $this->request_params['round_id'],
                                ]);

                                if (!empty($bet_transactions)) {
                                    $bet_count = count($bet_transactions);
                                    $valid_bet_count = 0;
                                    $cancel_count = 0;
                                    $total_withdraw = 0;
                                    $is_total_withdraw_equal = true;
                                    $is_valid_bet_count_equal = true;

                                    foreach ($bet_transactions as $bet_transaction) {
                                        if ($bet_transaction['status'] != 7) {
                                            $valid_bet_count++;
                                            $total_withdraw += $bet_transaction['amount'];
                                        } else {
                                            $cancel_count++;
                                        }
                                    }

                                    $is_canceled = $bet_count == $cancel_count;

                                    if (!$is_canceled) {
                                        if (in_array($this->request_params['deposit_mode'], [self::DEPOSIT_MODE_DEPOSIT_END_ROUND])) {
                                            if ($this->request_params['bet_count'] != $valid_bet_count) {
                                                $is_valid_bet_count_equal = false;
                                            }

                                            if ($total_withdraw != $this->request_params['total_withdraw']) {
                                                $is_total_withdraw_equal = false;
                                            }
                                        }

                                        if ($is_total_withdraw_equal) {
                                            $this->transaction_type = self::CREDIT;
                                            $adjustment_type = $this->ssa_increase;
                                            $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                            if ($success) {
                                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                            }
                                        } else {
                                            $this->ssa_set_response(400, self::RESPONSE_TOTAL_WITHDRAW_NOT_EQUAL);
                                        }
                                    } else {
                                        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'The transaction has already been canceled');
                                    }
                                } else {
                                    $this->ssa_set_response(500, self::RESPONSE_ORDER_NOT_EXIST, 'Bet not exist');
                                }
                            } else {
                                $success = false;
                                $this->ssa_set_response(400, self::RESPONSE_TRANSACTION_ALREADY_EXISTS);
                                $this->transaction_already_exists = true;
                                $this->player_balance = $get_transaction['after_balance'];
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
                $this->ssa_set_response(401, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }

    protected function cancel() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $this->is_end_round = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'param' => ['required'],
                'param.channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'param.playerId' => ['required'],
                'param.account' => ['required'],
                'param.gameId' => ['optional'],
                'param.roundId' => ['required'],
                'param.gameNo' => ['optional'],
                'param.orderId' => ['required'],
                'channelId' => ['required', 'expected_value' => $this->game_api->agent],
                'signature' => ['required'],
                'timestamp' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_INVALID_ARGUMENTS)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->request_params['game_username'], $this->game_platform_id)) {
                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                            $success = false;
                            $transaction_already_exists = false;
                            $is_processed = false;
                            $save_only = false;
                            $allowed_negative_balance = false;

                            // check if transaction exists
                            $get_transaction = $this->getTransaction([
                                'external_unique_id' => $this->request_params['external_transaction_id'],
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
                                $bet_transaction = $this->getTransaction([
                                    'api_method' => self::API_METHOD_WITHDRAW,
                                    'transaction_id' => $this->request_params['transaction_id'],
                                ]);

                                $this->request_params['game_code'] = isset($bet_transaction['game_code']) ? $bet_transaction['game_code'] : null;
                                $amount = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                                $this->request_params['amount'] = $this->ssa_operate_amount($amount, $this->precision, $this->conversion, $this->arithmetic_name);

                                if ($bet_transaction) {
                                    $is_already_settled = $this->isTransactionExists("(api_method IN ('deposit', 'endRound', 'deposit-endRound') AND game_username='{$this->player_details['game_username']}' AND round_id='{$this->request_params['round_id']}')");
    
                                    if (!$is_already_settled) {
                                        $this->transaction_type = self::CREDIT;
                                        $adjustment_type = $this->ssa_increase;
                                        $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                        if ($success) {
                                            $this->is_canceled = true;
                                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                        }
                                    } else {
                                        $this->ssa_set_response(500, self::RESPONSE_WALLET_OPERATION_FAILED, 'The transaction has already been settled');
                                    }
                                } else {
                                    $this->ssa_set_response(500, self::RESPONSE_ORDER_NOT_EXIST, 'Bet not exist');
                                }
                            } else {
                                $success = false;
                                $this->ssa_set_response(400, self::RESPONSE_TRANSACTION_ALREADY_EXISTS);
                                $this->transaction_already_exists = true;
                                $this->player_balance = $get_transaction['after_balance'];
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
                $this->ssa_set_response(401, $this->ssa_get_operator_response(), $this->ssa_custom_message_response);
                $this->ssa_set_hint($this->ssa_request_param_key, $hint_message);
            }
        }

        return $this->response();
    }
}
