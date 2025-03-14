<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: RTG
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2024 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -rtg_seamless_service_api.php
 **/

class Game_api_rtg_seamless extends Abstract_game_api {
    use Year_month_table_module;

    // default
    public $CI;
    public $http_method;
    public $api_url;
    public $language;
    public $force_language;
    public $currency;
    public $prefix_for_username;
    public $game_api_player_blocked_validate_api_methods;
    public $save_game_seamless_service_logs;
    public $sync_time_interval;
    public $sleep_time;
    public $enable_sync_original_game_logs;
    public $use_transaction_data;
    public $game_provider_gmt;
    public $game_provider_date_time_format;
    public $get_usec;
    public $use_bet_time;
    public $show_hint;
    public $api_name;
    public $token_timeout_seconds;
    public $force_refresh_token_timeout;
    public $get_token_test_accounts;
    public $game_launcher_url;
    public $use_utils_get_url;
    public $home_url;
    public $cashier_url;
    public $logout_url;
    public $failed_url;

    // conversions
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;

    // default tables
    public $original_seamless_wallet_transactions_table = 'rtg_seamless_wallet_transactions';
    public $game_seamless_service_logs_table = 'rtg_seamless_service_logs';
    public $original_seamless_game_logs_table = 'rtg_seamless_game_logs';

    // monthly transactions table
    public $initialize_monthly_transactions_table;
    public $use_monthly_transactions_table;
    public $force_check_previous_transactions_table;
    public $force_check_other_transactions_table;
    public $previous_table = null;
    public $show_logs;
    public $use_monthly_service_logs_table;
    public $use_monthly_game_logs_table;
    public $enable_merging_rows;

    // free spin API
    public $free_spin_reference_id_prefix;
    public $free_spin_reference_id_length;
    public $free_spin_default_number_of_rounds;
    public $free_spin_default_game_ids;
    public $free_spin_default_bet_value;
    public $free_spin_default_validity_hours;

    // for t1 API
    public $launcher_mode;
    public $is_support_lobby;
    public $game_type_demo_lobby_supported;
    public $game_type_lobby_supported;


    
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';

    const RESPONSE_CODE_SUCCESS = 0;

    const IS_PROCESSED = 1;

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'extra_info',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS = [
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_MERGE_FROM_TRANS = [
        'player_id',
        'before_balance',
        'after_balance',
        'game_code',
        'api_method',
        'transaction_type',
        'status',
        'response_result_id',
        'external_uniqueid',
        'start_at',
        'bet_at',
        'end_at',
        'created_at',
        'updated_at',
        'transaction_id',
        'round_id',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'wallet_adjustment_status',
        'is_processed',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    // TRANSACTION TYPES
    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    // GAME API NAME
    public $seamless_game_api_name = 'RTG_SEAMLESS_GAME_API';

    const API_startToken = 'startToken';
    const API_start = 'start';

    const URI_MAP = [
        self::API_createPlayer => '/player',
        self::API_isPlayerExist => '/player/id',
        self::API_logout => '/player/logout',
        self::API_startToken => '/start/token',
        self::API_start => '/start',
        self::API_queryGameListFromGameProvider => '/gamestrings',
        self::API_queryForwardGame => '/GameLauncher',
        self::API_queryForwardGameLobby => '/launcher/lobby',
    ];

    // API METHODS HERE
    const API_METHOD_PLACE_BET = 'placeBet';
    const API_METHOD_SETTLEMENT = 'settlement';
    const API_METHOD_CANCEL_BET = 'cancelBet';

    // additional
    public $agent;
    public $agent_id;
    public $username;
    public $password;
    public $token;

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);

        // default
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url', '');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language');
        $this->force_language = $this->getSystemInfo('force_language', false);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->save_game_seamless_service_logs = $this->getSystemInfo('save_game_seamless_service_logs', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->get_usec = $this->getSystemInfo('get_usec', true);
        $this->use_bet_time = $this->getSystemInfo('use_bet_time', true);
        $this->show_hint = $this->getSystemInfo('show_hint', false);
        $this->show_logs = $this->getSystemInfo('show_logs', false);
        $this->token_timeout_seconds = $this->getSystemInfo('token_timeout_seconds', 7200); // 1 minute (60), 1 hour (3600)
        $this->force_refresh_token_timeout = $this->getSystemInfo('force_refresh_token_timeout', false);
        $this->get_token_test_accounts = $this->getSystemInfo('get_token_test_accounts', []);
        $this->game_launcher_url = $this->getSystemInfo('game_launcher_url', '');
        $this->use_utils_get_url = $this->getSystemInfo('use_utils_get_url', false);
        $this->home_url = $this->getSystemInfo('home_url', '');
        $this->cashier_url = $this->getSystemInfo('cashier_url', $this->home_url);
        $this->logout_url = $this->getSystemInfo('logout_url', $this->home_url);
        $this->failed_url = $this->getSystemInfo('failed_url', $this->home_url);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        $this->ymt_init();

        // free spin API
        $this->free_spin_reference_id_prefix = $this->getSystemInfo('free_spin_reference_id_prefix', 'HS');
        $this->free_spin_reference_id_length = $this->getSystemInfo('free_spin_reference_id_length', 12);
        $this->free_spin_default_number_of_rounds = $this->getSystemInfo('free_spin_default_number_of_rounds', 1);
        $this->free_spin_default_game_ids = $this->getSystemInfo('free_spin_default_game_ids', '');
        $this->free_spin_default_bet_value = $this->getSystemInfo('free_spin_default_bet_value', '');
        $this->free_spin_default_validity_hours = $this->getSystemInfo('free_spin_default_validity_hours', '+2 hours');

        // for t1 api
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'lobbyAndSingle');
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', true);
        $this->game_type_demo_lobby_supported = $this->getSystemInfo('game_type_demo_lobby_supported', ['slots']);
        $this->game_type_lobby_supported = $this->getSystemInfo('game_type_lobby_supported', ['slots']);

