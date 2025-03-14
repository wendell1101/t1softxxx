<?php 

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';

class Idnlive_seamless_service_api extends BaseController
{
    private $game_api;
    private $game_platform_id;
    private $headers;
    private $callback_method;
    private $response;
    private $response_result_id;
    private $response_parameters;
    private $request_parameters;
    private $custom_message;
    private $operator_id;
    private $player_details;
    private $data;
    private $processed_transaction_id;
    private $processed_multiple_transaction;
    private $external_transaction_id;
    private $http_status_response;
    private $check_credit_exist;
    private $check_refund_exist;

    const SEAMLESS_GAME_API_ID = IDNLIVE_SEAMLESS_GAME_API;

    const SUCCESS = [
        'status' => true,
        'error_code' => 0,
        'message' => 'Success',
    ];

    const ERROR_RESPONSE_INSUFFICIENT_BALANCE = [
        'status' => false,
        'error_code' => 301,
        'message' => 'Insufficient Balance',
    ];

    const ERROR_RESPONSE_OPERATOR_SIDE = [
        'status' => false,
        'error_code' => 302,
        'message' => 'There is a problem in the client connection.',
    ];

    const ERROR_RESPONSE_BAD_PARAMETER_REQUEST = [
        'status' => false,
        'error_code' => 303,
        'message' => 'Bad Parameter Request',
    ];

    const ERROR_RESPONSE_GAME_NOT_FOUND = [
        'status' => false,
        'error_code' => 304,
        'message' => 'Game is not found.',
    ];

    const ERROR_RESPONSE_BAD_REQUEST = [
        'status' => false,
        'error_code' => 400,
        'message' => 'Bad Request',
    ];

    const ERROR_RESPONSE_INTERNAL_SERVER_ERROR = [
        'status' => false,
        'error_code' => 500,
        'message' => 'Internal Server Error',
    ];

    const HTTP_RESPONSE_STATUS_CODE_200 = [
        'status' => 'ok',
        'status_code' => 200,
        'status_text' => 'OK',
        'description' => 'Success'
    ];

    const HTTP_RESPONSE_STATUS_CODE_400 = [
        'status' => 'error',
        'status_code' => 400,
        'status_text' => 'Bad Request',
        'description' => 'Malformed request syntax, Invalid request message framing, or Deceptive request routing'
    ];

    const HTTP_RESPONSE_STATUS_CODE_401 = [
        'status' => 'error',
        'status_code' => 401,
        'status_text' => 'Unauthorized',
        'description' => 'Request has not been completed because it lacks valid authentication credentials for the requested resource.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_404 = [
        'status' => 'error',
        'status_code' => 404,
        'status_text' => 'Not Found',
        'description' => 'The server can not find the requested resource.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_500 = [
        'status' => 'error',
        'status_code' => 500,
        'status_text' => 'Internal Server Error',
        'description' => 'Internal Server Error'
    ];

    const HTTP_RESPONSE_STATUS_CODE_501 = [
        'status' => 'error',
        'status_code' => 501,
        'status_text' => 'Not Implemented',
        'description' => 'The request method is not supported by the server and cannot be handled.'
    ];

    const HTTP_RESPONSE_STATUS_CODE_503 = [
        'status' => 'error',
        'status_code' => 503,
        'status_text' => 'Service Unavailable',
        'description' => 'The server is not ready to handle the request. Common causes are a server that is down for maintenance or that is disabled.'
    ];

    const ERROR_MESSAGE_INVALID_OPERATOR_ID = 'Invalid Operator ID';
    const ERROR_MESSAGE_INVALID_TOKEN = 'Invalid Token';
    const ERROR_MESSAGE_INVALID_CURRENCY = 'Invalid Currency';
    const ERROR_MESSAGE_INVALID_LANGUAGE = 'Invalid Language';
    const ERROR_MESSAGE_INVALID_UID = 'Invalid UID';
    const ERROR_MESSAGE_INVALID_NICKNAME = 'Invalid Nickname';
    const ERROR_MESSAGE_INVALID_AMOUNT = 'Invalid Amount';
    const ERROR_MESSAGE_INVALID_ROUND_ID = 'Invalid Round Id';
    const ERROR_MESSAGE_INVALID_GAME_ID = 'Invalid Game Id';
    const ERROR_MESSAGE_INVALID_CALLBACK_METHOD = 'Invalid Callback Method';
    const ERROR_MESSAGE_EMPTY_POST_PARAMS = 'Empty Post Params';
    const ERROR_MESSAGE_GAME_MAINTENANCE = 'Game is maintenance.';
    const ERROR_MESSAGE_GAME_DISABLED = 'Game is disabled.';
    const ERROR_MESSAGE_IP_ADDRESS_NOT_ALLOWED = 'IP address is not allowed.';
    const ERROR_MESSAGE_AUTHORIZATION_FAILED = 'Authorization Failed, Invalid Auth Type';
    const ERROR_MESSAGE_INVALID_AUTHORIZATION = 'Authorization Failed, Invalid Authorization Header';
    const ERROR_MESSAGE_INVALID_API_KEY = 'Authorization Failed, Invalid API Key Header';
    const ERROR_MESSAGE_EMPTY_AUTHORIZATION = 'Authorization Failed, Empty Authorization Header';
    const ERROR_MESSAGE_EMPTY_API_KEY = 'Authorization Failed, Empty API Key Header';
    const ERROR_MESSAGE_UNEXPECTED_REVISION = 'Unexpected Revision. Transaction not exist.';
    const ERROR_MESSAGE_TRANSACTION_ALREADY_EXISTS = 'Transaction Already Exists';
    const ERROR_MESSAGE_ROUND_NOT_EXIST = 'Round not exist';
    const ERROR_MESSAGE_PLAYER_NOT_EXIST = 'Player not exist';
    const ERROR_MESSAGE_PLAYER_NOT_FOUND = 'Player not found';
    const ERROR_MESSAGE_BET_NOT_EXIST = 'Bet not exist';
    const ERROR_MESSAGE_ALREADY_SETTLED_BY_CREDIT = 'Already settled by credit.';
    const ERROR_MESSAGE_ALREADY_SETTLED_BY_REFUND = 'Already settled by refund.';

