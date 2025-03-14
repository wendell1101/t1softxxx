<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Tada_seamless_service_api extends BaseController {
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
    private $external_unique_id;
    private $processed_transaction_id;
    private $processed_multiple_transaction;
    private $conversion;
    private $precision;
    private $arithmetic_name;
    private $adjustment_precision;
    private $adjustment_conversion;
    private $adjustment_arithmetic_name;
    private $whitelist_ip_validate_api_methods;
    private $game_api_active_validate_api_methods;
    private $game_api_maintenance_validate_api_methods;
    private $game_api_player_blocked_validate_api_methods;
    private $extra_transaction;
    private $transaction_already_exists;
    private $is_end_round = false;
    private $seamless_service_unique_id = null;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];
    protected $seamless_service_related_unique_id = null;
    protected $seamless_service_related_action = null;

    // monthly transaction table
    protected $use_monthly_transactions_table = false;
    protected $force_check_previous_transactions_table = false;
    protected $force_check_other_transactions_table = false;
    protected $previous_table = null;
    protected $check_previous_transaction_table = false;

    // additional
    private $token_required_api_methods;
    private $additional_validation_session_bet;
    private $transaction_already_accepted;
    private $transaction_already_settled;
    private $transaction_already_cancelled;

    const SEAMLESS_GAME_API = TADA_SEAMLESS_GAME_API;

    const API_SUCCESS = [
        'code' => 0,
        'message' => 'Success',
    ];

    const API_ERROR_ALREADY_ACCEPTED = [
        'code' => 1,
        'message' => 'Already accepted',
    ];

    const API_ERROR_ALREADY_CANCELED = [
        'code' => 1,
        'message' => 'Already canceled',
    ];

    const API_ERROR_NOT_ENOUGH_BALANCE = [
        'code' => 2,
        'message' => 'Not enough balance',
    ];

    const API_ERROR_ROUND_NOT_FOUND = [
        'code' => 2,
        'message' => 'Round not found',
    ];

    const API_ERROR_INVALID_PARAMETER = [
        'code' => 3,
        'message' => 'Invalid parameter',
    ];

    const API_ERROR_TOKEN_EXPIRED = [
        'code' => 4,
        'message' => 'Token expired',
    ];

    const API_ERROR_OTHER_ERROR = [
        'code' => 5,
        'message' => 'Other error',
    ];

    const API_ERROR_CANCEL_REFUSED = [
        'code' => 6,
        'message' => 'Cancel refused',
    ];

    const API_METHOD_AUTH = 'auth';
    const API_METHOD_BET = 'bet';
    const API_METHOD_CANCEL_BET = 'cancelBet';
    const API_METHOD_SESSION_BET = 'sessionBet';
    const API_METHOD_CANCEL_SESSION_BET = 'cancelSessionBet';

    const ALLOWED_API_METHODS = [
        self::API_METHOD_AUTH,
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL_BET,
        self::API_METHOD_SESSION_BET,
        self::API_METHOD_CANCEL_SESSION_BET,
    ];

    const WHITELIST_IP_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTH,
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL_BET,
        self::API_METHOD_SESSION_BET,
        self::API_METHOD_CANCEL_SESSION_BET,
    ];

    const GAME_API_ACTIVE_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTH,
        self::API_METHOD_BET,
    ];

    const GAME_API_MAINTENANCE_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTH,
        self::API_METHOD_BET,
    ];

    const GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTH,
        self::API_METHOD_BET,
    ];

    const ADDITIONAL_VALIDATION_API_METHODS = [
        self::API_METHOD_BET,
        self::API_METHOD_CANCEL_BET,
        self::API_METHOD_SESSION_BET,
        self::API_METHOD_CANCEL_SESSION_BET,
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
        'req_id',
        'is_free_round',
        'offline_payment_transaction_id',
        'platform',
        'statement_type',
        'game_category',
        'session_id',
        'type',
        'turnover',
        'preserve',
        'session_total_bet',
        // default
        'elapsed_time',
        'extra_info',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'external_unique_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        // default
        'before_balance',
        'after_balance',
        'amount',
        // optional
        'turnover',
        'preserve',
        'session_total_bet',
        // default
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

    const BET_CODE = 1;
    const SETTLE_CODE = 2;
    const REFUND_CODE = 3;
    const BET_SETTLE_CODE = 4;

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = [];
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->player_details = $this->extra_transaction = $this->processed_multiple_transaction = [];
        $this->game_api = $this->api_method = $this->external_unique_id = $this->processed_transaction_id = $this->response_result_id = null;
        $this->api_method = 'default';
        $this->player_balance = null;
        $this->conversion = 1;
        $this->precision = 2;
        $this->transaction_already_exists = $this->transaction_already_accepted = $this->transaction_already_settled = $this->transaction_already_cancelled = $this->additional_validation_session_bet = false;

        $this->token_required_api_methods = [
            self::API_METHOD_AUTH,
            self::API_METHOD_BET,
            self::API_METHOD_SESSION_BET,
        ];
    }

    public function index($game_platform_id, $api_method) {
        if ($this->initialize($game_platform_id, $api_method)) {
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    private function initialize($game_platform_id, $api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if (empty($game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (initialize empty $game_platform_id)');
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($game_platform_id);

        if ($this->game_api) {
            // default
            $this->game_platform_id = $this->game_api->getPlatformCode();
            $this->transaction_table = $this->game_api->getSeamlessTransactionTable();
            $this->language = $this->game_api->language;
            $this->currency = $this->game_api->currency;
            $this->conversion = $this->game_api->conversion;
            $this->precision = $this->game_api->precision;
            $this->arithmetic_name = $this->game_api->arithmetic_name;
            $this->adjustment_precision = $this->game_api->adjustment_precision;
            $this->adjustment_conversion = $this->game_api->adjustment_conversion;
            $this->adjustment_arithmetic_name = $this->game_api->adjustment_arithmetic_name;
            $this->whitelist_ip_validate_api_methods = !empty($this->game_api->whitelist_ip_validate_api_methods) ? $this->game_api->whitelist_ip_validate_api_methods : self::WHITELIST_IP_VALIDATE_API_METHODS;
            $this->game_api_active_validate_api_methods = !empty($this->game_api->game_api_active_validate_api_methods) ? $this->game_api->game_api_active_validate_api_methods : self::GAME_API_ACTIVE_VALIDATE_API_METHODS;
            $this->game_api_maintenance_validate_api_methods = !empty($this->game_api->game_api_maintenance_validate_api_methods) ? $this->game_api->game_api_maintenance_validate_api_methods : self::GAME_API_MAINTENANCE_VALIDATE_API_METHODS;
            $this->game_api_player_blocked_validate_api_methods = !empty($this->game_api->game_api_player_blocked_validate_api_methods) ? $this->game_api->game_api_player_blocked_validate_api_methods : self::GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS;
            $this->use_remote_wallet_failed_transaction_monthly_table = $this->game_api->use_remote_wallet_failed_transaction_monthly_table;

            // monthly transaction table
            $this->use_monthly_transactions_table = $this->game_api->use_monthly_transactions_table;
            $this->force_check_previous_transactions_table = $this->game_api->force_check_previous_transactions_table;
            $this->previous_table = $this->game_api->ymt_get_previous_year_month_table();
            $this->check_previous_transaction_table = $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table);

            if (in_array($api_method, $this->whitelist_ip_validate_api_methods)) {
                if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                    $this->ssa_http_response_status_code = 401;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'IP address is not allowed (' . $this->ssa_get_ip_address() . ')');
                    return false;
                }
            }

            $this->additional_validation_session_bet = isset($this->ssa_request_params['type']) && $this->ssa_request_params['type'] == self::BET_CODE && $api_method == self::API_METHOD_SESSION_BET;

            if (in_array($api_method, $this->game_api_active_validate_api_methods) || $this->additional_validation_session_bet) {
                if (!$this->ssa_is_game_api_active($this->game_api)) {
                    $this->ssa_http_response_status_code = 503;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Game is disabled');
                    return false;
                }
            }

            if (in_array($api_method, $this->game_api_maintenance_validate_api_methods) || $this->additional_validation_session_bet) {
                if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                    $this->ssa_http_response_status_code = 503;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Game is under maintenance');
                    return false;
                }
            }
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (load_game_api)');
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Method ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    private function additionalValidation() {
        if (in_array($this->api_method, self::ADDITIONAL_VALIDATION_API_METHODS)) {
            if (isset($this->ssa_request_params['currency']) && $this->ssa_request_params['currency'] != $this->currency) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter currency');
                return false;
            }
        }

        if (!$this->validateRequestPlayer()) {
            return false;
        }

        return true;
    }

    private function validateRequestPlayer() {
        $token = isset($this->ssa_request_params['token']) ? $this->ssa_request_params['token'] : null;
        $game_username = isset($this->ssa_request_params['userId']) ? $this->ssa_request_params['userId'] : null;
        $is_free_round = isset($this->ssa_request_params['isFreeRound']) ? $this->ssa_request_params['isFreeRound'] : false;

        if ($is_free_round && in_array($this->api_method, $this->token_required_api_methods)) {
            unset($this->token_required_api_methods[array_search($this->api_method, $this->token_required_api_methods)]);
        }

        if (isset($this->ssa_request_params['type']) && $this->ssa_request_params['type'] == self::SETTLE_CODE && in_array($this->api_method, $this->token_required_api_methods)) {
            unset($this->token_required_api_methods[array_search($this->api_method, $this->token_required_api_methods)]);
        }

        if (in_array($this->api_method, $this->token_required_api_methods)) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $token, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_TOKEN_EXPIRED;
                return false;
            }
    
            if (!empty($game_username) && $game_username != $this->player_details['game_username']) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Invalid parameter userId');
                return false;
            }
        } else {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Invalid parameter userId');
                return false;
            }
    
            if ($this->player_details['game_username'] != $game_username) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Invalid parameter userId');
                return false;
            }
        }

        if (in_array($this->api_method, $this->game_api_player_blocked_validate_api_methods) || $this->additional_validation_session_bet) {
            if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
                $this->ssa_http_response_status_code = 401;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Player is blocked');
                return false;
            }
        }

        // $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        return true;
    }

    private function auth() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'reqId' => ['required'],
            'token' => ['required'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                    $result = $controller->ssa_get_player_wallet_balance($controller->player_details['player_id'], $controller->game_platform_id, true);

                    if (!$result['success']) {
                        return false;
                    }else{
                        if(isset($result['balance'])){
                            $controller->player_balance = $result['balance'];
                        }
                    }

                    return true;
                });

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = self::API_SUCCESS;
                } else {
                    $this->ssa_http_response_status_code = 500;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Error in auth getting balance');
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function bet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'reqId' => ['required'],
            'token' => ['required'],
            'currency' => ['required'],
            'game' => ['required'], // game_code
            'round' => ['required'], // transaction_id/external_unique_id/round_id
            'wagersTime' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'], // bet amount
            'winloseAmount' => ['required', 'nullable', 'numeric'], // win/lose amount
            'isFreeRound' => ['optional'],
            'userId' => ['optional'], // game_username
            'transactionId' => ['optional'], // free round transaction id
            'platform' => ['optional'],
            'statementType' => ['optional'],
            'gameCategory' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (bet settle default)');
                $this->ssa_request_params['round'] = strval($this->ssa_request_params['round']);
                $this->external_unique_id = self::BET_SETTLE_CODE . $this->ssa_request_params['round'];
                $this->extra_transaction['bet_amount'] = isset($this->ssa_request_params['betAmount']) ? $this->ssa_request_params['betAmount'] : 0;
                $this->extra_transaction['winlose_amount'] = isset($this->ssa_request_params['winloseAmount']) ? $this->ssa_request_params['winloseAmount'] : 0;
                $this->extra_transaction['result_amount'] = $this->extra_transaction['winlose_amount'] - $this->extra_transaction['bet_amount'];
                $this->extra_transaction['amount'] = abs($this->extra_transaction['result_amount']);
                $this->extra_transaction['flag_of_updated_result'] = $this->ssa_flag_not_updated;

                $this->transaction_already_exists = $this->isTransactionExists("(external_unique_id='{$this->external_unique_id}' OR req_id='{$this->ssa_request_params['reqId']}')");

                if (!$this->transaction_already_exists) {
                    $controller = $this;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                        $remote_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;

                        if ($this->extra_transaction['result_amount'] < 0) {
                            $action = $this->ssa_decrease;
                        } else {
                            $action = $this->ssa_increase;
                        }

                        $this->is_end_round = true;
                        return $controller->walletAdjustment($action, $this->ssa_insert, $this->extra_transaction['amount'], $remote_action_type);
                    });
                } else {
                    $success = $this->transaction_already_accepted = true;
                    $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->transaction_already_accepted ? self::API_ERROR_ALREADY_ACCEPTED : self::API_SUCCESS;
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function cancelBet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'reqId' => ['required'],
            'currency' => ['required'],
            'game' => ['required'], // game_code
            'round' => ['required'], // transaction_id/external_unique_id/round_id
            'betAmount' => ['required', 'nullable', 'numeric'], // bet amount
            'winloseAmount' => ['required', 'nullable', 'numeric'], // win/lose amount
            'userId' => ['optional'], // game_username
            'token' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (cancelBet default)');
                $this->ssa_request_params['round'] = strval($this->ssa_request_params['round']);
                $this->external_unique_id = self::REFUND_CODE . $this->ssa_request_params['round'];

                $this->transaction_already_exists = $this->isTransactionExists("(external_unique_id='{$this->external_unique_id}' OR req_id='{$this->ssa_request_params['reqId']}')");

                if (!$this->transaction_already_exists) {
                    $bet_transaction = $this->getTransaction([
                        'transaction_type' => self::API_METHOD_BET,
                        'round_id' => $this->ssa_request_params['round'],
                    ]);

                    if (!empty($bet_transaction)) {
                        $this->seamless_service_related_unique_id = !empty($bet_transaction['seamless_service_unique_id']) ? $bet_transaction['seamless_service_unique_id'] : null;
                        $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT;

                        if (empty($bet_transaction['win_amount'])) {
                            if (isset($bet_transaction['game_code']) && $bet_transaction['game_code'] == $this->ssa_request_params['game']) {
                                $controller = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                                    $remote_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->ssa_request_params['betAmount'], $remote_action_type);
                                });
                            } else {
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter game');
                            }
                        } else {
                            $success = $this->transaction_already_settled = true;
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = self::API_ERROR_ROUND_NOT_FOUND;
                    }
                } else {
                    $success = $this->transaction_already_accepted = true;
                    $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    if ($this->transaction_already_accepted) {
                        $this->ssa_operator_response = self::API_ERROR_ALREADY_CANCELED;
                    } elseif ($this->transaction_already_settled) {
                        $this->ssa_operator_response = self::API_ERROR_CANCEL_REFUSED;
                    } else {
                        $this->ssa_operator_response = self::API_SUCCESS;
                    }
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function sessionBet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'reqId' => ['required'],
            'token' => ['required'],
            'currency' => ['required'],
            'game' => ['required'], // game_code
            'round' => ['required'], // transaction_id/external_unique_id
            'wagersTime' => ['required'],
            'betAmount' => ['required', 'nullable', 'numeric'], // bet amount
            'winloseAmount' => ['required', 'nullable', 'numeric'], // win/lose amount
            'sessionId' => ['required'], // round_id
            'type' => ['required'],
            'userId' => ['optional'], // game_username
            'turnover' => ['required', 'nullable', 'numeric'],
            'preserve' => ['optional'],
            'platform' => ['optional'],
            'sessionTotalBet' => ['optional'],
            'statementType' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (bet default)');
                $this->ssa_request_params['round'] = strval($this->ssa_request_params['round']);
                $this->ssa_request_params['sessionId'] = strval($this->ssa_request_params['sessionId']);

                if (isset($this->ssa_request_params['type']) && $this->ssa_request_params['type'] == self::BET_CODE) { // 1 = bet, 2 = settle

                    $this->external_unique_id = self::BET_CODE . $this->ssa_request_params['round'];
                    $this->extra_transaction['action'] = $this->ssa_decrease;
                    #$this->extra_transaction['amount'] = $this->ssa_request_params['betAmount'];
                    $this->extra_transaction['amount'] = $this->calculateSessionBetAmount($this->ssa_request_params);
                    $remote_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;

                    $bet_transaction = [
                        'game_username' => $this->ssa_request_params['userId'],
                        'game_code' => $this->ssa_request_params['game'],
                    ];
                } else {
                    $this->is_end_round = true;
                    $this->external_unique_id = self::SETTLE_CODE . $this->ssa_request_params['round'];
                    $this->extra_transaction['action'] = $this->ssa_increase;
                    #$this->extra_transaction['amount'] = $this->ssa_request_params['winloseAmount'];
                    $this->extra_transaction['amount'] = $this->calculateSessionBetAmount($this->ssa_request_params);
                    
                    $remote_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;

                    $bet_transaction = $this->getTransaction([
                        'transaction_type' => self::API_METHOD_SESSION_BET,
                        'round_id' => $this->ssa_request_params['sessionId'],
                        'type' => self::BET_CODE,
                    ]);
                }

                $this->transaction_already_exists = $this->isTransactionExists("(external_unique_id='{$this->external_unique_id}' OR req_id='{$this->ssa_request_params['reqId']}')");
                
                if (!$this->transaction_already_exists) {
                    $is_round_already_exists = $this->isTransactionExists(['transaction_id' => $this->ssa_request_params['round']]);

                    if (!$is_round_already_exists) {
                        if (!empty($bet_transaction)) {
                            if ($this->ssa_request_params['type'] == self::SETTLE_CODE) {
                                $this->seamless_service_related_unique_id = !empty($bet_transaction['seamless_service_unique_id']) ? $bet_transaction['seamless_service_unique_id'] : null;
                                $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                            }

                            if (isset($bet_transaction['game_username']) && $bet_transaction['game_username'] == $this->ssa_request_params['userId']) {
                                if (isset($bet_transaction['game_code']) && $bet_transaction['game_code'] == $this->ssa_request_params['game']) {
                                    $controller = $this;
                                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $remote_action_type) {
                                        return $controller->walletAdjustment($this->extra_transaction['action'], $this->ssa_insert, $this->extra_transaction['amount'], $remote_action_type);
                                    });
                                } else {
                                    $this->ssa_http_response_status_code = 400;
                                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter game');
                                }
                            } else {
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter userId');
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = self::API_ERROR_ROUND_NOT_FOUND;
                        }
                        
                    } else {
                        $success = $this->transaction_already_accepted = true;
                    }
                } else {
                    $success = $this->transaction_already_accepted = true;
                    $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = $this->transaction_already_accepted ? self::API_ERROR_ALREADY_ACCEPTED : self::API_SUCCESS;
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function cancelSessionBet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'reqId' => ['required'],
            'currency' => ['required'],
            'game' => ['required'], // game_code
            'round' => ['required'], // transaction_id/external_unique_id
            'betAmount' => ['required', 'nullable', 'numeric'], // bet amount
            'winloseAmount' => ['required', 'nullable', 'numeric'], // win/lose amount
            'userId' => ['optional'], // game_username
            'token' => ['optional'],
            'sessionId' => ['required'], // round_id
            'type' => ['required'],
            'preserve' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (cancelBet default)');
                $this->ssa_request_params['round'] = strval($this->ssa_request_params['round']);
                $this->ssa_request_params['sessionId'] = strval($this->ssa_request_params['sessionId']);
                $this->external_unique_id = self::REFUND_CODE . $this->ssa_request_params['round'];

                $this->transaction_already_exists = $this->isTransactionExists("(external_unique_id='{$this->external_unique_id}' OR req_id='{$this->ssa_request_params['reqId']}')");

                if (!$this->transaction_already_exists) {
                    if (isset($this->ssa_request_params['type']) && $this->ssa_request_params['type'] == self::BET_CODE) {
                        $session_bet_transaction = $this->getTransaction([
                            'transaction_type' => self::API_METHOD_SESSION_BET,
                            'transaction_id' => $this->ssa_request_params['round'],
                            'round_id' => $this->ssa_request_params['sessionId'],
                            'type' => self::BET_CODE,
                        ]);

                        if (!empty($session_bet_transaction)) {
                            $this->seamless_service_related_unique_id = !empty($session_bet_transaction['seamless_service_unique_id']) ? $session_bet_transaction['seamless_service_unique_id'] : null;
                            $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;

                            if (isset($session_bet_transaction['game_username']) && $session_bet_transaction['game_username'] == $this->ssa_request_params['userId']) {
                                if (isset($session_bet_transaction['game_code']) && $session_bet_transaction['game_code'] == $this->ssa_request_params['game']) {


                                    $this->extra_transaction['amount'] = $this->calculateCancelSessionBetAmount($this->ssa_request_params);

                                    $session_settle_transaction = $this->getTransaction([
                                        'transaction_type' => self::API_METHOD_SESSION_BET,
                                        'transaction_id' => $this->ssa_request_params['round'],
                                        'round_id' => $this->ssa_request_params['sessionId'],
                                        'type' => self::SETTLE_CODE,
                                    ]);

                                    if (empty($session_settle_transaction['amount'])) {
                                        $controller = $this;
                                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                                            $remote_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
                                            return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->extra_transaction['amount'], $remote_action_type);
                                        });
                                    } else {
                                        $success = $this->transaction_already_settled = true;
                                    }
                                } else {
                                    $this->ssa_http_response_status_code = 400;
                                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter game');
                                }
                            } else {
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter userId');
                            }
                        } else {
                            $this->ssa_http_response_status_code = 200;
                            $this->ssa_operator_response = self::API_ERROR_ROUND_NOT_FOUND;
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, 'Invalid parameter type');
                    }
                } else {
                    $success = $this->transaction_already_accepted = true;
                    $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    if ($this->transaction_already_accepted) {
                        $this->ssa_operator_response = self::API_ERROR_ALREADY_CANCELED;
                    } elseif ($this->transaction_already_settled) {
                        $this->ssa_operator_response = self::API_ERROR_CANCEL_REFUSED;
                    } else {
                        $this->ssa_operator_response = self::API_SUCCESS;
                    }
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_INVALID_PARAMETER, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function walletAdjustment($adjustment_type, $query_type, $amount, $remote_action_type = WALLET_MODEL::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT) {
        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        $this->ssa_set_game_provider_action_type($remote_action_type);
        
        if ($remote_action_type == Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT) {
            $bet_amount = isset($this->ssa_request_params['betAmount']) ? $this->ssa_request_params['betAmount'] : 0;
            $winlose_amount= isset($this->ssa_request_params['winloseAmount']) ? $this->ssa_request_params['winloseAmount'] : 0;

            $this->ssa_set_game_provider_bet_amount($bet_amount);
            $this->ssa_set_game_provider_payout_amount($winlose_amount);
        }

        $game_unique_id = isset($this->ssa_request_params['game']) ? $this->ssa_request_params['game'] : null;
        $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->external_unique_id]);
        $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id, $game_unique_id);

        $round_id = isset($this->ssa_request_params['round'])?$this->ssa_request_params['round']:null;
        if(isset($this->ssa_request_params['sessionId']) && !empty($this->ssa_request_params['sessionId'])){
            $round_id = $this->ssa_request_params['sessionId'];
        }
        $this->ssa_set_game_provider_round_id($round_id);
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        $after_balance = null;
        $save_transaction = false;
        $is_processed = false;

        if ($adjustment_type == $this->ssa_decrease) {
            if ($amount > $before_balance) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_NOT_ENOUGH_BALANCE; // Insufficient balance
                $success = false;
            } else {
                if ($amount == 0) {
                    $success = true;
                } else {
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if (!$success) {
                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {

                            // treat success if remote wallet return double uniqueid
                            if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                $success = true;
                                $before_balance += $amount;
                            }

                            if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                $save_transaction = true;
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (invalid remote wallet unique id)');
                            }

                            if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                $save_transaction = true;
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = self::API_ERROR_NOT_ENOUGH_BALANCE; // Insufficient balance
                            }

                            if ($this->ssa_remote_wallet_error_maintenance()) {
                                $save_transaction = true;
                                $this->ssa_http_response_status_code = 500;
                                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (remote wallet maintenance)');
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = self::API_ERROR_NOT_ENOUGH_BALANCE; // Insufficient balance
                        }
                    }
                }
            }
        } elseif ($adjustment_type == $this->ssa_increase) {
            if($this->utils->compareResultFloat($amount, '=', 0)){
                if($this->ssa_enabled_remote_wallet()){
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                }else{
                    $success=true;
                }
            }elseif($this->utils->compareResultFloat($amount, '>=', 0)){
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                if($this->ssa_enabled_remote_wallet()){
                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
                }
                if (!$success) {
                    // remote wallet error
                    if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
    
                        // treat success if remote wallet return double uniqueid
                        if ($this->ssa_remote_wallet_error_double_unique_id()) {
                            $success = true;
                            $before_balance -= $amount;
                        }
    
                        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                            $save_transaction = true;
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (invalid remote wallet unique id)');
                        }
    
                        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                            $save_transaction = true;
                            $this->ssa_http_response_status_code = 400;
                            $this->ssa_operator_response = self::API_ERROR_NOT_ENOUGH_BALANCE; // Insufficient balance
                        }
    
                        if ($this->ssa_remote_wallet_error_maintenance()) {
                            $save_transaction = true;
                            $this->ssa_http_response_status_code = 500;
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (remote wallet maintenance)');
                        }
                    } else {
                        $this->ssa_http_response_status_code = 500;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (ssa_increase_player_wallet)');
                    }
                }
            }

        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (walletAdjustment default)');
            $success = false;
        }

        if($after_balance===null){
            $after_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
        }
        
        $this->player_balance = $after_balance;
        
        if ($success) {
            $is_processed = true;
        }

        $transaction_data = [
            'adjustment_type' => $adjustment_type,
            'amount' => $amount,
        ];

        if (!empty($this->remote_wallet_status)) {
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $transaction_data);
        }

        if ($success || $save_transaction) {
            $result = [
                'amount' => $amount,
                'before_balance' => $before_balance,
                'after_balance' => $after_balance,
                'is_processed' => $is_processed,
            ];

            $this->processed_transaction_id = $this->preprocessRequestData($query_type, $result);

            if (!$this->processed_transaction_id) {
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_OTHER_ERROR, 'Internal Server Error (preprocessRequestData)');
                $success = false;
            } else {
                if ($query_type == $this->ssa_insert) {
                    array_push($this->processed_multiple_transaction, $this->processed_transaction_id);
                }
            }
        }

        return $success;
    }

    public function calculateSessionBetAmount($data){
        $bet_amount = isset($data['betAmount'])?abs($data['betAmount']):0;
        $preserve = isset($data['preserve'])?abs($data['preserve']):0;
        $winlose_amount = isset($data['winloseAmount'])?abs($data['winloseAmount']):0;
		$session_type = isset($data['type'])?$data['type']:false;

        $balanceAdjustment = false;

        # with preserve
        if($preserve>0){
            if($session_type==self::BET_CODE){

                $balanceAdjustment = $preserve;
        
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case1 with preserve bet',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }elseif($session_type==self::SETTLE_CODE){

                $balanceAdjustment = $preserve - $bet_amount + $winlose_amount;
        
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case2 with preserve settle',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }
        }else{

            if($session_type==self::BET_CODE){
                $balanceAdjustment = $bet_amount;
            
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case3 no preserve bet',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }elseif($session_type==self::SETTLE_CODE){

                $balanceAdjustment = $winlose_amount;
        
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case4 no preserve settle',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }
        }

        if($balanceAdjustment===false){

        
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
            'case5 not matched',
            'api_method', $this->api_method, 
            'request_params', $this->ssa_request_params,
            'data',$data,
            'balanceAdjustment', $balanceAdjustment);
        }


        return $balanceAdjustment;  
    }

    public function calculateCancelSessionBetAmount($data){
        $bet_amount = isset($data['betAmount'])?abs($data['betAmount']):0;
        $preserve = isset($data['preserve'])?abs($data['preserve']):0;
        $session_type = isset($data['type']) ? $data['type'] : null;
        $winloseAmount = isset($data['winloseAmount']) ? $data['winloseAmount'] : null;

        $balanceAdjustment = false;
        
        # with preserve
        if($preserve>0){
            $balanceAdjustment = $preserve;
        }else{# no preserve
            $balanceAdjustment = $bet_amount;
        }

        # with preserve
        if($preserve>0){
            if($session_type==self::BET_CODE){

                $balanceAdjustment = $preserve;
        
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case1 with preserve bet',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }elseif($session_type==self::SETTLE_CODE){

                $balanceAdjustment = $preserve - $bet_amount + $winloseAmount;
        
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case2 with preserve settle',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }
        }else{

            if($session_type==self::BET_CODE){
                $balanceAdjustment = $bet_amount;
            
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case3 no preserve bet',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }elseif($session_type==self::SETTLE_CODE){

                $balanceAdjustment = $winloseAmount;
        
                $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
                'case4 no preserve settle',
                'api_method', $this->api_method, 
                'request_params', $this->ssa_request_params,
                'data',$data,
                'balanceAdjustment', $balanceAdjustment);

            }
        }

        if($balanceAdjustment===false){

        
            $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 
            'case5 not matched',
            'api_method', $this->api_method, 
            'request_params', $this->ssa_request_params,
            'data',$data,
            'balanceAdjustment', $balanceAdjustment);
        }


        return $balanceAdjustment;  
    }

    private function preprocessRequestData($query_type, $transaction_data) {
        $processed_transaction_id = null;
        $field_id = 'external_unique_id';

        if ($query_type == $this->ssa_insert) {
            $update_with_result = false;

            $processed_data = [
                // default
                'game_platform_id' => $this->game_platform_id,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                'language' => $this->language,
                'currency' => $this->currency,
                'transaction_type' => $this->api_method,
                'transaction_id' => isset($this->ssa_request_params['round']) ? $this->ssa_request_params['round'] : null,
                'game_code' => isset($this->ssa_request_params['game']) ? $this->ssa_request_params['game'] : null,
                'round_id' => isset($this->ssa_request_params['sessionId']) ? $this->ssa_request_params['sessionId'] : $this->ssa_request_params['round'],
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'status' => $this->setStatus(),
                'start_at' => $this->utils->getNowForMysql(),
                'end_at' => $this->utils->getNowForMysql(),
                // optional
                'req_id' => isset($this->ssa_request_params['reqId']) ? $this->ssa_request_params['reqId'] : null,
                'is_free_round' => isset($this->ssa_request_params['isFreeRound']) ? $this->ssa_request_params['isFreeRound'] : false,
                'offline_payment_transaction_id' => isset($this->ssa_request_params['transactionId']) ? $this->ssa_request_params['transactionId'] : null,
                'platform' => isset($this->ssa_request_params['platform']) ? $this->ssa_request_params['platform'] : null,
                'statement_type' => isset($this->ssa_request_params['statementType']) ? $this->ssa_request_params['statementType'] : null,
                'game_category' => isset($this->ssa_request_params['gameCategory']) ? $this->ssa_request_params['gameCategory'] : null,
                'session_id' => isset($this->ssa_request_params['sessionId']) ? $this->ssa_request_params['sessionId'] : null,
                'type' => isset($this->ssa_request_params['type']) ? $this->ssa_request_params['type'] : null,
                'turnover' => isset($this->ssa_request_params['turnover']) ? $this->ssa_request_params['turnover'] : 0,
                'preserve' => isset($this->ssa_request_params['preserve']) ? $this->ssa_request_params['preserve'] : 0,
                'session_total_bet' => isset($this->ssa_request_params['sessionTotalBet']) ? $this->ssa_request_params['sessionTotalBet'] : 0,
                // default
                'elapsed_time' => intval($this->utils->getExecutionTimeToNow() * 1000),
                'extra_info' => json_encode($this->ssa_request_params),
                'bet_amount' => isset($this->extra_transaction['bet_amount']) ? $this->extra_transaction['bet_amount'] : 0,
                'win_amount' => isset($this->extra_transaction['winlose_amount']) ? $this->extra_transaction['winlose_amount'] : 0,
                'result_amount' => isset($this->extra_transaction['result_amount']) ? $this->extra_transaction['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->extra_transaction['flag_of_updated_result']) ? $this->extra_transaction['flag_of_updated_result'] : $this->ssa_flag_not_updated,
                'external_unique_id' => $this->external_unique_id,
                'remote_wallet_status' => $this->remote_wallet_status,
                'seamless_service_unique_id' => $this->utils->mergeArrayValues(['game', $this->seamless_service_unique_id]),
                'is_processed' => $transaction_data['is_processed'],
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        } else {
            $update_with_result = true;

            $processed_data = [
                'transaction_type' => $this->api_method,
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'end_at' => $this->utils->getNowForMysql(),
                'updated_at' => $this->utils->getNowForMysql(),
                'status' => $this->setStatus(),
                'bet_amount' => 0,
                'win_amount' => 0,
                'result_amount' => 0,
                'flag_of_updated_result' => $this->ssa_flag_not_updated,
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
        }

        $processed_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $processed_data, $field_id, $this->external_unique_id, $update_with_result);

        return $processed_transaction_id;
    }

    private function setStatus() {
        switch ($this->api_method) {
            case self::API_METHOD_BET:
            case self::API_METHOD_SESSION_BET:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_CANCEL_BET:
            case self::API_METHOD_CANCEL_SESSION_BET:
                $result = Game_logs::STATUS_REFUND;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    private function rebuildOperatorResponse($flag, $operator_response) {
        if ($flag == Response_result::FLAG_NORMAL) {
            $operator_response = [
                'errorCode' => isset($operator_response['code']) ? $operator_response['code'] : self::API_ERROR_OTHER_ERROR['code'],
                'message' => isset($operator_response['message']) ? $operator_response['message'] : self::API_ERROR_OTHER_ERROR['message'],
                'username' => $this->player_details['game_username'],
                'currency' => $this->currency,
                'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
            ];

            if (!empty($this->external_unique_id)) {
                $operator_response['txId'] = $this->external_unique_id;
            }

            if (!empty($this->ssa_request_params['token']) && in_array($this->api_method, $this->token_required_api_methods)) {
                $operator_response['token'] = $this->ssa_request_params['token'];
            }
        } else {
            $operator_response = [
                'errorCode' => isset($operator_response['code']) ? $operator_response['code'] : self::API_ERROR_OTHER_ERROR['code'],
                'message' => isset($operator_response['message']) ? $operator_response['message'] : self::API_ERROR_OTHER_ERROR['message'],
            ];
        }

        return $operator_response;
    }

    private function response() {
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $this->ssa_operator_response = $this->rebuildOperatorResponse($flag, $this->ssa_operator_response);
        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $this->ssa_operator_response, $http_response, $player_id);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];

                $is_updated = $this->ssa_update_transaction_with_result($this->transaction_table, $updated_data, 'id', $processed_transaction_id);

                if ($this->use_monthly_transactions_table && $this->check_previous_transaction_table) {
                    if (!$is_updated) {
                        $is_updated = $this->ssa_update_transaction_with_result($this->previous_table, $updated_data, 'id', $processed_transaction_id);
                    }
                }
            }
        }

        return $this->returnJsonResult($this->ssa_operator_response, true, '*', false, false, $this->ssa_http_response_status_code);
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => isset($this->ssa_request_params['round']) ? $this->ssa_request_params['round'] : null,
            'round_id' => isset($this->ssa_request_params['sessionId']) ? $this->ssa_request_params['sessionId'] : $this->ssa_request_params['round'],
            'external_game_id' => isset($this->ssa_request_params['game']) ? $this->ssa_request_params['game'] : null,
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
            'external_uniqueid' => $this->external_unique_id,
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

    protected function getTransaction($where = [], $selected_columns = [], $order_by = ['field_name' => '', 'is_desc' => false]) {
        if (empty($where)) {
            $where = [
                'player_id' => $this->player_details['player_id'],
                'round_id' => $this->ssa_request_params['sessionId'],
            ];
        }

        $get_transactions = $this->ssa_get_transaction($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->check_previous_transaction_table) {
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
                'round_id' => $this->ssa_request_params['sessionId'],
            ];
        }

        $get_transactions = $this->ssa_get_transactions($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->check_previous_transaction_table) {
            if (empty($get_transactions)) {
                $get_transactions = $this->ssa_get_transactions($this->previous_table, $where);
            }
        }

        return $get_transactions;
    }

    protected function isTransactionExists($where = []) {
        $is_exist = $this->ssa_is_transaction_exists($this->transaction_table, $where);

        if ($this->use_monthly_transactions_table && $this->check_previous_transaction_table) {
            if (!$is_exist) {
                $is_exist = $this->ssa_is_transaction_exists($this->previous_table, $where);
            }
        }

        return $is_exist;
    }
}