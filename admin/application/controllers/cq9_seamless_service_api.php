<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Cq9_seamless_service_api extends BaseController
{
    use Seamless_service_api_module;

    private $game_api;
    private $game_platform_id;
    private $transactions_table;
    private $api_token;
    private $gamehall;
    private $language;
    private $currency;
    private $app;
    private $detect;
    private $request_headers;
    private $request_params;
    private $param;
    private $data;
    private $additional_data;
    private $action_type;
    private $transaction_type;
    private $external_transaction_id;
    private $response_result_id;
    private $processed_transaction_id;
    private $processed_multiple_transaction;
    private $player_details;
    private $custom_message;
    private $datetime_rfc3339;
    private $use_session_token;
    private $show_request_params_guide;
    private $check_api_token;
    private $check_datetime_format;
    private $check_same_gamehall;
    private $session_methods;
    private $transaction_already_exist;
    private $utc;
    private $whitelist_ip_validate_api_methods;
    private $game_api_active_validate_api_methods;
    private $game_api_maintenance_validate_api_methods;
    protected $conversion = 1;
    protected $precision = 2;
    protected $arithmetic_name = '';
    protected $adjustment_conversion = 1;
    protected $adjustment_precision = 2;
    protected $adjustment_arithmetic_name = '';
    protected $seamless_service_related_unique_id = null;
    protected $seamless_service_related_action = null;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $transaction_data = [];

    const SEAMLESS_GAME_API = CQ9_SEAMLESS_GAME_API;

    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';

    const SUCCESS = [
        'code' => "0",
        'message' => 'Success',
    ];

    const ERROR_GAME_ACTION = [
        'code' => "1002",
        'message' => 'Game action error: Method not allowed.'
    ];

    const ERROR_PARAMETER = [
        'code' => "1003",
        'message' => 'Parameter error.'
    ];

    const ERROR_TIME_FORMAT = [
        'code' => "1004",
        'message' => 'Time Format error.'
    ];

    const ERROR_INSUFFICIENT_BALANCE = [
        'code' => "1005",
        'message' => 'Insufficient Balance.'
    ];

    const ERROR_PLAYER_NOT_FOUND = [
        'code' => "1006",
        'message' => 'Player not found.'
    ];

    const ERROR_TRANSACTION_NOT_FOUND = [
        'code' => "1014",
        'message' => 'Transaction record not found.'
    ];

    const ERROR_ALREADY_PROCESSED = [
        'code' => "1015",
        'message' => 'Already processed by other action.'
    ];

    const ERROR_SERVER = [
        'code' => "1100",
        'message' => 'Server error.'
    ];

    const ERROR_IP_NOT_ALLOWED = [
        'code' => "1100",
        'message' => 'IP Address is not allowed.'
    ];

    const ERROR_TRANSACTION_ALREADY_EXIST = [
        'code' => "2009",
        'message' => 'Duplicate MTCode: Transaction already Exist.'
    ];

    const ERROR_MULTIPLE_TRANSACTIONS_NOT_ALLOWED = [
        'code' => "1015",
        'message' => 'Multiple Transactions are not allowed.'
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

    const HTTP_RESPONSE_STATUS_CODE_405 = [
        'status' => 'error',
        'code' => 405,
        'status_text' => 'Method Not Allowed',
        'description' => 'Method Not Allowed.'
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

    const ERROR_MESSAGE_IP_NOT_ALLOWED = 'IP Address not allowed.';
    const ERROR_MESSAGE_MAINTENANCE_OR_DISABLED = 'Game under maintenance or disabled.';
    const ERROR_MESSAGE_METHOD_NOT_ALLOWED = 'Method not allowed.';
    const ERROR_MESSAGE_INVALID_SESSION = 'Invalid Session, Player not found.';

    const PREFIX_GAME_ACTION_ERROR = 'Game Action Error:';
    const PREFIX_PARAMETER_ERROR = 'Parameter Error:';
    const PREFIX_DATETIME_FORMAT_ERROR = 'Datetime Format Error:';

    const METHOD_CHECKPLAYER = 'checkPlayer';
    const METHOD_BALANCE = 'balance';
    const METHOD_BET = 'bet';
    const METHOD_ENDROUND = 'endround';
    const METHOD_ROLLOUT = 'rollout';
    const METHOD_TAKEALL = 'takeall';
    const METHOD_ROLLIN = 'rollin';
    const METHOD_DEBIT = 'debit';
    const METHOD_CREDIT = 'credit';
    const METHOD_PAYOFF = 'payoff';
    const METHOD_REFUND = 'refund';
    const METHOD_BETS = 'bets';
    const METHOD_REFUNDS = 'refunds';
    const METHOD_CANCEL = 'cancel';
    const METHOD_WIN = 'win';
    const METHOD_WINS = 'wins';
    const METHOD_AMENDS = 'amends';
    const METHOD_AMEND = 'amend';
    const METHOD_RECORD = 'record';

    const GAME_ACTION_METHODS = [
        self::METHOD_BET,
        self::METHOD_ENDROUND,
        self::METHOD_ROLLOUT,
        self::METHOD_TAKEALL,
        self::METHOD_ROLLIN,
        self::METHOD_DEBIT,
        self::METHOD_CREDIT,
        self::METHOD_PAYOFF,
        self::METHOD_REFUND,
        self::METHOD_BETS,
        self::METHOD_REFUNDS,
        self::METHOD_CANCEL,
        self::METHOD_WINS,
        self::METHOD_AMENDS,
        self::METHOD_AMEND,
        self::METHOD_RECORD
    ];

    const WHITELIST_METHODS = [
        self::METHOD_CHECKPLAYER,
        self::METHOD_BALANCE,
        self::METHOD_BET,
        self::METHOD_ENDROUND,
        self::METHOD_ROLLOUT,
        self::METHOD_TAKEALL,
        self::METHOD_ROLLIN,
        self::METHOD_DEBIT,
        self::METHOD_CREDIT,
        self::METHOD_PAYOFF,
        self::METHOD_REFUND,
        self::METHOD_BETS,
        self::METHOD_REFUNDS,
        self::METHOD_CANCEL,
        self::METHOD_WIN,
        self::METHOD_WINS,
        self::METHOD_AMENDS,
        self::METHOD_AMEND,
        self::METHOD_RECORD
    ];

    const WHITELIST_IP_VALIDATE_API_METHODS = [
        self::METHOD_CHECKPLAYER,
        self::METHOD_BALANCE,
        self::METHOD_BET,
        self::METHOD_ENDROUND,
        self::METHOD_ROLLOUT,
        self::METHOD_TAKEALL,
        self::METHOD_ROLLIN,
        self::METHOD_DEBIT,
        self::METHOD_CREDIT,
        self::METHOD_PAYOFF,
        self::METHOD_REFUND,
        self::METHOD_BETS,
        self::METHOD_REFUNDS,
        self::METHOD_CANCEL,
        self::METHOD_WIN,
        self::METHOD_WINS,
        self::METHOD_AMENDS,
        self::METHOD_AMEND,
        self::METHOD_RECORD
    ];

    const GAME_API_ACTIVE_VALIDATE_API_METHODS = [
        self::METHOD_CHECKPLAYER,
        self::METHOD_BALANCE,
        self::METHOD_BET,
        self::METHOD_ENDROUND,
        self::METHOD_ROLLOUT,
        self::METHOD_TAKEALL,
        self::METHOD_ROLLIN,
        self::METHOD_DEBIT,
        self::METHOD_CREDIT,
        self::METHOD_PAYOFF,
        self::METHOD_REFUND,
        self::METHOD_BETS,
        self::METHOD_REFUNDS,
        self::METHOD_CANCEL,
        self::METHOD_WIN,
        self::METHOD_WINS,
        self::METHOD_AMENDS,
        self::METHOD_AMEND,
        self::METHOD_RECORD
    ];

    const GAME_API_MAINTENANCE_VALIDATE_API_METHODS = [
        self::METHOD_CHECKPLAYER,
        self::METHOD_BALANCE,
        self::METHOD_BET,
        self::METHOD_ROLLOUT,
        self::METHOD_TAKEALL,
        self::METHOD_BETS,
    ];


    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;

    const DECREASE = 'decrease';
    const INCREASE = 'increase';

    const INSERT = 'insert';
    const UPDATE = 'update';

    const MD5_FIELDS_FOR_ORIGINAL = [
        'game_platform_id',
        'session',
        'player_id',
        'account',
        'currency',
        'platform',
        'transaction_type',
        'transaction_id',
        'ucode',
        'gamehall',
        'game_id',
        'round_id',
        'before_balance',
        'after_balance',
        'amount',
        'rake',
        'jackpot',
        'event_time',
        'create_time',
        'start_at',
        'end_at',
        'status',
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
        'rake',
        'jackpot',
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

    // default system error code ---------------
    const SYSTEM_ERROR_EMPTY_GAME_API = [
        'code' => 'SE_1',
        'message' => 'Error in inilialize method: Empty game_platform_api',
    ];

    const SYSTEM_ERROR_INITIALIZE_GAME_API = [
        'code' => 'SE_2',
        'message' => 'Error in inilialize method: failed to load game API',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_NOT_FOUND = [
        'code' => 'SE_3',
        'message' => 'Error in inilialize method: function method not found',
    ];

    const SYSTEM_ERROR_FUNCTION_METHOD_FORBIDDEN = [
        'code' => 'SE_4',
        'message' => 'Error in inilialize method: function method forbidden',
    ];

    const SYSTEM_ERROR_DECREASE_BALANCE = [
        'code' => 'SE_5',
        'message' => 'Error in walletAdjustment method: decrease balance',
    ];

    const SYSTEM_ERROR_INCREASE_BALANCE = [
        'code' => 'SE_6',
        'message' => 'Error in walletAdjustment method: increase balance',
    ];

    const SYSTEM_ERROR_WALLET_ADJUSTMENT_DEFAULT = [
        'code' => 'SE_7',
        'message' => 'Error in walletAdjustment method: default',
    ];

    const SYSTEM_ERROR_SAVE_TRANSACTION_REQUEST_DATA = [
        'code' => 'SE_8',
        'message' => 'Error in walletAdjustment method: save transaction request data',
    ];

    const SYSTEM_ERROR_SAVE_SERVICE_LOGS = [
        'code' => 'SE_9',
        'message' => 'Error in saveServiceLogs method',
    ];

    const SYSTEM_ERROR_REBUILD_OPERATOR_RESPONSE = [
        'code' => 'SE_10',
        'message' => 'Error in rebuildOperatorResponse method',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_DOUBLE_UNIQUEID = [
        'code' => 'SE_11',
        'message' => 'Error in walletAdjustment method: double unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID = [
        'code' => 'SE_12',
        'message' => 'Error in walletAdjustment method: invalid unique id',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_INSUFFICIENT_BALANCE = [
        'code' => 'SE_13',
        'message' => 'Error in walletAdjustment method: remote wallet insufficient balance',
    ];

    const SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE = [
        'code' => 'SE_14',
        'message' => 'Error in walletAdjustment method: remote wallet maintenance',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ssa_init();
        $this->request_headers = getallheaders();
        $this->processed_multiple_transaction = $this->request_params = $this->additional_data = [];
        $this->param = $this->action_type = $this->transaction_type = $this->custom_message = '';
        $this->external_transaction_id = $this->response_result_id = $this->processed_transaction_id = $this->data = null;
        $this->datetime_rfc3339 = date(DateTime::RFC3339); //date('Y-m-d\TH:i:sP');
        $this->transaction_already_exist = false;
    }

    public function index($game_platform_id, $method, $account = null)
    {
        $this->request_params = $this->getRequestParams();

        if (method_exists($this, $method) && in_array($method, self::WHITELIST_METHODS)) {
            $this->action_type = $method;
        } else {
            $this->action_type = __FUNCTION__;
        }

        if (empty($game_platform_id) || $game_platform_id != self::SEAMLESS_GAME_API) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_500, self::ERROR_SERVER);
        }

        $this->game_api = $this->CI->utils->loadExternalSystemLibObject($game_platform_id);

        if (!$this->game_api) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_500, self::ERROR_SERVER);
        }

        $this->game_platform_id = $this->game_api->getPlatformCode();
        $this->transactions_table = $this->game_api->getSeamlessTransactionTable();
        $this->api_token = $this->game_api->api_token;
        $this->gamehall = $this->game_api->gamehall;
        $this->language = $this->game_api->language;
        $this->currency = $this->game_api->currency;
        $this->app = $this->game_api->app;
        $this->detect = $this->game_api->detect;
        $this->use_session_token = $this->game_api->use_session_token;
        $this->show_request_params_guide = $this->game_api->show_request_params_guide;
        $this->check_api_token = $this->game_api->check_api_token;
        $this->check_datetime_format = $this->game_api->check_datetime_format;
        $this->check_same_gamehall = $this->game_api->check_same_gamehall;
        $this->session_methods = $this->game_api->session_methods;
        $this->utc = $this->game_api->utc;
        $this->conversion = $this->game_api->conversion;
        $this->precision = $this->game_api->precision;
        $this->arithmetic_name = $this->game_api->arithmetic_name;
        $this->adjustment_precision = $this->game_api->adjustment_precision;
        $this->adjustment_conversion = $this->game_api->adjustment_conversion;
        $this->adjustment_arithmetic_name = $this->game_api->adjustment_arithmetic_name;

        $this->whitelist_ip_validate_api_methods = !empty($this->game_api->whitelist_ip_validate_api_methods) ? $this->game_api->whitelist_ip_validate_api_methods : self::WHITELIST_IP_VALIDATE_API_METHODS;
        $this->game_api_active_validate_api_methods = !empty($this->game_api->game_api_active_validate_api_methods) ? $this->game_api->game_api_active_validate_api_methods : self::GAME_API_ACTIVE_VALIDATE_API_METHODS;
        $this->game_api_maintenance_validate_api_methods = !empty($this->game_api->game_api_maintenance_validate_api_methods) ? $this->game_api->game_api_maintenance_validate_api_methods : self::GAME_API_MAINTENANCE_VALIDATE_API_METHODS;

        if (in_array($method, $this->whitelist_ip_validate_api_methods)) {
            if (!$this->ssa_is_server_ip_allowed($this->game_api)) {
                $this->custom_message = self::PREFIX_GAME_ACTION_ERROR . " '" . $method . "' IP not allowed.";
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_401, self::ERROR_SERVER);
            }
        }

        if (in_array($method, $this->game_api_active_validate_api_methods)) {
            if (!$this->ssa_is_game_api_active($this->game_api)) {
                $this->custom_message = self::PREFIX_GAME_ACTION_ERROR . " '" . $method . "' Method not allowed.";
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_503, self::ERROR_SERVER);
            }
        }

        if (in_array($method, $this->game_api_maintenance_validate_api_methods)) {
            if ($this->ssa_is_game_api_maintenance($this->game_api)) {
                $this->custom_message = self::ERROR_MESSAGE_MAINTENANCE_OR_DISABLED;
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_503, self::ERROR_SERVER);
            }
        }

        if (!$this->validateApiHeaders()) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_401, self::ERROR_SERVER);
        }

        if ($this->action_type == self::METHOD_CHECKPLAYER) {
            return $this->checkPlayer($account);
        }

        if ($this->action_type == self::METHOD_BALANCE) {
            return $this->balance($account);
        }

        if ($this->action_type == self::METHOD_RECORD) {
            #$account as mtcode/transaction ID
            $mtcode = $account;
            return $this->record($mtcode);
        }

        return $this->$method();
    }

    private function checkPlayer($account)
    {
        $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);

        if (!empty($this->player_details)) {
            $this->data = true;
        } else {
            $this->data = false;
        }

        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::SUCCESS);
    }

    private function balance($account)
    {
        $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);

        if (!empty($this->player_details)) {
            $balance = $this->queryPlayerBalance($this->player_details['username']);
            $operator_response = self::SUCCESS;

            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            $operator_response = self::ERROR_PLAYER_NOT_FOUND;
            if (!empty($account)) {
                $this->custom_message = $account . ' not found.';
            }
        }

        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
    }

    private function record($mtcode)
    {   
        $operator_response = self::SUCCESS;
        $data = [
            'transaction_id' => $mtcode,
        ];

        $transaction_data   = $this->ssa_get_transaction($this->transactions_table,$data);
        $status             =  $transaction_data['status'] != game_logs::STATUS_REFUND ? 'success' : 'refund';

        if(empty($transaction_data)){
            $operator_response = self::ERROR_TRANSACTION_NOT_FOUND;
        }

        $event_data = [];
        if($transaction_data['transaction_type'] == 'endround'){
            $extra_info = json_decode($transaction_data['extra_info'],true);
            $event_data = json_decode($extra_info['data'],true);
            foreach($event_data as $key => $value){
                unset($event_data[$key]['validbet']);
            }
        }else{
            $event_data[] = [
                'mtcode' => $transaction_data['transaction_id'],
                'amount' => floatval($transaction_data['amount']),
                'eventtime' => $this->formatDate($transaction_data['event_time'], DateTime::RFC3339),
            ];
        }

        if($operator_response == self::SUCCESS){
            $this->data = [
                '_id' => md5($mtcode),
                'action' => $transaction_data['transaction_type'],
                'target' => [
                    'account' => $transaction_data['account']
                ],
                'status' => [
                    "createtime" => $this->formatDate($transaction_data['start_at'], DateTime::RFC3339),
                    "endtime"    => $this->formatDate($transaction_data['end_at'], DateTime::RFC3339),
                    "status"     => $status,
                    "message"    => $status
                ],
                'before' => floatval($transaction_data['before_balance']),
                'balance' => $transaction_data['after_balance'],
                'currency' => $transaction_data['currency'],
                'event' => $event_data,
            ];
        }
        
        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, $operator_response);
    }

    private function formatDate($dateString, $format){
        $dateTime = new DateTime($dateString);
        $formatted_date = $dateTime->format($format);
        return $formatted_date;
    }
   
    private function bet()
    {
        $this->transaction_type = self::METHOD_BET;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'amount' => 'required|positive|numeric|nullable',
            'mtcode' => 'required|unique',
            'platform' => 'optional',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {
            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::INSERT, $balance, $http_response, $operator_response, 'bet', false);
                return $success;
            });
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function endround()
    {
        $this->transaction_type = self::METHOD_ENDROUND;

        $rule_sets = [
            'account' => 'required|same',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'data' => 'required',
            'createTime' => 'required|date_format:RFC3339',
            'freegame' => 'optional',
            'bonus' => 'optional',
            'luckydraw' => 'optional',
            'jackpot' => 'optional',
            'jackpotcontribution' => 'optional',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $rule_sets = [
            'mtcode' => 'required|unique',
            'amount' => 'required|positive|numeric|nullable',
            'eventtime' => 'required|date_format:RFC3339',
        ];

        if (is_array($this->request_params['data'])) {
            $data_parameter = $this->request_params['data'];
        } else {
            $data_parameter = json_decode($this->request_params['data'], true);
        }

        //validate data request params
        foreach ($data_parameter as $request_params) {
            list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $request_params);

            if (!$is_valid) {
                return $this->setResponse($http_response, $operator_response);
            }
        }

        $is_already_processed_by_refund = $this->ssa_is_transaction_exists($this->transactions_table, [
            'transaction_type' => self::METHOD_REFUND,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if ($is_already_processed_by_refund) {
            $this->custom_message = 'Already processed by refund.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
        }

        $is_already_processed_by_cancel = $this->ssa_is_transaction_exists($this->transactions_table, [
            'transaction_type' => self::METHOD_CANCEL,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if ($is_already_processed_by_cancel) {
            $this->custom_message = 'Already processed by cancel.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
        }

        $bet_transaction = $this->ssa_get_transaction($this->transactions_table, [
            'transaction_type' => self::METHOD_BET,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if (empty($bet_transaction)) {
            $this->custom_message = 'Bet transaction record not found.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
        }

        if (!empty($bet_transaction['external_unique_id'])) {
            $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $bet_transaction['external_unique_id']]);
            $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        //proceed to wallet adjustment
        foreach ($data_parameter as $request_params) {
            $this->request_params['mtcode'] = $this->external_transaction_id = isset($request_params['mtcode']) && !empty($request_params['mtcode']) ? $request_params['mtcode'] : null;
            $this->request_params['amount'] = isset($request_params['amount']) && !empty($request_params['amount']) ? $request_params['amount'] : 0;
            $this->request_params['eventTime'] = isset($request_params['eventtime']) && !empty($request_params['eventtime']) ? $request_params['eventtime'] : null;

            if (!$this->transaction_already_exist) {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $balance, $http_response, $operator_response, 'payout', true);
                    return $success;
                });

                if (!$success) {
                    break;
                }
            } else {
                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
                $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
            }
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function rollout()
    {
        $this->transaction_type = self::METHOD_ROLLOUT;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'amount' => 'required|positive|numeric|nullable',
            'mtcode' => 'required|unique',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $is_takeall_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
            'transaction_type' => self::METHOD_TAKEALL,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if ($is_takeall_exist) {
            $this->custom_message = 'Already processed by takeall';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {
            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::INSERT, $balance, $http_response, $operator_response,'bet', false);
                return $success;
            });
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    /**
     * Takeall & Rollin
     * To transfer the player 『all』 balance to our game, and transfer it back to the player’s wallet after settlement
     */
    private function takeall()
    {

        $this->transaction_type = self::METHOD_TAKEALL;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'mtcode' => 'required|unique',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $is_rollout_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
            'transaction_type' => self::METHOD_ROLLOUT,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);


        if ($is_rollout_exist) {
            $this->custom_message = 'Already processed by rollout';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
        }

        $this->request_params['amount'] = $this->queryPlayerBalance($this->player_details['username']); //amount must be the balance

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {

            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::INSERT, $balance, $http_response, $operator_response , 'bet', false);
                return $success;
            });
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $this->request_params['amount'] = $this->truncateAmount($transaction_data['before_balance'], 4);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'amount' => $this->request_params['amount'],
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::SUCCESS);
    }

    private function rollin()
    {
        $this->transaction_type = self::METHOD_ROLLIN;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'validbet' => 'optional|positive|numeric|nullable',
            'bet' => 'required|numeric|nullable',
            'win' => 'required|numeric|nullable',
            'roomfee' => 'optional',
            'amount' => 'required|positive|numeric|nullable',
            'mtcode' => 'required|unique',
            'createTime' => 'required|date_format:RFC3339',
            'rake' => 'required|numeric|nullable',
            'gametype' => 'required',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

       $rollout_transaction = $this->ssa_get_transaction($this->transactions_table, [
            'transaction_type' => self::METHOD_ROLLOUT,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if (empty($rollout_transaction)) {
            $takeall_transaction = $this->ssa_get_transaction($this->transactions_table, [
                'transaction_type' => self::METHOD_TAKEALL,
                'account' => $this->request_params['account'],
                'game_id' => $this->request_params['gamecode'],
                'round_id' => $this->request_params['roundid'],
            ]);

            if (empty($takeall_transaction)) {
                $this->custom_message = 'Rollout or Takeall transaction record not found.';
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
            } else {
                if (!empty($takeall_transaction['external_unique_id'])) {
                    $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $takeall_transaction['external_unique_id']]);
                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                }
            }
        } else {
            if (!empty($rollout_transaction['external_unique_id'])) {
                $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $rollout_transaction['external_unique_id']]);
                $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
            }
        }

        $is_already_processed_by_refund = $this->ssa_is_transaction_exists($this->transactions_table, [
            'transaction_type' => self::METHOD_REFUND,
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if ($is_already_processed_by_refund) {
            $this->custom_message = 'Already processed by refund.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {
            $is_round_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
                'transaction_type' => self::METHOD_ROLLIN,
                'account' => $this->request_params['account'],
                'game_id' => $this->request_params['gamecode'],
                'round_id' => $this->request_params['roundid'],
            ]);

            if ($is_round_exist) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_MULTIPLE_TRANSACTIONS_NOT_ALLOWED);
            } else {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $balance, $http_response, $operator_response,'payout', true);
                    return $success;
                });
            }
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function debit()
    {
        $this->transaction_type = self::METHOD_DEBIT;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'amount' => 'required|positive|numeric|nullable',
            'mtcode' => 'required|unique',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $is_game_round_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if (!$is_game_round_exist) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
        }

        /* $this->additional_data = [
            'result_amount' => -$this->request_params['amount'],
            'flag_of_updated_result' => self::FLAG_UPDATED,
        ]; */

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {
            $is_round_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
                'transaction_type' => self::METHOD_DEBIT,
                'account' => $this->request_params['account'],
                'game_id' => $this->request_params['gamecode'],
                'round_id' => $this->request_params['roundid'],
            ]);

            if ($is_round_exist) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_MULTIPLE_TRANSACTIONS_NOT_ALLOWED);
            } else {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::INSERT, $balance, $http_response, $operator_response, 'bet', false);
                    return $success;
                });
            }
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function credit()
    {
        $this->transaction_type = self::METHOD_CREDIT;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'roundid' => 'required',
            'amount' => 'required|positive|numeric|nullable',
            'mtcode' => 'required|unique',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $is_game_round_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
            'account' => $this->request_params['account'],
            'game_id' => $this->request_params['gamecode'],
            'round_id' => $this->request_params['roundid'],
        ]);

        if (!$is_game_round_exist) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
        }

        /* $this->additional_data = [
            'result_amount' => $this->request_params['amount'],
            'flag_of_updated_result' => self::FLAG_UPDATED,
        ]; */

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {
            $is_round_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
                'transaction_type' => self::METHOD_CREDIT,
                'account' => $this->request_params['account'],
                'game_id' => $this->request_params['gamecode'],
                'round_id' => $this->request_params['roundid'],
            ]);

            if ($is_round_exist) {
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_MULTIPLE_TRANSACTIONS_NOT_ALLOWED);
            } else {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $balance, $http_response, $operator_response, 'payout', true);
                    return $success;
                });
            }
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function payoff()
    {
        $this->transaction_type = self::METHOD_PAYOFF;

        $rule_sets = [
            'account' => 'required|same',
            'eventTime' => 'required|date_format:RFC3339',
            'amount' => 'required|positive|numeric|nullable',
            'mtcode' => 'required|unique',
            'remark' => 'optional',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if (!$this->transaction_already_exist) {
            $controller = $this;
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $balance, $http_response, $operator_response, 'payout', true);
                return $success;
            });
        } else {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
            $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function refund()
    {
        $this->transaction_type = self::METHOD_REFUND;

        $rule_sets = [
            'mtcode' => 'required|unique',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $success = true;
        $balance = 0;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        if ($this->transaction_already_exist) {
            $bet_reference_id = $this->external_transaction_id; // bet transaction id
            $bet_transaction = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $bet_reference_id]);

            if (!empty($bet_transaction) && is_array($bet_transaction)) {
                $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $bet_reference_id]);
                $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;

                // generated refund transaction id
                $this->request_params['mtcode'] = $this->external_transaction_id = $this->utils->mergeArrayValues([$this->action_type, $bet_reference_id]);

                $this->request_params['account'] = $account = isset($bet_transaction['account']) ? $bet_transaction['account'] : null;
                $this->request_params['gamecode'] = $game_id = isset($bet_transaction['game_id']) ? $bet_transaction['game_id'] : null;
                $this->request_params['roundid'] = $round_id = isset($bet_transaction['round_id']) ? $bet_transaction['round_id'] : null;
                $this->request_params['amount'] = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                $this->request_params['gamehall'] = isset($bet_transaction['gamehall']) ? $bet_transaction['gamehall'] : null;
                $this->request_params['session'] = isset($bet_transaction['session']) ? $bet_transaction['session'] : null;
                $this->request_params['platform'] = isset($bet_transaction['platform']) ? $bet_transaction['platform'] : null;
                $this->request_params['eventTime'] = isset($bet_transaction['event_time']) ? $bet_transaction['event_time'] : null;

                if (empty($this->player_details) && isset($account)) {
                    $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);
                }

                $refund_transaction = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);

                if (empty($refund_transaction)) {
                    $balance = $this->queryPlayerBalance($this->player_details['username']);

                    $is_already_processed_by_endround = $this->ssa_is_transaction_exists($this->transactions_table, [
                        'transaction_type' => self::METHOD_ENDROUND,
                        'account' => $account,
                        'game_id' => $game_id,
                        'round_id' => $round_id,
                    ]);
    
                    if ($is_already_processed_by_endround) {
                        $this->custom_message = 'Already processed by endround.';
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                    }
    
                    $is_already_processed_by_rollin = $this->ssa_is_transaction_exists($this->transactions_table, [
                        'transaction_type' => self::METHOD_ROLLIN,
                        'account' => $account,
                        'game_id' => $game_id,
                        'round_id' => $round_id,
                    ]);
    
                    if ($is_already_processed_by_rollin) {
                        $this->custom_message = 'Already processed by rollin.';
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                    }
    
                    $is_already_processed_by_win = $this->ssa_is_transaction_exists($this->transactions_table, [
                        'transaction_type' => self::METHOD_WIN,
                        'account' => $account,
                        'game_id' => $game_id,
                        'round_id' => $round_id,
                    ]);
    
                    if ($is_already_processed_by_win) {
                        $this->custom_message = 'Already processed by win.';
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                    }
    
                    $is_cancelled = $this->ssa_is_transaction_exists($this->transactions_table, [
                        'transaction_type' => self::METHOD_CANCEL,
                        'transaction_id' => $this->external_transaction_id,
                    ]);
    
                    /* $is_refunded = $this->ssa_is_transaction_exists($this->transactions_table, [
                        'transaction_type' => self::METHOD_REFUND,
                        'transaction_id' => $this->external_transaction_id,
                    ]); */
    
                    $this->additional_data = [
                        'bet_amount' => $this->request_params['amount'],
                        'result_amount' => $this->request_params['amount'],
                        /* 'flag_of_updated_result' => self::FLAG_UPDATED, */
                    ];
    
                    if (!$is_cancelled) {
                        /* if (!$is_refunded) { */
                            $controller = $this;
                            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                                list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $balance, $http_response, $operator_response, 'refund', true);
                                return $success;
                            });
                        /* } else {
                            $balance = $this->truncateAmount($bet_transaction['after_balance'], 4);
                        } */
                    } else {
                        $this->custom_message = 'Already cancelled.';
                        return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                    }
                } else {
                    $balance = $this->truncateAmount($refund_transaction['after_balance'], 4);
                }
            } else {
                $this->custom_message = 'Transaction record bet, rollout or takeall not found.';
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
            }
        } else {
            $this->custom_message = 'Transaction record bet, rollout or takeall not found.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function bets() # (Only for Sports and Lotto)
    {
        $this->transaction_type = self::METHOD_BET;

        $rule_sets = [
            'account' => 'required|same',
            'gamehall' => 'required|same',
            'gamecode' => 'required',
            'data' => 'required',
            'createTime' => 'required|date_format:RFC3339',
            'genre' => 'optional',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $rule_sets = [
            'mtcode' => 'required|unique',
            'amount' => 'required|positive|numeric|nullable',
            'roundid' => 'required',
            'eventtime' => 'required|date_format:RFC3339',
        ];

        if (is_array($this->request_params['data'])) {
            $data_parameter = $this->request_params['data'];
        } else {
            $data_parameter = json_decode($this->request_params['data'], true);
        }

        //validate data request params
        foreach ($data_parameter as $request_params) {
            list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $request_params);

            if (!$is_valid) {
                return $this->setResponse($http_response, $operator_response);
            }
        }

        //advance transaction validation
        $total_amount = 0;
        foreach ($data_parameter as $request_params) {
            $total_amount += $request_params['amount'];
        }

        $balance = $this->queryPlayerBalance($this->player_details['username']);
        if ($total_amount > $balance) {
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_INSUFFICIENT_BALANCE);
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        //proceed to wallet adjustment
        foreach ($data_parameter as $request_params) {
            $this->request_params['mtcode'] = $this->external_transaction_id = isset($request_params['mtcode']) && !empty($request_params['mtcode']) ? $request_params['mtcode'] : null;
            $this->request_params['amount'] = isset($request_params['amount']) && !empty($request_params['amount']) ? $request_params['amount'] : 0;
            $this->request_params['roundid'] = isset($request_params['roundid']) && !empty($request_params['roundid']) ? $request_params['roundid'] : null;
            $this->request_params['eventTime'] = isset($request_params['eventtime']) && !empty($request_params['eventtime']) ? $request_params['eventtime'] : null;

            if (!$this->transaction_already_exist) {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::INSERT, $balance, $http_response, $operator_response, 'bet', false);
                    return $success;
                });

                if (!$success) {
                    break;
                }
            } else {
                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['external_unique_id' => $this->request_params['mtcode']]);
                $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
            }
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function refunds() # (Only for Sports and Lotto)
    {
        $this->transaction_type = self::METHOD_REFUND;

        $rule_sets = [
            'mtcode' => 'required|unique|array',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $balance = 0;
        $is_refunded = false;
        $success = true;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        //validation
        foreach ($this->request_params['mtcode'] as $mtcode) {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $mtcode]);
            if (!empty($transaction_data) && is_array($transaction_data)) {
                $account = isset($transaction_data['account']) ? $transaction_data['account'] : null;
                $game_id = isset($transaction_data['game_id']) ? $transaction_data['game_id'] : null;
                $round_id = isset($transaction_data['round_id']) ? $transaction_data['round_id'] : null;

                if (!empty($mtcode)) {
                    $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $mtcode]);
                    $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                }

                if (empty($this->player_details)) {
                    $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);
                }

                $balance = $this->queryPlayerBalance($this->player_details['username']);

                $is_already_processed_by_endround = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_ENDROUND,
                    'account' => $account,
                    'game_id' => $game_id,
                    'round_id' => $round_id,
                ]);

                if ($is_already_processed_by_endround) {
                    $this->custom_message = 'Mtcode: ' . $mtcode .' Already processed by endround.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                }

                $is_already_processed_by_rollin = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_ROLLIN,
                    'account' => $account,
                    'game_id' => $game_id,
                    'round_id' => $round_id,
                ]);

                if ($is_already_processed_by_rollin) {
                    $this->custom_message = 'Mtcode: ' . $mtcode .' Already processed by rollin.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                }

                $is_already_processed_by_win = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_WIN,
                    'account' => $account,
                    'game_id' => $game_id,
                    'round_id' => $round_id,
                ]);

                if ($is_already_processed_by_win) {
                    $this->custom_message = 'Mtcode: ' . $mtcode .' Already processed by win.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                }

                $is_cancelled = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_CANCEL,
                    'transaction_id' => $mtcode,
                ]);

                if ($is_cancelled) {
                    $this->custom_message = 'Mtcode: ' . $mtcode .' Already cancelled.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_ALREADY_PROCESSED);
                }

                $is_refunded = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_REFUND,
                    'transaction_id' => $mtcode,
                ]);

                if ($is_refunded) {
                    break;
                }
            } else {
                $this->custom_message = 'Mtcode: ' . $mtcode .' Transaction record bet, rollout or takeall not found.';
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
            }
        }

        if ($this->transaction_already_exist) {
            //wallet adjustment
            foreach ($this->request_params['mtcode'] as $mtcode) {
                $this->external_transaction_id = $mtcode;
                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $mtcode]);
                if (!empty($transaction_data) && is_array($transaction_data)) {
                    $account = isset($transaction_data['account']) ? $transaction_data['account'] : null;
                    $this->request_params['amount'] = isset($transaction_data['amount']) ? $transaction_data['amount'] : 0;

                    $this->additional_data = [
                        'bet_amount' => $this->request_params['amount'],
                        'result_amount' => $this->request_params['amount'],
                        'flag_of_updated_result' => self::FLAG_UPDATED,
                    ];

                    if (empty($this->player_details)) {
                        $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);
                    }

                    $balance = $this->queryPlayerBalance($this->player_details['username']);
                    if (!$is_refunded) {
                    $controller = $this;
                    $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                        list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::UPDATE, $balance, $http_response, $operator_response, 'refund', true);
                        return $success;
                    });
                    } else {
                        $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $mtcode]);
                        $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
                    }
                } else {
                    $this->custom_message = 'Mtcode: ' . $mtcode .' Transaction record bet, rollout or takeall not found.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
                }
            }
        } else {
            $this->custom_message = 'Transaction record bet, rollout or takeall not found.';
            return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function cancel() # (Only for Sports and Lotto)
    {
        $this->transaction_type = self::METHOD_CANCEL;

        $rule_sets = [
            'mtcode' => 'required|unique|array',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $success = true;
        $is_cancelled = false;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;

        //validation
        foreach ($this->request_params['mtcode'] as $mtcode) {
            $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $mtcode]);

            if (!empty($transaction_data) && is_array($transaction_data)) {
                $account = isset($transaction_data['account']) ? $transaction_data['account'] : null;

                if (empty($this->player_details) && isset($account)) {
                    $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);
                }

                $balance = $this->queryPlayerBalance($this->player_details['username']);

                $is_cancelled = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_CANCEL,
                    'transaction_id' => $mtcode,
                ]);

                if ($is_cancelled) {
                    break;
                }

                $is_refund_exist = $this->ssa_is_transaction_exists($this->transactions_table, [
                    'transaction_type' => self::METHOD_REFUND,
                    'transaction_id' => $mtcode,
                ]);

                if (!$is_refund_exist) {
                    $this->custom_message =  'Mtcode: ' . $mtcode .' Transaction record refund not found.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
                }
            } else {
                $this->custom_message =  'Mtcode: ' . $mtcode .' Transaction record not found.';
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
            }
        }

        if ($this->transaction_already_exist) {
            //wallet adjustment
            foreach ($this->request_params['mtcode'] as $mtcode) {
                $this->external_transaction_id = $mtcode;
                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $mtcode]);
                if (!empty($transaction_data) && is_array($transaction_data)) {
                    $account = isset($transaction_data['account']) ? $transaction_data['account'] : null;
                    $this->request_params['amount'] = isset($transaction_data['amount']) ? $transaction_data['amount'] : 0;

                    $this->additional_data = [
                        'bet_amount' => $this->request_params['amount'],
                        'result_amount' => -$this->request_params['amount'],
                        'flag_of_updated_result' => self::FLAG_UPDATED,
                    ];

                    if (empty($this->player_details)) {
                        $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);
                    }

                    $balance = $this->queryPlayerBalance($this->player_details['username']);
                    if (!$is_cancelled) {
                        $controller = $this;
                        $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                            list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::DECREASE, self::UPDATE, $balance, $http_response, $operator_response, 'refund', true);
                            return $success;
                        });
                    } else {
                        $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $mtcode]);
                        $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
                    }
                } else {
                    $this->custom_message = 'Transaction record bet, rollout or takeall not found.';
                    return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
                }
            }
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function wins() # (Only for Sports and Lotto)
    {
        $this->transaction_type = self::METHOD_WIN;

        $rule_sets = [
            'list' => 'required',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        if (is_array($this->request_params['list'])) {
            $list_parameter = $this->request_params['list'];
        } else {
            $list_parameter = json_decode($this->request_params['list'], true);
        }

        $success = true;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;
        $success_data = [];
        $failed_data = [];

        $this->data = [
            'success' => $success_data,
            'failed' => $failed_data,
        ];

        foreach ($list_parameter as $list_request_params) {
            $this->transaction_already_exist = false;

            $rule_sets = [
                'account' => 'required|same',
                'eventtime' => 'required|date_format:RFC3339',
                'ucode' => 'required',
                'event' => 'required',
            ];

            if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
                $rule_sets['session'] = 'required|same';
            }

            list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $list_request_params);

            $this->request_params['account'] = isset($list_request_params['account']) && !empty($list_request_params['account']) ? $list_request_params['account'] : null;
            $this->request_params['ucode'] = isset($list_request_params['ucode']) && !empty($list_request_params['ucode']) ? $list_request_params['ucode'] : null;
            $this->request_params['event'] = isset($list_request_params['event']) && !empty($list_request_params['event']) ? $list_request_params['event'] : null;

            if ($is_valid) {
                $balance = $this->queryPlayerBalance($this->player_details['username']);

                 if(isset($this->request_params['event'])) {
                    if (is_array($this->request_params['event'])) {
                        $event_parameter = $this->request_params['event'];
                    } else {
                        $event_parameter = json_decode($this->request_params['event'], true);
                    }
                }

                $proceed = true;
                $validated_event = [];

                foreach ($event_parameter as $check_event_request_params) {
                    $rule_sets = [
                        'mtcode' => 'required|unique',
                        'amount' => 'required|positive|numeric|nullable',
                        'validbet' => 'optional|positive|numeric|nullable',
                        'roundid' => 'required',
                        'eventtime' => 'required|date_format:RFC3339',
                        'gamecode' => 'required',
                        'gamehall' => 'required|same',
                    ];

                    list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $check_event_request_params);

                    if (!$is_valid) {
                        $proceed = false;
                    }
                }

                if ($proceed) {
                    $validated_event = $event_parameter;
                }

                if ($proceed && !empty($validated_event)) {
                    $is_valid = true;
                    foreach ($validated_event as $event_request_params) {
                        $this->request_params['mtcode'] = $this->external_transaction_id = isset($event_request_params['mtcode']) && !empty($event_request_params['mtcode']) ? $event_request_params['mtcode'] : null;
                        $this->request_params['amount'] = isset($event_request_params['amount']) && !empty($event_request_params['amount']) ? $event_request_params['amount'] : 0;
                        $this->request_params['validbet'] = isset($event_request_params['validbet']) && !empty($event_request_params['validbet']) ? $event_request_params['validbet'] : 0;
                        $this->request_params['roundid'] = isset($event_request_params['roundid']) && !empty($event_request_params['roundid']) ? $event_request_params['roundid'] : null;
                        $this->request_params['eventTime'] = isset($event_request_params['eventtime']) && !empty($event_request_params['eventtime']) ? $event_request_params['eventtime'] : null;
                        $this->request_params['gamecode'] = isset($event_request_params['gamecode']) && !empty($event_request_params['gamecode']) ? $event_request_params['gamecode'] : null;
                        $this->request_params['gamehall'] = isset($event_request_params['gamehall']) && !empty($event_request_params['gamehall']) ? $event_request_params['gamehall'] : null;

                        $bet_transaction = $this->ssa_get_transactions($this->transactions_table, [
                            'transaction_type' => self::METHOD_BET,
                            'account' => $this->request_params['account'],
                            'game_id' => $this->request_params['gamecode'],
                            'round_id' => $this->request_params['roundid'],
                        ]);

                        if (empty($bet_transaction)) {
                            $is_valid = false;
                            $this->custom_message = 'Mtcode: ' .$this->request_params['mtcode']. ' Bet transaction record not found.';
                            $operator_response = self::ERROR_TRANSACTION_NOT_FOUND;
                        } else {
                            if (!empty($bet_transaction['external_unique_id'])) {
                                $this->seamless_service_related_unique_id = $this->utils->mergeArrayValues(['game', $this->game_platform_id, $bet_transaction['external_unique_id']]);
                                $this->seamless_service_related_action = Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET;
                            }
                        }

                        $is_already_processed_by_refund = $this->ssa_is_transaction_exists($this->transactions_table, [
                            'transaction_type' => self::METHOD_REFUND,
                            'account' => $this->request_params['account'],
                            'game_id' => $this->request_params['gamecode'],
                            'round_id' => $this->request_params['roundid'],
                        ]);

                        if ($is_already_processed_by_refund) {
                            $is_valid = false;
                            $this->custom_message = 'Mtcode: ' .$this->request_params['mtcode']. ' Already processed by refund.';
                            $operator_response = self::ERROR_ALREADY_PROCESSED;
                        }

                        $is_already_processed_by_cancel = $this->ssa_is_transaction_exists($this->transactions_table, [
                            'transaction_type' => self::METHOD_CANCEL,
                            'account' => $this->request_params['account'],
                            'game_id' => $this->request_params['gamecode'],
                            'round_id' => $this->request_params['roundid'],
                        ]);

                        if ($is_already_processed_by_cancel) {
                            $is_valid = false;
                            $this->custom_message = 'Mtcode: ' .$this->request_params['mtcode']. ' Already processed by cancel.';
                            $operator_response = self::ERROR_TRANSACTION_NOT_FOUND;
                        }

                        if ($is_valid) {
                            if (!$this->transaction_already_exist) {
                                $controller = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet(self::INCREASE, self::INSERT, $balance, $http_response, $operator_response, 'payout', true);
                                    return $success;
                                });
                            } else {
                                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $this->request_params['mtcode']]);
                                $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
                            }

                            if ($success) {
                                $success_data = [
                                    'account' => $this->request_params['account'],
                                    'balance' => $balance,
                                    'currency' => $this->currency,
                                    'ucode' => $this->request_params['ucode'],
                                ];
                            } else {
                                $failed_data = [
                                    'account' => $this->request_params['account'],
                                    'code' => $operator_response['code'],
                                    'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                                    'ucode' => $this->request_params['ucode'],
                                ];
                            }
                        } else {
                            $failed_data = [
                                'account' => $this->request_params['account'],
                                'code' => $operator_response['code'],
                                'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                                'ucode' => $this->request_params['ucode'],
                            ];
                        }
                    }
                } else {
                    $failed_data = [
                        'account' => $this->request_params['account'],
                        'code' => $operator_response['code'],
                        'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                        'ucode' => $this->request_params['ucode'],
                    ];
                }
            } else {
                $failed_data = [
                    'account' => $this->request_params['account'],
                    'code' => $operator_response['code'],
                    'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                    'ucode' => $this->request_params['ucode'],
                ];
            }

            if (!empty($success_data)) {
                array_push($this->data['success'], $success_data);
            }

            if (!empty($failed_data)) {
                array_push($this->data['failed'], $failed_data);
            }

            unset($this->custom_message, $success_data, $failed_data);
        }

        return $this->setResponse($http_response, self::SUCCESS);
    }

    private function amends() # (Only for Sports and Lotto)
    {
        $this->transaction_type = self::METHOD_AMEND;

        $rule_sets = [
            'list' => 'required',
        ];

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        if (is_array($this->request_params['list'])) {
            $list_parameter = $this->request_params['list'];
        } else {
            $list_parameter = json_decode($this->request_params['list'], true);
        }

        $success = true;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;
        $success_data = [];
        $failed_data = [];

        $this->data = [
            'success' => $success_data,
            'failed' => $failed_data,
        ];

        foreach ($list_parameter as $list_request_params) {
            $this->transaction_already_exist = false;

            $rule_sets = [
                'account' => 'required|same',
                'eventtime' => 'required|date_format:RFC3339',
                'amount' => 'required|positive|numeric|nullable',
                'action' => 'required',
                'ucode' => 'required',
                'event' => 'required',
            ];

            if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
                $rule_sets['session'] = 'required|same';
            }

            list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $list_request_params);

            $this->request_params['account'] = isset($list_request_params['account']) && !empty($list_request_params['account']) ? $list_request_params['account'] : null;
            $this->request_params['ucode'] = isset($list_request_params['ucode']) && !empty($list_request_params['ucode']) ? $list_request_params['ucode'] : null;
            $this->request_params['event'] = isset($list_request_params['event']) && !empty($list_request_params['event']) ? $list_request_params['event'] : null;

            if ($is_valid) {
                $balance = $this->queryPlayerBalance($this->player_details['username']);

                 if(isset($this->request_params['event'])) {
                    if (is_array($this->request_params['event'])) {
                        $event_parameter = $this->request_params['event'];
                    } else {
                        $event_parameter = json_decode($this->request_params['event'], true);
                    }
                }

                $proceed = true;
                $validated_event = [];

                foreach ($event_parameter as $check_event_request_params) {
                    $rule_sets = [
                        'mtcode' => 'required|unique',
                        'amount' => 'required|positive|numeric|nullable',
                        'validbet' => 'optional|positive|numeric|nullable',
                        'action' => 'required',
                        'roundid' => 'required',
                        'eventtime' => 'required|date_format:RFC3339',
                        'gamecode' => 'required',
                    ];

                    list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $check_event_request_params);

                    if (!$is_valid) {
                        $proceed = false;
                    }
                }

                if ($proceed) {
                    $validated_event = $event_parameter;
                }

                if ($proceed && !empty($validated_event)) {
                    $is_valid = true;
                    $before_balance = $this->queryPlayerBalance($this->player_details['username']);
                    foreach ($validated_event as $event_request_params) {
                        $this->request_params['mtcode'] = $this->external_transaction_id = isset($event_request_params['mtcode']) && !empty($event_request_params['mtcode']) ? $event_request_params['mtcode'] : null;
                        $this->request_params['amount'] = isset($event_request_params['amount']) && !empty($event_request_params['amount']) ? $event_request_params['amount'] : 0;
                        $this->request_params['validbet'] = isset($event_request_params['validbet']) && !empty($event_request_params['validbet']) ? $event_request_params['validbet'] : 0;
                        $this->request_params['action'] = isset($event_request_params['action']) && !empty($event_request_params['action']) ? $event_request_params['action'] : null;
                        $this->request_params['roundid'] = isset($event_request_params['roundid']) && !empty($event_request_params['roundid']) ? $event_request_params['roundid'] : null;
                        $this->request_params['eventtime'] = isset($event_request_params['eventtime']) && !empty($event_request_params['eventtime']) ? $event_request_params['eventtime'] : null;
                        $this->request_params['gamecode'] = isset($event_request_params['gamecode']) && !empty($event_request_params['gamecode']) ? $event_request_params['gamecode'] : null;

                        /* $this->additional_data = [
                            'bet_amount' => $this->request_params['validbet'],
                            'flag_of_updated_result' => self::FLAG_UPDATED,
                        ]; */

                        $is_already_processed_by_win = $this->ssa_is_transaction_exists($this->transactions_table, [
                            'transaction_type' => self::METHOD_WIN,
                            'account' => $this->request_params['account'],
                            'game_id' => $this->request_params['gamecode'],
                            'round_id' => $this->request_params['roundid'],
                        ]);

                        if (!$is_already_processed_by_win) {
                            $is_valid = false;
                            $this->custom_message = 'Mtcode: ' .$this->request_params['mtcode']. ' Win transaction record not found.';
                            $operator_response = self::ERROR_TRANSACTION_NOT_FOUND;
                        }

                        if ($is_valid) {
                            if (!$this->transaction_already_exist) {
                                $controller = $this;
                                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                                    if ($controller->request_params['action'] == 'credit') {
                                        $adjustment_type = self::INCREASE;
                                        /* $this->additional_data['result_amount'] = $this->request_params['amount']; */
                                    } else {
                                        $adjustment_type = self::DECREASE;
                                        /* $this->additional_data['result_amount'] = -$this->request_params['amount']; */
                                    }

                                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet($adjustment_type, self::INSERT, $balance, $http_response, $operator_response, 'adjustment', true);
                                    return $success;
                                });
                            } else {
                                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $this->request_params['mtcode']]);
                                $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
                            }

                            if ($success) {
                                $success_data = [
                                    'account' => $this->request_params['account'],
                                    'currency' => $this->currency,
                                    'before' => $before_balance,
                                    'balance' => $balance,
                                    'ucode' => $this->request_params['ucode'],
                                ];
                            } else {
                                $failed_data = [
                                    'account' => $this->request_params['account'],
                                    'code' => $operator_response['code'],
                                    'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                                    'ucode' => $this->request_params['ucode'],
                                ];
                            }
                        } else {
                            $failed_data = [
                                'account' => $this->request_params['account'],
                                'code' => $operator_response['code'],
                                'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                                'ucode' => $this->request_params['ucode'],
                            ];
                        }
                    }
                } else {
                    $failed_data = [
                        'account' => $this->request_params['account'],
                        'code' => $operator_response['code'],
                        'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                        'ucode' => $this->request_params['ucode'],
                    ];
                }
            } else {
                $failed_data = [
                    'account' => $this->request_params['account'],
                    'code' => $operator_response['code'],
                    'message' => !empty($this->custom_message) ? $this->custom_message : $operator_response['message'],
                    'ucode' => $this->request_params['ucode'],
                ];
            }

            if (!empty($success_data)) {
                array_push($this->data['success'], $success_data);
            }

            if (!empty($failed_data)) {
                array_push($this->data['failed'], $failed_data);
            }

            unset($this->custom_message, $success_data, $failed_data);
        }

        return $this->setResponse($http_response, self::SUCCESS);
    }

    private function amend() # (Only for Sports and Lotto)
    {
        $this->transaction_type = self::METHOD_AMEND;

        $rule_sets = [
            'account' => 'required|same',
            'action' => 'required',
            'gamecode' => 'required',
            'gamehall' => 'required|same',
            'amount' => 'required|positive|numeric|nullable',
            'createTime' => 'required|date_format:RFC3339',
            'data' => 'required',
        ];

        if ($this->use_session_token && in_array($this->action_type, $this->session_methods)) {
            $rule_sets['session'] = 'required|same';
        }

        list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $this->request_params);

        if (!$is_valid) {
            return $this->setResponse($http_response, $operator_response);
        }

        $rule_sets = [
            'mtcode' => 'required|unique',
            'amount' => 'required|positive|numeric|nullable',
            'validbet' => 'optional|positive|numeric|nullable',
            'roundid' => 'required',
            'eventtime' => 'required|date_format:RFC3339',
            'action' => 'required',
        ];

        if (is_array($this->request_params['data'])) {
            $data_parameter = $this->request_params['data'];
        } else {
            $data_parameter = json_decode($this->request_params['data'], true);
        }

        //validate data request params
        foreach ($data_parameter as $request_params) {
            list($is_valid, $http_response, $operator_response) = $this->validateRequestParams($rule_sets, $request_params);

            if (!$is_valid) {
                return $this->setResponse($http_response, $operator_response);
            }
        }

        foreach ($data_parameter as $request_params) {
            $is_already_processed_by_win = $this->ssa_is_transaction_exists($this->transactions_table, [
                'transaction_type' => self::METHOD_WIN,
                'account' => $this->request_params['account'],
                'game_id' => $this->request_params['gamecode'],
                'round_id' => $request_params['roundid'],
            ]);

            if (!$is_already_processed_by_win) {
                $this->custom_message = 'Mtcode: ' . $request_params['mtcode']. ' Win transaction record not found.';
                return $this->setResponse(self::HTTP_RESPONSE_STATUS_CODE_200, self::ERROR_TRANSACTION_NOT_FOUND);
            }
        }

        $success = true;
        $balance = $this->queryPlayerBalance($this->player_details['username']);
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::SUCCESS;
        //proceed to wallet adjustment
        foreach ($data_parameter as $request_params) {
            $this->request_params['mtcode'] = $this->external_transaction_id = isset($request_params['mtcode']) && !empty($request_params['mtcode']) ? $request_params['mtcode'] : null;
            $this->request_params['amount'] = isset($request_params['amount']) && !empty($request_params['amount']) ? $request_params['amount'] : 0;
            $this->request_params['validbet'] = isset($request_params['validbet']) && !empty($request_params['validbet']) ? $request_params['validbet'] : 0;
            $this->request_params['roundid'] = isset($request_params['roundid']) && !empty($request_params['roundid']) ? $request_params['roundid'] : null;
            $this->request_params['eventTime'] = isset($request_params['eventtime']) && !empty($request_params['eventtime']) ? $request_params['eventtime'] : null;
            $this->request_params['action'] = isset($request_params['action']) && !empty($request_params['action']) ? $request_params['action'] : null;

            /* $this->additional_data = [
                'bet_amount' => $this->request_params['validbet'],
                'flag_of_updated_result' => self::FLAG_UPDATED,
            ]; */

            if (!$this->transaction_already_exist) {
                $controller = $this;
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function() use($controller, &$balance, &$http_response, &$operator_response) {
                    if ($controller->request_params['action'] == 'credit') {
                        $adjustment_type = self::INCREASE;
                        /* $this->additional_data['result_amount'] = $this->request_params['amount']; */
                    } else {
                        $adjustment_type = self::DECREASE;
                        /* $this->additional_data['result_amount'] = -$this->request_params['amount']; */
                    }

                    list($success, $balance, $http_response, $operator_response) = $controller->adjustWallet($adjustment_type, self::INSERT, $balance, $http_response, $operator_response, 'adjustment', true);
                    return $success;
                });
            } else {
                $transaction_data = $this->ssa_get_transaction($this->transactions_table, ['transaction_id' => $this->request_params['mtcode']]);
                $balance = $this->truncateAmount($transaction_data['after_balance'], 4);
                break;
            }

            if (!$success) {
                break;
            }
        }

        if ($success) {
            $this->data = [
                'balance' => $balance,
                'currency' => $this->currency,
            ];
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return $this->setResponse($http_response, $operator_response);
    }

    private function adjustWallet($adjustment_type, $query_type, $balance, $http_response, $operator_response, $actionType = 'bet', $isEnd = false)
    {
        // set remote wallet unique ids
        $seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->external_transaction_id]);
        $this->ssa_set_uniqueid_of_seamless_service($seamless_service_unique_id);
        $this->ssa_set_external_game_id(!empty($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null);
        $this->ssa_set_game_provider_action_type($actionType);
        $this->ssa_set_game_provider_is_end_round($isEnd);
        $this->ssa_set_game_provider_round_id(!empty($this->request_params['roundid']) ? $this->request_params['roundid'] : null);
        $this->ssa_set_related_uniqueid_of_seamless_service($this->seamless_service_related_unique_id);
        $this->ssa_set_related_action_of_seamless_service($this->seamless_service_related_action);

        $success = true;
        $before_balance = $after_balance = $balance;
        $amount = isset($this->request_params['amount']) ? $this->request_params['amount'] : 0;
        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        if ($adjustment_type == self::DECREASE) {
            if ($amount > $before_balance) {
                $success = false;
                $operator_response = self::ERROR_INSUFFICIENT_BALANCE;
            } else {
                if ($amount == 0) {
                    $after_balance = $before_balance;
                } else {
                    $after_balance = null;
                    $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);

                    $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    if (!$success) {
                        // remote wallet error
                        if ($this->ssa_enabled_remote_wallet() && !empty($this->ssa_get_remote_wallet_error_code())) {
                            // treat success if remote wallet return double uniqueid
                            if ($this->ssa_remote_wallet_error_double_unique_id()) {
                                $operator_response = self::SUCCESS;
                                $success = true;
                                $before_balance += $amount;
                            }

                            if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                                $operator_response = $this->ssa_operator_response_custom_message(self::ERROR_SERVER, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']);
                                $success = false;
                            }

                            if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                                $operator_response = $this->ssa_operator_response_custom_message(self::ERROR_SERVER, self::SYSTEM_ERROR_REMOTE_WALLET_INSUFFICIENT_BALANCE['code']);
                                $success = false;
                            }

                            if ($this->ssa_remote_wallet_error_maintenance()) {
                                $operator_response = $this->ssa_operator_response_custom_message(self::ERROR_SERVER, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']);
                                $success = false;
                            }
                        } else {
                            $operator_response = self::ERROR_INSUFFICIENT_BALANCE;
                        }
                    }

                    $after_balance = !empty($after_balance) ? $after_balance : $this->queryPlayerBalance($this->player_details['username']);
                }
            }
        } else {
            $increase = true;

            if ($amount == 0) {
                $increase = false;
                $after_balance = $before_balance;

                if ($this->ssa_enabled_remote_wallet() && in_array($this->action_type, [
                    self::METHOD_ENDROUND,
                    self::METHOD_ROLLIN,
                ])) {
                    $increase = true;
                    $this->utils->debug_log(__METHOD__, "{$this->game_platform_id} CQ9_SEAMLESS_GAME_API: amount 0 call remote wallet", 'request_params', $this->request_params);
                }
            }

            if ($increase) {
                $after_balance = null;
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);

                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                if (!$success) {
                    // remote wallet error
                    if ($this->ssa_enabled_remote_wallet() && !empty($this->ssa_get_remote_wallet_error_code())) {
                        // treat success if remote wallet return double uniqueid
                        if ($this->ssa_remote_wallet_error_double_unique_id()) {
                            $operator_response = self::SUCCESS;
                            $success = true;
                            $before_balance -= $amount;
                        }

                        if ($this->ssa_remote_wallet_error_invalid_unique_id()) {
                            $operator_response = $this->ssa_operator_response_custom_message(self::ERROR_SERVER, self::SYSTEM_ERROR_REMOTE_WALLET_INVALID_UNIQUEID['code']);
                            $success = false;
                        }

                        if ($this->ssa_remote_wallet_error_insufficient_balance()) {
                            $operator_response = $this->ssa_operator_response_custom_message(self::ERROR_SERVER, self::SYSTEM_ERROR_REMOTE_WALLET_INSUFFICIENT_BALANCE['code']);
                            $success = false;
                        }

                        if ($this->ssa_remote_wallet_error_maintenance()) {
                            $operator_response = $this->ssa_operator_response_custom_message(self::ERROR_SERVER, self::SYSTEM_ERROR_REMOTE_WALLET_MAINTENANCE['code']);
                            $success = false;
                        }
                    } else {
                        $operator_response = self::ERROR_TRANSACTION_NOT_FOUND;
                    }
                }

                $after_balance = !empty($after_balance) ? $after_balance : $this->queryPlayerBalance($this->player_details['username']);
            }
        }

        $balance = $after_balance;

        $transaction_data = [
            'before_balance' => $before_balance,
            'after_balance' => $after_balance,
            'amount' => $amount,

            // additional
            'adjustment_type' => $adjustment_type,
            'game_username' => $this->player_details['game_username'],
            'player_id' => $this->player_details['player_id'],
            'transaction_type' => !empty($this->transaction_type) ? $this->transaction_type : $this->action_type,
            'transaction_id' => isset($this->request_params['mtcode']) && !empty($this->request_params['mtcode']) ? $this->request_params['mtcode'] : null,
            'game_id' => isset($this->request_params['gamecode']) && !empty($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null,
            'round_id' => isset($this->request_params['roundid']) && !empty($this->request_params['roundid']) ? $this->request_params['roundid'] : null,
        ];

        if (!empty($this->remote_wallet_status) && !empty($transaction_data)) {
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $transaction_data);
        }

        if ($success) {
            $this->processed_transaction_id = $this->preprocessRequestData($query_type, $transaction_data);

            if (!$this->processed_transaction_id) {
                $success = false;
                $operator_response = self::ERROR_SERVER;
            } else {
                if ($query_type == self::INSERT) {
                    array_push($this->processed_multiple_transaction, $this->processed_transaction_id);
                }
            }
        } else {
            if ($http_response['code'] == 200 && $operator_response['code'] == "0") {
                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                $operator_response = self::ERROR_SERVER;
            }
        }

        return array($success, $balance, $http_response, $operator_response);
    }

    private function validateApiHeaders()
    {
        $is_valid = true;
        $wtoken = isset($this->request_headers['Wtoken']) && !empty($this->request_headers['Wtoken']) ? $this->request_headers['Wtoken'] : null;

        if ($this->check_api_token && $wtoken != $this->api_token) {
            $this->custom_message = 'Invalid wtoken!';
            $is_valid = false;
        }

        return $is_valid;
    }

    private function getRequestParams()
    {
        $file_get_contents = file_get_contents("php://input");

        $this->utils->debug_log(__CLASS__, __METHOD__, self::SEAMLESS_GAME_API, 'raw_request_params', $file_get_contents);

        if ($_SERVER['REQUEST_METHOD'] == self::REQUEST_METHOD_GET) {
            parse_str($file_get_contents, $request_params);
            if (empty($request_params)) {
                $request_params = $this->input->get();
            }
        } else {
            if (!empty($this->input->post())) {
                $request_params = $this->input->post();
            } else {
                $request_params = !empty($file_get_contents) ? json_decode($file_get_contents, true) : [];
            }
        }

        return $request_params;
    }

    private function validateRequestParams($rule_sets, array $request_params)
    {
        $is_valid = true;
        $http_response = self::HTTP_RESPONSE_STATUS_CODE_200;
        $operator_response = self::ERROR_PARAMETER;

        foreach ($rule_sets as $param => $rules) {
            $this->param = $param;
            $rules = explode('|', $rules);
            foreach ($rules as $rule) {
                switch ($rule) {
                    case 'optional':
                        $is_valid = true;
                        break;
                    case 'array':
                        if (isset($request_params[$param]) && !is_array($request_params[$param])) {
                            $is_valid = false;
                            $this->custom_message = self::PREFIX_PARAMETER_ERROR . " '" . $param . "' must be an " . $rule . '.';
                        }
                        break;
                    case 'nullable':
                        continue 2;
                    case 'required':
                        if (in_array('nullable', $rules)) {
                            if (!array_key_exists($param, $request_params)) {
                                $is_valid = false;
                                $this->custom_message = self::PREFIX_PARAMETER_ERROR . " '" . $param . "' is " . $rule . '.' . $this->showRequestParamsGuide($param);
                            }
                        } else {
                            if (!array_key_exists($param, $request_params) || empty($request_params[$param])) {
                                $is_valid = false;
                                $this->custom_message = self::PREFIX_PARAMETER_ERROR . " '" . $param . "' is " . $rule . '.' . $this->showRequestParamsGuide($param);
                            }
                        }
                        break;
                    case 'positive':
                        if (isset($request_params[$param]) && $request_params[$param] < 0) {
                            $is_valid = false;
                            $this->custom_message = self::PREFIX_PARAMETER_ERROR . " '" . $param . "' must be " . $rule . '.';
                        }
                        break;
                    case 'numeric':
                        if (isset($request_params[$param]) && !is_numeric($request_params[$param])) {
                            $is_valid = false;
                            $this->custom_message = self::PREFIX_PARAMETER_ERROR . " '" . $param . "' must be " . $rule . '.';
                        }
                        break;
                    case 'date_format:RFC3339':
                        if (isset($request_params[$param]) && !$this->validateRFC3339Datetime($request_params[$param])) {
                            $is_valid = false;
                            $operator_response = self::ERROR_TIME_FORMAT;
                            if (empty($this->custom_message)) {
                                $this->custom_message = self::PREFIX_DATETIME_FORMAT_ERROR . " '" . $param . "' must be " . $rule;
                            }
                        }
                        break;
                    case 'same':
                        //register param here to check if match the system info under validation
                        switch ($param) {
                            case 'account':
                                if (isset($request_params[$param])) {
                                    $this->player_details = $this->ssa_get_player_details_by_game_username($request_params[$param], $this->game_platform_id);
                                    if (empty($this->player_details)) {
                                        $is_valid = false;
                                        if (!empty($request_params[$param])) {
                                            $this->custom_message = $param .': '. $request_params[$param] . ' not found.';
                                        }
                                        $operator_response = self::ERROR_PLAYER_NOT_FOUND;
                                    }
                                }
                                break;
                            case 'session':
                                if (isset($request_params[$param])) {
                                    $this->player_details = $this->ssa_get_player_details_by_token($request_params[$param], $this->game_platform_id);
                                    if (empty($this->player_details)) {
                                        $is_valid = false;
                                        $this->custom_message = self::ERROR_MESSAGE_INVALID_SESSION . $this->showRequestParamsGuide('session');
                                    } else {
                                        if (isset($request_params['account']) && $request_params['account'] != $this->player_details['game_username']) {
                                            $is_valid = false;
                                            $this->custom_message = 'account: '. $request_params['account'] . ' not found. Invalid session.' . $this->showRequestParamsGuide('session');
                                        }
                                    }
                                    $operator_response = self::ERROR_PLAYER_NOT_FOUND;
                                }
                                break;
                            case 'gamehall':
                                if ($this->check_same_gamehall && isset($request_params[$param]) && $request_params[$param] != $this->gamehall) {
                                    $is_valid = false;
                                    $operator_response = self::ERROR_TIME_FORMAT;
                                    $this->custom_message = self::PREFIX_PARAMETER_ERROR . " Invalid '" . $param . "'." . $this->showRequestParamsGuide($param);
                                }
                                break;
                            default:
                                $is_valid = false;
                                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                                $operator_response = self::ERROR_SERVER;
                                $this->custom_message = "Not registered key '" . $param . "' on rule '" . $rule . "'";
                                break;
                        }
                        break;
                    case 'unique':
                        //register param here to check if already exist
                        switch ($param) {
                            case 'mtcode':
                                if (isset($request_params[$param])) {
                                    if (in_array('array', $rules) && is_array($request_params[$param])) {
                                        foreach ($request_params[$param] as $mtcode) {
                                            $this->external_transaction_id = $mtcode;
                                            if ($this->ssa_is_transaction_exists_by_external_uniqueId($this->transactions_table, $this->external_transaction_id)) {
                                                $this->transaction_already_exist = true;
                                            }
                                        }
                                    } else {
                                        $this->external_transaction_id = $request_params[$param];
                                        if ($this->ssa_is_transaction_exists_by_external_uniqueId($this->transactions_table, $this->external_transaction_id)) {
                                            $this->transaction_already_exist = true;
                                        }
                                    }
                                }
                                break;
                            default:
                                $is_valid = false;
                                $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                                $operator_response = self::ERROR_SERVER;
                                $this->custom_message = "Not registered key '" . $param . "' on rule '" . $rule . "'";
                                break;
                        }
                        break;
                    default:
                        $is_valid = false;
                        $http_response = self::HTTP_RESPONSE_STATUS_CODE_500;
                        $operator_response = self::ERROR_SERVER;
                        $this->custom_message = "Invalid rule '" . $rule . "' on parameter '" . $param . "'";
                        break;
                }
            }

            if (!$is_valid) {
                break;
            }
        }

        return array($is_valid, $http_response, $operator_response);
    }

    private function validateRFC3339Datetime($date)
    {
        if ($this->check_datetime_format) {
            $have_decimal = strpos($date, '.');
            if ($have_decimal !== false) {
                if (DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $date) === FALSE) {
                    $date_parse = date_parse($date);
                    $tz_abbr = isset($date_parse['tz_abbr']) ? $date_parse['tz_abbr'] : null;
                    $find_milliseconds = isset(explode('.', $date)[1]) ? explode('.', $date)[1] : null;
                    $milliseconds = isset(explode('-', $find_milliseconds)[0]) ? explode('-', $find_milliseconds)[0] : null;
                    $count_milliseconds = strlen($milliseconds);
                    $utc = isset(explode('-', $find_milliseconds)[1]) ? '-' . explode('-', $find_milliseconds)[1] : null;

                    if (!empty($tz_abbr)) {
                        return false;
                    }

                    if ($utc != $this->utc) {
                        return false;
                    }

                    if (strlen($count_milliseconds) > 9) {
                        $this->custom_message = self::PREFIX_DATETIME_FORMAT_ERROR . " {$count_milliseconds} digit milliseconds not supported";
                        return false;
                    }
                } else {
                    $utc = isset(explode('-', $date)[3]) ? '-' . explode('-', $date)[3] : null;

                    if ($utc != $this->utc) {
                        return false;
                    }
                }
            } else {
                if (DateTime::createFromFormat(DateTime::RFC3339, $date) === FALSE) { //'Y-m-d\TH:i:sP'
                    return false;
                } else {
                    $utc = isset(explode('-', $date)[3]) ? '-' . explode('-', $date)[3] : null;

                    if ($utc != $this->utc) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function showRequestParamsGuide($param)
    {
        $request_params = $this->request_params;
        $message = '';

        if ($this->show_request_params_guide) {
            $account = isset($request_params['account']) ? $request_params['account'] : null;
            $this->player_details = $this->ssa_get_player_details_by_game_username($account, $this->game_platform_id);
            $player_id = isset($this->player_details['player_id']) ? $this->player_details['player_id'] : null;
            $player_token = $this->ssa_get_player_common_token_by_player_id($player_id);

            //register param here
            switch ($param) {
                case 'session':
                    if (!empty($player_token)) {
                        $message = ' playerToken: ' . $player_token;
                    }
                    break;
                case 'gamehall':
                    $message = " Must be '" . $this->gamehall . "'";
                    break;
                default:
                    $message = '';
                    break;
            }
        }

        return $message;
    }

    private function queryPlayerBalance($playerName)
    {
        // $playerBalance = $this->game_api->queryPlayerBalance($playerName);

        // if ($playerBalance['success']) {
        //     $result = $this->truncateAmount($playerBalance['balance'], 4);
        // } else {
        //     $result = false;
        // }

        // return $result;
        if($this->player_details['player_id']){
            return $this->ssa_get_player_wallet_balance($this->player_details['player_id'], self::SEAMLESS_GAME_API);
        } else {
            return false;
        }
    }

    public function truncateAmount($amount, $precision = 2)
    {
        $amount = floatval($amount);

        if ($amount == 0) {
            return $amount;
        }

        return floatval(bcdiv($amount, 1, $precision));
    }

    private function preprocessRequestData($query_type, $transaction_data)
    {
        if ($query_type == self::INSERT) {
            $field_id = $id = null;
            $update_with_result = false;

            $processed_data = [
                'game_platform_id' => $this->game_platform_id,
                'session' => isset($this->request_params['session']) && !empty($this->request_params['session']) ? $this->request_params['session'] : null,
                'player_id' => $this->player_details['player_id'],
                'account' => isset($this->request_params['account']) && !empty($this->request_params['account']) ? $this->request_params['account'] : null,
                'currency' => $this->currency,
                'platform' => isset($this->request_params['platform']) && !empty($this->request_params['platform']) ? $this->request_params['platform'] : null,
                'transaction_type' => !empty($this->transaction_type) ? $this->transaction_type : $this->action_type,
                'transaction_id' => isset($this->request_params['mtcode']) && !empty($this->request_params['mtcode']) ? $this->request_params['mtcode'] : null,
                'ucode' => isset($this->request_params['ucode']) && !empty($this->request_params['ucode']) ? $this->request_params['ucode'] : null,
                'gamehall' => isset($this->request_params['gamehall']) && !empty($this->request_params['gamehall']) ? $this->request_params['gamehall'] : null,
                'gametype' => isset($this->request_params['gametype']) && !empty($this->request_params['gametype']) ? $this->request_params['gametype'] : null,
                'game_id' => isset($this->request_params['gamecode']) && !empty($this->request_params['gamecode']) ? $this->request_params['gamecode'] : null,
                'round_id' => isset($this->request_params['roundid']) && !empty($this->request_params['roundid']) ? $this->request_params['roundid'] : null,
                'before_balance' => isset($transaction_data['before_balance']) && !empty($transaction_data['before_balance']) ? $transaction_data['before_balance'] : 0,
                'after_balance' => isset($transaction_data['after_balance']) && !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0,
                'amount' => isset($transaction_data['amount']) && !empty($transaction_data['amount']) ? $transaction_data['amount'] : 0,
                'rake' => isset($this->request_params['rake']) && !empty($this->request_params['rake']) ? $this->request_params['rake'] : 0,
                'jackpot' => isset($this->request_params['jackpot']) && !empty($this->request_params['jackpot']) ? $this->request_params['jackpot'] : 0,
                'action' => isset($this->request_params['action']) && !empty($this->request_params['action']) ? $this->request_params['action'] : null,
                'event_time' => isset($this->request_params['eventTime']) && !empty($this->request_params['eventTime']) ? $this->request_params['eventTime'] : null,
                'create_time' => isset($this->request_params['createTime']) && !empty($this->request_params['createTime']) ? $this->request_params['createTime'] : null,
                'start_at' => $this->CI->utils->getNowForMysql(),
                'end_at' => $this->CI->utils->getNowForMysql(),
                'status' => $this->setStatus($this->transaction_type),
                'elapsed_time' => intval($this->CI->utils->getExecutionTimeToNow() * 1000),
                'extra_info' => json_encode($this->request_params),
                'bet_amount' => isset($this->additional_data['bet_amount']) && !empty($this->additional_data['bet_amount']) ? $this->additional_data['bet_amount'] : 0,
                'win_amount' => isset($this->additional_data['win_amount']) && !empty($this->additional_data['win_amount']) ? $this->additional_data['win_amount'] : 0,
                'result_amount' => isset($this->additional_data['result_amount']) && !empty($this->additional_data['result_amount']) ? $this->additional_data['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->additional_data['flag_of_updated_result']) && !empty($this->additional_data['flag_of_updated_result']) ? $this->additional_data['flag_of_updated_result'] : self::FLAG_NOT_UPDATED,
                'external_unique_id' => $this->external_transaction_id,
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
        } else {
            $field_id = 'external_unique_id';
            $id = $this->external_transaction_id;
            $update_with_result = true;

            $processed_data = [
                'transaction_type' => $this->transaction_type,
                'before_balance' => $transaction_data['before_balance'],
                'after_balance' => $transaction_data['after_balance'],
                'amount' => $transaction_data['amount'],
                'end_at' => $this->CI->utils->getNowForMysql(),
                'updated_at' => $this->CI->utils->getNowForMysql(),
                'status' => $this->setStatus($this->transaction_type),
                'bet_amount' => isset($this->additional_data['bet_amount']) && !empty($this->additional_data['bet_amount']) ? $this->additional_data['bet_amount'] : 0,
                'win_amount' => isset($this->additional_data['win_amount']) && !empty($this->additional_data['win_amount']) ? $this->additional_data['win_amount'] : 0,
                'result_amount' => isset($this->additional_data['result_amount']) && !empty($this->additional_data['result_amount']) ? $this->additional_data['result_amount'] : 0,
                'flag_of_updated_result' => isset($this->additional_data['flag_of_updated_result']) && !empty($this->additional_data['flag_of_updated_result']) ? $this->additional_data['flag_of_updated_result'] : self::FLAG_NOT_UPDATED,
            ];

            $processed_data['md5_sum'] = $this->ssa_generate_md5_sum($processed_data, self::MD5_FIELDS_FOR_ORIGINAL_UPDATE, self::MD5_FLOAT_AMOUNT_FIELDS_UPDATE);
        }

        $processed_transaction_id = $this->ssa_insert_update_transaction($this->transactions_table, $query_type, $processed_data, $field_id, $id, $update_with_result);

        return $processed_transaction_id;
    }

    private function saveResponseResult($flag, $returnResponse, $http_response)
    {
        /* $response_result_id = $this->response_result->saveResponseResult(
            $this->game_platform_id,
            $flag,
            $this->action_type,
            json_encode($this->request_params),
            $returnResponse,
            $http_response['code'],
            $http_response['status_text'],
            is_array($this->request_headers) ? json_encode($this->request_headers) : $this->request_headers,
            ['player_id' => isset($this->player_details['player_id']) && !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null],
            false,
            null,
            intval($this->utils->getExecutionTimeToNow() * 1000) //costMs
        ); */

        $response_result_id = $this->ssa_save_response_result(
            $this->game_platform_id,
            $flag,
            $this->action_type,
            $this->request_params,
            $returnResponse,
            [
                'code' => $http_response['code'],
                'text' => $http_response['status_text'],
            ],
            !empty($this->player_details['player_id']) ? $this->player_details['player_id'] : null
        );

        return $response_result_id;
    }

    private function setStatus($action_type)
    {
        switch ($action_type) {
            case self::METHOD_BET:
                $status = Game_logs::STATUS_SETTLED;
                break;
            case self::METHOD_REFUND:
                $status = Game_logs::STATUS_REFUND;
                break;
            case self::METHOD_CANCEL:
                $status = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $status = Game_logs::STATUS_SETTLED;
                break;
        }

        return $status;
    }

    private function setResponse($http_response, $operator_response = [])
    {
        if (!empty($this->custom_message)) {
            $operator_response['message'] = $this->custom_message;
        }

        $flag = $operator_response['code'] == self::SUCCESS['code'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        if (isset($this->data['balance'])) {
            $this->data['balance'] = $this->ssa_operate_amount($this->data['balance'], $this->precision, $this->conversion, $this->arithmetic_name);
        }

        $returnResponse = [
            'data' => $this->data,
            'status' => [
                'code' => $operator_response['code'],
                'message' => $operator_response['message'],
                'datetime' => $this->datetime_rfc3339,
            ],
        ];

        $this->response_result_id = $this->saveResponseResult($flag, $returnResponse, $http_response);

        if (!empty($this->processed_multiple_transaction) && is_array($this->processed_multiple_transaction)) {
            foreach ($this->processed_multiple_transaction as $processed_transaction_id) {
                $updated_data = [
                    'response_result_id' => $this->response_result_id,
                ];

                $this->ssa_update_transaction_without_result($this->transactions_table, $updated_data, 'id', $processed_transaction_id);
            }
        }

        return $this->returnJsonResult($returnResponse, true, '*', false, false, $http_response['code']);
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => !empty($data['transaction_id']) ? $data['transaction_id'] : null,
            'round_id' => !empty($data['round_id']) ? $data['round_id'] : null,
            'external_game_id' => !empty($data['game_id']) ? $data['game_id'] : null,
            'player_id' => !empty($data['player_id']) ? $data['player_id'] : null,
            'game_username' => !empty($data['game_username']) ? $data['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['adjustment_type']) && $data['adjustment_type'] == $this->ssa_decrease ? $this->ssa_decrease : $this->ssa_increase,
            'action' => !empty($data['transaction_type']) ? $data['transaction_type'] : null,
            'game_platform_id' => $this->game_platform_id,
            'transaction_raw_data' => json_encode($this->request_params),
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => !empty($data['start_at']) ? $data['start_at'] : $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => $this->external_transaction_id,
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