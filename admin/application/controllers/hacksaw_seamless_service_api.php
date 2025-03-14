<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Hacksaw_seamless_service_api extends BaseController {
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
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;

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
    protected $seamless_service_unique_id_with_game_prefix  = null;
    protected $game_provider_action_type = null;
    protected $external_game_id = null;
    protected $game_provider_related_action_type = null;
    protected $seamless_service_related_unique_id = null;
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

    const RESPONSE_GENERAL_ERROR = [
        'code' => 1,
        'message' => 'General / Server error',
    ];

    const RESPONSE_INVALID_USER = [
        'code' => 2,
        'message' => 'Invalid user / token expired',
    ];

    const RESPONSE_INVALID_CURRENCY = [
        'code' => 3,
        'message' => 'Invalid currency for user',
    ];

    const RESPONSE_INVALID_PARTNER_CODE = [
        'code' => 4,
        'message' => 'Invalid partner code',
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        'code' => 5,
        'message' => 'Insufficient funds to place bet',
    ];

    const RESPONSE_ACCOUNT_LOCKED = [
        'code' => 6,
        'message' => 'Account locked',
    ];

    const RESPONSE_ACCOUNT_DISABLED = [
        'code' => 7,
        'message' => 'Account disabled',
    ];

    const RESPONSE_GAMBLING_LIMIT_EXCEEDED = [
        'code' => 8,
        'message' => 'Gambling limit exceeded (Loss limit or betting limit)',
    ];

    const RESPONSE_TIME_LIMIT_EXEEDED = [
        'code' => 9,
        'message' => 'Time limit exceeded',
    ];

    const RESPONSE_SESSION_TIMEOUT = [
        'code' => 10,
        'message' => 'Session timeout or invalid session id',
    ];

    const RESPONSE_GENERAL_ERROR_NO_ROLLBACK = [
        'code' => 11,
        'message' => 'General Error',
    ];

    const RESPONSE_INVALID_ACTION = [
        'code' => 12,
        'message' => 'Invalid action',
    ];

    # API METHODS HERE
    const API_METHOD_AUTHENTICATE = 'Authenticate';
    const API_METHOD_BALANCE = 'Balance';
    const API_METHOD_BET = 'Bet';
    const API_METHOD_WIN = 'Win';
    const API_METHOD_ROLLBACK = 'Rollback';
    const API_METHOD_END_SESSION = 'EndSession';
    const API_METHOD_PROMO_PAYOUT = 'PromoPayout';

    const API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_WIN,
        self::API_METHOD_ROLLBACK,
        self::API_METHOD_END_SESSION,
        self::API_METHOD_PROMO_PAYOUT,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_BET,
        self::API_METHOD_WIN,
        self::API_METHOD_ROLLBACK,
        self::API_METHOD_PROMO_PAYOUT,
    ];

    # ACTIONS HERE
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'get_token';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN
    ];

    # ADDITIONAL PROPERTIES HERE
    protected $secret_key;
    protected $api_username;
    protected $api_password;

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
    }

    public function index($game_platform_id, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->api_method = $api_method = isset($this->ssa_request_params['action']) ? $this->ssa_request_params['action'] : null;
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
            $this->use_remote_wallet_failed_transaction_monthly_table = $this->game_api->use_remote_wallet_failed_transaction_monthly_table;

            // additional
            $this->secret_key = $this->game_api->secret_key;
            $this->api_username = $this->game_api->api_username;
            $this->api_password = $this->game_api->api_password;
        } else {
            $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_INITIALIZE_GAME_API['message']);
            return false;
        }

        if ($this->action == self::ACTION_GET_TOKEN) {
            return true;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $this->api_method)) {
            $this->ssa_set_response(404, self::RESPONSE_INVALID_ACTION);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message']);
            return false;
        }

        if ($this->ssa_is_api_method_allowed($this->api_method, self::API_METHODS)) {
            $this->ssa_set_response(404, self::RESPONSE_INVALID_ACTION);
            $this->ssa_set_hint(self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code'], self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message']);
            return false;
        }

        $request_headers = $this->ssa_request_headers();

        if (isset($request_headers['Authorization'])) {
            if (!$this->ssa_validate_basic_auth_request($this->api_username, $this->api_password)) {
                $this->ssa_set_response(404, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Invalid Authorization');
                $this->ssa_set_hint('Authorization', 'Check api_username and api_password');
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

    protected function rebuildRequestParams($data = null) {
        $this->request_params = $this->ssa_request_params;

        if (empty($data)) {
            $data = $this->request_params;
        }

        // request params
        $this->request_params['token'] = isset($data['token']) ? $data['token'] : null;
        $this->request_params['game_username'] = isset($data['externalPlayerId']) ? strtolower($data['externalPlayerId']) : null;
        $this->request_params['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $this->request_params['transaction_id'] = isset($data['transactionId']) ? $data['transactionId'] : null;
        $this->request_params['round_id'] = isset($data['roundId']) ? $data['roundId'] : null;
        $this->request_params['game_code'] = isset($data['gameId']) ? $data['gameId'] : null;
        $this->request_params['amount'] = !empty($data['amount']) ? $data['amount'] : 0;

        $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
            $this->api_method,
            $this->request_params['transaction_id'],
        ]);

        $this->request_params['game_session_id'] = isset($data['gameSessionId']) ? $data['gameSessionId'] : null;
        $this->request_params['external_session_id'] = isset($data['externalSessionId']) ? $data['externalSessionId'] : null;
        $this->request_params['bought_bonus'] = isset($data['boughtBonus']) ? $data['boughtBonus'] : null;
        $this->request_params['base_bet_level'] = isset($data['baseBetLevel']) ? $data['baseBetLevel'] : null;
        $this->request_params['free_round_data'] = isset($data['freeRoundData']) ? $data['freeRoundData'] : null;
        $this->request_params['free_round_activation_id'] = isset($data['freeRoundData']['freeRoundActivationId']) ? $data['freeRoundData']['freeRoundActivationId'] : null;
        $this->request_params['campaign_id'] = isset($data['freeRoundData']['campaignId']) ? $data['freeRoundData']['campaignId'] : null;
        $this->request_params['offer_id'] = isset($data['freeRoundData']['offerId']) ? $data['freeRoundData']['offerId'] : null;
        $this->request_params['type'] = isset($data['type']) ? $data['type'] : null;
        $this->request_params['jackpot_amount'] = isset($data['jackpotAmount']) ? $data['jackpotAmount'] : null;
        $this->request_params['ended'] = isset($data['ended']) ? $data['ended'] : null;
        $this->request_params['bet_transaction_id'] = isset($data['betTransactionId']) ? $data['betTransactionId'] : null;
        $this->request_params['rolled_back_transaction_id'] = isset($data['rolledBackTransactionId']) ? $data['rolledBackTransactionId'] : null;
        $this->request_params['promotion_id'] = isset($data['promotionId']) ? $data['promotionId'] : null;
        $this->request_params['promo_payout_id'] = isset($data['promoPayoutId']) ? $data['promoPayoutId'] : null;
        $this->request_params['external_promo_id'] = isset($data['externalPromoId']) ? $data['externalPromoId'] : null;

        if ($this->api_method == self::API_METHOD_PROMO_PAYOUT) {
            $this->request_params['external_unique_id'] = $this->external_unique_id = $this->utils->mergeArrayValues([
                $this->api_method,
                $this->request_params['promo_payout_id'],
            ]);
        }
    }

    protected function initializePlayer($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add)) {

            if ($get_player_by == 'token') {
                $this->ssa_set_response(401, self::RESPONSE_INVALID_USER);
                $this->ssa_set_hint('externalPlayerId', 'Invalid Token');
            } else {
                $this->ssa_set_response(400, self::RESPONSE_INVALID_USER);
                $this->ssa_set_hint('externalPlayerId', self::RESPONSE_INVALID_USER['message']);
            }

            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;

        return true;
    }

    protected function isPlayerBlocked($subject, $is_game_username = true) {
        if ($this->ssa_is_player_blocked($this->game_api, $subject, $is_game_username)) {
            $this->ssa_set_response(400, self::RESPONSE_ACCOUNT_DISABLED);
            $this->ssa_set_hint('blocked', self::RESPONSE_ACCOUNT_DISABLED['message']);

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
            $this->ssa_set_response(401, self::RESPONSE_GENERAL_ERROR, 'IP Address not allowed');
            $this->ssa_set_hint('ipaddress', $this->ssa_get_ip_address());
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_set_response(503, self::RESPONSE_GENERAL_ERROR, self::RESPONSE_ERROR_GAME_DISABLED['message']);
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_set_response(503, self::RESPONSE_GENERAL_ERROR, self::RESPONSE_ERROR_GAME_MAINTENANCE['message']);
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
        return !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, false, $this->request_params['game_code']);
    }

    protected function remoteWalletError(&$data) {
        // treat success if remote wallet return double uniqueid
        if ($this->ssa_remote_wallet_error_double_unique_id()) {
            return $data['is_processed'] = true;
        }

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_set_response(402, self::RESPONSE_GENERAL_ERROR, self::RESPONSE_INSUFFICIENT_BALANCE['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message']);
            $data['wallet_adjustment_status'] = $this->ssa_failed;

            return $data['is_processed'] = false;
        }

        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message']);
        $data['wallet_adjustment_status'] = $this->ssa_failed;

        return $data['is_processed'] = false;
    }

    protected function walletAdjustment($adjustment_type, $query_type, $is_transaction_already_exists = false, $save_transaction_record_only = false, $allowed_negative_balance = false, $data = [], $isEndRound = true) {
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
        $this->ssa_set_game_provider_is_end_round($isEndRound);
        $this->ssa_set_game_provider_round_id($this->request_params['round_id']);
        $this->ssa_set_related_action_of_seamless_service($this->game_provider_related_action_type);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);

        $before_balance = $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, false, $this->request_params['game_code']);
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
            if ( ($save_transaction_record_only || $adjustment_type == $this->ssa_retain) && $amount != 0 ) {
                $data['amount'] = 0;
                $data['wallet_adjustment_status'] = $this->ssa_retained;
                $data['is_processed'] = true;
                $success = true;
            } else {
                // decrease wallet
                if ($adjustment_type == $this->ssa_decrease && $amount !=0) {
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
                                $after_balance = $this->afterBalance($after_balance) - $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_DECREASE_BALANCE['message']);
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
                                $after_balance = $this->afterBalance($after_balance) + $amount;
                            }
                        } else {
                            // default error
                            $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_INCREASE_BALANCE['message']);
                            $data['wallet_adjustment_status'] = $this->ssa_failed;
                            $data['is_processed'] = false;
                        }
                    }

                    $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                // undefined action
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $data['is_processed'] = false;
                    $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message']);

                    return false;
                }
            }

            if (!empty($this->remote_wallet_status)) {
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $data);
            }

            array_push($this->saved_multiple_transactions, $data);

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message']);

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
            'game_session_id' => isset($this->request_params['game_session_id']) ? $this->request_params['game_session_id'] : null,
            'external_session_id' => isset($this->request_params['external_session_id']) ? $this->request_params['external_session_id'] : null,
            'bought_bonus' => isset($this->request_params['bought_bonus']) ? $this->request_params['bought_bonus'] : null,
            'base_bet_level' => isset($this->request_params['base_bet_level']) ? $this->request_params['base_bet_level'] : null,
            'free_round_data' => isset($this->request_params['free_round_data']) ? json_encode($this->request_params['free_round_data']) : null,
            'free_round_activation_id' => isset($this->request_params['free_round_activation_id']) ? $this->request_params['free_round_activation_id'] : null,
            'campaign_id' => isset($this->request_params['campaign_id']) ? $this->request_params['campaign_id'] : null,
            'offer_id' => isset($this->request_params['offer_id']) ? $this->request_params['offer_id'] : null,
            'type' => isset($this->request_params['type']) ? $this->request_params['type'] : null,
            'jackpot_amount' => isset($this->request_params['jackpot_amount']) ? $this->request_params['jackpot_amount'] : null,
            'ended' => isset($this->request_params['ended']) ? $this->request_params['ended'] : null,
            'bet_transaction_id' => isset($this->request_params['bet_transaction_id']) ? $this->request_params['bet_transaction_id'] : null,
            'rolled_back_transaction_id' => isset($this->request_params['rolled_back_transaction_id']) ? $this->request_params['rolled_back_transaction_id'] : null,
            'promotion_id' => isset($this->request_params['promotion_id']) ? $this->request_params['promotion_id'] : null,
            'promo_payout_id' => isset($this->request_params['promo_payout_id']) ? $this->request_params['promo_payout_id'] : null,
            'external_promo_id' => isset($this->request_params['external_promo_id']) ? $this->request_params['external_promo_id'] : null,

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
            case self::API_METHOD_BET:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::API_METHOD_WIN:
            case self::API_METHOD_PROMO_PAYOUT:
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
            $data['game_session_id'] = isset($this->request_params['game_session_id']) ? $this->request_params['game_session_id'] : null;
            $data['external_session_id'] = isset($this->request_params['external_session_id']) ? $this->request_params['external_session_id'] : null;
            $data['bought_bonus'] = isset($this->request_params['bought_bonus']) ? $this->request_params['bought_bonus'] : null;
            $data['base_bet_level'] = isset($this->request_params['base_bet_level']) ? $this->request_params['base_bet_level'] : null;
            $data['free_round_data'] = isset($this->request_params['free_round_data']) ? json_encode($this->request_params['free_round_data']) : null;
            $data['free_round_activation_id'] = isset($this->request_params['free_round_activation_id']) ? $this->request_params['free_round_activation_id'] : null;
            $data['campaign_id'] = isset($this->request_params['campaign_id']) ? $this->request_params['campaign_id'] : null;
            $data['offer_id'] = isset($this->request_params['offer_id']) ? $this->request_params['offer_id'] : null;
            $data['type'] = isset($this->request_params['type']) ? $this->request_params['type'] : null;
            $data['jackpot_amount'] = isset($this->request_params['jackpot_amount']) ? $this->request_params['jackpot_amount'] : null;
            $data['ended'] = isset($this->request_params['ended']) ? $this->request_params['ended'] : null;
            $data['bet_transaction_id'] = isset($this->request_params['bet_transaction_id']) ? $this->request_params['bet_transaction_id'] : null;
            $data['rolled_back_transaction_id'] = isset($this->request_params['rolled_back_transaction_id']) ? $this->request_params['rolled_back_transaction_id'] : null;
            $data['promotion_id'] = isset($this->request_params['promotion_id']) ? $this->request_params['promotion_id'] : null;
            $data['promo_payout_id'] = isset($this->request_params['promo_payout_id']) ? $this->request_params['promo_payout_id'] : null;
            $data['external_promo_id'] = isset($this->request_params['external_promo_id']) ? $this->request_params['external_promo_id'] : null;

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
        if ($flag == Response_result::FLAG_NORMAL) {
            $operator_response['accountBalance'] = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);

            if ($this->api_method == self::API_METHOD_AUTHENTICATE) {
                $operator_response['externalPlayerId'] = $this->player_details['game_username'];
                $operator_response['name'] = $this->player_details['game_username'];
                $operator_response['accountCurrency'] = $this->currency;
                $operator_response['languageId'] = $this->language;
            }

            if ($this->api_method == self::API_METHOD_BALANCE) {
                $operator_response['accountCurrency'] = $this->currency;
            }

            if ($this->api_method == self::API_METHOD_BET ||
                $this->api_method == self::API_METHOD_WIN ||
                $this->api_method == self::API_METHOD_ROLLBACK ||
                $this->api_method == self::API_METHOD_PROMO_PAYOUT)
            {
                $operator_response['externalTransactionId'] = $this->external_unique_id;
            }

            if ($this->api_method == self::API_METHOD_END_SESSION) {
                $operator_response = [
                    'statusCode' => $this->ssa_get_operator_response(0)->code,
                    'statusMessage' => $this->ssa_get_operator_response(0)->message,
                ];
            }
        }

        $operator_response['statusCode'] = $this->ssa_get_operator_response(0)->code;
        $operator_response['statusMessage'] = $this->ssa_get_operator_response(0)->message;

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

        // It is expected that your wallet server always returns HTTP 200.
        // If the HTTP code is not 200, or if there is a network timeout, it will be taken as an error of type general error (equivalent to returning statusCode = 1)
        return $this->ssa_response_result([
            'response' => $operator_response,
            'add_origin' => true,
            'origin' => '*',
            'http_status_code' => in_array($http_response_status_code, [500, 501]) ? $http_response_status_code : 200,
            'http_status_text' => '',
            'content_type' => $this->content_type,
        ]);
    }

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
                'api_method' => self::API_METHOD_BET,
                'player_id' => $this->player_details['player_id'],
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
                'api_method' => self::API_METHOD_WIN,
                'player_id' => $this->player_details['player_id'],
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
                'api_method' => self::API_METHOD_ROLLBACK,
                'player_id' => $this->player_details['player_id'],
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

    protected function transactionAlreadyProcessed($where = []) {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }

    protected function Authenticate() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'token' => ['required'],
                'gameId' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK)) {
                if ($this->initializePlayer(true, $this->ssa_subject_type_token, $this->ssa_request_params['token'], $this->game_platform_id)) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                        $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true, false, $this->ssa_request_params['gameId']);

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
                        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, 'Internal Server Error: Authenticate failed');
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

    protected function Balance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'externalPlayerId' => ['required'],
                'externalSessionId' => ['optional'],
                'gameId' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK)) {
                if ($this->initializePlayer(false, $this->ssa_subject_type_game_username, $this->ssa_request_params['externalPlayerId'], $this->game_platform_id)) {
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                        $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true, false, $this->ssa_request_params['gameId']);

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
                        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, 'Internal Server Error: get balance failed');
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

    protected function Bet() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'externalPlayerId' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'roundId' => ['required'],
                'gameId' => ['required'],
                'gameSessionId' => ['required'],
                'externalSessionId' => ['optional'],
                'transactionId' => ['required'],
                'freeRoundData' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->ssa_request_params['externalPlayerId'], $this->game_platform_id)) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

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
                                if ($this->isRefundExists([
                                    'player_id' => $this->player_details['player_id'],
                                    'api_method' => self::API_METHOD_ROLLBACK,
                                    'rolled_back_transaction_id' => $this->request_params['transaction_id'],
                                    'is_processed' => true,
                                ])) {
                                    $adjustment_type = $this->ssa_retain;
                                } else {
                                    $adjustment_type = $this->ssa_decrease;
                                }

                                $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance, [], false);

                                if ($success) {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->external_unique_id = $get_transaction['external_unique_id'];

                                /* $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR);
                                $this->ssa_set_hint(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['code'], self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['message']);
                                $this->transaction_already_exists = $success = false;
                                */
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

    protected function Win() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->game_provider_related_action_type = Wallet_model::REMOTE_RELATED_ACTION_BET;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'externalPlayerId' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'roundId' => ['required'],
                'gameId' => ['required'],
                'gameSessionId' => ['required'],
                'externalSessionId' => ['optional'],
                'transactionId' => ['required'],
                'type' => ['required'],
                'freeRoundData' => ['optional'],
                'jackpotAmount' => ['optional', 'nullable', 'numeric', 'min:0'],
                'ended' => ['required'],
                'betTransactionId' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->ssa_request_params['externalPlayerId'], $this->game_platform_id)) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

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
                                if ($this->isWagerExists([
                                    'player_id' => $this->player_details['player_id'],
                                    'api_method' => self::API_METHOD_BET,
                                    'transaction_id' => $this->request_params['bet_transaction_id'],
                                    'is_processed' => true,
                                ])) {
                                    if (!$this->isRefundExists([
                                        'player_id' => $this->player_details['player_id'],
                                        'api_method' => self::API_METHOD_ROLLBACK,
                                        'rolled_back_transaction_id' => $this->request_params['bet_transaction_id'],
                                        'is_processed' => true,
                                    ])) {

                                        $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues([
                                            'game',
                                            $this->game_platform_id,
                                            self::API_METHOD_BET,
                                            $this->request_params['bet_transaction_id'],
                                        ]);

                                        $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                        if ($success) {
                                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                        }
                                    } else {
                                        $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Already processed by rollback');
                                    }
                                } else {
                                    $this->ssa_set_response(400, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Bet not exists');
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->external_unique_id = $get_transaction['external_unique_id'];

                                /* $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR);
                                $this->ssa_set_hint(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['code'], self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['message']);
                                $this->transaction_already_exists = $success = false;
                                */
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

    protected function Rollback() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $this->game_provider_related_action_type = Wallet_model::REMOTE_RELATED_ACTION_BET;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'externalPlayerId' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'roundId' => ['required'],
                'gameId' => ['required'],
                'gameSessionId' => ['required'],
                'externalSessionId' => ['optional'],
                'transactionId' => ['required'],
                'rolledBackTransactionId' => ['required'],
                'ended' => ['required'],
                'freeRoundData' => ['optional'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->ssa_request_params['externalPlayerId'], $this->game_platform_id)) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

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
                                if ($this->isWagerExists([
                                    'player_id' => $this->player_details['player_id'],
                                    'api_method' => self::API_METHOD_BET,
                                    'transaction_id' => $this->request_params['rolled_back_transaction_id'],
                                    'is_processed' => true,
                                ])) {
                                    // $this->ssa_set_response(400, self::RESPONSE_SUCCESS, 'Bet not exists');
                                    $adjustment_type = $this->ssa_increase;
                                } else {
                                    $adjustment_type = $this->ssa_retain;
                                }

                                if (!$this->transactionAlreadyProcessed([
                                    'player_id' => $this->player_details['player_id'],
                                    'api_method' => self::API_METHOD_ROLLBACK,
                                    'rolled_back_transaction_id' => $this->request_params['rolled_back_transaction_id'],
                                    'is_processed' => true,
                                ])) {
                                    if (!$this->isPayoutExists([
                                        'player_id' => $this->player_details['player_id'],
                                        'api_method' => self::API_METHOD_WIN,
                                        'bet_transaction_id' => $this->request_params['rolled_back_transaction_id'],
                                        'is_processed' => true,
                                    ])) {

                                        $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues([
                                            'game',
                                            $this->game_platform_id,
                                            self::API_METHOD_BET,
                                            $this->request_params['bet_transaction_id'],
                                        ]);

                                        $success = $this->walletAdjustment($adjustment_type, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance, [], true);

                                        if ($success) {
                                            $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                        }
                                    } else {
                                        $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Already processed by win');
                                    }
                                } else {
                                    $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Already processed by rollback');
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->external_unique_id = $get_transaction['external_unique_id'];

                                /* $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR);
                                $this->ssa_set_hint(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['code'], self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['message']);
                                $this->transaction_already_exists = $success = false;
                                */
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

    protected function EndSession() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'externalPlayerId' => ['required'],
                'gameSessionId' => ['required'],
                'externalSessionId' => ['optional'],
                'currency' => ['required', "expected_value:{$this->currency}"],
                'gameId' => ['required'],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR_NO_ROLLBACK)) {
                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->ssa_request_params['externalPlayerId'], $this->game_platform_id)) {
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

    protected function PromoPayout() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_set_response(500, self::RESPONSE_GENERAL_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => false,
            'USE_GAME_API_MAINTENANCE' => false,
        ])) {
            $rule_sets = [
                'action' => ['required'],
                'secret' => ['required', "expected_value:{$this->secret_key}"],
                'externalPlayerId' => ['required'],
                'promotionId' => ['required'],
                'promoPayoutId' => ['required'],
                'externalPromoId' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'currency' => ['required', "expected_value:{$this->currency}"],
            ];

            if ($this->ssa_validate_request_params($this->ssa_request_params, $rule_sets, [], self::RESPONSE_GENERAL_ERROR)) {
                $this->rebuildRequestParams($this->ssa_request_params);

                if ($this->initializePlayer(true, $this->ssa_subject_type_game_username, $this->ssa_request_params['externalPlayerId'], $this->game_platform_id)) {
                    $success = false;
                    $transaction_already_exists = false;
                    $is_processed = false;
                    $save_only = false;
                    $allowed_negative_balance = false;

                    if (!$this->isPlayerBlocked($this->player_details['game_username'])) {
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use($success, $transaction_already_exists, $is_processed, $save_only, $allowed_negative_balance) {
                            // check if transction exists
                            $get_transaction = $this->getTransactionRequest([
                                'promo_payout_id' => $this->request_params['promo_payout_id'],
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
                                $success = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $transaction_already_exists, $save_only, $allowed_negative_balance);

                                if ($success) {
                                    $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                }
                            } else {
                                $this->ssa_set_response(200, self::RESPONSE_SUCCESS);
                                $this->transaction_already_exists = $success = true;
                                $this->player_balance = $get_transaction['after_balance'];
                                $this->external_unique_id = $get_transaction['external_unique_id'];

                                /* $this->ssa_set_response(409, self::RESPONSE_GENERAL_ERROR);
                                $this->ssa_set_hint(self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['code'], self::SYSTEM_ERROR_INTERNAL_SERVER_ERROR['message']);
                                $this->transaction_already_exists = $success = false;
                                */
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

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => isset($this->request_params['transaction_id']) ? $this->request_params['transaction_id'] : null,
            'round_id' => isset($this->request_params['round_id']) ? $this->request_params['round_id'] : null,
            'external_game_id' =>isset($this->request_params['game_code']) ? $this->request_params['game_code'] : null,
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