        // additional
        $this->agent = $this->getSystemInfo('agent');
        $this->agent_id = $this->getSystemInfo('agent_id');
        $this->username = $this->getSystemInfo('username');
        $this->password = $this->getSystemInfo('password');
    }

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', true);
        $this->use_monthly_game_logs_table = $this->getSystemInfo('use_monthly_game_logs_table', false);

        $this->ymt_initialize($this->original_seamless_wallet_transactions_table, $this->use_monthly_transactions_table ? $this->use_monthly_transactions_table : $this->initialize_monthly_transactions_table);

        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_current_year_month_table();
            $this->previous_table = $this->ymt_get_previous_year_month_table();
        }

        if ($this->use_monthly_service_logs_table) {
            $this->ymt_initialize_tables($this->game_seamless_service_logs_table);
            $this->game_seamless_service_logs_table = $this->ymt_get_current_year_month_table($this->game_seamless_service_logs_table);
        }

        if ($this->use_monthly_game_logs_table) {
            $this->ymt_initialize_tables($this->original_seamless_game_logs_table);
            $this->original_seamless_game_logs_table = $this->ymt_get_current_year_month_table($this->original_seamless_game_logs_table);
        }
        // end monthly tables
    }

    public function getPlatformCode() {
        return RTG_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getSeamlessGameLogsTable() {
        return $this->original_seamless_game_logs_table;
    }

    public function getSeamlessTransactionTable() {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getGameSeamlessServiceLogsTable() {
        return $this->game_seamless_service_logs_table;
    }

    public function getHttpHeaders($params) {
        $headers = [
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
        ];

        $this->utils->debug_log($this->seamless_game_api_name, __METHOD__, 'api_name', $this->api_name, 'headers', $headers);

        return $headers;
    }

    protected function customHttpCall($ch, $params) {
        if (in_array($this->http_method, [self::HTTP_METHOD_POST, self::HTTP_METHOD_PUT])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->http_method == self::HTTP_METHOD_PUT ? 'PUT' : 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_isPlayerExist:
                    $url .= "{$api_uri}/{$params['login']}?agentId={$params['agentId']}";
                    break;
                default:
                    $url .= "{$api_uri}" . '?' . http_build_query($params);
                    break;
            }
        } else {
            switch ($api_name) {
                case self::API_logout:
                    $url .= "{$api_uri}/{$params['id']}";
                    break;
                default:
                    $url .= "{$api_uri}";
                    break;
            }
        }

        return $url;
    }

    public function processResultBoolean($response_result_id, $result_arr, $status_code, $player_name = null) {
        $success = false;

        if (in_array($status_code, [200, 201]) && !isset($result_arr['errorCode'])) {
            $success = true;
        }

        // player already exists
        if (!empty($result_arr['errorCode']) && $result_arr['errorCode'] == 11020) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($response_result_id);
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name . ' API got error ', $response_result_id, 'status_code', $status_code, 'player_name', $player_name, 'result', $result_arr);
        }

        return $success;
    }

    public function createPlayer($player_name, $player_id, $password, $email = null, $extra = null) {
        $createPlayer = parent::createPlayer($player_name, $player_id, $password, $email, $extra);
        $success = false;
        $message = 'Unable to create account for ' . $this->seamless_game_api_name;

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for ' . $this->seamless_game_api_name;
        }

        $result = [
            'success' => $success, 
            'message' => $message,
        ];

        return $result;
    }

    /* public function createPlayer($player_name, $player_id, $password, $email = null, $extra = null) {
        parent::createPlayer($player_name, $player_id, $password, $email, $extra);
        $this->startToken();
        $this->http_method = self::HTTP_METHOD_PUT;
        $this->api_name = self::API_createPlayer;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'player_name' => $player_name,
            'player_id' => $player_id,
            'game_username' => $game_username
        ];

        $gender = ['Male', 'Female'];
        shuffle($gender);

        $params = [
            'agentId' => $this->agent_id,
            'username' => $game_username,
            'firstName' => "fn{$game_username}", // fake firstName
            'lastName' => "ln{$game_username}", // fake lastName
            'email' => !empty($email) ? $email : "tot_{$game_username}@email.com", // fake email
            'gender' => $gender[0], // fake gender
            'birthdate' => '1996-01-01T23:00', // fake birthdate
            'walletId' => $game_username,
            'currency' => $this->currency,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $player_id = $this->getVariableFromContext($params, 'player_id');

        $result = [
            'response_result_id' => $response_result_id,
            'response_result' => null,
        ];

        if ($success) {
            $result['response_result'] = !empty($result_arr['result']) ? $result_arr['result'] : [];
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
        }

        $this->CI->utils->debug_log(__METHOD__, 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function isPlayerExist($player_name) {
        $this->startToken();
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_isPlayerExist;
        $player_id = $this->getPlayerIdFromUsername($player_name);
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'player_id' => $player_id,
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'login' => $game_username,
            'agentId' => $this->agent_id,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $player_id = $this->getVariableFromContext($params, 'player_id');

        $result = [
            'response_result_id' => $response_result_id,
            'response_result' => null,
        ];

        if ($success) {
            $result['response_result'] = !empty($result_arr['result']) ? $result_arr['result'] : [];
            $result['exists'] = true;
            $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
        } else {
            $success = true;
            $result['exists'] = false;
        }

        $this->CI->utils->debug_log(__METHOD__, 'result: ' . json_encode($result));

        return array($success, $result);
    } */

    public function logout($player_name, $password = null) {
        $this->startToken();
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_logout;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'id' => $game_username,
            'agentId' => $this->agent_id,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForLogout($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        
        $result = [
            'response_result_id' => $response_result_id,
            'response_result' => null,
        ];

        if ($success) {
            $result['response_result'] = !empty($result_arr['result']) ? $result_arr['result'] : [];
        }
        
        $this->CI->utils->debug_log(__METHOD__, 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function depositToGame($player_name, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $result = [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function withdrawFromGame($player_name, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $result = [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function queryTransaction($transaction_id, $extra) {
        return $this->returnUnimplemented();
    }

    public function startToken() {
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_startToken;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForStartToken',
        ];

        $params = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForStartToken($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $result = [
            'token' => null,
        ];

        if ($success) {
            $result['token'] = $this->token = isset($result_arr['token']) ? $result_arr['token'] : null;
        }

        return array($success, $result);
    }

    public function start() {
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_start;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForStart',
        ];

        $params = [];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForStart($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $result = [];

        if ($success) {
            $result = !empty($result_arr) ? $result_arr : [];
        }

        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra = []) {
        $this->startToken();

        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_queryGameListFromGameProvider;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        /*
            locale: String (Required) – Language/Locale. Obtain from "start" API for supported languages
            orderby: Order by value
            Alphabetic - ascending
            Game Rank - descending
            Release date - descending
            Manual order - descending
        */

        $params = [
            'locale' => isset($extra['locale']) ? $extra['locale'] : 'en-US',
            'orderby' => isset($extra['orderby']) ? $extra['orderby'] : 'Alphabetic',
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $result = [];

        if ($success) {
            $result = !empty($result_arr) ? $result_arr : [];
        }

        return array($success, $result);
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en-US',
            'zh_cn' => 'zh-CN',
            'id_id' => 'id-ID',
            'vi_vn' => 'vi-VN',
            'ko_kr' => 'ko-KR',
            'th_th' => 'th-TH',
            'hi_in' => 'hi-IN',
            'pt_pt' => 'pt-PT',
            'es_es' => 'es-ES',
            'kk_kz' => 'kk-KZ',
            'pt_br' => 'pt-BR',
            'ja_jp' => 'ja-JP',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->startToken();

        $player_id = $this->getPlayerIdFromUsername($player_name);
        $token = $this->getPlayerToken($player_id);
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        if (!empty($this->language)) {
            $language = $this->language;
        } else {
            $language = isset($extra['language']) ? $extra['language'] : null;
        }

        $language = $this->getLauncherLanguage($language);

        if ($this->force_language) {
            $language = $this->language;
        }

        if ($this->use_utils_get_url) {
            $this->home_url = $this->utils->getUrl();
        }
        
        if (!empty($this->home_url)) {
            $home_url = $this->home_url;
        } else {
            if (!empty($extra['home_link'])) {
                $home_url = $extra['home_link'];
            } elseif (!empty($extra['extra']['home_link'])) {
                $home_url = $extra['extra']['home_link'];
            } else {
                if ($is_mobile) {
                    $home_url = $this->getHomeLink(true);
                } else {
                    $home_url = $this->getHomeLink(false);
                }
            }
        }

        if (!empty($this->cashier_url)) {
            $cashier_url = $this->cashier_url;
        } else {
            if (!empty($extra['cashier_link'])) {
                $cashier_url = $extra['cashier_link'];
            } elseif (!empty($extra['extra']['cashier_link'])) {
                $cashier_url = $extra['extra']['cashier_link'];
            } else {
                $cashier_url = $home_url;
            }
        }

        if (!empty($this->logout_url)) {
            $logout_url = $this->logout_url;
        } else {
            $logout_url = $home_url;
        }

        if (!empty($this->failed_url)) {
            $failed_url = $this->failed_url;
        } else {
            $failed_url = $home_url;
        }

        $start_result = $this->start();

        // validate locale. Check if game provider supports player language.
        if (isset($start_result['casinos']['locales']) && !in_array($language, $start_result['casinos']['locales'])) {
            $language = 'en-US';
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_id' => $player_id,
            'game_username' => $game_username,
        ];

        $params = [
            'player' => [
                // 'playerId' => $game_username,
                'playerLogin' => $game_username,
                'agentId' => $this->agent_id,
                'playerCurrency' => $this->currency,
            ],
            'locale' => $language,
            'isDemo' => $is_demo_mode,
        ];

        if (!empty($game_code)) { // with game code
            $this->api_name = self::API_queryForwardGame;
            $params['externalToken'] = $token;
            $params['gameId'] = $game_code;
            $params['returnUrl'] = $home_url;
        } else { // lobby
            $this->api_name = self::API_queryForwardGameLobby;

            // locale and language is different. Language parameter is for lobby only
            // 4 Languages are supported: EN (English), CN (Simplified Chinese),TH (Thai), JA (Japaneses)
            switch ($language) {
                case 'en-US':
                    $language = 'en';
                    break;
                case 'zh-CN':
                    $language = 'cn';
                    break;
                case 'th-TH':
                    $language = 'th';
                    break;
                case 'ja-JP':
                    $language = 'ja';
                    break;
                default:
                    $language = 'en';
                    break;
            }

            $params['language'] = $language;
        }

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'params', $params);

        $this->http_method = self::HTTP_METHOD_POST;
        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $result = [
            'url' => null,
        ];

        if ($success) {
            $result['url'] = isset($result_arr['instantPlayUrl']) ? $result_arr['instantPlayUrl'] : null;
        }

        $result['success'] = $success;

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogsFromTrans($token = false) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $endDate->format('Y-m-d H:i:s');

        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_year_month_table_by_date(null, $queryDateTimeStart);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $queryDateTimeStart);
        }

        $transactions = $this->queryTransactionsForUpdate($this->original_seamless_wallet_transactions_table, $queryDateTimeStart, $queryDateTimeEnd, null, $this->use_bet_time);

        if ($this->enable_merging_rows) {
            $is_sum = true;
        } else {
            $is_sum = false;
        }

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                if (!empty($transaction['api_method'])) {
                    switch ($transaction['api_method']) {
                        case self::API_METHOD_PLACE_BET:
                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => $transaction['amount'],
                                'win_amount' => 0,
                                'result_amount' => -$transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode([]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction['external_unique_id']);
                            break;
                        case self::API_METHOD_SETTLEMENT:
                            list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::API_METHOD_PLACE_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], $transaction['reference_transaction_id'], $is_sum);

                            $extra_info['after_balance'] = $transaction['after_balance'];

                            $settlement_data = [
                                'status' => $transaction['status'],
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => $bet_transaction['amount'],
                                'win_amount' => $transaction['amount'],
                                'result_amount' => $transaction['amount'] - $bet_transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode($extra_info),
                            ];

                            if (!$this->enable_merging_rows) {
                                $settlement_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $settlement_data['bet_amount'] = 0;
                                $settlement_data['win_amount'] = $transaction['amount'];
                                $settlement_data['result_amount'] = $transaction['amount'];

                                $bet_data['bet_amount'] = $bet_transaction['amount'];
                                $bet_data['win_amount'] = 0;
                                $bet_data['result_amount'] = -$bet_transaction['amount'];
                            }

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $settlement_data, 'external_unique_id', $transaction['external_unique_id']);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($bet_table, $bet_data, 'external_unique_id', $bet_transaction['external_unique_id']);
                            break;
                        case self::API_METHOD_CANCEL_BET:
                            list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::API_METHOD_PLACE_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], $transaction['reference_transaction_id'], $is_sum);

                            $extra_info['after_balance'] = $transaction['after_balance'];

                            $rollback_data = [
                                'status' => $transaction['status'],
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => 0,
                                'win_amount' => 0,
                                'result_amount' => $bet_transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode($extra_info),
                            ];

                            if (!$this->enable_merging_rows) {
                                $rollback_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $rollback_data['bet_amount'] = 0;
                                $rollback_data['win_amount'] = 0;
                                $rollback_data['result_amount'] = $transaction['amount'];

                                $bet_data['bet_amount'] = $bet_transaction['amount'];
                                $bet_data['win_amount'] = 0;
                                $bet_data['result_amount'] = -$bet_transaction['amount'];
                            }

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollback_data, 'external_unique_id', $transaction['external_unique_id']);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($bet_table, $bet_data, 'external_unique_id', $bet_transaction['external_unique_id']);
                            break;
                        default:
                            break;
                    }
                }

                $this->utils->info_log(__METHOD__, $this->seamless_game_api_name, 'transaction_start_at', $transaction['start_at'], 'transaction_updated_at', $transaction['updated_at']);
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->utils->info_log(__METHOD__, $this->seamless_game_api_name, 'result', $result);

        return ['success' => true, $result];
    }

    public function queryTransactionsForUpdate($transaction_table, $dateFrom, $dateTo, $transaction_type = null, $use_bet_time = true) {
        $sqlTime = 'updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'start_at BETWEEN ? AND ?';
        }

        if (!empty($transaction_type)) {
            $and_transaction_type = 'AND transaction_type = ?';
        } else {
            $and_transaction_type = '';
        }

        $sql = <<<EOD
