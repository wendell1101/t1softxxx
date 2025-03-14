<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/**
 * Playtech seamless service API
 *
 * @property Api_lib $api_lib
 * @property CI_Loader $load
 * @property Common_token $common_token
 * @property Player_model $player_model
 */
class Pt_seamless_service_api extends BaseController {
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
    protected $is_admin = false;

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
    protected $request_params;
    protected $request_headers;
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
    protected $seamless_service_related_unique_id = null;
    protected $seamless_service_related_action = null;
    protected $action_type = null;
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
    protected $transaction_status;
    protected $external_transaction_date;
    protected $remote_wallet_status = null;
    protected $use_failed_transaction_monthly_table = true;

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

    const SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED = [
        // 'code' => 500,
        'code' => 'SE_999',
        'message' => 'An unexpected error occurred',
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
        'code' => '0',
        'message' => 'Success',
    ];

    // general error
    const RESPONSE_INTERNAL_ERROR = [
        'code' => 'INTERNAL_ERROR',
        'message' => 'Internal server error',
    ];

    const RESPONSE_AUTHENTICATION_FAILED = [
        'code' => 'ERR_AUTHENTICATION_FAILED',
        'message' => 'Failure to authenticate the player',
    ];

    const RESPONSE_INSUFFICIENT_FUNDS = [
        'code' => 'ERR_INSUFFICIENT_FUNDS',
        'message' => 'Insufficient funds',
    ];

    const RESPONSE_TRANSACTION_DECLINED = [
        'code' => 'ERR_TRANSACTION_DECLINED',
        'message' => 'Transaction is declined',
    ];

    const RESPONSE_REGULATORY_REALITYCHECK = [
        'code' => 'ERR_REGULATORY_REALITYCHECK',
        'message' => 'Reality check timer expired',
    ];

    const RESPONSE_ERR_REGULATORY_GENERAL = [
        'code' => 'ERR_REGULATORY_GENERAL',
        'message' => 'General regulatory error',
    ];

    const RESPONSE_INVALID_REQUEST_PAYLOAD = [
        'code' => 'INVALID_REQUEST_PAYLOAD',
        'message' => 'Invalid request payload',
    ];

    const RESPONSE_CONSTRAINT_VIOLATION = [
        'code' => 'CONSTRAINT_VIOLATION',
        'message' => 'Constraint Violation',
    ];

    const RESPONSE_PLAYER_NOT_FOUND = [
        'code' => 'ERR_PLAYER_NOT_FOUND',
        'message' => 'Player not found',
    ];

    # API METHODS HERE
    // default
    const API_METHOD_OPERATOR_ACTION = 'operatorAction';
    const ACTION_SHOW_HINT = 'showHint';
    const ACTION_GET_TOKEN = 'getToken';
    const ACTION_ENCRYPT_PARAM = 'encryptParam';
    const ACTION_DECRYPT_PARAM = 'decryptParam';

    const ACTIONS = [
        self::ACTION_SHOW_HINT,
        self::ACTION_GET_TOKEN,
        // self::ACTION_ENCRYPT_PARAM,
        // self::ACTION_DECRYPT_PARAM,
    ];

    // From API docs
    const API_METHOD_AUTHENTICATE = 'authenticate';
    const API_METHOD_GET_BALANCE = 'getbalance';
    const API_METHOD_BET = 'bet';
    const API_METHOD_GAME_ROUND_RESULT = 'gameroundresult';
    const API_METHOD_LOGOUT = 'logout';
    const API_METHOD_KEEP_ALIVE = 'keepalive';
    const API_METHOD_HEALTH_CHECK = 'healthcheck';

    const API_METHODS = [
        // from GP
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_BET,
        self::API_METHOD_GAME_ROUND_RESULT,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_LOGOUT,
        self::API_METHOD_KEEP_ALIVE,
        self::API_METHOD_HEALTH_CHECK,

        // from operator
        self::ACTION_GET_TOKEN,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_BET,
        self::API_METHOD_GAME_ROUND_RESULT,
    ];

