<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

/**
 * @property CI_Loader $load
 * @property Wallet_model $wallet_model
 * @property Common_token $common_token
 * @property Original_seamless_wallet_transactions $original_seamless_wallet_transactions
 * @property Response_result $response_result
 */
class Bgaming_seamless_service_api extends BaseController
{
    use Seamless_service_api_module;

    private $request_headers;
    private $request_params;
    private $callback_method;
    private $game_platform_id;
    private $game_api;
    private $casino_id;
    private $auth_token;
    private $language;
    private $currency;
    private $response_result_id;
    private $player_details;
    private $player_balance_response;
    private $custom_message;
    private $transaction_table;
    private $transaction_already_exist;
    private $external_transaction_id;
    private $processed_transaction_id;
    private $processed_multiple_transaction;
    private $precision;
    private $conversion;
    private $game_provider_action_type = null;
    private $remote_Wallet_error_code = null;
    protected $is_end_round = false;

    const SEAMLESS_GAME_API = BGAMING_SEAMLESS_GAME_API;

    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';

    const API_ERROR_100 = [
        'code' => 100,
        'message' => 'Player has not enough funds to process an action.',
    ];

    const API_ERROR_101 = [
        'code' => 101,
        'message' => 'Player is invalid.',
    ];

    const API_ERROR_105 = [
        'code' => 105,
        'message' => 'Player reached customized bet limit.',
    ];

    const API_ERROR_106 = [
        'code' => 106,
        'message' => 'Bet exceeded max bet limit.',
    ];

    const API_ERROR_107 = [
        'code' => 107,
        'message' => '	Game is forbidden to the player.',
    ];

    const API_ERROR_110 = [
        'code' => 110,
        'message' => 'Player is disabled.',
    ];

    const API_ERROR_153 = [
        'code' => 153,
        'message' => "Game is not available in Player's country.",
    ];

    const API_ERROR_154 = [
        'code' => 154,
        'message' => 'Currency is not allowed for the player.',
    ];

    // When user's email and currency already set and you provide different email or currency you will get this error.
    const API_ERROR_155 = [
        'code' => 155,
        'message' => 'Forbidden to change already set field.',
    ];

    // Badly formatted JSON.
    const API_ERROR_400 = [
        'code' => 400,
        'message' => 'Bad request.',
    ];

    // Request sign doesn't match.
    const API_ERROR_403 = [
        'code' => 403,
        'message' => 'Forbidden.',
    ];

    const API_ERROR_404 = [
        'code' => 404,
        'message' => 'Not found.',
    ];

    const API_ERROR_405 = [
        'code' => 405,
        'message' => 'Game is not available to your casino.',
    ];

    const API_ERROR_406 = [
        'code' => 406,
        'message' => 'Freespins are not available for your casino.',
    ];

    const API_ERROR_500 = [
        'code' => 500,
        'message' => 'Unknown error.',
    ];

    const API_ERROR_502 = [
        'code' => 502,
        'message' => 'Unknown error in external service.',
    ];

    const API_ERROR_503 = [
        'code' => 503,
        'message' => 'Service is unavailable.',
    ];

    const API_ERROR_504 = [
        'code' => 504,
        'message' => 'Request timed out.',
    ];

    const API_ERROR_600 = [
        'code' => 600,
        'message' => "Game provider doesn't provide freespins.",
    ];

    const API_ERROR_601 = [
        'code' => 601,
        'message' => 'Impossible to issue freespins in requested game.',
    ];

    const API_ERROR_602 = [
        'code' => 602,
        'message' => 'You should provide at least one game to issue freespins.',
    ];

    // Expiration date should be in future and freespins shouldn't be active for more that 1 month.
    const API_ERROR_605 = [
        'code' => 605,
        'message' => "Can't change issue state from its current to requested.",
    ];

    const API_ERROR_606 = [
        'code' => 606,
        'message' => "You can't change issue state when issue status is not synced.",
    ];

    const API_ERROR_607 = [
        'code' => 607,
        'message' => "Can't issue freespins for different game providers.",
    ];

    const API_ERROR_610 = [
        'code' => 610,
        'message' => 'Invalid freespins issue.',
    ];

    const API_ERROR_611 = [
        'code' => 611,
        'message' => 'Freespins issue has already expired.',
    ];

    const API_ERROR_620 = [
        'code' => 620,
        'message' => "Freespins issue can't be canceled.",
    ];

    const API_ERROR_700 = [
        'code' => 700,
        'message' => 'Requested live game is not available right now.',
    ];

    const HTTP_RESPONSE_STATUS_CODE_200 = [
        'status' => 'ok',
        'code' => 200,
        'status_text' => 'Ok',
        'description' => 'Success'
    ];

    const HTTP_RESPONSE_STATUS_CODE_400 = [
        'status' => 'error',
        'code' => 400,
        'status_text' => 'Bad Request',
        'description' => 'Malformed request syntax, Invalid request message framing, or Deceptive request routing'
    ];

    const HTTP_RESPONSE_STATUS_CODE_401 = [
        'status' => 'error',
        'code' => 401,
        'status_text' => 'Unauthorized',
        'description' => 'Request has not been completed because it lacks valid authentication credentials for the requested resource.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_404 = [
        'status' => 'error',
        'code' => 404,
        'status_text' => 'Not Found',
        'description' => 'The server can not find the requested resource.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_403 = [
        'status' => 'error',
        'code' => 403,
        'status_text' => 'Forbidden',
        'description' => 'Forbidden.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_405 = [
        'status' => 'error',
        'code' => 405,
        'status_text' => 'Method Not Allowed',
        'description' => 'Method Not Allowed.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_412 = [
        'status' => 'error',
        'code' => 412,
        'status_text' => 'Precondition Failed',
        'description' => 'Precondition Failed.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_500 = [
        'status' => 'error',
        'code' => 500,
        'status_text' => 'Internal Server Error',
        'description' => 'Internal Server Error'
    ];

    const HTTP_RESPONSE_STATUS_CODE_501 = [
        'status' => 'error',
        'code' => 501,
        'status_text' => 'Not Implemented',
        'description' => 'The request method is not supported by the server and cannot be handled.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_503 = [
        'status' => 'error',
        'code' => 503,
        'status_text' => 'Service Unavailable',
        'description' => 'The server is not ready to handle the request. Common causes are a server that is down for maintenance or that is disabled.'
    ];