SELECT
id,
game_platform_id,
player_id,
api_method,
transaction_type,
transaction_id,
game_code,
round_id,
amount,
start_at,
end_at,
status,
request,
external_unique_id,
updated_at,
bet_amount,
win_amount,
result_amount,
before_balance,
after_balance,
extra_info,
reference_transaction_id,
rollback_transaction_id
FROM {$transaction_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND is_processed = ? AND transaction_type != '' AND {$sqlTime} {$and_transaction_type}
EOD;

        $params = [
            $this->getPlatformCode(),
            self::FLAG_NOT_UPDATED,
            self::IS_PROCESSED,
            $dateFrom,
            $dateTo
        ];

        if (!empty($transaction_type)) {
            array_push($params, $transaction_type);
        }

        if ($this->show_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($api_method, $player_id, $game_code, $round_id, $reference_transaction_id = null, $is_sum = true) {
        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $win_amount = $is_sum ? 'SUM(win_amount) as win_amount' : 'win_amount';
        $result_amount = $is_sum ? 'SUM(result_amount) as result_amount' : 'result_amount';

        $result = [];
        $from_table = null;

        foreach ($table_names as $table_name) {
            $from_table = $table_name;

            $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
{$amount},
status,
request,
external_unique_id,
{$bet_amount},
{$win_amount},
{$result_amount},
extra_info
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ? AND reference_transaction_id = ?
AND is_processed = ? AND transaction_type != ''
EOD;

            $params = [
                $this->getPlatformCode(),
                $api_method,
                $player_id,
                $game_code,
                $round_id,
                $reference_transaction_id,
                self::IS_PROCESSED,
            ];

            if ($this->show_logs) {
                $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
            }

            $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

            if (!empty($result['id'])) {
                break;
            }
        }

        return [$result, $from_table];
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;

        if ($this->use_transaction_data) {
            $this->syncOriginalGameLogsFromTrans($token);
        }

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabled_game_logs_unsettle
        );
    }

    /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime = "AND transaction.updated_at BETWEEN ? AND ?";

        if ($use_bet_time) {
            $sqlTime = "AND transaction.start_at BETWEEN ? AND ?";
        }

        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }

        $sql = <<<EOD
