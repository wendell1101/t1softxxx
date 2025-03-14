<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/**
 * FA seamless service API
 *
 * @property Api_lib $api_lib
 * @property CI_Loader $load
 * @property Common_token $common_token
 * @property Player_model $player_model
 */
class Fa_seamless_service_api extends BaseController {
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
        'code' => 0,
        'message' => 'Success',
    ];

    const RESPONSE_BALANCE_INSUFFICIENT = [
        'code' => 1,
        'message' => 'Balance Insufficient',
    ];

    const RESPONSE_API_ID_NOT_FOUND = [
        'code' => 2,
        'message' => 'API-ID Not Found',
    ];

    const RESPONSE_API_ID_INVALID = [
        'code' => 3,
        'message' => 'API-ID Invalid',
    ];

    const RESPONSE_PLAYER_INVALID = [
        'code' => 4,
        'message' => 'Player Invalid',
    ];

    const RESPONSE_TOKEN_INVALID = [
        'code' => 5,
        'message' => 'Token Invalid',
    ];

    const RESPONSE_API_ID_DUPLICATE = [
        'code' => 8,
        'message' => 'API-ID Duplicate',
    ];

    const RESPONSE_SERVER_ERROR = [
        'code' => 9,
        'message' => 'Server Error',
    ];

    const RESPONSE_ACTION_INVALID = [
        'code' => 112,
        'message' => 'Action Invalid',
    ];

    const RESPONSE_IP_INVALID = [
        'code' => 113,
        'message' => 'IP Invalid',
    ];

    const RESPONSE_AUTHENTICATION_INVALID = [
        'code' => 142,
        'message' => 'Authentication Invalid',
    ];

    const RESPONSE_PROVIDER_REQUIRED = [
        'code' => 221,
        'message' => 'Provider Required',
    ];

    const RESPONSE_PROVIDER_INVALID = [
        'code' => 222,
        'message' => 'Provider Invalid',
    ];

    const RESPONSE_PROVIDER_NOT_FOUND = [
        'code' => 224,
        'message' => 'Provider Not Found',
    ];

    const RESPONSE_PARAMETER_REQUIRED = [
        'code' => 291,
        'message' => 'Parameter Required',
    ];

    const RESPONSE_PARAMETER_INVALID = [
        'code' => 292,
        'message' => 'Parameter Invalid',
    ];

    const RESPONSE_PLAYER_REQUIRED = [
        'code' => 311,
        'message' => 'Player Invalid',
    ];

    const RESPONSE_PLAYER_NOT_FOUND = [
        'code' => 314,
        'message' => 'Player Not Found',
    ];

    # API METHODS HERE
    // default
    const HELPER_METHOD_SHOW_HINT = 'showHint';
    const HELPER_METHOD_GET_TOKEN = 'getToken';
    const HELPER_METHOD_ENCRYPT_PARAM = 'encryptParam';
    const HELPER_METHOD_DECRYPT_PARAM = 'decryptParam';

    const HELPER_METHODS = [
        self::HELPER_METHOD_SHOW_HINT,
        self::HELPER_METHOD_GET_TOKEN,
        // self::HELPER_METHOD_ENCRYPT_PARAM,
        // self::HELPER_METHOD_DECRYPT_PARAM,
    ];

    // From API docs
    const API_METHOD_BALANCE = 'balance';
    const API_METHOD_BET = 'bet';
    const API_METHOD_CANCEL = 'cancel';
    const API_METHOD_SETTLE = 'settle';
    const API_METHOD_CHECK = 'check';
    const API_METHOD_JACKPOT = 'jackpot';

    const API_METHODS = [
        self::API_METHOD_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL,
        self::API_METHOD_SETTLE,
        self::API_METHOD_CHECK,
        self::API_METHOD_JACKPOT,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL,
        self::API_METHOD_SETTLE,
        self::API_METHOD_JACKPOT,
    ];

    const CHECK_WHITELIST_IP_API_METHODS = [
        self::API_METHOD_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL,
        self::API_METHOD_SETTLE,
        self::API_METHOD_CHECK,
        self::API_METHOD_JACKPOT,
    ];

    const CHECK_BLOCK_API_METHODS = [
        self::API_METHOD_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CHECK,
        self::API_METHOD_JACKPOT,
    ];

    const CHECK_MAINTENANCE_API_METHODS = [
        self::API_METHOD_BALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CHECK,
        self::API_METHOD_JACKPOT,
    ];

    # ADDITIONAL PROPERTIES HERE
    protected $provider;
    protected $merchant_name;
    protected $merchant_slug;
    protected $auth_token;

    public function __construct() {
        parent::__construct();
        $this->load->library('api_lib');
        $this->load->model(['common_token', 'player_model']);
        $this->api_lib->storeRequestData($this->api_lib->request()->params());
    }

    protected function getSubProviderGamePlatformId($provider) {
        # Provider List: https://docs.google.com/document/d/17Wq6kwPE6h5n6k5CxSW3NDNF6iKSq_Gn/edit?tab=t.0
        switch ($provider) {
            case 'ws168':
                $gamePlatformId = FA_WS168_SEAMLESS_GAME_API;
                break;
            default:
                $this->utils->debug_log(__METHOD__, 'Sub provider not found');
                $gamePlatformId = DUMMY_GAME_API;
                break;
        }

        return $gamePlatformId;
    }

    protected function initialize() {
        $provider = $this->api_lib->request()->params('provider', $this->api_lib->request()->params('provider', null, 'GET'));
        $this->game_platform_id = $this->getSubProviderGamePlatformId($provider);

        $this->api_lib->assertion()->assertTrue(!empty($provider), self::RESPONSE_PROVIDER_REQUIRED['code'], self::RESPONSE_PROVIDER_REQUIRED['message'], 400);
        $this->api_lib->assertion()->assertFalse($this->game_platform_id == DUMMY_GAME_API, self::RESPONSE_PROVIDER_INVALID['code'], self::RESPONSE_PROVIDER_INVALID['message'], 500);

        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        $this->api_lib->assertion()->assertTrue($this->game_api, self::RESPONSE_SERVER_ERROR['code'], 'Failed to load game API', 500);

        // $this->utils->debug_log(__METHOD__, "Loaded game API {$this->game_platform_id} successfully");

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
        $this->previous_table = $this->game_api->previous_table;
        $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
        $this->use_failed_transaction_monthly_table = $this->game_api->use_failed_transaction_monthly_table;

        // additional
        $this->provider = $this->game_api->provider;
        $this->merchant_name = $this->game_api->merchant_name;
        $this->merchant_slug = $this->game_api->merchant_slug;
        $this->auth_token = $this->game_api->auth_token;

        //? tester admin mode will add hint to the failed response and skip some validation and authorization to avoid interference in testing.
        $developerAuthResult = $this->api_lib->validation()->validateBasicAuthRequest($this->game_api->admin_auth_username, $this->game_api->admin_auth_password);
        $this->is_admin = $developerAuthResult['isValid'];

        if ($this->is_admin) {
            // skip Authorization
        } else {
            // check Authorization
            $actual = $this->api_lib->request()->headers('Authorization');
            $expected = "Bearer {$this->auth_token}";
            $this->api_lib->assertion()->assertEquals($actual, $expected, self::RESPONSE_AUTHENTICATION_INVALID['code'], self::RESPONSE_AUTHENTICATION_INVALID['message'], 403);
        }

        if (in_array($this->api_method, self::CHECK_BLOCK_API_METHODS)) {
            $this->api_lib->assertion()->assertTrue($this->game_api->isActive(), self::RESPONSE_SERVER_ERROR['code'], self::RESPONSE_ERROR_GAME_DISABLED['message'], 503);
        }

        if (in_array($this->api_method, self::CHECK_MAINTENANCE_API_METHODS)) {
            $this->api_lib->assertion()->assertFalse($this->game_api->isMaintenance(), self::RESPONSE_SERVER_ERROR['code'], self::RESPONSE_ERROR_GAME_MAINTENANCE['message'], 503);
        }

        if (!$this->game_api->validateWhiteIP() && in_array($this->api_method, self::CHECK_WHITELIST_IP_API_METHODS)) {
            $this->api_lib->setHint('IP', $this->ssa_get_ip_address());
            $this->api_lib->assertion()->assertTrue($this->game_api->validateWhiteIP(), self::RESPONSE_IP_INVALID['code'], self::RESPONSE_ERROR_IP_ADDRESS_NOT_ALLOWED['message'], 403);
        };
    }

    /**
     * The helper method is not a game provider API. Its purpose is to help testers get the player token, etc.
     */
    public function helper($method) {
        $this->api_method = $method;

        $success = false;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = self::RESPONSE_SERVER_ERROR['message'];
        $status = 500;

        try {
            $this->initialize();

            /* $this->utils->debug_log(__METHOD__, [
                'game_platform_id' => $this->game_platform_id,
                'api_method' => $method,
                'request_params' => $this->api_lib->request()->params(),
                'request_headers' => $this->api_lib->request()->headers(),
            ]); */

            $this->api_lib->assertion()->assertInArray($this->api_method, self::HELPER_METHODS, self::RESPONSE_SERVER_ERROR['code'], "API method {$this->api_method} not found", 404);
            $this->api_lib->assertion()->assertInArray($this->api_method, get_class_methods(get_class($this)), self::RESPONSE_SERVER_ERROR['code'], "API method {$this->api_method} not implemented", 404);

            $success = true;
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = 'An unexpected error occurred';
        }

        if (!$success) {
            return $this->response($this->errorData($code, $message), $status);
        }

        return $this->{$this->api_method}();
    }

    /**
     * The wallet method is for interacting with the game provider API.
     */
    public function wallet($method, $request = null) {
        $this->api_method = $method;

        $success = false;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = self::RESPONSE_SERVER_ERROR['message'];
        $status = 500;

        try {
            $this->initialize();

            /* $this->utils->debug_log(__METHOD__, [
                'game_platform_id' => $this->game_platform_id,
                'api_method' => $method,
                'request_params' => $this->api_lib->request()->params(),
                'request_headers' => $this->api_lib->request()->headers(),
            ]); */

            $this->api_lib->assertion()->assertInArray($this->api_method, self::API_METHODS, self::RESPONSE_SERVER_ERROR['code'], "API method {$this->api_method} not found", 404);
            $this->api_lib->assertion()->assertInArray($this->api_method, get_class_methods(get_class($this)), self::RESPONSE_SERVER_ERROR['code'], "API method {$this->api_method} not implemented", 404);

            $success = true;
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = 'An unexpected error occurred';
        }

        if (!$success) {
            return $this->response($this->errorData($code, $message), $status);
        }

        // this is for API methods that's have param
        if (in_array($this->api_method, [self::API_METHOD_BALANCE, self::API_METHOD_CHECK])) {
            return $this->{$this->api_method}($request);
        }

        return $this->{$this->api_method}();
    }

    protected function response($data = [], $status = 200, $headers = []) {
        $playerId = $this->api_lib->lookup($this->player_details, 'player_id');
        $extra = [];

        if (isset($data['code']) && $data['code'] === self::RESPONSE_SUCCESS['code']) {
            $flag = Response_result::FLAG_NORMAL;
        } else {
            $flag = Response_result::FLAG_ERROR;

            $this->utils->debug_log(__METHOD__, 'API_RETURN_ERROR ------------------------>', [
                'game_platform_id' => $this->game_platform_id,
                'api_method' => $this->api_method,
                'request_params' => $this->api_lib->request()->params(),
                'request_headers' => $this->api_lib->request()->headers(),
                'response' => $data,
            ]);

            $this->saveFailedTransactionData($data);
        }

        // save response result
        $responseResultId = $this->ssa_save_response_result(
            $this->game_platform_id,
            $flag,
            $this->api_method,
            $this->api_lib->request()->params(),
            $data,
            $this->api_lib->httpResponse($status, false),
            $playerId,
            $extra
        );

        // update transaction
        if ($flag == Response_result::FLAG_NORMAL && !$this->transaction_already_exists && in_array($this->api_method, self::TRANSFER_TYPE_API_METHODS)) {
            // add reponse result id and response to record
            if ($responseResultId && $this->external_unique_id) {
                $updateData = [
                    'response_result_id' => $responseResultId,
                    'response' => $this->utils->isNotEmptyArray($data) ? json_encode($data) : null,
                ];
                $where = ['external_unique_id' => $this->external_unique_id];
                $this->ssa_update_transaction_with_result_custom($this->transaction_table, $updateData, $where);
            }

            // update bet status
            if (in_array($this->api_method, [self::API_METHOD_SETTLE, self::API_METHOD_CANCEL])) {
                $where = [
                    'api_method' => self::API_METHOD_BET,
                    'player_id' => $playerId,
                    'round_id' => $this->api_lib->request()->params('bet_num'),
                ];

                if (!empty($this->api_lib->request()->params('ori_api_id'))) {
                    $where['transaction_id'] = $this->api_lib->request()->params('ori_api_id');
                }

                if ($this->api_method == self::API_METHOD_CANCEL) {
                    $updateData = ['status' => Game_logs::STATUS_REFUND];
                    $this->ssa_update_transaction_with_result_custom($this->transaction_table, $updateData, $where);
                } else {
                    if ($this->is_end_round) {
                        $updateData = ['status' => GAME_LOGS::STATUS_SETTLED];
                        $this->ssa_update_transaction_with_result_custom($this->transaction_table, $updateData, $where);
                    }
                }
            }
        }

        if ($this->show_hint || $this->is_admin) {
            $hint = $this->api_lib->hint();

            if ((intval($this->api_lib->request()->params('show_hint', 0, 'GET')) === 1 || intval($this->api_lib->request()->headers('X-Show-Hint')) === 1) && !empty($hint)) {
                $data['hint'] = $hint;
            }
        }

        return $this->api_lib->response()->json($data, $status, $headers);
    }

    protected function successData($extraData = []) {
        $data = [
            'code' => self::RESPONSE_SUCCESS['code'],
            'message' => self::RESPONSE_SUCCESS['message'],
        ];

        if (in_array($this->api_method, self::TRANSFER_TYPE_API_METHODS) || $this->api_method === self::API_METHOD_BALANCE) {
            $data['player'] = $this->player_details['game_username'];
            $data['balance'] = $this->ssa_amount_conversion($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);

            if ($this->transaction_already_exists) {
                $data['message'] = 'Transaction already exists';
            }
        }

        return array_merge($data, $extraData);
    }

    protected function errorData($code = null, $message = null, $extraData = []) {
        $data = [
            'code' => $code,
            'message' => $message,
        ];

        return array_merge($data, $extraData);
    }

    // operator/helper method
    protected function getToken() {
        $this->api_method = __FUNCTION__;
        $token = null;
        $status = 500;
        $code = self::SYSTEM_ERROR_BAD_REQUEST['code'];
        $message = self::SYSTEM_ERROR_BAD_REQUEST['message'];

        try {
            $rules = [
                'player_username' => ['required'],
            ];
    
            // request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_ERROR_INVALID_PARAMETER['code'], $validateRequest->message, 400);

            $this->player_details = $this->ssa_get_player_details_by_username($this->api_lib->request()->params('player_username'), $this->game_platform_id);
            $this->assertPlayerDetailsNotEmpty($this->player_details);
    
            if (!empty($this->player_details['token'])) {
                $this->ssa_update_player_token($this->player_details['player_id'], $this->player_details['token']);
                $token = $this->player_details['token'];
            } else {
                $token = $this->game_api->getPlayerToken($this->player_details['player_id']);
            }

            $status = 200;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } catch (ApiException $e) {
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $code = self::RESPONSE_SERVER_ERROR['code'];
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
        $get_transaction = $this->ssa_get_transaction($this->transaction_table, $where, $selectedColumns, $order_by);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transaction)) {
                $get_transaction = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        return $get_transaction;
    }

    protected function getTransactions($where = []) {
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
        $extraInfo = [];
        $provider = !empty($this->api_lib->request()->params('provider')) ? $this->api_lib->request()->params('provider') : $this->api_lib->request()->params('provider', null, 'GET');

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
            'transaction_id' => $this->api_lib->request()->params('api_id'),
            'round_id' => $this->api_lib->request()->params('bet_num'),
            'game_code' => $this->api_lib->request()->params('channel', $this->provider),
            'wallet_adjustment_status' => !empty($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : $this->ssa_retained,
            'amount' => !empty($data['amount']) ? $data['amount'] : 0,
            'before_balance' => !empty($data['before_balance']) ? $data['before_balance'] : 0,
            'after_balance' => !empty($data['after_balance']) ? $data['after_balance'] : 0,
            'status' => $this->transaction_status,
            'start_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'reference_transaction_id' => $this->api_lib->request()->params('ori_api_id'),
            'is_end_round' => $this->is_end_round,
            'is_processed' => !empty($data['is_processed']) ? $data['is_processed'] : 0,

            // addtional
            'provider' => $provider,

            // default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => $this->utils->isNotEmptyArray($this->api_lib->request()->params()) ? json_encode($this->api_lib->request()->params()) : null,
            'response' => null,
            'extra_info' => $this->utils->isNotEmptyArray($extraInfo) ? json_encode($extraInfo) : null,
            'valid_bet_amount' => isset($data['valid_bet_amount']) ? $data['valid_bet_amount'] : 0,
            'bet_amount' => isset($data['bet_amount']) ? $data['bet_amount'] : 0,
            'win_amount' => isset($data['win_amount']) ? $data['win_amount'] : 0,
            'result_amount' => isset($data['result_amount']) ? $data['result_amount'] : 0,
            'flag_of_updated_result' => isset($data['flag_of_updated_result']) ? $data['flag_of_updated_result'] : $this->ssa_flag_not_updated,
            'seamless_service_unique_id' => $this->seamless_service_unique_id_with_game_prefix,
            'external_game_id' => $this->api_lib->request()->params('channel'),
            'external_transaction_id' => $this->api_lib->request()->params('api_id'),
            'external_unique_id' => $this->external_unique_id,
        ];

        $insertData['md5_sum'] = md5(json_encode($insertData));

        // $this->utils->debug_log(__METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'insertData', $insertData);

        return $this->ssa_insert_transaction_data($this->transaction_table, $insertData);
    }

    protected function saveFailedTransactionData($data = []) {
        if (empty($this->external_unique_id)) {
            return false;
        }

        if (!empty($this->remote_wallet_status)) {
            $errorCode = $this->remote_wallet_status;
        } else {
            $errorCode = !empty($data['code']) ? $data['code'] : null;
        }

        $saveData = [
            'transaction_id' => $this->api_lib->request()->params('api_id'),
            'round_id' => $this->api_lib->request()->params('bet_num'),
            'external_game_id' => $this->api_lib->request()->params('channel'),
            'player_id' => $this->api_lib->lookup($this->player_details, 'player_id'),
            'game_username' => $this->api_lib->lookup($this->player_details, 'game_username'),
            'amount' => abs($this->api_lib->request()->params('amount', 0)),
            'balance_adjustment_type' => $this->transaction_type == self::DEBIT ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $this->action_type,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->api_lib->request()->params()),
            'remote_raw_data' => $this->utils->isNotEmptyArray($data) ? json_encode($data) : null,
            'remote_wallet_status' => $errorCode,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $this->game_platform_id .'-'. $this->external_unique_id,
        ];

        $saveData['md5_sum'] = md5(json_encode($saveData));
        $saveData['transaction_date'] = $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format);
        $saveData['request_id'] = $this->utils->getRequestId();
        $saveData['headers'] = json_encode($this->api_lib->request()->headers());

        $tableName = $this->ssa_failed_remote_common_seamless_transactions_table;

        // check if exist
        if ($this->use_failed_transaction_monthly_table) {
            $tableName = $this->ssa_get_current_year_month_table($tableName);
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
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message'];
        } elseif ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $code = self::RESPONSE_BALANCE_INSUFFICIENT['code'];
            $message = self::RESPONSE_BALANCE_INSUFFICIENT['message'];
        } elseif ($this->ssa_remote_wallet_error_maintenance()) {
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message'];
        } elseif ($this->ssa_remote_wallet_error_double_unique_id()) {
            $success = true;
            $code = self::RESPONSE_SUCCESS['code'];
            $message = self::RESPONSE_SUCCESS['message'];
        } else {
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = "{$adjustmentType} balance failed";
        }

        return compact(['success', 'code', 'message']);
    }

    protected function walletAdjustment($adjustmentType, $amount, $balance) {
        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = 'Wallet adjustment failed';
        $amount = $this->ssa_amount_conversion(abs($amount), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $beforeBalance = $afterBalance = $balance;
        $walletAdjustmentStatus = $this->ssa_retained;
        $isProcessed = false;

        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([
            $this->game_platform_id,
            $this->external_unique_id,
        ]);

        $this->seamless_service_unique_id_with_game_prefix = $this->utils->mergeArrayValues([
            'game',
            $this->seamless_service_unique_id,
        ]);

        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->api_lib->request()->params('channel'));
        $this->ssa_set_game_provider_round_id($this->api_lib->request()->params('bet_num'));
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);
        $this->ssa_set_game_provider_action_type($this->action_type);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        if ($amount == 0) {
            $success = true;
            $walletAdjustmentStatus = $this->ssa_retained;
            $isProcessed = true;

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
                        $status = 400;
                        $code = self::RESPONSE_BALANCE_INSUFFICIENT['code'];
                        $message = self::RESPONSE_BALANCE_INSUFFICIENT['message'];
                    } else {
                        $afterBalance = null;
                        $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $afterBalance);
    
                        if ($success) {
                            $code = self::RESPONSE_SUCCESS['code'];
                            $message = self::RESPONSE_SUCCESS['message'];
                            $walletAdjustmentStatus = $this->ssa_decreased;
                            $isProcessed = true;
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
    
                                $code = !empty($validateRemoteWalletError['code']) ? $validateRemoteWalletError['code'] : self::RESPONSE_SERVER_ERROR['code'];
                                $message = !empty($validateRemoteWalletError['message']) ? $validateRemoteWalletError['message'] : 'Remote wallet failed';
                            } else {
                                $code = self::RESPONSE_SERVER_ERROR['code'];
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
                        $isProcessed = true;
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

                            $code = !empty($validateRemoteWalletError['code']) ? $validateRemoteWalletError['code'] : self::RESPONSE_SERVER_ERROR['code'];
                            $message = !empty($validateRemoteWalletError['message']) ? $validateRemoteWalletError['message'] : 'Remote wallet failed';
                        } else {
                            $code = self::RESPONSE_SERVER_ERROR['code'];
                            $message = 'Increase balance failed';
                        }
                    }
                    break;
                default:
                    $code = self::RESPONSE_SERVER_ERROR['code'];
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
            'is_processed' => $isProcessed,
        ];

        // array_push($this->saved_multiple_transactions, $data);

        if ($code === self::RESPONSE_SUCCESS['code']) {
            $status = 200;
        }

        return compact(['success', 'code', 'message', 'status', 'data']);
    }
    
    protected function assertPlayerDetailsNotEmpty($playerDetails, $status = 200) {
        $this->api_lib->assertion()->assertNotEmpty($playerDetails, self::RESPONSE_PLAYER_INVALID['code'], 'Player not found', $status);
    }

    protected function assertGameUsernameEquals($actual, $expected, $status = 200) {
        if ($actual != $expected) {
            $this->api_lib->setHint('player', $expected);
            $this->api_lib->assertion()->assertEquals($actual, $expected, self::RESPONSE_PLAYER_INVALID['code'], 'Invalid Player', $status);
        }
    }

    protected function assertPlayerNotBlocked($gameUsername, $status = 200) {
        $this->api_lib->assertion()->assertFalse($this->game_api->isBlockedUsernameInDB($gameUsername), self::RESPONSE_PLAYER_INVALID['code'], self::RESPONSE_ERROR_PLAYER_BLOCKED['message'], $status);
    }

    protected function balance($playerGameUsername) {
        $this->api_method = __FUNCTION__;

        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $requestParams = $this->api_lib->request()->params(null, null, 'GET');

        try {
            $rules = [
                'provider' => ['required'],
            ];

            // request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($requestParams, $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_API_ID_INVALID['code'], $validateRequest->message, 400);

            // player details and validation
            $this->player_details = $this->ssa_get_player_details_by_game_username($playerGameUsername, $this->game_platform_id);
            $this->assertPlayerDetailsNotEmpty($this->player_details, 400);
            $this->assertGameUsernameEquals($playerGameUsername, $this->player_details['game_username'], 400);
            $this->assertPlayerNotBlocked($this->player_details['game_username'], 400);

            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message) {
                $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);

                // validate player balance
                if ($this->player_balance === null || $this->player_balance === false) {
                    $code = self::RESPONSE_SERVER_ERROR['code'];
                    $message = 'Unable to get player balance';
                    return false;
                }

                return true;
            });

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
        }

        if ($success) {
            $status = 200;
            $response = $this->successData();
        } else {
            $response = $this->errorData($code, $message);
        }

        return $this->response($response, $status);
    }

    protected function bet() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::DEBIT;
        $this->transaction_status = Game_logs::STATUS_PENDING;
        $this->action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $this->is_end_round = false;

        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = "Internal server error {$this->api_method}";

        try {
            $rules = [
                'api_id' => ['required'], // transaction_id
                'bet_num' => ['optional'], // round_id
                'provider' => ['required'],
                'channel' => ['optional'], //game_code
                'player' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'negative'],
                'bet_at' => ['required', 'nullable'],
            ];

            // Request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_API_ID_INVALID['code'], $validateRequest->message, 400);

            $playerGameUsername = $this->api_lib->request()->params('player');
            $transactionId = $this->api_lib->request()->params('api_id');
            $roundId = $this->api_lib->request()->params('bet_num');
            $amount = abs($this->api_lib->request()->params('amount', 0));

            $this->external_unique_id = $this->utils->mergeArrayValues([$this->action_type, $transactionId]);
            $transaction = $this->getTransaction(['external_unique_id' => $this->external_unique_id]);
            $success = $this->transaction_already_exists = !empty($transaction);

            if ($this->transaction_already_exists) {
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->ssa_get_player_details_by_game_username($playerGameUsername, $this->game_platform_id);
            }

            $this->assertPlayerDetailsNotEmpty($this->player_details, 400);
            $this->assertGameUsernameEquals($playerGameUsername, $this->player_details['game_username'], 400);
            $this->assertPlayerNotBlocked($this->player_details['game_username'], 400);

            $data = compact([
                'transactionId',
                'roundId',
                'amount',
            ]);

            if (!$this->transaction_already_exists) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message, &$status, $data) {
                    $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);
                    // validate player balance
                    if ($this->player_balance === null || $this->player_balance === false) {
                        $code = self::RESPONSE_SERVER_ERROR['code'];
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
            }

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
        }

        if ($success) {
            $status = 200;
            $response = $this->successData();
        } else {
            $response = $this->errorData($code, $message);
        }

        return $this->response($response, $status);
    }

    protected function settle() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->transaction_status = Game_logs::STATUS_SETTLED;
        $this->action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;

        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = "Internal server error {$this->api_method}";

        try {
            $rules = [
                'api_id' => ['required'], // transaction_id
                'ori_api_id' => ['optional'], // reference_transaction_id
                'bet_num' => ['optional'], // round_id
                'provider' => ['required'],
                'player' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'positive'],
                'bet_at' => ['required', 'nullable'],
                'settle_at' => ['required', 'nullable'],
            ];

            // request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_API_ID_INVALID['code'], $validateRequest->message, 400);

            $playerGameUsername = $this->api_lib->request()->params('player');
            $transactionId = $this->api_lib->request()->params('api_id');
            $roundId = $this->api_lib->request()->params('bet_num');
            $amount = abs($this->api_lib->request()->params('amount', 0));
            $referenceTransactionId = $this->api_lib->request()->params('ori_api_id');

            $this->external_unique_id = $this->utils->mergeArrayValues([$this->action_type, $transactionId]);
            $transaction = $this->getTransaction(['external_unique_id' => $this->external_unique_id]);
            $success = $this->transaction_already_exists = !empty($transaction);

            if ($this->transaction_already_exists) {
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->ssa_get_player_details_by_game_username($playerGameUsername, $this->game_platform_id);
            }

            $this->assertPlayerDetailsNotEmpty($this->player_details, 400);
            $this->assertGameUsernameEquals($playerGameUsername, $this->player_details['game_username'], 400);
            $this->assertPlayerNotBlocked($this->player_details['game_username'], 400);

            $data = compact([
                'transactionId',
                'roundId',
                'amount',
                'referenceTransactionId',
            ]);
            
            if (!$this->transaction_already_exists) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message, &$status, $data) {
                    $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);
                    // validate player balance
                    if ($this->player_balance === null || $this->player_balance === false) {
                        $code = self::RESPONSE_SERVER_ERROR['code'];
                        $message = 'Unable to get player balance';
                        return false;
                    }

                    $whereBet = [
                        'player_id' => $this->player_details['player_id'],
                        'api_method' => self::API_METHOD_BET,
                        'round_id' => $data['roundId'],
                    ];

                    if (!empty($data['referenceTransactionId'])) {
                        $whereBet['transaction_id'] = $data['referenceTransactionId'];
                    }

                    // check single or multiple bets
                    $betTransactions = $this->getTransactions($whereBet);

                    if (empty($betTransactions)) {
                        $code = self::RESPONSE_API_ID_INVALID['code'];
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

                    if (empty($pendingBetCount) && !empty($refundedBetCount) && !empty($data['amount'])) {
                        $code = self::RESPONSE_API_ID_INVALID['code'];
                        $message = 'Unable to process payout, transaction already refunded';
                        return false;
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
            }

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
        }

        if ($success) {
            $status = 200;
            $response = $this->successData();
        } else {
            $response = $this->errorData($code, $message);
        }

        return $this->response($response, $status);
    }

    protected function cancel() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->transaction_status = Game_logs::STATUS_REFUND;
        $this->action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $this->is_end_round = true;

        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = "Internal server error {$this->api_method}";

        try {
            $rules = [
                'api_id' => ['required'], // transaction_id
                'ori_api_id' => ['optional'], // reference_transaction_id
                'bet_num' => ['optional'], // round_id
                'provider' => ['required'],
                'player' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'positive'],
            ];

            // request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_API_ID_INVALID['code'], $validateRequest->message, 400);

            $playerGameUsername = $this->api_lib->request()->params('player');
            $transactionId = $this->api_lib->request()->params('api_id');
            $roundId = $this->api_lib->request()->params('bet_num');
            $amount = abs($this->api_lib->request()->params('amount', 0));
            $referenceTransactionId = $this->api_lib->request()->params('ori_api_id');

            $this->external_unique_id = $this->utils->mergeArrayValues([$this->action_type, $transactionId]);
            $transaction = $this->getTransaction(['external_unique_id' => $this->external_unique_id]);
            $success = $this->transaction_already_exists = !empty($transaction);

            if ($this->transaction_already_exists) {
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->ssa_get_player_details_by_game_username($playerGameUsername, $this->game_platform_id);
            }

            $this->assertPlayerDetailsNotEmpty($this->player_details, 400);
            $this->assertGameUsernameEquals($playerGameUsername, $this->player_details['game_username'], 400);
            $this->assertPlayerNotBlocked($this->player_details['game_username'], 400);

            $data = compact([
                'transactionId',
                'roundId',
                'amount',
                'referenceTransactionId',
            ]);
            
            if (!$this->transaction_already_exists) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message, &$status, $data) {
                    $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);
                    // validate player balance
                    if ($this->player_balance === null || $this->player_balance === false) {
                        $code = self::RESPONSE_SERVER_ERROR['code'];
                        $message = 'Unable to get player balance';
                        return false;
                    }

                    $whereBet = [
                        'player_id' => $this->player_details['player_id'],
                        'api_method' => self::API_METHOD_BET,
                        'round_id' => $data['roundId'],
                    ];
    
                    if (!empty($data['referenceTransactionId'])) {
                        $whereBet['transaction_id'] = $data['referenceTransactionId'];
                    }

                    // check single or multiple bets
                    $betTransactions = $this->getTransactions($whereBet);

                    if (empty($betTransactions)) {
                        $code = self::RESPONSE_API_ID_INVALID['code'];
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

                    if (empty($pendingBetCount)) {
                        $code = self::RESPONSE_API_ID_INVALID['code'];
                        $message = 'Unable to process refund, transaction already settled';
                        return false;
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
            }

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
        }

        if ($success) {
            $status = 200;
            $response = $this->successData();
        } else {
            $response = $this->errorData($code, $message);
        }

        return $this->response($response, $status);
    }

    protected function check($transactionId) {
        $this->api_method = __FUNCTION__;
        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = "Internal server error {$this->api_method}";
        $transaction = [];
        $requestParams = $this->api_lib->request()->params(null, null, 'GET');

        try {
            $rules = [
                'type' => ['required'],
                'player' => ['required'],
            ];

            // request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($requestParams, $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_API_ID_INVALID['code'], $validateRequest->message, 400);

            $transaction = $this->getTransaction([
                'game_username' => $requestParams['player'],
                'api_method' => $requestParams['type'],
                'transaction_id' => $transactionId,
            ]);

            $success = $this->transaction_already_exists = !empty($transaction);

            if ($this->transaction_already_exists) {
                $code = self::RESPONSE_SUCCESS['code'];
                $message = self::RESPONSE_SUCCESS['message'];
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->ssa_get_player_details_by_game_username($requestParams['player'], $this->game_platform_id);
            }

            $this->assertPlayerDetailsNotEmpty($this->player_details, 400);
            $this->assertGameUsernameEquals($requestParams['player'], $this->player_details['game_username'], 400);
            $this->assertPlayerNotBlocked($this->player_details['game_username'], 400);

            // if not exist, check if failed transaction or not really exist
            if (!$this->transaction_already_exists) {
                $failedTransactionTable = $this->ssa_failed_remote_common_seamless_transactions_table;

                if ($this->use_failed_transaction_monthly_table) {
                    $failedTransactionTable = $this->ssa_get_current_year_month_table($failedTransactionTable);
                }

                switch ($requestParams['type']) {
                    case self::API_METHOD_BET:
                        $action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                        break;
                    case self::API_METHOD_SETTLE:
                    case self::API_METHOD_JACKPOT:
                        $action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                        break;
                    case self::API_METHOD_CANCEL:
                        $action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
                        break;
                    default:
                        $action = null;
                        break;
                }

                $transaction = $this->ssa_get_transaction($failedTransactionTable, [
                    'game_username' => $requestParams['player'],
                    'action' => $action,
                    'transaction_id' => $transactionId,
                ]);

                if (empty($transaction)) {
                    $code = self::RESPONSE_API_ID_NOT_FOUND['code'];
                    $message = self::RESPONSE_API_ID_NOT_FOUND['message'];

                    if ($this->use_failed_transaction_monthly_table) {
                        // check previous year month table
                        $previousFailedTransactionTable = $this->ssa_get_previous_year_month_table($this->ssa_failed_remote_common_seamless_transactions_table);

                        $transaction = $this->ssa_get_transaction($previousFailedTransactionTable, [
                            'game_username' => $requestParams['player'],
                            'action' => $action,
                            'transaction_id' => $transactionId,
                        ]);
                    }
                }

                if (!empty($transaction)) {
                    $rawData = !empty($transaction['remote_raw_data']) ? json_decode($transaction['remote_raw_data']) : null;
                    $code = self::RESPONSE_API_ID_INVALID['code'];
                    $message = !empty($rawData->message) ? $rawData->message : self::RESPONSE_API_ID_INVALID['message'];
                }
            }

            $this->api_lib->assertion()->assertTrue($code === self::RESPONSE_SUCCESS['code'], $code, $message, 400);

            $success = true;
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
        }

        if ($success) {
            $status = 200;
            $response = $this->successData([
                'api_id' => $transactionId,
            ]);
        } else {
            $response = $this->errorData($code, $message);
        }

        return $this->response($response, $status);
    }

    protected function jackpot() {
        $this->api_method = __FUNCTION__;
        $this->transaction_type = self::CREDIT;
        $this->transaction_status = Game_logs::STATUS_SETTLED;
        $this->action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $this->is_end_round = true;

        $success = false;
        $status = 500;
        $code = self::RESPONSE_SERVER_ERROR['code'];
        $message = "Internal server error {$this->api_method}";

        try {
            $rules = [
                'api_id' => ['required'], // transaction_id
                'ori_api_id' => ['optional'], // reference_transaction_id
                'bet_num' => ['optional'], // round_id
                'provider' => ['required'],
                'player' => ['required'],
                'amount' => ['required', 'nullable', 'numeric', 'positive'],
                'bet_at' => ['required', 'nullable'],
                'settle_at' => ['required', 'nullable'],
            ];
    
            // request validation
            $validateRequest = $this->api_lib->validation()->validateRequest($this->api_lib->request()->params(), $rules);
            $this->api_lib->assertion()->assertTrue($validateRequest->is_valid, self::RESPONSE_API_ID_INVALID['code'], $validateRequest->message, 400);

            $playerGameUsername = $this->api_lib->request()->params('player');
            $transactionId = $this->api_lib->request()->params('api_id');
            $roundId = $this->api_lib->request()->params('bet_num');
            $amount = abs($this->api_lib->request()->params('amount', 0));
            $referenceTransactionId = $this->api_lib->request()->params('ori_api_id');

            $this->external_unique_id = $this->utils->mergeArrayValues([$this->action_type, $transactionId]);
            $transaction = $this->getTransaction(['external_unique_id' => $this->external_unique_id]);
            $success = $this->transaction_already_exists = !empty($transaction);

            if ($this->transaction_already_exists) {
                $this->player_balance = $this->api_lib->lookup($transaction, 'after_balance');

                $this->player_details = [
                    'player_id' => $this->api_lib->lookup($transaction, 'player_id'),
                    'game_username' => $this->api_lib->lookup($transaction, 'game_username'),
                ];
            } else {
                $this->player_details = $this->ssa_get_player_details_by_game_username($playerGameUsername, $this->game_platform_id);
            }

            $this->assertPlayerDetailsNotEmpty($this->player_details, 400);
            $this->assertGameUsernameEquals($playerGameUsername, $this->player_details['game_username'], 400);
            $this->assertPlayerNotBlocked($this->player_details['game_username'], 400);

            $data = compact([
                'transactionId',
                'roundId',
                'amount',
                'referenceTransactionId',
            ]);
            
            if (!$this->transaction_already_exists) {
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use (&$code, &$message, &$status, $data) {
                    $this->player_balance = $this->player_model->getPlayerSubWalletBalance($this->player_details['player_id'], $this->game_platform_id, true);
                    // validate player balance
                    if ($this->player_balance === null || $this->player_balance === false) {
                        $code = self::RESPONSE_SERVER_ERROR['code'];
                        $message = 'Unable to get player balance';
                        return false;
                    }

                    $whereBet = [
                        'player_id' => $this->player_details['player_id'],
                        'api_method' => self::API_METHOD_BET,
                        'round_id' => $data['roundId'],
                    ];
    
                    if (!empty($data['referenceTransactionId'])) {
                        $whereBet['transaction_id'] = $data['referenceTransactionId'];
                    }
    
                    // check single or multiple bets
                    $betTransactions = $this->getTransactions($whereBet);
    
                    if (empty($betTransactions)) {
                        $code = self::RESPONSE_API_ID_INVALID['code'];
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

                    if (empty($pendingBetCount) && !empty($refundedBetCount) && !empty($data['amount'])) {
                        $code = self::RESPONSE_API_ID_INVALID['code'];
                        $message = 'Unable to process payout, transaction already refunded';
                        return false;
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
            }

            $this->api_lib->assertion()->assertTrue($success, $code, $message);
        } catch (ApiException $e) {
            $success = false;
            $status = $e->getStatus();
            $code = $e->getStringCode();
            $message = $e->getMessage();
        } catch (Exception $e) {
            $success = false;
            $status = 500;
            $code = self::RESPONSE_SERVER_ERROR['code'];
            $message = self::SYSTEM_ERROR_UNEXPECTED_ERROR_OCCURED['message'];
        }

        if ($success) {
            $status = 200;
            $response = $this->successData();
        } else {
            $response = $this->errorData($code, $message);
        }

        return $this->response($response, $status);
    }
}
