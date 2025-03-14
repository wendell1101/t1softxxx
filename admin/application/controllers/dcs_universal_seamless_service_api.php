<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Dcs_universal_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const RESPONSE_TYPE_JSON = 'json';
    const RESPONSE_TYPE_XML = 'xml';

    // default configs
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
    protected $response_type = self::RESPONSE_TYPE_JSON;
    protected $content_type = 'application/json';
    protected $save_data = false;
    protected $encrypt_response = false;
    protected $seamless_service_unique_id = null;
    protected $external_game_id = null;
    protected $game_provider_action_type = null;
    protected $rebuilded_operator_response = [];
    protected $get_usec = false;
    protected $use_strip_slashes = true;
    protected $allowed_negative_balance_api_methods = [];
    protected $action = null;
    protected $show_hint = false;
    protected $hint = null;
    protected $remote_wallet_status = null;
    protected $use_remote_wallet_failed_transaction_monthly_table = false;
    protected $transaction_data = [];
    protected $seamless_service_unique_id_with_game_prefix = null;
    protected $seamless_service_related_unique_id = null;
    protected $seamless_service_related_action = null;

    // monthly transaction table
    protected $use_monthly_transactions_table = false;
    protected $force_check_previous_transactions_table = false;
    protected $force_check_other_transactions_table = false;
    protected $previous_table = null;

    // additional
    protected $api_key;
    protected $brand_id;
    protected $validate_sign;
    protected $provider;
    protected $is_empty_external_token_info = false;

    // default parameter name is code and message. You can change it using the rebuildOperatorResponse method.
    const RESPONSE_SUCCESS = [
        'code' => 1000,
        'message' => 'Success',
    ];

    const RESPONSE_SYSTEM_ERROR = [
        'code' => 1001,
        'message' => 'System error',
    ];

    const RESPONSE_UNKNOWN = [
        'code' => 1002,
        'message' => 'Unknown error',
    ];

    const RESPONSE_SIGN_ERROR = [
        'code' => 5000,
        'message' => 'Verification code error',
    ];

    const RESPONSE_REQUEST_PARAM_ERROR = [
        'code' => 5001,
        'message' => 'Request parameter error',
    ];

    const RESPONSE_CURRENCY_NOT_SUPPORT = [
        'code' => 5002,
        'message' => 'Currency not supported',
    ];

    const RESPONSE_INSUFFICIENT_BALANCE = [
        'code' => 5003,
        'message' => 'Insufficient balance',
    ];

    const RESPONSE_BRAND_NOT_EXIST = [
        'code' => 5005,
        'message' => 'Brand does not exist',
    ];

    const RESPONSE_COUNTRY_CODE_ERROR = [
        'code' => 5008,
        'message' => 'Country code error',
    ];

    const RESPONSE_PLAYER_NOT_EXIST = [
        'code' => 5009,
        'message' => 'Player id does not exist',
    ];

    const RESPONSE_PLAYER_BLOCKED = [
        'code' => 5010,
        'message' => 'Player blocked',
    ];

    const RESPONSE_GAME_ID_NOT_EXIST = [
        'code' => 5012,
        'message' => 'Game id does not exist',
    ];

    const RESPONSE_NOT_LOGGED_IN = [
        'code' => 5013,
        'message' => 'Session authentication failed',
    ];

    const RESPONSE_INVALID_TIME_FORMAT = [
        'code' => 5014,
        'message' => 'Invalid time format',
    ];

    const RESPONSE_INVALID_PROVIDER = [
        'code' => 5015,
        'message' => 'Invalid provider',
    ];

    const RESPONSE_INVALID_AMOUNT = [
        'code' => 5016,
        'message' => 'Invalid amount',
    ];

    const RESPONSE_API_INSUFFICIENT_PERMISSION = [
        'code' => 5017,
        'message' => 'Api has insufficient permission',
    ];

    const RESPONSE_INVALID_BRAND_UID = [
        'code' => 5018,
        'message' => 'Invalid brand_uid',
    ];

    const RESPONSE_PROVIDER_IS_MAINTAINING = [
        'code' => 5019,
        'message' => 'Provider is maintaining',
    ];

    const RESPONSE_BRAND_LOCKED = [
        'code' => 5020,
        'message' => 'Brand locked',
    ];

    const RESPONSE_DEMO_NOT_SUPPORTED = [
        'code' => 5021,
        'message' => 'No support try game',
    ];

    const RESPONSE_REPLAY_NOT_SUPPORTED = [
        'code' => 5023,
        'message' => 'No support get replay',
    ];

    const RESPONSE_TOKEN_CANNOT_BE_USED = [
        'code' => 5024,
        'message' => 'Token cannot be used',
    ];

    const RESPONSE_REQUEST_RATE_LIMIT = [
        'code' => 5040,
        'message' => 'Request rate limit, once request per 3 seconds.',
    ];

    const RESPONSE_REQUEST_DATE_RANGE_LIMIT = [
        'code' => 5041,
        'message' => 'Request date range limit, once request time period can only be within 24 hours. request time can only be within 6 months.',
    ];

    const RESPONSE_BET_RECORD_NOT_EXIST = [
        'code' => 5042,
        'message' => 'Bet record not exist',
    ];

    const RESPONSE_TRANSACTION_ALREADY_EXIST = [
        'code' => 5043,
        'message' => 'Transaction already exist',
    ];

    const RESPONSE_FREE_SPIN_ID_NOT_EXIST = [
        'code' => 5070,
        'message' => 'Free spin ID not exist',
    ];

    const RESPONSE_INCORRECT_ROUND_COUNT = [
        'code' => 5071,
        'message' => 'Incorrect round count',
    ];

    const RESPONSE_FREE_SPIN_ALREADY_CANCELLED = [
        'code' => 5072,
        'message' => 'The free spin already cancelled',
    ];

    const RESPONSE_FREE_SPIN_ALREADY_LOCKED = [
        'code' => 5073,
        'message' => 'The free spin already locked',
    ];

    const RESPONSE_FREE_SPIN_NOT_SUPPORTED = [
        'code' => 5074,
        'message' => 'The provider does not support free spin',
    ];

    const RESPONSE_FREE_SPIN_SET_UP_ERROR = [
        'code' => 5075,
        'message' => 'The free spin set up error',
    ];

    const API_METHOD_LOGIN = 'login';
    const API_METHOD_GET_BALANCE = 'getBalance';
    const API_METHOD_WAGER = 'wager';
    const API_METHOD_CANCEL_WAGER = 'cancelWager';
    const API_METHOD_APPEND_WAGER = 'appendWager';
    const API_METHOD_END_WAGER = 'endWager';
    const API_METHOD_FREE_SPIN_RESULT = 'freeSpinResult';
    const API_METHOD_PROMO_PAYOUT = 'promoPayout';

    const API_METHODS = [
        self::API_METHOD_LOGIN,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_WAGER,
        self::API_METHOD_CANCEL_WAGER,
        self::API_METHOD_APPEND_WAGER,
        self::API_METHOD_END_WAGER,
        self::API_METHOD_FREE_SPIN_RESULT,
        self::API_METHOD_PROMO_PAYOUT,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_WAGER,
        self::API_METHOD_CANCEL_WAGER,
        self::API_METHOD_APPEND_WAGER,
        self::API_METHOD_END_WAGER,
        self::API_METHOD_FREE_SPIN_RESULT,
        self::API_METHOD_PROMO_PAYOUT,
    ];

    const ALLOWED_API_METHODS = self::API_METHODS;

    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GENERATE_SIGN = 'generateSign';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GENERATE_SIGN,
    ];

    const ALLOWED_ACTIONS = self::ACTIONS;

    const BET_TYPE_NORMAL = 1;
    const BET_TYPE_TIP = 2;

    const BET_TYPES = [
        self::BET_TYPE_NORMAL,
        self::BET_TYPE_TIP,
    ];

    const WAGER_TYPE_CANCEL_WAGER = 1;
    const WAGER_TYPE_CANCEL_END_WAGER = 2;

    const WAGER_TYPES = [
        self::WAGER_TYPE_CANCEL_WAGER,
        self::WAGER_TYPE_CANCEL_END_WAGER,
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        // default
        'game_platform_id',
        'token',
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

        //addtional
        'provider',
        'brand_id',
        'jackpot_contribution',
        'bet_type',
        'wager_type',
        'is_endround',
        'description',
        'game_result',
        'game_name',
        'promotion_id',

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
        'seamless_service_unique_id',
        'external_game_id',
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

    // default system error code ---------------
    const SYSTEM_ERROR_EMPTY_GAME_API = [
        'code' => 'SE_1',
        'message' => 'Empty game platform id',
    ];

    const SYSTEM_ERROR_INITIALIZE_GAME_API = [
        'code' => 'SE_2',
        'message' => 'Failed to load game API',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND = [
        'code' => 'SE_3',
        'message' => 'Function method not found',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN = [
        'code' => 'SE_4',
        'message' => 'Function method forbidden',
    ];

    const SYSTEM_ERROR_DECREASE_BALANCE = [
        'code' => 'SE_5',
        'message' => 'Error decrease balance',
    ];

    const SYSTEM_ERROR_INCREASE_BALANCE = [
        'code' => 'SE_6',
        'message' => 'Error increase balance',
    ];

    const SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT = [
        'code' => 'SE_7',
        'message' => 'Error wallet adjustment',
    ];

    const SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA = [
        'code' => 'SE_8',
        'message' => 'Error save transaction request data',
    ];

    const SYSTEM_ERROR_SAVE_SERVICE_LOGS = [
        'code' => 'SE_9',
        'message' => 'Error save service logs',
    ];

    const SYSTEM_ERROR_REBUILD_OPERATOR_RESPONSE = [
        'code' => 'SE_10',
        'message' => 'Error rebuild operator response',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_DOUBLE_UNIQUEID = [
        'code' => 'SE_11',
        'message' => 'Remote wallet double unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID = [
        'code' => 'SE_12',
        'message' => 'Remote wallet invalid unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INSUFFICIENT_BALANCE = [
        'code' => 'SE_13',
        'message' => 'Remote wallet insufficient balance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE = [
        'code' => 'SE_14',
        'message' => 'Remote wallet maintenance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN = [
        'code' => 'SE_15',
        'message' => 'Remote wallet unknown error',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_ADJUSTED_IN_LOCK_BALANCE = [
        'code' => 'SE_16',
        'message' => 'Remote wallet adjusted in lock balance',
    ];

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->setParams($this->params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = [];
        $this->game_platform_id = $this->getProviderGamePlatformId();
        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);
        $this->allowed_negative_balance_api_methods = [];
    }

    public function index($api_method, $action = null) {
        $this->action = $action;

        if ($this->initialize($api_method)) {
            if (!empty($this->action)) {
                if ($this->action == self::ACTION_GENERATE_SIGN) {
                    return $this->generateSign();
                }
            }

            return $this->$api_method(); 
        }

        return $this->response();
    }

    protected function getProviderGamePlatformId() {
        $provider_platform = isset($this->ssa_request_params['provider']) ? $this->ssa_request_params['provider'] : null;
        $game_platform_id = DCS_UNIVERSAL_SEAMLESS_GAME_API;

        if (!empty($provider_platform)) {
            switch ($provider_platform) {
                case 'yg':
                    $game_platform_id = YGG_DCS_SEAMLESS_GAME_API;
                    break;
                case 'hs':
                    $game_platform_id = HACKSAW_DCS_SEAMLESS_GAME_API;
                    break;
                case 'aux':
                    $game_platform_id = AVATAR_UX_DCS_SEAMLESS_GAME_API;
                    break;
                case 'relax':
                    $game_platform_id = RELAX_DCS_SEAMLESS_GAME_API;
                    break;
                default:
                    $game_platform_id = DCS_UNIVERSAL_SEAMLESS_GAME_API;
                    break;
            }
        } else {
            if (isset($this->ssa_request_params['token'])) {
                $external_token_info = $this->ssa_get_external_token_info_by_token($this->ssa_request_params['token']);

                if (empty($external_token_info)) {
                    $this->is_empty_external_token_info = true;
                }

                $game_platform_id = isset($external_token_info['game_platform_id']) ? $external_token_info['game_platform_id'] : $game_platform_id;
            }
        }

        return $game_platform_id;
    }

    protected function initialize($api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->responseConfig();

        if (empty($this->game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (initialize empty game_platform_id)');
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);

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
            $this->use_remote_wallet_failed_transaction_monthly_table = $this->game_api->use_remote_wallet_failed_transaction_monthly_table;

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
            $this->previous_table = $this->game_api->ymt_get_previous_year_month_table();

            // additional
            $this->api_key = $this->game_api->api_key;
            $this->brand_id = $this->game_api->brand_id;
            $this->validate_sign = $this->game_api->validate_sign;
            $this->provider = $this->game_api->provider;

            $this->setParams($this->params);
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (load_game_api)');
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Method ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    protected function responseConfig() {
        $this->transfer_type_api_methods = self::TRANSFER_TYPE_API_METHODS;

        $this->content_type_json_api_methods = self::API_METHODS;

        $this->content_type_xml_api_methods = [];

        $this->content_type_plain_text_api_methods = [];

        $this->save_data_api_methods = self::API_METHODS;

        $this->encrypt_response_api_methods = [];

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

    protected function setParams($data = []) {
        if (isset($this->ssa_request_params['trans_id']) && $this->api_method == self::API_METHOD_PROMO_PAYOUT) {
            $transaction_id = $this->ssa_request_params['trans_id'];
        } else {
            $transaction_id = isset($this->ssa_request_params['wager_id']) ? $this->ssa_request_params['wager_id'] : null;
        }

        if (isset($this->ssa_request_params['promotion_id']) && $this->api_method == self::API_METHOD_PROMO_PAYOUT) {
            $round_id = $this->ssa_request_params['promotion_id'];
        } else {
            $round_id = isset($this->ssa_request_params['round_id']) ? $this->ssa_request_params['round_id'] : null;
        }

        // default
        $this->params['token'] = isset($this->ssa_request_params['token']) ? strtolower($this->ssa_request_params['token']) : null;
        $this->params['game_username'] = isset($this->ssa_request_params['brand_uid']) ? strtolower($this->ssa_request_params['brand_uid']) : null;
        $this->params['transaction_type'] = $this->api_method;
        $this->params['transaction_id'] = $transaction_id;
        $this->params['game_code'] = isset($this->ssa_request_params['game_id']) ? $this->ssa_request_params['game_id'] : null;
        $this->params['round_id'] = $round_id;
        $this->params['currency'] = isset($this->ssa_request_params['currency']) ? $this->ssa_request_params['currency'] : null;
        $this->params['amount'] = isset($this->ssa_request_params['amount']) ? abs($this->ssa_request_params['amount']) : 0;

        // additional
        $this->params['provider'] = isset($this->ssa_request_params['provider']) ? $this->ssa_request_params['provider'] : null;
        $this->params['brand_id'] = isset($this->ssa_request_params['brand_id']) ? $this->ssa_request_params['brand_id'] : null;
        $this->params['jackpot_contribution'] = isset($this->ssa_request_params['jackpot_contribution']) ? $this->ssa_request_params['jackpot_contribution'] : 0;
        $this->params['bet_type'] = isset($this->ssa_request_params['bet_type']) ? $this->ssa_request_params['bet_type'] : null;
        $this->params['wager_type'] = isset($this->ssa_request_params['wager_type']) ? $this->ssa_request_params['wager_type'] : null;
        $this->params['is_endround'] = isset($this->ssa_request_params['is_endround']) ? $this->ssa_request_params['is_endround'] : null;
        $this->params['description'] = isset($this->ssa_request_params['description']) ? $this->ssa_request_params['description'] : null;
        $this->params['game_result'] = !empty($this->ssa_request_params['game_result']) ? $this->ssa_request_params['game_result'] : null;
        $this->params['game_name'] = isset($this->ssa_request_params['game_name']) ? $this->ssa_request_params['game_name'] : null;
        $this->params['promotion_id'] = $round_id;

        $external_unique_id_params = [
            $this->api_method,
            $this->params['transaction_id'],
            // $this->params['round_id'],
        ];

        if (!empty($this->params['wager_type'])) {
            array_push($external_unique_id_params, $this->params['wager_type']);
        }

        $external_unique_id = $this->utils->mergeArrayValues($external_unique_id_params);

        $this->params['external_unique_id'] = !empty($this->params['transaction_id']) ? $external_unique_id : null;
    }

    protected function initializePlayerDetails($get_balance = false, $get_player_by = 'token', $use_params = true, $data = []) {
        if ($use_params) {
            $this->setParams($data);
        }

        if ($get_player_by == $this->ssa_subject_type_token) {
            // get player details by token
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $this->params['token'], $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = self::RESPONSE_NOT_LOGGED_IN;
                return false;
            } else {
                if (!empty($this->params['game_username']) && $this->params['game_username'] != $this->player_details['game_username']) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = self::RESPONSE_PLAYER_NOT_EXIST;
                    $this->player_details = [];
                    return false;
                }
            }
        } elseif ($get_player_by == $this->ssa_subject_type_game_username) {
            // get player details by player game username
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $this->params['game_username'], $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = self::RESPONSE_PLAYER_NOT_EXIST;
                return false;
            } else {
                if (!empty($this->params['game_username']) && $this->params['game_username'] != $this->player_details['game_username']) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = self::RESPONSE_PLAYER_NOT_EXIST;
                    $this->player_details = [];
                    return false;
                }
            }
        } else {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_PLAYER_NOT_EXIST;
            $this->player_details = [];
            return false;
        }

        if ($get_balance) {
            $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
        }

        return true;
    }

    protected function isPlayerBlocked() {
        if (isset($this->player_details['username']) && $this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_PLAYER_BLOCKED, 'Player is blocked');
            return true;
        }

        return false;
    }

    protected function validatePlayer() {
        $this->validate_block_player_api_methods = self::API_METHODS;

        if ($this->isPlayerBlocked() && in_array($this->api_method, $this->validate_block_player_api_methods)) {
            return false;
        }

        return true;
    }

    protected function isServerIpAllowed() {
        if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_API_INSUFFICIENT_PERMISSION, 'IP address is not allowed');
            $this->hint = $this->ssa_get_ip_address();
            return false;
        }

        return true;
    }

    protected function isGameApiActive() {
        if (!$this->ssa_is_game_api_active($this->game_api)) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_API_INSUFFICIENT_PERMISSION, 'Game is disabled');
            return false;
        }

        return true;
    }

    protected function isGameApiMaintenance() {
        if ($this->ssa_is_game_api_maintenance($this->game_api)) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_API_INSUFFICIENT_PERMISSION, 'Game is under maintenance');
            return true;
        }

        return false;
    }

    protected function validateGameApi($config = [
        'use_ssa_is_server_ip_allowed' => false,
        'use_ssa_is_game_api_active' => false,
        'use_ssa_is_game_api_maintenance' => false,
    ]) {

        $config['use_ssa_is_server_ip_allowed'] = isset($config['use_ssa_is_server_ip_allowed']) && $config['use_ssa_is_server_ip_allowed'] ? true : false;
        $config['use_ssa_is_game_api_active'] = isset($config['use_ssa_is_game_api_active']) && $config['use_ssa_is_game_api_active'] ? true : false;
        $config['use_ssa_is_game_api_maintenance'] = isset($config['use_ssa_is_game_api_maintenance']) && $config['use_ssa_is_game_api_maintenance'] ? true : false;

        if ($config['use_ssa_is_server_ip_allowed']) {
            if (!$this->isServerIpAllowed()) {
                return false;
            }
        }

        if ($config['use_ssa_is_game_api_active']) {
            if (!$this->isGameApiActive()) {
                return false;
            }
        }

        if ($config['use_ssa_is_game_api_maintenance']) {
            if ($this->isGameApiMaintenance()) {
                return false;
            }
        }

        return true;
    }

    protected function validateParams() {
        // default
        if (!empty($this->params['language']) && $this->params['language'] != $this->language) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Invalid parameter language');
            $this->hint = $this->language;
            return false;
        }

        if (!empty($this->params['currency']) && $this->params['currency'] != $this->currency) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Invalid parameter currency');
            $this->hint = $this->currency;
            return false;
        }

        // additional
        if (!empty($this->ssa_request_params['brand_id']) && $this->ssa_request_params['brand_id'] != $this->brand_id) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BRAND_NOT_EXIST);
            $this->hint = $this->brand_id;
            return false;
        }

        if (!$this->validateSign()) {
            return false;
        }

        if (!empty($this->ssa_request_params['provider']) && $this->ssa_request_params['provider'] != $this->provider) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PROVIDER);
            $this->hint = $this->provider;
            return false;
        }

        if (!empty($this->ssa_request_params['wager_type']) && !in_array($this->ssa_request_params['wager_type'], self::WAGER_TYPES)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Invalid parameter wager_type');
            $this->hint = '1=cancelWager, 2=cancelEndWager';
            return false;
        }

        return true;
    }

    public function validateSign() {
        if (!empty($this->ssa_request_params['sign']) && $this->validate_sign) {
            if ($this->generatedSign() != $this->ssa_request_params['sign']) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SIGN_ERROR);
                $this->hint = $this->generatedSign();
                return false;
            }
        }

        return true;
    }

    protected function getTransactions() {
        $transaction_count = 0;
        $get_transactions = [];
        $wager_transaction = [];
        $cancel_wager_transaction = [];
        $append_wager_transaction = [];
        $end_wager_transaction = [];
        $free_spin_result_transaction = [];
        $promo_payout_transaction = [];

        $selected_columns = [
            // default
            'transaction_type',
            'transaction_id',
            'game_code',
            'round_id',
            'amount',
            'bet_amount',
            'wallet_adjustment_status',
            'seamless_service_unique_id',

            // optional
            'wager_type',
        ];

        $where = "(player_id='{$this->player_details['player_id']}' AND round_id='{$this->params['round_id']}')";

        $get_transactions = $this->ssa_get_transactions($this->transaction_table, $where, $selected_columns);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transaction)) {
                $get_transactions = $this->ssa_get_transactions($this->previous_table, $where, $selected_columns);
            }
        }

        if (!empty($get_transactions) && is_array($get_transactions)) {
            $transaction_count = count($get_transactions);

            foreach ($get_transactions as $transaction) {
                if (isset($transaction['transaction_type'])) {
                    switch ($transaction['transaction_type']) {
                        case self::API_METHOD_WAGER:
                            array_push($wager_transaction, $transaction);
                            break;
                        case self::API_METHOD_CANCEL_WAGER:
                            array_push($cancel_wager_transaction, $transaction);
                            break;
                        case self::API_METHOD_APPEND_WAGER:
                            array_push($append_wager_transaction, $transaction);
                            break;
                        case self::API_METHOD_END_WAGER:
                            array_push($end_wager_transaction, $transaction);
                            break;
                        case self::API_METHOD_FREE_SPIN_RESULT:
                            array_push($free_spin_result_transaction, $transaction);
                            break;
                        case self::API_METHOD_PROMO_PAYOUT:
                            array_push($promo_payout_transaction, $transaction);
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        $result = [
            'get_transactions' => $get_transactions,
            'transaction_count' => $transaction_count,
            'wager_transaction' => $wager_transaction,
            'cancel_wager_transaction' => $cancel_wager_transaction,
            'append_wager_transaction' => $append_wager_transaction,
            'end_wager_transaction' => $end_wager_transaction,
            'free_spin_result_transaction' => $free_spin_result_transaction,
            'promo_payout_transaction' => $promo_payout_transaction,
        ];

        return $result;
    }

    protected function isTransactionProcessedSuccessfully($transactions) {
        if (!empty($transactions)) {
            if ($this->ssa_is_multidimensional_array($transactions)) {
                foreach ($transactions as $transaction) {
                    if (isset($transaction['wallet_adjustment_status'])) {
                        if (!in_array($transaction['wallet_adjustment_status'], [$this->ssa_decreased, $this->ssa_increased, $this->ssa_retained, $this->ssa_remote_wallet_decreased, $this->ssa_remote_wallet_increased, $this->ssa_remote_wallet_retained])) {
                            return false;
                        }
                    }
                }
            } else {
                if (isset($transactions['wallet_adjustment_status'])) {
                    if (!in_array($transactions['wallet_adjustment_status'], [$this->ssa_decreased, $this->ssa_increased, $this->ssa_retained, $this->ssa_remote_wallet_decreased, $this->ssa_remote_wallet_increased, $this->ssa_remote_wallet_retained])) {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }

        return true;
    }

    protected function isTransactionfloating($transactions) {
        if (!empty($transactions)) {
            if ($this->ssa_is_multidimensional_array($transactions)) {
                foreach ($transactions as $transaction) {
                    if (isset($transaction['wallet_adjustment_status'])) {
                        if (in_array($transaction['wallet_adjustment_status'], [$this->ssa_decreased, $this->ssa_increased, $this->ssa_retained, $this->ssa_remote_wallet_decreased, $this->ssa_remote_wallet_increased, $this->ssa_remote_wallet_retained])) {
                            return false;
                        }
                    }
                }
            } else {
                if (isset($transactions['wallet_adjustment_status'])) {
                    if (in_array($transactions['wallet_adjustment_status'], [$this->ssa_decreased, $this->ssa_increased, $this->ssa_retained, $this->ssa_remote_wallet_decreased, $this->ssa_remote_wallet_increased, $this->ssa_remote_wallet_retained])) {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }

        return true;
    }

    protected function getTransactionsAndvalidateExistence($config = [
        'validate_wager_transaction' => false,
        'validate_cancel_wager_transaction' => false,
        'validate_append_wager_transaction' => false,
        'validate_end_wager_transaction' => false,
        'validate_free_spin_result_transaction' => false,
        'validate_promo_payout_transaction' => false,
    ]) {

        $config['validate_wager_transaction'] = isset($config['validate_wager_transaction']) && $config['validate_wager_transaction'] ? true : false;
        $config['validate_cancel_wager_transaction'] = isset($config['validate_cancel_wager_transaction']) && $config['validate_cancel_wager_transaction'] ? true : false;
        $config['validate_append_wager_transaction'] = isset($config['validate_append_wager_transaction']) && $config['validate_append_wager_transaction'] ? true : false;
        $config['validate_end_wager_transaction'] = isset($config['validate_end_wager_transaction']) && $config['validate_end_wager_transaction'] ? true : false;
        $config['validate_free_spin_result_transaction'] = isset($config['validate_free_spin_result_transaction']) && $config['validate_free_spin_result_transaction'] ? true : false;
        $config['validate_promo_payout_transaction'] = isset($config['validate_promo_payout_transaction']) && $config['validate_promo_payout_transaction'] ? true : false;

        $result = $this->getTransactions();
        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $results = [true, false];
        $result_true = [];
        $result_false = [];

        foreach ($results as $value) {
            $result = array_merge($result, ['result' => $value]);

            if ($value) {
                $result_true = $result;
            } else {
                $result_false = $result;
            }
        }

        if ($config['validate_wager_transaction']) {
            if (empty($wager_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'Wager not found');
                return $result_false;
            }

            if (!$this->isTransactionProcessedSuccessfully($wager_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'Wager not found');
                return $result_false;
            }

            if (!empty($wager_transaction['game_code']) && !empty($this->params['game_code']) && $this->params['game_code'] != $wager_transaction['game_code']) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Invalid game code');
                $this->hint = $wager_transaction['game_code'];
                return $result_false;
            }

            if (!empty($wager_transaction['round_id']) && !empty($this->params['round_id']) && $this->params['round_id'] != $wager_transaction['round_id']) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Invalid round id');
                $this->hint = $wager_transaction['round_id'];
                return $result_false;
            }
        }

        if ($config['validate_cancel_wager_transaction']) {
            if ($this->isTransactionProcessedSuccessfully($cancel_wager_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already cancel wager');
                return $result_false;
            }
        }

        if ($config['validate_append_wager_transaction']) {
            if ($this->isTransactionProcessedSuccessfully($append_wager_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already append wager');
                return $result_false;
            }
        }

        if ($config['validate_end_wager_transaction']) {
            if ($this->isTransactionProcessedSuccessfully($end_wager_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already end wager');
                return $result_false;
            }
        }

        if ($config['validate_free_spin_result_transaction']) {
            if ($this->isTransactionProcessedSuccessfully($free_spin_result_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already unvoid bet');
                return $result_false;
            }
        }

        if ($config['validate_free_spin_result_transaction']) {
            if ($this->isTransactionProcessedSuccessfully($free_spin_result_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already refund');
                return $result_false;
            }
        }

        if ($config['validate_promo_payout_transaction']) {
            if ($this->isTransactionProcessedSuccessfully($promo_payout_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already unsettle');
                return $result_false;
            }
        }

        return $result_true;
    }

    protected function isTransactionAlreadyExists($use_request_id = false) {
        if ($use_request_id) {
            if ($this->isRequestUniqueIdExists()) {
                return true;
            }
        }

        $where = "(external_unique_id='{$this->params['external_unique_id']}' AND wallet_adjustment_status IN ('{$this->ssa_decreased}', '{$this->ssa_increased}', '{$this->ssa_retained}'))";

        $this->transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$this->transaction_already_exists) {
                $this->transaction_already_exists = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        if ($this->transaction_already_exists) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        return false;
    }

    protected function isTransactionAlreadyExistInDB($transactions, $transaction_id) {
        $is_exist = false;
        $existing_transaction = [];

        if ($this->ssa_is_multidimensional_array($transactions)) {
            foreach ($transactions as $transaction) {
                if (isset($transaction['transaction_id']) && $transaction['transaction_id'] == $transaction_id) {
                    $is_exist = true;
                    $existing_transaction = $transaction;
                    break;
                }
            }
        }

        return array($is_exist, $existing_transaction);
    }

    protected function isRequestUniqueIdExists() {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'package_id' => !empty($this->params['package_id']) ? $this->params['package_id'] : null,
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, [
                    'package_id' => !empty($this->params['package_id']) ? $this->params['package_id'] : null,
                ]);
            }
        }

        if ($is_exist) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        return false;
    }

    protected function isBetExists() {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_WAGER,
            'transaction_id' => !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, [
                    'transaction_type' => self::API_METHOD_WAGER,
                    'transaction_id' => !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
                ]);
            }
        }

        if ($is_exist) {
            return true;
        } else {
            return false;
        }
    }

    protected function isPayoutExists() {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_END_WAGER,
            'transaction_id' => !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, [
                    'transaction_type' => self::API_METHOD_END_WAGER,
                    'transaction_id' => !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
                ]);
            }
        }

        if ($is_exist) {
            return true;
        } else {
            return false;
        }
    }

    protected function isRefundExists() {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_CANCEL_WAGER,
            'transaction_id' => !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, [
                    'transaction_type' => self::API_METHOD_CANCEL_WAGER,
                    'transaction_id' => !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
                ]);
            }
        }

        if ($is_exist) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateTransactionRecords() {
        if ($this->isTransactionAlreadyExists()) {
            return false;
        }

        if (!$this->isBetExists()) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Bet not exist');
            return false;
        }

        if ($this->isPayoutExists()) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already settled');
            return false;
        }

        if ($this->isRefundExists()) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, 'Already refunded');
            return false;
        }

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INSUFFICIENT_BALANCE, 'Insufficient balance');
            return true;
        }

        return false;
    }

    protected function walletAdjustment($adjustment_type, $query_type, $amount) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->params['external_unique_id']]);
        $this->seamless_service_unique_id_with_game_prefix = $this->utils->mergeArrayValues(['game', $this->seamless_service_unique_id]);
        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->params['game_code']);
        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);
        $this->ssa_set_game_provider_round_id($this->params['round_id']);
        $is_end = isset($this->params['is_endround']) && $this->params['is_endround'] ? true : false;
        $this->ssa_set_game_provider_is_end_round($is_end);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        $transaction_data = [
            // default
            'external_unique_id' => $this->params['external_unique_id'],
            'amount' => $amount,
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
            'wallet_adjustment_status' => $this->ssa_preserved,
            'adjustment_type' => $adjustment_type,
        ];

        if ($amount < 0) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_AMOUNT, 'Invalid amount');
            return false;
        }

        if ($adjustment_type == $this->ssa_decrease) {
            // check allowed negative balance or insufficient balance
            if (!empty($this->utils->getConfig('remote_wallet_api_allowed_negative_balance'))) {
                if (!in_array($this->game_platform_id, $this->utils->getConfig('remote_wallet_api_allowed_negative_balance'))) {
                    if ($this->isInsufficientBalance($before_balance, $amount)) {
                        return false;
                    }
                } else {
                    if (!in_array($this->api_method, $this->allowed_negative_balance_api_methods)) {
                        if ($this->isInsufficientBalance($before_balance, $amount)) {
                            return false;
                        }
                    }
                }
            } else {
                if ($this->isInsufficientBalance($before_balance, $amount)) {
                    return false;
                }
            }
        }

        // save transaction data first
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $transaction_data);
        $is_transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->params['external_unique_id']]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_transaction_already_exists) {
                $is_transaction_already_exists = $this->ssa_is_transaction_exists($this->previous_table, ['external_unique_id' => $this->params['external_unique_id']]);
            }
        }

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($is_transaction_already_exists) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'data has been saved.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            $after_balance = null;

            #increase balance must be call in 0 amount
            if ($amount == 0 && $this->api_method == self::API_METHOD_END_WAGER) {
                if ($this->ssa_enabled_remote_wallet()) {
                    $this->utils->debug_log(__METHOD__, "{$this->game_api->seamless_game_api_name}: amount 0 call remote wallet", 'request_params', $this->ssa_request_params);
                    $this->ssa_increase_remote_wallet($this->player_details['player_id'], $amount, $this->game_platform_id, $after_balance);
                } 
            }

            if ($amount == 0) {
                $transaction_data['wallet_adjustment_status'] = $this->ssa_retained;
                $success = true;
            } else {
                if ($adjustment_type == $this->ssa_decrease) {
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if ($success) {
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_decreased;
                    } else {
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;

                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                            // treat success if remote wallet return double uniqueid
                            if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                $transaction_data['wallet_adjustment_status'] = $this->ssa_remote_wallet_decreased;
                                $success = true;
                            } elseif ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']);
                            } elseif ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INSUFFICIENT_BALANCE, 'Insufficient balance');
                            } elseif ($this->ssa_remote_wallet_error_maintenance()) {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']);
                            } else {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['code']);
                            }
                        } elseif ($this->ssa_enabled_remote_wallet() && $this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_code_success) {
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_ADJUSTED_IN_LOCK_BALANCE['code']);
                            $transaction_data['wallet_adjustment_status'] = $this->ssa_remote_wallet_decreased;
                        } else {
                            // default error
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (decrease balance)');
                        }
                    }

                    $transaction_data['after_balance'] = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

                } elseif ($adjustment_type == $this->ssa_increase) {
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if ($success) {
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_increased;
                    } else {
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;

                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                            // treat success if remote wallet return double uniqueid
                            if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                $transaction_data['wallet_adjustment_status'] = $this->ssa_remote_wallet_increased;
                                $success = true;
                            } elseif ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']);
                            } elseif ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                $this->ssa_http_response_status_code = 200;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INSUFFICIENT_BALANCE, 'Insufficient balance');
                            } elseif ($this->ssa_remote_wallet_error_maintenance()) {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']);
                            } else {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['code']);
                            }
                        } elseif ($this->ssa_enabled_remote_wallet() && $this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_code_success) {
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_ADJUSTED_IN_LOCK_BALANCE['code']);
                            $transaction_data['wallet_adjustment_status'] = $this->ssa_remote_wallet_increased;
                        } else {
                            // default error
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (increase balance)');
                        }
                    }

                    $transaction_data['after_balance'] = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                } else {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (walletAdjustment default)');
                    return false;
                }
            }

            array_push($this->saved_multiple_transactions, $transaction_data);

            if (!empty($this->remote_wallet_status)) {
                $this->save_remote_wallet_failed_transaction($this->ssa_insert, $transaction_data);
            }

            return $success;
        } else {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save data.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (saveTransactionRequestData)');
            return false;
        }
    }

    protected function rebuildTransactionRequestData($query_type = null, $transaction_data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $extra_info = [
            'generatedSign' => $this->generatedSign(),
        ];

        $new_transaction_data = [
            // default
            'game_platform_id' => $this->game_platform_id,
            'token' => isset($this->params['token']) ? $this->params['token'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'language' => $this->language,
            'currency' => $this->currency,
            'transaction_type' => $this->api_method,
            'transaction_id' => isset($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
            'game_code' => isset($this->params['game_code']) ? $this->params['game_code'] : null,
            'round_id' => isset($this->params['round_id']) ? $this->params['round_id'] : null,
            'amount' => $transaction_data['amount'],
            'before_balance' => $transaction_data['before_balance'],
            'after_balance' => $transaction_data['after_balance'],
            'status' => $this->setStatus(),
            'start_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            // addtional
            'provider' => isset($this->params['provider']) ? $this->params['provider'] : null,
            'brand_id' => isset($this->params['brand_id']) ? $this->params['brand_id'] : null,
            'jackpot_contribution' => isset($this->params['jackpot_contribution']) ? $this->params['jackpot_contribution'] : 0,
            'bet_type' => isset($this->params['bet_type']) ? $this->params['bet_type'] : null,
            'wager_type' => isset($this->params['wager_type']) ? $this->params['wager_type'] : null,
            'is_endround' => $this->params['is_endround'] ? true : false,
            'description' => isset($this->params['description']) ? json_encode(['description' => $this->params['description']]) : null,
            'game_result' => isset($this->params['game_result']) ? json_encode($this->params['game_result']) : null,
            'game_name' => isset($this->params['game_name']) ? $this->params['game_name'] : null,
            'promotion_id' => isset($this->params['promotion_id']) ? $this->params['promotion_id'] : null,

            // default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => !empty($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
            'response' => null,
            'extra_info' => json_encode($extra_info),
            'bet_amount' => isset($this->params['bet_amount']) ? $this->params['bet_amount'] : 0,
            'win_amount' => isset($this->params['win_amount']) ? $this->params['win_amount'] : 0,
            'result_amount' => isset($this->params['result_amount']) ? $this->params['result_amount'] : 0,
            'flag_of_updated_result' => isset($this->params['flag_of_updated_result']) ? $this->params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
            'wallet_adjustment_status' => $transaction_data['wallet_adjustment_status'],
            'external_unique_id' => $this->params['external_unique_id'],
            'seamless_service_unique_id' => $this->seamless_service_unique_id_with_game_prefix,
            'external_game_id' => !empty($this->params['game_code']) ? $this->params['game_code'] : null,
        ];

        $new_transaction_data['md5_sum'] = $this->ssa_generate_md5_sum($new_transaction_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        return $new_transaction_data;
    }

    protected function saveTransactionRequestData($query_type, $transaction_data) {
        $new_transaction_data = $this->rebuildTransactionRequestData($query_type, $transaction_data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $new_transaction_data, 'external_unique_id', $this->params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    protected function setStatus() {
        switch ($this->api_method) {
            case self::API_METHOD_WAGER:
            case self::API_METHOD_APPEND_WAGER:
            case self::API_METHOD_END_WAGER:
            case self::API_METHOD_FREE_SPIN_RESULT:
                if (isset($this->params['is_endround']) && $this->params['is_endround']) {
                    $result = Game_logs::STATUS_SETTLED;
                } else {
                    $result = Game_logs::STATUS_PENDING;
                }
                break;
            case self::API_METHOD_PROMO_PAYOUT:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_CANCEL_WAGER:
                $result = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    protected function rebuildOperatorResponse($flag, $operator_response) {
        $is_normal = $flag == Response_result::FLAG_NORMAL;
        $code = isset($operator_response['code']) ? $operator_response['code'] : self::RESPONSE_SYSTEM_ERROR['code'];
        $message = isset($operator_response['message']) ? $operator_response['message'] : self::RESPONSE_SYSTEM_ERROR['message'];
        $balance = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
        $game_username = isset($this->player_details['game_username']) ? $this->player_details['game_username'] : null;

        $operator_response = [
            'code' => $code,
            'msg' => $message,
        ];

        switch ($code) {
            case self::RESPONSE_SUCCESS['code']:
            case self::RESPONSE_TRANSACTION_ALREADY_EXIST['code']:
            case self::RESPONSE_INSUFFICIENT_BALANCE['code']:
            case self::RESPONSE_BET_RECORD_NOT_EXIST['code']:
                $operator_response['data'] = [
                    'brand_uid' => $game_username,
                    'currency' => $this->currency,
                    'balance' => $balance,
                ];
                break;
        }

        if (!empty($this->action)) {
            if (in_array($this->action, self::ALLOWED_ACTIONS)) {
                switch ($this->action) {
                    case self::ACTION_SHOW_HINT:
                        if ($this->show_hint) {
                            if (!empty($this->hint)) {
                                $operator_response['hint'] = $this->hint;
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

    protected function finalizeTransactionData() {
        if ($this->is_transfer_type) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            if (!empty($this->saved_multiple_transactions) && is_array($this->saved_multiple_transactions)) {
                foreach ($this->saved_multiple_transactions as $transaction_data) {
                    $external_unique_id = isset($transaction_data['external_unique_id']) ? $transaction_data['external_unique_id'] : null;
                    $after_balance = isset($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
                    $wallet_adjustment_status = isset($transaction_data['wallet_adjustment_status']) ? $transaction_data['wallet_adjustment_status'] : $this->ssa_preserved;
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

                    if (!empty($external_unique_id)) {
                        $data = [
                            'after_balance' => $after_balance,
                            'wallet_adjustment_status' => $wallet_adjustment_status,
                            'response' => $operator_response,
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

    protected function saveGameSeamlessServiceLogs() {
        if (!empty($this->game_seamless_service_logs_table) && $this->save_game_seamless_service_logs) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::RESPONSE_SYSTEM_ERROR['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::RESPONSE_SYSTEM_ERROR['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

            $extra_info = [
                'generatedSign' => $this->generatedSign(),
            ];

            $md5_data = $data = [
                'game_platform_id' => $this->game_platform_id,
                'token' => isset($this->params['token']) ? $this->params['token'] : null,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                'transaction_type' => $this->api_method,
                'game_code' => !empty($this->params['game_code']) ? $this->params['game_code'] : null,
                'round_id' => !empty($this->params['round_id']) ? $this->params['round_id'] : null,
                'status_code' => isset($http_response['code']) ? $http_response['code'] : null,
                'status_text' => isset($http_response['text']) ? $http_response['text'] : null,
                'response_code' => $code,
                'response_message' => $message,
                'flag' => $flag,
                'request' => !empty($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
                'response' => $operator_response,
                'extra_info' => json_encode($extra_info),
                'seamless_service_unique_id' => $this->seamless_service_unique_id_with_game_prefix,
                'external_game_id' => !empty($this->params['game_code']) ? $this->params['game_code'] : null,
                'external_unique_id' => !empty($this->params['external_unique_id']) ? $this->params['external_unique_id'] : null,
                // addtional
                'provider' => isset($this->params['provider']) ? $this->params['provider'] : null,
                'brand_id' => isset($this->params['brand_id']) ? $this->params['brand_id'] : null,
                'jackpot_contribution' => isset($this->params['jackpot_contribution']) ? $this->params['jackpot_contribution'] : 0,
                'bet_type' => isset($this->params['bet_type']) ? $this->params['bet_type'] : null,
                'wager_type' => isset($this->params['wager_type']) ? $this->params['wager_type'] : null,
                'is_endround' => $this->params['is_endround'] ? true : false,
                'description' => isset($this->params['description']) ? json_encode(['description' => $this->params['description']]) : null,
                'game_result' => isset($this->params['game_result']) ? $this->params['game_result'] : null,
                'game_name' => isset($this->params['game_name']) ? $this->params['game_name'] : null,
                'promotion_id' => isset($this->params['promotion_id']) ? $this->params['promotion_id'] : null,
            ];

            unset($md5_data['response'], $md5_data['extra_info']);

            $data['md5_sum'] = md5(json_encode($md5_data));
            $data['elapsed_time'] = $this->utils->getCostMs();
            $data['response_result_id'] = $this->response_result_id;
            $data['transaction_id'] = !empty($this->params['transaction_id']) ? $this->params['transaction_id'] : null;
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

    protected function response() {
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $operator_response = $this->rebuildOperatorResponse($flag, $this->ssa_operator_response);

        if ($this->save_data) {
            $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id);

            $this->finalizeTransactionData();
            $this->saveGameSeamlessServiceLogs();
        }

        return $this->ssa_response_result([
            'response' => $operator_response,
            'add_origin' => true,
            'origin' => '*',
            'http_status_code' => $this->ssa_http_response_status_code,
            'http_status_text' => '',
            'content_type' => $this->content_type,
        ]);
    }

    // ------------------------------------- START FUNCTION METHOD IMPLEMENTATION  ------------------------------------- //

    protected function generatedSign() {
        switch ($this->api_method) {
            case self::API_METHOD_LOGIN:
            case self::API_METHOD_GET_BALANCE:
                $params = [
                    $this->brand_id,
                    $this->params['token'],
                ];
                break;
            case self::API_METHOD_WAGER:
            case self::API_METHOD_CANCEL_WAGER:
            case self::API_METHOD_APPEND_WAGER:
            case self::API_METHOD_END_WAGER:
            case self::API_METHOD_FREE_SPIN_RESULT:
                $params = [
                    $this->brand_id,
                    $this->params['transaction_id'],
                ];
                break;
            case self::API_METHOD_PROMO_PAYOUT:
                $params = [
                    $this->brand_id,
                    $this->params['promotion_id'],
                    $this->params['transaction_id'],
                ];
                break;
            default:
                $params = [
                    $this->brand_id
                ];
                break;
        }

        return $this->game_api->generatedSign($params);
    }

    public function generateSign() {
        $response = [
            'generated_sign' => $this->generatedSign(),
        ];

        return $this->ssa_response_result([
            'response' => $response,
            'add_origin' => true,
            'origin' => '*',
            'http_status_code' => 200,
            'http_status_text' => '',
            'content_type' => $this->content_type,
        ]);
    }

    protected function isEmptyExternalTokenInfo () {
        if ($this->is_empty_external_token_info) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_NOT_LOGGED_IN;
            return true;
        }

        return false;
    }

    protected function login() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => true,
            'use_ssa_is_game_api_maintenance' => true,
        ])) {
            if (!$this->isEmptyExternalTokenInfo()) {
                if ($this->ssa_validate_request_params($this->ssa_request_params, [
                    'brand_id' => ['required'],
                    'sign' => ['required'],
                    'token' => ['required'],
                    'brand_uid' => ['required'],
                    'currency' => ['required'],
                ])) {
                    if ($this->validateSign()) {
                        if ($this->initializePlayerDetails(true, $this->ssa_subject_type_token, true)) {
                            if ($this->validatePlayer()) {
                                if ($this->validateParams()) {
                                    $this->ssa_http_response_status_code = 200;
                                    $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                                }
                            }
                        }
                    }
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
                }
            }
        }

        return $this->response();
    }

    protected function getBalance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => true,
            'use_ssa_is_game_api_maintenance' => true,
        ])) {
            if (!$this->isEmptyExternalTokenInfo()) {
                if ($this->ssa_validate_request_params($this->ssa_request_params, [
                    'brand_id' => ['required'],
                    'sign' => ['required'],
                    'brand_uid' => ['required'],
                    'currency' => ['required'],
                    'token' => ['required'],
                ])) {
                    if ($this->validateSign()) {
                        if ($this->initializePlayerDetails(false, $this->ssa_subject_type_token, true)) {
                            if ($this->validatePlayer()) {
                                if ($this->validateParams()) {
                                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                                        $result = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true);

                                        if (isset($result['success']) && $result['success'] && isset($result['balance'])) {
                                            $this->player_balance = $result['balance'];
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
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Error in getting balance');
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
                }
            }
        }

        return $this->response();
    }

    protected function wager() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => true,
            'use_ssa_is_game_api_maintenance' => true,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'brand_id' => ['required'],
                'sign' => ['required'],
                'token' => ['required'],
                'brand_uid' => ['required'],
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'jackpot_contribution' => ['required', 'nullable', 'numeric'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'round_id' => ['required'],
                'wager_id' => ['required'],
                'provider' => ['required'],
                'bet_type' => ['required'],
                'is_endround' => ['required', 'nullable'],
            ])) {

                $round_id = isset($this->ssa_request_params['round_id']) ? $this->ssa_request_params['round_id'] : null;
                $bet_amount = isset($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : null;
                $is_end_round = isset($this->ssa_request_params['is_endround']) ? $this->ssa_request_params['is_endround'] : null;
                $this->ssa_set_game_provider_round_id($round_id);
                $this->ssa_set_game_provider_bet_amount($bet_amount);
                $this->ssa_set_game_provider_is_end_round($is_end_round);

                $this->processWagerRequest();
            } else {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processWagerRequest($data = []) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if (!$this->validateSign()) {
            return false;
        }

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_token, true)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_wager_transaction' => false,
            'validate_cancel_wager_transaction' => false,
            'validate_append_wager_transaction' => false,
            'validate_end_wager_transaction' => false,
            'validate_free_spin_result_transaction' => false,
            'validate_promo_payout_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $query_type = $this->ssa_insert;

        if (!empty($wager_transaction)) {
            list($is_exist, $existing_transaction) = $this->isTransactionAlreadyExistInDB($wager_transaction, $this->params['transaction_id']);

            if ($is_exist) {
                if ($this->isTransactionfloating($existing_transaction)) {
                    $query_type = $this->ssa_update;
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_ALREADY_EXIST, 'Transaction already Exists');
                    return true;
                }
            }
        }

        if (isset($result['result']) && !$result['result']) {
            if ($query_type = $this->ssa_insert) {
                return false;
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($query_type) {
            return $this->walletAdjustment($this->ssa_decrease, $query_type, $this->params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function cancelWager() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => false,
            'use_ssa_is_game_api_maintenance' => false,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'brand_id' => ['required'],
                'sign' => ['required'],
                'brand_uid' => ['required'],
                'currency' => ['required'],
                'round_id' => ['required'],
                'wager_id' => ['required'],
                'provider' => ['required'],
                'wager_type' => ['required'],
                'is_endround' => ['required', 'nullable'],
            ])) {
                $round_id = isset($this->ssa_request_params['round_id']) ? $this->ssa_request_params['round_id'] : null;
                $is_end_round = isset($this->ssa_request_params['is_endround']) ? $this->ssa_request_params['is_endround'] : null;
                $this->ssa_set_game_provider_round_id($round_id);
                $this->ssa_set_game_provider_is_end_round($is_end_round);
                $this->processCancelWagerRequest();
            } else {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processCancelWagerRequest($data = []) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if (!$this->validateSign()) {
            return false;
        }

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_wager_transaction' => false,
            'validate_cancel_wager_transaction' => false,
            'validate_append_wager_transaction' => false,
            'validate_end_wager_transaction' => false,
            'validate_free_spin_result_transaction' => false,
            'validate_promo_payout_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $query_type = $this->ssa_insert;

        if (!empty($cancel_wager_transaction)) {
            $existing_transaction = [];

            foreach ($cancel_wager_transaction as $transaction) {
                if (isset($transaction['transaction_id']) && isset($transaction['wager_type'])) {
                    if ($transaction['transaction_id'] == $this->params['transaction_id'] && $transaction['wager_type'] == $this->params['wager_type']) {
                        $existing_transaction = $transaction;
                        break;
                    }
                }
            }
    
            if ($existing_transaction) {
                if ($this->isTransactionfloating($existing_transaction)) {
                    $query_type = $this->ssa_update;
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_ALREADY_EXIST, 'Transaction already Exists');
                    return true;
                }
            }
        }

        // check if existing and get wallet action by wager type
        if (isset($this->params['wager_type'])) {
            if ($this->params['wager_type'] == self::WAGER_TYPE_CANCEL_END_WAGER) {
                if (empty($end_wager_transaction)) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'end wager not exists');
                    return false;
                }

                list($is_exist, $end_wager_existing_transaction) = $this->isTransactionAlreadyExistInDB($end_wager_transaction, $this->params['transaction_id']);

                if (!$is_exist) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'end wager not exists');
                    return false;
                }

                $end_wager_transaction = $end_wager_existing_transaction;
                $this->params['amount'] = !empty($end_wager_transaction['amount']) ? $end_wager_transaction['amount'] : 0;
                $this->params['game_code'] = !empty($end_wager_transaction['game_code']) ? $end_wager_transaction['game_code'] : null;
                $wallet_action = $this->ssa_decrease;

                if (!empty($end_wager_transaction['seamless_service_unique_id'])) {
                    $this->seamless_service_related_unique_id = $end_wager_transaction['seamless_service_unique_id'];
                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                }
            } else {
                if (empty($wager_transaction)) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'wager not exists');
                    return false;
                }

                list($is_exist, $wager_existing_transaction) = $this->isTransactionAlreadyExistInDB($wager_transaction, $this->params['transaction_id']);
        
                if (!$is_exist) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'wager not exists');
                    return false;
                }

                $wager_transaction = $wager_existing_transaction;
                $this->params['amount'] = !empty($wager_transaction['amount']) ? $wager_transaction['amount'] : 0;
                $this->params['game_code'] = !empty($wager_transaction['game_code']) ? $wager_transaction['game_code'] : null;
                $wallet_action = $this->ssa_increase;

                if (!empty($wager_transaction['seamless_service_unique_id'])) {
                    $this->seamless_service_related_unique_id = $wager_transaction['seamless_service_unique_id'];
                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                }
            }
        }

        if (isset($result['result']) && !$result['result']) {
            if ($query_type = $this->ssa_insert) {
                return false;
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($wallet_action, $query_type) {
            return $this->walletAdjustment($wallet_action, $query_type, $this->params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function appendWager() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => false,
            'use_ssa_is_game_api_maintenance' => false,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'brand_id' => ['required'],
                'sign' => ['required'],
                'brand_uid' => ['required'],
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'round_id' => ['required'],
                'wager_id' => ['required'],
                'provider' => ['required'],
                'description' => ['required'],
                'is_endround' => ['required', 'nullable'],
            ])) {
                $this->processAppendWagerRequest();
            } else {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processAppendWagerRequest($data = []) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if (!$this->validateSign()) {
            return false;
        }

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_wager_transaction' => true,
            'validate_cancel_wager_transaction' => false,
            'validate_append_wager_transaction' => false,
            'validate_end_wager_transaction' => false,
            'validate_free_spin_result_transaction' => false,
            'validate_promo_payout_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $query_type = $this->ssa_insert;

        if (!empty($append_wager_transaction)) {
            list($is_exist, $existing_transaction) = $this->isTransactionAlreadyExistInDB($append_wager_transaction, $this->params['transaction_id']);

            if ($is_exist) {
                if ($this->isTransactionfloating($existing_transaction)) {
                    $query_type = $this->ssa_update;
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_ALREADY_EXIST, 'Transaction already Exists');
                    return true;
                }
            }
        }

        if (!empty($wager_transaction)) {
            $wager_transaction = end($wager_transaction);

            if (!empty($wager_transaction['seamless_service_unique_id'])) {
                $this->seamless_service_related_unique_id = $wager_transaction['seamless_service_unique_id'];
                $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
            }
        } else {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'wager not exists');
            return false;
        }

        if (isset($result['result']) && !$result['result']) {
            if ($query_type = $this->ssa_insert) {
                return false;
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($query_type) {
            return $this->walletAdjustment($this->ssa_increase, $query_type, $this->params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function endWager() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => false,
            'use_ssa_is_game_api_maintenance' => false,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'brand_id' => ['required'],
                'sign' => ['required'],
                'brand_uid' => ['required'],
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'round_id' => ['required'],
                'wager_id' => ['required'],
                'provider' => ['required'],
                'is_endround' => ['required', 'nullable'],
                'game_result' => ['optional'],
            ])) {
                $round_id = isset($this->ssa_request_params['round_id']) ? $this->ssa_request_params['round_id'] : null;
                $payout_amount = isset($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : null;
                $is_end_round = isset($this->ssa_request_params['is_endround']) ? $this->ssa_request_params['is_endround'] : null;
                $this->ssa_set_game_provider_round_id($round_id);
                $this->ssa_set_game_provider_payout_amount($payout_amount);
                $this->ssa_set_game_provider_is_end_round($is_end_round);

                $this->processEndWagerRequest();
            } else {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processEndWagerRequest($data = []) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if (!$this->validateSign()) {
            return false;
        }

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_wager_transaction' => true,
            'validate_cancel_wager_transaction' => false,
            'validate_append_wager_transaction' => false,
            'validate_end_wager_transaction' => false,
            'validate_free_spin_result_transaction' => false,
            'validate_promo_payout_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $query_type = $this->ssa_insert;

        if (!empty($end_wager_transaction)) {
            list($is_exist, $existing_transaction) = $this->isTransactionAlreadyExistInDB($end_wager_transaction, $this->params['transaction_id']);

            if ($is_exist) {
                if ($this->isTransactionfloating($existing_transaction)) {
                    $query_type = $this->ssa_update;
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_ALREADY_EXIST, 'Transaction already Exists');
                    return true;
                }
            }
        }

        if (!empty($wager_transaction)) {
            $wager_transaction = end($wager_transaction);

            if (!empty($wager_transaction['seamless_service_unique_id'])) {
                $this->seamless_service_related_unique_id = $wager_transaction['seamless_service_unique_id'];
                $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
            }
        } else {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_BET_RECORD_NOT_EXIST, 'wager not exists');
            return false;
        }

        $this->params['game_code'] = !empty($wager_transaction['game_code']) ? $wager_transaction['game_code'] : null;

        if (isset($result['result']) && !$result['result']) {
            if ($query_type = $this->ssa_insert) {
                return false;
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($query_type) {
            return $this->walletAdjustment($this->ssa_increase, $query_type, $this->params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function freeSpinResult() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => false,
            'use_ssa_is_game_api_maintenance' => false,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'brand_id' => ['required'],
                'sign' => ['required'],
                'brand_uid' => ['required'],
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'game_id' => ['required'],
                'game_name' => ['required'],
                'round_id' => ['required'],
                'wager_id' => ['required'],
                'provider' => ['required'],
                'is_endround' => ['required', 'nullable'],
            ])) {
                $this->processFreeSpinResultRequest();
            } else {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processFreeSpinResultRequest($data = []) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if (!$this->validateSign()) {
            return false;
        }

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_wager_transaction' => false,
            'validate_cancel_wager_transaction' => false,
            'validate_append_wager_transaction' => false,
            'validate_end_wager_transaction' => false,
            'validate_free_spin_result_transaction' => false,
            'validate_promo_payout_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $query_type = $this->ssa_insert;

        if (!empty($free_spin_result_transaction)) {
            list($is_exist, $existing_transaction) = $this->isTransactionAlreadyExistInDB($free_spin_result_transaction, $this->params['transaction_id']);

            if ($is_exist) {
                if ($this->isTransactionfloating($existing_transaction)) {
                    $query_type = $this->ssa_update;
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_ALREADY_EXIST, 'Transaction already Exists');
                    return true;
                }
            }
        }

        if (isset($result['result']) && !$result['result']) {
            if ($query_type = $this->ssa_insert) {
                return false;
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($query_type) {
            return $this->walletAdjustment($this->ssa_increase, $query_type, $this->params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function promoPayout() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_ADJUSTMENT;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => true,
            'use_ssa_is_game_api_active' => false,
            'use_ssa_is_game_api_maintenance' => false,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'brand_id' => ['required'],
                'sign' => ['required'],
                'brand_uid' => ['required'],
                'currency' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'min:0'],
                'promotion_id' => ['required'],
                'trans_id' => ['required'],
                'provider' => ['required'],
            ])) {
                $this->processPromoPayoutRequest();
            } else {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_REQUEST_PARAM_ERROR, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processPromoPayoutRequest($data = []) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if (!$this->validateSign()) {
            return false;
        }

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_wager_transaction' => false,
            'validate_cancel_wager_transaction' => false,
            'validate_append_wager_transaction' => false,
            'validate_end_wager_transaction' => false,
            'validate_free_spin_result_transaction' => false,
            'validate_promo_payout_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $wager_transaction = !empty($result['wager_transaction']) ? $result['wager_transaction'] : [];
        $cancel_wager_transaction = !empty($result['cancel_wager_transaction']) ? $result['cancel_wager_transaction'] : [];
        $append_wager_transaction = !empty($result['append_wager_transaction']) ? $result['append_wager_transaction'] : [];
        $end_wager_transaction = !empty($result['end_wager_transaction']) ? $result['end_wager_transaction'] : [];
        $free_spin_result_transaction = !empty($result['free_spin_result_transaction']) ? $result['free_spin_result_transaction'] : [];
        $promo_payout_transaction = !empty($result['promo_payout_transaction']) ? $result['promo_payout_transaction'] : [];

        $query_type = $this->ssa_insert;

        if (!empty($promo_payout_transaction)) {
            list($is_exist, $existing_transaction) = $this->isTransactionAlreadyExistInDB($promo_payout_transaction, $this->params['transaction_id']);

            if ($is_exist) {
                if ($this->isTransactionfloating($existing_transaction)) {
                    $query_type = $this->ssa_update;
                } else {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_ALREADY_EXIST, 'Transaction already Exists');
                    return true;
                }
            }
        }

        if (isset($result['result']) && !$result['result']) {
            if ($query_type = $this->ssa_insert) {
                return false;
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($query_type) {
            return $this->walletAdjustment($this->ssa_increase, $query_type, $this->params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => isset($this->params['transaction_id']) ? $this->params['transaction_id'] : null,
            'round_id' => isset($this->params['round_id']) ? $this->params['round_id'] : null,
            'external_game_id' => !empty($this->params['game_code']) ? $this->params['game_code'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $this->api_method,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->ssa_original_request_params),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $this->params['external_unique_id'],
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