    const MAIN_METHOD = 'index';
    const CALLBACK_METHOD_AUTH_TOKEN = 'authToken';
    const CALLBACK_METHOD_DEBIT = 'debit';
    const CALLBACK_METHOD_REVISION = 'revision';
    const CALLBACK_METHOD_CREDIT = 'credit';
    const CALLBACK_METHOD_REFUND = 'refund';

    const CALLBACK_METHODS = [
        self::CALLBACK_METHOD_AUTH_TOKEN,
        self::CALLBACK_METHOD_DEBIT,
        self::CALLBACK_METHOD_REVISION,
        self::CALLBACK_METHOD_CREDIT,
        self::CALLBACK_METHOD_REFUND,
    ];

    const CALLBACK_TRANSACTION_METHODS = [
        self::CALLBACK_METHOD_DEBIT,
        self::CALLBACK_METHOD_REVISION,
        self::CALLBACK_METHOD_CREDIT,
        self::CALLBACK_METHOD_REFUND,
    ];

    const CALLBACK_MULTIPLE_REQUEST_METHODS = [
        self::CALLBACK_METHOD_CREDIT,
        self::CALLBACK_METHOD_REFUND,
    ];

    const ACTION_METHOD_GET_PLAYER_TOKEN = 'getPlayerToken';

    const ACTION_METHODS = [
        self::ACTION_METHOD_GET_PLAYER_TOKEN,
    ];

    const NO_AUTH = 'no_auth';
    const BASIC_AUTH = 'basic_auth';
    const DIGEST_AUTH = 'digest_auth';

    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;

    const STATUS_BET = 'bet';
    const STATUS_WIN = 'win';
    const STATUS_LOSE = 'lose';
    const STATUS_REFUND = 'refund';

    const BET_TYPE_BUY = 'Buy';
    const BET_TYPE_CORRECTION = 'Correction';
    const BET_TYPE_WIN = 'Win';
    const BET_TYPE_REFUND = 'Refund';

    const ACTION_INCREASE = 'increase';
    const ACTION_DECREASE = 'decrease';

    const REQUEST_TYPE_SINGLE = 'single_request';
    const REQUEST_TYPE_MULTIPLE = 'multiple_request';

    const MD5_FIELDS_FOR_ORIGINAL = [
        'game_platform_id',
        'amount',
        'before_balance',
        'after_balance',
        'player_id',
        'game_id',
        'transaction_type',
        'status',
        'extra_info',
        'start_at',
        'end_at',
        'transaction_id',
        'round_id',
        'bet_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_DEBIT_UPDATE = [
        'status',
        'updated_at',
        'bet_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_DEBIT_UPDATE = [
        'bet_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_UPDATE = [
        'response_result_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_UPDATE = [];

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(['common_token', 'common_seamless_wallet_transactions', 'common_seamless_error_logs', 'wallet_model', 'external_system']);
        $this->game_platform_id = self::SEAMLESS_GAME_API_ID;
        $this->headers = $this->response = $this->response_parameters = $this->request_parameters = $this->data = $this->processed_multiple_transaction = $this->http_status_response = [];
        $this->external_transaction_id = $this->processed_transaction_id = $this->response_result_id = $this->callback_method = null;
        $this->custom_message = '';
        $this->data['balance'] = 0;
    }

