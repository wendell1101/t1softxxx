<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class King_maker_seamless_service_api extends BaseController {
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
    protected $transaction_type;
    protected $external_transaction_id = null;
    protected $prefix_for_external_transaction_id = 'T';

    // monthly transaction table
    protected $use_monthly_transactions_table = false;
    protected $force_check_previous_transactions_table = false;
    protected $force_check_other_transactions_table = false;
    protected $previous_table = null;

    // Additional 
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];
    private $current_adjustment_type = null;
    #END: default_local_properties ------------------------------------------------------------------------------------->

    /* default response format
        const <RESPONSE_NAME> = [
            'code' => <int> || '<str>',
            'message' => '<str>',
        ];
    */
    const RESPONSE_SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];

    const RESPONSE_INVALID_TOKEN = [
        'code' => 10,
        'message' => 'Invalid or expired token',
    ];

    const RESPONSE_INVALID_TOKEN_SCOPE = [
        'code' => 19,
        'message' => 'Invalid token scope',
    ];

    const RESPONSE_USER_BLOCKED = [
        'code' => 20,
        'message' => 'User blocked',
    ];

    const RESPONSE_INVALID_CREDENTIAL = [
        'code' => 30,
        'message' => 'Invalid Credential',
    ];

    const RESPONSE_CURRENCY_MISMATCH = [
        'code' => 50,
        'message' => 'Currency Mismatch',
    ];

    const RESPONSE_INSUFFICIENT_FUNDS_TO_PERFORM_OPERATION = [
        'code' => 100,
        'message' => 'Insufficient funds to perform the operation',
    ];

    const RESPONSE_INVALID_ARGUMENTS = [
        'code' => 300,
        'message' => 'Invalid arguments',
    ];

    const RESPONSE_MISSING_TOKEN = [
        'code' => 400,
        'message' => 'Missing token',
    ];

    const RESPONSE_INCORRECT_FORMAT = [
        'code' => 500,
        'message' => 'Incorrect format',
    ];

    const RESPONSE_TRANSACTION_DOES_NOT_EXIST = [
        'code' => 600,
        'message' => 'Transaction does not exist',
    ];

    const RESPONSE_TRANSACTION_ALREADY_CANCELLED = [
        'code' => 610,
        'message' => 'Transaction already cancelled',
    ];

    const RESPONSE_OPERATION_FAILED_DETERMINISTICALLY = [
        'code' => 800,
        'message' => 'Operation Failed',
    ];

    const RESPONSE_SYSTEM_ERROR = [
        'code' => 900,
        'message' => 'System Error',
    ];

    const RESPONSE_CONFIGURED_TIMEOUT_EXCEEDED = [
        'code' => 903,
        'message' => 'Configured Timeout Exceeded',
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

    const SYSTEM_ERROR_SWITCH_DB_FAILED = [
        'code' => 'SE_17',
        'message' => 'Switch DB Failed',
    ];

    const API_METHOD_BALANCE = 'balance';
    const API_METHOD_DEBIT = 'debit';
    const API_METHOD_CREDIT = 'credit';
    const API_METHOD_REWARD = 'reward';

    const API_METHODS = [
        self::API_METHOD_BALANCE,
        self::API_METHOD_DEBIT,
        self::API_METHOD_CREDIT,
        self::API_METHOD_REWARD,
    ];

    const TRANSFER_TYPE_API_METHODS = [
        self::API_METHOD_DEBIT,
        self::API_METHOD_CREDIT,
        self::API_METHOD_REWARD,
    ];

    const ALLOWED_API_METHODS = self::API_METHODS;

    const ACTION_SHOW_HINT = 'showHint';

    const ACTIONS = [
        self::ACTION_SHOW_HINT
    ];

    const ALLOWED_ACTIONS = self::ACTIONS;

    #additional_local_properties
    protected $brand_code;
    protected $client_id;
    protected $client_secret;
    protected $gpcode;

    const WALLET_CODE = 'MainWallet';

    const TRANSACTION_TYPES = [
        'PLACE_BET' => 500,
        'WIN_BET' => 510,
        'WIN_JACKPOT' => 511,
        'LOSE_BET' => 520,
        'FREE_BET' => 530,
        'TIE_BET' => 540,
        'CANCEL_TRANSACTION' => 560,
        'END_ROUND' => 590,
        'FUND_IN' => 600, // fund in the player's wallet
        'FUND_OUT' => 610, // fund out the player's wallet
        'CANCEL_FUND_OUT' => 611,
    ];

    const TRANSACTION_TYPE_PLACE_BET = 'placeBet';
    const TRANSACTION_TYPE_WIN_BET = 'winBet';
    const TRANSACTION_TYPE_WIN_JACKPOT = 'winJackpot';
    const TRANSACTION_TYPE_LOSE_BET = 'loseBet';
    const TRANSACTION_TYPE_FREE_BET = 'freeBet';
    const TRANSACTION_TYPE_TIE_BET = 'tieBet';
    const TRANSACTION_TYPE_CANCEL_TRANSACTION = 'cancelTransaction';
    const TRANSACTION_TYPE_END_ROUND = 'endRound';
    const TRANSACTION_TYPE_FUND_IN = 'fundIn';
    const TRANSACTION_TYPE_FUND_OUT = 'fundOut';
    const TRANSACTION_TYPE_CANCEL_FUND_OUT = 'cancelFundOut';

    const REFERENCE_ID_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPES['WIN_BET'],
        self::TRANSACTION_TYPES['LOSE_BET'],
        self::TRANSACTION_TYPES['FREE_BET'],
        self::TRANSACTION_TYPES['TIE_BET'],
        self::TRANSACTION_TYPES['CANCEL_TRANSACTION'],
    ];

    const BONUS_TYPE = [
        'NO_BONUS' => 0,
    ];

    const GAME_TYPES = [
        'SLOT' => 0,
        'TABLE_GAME' => 1,
    ];

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->ssa_is_player_game_username = true;
    }

    public function index($game_platform_id, $api_method, $action = null) {
        if ($this->initialize($game_platform_id, $api_method, $action)) {
            return $this->$api_method();
        }

        return $this->response();
    }

    protected function initialize($game_platform_id, $api_method, $action = null) {
        $this->game_platform_id = $game_platform_id;
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->action = $action;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $request_currency = $this->getRequestCurrency($api_method);

        if (!empty($request_currency)) {
            $this->ssa_switch_db($request_currency);
        }

        $this->responseConfig();

        if (empty($this->game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_EMPTY_GAME_API['code']);
            $this->ssa_hint[self::SYSTEM_ERROR_EMPTY_GAME_API['code']] = self::SYSTEM_ERROR_EMPTY_GAME_API['message'];
            return false;
        }

        $this->ssa_initialize_game_api($this->game_platform_id);
        $this->game_api = $this->ssa_game_api;

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

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
            $this->previous_table = $this->game_api->ymt_get_previous_year_month_table();

            // failed monthly transaction table
            $this->use_remote_wallet_failed_transaction_monthly_table = $this->game_api->use_remote_wallet_failed_transaction_monthly_table;

            // additional
            $this->brand_code = $this->game_api->brand_code;
            $this->client_id = $this->game_api->client_id;
            $this->client_secret = $this->game_api->client_secret;
            $this->gpcode = $this->game_api->gpcode;
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_INITIALIZE_GAME_API['code']);
            $this->ssa_hint[self::SYSTEM_ERROR_INITIALIZE_GAME_API['code']] = self::SYSTEM_ERROR_INITIALIZE_GAME_API['message'];
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code']);
            $this->ssa_hint[self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['code']] = self::SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND['message'];
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code']);
            $this->ssa_hint[self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['code']] = self::SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN['message'];
            return false;
        }

        return true;
    }

    protected function getRequestCurrency($api_method) {
        switch ($api_method) {
            case self::API_METHOD_BALANCE:
                $request_params = $this->ssa_request_params['users'];
                break;
            case self::API_METHOD_DEBIT:
            case self::API_METHOD_CREDIT:
            case self::API_METHOD_REWARD:
                $request_params = $this->ssa_request_params['transactions'];
                break;
            default:
                $request_params = [];
                break;
        }

        foreach ($request_params as $request_param) {
            if (!empty($request_param['cur'])) {
                return $request_param['cur'];
            }
        }

        return false;
    }

    protected function responseConfig() {
        $this->transfer_type_api_methods = self::TRANSFER_TYPE_API_METHODS;

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

        $this->ssa_set_custom_response = [
            'cur' => [
                'rules' => ['expected_value'],
                'http_response_status_code' => 400,
                'operator_response' => self::RESPONSE_CURRENCY_MISMATCH,
            ],
        ];
    }

    protected function rebuildRequestParams($data = null) {
        // request params
        $this->request_params['userid'] = isset($data['userid']) ? strtolower($data['userid']) : null;
        $this->request_params['authtoken'] = isset($data['authtoken']) ? $data['authtoken'] : null;
        $this->request_params['brandcode'] = isset($data['brandcode']) ? $data['brandcode'] : null;
        $this->request_params['amt'] = isset($data['amt']) ? $data['amt'] : 0;
        $this->request_params['cur'] = isset($data['cur']) ? $data['cur'] : null;
        $this->request_params['lang'] = isset($data['lang']) ? $data['lang'] : null;
        $this->request_params['ptxid'] = isset($data['ptxid']) ? $data['ptxid'] : null;
        $this->request_params['refptxid'] = isset($data['refptxid']) ? $data['refptxid'] : null;
        $this->request_params['roundid'] = isset($data['roundid']) ? $data['roundid'] : null;
        $this->request_params['gamecode'] = isset($data['gamecode']) ? $data['gamecode'] : null;
        $this->request_params['timestamp'] = isset($data['timestamp']) ? $data['timestamp'] : null;
        $this->request_params['senton'] = isset($data['senton']) ? $data['senton'] : null;
        $this->request_params['gpcode'] = isset($data['gpcode']) ? $data['gpcode'] : null;
        $this->request_params['gamename'] = isset($data['gamename']) ? $data['gamename'] : null;
        $this->request_params['externalgameid'] = isset($data['externalgameid']) ? $data['externalgameid'] : null;
        $this->request_params['txtype'] = isset($data['txtype']) ? $data['txtype'] : null;
        $this->request_params['platformtype'] = isset($data['platformtype']) ? $data['platformtype'] : null;
        $this->request_params['gametype'] = isset($data['gametype']) ? $data['gametype'] : null;
        $this->request_params['bonustype'] = isset($data['bonustype']) ? $data['bonustype'] : null;
        $this->request_params['externalroundid'] = isset($data['externalroundid']) ? $data['externalroundid'] : null;
        $this->request_params['betid'] = isset($data['betid']) ? $data['betid'] : null;
        $this->request_params['externalbetid'] = isset($data['externalbetid']) ? $data['externalbetid'] : null;
        $this->request_params['isclosinground'] = isset($data['isclosinground']) ? $data['isclosinground'] : null;
        $this->request_params['isbuyingame'] = isset($data['isbuyingame']) ? $data['isbuyingame'] : null;
        $this->request_params['ggr'] = isset($data['ggr']) ? $data['ggr'] : 0;
        $this->request_params['turnover'] = isset($data['turnover']) ? $data['turnover'] : 0;
        $this->request_params['commission'] = isset($data['commission']) ? $data['commission'] : 0;
        $this->request_params['unsettledbets'] = isset($data['unsettledbets']) ? $data['unsettledbets'] : 0;
        $this->request_params['walletcode'] = isset($data['walletcode']) ? $data['walletcode'] : null;
        $this->request_params['bonuscode'] = isset($data['bonuscode']) ? $data['bonuscode'] : null;
        $this->request_params['redeemcode'] = isset($data['redeemcode']) ? $data['redeemcode'] : null;
        $this->request_params['desc'] = isset($data['desc']) ? $data['desc'] : null;

        // additional
        $this->request_params['transaction_type'] = isset($data['txtype']) ? $this->getTransactionType($data['txtype']) : $this->api_method;

        $external_unique_id_params = [
            // $this->api_method,
            $this->request_params['ptxid'],
        ];

        $external_unique_id = $this->utils->mergeArrayValues($external_unique_id_params);

        $this->request_params['external_unique_id'] = !empty($this->request_params['ptxid']) ? $external_unique_id : null;
    }

    protected function initialize_player($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id) {

        if (!$this->ssa_initialize_player($get_balance, $get_player_by, $subject, $game_platform_id)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'User not found');
            $this->ssa_hint['userid'] = 'User not found';
            return false;
        }

        $this->player_details = $this->ssa_player_details();
        $this->player_balance = $this->ssa_player_balance;

        return true;
    }

    protected function systemCheckpoint($config = [
        'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
        'USE_GAME_API_DISABLED' => true,
        'USE_GAME_API_MAINTENANCE' => true,
    ]) {
        $this->ssa_system_checkpoint($config);

        if ($this->ssa_system_errors('SERVER_IP_ADDRESS_NOT_ALLOWED')) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_OPERATION_FAILED_DETERMINISTICALLY, 'IP address is not allowed');
            $this->ssa_hint['ipaddress'] = $this->ssa_get_ip_address();
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_DISABLED')) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Game is disabled');
            return false;
        }

        if ($this->ssa_system_errors('GAME_API_MAINTENANCE')) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Game is under maintenance');
            return false;
        }

        // additional
        // required
        if (!$this->clientServerAuthorization()) {
            return false;
        }

        return true;
    }

    protected function isInsufficientBalance($balance, $amount) {
        if ($balance < $amount) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INSUFFICIENT_FUNDS_TO_PERFORM_OPERATION;
            $this->ssa_hint['amt'] = 'insufficient balance';
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
        return !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false);
    }

    protected function remoteWalletError(&$data) {
        // treat success if remote wallet return double uniqueid
        if ($this->ssa_remote_wallet_error_double_unique_id()) {
            return $data['is_processed'] = true;
        }

        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']);
            $this->ssa_hint[self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']] = self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['message'];
            $data['wallet_adjustment_status'] = $this->ssa_failed;
            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INSUFFICIENT_FUNDS_TO_PERFORM_OPERATION;
            $data['wallet_adjustment_status'] = $this->ssa_failed;
            return $data['is_processed'] = false;
        }

        if ($this->ssa_remote_wallet_error_maintenance()) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']);
            $this->ssa_hint[self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']] = self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['message'];
            $data['wallet_adjustment_status'] = $this->ssa_failed;
            return $data['is_processed'] = false;
        }

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['code']);
        $this->ssa_hint[self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['code']] = self::SYSTEM_ERROR_REMOTE_WALLET_UNKNOWN['message'];
        $data['wallet_adjustment_status'] = $this->ssa_failed;
        return $data['is_processed'] = false;
    }

    protected function walletAdjustment($adjustment_type, $query_type, $balance, $amount, $is_transaction_already_exists = false, $save_transaction_record_only = false, $allowed_negative_balance = false) {
        $this->current_adjustment_type = $adjustment_type; 

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $data = [];

        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($adjustment_type, $query_type, $balance, $amount, $is_transaction_already_exists, $save_transaction_record_only, $allowed_negative_balance, &$data) {
            // set remote wallet unique ids
            $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->request_params['external_unique_id']]);
            $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
            $this->ssa_set_external_game_id($this->request_params['gamecode']);

            // $before_balance = $after_balance = $this->player_balance = $balance;
            $before_balance = $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, false);
            $amount = $this->ssa_operate_amount(abs($amount), $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

            if ($adjustment_type == $this->ssa_decrease) {
                // if negative balance not allowed, check if insufficient
                if (!$allowed_negative_balance) {
                    if ($this->isInsufficientBalance($balance, $amount)) {
                        return false;
                    }
                }
            }

            $data = [
                // default
                'external_unique_id' => $this->request_params['external_unique_id'],
                'amount' => $amount,
                'before_balance' => $before_balance,
                'after_balance' => $after_balance,
                'wallet_adjustment_status' => $this->ssa_preserved,
                'is_processed' => false,
            ];

            if (!$is_transaction_already_exists) {
                // save transaction record first
                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

                $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $data);
                $is_transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->request_params['external_unique_id']]);

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
                if ($amount == 0 || $save_transaction_record_only) {
                    $data['wallet_adjustment_status'] = $this->ssa_retained;
                    $data['is_processed'] = true;
                    $success = true;
                } else {
                    // decrease wallet
                    if ($adjustment_type == $this->ssa_decrease) {
                        $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance, $allowed_negative_balance);
                        $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                        $this->utils->debug_log("KING MAKER SERVICE API: ", [
                            '$this->remote_wallet_status' => $this->remote_wallet_status,
                            '$success' => $success,
                            '!empty($this->remote_wallet_status)' => !empty($this->remote_wallet_status)
                        ]);


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

                                if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {

                                    // treat success if remote wallet return double uniqueid
                                    if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                        $success = true;
                                        $before_balance -= $amount;
                                    }
            
                                    if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                        $this->ssa_http_response_status_code = 500;
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (invalid remote wallet unique id)');
                                    }
            
                                    if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                        $this->ssa_http_response_status_code = 400;
                                        $this->ssa_operator_response = self::API_ERROR_NOT_ENOUGH_BALANCE; // Insufficient balance
                                    }
            
                                    if ($this->ssa_remote_wallet_error_maintenance()) {
                                        $this->ssa_http_response_status_code = 500;
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (remote wallet maintenance)');
                                    }
                                }else{
                                    $this->ssa_http_response_status_code = 500;
                                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_DECREASE_BALANCE['code']);
                                    $this->ssa_hint[self::SYSTEM_ERROR_DECREASE_BALANCE['code']] = self::SYSTEM_ERROR_DECREASE_BALANCE['message'];
                                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                                    $data['is_processed'] = false;
                                }
                                
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
                            if ($this->ssa_enabled_remote_wallet() && !empty($this->ssa_get_remote_wallet_error_code())) {
                                $data['wallet_adjustment_status'] = $this->ssa_remote_wallet_increased;
                                $success = $this->remoteWalletError($data);

                                if ($success) {
                                    $after_balance = $this->afterBalance($after_balance) + $amount;
                                }

                            } else {
                                if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {

                                    // treat success if remote wallet return double uniqueid
                                    if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                        $success = true;
                                        $before_balance -= $amount;
                                    }
            
                                    if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                        $this->ssa_http_response_status_code = 500;
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (invalid remote wallet unique id)');
                                    }
            
                                    if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                        $this->ssa_http_response_status_code = 400;
                                        $this->ssa_operator_response = self::API_ERROR_NOT_ENOUGH_BALANCE; // Insufficient balance
                                    }
            
                                    if ($this->ssa_remote_wallet_error_maintenance()) {
                                        $this->ssa_http_response_status_code = 500;
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (remote wallet maintenance)');
                                    }
                                } else {
                                    // default error
                                    $this->ssa_http_response_status_code = 500;
                                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_INCREASE_BALANCE['code']);
                                    $this->ssa_hint[self::SYSTEM_ERROR_INCREASE_BALANCE['code']] = self::SYSTEM_ERROR_INCREASE_BALANCE['message'];
                                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                                    $data['is_processed'] = false;
                                }

                            }
                        }

                        $data['after_balance'] = $this->player_balance = $this->afterBalance($after_balance);

                    // undefined action
                    } else {
                        $data['wallet_adjustment_status'] = $this->ssa_failed;
                        $data['is_processed'] = false;
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['code']);
                        $this->ssa_hint[self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['code']] = self::SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT['message'];

                        return false;
                    }
                }

                
                $transaction_data = [
                    'adjustment_type' => $this->current_adjustment_type,
                    'amount' => $amount,
                ];

                $this->utils->debug_log("KING MAKER SERVICE API: ", [
                    '$this->remote_wallet_status' => $this->remote_wallet_status,
                    '$transaction_data' => $transaction_data,
                    '$success' => $success,
                    '!empty($this->remote_wallet_status)' => !empty($this->remote_wallet_status),
                ]);

                if (!empty($this->remote_wallet_status)) {
                    $this->save_remote_wallet_failed_transaction($this->ssa_insert, $transaction_data);
                }

                array_push($this->saved_multiple_transactions, $data);

                return $success;
            } else {
                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'failed to save record.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['code']);
                $this->ssa_hint[self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['code']] = self::SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA['message'];
                return false;
            }
        });

        return $success;
    }

    protected function rebuildTransactionRequestData($data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $extra_info = [];

        $new_transaction_data = [
            // default
            'game_platform_id' => $this->game_platform_id,
            'token' => isset($this->request_params['token']) ? $this->request_params['token'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'language' => $this->language,
            'currency' => $this->currency,
            'api_method' => $this->api_method,
            'transaction_type' => $this->request_params['transaction_type'],
            'transaction_id' => isset($this->request_params['ptxid']) ? $this->request_params['ptxid'] : null,
            'round_id' => isset($this->request_params['roundid']) ? $this->request_params['roundid'] : null,
            'game_code' => isset($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null,
            'wallet_adjustment_status' => !empty($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : null,
            'amount' => !empty($data['amount']) ? $data['amount'] : 0,
            'before_balance' => !empty($data['before_balance']) ? $data['before_balance'] : 0,
            'after_balance' => !empty($data['after_balance']) ? $data['after_balance'] : 0,
            'status' => $this->setStatus(),
            'start_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
            'end_at' => $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),

            // addtional
            'authtoken' => isset($this->request_params['authtoken']) ? $this->request_params['authtoken'] : null,
            'brandcode' => isset($this->request_params['brandcode']) ? $this->request_params['brandcode'] : null,
            'refptxid' => isset($this->request_params['refptxid']) ? $this->request_params['refptxid'] : null,
            'timestamp' => isset($this->request_params['timestamp']) ? $this->request_params['timestamp'] : null,
            'senton' => isset($this->request_params['senton']) ? $this->request_params['senton'] : null,
            'gpcode' => isset($this->request_params['gpcode']) ? $this->request_params['gpcode'] : null,
            'gamename' => isset($this->request_params['gamename']) ? $this->request_params['gamename'] : null,
            'externalgameid' => isset($this->request_params['externalgameid']) ? $this->request_params['externalgameid'] : null,
            'txtype' => isset($this->request_params['txtype']) ? $this->request_params['txtype'] : null,
            'platformtype' => isset($this->request_params['platformtype']) ? $this->request_params['platformtype'] : null,
            'gametype' => isset($this->request_params['gametype']) ? $this->request_params['gametype'] : null,
            'bonustype' => isset($this->request_params['bonustype']) ? $this->request_params['bonustype'] : null,
            'externalroundid' => isset($this->request_params['externalroundid']) ? $this->request_params['externalroundid'] : null,
            'betid' => isset($this->request_params['betid']) ? $this->request_params['betid'] : null,
            'externalbetid' => isset($this->request_params['externalbetid']) ? $this->request_params['externalbetid'] : null,
            'isclosinground' => isset($this->request_params['isclosinground']) ? $this->request_params['isclosinground'] : null,
            'isbuyingame' => isset($this->request_params['isbuyingame']) ? $this->request_params['isbuyingame'] : null,
            'ggr' => isset($this->request_params['ggr']) ? $this->request_params['ggr'] : 0,
            'turnover' => isset($this->request_params['turnover']) ? $this->request_params['turnover'] : 0,
            'commission' => isset($this->request_params['commission']) ? $this->request_params['commission'] : 0,
            'unsettledbets' => isset($this->request_params['unsettledbets']) ? $this->request_params['unsettledbets'] : 0,
            'walletcode' => isset($this->request_params['walletcode']) ? $this->request_params['walletcode'] : null,
            'bonuscode' => isset($this->request_params['bonuscode']) ? $this->request_params['bonuscode'] : null,
            'redeemcode' => isset($this->request_params['redeemcode']) ? $this->request_params['redeemcode'] : null,
            'desc' => isset($this->request_params['desc']) ? $this->request_params['desc'] : null,

            // default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => !empty($this->ssa_request_params) && is_array($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
            'response' => null,
            'extra_info' => !empty($extra_info) && is_array($extra_info) ? json_encode($extra_info) : null,
            'bet_amount' => isset($this->request_params['bet_amount']) ? $this->request_params['bet_amount'] : 0,
            'win_amount' => isset($this->request_params['win_amount']) ? $this->request_params['win_amount'] : 0,
            'result_amount' => isset($this->request_params['result_amount']) ? $this->request_params['result_amount'] : 0,
            'flag_of_updated_result' => isset($this->request_params['flag_of_updated_result']) ? $this->request_params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
            'is_processed' => isset($data['is_processed']) ? $data['is_processed'] : 0,
            'external_unique_id' => $this->request_params['external_unique_id'],
            'seamless_service_unique_id' => $this->seamless_service_unique_id,
            'external_game_id' => !empty($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null,
            'external_transaction_id' => isset($this->request_params['ptxid']) ? $this->request_params['ptxid'] : null,
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
        switch ($this->request_params['transaction_type']) {
            case self::TRANSACTION_TYPE_PLACE_BET:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::TRANSACTION_TYPE_CANCEL_TRANSACTION:
            case self::TRANSACTION_TYPE_CANCEL_FUND_OUT:
                $result = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;

                if (isset($this->request_params['isclosinground'])) {
                    if ($this->request_params['isclosinground']) {
                        $result = Game_logs::STATUS_SETTLED;
                    }
                }
                break;
        }

        return $result;
    }

    protected function rebuildOperatorResponse($flag, $operator_response) {
        $is_normal = $flag == Response_result::FLAG_NORMAL;
        $message = isset($operator_response['message']) ? $operator_response['message'] : self::SYSTEM_ERROR_REBUILD_OPERATOR_RESPONSE['code'];
        $game_username = isset($this->player_details['game_username']) ? $this->player_details['game_username'] : null;

        if (isset($operator_response['code'])) {
            $operator_response = [
                'err' => $operator_response['code'],
                'errdesc' => $message,
            ];
        }

        // for showHint
        if (!empty($this->action)) {
            if (in_array($this->action, self::ALLOWED_ACTIONS)) {
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
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

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

                        $is_updated = $this->ssa_update_transaction_with_result($this->transaction_table, $data, 'external_unique_id', $external_unique_id);

                        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                            if (!$is_updated) {
                                $is_updated = $this->ssa_update_transaction_with_result($this->previous_table, $data, 'external_unique_id', $external_unique_id);
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
            $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::RESPONSE_SUCCESS['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::RESPONSE_SUCCESS['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);

            $extra_info = [];

            $md5_data = $data = [
                'game_platform_id' => $this->game_platform_id,
                'token' => isset($this->request_params['token']) ? $this->request_params['token'] : null,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                'transaction_type' => $this->api_method,
                'round_id' => !empty($this->request_params['roundid']) ? $this->request_params['roundid'] : null,
                'game_code' => !empty($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null,
                'status_code' => isset($http_response['code']) ? $http_response['code'] : null,
                'status_text' => isset($http_response['text']) ? $http_response['text'] : null,
                'response_code' => $code,
                'response_message' => $message,
                'flag' => $flag,
                'request' => !empty($this->ssa_request_params) ? json_encode($this->ssa_request_params) : null,
                'response' => $operator_response,
                'extra_info' => json_encode($extra_info),
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null,
                'external_unique_id' => !empty($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
            ];

            unset($md5_data['response'], $md5_data['extra_info']);

            $data['md5_sum'] = md5(json_encode($md5_data));
            $data['elapsed_time'] = $this->utils->getCostMs();
            $data['response_result_id'] = $this->response_result_id;
            $data['transaction_id'] = !empty($this->request_params['ptxid']) ? $this->request_params['ptxid'] : null;
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
            $this->saveServiceLogs();
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

    // ------------------------------------- START FUNCTION IMPLEMENTATION  ------------------------------------- //

    protected function clientServerAuthorization() {
        $request_client_id = isset($this->ssa_request_headers['X-Qm-Clientid']) ? $this->ssa_request_headers['X-Qm-Clientid'] : null;
        $request_client_secret = isset($this->ssa_request_headers['X-Qm-Clientsecret']) ? $this->ssa_request_headers['X-Qm-Clientsecret'] : null;

        if ($request_client_id != $this->client_id) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INVALID_CREDENTIAL;
            $this->ssa_hint['client_id'] = 'Wrong client id';
            return false;
        }

        if ($request_client_secret != $this->client_secret) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_INVALID_CREDENTIAL;
            $this->ssa_hint['client_secret'] = 'Wrong client secret';
            return false;
        }

        return true;
    }

    protected function getTransactionType($txtype) {
        switch ($txtype) {
            case self::TRANSACTION_TYPES['PLACE_BET']:
                return self::TRANSACTION_TYPE_PLACE_BET;
            case self::TRANSACTION_TYPES['WIN_BET']:
                return self::TRANSACTION_TYPE_WIN_BET;
            case self::TRANSACTION_TYPES['WIN_JACKPOT']:
                return self::TRANSACTION_TYPE_WIN_JACKPOT;
            case self::TRANSACTION_TYPES['LOSE_BET']:
                return self::TRANSACTION_TYPE_LOSE_BET;
            case self::TRANSACTION_TYPES['FREE_BET']:
                return self::TRANSACTION_TYPE_FREE_BET;
            case self::TRANSACTION_TYPES['TIE_BET']:
                return self::TRANSACTION_TYPE_TIE_BET;
            case self::TRANSACTION_TYPES['CANCEL_TRANSACTION']:
                return self::TRANSACTION_TYPE_CANCEL_TRANSACTION;
            case self::TRANSACTION_TYPES['END_ROUND']:
                return self::TRANSACTION_TYPE_END_ROUND;
            case self::TRANSACTION_TYPES['FUND_IN']:
                return self::TRANSACTION_TYPE_FUND_IN;
            case self::TRANSACTION_TYPES['FUND_OUT']:
                return self::TRANSACTION_TYPE_FUND_OUT;
            case self::TRANSACTION_TYPES['CANCEL_FUND_OUT']:
                return self::TRANSACTION_TYPE_CANCEL_FUND_OUT;
            default:
                return $this->api_method;
        }
    }

    protected function isValidTransactionalRequest($rule_sets) {
        foreach ($this->ssa_request_params['transactions'] as $transaction) {
            $this->rebuildRequestParams($transaction);

            $is_transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->request_params['external_unique_id']]);

            if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (!$is_transaction_already_exists) {
                    $is_transaction_already_exists = $this->ssa_is_transaction_exists($this->previous_table, ['external_unique_id' => $this->request_params['external_unique_id']]);
                }
            }

            if (!$is_transaction_already_exists) {
                if (!$this->initialize_player(true, $this->ssa_subject_type_game_username, $this->request_params['userid'], $this->game_platform_id)) {
                    return false;
                }

                // check lock balance also
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                    $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true);
    
                    if (isset($get_player_wallet['success']) && $get_player_wallet['success'] && isset($get_player_wallet['balance'])) {
                        return true;
                    } else {
                        return false;
                    }
                });

                if (!$success) {
                    return false;
                }

                if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['game_username'])) {
                    return false;
                }
                
                if (!$this->ssa_validate_request_params($transaction, $rule_sets, $this->ssa_set_custom_response, self::RESPONSE_INVALID_ARGUMENTS)) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                    $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                    return false;
                }

                if ($this->api_method == self::API_METHOD_DEBIT) {
                    if (!in_array($this->request_params['txtype'], [self::TRANSACTION_TYPES['PLACE_BET'], self::TRANSACTION_TYPES['FUND_OUT']])) {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter txtype');
                        $this->ssa_hint['txtype'] = $this->ssa_operator_response['message'];
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected function justSaveTransactionRecordOnly() {
        $save_transaction_record_only = false;

        if (!empty($this->request_params['txtype'])) {
            switch ($this->request_params['txtype']) {
                    // common 5**: if isbuyingame is true, just save record only.
                case self::TRANSACTION_TYPES['PLACE_BET']:
                case self::TRANSACTION_TYPES['WIN_BET']:
                case self::TRANSACTION_TYPES['LOSE_BET']:
                    $save_transaction_record_only = $this->request_params['isbuyingame'] ? true : false;
                    break;
                    // other 5**: if isbuyingame is true, just save record only.
                case self::TRANSACTION_TYPES['WIN_JACKPOT']:
                case self::TRANSACTION_TYPES['FREE_BET']:
                case self::TRANSACTION_TYPES['TIE_BET']:
                case self::TRANSACTION_TYPES['CANCEL_TRANSACTION']:
                case self::TRANSACTION_TYPES['END_ROUND']:
                    $save_transaction_record_only = $this->request_params['isbuyingame'] ? true : false;
                    break;
                    // 6**: if isbuyingame is true, process it.
                case self::TRANSACTION_TYPES['FUND_IN']:
                case self::TRANSACTION_TYPES['FUND_OUT']:
                case self::TRANSACTION_TYPES['CANCEL_FUND_OUT']:
                    $save_transaction_record_only = $this->request_params['isbuyingame'] ? false : true;
                    break;
                default:
                    $save_transaction_record_only = false;
                    break;
            }
        }

        return $save_transaction_record_only;
    }

    protected function isEmptyReferenceId() {
        if (empty($this->request_params['refptxid'])) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'parameter refptxid is required');
            return true;
        }

        return false;
    }

    protected function isBetExists($validate_round_id = true, $validate_game_code = true) {
        $transaction = $this->ssa_get_transaction($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_PLACE_BET,
            'external_unique_id' => $this->request_params['refptxid'],
            'is_processed' => true
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($transaction)) {
                $transaction = $this->ssa_get_transaction($this->previous_table, [
                    'transaction_type' => self::TRANSACTION_TYPE_PLACE_BET,
                    'external_unique_id' => $this->request_params['refptxid'],
                    'is_processed' => true
                ]);
            }
        }

        if (!empty($transaction)) {
            if (isset($transaction['game_username']) && $transaction['game_username'] != $this->request_params['userid']) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter userid');
                return false;
            }

            if (isset($transaction['round_id']) && $transaction['round_id'] != $this->request_params['roundid'] && $validate_round_id) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter roundid');
                return false;
            }

            if (isset($transaction['game_code']) && $transaction['game_code'] != $this->request_params['gamecode'] && $validate_game_code) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter gamecode');
                return false;
            }
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_TRANSACTION_DOES_NOT_EXIST;
            return false;
        }

        return true;
    }

    protected function isAlreadyProcessed() {
        $is_processed = $this->ssa_is_transaction_exists($this->transaction_table, [
            'refptxid' => $this->request_params['refptxid'],
            'is_processed' => true
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_processed) {
                $is_processed = $this->ssa_is_transaction_exists($this->previous_table, [
                    'refptxid' => $this->request_params['refptxid'],
                    'is_processed' => true
                ]);
            }
        }

        if ($is_processed) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_DOES_NOT_EXIST, 'Transaction already processed');
        }

        return $is_processed;
    }

    protected function isCancelTransactionExist() {
        $is_cancelled = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_CANCEL_TRANSACTION,
            'refptxid' => $this->request_params['refptxid'],
            'is_processed' => true
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_cancelled) {
                $is_cancelled = $this->ssa_is_transaction_exists($this->previous_table, [
                    'transaction_type' => self::TRANSACTION_TYPE_CANCEL_TRANSACTION,
                    'refptxid' => $this->request_params['refptxid'],
                    'is_processed' => true
                ]);
            }
        }

        if ($is_cancelled) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_TRANSACTION_ALREADY_CANCELLED;
        }

        return $is_cancelled;
    }

    protected function isFundOutExist($validate_round_id = true, $validate_game_code = true) {
        $where = [
            'transaction_type' => self::TRANSACTION_TYPE_FUND_OUT,
            'externalbetid' => $this->request_params['externalbetid'],
            'is_processed' => true
        ];

        if ($this->request_params['transaction_type'] == self::TRANSACTION_TYPE_CANCEL_FUND_OUT) {
            unset($where['externalbetid']);
            $where['transaction_id'] = $this->request_params['refptxid'];
        }

        $transaction = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($transaction)) {
                $transaction = $this->ssa_get_transaction($this->previous_table, $where);
            }
        }

        if (empty($transaction)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_TRANSACTION_DOES_NOT_EXIST;
            return false;
        }

        if (isset($transaction['game_username']) && $transaction['game_username'] != $this->request_params['userid']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter userid');
            return false;
        }

        if (isset($transaction['round_id']) && $transaction['round_id'] != $this->request_params['roundid'] && $validate_round_id) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter roundid');
            return false;
        }

        if (isset($transaction['game_code']) && $transaction['game_code'] != $this->request_params['gamecode'] && $validate_game_code) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter gamecode');
            return false;
        }

        return true;
    }

    protected function isFundInExist() {
        $is_fund_in = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_FUND_IN,
            'externalbetid' => $this->request_params['externalbetid'],
            'is_processed' => true
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_fund_in) {
                $is_fund_in = $this->ssa_is_transaction_exists($this->previous_table, [
                    'transaction_type' => self::TRANSACTION_TYPE_FUND_IN,
                    'externalbetid' => $this->request_params['externalbetid'],
                    'is_processed' => true
                ]);
            }
        }

        if ($is_fund_in) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_TRANSACTION_DOES_NOT_EXIST, 'Transaction already processed');
        }

        return $is_fund_in;
    }

    protected function isCancelFundOutExist() {
        $is_cancelled = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_CANCEL_FUND_OUT,
            'externalbetid' => $this->request_params['externalbetid'],
            'is_processed' => true
        ]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_cancelled) {
                $is_cancelled = $this->ssa_is_transaction_exists($this->previous_table, [
                    'transaction_type' => self::TRANSACTION_TYPE_CANCEL_FUND_OUT,
                    'externalbetid' => $this->request_params['externalbetid'],
                    'is_processed' => true
                ]);
            }
        }

        if ($is_cancelled) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_TRANSACTION_ALREADY_CANCELLED;
        }

        return $is_cancelled;
    }

    protected function balance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'testmode' => ['optional'],
                'users' => ['required', 'multidimensional_array'],
            ], [], self::RESPONSE_INVALID_ARGUMENTS)) {
                $operator_response['users'] = [];

                $rule_sets = [
                    'authtoken' => ['optional'],
                    'userid' => ['required'],
                    'brandcode' => ['required', "expected_value:{$this->brand_code}"],
                    'lang' => ['optional', "expected_value:{$this->language}"],
                    'cur' => ['required', "expected_value:{$this->currency}"],
                    'walletcode' => ['optional'],
                ];

                foreach ($this->ssa_request_params['users'] as $data) {
                    list($success, $response_data) = $this->processBalanceRequest($data, $rule_sets);

                    if ($success) {
                        array_push($operator_response['users'], $response_data);
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }

            if (!empty($operator_response['users'])) {
                $this->ssa_operator_response = $operator_response;
            }
        }

        return $this->response();
    }

    protected function processBalanceRequest($data, $rule_sets) {
        $success = false;
        $response_data = [];
        $err = null;
        $errdesc = null;
        $this->rebuildRequestParams($data);

        if ($this->initialize_player(false, $this->ssa_subject_type_game_username, $this->request_params['userid'], $this->game_platform_id)) {
            $success = true;
            $response_data['userid'] = $this->player_details['game_username'];

            if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['game_username'])) {
                $err = $this->ssa_operator_response['code'];
                $errdesc = $this->ssa_operator_response['message'];
            } else {
                if (!$this->ssa_validate_request_params($data, $rule_sets, $this->ssa_set_custom_response, self::RESPONSE_INVALID_ARGUMENTS)) {
                    $err = $this->ssa_operator_response['code'];
                    $errdesc = $this->ssa_custom_message_response;
                    $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                }
            }

            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () {
                $get_player_wallet = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id, true);

                if (isset($get_player_wallet['success']) && $get_player_wallet['success'] && isset($get_player_wallet['balance'])) {
                    $this->player_balance = $get_player_wallet['balance'];
                    return true;
                } else {
                    return false;
                }
            });
        } else {
            $err = $this->ssa_operator_response['code'];
            $errdesc = $this->ssa_operator_response['message'];
        }

        if (empty($response_data)) {
            $success = false;
        }

        if ($success) {
            $this->ssa_http_response_status_code = 200;

            if (!isset($err)) {
                $response_data['wallets'] = [];

                array_push($response_data['wallets'], [
                    'code' => self::WALLET_CODE,
                    'bal' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    'cur' => $this->request_params['cur']
                ]);
            } else {
                $response_data['err'] = $err;

                if (!empty($errdesc)) {
                    $response_data['errdesc'] = $errdesc;
                }
            }
        }

        return array($success, $response_data);
    }

    protected function debit() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'testmode' => ['optional'],
                'transactional' => ['required', 'boolean'],
                'transactions' => ['required', 'multidimensional_array'],
            ], [], self::RESPONSE_INVALID_ARGUMENTS)) {
                $operator_response['transactions'] = [];

                $rule_sets = [
                    'userid' => ['required'],
                    'authtoken' => ['optional'],
                    'brandcode' => ['required', "expected_value:{$this->brand_code}"],
                    'amt' => ['required', 'nullable', 'numeric', 'min:0'],
                    'cur' => ['required', "expected_value:{$this->currency}"],
                    'ipaddress' => ['optional'],
                    'ptxid' => ['required'],
                    'refptxid' => ['optional'],
                    'txtype' => [
                        'required',
                        'expected_value_in' => [
                            self::TRANSACTION_TYPES['PLACE_BET'], 
                            self::TRANSACTION_TYPES['FUND_OUT']
                        ]
                    ],
                    'timestamp' => ['required'],
                    'platformtype' => ['required', 'nullable'],
                    'gpcode' => ['required', "expected_value:{$this->gpcode}"],
                    'gamecode' => ['required'],
                    'gamename' => ['required'],
                    'gametype' => ['optional'],
                    'externalgameid' => ['required'],
                    'roundid' => ['required'],
                    'externalroundid' => ['required'],
                    'betid' => ['optional'],
                    'externalbetid' => ['optional'],
                    'senton' => ['required'],
                    'isclosinground' => ['required', 'boolean'],
                    'ggr' => ['required', 'nullable', 'numeric'],
                    'turnover' => ['required', 'nullable', 'numeric'],
                    'unsettledbets' => ['required', 'nullable', 'numeric'],
                    'isbuyingame' => ['optional', 'boolean'],
                    'walletcode' => ['optional'],
                    'bonustype' => ['optional'],
                    'bonuscode' => ['optional'],
                    'desc' => ['optional'],
                    'commission' => ['optional'],
                ];

                if ($this->ssa_request_params['transactional']) {
                    $process = $this->isValidTransactionalRequest($rule_sets);
                } else {
                    $process = true;
                }

                if ($process) {
                    foreach ($this->ssa_request_params['transactions'] as $transaction) {
                        list($success, $response_data) = $this->processDebitRequest($transaction, $rule_sets);

                        if ($success) {
                            array_push($operator_response['transactions'], $response_data);
                        }
                    }
                }/*  else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_SYSTEM_ERROR;
                } */
            } else {
                $this->ssa_http_response_status_code = 400;
                // $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, $this->ssa_custom_message_response);
                $this->ssa_operator_response = self::RESPONSE_SYSTEM_ERROR;
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        if (!empty($operator_response['transactions'])) {
            $this->ssa_operator_response = $operator_response;
        }

        return $this->response();
    }

    protected function processDebitRequest($data = [], $rule_sets) {
        $success = false;
        $response_data = [];
        $err = null;
        $errdesc = null;
        $this->rebuildRequestParams($data);

        $get_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $this->request_params['external_unique_id']]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transaction)) {
                $get_transaction = $this->ssa_get_transaction($this->previous_table, ['external_unique_id' => $this->request_params['external_unique_id']]);
            }
        }

        if (!empty($get_transaction)) {
            $is_transaction_already_exists = true;

            if (isset($get_transaction['is_processed']) && $get_transaction['is_processed']) {
                $is_transaction_already_processed = $success = true;
            } else {
                $is_transaction_already_processed = false;
            }
        } else {
            $is_transaction_already_processed = $is_transaction_already_exists = false;
        }

        if ($is_transaction_already_processed) {
            $txid = $ptxid = isset($get_transaction['transaction_id']) ? $get_transaction['transaction_id'] : $this->request_params['ptxid'];
            $balance = isset($get_transaction['after_balance']) ? $get_transaction['after_balance'] : $this->player_balance;
            $currency = isset($get_transaction['currency']) ? $get_transaction['currency'] : $this->request_params['cur'];
        } else {
            $success = true;
            $txid = $ptxid = $this->request_params['ptxid'];
            $amount = $this->request_params['amt'];
            $currency = $this->request_params['cur'];

            if ($this->initialize_player(true, $this->ssa_subject_type_game_username, $this->request_params['userid'], $this->game_platform_id)) {
                $balance = $this->player_balance;

                if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['game_username'])) {
                    $err = $this->ssa_operator_response['code'];
                    $errdesc = $this->ssa_operator_response['message'];
                } else {
                    if ($this->ssa_validate_request_params($data, $rule_sets, $this->ssa_set_custom_response, self::RESPONSE_INVALID_ARGUMENTS)) {
                        switch ($this->request_params['txtype']) {
                            case self::TRANSACTION_TYPES['PLACE_BET']:
                                $processed = $this->placeBet($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['FUND_OUT']:
                                $processed = $this->fundOut($balance, $amount, $is_transaction_already_exists);
                                break;
                            default:
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter txtype');
                                $this->ssa_hint['txtype'] = 'Invalid parameter txtype';
                                $processed = false;
                                break;
                        }



                        if ($processed) {
                            $balance = $this->player_balance;
                        } else {
                            $err = $this->ssa_operator_response['code'];
                            $errdesc = $this->ssa_operator_response['message'];
                        }
                    } else {
                        $err = $this->ssa_operator_response['code'];
                        $errdesc = $this->ssa_custom_message_response;
                        $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                    }
                }
            } else {
                $err = $this->ssa_operator_response['code'];
                $errdesc = $this->ssa_operator_response['message'];
            }
        }

        if ($success) {
            $this->ssa_http_response_status_code = 200;

            $response_data['txid'] = $txid;
            $response_data['ptxid'] = $ptxid;

            if (isset($balance)) {
                $response_data['bal'] = $this->ssa_operate_amount($balance, $this->precision, $this->conversion, $this->arithmetic_name);
            }

            $response_data['cur'] = $currency;
            $response_data['dup'] = $is_transaction_already_processed ? true : false;

            if (!empty($err)) {
                $success = false;
                $response_data['err'] = $err;
            }

            if (!empty($errdesc)) {
                $success = false;
                $response_data['errdesc'] = $errdesc;
            }

            if (!$success) {
                $this->ssa_http_response_status_code = 500;
            }
        }

        return array($success, $response_data);
    }

    protected function credit() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'testmode' => ['optional'],
                'transactional' => ['optional'],
                'transactions' => ['required', 'multidimensional_array'],
            ], [], self::RESPONSE_INVALID_ARGUMENTS)) {
                $is_transactional = isset($this->ssa_request_params['transactional']) && $this->ssa_request_params['transactional'] ? true : false;
                $transactions = $this->ssa_request_params['transactions'];
                $operator_response['transactions'] = [];

                $rule_sets = [
                    'userid' => ['required'],
                    'authtoken' => ['optional'],
                    'brandcode' => ['required', "expected_value:{$this->brand_code}"],
                    'amt' => ['required', 'nullable', 'numeric', 'min:0'],
                    'cur' => ['required', "expected_value:{$this->currency}"],
                    'ipaddress' => ['optional'],
                    'ptxid' => ['required'],
                    'refptxid' => ['optional'],
                    'txtype' => [
                        'required',
                        'expected_value_in' => [
                            self::TRANSACTION_TYPES['WIN_BET'],
                            self::TRANSACTION_TYPES['WIN_JACKPOT'],
                            self::TRANSACTION_TYPES['LOSE_BET'],
                            self::TRANSACTION_TYPES['FREE_BET'],
                            self::TRANSACTION_TYPES['TIE_BET'],
                            self::TRANSACTION_TYPES['CANCEL_TRANSACTION'],
                            self::TRANSACTION_TYPES['END_ROUND'],
                            self::TRANSACTION_TYPES['FUND_IN'],
                            self::TRANSACTION_TYPES['CANCEL_FUND_OUT'],
                        ]
                    ],
                    'timestamp' => ['required'],
                    'platformtype' => ['required', 'nullable'],
                    'gpcode' => ['required', "expected_value:{$this->gpcode}"],
                    'gamecode' => ['required'],
                    'gamename' => ['required'],
                    'gametype' => ['optional'],
                    'externalgameid' => ['required'],
                    'roundid' => ['required'],
                    'externalroundid' => ['required'],
                    'betid' => ['optional'],
                    'externalbetid' => ['optional'],
                    'senton' => ['required'],
                    'isclosinground' => ['required', 'boolean'],
                    'ggr' => ['required', 'nullable', 'numeric'],
                    'turnover' => ['required', 'nullable', 'numeric'],
                    'unsettledbets' => ['required', 'nullable', 'numeric'],
                    'isbuyingame' => ['optional', 'boolean'],
                    'walletcode' => ['optional'],
                    'bonustype' => ['optional'],
                    'bonuscode' => ['optional'],
                    'desc' => ['optional'],
                    'jpexternalid' => ['optional'],
                    'jpcur' => ['optional'],
                    'jprate' => ['optional'],
                    'jpamt' => ['optional'],
                    'jpcvtamt' => ['optional'],
                    'jpbal' => ['optional'],
                    'jpcontribs' => ['optional'],
                    'commission' => ['optional'],
                    'redeemcode' => ['optional'],
                ];

                if ($is_transactional) {
                    $process = $this->isValidTransactionalRequest($rule_sets);
                } else {
                    $process = true;
                }

                if ($process) {
                    foreach ($transactions as $transaction) {
                        list($success, $response_data) = $this->processCreditRequest($transaction, $rule_sets);

                        if ($success) {
                            array_push($operator_response['transactions'], $response_data);
                        }
                    }
                }/*  else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_SYSTEM_ERROR;
                } */
            } else {
                $this->ssa_http_response_status_code = 400;
                // $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, $this->ssa_custom_message_response);
                $this->ssa_operator_response = self::RESPONSE_SYSTEM_ERROR;
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        if (!empty($operator_response['transactions'])) {
            $this->ssa_operator_response = $operator_response;
        }

        return $this->response();
    }

    protected function processCreditRequest($data = [], $rule_sets) {
        $success = false;
        $response_data = [];
        $err = null;
        $errdesc = null;
        $this->rebuildRequestParams($data);

        $get_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $this->request_params['external_unique_id']]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transaction)) {
                $get_transaction = $this->ssa_get_transaction($this->previous_table, ['external_unique_id' => $this->request_params['external_unique_id']]);
            }
        }

        if (!empty($get_transaction)) {
            $is_transaction_already_exists = true;

            if (isset($get_transaction['is_processed']) && $get_transaction['is_processed']) {
                $is_transaction_already_processed = $success = true;
            } else {
                $is_transaction_already_processed = false;
            }
        } else {
            $is_transaction_already_processed = $is_transaction_already_exists = false;
        }

        if ($is_transaction_already_processed) {
            $txid = $ptxid = isset($get_transaction['transaction_id']) ? $get_transaction['transaction_id'] : $this->request_params['ptxid'];
            $balance = isset($get_transaction['after_balance']) ? $get_transaction['after_balance'] : $this->player_balance;
            $currency = isset($get_transaction['currency']) ? $get_transaction['currency'] : $this->request_params['cur'];
        } else {
            if ($this->initialize_player(true, $this->ssa_subject_type_game_username, $this->request_params['userid'], $this->game_platform_id)) {
                $success = true;
                $txid = $ptxid = $this->request_params['ptxid'];
                $balance = $this->player_balance;
                $amount = $this->request_params['amt'];
                $currency = $this->request_params['cur'];

                if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['game_username'])) {
                    $err = $this->ssa_operator_response['code'];
                    $errdesc = $this->ssa_operator_response['message'];
                } else {
                    if ($this->ssa_validate_request_params($data, $rule_sets, $this->ssa_set_custom_response, self::RESPONSE_INVALID_ARGUMENTS)) {
                        switch ($this->request_params['txtype']) {
                            case self::TRANSACTION_TYPES['WIN_BET']:
                                $processed = $this->winBet($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['WIN_JACKPOT']:
                                $processed = $this->winJackpot($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['LOSE_BET']:
                                $processed = $this->loseBet($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['FREE_BET']:
                                $processed = $this->freeBet($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['TIE_BET']:
                                $processed = $this->tieBet($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['CANCEL_TRANSACTION']:
                                $processed = $this->cancelTransaction($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['END_ROUND']:
                                $processed = $this->endRound($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['FUND_IN']:
                                $processed = $this->fundIn($balance, $amount, $is_transaction_already_exists);
                                break;
                            case self::TRANSACTION_TYPES['CANCEL_FUND_OUT']:
                                $processed = $this->cancelFundOut($balance, $amount, $is_transaction_already_exists);
                                break;
                            default:
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter txtype');
                                $this->ssa_hint['txtype'] = 'Invalid parameter txtype';
                                $processed = false;
                                break;
                        }

                        if ($processed) {
                            $balance = $this->player_balance;
                        } else {
                            $err = $this->ssa_operator_response['code'];
                            $errdesc = $this->ssa_operator_response['message'];
                        }
                    } else {
                        $err = $this->ssa_operator_response['code'];
                        $errdesc = $this->ssa_custom_message_response;
                        $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                    }
                }
            } else {
                $err = $this->ssa_operator_response['code'];
                $errdesc = $this->ssa_operator_response['message'];
            }
        }

        if ($success) {
            $this->ssa_http_response_status_code = 200;

            $response_data['txid'] = $txid;
            $response_data['ptxid'] = $ptxid;

            if (isset($balance)) {
                $response_data['bal'] = $this->ssa_operate_amount($balance, $this->precision, $this->conversion, $this->arithmetic_name);
            }

            $response_data['cur'] = $currency;
            $response_data['dup'] = $is_transaction_already_processed ? true : false;

            if (!empty($err)) {
                $response_data['err'] = $err;
            }

            if (!empty($errdesc)) {
                $response_data['errdesc'] = $errdesc;
            }
        }

        return array($success, $response_data);
    }

    protected function reward() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SYSTEM_ERROR, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->systemCheckpoint([
            'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => true,
            'USE_GAME_API_DISABLED' => true,
            'USE_GAME_API_MAINTENANCE' => true,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'transactional' => ['optional'],
                'brandcode' => ['required', "expected_value:{$this->brand_code}"],
                'transactions' => ['required', 'multidimensional_array'],
            ], [], self::RESPONSE_INVALID_ARGUMENTS)) {
                $is_transactional = isset($this->ssa_request_params['transactional']) && $this->ssa_request_params['transactional'] ? true : false;
                $this->request_params['brandcode'] = $this->ssa_request_params['brandcode'];
                $transactions = $this->ssa_request_params['transactions'];
                $operator_response['transactions'] = [];

                $rule_sets = [
                    'userid' => ['required'],
                    'amt' => ['required', 'nullable', 'numeric', 'min:0'],
                    'cur' => ['required', "expected_value:{$this->currency}"],
                    'ptxid' => ['required'],
                    'desc' => ['optional'],
                ];

                if ($is_transactional) {
                    $process = $this->isValidTransactionalRequest($rule_sets);
                } else {
                    $process = true;
                }

                if ($process) {
                    foreach ($transactions as $transaction) {
                        list($success, $response_data) = $this->processRewardRequest($transaction, $rule_sets);

                        if ($success) {
                            array_push($operator_response['transactions'], $response_data);
                        }
                    }
                }
            } else {
                $this->ssa_http_response_status_code = 400;
                // $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, $this->ssa_custom_message_response);
                $this->ssa_operator_response = self::RESPONSE_SYSTEM_ERROR;
                $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
            }
        }

        if (!empty($operator_response['transactions'])) {
            $this->ssa_operator_response = $operator_response;
        }

        return $this->response();
    }

    protected function processRewardRequest($data = [], $rule_sets) {
        $success = false;
        $response_data = [];
        $err = null;
        $errdesc = null;
        $this->rebuildRequestParams($data);

        $get_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $this->request_params['external_unique_id']]);

        if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (empty($get_transaction)) {
                $get_transaction = $this->ssa_get_transaction($this->previous_table, ['external_unique_id' => $this->request_params['external_unique_id']]);
            }
        }

        if (!empty($get_transaction)) {
            $is_transaction_already_exists = true;

            if (isset($get_transaction['is_processed']) && $get_transaction['is_processed']) {
                $is_transaction_already_processed = $success = true;
            } else {
                $is_transaction_already_processed = false;
            }
        } else {
            $is_transaction_already_processed = $is_transaction_already_exists = false;
        }

        if ($is_transaction_already_processed) {
            $txid = $ptxid = isset($get_transaction['transaction_id']) ? $get_transaction['transaction_id'] : $this->request_params['ptxid'];
            $balance = isset($get_transaction['after_balance']) ? $get_transaction['after_balance'] : $this->player_balance;
            $currency = isset($get_transaction['currency']) ? $get_transaction['currency'] : $this->request_params['cur'];
        } else {
            $success = true;
            $txid = $ptxid = $this->request_params['ptxid'];
            $amount = $this->request_params['amt'];
            $currency = $this->request_params['cur'];

            if ($this->initialize_player(true, $this->ssa_subject_type_game_username, $this->request_params['userid'], $this->game_platform_id)) {
                $balance = $this->player_balance;

                if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['game_username'])) {
                    $err = $this->ssa_operator_response['code'];
                    $errdesc = $this->ssa_operator_response['message'];
                } else {
                    if ($this->ssa_validate_request_params($data, $rule_sets, $this->ssa_set_custom_response, self::RESPONSE_INVALID_ARGUMENTS)) {
                        $processed = $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());

                        if ($processed) {
                            $balance = $this->player_balance;
                        } else {
                            $err = $this->ssa_operator_response['code'];
                            $errdesc = $this->ssa_operator_response['message'];
                        }
                    } else {
                        $err = $this->ssa_operator_response['code'];
                        $errdesc = $this->ssa_custom_message_response;
                        $this->ssa_hint[$this->ssa_request_param_key] = !isset($this->ssa_hint[$this->ssa_request_param_key]) ? $this->ssa_custom_message_response: $this->ssa_hint[$this->ssa_request_param_key];
                    }
                }
            } else {
                $err = $this->ssa_operator_response['code'];
                $errdesc = $this->ssa_operator_response['message'];
            }
        }

        if ($success) {
            $this->ssa_http_response_status_code = 200;

            $response_data['txid'] = $txid;
            $response_data['ptxid'] = $ptxid;

            if (isset($balance)) {
                $response_data['bal'] = $this->ssa_operate_amount($balance, $this->precision, $this->conversion, $this->arithmetic_name);
            }

            $response_data['cur'] = $currency;
            $response_data['dup'] = $is_transaction_already_processed ? true : false;

            if (!empty($err)) {
                $response_data['err'] = $err;
            }

            if (!empty($errdesc)) {
                $response_data['errdesc'] = $errdesc;
            }
        }

        return array($success, $response_data);
    }

    // txtype = 500
    protected function placeBet($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 510
    protected function winBet($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if ($this->isEmptyReferenceId()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        if ($this->isCancelTransactionExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 511
    protected function winJackpot($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if ($this->isEmptyReferenceId()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        if ($this->isCancelTransactionExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 520
    protected function loseBet($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if ($this->request_params['amt'] > 0) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_INVALID_ARGUMENTS, 'Invalid parameter amt, should be 0');
            return false;
        }

        if ($this->isEmptyReferenceId()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        if ($this->isCancelTransactionExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 530
    protected function freeBet($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if ($this->isEmptyReferenceId()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 540
    protected function tieBet($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if ($this->isEmptyReferenceId()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        if ($this->isCancelTransactionExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 560
    protected function cancelTransaction($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if ($this->isEmptyReferenceId()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        if ($this->isAlreadyProcessed()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 590
    protected function endRound($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if (!$this->isBetExists()) {
            return false;
        }

        if ($this->isCancelTransactionExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 610
    protected function fundOut($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        return $this->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 600
    protected function fundIn($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if (!$this->isFundOutExist(false)) {
            return false;
        }

        if ($this->isCancelFundOutExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    // txtype = 611
    protected function cancelFundOut($balance, $amount, $is_transaction_already_exists) {
        $this->request_params['transaction_type'] = __FUNCTION__;

        if (!$this->isFundOutExist()) {
            return false;
        }

        if ($this->isFundInExist()) {
            return false;
        }

        return $this->walletAdjustment($this->ssa_increase, $this->ssa_insert, $balance, $amount, $is_transaction_already_exists, $this->justSaveTransactionRecordOnly());
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $this->utils->debug_log( "KING MAKER SERVICE API: ", [
            '__CLASS__' => __CLASS__,
            '__METHOD__' => __METHOD__,
            '$data' => $data,
            '$this->request_params' => $this->request_params,
            '$this->player_details' => $this->player_details,
        ] );


        $save_data = $md5_data = [
            'transaction_id' => isset($this->request_params['ptxid']) ? $this->request_params['ptxid'] : null,
            'round_id' => isset($this->request_params['roundid']) ? $this->request_params['roundid'] : $this->request_params['roundid'],
            'external_game_id' => isset($this->request_params['externalgameid']) ? $this->request_params['externalgameid'] : null,
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
            'external_uniqueid' => !empty($this->request_params['external_unique_id']) ? $this->request_params['external_unique_id'] : null,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        $this->utils->debug_log( "KING MAKER SERVICE API: ", [
            '__CLASS__' => __CLASS__,
            '__METHOD__' => __METHOD__,
            '$data' => $data,
            '$save_data' => $save_data,
            '$md5_data' => $md5_data,
            '$this->request_params' => $this->request_params,
            '$this->player_details' => $this->player_details,
        ] );

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

 
        $result = $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);

        $this->utils->debug_log( "KING MAKER SERVICE API: ", [
            '__CLASS__' => __CLASS__,
            '__METHOD__' => __METHOD__,
            '$this->game_platform_id' => $this->game_platform_id,
            '$this->ssa_request_params' => $this->ssa_request_params,
            '$result' => $result,
        ] );

        return $result;
    }

}