    const CALLBACK_METHOD_PLAY = 'play';
    const CALLBACK_METHOD_ROLLBACK = 'rollback';
    const CALLBACK_METHOD_FREESPINS = 'freespins';

    const ALLOWED_CALLBACK_METHODS = [
        self::CALLBACK_METHOD_PLAY,
        self::CALLBACK_METHOD_ROLLBACK,
        self::CALLBACK_METHOD_FREESPINS,
    ];

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_WIN = 'win';
    const TRANSACTION_TYPE_ROLLBACK = 'rollback';

    const TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_WIN,
        self::TRANSACTION_TYPE_ROLLBACK,
    ];

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED = 1;
    const FLAG_RETAIN = 3;

    const DECREASE = 'decrease';
    const INCREASE = 'increase';
    const RETAIN = 'retain';

    const INSERT = 'insert';
    const UPDATE = 'update';

    const MD5_FIELDS_FOR_ORIGINAL = [
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
        'casino_id',
        'rollback_action',
        'rollback_original_action_id',
        'jackpot_contribution',
        'jackpot_win',
        'bonus_amount',
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
        'jackpot_contribution',
        'jackpot_win',
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

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['common_token']);
        $this->ssa_init();
        $this->request_headers = getallheaders();
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->request_params = $this->player_details = $this->processed_multiple_transaction = [];
        $this->player_balance_response = $this->external_transaction_id = $this->processed_transaction_id = $this->response_result_id = null;
        $this->transaction_already_exist = false;
        $this->custom_message = '';
        $this->precision = 2;
    }

    public function index($game_platform_id, $method)
    {
        $this->callback_method = __FUNCTION__;
        $this->request_params = $this->getRequestParams();
        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'request_params', $this->request_params);

        if (in_array($method, get_class_methods(get_class($this)))) {
            if (in_array($method, self::ALLOWED_CALLBACK_METHODS)) {
                $this->callback_method = $method;
            } else {
                $this->custom_message = 'Method ' . $method . ' forbidden.';
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_405, self::API_ERROR_403);
            }
        } else {
            $this->custom_message = 'Method ' . $method . ' not found.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_404, self::API_ERROR_404);
        }

        if (empty($game_platform_id)) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_500, self::API_ERROR_502);
        }

        $this->game_api = $this->utils->loadExternalSystemLibObject($game_platform_id);

        if ($this->game_api) {
            // add need properties from game api here
            $this->game_platform_id = $this->game_api->getPlatformCode();
            $this->transaction_table = $this->game_api->getSeamlessTransactionTable();
            $this->casino_id = $this->game_api->casino_id;
            $this->auth_token = $this->game_api->auth_token;
            $this->language = $this->game_api->language;
            $this->currency = $this->game_api->currency;
            $this->precision = $this->game_api->precision;
            $this->conversion = $this->game_api->conversion;
        } else {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_500, self::API_ERROR_502);
        }

        if (!$this->game_api->validateWhiteIP()) {
            $this->custom_message = 'IP address is not allowed.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_401, self::API_ERROR_403);
        }

        if (!$this->game_api->isActive()) {
            $this->custom_message = 'Game is disabled.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_503, self::API_ERROR_503);
        }

        if ($this->game_api->isMaintenance()) {
            $this->custom_message = 'Game is under maintenance.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_503, self::API_ERROR_503);
        }

        if (!$this->validateApiHeaders()) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_403, self::API_ERROR_403);
        }

        $this->utils->debug_log(__METHOD__, $this->game_platform_id, 'request_params', $this->request_params);

        return $this->$method();
    }

    private function play()
    {
        if (empty($this->request_params['actions'])) {
            return $this->balanceRequest();
        } else {
            return $this->playRequest();
        }
    }

    private function balanceRequest()
    {
        $this->callback_method = __FUNCTION__;

        $rule_sets = [
            'user_id' => 'required|same', // game username
            'currency' => 'required|same',
            'game' => 'optional', // game code
            'game_id' => 'optional', // round id
            'finished' => 'optional',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $balance = null;

        $success = $this->lockAndTransForPlayerBalance($player_id, function () use($player_id, &$balance) {
            $balance = $this->queryPlayerBalance($player_id);

            if ($balance !== false) {
                return true;
            } else {
                return false;
            }
        });

        $operator_response = self::API_ERROR_502;

        if ($success) {
            $operator_response = [
                'balance' => $this->multiplying_conversion($balance, $this->conversion, $this->precision),
            ];
        }

        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
    }

    private function playRequest()
    {
        $this->callback_method = __FUNCTION__;
        $transactions = [];

        $rule_sets = [
            'user_id' => 'required|same', // game username
            'currency' => 'required|same',
            'game' => 'required', // game code
            'game_id' => 'required', // round id
            'finished' => 'optional',
            'actions' => 'required',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        list($is_valid_action, $http_response, $operator_response) = $this->validateAction();

        if (!$is_valid_action) {
            return $this->setResponse($http_response, $operator_response);
        }

        $success = false;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $operator_response = self::API_ERROR_502;
        $action_result = [];
        $actions = !empty($this->request_params['actions']) ? $this->request_params['actions'] : [];
        $round_id = !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null;
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $balance = $this->queryPlayerBalance($player_id);

        $proceed = true;
        $total_amount = 0;
        foreach ($actions as $request_params) {
            $action = !empty($request_params['action']) ? $request_params['action'] : null;
            $request_amount = !empty($request_params['amount']) ? $request_params['amount'] / $this->conversion : 0;
            $total_amount = $this->computeAmount($total_amount, $request_amount);

            if ($action == self::TRANSACTION_TYPE_BET) {
                list($proceed, $http_response, $operator_response) = $this->checkBalanceBeforeAction($total_amount, $balance);
            }

            if (!$proceed) {
                return $this->setResponse($http_response, $operator_response);
            }
        }

        foreach ($actions as $request_params) {
            $rule_sets = [
                'action' => 'required',
            ];

            list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $request_params);

            if (!$is_valid) {
                return $this->setResponse($http_response, $operator_response);
            }

            $action = !empty($request_params['action']) ? $request_params['action'] : null;

            list($success, $balance, $action_result, $http_response, $operator_response) = $this->$action($request_params, $balance); // bet or win

            if (!$success) {
                return $this->setResponse($http_response, $operator_response);
            }

            array_push($transactions, $action_result);
        }

        if ($success) {
            $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;

            $operator_response = [
                'balance' => $this->multiplying_conversion($balance, $this->conversion, $this->precision),
                'game_id' => $round_id,
                'transactions' => $transactions,
            ];
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function bet($request_params, $balance)
    {
        $this->callback_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        $request_params['amount'] = $request_params['amount'] / $this->conversion;
        // $balance = 0;
        $result = [];

        // no action win means round is end in bet
        if (!empty($this->request_params['actions']) && count($this->request_params['actions']) == 1) {
            $this->is_end_round = isset($this->request_params['finished']) && $this->request_params['finished'] ? $this->request_params['finished'] : false;
        }

        $rule_sets = [
            'amount' => 'required|nullable|numeric',
            'action_id' => 'required|unique', // transaction id
            'jackpot_contribution' => 'optional',
            'jackpot_win' => 'optional',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $request_params);

        if (!$is_valid) {
            return array($is_valid, $balance, $result, $http_response, $operator_response);
        }

        $success = false;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $operator_response = self::API_ERROR_502;
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        // $balance = $this->queryPlayerBalance($player_id);
        $action_result = [];

        $transaction_type = !empty($request_params['action']) ? $request_params['action'] : null;
        $transaction_id = !empty($request_params['action_id']) ? $request_params['action_id'] : null;
        $game_username = !empty($this->request_params['user_id']) ? $this->request_params['user_id'] : null;
        $game_code = !empty($this->request_params['game']) ? $this->request_params['game'] : null;
        $round_id = !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null;
        $casino_id = $this->casino_id;
        $params = compact('transaction_type', 'transaction_id');
        $this->external_transaction_id = $this->externalTransactionId($params);

        $is_rollback_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_ROLLBACK,
            'game_username' => $game_username, 
            'game_code' => $game_code,
            'round_id' => $round_id,
            'rollback_action' => self::RETAIN,
            'rollback_original_action_id' => $transaction_id,
        ]);

        if ($is_rollback_exist) {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['rollback_original_action_id' => $transaction_id]);
            $balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;

            $action_result = [
                'action_id' => !empty($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : null,
                'tx_id' => !empty($transaction_data['amount']) ? $transaction_data['external_unique_id'] : '',
                'processed_at' => $this->dateTimeISO8601($transaction_data['start_at']),
            ];

            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
        }

        if (!$this->transaction_already_exist) {
            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($player_id, function() use($controller, &$request_params, &$balance, &$result, &$http_response, &$operator_response) {
                list($success, $balance, $result, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::INSERT, $request_params, $balance, $result, $http_response, $operator_response);
                return $success;
            });
        } else {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['transaction_id' => $transaction_id]);
            $balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;

            $action_result = [
                'action_id' => !empty($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : null,
                'tx_id' => !empty($transaction_data['external_unique_id']) ? $transaction_data['external_unique_id'] : null,
                'processed_at' => $this->dateTimeISO8601($transaction_data['start_at']),
            ];

            $this->transaction_already_exist = false;
            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
        }

        if ($success) {
            $action_result = [
                'action_id' => $transaction_id,
                'tx_id' => $this->external_transaction_id,
                'processed_at' => $this->dateTimeISO8601($result['processed_at']),
            ];
        }

        return array($success, $balance, $action_result, $http_response, $operator_response);
    }

    private function win($request_params, $balance)
    {
        $this->callback_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT;
        $request_params['amount'] = $request_params['amount'] / $this->conversion;
        // $balance = 0;
        $result = [];

        $this->is_end_round = isset($this->request_params['finished']) && $this->request_params['finished'] ? $this->request_params['finished'] : false;

        $rule_sets = [
            'amount' => 'required|nullable|numeric',
            'action_id' => 'required|unique', // transaction id
            'jackpot_contribution' => 'optional',
            'jackpot_win' => 'optional',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $request_params);

        if (!$is_valid) {
            return array($is_valid, $balance, $result, $http_response, $operator_response);
        }

        $success = false;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $operator_response = self::API_ERROR_502;
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        // $balance = $this->queryPlayerBalance($player_id);
        $action_result = [];

        $transaction_type = !empty($request_params['action']) ? $request_params['action'] : null;
        $transaction_id = !empty($request_params['action_id']) ? $request_params['action_id'] : null;
        $game_username = !empty($this->request_params['user_id']) ? $this->request_params['user_id'] : null;
        $game_code = !empty($this->request_params['game']) ? $this->request_params['game'] : null;
        $round_id = !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null;
        $casino_id = $this->casino_id;
        $params = compact('transaction_type', 'transaction_id');
        $this->external_transaction_id = $this->externalTransactionId($params);

   
        
        $related_bet = $this->ssa_get_transaction($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_BET,
            'game_username' => $game_username, 
            'game_code' => $game_code,
            'round_id' => $round_id,
        ]);

        $related_bet_external_unique_id = !empty($related_bet['external_unique_id']) ? $related_bet['external_unique_id'] : null;

        $this->ssa_set_related_uniqueid_of_seamless_service($this->utils->mergeArrayValues([
            'game',
            $this->game_platform_id,
            $related_bet_external_unique_id,
        ]));

        $this->ssa_set_related_action_of_seamless_service(Wallet_model::REMOTE_RELATED_ACTION_BET);


        $is_rollback_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_ROLLBACK,
            'game_username' => $game_username, 
            'game_code' => $game_code,
            'round_id' => $round_id,
            'rollback_action' => self::RETAIN,
            'rollback_original_action_id' => $transaction_id,
        ]);

        if ($is_rollback_exist) {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['rollback_original_action_id' => $transaction_id]);
            $balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;

            $action_result = [
                'action_id' => !empty($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : null,
                'tx_id' => !empty($transaction_data['amount']) ? $transaction_data['external_unique_id'] : '',
                'processed_at' => $this->dateTimeISO8601($transaction_data['start_at']),
            ];

            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
        }

        $is_bet_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_BET,
            'game_username' => $game_username, 
            'game_code' => $game_code,
            'round_id' => $round_id,
        ]);

        if (!$is_bet_exist) {
            $this->custom_message = 'Bet transaction record not found.';
            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_404, self::API_ERROR_404);
        }

        $is_refunded_bet_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            'transaction_type' => self::TRANSACTION_TYPE_ROLLBACK,
            'game_username' => $game_username, 
            'game_code' => $game_code,
            'round_id' => $round_id,
        ]);

        if ($is_refunded_bet_exist) {
            $this->custom_message = 'Bet has been refunded.';
            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_403, self::API_ERROR_403);
        }


        if (!$this->transaction_already_exist) {
            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($player_id, function() use($controller, &$request_params, &$balance, &$result, &$http_response, &$operator_response) {
                list($success, $balance, $result, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $request_params, $balance, $result, $http_response, $operator_response);
                return $success;
            });
        } else {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['transaction_id' => $transaction_id]);
            $balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;

            $action_result = [
                'action_id' => !empty($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : null,
                'tx_id' => !empty($transaction_data['external_unique_id']) ? $transaction_data['external_unique_id'] : null,
                'processed_at' => $this->dateTimeISO8601($transaction_data['start_at']),
            ];

            $this->transaction_already_exist = false;
            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
        }

        if ($success) {
            $action_result = [
                'action_id' => $transaction_id,
                'tx_id' => $this->external_transaction_id,
                'processed_at' => $this->dateTimeISO8601($result['processed_at']),
            ];
        }

        return array($success, $balance, $action_result, $http_response, $operator_response);
    }

    private function rollback()
    {
        $this->callback_method = __FUNCTION__;
        $this->game_provider_action_type = Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND;
        $transactions = [];

        $rule_sets = [
            'user_id' => 'required|same', // game username
            'currency' => 'required|same',
            'game' => 'required', // game code
            'game_id' => 'required', // round id
            'finished' => 'optional',
            'actions' => 'required',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        list($is_valid_action, $http_response, $operator_response) = $this->validateAction();

        if (!$is_valid_action) {
            return $this->setResponse($http_response, $operator_response);
        }

        $success = false;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $operator_response = self::API_ERROR_502;
        $action_result = [];
        $actions = !empty($this->request_params['actions']) ? $this->request_params['actions'] : [];
        $round_id = !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null;

        foreach ($actions as $request_params) {
            $rule_sets = [
                'action' => 'required',
            ];

            list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $request_params);

            if (!$is_valid) {
                return $this->setResponse($http_response, $operator_response);
            }

            // $is_rollback_bet_settled_exist = $this->ssa_is_transaction_exists($this->transaction_table, [
            //     'transaction_type' => self::TRANSACTION_TYPE_WIN,
            //     'round_id' => $round_id,
            // ]);
    
            // if ($is_rollback_bet_settled_exist) {
            //     $this->custom_message = 'Bet has been Settled.';
            //     return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_403, self::API_ERROR_403);

            // }
            
            list($success, $balance, $action_result, $http_response, $operator_response) = $this->processRollbackRequest($request_params);

            if (!$success) {
                return $this->setResponse($http_response, $operator_response);
            }

            array_push($transactions, $action_result);
        }

        if ($success) {
            $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;

            $operator_response = [
                'balance' => $this->multiplying_conversion($balance, $this->conversion, $this->precision),
                'game_id' => $round_id,
                'transactions' => $transactions,
            ];
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function processRollbackRequest($request_params)
    {
        $this->callback_method = self::TRANSACTION_TYPE_ROLLBACK;
        $balance = 0;
        $result = [];

        $rule_sets = [
            'action_id' => 'required|unique', // transaction id
            'original_action_id' => 'required',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $request_params);

        if (!$is_valid) {
            return array($is_valid, $balance, $result, $http_response, $operator_response);
        }

        $success = false;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $operator_response = self::API_ERROR_502;
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $balance = $this->queryPlayerBalance($player_id);
        $action_result = [];

        $transaction_type = !empty($request_params['action']) ? $request_params['action'] : null;
        $transaction_id = !empty($request_params['action_id']) ? $request_params['action_id'] : null;
        $rollback_original_action_id = !empty($request_params['original_action_id']) ? $request_params['original_action_id'] : null;
        $game_username = !empty($this->request_params['user_id']) ? $this->request_params['user_id'] : null;
        $game_code = !empty($this->request_params['game']) ? $this->request_params['game'] : null;
        $round_id = !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null;
        $casino_id = $this->casino_id;

        $where = [
            'transaction_id' => $rollback_original_action_id,
            'game_username' => $game_username, 
            'game_code' => $game_code,
            'round_id' => $round_id,
        ];

        $transaction_data = $this->ssa_get_transaction($this->transaction_table, $where);
        $request_params['amount'] = !empty($transaction_data['amount']) ? $transaction_data['amount'] : 0;

        if (!empty($transaction_data)) {
            if (isset($transaction_data['transaction_type']) && $transaction_data['transaction_type'] == self::TRANSACTION_TYPE_BET) {
                $related_bet_external_unique_id = !empty($transaction_data['external_unique_id']) ? $transaction_data['external_unique_id'] : null;
                $this->ssa_set_related_uniqueid_of_seamless_service($this->utils->mergeArrayValues([
                    'game',
                    $this->game_platform_id,
                    $related_bet_external_unique_id,
                ]));
                $this->ssa_set_related_action_of_seamless_service(Wallet_model::REMOTE_RELATED_ACTION_BET);

                $rollback_action = self::TRANSACTION_TYPE_BET;
                $adjustment_type = self::INCREASE;

            } else {
                $related_payout_external_unique_id = !empty($transaction_data['external_unique_id']) ? $transaction_data['external_unique_id'] : null;
                $this->ssa_set_related_uniqueid_of_seamless_service($this->utils->mergeArrayValues([
                    'game',
                    $this->game_platform_id,
                    $related_payout_external_unique_id,
                ]));
                $this->ssa_set_related_action_of_seamless_service(Wallet_model::REMOTE_RELATED_ACTION_PAYOUT);
                $rollback_action = self::TRANSACTION_TYPE_WIN;
                $adjustment_type = self::DECREASE;

                if ($balance < $request_params['amount']) {
                    $rollback_action = $adjustment_type = self::RETAIN;
                }
            }
        } else {
            $rollback_action = $adjustment_type = self::RETAIN;
        }

        $extra_info = [
            'rollback_action' => $rollback_action,
            'rollback_original_action_id' => $rollback_original_action_id,
            'flag_of_updated_result' => $rollback_action == self::RETAIN ? self::FLAG_RETAIN : null,
        ];

        $params = compact('transaction_type', 'rollback_action', 'transaction_id');
        $this->external_transaction_id = $this->externalTransactionId($params);

        if (!$this->transaction_already_exist) {
            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($player_id, function() use($controller, &$adjustment_type, &$request_params, &$balance, &$result, &$http_response, &$operator_response, &$extra_info) {
                list($success, $balance, $result, $http_response, $operator_response) = $controller->adjustWallet($adjustment_type, self::INSERT, $request_params, $balance, $result, $http_response, $operator_response, $extra_info);
                return $success;
            });
        } else {
            $success = true;
            $transaction_data = $this->ssa_get_transaction($this->transaction_table, ['transaction_id' => $transaction_id]);
            $balance = !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;

            $action_result = [
                'action_id' => !empty($transaction_data['transaction_id']) ? $transaction_data['transaction_id'] : null,
                'tx_id' => !empty($transaction_data['amount']) ? $transaction_data['external_unique_id'] : '',
                'processed_at' => $this->dateTimeISO8601($transaction_data['start_at']),
            ];

            $this->transaction_already_exist = false;
            return array($success, $balance, $action_result, self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
        }

        if ($success) {
            $action_result = [
                'action_id' => $transaction_id,
                'tx_id' => !empty($transaction_data) ? $this->external_transaction_id : '',
                'processed_at' => $this->dateTimeISO8601($result['processed_at']),
            ];
        }

        return array($success, $balance, $action_result, $http_response, $operator_response);
    }

    private function freespins() // need to fix, not fully implemented.
    {
        $this->callback_method = __FUNCTION__;

        $rule_sets = [
            'issue_id' => 'required',
            'status' => 'required',
            'total_amount' => 'optional',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $balance = $this->queryPlayerBalance($player_id);

        $operator_response = [
            'balance' => $this->multiplying_conversion($balance, $this->conversion, $this->precision),
        ];

        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
    }

    private function adjustWallet($adjustment_type, $query_type, $request_params, $balance, $result, $http_response, $operator_response, $extra_info = [])
    {
        $success = true;
        $before_balance = $after_balance = $balance;
        $transaction_after_balance = null;
        $amount = !empty($request_params['amount']) ? $request_params['amount'] : 0;
        $player_id = !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
        $game_code = !empty($this->request_params['game']) ? $this->request_params['game'] : null;

        # Remote wallet API Start
        $this->ssa_set_uniqueid_of_seamless_service($this->utils->mergeArrayValues([
            $this->game_platform_id,
            $this->external_transaction_id,
        ]));

        $this->ssa_set_game_provider_action_type($this->game_provider_action_type);
        $this->ssa_set_external_game_id($game_code);
        $this->ssa_set_game_provider_round_id(!empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null);
        $this->ssa_set_game_provider_is_end_round($this->is_end_round);

        // amount 0, call increase remote wallet
        if ($amount == 0 && $this->callback_method == self::TRANSACTION_TYPE_WIN) {
            if ($this->ssa_enabled_remote_wallet()) {
                $this->utils->debug_log(__METHOD__, "{$this->game_api->seamless_game_api_name}: amount 0 call remote wallet", 'request_params', $this->ssa_request_params);
                $this->ssa_increase_remote_wallet($this->player_details['player_id'], $amount, $this->game_platform_id, $after_balance);
            } 
        }
        # Remote Wallet API End


        if ($adjustment_type == self::DECREASE) {
            if ($amount > $before_balance) {
                $success = false;
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_412;
                $operator_response = self::API_ERROR_100;
                $this->player_balance_response = $before_balance;
            } else {
                if ($amount == 0) {
                    $after_balance = $before_balance;
                } else {
                    $success = $this->wallet_model->decSubWallet($player_id, $this->game_platform_id, $amount, $transaction_after_balance);

                    # treat success if remote wallet return double uniqueid
                    $this->remote_Wallet_error_code = $this->remoteWalletErrorCode();
                    if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                        if ($this->utils->isEnabledRemoteWalletClient()) {
                            if ($this->remote_Wallet_error_code == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                                $success = true;
                            }
                        }
                    }

                    if (!$success) {
                        $http_response = self::HTTP_RESPONSE_STATUS_CODE_412;
                        $operator_response = self::API_ERROR_100;
                        $this->player_balance_response = $before_balance;
                    } else {
                        $after_balance = !empty($transaction_after_balance) ? $transaction_after_balance : $this->queryPlayerBalance($player_id);
                    }
                }
            }
        } elseif ($adjustment_type == self::INCREASE) {
            if ($amount == 0) {
                $after_balance = $before_balance;
            } else {
                $success = $this->wallet_model->incSubWallet($player_id, $this->game_platform_id, $amount);

                # treat success if remote wallet return double uniqueid
                $this->remote_Wallet_error_code = $this->remoteWalletErrorCode();
                if(method_exists($this->utils, 'isEnabledRemoteWalletClient')){
                    if ($this->utils->isEnabledRemoteWalletClient()) {
                        if ($this->remote_Wallet_error_code == Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID) {
                            $success = true;
                        }
                    }
                }
                
                if ($success) {
                    $after_balance = !empty($transaction_after_balance) ? $transaction_after_balance : $this->queryPlayerBalance($player_id);
                }
            }
        } else {
            $after_balance = $before_balance;
        }

        if($this->remote_Wallet_error_code){
            foreach($this->request_params['actions'] as $action){
                $failed_external_uniqueid = $this->externalTransactionId([$action['action'], $action['action_id']]);
                $failed_transaction_data = $md5_data = [
                    'round_id' =>  $this->request_params['game_id'],
                    'transaction_id' =>$action['action_id'],
                    'external_game_id' => $this->request_params['game'],
                    'player_id' => $this->player_details['player_id'],
                    'game_username' =>  $this->request_params['user_id'],
                    'amount' => $action['amount'],
                    'balance_adjustment_type' => $adjustment_type == self::INCREASE ? 'credit' : 'debit',
                    'action' => $action['action'],
                    'game_platform_id' => $this->game_api->getPlatformCode(),
                    'transaction_raw_data' => json_encode($this->request_params),
                    'remote_raw_data' => null,
                    'remote_wallet_status' => $this->remote_Wallet_error_code,
                    'transaction_date' => null,
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

        $result = [
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
            'amount' => $amount,
            'processed_at' => $this->utils->getNowForMysql(),
            'transaction_id' => !empty($request_params['action_id']) ? $request_params['action_id'] : null,
            'jackpot_contribution' => !empty($request_params['jackpot_contribution']) ? $request_params['jackpot_contribution'] : 0,
            'jackpot_win' => !empty($request_params['jackpot_win']) ? $request_params['jackpot_win'] : 0,
            'bonus_amount' => !empty($request_params['bonus_amount']) ? $request_params['bonus_amount'] : 0,
            'rollback_action' => !empty($extra_info['rollback_action']) ? $extra_info['rollback_action'] : null,
            'rollback_original_action_id' => !empty($extra_info['rollback_original_action_id']) ? $extra_info['rollback_original_action_id'] : null,
            'flag_of_updated_result' => !empty($extra_info['flag_of_updated_result']) ? $extra_info['flag_of_updated_result'] : null,
        ];

        if ($success) {
            list($success, $http_response, $operator_response) = $this->insertOrUpdateTransaction($query_type, $result);
        }

        return array($success, $after_balance, $result, $http_response, $operator_response);
    }

    private function saveFailedTransaction($query_type='insert', $data=[], $where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $this->utils->debug_log("PP SEAMLESS SERVICE API: saveFailedTransaction",$query_type, $table_name, $data, $where);
        $this->original_seamless_wallet_transactions->saveTransactionData($table_name, $query_type, $data, $where);
    }

    private function isFailedTransactionExist($where=[]){
        $this->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $isExisting = $this->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
        $this->utils->debug_log("PP SEAMLESS SERVICE API: isFailedTransactionExist",$table_name, $where, $isExisting);
        return $isExisting;
    }

    private function remoteWalletErrorCode(){
        $this->load->model(['wallet_model']);
        if (method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
            $errorCode = $this->wallet_model->getRemoteWalletErrorCode();
            $this->utils->debug_log("PP SEAMLESS SERVICE API: remoteWalletErrorCode", $errorCode);
            return $errorCode;
        }
        return null;
    }

    private function getRawRequestData()
    {
        return file_get_contents("php://input");
    }

    private function validateApiHeaders()
    {
        $is_valid = true;
        $raw_request_data = $this->getRawRequestData();
        $x_header_sign = isset($this->request_headers['X-Request-Sign']) ? $this->request_headers['X-Request-Sign'] : null;

        if(!empty($x_header_sign) && !empty($this->game_api->dev_internal_password) && $x_header_sign == $this->game_api->dev_internal_password){
            return true;
        }
        
        $generated_request_sign = $this->game_api->generateRequestSign($raw_request_data, $this->auth_token);
        $request_sign = !empty($this->request_headers['X-Request-Sign']) ? $this->request_headers['X-Request-Sign'] : null;

        if ($generated_request_sign != $request_sign) {
            $this->custom_message = 'Invalid request sign!';
            $is_valid = false;
        }

        return $is_valid;
    }

    private function getRequestParams()
    {
        $raw_request_data = $this->getRawRequestData();
        $request_params = !empty($raw_request_data) ? json_decode($raw_request_data, true) : [];

        return $request_params;
    }

    private function validateParams($rule_sets, array $request_params)
    {
        $is_valid = true;
        $http_response = [];
        $operator_response = [];

        foreach ($rule_sets as $param => $rules) {
            $rules = explode('|', $rules);
            foreach ($rules as $rule) {
                // to check the greater than or less than value
                if (strpos($rule, ':') !== false) {
                    $condition = !empty(explode(':', $rule)[0]) ? explode(':', $rule)[0] : null;
                    $number = !empty(explode(':', $rule)[1]) ? explode(':', $rule)[1] : null;

                    if ($condition == 'greater_than') {
                        $rule = 'greater_than';
                    }

                    if ($condition == 'less_than') {
                        $rule = 'less_than';
                    }
                }

                switch ($rule) {
                    case 'optional':
                        // allowed
                        break;
                    case 'array':
                        if (isset($request_params[$param]) && !is_array($request_params[$param])) {
                            $is_valid = false;
                            $this->custom_message = 'Parameter ' . $param . ' must be an ' . $rule;
                        }
                        break;
                    case 'nullable':
                        continue 2;
                    case 'required':
                        if (in_array('nullable', $rules)) {
                            if (!array_key_exists($param, $request_params)) {
                                $is_valid = false;
                                $this->custom_message = 'Parameter ' . $param . ' is ' . $rule;
                            }
                        } else {
                            if (!array_key_exists($param, $request_params) || empty($request_params[$param])) {
                                $is_valid = false;
                                $this->custom_message = 'Parameter ' . $param . ' is ' . $rule;
                            }
                        }
                        break;
                    case 'numeric':
                        if (isset($request_params[$param]) && !is_numeric($request_params[$param])) {
                            $is_valid = false;
                            $this->custom_message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'positive':
                        if (isset($request_params[$param]) && $request_params[$param] < 0) {
                            $is_valid = false;
                            $this->custom_message = 'Parameter ' . $param . ' must be an ' . $rule;
                        }
                        break;
                    case 'greater_than':
                        if (isset($request_params[$param]) && $request_params[$param] <= $number) {
                            $is_valid = false;
                            $this->custom_message = 'Parameter ' . $param . ' is expected to be ' . str_replace('_', ' ', $rule) . ' ' . $number;
                        }
                        break;
                    case 'less_than':
                        if (isset($request_params[$param]) && $request_params[$param] >= $number) {
                            $is_valid = false;
                            $this->custom_message = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $number;
                        }
                        break;
                    case 'date_format:ISO8601':
                        if (isset($request_params[$param]) && !$this->validateISO8601DateTime($request_params[$param])) {
                            $is_valid = false;
                            $this->custom_message = 'Parameter ' . $param . ' must be ' . $rule;
                        }
                        break;
                    case 'same':
                        //register param here to check if match the system info under validation 
                        switch ($param) {
                            case 'user_id':
                                if (isset($request_params[$param])) {
                                    /* $this->player_details array keys
                                        Array 
                                        (
                                        [0] => player_id
                                        [1] => username
                                        [2] => password
                                        [3] => active
                                        [4] => blocked
                                        [5] => frozen
                                        [6] => created_at
                                        [7] => game_provider_id
                                        [8] => game_username
                                        [9] => game_password
                                        [10] => game_isregister
                                        [11] => game_status
                                        [12] => game_blocked
                                        [13] => is_demo_flag
                                        )
                                    */
                                    $this->player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($request_params[$param], $this->game_platform_id);

                                    if (empty($this->player_details)) {
                                        $is_valid = false;
                                        if (!empty($request_params[$param])) {
                                            $operator_response = self::API_ERROR_101;
                                            $this->custom_message = $param .': '. $request_params[$param] . ' not found.';
                                        }
                                    } else {
                                        $username = !empty($this->player_details['username']) ? $this->player_details['username'] : null;

                                        if ($this->game_api->isBlocked($username)) {
                                            $is_valid = false;
                                            $http_response = self::HTTP_RESPONSE_STATUS_CODE_403;
                                            $operator_response = self::API_ERROR_110;
                                            $this->custom_message = 'Player '. $request_params[$param] . ' is blocked.';
                                        }
                                    }
                                }
                                break;
                            case 'currency':
                                if (isset($this->request_params[$param]) && $this->request_params[$param] != $this->currency) {
                                    $is_valid = false;
                                    $operator_response = self::API_ERROR_154;
                                    $this->custom_message = 'Invalid Currency';
                                }
                                break;
                            default:
                                $is_valid = false;
                                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                                $operator_response = self::API_ERROR_502;
                                $this->custom_message = "Not registered key '" . $param . "' on rule '" . $rule . "'";
                                break;
                        }
                        break;
                    case 'unique':
                        //register param here to check if already exist
                        switch ($param) {
                            case 'action_id':
                                if (isset($request_params[$param])) {
                                    if ($this->ssa_is_transaction_exists($this->transaction_table, ['transaction_id' => $request_params[$param]])) {
                                        $this->transaction_already_exist = true;
                                    }
                                }
                                break;
                            default:
                                $is_valid = false;
                                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                                $operator_response = self::API_ERROR_502;
                                $this->custom_message = "Not registered key '" . $param . "' on rule '" . $rule . "'";
                                break;
                        }
                        break;
                    default:
                        $is_valid = false;
                        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                        $operator_response = self::API_ERROR_502;
                        $this->custom_message = "Invalid rule '" . $rule . "' on parameter '" . $param . "'";
                        break;
                }
            }

            if (!$is_valid) {
                break;
            }
        }

        if (!$is_valid) {
            if (empty($http_response)) {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_400;
            }
            
            if (empty($operator_response)) {
                $operator_response = self::API_ERROR_400;
            }
        } else {
            $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
            $operator_response = [];
        }

        return array($is_valid, $http_response, $operator_response);
    }

    private function computeAmount($amount, $request_amount)
    {
        return $amount += $request_amount;
    }

    private function checkBalanceBeforeAction($amount, $balance)
    {
        $proceed = true;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = [];

        if ($amount > $balance) {
            $proceed = false;
            $http_response = self::HTTP_RESPONSE_STATUS_CODE_412;
            $operator_response =self::API_ERROR_100;
            $this->player_balance_response = $balance;
        }
        
        return array($proceed, $http_response, $operator_response);
    }

    private function validateAction()
    {
        $succcess = true;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_400;
        $operator_response = self::API_ERROR_400;
        $actions = !empty($this->request_params['actions']) ? $this->request_params['actions'] : [];
        $game_username = !empty($this->request_params['user_id']) ? $this->request_params['user_id'] : null;
        $game_code = !empty($this->request_params['game']) ? $this->request_params['game'] : null;
        $round_id = !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null;

        if ($this->callback_method == self::TRANSACTION_TYPE_ROLLBACK) {
            foreach ($actions as $request_params) {
                $action = !empty($request_params['action']) ? $request_params['action'] : null;

                if ($action != self::TRANSACTION_TYPE_ROLLBACK) {
                    $succcess = false;
                    $this->custom_message = 'Action ' . $action . ' is invalid. Must be rollback action.';

                    return array($succcess, $http_response, $operator_response);
                }
            }
        } else {
            $is_already_processed_by_rollback = $this->ssa_is_transaction_exists($this->transaction_table, [
                'transaction_type' => self::TRANSACTION_TYPE_ROLLBACK,
                'game_username' => $game_username, 
                'game_code' => $game_code,
                'round_id' => $round_id,
            ]);
    
            if ($is_already_processed_by_rollback) {
                /* $succcess = false;
                $this->custom_message = 'Already processed by rollback.';

                return array($succcess, $http_response, $operator_response); */
            }

            foreach ($actions as $request_params) {
                $action = !empty($request_params['action']) ? $request_params['action'] : null;

                if (!in_array($action, self::TRANSACTION_TYPES)) {
                    $succcess = false;
                    $this->custom_message = 'Action ' . $action . ' is invalid.';

                    return array($succcess, $http_response, $operator_response);
                }
            }
        }

        return array($succcess, $http_response, $operator_response);
    }

    private function validateISO8601DateTime($arg_date_time)
    {
        if (!is_string($arg_date_time)) {
            return false;
        }

        $dateTime = \DateTime::createFromFormat(\DateTime::ISO8601, $arg_date_time);

        if ($dateTime) {
            return $dateTime->format(\DateTime::ISO8601) === $arg_date_time;
        }

        return false;
    }

    private function dateTimeISO8601($datetime)
    {
        return date('Y-m-d\TH:i:s', strtotime($datetime)) . 'Z';
    }

    private function queryPlayerBalance($player_id)
    {
        if (isset($this->request_params['game'])) {
            $this->wallet_model->setExternalGameId($this->request_params['game']);
        }

        list($success, $balance) = $this->game_api->queryPlayerBalance($player_id);

        if ($success) {
            $result = $balance;
        } else {
            $result = false;
        }

        return $result;
    }

    private function truncateAmount($amount, $precision = 2)
    {
        $amount = floatval($amount);

        if ($amount == 0) {
            return $amount;
        }

        return floatval(bcdiv($amount, 1, $precision));
    }

    private function multiplying_conversion($amount, $conversion = 1, $precision = 2)
    {
        // return $this->truncateAmount($amount, $precision) * $conversion;

        $value = floatval($amount * $conversion);
        return $this->truncateAmount($value, $precision);
    }

    private function externalTransactionId($params)
    {
        $compose_external_transaction_id = array_reduce($params, function ($arg1, $arg2) {
            return $arg1 . '-' . $arg2;
        });

        $external_transaction_id = ltrim($compose_external_transaction_id, '-');

        return $external_transaction_id;
    }

    private function insertOrUpdateTransaction($query_type, $result)
    {
        $rule_sets = [
            'before_balance' => 'optional',
            'after_balance' => 'optional',
            'amount' => 'required|nullable',
            'processed_at' => 'required',
            'transaction_id' => 'required',
            'jackpot_contribution' => 'optional',
            'jackpot_win' => 'optional',
            'bonus_amount' => 'optional',
            'rollback_action' => 'optional',
            'rollback_original_action_id' => 'optional',
            'flag_of_updated_result' => 'optional',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateParams($rule_sets, $result);

        $success = false;

        if ($is_valid) {
            $success = true;
            $this->processed_transaction_id = $this->preprocessRequestData($query_type, $result);

            if (!$this->processed_transaction_id) {
                $success = false;
                $operator_response = self::API_ERROR_502;
            } else {
                if ($query_type == self::INSERT) {
                    array_push($this->processed_multiple_transaction, $this->processed_transaction_id);
                }
            }
        }

        return array($success, $http_response, $operator_response);
    }

    private function preprocessRequestData($query_type, $transaction_data)
    {
        $processed_transaction_id = null;
        $is_finished = isset($this->request_params['finished']) && $this->request_params['finished'];

        if ($query_type == self::INSERT) {
            $field_id = $id = null;
            $update_with_result = false;

            $processed_data = [
                'game_platform_id' => $this->game_platform_id,
                'player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                'game_username' => !empty($this->request_params['user_id']) ? $this->request_params['user_id'] : null,
                'language' => $this->language,
                'currency' => $this->currency,
                'transaction_type' => $this->callback_method,
                'transaction_id' => $transaction_data['transaction_id'],
                'game_code' => !empty($this->request_params['game']) ? $this->request_params['game'] : null,
                'round_id' => !empty($this->request_params['game_id']) ? $this->request_params['game_id'] : null,
                'amount' => $transaction_data['amount'],
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'status' => $this->setStatus($this->callback_method, $is_finished),
                'start_at' => $transaction_data['processed_at'],
                'end_at' => $this->utils->getNowForMysql(),
                'casino_id' => $this->casino_id,
                'rollback_action' => $transaction_data['rollback_action'],
                'rollback_original_action_id' => $transaction_data['rollback_original_action_id'],
                'jackpot_contribution' => $transaction_data['jackpot_contribution'],
                'jackpot_win' => $transaction_data['jackpot_win'],
                'bonus_amount' => $transaction_data['bonus_amount'],
                'elapsed_time' => intval($this->utils->getExecutionTimeToNow() * 1000),
                'extra_info' => json_encode($this->request_params),
                'bet_amount' => 0,
                'win_amount' => 0,
                'result_amount' => 0,
                'flag_of_updated_result' => !empty($transaction_data['flag_of_updated_result']) ? $transaction_data['flag_of_updated_result'] : self::FLAG_NOT_UPDATED,
                'external_unique_id' => $this->external_transaction_id,
            ];
            
            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);

        } else {
            $field_id = 'external_unique_id';
            $id = $this->external_transaction_id;
            $update_with_result = true;

            $processed_data = [
                'transaction_type' => $this->callback_method,
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'amount' => $transaction_data['amount'],
                'end_at' => $this->utils->getNowForMysql(),
                'updated_at' => $this->utils->getNowForMysql(),
                'status' => $this->setStatus($this->callback_method, $is_finished),
                'bet_amount' => 0,
                'win_amount' => 0,
                'result_amount' => 0,
                'flag_of_updated_result' => self::FLAG_NOT_UPDATED,
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
        }

        $processed_transaction_id = $this->ssa_insert_update_transaction($this->transaction_table, $query_type, $processed_data, $field_id, $id, $update_with_result);

        return $processed_transaction_id;
    }

    private function setStatus($action, $status)
    {
        if ($status) {
            switch ($action) {
                case self::TRANSACTION_TYPE_BET:
                case self::TRANSACTION_TYPE_WIN:
                    $result = Game_logs::STATUS_SETTLED;
                    break;
                case self::TRANSACTION_TYPE_ROLLBACK:
                    $result = Game_logs::STATUS_REFUND;
                    break;
                default:
                    $result = Game_logs::STATUS_PENDING;
                    break;
            }
        } else {
            $result = Game_logs::STATUS_PENDING;
        }

        return $result;
    }

    private function saveResponseResult($flag, $operator_response, $http_response)
    {
        $response_result_id = $this->response_result->saveResponseResult(
            $this->game_platform_id,
            $flag,
            $this->callback_method,
            json_encode($this->request_params),
            !empty($operator_response) ? $operator_response : null,
            !empty($http_response['code']) ? $http_response['code'] : self::HTTP_RESPONSE_STATUS_CODE_500['code'],
            !empty($http_response['status_text']) ? $http_response['status_text'] : self::HTTP_RESPONSE_STATUS_CODE_500['status_text'],
            is_array($this->request_headers) ? json_encode($this->request_headers) : $this->request_headers,
            ['player_id' => !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null],
            false,
            null,
            intval($this->utils->getExecutionTimeToNow() * 1000) //costMs
        );

        return $response_result_id;
    }

    private function setResponse($http_response, $operator_response = [])
    {
        $http_response_code = !empty($http_response['code']) ? $http_response['code'] : self::HTTP_RESPONSE_STATUS_CODE_500['code'];
        $flag = $http_response_code == self::HTTP_RESPONSE_STATUS_CODE_200['code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if (!empty($this->custom_message)) {
            $operator_response['message'] = $this->custom_message;
        }

        if (!is_null($this->player_balance_response)) {
            $operator_response['balance'] = $this->multiplying_conversion($this->player_balance_response, $this->conversion, $this->precision);
        }

        $this->response_result_id = $this->saveResponseResult($flag, $operator_response, $http_response);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];
    
                $this->ssa_update_transaction_without_result($this->transaction_table, $updated_data, 'id', $processed_transaction_id);
            }
        }

        return $this->returnJsonResult($operator_response, true, '*', false, false, $http_response_code);
    }
}