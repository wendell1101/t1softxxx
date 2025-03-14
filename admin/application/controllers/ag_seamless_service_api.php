<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Ag_seamless_service_api extends BaseController {
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
    // additional
    private $product_id;
    private $use_error_response_description;
    private $transaction_id;
    private $real_game_type;

    const SEAMLESS_GAME_API = AG_SEAMLESS_GAME_API;

    const API_RESPONSE_OK = [
        'http_status_code' => 200,
        'response_code' => 'OK',
        'description' => 'Call was successful. Transaction was processed and accepted.',
    ];

    const API_RESPONSE_INVALID_DATA = [
        'http_status_code' => 400,
        'response_code' => 'INVALID_DATA',
        'description' => 'The request was invalid.',
    ];

    const API_RESPONSE_INCORRECT_SESSION_TYPE = [
        'http_status_code' => 403,
        'response_code' => 'INCORRECT_SESSION_TYPE',
        'description' => 'The profile was requested with the incorrect session type.',
    ];

    const API_RESPONSE_INVALID_SESSION = [
        'http_status_code' => 404,
        'response_code' => 'INVALID_SESSION',
        'description' => 'The session does not exist.',
    ];

    const API_RESPONSE_INVALID_TRANSACTION = [
        'http_status_code' => 404,
        'response_code' => 'INVALID_TRANSACTION',
        'description' => 'The transferee or the transaction ID cannot be found.',
    ];

    const API_RESPONSE_INSUFFICIENT_FUNDS = [
        'http_status_code' => 409,
        'response_code' => 'INSUFFICIENT_FUNDS',
        'description' => 'The client has insufficient funds to handle the transaction.',
    ];

    const API_RESPONSE_INSUFFICIENT_CLEARED_FUNDS = [
        'http_status_code' => 409,
        'response_code' => 'INSUFFICIENT_CLEARED_FUNDS',
        'description' => 'The client has enough balance but not enough cleared funds.',
    ];

    const API_RESPONSE_ERROR = [
        'http_status_code' => 500,
        'response_code' => 'ERROR',
        'description' => 'There was an error on the server while trying to fulfill the request. This will trigger the message resend mechanism.',
    ];

    const REAL_GAME_TYPE_LIVE_DEALER = 'live_dealer';
    const REAL_GAME_TYPE_SLOTS = 'slots';

    const API_ENDPOINT_POST_TRANSFER = 'postTransfer';
    const API_ENDPOINT_BET_RESPONSE = 'betResponse';
    const API_ENDPOINT_SLOT = 'slot';

    const ALLOWED_API_ENDPOINTS = [
        self::API_ENDPOINT_POST_TRANSFER,
        self::API_ENDPOINT_BET_RESPONSE,
        self::API_ENDPOINT_SLOT,
    ];

    // live_dealer
    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_WIN = 'win';
    const TRANSACTION_TYPE_LOSE = 'lose';
    const TRANSACTION_TYPE_REFUND = 'refund';

    // slots
    const TRANSACTION_TYPE_BALANCE = 'balance';
    const TRANSACTION_TYPE_WITHDRAW = 'withdraw';
    const TRANSACTION_TYPE_DEPOSIT = 'deposit';
    const TRANSACTION_TYPE_ROLLBACK = 'rollback';

    const POST_TRANSFER_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_WIN,
        self::TRANSACTION_TYPE_LOSE,
        self::TRANSACTION_TYPE_REFUND,
    ];

    const SLOT_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
        self::TRANSACTION_TYPE_DEPOSIT,
        self::TRANSACTION_TYPE_ROLLBACK,
    ];

    const ALLOWED_TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_WIN,
        self::TRANSACTION_TYPE_LOSE,
        self::TRANSACTION_TYPE_REFUND,
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
        self::TRANSACTION_TYPE_DEPOSIT,
        self::TRANSACTION_TYPE_ROLLBACK,
    ];

    const WHITELIST_IP_VALIDATE_API_METHODS = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_WIN,
        self::TRANSACTION_TYPE_LOSE,
        self::TRANSACTION_TYPE_REFUND,
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
        self::TRANSACTION_TYPE_DEPOSIT,
        self::TRANSACTION_TYPE_ROLLBACK,
    ];

    const GAME_API_ACTIVE_VALIDATE_API_METHODS = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
    ];

    const GAME_API_MAINTENANCE_VALIDATE_API_METHODS = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
    ];

    const GAME_API_PLAYER_BLOCKED_VALIDATE_API_METHODS = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
    ];

    const ADDITIONAL_VALIDATION_API_METHODS = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_WIN,
        self::TRANSACTION_TYPE_LOSE,
        self::TRANSACTION_TYPE_REFUND,
        self::TRANSACTION_TYPE_WITHDRAW,
        self::TRANSACTION_TYPE_DEPOSIT,
        self::TRANSACTION_TYPE_ROLLBACK,
    ];

    const TOKEN_REQUIRED_API_METHODS = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_BALANCE,
        self::TRANSACTION_TYPE_WITHDRAW,
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
        'play_name',
        'agent_code',
        'platform_type',
        'round',
        'real_game_type',
        'game_type',
        'play_type',
        'bill_no',
        'table_code',
        'transaction_code',
        'ticket_status',
        'net_amount',
        'valid_bet_amount',
        'game_result',
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
        'net_amount',
        'valid_bet_amount',
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

    private $remoteWallet = [
		'action_type' => 'bet',
		'round_id' => null,
		'is_end' => false,
		'bet_amount' => 0,
		'payout_amount' => 0,
		'external_game_id' => null
	];

    public function __construct() {
        parent::__construct();
        $this->ssa_init();
        $this->ssa_http_response_status_code = 500;
        $this->ssa_operator_response = [];
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->player_details = $this->extra_transaction = $this->processed_multiple_transaction = [];
        $this->game_api = $this->external_unique_id = $this->processed_transaction_id = $this->response_result_id = $this->transaction_id = $this->real_game_type = null;
        $this->api_method = 'index';
        $this->player_balance = 0;
        $this->precision = 4;
        $this->conversion = 1;
    }

    public function index($game_platform_id, $endpoint) {
        $this->ssa_request_params = !empty($this->ssa_request_params()) ? $this->ssa_request_params() : $this->requestArrayFromXml();

        if (in_array($endpoint, self::ALLOWED_API_ENDPOINTS)) {
            $this->game_platform_id = $game_platform_id;
            $this->api_method = $endpoint;
            return $this->$endpoint();
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
            return $this->response();
        }
    }

    private function initialize() {
        if (empty($this->game_platform_id)) {
            $this->ssa_operator_response = self::API_RESPONSE_ERROR;
            return false;
        }

        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);

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

            // additional
            $this->product_id = $this->game_api->product_id;
            $this->use_error_response_description = $this->game_api->use_error_response_description;

            if (in_array($this->api_method, $this->whitelist_ip_validate_api_methods)) {
                if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                    $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
                    $this->ssa_custom_message_response = 'IP address is not allowed';
                    return false;
                }
            }

            if (in_array($this->api_method, $this->game_api_active_validate_api_methods)) {
                if (!$this->ssa_is_game_api_active($this->game_api)) {
                    $this->ssa_operator_response = self::API_RESPONSE_INVALID_SESSION;
                    $this->ssa_custom_message_response = 'Game is disabled';
                    return false;
                }
            }

            if (in_array($this->api_method, $this->game_api_maintenance_validate_api_methods)) {
                if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                    $this->ssa_operator_response = self::API_RESPONSE_INVALID_SESSION;
                    $this->ssa_custom_message_response = 'Maintenance';
                    return false;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_ERROR;
            $this->ssa_custom_message_response = 'Game platform id not found';
            return false;
        }

        $class_methods = get_class_methods(get_class($this));

        if ($this->ssa_is_api_method_not_found($class_methods, $this->api_method)) {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
            $this->ssa_custom_message_response = 'Transaction type not found';
            return false;
        }

        if ($this->ssa_is_api_method_allowed($this->api_method, self::ALLOWED_TRANSACTION_TYPES)) {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
            $this->ssa_custom_message_response = 'Transaction type not allowed';
            return false;
        }

        return true;
    }

    private function additionalValidation() {
        if (in_array($this->api_method, self::ADDITIONAL_VALIDATION_API_METHODS)) {
            if (isset($this->ssa_request_params['Record']['agentCode']) && $this->ssa_request_params['Record']['agentCode'] != $this->product_id) {
                $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
                $this->ssa_custom_message_response = 'Invalid Agent Code';
                return false;
            }

            if (isset($this->ssa_request_params['Record']['currency']) && $this->ssa_request_params['Record']['currency'] != $this->currency) {
                $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
                $this->ssa_custom_message_response = 'Invalid Currency';
                return false;
            }
        }

        if (!$this->validateRequestPlayer()) {
            return false;
        }

        return true;
    }

    private function validateRequestPlayer() {
        $token = isset($this->ssa_request_params['Record']['sessionToken'])? $this->ssa_request_params['Record']['sessionToken'] : null;
        $game_username = isset($this->ssa_request_params['Record']['playname']) ? str_replace($this->product_id, '', $this->ssa_request_params['Record']['playname']) : null;

        if (in_array($this->api_method, self::TOKEN_REQUIRED_API_METHODS)) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $token, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_operator_response = self::API_RESPONSE_INVALID_SESSION;
                return false;
            }
        } else {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);

            if (empty($this->player_details)) {
                $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
                $this->ssa_custom_message_response = 'Invalid playname ' . $game_username;
                return false;
            }
        }

        if ($this->player_details['game_username'] != $game_username) {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
            $this->ssa_custom_message_response = 'Invalid playname ' . $game_username;
            return false;
        }

        if (in_array($this->api_method, $this->game_api_player_blocked_validate_api_methods)) {
            if ($this->ssa_is_player_blocked($this->game_api, $this->player_details['username'])) {
                $this->ssa_operator_response = self::API_RESPONSE_INVALID_SESSION;
                return false;
            }
        }

        $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

        return true;
    }

    private function postTransfer() {
        $this->api_method = __FUNCTION__;
        $this->real_game_type = self::REAL_GAME_TYPE_LIVE_DEALER;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'transactionType' => ['required'], // transaction_type
        ]);

        if ($is_valid) {
            $this->ssa_request_params['Record']['transactionType'] = strtolower($this->ssa_request_params['Record']['transactionType']);
            $this->api_method = $api_method = $this->ssa_request_params['Record']['transactionType'];

            if (in_array($this->api_method, self::POST_TRANSFER_TRANSACTION_TYPES)) {
                if ($this->initialize()) {
                    $api_method = $this->api_method;
                    return $this->$api_method();
                }
            } else {
                $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
                $this->ssa_custom_message_response = 'Transaction type not found';
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function slot() {
        $this->api_method = __FUNCTION__;
        $this->real_game_type = self::REAL_GAME_TYPE_SLOTS;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'transactionType' => ['required'], // transaction_type
        ]);

        if ($is_valid) {
            $this->ssa_request_params['Record']['transactionType'] = strtolower($this->ssa_request_params['Record']['transactionType']);
            $this->api_method = $api_method = $this->ssa_request_params['Record']['transactionType'];

            if (in_array($this->api_method, self::SLOT_TRANSACTION_TYPES)) {
                if ($this->initialize()) {
                    $api_method = $this->api_method;
                    return $this->$api_method();
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function bet() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'currency' => ['required'],
            'value' => ['required', 'nullable', 'numeric'], // amount
            'playname' => ['required'], // game_username
            'agentCode' => ['required'],
            'betTime' => ['optional'],
            'transactionID' => ['required'],
            'platformType' => ['optional'],
            'round' => ['optional'],
            'gametype' => ['required'], // game_code 2
            'gameCode' => ['required'], // round_id
            'tableCode' => ['required'], // game_code
            'transactionType' => ['required'],
            'transactionCode' => ['optional'],
            'deviceType' => ['optional'],
            'playtype' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on bet method';
                $this->external_unique_id = $this->generateExternalUniqueId();

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $controller = $this;
                    $request = $this->ssa_request_params;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $request) {
                        $externalGameId = isset($request['Record']['gameCode']) ? $request['Record']['gameCode'] : null;
                        $betAmount = isset($request['Record']['value']) ? $request['Record']['value'] : 0;
                        $payoutAmount = 0;
                        $this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                        $this->remoteWallet['is_end'] = false;
                        $this->remoteWallet['round_id'] = $externalGameId;
                        $this->remoteWallet['bet_amount'] = $betAmount;
                        $this->remoteWallet['payout_amount'] = $payoutAmount;
                        $this->remoteWallet['external_game_id'] = $externalGameId;
                        return $controller->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->ssa_request_params['Record']['value']);
                    });
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function win() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'currency' => ['required'],
            'netAmount' => ['required', 'nullable', 'numeric'], // amount
            'validBetAmount' => ['required', 'nullable', 'numeric'], // amount
            'playname' => ['required'], // game_username
            'agentCode' => ['required'],
            'settletime' => ['optional'],
            'transactionID' => ['required'],
            'billNo' => ['optional'],
            'gametype' => ['required'],
            'gameCode' => ['required'], // round_id
            'transactionType' => ['required'],
            'transactionCode' => ['optional'],
            'ticketStatus' => ['optional'],
            'gameResult' => ['optional'],
            'finish' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on win method';
                $this->external_unique_id = $this->generateExternalUniqueId();
                $bet_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_BET);
                $lose_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_LOSE);
                $refund_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_REFUND);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $is_bet_exist = false;

                    // check bet exist
                    if (is_array($this->ssa_request_params['Record']['transactionID'])) {
                        foreach ($this->ssa_request_params['Record']['transactionID'] as $transaction_id) {
                            $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => self::TRANSACTION_TYPE_BET . '-' . $transaction_id]);

                            if (empty($bet_transaction)) {
                                $is_bet_exist = false;
                                break;
                            } else {
                                $is_bet_exist = true;
                            }
                        }
                    } else {
                        $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $bet_external_unique_id]);

                        if (!empty($bet_transaction)) {
                            $is_bet_exist = true;
                        }
                    }

                    if ($is_bet_exist) {
                        $this->ssa_request_params['Record']['tableCode'] = !empty($bet_transaction['game_code']) ? $bet_transaction['game_code'] : $this->ssa_request_params['Record']['gametype'];

                        if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $lose_external_unique_id])) {
                            if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $refund_external_unique_id])) {
                                $controller = $this;
                                $request = $this->ssa_request_params;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $request) {
                                    $amount = $this->ssa_request_params['Record']['netAmount'] + $this->ssa_request_params['Record']['validBetAmount'];

                                    $gameUniqueId = isset($request['Record']['gameCode']) ? $request['Record']['gameCode'] : null;
                                    $this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                                    $this->remoteWallet['is_end'] = true;
                                    $this->remoteWallet['round_id'] = $gameUniqueId;
                                    $this->remoteWallet['bet_amount'] = 0;
                                    $this->remoteWallet['payout_amount'] = $amount;
                                    $this->remoteWallet['external_game_id'] = $gameUniqueId;
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $amount);
                                });
                            } else {
                                $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                                $this->ssa_custom_message_response = 'already processed by refund';
                            }
                        } else {
                            $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                            $this->ssa_custom_message_response = 'already processed by lose';
                        }
                    } else {
                        $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                        $this->ssa_custom_message_response = 'bet not found';
                    }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function lose() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'currency' => ['required'],
            'netAmount' => ['required', 'nullable', 'numeric'], // amount
            'validBetAmount' => ['required', 'nullable', 'numeric'], // amount
            'playname' => ['required'], // game_username
            'agentCode' => ['required'],
            'settletime' => ['optional'],
            'transactionID' => ['required'],
            'billNo' => ['optional'],
            'gametype' => ['required'],
            'gameCode' => ['required'], // round_id
            'transactionType' => ['required'],
            'transactionCode' => ['optional'],
            'ticketStatus' => ['optional'],
            'gameResult' => ['optional'],
            'finish' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on lose method';
                $this->external_unique_id = $this->generateExternalUniqueId();
                $bet_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_BET);
                $win_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_WIN);
                $refund_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_REFUND);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $bet_external_unique_id]);

                    if (!empty($bet_transaction)) {
                        $this->ssa_request_params['Record']['tableCode'] = !empty($bet_transaction['game_code']) ? $bet_transaction['game_code'] : $this->ssa_request_params['Record']['gametype'];

                        if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $win_external_unique_id])) {
                            if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $refund_external_unique_id])) {
                                $controller = $this;
                                $request = $this->ssa_request_params;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $request) {
                                    $amount = $this->ssa_request_params['Record']['netAmount'] + $this->ssa_request_params['Record']['validBetAmount'];
                                    $gameUniqueId = isset($request['Record']['gameCode']) ? $request['Record']['gameCode'] : null;
                                    $this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
                                    $this->remoteWallet['is_end'] = true;
                                    $this->remoteWallet['round_id'] = $gameUniqueId;
                                    $this->remoteWallet['external_game_id'] = $gameUniqueId;
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $amount);
                                });
                            } else {
                                $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                                $this->ssa_custom_message_response = 'already processed by refund';
                            }
                        } else {
                            $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                            $this->ssa_custom_message_response = 'already processed by win';
                        }
                    } else {
                        $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                        $this->ssa_custom_message_response = 'bet not found';
                    }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function refund() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'currency' => ['required'],
            'value' => ['required', 'nullable', 'numeric'], // amount
            'playname' => ['required'], // game_username
            'agentCode' => ['required'],
            'betTime' => ['optional'],
            'transactionID' => ['required'],
            'billNo' => ['optional'],
            'platformType' => ['optional'],
            'round' => ['optional'],
            'gametype' => ['required'], // game_code 2
            'gameCode' => ['required'], // round_id
            'tableCode' => ['required'], // game_code
            'transactionType' => ['required'],
            'transactionCode' => ['optional'],
            'ticketStatus' => ['optional'],
            'playtype' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on refund method';
                $this->external_unique_id = $this->generateExternalUniqueId();
                $bet_extertal_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_BET);
                $win_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_WIN);
                $lose_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_LOSE);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    if ($this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $bet_extertal_unique_id])) {
                        if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $win_external_unique_id])) {
                            if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $lose_external_unique_id])) {
                                $controller = $this;
                                $request = $this->ssa_request_params;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, $request) {
                                    $gameUniqueId = isset($request['Record']['gameCode']) ? $request['Record']['gameCode'] : null;
                                    $amount = isset($request['Record']['value']) ? $request['Record']['value'] : 0;
                                    $this->remoteWallet['action_type'] = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
                                    $this->remoteWallet['is_end'] = true;
                                    $this->remoteWallet['round_id'] = $gameUniqueId;
                                    $this->remoteWallet['bet_amount'] = 0;
                                    $this->remoteWallet['payout_amount'] = $amount;
                                    $this->remoteWallet['external_game_id'] = $gameUniqueId;
                                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->ssa_request_params['Record']['value']);
                                });
                            } else {
                                $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                                $this->ssa_custom_message_response = 'already processed by lose';
                            }
                        } else {
                            $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                            $this->ssa_custom_message_response = 'already processed by win';
                        }
                    } else {
                        $success = true;
                    }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function balance() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'playname' => ['required'],
            'transactionType' => ['required'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $this->ssa_operator_response = self::API_RESPONSE_OK;
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function withdraw() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'playname' => ['required'], // game_username
            'transactionType' => ['required'], // transaction_type
            'transactionID' => ['required'], // transaction_id
            'currency' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'], // amount
            'gameId' => ['required'], // game_code
            'roundid' => ['optional'], // round_id
            'time' => ['optional'],
            'remark' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on withdraw method';
                $this->external_unique_id = $this->generateExternalUniqueId();

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $controller = $this;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                        return $controller->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->ssa_request_params['Record']['amount']);
                    });
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function deposit() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'playname' => ['required'], // game_username
            'transactionType' => ['required'], // transaction_type
            'transactionID' => ['required'], // transaction_id
            'currency' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'], // amount
            'gameId' => ['required'], // game_code
            'roundId' => ['required'], // round_id
            'time' => ['optional'],
            'remark' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on deposit method';
                $this->external_unique_id = $this->generateExternalUniqueId();

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    if ($this->ssa_is_transaction_exists($this->transaction_table, [
                        'transaction_type' => self::TRANSACTION_TYPE_WITHDRAW,
                        'game_username' => $this->player_details['game_username'],
                        'game_code' => $this->ssa_request_params['Record']['gameId'],
                        'round_id' => $this->ssa_request_params['Record']['roundId'],
                    ])) {
                        if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                            'transaction_type' => self::TRANSACTION_TYPE_ROLLBACK,
                            'game_username' => $this->player_details['game_username'],
                            'game_code' => $this->ssa_request_params['Record']['gameId'],
                            'round_id' => $this->ssa_request_params['Record']['roundId'],
                        ])) {
                            $controller = $this;
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                                return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->ssa_request_params['Record']['amount']);
                            });
                        } else {
                            $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                            $this->ssa_custom_message_response = 'already processed by rollback';
                        }
                    } else {
                        $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                        $this->ssa_custom_message_response = 'bet not found';
                    }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function rollback() {
        $this->api_method = __FUNCTION__;

        $is_valid = $this->ssa_validate_request_params($this->ssa_request_params['Record'], [
            'sessionToken' => ['required'],
            'playname' => ['required'], // game_username
            'transactionType' => ['required'], // transaction_type
            'transactionID' => ['required'], // transaction_id
            'currency' => ['required'],
            'amount' => ['required', 'nullable', 'numeric'], // amount
            'gameId' => ['required'], // game_code
            'roundId' => ['required'], // round_id
            'time' => ['optional'],
            'remark' => ['optional'],
        ]);

        if ($is_valid) {
            if ($this->additionalValidation()) {
                $success = false;
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on rollback method';
                $this->external_unique_id = $this->generateExternalUniqueId();
                $bet_external_unique_id = $this->generateExternalUniqueId(self::TRANSACTION_TYPE_WITHDRAW);

                if (!$this->ssa_is_transaction_exists($this->transaction_table, ['external_unique_id' => $this->external_unique_id])) {
                    $bet_transaction = $this->ssa_get_transaction($this->transaction_table, ['external_unique_id' => $bet_external_unique_id]);

                    if (!empty($bet_transaction)) {
                        $this->ssa_request_params['Record']['tableCode'] = !empty($bet_transaction['game_code']) ? $bet_transaction['game_code'] : $this->ssa_request_params['Record']['gametype'];

                        if (!$this->ssa_is_transaction_exists($this->transaction_table, [
                            'transaction_type' => self::TRANSACTION_TYPE_DEPOSIT,
                            'game_username' => $this->player_details['game_username'],
                            'game_code' => $this->ssa_request_params['Record']['gameId'],
                            'round_id' => $this->ssa_request_params['Record']['roundId'],
                        ])) {
                            $controller = $this;
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller) {
                                return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->ssa_request_params['Record']['amount']);
                            });
                        } else {
                            $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                            $this->ssa_custom_message_response = 'already processed by deposit';
                        }
                    } else {
                        $this->ssa_operator_response = self::API_RESPONSE_INVALID_TRANSACTION;
                        $this->ssa_custom_message_response = 'bet not found';
                    }
                } else {
                    $success = true;
                }

                if ($success) {
                    $this->ssa_operator_response = self::API_RESPONSE_OK;
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_INVALID_DATA;
        }

        return $this->response();
    }

    private function generateExternalUniqueId($transaction_type = null) {
        $api_method = !empty($transaction_type) ? $transaction_type : $this->api_method;
        $external_unique_id = [$api_method];
        $transaction_ids = [];

        if (is_array($this->ssa_request_params['Record']['transactionID'])) {
            foreach ($this->ssa_request_params['Record']['transactionID'] as $transaction_id) {
                array_push($external_unique_id, $transaction_id);
                array_push($transaction_ids, $transaction_id);
            }
        } else {
            array_push($external_unique_id, $this->ssa_request_params['Record']['transactionID']);
        }

        /* if (!empty($this->ssa_request_params['Record']['billNo'])) {
            array_push($external_unique_id, $this->ssa_request_params['Record']['billNo']);
        }

        if (!empty($this->ssa_request_params['Record']['roundid'])) {
            array_push($external_unique_id, $this->ssa_request_params['Record']['roundid']);
        } */

        $this->transaction_id = !empty($transaction_ids) ? $this->ssa_composer($transaction_ids) : $this->ssa_request_params['Record']['transactionID'];

        return $this->ssa_composer($external_unique_id);
    }

    private function walletAdjustment($adjustment_type, $query_type, $amount) {
        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);
        $before_balance = $after_balance = $this->player_balance;

        if ($adjustment_type == $this->ssa_decrease) {
            if ($amount > $before_balance) {
                $this->ssa_operator_response = self::API_RESPONSE_INSUFFICIENT_FUNDS; // Insufficient balance
                $success = false;
            } else {
                if ($this->utils->compareResultFloat($amount, '=', 0)) {
                    $success = true;
                } else {
                    $uniqueid =  $this->game_platform_id.'-'.$this->external_unique_id;

                    if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
                        $this->wallet_model->setUniqueidOfSeamlessService($uniqueid);   
                    }
                    
                    if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                        $this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
                    }
                    
                    if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                        $this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
                    }
                    
                    if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                        $this->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
                    }
                    
                    if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
                        $this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
                    }
                    
                    if (method_exists($this->wallet_model, 'setExternalGameId')) {
                        $this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
                    }
                    
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);

                    if ($success) {
                        $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                    } else {
                        $this->ssa_operator_response = self::API_RESPONSE_INSUFFICIENT_FUNDS; // Insufficient balance
                    }
                }
            }
        } elseif ($adjustment_type == $this->ssa_increase) {
            if ($this->utils->compareResultFloat($amount, '=', 0)) {
                $uniqueid =  $this->game_platform_id.'-'.$this->external_unique_id;
                if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid);   
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                    $this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                    $this->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
                    $this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
                }
                
                if (method_exists($this->wallet_model, 'setExternalGameId')) {
                    $this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
                }
                $success = true;
            } else {
                $uniqueid =  $this->game_platform_id.'-'.$this->external_unique_id;
                if(method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')){
                    $this->wallet_model->setUniqueidOfSeamlessService($uniqueid);   
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
                    $this->wallet_model->setGameProviderActionType($this->remoteWallet['action_type']);
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
                    $this->wallet_model->setGameProviderRoundId($this->remoteWallet['round_id']);
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderIsEndRound')) {
                    $this->wallet_model->setGameProviderIsEndRound($this->remoteWallet['is_end']);
                }
                
                if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
                    $this->wallet_model->setGameProviderBetAmount($this->remoteWallet['bet_amount']);
                }
                
                if (method_exists($this->wallet_model, 'setExternalGameId')) {
                    $this->wallet_model->setExternalGameId($this->remoteWallet['external_game_id']);
                }
                
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount);

                if ($success) {
                    $after_balance = $this->player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                } else {
                    $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                    $this->ssa_custom_message_response = 'error on increasing player wallet';
                }
            }
        } else {
            $this->ssa_operator_response = self::API_RESPONSE_ERROR;
            $this->ssa_custom_message_response = 'wallet adjustment type no found';
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
                $this->ssa_operator_response = self::API_RESPONSE_ERROR;
                $this->ssa_custom_message_response = 'error on processing request data';
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

        if (!empty($this->real_game_type) && $this->real_game_type == self::REAL_GAME_TYPE_LIVE_DEALER) {
            $game_code = !empty($this->ssa_request_params['Record']['tableCode']) ? $this->ssa_request_params['Record']['tableCode'] : null;
            $round_id = !empty($this->ssa_request_params['Record']['gameCode']) ? $this->ssa_request_params['Record']['gameCode'] : null;
        } else {
            $game_code = !empty($this->ssa_request_params['Record']['gameId']) ? $this->ssa_request_params['Record']['gameId'] : null;
            $round_id = !empty($this->ssa_request_params['Record']['roundId']) ? $this->ssa_request_params['Record']['roundId'] : null;
        }

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
                'transaction_id' => $this->transaction_id,
                'game_code' =>  $game_code,
                'round_id' => $round_id,
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'status' => $this->setStatus(),
                'start_at' => $this->utils->getNowForMysql(),
                'end_at' => $this->utils->getNowForMysql(),
                // optional
                'play_name' => isset($this->ssa_request_params['Record']['playname']) ? $this->ssa_request_params['Record']['playname'] : null,
                'agent_code' => isset($this->ssa_request_params['Record']['agentCode']) ? $this->ssa_request_params['Record']['agentCode'] : null,
                'platform_type' => isset($this->ssa_request_params['Record']['platformType']) ? $this->ssa_request_params['Record']['platformType'] : null,
                'round' => isset($this->ssa_request_params['Record']['round']) ? $this->ssa_request_params['Record']['round'] : null,
                'real_game_type' => $this->real_game_type,
                'game_type' => isset($this->ssa_request_params['Record']['gametype']) ? $this->ssa_request_params['Record']['gametype'] : null,
                'play_type' => isset($this->ssa_request_params['Record']['playtype']) ? $this->ssa_request_params['Record']['playtype'] : null,
                'bill_no' => isset($this->ssa_request_params['Record']['billNo']) ? $this->ssa_request_params['Record']['billNo'] : null,
                'table_code' => isset($this->ssa_request_params['Record']['tableCode']) ? $this->ssa_request_params['Record']['tableCode'] : null,
                'transaction_code' => isset($this->ssa_request_params['Record']['transactionCode']) ? $this->ssa_request_params['Record']['transactionCode'] : null,
                'ticket_status' => isset($this->ssa_request_params['Record']['ticketStatus']) ? $this->ssa_request_params['Record']['ticketStatus'] : null,
                'net_amount' => isset($this->ssa_request_params['Record']['netAmount']) ? $this->ssa_request_params['Record']['netAmount'] : 0,
                'valid_bet_amount' => isset($this->ssa_request_params['Record']['validBetAmount']) ? $this->ssa_request_params['Record']['validBetAmount'] : 0,
                'game_result' => isset($this->ssa_request_params['Record']['gameResult']) ? $this->ssa_request_params['Record']['gameResult'] : null,
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
            case self::TRANSACTION_TYPE_BET:
            case self::TRANSACTION_TYPE_WITHDRAW:
                $result = Game_logs::STATUS_PENDING;
                break;
            case self::TRANSACTION_TYPE_WIN:
            case self::TRANSACTION_TYPE_LOSE:
            case self::TRANSACTION_TYPE_DEPOSIT:
                $result = Game_logs::STATUS_SETTLED;
                break;
            case self::TRANSACTION_TYPE_REFUND:
            case self::TRANSACTION_TYPE_ROLLBACK:
                $result = Game_logs::STATUS_REFUND;
                break;
            default:
                $result = Game_logs::STATUS_UNSETTLED;
                break;
        }

        return $result;
    }

    private function rebuildOperatorResponse() {
        $this->ssa_http_response_status_code = isset($this->ssa_operator_response['http_status_code']) ? $this->ssa_operator_response['http_status_code'] : 503;
        $response_code = isset($this->ssa_operator_response['response_code']) ? $this->ssa_operator_response['response_code'] : null;
        $description = isset($this->ssa_operator_response['description']) ? $this->ssa_operator_response['description'] : null;

        if ($this->ssa_http_response_status_code == self::API_RESPONSE_OK['http_status_code']) {
            $operator_response = [
                'TransferResponse' => [
                    'ResponseCode' => $response_code,
                    'Balance' => $this->ssa_operate_amount($this->player_balance, $this->precision, $this->conversion, $this->arithmetic_name),
                ],
            ];
        } else {
            $operator_response = [
                'TransferResponse' => [
                    'ResponseCode' => $response_code,
                ],
            ];

            if ($this->use_error_response_description) {
                $operator_response['TransferResponse']['Description'] = !empty($this->ssa_custom_message_response) ? $this->ssa_custom_message_response : $description;
            }
        }

        return $operator_response;
    }

    private function response() {
        $this->ssa_operator_response = $this->rebuildOperatorResponse();
        $http_response = $this->ssa_get_http_response($this->ssa_http_response_status_code);
        $flag = $this->ssa_http_response_status_code == 200 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $this->response_result_id = $this->ssa_save_response_result($this->game_platform_id, $flag, $this->api_method, $this->ssa_request_params, $this->ssa_operator_response, $http_response, $player_id);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];
    
                $this->ssa_update_transaction_without_result($this->transaction_table, $updated_data, 'id', $processed_transaction_id);
            }
        }

        return $this->outputXmlResponse($this->ssa_operator_response, $this->ssa_http_response_status_code, $this->response_result_id, $player_id);
    }
}