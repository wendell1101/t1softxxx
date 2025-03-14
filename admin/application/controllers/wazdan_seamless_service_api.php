<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Wazdan_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    private $game_platform_id;
    private $game_api;
    private $api_method;
    private $username;
    private $password;
    private $language;
    private $currency;
    private $response_result_id;
    private $player_details;
    private $player_balance;
    private $transaction_table;
    private $processed_transaction_id;
    private $processed_multiple_transaction;
    private $precision;
    private $message_type_response;
    private $whitelist_ip_validate_api_methods;
    private $game_api_active_validate_api_methods;
    private $game_api_maintenance_validate_api_methods;
    private $game_api_player_blocked_validate_api_methods;
    private $free_spins_mode;
    private $free_rounds_mode;
    private $game_provider_action_type = null;
    private $is_end_round = false;

    const SEAMLESS_GAME_API = WAZDAN_SEAMLESS_GAME_API;

    const API_ERROR_1 = [
        'code' => 1,
        'message' => 'Session not found',
    ];

    const API_ERROR_2 = [
        'code' => 2,
        'message' => 'Session expired',
    ];

    const API_ERROR_3 = [
        'code' => 3,
        'message' => 'Session already exists',
    ];

    const API_ERROR_4 = [
        'code' => 4,
        'message' => 'Limit reached',
    ];

    const API_ERROR_5 = [
        'code' => 5,
        'message' => 'User is blocked',
    ];

    const API_ERROR_8 = [
        'code' => 8,
        'message' => 'Insufficient funds',
    ];

    const API_ERROR_9 = [
        'code' => 9,
        'message' => "Player's IP is not allowed",
    ];

    const API_METHOD_AUTHENTICATE = 'authenticate';
    const API_METHOD_GETSTAKE = 'getStake';
    const API_METHOD_RETURNWIN = 'returnWin';
    const API_METHOD_ROLLBACKSTAKE = 'rollbackStake';
    const API_METHOD_GAMECLOSE = 'gameClose';
    const API_METHOD_GETFUNDS = 'getFunds';
    const API_METHOD_RCCLOSE = 'rcClose';
    const API_METHOD_RCCONTINUE = 'rcContinue';
    const API_METHOD_MESSAGECHOICE = 'messageChoice';

    const ALLOWED_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_GETSTAKE,
        self::API_METHOD_RETURNWIN,
        self::API_METHOD_ROLLBACKSTAKE,
        self::API_METHOD_GAMECLOSE,
        self::API_METHOD_GETFUNDS,
        self::API_METHOD_RCCLOSE,
        self::API_METHOD_RCCONTINUE,
        self::API_METHOD_MESSAGECHOICE,
    ];

    const WHITELIST_IP_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_GETSTAKE,
        self::API_METHOD_RETURNWIN,
        self::API_METHOD_ROLLBACKSTAKE,
        self::API_METHOD_GETFUNDS,
    ];

    const GAME_API_ACTIVE_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_GETSTAKE,
        self::API_METHOD_GETFUNDS,
    ];

    const GAME_API_MAINTENANCE_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_GETSTAKE,
        self::API_METHOD_GETFUNDS,
    ];

    const GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_GETSTAKE,
        self::API_METHOD_GETFUNDS,
    ];

    const TOKEN_REQUIRED_API_METHODS = [
        self::API_METHOD_AUTHENTICATE,
        self::API_METHOD_GETSTAKE,
    ];

    const NON_INTRUSIVE_MESSAGE = 1;
    const INTRUSIVE_MESSAGE_CONFIRM = 2;
    const INTRUSIVE_MESSAGE_ACTION = 3;

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
        'skin_id',
        'token',
        'type',
        'end_round',
        'free_spin',
        'free_round',
        'last_free_spin',
        'last_free_round',
        'cash_drop_id',
        'prize_id',
        'promotion_id',
        'bet_transaction_id',
        'original_transaction_id',
        'bonus_amount',
        'free_round_info',
        'cash_drop_info',
        'round_info',
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
        'before_balance',
        'after_balance',
        'amount',
        'bonus_amount',
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
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->player_details = $this->processed_multiple_transaction = [];
        $this->api_method = $this->processed_transaction_id = $this->response_result_id = null;
        $this->player_balance = 0;
        $this->precision = 2;
        $this->message_type_response = self::NON_INTRUSIVE_MESSAGE;
    }

    public function index($game_platform_id, $api_method) {
        $this->api_method = $this->ssa_api_method(__FUNCTION__, $api_method, self::ALLOWED_API_METHODS);
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        list($is_initialized, $http_response_status_code, $operator_response) = $this->initialize($game_platform_id, $api_method);

        if (!$is_initialized) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $this->utils->debug_log(__CLASS__, __METHOD__, $this->game_platform_id, 'api_method', $this->api_method, 'request_params', $this->ssa_request_params);

        return $this->$api_method();
    }

    private function initialize($game_platform_id, $api_method) {
        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $api_method)) {
            return array(false, 404, $this->ssa_common_operator_response(404, 'Method ' . $api_method . ' not found.'));
        }

        if ($this->ssa_is_api_method_allowed($api_method, self::ALLOWED_API_METHODS)) {
            return array(false, 403, $this->ssa_common_operator_response(403, 'Method ' . $api_method . ' forbidden.'));
        }

        if (empty($game_platform_id)) {
            return array(false, 500, $this->ssa_common_operator_response(500, 'Internal Server Error (initialize empty $game_platform_id)'));
        }

        $this->game_api = $this->ssa_load_game_api_class($game_platform_id);

        if ($this->game_api) {
            // add need properties from game api here
            $this->game_platform_id = $this->game_api->getPlatformCode();
            $this->transaction_table = $this->game_api->getSeamlessTransactionTable();
            $this->language = $this->game_api->language;
            $this->currency = $this->game_api->currency;
            $this->precision = $this->game_api->precision;
            $this->username = $this->game_api->username;
            $this->password = $this->game_api->password;
            $this->whitelist_ip_validate_api_methods = !empty($this->game_api->whitelist_ip_validate_api_methods) ? $this->game_api->whitelist_ip_validate_api_methods : self::WHITELIST_IP_VALIDATE_API_METHODS;
            $this->game_api_active_validate_api_methods = !empty($this->game_api->game_api_active_validate_api_methods) ? $this->game_api->game_api_active_validate_api_methods : self::GAME_API_ACTIVE_VALIDATE_API_METHODS;
            $this->game_api_maintenance_validate_api_methods = !empty($this->game_api->game_api_maintenance_validate_api_methods) ? $this->game_api->game_api_maintenance_validate_api_methods : self::GAME_API_MAINTENANCE_VALIDATE_API_METHODS;
            $this->game_api_player_blocked_validate_api_methods = !empty($this->game_api->game_api_player_blocked_validate_api_methods) ? $this->game_api->game_api_player_blocked_validate_api_methods : self::GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS;
            $this->free_spins_mode = $this->game_api->free_spins_mode;
            $this->free_rounds_mode = $this->game_api->free_rounds_mode;

            if (in_array($api_method, $this->whitelist_ip_validate_api_methods)) {
                if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                    return array(false, 401, $this->ssa_common_operator_response(401, 'IP address is not allowed'));
                }
            }

            if (in_array($api_method, $this->game_api_active_validate_api_methods)) {
                if (!$this->ssa_is_game_api_active($this->game_api)) {
                    return array(false, 503, $this->ssa_common_operator_response(503, 'Game is disabled'));
                }
            }

            if (in_array($api_method, $this->game_api_maintenance_validate_api_methods)) {
                if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                    return array(false, 503, $this->ssa_common_operator_response(503, 'Game is under maintenance'));
                }
            }

            if (!$this->ssa_validate_basic_auth_request($this->username, $this->password)) {
                return array(false, 401, $this->ssa_common_operator_response(401, $this->ssa_custom_message_response));
            }
        } else {
            return array(false, 500, $this->ssa_common_operator_response(500, 'Internal Server Error (load_game_api)'));
        }

        return array(true, 200, $this->ssa_api_success);
    }

    private function authenticate() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'token' => ['required'],
            'ip' => ['optional'],
            'gameId' => ['optional'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $operator_response = [
            'status' => $this->ssa_api_success['code'],
            'user' => [
                'id' => $this->player_details['game_username'],
                'currency' => $this->currency,
            ],
            'funds' => [
                'balance' => $this->ssa_truncate_amount($this->player_balance, $this->precision),
            ],
            'options' => [
                'freeSpinsMode' => $this->free_spins_mode,
                'freeRoundsMode' => $this->free_rounds_mode,
            ]
        ];

        return $this->setResponse(200, $operator_response);
    }

    private function getStake() {
        $this->api_method = __function__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
            'transactionId' => ['required'],
            'gameId' => ['required'],
            'roundId' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'],
            'freeSpin' => ['optional'],
            'freeRound' => ['optional'],
            'freeRoundInfo' => ['optional'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['required'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $success = false;
        $http_response_status_code = 500;
        $operator_response = $this->ssa_common_operator_response(500, 'Internal Server Error (getStake default)');

        if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->ssa_request_params['transactionId']])) {
            $win_transaction = $this->ssa_get_transaction($this->transaction_table, [
                'transaction_type' => self::API_METHOD_RETURNWIN,
                'game_username' => $this->ssa_request_params['user']['id'],
                'game_code' => $this->ssa_request_params['gameId'],
                'round_id' => $this->ssa_request_params['roundId'],
                'status' => true,
            ]);

            if (!empty($win_transaction) && isset($win_transaction['transaction_id'])) {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Round already finished by transaction id: ' . $win_transaction['transaction_id']));
            }

            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$success, &$http_response_status_code, &$operator_response) {
                list($success, $http_response_status_code, $operator_response) = $controller->adjustWallet($this->ssa_decrease, $this->ssa_insert, $success, $http_response_status_code, $operator_response);
                return $success;
            });
        } else {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['transaction_id' => $this->ssa_request_params['transactionId']]);
            $this->player_balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
        }

        if ($success) {
            $http_response_status_code = 200;
            $operator_response = [
                'status' => $this->ssa_api_success['code'],
                'funds' => [
                    'balance' => $this->ssa_truncate_amount($this->player_balance, $this->precision),
                ],
            ];
        }

        return $this->setResponse($http_response_status_code, $operator_response);
    }

    private function returnWin() {
        $this->api_method = __function__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
            'transactionId' => ['required'],
            'gameId' => ['required'],
            'roundId' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'],
            'round' => ['required', 'array'],
            'round.endRound' => ['required'],
            'type' => ['optional'],
            'cashDropInfo' => ['optional'],
            'freeRoundInfo' => ['optional'],
            'roundInfo' => ['optional'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        $this->is_end_round = isset($this->ssa_request_params['round']['endRound']) ?: false;

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['required'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        if (isset($this->ssa_request_params['round'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['round'], [
                'endRound' => ['required'],
                'betTransactionId' => ['required'],
                'lastFreeSpin' => ['optional'],
                'lastFreeRound' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $success = false;
        $http_response_status_code = 500;
        $operator_response = $this->ssa_common_operator_response(500, 'Internal Server Error (returnWin default)');

        if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->ssa_request_params['transactionId']])) {
            $win_transaction = $this->ssa_get_transaction($this->transaction_table, [
                'transaction_type' => self::API_METHOD_RETURNWIN,
                'game_username' => $this->ssa_request_params['user']['id'],
                'game_code' => $this->ssa_request_params['gameId'],
                'round_id' => $this->ssa_request_params['roundId'],
                'status' => true,
            ]);

            if (!empty($win_transaction) && isset($win_transaction['transaction_id'])) {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Round already finished by transaction id: ' . $win_transaction['transaction_id']));
            }

            $rollback_transaction = $this->ssa_get_transaction($this->transaction_table, [
                'transaction_type' => self::API_METHOD_ROLLBACKSTAKE,
                'game_username' => $this->ssa_request_params['user']['id'],
                'game_code' => $this->ssa_request_params['gameId'],
                'round_id' => $this->ssa_request_params['roundId'],
            ]);

            if ($rollback_transaction) {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Already processed by rollbackStake transaction id: ' . $rollback_transaction['transaction_id']));
            }

            $bet_transaction = $this->ssa_get_transaction($this->transaction_table, [
                'transaction_type' => self::API_METHOD_GETSTAKE,
                'transaction_id' => $this->ssa_request_params['round']['betTransactionId'],
            ]);

            if (!empty($bet_transaction)) {
                if (isset($bet_transaction['game_code']) && $bet_transaction['game_code'] != $this->ssa_request_params['gameId']) {
                    return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Invalid gameId'));
                }

                if (isset($bet_transaction['round_id']) && $bet_transaction['round_id'] != $this->ssa_request_params['roundId']) {
                    return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Invalid roundId'));
                }
            } else {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Bet (betTransactionId) not found'));
            }

            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$success, &$http_response_status_code, &$operator_response) {
                list($success, $http_response_status_code, $operator_response) = $controller->adjustWallet($this->ssa_increase, $this->ssa_insert, $success, $http_response_status_code, $operator_response);
                return $success;
            });
        } else {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['transaction_id' => $this->ssa_request_params['transactionId']]);
            $this->player_balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
        }

        if ($success) {
            $http_response_status_code = 200;
            $operator_response = [
                'status' => $this->ssa_api_success['code'],
                'funds' => [
                    'balance' => $this->ssa_truncate_amount($this->player_balance, $this->precision),
                ],
            ];
        }

        return $this->setResponse($http_response_status_code, $operator_response);
    }

    private function rollbackStake() {
        $this->api_method = __function__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $this->is_end_round = true;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
            'amount' => ['required', 'nullable', 'numeric'],
            'gameId' => ['required'],
            'roundId' => ['required'],
            'originalTransactionId' => ['required'],
            'transactionId' => ['required'],
            'bonusAmount' => ['optional', 'nullable', 'numeric'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['required'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $success = false;
        $http_response_status_code = 500;
        $operator_response = $this->ssa_common_operator_response(500, 'Internal Server Error (rollbackStake default)');

        if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->ssa_request_params['transactionId']])) {
            $rollback_transaction = $this->ssa_get_transaction($this->transaction_table, [
                'transaction_type' => self::API_METHOD_ROLLBACKSTAKE,
                'original_transaction_id' => $this->ssa_request_params['originalTransactionId'],
            ]);

            if (!empty($rollback_transaction) && isset($rollback_transaction['transaction_id'])) {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Already rollback by transaction id: ' . $rollback_transaction['transaction_id']));
            }

            $is_win_exists = $this->ssa_is_transaction_exists($this->transaction_table, [
                'transaction_type' => self::API_METHOD_RETURNWIN,
                'game_username' => $this->ssa_request_params['user']['id'],
                'game_code' => $this->ssa_request_params['gameId'],
                'round_id' => $this->ssa_request_params['roundId'],
            ]);

            if ($is_win_exists) {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Already processed by returnWin'));
            }

            $bet_transaction = $this->ssa_get_transaction($this->transaction_table, [
                'transaction_type' => self::API_METHOD_GETSTAKE,
                'transaction_id' => $this->ssa_request_params['originalTransactionId'],
            ]);

            if (!empty($bet_transaction)) {
                if (isset($bet_transaction['game_code']) && $bet_transaction['game_code'] != $this->ssa_request_params['gameId']) {
                    return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Invalid gameId'));
                }

                if (isset($bet_transaction['round_id']) && $bet_transaction['round_id'] != $this->ssa_request_params['roundId']) {
                    return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Invalid roundId'));
                }
            } else {
                return $this->setResponse(400, $this->ssa_common_operator_response(400, 'Bet (originalTransactionId) not found'));
            }

            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$success, &$http_response_status_code, &$operator_response) {
                list($success, $http_response_status_code, $operator_response) = $controller->adjustWallet($this->ssa_increase, $this->ssa_insert, $success, $http_response_status_code, $operator_response);
                return $success;
            });
        } else {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['transaction_id' => $this->ssa_request_params['transactionId']]);
            $this->player_balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
        }

        if ($success) {
            $http_response_status_code = 200;
            $operator_response = [
                'status' => $this->ssa_api_success['code'],
                'funds' => [
                    'balance' => $this->ssa_truncate_amount($this->player_balance, $this->precision),
                ],
            ];
        }

        return $this->setResponse($http_response_status_code, $operator_response);
    }

    private function gameClose() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['optional'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $operator_response = [
            'status' => $this->ssa_api_success['code'],
        ];

        return $this->setResponse(200, $operator_response);
    }

    private function getFunds() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $operator_response = [
            'status' => $this->ssa_api_success['code'],
            'funds' => [
                'balance' => $this->ssa_truncate_amount($this->player_balance, $this->precision),
            ],
        ];

        return $this->setResponse(200, $operator_response);
    }

    private function rcClose() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
            'gameId' => ['optional'],
            'roundId' => ['optional'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['optional'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $operator_response = [
            'status' => $this->ssa_api_success['code'],
        ];

        return $this->setResponse(200, $operator_response);
    }

    private function rcContinue() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
            'gameId' => ['optional'],
            'roundId' => ['optional'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['optional'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $operator_response = [
            'status' => $this->ssa_api_success['code'],
        ];

        return $this->setResponse(200, $operator_response);
    }

    private function messageChoice() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params, [
            'user' => ['required', 'array'],
            'gameId' => ['optional'],
            'roundId' => ['optional'],
            'messageId' => ['optional'],
            'response' => ['optional'],
        ]);

        if (!$is_valid) {
            return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
        }

        if (isset($this->ssa_request_params['user'])) {
            $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['user'], [
                'id' => ['required'],
                'token' => ['optional'],
                'skinId' => ['optional'],
            ]);

            if (!$is_valid) {
                return $this->setResponse($this->ssa_http_response_status_code, $this->ssa_operator_response_custom_message($this->ssa_operator_response, $this->ssa_custom_message_response));
            }
        }

        list($is_valid, $http_response_status_code, $operator_response) = $this->validateRequestPlayer();

        if (!$is_valid) {
            return $this->setResponse($http_response_status_code, $operator_response);
        }

        $operator_response = [
            'status' => $this->ssa_api_success['code'],
        ];

        return $this->setResponse(200, $operator_response);
    }

    private function adjustWallet($adjustment_type, $query_type, $success, $http_response_status_code, $operator_response) {
        $before_balance = $after_balance = $this->player_balance;
        $amount = isset($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : 0;

        # Remote wallet API Start
        $this->ssa_set_uniqueid_of_seamless_service($this->utils->mergeArrayValues([
            $this->game_platform_id,
            $this->ssa_request_params['transactionId'],
        ]));

        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);
        $this->ssa_set_external_game_id($this->ssa_request_params['gameId']);
        $this->ssa_set_game_provider_round_id($this->ssa_request_params['roundId']);
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);

        // amount 0, call increase remote wallet
        if ($amount == 0 && $this->api_method == self::API_METHOD_RETURNWIN) {
            if ($this->ssa_enabled_remote_wallet()) {
                $this->utils->debug_log(__METHOD__, "{$this->game_api->seamless_game_api_name}: amount 0 call remote wallet", 'request_params', $this->ssa_request_params);
                $this->ssa_increase_remote_wallet($this->player_details['player_id'], $amount, $this->game_platform_id, $after_balance);
            } 
        }
        # Remote Wallet API End

        if ($adjustment_type == $this->ssa_decrease) {
            if ($amount > $before_balance) {
                $success = false;
                $http_response_status_code = 412;
                $operator_response = self::API_ERROR_8;
            } else {
                if ($amount == 0) {
                    $success = true;
                    $after_balance = $before_balance;
                } else {
                    $after_balance = null;
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);

                    if (!$success) {
                        $http_response_status_code = 412;
                        $operator_response = self::API_ERROR_8;
                    }
                }
            }
        } elseif ($adjustment_type == $this->ssa_increase) {
            if ($amount == 0) {
                $success = true;
                $after_balance = $before_balance;
            } else {
                $after_balance = null;
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);

                if (!$success) {
                    $http_response_status_code = 500;
                    $operator_response = $this->ssa_common_operator_response($http_response_status_code, 'Internal Server Error (ssa_increase_player_wallet)');
                }
            }
        } else {
            $success = false;
            $http_response_status_code = 500;
            $operator_response = $this->ssa_common_operator_response($http_response_status_code, 'Internal Server Error (adjustWallet default)');
        }

        $after_balance = $this->player_balance = !empty($after_balance) ? $after_balance : $this->ssa_get_player_balance($this->game_api, $this->player_details['username']);

        $result = [
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
        ];

        if ($success) {
            $http_response_status_code = 200;
            $operator_response = $this->ssa_common_operator_response($http_response_status_code);
            $this->processed_transaction_id = $this->preprocessRequestData($query_type, $result);

            if (!$this->processed_transaction_id) {
                $success = false;
                $http_response_status_code = 500;
                $operator_response = $this->ssa_common_operator_response($http_response_status_code, 'Internal Server Error (preprocessRequestData)');
            } else {
                if ($query_type == $this->ssa_insert) {
                    array_push($this->processed_multiple_transaction, $this->processed_transaction_id);
                }
            }
        }

        return array($success, $http_response_status_code, $operator_response);
    }

    private function validateRequestPlayer() {
        if (isset($this->ssa_request_params['token'])) {
            $token = $this->ssa_request_params['token'];
        } else {
            if (isset($this->ssa_request_params['user']['token'])) {
                $token = $this->ssa_request_params['user']['token'];
            } else {
                $token = null;
            }
        }

        if (in_array($this->api_method, self::TOKEN_REQUIRED_API_METHODS)) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $token, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->message_type_response = self::INTRUSIVE_MESSAGE_CONFIRM;
                return array(false, 400, self::API_ERROR_1);
            }
        } else {
            if (isset($token)) {
                $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $token, $this->game_platform_id);
            }

            if (empty($this->player_details)) {
                if (isset($this->ssa_request_params['user']['id'])) {
                    $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $this->ssa_request_params['user']['id'], $this->game_platform_id);

                    if (empty($this->player_details)) {
                        return array(false, 400, $this->ssa_common_operator_response(400, 'Invalid User Id ' . $this->ssa_request_params['user']['id']));
                    }
                }
            }
        }

        if (isset($this->ssa_request_params['user']['id'])) {
            if ($this->player_details['game_username'] != $this->ssa_request_params['user']['id']) {
                $this->message_type_response = self::INTRUSIVE_MESSAGE_CONFIRM;
                return array(false, 400, $this->ssa_common_operator_response(400, 'Invalid User Id ' . $this->ssa_request_params['user']['id']));
            }
        }

        if (in_array($this->api_method, $this->game_api_player_blocked_validate_api_methods)) {
            if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
                return array(false, 401, $this->ssa_common_operator_response(401, 'Player is blocked'));
            }
        }

        $this->player_balance = $this->ssa_get_player_balance($this->game_api, $this->player_details['username']);

        return array(true, 200, $this->ssa_api_success);
    }

    private function preprocessRequestData($query_type, $transaction_data) {
        $processed_transaction_id = null;
        $transacion_id = isset($this->ssa_request_params['transactionId']) ? $this->ssa_request_params['transactionId'] : null;
        $is_round_end = isset($this->ssa_request_params['round']['endRound']) ? $this->ssa_request_params['round']['endRound'] : null;
        $field_id = 'external_unique_id';

        if (isset($this->ssa_request_params['token'])) {
            $token = $this->ssa_request_params['token'];
        } else {
            if (isset($this->ssa_request_params['user']['token'])) {
                $token = $this->ssa_request_params['user']['token'];
            } else {
                $token = null;
            }
        }

        if ($query_type == $this->ssa_insert) {
            $update_with_result = false;

            $processed_data = [
                // default
                'game_platform_id' => $this->game_platform_id,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => isset($this->ssa_request_params['user']['id']) ? $this->ssa_request_params['user']['id'] : null,
                'language' => $this->language,
                'currency' => $this->currency,
                'transaction_type' => $this->api_method,
                'transaction_id' => $transacion_id,
                'game_code' => isset($this->ssa_request_params['gameId']) ? $this->ssa_request_params['gameId'] : null,
                'round_id' => isset($this->ssa_request_params['roundId']) ? $this->ssa_request_params['roundId'] : null,
                'amount' => isset($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : 0,
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'status' => $this->setStatus($this->api_method, $is_round_end),
                'start_at' => $this->utils->getNowForMysql(),
                'end_at' => $this->utils->getNowForMysql(),
                // optional
                'skin_id' => isset($this->ssa_request_params['skinId']) ? $this->ssa_request_params['skinId'] : null,
                'token' => $token,
                'type' => isset($this->ssa_request_params['type']) ? $this->ssa_request_params['type'] : null,
                'end_round' => $is_round_end,
                'free_spin' => isset($this->ssa_request_params['freeSpin']) ? $this->ssa_request_params['freeSpin'] : null,
                'free_round' => isset($this->ssa_request_params['freeRound']) ? $this->ssa_request_params['freeRound'] : null,
                'last_free_spin' => isset($this->ssa_request_params['round']['lastFreeSpin']) ? $this->ssa_request_params['round']['lastFreeSpin'] : null,
                'last_free_round' => isset($this->ssa_request_params['round']['lastFreeRound']) ? $this->ssa_request_params['round']['lastFreeRound'] : null,
                'cash_drop_id' => isset($this->ssa_request_params['cashDropId']) ? $this->ssa_request_params['cashDropId'] : null,
                'prize_id' => isset($this->ssa_request_params['prizeId']) ? $this->ssa_request_params['prizeId'] : null,
                'promotion_id' => isset($this->ssa_request_params['promotionId']) ? $this->ssa_request_params['promotionId'] : null,
                'bet_transaction_id' => isset($this->ssa_request_params['round']['betTransactionId']) ? $this->ssa_request_params['round']['betTransactionId'] : null,
                'original_transaction_id' => isset($this->ssa_request_params['originalTransactionId']) ? $this->ssa_request_params['originalTransactionId'] : null,
                'bonus_amount' => isset($this->ssa_request_params['bonusAmount']) ? $this->ssa_request_params['bonusAmount'] : 0,
                'free_round_info' => isset($this->ssa_request_params['freeRoundInfo']) ? json_encode($this->ssa_request_params['freeRoundInfo']) : null,
                'cash_drop_info' => isset($this->ssa_request_params['cashDropInfo']) ? json_encode($this->ssa_request_params['cashDropInfo']) : null,
                'round_info' => isset($this->ssa_request_params['roundInfo']) ? json_encode($this->ssa_request_params['roundInfo']) : null,
                // default
                'elapsed_time' => intval($this->utils->getExecutionTimeToNow() * 1000),
                'extra_info' => json_encode($this->ssa_request_params),
                'bet_amount' => 0,
                'win_amount' => 0,
                'result_amount' => 0,
                'flag_of_updated_result' => $this->ssa_flag_not_updated,
                'external_unique_id' => $transacion_id,
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        } else {
            $update_with_result = true;

            $processed_data = [
                'transaction_type' => $this->api_method,
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'amount' => isset($this->ssa_request_params['amount']) ? $this->ssa_request_params['amount'] : 0,
                'end_at' => $this->utils->getNowForMysql(),
                'updated_at' => $this->utils->getNowForMysql(),
                'status' => $this->setStatus($this->api_method, $is_round_end),
                'bet_amount' => 0,
                'win_amount' => 0,
                'result_amount' => 0,
                'flag_of_updated_result' => $this->ssa_flag_not_updated,
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
        }

        $processed_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $processed_data, $field_id, $transacion_id, $update_with_result);

        return $processed_transaction_id;
    }

    private function setStatus($action, $status) {
        switch ($action) {
            case self::API_METHOD_GETSTAKE:
            case self::API_METHOD_RETURNWIN:
                if ($status) {
                    $result = Game_logs::STATUS_SETTLED;
                } else {
                    $result = Game_logs::STATUS_PENDING;
                }
                break;
            case self::API_METHOD_ROLLBACKSTAKE:
                $result = Game_logs::STATUS_REFUND;
                break;
            default:
                $result = Game_logs::STATUS_PENDING;
                break;
        }

        return $result;
    }

    private function rebuildOperatorResponse($operator_response) {
        $status_code = isset($operator_response['code']) ? $operator_response['code'] : null;

        $new_operator_response = [
            'status' => $status_code,
            'message' => [
                'type' => $this->message_type_response,
                'header' => 'Status: ' . $status_code,
                'text' => isset($operator_response['message']) ? $operator_response['message'] : null,
            ],
        ];

        return $new_operator_response;
    }

    private function setResponse($http_response_status_code, $operator_response) {
        $flag = $http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $http_response = $this->ssa_get_http_response($http_response_status_code);
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;

        if ($flag == Response_result::FLAG_ERROR) {
            $operator_response = $this->rebuildOperatorResponse($operator_response);
        }

        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $operator_response, $http_response, $player_id);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];
    
                $this->ssa_update_transaction_without_result($this->transaction_table, $updated_data, 'id', $processed_transaction_id);
            }
        }

        return $this->returnJsonResult($operator_response, true, '*', false, false, $http_response_status_code);
    }
}