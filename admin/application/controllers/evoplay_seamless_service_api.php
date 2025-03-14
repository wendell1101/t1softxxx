<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';

class Evoplay_seamless_service_api extends BaseController
{
    private $api;
    private $game_platform_id;
    private $project_id;
    private $callback_version;
    private $secret_key;
    private $balance;
    private $scope;
    private $no_refund;
    private $headers;
    private $request_uri_function;
    private $request_params;
    private $token;
    private $callback_id;
    private $refund_callback_id;
    private $callback_type;
    private $data;
    private $absolute_name;
    private $game_id;
    private $round_id;
    private $action_id;
    private $action;
    private $final_action;
    private $amount;
    private $currency;
    private $details;
    private $signature;
    private $external_transaction_id;
    private $reason;
    private $http_status_response;
    private $check_by_round_and_action;
    private $use_bet_extra_info_token;
    private $use_new_token;
    private $use_signature;
    private $use_is_win_exist_real_error;
    private $use_is_refund_exist_real_error;
    private $response_result_id;
    private $processed_transaction_id;
    private $external_unique_id;
    private $uniqueid_of_seamless_service;
    private $external_game_id;
    private $remote_Wallet_error_code;
    public $player;

    const SEAMLESS_GAME_API = EVOPLAY_SEAMLESS_GAME_API;

    const ERROR_REASON_UNKNOWN_ERROR = [
        'STATUS' => 'error',
        'CODE' => 0,
        'REASON' => 'Unknown Error'
    ];

    const ERROR_REASON_EMPTY_TOKEN = [
        'STATUS' => 'error',
        'CODE' => 1,
        'REASON' => 'Empty Token'
    ];

    const ERROR_REASON_EMPTY_CALLBACK_ID = [
        'STATUS' => 'error',
        'CODE' => 2,
        'REASON' => 'Empty Callback ID'
    ];

    const ERROR_REASON_EMPTY_REFUND_CALLBACK_ID = [
        'STATUS' => 'error',
        'CODE' => 3,
        'REASON' => 'Empty Refund Callback ID'
    ];

    const ERROR_REASON_EMPTY_CALLBACK_TYPE = [
        'STATUS' => 'error',
        'CODE' => 4,
        'REASON' => 'Empty Callback Type'
    ];

    const ERROR_REASON_EMPTY_DATA = [
        'STATUS' => 'error',
        'CODE' => 5,
        'REASON' => 'Empty Data'
    ];

    const ERROR_REASON_EMPTY_ROUND_ID = [
        'STATUS' => 'error',
        'CODE' => 6,
        'REASON' => 'Empty Round ID'
    ];

    const ERROR_REASON_EMPTY_REFUND_ROUND_ID = [
        'STATUS' => 'error',
        'CODE' => 7,
        'REASON' => 'Empty Refund Round ID'
    ];

    const ERROR_REASON_EMPTY_ACTION_ID = [
        'STATUS' => 'error',
        'CODE' => 8,
        'REASON' => 'Empty Action ID'
    ];

    const ERROR_REASON_EMPTY_REFUND_ACTION_ID = [
        'STATUS' => 'error',
        'CODE' => 9,
        'REASON' => 'Empty Refund Action ID'
    ];

    const ERROR_REASON_EMPTY_AMOUNT = [
        'STATUS' => 'error',
        'CODE' => 10,
        'REASON' => 'Empty Amount'
    ];

    const ERROR_REASON_EMPTY_CURRENCY = [
        'STATUS' => 'error',
        'CODE' => 11,
        'REASON' => 'Empty Currency'
    ];

    const ERROR_REASON_EMPTY_SIGNATURE = [
        'STATUS' => 'error',
        'CODE' => 12,
        'REASON' => 'Empty Signature'
    ];

    const ERROR_REASON_INVALID_TOKEN = [
        'STATUS' => 'error',
        'CODE' => 13,
        'REASON' => 'Invalid Token'
    ];

    const ERROR_REASON_INVALID_CALLBACK_ID = [
        'STATUS' => 'error',
        'CODE' => 14,
        'REASON' => 'Invalid Callback ID'
    ];

    const ERROR_REASON_INVALID_REFUND_CALLBACK_ID = [
        'STATUS' => 'error',
        'CODE' => 15,
        'REASON' => 'Invalid Refund Callback ID'
    ];

    const ERROR_REASON_INVALID_CALLBACK_TYPE = [
        'STATUS' => 'error',
        'CODE' => 16,
        'REASON' => 'Invalid Callback Type'
    ];

    const ERROR_REASON_INVALID_DATA = [
        'STATUS' => 'error',
        'CODE' => 17,
        'REASON' => 'Invalid Data'
    ];

    const ERROR_REASON_INVALID_ROUND_ID = [
        'STATUS' => 'error',
        'CODE' => 18,
        'REASON' => 'Invalid Round ID'
    ];

    const ERROR_REASON_INVALID_REFUND_ROUND_ID = [
        'STATUS' => 'error',
        'CODE' => 19,
        'REASON' => 'Invalid Refund Round ID'
    ];

    const ERROR_REASON_INVALID_ACTION_ID = [
        'STATUS' => 'error',
        'CODE' => 20,
        'REASON' => 'Invalid Action ID'
    ];