SELECT
transaction.id AS sync_index,
transaction.player_id,
transaction.game_username AS player_username,
transaction.before_balance,
transaction.after_balance,
transaction.game_code,
transaction.api_method,
transaction.transaction_type,
transaction.status,
transaction.response_result_id,
transaction.external_unique_id AS external_uniqueid,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id,
transaction.amount,
transaction.bet_amount,
transaction.bet_amount as real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.is_processed,
transaction.request,
transaction.response,
transaction.extra_info,
transaction.round_id,
transaction.reference_transaction_id,
transaction.rollback_transaction_id,
transaction.freegame,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ? AND transaction.transaction_type != ''
{$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            self::IS_PROCESSED,
            $dateFrom,
            $dateTo,
        ];

        if ($this->show_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS);
        }

        $data = [
            'game_info' => [
                'game_type_id' => !empty($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => !empty($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'game_type' => null,
                'game' => !empty($row['game']) ? $row['game'] : null,
            ],
            'player_info' => [
                'player_id' => !empty($row['player_id']) ? $row['player_id'] : null,
                'player_username' => !empty($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => !empty($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => !empty($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => !empty($row['after_balance']) ? $row['after_balance'] : 0,
            ],
            'date_info' => [
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : null,
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : null,
                'bet_at' => !empty($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at' => $this->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => isset($row['status']) ? $row['status'] : Game_logs::STATUS_PENDING,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => !empty($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => !empty($row['round_id']) ? $row['round_id'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            'bet_details' => $this->preprocessBetDetails($row),
            'extra' => [
                'note' => !empty($row['note']) ? $row['note'] : null,
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        //$this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'data', $data);

        return $data;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogsFromTrans(array &$row) {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if ($this->enable_merging_rows) {
            $get_original_request = $this->getOriginalRequest(self::API_METHOD_SETTLEMENT, $row['round_id']);
            $request = !empty($get_original_request) ? json_decode($get_original_request, true) : [];
        } else {
            $request = !empty($row['request']) ? json_decode($row['request'], true) : [];
        }

        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        // $row['after_balance'] = $this->getAfterBalance($row['player_id'], $row['game_code'], $row['bet_id'], $row['after_balance']);

        if (!empty($row['extra_info']) && !is_array($row['extra_info'])) {
            $row['extra_info'] = json_decode($row['extra_info'], true);
        }

        if ($this->enable_merging_rows) {
            $row['after_balance'] = isset($row['extra_info']['after_balance']) ? $row['extra_info']['after_balance'] : $row['after_balance'];
        }

        $row['note'] = $this->getResult($row['status'], $result_amount, $row);

        $row['extra'] = [
            'jackpot_wins' => [
                isset($request['jackpotamount']) ? $request['jackpotamount'] : 0,
            ],
            'progressive_contributions' => [
                isset($request['jackpotcontribution']) ? $request['jackpotcontribution'] : 0,
            ]
        ];
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_code = !empty($row['game_code']) ? $row['game_code'] : null;
        $game_type_id = !empty($row['game_type_id']) ? $row['game_type_id'] : $unknownGame->game_type_id;

        if (!empty($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
        } else {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $game_code, $game_code);
        }

        return [$game_description_id, $game_type_id];
    }

    public function getAfterBalance($player_id, $game_code, $round_id, $after_balance) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $where = [
            'player_id' => $player_id,
            'game_code' => $game_code,
            'round_id' => $round_id,
        ];

        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, $where);

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($transaction)) {
                    $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, $where);
                }
            }
        }

        return isset($transaction['after_balance']) ? $transaction['after_balance'] : $after_balance;
    }

    public function getResult($status, $result_amount, $row = []) {
        $result = 'Unknown';

        if ($status == Game_logs::STATUS_SETTLED) {
            if ($this->enable_merging_rows) {
                if ($result_amount > 0) {
                    $result = 'Win';
                } elseif ($result_amount < 0) {
                    $result = 'Lose';
                } elseif ($result_amount == 0) {
                    $result = 'Draw';
                } else {
                    $result = 'Free Game';
                }
            } else {
                $result = $row['transaction_type'];
            }

            if ($row['freegame']) {
                $result .= ' Free spin';
            }
        } elseif ($status == Game_logs::STATUS_PENDING) {
            $result = 'Pending';
        } elseif ($status == Game_logs::STATUS_ACCEPTED) {
            $result = 'Accepted';
        } elseif ($status == Game_logs::STATUS_REJECTED) {
            $result = 'Rejected';
        } elseif ($status == Game_logs::STATUS_CANCELLED) {
            $result = 'Cancelled';
        } elseif ($status == Game_logs::STATUS_VOID) {
            $result = 'Void';
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $result = 'Refund';
        } elseif ($status == Game_logs::STATUS_SETTLED_NO_PAYOUT) {
            $result = 'Settled no payout';
        } elseif ($status == Game_logs::STATUS_UNSETTLED) {
            $result = 'Unsettled';
        } else {
            $result = 'Unknown';
        }

        return $result;
    }

    public function queryTransactionByDateTime($startDate, $endDate) {
        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_year_month_table_by_date(null, $startDate);
        }

        if (empty($this->original_seamless_wallet_transactions_table)) {
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        if ($this->utils->getYearMonthByDate($startDate) == $this->utils->getThisYearMonth()) {
            $query_time = "AND transaction.updated_at BETWEEN ? AND ?";
        } else {
            $query_time = "AND transaction.created_at BETWEEN ? AND ?";
        }

        $md5_fields = implode(", ", ['transaction.amount', 'transaction.before_balance', 'transaction.after_balance', 'transaction.result_amount', 'transaction.updated_at']);

        $sql = <<<EOD
SELECT
transaction.player_id,
transaction.start_at AS transaction_date,
transaction.amount,
transaction.after_balance,
transaction.before_balance,
transaction.round_id AS round_no,
transaction.transaction_id,
transaction.external_unique_id AS external_uniqueid,
transaction.transaction_type AS trans_type,
transaction.updated_at,
MD5(CONCAT({$md5_fields})) AS md5_sum,
transaction.result_amount,
transaction.request
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' {$query_time}
ORDER BY transaction.updated_at ASC, transaction.id ASC;
EOD;

        $params = [
            $this->getPlatformCode(),
            self::IS_PROCESSED,
            $startDate,
            $endDate,
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions) {
        $temp_game_records = [];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $temp_game_record['md5_sum'] = $transaction['md5_sum'];

                if (empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])) {
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];
                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_DEBIT) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['game'])) {
            $bet_details['game_name'] = $row['game'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        if (isset($row['extra'])) {
            $bet_details['extra'] = $row['extra'];
        }

        if (!$this->enable_merging_rows) {
            if ($row['api_method'] == self::API_METHOD_PLACE_BET) {
                // get win amount
                list($settlement_transaction, $settlement_table) = $this->queryPlayerTransaction(self::API_METHOD_SETTLEMENT, $row['player_id'], $row['game_code'], $row['round_id'], $row['reference_transaction_id'], false);
                $bet_details['win_amount'] = $settlement_transaction['amount'];
            }

            if ($row['api_method'] == self::API_METHOD_SETTLEMENT) {
                // get bet amount
                list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::API_METHOD_PLACE_BET, $row['player_id'], $row['game_code'], $row['round_id'], $row['reference_transaction_id'], false);
                $bet_details['bet_amount'] = $bet_transaction['amount'];
            }

            if ($row['status'] == Game_logs::STATUS_REFUND) {
                // get bet amount
                list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::API_METHOD_PLACE_BET, $row['player_id'], $row['game_code'], $row['round_id'], $row['reference_transaction_id'], false);
                $bet_details['bet_amount'] = $bet_transaction['amount'];

                $bet_details['refund_amount'] = $row['amount'];

                unset($bet_details['win_amount']);
            }
        } else {
            list($rollback_transaction, $rollback_table) = $this->queryPlayerTransaction(self::API_METHOD_CANCEL_BET, $row['player_id'], $row['game_code'], $row['round_id'], $row['reference_transaction_id'], false);
            
            if (!empty($rollback_transaction)) {
                $bet_details['refund_amount'] = $rollback_transaction['amount'];
            }
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function isSupportsLobby(){
        return $this->is_support_lobby;
    }

    public function getGameTypeDemoLobbySupported(){
        return $this->game_type_demo_lobby_supported;
    }

    public function getGameTypeLobbySupported(){
        return $this->game_type_lobby_supported;
    }

    protected function isErrorCode($api_name, $params, $status_code, $error_code, $error) {
        if (in_array($status_code, [409, 404])) {
            return false;
        }

        return $error_code || intval($status_code) >= 400;
    }

    public function getOriginalRequest($api_method, $round_id) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $where = [
            'api_method' => $api_method,
            'round_id' => $round_id,
        ];

        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, $where);

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($transaction)) {
                    $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, $where);
                }
            }
        }

        return isset($transaction['request']) ? $transaction['request'] : [];
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }
        $STATUS_PENDING = Game_logs::STATUS_PENDING;
        $STATUS_ACCEPTED = Game_logs::STATUS_ACCEPTED;

        $sqlTime="rtg.created_at >= ? AND rtg.created_at <= ? AND rtg.transaction_type = ? AND rtg.status in ('{$STATUS_PENDING}', '{$STATUS_ACCEPTED}')";
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
rtg.round_id as round_id, 
rtg.transaction_id as transaction_id, 
rtg.created_at as transaction_date,
rtg.external_unique_id as external_uniqueid,
rtg.player_id,
rtg.transaction_type,
rtg.amount,
rtg.amount as deducted_amount,
0 as added_amount,
rtg.game_platform_id as game_platform_id,
gd.id as game_description_id,
gd.game_type_id

from {$original_transactions_table} as rtg
LEFT JOIN game_description as gd ON rtg.game_code = gd.external_game_id and gd.game_platform_id = ?
where
{$sqlTime}
EOD;

        $transaction_type = "debit";
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $transaction_type,

        ];
        $this->CI->utils->debug_log('==> rtg getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $external_uniqueid = $row['external_uniqueid'];
            $bet = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_unique_id'=> $external_uniqueid]);
            if($bet['status'] == Game_logs::STATUS_PENDING || $bet['status'] == Game_logs::STATUS_ACCEPTED){
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                // unset($row['row_count']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('rtg SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $original_transactions_table = $this->getSeamlessTransactionTable();
        $row = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_unique_id'=> $external_uniqueid]);
        if(!empty($row)){
            return array('success'=>true, 'status'=> $row['status']);
        }
        
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }
}
//end of class