    const CHECK_WHITELIST_IP_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_BET,
        self::API_METHOD_GAME_ROUND_RESULT,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_LOGOUT,
        self::API_METHOD_KEEP_ALIVE,
        self::API_METHOD_HEALTH_CHECK,
    ];

    const CHECK_BLOCK_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_BET,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_LOGOUT,
        self::API_METHOD_KEEP_ALIVE,
    ];

    const CHECK_MAINTENANCE_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_BET,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_LOGOUT,
        self::API_METHOD_KEEP_ALIVE,
    ];

    const PAY_TYPE_WIN = 'WIN';
    const PAY_TYPE_REFUND = 'REFUND';

    # ADDITIONAL PROPERTIES HERE
    protected $server_name;
    protected $kiosk_key;
    protected $kiosk_name;
    protected $kiosk_prefix;
    protected $country_code;
    protected $player_message;

    public function __construct() {
        parent::__construct();
        $this->load->library('api_lib');
        $this->load->model(['common_token', 'player_model']);
        $this->api_lib->storeRequestData($this->api_lib->request()->params());
    }

    public function index($game_platform_id, $api_method) {
        $this->utils->debug_log(__METHOD__, [
            'game_platform_id' => $game_platform_id,
            'api_method' => $api_method,
            'request_params' => $this->api_lib->request()->params(),
            'request_headers' => $this->api_lib->request()->headers(),
        ]);

        $this->game_platform_id = $game_platform_id;
        $this->api_method = $api_method;

        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = self::RESPONSE_INTERNAL_ERROR['message'];
        $status = 200;

        try {
            $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
            $this->api_lib->assertion()->assertTrue($this->game_api, self::RESPONSE_INTERNAL_ERROR['code'], 'Failed to load game API', 500);
            $this->utils->debug_log(__METHOD__, "Loaded game API {$this->game_platform_id} successfully");

            $this->api_lib->assertion()->assertInArray($this->api_method, self::API_METHODS, self::RESPONSE_INTERNAL_ERROR['code'], "API method {$this->api_method} not found");
            $this->api_lib->assertion()->assertInArray($this->api_method, get_class_methods(get_class($this)), self::RESPONSE_INTERNAL_ERROR['code'], "API method {$this->api_method} not implemented");

            // default
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
            $this->use_failed_transaction_monthly_table = $this->game_api->use_failed_transaction_monthly_table;

            // additional
            $this->server_name = $this->game_api->server_name;
            $this->kiosk_key = $this->game_api->kiosk_key;
            $this->kiosk_name = $this->game_api->kiosk_name;
            $this->kiosk_prefix = $this->game_api->kiosk_prefix;
            $this->country_code = $this->game_api->country_code;

            if (in_array($this->api_method, self::CHECK_BLOCK_API_METHODS)) {
                $this->api_lib->assertion()->assertTrue($this->game_api->isActive(), self::RESPONSE_CONSTRAINT_VIOLATION['code'], self::RESPONSE_ERROR_GAME_DISABLED['message'], 500);
            }

            if (in_array($this->api_method, self::CHECK_MAINTENANCE_API_METHODS)) {
                $this->api_lib->assertion()->assertFalse($this->game_api->isMaintenance(), self::RESPONSE_CONSTRAINT_VIOLATION['code'], self::RESPONSE_ERROR_GAME_MAINTENANCE['message']);
            }

            if (!$this->game_api->validateWhiteIP() && in_array($this->api_method, self::CHECK_WHITELIST_IP_API_METHODS)) {
                $this->api_lib->setHint('IP', $this->ssa_get_ip_address());
                $this->api_lib->assertion()->assertTrue($this->game_api->validateWhiteIP(), self::RESPONSE_CONSTRAINT_VIOLATION['code'], self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED['message']);
            };

            $authResult = $this->api_lib->validation()->validateBasicAuthRequest($this->game_api->admin_auth_username, $this->game_api->admin_auth_password);
            $this->is_admin = $authResult['isValid'];
            $success = true;
        } catch (ApiException $e) {
            $code = $e->getStringCode();
            $message = $e->getMessage();
            $status = $e->getStatus();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = 'An unexpected error occurred';
            $status = 500;
        }

        if (!$success) {
            return $this->response($this->errorData([], $code, $message), $status);
        }

        return $this->{$this->api_method}();
    }

    protected function errorData($data = [], $code = null, $message = null) {
        $defaultData = [
            'requestId' => $this->api_lib->request()->params('requestId'),
            'error' => [
                'code' => $code,
                'description' => $message,
            ]
        ];

        return array_merge($defaultData, $data);
    }

    protected function response($data = [], $status = 200, $headers = []) {
        $playerId = $this->api_lib->lookup($this->player_details, 'player_id');
        $extra = [];

        if (isset($data['error'])) {
            $flag = Response_result::FLAG_ERROR;

            $this->utils->debug_log(__METHOD__, 'API_RETURN_ERROR ------------------------>', [
                'game_platform_id' => $this->game_platform_id,
                'api_method' => $this->api_method,
                'data' => $data,
            ]);

            $this->savefailedTransactionData();
        } else {
            $flag = Response_result::FLAG_NORMAL;
        }

        $this->response_result_id = $this->ssa_save_response_result(
            $this->game_platform_id,
            $flag,
            $this->api_method,
            $this->api_lib->request()->params(),
            $data,
            $this->api_lib->httpResponse($status, false),
            $playerId,
            $extra
        );

        if (!empty($this->player_message)) {
            $data['playerMessage'] = $this->player_message;
        }

        if ($this->api_method == self::API_METHOD_HEALTH_CHECK) {
            $data = [];
        }

        // save response result
        if ($flag == Response_result::FLAG_NORMAL && !$this->transaction_already_exists) {
            // add reponse resullt id and response to record
            if ($this->response_result_id) {
                $updateData = [
                    'response_result_id' => $this->response_result_id,
                    'response' => !empty($data) && is_array($data) ? json_encode($data) : null,
                ];
                $where = ['external_unique_id' => $this->external_unique_id];
                $this->ssa_update_transaction_with_result_custom($this->transaction_table, $updateData, $where);
            }

            // update bet status
            if ($this->api_method == self::API_METHOD_GAME_ROUND_RESULT) {
                if ($this->api_lib->request()->params('pay.type') == self::PAY_TYPE_REFUND) {
                    $updateData = ['status' => Game_logs::STATUS_REFUND];
                    $where = ['transaction_id' => $this->api_lib->request()->params('pay.relatedTransactionCode')];
                    $this->ssa_update_transaction_with_result_custom($this->transaction_table, $updateData, $where);
                } else {
                    if ($this->is_end_round) {
                        $updateData = ['status' => GAME_LOGS::STATUS_SETTLED];
                        $where = [
                            'api_method' => self::API_METHOD_BET,
                            'round_id' => $this->api_lib->request()->params('gameRoundCode'),
                            'status' => Game_logs::STATUS_PENDING,
                        ];
                        $this->ssa_update_transaction_with_result_custom($this->transaction_table, $updateData, $where);
                    }
                }
            }
        }

        if ($this->show_hint || $this->is_admin) {
            $hint = $this->api_lib->hint();

            if ($this->api_lib->request()->params('show_hint', 0, 'GET')) {
                $data['hint'] = $hint;
            }
        }

        return $this->api_lib->response()->json($data, $status, $headers);
    }

    protected function getToken() {
        $this->api_method = __FUNCTION__;
        $token = null;
        $code = self::SYSTEM_ERROR_BAD_REQUEST['code'];
        $message = self::SYSTEM_ERROR_BAD_REQUEST['message'];

        $rules = [
            'player_username' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_ERROR_INVALID_PARAMETER['code'], $validateRequest->message, 400);

            $this->player_details = $this->ssa_get_player_details_by_username($this->api_lib->request()->params('player_username'), $this->game_platform_id);
    
            $this->assertPlayerDetailsNotEmpty($this->player_details);
    
            if (!empty($this->player_details['token'])) {
                $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token']);
                $token = $this->kiosk_prefix . '_'. $this->player_details['token'];
            } else {
                $token = $this->kiosk_prefix . '_'. $this->game_api->getPlayerToken($this->player_details['player_id']);
            }

            $status = 200;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } catch (ApiException $e) {
            $code = $e->getStringCode();
            $message = $e->getMessage();
            $status = $e->getStatus();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = 'An unexpected error occurred';
            $status = 500;
        }

        $response = [
            'code' => $code,
            'message' => $message,
            'token' => $token,
        ];

        return $this->response($response, $status);
    }

    protected function getTransaction($where = [], $selectedColumns = [], $order_by = ['field_name' => '', 'is_desc' => false]) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'round_id' => $this->request_params['round_id'],
            ];
        }

        $get_transactions = $this->ssa_get_transaction($this->transaction_table, $where, $selectedColumns, $order_by);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transactions)) {
                $get_transactions = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $get_transactions;
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

    protected function getPlayerDetails($subject = 'token') {
        if ($subject == 'token') {
            $player_details = $this->ssa_get_player_details_by_token($this->rebuildRequestToken(), $this->game_platform_id);
        } else {
            $player_details = $this->ssa_get_player_details_by_game_username($this->rebuildRequestGameUsername(), $this->game_platform_id);
        }

        return $player_details;
    }

    protected function isInsufficientBalance($amount, $balance) {
        if ($amount > $balance) {
            return true;
        }

        return false;
    }

    protected function afterBalance($balance = null) {
        return is_null($balance) ? $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true) : $balance;
    }

    protected function insertTransactionData($data = []) {
        $extraInfo = [
            'externalTransactionDate' => $this->external_transaction_date,
        ];

        $insertData = [
            // default
            'game_platform_id' => $this->game_platform_id,
            'token' => $this->api_lib->lookup($this->player_details, 'token'),
            'player_id' => $this->api_lib->lookup($this->player_details, 'player_id'),
            'game_username' => $this->api_lib->lookup($this->player_details, 'game_username'),
            'language' => $this->language,
            'currency' => $this->currency,
            'api_method' => $this->api_method,
            'transaction_type' => $this->transaction_type,
            'transaction_id' => $this->api_lib->request()->params('pay.transactionCode', $this->api_lib->request()->params('transactionCode')),
            'round_id' => $this->api_lib->request()->params('gameRoundCode'),
            'game_code' => $this->api_lib->request()->params('gameCodeName'),
            'wallet_adjustment_status' => !empty($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : $this->ssa_retained,
            'amount' => !empty($data['amount']) ? $data['amount'] : 0,
            'before_balance' => !empty($data['before_balance']) ? $data['before_balance'] : 0,
            'after_balance' => !empty($data['after_balance']) ? $data['after_balance'] : 0,
            'status' => $this->transaction_status,
            'start_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'reference_transaction_id' => $this->api_lib->request()->params('pay.relatedTransactionCode', $this->api_lib->request()->params('gameRoundCode')),
            'is_end_round' => $this->is_end_round,
            'is_processed' => !empty($data['is_processed']) ? $data['is_processed'] : 0,

            // addtional
            'request_id' => $this->api_lib->request()->params('requestId'),
            'game_history_url' => $this->api_lib->request()->params('gameHistoryUrl'),

            // default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => !empty($this->api_lib->request()->params()) && is_array($this->api_lib->request()->params()) ? json_encode($this->api_lib->request()->params()) : null,
            'response' => null,
            'extra_info' => !empty($extraInfo) && is_array($extraInfo) ? json_encode($extraInfo) : null,
            'valid_bet_amount' => isset($data['valid_bet_amount']) ? $data['valid_bet_amount'] : 0,
            'bet_amount' => isset($data['bet_amount']) ? $data['bet_amount'] : 0,
            'win_amount' => isset($data['win_amount']) ? $data['win_amount'] : 0,
            'result_amount' => isset($data['result_amount']) ? $data['result_amount'] : 0,
            'flag_of_updated_result' => isset($data['flag_of_updated_result']) ? $data['flag_of_updated_result'] : $this->ssa_flag_not_updated,
            'seamless_service_unique_id' => $this->seamless_service_unique_id_with_game_prefix,
            'external_game_id' => $this->api_lib->request()->params('gameCodeName'),
            'external_transaction_id' => $this->api_lib->request()->params('pay.transactionCode', $this->api_lib->request()->params('transactionCode')),
            'external_unique_id' => $this->external_unique_id,
        ];

        $insertData['md5_sum'] = md5(json_encode($insertData));

        $this->utils->debug_log(__METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'insertData', $insertData);
        return $this->ssa_insert_transaction_data($this->transaction_table, $insertData);
    }

    private function savefailedTransactionData() {
        $saveData = [
            'transaction_id' => $this->api_lib->request()->params('pay.transactionCode', $this->api_lib->request()->params('transactionCode')),
            'round_id' => $this->api_lib->request()->params('gameRoundCode'),
            'external_game_id' => $this->api_lib->request()->params('gameCodeName'),
            'player_id' => $this->api_lib->lookup($this->player_details, 'player_id'),
            'game_username' => $this->api_lib->lookup($this->player_details, 'game_username'),
            'amount' => $this->api_lib->request()->params('pay.amount', $this->api_lib->request()->params('amount')),
            'balance_adjustment_type' => $this->transaction_type == self::DEBIT ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $this->action_type,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->api_lib->request()->params()),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $this->game_platform_id .'-'. $this->external_unique_id,
        ];

        $saveData['md5_sum'] = md5(json_encode($saveData));
        $saveData['transaction_date'] = $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format);
        $saveData['request_id'] = $this->utils->getRequestId();
        $saveData['headers'] = json_encode($this->api_lib->request()->headers());

        if (empty($saveData['external_uniqueid'])) {
            return false;
        }

        $tableName = $this->ssa_failed_remote_common_seamless_transactions_table;
        // check if exist
        if ($this->use_failed_transaction_monthly_table) {
            $year_month = $this->utils->getThisYearMonth();
            $tableName = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$year_month}";
            $this->utils->createTableLike($tableName, $this->ssa_failed_remote_common_seamless_transactions_table);
        }

        $failedTransaction = $this->ssa_get_transaction($tableName, ['external_uniqueid' => $saveData['external_uniqueid']]);

        if (!empty($failedTransaction)) {
            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $saveData['external_uniqueid'],
                ];
            }
            
            if ($failedTransaction['md5_sum'] == $saveData['md5_sum']) {
                return true;
            }

            return $this->ssa_update_transaction_with_result_custom($tableName, $saveData, $where);
        }

        return $this->ssa_insert_transaction_data($tableName, $saveData);
    }

    protected function validateRemoteWalletError($adjustmentType) {
        $adjustmentType = ucfirst($adjustmentType);
        $success = false;

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message'];
        } elseif ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $code = self::RESPONSE_INSUFFICIENT_FUNDS['code'];
            $message = self::RESPONSE_INSUFFICIENT_FUNDS['message'];
        } elseif ($this->ssa_remote_wallet_error_maintenance()) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message'];
        } elseif ($this->ssa_remote_wallet_error_double_unique_id()) {
            $success = true;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } else {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = "{$adjustmentType} balance failed";
        }

        return compact(['success', 'code', 'message']);
    }

    protected function walletAdjustment($adjustmentType, $amount, $balance) {
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = 'Wallet adjustment failed';
        $status = 200;
        $amount = $this->ssa_operate_amount(abs($amount), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $beforeBalance = $afterBalance = $balance;
        $walletAdjustmentStatus = $this->ssa_retained;
        $is_processed = false;

        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([
            $this->game_platform_id,
            $this->external_unique_id,
        ]);

        $this->seamless_service_unique_id_with_game_prefix = $this->utils->mergeArrayValues([
            'game',
            $this->seamless_service_unique_id,
        ]);

        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->api_lib->request()->params('gameCodeName'));
        $this->ssa_set_game_provider_round_id($this->api_lib->request()->params('gameRoundCode'));
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);
        $this->ssa_set_game_provider_action_type($this->action_type);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        if ($amount == 0) {
            $success = true;
            $walletAdjustmentStatus = $this->ssa_retained;
            $is_processed = true;

            if (in_array($this->action_type, [Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT])) {
                if ($this->ssa_enabled_remote_wallet()) {
                    $this->utils->debug_log(__METHOD__, "{$this->game_api->seamless_game_api_name}: amount 0 call remote wallet", 'request_params', $this->api_lib->request()->params());
                    $this->ssa_increase_remote_wallet($this->player_details['player_id'], $amount, $this->game_platform_id, $afterBalance);
                }
            }
        } else {
            switch ($adjustmentType) {
                case $this->ssa_decrease:
                    if ($this->isInsufficientBalance($amount, $beforeBalance)) {
                        $code = self::RESPONSE_INSUFFICIENT_FUNDS['code'];
                        $message = self::RESPONSE_INSUFFICIENT_FUNDS['message'];
                    } else {
                        $afterBalance = null;
                        $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $afterBalance);
    
                        if ($success) {
                            $code = self::RESPONSE_SUCCESS['code'];
                            $message = self::RESPONSE_SUCCESS['message'];
                            $walletAdjustmentStatus = $this->ssa_decreased;
                            $is_processed = true;
                        } else {
                            // remote wallet error
                            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
                            if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                                $validateRemoteWalletError = $this->validateRemoteWalletError($adjustmentType);
    
                                if (isset($validateRemoteWalletError['success']) && $validateRemoteWalletError['success']) {
                                    $success = true;
                                    $walletAdjustmentStatus = $this->ssa_remote_wallet_decreased;
                                    $beforeBalance = $this->afterBalance($afterBalance) + $amount;
                                }
    
                                $code = !empty($validateRemoteWalletError['code']) ? $validateRemoteWalletError['code'] : self::RESPONSE_INTERNAL_ERROR['code'];
                                $message = !empty($validateRemoteWalletError['message']) ? $validateRemoteWalletError['message'] : 'Remote wallet failed';
                            } else {
                                $code = self::RESPONSE_INTERNAL_ERROR['code'];
                                $message = 'Decrease balance failed';
                            }
                        }
                    }
                    break;
                case $this->ssa_increase:
                    $afterBalance = null;
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $afterBalance);
    
                    if ($success) {
                        $code = self::RESPONSE_SUCCESS['code'];
                        $message = self::RESPONSE_SUCCESS['message'];
                        $walletAdjustmentStatus = $this->ssa_increased;
                        $is_processed = true;
                    } else {
                        // remote wallet error
                        $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                            $validateRemoteWalletError = $this->validateRemoteWalletError($adjustmentType);

                            if (isset($validateRemoteWalletError['success']) && $validateRemoteWalletError['success']) {
                                $success = true;
                                $walletAdjustmentStatus = $this->ssa_remote_wallet_increased;
                                $beforeBalance = $this->afterBalance($afterBalance) - $amount;
                            }

                            $code = !empty($validateRemoteWalletError['code']) ? $validateRemoteWalletError['code'] : self::RESPONSE_INTERNAL_ERROR['code'];
                            $message = !empty($validateRemoteWalletError['message']) ? $validateRemoteWalletError['message'] : 'Remote wallet failed';
                        } else {
                            $code = self::RESPONSE_INTERNAL_ERROR['code'];
                            $message = 'Increase balance failed';
                        }
                    }
                    break;
                default:
                    $code = self::RESPONSE_INTERNAL_ERROR['code'];
                    $message = 'Action not found';
                    break;
            }
        }

        $this->player_balance = $afterBalance = $this->afterBalance($afterBalance);

        $data = [
            'amount' => $amount,
            'before_balance' => $beforeBalance,
            'after_balance' => $afterBalance,
            'wallet_adjustment_status' => $walletAdjustmentStatus,
            'is_processed' => $is_processed,
        ];

        // array_push($this->saved_multiple_transactions, $data);

        return compact(['success', 'code', 'message', 'status', 'data']);
    }
    
    protected function assertPlayerDetailsNotEmpty($playerDetails, $status = 200) {
        $this->api_lib->assertion()->assertNotEmpty($playerDetails, self::RESPONSE_AUTHENTICATION_FAILED['code'], 'Player not found', $status);
    }

    protected function assertGameUsernameEquals($actual, $expected) {
        if ($actual != $expected) {
            $this->api_lib->setHint('username', $expected);
            $this->api_lib->assertion()->assertEquals($actual, $expected, self::RESPONSE_PLAYER_NOT_FOUND['code'], 'Invalid username');
        }
    }

    protected function assertPlayerNotBlocked($gameUsername) {
        $this->api_lib->assertion()->assertFalse($this->game_api->isBlockedUsernameInDB($gameUsername), self::RESPONSE_AUTHENTICATION_FAILED['code'], self::RESPONSE_ERROR_PLAYER_BLOCKED['message']);
    }

    protected function removeKioskPrefix($subject) {
        return str_replace($this->kiosk_prefix . '_', "", $subject);
    }

    protected function rebuildRequestToken() {
        return $this->removeKioskPrefix($this->api_lib->request()->params('externalToken'));
    }

    protected function rebuildRequestGameUsername() {
        return $this->kiosk_prefix . '_' . strtolower($this->removeKioskPrefix($this->api_lib->request()->params('username')));
    }

    protected function timestamp() {
        $microtime = microtime(true);
        $milliseconds = sprintf('%03d', ($microtime - floor($microtime)) * 1000);
        $datetime = date('Y-m-d H:i:s', (int) $microtime) . '.' . $milliseconds;
        return $datetime;
    }

    protected function getFormattedDateTime($date = '') {
        if (empty($date)) {
            $date = $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format);
        }

        $dateTime = new DateTime($date);
        return $dateTime->format('Y-m-d H:i:s');
    }

    protected function externalTransactionDate() {
        $microtime = microtime(true);
        $milliseconds = sprintf('%03d', ($microtime - floor($microtime)) * 1000);
        return $this->utils->getNowForMysql() . '.' . $milliseconds;
    }

    protected function authenticate() {
        $this->api_method = __FUNCTION__;

        $rules = [
            'requestId' => ['required'],
            'username' => ['required'],
            'externalToken' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $status = 200;

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_INVALID_REQUEST_PAYLOAD['code'], $validateRequest->message);

            $this->player_details = $this->ssa_get_player_details_by_token($this->rebuildRequestToken(), $this->game_platform_id);

            $this->assertPlayerDetailsNotEmpty($this->player_details);
            $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
            $this->assertPlayerNotBlocked($this->player_details['game_username']);

            $success = true;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } catch (ApiException $e) {
            $success = false;
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
            $status = 500;
        }

        if ($success) {
            $response = [
                'requestId' => $this->api_lib->request()->params('requestId'),
                'username' => $this->api_lib->request()->params('username'),
                'currencyCode' => $this->currency,
                'countryCode' => $this->country_code,
            ];
        } else {
            $response = $this->errorData([], $code, $message);
        }

        return $this->response($response, $status);
    }

    protected function bet() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->transaction_status = Game_logs::STATUS_PENDING;
        $this->action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->is_end_round = false;

        $rules = [
            'requestId' => ['required'],
            'username' => ['required'],
            'externalToken' => ['required'],
            'gameRoundCode' => ['required'],
            'transactionCode' => ['required'],
            'transactionDate' => ['required'],
            'amount' => ['required', 'nullable', 'numeric', 'min:0'],
            'internalFundChanges' => ['required', 'nullable'],
            'gameCodeName' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $status = 200;

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_INVALID_REQUEST_PAYLOAD['code'], $validateRequest->message);

            $requestId = $this->api_lib->request()->params('requestId');
            $transactionCode = $this->api_lib->request()->params('transactionCode');
            $gameRoundCode = $this->api_lib->request()->params('gameRoundCode');
            $amount = $this->api_lib->request()->params('amount', 0);

            $this->external_unique_id = $this->utils->mergeArrayValues([$this->action_type, $transactionCode, $gameRoundCode]);
            $this->transaction_already_exists = $transaction = $this->getTransaction("(external_unique_id='{$this->external_unique_id}' OR request_id='{$requestId}')");
            $this->external_transaction_date = $this->api_lib->lookup($transaction, 'extra_info.externalTransactionDate', $this->externalTransactionDate());

            if (!empty($transaction)) {
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->getPlayerDetails(empty($transaction) ? 'token' : 'game_username');
                $this->assertPlayerDetailsNotEmpty($this->player_details);
                $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
                $this->assertPlayerNotBlocked($this->player_details['game_username']);
            }

            $data = compact([
                'requestId',
                'transactionCode',
                'gameRoundCode',
                'amount',
            ]);

            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message, &$status, $data) {
                if ($this->transaction_already_exists) {
                    return true; 
                }

                $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);

                if ($this->player_balance === null || $this->player_balance === false) {
                    $code = self::RESPONSE_INTERNAL_ERROR['code'];
                    $message = 'Unable to get player balance';
                    return false;
                }
 
                $result = $this->walletAdjustment($this->ssa_decrease, $data['amount'], $this->player_balance);
                $success = isset($result['success']) ? $result['success'] : false;
                $code = isset($result['code']) ? $result['code'] : $code;
                $message = isset($result['message']) ? $result['message'] : 'Wallet adjustment failed';
                $status = isset($result['status']) ? $result['status'] : $status;
                $insertData = !empty($result['data']) ? $result['data'] : [];

                if ($success) {
                    $success = $this->insertTransactionData($insertData);

                    if (!$success) {
                        $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'] . ': Failed to save data';
                    }
                }

                return $success;
            });

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
            $status = 500;
        }

        if ($success) {
            $response = [
                'requestId' => $requestId,
                'externalTransactionCode' => $this->external_unique_id,
                'externalTransactionDate' => $this->external_transaction_date,
                'balance' => [
                    'real' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    'timestamp' => $this->timestamp(),
                ],
            ];

            if ($this->transaction_already_exists) {
                $response['message'] = 'Transaction already exists';
            }
        } else {
            $response = $this->errorData([], $code, $message);
        }

        return $this->response($response, $status);
    }

    protected function gameroundresult() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;

        $payType = $this->api_lib->request()->params('pay.type');

        $this->transaction_status = $payType == self::PAY_TYPE_REFUND ? Game_logs::STATUS_REFUND : Game_logs::STATUS_SETTLED;
        $this->action_type = $payType == self::PAY_TYPE_REFUND ? Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND : Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = !empty($this->api_lib->request()->params('gameRoundClose', false));

        $rules = [
            'requestId' => ['required'],
            'username' => ['required'],
            'externalToken' => ['required'],
            'gameRoundCode' => ['required'],
            'gameCodeName' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $status = 200;

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_INVALID_REQUEST_PAYLOAD['code'], $validateRequest->message);

            $requestId = $this->api_lib->request()->params('requestId');
            $transactionCode = $this->api_lib->request()->params('pay.transactionCode');
            $gameRoundCode = $this->api_lib->request()->params('gameRoundCode');
            $amount = $this->api_lib->request()->params('pay.amount', 0);
            $relatedTransactionCode = $this->api_lib->request()->params('pay.relatedTransactionCode');

            $this->external_unique_id = $this->utils->mergeArrayValues([$this->action_type, $transactionCode, $gameRoundCode]);
            $this->transaction_already_exists = $transaction = $this->getTransaction("(external_unique_id='{$this->external_unique_id}' OR request_id='{$requestId}')");
            $this->external_transaction_date = $this->api_lib->lookup($transaction, 'extra_info.externalTransactionDate', $this->externalTransactionDate());

            if (!empty($transaction)) {
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->getPlayerDetails('game_username');
                $this->assertPlayerDetailsNotEmpty($this->player_details);
                $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
                $this->assertPlayerNotBlocked($this->player_details['game_username']);
            }

            $this->assertPlayerDetailsNotEmpty($this->player_details);
            $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
            $this->assertPlayerNotBlocked($this->player_details['game_username']);

            $data = compact([
                'payType',
                'requestId',
                'transactionCode',
                'gameRoundCode',
                'amount',
                'relatedTransactionCode',
            ]);

            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message, &$status, $data) {
                if ($this->transaction_already_exists) {
                    return true; 
                }

                $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);

                if ($this->player_balance === null || $this->player_balance === false) {
                    $code = self::RESPONSE_INTERNAL_ERROR['code'];
                    $message = 'Unable to get player balance';
                    return false;
                }

                $whereEndRound = [
                    'player_id' => $this->player_details['player_id'],
                    'api_method' => self::API_METHOD_GAME_ROUND_RESULT,
                    'round_id' => $data['gameRoundCode'],
                    'is_end_round' => true,
                ];

                // check if already settled. is end round exists
                $isRoundEnded = $this->isTransactionExists($whereEndRound);

                if ($isRoundEnded) {
                    $code = self::RESPONSE_TRANSACTION_DECLINED['code'];
                    $message = 'Unable to process transaction. Round is already ended.';
                    return false;
                }

                $whereBet = [
                    'player_id' => $this->player_details['player_id'],
                    'api_method' => self::API_METHOD_BET,
                    'round_id' => $data['gameRoundCode'],
                ];

                if (!empty($data['relatedTransactionCode'])) {
                    $whereBet['transaction_id'] = $data['relatedTransactionCode'];
                }

                // check single or multiple bets
                $betTransactions = $this->getTransactions($whereBet);

                if (empty($betTransactions)) {
                    $code = self::RESPONSE_TRANSACTION_DECLINED['code'];
                    $message = 'Bet not found';
                    return false;
                }

                $pendingBetCount = 0;
                $settledBetCount = 0;
                $refundedBetCount = 0;
                foreach ($betTransactions as $betTransaction) {
                    $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $betTransaction['external_unique_id']]);
                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;

                    if ($betTransaction['status'] == Game_logs::STATUS_PENDING) {
                        $pendingBetCount++;
                    }

                    if ($betTransaction['status'] == Game_logs::STATUS_SETTLED) {
                        $settledBetCount++;
                    }

                    if ($betTransaction['status'] == Game_logs::STATUS_REFUND) {
                        $refundedBetCount++;
                    }
                }

                if ($data['payType'] == self::PAY_TYPE_REFUND) {
                    if (empty($pendingBetCount)) {
                        $code = self::RESPONSE_TRANSACTION_DECLINED['code'];
                        $message = 'Transaction already processed by win payout';
                        return false;
                    }
                } else {
                    if (empty($pendingBetCount) && !empty($refundedBetCount) && !empty($data['amount']) && !$this->is_end_round) {
                        $code = self::RESPONSE_TRANSACTION_DECLINED['code'];
                        $message = 'Unable to process payout. Transaction already processed by refund.';
                        return false;
                    } elseif (empty($pendingBetCount) && !empty($refundedBetCount) && !empty($data['amount']) && $this->is_end_round) {
                        $code = self::RESPONSE_TRANSACTION_DECLINED['code'];
                        $message = 'Unable to end round with payout. Transaction already processed by refund.';
                        return false;
                    }
                }

                $result = $this->walletAdjustment($this->ssa_increase, $data['amount'], $this->player_balance);
                $success = isset($result['success']) ? $result['success'] : false;
                $code = isset($result['code']) ? $result['code'] : $code;
                $message = isset($result['message']) ? $result['message'] : 'Wallet adjustment failed';
                $status = isset($result['status']) ? $result['status'] : $status;
                $insertData = !empty($result['data']) ? $result['data'] : [];

                if ($success) {
                    $success = $this->insertTransactionData($insertData);

                    if (!$success) {
                        $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'] . ': Failed to save data';
                    }
                }

                return $success;
            });

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
            $status = 500;
        }

        if ($success) {
            $response = [
                'requestId' => $requestId,
                'externalTransactionCode' => $this->external_unique_id,
                'externalTransactionDate' => $this->external_transaction_date,
                'balance' => [
                    'real' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    'timestamp' => $this->timestamp(),
                ],
            ];

            if ($this->transaction_already_exists) {
                $response['message'] = 'Transaction already exists';
            }
        } else {
            $response = $this->errorData([], $code, $message);
        }

        return $this->response($response, $status);
    }

    protected function getbalance() {
        $this->api_method = __FUNCTION__;

        $rules = [
            'requestId' => ['required'],
            'username' => ['required'],
            'externalToken' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $status = 200;

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_INVALID_REQUEST_PAYLOAD['code'], $validateRequest->message);

            $this->player_details = $this->ssa_get_player_details_by_token($this->rebuildRequestToken(), $this->game_platform_id);

            $this->assertPlayerDetailsNotEmpty($this->player_details);
            $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
            $this->assertPlayerNotBlocked($this->player_details['game_username']);

            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message) {
                $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);

                if ($this->player_balance === null || $this->player_balance === false) {
                    $code = self::RESPONSE_INTERNAL_ERROR['code'];
                    $message = 'Unable to get player balance';
                    return false;
                }

                return true;
            });

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
            $status = 500;
        }

        if ($success) {
            $response = [
                'requestId' => $this->api_lib->request()->params('requestId'),
                'balance' => [
                    'real' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    'timestamp' => $this->timestamp(),
                ],
            ];
        } else {
            $response = $this->errorData([], $code, $message);
        }

        return $this->response($response, $status);
    }

    protected function logout() {
        $this->api_method = __FUNCTION__;

        $rules = [
            'requestId' => ['required'],
            'username' => ['required'],
            'externalToken' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $status = 200;

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_INVALID_REQUEST_PAYLOAD['code'], $validateRequest->message);

            $this->player_details = $this->ssa_get_player_details_by_token($this->rebuildRequestToken(), $this->game_platform_id);

            $this->assertPlayerDetailsNotEmpty($this->player_details);
            $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
            $this->assertPlayerNotBlocked($this->player_details['game_username']);

            $this->common_token->disableToken($this->player_details['token']);
            $success = true;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } catch (ApiException $e) {
            $success = false;
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
            $status = 500;
        }

        if ($success) {
            $response = [
                'requestId' => $this->api_lib->request()->params('requestId'),
            ];
        } else {
            $response = $this->errorData([], $code, $message);
        }

        return $this->response($response, $status);
    }

    protected function keepalive() {
        $this->api_method = __FUNCTION__;

        $rules = [
            'requestId' => ['required'],
            'username' => ['required'],
            'externalToken' => ['required'],
        ];

        $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
        $success = false;
        $code = self::RESPONSE_INTERNAL_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $status = 200;

        try {
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_INVALID_REQUEST_PAYLOAD['code'], $validateRequest->message);

            $this->player_details = $this->ssa_get_player_details_by_token($this->rebuildRequestToken(), $this->game_platform_id);

            $this->assertPlayerDetailsNotEmpty($this->player_details);
            $this->assertGameUsernameEquals($this->rebuildRequestGameUsername(), $this->player_details['game_username']);
            $this->assertPlayerNotBlocked($this->player_details['game_username']);

            $success = true;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } catch (ApiException $e) {
            $success = false;
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_INTERNAL_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
            $status = 500;
        }

        if ($success) {
            $response = [
                'requestId' => $this->api_lib->request()->params('requestId'),
            ];
        } else {
            $response = $this->errorData([], $code, $message);
        }

        return $this->response($response, $status);
    }

    protected function healthcheck() {
        $this->api_method = __FUNCTION__;
        return $this->response();
    }
}
