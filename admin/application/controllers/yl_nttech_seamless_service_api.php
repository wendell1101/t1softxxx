<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Yl_nttech_seamless_service_api extends BaseController {
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
    private $precision;
    private $conversion;
    private $arithmetic_name;
    private $adjustment_precision;
    private $adjustment_conversion;
    private $adjustment_arithmetic_name;
    private $whitelist_ip_validate_api_methods;
    private $game_api_active_validate_api_methods;
    private $game_api_maintenance_validate_api_methods;
    private $game_api_player_blocked_validate_api_methods;
    private $extra_transaction;
    private $use_operator_response_message_code_only;
    private $cert;
    private $extension1;

    const SEAMLESS_GAME_API = YL_NTTECH_SEAMLESS_GAME_API;

    const API_SUCCESS_200 = [
        'code' => 200,
        'message' => 'Success',
    ];

    const API_ERROR_500 = [
        'code' => 500,
        'message' => 'Fail',
    ];

    const API_ERROR_1001 = [
        'api' => true,
        'code' => 1001,
        'message' => 'Not Authorized',
    ];

    const API_ERROR_1002 = [
        'api' => true,
        'code' => 1002,
        'message' => 'Balance is invalid',
    ];

    const API_ERROR_1003 = [
        'api' => true,
        'code' => 1003,
        'message' => 'No Data',
    ];

    const API_ERROR_1004 = [
        'api' => true,
        'code' => 1004,
        'message' => 'Userid/Username is invalid',
    ];

    const API_ERROR_1006 = [
        'api' => true,
        'code' => 1006,
        'message' => 'Insufficient parameters',
    ];

    const API_ERROR_1007 = [
        'api' => true,
        'code' => 1007,
        'message' => 'Not supported API',
    ];

    const API_ERROR_1008 = [
        'api' => true,
        'code' => 1008,
        'message' => 'Invalid parameters',
    ];

    const API_ERROR_1009 = [
        'api' => true,
        'code' => 1009,
        'message' => 'Insufficient balance',
    ];

    const API_ERROR_1011 = [
        'api' => true,
        'code' => 1011,
        'message' => 'Game ID not exist',
    ];

    const API_ERROR_1012 = [
        'api' => true,
        'code' => 1012,
        'message' => 'Agent does not exist',
    ];

    const API_ERROR_1014 = [
        'api' => true,
        'code' => 1014,
        'message' => 'Query too fast',
    ];

    const API_ERROR_1015 = [
        'api' => true,
        'code' => 1015,
        'message' => 'Invalid URL',
    ];

    const API_ERROR_1016 = [
        'api' => true,
        'code' => 1016,
        'message' => 'Invalid Agent',
    ];

    const API_ERROR_1018 = [
        'api' => true,
        'code' => 1018,
        'message' => 'Account locked',
    ];

    const API_ERROR_1023 = [
        'api' => true,
        'code' => 1023,
        'message' => 'Invalid website',
    ];

    const API_ERROR_1024 = [
        'api' => true,
        'code' => 1024,
        'message' => 'Invalid currency setting',
    ];

    const API_ERROR_1029 = [
        'api' => true,
        'code' => 1029,
        'message' => 'Settled transaction id',
    ];

    const API_ERROR_1030 = [
        'api' => true,
        'code' => 1030,
        'message' => 'Agent is not belong to website',
    ];

    const API_METHOD_GETBALANCE = 'getBalance';
    const API_METHOD_BET = 'bet';
    const API_METHOD_CANCELBET = 'cancelBet';
    const API_METHOD_SETTLE = 'settle';
    const API_METHOD_VOIDGAME = 'voidGame';
    const API_METHOD_SETTLEFISHBET = 'settleFishBet';
    const API_METHOD_VOIDFISHBET = 'voidFishBet';

    const ALLOWED_API_METHODS = [
        self::API_METHOD_GETBALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CANCELBET,
        self::API_METHOD_SETTLE,
        self::API_METHOD_VOIDGAME,
        self::API_METHOD_SETTLEFISHBET,
        self::API_METHOD_VOIDFISHBET,
    ];

    const WHITELIST_IP_VALIDATE_API_METHODS = [
        self::API_METHOD_GETBALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_CANCELBET,
        self::API_METHOD_SETTLE,
        self::API_METHOD_VOIDGAME,
        self::API_METHOD_SETTLEFISHBET,
        self::API_METHOD_VOIDFISHBET,
    ];

    const GAME_API_ACTIVE_VALIDATE_API_METHODS = [
        self::API_METHOD_GETBALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_SETTLEFISHBET,
    ];

    const GAME_API_MAINTENANCE_VALIDATE_API_METHODS = [
        self::API_METHOD_GETBALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_SETTLEFISHBET,
    ];

    const GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS = [
        self::API_METHOD_GETBALANCE,
        self::API_METHOD_BET,
        self::API_METHOD_SETTLEFISHBET,
    ];

    const ADDITIONAL_VALIDATION_API_METHODS = [
        self::API_METHOD_BET,
        self::API_METHOD_CANCELBET,
        self::API_METHOD_SETTLE,
        self::API_METHOD_VOIDGAME,
        self::API_METHOD_SETTLEFISHBET,
        self::API_METHOD_VOIDFISHBET,
    ];

    /* const TOKEN_REQUIRED_API_METHODS = [
        self::API_METHOD_BET,
    ]; */

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
        'extension1',
        'chair',
        'room_id',
        'room_level',
        'commission',
        'profit',
        'validbet',
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
        'commission',
        'profit',
        'validbet',
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

    public function __construct() {
        parent::__construct();
        $this->ssa_init();

        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = [];

        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->player_details = $this->extra_transaction = $this->processed_multiple_transaction = [];
        $this->game_api = $this->api_method = $this->external_unique_id = $this->processed_transaction_id = $this->response_result_id = null;
        $this->api_method = 'default';
        $this->player_balance = 0;
        $this->precision = 2;
    }

    public function index($game_platform_id) {
        if ($this->initialize($game_platform_id)) {
            $api_method = $this->api_method;
            return $this->$api_method();
        } else {
            return $this->response();
        }
    }

    private function initialize($game_platform_id) {
        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'key' => ['required'],
            'message' => ['required'],
        ]);

        if (!$is_valid) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            return false;
        }

        $this->ssa_request_params['message'] = is_array($this->ssa_request_params['message']) ? $this->ssa_request_params['message'] : json_decode($this->ssa_request_params['message'], true);

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'],
        ]);

        if (!$is_valid) {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
            return false;
        }

        $api_method = $this->ssa_request_params['message']['action'];

        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        if (empty($game_platform_id)) {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (initialize empty $game_platform_id)');
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($game_platform_id);

        if ($this->game_api) {
            // default
            $this->game_platform_id = $this->game_api->getPlatformCode();
            $this->transaction_table = $this->game_api->getSeamlessTransactionTable();
            $this->language = $this->game_api->language;
            $this->currency = $this->game_api->currency;
            $this->precision = $this->game_api->precision;
            $this->conversion = $this->game_api->conversion;
            $this->arithmetic_name = $this->game_api->arithmetic_name;
            $this->adjustment_precision = $this->game_api->adjustment_precision;
            $this->adjustment_conversion = $this->game_api->adjustment_conversion;
            $this->adjustment_arithmetic_name = $this->game_api->adjustment_arithmetic_name;
            $this->whitelist_ip_validate_api_methods = !empty($this->game_api->whitelist_ip_validate_api_methods) ? $this->game_api->whitelist_ip_validate_api_methods : self::WHITELIST_IP_VALIDATE_API_METHODS;
            $this->game_api_active_validate_api_methods = !empty($this->game_api->game_api_active_validate_api_methods) ? $this->game_api->game_api_active_validate_api_methods : self::GAME_API_ACTIVE_VALIDATE_API_METHODS;
            $this->game_api_maintenance_validate_api_methods = !empty($this->game_api->game_api_maintenance_validate_api_methods) ? $this->game_api->game_api_maintenance_validate_api_methods : self::GAME_API_MAINTENANCE_VALIDATE_API_METHODS;
            $this->game_api_player_blocked_validate_api_methods = !empty($this->game_api->game_api_player_blocked_validate_api_methods) ? $this->game_api->game_api_player_blocked_validate_api_methods : self::GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS;

            // addtional
            $this->cert = $this->game_api->cert;
            $this->extension1 = $this->game_api->extension1;
            $this->use_operator_response_message_code_only = $this->game_api->use_operator_response_message_code_only;

            if (in_array($api_method, $this->whitelist_ip_validate_api_methods)) {
                if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                    $this->ssa_http_response_status_code = 401;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1001, 'IP address is not allowed');
                    return false;
                }
            }

            if (in_array($api_method, $this->game_api_active_validate_api_methods)) {
                if (!$this->ssa_is_game_api_active($this->game_api)) {
                    $this->ssa_http_response_status_code = 503;
                    $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Game is disabled');
                    return false;
                }
            }

            if (in_array($api_method, $this->game_api_maintenance_validate_api_methods)) {
                if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                    $this->ssa_http_response_status_code = 503;
                    $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Game is under maintenance');
                    return false;
                }
            }
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (load_game_api)');
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            $this->ssa_http_response_status_code = 404;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1007, 'Parameter action ' . $api_method . ' not found');
            return false;
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            $this->ssa_http_response_status_code = 403;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1001, 'Method ' . $api_method . ' forbidden');
            return false;
        }

        return true;
    }

    private function additionalValidation() {
        if (isset($this->ssa_request_params['key']) && $this->ssa_request_params['key'] != $this->cert) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1008, 'Invalid parameter key');
            return false;
        }

        if (in_array($this->api_method, self::ADDITIONAL_VALIDATION_API_METHODS)) {
            if (isset($this->ssa_request_params['message']['extension1']) && $this->ssa_request_params['message']['extension1'] != $this->extension1) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1030, 'Invalid parameter extension1');
                return false;
            }

            if (isset($this->ssa_request_params['message']['currency']) && $this->ssa_request_params['message']['currency'] != $this->currency) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1024, 'Invalid parameter currency');
                return false;
            }
        }

        if (!$this->validateRequestPlayer()) {
            return false;
        }

        return true;
    }

    private function validateRequestPlayer() {
        $game_username = $this->ssa_request_params['message']['userId'];
        $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);

        if (empty($this->player_details)) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1004, 'Invalid parameter userId ' . $game_username);
            return false;
        }

        if ($this->player_details['game_username'] != $game_username) {
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1004, 'Invalid parameter userId ' . $game_username);
            return false;
        }

        if (in_array($this->api_method, $this->game_api_player_blocked_validate_api_methods)) {
            if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
                $this->ssa_http_response_status_code = 401;
                $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1018, 'Player is blocked');
                return false;
            }
        }

        $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        return true;
    }

    private function getBalance() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'],
            'userId' => ['required'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $this->ssa_http_response_status_code = 200;
                $this->ssa_operator_response = [
                    'status' => strval(self::API_SUCCESS_200['code']),
                    'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                ];
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function bet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'], // transaction_type
            'userId' => ['required'], // game_username
            'extension1' => ['required'],
            'gameNumber' => ['required'], // round_id
            'gameId' => ['required'], // game_code
            'chair' => ['optional'],
            'currency' => ['required'],
            'txId' => ['required'], // transaction_id
            'roomId' => ['optional'],
            'roomLevel' => ['optional'],
            'extension3' => ['optional'],
            'betTransTime' => ['optional'],
            'betAmount' => ['required', 'nullable', 'numeric'], // amount
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (bet default)');
                $this->external_unique_id = $this->ssa_composer([$this->api_method, $this->ssa_request_params['message']['txId']]);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $controller = $this;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                        return $controller->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->ssa_request_params['message']['betAmount']);
                    });
                } else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Transaction already exists');
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = [
                        'status' => strval(self::API_SUCCESS_200['code']),
                        'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    ];
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function settle() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'], // transaction_type
            'userId' => ['required'], // game_username
            'extension1' => ['required'],
            'gameNumber' => ['required'], // round_id
            'gameId' => ['required'], // game_code
            'chair' => ['optional'],
            'roomId' => ['optional'],
            'roomLevel' => ['optional'],
            'txId' => ['required'], // transaction_id
            'extension3' => ['optional'],
            'currency' => ['required'],
            'betTransTime' => ['optional'],
            'betAmount' => ['required', 'nullable', 'numeric'],
            'realBetMoney' => ['optional'],
            'payTransTime' => ['optional'],
            'payAmount' => ['required', 'nullable', 'numeric'], // amount
            'realPayMoney' => ['optional'],
            'commission' => ['optional'],
            'profit' => ['optional'],
            'validbet' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (bet default)');
                $this->external_unique_id = $this->ssa_composer([$this->api_method, $this->ssa_request_params['message']['txId']]);

                $this->ssa_set_game_provider_is_end_round(true);
                $this->ssa_set_related_action_of_seamless_service('bet');
                $related_unique_id = 'game-'.$this->game_platform_id.'-'.$this->ssa_composer([self::API_METHOD_BET, $this->ssa_request_params['message']['txId']]);
                $this->ssa_set_related_uniqueid_of_seamless_service($related_unique_id);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                        'transaction_type' => self::API_METHOD_CANCELBET,
                        'game_username' => $this->ssa_request_params['message']['userId'],
                        'game_code' => $this->ssa_request_params['message']['gameId'],
                        'round_id' => $this->ssa_request_params['message']['gameNumber'],
                    ])) {
                        if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                            'transaction_type' => self::API_METHOD_VOIDGAME,
                            'game_username' => $this->ssa_request_params['message']['userId'],
                            'game_code' => $this->ssa_request_params['message']['gameId'],
                            'round_id' => $this->ssa_request_params['message']['gameNumber'],
                        ])) {
                            if ($this->ssa_is_transaction_exists($this->transaction_table, [
                                'transaction_type' => self::API_METHOD_BET,
                                'transaction_id' => $this->ssa_request_params['message']['txId'],
                                'game_username' => $this->ssa_request_params['message']['userId'],
                                'game_code' => $this->ssa_request_params['message']['gameId'],
                                'round_id' => $this->ssa_request_params['message']['gameNumber'],
                            ])) {
                                $controller = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->ssa_request_params['message']['payAmount']);
                                });
                            } else {
                                $this->ssa_http_response_status_code = 400;
                                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Bet not found');
                            }
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            // $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Already processed by voidGame');
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1029, 'Settled transaction id (already processed by voidGame)');
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        // $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Already processed by cancelBet');
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1029, 'Settled transaction id (already processed by cancelBet)');
                    }
                } else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_common_operator_response(self::API_ERROR_1029, 'Transaction already exists');
                    // $success = true;
                    // $this->player_balance = $this->ssa_get_transaction_after_balance($this->transaction_table, ['external_unique_id' => $this->external_unique_id]);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = [
                        'status' => strval(self::API_SUCCESS_200['code']),
                        'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    ];
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function cancelBet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'], // transaction_type
            'userId' => ['required'], // game_username
            'gameId' => ['optional'], // game_code
            'txId' => ['required'], // transaction_id
            'gameNumber' => ['optional'], // round_id
            'updateTime' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (cancelBet default)');
                $this->external_unique_id = $this->ssa_composer([$this->api_method, $this->ssa_request_params['message']['txId']]);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                        $bet_external_unique_id = $this->ssa_composer([self::API_METHOD_BET, $this->ssa_request_params['message']['txId']]);
                        $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $bet_external_unique_id]);

                        $this->ssa_set_game_provider_is_end_round(true);
                        $this->ssa_set_related_action_of_seamless_service('bet');
                        $related_unique_id = 'game-'.$this->game_platform_id.'-'.$this->ssa_composer([self::API_METHOD_BET, $this->ssa_request_params['message']['txId']]);
                        $this->ssa_set_related_uniqueid_of_seamless_service($related_unique_id);

                        if (!empty($bet_transaction)) {
                            $bet_amount = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                            $game_username = $this->ssa_request_params['message']['userId'] = isset($bet_transaction['game_username']) ? $bet_transaction['game_username'] : null;
                            $game_code = $this->ssa_request_params['message']['gameId'] = isset($bet_transaction['game_code']) ? $bet_transaction['game_code'] : null;
                            $round_id = $this->ssa_request_params['message']['gameNumber'] = isset($bet_transaction['round_id']) ? $bet_transaction['round_id'] : null;
                            
                            if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                                'transaction_type' => self::API_METHOD_SETTLE,
                                'game_username' => $game_username,
                                'game_code' => $game_code,
                                'round_id' => $round_id,
                                ])) { 
                                $controller = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $bet_amount) {
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $bet_amount);
                                });
                            } else {
                                $success = true;
                            }
                        } else {
                            $success = true;
                        }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = [
                        'status' => strval(self::API_SUCCESS_200['code']),
                        'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    ];
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function voidGame() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'], // transaction_type
            'userId' => ['required'], // game_username
            'gameId' => ['optional'], // game_code
            'txId' => ['required'], // transaction_id
            'gameNumber' => ['optional'], // round_id
            'updateTime' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (cancelBet default)');
                $this->external_unique_id = $this->ssa_composer([$this->api_method, $this->ssa_request_params['message']['txId']]);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                        'transaction_type' => self::API_METHOD_SETTLE,
                        'game_username' => $this->ssa_request_params['message']['userId'],
                        'game_code' => $this->ssa_request_params['message']['gameId'],
                        'round_id' => $this->ssa_request_params['message']['gameNumber'],
                    ])) {
                        if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                            'transaction_type' => self::API_METHOD_CANCELBET,
                            'game_username' => $this->ssa_request_params['message']['userId'],
                            'game_code' => $this->ssa_request_params['message']['gameId'],
                            'round_id' => $this->ssa_request_params['message']['gameNumber'],
                        ])) {
                            $bet_external_unique_id = $this->ssa_composer([self::API_METHOD_BET, $this->ssa_request_params['message']['txId']]);
                            $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $bet_external_unique_id]);

                            if (!empty($bet_transaction)) {
                                $bet_amount = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;

                                $controller = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $bet_amount) {
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $bet_amount);
                                });
                            } else {
                                $success = true;
                            }
                        } else {
                            $success = true;
                        }
                    } else {
                        $success = true;
                    }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = [
                        'status' => strval(self::API_SUCCESS_200['code']),
                        'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    ];
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function settleFishBet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'], // transaction_type
            'userId' => ['required'], // game_username
            'extension1' => ['required'],
            'gameNumber' => ['required'], // round_id
            'gameId' => ['required'], // game_code
            'chair' => ['optional'],
            'roomId' => ['optional'],
            'roomLevel' => ['optional'],
            'txId' => ['required'], // transaction_id
            'extension3' => ['optional'],
            'currency' => ['required'],
            'betTransTime' => ['optional'],
            'betAmount' => ['required', 'nullable', 'numeric'], // bet amount
            'realBetMoney' => ['optional'],
            'payTransTime' => ['optional'],
            'payAmount' => ['required', 'nullable', 'numeric'], // win amount
            'realPayMoney' => ['optional'],
            'commission' => ['optional'],
            'profit' => ['optional'],
            'validbet' => ['optional'],
            'requireAmount' => ['required', 'nullable', 'numeric'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (bet default)');
                $this->external_unique_id = $this->ssa_composer([$this->api_method, $this->ssa_request_params['message']['txId']]);
                $this->extra_transaction['bet_amount'] = isset($this->ssa_request_params['message']['betAmount']) ? $this->ssa_request_params['message']['betAmount'] : 0;
                $this->extra_transaction['win_amount'] = isset($this->ssa_request_params['message']['payAmount']) ? $this->ssa_request_params['message']['payAmount'] : 0;
                $this->extra_transaction['result_amount'] = $this->extra_transaction['win_amount'] - $this->extra_transaction['bet_amount'];
                $this->extra_transaction['amount'] = abs($this->extra_transaction['result_amount']);
                $this->extra_transaction['flag_of_updated_result'] = $this->ssa_flag_not_updated;

                $this->ssa_set_game_provider_is_end_round(true);


                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    if ($this->ssa_request_params['message']['requireAmount'] <= $this->player_balance && $this->extra_transaction['bet_amount'] <= $this->player_balance) {
                            $controller = $this;
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                                if ($this->extra_transaction['result_amount'] < 0) {
                                    $action = $this->ssa_decrease;
                                } else {
                                    $action = $this->ssa_increase;
                                }
                                return $controller->walletAdjustment($action, $this->ssa_insert, $this->extra_transaction['amount'], self::API_METHOD_SETTLEFISHBET);
                            });
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = self::API_ERROR_1009; // Insufficient balance
                    }
                } else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Transaction already exists');
                    // $success = true;
                    // $this->player_balance = $this->ssa_get_transaction_after_balance($this->transaction_table, ['external_unique_id' => $this->external_unique_id]);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = [
                        'status' => strval(self::API_SUCCESS_200['code']),
                        'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    ];
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function voidFishBet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['message'], [
            'action' => ['required'], // transaction_type
            'userId' => ['required'], // game_username
            'gameId' => ['optional'], // game_code
            'txId' => ['required'], // transaction_id
            'gameNumber' => ['optional'], // round_id
            'updateTime' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (cancelBet default)');
                $this->external_unique_id = $this->ssa_composer([$this->api_method, $this->ssa_request_params['message']['txId']]);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $settle_fish_bet_external_unique_id = $this->ssa_composer([self::API_METHOD_SETTLEFISHBET, $this->ssa_request_params['message']['txId']]);
                    $settle_fish_bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $settle_fish_bet_external_unique_id]);

                    if (!empty($settle_fish_bet_transaction)) {
                        $bet_amount = !empty($settle_fish_bet_transaction['bet_amount']) ? $settle_fish_bet_transaction['bet_amount'] : 0;

                        if (empty($settle_fish_bet_transaction['win_amount'])) {
                            $controller = $this;
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $bet_amount) {
                                return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $bet_amount);
                            });
                        } else {
                            $this->ssa_http_response_status_code = 400;
                            // $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Already settled');
                            $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1029, 'Settled transaction id (already settled)');
                        }
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1029, 'Invalid parameter txId, Bet not found');
                    }
                } else {
                    $this->ssa_http_response_status_code = 400;
                    $this->ssa_operator_response = $this->ssa_operator_response_custom_message(self::API_ERROR_1029, 'Transaction already exists');
                    // $success = true;
                    // $this->player_balance = $this->ssa_get_transaction_after_balance($this->transaction_table, ['external_unique_id' => $this->external_unique_id]);
                }

                if ($success) {
                    $this->ssa_http_response_status_code = 200;
                    $this->ssa_operator_response = [
                        'status' => strval(self::API_SUCCESS_200['code']),
                        'balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                    ];
                }
            }
        } else {
            $this->ssa_operator_response = $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response);
        }

        return $this->response();
    }

    private function walletAdjustment($adjustment_type, $query_type, $amount, $method = null) {
        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance;
        $this->extra_transaction['game_code'] =  isset($this->ssa_request_params['message']['gameId']) ? $this->ssa_request_params['message']['gameId'] : null;

        $this->ssa_set_uniqueid_of_seamless_service($this->game_platform_id . '-' . $this->external_unique_id, $this->extra_transaction['game_code']);

        if($method == self::API_METHOD_SETTLEFISHBET){
            $this->ssa_set_game_provider_action_type(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT);
            $this->ssa_set_game_provider_bet_amount($this->extra_transaction['bet_amount']);
            $this->ssa_set_game_provider_payout_amount($this->extra_transaction['win_amount']);
        }

        if ($adjustment_type == $this->ssa_decrease) {
            if ($amount > $before_balance) {
                $this->ssa_http_response_status_code = 400;
                $this->ssa_operator_response = self::API_ERROR_1009; // Insufficient balance
                $success = false;
            } else {
                if ($amount == 0) {
                    $success = true;
                } else {
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);

                    if ($success) {
                        $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                    } else {
                        $this->ssa_http_response_status_code = 400;
                        $this->ssa_operator_response = self::API_ERROR_1009; // Insufficient balance
                    }
                }
            }
        } elseif ($adjustment_type == $this->ssa_increase) {
            $isEnableRemoteWallet = $this->ssa_enabled_remote_wallet();

            if ($isEnableRemoteWallet && $amount >= 0) {
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);
            } elseif (!$isEnableRemoteWallet) {
                if($amount == 0){
                    $success = true;
                }else{
                    $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);
                }
            } else {
                $success = false;
            }

            if ($success) {
                $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
            } else {
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (ssa_increase_player_wallet)');
            }
           
        } else {
            $this->ssa_http_response_status_code = 500;
            $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (walletAdjustment default)');
            $success = false;
        }

        if ($success) {
            $result = [
                'amount' => $amount,
                'before_balance' => $before_balance,
                'after_balance' => $after_balance,
            ];

            $this->processed_transaction_id = $this->preprocessRequestData($query_type, $result);

            if (!$this->processed_transaction_id) {
                $this->ssa_http_response_status_code = 500;
                $this->ssa_operator_response = $this->ssa_common_operator_response($this->ssa_http_response_status_code, 'Internal Server Error (preprocessRequestData)');
                $success = false;
            } else {
                if ($query_type == $this->ssa_insert) {
                    array_push($this->processed_multiple_transaction, $this->processed_transaction_id);
                }
            }
        }

        return $success;
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
                'game_username' => isset($this->ssa_request_params['message']['userId']) ? $this->ssa_request_params['message']['userId'] : null,
                'language' => $this->language,
                'currency' => $this->currency,
                'transaction_type' => $this->api_method,
                'transaction_id' => isset($this->ssa_request_params['message']['txId']) ? $this->ssa_request_params['message']['txId'] : null,
                'game_code' => isset($this->ssa_request_params['message']['gameId']) ? $this->ssa_request_params['message']['gameId'] : null,
                'round_id' => isset($this->ssa_request_params['message']['gameNumber']) ? $this->ssa_request_params['message']['gameNumber'] : null,
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'status' => $this->setStatus(),
                'start_at' => $this->utils->getNowForMysql(),
                'end_at' => $this->utils->getNowForMysql(),
                // optional
                'extension1' => isset($this->ssa_request_params['message']['extension1']) ? $this->ssa_request_params['message']['extension1'] : null,
                'chair' => isset($this->ssa_request_params['message']['chair']) ? $this->ssa_request_params['message']['chair'] : null,
                'room_id' => isset($this->ssa_request_params['message']['roomId']) ? $this->ssa_request_params['message']['roomId'] : null,
                'room_level' => isset($this->ssa_request_params['message']['roomLevel']) ? $this->ssa_request_params['message']['roomLevel'] : null,
                'commission' => isset($this->ssa_request_params['message']['commission']) ? $this->ssa_request_params['message']['commission'] : 0,
                'profit' => isset($this->ssa_request_params['message']['profit']) ? $this->ssa_request_params['message']['profit'] : 0,
                'validbet' => isset($this->ssa_request_params['message']['validbet']) ? $this->ssa_request_params['message']['validbet'] : 0,
                // default
                'elapsed_time' => intval($this->utils->getExecutionTimeToNow() * 1000),
                'extra_info' => json_encode($this->ssa_request_params),
                'bet_amount' => isset($this->extra_transaction['bet_amount']) ? $this->extra_transaction['bet_amount'] : 0,
                'win_amount' => isset($this->extra_transaction['win_amount']) ? $this->extra_transaction['win_amount'] : 0,
                'result_amount' => isset($this->extra_transaction['result_amount']) ? $this->extra_transaction['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->extra_transaction['flag_of_updated_result']) ? $this->extra_transaction['flag_of_updated_result'] : $this->ssa_flag_not_updated,
                'external_unique_id' => $this->external_unique_id,
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
                $result = Game_logs::STATUS_UNSETTLED;
                break;
            case self::API_METHOD_SETTLE:
            case self::API_METHOD_SETTLEFISHBET:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::API_METHOD_CANCELBET:
                $result = Game_logs::STATUS_CANCELLED;
                break;
            case self::API_METHOD_VOIDGAME:
            case self::API_METHOD_VOIDFISHBET:
                $result = Game_logs::STATUS_VOID;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    private function rebuildOperatorResponse($flag) {
        if ($flag == Response_result::FLAG_NORMAL) {
            $new_operator_response = $this->ssa_operator_response;
        } else {
            $is_from_api = isset($this->ssa_operator_response['api']) && $this->ssa_operator_response['api'] ? $this->ssa_operator_response['api'] : false;
            $operator_response_code = isset($this->ssa_operator_response['code']) ? $this->ssa_operator_response['code'] : null;
            $operator_response_message = isset($this->ssa_operator_response['message']) ? $this->ssa_operator_response['message'] : null;

            $new_operator_response = [
                'status' => strval(self::API_ERROR_500['code']),
                'msg' => $operator_response_message,
            ];

            if ($is_from_api) {
                if ($this->use_operator_response_message_code_only) {
                    // $new_operator_response['msg'] = 'code:' . $operator_response_code;
                    $new_operator_response['msg'] = strval($operator_response_code);
                } else {
                    // $new_operator_response['msg'] = 'code:' . $operator_response_code . ' ' . $operator_response_message;
                    $new_operator_response['msg'] = $operator_response_code . ' ' . $operator_response_message;
                }
            }
        }

        return $new_operator_response;
    }

    private function response() {
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $this->ssa_operator_response = $this->rebuildOperatorResponse($flag);
        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $this->ssa_operator_response, $http_response, $player_id);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];
    
                $this->ssa_update_transaction_without_result($this->transaction_table, $updated_data, 'id', $processed_transaction_id);
            }
        }

        return $this->returnJsonResult($this->ssa_operator_response, true, '*', false, false, $this->ssa_http_response_status_code);
    }
}