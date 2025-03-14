<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Booming_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    // default
    private $game_platform_id;
    private $game_api;
    private $api_method;
    private $language;
    private $currency;
    private $response_result_id;
    private $player_details;
    private $player_balance;
    private $transaction_table;
    private $game_seamless_service_logs_table;
    private $save_game_seamless_service_logs;
    private $saved_transaction_id;
    private $saved_multiple_transactions;
    private $conversion;
    private $precision;
    private $arithmetic_name;
    private $adjustment_precision;
    private $adjustment_conversion;
    private $adjustment_arithmetic_name;
    private $whitelist_ip_validate_api_methods;
    private $game_api_active_validate_api_methods;
    private $game_api_maintenance_validate_api_methods;
    private $transaction_already_exists;
    private $main_params;
    private $extra_params;
    private $is_transfer_type;
    private $game_provider_gmt;
    private $game_provider_date_time_format;
    private $token_required_api_methods;
    private $seamless_service_unique_id = null;
    private $external_game_id = null;
    protected $game_provider_action_type = null;
    private $rebuilded_operator_response;

    // additional
    private $allow_rollback_request_with_payout;

    const SEAMLESS_GAME_API = BOOMING_SEAMLESS_GAME_API;

    const RESPONSE_SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];

    const RESPONSE_LOW_BALANCE = [
        'code' => 'low_balance',
        'message' => 'You have insufficient balance to place a bet.',
    ];

    const RESPONSE_REALITY_CHECK = [
        'code' => 'reality_check',
        'message' => "Reality check messagein the operator's jurisdiction.",
    ];

    const RESPONSE_SELF_EXCLUDED = [
        'code' => 'self_excluded',
        'message' => 'Player has excluded self from further game-play.',
    ];

    const RESPONSE_LOSS_LIMIT = [
        'code' => 'loss_limit',
        'message' => 'Player has reached a loss limit.',
    ];

    const RESPONSE_WAGER_LIMIT = [
        'code' => 'wager_limit',
        'message' => 'Player has reached a wager limit.',
    ];

    const RESPONSE_CUSTOM = [
        'code' => 'custom',
        'message' => 'custom message',
    ];

    const API_METHOD_CALLBACK = 'callback';
    const API_METHOD_ROLLBACK_CALLBACK = 'rollback_callback';

    const ALLOWED_API_METHODS = [
        self::API_METHOD_CALLBACK,
        self::API_METHOD_ROLLBACK_CALLBACK,
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
        // optional
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
        'seamless_service_unique_id',
        'external_game_id',
        'external_unique_id',
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
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->player_details = $this->saved_multiple_transactions = $this->main_params = $this->extra_params = $this->token_required_api_methods = $this->rebuilded_operator_response = [];
        $this->game_api = $this->api_method = $this->saved_transaction_id = $this->response_result_id = $this->game_seamless_service_logs_table = $this->seamless_service_unique_id = $this->external_game_id = null;
        $this->transaction_already_exists = $this->is_transfer_type = $this->save_game_seamless_service_logs = false;
        $this->allow_rollback_request_with_payout = true;
        $this->api_method = 'default';
        $this->player_balance = 0;
        $this->conversion = 1;
        $this->precision = 2;

        // default
        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);
    }

    public function index($game_platform_id = null, $api_method, $game_code) {
        $this->external_game_id = $game_code;

        if ($this->initialize($game_platform_id, $api_method)) {
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    private function initialize($game_platform_id, $api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->setMainParams();

        if (empty($game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (initialize empty $game_platform_id)');
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
            // Additional
            $this->allow_rollback_request_with_payout = $this->game_api->allow_rollback_request_with_payout;
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (load_game_api)');
            return false;
        }

        if (!$this->validateRequestPlayer()) {
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Method ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    private function setMainParams() {
        $session_id = isset($this->ssa_request_params['session_id']) ? $this->ssa_request_params['session_id'] : null;
        $round = isset($this->ssa_request_params['round']) ? $this->ssa_request_params['round'] : null;
        $this->main_params['external_unique_id'] = $this->ssa_composer([$this->api_method, $session_id, $round]);
        $this->main_params['token'] = $session_id;
        $this->main_params['game_username'] = !empty($this->ssa_request_params['player_id']) ? strtolower($this->ssa_request_params['player_id']) : null;
        $this->main_params['transaction_id'] = !empty($this->ssa_request_params['session_id']) ? $this->ssa_request_params['session_id'] : null;
        $this->main_params['round_id'] = isset($this->ssa_request_params['round']) ? $this->ssa_request_params['round'] : null;
        $this->main_params['game_code'] = $this->external_game_id;
        $this->main_params['bet_amount'] = isset($this->ssa_request_params['debit']) ? $this->ssa_request_params['debit'] : 0;
        $this->main_params['win_amount'] = isset($this->ssa_request_params['credit']) ? $this->ssa_request_params['credit'] : 0;
        $this->main_params['result_amount'] = $this->main_params['win_amount'] - $this->main_params['bet_amount'];
        $this->main_params['amount'] = abs($this->main_params['result_amount']);
        $this->main_params['bet_external_unique_id'] = $this->ssa_composer([self::API_METHOD_CALLBACK, $session_id, $round]);

        $this->setExtraParams();
    }

    private function setExtraParams() {
        return null;
    }

    private function validateRequestPlayer() {
        if (empty($this->main_params['game_username'])) {
            if (!empty($this->main_params['token'])) {
                $player_id = $this->ssa_get_player_id_by_external_token($this->main_params['token'], $this->game_platform_id);
                $this->main_params['game_username'] = $this->game_api->getGameUsernameByPlayerId($player_id);
            }
        }

        $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $this->main_params['game_username'], $this->game_platform_id);

        if (empty($this->player_details)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid player_id');
            return false;
        }

        if ($this->player_details['game_username'] != $this->main_params['game_username']) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid player_id');
            return false;
        }

        $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        return true;
    }

    private function checkPoint($features = [
        'use_ssa_is_server_ip_allowed' => 1,
        'use_ssa_is_game_api_active' => 1,
        'use_ssa_is_game_api_maintenance' => 1,
        'ssa_is_player_blocked' => 1,
    ]) {

        if ($features['use_ssa_is_server_ip_allowed']) {
            if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                $this->ssa_http_response_status_code = 401;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'IP address is not allowed');
                return false;
            }
        }

        if ($features['use_ssa_is_game_api_active']) {
            if (!$this->ssa_is_game_api_active($this->game_api)) {
                $this->ssa_http_response_status_code = 503;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Game is disabled');
                return false;
            }
        }

        if ($features['use_ssa_is_game_api_maintenance']) {
            if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                $this->ssa_http_response_status_code = 503;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Game is under maintenance');
                return false;
            }
        }

        if ($features['ssa_is_player_blocked']) {
            if (isset($this->player_details['username']) && $this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
                $this->ssa_http_response_status_code = 401;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Player is blocked');
                return false;
            }
        }

        return true;
    }

    private function additionalParamsValidation() {
        if (isset($this->ssa_request_params['language']) && $this->ssa_request_params['language'] != $this->language) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid parameter language');
            return false;
        }

        if (isset($this->ssa_request_params['currency']) && $this->ssa_request_params['currency'] != $this->currency) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = self::RESPONSE_CUSTOM;
            return false;
        }

        return true;
    }

    private function validateTransactionRecords() {
        $this->transaction_already_exists = $this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->main_params['external_unique_id']]);
        $bet_transaction = $this->ssa_get_transaction($this->transaction_table,
        "(external_unique_id='{$this->main_params['bet_external_unique_id']}' AND wallet_adjustment_status NOT IN ('preserved', 'failed'))");

        $win_amount = isset($bet_transaction['win_amount']) ? $bet_transaction['win_amount'] : 0;

        if ($this->transaction_already_exists) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
            return false;
        }

        if ($this->api_method == self::API_METHOD_ROLLBACK_CALLBACK) {
            if (empty($bet_transaction)) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'bet not exist');
                return false;
            } else {
                if (!$this->allow_rollback_request_with_payout) {
                    if (!empty($win_amount)) {
                        $this->ssa_http_response_status_code = 200;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'already have payout');
                        return false;
                    }
                }
            }
        }

        if (isset($bet_transaction['game_username']) && $bet_transaction['game_username'] != $this->main_params['game_username']) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid player_id');
            return false;
        }

        if (isset($bet_transaction['transaction_id']) && $bet_transaction['transaction_id'] != $this->main_params['transaction_id']) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid session_id');
            return false;
        }

        if (isset($bet_transaction['round_id']) && $bet_transaction['round_id'] != $this->main_params['round_id']) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid round');
            return false;
        }

        if (isset($bet_transaction['game_code']) && $bet_transaction['game_code'] != $this->main_params['game_code']) {
            $this->ssa_http_response_status_code = 200;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'URI game_id');
            return false;
        }

        return true;
    }

    private function callback() {
        $this->api_method = __FUNCTION__;
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->is_transfer_type = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 1,
            'use_ssa_is_game_api_maintenance' => 1,
            'ssa_is_player_blocked' => 1,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'session_id' => ['required'],
                'round' => ['required', 'nullable'],
                'player_id' => ['required'],
                'type' => ['required'],
                'debit' => ['required', 'nullable', 'numeric'],
                'credit' => ['required', 'nullable', 'numeric'],
                'game_cycle' => ['optional'],
                'operator_launch_data' => ['optional'],
                'operator_game_cycle_data' => ['optional'],
                'operator_game_round_data' => ['optional'],
                'campaign' => ['optional'],
            ]);

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    if ($this->validateTransactionRecords()) {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (' . __FUNCTION__ . ')');

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                            if ($this->main_params['result_amount'] < 0 ) {
                                $action = $this->ssa_decrease;
                            } else if($this->main_params['result_amount'] >= 0) {
                                $action = $this->ssa_increase;
                            }
                            $transaction_id = $this->ssa_request_params['session_id'];
                            $root_round_id = $this->ssa_request_params['game_cycle']['root_round']['round'];

                            $root_transaction = $this->ssa_get_transaction($this->transaction_table, [
                                'transaction_id' => $transaction_id,
                                'round_id' => $root_round_id,
                            ]);

                             $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, [
                                '$root_transaction' => $root_transaction,
                                '$this->ssa_request_params' => $this->ssa_request_params,
                             ]);

                             $this->ssa_set_game_provider_bet_amount($this->ssa_request_params['debit']);
                             $this->ssa_set_game_provider_payout_amount($this->ssa_request_params['credit']);
                            if( $this->ssa_request_params['round'] != $this->ssa_request_params['game_cycle']['root_round']['round']){ #free spin when root round is not equal to current round
                                $this->game_provider_action_type = 'payout';
                                $this->ssa_set_related_action_of_seamless_service('bet-payout');
                                $this->ssa_set_related_uniqueid_of_seamless_service( 'game-'.$root_transaction['seamless_service_unique_id'] );
                            }else{
                                $this->game_provider_action_type = 'bet-payout';
                            }

                            $this->ssa_set_game_provider_is_end_round($this->ssa_request_params['game_cycle']['ends']);


                            return $this->walletAdjustment($action, $this->ssa_insert, $this->main_params['amount']);
                        });
    
                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function rollback_callback() {
        $this->api_method = __FUNCTION__;
        $this->game_provider_action_type = 'refund';
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (' . __FUNCTION__ . ')');
        $this->is_transfer_type = true;
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if ($this->checkPoint([
            'use_ssa_is_server_ip_allowed' => 1,
            'use_ssa_is_game_api_active' => 0,
            'use_ssa_is_game_api_maintenance' => 0,
            'ssa_is_player_blocked' => 0,
        ])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
                'session_id' => ['required'],
                'round' => ['required', 'nullable'],
                'player_id' => ['required'],
                'type' => ['required'],
                'debit' => ['required', 'nullable', 'numeric'],
                'credit' => ['required', 'nullable', 'numeric'],
                'game_cycle' => ['optional'],
                'operator_launch_data' => ['optional'],
                'operator_game_cycle_data' => ['optional'],
                'operator_game_round_data' => ['optional'],
                'campaign' => ['optional'],
            ]);

            if ($is_valid) {
                if ($this->additionalParamsValidation()) {
                    if ($this->validateTransactionRecords()) {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (' . __FUNCTION__ . ')');

                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() {
                            $action = $this->main_params['result_amount'] > 0 ? $this->ssa_decrease : $this->ssa_increase; // revert process
                            return $this->walletAdjustment($action, $this->ssa_insert, $this->main_params['amount']);
                        });
    
                        if ($success) {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::RESPONSE_SUCCESS;
                        }
                    }
                }
            } else {
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, $this->ssa_custom_message_response);
            }
        }

        return $this->response();
    }

    private function walletAdjustment($adjustment_type, $query_type, $amount) {
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $this->seamless_service_unique_id = $this->ssa_composer([$this->game_platform_id, $this->main_params['external_unique_id']]);
        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
        $this->ssa_set_external_game_id($this->main_params['game_code']);
        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);

        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance;

        $transaction_data = [
            'saved_transaction_id' => $this->saved_transaction_id,
            'amount' => $amount,
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
            'wallet_adjustment_status' => $this->ssa_preserved,
        ];

        if ($amount < 0) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Invalid amount');
            return false;
        }

        if ($query_type == $this->ssa_insert) {
            if ($adjustment_type == $this->ssa_decrease) {
                if ($amount > $before_balance) {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = self::RESPONSE_LOW_BALANCE; // Insufficient balance
                    return false;
                }
            }

            // save transaction data first
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'start saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $this->saved_transaction_id = $this->saveTransactionRequestData($query_type, $transaction_data);
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'end saveTransactionRequestData', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            $transaction_data['saved_transaction_id'] = $this->saved_transaction_id;

            if ($this->saved_transaction_id) {
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'data has been saved.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

                if ($adjustment_type == $this->ssa_decrease) {
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);
    
                    if ($success) {
                        $transaction_data['after_balance'] = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_decreased;
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = self::RESPONSE_LOW_BALANCE; // Insufficient balance
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;
                    }
                } elseif ($adjustment_type == $this->ssa_increase) {
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);
    
                    if ($success) {
                        $transaction_data['after_balance'] = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_increased;
                    } else {
                        $transaction_data['wallet_adjustment_status'] = $this->ssa_failed;
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (ssa_increase_player_wallet)');
                    }
                } else {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (walletAdjustment default)');
                    return false;
                }
                array_push($this->saved_multiple_transactions, $transaction_data);
                return $success;
            } else {
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'failed to save data.', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::RESPONSE_CUSTOM, 'Internal Server Error (saveTransactionRequestData)');
                return false;
            }
        }

        return false;
    }

    private function rebuildTransactionRequestData($query_type, $transaction_data) {
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

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
                // optional
                // default
                'elapsed_time' => $this->utils->getCostMs(),
                'request' => json_encode($this->ssa_request_params),
                'response' => null,
                'extra_info' => null,
                'bet_amount' => isset($this->extra_params['bet_amount']) ? $this->extra_params['bet_amount'] : $this->main_params['bet_amount'],
                'win_amount' => isset($this->extra_params['win_amount']) ? $this->extra_params['win_amount'] : $this->main_params['win_amount'],
                'result_amount' => isset($this->extra_params['result_amount']) ? $this->extra_params['result_amount'] : $this->main_params['result_amount'],
                'flag_of_updated_result' => isset($this->extra_params['flag_of_updated_result']) ? $this->extra_params['flag_of_updated_result'] : $this->ssa_flag_not_updated,
                'wallet_adjustment_status' => $transaction_data['wallet_adjustment_status'],
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => $this->external_game_id,
                'external_unique_id' => $this->main_params['external_unique_id'],
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

        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        return $new_transaction_data;
    }

    private function saveTransactionRequestData($query_type, $transaction_data) {
        $new_transaction_data = $this->rebuildTransactionRequestData($query_type, $transaction_data);
        $update_with_result = $query_type == $this->ssa_insert ? false : true;

        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'start ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        $saved_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $new_transaction_data, 'external_unique_id', $this->main_params['external_unique_id'], $update_with_result);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'end ssa_insert_update_transaction', 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $saved_transaction_id;
    }

    private function setStatus() {
        switch ($this->api_method) {
            case self::API_METHOD_CALLBACK:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_ROLLBACK_CALLBACK:
                $result = Game_logs::STATUS_REFUND;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    private function rebuildOperatorResponse($flag, $operator_response) {
        $code = isset($operator_response['code']) ? $operator_response['code'] : self::RESPONSE_CUSTOM['code'];
        $message = isset($operator_response['message']) ? $operator_response['message'] : self::RESPONSE_CUSTOM['message'];
        $balance = $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name);

        if ($flag == Response_result::FLAG_NORMAL) {
            if ($code === self::RESPONSE_SUCCESS['code']) {
                $operator_response = [
                    'balance' => $balance,
                    'buttons' => [
                        'title' => 'Exit Game',
                        'action' => 'exit',
                    ],
                ];
            } else {
                $operator_response = $this->rebuildErrorResponse($code, $message);
            }
        } else {
            $operator_response = $this->rebuildErrorResponse($code, $message);
        }

        $this->rebuilded_operator_response = $operator_response;

        return $operator_response;
    }

    private function rebuildErrorResponse($code, $message) {
        $operator_response = [
            'error' => $code,
            'message' => $message,
            'buttons' => [],
        ];

        if ($code == self::RESPONSE_LOW_BALANCE['code']) {
            $buttons = [
                [
                    'title' => 'OK',
                    'action' => 'close_dialog',
                ],
                [
                    'title' => 'Deposit Funds',
                    'action' => 'top_up',
                ],
            ];
            
            $operator_response['buttons'] = array_merge($operator_response['buttons'], $buttons);
        }

        $buttons = [
            [
                'title' => 'Exit Game',
                'action' => 'exit',
            ]
        ];

        $operator_response['buttons'] = array_merge($operator_response['buttons'], $buttons);

        return $operator_response;
    }

    private function finalizeTransactionData() {
        if ($this->is_transfer_type) {
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

            if (!empty($this->saved_multiple_transactions) && is_array($this->saved_multiple_transactions)) {
                foreach ($this->saved_multiple_transactions as $transaction_data) {
                    $saved_transaction_id = isset($transaction_data['saved_transaction_id']) ? $transaction_data['saved_transaction_id'] : null;
                    $after_balance = isset($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
                    $wallet_adjustment_status = isset($transaction_data['wallet_adjustment_status']) ? $transaction_data['wallet_adjustment_status'] : $this->ssa_preserved;
    
                    $updated_data = [
                        'after_balance' => $after_balance,
                        'wallet_adjustment_status' => $wallet_adjustment_status,
                        'response' => !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response),
                        'response_result_id' => $this->response_result_id,
                    ];
    
                    if (!empty($saved_transaction_id)) {
                        $this->ssa_update_transaction_without_result($this->transaction_table, $updated_data, 'id', $saved_transaction_id);
                    }
                }
            }
    
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'done', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
        }
    }

    private function saveGameSeamlessServiceLogs() {
        if (!empty($this->game_seamless_service_logs_table) && $this->save_game_seamless_service_logs) {
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'enter method', __FUNCTION__, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);
            $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
            $code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : self::RESPONSE_CUSTOM['code'];
            $message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : self::RESPONSE_CUSTOM['message'];
            $flag = $this->ssa_http_response_status_code == 200 ? $this->ssa_success : $this->ssa_error;

            $data = [
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
                'response' => !empty($this->rebuilded_operator_response) ? json_encode($this->rebuilded_operator_response) : json_encode($this->ssa_operator_response),
                'extra_info' => null,
                'seamless_service_unique_id' => $this->seamless_service_unique_id,
                'external_game_id' => $this->external_game_id,
                'external_unique_id' => !empty($this->main_params['external_unique_id']) ? $this->main_params['external_unique_id'] : null,
            ];

            $data['md5_sum'] = md5(json_encode($data));
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
        $this->ssa_http_response_status_code = 200; // success and error must return http status code 200
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $operator_response = $this->rebuildOperatorResponse($flag, $this->ssa_operator_response);

        $extra = [
            'game_code' => $this->main_params['game_code'],
        ];

        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id, $extra);

        $this->finalizeTransactionData();
        $this->saveGameSeamlessServiceLogs();
        return $this->returnJsonResult($operator_response, true, '*', false, false, $this->ssa_http_response_status_code, '', 'application/json+vnd.api');
    }
}