<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Cmd_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const RESPONSE_TYPE_JSON = 'json';
    const RESPONSE_TYPE_XML = 'xml';
    
    // default configs
    private $game_platform_id = CMD_SEAMLESS_GAME_API;
    private $game_api = null;
    private $api_method = 'default';
    private $language = null;
    private $currency = null;
    private $response_result_id = null;
    private $player_details = [];
    private $player_balance = 0;
    private $transaction_table = null;
    private $game_seamless_service_logs_table = null;
    private $save_game_seamless_service_logs = false;
    private $saved_transaction_id = null;
    private $saved_multiple_transactions = [];
    private $conversion = 1;
    private $precision = 2;
    private $arithmetic_name = '';
    private $adjustment_conversion = 1;
    private $adjustment_precision = 2;
    private $adjustment_arithmetic_name = '';
    private $transaction_already_exists = false;
    private $main_params = [];
    private $extra_params = [];
    private $game_provider_gmt = '+0 hours';
    private $game_provider_date_time_format = 'Y-m-d H:i:s';
    private $whitelist_ip_validate_api_methods = [];
    private $game_api_active_validate_api_methods = [];
    private $game_api_maintenance_validate_api_methods = [];
    private $required_token_api_methods = [];
    private $validate_player_api_methods = [];
    private $transfer_type_api_methods = [];
    private $content_type_xml_api_methods = [];
    private $content_type_plain_text_api_methods = [];
    private $save_data_api_methods = [];
    private $encrypt_response_api_methods = [];
    private $is_transfer_type = false;
    private $response_type = self::RESPONSE_TYPE_JSON;
    private $content_type = 'application/json';
    private $save_data = false;
    private $encrypt_response = false;
    private $seamless_service_unique_id = null;
    private $seamless_service_related_unique_id = null;
    private $seamless_service_related_action = null;
    private $external_game_id = null;
    private $rebuilded_operator_response = [];
    private $get_usec = false;
    protected $use_strip_slashes = true;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];
    private $allowed_negative_balance = false;

    const ACTION_PlaceBet = 1003;
    const ACTION_DangerRefund = 2001;
    const ACTION_ResettleTicket = 2002;
    const ACTION_BTBuyBack = 3001;
    const ACTION_SettleHT = 4001;
    const ACTION_SettleFT = 4002;
    const ACTION_SettleParlay = 4003;
    const ACTION_UnsettleHT = 5001;
    const ACTION_UnsettleFT = 5002;
    const ACTION_UnsettleParlay = 5003;
    const ACTION_CancelHT = 6001;
    const ACTION_CancelFT = 6002;
    const ACTION_UncancelHT = 7001;
    const ACTION_UncancelFT = 7002;
    const ACTION_SystemAdjustment = 9000;

    private $related_actions_map = [
        self::ACTION_DangerRefund => self::ACTION_PlaceBet, //Yes,sir,It is refund the bets amount
        self::ACTION_ResettleTicket => self::ACTION_PlaceBet,
        self::ACTION_BTBuyBack => self::ACTION_PlaceBet,
        self::ACTION_SettleHT => self::ACTION_PlaceBet,
        self::ACTION_SettleFT => self::ACTION_PlaceBet,
        self::ACTION_SettleParlay => self::ACTION_PlaceBet,
        self::ACTION_UnsettleHT => self::ACTION_SettleHT,
        self::ACTION_UnsettleFT => self::ACTION_SettleFT,
        self::ACTION_UnsettleParlay => self::ACTION_SettleParlay,
        self::ACTION_CancelHT => self::ACTION_PlaceBet,
        self::ACTION_CancelFT => self::ACTION_PlaceBet,
        self::ACTION_UncancelHT => self::ACTION_CancelHT,
        self::ACTION_UncancelFT => self::ACTION_CancelFT,
        self::ACTION_SystemAdjustment => self::ACTION_PlaceBet,
    ];

    private $actions_map =  [
        self::ACTION_DangerRefund => 'refund',
        self::ACTION_PlaceBet => 'bet',
        self::ACTION_ResettleTicket => 'payout',
        self::ACTION_BTBuyBack => 'cancel',
        self::ACTION_SettleHT => 'payout',
        self::ACTION_SettleFT => 'payout',
        self::ACTION_SettleParlay => 'payout',
        self::ACTION_UnsettleHT => 'refund',
        self::ACTION_UnsettleFT => 'refund',
        self::ACTION_UnsettleParlay => 'refund',
        self::ACTION_CancelHT => 'refund',
        self::ACTION_CancelFT => 'refund',
        self::ACTION_UncancelHT => 'refund',
        self::ACTION_UncancelFT => 'refund',
        self::ACTION_SystemAdjustment => 'adjustment',
    ];

    private $is_end_map =  [
        self::ACTION_DangerRefund => true,
        self::ACTION_PlaceBet => false,
        self::ACTION_ResettleTicket => true,
        self::ACTION_BTBuyBack => true,
        self::ACTION_SettleHT => true,
        self::ACTION_SettleFT => true,
        self::ACTION_SettleParlay => true,
        self::ACTION_UnsettleHT => false,
        self::ACTION_UnsettleFT => false,
        self::ACTION_UnsettleParlay => false,
        self::ACTION_CancelHT => true,
        self::ACTION_CancelFT => true,
        self::ACTION_UncancelHT => false,
        self::ACTION_UncancelFT => false,
        self::ACTION_SystemAdjustment => false,
    ];


    // additional
    protected $cmd_game_code = 'cmd_sports';
    protected $update_bet_round_id = false;

    const RESPONSE_SUCCESS = [
        'code' => 100,
        'message' => 'Success',
    ];

    const RESPONSE_FAILED = [
        'code' => -1,
        'message' => 'Failed',
    ];

    const RESPONSE_ILLEGAL_REQUEST = [
        'code' => -95,
        'message' => 'Illegal Request',
    ];

    const RESPONSE_TRANSACTION_ID_ALREADY_EXISTS = [
        'code' => -96,
        'message' => 'Transaction ID Already Exists',
    ];

    const RESPONSE_USER_NOT_EXISTS = [
        'code' => -97,
        'message' => 'User Not Exists',
    ];

    const RESPONSE_USER_ALREADY_EXISTS = [
        'code' => -98,
        'message' => 'User Already Exists',
    ];

    const RESPONSE_INVALID_ARGUMENTS = [
        'code' => -100,
        'message' => 'Invalid Arguments',
    ];

    const RESPONSE_UNDER_MAINTENANCE = [
        'code' => -101,
        'message' => 'Under Maintenance',
    ];

    const RESPONSE_REQUEST_LIMIT = [
        'code' => -102,
        'message' => 'Request Limit',
    ];

    const RESPONSE_ACCESS_DENIED = [
        'code' => -103,
        'message' => 'Access Denied ',
    ];

    const RESPONSE_SERVER_EXCEPTION = [
        'code' => -999,
        'message' => 'Server Exception',
    ];

    const RESPONSE_SERVER_TIMEOUT = [
        'code' => -1000,
        'message' => 'Server Timeout',
    ];

    const ACTION_LIST = [
        'DangerRefund' => 2001,
        'ResettleTicket' => 2002,
        'BTBuyBack' => 3001,
        'SettleHT' => 4001,
        'SettleFT' => 4002,
        'SettleParlay' => 4003,
        'UnsettleHT' => 5001,
        'UnsettleFT' => 5002,
        'UnsettleParlay' => 5003,
        'CancelHT' => 6001,
        'CancelFT' => 6002,
        'UncancelHT' => 7001,
        'UncancelFT' => 7002,
        'SystemAdjustment' => 9000,
    ];

    const API_METHOD_AUTHENTICATION = 'Authentication';
    const API_METHOD_GET_BALANCE = 'GetBalance';
    const API_METHOD_DEDUCT_BALANCE = 'DeductBalance';
    const API_METHOD_UPDATE_BALANCE = 'UpdateBalance';
    const METHOD_ENCRYPT = 'Encrypt';
    const METHOD_DECRYPT = 'Decrypt';

    const ALLOWED_API_METHODS = [
        self::METHOD_ENCRYPT,
        self::METHOD_DECRYPT,
        self::API_METHOD_AUTHENTICATION,
        self::API_METHOD_GET_BALANCE,
        self::API_METHOD_DEDUCT_BALANCE,
        self::API_METHOD_UPDATE_BALANCE,
    ];

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
        'action_id',
        'package_id',
        'date_sent',
        'date_received',

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
        $this->game_platform_id = !empty($this->uri->segment(2)) ? $this->uri->segment(2) : $this->game_platform_id;
        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);
        $this->setExtraParams();
        $this->setMainParams($this->extra_params);
    }

    public function index($game_platform_id = null, $api_method) {
        if ($this->initialize($game_platform_id, $api_method)) {
            return $this->$api_method(); 
        }

        return $this->response();
    }

    private function initialize($game_platform_id, $api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->responseConfig();

        if (empty($game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (initialize empty $game_platform_id)');
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($game_platform_id);

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

            // Additional
            $this->cmd_game_code = $this->game_api->cmd_game_code;
            $this->update_bet_round_id = $this->game_api->update_bet_round_id;
            $this->allowed_negative_balance = $this->game_api->allowed_negative_balance;

            $this->setExtraParams();
            $this->setMainParams($this->extra_params);
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (load_game_api)');
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Method ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    private function responseConfig() {
        $this->transfer_type_api_methods = [
            self::API_METHOD_DEDUCT_BALANCE,
            self::API_METHOD_UPDATE_BALANCE,
        ];

        $this->content_type_xml_api_methods = [
            self::API_METHOD_AUTHENTICATION,
        ];

        $this->content_type_plain_text_api_methods = [
            self::API_METHOD_GET_BALANCE,
            self::API_METHOD_DEDUCT_BALANCE,
            self::API_METHOD_UPDATE_BALANCE,
            self::METHOD_ENCRYPT,
        ];

        $this->save_data_api_methods = [
            self::API_METHOD_AUTHENTICATION,
            self::API_METHOD_GET_BALANCE,
            self::API_METHOD_DEDUCT_BALANCE,
            self::API_METHOD_UPDATE_BALANCE,
        ];

        $this->encrypt_response_api_methods = [
            self::API_METHOD_GET_BALANCE,
            self::API_METHOD_DEDUCT_BALANCE,
            self::API_METHOD_UPDATE_BALANCE,
        ];

        if (in_array($this->api_method, $this->transfer_type_api_methods)) {
            $this->is_transfer_type = true;
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

    private function setMainParams($data = []) {
        // default
        $this->main_params['token'] = !empty($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null;
        #$this->main_params['game_username'] = !empty($data['game_username']) ? strtolower($data['game_username']) : null;
        $this->main_params['game_username'] = !empty($data['game_username']) ? $data['game_username'] : null;
        $this->main_params['transaction_type'] = $this->api_method;
        $this->main_params['transaction_id'] = !empty($data['transaction_id']) ? $data['transaction_id'] : null;
        $this->main_params['game_code'] = $this->cmd_game_code;

        if (!empty($data['match_id'])) {
            $this->main_params['round_id'] = $data['match_id'];
        } else {
            $this->main_params['round_id'] = $this->main_params['transaction_id'];
        }
        
        $this->main_params['amount'] = !empty($data['transaction_amount']) ? abs($data['transaction_amount']) : 0;

        $this->main_params['package_id'] = !empty($this->ssa_request_params['packageId']) ? $this->ssa_request_params['packageId'] : null;
        $this->main_params['external_unique_id'] = !empty($data['action_id']) ? $this->ssa_composer([$data['action_id'], $this->main_params['transaction_id'], $this->main_params['package_id']]) : $this->ssa_composer(['NAID-', $this->main_params['transaction_id'], $this->main_params['package_id']]);
    }

    private function setExtraParams() {
        $this->extra_params['decrypt_response'] = !empty($this->ssa_request_params['decrypt_response']) ? $this->ssa_request_params['decrypt_response'] : false;
        $this->extra_params['package_id'] = !empty($this->ssa_request_params['packageId']) ? $this->ssa_request_params['packageId'] : null;
        $this->extra_params['date_sent'] = !empty($this->ssa_request_params['dateSent']) ? intval($this->ssa_request_params['dateSent']) : null;
        $this->extra_params['date_received'] = $this->ssa_get_timeticks($this->get_usec);
        
        if ($_SERVER['REQUEST_METHOD'] == $this->ssa_request_method_get) {
            $this->extra_params['balance_package_encrypted'] = isset($this->ssa_request_params['balancePackage']) ? str_replace(' ', '+', $this->ssa_request_params['balancePackage']) : null;
        } else {
            $this->extra_params['balance_package_encrypted'] = isset($this->ssa_request_params['balancePackage']) ? urldecode($this->ssa_request_params['balancePackage']) : null;
        }

        $this->extra_params['balance_package_decrypted'] = isset($this->ssa_request_params['balancePackage']) ? json_decode($this->game_api->aesDecrypt($this->extra_params['balance_package_encrypted']), true) : null;
        $this->extra_params['action_id'] = !empty($this->extra_params['balance_package_decrypted']['ActionId']) ? $this->extra_params['balance_package_decrypted']['ActionId'] : null;
        $this->extra_params['game_username'] = !empty($this->extra_params['balance_package_decrypted']['SourceName']) ? $this->extra_params['balance_package_decrypted']['SourceName'] : null;
        $this->extra_params['transaction_id'] = !empty($this->extra_params['balance_package_decrypted']['ReferenceNo']) ? $this->extra_params['balance_package_decrypted']['ReferenceNo'] : null;
        $this->extra_params['transaction_amount'] = !empty($this->extra_params['balance_package_decrypted']['TransactionAmount']) ? $this->extra_params['balance_package_decrypted']['TransactionAmount'] : 0;
        $this->extra_params['match_id'] = $this->extra_params['round_id'] = !empty($this->extra_params['balance_package_decrypted']['MatchID']) ? $this->extra_params['balance_package_decrypted']['MatchID'] : null;
        $this->extra_params['ticket_details'] = !empty($this->extra_params['balance_package_decrypted']['TicketDetails']) ? $this->extra_params['balance_package_decrypted']['TicketDetails'] : [];
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'extra_params', $this->extra_params);

    }

    private function initializePlayerDetails($get_balance = false, $get_player_by = 'token', $use_main_params = true, $data = []) {
        if ($use_main_params) {
            $this->setMainParams($data);
        }

        if ($get_player_by == $this->ssa_subject_type_token) {
            // get player details by token
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $this->main_params['token'], $this->game_platform_id);
            $this->utils->debug_log(__CLASS__, __METHOD__, 'ssa_subject_type_token', $this->ssa_subject_type_token, 'player_details', $this->player_details, 
            'main_params', $this->main_params);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::RESPONSE_USER_NOT_EXISTS;
                return false;
            } else {
                if (!empty($this->main_params['game_username']) && $this->main_params['game_username'] != $this->player_details['game_username']) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_USER_NOT_EXISTS;
                    $this->player_details = [];
                    return false;
                }
            }
        } elseif ($get_player_by == $this->ssa_subject_type_game_username) {
            // get player details by player game username
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $this->main_params['game_username'], $this->game_platform_id);

            $this->utils->debug_log(__CLASS__, __METHOD__, 'ssa_subject_type_game_username', $this->ssa_subject_type_game_username, 'player_details', $this->player_details, 
            'main_params', $this->main_params);
            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::RESPONSE_USER_NOT_EXISTS;

                $this->utils->debug_log(__CLASS__, __METHOD__, 'ssa_subject_type_game_username empty player_details', $this->player_details, 
                'main_params', $this->main_params);
                return false;
            } else {
                if (!empty($this->main_params['game_username']) && $this->main_params['game_username'] != $this->player_details['game_username']) {

                    $this->utils->debug_log(__CLASS__, __METHOD__, 'ssa_subject_type_game_username game username not equal', $this->main_params['game_username'], 
                    $this->player_details['game_username']);
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_USER_NOT_EXISTS;
                    $this->player_details = [];
                    return false;
                }
            }
        } else {            
            
            $this->utils->debug_log(__CLASS__, __METHOD__, 'ssa_subject_type_game_usernam none of the above', 'main_params', $this->main_params);
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_USER_NOT_EXISTS;
            $this->player_details = [];
            return false;
        }

        if ($get_balance) {
            $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
        }

        return true;
    }

    private function isPlayerBlocked() {
        if (isset($this->player_details['username']) && $this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_ACCESS_DENIED, 'Player is blocked');
            return true;
        }

        return false;
    }

    private function validatePlayer() {
        if ($this->isPlayerBlocked()) {
            return false;
        }

        return true;
    }

    private function isServerIpAllowed() {
        $ip = $this->ssa_get_ip_address();

        if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
            $this->ssa_http_response_status_code = 401;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_ACCESS_DENIED, "IP address ({$ip}) is not allowed");
            return false;
        }

        return true;
    }

    private function isGameApiActive() {
        if (!$this->ssa_is_game_api_active($this->game_api)) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_ACCESS_DENIED, 'Game is disabled');
            return false;
        }

        return true;
    }

    private function isGameApiMaintenance() {
        if ($this->ssa_is_game_api_maintenance($this->game_api)) {
            $this->ssa_http_response_status_code = 503;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_UNDER_MAINTENANCE, 'Game is under maintenance');
            return true;
        }

        return false;
    }

    private function validateGameApi($features = [
        'use_ssa_is_server_ip_allowed' => 0,
        'use_ssa_is_game_api_active' => 0,
        'use_ssa_is_game_api_maintenance' => 0,
    ]) {

        if ($features['use_ssa_is_server_ip_allowed']) {
            if (!$this->isServerIpAllowed()) {
                return false;
            }
        }

        if ($features['use_ssa_is_game_api_active']) {
            if (!$this->isGameApiActive()) {
                return false;
            }
        }

        if ($features['use_ssa_is_game_api_maintenance']) {
            if ($this->isGameApiMaintenance()) {
                return false;
            }
        }

        return true;
    }

    private function validateParams() {
        if (isset($this->ssa_request_params['language']) && $this->ssa_request_params['language'] != $this->language) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Invalid parameter language');
            return false;
        }

        if (isset($this->ssa_request_params['currency']) && $this->ssa_request_params['currency'] != $this->currency) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Invalid parameter currency');
            return false;
        }

        return true;
    }

    private function isTransactionExists($use_request_id = true) {
        if ($use_request_id) {
            if ($this->isRequestUniqueIdExists()) {
                return true;
            }
        }

        $this->transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table,
            "(external_unique_id='{$this->main_params['external_unique_id']}' AND wallet_adjustment_status IN ('decreased', 'increased', 'retained'))");

        if ($this->transaction_already_exists) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        return false;
    }

    private function isBetExists() {
        if ($this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::API_METHOD_DEDUCT_BALANCE,
            'transaction_id' => !empty($this->extra_params['transaction_id']) ? $this->extra_params['transaction_id'] : null,
            'wallet_adjustment_status' => $this->ssa_decreased,
        ])) {
            return true;
        } else {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_ILLEGAL_REQUEST, 'Bet not exist');
            return false;
        }
    }

    private function isRequestUniqueIdExists() {
        if ($this->ssa_is_transaction_exists($this->transaction_table,
            "(package_id='{$this->extra_params['package_id']}' AND wallet_adjustment_status IN ('decreased', 'increased', 'retained'))")) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Transaction already Exists');
            return true;
        }

        return false;
    }

    private function isPayoutExists() {
        return false;
    }

    private function isRefundExists() {
        return false;
    }

    private function validateTransactionRecords() {
        if ($this->isTransactionExists()) {
            return false;
        }

        if (!$this->isBetExists()) {
            return false;
        }

        return true;
    }

    private function walletAdjustment($adjustment_type, $data, $isEnd = false) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params, 'data:' , $data);
        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->main_params['game_code']);
        $this->ssa_set_game_provider_is_end_round($isEnd);
        $this->ssa_set_game_provider_round_id($this->main_params['round_id']);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);
        $currenctAction = $this->extra_params['action_id'];
        $remoteActionType = isset($this->actions_map[$currenctAction])?$this->actions_map[$currenctAction]:null;
        $this->ssa_set_game_provider_action_type($remoteActionType);
        $data['adjustment_type'] = $adjustment_type;
        $after_balance = null;

        if ($this->api_method == self::API_METHOD_UPDATE_BALANCE && $adjustment_type == $this->ssa_decrease) {
            // if negative balance not allowed, check if insufficient
            if (!$this->allowed_negative_balance) {
                if ($this->isInsufficientBalance($this->player_balance, $data['amount'])) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Insufficient balance');
                    return false;
                }
            }
        }

        if ($data['amount'] == 0) {
            $data['wallet_adjustment_status'] = $this->ssa_retained;
            $success = true;
        } else {
            if ($adjustment_type == $this->ssa_decrease) {
                $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $data['amount'], $after_balance, $this->allowed_negative_balance);

                if ($success) {
                    $data['after_balance'] = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                    $data['wallet_adjustment_status'] = $this->ssa_decreased;
                } else {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (decrease balance)');
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                }
            } elseif ($adjustment_type == $this->ssa_increase) {
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $data['amount'], $after_balance);

                if ($success) {
                    $data['after_balance'] = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                    $data['wallet_adjustment_status'] = $this->ssa_increased;
                } else {
                    $data['wallet_adjustment_status'] = $this->ssa_failed;
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (increase balance)');
                }
            } else {
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (walletAdjustment default)');
                return false;
            }
        }

        $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

        if (!$success) {
            if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                // treat success if remote wallet return double uniqueid
                if ($this->ssa_remote_wallet_error_double_unique_id()) {
                    $success = true;

                    if ($adjustment_type == $this->ssa_decrease) {
                        $data['wallet_adjustment_status'] = $this->ssa_decreased;
                        $data['before_balance'] += $data['amount'];
                    } else {
                        $data['wallet_adjustment_status'] = $this->ssa_increased;
                        $data['before_balance'] -= $data['amount'];
                    }
                }

                if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Invalid unique id');
                }

                if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Insufficient balance');
                }

                if ($this->ssa_remote_wallet_error_maintenance()) {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Remote wallet maintenance');
                }
            }
        }

        $data['external_unique_id'] = $this->main_params['external_unique_id'];
        $data['start_at'] = $data['end_at'] = $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format);

        if (!empty($this->remote_wallet_status) && !empty($data)) {
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $data);
        }

        array_push($this->saved_multiple_transactions, $data);
        return $success;
    }

    private function rebuildTransactionRequestData($query_type, $data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($query_type == $this->ssa_insert) {
            $request = $this->ssa_request_params;
            $request['balance_package_decrypted'] = isset($this->extra_params['balance_package_decrypted'])?$this->extra_params['balance_package_decrypted']:[];

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
                'amount' => $data['amount'],
                'before_balance' => $data['before_balance'],
                'after_balance' => $data['after_balance'],
                'status' => $this->setStatus(),
                'start_at' => isset($data['start_at']) ? $data['start_at'] : $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
                'end_at' => isset($data['end_at']) ? $data['end_at'] : $this->ssa_date_time_modifier($this->utils->getNowForMysql(), $this->game_provider_gmt, $this->game_provider_date_time_format),
                // addtional
                'action_id' => isset($this->extra_params['action_id']) ? $this->extra_params['action_id'] : null,
                'package_id' => isset($this->extra_params['package_id']) ? $this->extra_params['package_id'] : null,
                'date_sent' => isset($this->extra_params['date_sent']) ? $this->extra_params['date_sent'] : null,
                'date_received' => isset($this->extra_params['date_received']) ? $this->extra_params['date_received'] : null,
                // default
                'elapsed_time' => $this->utils->getCostMs(),
                'request' => json_encode($request),
                'response' => null,
                'extra_info' => null,
                'bet_amount' => isset($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : 0,
                'win_amount' => isset($this->extra_params['win_amount']) ? $this->extra_params['win_amount'] : 0,
                'result_amount' => isset($this->extra_params['result_amount']) ? $this->extra_params['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->extra_params['flag_of_updated_result']) ? $this->extra_params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
                'wallet_adjustment_status' => $data['wallet_adjustment_status'],
                'external_unique_id' => $this->main_params['external_unique_id'],
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
            ];

            $new_transaction_data['md5_sum'] = $this->ssa_generate_md5_sum($new_transaction_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        } else {
            $new_transaction_data = [
                'transaction_type' => $this->api_method,
                'amount' => $data['amount'],
                'before_balance' => $data['before_balance'],
                'after_balance' => $data['after_balance'],
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

    private function saveTransactionRequestData($query_type, $data) {
        $new_transaction_data = $this->rebuildTransactionRequestData($query_type, $data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $new_transaction_data, 'external_unique_id', $this->main_params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    private function setStatus() {
        switch ($this->api_method) {
            case self::API_METHOD_DEDUCT_BALANCE:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::API_METHOD_UPDATE_BALANCE:
                if (isset($this->extra_params['action_id'])) {
                    switch ($this->extra_params['action_id']) {
                        case self::ACTION_LIST['BTBuyBack']:
                        case self::ACTION_LIST['SettleHT']:
                        case self::ACTION_LIST['SettleFT']:
                        case self::ACTION_LIST['SettleParlay']:
                            $result = Game_logs::STATUS_SETTLED;
                            break;
                        case self::ACTION_LIST['ResettleTicket']:
                        case self::ACTION_LIST['UncancelHT']:
                        case self::ACTION_LIST['UncancelFT']:
                        case self::ACTION_LIST['SystemAdjustment']:
                            $result = Game_logs::STATUS_PENDING;

                            if ($this->extra_params['action_id'] == self::ACTION_LIST['ResettleTicket']) {
                                if (!empty($this->extra_params['transaction_amount']) && $this->extra_params['transaction_amount'] > 0) {
                                    $result = Game_logs::STATUS_REFUND;
                                }
                            }
                            break;
                        case self::ACTION_LIST['CancelHT']:
                        case self::ACTION_LIST['CancelFT']:
                        case self::ACTION_LIST['DangerRefund']:
                            $result = Game_logs::STATUS_REFUND;
                            break;
                        case self::ACTION_LIST['UnsettleHT']:
                        case self::ACTION_LIST['UnsettleFT']:
                        case self::ACTION_LIST['UnsettleParlay']:
                            $result = Game_logs::STATUS_UNSETTLED;
                            break;
                        default:
                            $result = Game_logs::STATUS_PENDING;
                            break;
                    }
                } else {
                    $result = Game_logs::STATUS_SETTLED;
                }
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    private function rebuildOperatorResponse($flag, $operator_response) {
        $is_normal = $flag == Response_result::FLAG_NORMAL;
        $code = isset($operator_response['code']) ? $operator_response['code'] : self::RESPONSE_SERVER_EXCEPTION['code'];
        $message = isset($operator_response['message']) ? $operator_response['message'] : self::RESPONSE_SERVER_EXCEPTION['message'];
        $balance = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);
        $game_username = isset($this->player_details['game_username']) ? $this->player_details['game_username'] : null;
        $package_id = isset($this->extra_params['package_id']) ? $this->extra_params['package_id'] : null;
        $date_received = isset($this->extra_params['date_received']) ? $this->extra_params['date_received'] : null;
        $date_sent = isset($this->extra_params['date_sent']) ? $this->extra_params['date_sent'] : null;

        if ($this->api_method == self::METHOD_ENCRYPT) {
            return $operator_response;
        }

        if ($this->api_method == self::METHOD_DECRYPT) {
            return is_array($operator_response) ? $operator_response : json_decode($operator_response, true);
        }

        if ($this->api_method == self::API_METHOD_AUTHENTICATION) {
            $operator_response = [
                'authenticate' => [
                    'member_id' => $game_username,
                    'status_code' => $is_normal ? 0 : 2,
                    'message' => $message,
                ],
            ];
        }

        if ($this->api_method == self::API_METHOD_GET_BALANCE || $this->api_method == self::API_METHOD_DEDUCT_BALANCE) {
            $operator_response = [
                'StatusCode' => $code,
                'StatusMessage' => $message,
                'PackageId' => $package_id,
            ];

            if ($is_normal) {
                $operator_response['Balance'] = $balance;
            }

            $operator_response['DateReceived'] = $date_received;
            $operator_response['DateSent'] = $date_sent;
        }

        if ($this->api_method == self::API_METHOD_UPDATE_BALANCE) {
            $operator_response = [
                'StatusCode' => $code,
                'StatusMessage' => $message,
                'PackageId' => $package_id,
                'DateReceived' => $date_received,
                'DateSent' => $date_sent,
            ];
        }

        $this->rebuilded_operator_response = $operator_response = $this->encrypt_response ? $this->game_api->aesEncrypt($operator_response) : $operator_response;

        return $operator_response;
    }

    private function finalizeTransactionData() {
        if ($this->is_transfer_type) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            if (!empty($this->saved_multiple_transactions) && is_array($this->saved_multiple_transactions)) {
                foreach ($this->saved_multiple_transactions as $data) {
                    $saved_transaction_id = isset($data['saved_transaction_id']) ? $data['saved_transaction_id'] : null;
                    $external_unique_id = isset($data['external_unique_id']) ? $data['external_unique_id'] : null;
                    $before_balance = isset($data['before_balance']) ? $data['before_balance'] : 0;
                    $after_balance = isset($data['after_balance']) ? $data['after_balance'] : 0;
                    $wallet_adjustment_status = isset($data['wallet_adjustment_status']) ? $data['wallet_adjustment_status'] : $this->ssa_preserved;
                    $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);
                    $decrypted_response = $this->decryptOperatorResponse($operator_response);

                    $extra_info = [
                        'decrypted_response' => $decrypted_response,
                    ];
    
                    if (!empty($external_unique_id)) {
                        $data = [
                            'before_balance' => $before_balance,
                            'after_balance' => $after_balance,
                            'wallet_adjustment_status' => $wallet_adjustment_status,
                            'elapsed_time' => $this->utils->getCostMs(),
                            'response' => $operator_response,
                            'response_result_id' => $this->response_result_id,
                            'extra_info' => json_encode($extra_info),
                        ];

                        $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'external_unique_id', $external_unique_id);

                        if ($this->api_method == self::API_METHOD_UPDATE_BALANCE && $this->update_bet_round_id) {
                            $data = [
                                'round_id' => isset($data['round_id']) ? $data['round_id'] : null,
                            ];
                    
                            $where = [
                                'player_id' => isset($data['player_id']) ? $data['player_id'] : null,
                                'transaction_type' => self::API_METHOD_DEDUCT_BALANCE,
                                'transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : null,
                            ];

                            $this->ssa_update_transaction_without_result_custom($this->transaction_table, $data, $where);
                        }
                    }
                }
            }
    
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        }
    }

    private function saveGameSeamlessServiceLogs() {
        if (!empty($this->game_seamless_service_logs_table) && $this->save_game_seamless_service_logs) {
            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::RESPONSE_SERVER_EXCEPTION['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::RESPONSE_SERVER_EXCEPTION['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;
            $operator_response = !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response);
            $decrypted_response = $this->decryptOperatorResponse($operator_response);

            $extra_info = [
                'decrypted_response' => $decrypted_response,
            ];

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
                'request' => json_encode($this->ssa_request_params),
                'response' => $operator_response,
                'extra_info' => json_encode($extra_info),
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => !empty($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
                'external_unique_id' => !empty($this->main_params['external_unique_id']) ? $this->main_params['external_unique_id'] : null,
                // addtional
                'action_id' => isset($this->extra_params['action_id']) ? $this->extra_params['action_id'] : null,
                'package_id' => isset($this->extra_params['package_id']) ? $this->extra_params['package_id'] : null,
                'date_sent' => isset($this->extra_params['date_sent']) ? $this->extra_params['date_sent'] : null,
                'date_received' => isset($this->extra_params['date_received']) ? $this->extra_params['date_received'] : null,
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

    private function response() {
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $operator_response = $this->rebuildOperatorResponse($flag, $this->ssa_operator_response);
        $decrypted_response = $this->decryptOperatorResponse($operator_response);

        $extra = [
            'decrypted_response' => $decrypted_response,
        ];

        $request_params = $this->ssa_request_params;
        $request_params['balance_package_decrypted'] = isset($this->extra_params['balance_package_decrypted'])?$this->extra_params['balance_package_decrypted']:[];

        if (isset($this->extra_params['decrypt_response']) && $this->extra_params['decrypt_response']) {
            $operator_response = $decrypted_response;
        }

        if ($this->save_data) {
            $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $request_params, $operator_response, $http_response, $player_id, $extra);

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
    private function isValidAmount($amount) {
        if ($amount < 0) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Invalid amount');
            return false;
        }

        return true;
    }

    private function isInsufficientBalance($before_balance, $amount) {
        if ($before_balance < $amount) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Insufficient balance');
            return true;
        }

        return false;
    }

    private function Authentication() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
        ])) {
            if($this->ssa_validate_request_params($this->ssa_request_params, [
                'token' => ['required'],
            ])) {
                if ($this->initializePlayerDetails(true, $this->ssa_subject_type_token)) {
                    if ($this->validatePlayer()) {
                        if ($this->validateParams()) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function GetBalance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');

        $this->setExtraParams();
        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'method' => ['required'],
                'balancePackage' => ['required'],
                'packageId' => ['required'],
                'dateSent' => ['required'],
            ])) {
                if ($this->initializePlayerDetails(false, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
                    if ($this->validatePlayer()) {
                        if ($this->validateParams()) {
                            $self = $this;
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($self) {
                                $result = $self->ssa_get_player_wallet_balance($self->player_details['player_id'], $self->game_platform_id, true);

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
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Error in getting balance');
                            }
                        }
                    }
                }else{
                    $this->utils->debug_log(__CLASS__, __METHOD__, 'failed initializePlayerDetails ', 'ssa_request_params', $this->ssa_request_params, 
                    'player_details', $this->player_details);
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function DeductBalance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'method' => ['required'],
                'balancePackage' => ['required'],
                'packageId' => ['required'],
                'dateSent' => ['required'],
            ])) {
                if ($this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
                    if ($this->validatePlayer()) {
                        if ($this->validateParams()) {
                            if (!$this->isTransactionExists()) {
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');
                                $this->extra_params['bet_amount'] = $this->main_params['amount'];
                                $this->extra_params['result_amount'] = -$this->main_params['amount'];

                                $adjustment_type = $this->ssa_decrease;
                                $query_type = $this->ssa_insert;
                                $before_balance = $after_balance = $this->player_balance;
                                $this->seamless_service_unique_id = $this->ssa_composer([$this->game_platform_id, $this->main_params['external_unique_id']]);
                                $this->seamless_service_related_unique_id = null;
                                $this->seamless_service_related_action = null;

                                if ($this->isValidAmount($this->main_params['amount'])) {
                                    if (!$this->isInsufficientBalance($before_balance, $this->main_params['amount'])) {
                                        $data = [
                                            // default
                                            'saved_transaction_id' => $this->saved_transaction_id,
                                            'amount' => $this->ssa_operate_amount($this->main_params['amount'], $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name),
                                            'before_balance' => $before_balance,
                                            'after_balance' => $after_balance,
                                            'wallet_adjustment_status' => $this->ssa_preserved,

                                            //additional
                                            'player_id' => isset($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                                            'transaction_id' => isset($this->main_params['transaction_id']) ? $this->main_params['transaction_id'] : null,
                                            'round_id' => isset($this->main_params['round_id']) ? $this->main_params['round_id'] : null,
                                        ];

                                        if ($this->ssa_is_transaction_exists($this->transaction_table,
                                        "(external_unique_id='{$this->main_params['external_unique_id']}' AND wallet_adjustment_status IN ('preserved', 'failed'))")) {
                                            $data_saved = true;
                                        } else {
                                            // save transaction data first
                                            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                                            $data_saved = $data['saved_transaction_id'] = $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $data);
                                            $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                                        }

                                        if ($data_saved) {
                                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($adjustment_type, $data) {
                                                return $this->walletAdjustment($adjustment_type, $data, false);
                                            });

                                            if ($success) {
                                                $this->ssa_http_response_status_code = 200;
                                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Deduct Balance Succeed');
                                            }
                                        } else {
                                            $this->ssa_http_response_status_code = 500;
                                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (saveTransactionRequestData)');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function UpdateBalance() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->validateGameApi([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
        ])) {
            if ($this->ssa_validate_request_params($this->ssa_request_params, [
                'method' => ['required'],
                'balancePackage' => ['required'],
                'packageId' => ['required'],
                'dateSent' => ['required'],
            ])) {
                if ($this->validateParams()) {
                    $this->setExtraParams();
                    //if (!$this->isRequestUniqueIdExists()) {
                        if ($this->validateTicketDetailsForUpdateBalance()) {
                            if (!empty($this->extra_params['ticket_details']) && is_array($this->extra_params['ticket_details'])) {
                                $success = false;
        
                                foreach ($this->extra_params['ticket_details'] as $ticket_detail) {
                                    $this->extra_params['game_username'] = isset($ticket_detail['SourceName']) ? $ticket_detail['SourceName'] : null;
                                    $this->extra_params['transaction_id'] = isset($ticket_detail['ReferenceNo']) ? $ticket_detail['ReferenceNo'] : null;
                                    $this->extra_params['transaction_amount'] = isset($ticket_detail['TransactionAmount']) ? $ticket_detail['TransactionAmount'] : null;
        
                                    if ($this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
                                        $this->ssa_http_response_status_code = 500;
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');
            
                                        $adjustment_type = $this->extra_params['transaction_amount'] < 0 ? $this->ssa_decrease : $this->ssa_increase;
                                        $query_type = $this->ssa_insert;
                                        $before_balance = $after_balance = $this->player_balance;
                                        $this->seamless_service_unique_id = $this->ssa_composer([$this->game_platform_id, $this->main_params['external_unique_id']]);
                                        
                                        # get update balance external unique id
                                        
                                        $action = (int)$this->extra_params['action_id'];
                                        $relationAction = isset($this->related_actions_map[$action])&&!empty($this->related_actions_map[$action])?$this->related_actions_map[$action]:null;
                                        
                                        $this->utils->error_log(__CLASS__, __METHOD__, $this->game_platform_id, ' empty relationAction', 'relationAction', $relationAction, 
                                        'request_params', $this->ssa_request_params,
                                        'action', $action,
                                        'relationAction', $relationAction,
                                        'related_actions_map', $this->related_actions_map);
                                        

                                        $relatedTransWhere = [
                                            #'transaction_type'=>self::API_METHOD_DEDUCT_BALANCE, 
                                            'action_id'=>$relationAction,
                                            'transaction_id'=>$this->extra_params['transaction_id'], 
                                            'game_username'=>$this->extra_params['game_username']];
                                        $relatedTrans = $this->ssa_get_transaction($this->transaction_table, $relatedTransWhere);
                                        $this->seamless_service_related_unique_id = $this->ssa_composer(['game', $this->game_platform_id, $relatedTrans['external_unique_id']]);
                                        $this->seamless_service_related_action = isset($this->actions_map[$relationAction])?$this->actions_map[$relationAction]:null;

                                        $isEnd = isset($this->is_end_map[$action])?$this->is_end_map[$action]:null;

                                        if ($this->isValidAmount($this->main_params['amount'])) {
                                            $data = [
                                                // default
                                                'saved_transaction_id' => $this->saved_transaction_id,
                                                'amount' => $this->ssa_operate_amount($this->main_params['amount'], $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name),
                                                'before_balance' => $before_balance,
                                                'after_balance' => $after_balance,
                                                'wallet_adjustment_status' => $this->ssa_preserved,

                                                //additional
                                                'player_id' => isset($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                                                'transaction_id' => isset($this->main_params['transaction_id']) ? $this->main_params['transaction_id'] : null,
                                                'round_id' => isset($this->main_params['round_id']) ? $this->main_params['round_id'] : null,
                                            ];

                                            /* $transaction = $this->ssa_get_transaction($this->transaction_table,
                                            "(external_unique_id='{$this->main_params['external_unique_id']}' AND wallet_adjustment_status IN ('preserved', 'failed'))");*/

                                            $transaction = $this->ssa_get_transaction($this->transaction_table,[
                                                'external_unique_id' => $this->main_params['external_unique_id'],
                                            ]);

                                            if (!empty($transaction)) {
                                                if (in_array($transaction['wallet_adjustment_status'], ['preserved', 'failed'])) {
                                                    $data_saved = true;
                                                } else {
                                                    continue;
                                                }
                                            } else {
                                                // save transaction data first
                                                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                                                $data_saved = $data['saved_transaction_id'] = $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $data);
                                                $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                                            }

                                            if ($data_saved) {
                                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($adjustment_type, $data, $isEnd) {
                                                    return $this->walletAdjustment($adjustment_type, $data, $isEnd);
                                                });

                                                if ($success) {
                                                    $this->ssa_http_response_status_code = 200;
                                                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SUCCESS, 'Update Balance Succeed');
                                                }
                                            } else {
                                                $this->ssa_http_response_status_code = 500;
                                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (saveTransactionRequestData)');
                                                $success = false;
                                            }
                                        } else {
                                            $success = false;
                                        }
                                    } else {
                                        $success = false;
                                    }
        
                                    if (!$success) {
                                        break;
                                    }
                                }
                            } else {
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Required TicketDetails parameter');
                            }
                        }
                    //}
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function validateActionForUpdateBalance() {
        $transaction_id = !empty($this->extra_params['transaction_id']) ? $this->extra_params['transaction_id'] : null;
        $action_id = isset($this->extra_params['action_id']) ? $this->extra_params['action_id'] : null;
        $round_id = !empty($this->extra_params['round_id']) ? $this->extra_params['round_id'] : null;
        $action_code = null;

        $where_update_balance = [
            'transaction_type' => self::API_METHOD_UPDATE_BALANCE,
            'transaction_id' => $transaction_id,
            'round_id' => $round_id,
        ];

        if (!is_null($action_id)) {
            switch ($action_id) {
                case self::ACTION_LIST['SettleHT']:
                case self::ACTION_LIST['SettleFT']:
                case self::ACTION_LIST['SettleParlay']:
                case self::ACTION_LIST['CancelHT']:
                case self::ACTION_LIST['CancelFT']:
                    return $this->isBetExists() ? true : false;
                case self::ACTION_LIST['UnsettleHT']:
                    $where_update_balance['action_id'] = self::ACTION_LIST['SettleHT'];
                    $action_code = 'SettleHT';
                    break;
                case self::ACTION_LIST['UnsettleFT']:
                    $where_update_balance['action_id'] = self::ACTION_LIST['SettleFT'];
                    $action_code = 'SettleFT';
                    break;
                case self::ACTION_LIST['UnsettleParlay']:
                    $where_update_balance['action_id'] = self::ACTION_LIST['SettleParlay'];
                    $action_code = 'SettleParlay';
                    break;
                case self::ACTION_LIST['UncancelHT']:
                    $where_update_balance['action_id'] = self::ACTION_LIST['CancelHT'];
                    $action_code = 'CancelHT';
                    break;
                case self::ACTION_LIST['UncancelFT']:
                    $where_update_balance['action_id'] = self::ACTION_LIST['CancelFT'];
                    $action_code = 'CancelFT';
                    break;
                default:
                    return true;
            }

            if (!$this->ssa_is_transaction_exists($this->transaction_table, $where_update_balance)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = !empty($action_code) ? $this->ssa_operator_response_custom_message(self::RESPONSE_ILLEGAL_REQUEST, "{$action_code} not exist") : self::RESPONSE_ILLEGAL_REQUEST;
                return false;
            }
        }

        return true;
    }

    private function validateTicketDetailsForUpdateBalance() {
        $success = false;
        $balance = 0;

        foreach ($this->extra_params['ticket_details'] as $key => $ticket_detail) {
            $this->extra_params['game_username'] = isset($ticket_detail['SourceName']) ? $ticket_detail['SourceName'] : null;
            $this->extra_params['transaction_id'] = isset($ticket_detail['ReferenceNo']) ? $ticket_detail['ReferenceNo'] : null;
            $this->extra_params['transaction_amount'] = isset($ticket_detail['TransactionAmount']) ? $ticket_detail['TransactionAmount'] : 0;

            if ($this->validateActionForUpdateBalance()) {
                if ($this->initializePlayerDetails(true, $this->ssa_subject_type_game_username, true, $this->extra_params)) {
                    $success = true;
                    $balance = $this->player_balance;

                    if (!$this->isTransactionExists(false)) {
                        if (!$this->allowed_negative_balance) {
                            if (isset($this->extra_params['transaction_amount'])) {
                                // is for decrease, check balance
                                if ($this->extra_params['transaction_amount'] < 0) {
                                    if ($this->main_params['amount'] > $balance) {
                                        $this->ssa_http_response_status_code = 400;
                                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, 'Insufficient balance');
                                        $success = false;
                                        break;
                                    } else {
                                        $balance -= abs($this->extra_params['transaction_amount']);
                                    }
                                } else {
                                    $balance += abs($this->extra_params['transaction_amount']);
                                }
                            }
                        }
                    } else {
                        if (count($this->extra_params['ticket_details']) > 1) {
                            unset($this->extra_params['ticket_details'][$key]);
                        } else {
                            $success = false;
                            break;
                        }
                    }
                } else {
                    $success = false;
                    break;
                }
            } else {
                $success = false;
                break;
            }
        }

        return $success;
    }

    private function Encrypt() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->game_api) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = urlencode($this->game_api->aesEncrypt($this->ssa_request_params));
        }

        return $this->response();
    }

    private function Decrypt() {
        $this->api_method = __FUNCTION__;
        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_SERVER_EXCEPTION, 'Internal Server Error (' . __FUNCTION__ . ')');

        if ($this->ssa_validate_request_params($this->ssa_request_params, [
            'balancePackage' => ['required'],
        ])) {
            $this->setExtraParams();
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = !empty($this->extra_params['balance_package_decrypted']) ? $this->extra_params['balance_package_decrypted'] : $this->game_api->aesDecrypt($this->ssa_request_params['balancePackage']);
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_FAILED, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function decryptOperatorResponse($operator_response) {
        return $this->encrypt_response && !is_array($operator_response) ? json_decode($this->game_api->aesDecrypt($operator_response), true) : $operator_response;
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $request_params = $this->ssa_request_params;
        $request_params['balance_package_decrypted'] = isset($this->extra_params['balance_package_decrypted']) ? $this->extra_params['balance_package_decrypted'] : [];

        $save_data = $md5_data = [
            'transaction_id' => isset($this->main_params['transaction_id']) ? $this->main_params['transaction_id'] : null,
            'round_id' => isset($this->main_params['round_id']) ? $this->main_params['round_id'] : null,
            'external_game_id' => isset($this->main_params['game_code']) ? $this->main_params['game_code'] : null,
            'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
            'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => $this->api_method,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($request_params),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => !empty($data['start_at']) ? $data['start_at'] : $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $this->main_params['external_unique_id'],
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