    const ERROR_REASON_INVALID_REFUND_ACTION_ID = [
        'STATUS' => 'error',
        'CODE' => 21,
        'REASON' => 'Invalid Refund Action ID'
    ];

    const ERROR_REASON_INVALID_AMOUNT = [
        'STATUS' => 'error',
        'CODE' => 22,
        'REASON' => 'Invalid Amount'
    ];

    const ERROR_REASON_INVALID_CURRENCY = [
        'STATUS' => 'error',
        'CODE' => 23,
        'REASON' => 'Invalid Currency'
    ];

    const ERROR_REASON_INVALID_SIGNATURE = [
        'STATUS' => 'error',
        'CODE' => 24,
        'REASON' => 'Invalid Signature'
    ];

    const ERROR_REASON_INVALID_METHOD = [
        'STATUS' => 'error',
        'CODE' => 25,
        'REASON' => 'Invalid Method'
    ];

    const ERROR_REASON_INSUFFICIENT_BALANCE = [
        'STATUS' => 'error',
        'CODE' => 26,
        'REASON' => 'Not enough money'
    ];

    const ERROR_REASON_BET_NOT_FOUND = [
        'STATUS' => 'error',
        'CODE' => 27,
        'REASON' => 'Bet transaction not found or Round is not exist.'
    ];

    const ERROR_REASON_PLAYER_NOT_FOUND = [
        'STATUS' => 'error',
        'CODE' => 28,
        'REASON' => 'Player not found (Invalid Token) or Player is disabled'
    ];

    const ERROR_REASON_BET_NOT_ALLOWED = [
        'STATUS' => 'error',
        'CODE' => 29,
        'REASON' => 'Bet is not allowed.'
    ];

    const ERROR_REASON_TRANSACTION_ALREADY_EXISTS = [
        'STATUS' => 'error',
        'CODE' => 30,
        'REASON' => 'Transaction already exists'
    ];

    const ERROR_REASON_IP_NOT_ALLOWED = [
        'STATUS' => 'error',
        'CODE' => 31,
        'REASON' => 'IP Address is not allowed.'
    ];

    const ERROR_REASON_TRANSACTION_ALREADY_PROCESSED_BY_WIN = [
        'STATUS' => 'error',
        'CODE' => 32,
        'REASON' => 'Transaction already processed by win.'
    ];

    const ERROR_REASON_TRANSACTION_ALREADY_PROCESSED_BY_REFUND = [
        'STATUS' => 'error',
        'CODE' => 33,
        'REASON' => 'Transaction already processed by refund.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_200 = [
        'STATUS' => 'ok',
        'CODE' => 200,
        'STATUS_TEXT' => 'Ok',
        'DESCRIPTION' => 'Success'
    ];

    const HTTP_RESPONSE_STATUS_CODE_400 = [
        'STATUS' => 'error',
        'CODE' => 400,
        'STATUS_TEXT' => 'Bad Request',
        'DESCRIPTION' => 'Malformed request syntax, Invalid request message framing, or Deceptive request routing'
    ];

    const HTTP_RESPONSE_STATUS_CODE_401 = [
        'STATUS' => 'error',
        'CODE' => 401,
        'STATUS_TEXT' => 'Unauthorized',
        'DESCRIPTION' => 'Request has not been completed because it lacks valid authentication credentials for the requested resource.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_404 = [
        'STATUS' => 'error',
        'CODE' => 404,
        'STATUS_TEXT' => 'Not Found',
        'DESCRIPTION' => 'The server can not find the requested resource.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_500 = [
        'STATUS' => 'error',
        'CODE' => 500,
        'STATUS_TEXT' => 'Internal Server Error',
        'DESCRIPTION' => 'Internal Server Error'
    ];

    const HTTP_RESPONSE_STATUS_CODE_501 = [
        'STATUS' => 'error',
        'CODE' => 501,
        'STATUS_TEXT' => 'Not Implemented',
        'DESCRIPTION' => 'The request method is not supported by the server and cannot be handled.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_503 = [
        'STATUS' => 'error',
        'CODE' => 503,
        'STATUS_TEXT' => 'Service Unavailable',
        'DESCRIPTION' => 'The server is not ready to handle the request. Common causes are a server that is down for maintenance or that is disabled.'
    ];

    const MAIN_METHOD_INDEX = 'index';

    const CALLBACK_MAIN_FUNCTION = 'callback';

    const CALLBACK_TYPE_INIT = 'init';
    const CALLBACK_TYPE_BET = 'bet';
    const CALLBACK_TYPE_WIN = 'win';
    const CALLBACK_TYPE_REFUND = 'refund';

    const CALLBACK_TYPE_FUNCTIONS = [
        self::CALLBACK_TYPE_INIT,
        self::CALLBACK_TYPE_BET,
        self::CALLBACK_TYPE_WIN,
        self::CALLBACK_TYPE_REFUND,
    ];

    const CALLBACK_TYPE_WITH_DATA = [
        self::CALLBACK_TYPE_BET,
        self::CALLBACK_TYPE_WIN,
        self::CALLBACK_TYPE_REFUND,
    ];

    const SCOPE_INTERNAL = 'internal';
    const SCOPE_USER = 'user';

    const REFUND_CANNOT_RESENT = 1;
    const REFUND_CAN_RESENT = 0;

