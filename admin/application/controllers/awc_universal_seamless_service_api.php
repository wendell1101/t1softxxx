<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Awc_universal_seamless_service_api extends BaseController {
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
    protected $rebuilded_operator_response = [];
    protected $get_usec = false;
    protected $use_strip_slashes = true;
    protected $allowed_negative_balance_api_methods = [];

    // additional
    protected $agent_id = null;
    protected $cert = null;
    protected $platform = null;
    protected $game_type = null;

    const RESPONSE_SUCCESS = [
        'code' => "0000",
        'message' => 'Success',
    ];

    const RESPONSE_FAILED = [
        'code' => "9999",
        'message' => 'Failed',
    ];

    const RESPONSE_SYSTEM_BUSY = [
        'code' => "9998",
        'message' => 'System Busy',
    ];

    const RESPONSE_PLATFORM_NOT_EXIST_IN_AGENT = [
        'code' => "11",
        'message' => 'Do not have this platform under your agent.',
    ];

    const RESPONSE_INVALID_USER_ID = [
        'code' => "1000",
        'message' => 'Invalid user Id',
    ];

    const RESPONSE_ACCOUNT_EXISTED = [
        'code' => "1001",
        'message' => 'Account existed',
    ];

    const RESPONSE_ACCOUNT_NOT_EXISTS = [
        'code' => "1002",
        'message' => 'Account is not exists',
    ];

    const RESPONSE_INVALID_CURRENCY = [
        'code' => "1004",
        'message' => 'Invalid Currency',
    ];

    const RESPONSE_LANGUAGE_NOT_EXISTS = [
        'code' => "1005",
        'message' => 'language is not exists',
    ];

    const RESPONSE_EMPTY_PT_SETTING = [
        'code' => "1006",
        'message' => 'PT Setting is empty!',
    ];

    const RESPONSE_INVALID_PT_SETTING = [
        'code' => "1007",
        'message' => 'Invalid PT setting with parent!',
    ];

    const RESPONSE_INVALID_TOKEN = [
        'code' => "1008",
        'message' => 'Invalid token!',
    ];

    const RESPONSE_INVALID_TIMEZONE = [
        'code' => "1009",
        'message' => 'Invalid timezone',
    ];

    const RESPONSE_INVALID_AMOUNT = [
        'code' => "1010",
        'message' => 'Invalid amount',
    ];

    const RESPONSE_INVALID_TXCODE = [
        'code' => "1011",
        'message' => 'Invalid txCode',
    ];

    const RESPONSE_HAS_PENDING_TRANSFER = [
        'code' => "1012",
        'message' => 'Has Pending Transfer',
    ];

    const RESPONSE_ACCOUNT_IS_LOCK = [
        'code' => "1013",
        'message' => 'Account is Lock',
    ];

    const RESPONSE_ACCOUNT_IS_SUSPENDED = [
        'code' => "1014",
        'message' => 'Account is Suspend',
    ];

    const RESPONSE_TXCODE_ALREADY_OPERATION = [
        'code' => "1016",
        'message' => 'TxCode already operation!',
    ];

    const RESPONSE_TXCODE_NOT_EXISTS = [
        'code' => "1017",
        'message' => 'TxCode is not exist',
    ];

    const RESPONSE_NOT_ENOUGH_BALANCE = [
        'code' => "1018",
        'message' => 'Not Enough Balance',
    ];

    const RESPONSE_NO_DATA = [
        'code' => "1019",
        'message' => 'No Data',
    ];

    const RESPONSE_INVALID_DATETIME_FORMAT = [
        'code' => "1024",
        'message' => 'Invalid date time format',
    ];

    const RESPONSE_INVALID_TRANSACTION_STATUS = [
        'code' => "1025",
        'message' => 'Invalid transaction status',
    ];

    const RESPONSE_INVALID_BET_LIMIT_SETTING = [
        'code' => "1026",
        'message' => 'Invalid bet limit setting',
    ];

    const RESPONSE_INVALID_CERTIFICATE = [
        'code' => "1027",
        'message' => 'Invalid Certificate',
    ];

    const RESPONSE_UNABLE_TO_PROCEED = [
        'code' => "1028",
        'message' => 'Unable to proceed. please try again later.',
    ];

    const RESPONSE_INVALID_IP_ADDRESS = [
        'code' => "1029",
        'message' => 'Invalid IP address.',
    ];

    const RESPONSE_INVALID_DEVICE_TO_CALL_API = [
        'code' => "1030",
        'message' => 'Invalid Device to call API.',
    ];

    const RESPONSE_SYSTEM_UNDER_MAINTENANCE = [
        'code' => "1031",
        'message' => 'System is under maintenance.',
    ];

    const RESPONSE_DUPLICATE_LOGIN = [
        'code' => "1032",
        'message' => 'Duplicate login.',
    ];

    const RESPONSE_INVALID_GAME = [
        'code' => "1033",
        'message' => 'Invalid Game',
    ];

    const RESPONSE_TIME_DOES_NOT_MEET = [
        'code' => "1034",
        'message' => 'Time does not meet.',
    ];

    const RESPONSE_INVALID_AGENT_ID = [
        'code' => "1035",
        'message' => 'Invalid Agent Id.',
    ];

    const RESPONSE_INVALID_PARAMETERS = [
        'code' => "1036",
        'message' => 'Invalid parameters.',
    ];

    const RESPONSE_INVALID_CUSTOMER_SETTING = [
        'code' => "1037",
        'message' => 'Invalid customer setting.',
    ];

    const RESPONSE_DUPLICATE_TRANSACTION = [
        'code' => "1038",
        'message' => 'Duplicate transaction.',
    ];

    const RESPONSE_TRANSACTION_NOT_FOUND = [
        'code' => "1039",
        'message' => 'Transaction not found.',
    ];

    const RESPONSE_REQUEST_TIMEOUT = [
        'code' => "1040",
        'message' => 'Request timeout.',
    ];

    const RESPONSE_HTTP_STATUS_ERROR = [
        'code' => "1041",
        'message' => 'HTTP Status error.',
    ];

    const RESPONSE_HTTP_RESPONSE_EMPTY = [
        'code' => "1042",
        'message' => 'HTTP Response is empty.',
    ];

    const RESPONSE_BET_HAS_CANCELLED = [
        'code' => "1043",
        'message' => 'Bet has cancelled.',
    ];

    const RESPONSE_INVALID_BET = [
        'code' => "1044",
        'message' => 'Invalid bet.',
    ];

    const RESPONSE_ADD_ACCOUNT_STATEMENT_FAILED = [
        'code' => "1045",
        'message' => 'Add account statement failed.',
    ];

    const RESPONSE_TRANSFER_FAILED = [
        'code' => "1046",
        'message' => 'Transfer Failed!',
    ];

    const RESPONSE_GAME_UNDER_MAINTENANCE = [
        'code' => "1047",
        'message' => 'Game is under maintenance.',
    ];

    const RESPONSE_INVALID_PLATFORM = [
        'code' => "1054",
        'message' => 'Invalid Platform',
    ];

    const RESPONSE_EMPTY_PARAMETER = [
        'code' => "1056",
        'message' => 'Parameter is empty',
    ];

    const API_METHOD_GET_BALANCE = 'getBalance';
    const API_METHOD_BET = 'bet';
    const API_METHOD_CANCEL_BET = 'cancelBet';
    const API_METHOD_ADJUST_BET = 'adjustBet';
    const API_METHOD_VOID_BET = 'voidBet';
    const API_METHOD_UNVOID_BET = 'unvoidBet';
    const API_METHOD_REFUND = 'refund';
    const API_METHOD_SETTLE = 'settle';
    const API_METHOD_UNSETTLE = 'unsettle';
    const API_METHOD_VOID_SETTLE = 'voidSettle';
    const API_METHOD_UNVOID_SETTLE = 'unvoidSettle';
    const API_METHOD_BET_AND_SETTLE = 'betNSettle';
    const API_METHOD_CANCEL_BET_AND_SETTLE = 'cancelBetNSettle';
    const API_METHOD_FREE_SPIN = 'freeSpin';
    const API_METHOD_GIVE = 'give';
    const API_METHOD_RESETTLE = 'resettle';

    const ALLOWED_API_METHODS = [
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL_BET,
        self::API_METHOD_ADJUST_BET,
        self::API_METHOD_VOID_BET,
        self::API_METHOD_UNVOID_BET,
        self::API_METHOD_REFUND,
        self::API_METHOD_SETTLE,
        self::API_METHOD_UNSETTLE,
        self::API_METHOD_VOID_SETTLE,
        self::API_METHOD_UNVOID_SETTLE,
        self::API_METHOD_BET_AND_SETTLE,
        self::API_METHOD_CANCEL_BET_AND_SETTLE,
        self::API_METHOD_FREE_SPIN,
        self::API_METHOD_GIVE,
        self::API_METHOD_RESETTLE,
    ];

    const SETTLE_TYPE_TRANSACTION_ID = 'platformTxId';
    const SETTLE_TYPE_REFERENCE_TRANSACTION_ID = 'refPlatformTxId';
    const SETTLE_TYPE_ROUND_ID = 'roundId';

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

        //addtional
        'platform',
        'game_name',
        'game_type',
        'bet_type',
        'game_info',
        'settle_type',
        'reference_transaction_id',
        'refund_transaction_id',
        'promotion_id',
        'promotion_type_id',
        'bet_time',
        'transaction_time',
        'update_time',

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

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = [];
        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);
        $this->setExtraParams();
        $this->setMainParams($this->extra_params);

        $this->allowed_negative_balance_api_methods = [
            self::API_METHOD_UNVOID_BET,
            self::API_METHOD_UNSETTLE,
        ];
    }

    public function index($game_platform_id = null) {
        $api_method = $this->getApiMethod();

        if ($this->initialize($game_platform_id, $api_method)) {
            return $this->$api_method(); 
        }

        return $this->response();
    }

    protected function initialize($game_platform_id, $api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->responseConfig();

        if (empty($game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (initialize empty $game_platform_id)');
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($game_platform_id);

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

            // additional
            $this->agent_id = $this->game_api->agent_id;
            $this->cert = $this->game_api->cert;
            $this->platform = $this->game_api->platform;
            $this->game_type = $this->game_api->game_type;

            $this->setExtraParams();
            $this->setMainParams($this->extra_params);
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (load_game_api)');
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Method ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    protected function responseConfig() {
        $this->transfer_type_api_methods = [
            self::API_METHOD_BET,
            self::API_METHOD_CANCEL_BET,
            self::API_METHOD_ADJUST_BET,
            self::API_METHOD_VOID_BET,
            self::API_METHOD_UNVOID_BET,
            self::API_METHOD_REFUND,
            self::API_METHOD_SETTLE,
            self::API_METHOD_UNSETTLE,
            self::API_METHOD_VOID_SETTLE,
            self::API_METHOD_UNVOID_SETTLE,
            self::API_METHOD_BET_AND_SETTLE,
            self::API_METHOD_CANCEL_BET_AND_SETTLE,
            self::API_METHOD_FREE_SPIN,
            self::API_METHOD_GIVE,
            self::API_METHOD_RESETTLE,
        ];

        $this->content_type_json_api_methods = [
            self::API_METHOD_BET,
            self::API_METHOD_CANCEL_BET,
            self::API_METHOD_ADJUST_BET,
            self::API_METHOD_VOID_BET,
            self::API_METHOD_UNVOID_BET,
            self::API_METHOD_REFUND,
            self::API_METHOD_SETTLE,
            self::API_METHOD_UNSETTLE,
            self::API_METHOD_VOID_SETTLE,
            self::API_METHOD_UNVOID_SETTLE,
            self::API_METHOD_BET_AND_SETTLE,
            self::API_METHOD_CANCEL_BET_AND_SETTLE,
            self::API_METHOD_FREE_SPIN,
            self::API_METHOD_GIVE,
            self::API_METHOD_RESETTLE,
        ];

        $this->content_type_xml_api_methods = [];

        $this->content_type_plain_text_api_methods = [];

        $this->save_data_api_methods = [
            self::API_METHOD_GET_BALANCE,
            self::API_METHOD_BET,
            self::API_METHOD_CANCEL_BET,
            self::API_METHOD_ADJUST_BET,
            self::API_METHOD_VOID_BET,
            self::API_METHOD_UNVOID_BET,
            self::API_METHOD_REFUND,
            self::API_METHOD_SETTLE,
            self::API_METHOD_UNSETTLE,
            self::API_METHOD_VOID_SETTLE,
            self::API_METHOD_UNVOID_SETTLE,
            self::API_METHOD_BET_AND_SETTLE,
            self::API_METHOD_CANCEL_BET_AND_SETTLE,
            self::API_METHOD_FREE_SPIN,
            self::API_METHOD_GIVE,
            self::API_METHOD_RESETTLE,
        ];

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

    protected function setMainParams($data = []) {
        // default
        $this->main_params['token'] = null;
        $this->main_params['game_username'] = !empty($data['game_username']) ? strtolower($data['game_username']) : null;
        $this->main_params['transaction_type'] = $this->api_method;
        $this->main_params['transaction_id'] = !empty($data['transaction_id']) ? $data['transaction_id'] : null;
        $this->main_params['game_code'] = !empty($data['game_code']) ? $data['game_code'] : null;
        $this->main_params['round_id'] = !empty($data['round_id']) ? $data['round_id'] : null;
        $this->main_params['currency'] = !empty($data['currency']) ? $data['currency'] : null;
        $this->main_params['amount'] = !empty($data['amount']) ? abs($data['amount']) : 0;

        $external_unique_id = $this->ssa_composer([
            $this->api_method,
            $this->main_params['transaction_id'],
        ]);

        if ($this->api_method == self::API_METHOD_REFUND && !empty($data['refund_transaction_id'])) {
            $external_unique_id = $this->ssa_composer([
                $this->api_method,
                $this->main_params['transaction_id'],
                $data['refund_transaction_id'],
            ]);
        }

        if (($this->api_method == self::API_METHOD_SETTLE || $this->api_method == self::API_METHOD_FREE_SPIN || $this->api_method == self::API_METHOD_RESETTLE) && !empty($data['reference_transaction_id'])) {
            $external_unique_id = $this->ssa_composer([
                $this->api_method,
                $this->main_params['transaction_id'],
                $data['reference_transaction_id'],
            ]);
        }

        $this->main_params['external_unique_id'] = !empty($this->main_params['transaction_id']) ? $external_unique_id : null;
    }

    protected function setExtraParams($request_params = []) {
        if (empty($request_params)) {
            $request_params = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : $this->parseMessageParams();
        }

        $this->extra_params['transaction_id'] = isset($request_params['platformTxId']) ? $request_params['platformTxId'] : null;

        if ($this->api_method == self::API_METHOD_GIVE) {
            $this->extra_params['transaction_id'] = isset($request_params['promotionTxId']) ? $request_params['promotionTxId'] : null;
        }

        $this->extra_params['cert'] = isset($this->ssa_request_params['key']) ? $this->ssa_request_params['key'] : null;
        $this->extra_params['game_username'] = isset($request_params['userId']) ? $request_params['userId'] : null;
        $this->extra_params['round_id'] = isset($request_params['roundId']) ? $request_params['roundId'] : null;
        $this->extra_params['currency'] = isset($request_params['currency']) ? $request_params['currency'] : null;
        $this->extra_params['platform'] = isset($request_params['platform']) ? $request_params['platform'] : null;
        $this->extra_params['game_code'] = isset($request_params['gameCode']) ? $request_params['gameCode'] : null;
        $this->extra_params['game_name'] = isset($request_params['gameName']) ? $request_params['gameName'] : null;
        $this->extra_params['game_type'] = isset($request_params['gameType']) ? $request_params['gameType'] : null;
        $this->extra_params['bet_type'] = isset($request_params['betType']) ? $request_params['betType'] : null;
        $this->extra_params['game_info'] = isset($request_params['gameInfo']) ? $request_params['gameInfo'] : null;
        $this->extra_params['bet_amount'] = isset($request_params['betAmount']) ? $request_params['betAmount'] : 0;
        $this->extra_params['win_amount'] = isset($request_params['winAmount']) ? $request_params['winAmount'] : 0;
        $this->extra_params['adjust_amount'] = isset($request_params['adjustAmount']) ? $request_params['adjustAmount'] : 0;
        $this->extra_params['give_amount'] = isset($request_params['amount']) ? $request_params['amount'] : 0;
        $this->extra_params['settle_type'] = isset($request_params['settleType']) ? $request_params['settleType'] : null;
        $this->extra_params['reference_transaction_id'] = isset($request_params['refPlatformTxId']) ? $request_params['refPlatformTxId'] : null;
        $this->extra_params['refund_transaction_id'] = isset($request_params['refundPlatformTxId']) ? $request_params['refundPlatformTxId'] : null;
        $this->extra_params['promotion_id'] = isset($request_params['promotionId']) ? $request_params['promotionId'] : null;
        $this->extra_params['promotion_type_id'] = isset($request_params['promotionTypeId']) ? $request_params['promotionTypeId'] : null;
        $this->extra_params['bet_time'] = isset($request_params['betTime']) ? $request_params['betTime'] : null;
        $this->extra_params['transaction_time'] = isset($request_params['txTime']) ? $request_params['txTime'] : null;
        $this->extra_params['update_time'] = isset($request_params['updateTime']) ? $request_params['updateTime'] : null;

        list($wallet_action, $amount) = $this->getWalletActionAndAmount($this->extra_params['bet_amount'], $this->extra_params['win_amount'], $this->extra_params['adjust_amount'], $this->extra_params['give_amount']);

        $this->extra_params['wallet_action'] = !empty($wallet_action) ? $wallet_action : null;
        $this->extra_params['amount'] = !empty($amount) ? $amount : 0;
    }

    protected function initializePlayerDetails($get_balance = false, $get_player_by = 'token', $use_main_params = true, $data = []) {
        if ($use_main_params) {
            $this->setMainParams($data);
        }

        if ($get_player_by == $this->ssa_subject_type_token) {
            // get player details by token
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $this->main_params['token'], $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::RESPONSE_INVALID_USER_ID;
                return false;
            } else {
                if (!empty($this->main_params['game_username']) && $this->main_params['game_username'] != $this->player_details['game_username']) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_INVALID_USER_ID;
                    $this->player_details = [];
                    return false;
                }
            }
        } elseif ($get_player_by == $this->ssa_subject_type_game_username) {
            // get player details by player game username
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $this->main_params['game_username'], $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::RESPONSE_INVALID_USER_ID;
                return false;
            } else {
                if (!empty($this->main_params['game_username']) && $this->main_params['game_username'] != $this->player_details['game_username']) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_INVALID_USER_ID;
                    $this->player_details = [];
                    return false;
                }
            }
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INVALID_USER_ID;
            $this->player_details = [];
            return false;
        }

        if ($get_balance) {
            $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, true);
        }

        return true;
    }

    protected function isPlayerBlocked() {
        if (isset($this->player_details['username']) && $this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_ACCOUNT_IS_LOCK, 'Player is blocked');
            return true;
        }

        return false;
    }

    protected function validatePlayer() {
        $this->validate_block_player_api_methods = [
            self::API_METHOD_GET_BALANCE,
            self::API_METHOD_BET,
            self::API_METHOD_FREE_SPIN,
            self::API_METHOD_GIVE,
        ];

        if ($this->isPlayerBlocked() && in_array($this->api_method, $this->validate_block_player_api_methods)) {
            return false;
        }

        return true;
    }

    protected function isServerIpAllowed() {
        if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_IP_ADDRESS, 'IP address is not allowed');
            return false;
        }

        return true;
    }

    protected function isGameApiActive() {
        if (!$this->ssa_is_game_api_active($this->game_api)) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_UNABLE_TO_PROCEED, 'Game is disabled');
            return false;
        }

        return true;
    }

    protected function isGameApiMaintenance() {
        if ($this->ssa_is_game_api_maintenance($this->game_api)) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_GAME_UNDER_MAINTENANCE, 'Game is under maintenance');
            return true;
        }

        return false;
    }

    protected function validateGameApi($config = [
        'use_ssa_is_server_ip_allowed' => 0,
        'use_ssa_is_game_api_active' => 0,
        'use_ssa_is_game_api_maintenance' => 0,
    ]) {

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
        if (!empty($this->main_params['language']) && $this->main_params['language'] != $this->language) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_LANGUAGE_NOT_EXISTS, 'Invalid parameter language');
            return false;
        }

        if (!empty($this->main_params['currency']) && $this->main_params['currency'] != $this->currency) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_CURRENCY, 'Invalid parameter currency');
            return false;
        }

        // additional
        if (!empty($this->extra_params['cert']) && $this->extra_params['cert'] != $this->cert) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid parameter key (cert)');
            return false;
        }

        if (!empty($this->extra_params['platform']) && $this->extra_params['platform'] != $this->platform) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PLATFORM, 'Invalid parameter platform');
            return false;
        }

        if (!empty($this->extra_params['game_type']) && $this->extra_params['game_type'] != $this->game_type) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid parameter game type');
            return false;
        }

        return true;
    }

    protected function isTransactionExists($use_request_id = false) {
        if ($use_request_id) {
            if ($this->isRequestUniqueIdExists()) {
                return true;
            }
        }

        $where = "(external_unique_id='{$this->main_params['external_unique_id']}' AND wallet_adjustment_status IN ('{$this->ssa_decreased}', '{$this->ssa_increased}', '{$this->ssa_retained}'))";

        $this->transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->transaction_already_exists) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        return false;
    }

    protected function isRequestUniqueIdExists() {
        if ($this->ssa_is_transaction_exists($this->transaction_table, [
            'package_id' => !empty($this->extra_params['package_id']) ? $this->extra_params['package_id'] : null,
        ])) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        return false;
    }

    protected function isBetExists() {
        if ($this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_BET,
            'transaction_id' => !empty($this->extra_params['transaction_id']) ? $this->extra_params['transaction_id'] : null,
        ])) {
            return true;
        } else {
            return false;
        }
    }

    protected function isPayoutExists() {
        if ($this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_SETTLE,
            'transaction_id' => !empty($this->extra_params['transaction_id']) ? $this->extra_params['transaction_id'] : null,
        ])) {
            return true;
        } else {
            return false;
        }
    }

    protected function isRefundExists() {
        if ($this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_REFUND,
            'transaction_id' => !empty($this->extra_params['transaction_id']) ? $this->extra_params['transaction_id'] : null,
        ])) {
            return true;
        } else {
            return false;
        }
    }

    protected function validateTransactionRecords() {
        if ($this->isTransactionExists()) {
            return false;
        }

        if (!$this->isBetExists()) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_NOT_FOUND, 'Bet not exist');
            return false;
        }

        if ($this->isPayoutExists()) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already settled');
            return false;
        }

        if ($this->isRefundExists()) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already refunded');
            return false;
        }

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_NOT_ENOUGH_BALANCE, 'Insufficient balance');
            return true;
        }

        return false;
    }

    protected function walletAdjustment($adjustment_type, $query_type, $amount) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->seamless_service_unique_id = $this->ssa_composer([$this->game_platform_id, $this->main_params['external_unique_id']]);
        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->main_params['game_code']);

        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance;

        $transaction_data = [
            // default
            'saved_transaction_id' => $this->saved_transaction_id,
            'amount' => $amount,
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
            'wallet_adjustment_status' => $this->ssa_preserved,
        ];

        if ($amount < 0) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_AMOUNT, 'Invalid amount');
            return false;
        }

        if ($query_type == $this->ssa_insert) {
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
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            $transaction_data['saved_transaction_id'] = $this->saved_transaction_id;

            if ($this->saved_transaction_id) {
                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'data has been saved.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

                $after_balance = null;

                if ($amount == 0) {
                    $transaction_data['wallet_adjustment_status'] = $this->ssa_retained;
                    $success = true;
                } else {
                    if ($adjustment_type == $this->ssa_decrease) {
                        $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);

                        if ($success) {
                            $transaction_data['after_balance'] = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, true);
                            $transaction_data['wallet_adjustment_status'] = $this->ssa_decreased;
                        } else {
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (decrease balance)');
                            $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;
                        }
                    } elseif ($adjustment_type == $this->ssa_increase) {
                        $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
        
                        if ($success) {
                            $transaction_data['after_balance'] = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false, true);
                            $transaction_data['wallet_adjustment_status'] = $this->ssa_increased;
                        } else {
                            $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (increase balance)');
                        }
                    } else {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (walletAdjustment default)');
                        return false;
                    }
                }

                array_push($this->saved_multiple_transactions, $transaction_data);
                return $success;
            } else {
                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save data.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (saveTransactionRequestData)');
                return false;
            }
        }

        return false;
    }

    protected function rebuildTransactionRequestData($query_type, $transaction_data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

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
                // addtional
                'platform' => isset($this->extra_params['platform']) ? $this->extra_params['platform'] : null,
                'game_name' => isset($this->extra_params['game_name']) ? $this->extra_params['game_name'] : null,
                'game_type' => isset($this->extra_params['game_type']) ? $this->extra_params['game_type'] : null,
                'bet_type' => isset($this->extra_params['bet_type']) ? $this->extra_params['bet_type'] : null,
                'settle_type' => isset($this->extra_params['settle_type']) ? $this->extra_params['settle_type'] : null,
                'game_info' => isset($this->extra_params['game_info']) ? json_encode($this->extra_params['game_info']) : null,
                'reference_transaction_id' => isset($this->extra_params['reference_transaction_id']) ? $this->extra_params['reference_transaction_id'] : null,
                'refund_transaction_id' => isset($this->extra_params['refund_transaction_id']) ? $this->extra_params['refund_transaction_id'] : null,
                'promotion_id' => isset($this->extra_params['promotion_id']) ? $this->extra_params['promotion_id'] : null,
                'promotion_type_id' => isset($this->extra_params['promotion_type_id']) ? $this->extra_params['promotion_type_id'] : null,
                'bet_time' => isset($this->extra_params['bet_time']) ? $this->extra_params['bet_time'] : null,
                'transaction_time' => isset($this->extra_params['transaction_time']) ? $this->extra_params['transaction_time'] : null,
                'update_time' => isset($this->extra_params['update_time']) ? $this->extra_params['update_time'] : null,

                // default
                'elapsed_time' => $this->utils->getCostMs(),
                'request' => !empty($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
                'response' => null,
                'extra_info' => null,
                'bet_amount' => isset($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : 0,
                'win_amount' => isset($this->extra_params['win_amount']) ? $this->extra_params['win_amount'] : 0,
                'result_amount' => isset($this->extra_params['result_amount']) ? $this->extra_params['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->extra_params['flag_of_updated_result']) ? $this->extra_params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
                'wallet_adjustment_status' => $transaction_data['wallet_adjustment_status'],
                'external_unique_id' => $this->main_params['external_unique_id'],
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
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

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        return $new_transaction_data;
    }

    protected function saveTransactionRequestData($query_type, $transaction_data) {
        $new_transaction_data = $this->rebuildTransactionRequestData($query_type, $transaction_data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $new_transaction_data, 'external_unique_id', $this->main_params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    protected function setStatus() {
        switch ($this->api_method) {
            case self::API_METHOD_BET:
            case self::API_METHOD_ADJUST_BET:
            case self::API_METHOD_UNVOID_BET:
            case self::API_METHOD_UNSETTLE:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::API_METHOD_CANCEL_BET:
            case self::API_METHOD_CANCEL_BET_AND_SETTLE:
                $result = Game_logs::STATUS_CANCELLED;
                break;
            case self::API_METHOD_VOID_BET:
            case self::API_METHOD_VOID_SETTLE:
                $result = Game_logs::STATUS_VOID;
                break;
            case self::API_METHOD_REFUND:
                $result = Game_logs::STATUS_REFUND;
                break;
            case self::API_METHOD_SETTLE:
            case self::API_METHOD_UNVOID_SETTLE:
            case self::API_METHOD_BET_AND_SETTLE:
            case self::API_METHOD_FREE_SPIN:
            case self::API_METHOD_GIVE:
            case self::API_METHOD_RESETTLE:
                $result = Game_logs::STATUS_SETTLED;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    protected function rebuildOperatorResponse($flag, $operator_response) {
        $is_normal = $flag == Response_result::FLAG_NORMAL;
        $code = isset($operator_response['code']) ? $operator_response['code'] : self::RESPONSE_FAILED['code'];
        $message = isset($operator_response['message']) ? $operator_response['message'] : self::RESPONSE_FAILED['message'];
        $balance = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
        $game_username = isset($this->player_details['game_username']) ? $this->player_details['game_username'] : null;
        $balanceTs = $this->getBalanceTs();

        $operator_response = [
            'status' => $code,
            'desc' => $message,
        ];

        if ($is_normal && $code == self::RESPONSE_SUCCESS['code']) {
            $operator_response = [
                'status' => $code,
                'userId' => $game_username,
                'balance' => $balance,
                'balanceTs' => $balanceTs,
            ];
        }

        $this->rebuilded_operator_response = $operator_response;

        return $operator_response;
    }

    protected function finalizeTransactionData() {
        if ($this->is_transfer_type) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            if (!empty($this->saved_multiple_transactions) && is_array($this->saved_multiple_transactions)) {
                foreach ($this->saved_multiple_transactions as $transaction_data) {
                    $saved_transaction_id = isset($transaction_data['saved_transaction_id']) ? $transaction_data['saved_transaction_id'] : null;
                    $after_balance = isset($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
                    $wallet_adjustment_status = isset($transaction_data['wallet_adjustment_status']) ? $transaction_data['wallet_adjustment_status'] : $this->ssa_preserved;
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);
    
                    if (!empty($saved_transaction_id)) {
                        $data = [
                            'after_balance' => $after_balance,
                            'wallet_adjustment_status' => $wallet_adjustment_status,
                            'response' => $operator_response,
                            'response_result_id' => $this->response_result_id,
                        ];

                        $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'id', $saved_transaction_id);
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
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::RESPONSE_FAILED['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::RESPONSE_FAILED['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

            $md5_data = $data = [
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
                'request' => !empty($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
                'response' => $operator_response,
                'extra_info' => null,
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
                'external_unique_id' => !empty($this->main_params['external_unique_id']) ? $this->main_params['external_unique_id'] : null,
                // addtional
                'platform' => isset($this->extra_params['platform']) ? $this->extra_params['platform'] : null,
                'game_name' => isset($this->extra_params['game_name']) ? $this->extra_params['game_name'] : null,
                'game_type' => isset($this->extra_params['game_type']) ? $this->extra_params['game_type'] : null,
                'bet_type' => isset($this->extra_params['bet_type']) ? $this->extra_params['bet_type'] : null,
                'game_info' => isset($this->extra_params['game_info']) ? json_encode($this->extra_params['game_info']) : null,
                'settle_type' => isset($this->extra_params['settle_type']) ? $this->extra_params['settle_type'] : null,
                'reference_transaction_id' => isset($this->extra_params['reference_transaction_id']) ? $this->extra_params['reference_transaction_id'] : null,
                'refund_transaction_id' => isset($this->extra_params['refund_transaction_id']) ? $this->extra_params['refund_transaction_id'] : null,
                'promotion_id' => isset($this->extra_params['promotion_id']) ? $this->extra_params['promotion_id'] : null,
                'promotion_type_id' => isset($this->extra_params['promotion_type_id']) ? $this->extra_params['promotion_type_id'] : null,
                'bet_time' => isset($this->extra_params['bet_time']) ? $this->extra_params['bet_time'] : null,
                'transaction_time' => isset($this->extra_params['transaction_time']) ? $this->extra_params['transaction_time'] : null,
                'update_time' => isset($this->extra_params['update_time']) ? $this->extra_params['update_time'] : null,
            ];

            unset($md5_data['response'], $md5_data['extra_info']);

            $data['md5_sum'] = md5(json_encode($md5_data));
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

    protected function getApiMethod() {
        $request_params = $this->parseMessageParams();
        return isset($request_params['action']) ? $request_params['action'] : null;
    }

    protected function parseMessageParams() {
        if (isset($this->ssa_request_params['message'])) {
            if (is_array($this->ssa_request_params['message'])) {
                $params = $this->ssa_request_params['message'];
            } else {
                $params = json_decode($this->ssa_request_params['message'], true);
            }
        } else {
            $params = [];
        }

        return $params;
    }

    protected function getBalanceTs() {
        $t = microtime(true);
        $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
        $balanceTs = $this->ssa_format_dateTime('Y-m-d\TH:i:s.', $this->utils->getNowForMysql()) . substr($micro, 0, 3) . $this->ssa_format_dateTime('P', $this->utils->getNowForMysql());

        return $balanceTs;
    }

    protected function getWalletActionAndAmount($bet_amount = 0, $win_amount = 0, $adjust_amount = 0, $give_amount = 0) {
        $amount = 0;

        switch ($this->api_method) {
            case self::API_METHOD_ADJUST_BET:
                $amount = $adjust_amount;
                break;
            case self::API_METHOD_REFUND:
            case self::API_METHOD_BET_AND_SETTLE:
                $amount = $win_amount - $bet_amount;
                break;
            case self::API_METHOD_SETTLE:
            case self::API_METHOD_FREE_SPIN:
            case self::API_METHOD_RESETTLE:
                $amount = $win_amount;
                break;
            case self::API_METHOD_GIVE:
                $amount = $give_amount;
                break;
            default:
                $amount = $bet_amount;
                break;
        }

        if ($amount >= 0) {
            $wallet_action = $this->ssa_increase;
        } else {
            $wallet_action = $this->ssa_decrease;
        }

        return array($wallet_action, abs($amount));
    }

    protected function getRoundTransactions($use_function = true) {
        $transaction_count = 0;
        $get_transactions = [];
        $bet_transaction = [];
        $cancel_bet_transaction = [];
        $adjust_bet_transaction = [];
        $void_bet_transaction = [];
        $unvoid_bet_transaction = [];
        $refund_transaction = [];
        $settle_transaction = [];
        $unsettle_transaction = [];
        $void_settle_transaction = [];
        $unvoid_settle_transaction = [];
        $bet_and_settle_transaction = [];
        $cancel_bet_and_settle_transaction = [];
        $free_spin_transaction = [];
        $give_transaction = [];
        $resettle_transaction = [];

        if ($use_function) {
            $transaction_id = $this->main_params['transaction_id'];

            if ($this->api_method == self::API_METHOD_REFUND && !empty($this->extra_params['refund_transaction_id'])) {
                $transaction_id = $this->extra_params['refund_transaction_id'];
            }

            if (($this->api_method == self::API_METHOD_SETTLE || $this->api_method == self::API_METHOD_FREE_SPIN || $this->api_method == self::API_METHOD_RESETTLE) && !empty($this->extra_params['reference_transaction_id'])) {
                $transaction_id = $this->extra_params['reference_transaction_id'];
            }

            $where = "(player_id='{$this->player_details['player_id']}' AND transaction_id='{$transaction_id}' AND wallet_adjustment_status IN ('{$this->ssa_decreased}', '{$this->ssa_increased}', '{$this->ssa_retained}'))";

            $selected_columns = [
                'transaction_type',
                'transaction_id',
                'game_code',
                'round_id',
                'amount',
                'bet_amount',
                'update_time',
            ];

            $get_transactions = $this->ssa_get_transactions($this->transaction_table, $where, $selected_columns);

            if (!empty($get_transactions) && is_array($get_transactions)) {
                $transaction_count = count($get_transactions);

                foreach ($get_transactions as $transaction) {
                    if ($transaction['transaction_type'] == self::API_METHOD_BET) {
                        array_push($bet_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_CANCEL_BET) {
                        array_push($cancel_bet_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_ADJUST_BET) {
                        array_push($adjust_bet_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_VOID_BET) {
                        array_push($void_bet_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_UNVOID_BET) {
                        array_push($unvoid_bet_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_REFUND) {
                        array_push($refund_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_SETTLE) {
                        array_push($settle_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_UNSETTLE) {
                        array_push($unsettle_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_VOID_SETTLE) {
                        array_push($void_settle_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_UNVOID_SETTLE) {
                        array_push($unvoid_settle_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_BET_AND_SETTLE) {
                        array_push($bet_and_settle_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_CANCEL_BET_AND_SETTLE) {
                        array_push($cancel_bet_and_settle_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_FREE_SPIN) {
                        array_push($free_spin_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_GIVE) {
                        array_push($give_transaction, $transaction);
                    }

                    if ($transaction['transaction_type'] == self::API_METHOD_RESETTLE) {
                        array_push($resettle_transaction, $transaction);
                    }
                }
            }

            if (empty($refund_transaction)) {
                $refund_transaction = $this->ssa_get_transaction($this->transaction_table, [
                    'player_id' => $this->player_details['player_id'],
                    'refund_transaction_id' => $this->main_params['transaction_id'],
                ]);
            }
        }

        $result = [
            'get_transactions' => $get_transactions,
            'transaction_count' => $transaction_count,
            'bet_transaction' => $bet_transaction,
            'cancel_bet_transaction' => $cancel_bet_transaction,
            'adjust_bet_transaction' => $adjust_bet_transaction,
            'void_bet_transaction' => $void_bet_transaction,
            'unvoid_bet_transaction' => $unvoid_bet_transaction,
            'refund_transaction' => $refund_transaction,
            'settle_transaction' => $settle_transaction,
            'unsettle_transaction' => $unsettle_transaction,
            'void_settle_transaction' => $void_settle_transaction,
            'unvoid_settle_transaction' => $unvoid_settle_transaction,
            'bet_and_settle_transaction' => $bet_and_settle_transaction,
            'cancel_bet_and_settle_transaction' => $cancel_bet_and_settle_transaction,
            'free_spin_transaction' => $free_spin_transaction,
            'give_transaction' => $give_transaction,
            'resettle_transaction' => $resettle_transaction,
        ];

        return $result;
    }

    protected function getTransactionsAndvalidateExistence($config = [
        'validate_bet_transaction' => false,
        'validate_cancel_bet_transaction' => false,
        'validate_adjust_bet_transaction' => false,
        'validate_void_bet_transaction' => false,
        'validate_unvoid_bet_transaction' => false,
        'validate_refund_transaction' => false,
        'validate_settle_transaction' => false,
        'validate_unsettle_transaction' => false,
        'validate_void_settle_transaction' => false,
        'validate_unvoid_settle_transaction' => false,
        'validate_bet_and_settle_transaction' => false,
        'validate_cancel_bet_and_settle_transaction' => false,
        'validate_free_spin_transaction' => false,
        'validate_give_transaction' => false,
        'validate_resettle_transaction' => false,
    ]) {

        $config['validate_bet_transaction'] = isset($config['validate_bet_transaction']) ? $config['validate_bet_transaction'] : false;
        $config['validate_cancel_bet_transaction'] = isset($config['validate_cancel_bet_transaction']) ? $config['validate_cancel_bet_transaction'] : false;
        $config['validate_adjust_bet_transaction'] = isset($config['validate_adjust_bet_transaction']) ? $config['validate_adjust_bet_transaction'] : false;
        $config['validate_void_bet_transaction'] = isset($config['validate_void_bet_transaction']) ? $config['validate_void_bet_transaction'] : false;
        $config['validate_unvoid_bet_transaction'] = isset($config['validate_unvoid_bet_transaction']) ? $config['validate_unvoid_bet_transaction'] : false;
        $config['validate_refund_transaction'] = isset($config['validate_refund_transaction']) ? $config['validate_refund_transaction'] : false;
        $config['validate_settle_transaction'] = isset($config['validate_settle_transaction']) ? $config['validate_settle_transaction'] : false;
        $config['validate_unsettle_transaction'] = isset($config['validate_unsettle_transaction']) ? $config['validate_unsettle_transaction'] : false;
        $config['validate_void_settle_transaction'] = isset($config['validate_void_settle_transaction']) ? $config['validate_void_settle_transaction'] : false;
        $config['validate_unvoid_settle_transaction'] = isset($config['validate_unvoid_settle_transaction']) ? $config['validate_unvoid_settle_transaction'] : false;
        $config['validate_bet_and_settle_transaction'] = isset($config['validate_bet_and_settle_transaction']) ? $config['validate_bet_and_settle_transaction'] : false;
        $config['validate_cancel_bet_and_settle_transaction'] = isset($config['validate_cancel_bet_and_settle_transaction']) ? $config['validate_cancel_bet_and_settle_transaction'] : false;
        $config['validate_free_spin_transaction'] = isset($config['validate_free_spin_transaction']) ? $config['validate_free_spin_transaction'] : false;
        $config['validate_give_transaction'] = isset($config['validate_give_transaction']) ? $config['validate_give_transaction'] : false;
        $config['validate_resettle_transaction'] = isset($config['validate_resettle_transaction']) ? $config['validate_resettle_transaction'] : false;

        $result = $this->getRoundTransactions();
        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

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

        if ($config['validate_bet_transaction']) {
            if (empty($bet_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'Bet not found');
                return $result_false;
            }

            if (!empty($bet_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $bet_transaction['game_code']) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
                return $result_false;
            }

            if (!empty($bet_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $bet_transaction['round_id']) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
                return $result_false;
            }
        }

        if ($config['validate_cancel_bet_transaction']) {
            if (!empty($cancel_bet_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already cancel bet');
                return $result_false;
            }
        }

        if ($config['validate_adjust_bet_transaction']) {
            if (!empty($adjust_bet_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already adjust bet');
                return $result_false;
            }
        }

        if ($config['validate_void_bet_transaction']) {
            if (!empty($void_bet_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already void bet');
                return $result_false;
            }
        }

        if ($config['validate_unvoid_bet_transaction']) {
            if (!empty($unvoid_bet_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already unvoid bet');
                return $result_false;
            }
        }

        if ($config['validate_refund_transaction']) {
            if (!empty($refund_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already refund');
                return $result_false;
            }
        }

        if ($config['validate_settle_transaction']) {
            if (!empty($settle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already settle');
                return $result_false;
            }
        }

        if ($config['validate_unsettle_transaction']) {
            if (!empty($unsettle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already unsettle');
                return $result_false;
            }
        }

        if ($config['validate_void_settle_transaction']) {
            if (!empty($void_settle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already void settle');
                return $result_false;
            }
        }

        if ($config['validate_unvoid_settle_transaction']) {
            if (!empty($unvoid_settle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already unvoid settle');
                return $result_false;
            }
        }

        if ($config['validate_bet_and_settle_transaction']) {
            if (!empty($bet_and_settle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already bet and settle');
                return $result_false;
            }
        }

        if ($config['validate_cancel_bet_and_settle_transaction']) {
            if (!empty($cancel_bet_and_settle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already cancel bet and settle');
                return $result_false;
            }
        }

        if ($config['validate_free_spin_transaction']) {
            if (!empty($free_spin_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already free spin');
                return $result_false;
            }
        }

        if ($config['validate_give_transaction']) {
            if (!empty($give_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already give');
                return $result_false;
            }
        }

        if ($config['validate_resettle_transaction']) {
            if (!empty($resettle_transaction)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_ALREADY_OPERATION, 'Already resettle');
                return $result_false;
            }
        }

        return $result_true;
    }

    protected function getBalance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $this->setExtraParams();
        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'userId' => ['required'],
                ])) {
                    if ($this->initializePlayerDetails(false, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
                        if ($this->validatePlayer()) {
                            if ($this->validateParams()) {
                                $self = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($self) {
                                    $result = $self->ssa_get_player_wallet_balance($self->player_details['player_id'], $self->game_platform_id, true, true);

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
                                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Error in getting balance');
                                }
                            }
                        }
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function bet() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processBetRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processBetRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processBetRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'betTime' => ['required'],
            'betType' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => false,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => false,
            'validate_refund_transaction' => false,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => false,
            'validate_unvoid_settle_transaction' => false,
            'validate_bet_and_settle_transaction' => false,
            'validate_cancel_bet_and_settle_transaction' => false,
            'validate_free_spin_transaction' => false,
            'validate_give_transaction' => false,
            'validate_resettle_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($bet_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (!empty($cancel_bet_transaction)) {
            $this->main_params['amount'] = 0;
        }

        $this->extra_params['bet_amount'] = $this->main_params['amount'];
        $this->extra_params['result_amount'] = -$this->main_params['amount'];

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function cancelBet() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processCancelBetRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processCancelBetRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processCancelBetRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => false,
            'validate_adjust_bet_transaction' => true,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => true,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($cancel_bet_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (!empty($adjust_bet_transaction)) {
            $adjust_bet_transaction = end($adjust_bet_transaction);
            $this->main_params['amount'] = !empty($adjust_bet_transaction['bet_amount']) ? $adjust_bet_transaction['bet_amount'] : 0;
        } else {
            $bet_transaction = end($bet_transaction);
            $this->main_params['amount'] = !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function adjustBet() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processAdjustBetRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processAdjustBetRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processAdjustBetRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'adjustAmount' => ['required', 'nullable', 'numeric'],
            'updateTime' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => true,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => true,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($adjust_bet_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function voidBet() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processVoidBetRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processVoidBetRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processVoidBetRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'updateTime' => ['required'],
            'voidType' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => true,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($void_bet_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (!empty($adjust_bet_transaction)) {
            $adjust_bet_transaction = end($adjust_bet_transaction);
            $this->main_params['amount'] = !empty($adjust_bet_transaction['bet_amount']) ? $adjust_bet_transaction['bet_amount'] : 0;
        } else {
            $bet_transaction = end($bet_transaction);
            $this->main_params['amount'] = !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function unvoidBet() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processUnvoidBetRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processUnvoidBetRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processUnvoidBetRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'updateTime' => ['required'],
            'voidType' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => true,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => false,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($unvoid_bet_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (empty($void_bet_transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'void bet not found');
            return false;
        }

        $void_bet_transaction = end($void_bet_transaction);

        if (!empty($void_bet_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $void_bet_transaction['game_code']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
            return false;
        }

        if (!empty($void_bet_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $void_bet_transaction['round_id']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
            return false;
        }

        $this->main_params['amount'] = !empty($void_bet_transaction['amount']) ? $void_bet_transaction['amount'] : 0;

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function refund() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processRefundRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processRefundRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processRefundRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betType' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'winAmount' => ['required', 'nullable', 'numeric'],
            'turnover' => ['required', 'nullable', 'numeric'],
            'betTime' => ['required'],
            'updateTime' => ['required'],
            'refundPlatformTxId' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => true,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => false,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (empty($refund_transaction)) {
            $refund_transaction = $this->ssa_get_transactions($this->transaction_table, ['transaction_id' => $this->main_params['transaction_id']]);
        }

        if (!empty($refund_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function settle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processSettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processSettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processSettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betType' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'winAmount' => ['required', 'nullable', 'numeric'],
            'turnover' => ['required', 'nullable', 'numeric'],
            'betTime' => ['required'],
            'updateTime' => ['required'],
            'txTime' => ['required'],
            'settleType' => ['required'],
            'refPlatformTxId' => ['optional'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => true,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => false,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($settle_transaction)) {
            $last_settle_transaction = end($settle_transaction);

            if ($last_settle_transaction['update_time'] == $this->extra_params['update_time']) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
                return true;
            } else {
                $this->main_params['external_unique_id'] .= '-' . count($settle_transaction);
            }
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function unsettle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processUnsettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processUnsettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processUnsettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'updateTime' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($unsettle_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (empty($settle_transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'settle not found');
            return false;
        }

        $settle_transaction = end($settle_transaction);

        if (!empty($settle_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $settle_transaction['game_code']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
            return false;
        }

        if (!empty($settle_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $settle_transaction['round_id']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
            return false;
        }

        $this->main_params['amount'] = !empty($settle_transaction['amount']) ? $settle_transaction['amount'] : 0;

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function voidSettle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processVoidSettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processVoidSettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processVoidSettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'updateTime' => ['required'],
            'voidType' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => false,
            'validate_unvoid_settle_transaction' => false,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($void_settle_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (empty($settle_transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'settle not found');
            return false;
        }

        $settle_transaction = end($settle_transaction);

        if (!empty($settle_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $settle_transaction['game_code']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
            return false;
        }

        if (!empty($settle_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $settle_transaction['round_id']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
            return false;
        }

        $bet_amount = !empty($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : 0;
        $win_amount = !empty($settle_transaction['amount']) ? $settle_transaction['amount'] : 0;

        $this->main_params['amount'] = $win_amount - $bet_amount;

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function unvoidSettle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processUnvoidSettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processUnvoidSettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processUnvoidSettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'updateTime' => ['required'],
            'voidType' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => false,
            'validate_unvoid_settle_transaction' => false,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($unvoid_settle_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (empty($void_settle_transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'void settle not found');
            return false;
        }

        $void_settle_transaction = end($void_settle_transaction);

        if (!empty($void_settle_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $void_settle_transaction['game_code']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
            return false;
        }

        if (!empty($void_settle_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $void_settle_transaction['round_id']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
            return false;
        }

        $this->main_params['amount'] = !empty($void_settle_transaction['amount']) ? $void_settle_transaction['amount'] : 0;

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function betNSettle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processBetNSettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processBetNSettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processBetNSettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betType' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'winAmount' => ['required', 'nullable', 'numeric'],
            'turnover' => ['required', 'nullable', 'numeric'],
            'betTime' => ['required'],
            'updateTime' => ['required'],
            'txTime' => ['required'],
            'requireAmount' => ['optional'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => false,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => false,
            'validate_refund_transaction' => false,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => false,
            'validate_unvoid_settle_transaction' => false,
            'validate_bet_and_settle_transaction' => false,
            'validate_cancel_bet_and_settle_transaction' => false,
            'validate_free_spin_transaction' => false,
            'validate_give_transaction' => false,
            'validate_resettle_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($bet_and_settle_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        $this->extra_params['result_amount'] = isset($this->extra_params['wallet_action']) && $this->extra_params['wallet_action'] == $this->ssa_decrease ? -$this->main_params['amount'] : $this->main_params['amount'];

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->extra_params['wallet_action'], $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function cancelBetNSettle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processCancelBetNSettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processCancelBetNSettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processCancelBetNSettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'updateTime' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => false,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => false,
            'validate_refund_transaction' => false,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => false,
            'validate_unvoid_settle_transaction' => false,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => false,
            'validate_free_spin_transaction' => false,
            'validate_give_transaction' => false,
            'validate_resettle_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($cancel_bet_and_settle_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (empty($bet_and_settle_transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'bet and settle not found');
            return false;
        }

        $bet_and_settle_transaction = end($bet_and_settle_transaction);

        if (!empty($bet_and_settle_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $bet_and_settle_transaction['game_code']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
            return false;
        }

        if (!empty($bet_and_settle_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $bet_and_settle_transaction['round_id']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
            return false;
        }

        if (!empty($adjust_bet_transaction)) {
            $this->main_params['amount'] = !empty($adjust_bet_transaction['bet_amount']) ? $adjust_bet_transaction['bet_amount'] : 0;
        } else {
            $this->main_params['amount'] = !empty($bet_and_settle_transaction['amount']) ? $bet_and_settle_transaction['amount'] : 0;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function freeSpin() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processFreeSpinRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processFreeSpinRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processFreeSpinRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betType' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'winAmount' => ['required', 'nullable', 'numeric'],
            'turnover' => ['required', 'nullable', 'numeric'],
            'betTime' => ['required'],
            'updateTime' => ['required'],
            'refPlatformTxId' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => true,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => false,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => true,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($free_spin_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function give() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processGiveRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processGiveRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processGiveRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'txTime' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'],
            'currency' => ['required'],
            'promotionTxId' => ['required'],
            'promotionId' => ['required'],
            'promotionTypeId' => ['required'],
            'userId' => ['required'],
            'platform' => ['required'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => false,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => false,
            'validate_unvoid_bet_transaction' => false,
            'validate_refund_transaction' => false,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => false,
            'validate_void_settle_transaction' => false,
            'validate_unvoid_settle_transaction' => false,
            'validate_bet_and_settle_transaction' => false,
            'validate_cancel_bet_and_settle_transaction' => false,
            'validate_free_spin_transaction' => false,
            'validate_give_transaction' => false,
            'validate_resettle_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($give_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }

    protected function resettle() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'key' => ['required'],
                'message' => ['required'],
            ])) {
                if ($this->ssa_validate_request_params($this->parseMessageParams(), [
                    'action' => ['required'],
                    'txns' => ['required'],
                ])) {
                    $transactions = isset($this->parseMessageParams()['txns']) ? $this->parseMessageParams()['txns'] : [];

                    if (!empty($transactions)) {
                        if (is_array($transactions) && $this->ssa_is_multidimensional_array($transactions)) {
                            foreach ($transactions as $transaction) {
                                $success = $this->processResettleRequest($transaction);
                                
                                if (!$success) {
                                    break;
                                }
                            }
                        } else {
                            $this->processResettleRequest($transactions);
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, 'Empty txns');
                    }
                } else {
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    protected function processResettleRequest($transaction) {
        $success = false;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Internal Server Error (' . __FUNCTION__ . ')');

        $rule_sets = [
            'platformTxId' => ['required'],
            'userId' => ['required'],
            'currency' => ['optional'],
            'platform' => ['required'],
            'gameType' => ['required'],
            'gameCode' => ['required'],
            'roundId' => ['required'],
            'gameInfo' => ['optional'],
            'gameName' => ['required'],
            'betType' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'winAmount' => ['required', 'nullable', 'numeric'],
            'turnover' => ['required', 'nullable', 'numeric'],
            'betTime' => ['required'],
            'updateTime' => ['required'],
            'txTime' => ['required'],
            'settleType' => ['required'],
            'refPlatformTxId' => ['optional'],
        ];

        if (!$this->ssa_validate_request_params($transaction, $rule_sets)) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_EMPTY_PARAMETER, $this->ssa_custom_message_response);
            return false;
        }

        $this->setExtraParams($transaction);

        if (!$this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
            return false;
        }

        if (!$this->validatePlayer()) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        $result = $this->getTransactionsAndvalidateExistence([
            'validate_bet_transaction' => false,
            'validate_cancel_bet_transaction' => true,
            'validate_adjust_bet_transaction' => false,
            'validate_void_bet_transaction' => true,
            'validate_unvoid_bet_transaction' => true,
            'validate_refund_transaction' => true,
            'validate_settle_transaction' => false,
            'validate_unsettle_transaction' => true,
            'validate_void_settle_transaction' => true,
            'validate_unvoid_settle_transaction' => true,
            'validate_bet_and_settle_transaction' => true,
            'validate_cancel_bet_and_settle_transaction' => true,
            'validate_free_spin_transaction' => true,
            'validate_give_transaction' => true,
            'validate_resettle_transaction' => false,
        ]);

        $transaction_count = !empty($result['transaction_count']) ? $result['transaction_count'] : 0;
        $get_transactions = !empty($result['get_transactions']) ? $result['get_transactions'] : [];
        $bet_transaction = !empty($result['bet_transaction']) ? $result['bet_transaction'] : [];
        $cancel_bet_transaction = !empty($result['cancel_bet_transaction']) ? $result['cancel_bet_transaction'] : [];
        $adjust_bet_transaction = !empty($result['adjust_bet_transaction']) ? $result['adjust_bet_transaction'] : [];
        $void_bet_transaction = !empty($result['void_bet_transaction']) ? $result['void_bet_transaction'] : [];
        $unvoid_bet_transaction = !empty($result['unvoid_bet_transaction']) ? $result['unvoid_bet_transaction'] : [];
        $refund_transaction = !empty($result['refund_transaction']) ? $result['refund_transaction'] : [];
        $settle_transaction = !empty($result['settle_transaction']) ? $result['settle_transaction'] : [];
        $unsettle_transaction = !empty($result['unsettle_transaction']) ? $result['unsettle_transaction'] : [];
        $void_settle_transaction = !empty($result['void_settle_transaction']) ? $result['void_settle_transaction'] : [];
        $unvoid_settle_transaction = !empty($result['unvoid_settle_transaction']) ? $result['unvoid_settle_transaction'] : [];
        $bet_and_settle_transaction = !empty($result['bet_and_settle_transaction']) ? $result['bet_and_settle_transaction'] : [];
        $cancel_bet_and_settle_transaction = !empty($result['cancel_bet_and_settle_transaction']) ? $result['cancel_bet_and_settle_transaction'] : [];
        $free_spin_transaction = !empty($result['free_spin_transaction']) ? $result['free_spin_transaction'] : [];
        $give_transaction = !empty($result['give_transaction']) ? $result['give_transaction'] : [];
        $resettle_transaction = !empty($result['resettle_transaction']) ? $result['resettle_transaction'] : [];

        if (isset($result['result']) && !$result['result']) {
            return false;
        }

        if (!empty($resettle_transaction)) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        if (empty($settle_transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TXCODE_NOT_EXISTS, 'settle not found');
            return false;
        }

        $settle_transaction = end($settle_transaction);

        if (!empty($settle_transaction['game_code']) && !empty($this->main_params['game_code']) && $this->main_params['game_code'] != $settle_transaction['game_code']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid game code');
            return false;
        }

        if (!empty($settle_transaction['round_id']) && !empty($this->main_params['round_id']) && $this->main_params['round_id'] != $settle_transaction['round_id']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_PARAMETERS, 'Invalid round id');
            return false;
        }

        $calculated_amount = !empty($settle_transaction['amount']) ? $this->main_params['amount'] - $settle_transaction['amount'] : 0;
        $this->main_params['amount'] = abs($calculated_amount);

        if ($this->main_params['amount'] < 0) {
            $this->extra_params['wallet_action'] = $this->ssa_decrease;
        } else {
            $this->extra_params['wallet_action'] = $this->ssa_increase;
        }

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
            return $this->walletAdjustment($this->extra_params['wallet_action'], $this->ssa_insert, $this->main_params['amount']);
        });

        if ($success) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
        }

        return $success;
    }
}