    public function index($game_api_id, $callback_method)
    {
        $this->headers = getallheaders();
        $this->request_parameters = $this->getRequestParameters();
        $this->callback_method = $this->getCallbackType($callback_method);

        if (empty($game_api_id) || $game_api_id != $this->game_platform_id) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_INTERNAL_SERVER_ERROR, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_500);
        }

        $this->game_api = $this->CI->utils->loadExternalSystemLibObject($game_api_id);

        if (!$this->game_api) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_GAME_NOT_FOUND, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_404);
        }

        if (!$this->game_api->validateWhiteIP()) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, self::ERROR_MESSAGE_IP_ADDRESS_NOT_ALLOWED, self::HTTP_RESPONSE_STATUS_CODE_401);
        }

        $this->game_platform_id = $this->game_api->getPlatformCode();
        $this->check_credit_exist = $this->game_api->check_credit_exist;
        $this->check_refund_exist = $this->game_api->check_refund_exist;

        if($this->CI->external_system->isGameApiMaintenance($this->game_platform_id)) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_GAME_NOT_FOUND, self::ERROR_MESSAGE_GAME_MAINTENANCE, self::HTTP_RESPONSE_STATUS_CODE_404);
        }

        if(!$this->CI->external_system->isGameApiActive($this->game_platform_id)) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_GAME_NOT_FOUND, self::ERROR_MESSAGE_GAME_DISABLED, self::HTTP_RESPONSE_STATUS_CODE_404);
        }

        if (in_array($callback_method, self::ACTION_METHODS)) {
            return $this->getPlayerToken();
        }

        if (!in_array($callback_method, self::CALLBACK_METHODS)) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, self::ERROR_MESSAGE_INVALID_CALLBACK_METHOD, self::HTTP_RESPONSE_STATUS_CODE_404);
        }

        if (!$this->validateApiAuthHeaders()) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_401);
        }

        $rules_set = [
            'operatorId' => 'required',
        ];

        $validate_request_parameters = $this->validateRequestParameters($rules_set, $this->request_parameters);

        if (!$validate_request_parameters['is_valid']) {
            $this->custom_message = 'Required ' . $validate_request_parameters['required_parameter'];
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $this->operator_id = isset($this->request_parameters['operatorId']) && !empty($this->request_parameters['operatorId']) ? $this->request_parameters['operatorId'] : null;

        if ($this->operator_id != $this->game_api->operator_id) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_OPERATOR_ID, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        return $this->$callback_method();
    }

    private function getRequestParameters()
    {
        $get_json_request_parameters = file_get_contents('php://input');
        $request_parameters = json_decode($get_json_request_parameters, true);
		return !empty($request_parameters) ? $request_parameters : [];
    }

    private function validateRequestParameters($rules_set, $request_parameters)
    {
        $result = [
            'is_valid' => true,
        ];

        foreach ($rules_set as $required_parameter => $rules) {
            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                if ($rule == 'required' && !array_key_exists($required_parameter, $request_parameters)) {
                    $result['is_valid'] = false;
                    $result['required_parameter'] = $required_parameter;
                    break;
                }
            }

            if (!$result['is_valid']) {
                break;
            }
        }

        return $result;
    }

    private function getCallbackType($callback_method)
    {
        $callback_type = self::MAIN_METHOD;

        if (in_array($callback_method, self::CALLBACK_METHODS) || in_array($callback_method, self::ACTION_METHODS)) {
            $callback_type = $callback_method;

            if (isset($this->request_parameters['revision']) && $this->request_parameters['revision']) {
                $callback_type = self::CALLBACK_METHOD_REVISION;
            }

            if (isset($this->request_parameters['post_params'][0]['betType']) && $this->request_parameters['post_params'][0]['betType'] == self::BET_TYPE_REFUND) {
                $callback_type = self::CALLBACK_METHOD_REFUND;
            }
        }
    
        return $callback_type;
    }

    private function validateApiAuthHeaders()
    {
        $is_valid = false;
        $api_key = $this->game_api->api_key;
        $basic_auth = 'Basic ' . base64_encode($this->game_api->auth_user . ':' . $this->game_api->auth_password);
        $header_api_key = isset($this->headers['Api-Key']) && !empty($this->headers['Api-Key']) ? $this->headers['Api-Key'] : null;
        $header_authorization = isset($this->headers['Authorization']) && !empty($this->headers['Authorization']) ? $this->headers['Authorization'] : null;

        switch ($this->game_api->auth_type) {
            case self::NO_AUTH:
                $is_valid = true;
                break;
            case self::BASIC_AUTH:
                //validate api key
                if (!empty($header_api_key)) {
                    if ($header_api_key == $api_key) {
                        //validate authorization
                        if (!empty($header_authorization)) {
                            if ($header_authorization == $basic_auth) {
                                $is_valid = true;
                            } else {
                                $this->custom_message = self::ERROR_MESSAGE_INVALID_AUTHORIZATION;
                            }
                        } else {
                            $this->custom_message = self::ERROR_MESSAGE_EMPTY_AUTHORIZATION;
                        }
                    } else {
                        $this->custom_message = self::ERROR_MESSAGE_INVALID_API_KEY;
                    }
                } else {
                    $this->custom_message = self::ERROR_MESSAGE_EMPTY_API_KEY;
                }
                break;
            default:
                $this->custom_message = self::ERROR_MESSAGE_AUTHORIZATION_FAILED;
                $is_valid = false;
                break;
        }

        return $is_valid;
    }

    protected function authToken()
    {
        $rules_set = [
            'token' => 'required',
            'currency' => 'required',
        ];

        $validate_request_parameters = $this->validateRequestParameters($rules_set, $this->request_parameters);

        if (!$validate_request_parameters['is_valid']) {
            $this->custom_message = 'Required ' . $validate_request_parameters['required_parameter'];
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_400);
        }
        
        $this->data['player_token'] = isset($this->request_parameters['token']) && !empty($this->request_parameters['token']) ? $this->request_parameters['token'] : null;
        $this->data['currency'] = isset($this->request_parameters['currency']) && !empty($this->request_parameters['currency']) ? $this->request_parameters['currency'] : null;
        $this->player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->data['player_token'], $this->game_platform_id);

        if (empty($this->player_details)) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_TOKEN, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        if (!$this->common_token->isTokenValid($this->player_details['player_id'], $this->data['player_token'])) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_TOKEN, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        if ($this->data['currency'] != $this->game_api->currency) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_CURRENCY, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $this->data['balance'] = $this->game_api->dBtoGameAmount(floatval($this->queryPlayerBalance($this->player_details['username'])));
        $this->response_parameters = $this->setResponseParameters($this->data);

        return $this->outputResponseResult(self::SUCCESS, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_200);
    }

    protected function debit()
    {
        $rules_set = [
            'sessionToken' => 'required',
            'post_params' => 'required',
        ];

        $validate_request_parameters = $this->validateRequestParameters($rules_set, $this->request_parameters);

        if (!$validate_request_parameters['is_valid']) {
            $this->custom_message = 'Required ' . $validate_request_parameters['required_parameter'];
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $this->data['valid'] = true;
        $this->data['data'] = [];
        $this->data['time'] = isset($this->request_parameters['time']) && !empty($this->request_parameters['time']) ? $this->request_parameters['time'] : null;
        $this->data['player_token'] = isset($this->request_parameters['sessionToken']) && !empty($this->request_parameters['sessionToken']) ? $this->request_parameters['sessionToken'] : null;
        $this->data['revision'] = isset($this->request_parameters['revision']) && !empty($this->request_parameters['revision']) ? $this->request_parameters['revision'] : false;
        $post_params = isset($this->request_parameters['post_params']) && !empty($this->request_parameters['post_params']) ? $this->request_parameters['post_params'] : [];

        if (empty($post_params)) {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_EMPTY_POST_PARAMS, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $request_count = count($post_params);

        foreach ($post_params as $post_request_params) {
            $rules_set = [
                'uid' => 'required',
                'transactionId' => 'required',
                'roundId' => 'required',
                'gameId' => 'required',
                'currency' => 'required',
                'nickname' => 'required',
                'debitAmount' => 'required',
                'betType' => 'required',
                'betDetails' => 'required',
                'jsonDetails' => 'required',
            ];

            $validate_request_parameters = $this->validateRequestParameters($rules_set, $post_request_params);

            if (!$validate_request_parameters['is_valid']) {
                if ($request_count > 1) {
                    $this->data['valid'] = false;
                } else {
                    $this->data['valid'] = false;
                    $this->custom_message = 'Required ' . $validate_request_parameters['required_parameter'];
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_400);
                }
            }

            $this->data['uid'] = isset($post_request_params['uid']) && !empty($post_request_params['uid']) ? $post_request_params['uid'] : null;
            $this->data['nickname'] = isset($post_request_params['nickname']) && !empty($post_request_params['nickname']) ? strtolower($post_request_params['nickname']) : null;
            $this->data['currency'] = isset($post_request_params['currency']) && !empty($post_request_params['currency']) ? $post_request_params['currency'] : null;
            $this->data['transaction_id'] = isset($post_request_params['transactionId']) && !empty($post_request_params['transactionId']) ? $post_request_params['transactionId'] : null;
            $this->data['round_id'] = isset($post_request_params['roundId']) && !empty($post_request_params['roundId']) ? $post_request_params['roundId'] : null;
            $this->data['game_id'] = isset($post_request_params['gameId']) && !empty($post_request_params['gameId']) ? $post_request_params['gameId'] : null;
            $this->data['amount'] = isset($post_request_params['debitAmount']) && !empty($post_request_params['debitAmount']) ? $post_request_params['debitAmount'] : 0;
            $this->data['bet_status'] = isset($post_request_params['betStatus']) && !empty($post_request_params['betStatus']) ? $post_request_params['betStatus'] : null;
            $this->data['bet_type'] = isset($post_request_params['betType']) && !empty($post_request_params['betType']) ? $post_request_params['betType'] : null;
            $this->data['bet_details'] = isset($post_request_params['betDetails']) && !empty($post_request_params['betDetails']) ? $post_request_params['betDetails'] : null;
            $this->data['json_details'] = isset($post_request_params['jsonDetails']) && !empty($post_request_params['jsonDetails']) ? $post_request_params['jsonDetails'] : null;
            $this->data['external_unique_id'] = $this->external_transaction_id = $this->setExternalUniqueId($this->data['transaction_id']);

            if ($request_count == 1 && $this->callback_method == self::CALLBACK_METHOD_DEBIT) {
                $this->player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->data['player_token'], $this->game_platform_id);

                if (empty($this->player_details)) {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_TOKEN, self::HTTP_RESPONSE_STATUS_CODE_400);
                }

                if (!$this->common_token->isTokenValid($this->player_details['player_id'], $this->data['player_token'])) {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_TOKEN, self::HTTP_RESPONSE_STATUS_CODE_400);
                }

                $this->data['balance'] = $this->game_api->dBtoGameAmount(floatval($this->queryPlayerBalance($this->player_details['username'])));

                if ($this->data['uid'] != $this->player_details['player_id']) {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_UID, self::HTTP_RESPONSE_STATUS_CODE_400);
                }

                if ($this->data['nickname'] != $this->player_details['game_username']) {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_NICKNAME, self::HTTP_RESPONSE_STATUS_CODE_400);
                }

                if ($this->data['currency'] != $this->game_api->currency) {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_CURRENCY, self::HTTP_RESPONSE_STATUS_CODE_400);
                }

                $is_external_transacton_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->game_platform_id, $this->data['external_unique_id']);
                $is_transacton_id_exist = $this->common_seamless_wallet_transactions->isTransactionExistCustom($this->game_platform_id, $this->data['transaction_id']);

                if ($is_external_transacton_exist || $is_transacton_id_exist) {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, self::ERROR_MESSAGE_TRANSACTION_ALREADY_EXISTS, self::HTTP_RESPONSE_STATUS_CODE_400);
                } else {
                    //if transaction is not exist, adjust wallet and insert new transaction.
                    $is_transaction_success = $this->walletAdjustment(self::ACTION_DECREASE);

                    if ($is_transaction_success) {
                        $response_data = [
                            'uid' => $this->data['uid'],
                            'valid' => $this->data['valid'],
                            'balance' => $this->data['balance'],
                            'roundId' => $this->data['round_id'],
                            'currency' => $this->data['currency'],
                            'nickname' => $this->data['nickname'],
                            'timestamp' => $this->CI->utils->getTimestampNow(),
                            'transactionId' => $this->data['transaction_id'],
                        ];
    
                        array_push($this->data['data'], $response_data);
                        $this->response_parameters = $this->setResponseParameters($this->data);

                        return $this->outputResponseResult(self::SUCCESS, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_200);
                    } else {
                        return $this->outputResponseResult($this->response, $this->custom_message, $this->http_status_response);
                    }
                }
            } else {
                if ($this->data['valid']) {
                    $this->player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($this->data['nickname'], $this->game_platform_id);

                    if (!empty($this->player_details)) {
                        if (isset($this->player_details['player_id']) && $this->player_details['player_id'] != $this->data['uid'] && isset($this->player_details['game_username']) && $this->player_details['game_username'] != $this->data['nickname'] && $this->game_api->currency != $this->data['currency']) {
                            $this->data['valid'] = false;
                        }
                    } else {
                        $this->data['valid'] = false;
                    }

                    if ($this->data['valid']) {
                        if (isset($this->player_details['username']) && !empty($this->player_details['username'])) {
                            $this->data['balance'] = $this->game_api->dBtoGameAmount(floatval($this->queryPlayerBalance($this->player_details['username'])));
                        }

                        $is_external_transacton_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->game_platform_id, $this->data['external_unique_id']);
                        $is_transacton_id_exist = $this->common_seamless_wallet_transactions->isTransactionExistCustom($this->game_platform_id, $this->data['transaction_id']);
        
                        if ($is_external_transacton_exist || $is_transacton_id_exist) {
                            $this->data['valid'] = false;
                        } else {
                            if (!$this->data['revision']) {
                                //if transaction is not exist, adjust wallet and insert new transaction.
                                $is_transaction_success = $this->walletAdjustment(self::ACTION_DECREASE);
            
                                if (!$is_transaction_success) {
                                    $this->data['valid'] = false;
                                } else {
                                    $this->data['valid'] = true;
                                }
                            } else {
                                $this->revision(self::REQUEST_TYPE_MULTIPLE);
                            }
                        }
                    }
                }

                $response_data = [
                    'uid' => $this->data['uid'],
                    'valid' => $this->data['valid'],
                    'balance' => $this->data['balance'],
                    'roundId' => $this->data['round_id'],
                    'currency' => $this->data['currency'],
                    'nickname' => $this->data['nickname'],
                    'timestamp' => $this->CI->utils->getTimestampNow(),
                    'transactionId' => $this->data['transaction_id'],
                ];

                array_push($this->data['data'], $response_data);
                $this->response_parameters = $this->setResponseParameters($this->data);
                $this->data['valid'] = true;
            }
        }

        $this->response_parameters = $this->setResponseParameters($this->data);
        return $this->outputResponseResult(self::SUCCESS, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_200);
    }

    protected function revision($request_type)
    {
        $round_and_game = $this->game_api->getRoundAndGame(self::CALLBACK_METHOD_DEBIT, $this->data['round_id']);

        if ($request_type == self::REQUEST_TYPE_MULTIPLE) {
            if (!empty($round_and_game)) {
                if ($round_and_game['game_id'] == $this->data['game_id']) {
                    if ($this->data['bet_status'] == self::STATUS_WIN) {
                        $is_transaction_success = $this->walletAdjustment(self::ACTION_INCREASE);
                    } else {
                        $is_transaction_success = $this->walletAdjustment(self::ACTION_DECREASE);
                    }
    
                    if ($is_transaction_success) {
                        $this->data['valid'] = true;
                    } else {
                        $this->data['valid'] = false;
                    }
                } else {
                    $this->data['valid'] = false;
                }
            } else {
                $this->data['valid'] = false;
            }
        } else {
            if (!empty($round_and_game)) {
                if ($round_and_game['game_id'] == $this->data['game_id']) {
                    if ($this->data['bet_status'] == self::STATUS_WIN) {
                        $is_transaction_success = $this->walletAdjustment(self::ACTION_INCREASE);
                    } else {
                        $is_transaction_success = $this->walletAdjustment(self::ACTION_DECREASE);
                    }
    
                    if ($is_transaction_success) {
                        $response_data = [
                            'uid' => $this->data['uid'],
                            'valid' => $this->data['valid'],
                            'balance' => $this->data['balance'],
                            'roundId' => $this->data['round_id'],
                            'currency' => $this->data['currency'],
                            'nickname' => $this->data['nickname'],
                            'timestamp' => $this->CI->utils->getTimestampNow(),
                            'transactionId' => $this->data['transaction_id'],
                        ];

                        array_push($this->data['data'], $response_data);
                        $this->response_parameters = $this->setResponseParameters($this->data);

                        return $this->outputResponseResult(self::SUCCESS, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_200);
                    } else {
                        return $this->outputResponseResult($this->response, $this->custom_message, $this->http_status_response);
                    }
                } else {
                    return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, self::ERROR_MESSAGE_INVALID_GAME_ID, self::HTTP_RESPONSE_STATUS_CODE_400);
                }
            } else {
                return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, self::ERROR_MESSAGE_ROUND_NOT_EXIST, self::HTTP_RESPONSE_STATUS_CODE_400);
            }
        }
    }

    protected function credit()
    {
        $this->data['valid'] = false;
        $this->data['data'] = [];
        $this->data['time'] = isset($this->request_parameters['time']) && !empty($this->request_parameters['time']) ? $this->request_parameters['time'] : null;
        $this->data['player_token'] = isset($this->request_parameters['sessionToken']) && !empty($this->request_parameters['sessionToken']) ? $this->request_parameters['sessionToken'] : null;
        $post_params = isset($this->request_parameters['post_params']) && !empty($this->request_parameters['post_params']) ? $this->request_parameters['post_params'] : [];
        $game_result = isset($this->request_parameters['game_result']) && !empty($this->request_parameters['game_result']) ? $this->request_parameters['game_result'] : [];
        $request_count = count($post_params);

        if (!empty($post_params)) {
            foreach ($post_params as $post_request_params) {
                $rules_set = [
                    'uid' => 'required',
                    'transactionId' => 'required',
                    'roundId' => 'required',
                    'gameId' => 'required',
                    'currency' => 'required',
                    'nickname' => 'required',
                    'creditAmount' => 'required',
                    'betType' => 'required',
                    'betDetails' => 'required',
                    'jsonDetails' => 'required',
                ];

                $validate_request_parameters = $this->validateRequestParameters($rules_set, $post_request_params);

                if ($validate_request_parameters['is_valid']) {
                    $this->data['valid'] = true;
                } else {
                    if ($request_count > 1) {
                        $this->data['valid'] = false;
                    } else {
                        $this->custom_message = 'Required ' . $validate_request_parameters['required_parameter'];
                        return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_400);
                    }
                }

                $this->data['uid'] = isset($post_request_params['uid']) && !empty($post_request_params['uid']) ? $post_request_params['uid'] : null;
                $this->data['nickname'] = isset($post_request_params['nickname']) && !empty($post_request_params['nickname']) ? strtolower($post_request_params['nickname']) : null;
                $this->data['currency'] = isset($post_request_params['currency']) && !empty($post_request_params['currency']) ? $post_request_params['currency'] : null;
                $this->data['transaction_id'] = isset($post_request_params['transactionId']) && !empty($post_request_params['transactionId']) ? $post_request_params['transactionId'] : null;
                $this->data['round_id'] = isset($post_request_params['roundId']) && !empty($post_request_params['roundId']) ? $post_request_params['roundId'] : null;
                $this->data['game_id'] = isset($post_request_params['gameId']) && !empty($post_request_params['gameId']) ? $post_request_params['gameId'] : null;
                $this->data['amount'] = isset($post_request_params['creditAmount']) && !empty($post_request_params['creditAmount']) ? $post_request_params['creditAmount'] : 0;
                $this->data['bet_status'] = $this->callback_method == self::CALLBACK_METHOD_CREDIT ? self::STATUS_WIN : self::STATUS_REFUND;
                $this->data['bet_type'] = isset($post_request_params['betType']) && !empty($post_request_params['betType']) ? $post_request_params['betType'] : null;
                $this->data['bet_details'] = isset($post_request_params['betDetails']) && !empty($post_request_params['betDetails']) ? $post_request_params['betDetails'] : null;
                $this->data['json_details'] = isset($post_request_params['jsonDetails']) && !empty($post_request_params['jsonDetails']) ? $post_request_params['jsonDetails'] : null;
                $this->data['external_unique_id'] = $this->external_transaction_id = $this->setExternalUniqueId($this->data['transaction_id']);
                $this->player_details = (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($this->data['nickname'], $this->game_platform_id);

                if (isset($this->player_details['username']) && !empty($this->player_details['username'])) {
                    $this->data['balance'] = $this->game_api->dBtoGameAmount(floatval($this->queryPlayerBalance($this->player_details['username'])));
                }

                $is_external_transacton_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->game_platform_id, $this->data['external_unique_id']);
                $is_transacton_id_exist = $this->common_seamless_wallet_transactions->isTransactionExistCustom($this->game_platform_id, $this->data['transaction_id']);
                $round_and_game = $this->game_api->getRoundAndGame(self::CALLBACK_METHOD_DEBIT, $this->data['round_id']);
                $is_bet_exist = $this->game_api->isPlayerTransactionExistByTypeAndRound(self::CALLBACK_METHOD_DEBIT, $this->data['uid'], $this->data['game_id'], $this->data['round_id']);

                if ($this->data['valid']) {
                    if ($this->callback_method == self::CALLBACK_METHOD_CREDIT) {
                        if ($is_transacton_id_exist) {
                            $this->data['valid'] = false;
                        }
                    }
                }

                if ($this->check_credit_exist) {
                    $is_credit_exist = $this->game_api->isPlayerTransactionExistByTypeAndRound(self::CALLBACK_METHOD_CREDIT, $this->data['uid'], $this->data['game_id'], $this->data['round_id']);
                } else {
                    $is_credit_exist = false;
                }

                if ($this->check_refund_exist) {
                    $is_refund_exist = $this->game_api->isPlayerTransactionExistByTypeAndRound(self::CALLBACK_METHOD_REFUND, $this->data['uid'], $this->data['game_id'], $this->data['round_id']);
                } else {
                    $is_refund_exist = false;
                }

                if ($this->data['valid'] && !empty($this->player_details) && isset($this->player_details['player_id']) && $this->player_details['player_id'] == $this->data['uid'] && $this->game_api->currency == $this->data['currency'] && !$is_external_transacton_exist && !empty($round_and_game) && $round_and_game['game_id'] == $this->data['game_id'] && $is_bet_exist && !$is_credit_exist && !$is_refund_exist) {
                    if ($this->callback_method == self::CALLBACK_METHOD_REFUND) {
                        $this->data['valid'] = $this->refund();
                    }

                    if ($this->data['valid']) {
                        if ($this->data['bet_type'] == self::BET_TYPE_WIN || $this->data['bet_type'] == self::BET_TYPE_REFUND) {
                            $is_transaction_success = $this->walletAdjustment(self::ACTION_INCREASE);
                            if (!empty($game_result)) {
                                $this->gameResult($game_result, $this->data);
                            } else {
                                $this->updatePlayersDebitGameTransactionStatus($this->player_details['player_id'], $this->data);
                            }
                        } else {
                            $is_transaction_success = true;
                        }

                        if (!$is_transaction_success) {
                            $this->data['valid'] = false;
                        }
                    }
                } else {
                    $this->data['valid'] = false;
                }

                $response_data = [
                    'roundId' => $this->data['round_id'],
                    'transactionId' => $this->data['transaction_id'],
                    'uid' => $this->data['uid'],
                    'nickname' => $this->data['nickname'],
                    'valid' => $this->data['valid'],
                    'balance' => $this->data['balance'],
                    'currency' => $this->data['currency'],
                    'timestamp' => $this->CI->utils->getTimestampNow(),
                ];

                array_push($this->data['data'], $response_data);
                $this->data['valid'] = true;
            }
        } else {
            if (!empty($game_result)) {
                $this->data['round_id'] = isset($game_result['roundId']) && !empty($game_result['roundId']) ? $game_result['roundId'] : null;
                $this->data['game_id'] = isset($game_result['gameId']) && !empty($game_result['gameId']) ? $game_result['gameId'] : null;
                $this->data['description'] = 'There are no winners';
                $this->gameResult($game_result, $this->data);

                $response_data = [
                    'game_id' => $this->data['game_id'],
                    'roundId' => $this->data['round_id'],
                    'description' => $this->data['description'],
                ];
        
                array_push($this->data['data'], $response_data);
            } else {
                $this->updatePlayersDebitGameTransactionStatus($this->player_details['player_id'], $this->data);
            }
        }

        $this->response_parameters = $this->setResponseParameters($this->data);
        return $this->outputResponseResult(self::SUCCESS, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_200);
    }

    private function refund()
    {
        $is_bet_exist = $this->common_seamless_wallet_transactions->isTransactionExist($this->game_platform_id, self::CALLBACK_METHOD_DEBIT . '-' . $this->data['transaction_id']);

        if ($is_bet_exist) {
            return true;
        } else {
            return false;
        }
    }

    private function gameResult($game_result, $data)
    {
        $rules_set = [
            'gameId' => 'required',
            'roundId' => 'required',
        ];

        $validate_request_parameters = $this->validateRequestParameters($rules_set, $game_result);

        if (!$validate_request_parameters['is_valid']) {
            $this->custom_message = 'Required ' . $validate_request_parameters['required_parameter'];
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $this->data['game_id'] = isset($game_result['gameId']) && !empty($game_result['gameId']) ? $game_result['gameId'] : null;
        $this->data['round_id'] = isset($game_result['roundId']) && !empty($game_result['roundId']) ? strtolower($game_result['roundId']) : null;
        $players_game_round = $this->game_api->queryPlayersByGameAndRound($data['game_id'], $data['round_id']);

        foreach ($players_game_round as $player) {
            $this->updatePlayersDebitGameTransactionStatus($player['player_id'], $data);
        }
    }

    private function updatePlayersDebitGameTransactionStatus($player_id, $data)
    {
        $this->data['player_debit_transaction'] = $this->game_api->getPlayerTransactionByType(self::CALLBACK_METHOD_DEBIT, $player_id, $data['game_id'], $data['round_id']);

        if (isset($this->data['player_debit_transaction']['external_unique_id']) && !empty($this->data['player_debit_transaction']['external_unique_id']) && $this->data['player_debit_transaction']['status'] == 'betting') {
            $data['external_unique_id'] = $this->data['player_debit_transaction']['external_unique_id'];
    
            $updated_data = [
                'status' => self::STATUS_BET,
                'updated_at' => $this->CI->utils->getNowForMysql(),
                'bet_amount' => $this->data['player_debit_transaction']['amount'],
                'result_amount' => -$this->data['player_debit_transaction']['amount'],
                'flag_of_updated_result' => self::FLAG_UPDATED,
            ];
    
            $updated_data['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5SumOneRow($updated_data, self::MD5_FIELDS_FOR_ORIGINAL_DEBIT_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_DEBIT_UPDATE);
            $updated_transaction_id = $this->common_seamless_wallet_transactions->updateTransaction($this->game_platform_id, $data['external_unique_id'], $updated_data);

            if ($updated_transaction_id) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function walletAdjustment($action)
    {
        $this->response = self::ERROR_RESPONSE_INTERNAL_SERVER_ERROR;
        $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_500;
        $is_transaction_success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use(&$action)
        {
            $success = false;
            $amount = $this->data['amount'];
            $this->data['balance'] = $this->data['before_balance'] = $this->game_api->dBtoGameAmount(floatval($this->queryPlayerBalance($this->player_details['username'])));

            if ($action == self::ACTION_INCREASE) {
                if ($amount == 0) {
                    $success = true;
                } elseif ($amount < 0) {
                    $this->response = self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST;
                    $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_400;
                    $this->custom_message = self::ERROR_MESSAGE_INVALID_AMOUNT;
                    $success = false;
                } else {
                    $success = $this->wallet_model->incSubWallet($this->player_details['player_id'], $this->game_platform_id,  $this->game_api->gameAmountToDB(floatval($amount)));
                }
            } else {
                if ($amount > $this->data['before_balance']) {
                    $this->response = self::ERROR_RESPONSE_INSUFFICIENT_BALANCE;
                    $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_400;
                    $success = false;
                } elseif ($amount < 0) {
                    $this->response = self::ERROR_RESPONSE_BAD_PARAMETER_REQUEST;
                    $this->http_status_response = self::HTTP_RESPONSE_STATUS_CODE_400;
                    $this->custom_message = self::ERROR_MESSAGE_INVALID_AMOUNT;
                    $success = false;
                } elseif ($amount == 0) {
                    $success = true;
                } else {
                    $success = $this->wallet_model->decSubWallet($this->player_details['player_id'], $this->game_platform_id,  $this->game_api->gameAmountToDB(floatval($amount)));
                }
            }

            if ($success) {
                $this->data['balance'] = $this->data['after_balance'] = $this->game_api->dBtoGameAmount(floatval($this->queryPlayerBalance($this->player_details['username'])));
                $this->response_parameters = $this->setResponseParameters($this->data);
                $this->processed_transaction_id = $this->rebuildRequestData($this->data);

                if ($this->processed_transaction_id) {
                    $processed_transaction = [
                        'processed_transaction' => $this->processed_transaction_id,
                        'external_transaction_id' => $this->external_transaction_id,
                    ];

                    array_push($this->processed_multiple_transaction, $processed_transaction);
                } else {
                    $this->response = self::ERROR_RESPONSE_INTERNAL_SERVER_ERROR;
                    $success = false;
                }
            }

            return $success;
        });

        return $is_transaction_success;
    }

    private function getPlayerToken()
    {
        $game_username = isset($this->request_parameters['game_username']) && !empty($this->request_parameters['game_username']) ? $this->request_parameters['game_username'] : null;
        $extra_info = isset($this->request_parameters['extra_info']) && $this->request_parameters['extra_info'] ? $this->request_parameters['extra_info'] : false;
        $player_id = $this->game_api->getPlayerIdInGameProviderAuth($game_username);

        if (!empty($player_id)) {
            $this->data['player_token'] =  $this->common_token->getPlayerToken($player_id);

            if (empty($this->data['player_token'])) {
                return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, self::ERROR_MESSAGE_PLAYER_NOT_FOUND, self::HTTP_RESPONSE_STATUS_CODE_400);
            }
    
            $this->player_details = (array) $this->common_token->getPlayerCompleteDetailsByToken($this->data['player_token'], $this->game_platform_id);
    
            $data = [
                'player_token' => $this->data['player_token'],
            ];
    
            if ($extra_info) {
                if (!empty($this->player_details)) {
                    $data['extra_info'] =  [
                        'game_provider_id' => isset($this->player_details['game_provider_id']) && !empty($this->player_details['game_provider_id']) ? $this->player_details['game_provider_id'] : null,
                        'player_id' => isset($this->player_details['player_id']) && !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null,
                        'username' => isset($this->player_details['username']) && !empty($this->player_details['username']) ? $this->player_details['username'] : null,
                        'game_username' => isset($this->player_details['game_username']) && !empty($this->player_details['game_username']) ? $this->player_details['game_username'] : null,
                        'active' => isset($this->player_details['active']) ? $this->player_details['active'] : null,
                        'blocked' => isset($this->player_details['blocked']) ? $this->player_details['blocked'] : null,
                        'frozen' => isset($this->player_details['frozen']) ? $this->player_details['frozen'] : null,
                        'game_status' => isset($this->player_details['game_status']) ? $this->player_details['game_status'] : null,
                        'game_isregister' => isset($this->player_details['game_isregister']) ? $this->player_details['game_isregister'] : null,
                        'timeout_at' => isset($this->player_details['timeout_at']) && !empty($this->player_details['timeout_at']) ? $this->player_details['timeout_at'] : null,
                    ];
                } else {
                    $data['extra_info'] =  [
                        'message' => 'Empty Details',
                    ];
                }
            }
        } else {
            return $this->outputResponseResult(self::ERROR_RESPONSE_BAD_REQUEST, self::ERROR_MESSAGE_PLAYER_NOT_FOUND, self::HTTP_RESPONSE_STATUS_CODE_400);
        }

        $this->response_parameters = $this->setResponseParameters($data);
        return $this->outputResponseResult(self::SUCCESS, $this->custom_message, self::HTTP_RESPONSE_STATUS_CODE_200);
    }

    private function queryPlayerBalance($playerName)
    {
        $playerBalance = $this->game_api->queryPlayerBalance($playerName);

        if ($playerBalance['success']) {
            $result = $playerBalance['balance'];
        } else {
            $result = false;
        }

        return $result;
    }

    private function getClientIP()
    {
        $ip = $this->input->ip_address();

        if ($ip == '0.0.0.0') {
            $ip=$this->input->getRemoteAddr();
        }

        return $ip;
    }

    private function rebuildRequestData($data)
    {
        $processed_data = [
            'game_platform_id' => $this->game_platform_id,
            'amount' => $data['amount'],
            'before_balance' => $data['before_balance'],
            'after_balance' => $data['after_balance'],
            'player_id' => $this->player_details['player_id'],
            'game_id' => $data['game_id'],
            'transaction_type' => $this->callback_method,
            'status' => $data['bet_status'],
            'response_result_id' => $this->response_result_id,
            'external_unique_id' => $data['external_unique_id'],
            'extra_info' => json_encode($this->request_parameters),
            'start_at' => $this->game_api->gameTimeToServerTime($this->CI->utils->getNowForMysql()),
            'end_at' => $this->game_api->gameTimeToServerTime($this->CI->utils->getNowForMysql()),
            'round_id' => $data['round_id'],
            'transaction_id' => $data['transaction_id'],
            'elapsed_time' => intval($this->CI->utils->getExecutionTimeToNow()*1000),
            'bet_amount' => isset($data['bet_amount']) && !empty($data['bet_amount']) ? $data['bet_amount'] : 0,
            'result_amount' => isset($data['result_amount']) && !empty($data['result_amount']) ? $data['result_amount'] : 0,
            'flag_of_updated_result' => isset($data['flag_of_updated_result']) && !empty($data['flag_of_updated_result']) ? $data['flag_of_updated_result'] : self::FLAG_NOT_UPDATED,
        ];

        $processed_data['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5SumOneRow($processed_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        $processed_transaction_id = $this->common_seamless_wallet_transactions->insertTransaction([$processed_data]);
        return $processed_transaction_id;
    }

    private function setExternalUniqueId($transaction_id)
    {
        $external_unique_id = $this->callback_method . '-' . $transaction_id;
        return $external_unique_id;
    }

    private function setResponseParameters($data)
    {
        switch ($this->callback_method) {
            case self::CALLBACK_METHOD_AUTH_TOKEN:
                $response = [
                    'status' => self::SUCCESS['status'],
                    'operatorId' => $this->operator_id,
                    'sessionToken' => $this->player_details['token'],
                    'uid' => $this->player_details['player_id'],
                    'nickname' => $this->player_details['game_username'],
                    'balance' => $data['balance'],
                    'currency' => $this->game_api->currency,
                    'table' => $this->game_api->table,
                    'language' => $this->game_api->language,
                    'clientIP' => $this->getClientIP(),
                    'timestamp' => $this->CI->utils->getTimestampNow(),
                ];
                break;
            case self::CALLBACK_METHOD_DEBIT:
            case self::CALLBACK_METHOD_REVISION:
                $response = [
                    'status' => self::SUCCESS['status'],
                    'operatorId' => $this->operator_id,
                    'data' => $data['data'],
                ];
                break;
            case self::CALLBACK_METHOD_CREDIT:
            case self::CALLBACK_METHOD_REFUND:
                if (isset($data['description']) && !empty($data['description'])) {
                    $response = [
                        'status' => self::SUCCESS['status'],
                        'message' => self::SUCCESS['status'],
                        'response' => [
                            'data' => $data['data'],
                        ],
                        'response_code' => self::HTTP_RESPONSE_STATUS_CODE_200['status_code'],
                    ];
                } else {
                    $response = [
                        'status' => self::SUCCESS['status'],
                        'operatorId' => $this->operator_id,
                        'data' => $data['data'],
                    ];
                }
                break;
            case self::ACTION_METHOD_GET_PLAYER_TOKEN:
                $response = $data;
                break;
            default:
                $response = [];
                break;
        }

        return $response;
    }

    private function saveResponseResult($flag, $response, $http_status_response)
    {
        $response_result_id = $this->response_result->saveResponseResult(
            $this->game_platform_id,
            $flag,
            $this->callback_method,
            json_encode($this->request_parameters),
            $response,
            $http_status_response['status_code'],
            $http_status_response['status_text'],
            is_array($this->headers) ? json_encode($this->headers) : $this->headers,
            ['player_id' => isset($this->player_details['player_id']) && !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null]
        );

        return $response_result_id;
    }

    private function outputResponseResult($response = [], $custom_message = '', $http_status_response = [])
    {
        $flag = $response['error_code'] == self::SUCCESS['error_code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if (!empty($custom_message)) {
            $response['message'] = $custom_message;
        }

        if (!empty($this->response_parameters)) {
            $response = $this->response_parameters;
        }

        $this->response_result_id = $this->saveResponseResult($flag, $response, $http_status_response);

        if (!empty($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $trans_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];
        
                $updated_data['md5_sum'] = $this->common_seamless_wallet_transactions->generateMD5SumOneRow($updated_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
                $this->common_seamless_wallet_transactions->updateTransaction($this->game_platform_id, $trans_id['external_transaction_id'], $updated_data);
            }
        }

        return $this->returnJsonResult($response, true, '*', false, false, $http_status_response['status_code']);
    }
}