    const ACTION_GENERATE_SIGNATURE = 'generateSignature';
    const ACTION_GET_PLAYER_TOKEN = 'getPlayerToken';
    const ACTION_DECRYPT_TOKEN = 'decryptToken';
    const ACTION_GET_TRANSACTION_BY_ACTION_ID = 'getTransactionByActionId';

    const REGISTERED_ACTIONS = [
        self::ACTION_GENERATE_SIGNATURE,
        self::ACTION_GET_PLAYER_TOKEN,
        self::ACTION_DECRYPT_TOKEN,
        self::ACTION_GET_TRANSACTION_BY_ACTION_ID,
    ];

    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;

    const ACTION_OFF = 'off';
    const ACTION_ON = 'on';

    const MD5_FIELDS_FOR_ORIGINAL = [
        'game_platform_id',
        'token',
        'player_id',
        'currency',
        'transaction_type',
        'transaction_id',
        'absolute_name',
        'game_id',
        'round_id',
        'action_id',
        'action',
        'amount',
        'before_balance',
        'after_balance',
        'start_at',
        'end_at',
        'status',
        'signature',
        'elapsed_time',
        'extra_info',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'response_result_id',
        'external_unique_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_UPDATE = [
        'response_result_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_UPDATE = [];

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(array('wallet_model', 'common_token', 'original_seamless_wallet_transactions'));
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->balance = 0;
        $this->scope = self::SCOPE_INTERNAL; //internal - error message is not available to user | user - error message seen by user
        $this->no_refund = self::REFUND_CAN_RESENT; //1 - callback should not be resent | 0 - callback can be resent
        $this->headers = getallheaders();
        $this->request_uri_function = $this->uri->segment(3);
        $this->http_status_response = $this->reason = $this->player = [];
        $this->response_result_id = $this->processed_transaction_id = null;

        if (!empty($this->input->post())) {
            $this->request_params = $this->input->post();
        } else {
            $raw_params = file_get_contents("php://input");
            $this->request_params = !empty($raw_params) ? json_decode($raw_params, true) : [];
        }
    }

    public function index($seamless_game_api, $main_function, $action = null)
    {
        $this->callback_type = isset($this->request_params['name']) && !empty($this->request_params['name']) ? $this->request_params['name'] : self::MAIN_METHOD_INDEX;

        if (empty($this->request_uri_function) || $this->request_uri_function != self::CALLBACK_MAIN_FUNCTION) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_404);
        }

        $rules_set = [
            'token' => 'required',
            'callback_id' => 'required',
            'name' => 'required',
            'signature' => 'required'
        ];

        $validate_request = $this->validateRequest($rules_set, $this->request_params);

        if (!$validate_request && $action == null) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
        }

        if (empty($seamless_game_api) || $seamless_game_api != self::SEAMLESS_GAME_API) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $this->api = $this->CI->utils->loadExternalSystemLibObject($seamless_game_api);

        if (!$this->api) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        if (!$this->api->validateWhiteIP()) {
            $this->callback_type = 'validateWhiteIP';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_401, self::ERROR_REASON_IP_NOT_ALLOWED);
        }

        $this->game_platform_id = $this->api->getPlatformCode();
        $this->original_transactions_table = $this->api->original_transactions_table;
        $this->project_id = $this->api->project_id;
        $this->callback_version = $this->api->callback_version;
        $this->secret_key = $this->api->secret_key;
        $this->currency = $this->api->currency;
        $this->scope = $this->api->scope;
        $this->no_refund = $this->api->no_refund;
        $this->use_bet_extra_info_token = $this->api->use_bet_extra_info_token;
        $this->use_new_token = $this->api->use_new_token;
        $this->use_signature = $this->api->use_signature;
        $this->check_by_round_and_action = $this->api->check_by_round_and_action;
        $this->use_is_win_exist_real_error = $this->api->use_is_win_exist_real_error;
        $this->use_is_refund_exist_real_error = $this->api->use_is_refund_exist_real_error;

        if ($this->CI->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
            $this->callback_type = 'setNotActiveOrMaintenance';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_503);
        }

        if (empty($main_function) || $main_function != self::CALLBACK_MAIN_FUNCTION) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_404);
        }

        if (!empty($action) && !in_array($action, self::REGISTERED_ACTIONS)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        # get player token
        if ($action == self::ACTION_GET_PLAYER_TOKEN) {
            $username = isset($this->request_params['username']) ? $this->request_params['username'] : null;
            return $this->returnJsonResult(['token' => $this->api->encrypt($username)]);
        }

        # decrypt token
        if ($action == self::ACTION_DECRYPT_TOKEN) {
            $token = isset($this->request_params['token']) ? $this->request_params['token'] : null;
            return $this->returnJsonResult(['token' => $this->api->decrypt($token)]);
        }

        #get transaction by action id
        if ($action == self::ACTION_GET_TRANSACTION_BY_ACTION_ID) {
            $player_name = isset($this->request_params['player_name']) ? $this->request_params['player_name'] : null;
            $action_id = isset($this->request_params['action_id']) ? $this->request_params['action_id'] : null;
            $transaction = $this->api->queryTransactionByActionId($action_id, $player_name);
            return $this->returnJsonResult($transaction);
        }

        return $this->$main_function($action);
    }

    protected function callback($action = null)
    {
        $this->token = isset($this->request_params['token']) && !empty($this->request_params['token']) ? $this->request_params['token'] : null;
        $this->callback_id = isset($this->request_params['callback_id']) && !empty($this->request_params['callback_id']) ? $this->request_params['callback_id'] : null;
        $this->callback_type = $method = isset($this->request_params['name']) && !empty($this->request_params['name']) ? $this->request_params['name'] : self::CALLBACK_MAIN_FUNCTION;
        $this->signature = isset($this->request_params['signature']) && !empty($this->request_params['signature']) ? $this->request_params['signature'] : null;

        if (empty($this->token)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_TOKEN);
        }

        if ($this->callback_type == self::CALLBACK_TYPE_WIN || $this->callback_type == self::CALLBACK_TYPE_REFUND) {
            if ($this->use_bet_extra_info_token) {
                if ($this->callback_type == self::CALLBACK_TYPE_REFUND) {
                    $this->round_id = isset($this->request_params['data']['refund_round_id']) && !empty($this->request_params['data']['refund_round_id']) ? $this->request_params['data']['refund_round_id'] : null;
                } else {
                    $this->round_id = isset($this->request_params['data']['round_id']) && !empty($this->request_params['data']['round_id']) ? $this->request_params['data']['round_id'] : null;
                }

                $bet_transactions = $this->api->queryTransactionsByTypeAndRound(self::CALLBACK_TYPE_BET, $this->round_id);

                if (!empty($bet_transactions)) {
                    foreach ($bet_transactions as $bet_transaction) {
                        $extra_info = isset($bet_transaction['extra_info']) ? json_decode($bet_transaction['extra_info'], true) : null;
                        $token = isset($extra_info['token']) ? $extra_info['token'] : null;

                        if ($this->token == $token) {
                            $player_id = isset($bet_transaction['player_id']) ? $bet_transaction['player_id'] : null;
                            $game_username = $this->api->getGameUsernameByPlayerId($player_id);
                            $this->player = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $this->game_platform_id);

                            if (isset($this->request_params['use_bet_details']) && $this->request_params['use_bet_details'] && $this->signature == self::ACTION_OFF) {
                                $details = isset($extra_info['data']['details']) ? json_decode($extra_info['data']['details'], true) : null;

                                if (isset($details['game']['version'])) {
                                    unset($details['game']['version']);
                                }

                                if (isset($details['game']['action'])) {
                                    unset($details['game']['action']);
                                }

                                if (isset($details['game']['action_id'])) {
                                    unset($details['game']['action_id']);
                                }

                                $this->request_params['data']['details'] = json_encode($details);
                            }

                            break;
                        }
                    }
                } else {
                    if ($this->callback_type == self::CALLBACK_TYPE_REFUND) {
                        $username = $this->api->decrypt($this->token);
                        $this->player = (array) $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);
                    } else {
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_BET_NOT_FOUND);
                    }
                }

                if (empty($this->player)) {
                    if ($this->callback_type == self::CALLBACK_TYPE_REFUND) {
                        $this->amount = 0;
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
                    } else {
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_PLAYER_NOT_FOUND);
                    }
                }
            } else {
                if ($this->use_new_token) {
                    $username = $this->api->decrypt($this->token);
                    $this->player = (array) $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);

                    if (empty($this->player)) {
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_PLAYER_NOT_FOUND);
                    }
                } else {
                    $this->player = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->token, $this->game_platform_id);

                    if (empty($this->player)) {
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_PLAYER_NOT_FOUND);
                    }

                    if ($this->callback_type == self::CALLBACK_TYPE_BET && !$this->common_token->isTokenValid($this->player['player_id'], $this->token)) {
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_INVALID_TOKEN);
                    }
                }
            }
        } else {
            if ($this->use_new_token) {
                $username = $this->api->decrypt($this->token);
                $this->player = (array) $this->common_token->getPlayerCompleteDetailsByUsername($username, $this->game_platform_id);

                if (empty($this->player)) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_PLAYER_NOT_FOUND);
                }
            } else {
                $this->player = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->token, $this->game_platform_id);

                if (empty($this->player)) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_PLAYER_NOT_FOUND);
                }

                if ($this->callback_type == self::CALLBACK_TYPE_BET && !$this->common_token->isTokenValid($this->player['player_id'], $this->token)) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_INVALID_TOKEN);
                }
            }
        }

        if (empty($this->callback_id)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_CALLBACK_ID);
        }

        if (empty($this->callback_type)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_CALLBACK_TYPE);
        }

        if (!method_exists($this, $this->callback_type)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_INVALID_CALLBACK_TYPE);
        }

        if (!in_array($this->callback_type, self::CALLBACK_TYPE_FUNCTIONS)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_INVALID_CALLBACK_TYPE);
        }

        $check_request_params_signature = [
            'token' => $this->token,
            'callback_id' => $this->callback_id,
            'name' => $this->callback_type,
        ];

        if (in_array($this->callback_type, self::CALLBACK_TYPE_WITH_DATA)) {
            $rules_set = ['data' => 'required'];
            $validate_request = $this->validateRequest($rules_set, $this->request_params);

            if (!$validate_request) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
            }

            $this->data = isset($this->request_params['data']) && !empty($this->request_params['data']) ? $this->request_params['data'] : [];

            if (empty($this->data)) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_DATA);
            }

            if ($this->callback_type == self::CALLBACK_TYPE_REFUND) {
                $rules_set = [
                    'refund_callback_id' => 'required',
                    'refund_round_id' => 'required',
                    'refund_action_id' => 'required',
                ];

                $validate_request = $this->validateRequest($rules_set, $this->request_params['data']);

                if (!$validate_request) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
                }

                $this->refund_callback_id = isset($this->data['refund_callback_id']) && !empty($this->data['refund_callback_id']) ? $this->data['refund_callback_id'] : null;
                $this->round_id = isset($this->data['refund_round_id']) && !empty($this->data['refund_round_id']) ? $this->data['refund_round_id'] : null;
                $this->action_id = isset($this->data['refund_action_id']) && !empty($this->data['refund_action_id']) ? $this->data['refund_action_id'] : null;

                if (empty($this->data['refund_callback_id'])) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_REFUND_CALLBACK_ID);
                }

                if (empty($this->data['refund_round_id'])) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_REFUND_ROUND_ID);
                }

                if (empty($this->data['refund_action_id'])) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_REFUND_ACTION_ID);
                }
            } else {
                $rules_set = [
                    'round_id' => 'required',
                    'action_id' => 'required',
                ];

                $validate_request = $this->validateRequest($rules_set, $this->request_params['data']);

                if (!$validate_request) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
                }

                $this->round_id = isset($this->data['round_id']) && !empty($this->data['round_id']) ? $this->data['round_id'] : null;
                $this->action_id = isset($this->data['action_id']) && !empty($this->data['action_id']) ? $this->data['action_id'] : null;

                if (empty($this->data['round_id'])) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_ROUND_ID);
                }

                if (empty($this->data['action_id'])) {
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_ACTION_ID);
                }
            }

            $rules_set = [
                'amount' => 'required',
                'currency' => 'required',
                'details' => 'required',
            ];

            $validate_request = $this->validateRequest($rules_set, $this->request_params['data']);

            if (!$validate_request) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
            }

            $this->final_action = isset($this->data['final_action']) && !empty($this->data['final_action']) ? $this->data['final_action'] : 0;
            $this->amount = isset($this->data['amount']) && !empty($this->data['amount']) ? $this->data['amount'] : 0;

            if (empty($this->data['currency'])) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_CURRENCY);
            }

            if ($this->data['currency'] != $this->currency) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_INVALID_CURRENCY);
            }

            $this->details = isset($this->request_params['data']['details']) ? json_decode($this->request_params['data']['details'], true) : null;

            $rules_set = [
                'game' => 'required',
            ];

            $validate_request = $this->validateRequest($rules_set, $this->details ?: []);

            if (!$validate_request) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
            }

            $rules_set = [
                'absolute_name' => 'required',
                'game_id' => 'required',
            ];

            $validate_request = $this->validateRequest($rules_set, $this->details['game']);

            if (!$validate_request) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, $this->reason);
            }

            $this->absolute_name = isset($this->details['game']['absolute_name']) && !empty($this->details['game']['absolute_name']) ? $this->details['game']['absolute_name'] : null;
            $this->game_id = isset($this->details['game']['game_id']) && !empty($this->details['game']['game_id']) ? $this->details['game']['game_id'] : null;
            $this->action = isset($this->details['game']['action']) && !empty($this->details['game']['action']) ? $this->details['game']['action'] : null;

            $check_request_params_signature['data'] = $this->data;
            $this->external_transaction_id = $this->setExternalTransactionId();
        }

        if (empty($this->signature)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_EMPTY_SIGNATURE);
        }

        $check_signature = $this->api->getSignature($this->project_id, $this->callback_version, $check_request_params_signature, $this->secret_key);

        # generate signature for testing
        if ($action == self::ACTION_GENERATE_SIGNATURE) {
            return $this->returnJsonResult(['signature' => $check_signature]);
        }

        if ($this->signature != $check_signature && $this->signature != self::ACTION_OFF && $this->use_signature) {
            $is_valid_signature = $this->api->isValidCallbackSignature($this->request_params);
            if (!$is_valid_signature) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_INVALID_SIGNATURE);
            }
        }

        $this->balance = $this->queryPlayerBalance($this->player['username']);
        $this->external_unique_id = $this->external_transaction_id;
        $this->uniqueid_of_seamless_service = $this->game_platform_id . '-' . $this->external_unique_id;
		$this->external_game_id = isset($this->game_id) ? $this->game_id : null;
       
        return $this->$method();
    }

    protected function init()
    {
        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
    }

    protected function bet()
    {
        $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $is_transacton_exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_transactions_table, $this->external_transaction_id);

        if ($is_transacton_exist) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
            # return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_TRANSACTION_ALREADY_EXISTS);
        }

        $this->wallet_model->setGameProviderIsEndRound($this->final_action);
        $this->wallet_model->setGameProviderRoundId($this->round_id);

        $data = [];
        $self = $this;
        $success = $this->lockAndTransForPlayerBalance($this->player['player_id'], function () use ($self, &$data) {
            $player = $self->player;
            $amount = $self->amount;
            $data['before_balance'] = $self->queryPlayerBalance($player['username']);
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
        	$this->wallet_model->setUniqueidOfSeamlessService($this->uniqueid_of_seamless_service, $this->external_game_id);

            if ($amount > $data['before_balance']) {
                $this->scope = self::SCOPE_USER;
                $this->no_refund = self::REFUND_CANNOT_RESENT;
                $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_400;
                $this->reason = self::ERROR_REASON_INSUFFICIENT_BALANCE;
                $success = false;
            } elseif ($amount == 0) { # free spin
                $success = true;
            } else {
                $success = $self->wallet_model->decSubWallet($player['player_id'], $self->api->getPlatformCode(), $amount);
            }

            $this->remote_Wallet_error_code = $this->remoteWalletErrorCode();
            
            # treat success if remote wallet return double uniqueid
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                if ($this->utils->isEnabledRemoteWalletClient()) {
                    if ($this->remote_Wallet_error_code == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                        $success = true;
                    }
                }
            }

            if ($success) {
                $self->balance = $data['after_balance'] = $self->queryPlayerBalance($player['username']);
                $this->processed_transaction_id = $this->preProcessRequestData($data);

                if (!$this->processed_transaction_id) {
                    $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                    $success = false;
                }
            }

            return $success;
        });

        $this->saveRemoteWalletError($success);

        if ($success) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
        } else {
            return $this->setResponse($this->http_status_response, $this->reason);
        }
    }

    protected function win()
    {
        $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $is_transacton_exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_transactions_table, $this->external_transaction_id);

        if ($is_transacton_exist) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
            # return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_TRANSACTION_ALREADY_EXISTS);
        }

        $fields = [
            'transaction_type' => self::CALLBACK_TYPE_BET,
            'player_id' => $this->player['player_id'],
            'game_id' => $this->game_id,
            'round_id' => $this->round_id,
        ];

        $bet_transaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_transactions_table, $fields);
        if (empty($bet_transaction)) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_BET_NOT_FOUND);
        }

        if (!empty($bet_transaction['external_unique_id'])) {
            $this->wallet_model->setRelatedUniqueidOfSeamlessService($this->utils->mergeArrayValues(['game', $this->game_platform_id, $bet_transaction['external_unique_id']]));
            $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
        }

        $fields['transaction_type'] = self::CALLBACK_TYPE_REFUND;
        $is_refund_exist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_transactions_table, $fields);

        if ($is_refund_exist) {
            $this->amount = 0;
            if ($this->use_is_refund_exist_real_error) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_TRANSACTION_ALREADY_PROCESSED_BY_REFUND);
            } else {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
            }
        }

        $fields['transaction_type'] = self::CALLBACK_TYPE_WIN;
        $fields['action_id'] = $this->action_id;
        $is_win_exist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_transactions_table, $fields);

        if ($is_win_exist && $this->check_by_round_and_action) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
            # return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_TRANSACTION_ALREADY_EXISTS);
        }

        $this->wallet_model->setGameProviderIsEndRound($this->final_action);
        $this->wallet_model->setGameProviderRoundId($this->round_id);

        $data = [];
        $self = $this;
        $success = $this->lockAndTransForPlayerBalance($this->player['player_id'], function () use ($self, &$data) {
            $player = $self->player;
            $amount = $self->amount;
            $data['before_balance'] = $self->queryPlayerBalance($player['username']);

            $external_unique_id = $this->external_transaction_id;
            $uniqueid_of_seamless_service = $this->game_platform_id . '-' . $external_unique_id;
			$external_game_id = isset($this->game_id) ? $this->game_id : null;

            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
        	$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);

            $configEnabled = $this->CI->utils->getConfig('enabled_remote_wallet_client_on_currency');
            
            #accepts 0 amount to call increase balance
            if ($amount >= 0 && !empty($configEnabled) || $amount > 0) {
                $success = $self->wallet_model->incSubWallet($player['player_id'], $self->api->getPlatformCode(), $amount);
            } elseif ($amount < 0) {
                $this->scope = self::SCOPE_USER;
                $this->no_refund = self::REFUND_CANNOT_RESENT;
                $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_400;
                $this->reason = self::ERROR_REASON_BET_NOT_ALLOWED;
                $success = false;
            }elseif($amount == 0){
                $success = true;
            }

            $this->remote_Wallet_error_code = $this->remoteWalletErrorCode();

            # treat success if remote wallet return double uniqueid
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                if ($this->utils->isEnabledRemoteWalletClient()) {
                    if ($this->remote_Wallet_error_code == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                        $success = true;
                    }
                }
            }

            if ($success) {
                $self->balance = $data['after_balance'] = $self->queryPlayerBalance($player['username']);
                $this->processed_transaction_id = $this->preProcessRequestData($data);

                if (!$this->processed_transaction_id) {
                    $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                    $success = false;
                }
            }

            return $success;
        });

        $this->saveRemoteWalletError($success);

        if ($success) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
        } else {
            return $this->setResponse($this->http_status_response, $this->reason);
        }
    }

    protected function refund()
    {
        $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $is_transacton_exist = $this->original_seamless_wallet_transactions->isTransactionExist($this->original_transactions_table, $this->external_transaction_id);
        if ($is_transacton_exist) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
        }

        /* $get_round_id = $this->api->getSpecificRoundIdByTransactionType($this->round_id, self::CALLBACK_TYPE_REFUND);

        if (!empty($get_round_id && $get_round_id['round_id'] == $this->round_id)) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
        } */

        $fields = [
            'transaction_type' => self::CALLBACK_TYPE_BET,
            'player_id' => $this->player['player_id'],
            'round_id' => $this->round_id,
            'external_unique_id' => $this->refund_callback_id,
        ];

        $bet_transaction = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_transactions_table, $fields);
        if (empty($bet_transaction)) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
        }

        if (!empty($bet_transaction['external_unique_id'])) {
            $this->wallet_model->setRelatedUniqueidOfSeamlessService($this->utils->mergeArrayValues(['game', $this->game_platform_id, $bet_transaction['external_unique_id']]));
            $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
        }

        $fields = [
            'transaction_type' => self::CALLBACK_TYPE_WIN,
            'player_id' => $this->player['player_id'],
            'game_id' => $this->game_id,
            'round_id' => $this->round_id,
            'action_id' => $this->action_id,
        ];

        $is_win_exist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_transactions_table, $fields);
        if ($is_win_exist) {
            $this->amount = 0;
            if ($this->use_is_win_exist_real_error) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_TRANSACTION_ALREADY_PROCESSED_BY_WIN);
            } else {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
            }
        }

        $fields['transaction_type'] = self::CALLBACK_TYPE_REFUND;
        $is_refund_exist = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_transactions_table, $fields);
        if ($is_refund_exist && $this->check_by_round_and_action) {
            $this->amount = 0;
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
            # return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_400, self::ERROR_REASON_TRANSACTION_ALREADY_EXISTS);
        }

        $this->wallet_model->setGameProviderIsEndRound($this->final_action);
        $this->wallet_model->setGameProviderRoundId($this->round_id);

        $data = [];
        $self = $this;
        $success = $this->lockAndTransForPlayerBalance($this->player['player_id'], function () use ($self, &$data) {
            $player = $self->player;
            $amount = $self->amount;
            $data['before_balance'] = $self->queryPlayerBalance($player['username']);

            $external_unique_id = $this->external_transaction_id;
            $uniqueid_of_seamless_service = $this->game_platform_id . '-' . $external_unique_id;
			$external_game_id = isset($this->game_id) ? $this->game_id : null;

            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
        	$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);

            if ($amount == 0) {
                $success = true;
                $self->balance = $data['after_balance'] = $data['before_balance'];
            } elseif ($amount < 0) {
                $this->scope = self::SCOPE_USER;
                $this->no_refund = self::REFUND_CANNOT_RESENT;
                $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_400;
                $this->reason = self::ERROR_REASON_BET_NOT_ALLOWED;
                $success = false;
            } else {
                $success = $self->wallet_model->incSubWallet($player['player_id'], $self->api->getPlatformCode(), $amount);
            }

            if ($success) {
                $self->balance = $data['after_balance'] = $self->queryPlayerBalance($player['username']);
                $this->processed_transaction_id = $this->preProcessRequestData($data);

                if (!$this->processed_transaction_id) {
                    $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                    $success = false;
                }
            }

            return $success;
        });

        if ($success) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200);
        } else {
            return $this->setResponse($this->http_status_response, $this->reason);
        }
    }

    private function validateRequest($rules_set, $request)
    {
        $is_valid = true;

        foreach ($rules_set as $required_param => $rules) {
            $rules = explode('|', $rules);
            foreach ($rules as $rule) {
                if ($rule == 'required' && !array_key_exists($required_param, (array) $request)) {
                    $is_valid = false;
                    $this->reason = ['REASON' => 'Required ' . $required_param];
                    break;
                }
            }

            if (!$is_valid) {
                break;
            }
        }

        return $is_valid;
    }

    private function queryPlayerBalance($playerName)
    {
        $playerBalance = $this->api->queryPlayerBalance($playerName);

        if ($playerBalance['success']) {
            $result = $playerBalance['balance'];
        } else {
            $result = false;
        }

        return $result;
    }

    private function setExternalTransactionId()
    {
        # $external_transaction_id = strtoupper(substr($this->callback_type, 0, 1)) . '-' . $this->callback_id;
        $external_transaction_id = $this->callback_id;

        if ($this->callback_type == self::CALLBACK_TYPE_REFUND) {
            $external_transaction_id .= '-' . $this->refund_callback_id;
        }

        return $external_transaction_id;
    }

    private function preProcessRequestData(&$data)
    {
        $processedData = array(
            'game_platform_id' => $this->game_platform_id,
            'token' => $this->token,
            'player_id' => $this->player['player_id'],
            'currency' => $this->currency,
            'transaction_type' => $this->callback_type,
            'transaction_id' => $this->external_transaction_id,
            'absolute_name' => $this->absolute_name,
            'game_id' => $this->game_id,
            'round_id' => $this->round_id,
            'action_id' => $this->action_id,
            'action' => $this->action,
            'amount' => $this->amount,
            'before_balance' => $data['before_balance'],
            'after_balance' => $data['after_balance'],
            'start_at' => $this->api->gameTimeToServerTime($this->CI->utils->getNowForMysql()),
            'end_at' => $this->api->gameTimeToServerTime($this->CI->utils->getNowForMysql()),
            'status' => $this->final_action,
            'signature' => $this->signature,
            'elapsed_time' => intval($this->CI->utils->getExecutionTimeToNow() * 1000),
            'extra_info' => json_encode($this->request_params),
            'bet_amount' => 0,
            'win_amount' => 0,
            'result_amount' => 0,
            'flag_of_updated_result' => self::FLAG_NOT_UPDATED,
            'response_result_id' => $this->response_result_id,
            'external_unique_id' => $this->external_transaction_id,
        );

        $processedData['md5_sum'] = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($processedData, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        $processed_transaction_id = $this->original_seamless_wallet_transactions->insertTransactionData($this->original_transactions_table, $processedData);

        return $processed_transaction_id;
    }

    private function saveResponseResult($flag, $response, $http_status_response)
    {
        $response_result_id = $this->response_result->saveResponseResult(
            $this->game_platform_id,
            $flag,
            $this->callback_type,
            json_encode($this->request_params),
            $response,
            $http_status_response['CODE'],
            $http_status_response['STATUS_TEXT'],
            is_array($this->headers) ? json_encode($this->headers) : $this->headers,
            ['player_id' => isset($this->player['player_id']) && !empty($this->player['player_id']) ? $this->player['player_id'] : null],
            false,
            null,
            intval($this->utils->getExecutionTimeToNow()*1000) //costMs
        );

        return $response_result_id;
    }

    private function setResponse($response = [], $reason = [])
    {
        if (isset($reason['STATUS'])) {
            unset($reason['STATUS']);
        }

        if (isset($reason['CODE'])) {
            unset($reason['CODE']);
        }

        $complete_response = array_merge($response, $reason);
        $flag = $complete_response['CODE'] == self::HTTP_RESPONSE_STATUS_CODE_200['CODE'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if ($flag == Response_result::FLAG_NORMAL) {
            $returnResponse = [
                'status' => $complete_response['STATUS'],
                'data' => [
                    'balance' => $this->balance,
                    'currency' => $this->currency
                ]
            ];
        } else {
            $complete_response['REASON'] = isset($complete_response['REASON']) && !empty($complete_response['REASON']) ? $complete_response['REASON'] : $complete_response['DESCRIPTION'];

            $returnResponse = [
                'status' => $complete_response['STATUS'],
                'error' => [
                    'scope' => $this->scope,
                    'no_refund' => $this->no_refund,
                    'message' => $complete_response['REASON']
                ]
            ];
        }

        $this->response_result_id = $this->saveResponseResult($flag, $returnResponse, $complete_response);

        if ($this->processed_transaction_id) {
            $updated_data = [
                'response_result_id' => $this->response_result_id,
            ];
    
            $updated_data['md5_sum'] = $this->original_seamless_wallet_transactions->generateMD5SumOneRow($updated_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
            $this->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_transactions_table, $updated_data, 'id', $this->processed_transaction_id);
        }

        return $this->returnJsonResult($returnResponse, true, '*', false, false, $complete_response['CODE']);
    }

    private function saveFailedTransaction($query_type='insert', $data=[], $where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $this->utils->debug_log("EVOPLAY SEAMLESS SERVICE API: saveFailedTransaction",$query_type, $table_name, $data, $where);
        $this->original_seamless_wallet_transactions->saveTransactionData($table_name, $query_type, $data, $where);
    }

    private function isFailedTransactionExist($where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $isExisting = $this->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
        $this->utils->debug_log("EVOPLAY SEAMLESS SERVICE API: isFailedTransactionExist",$table_name, $where, $isExisting);
        return $isExisting;
    }

    private function remoteWalletErrorCode(){
        $this->load->model(['wallet_model']);
        if (method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
            $errorCode = $this->wallet_model->getRemoteWalletErrorCode();
            $this->utils->debug_log("EVOPLAY SEAMLESS SERVICE API: remoteWalletErrorCode", $errorCode);
            return $errorCode;
        }
        return null;
    }

    private function saveRemoteWalletError($success){
        #if false, save failed transaction
        if(!$success){
			$failed_external_uniqueid = $this->external_transaction_id;
			$failed_transaction_data = $md5_data = [
				'round_id' => $this->round_id,
				'transaction_id' => $this->external_transaction_id,
				'external_game_id' => $this->game_id,
				'player_id' => $this->player['player_id'],
				'game_username' => $this->api->getGameUsernameByPlayerId($this->player['player_id']),
				'amount' => $this->amount,
				'balance_adjustment_type' => $this->callback_type == 'bet' ? 'debit' : 'credit',
				'action' => $this->callback_type,
				'game_platform_id' => $this->api->getPlatformCode(),
				'transaction_raw_data' => json_encode($this->request_params),
				'remote_raw_data' => null,
				'remote_wallet_status' => $this->remote_Wallet_error_code,
				'transaction_date' => date('Y-m-d H:i:s'),
				'request_id' => $this->utils->getRequestId(),
				'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
				'headers' => json_encode(getallheaders()),
				'external_uniqueid' => $failed_external_uniqueid,
			];
			
			$failed_transaction_data['md5_sum'] = md5(json_encode($md5_data));

			$where = ['external_uniqueid' => $failed_external_uniqueid];
			if($this->isFailedTransactionExist($where)){
				$this->saveFailedTransaction('update',$failed_transaction_data, $where);
			}else{
				$this->saveFailedTransaction('insert',$failed_transaction_data);
			}
        }
    }
}
