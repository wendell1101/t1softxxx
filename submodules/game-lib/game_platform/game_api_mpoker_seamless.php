<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: Million Poker
 * Game Type: Poker
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2024 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -mpoker_seamless_service_api.php
 **/

class Game_api_mpoker_seamless extends Abstract_game_api {
    use Year_month_table_module;

    // default
    public $CI;
    public $http_method;
    public $api_url;
    public $language;
    public $currency;
    public $prefix_for_username;
    public $suffix_for_username;
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
    public $force_settle_game_logs;
    public $include_sync_original_game_logs;

    // conversions
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;

    // default tables
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $game_seamless_service_logs_table;

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

    // for t1 API
    public $launcher_mode;
    public $is_support_lobby;
    public $game_type_demo_lobby_supported;
    public $game_type_lobby_supported;
    public $game_image_directory;
    
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
        'valid_bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'extra_info',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS = [
        'bet_amount',
        'valid_bet_amount',
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
        'valid_bet_amount',
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
        'valid_bet_amount',
        'result_amount',
    ];

    // TRANSACTION TYPES
    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    // GAME API NAME
    public $seamless_game_api_name = 'MPOKER_SEAMLESS_GAME_API';

    const URI_MAP = [
        self::API_createPlayer => '/channelHandle',
        self::API_queryForwardGame => '/channelHandle',
        self::API_syncGameRecords => '/getRecordHandle',
    ];

    const SUBTYPE_OPERATION_MAP = [
        self::API_queryForwardGame => 0,
        self::API_syncGameRecords => 6,
    ];

    const LOBBY_CODE = 0;

    // API METHODS HERE
    const API_METHOD_WITHDRAW = 'withdraw';
    const API_METHOD_DEPOSIT = 'deposit';
    const API_METHOD_CANCEL = 'cancel';

    // additional
    public $agent;
    public $des_key;
    public $md5_key;
    public $line_code;
    public $return_type;
    public $sub_conversion;
    public $game_record_url;
    public $account_prefix;

    public function __construct() {
        parent::__construct();

        // default
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url', '');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->suffix_for_username = $this->getSystemInfo('suffix_for_username');
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
        $this->force_settle_game_logs = $this->getSystemInfo('force_settle_game_logs', false);
        $this->include_sync_original_game_logs = $this->getSystemInfo('include_sync_original_game_logs', false);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        // default tables
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'million_poker_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'million_poker_seamless_service_logs');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'million_poker_seamless_game_logs');

        $this->ymt_init();

        // for t1 api
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'lobbyAndSingle');
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', true);
        $this->game_type_demo_lobby_supported = $this->getSystemInfo('game_type_demo_lobby_supported', ['poker']);
        $this->game_type_lobby_supported = $this->getSystemInfo('game_type_lobby_supported', ['poker']);
        $this->game_image_directory = $this->getSystemInfo('game_image_directory', '/gamegatewayincludes/images/game-vendor-icon/Mpoker/');

        // additional
        $this->agent = $this->getSystemInfo('agent');
        $this->des_key = $this->getSystemInfo('des_key');
        $this->md5_key = $this->getSystemInfo('md5_key');
        $this->line_code = $this->getSystemInfo('line_code', $this->agent);
        $this->return_type = $this->getSystemInfo('return_type', 1);
        $this->sub_conversion = $this->getSystemInfo('sub_conversion', 100);
        $this->game_record_url = $this->getSystemInfo('game_record_url', '');
        $this->account_prefix = $this->getSystemInfo('account_prefix', $this->agent . '_');
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
        return MPOKER_SEAMLESS_GAME_API;
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
            $url .= "{$api_uri}" . '?' . http_build_query($params);

            if ($api_name == self::API_syncGameRecords) {
                $url = $this->game_record_url . "{$api_uri}" . '?' . http_build_query($params);
            }
        } else {
            $url .= $api_uri;
        }

        return $url;
    }

    public function processResultBoolean($response_result_id, $result_arr, $status_code, $player_name = null) {
        $success = false;

        if (in_array($status_code, [200, 201])) {
            $success = true;
        }

        if (isset($result_arr['d']['code']) && $result_arr['d']['code'] != self::RESPONSE_CODE_SUCCESS) {
            $success = false;
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

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en-us',
            'zh_cn' => 'zh-cn',
            'id_id' => 'ind',
            'vi_vn' => 'vie',
            'ko_kr' => 'ko',
            'th_th' => 'th',
            'hi_in' => 'hi',
            'pt_pt' => 'pt',
            'es_es' => 'es',
            'kk_kz' => 'kk',
            'pt_br' => 'pt-br',
            'ja_jp' => 'ja',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_queryForwardGame;

        $player_id = $this->getPlayerIdFromUsername($player_name);
        // $token = $this->getPlayerToken($player_id);
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);
        $language = !empty($this->language) ? $this->language : $this->getLauncherLanguage(isset($extra['language']) ? $extra['language'] : null);

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

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_id' => $player_id,
            'game_username' => $game_username,
            'home_url' => $home_url,
            'language' => $language,
        ];

        $timestamp = $this->utils->microtime_int();

        $request_params = [
            'agent' => $this->agent,
            'timestamp' => $timestamp,
            'key' => $this->generateSignature($this->agent, $timestamp),
        ];

        $params = [
            's' => self::SUBTYPE_OPERATION_MAP[self::API_queryForwardGame],
            'account' => $game_username,
            'money' => 0,
            'orderid' => $this->agent . date('YmdHis') . $game_username,
            'ip' => $this->utils->getIP(),
            'lineCode' => $this->line_code,
            'KindID' => !empty($game_code) ? $game_code : self::LOBBY_CODE,
        ];

        $request_params['param'] = $this->encrypt(http_build_query($params));

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'params', $params, 'request_params', $request_params);

        return $this->callApi($this->api_name, $request_params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $result_arr = $this->getResultJsonFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $home_url = $this->getVariableFromContext($params, 'home_url');
        $language = $this->getVariableFromContext($params, 'language');

        $additional_params = [
            'returnType' => $this->return_type,
            'returnUrl' => $home_url,
            'lang' => $language,
        ];

        $result = [
            'url' => null,
        ];

        if ($success) {
            $url = isset($result_arr['d']['url']) ? $result_arr['d']['url'] : null;
            $parsed_url = parse_url($url);
            parse_str($parsed_url['query'], $query_params);
            unset($query_params['lang']);
            $params = array_merge($query_params, $additional_params);
            $parsed_url['query'] = http_build_query($params);
            $unparsed_url = $this->utils->unparse_url($parsed_url);
            $result['url'] = !empty($unparsed_url) ? urldecode($unparsed_url) : null;

            if (!empty($query_params['account'])) {
                $this->updateExternalAccountIdForPlayer($player_id, $query_params['account']);
            }
        }

        $result['success'] = $success;

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token) {
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_syncGameRecords;

        $date_time_from = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $date_time_to = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $modified_start_date_time = $date_time_from->modify($this->getDatetimeAdjust());

        $date_time_from = new DateTime($this->serverTimeToGameTime($modified_start_date_time->format('Y-m-d H:i:s')));
        $date_time_to = new DateTime($date_time_to->format('Y-m-d H:i:s'));

        $start_date_time = $date_time_from->format('Y-m-d H:i:s');
        $end_date_time = $date_time_to->format('Y-m-d H:i:s');

        $result = [
            'success' => true,
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0,
            'sync_time_interval' => $this->sync_time_interval,
            'sleep_time' => $this->sleep_time
        ];

        while($start_date_time <= $end_date_time) {
            $modified_end_date_time = (new DateTime($start_date_time))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

            $timestamp = $this->utils->microtime_int();

            $request_params = [
                'agent' => $this->agent,
                'timestamp' => $timestamp,
                'key' => $this->generateSignature($this->agent, $timestamp),
            ];

            $params = [
                's' => self::SUBTYPE_OPERATION_MAP[$this->api_name],
                'startTime' => strtotime($start_date_time) * 1000,
                'endTime' => strtotime($modified_end_date_time) * 1000,
            ];

            $request_params['param'] = $this->encrypt(http_build_query($params));

            $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
                'start_date' => $start_date_time,
                'end_date' => $end_date_time,
                'request_params' => $request_params,
                'params' => $params,
            ];

            $gameRecords = $this->callApi($this->api_name, $request_params, $context);

            if ($gameRecords['success']) {
                $result['data_count'] += isset($gameRecords['data_count']) && !empty($gameRecords['data_count']) ? $gameRecords['data_count'] : 0;
                $result['data_count_insert'] += isset($gameRecords['data_count_insert']) && !empty($gameRecords['data_count_insert']) ? $gameRecords['data_count_insert']: 0;
                $result['data_count_update'] += isset($gameRecords['data_count_update']) && !empty($gameRecords['data_count_update']) ? $gameRecords['data_count_update'] : 0;
            } else {
                $result['data_count'] += 0;
                $result['data_count_insert'] += 0;
                $result['data_count_update'] += 0;
            }

            $this->CI->utils->debug_log('<--------------- processResultForSyncOriginalGameLogs --------------->', 'start_date_time: ' . $start_date_time, 'modified_end_date_time: ' . $modified_end_date_time);
            
            sleep($this->sleep_time);

            $start_date_time = (new DateTime($start_date_time))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return $result;
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $result_arr, $status_code);
        $request_params = $this->getVariableFromContext($params, 'request_params');
        $request_params['decrypted_params'] = $this->getVariableFromContext($params, 'params');

        $result = [
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        $extra = [
            'response_result_id' => $responseResultId,
            'request_params' => $request_params,
        ];

        if($success) {
            $game_records = !empty($result_arr['d']['list']) ? $result_arr['d']['list'] : [];

            if (!empty($game_records)) {
                $data = [];
                foreach ($game_records as $column => $records) {
                    foreach ($records as $key => $record) {
                        if (isset($data[$key])) {
                            $data[$key] = array_merge($data[$key], [$column => $record]);
                        } else {
                            $data[$key] = [$column => $record];
                        }
                    }
                }
                $game_records = $data;
            }

            if (!empty($game_records)) {
                $result['data_count'] = count($game_records);

                if (is_array($game_records)) {
                    foreach ($game_records as $game_record) {
                        $data = $this->rebuildGameRecords($game_record, $extra);
                        $external_unique_id = isset($data['external_unique_id']) ? $data['external_unique_id'] : null;
                        $md5_sum = isset($data['md5_sum']) ? $data['md5_sum'] : null;
                        $version_id = isset($data['version_id']) ? $data['version_id'] : null;

                        $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $version_id);
                        $get_record = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_game_logs_table, ['external_unique_id' => $external_unique_id]);

                        if (!empty($get_record)) {
                            if (isset($get_record['md5_sum']) && $get_record['md5_sum'] != $md5_sum) {
                                // update
                                $result['data_count_update'] += 1;
                                $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_game_logs_table, $data, 'external_unique_id', $external_unique_id);
                            }
                        } else {
                            // insert
                            $result['data_count_insert'] += 1;
                            $this->CI->original_seamless_wallet_transactions->insertTransactionData($this->original_seamless_game_logs_table, $data);
                        }
                    }
                }
            }
        }

        sleep($this->sleep_time);
        $this->CI->db->_reset_select();
        $this->CI->db->reconnect();
        $this->CI->db->initialize();

        return array($success, $result);
    }

    public function rebuildGameRecords($game_record, $extra) {
        $game_username = !empty($game_record['Accounts']) ? ltrim($game_record['Accounts'], $this->account_prefix) : null;
        $player_id = $this->getPlayerIdInGameProviderAuth($game_username);
        $language = $this->language;
        $currency = !empty($game_record['currency']) ? $game_record['currency'] : $this->currency;
        $transaction_id = !empty($game_record['GameID']) ? $game_record['GameID'] : null;
        $game_code = !empty($game_record['KindID']) ? $game_record['KindID'] : null;
        $round_id = !empty($game_record['GameID']) ? $game_record['GameID'] : null;
        $status = null;
        $external_unique_id = $transaction_id;

        $data = [
            'player_id' => $player_id,
            'game_username' => $game_username,
            'language' => $language,
            'currency' => $currency,
            'transaction_id' => $transaction_id,
            'game_code' => $game_code,
            'round_id' => $round_id,
            'status' => $status,
            'request' => isset($extra['request_params']) ? json_encode($extra['request_params']) : null,
            'response' => json_encode($game_record),
            'response_result_id' => isset($extra['response_result_id']) ? $extra['response_result_id'] : null,
            'external_unique_id' => $external_unique_id,
            'md5_sum' => md5(json_encode($game_record)),
        ];

        return $data;
    }

    public function updateFlagOfUpdatedResult($where, $flag) {

        $update_data = [
            'flag_of_updated_result' => $flag,
        ];

        $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);

        if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_updated) {
                $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->previous_table, $where, $update_data);
            }
        }

        return $is_updated;
    }

    public function updateTransactionRecord($where, $data) {
        $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $data);

        if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$is_updated) {
                $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->previous_table, $where, $data);
            }
        }

        return $is_updated;
    }

    public function isTransactionExistInGameLogs($where) {
        return $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_seamless_game_logs_table, $where);
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

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $request = !empty($transaction['request']) ? json_decode($transaction['request'], true) : [];
                $extra_info = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];

                if ($transaction['transaction_id']) {
                    $ogl_data = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_game_logs_table, ['external_unique_id' => $transaction['transaction_id']]);

                    if (!empty($ogl_data)) {
                        $response_data = !empty($ogl_data['response']) ? json_decode($ogl_data['response'], true) : [];
                        $bet_amount = !empty($response_data['AllBet']) ? $response_data['AllBet'] : 0;
                        $valid_bet_amount = !empty($response_data['CellScore']) ? $response_data['CellScore'] : 0;
                        $result_amount = !empty($response_data['Profit']) ? $response_data['Profit'] : 0;
                        $win_amount = $bet_amount + $result_amount;
                        $flag_of_updated_result = self::FLAG_UPDATED_FOR_GAME_LOGS;

                        $data = compact('bet_amount','valid_bet_amount','result_amount','win_amount', 'flag_of_updated_result');

                        $where = [
                            'api_method' => self::API_METHOD_DEPOSIT,
                            'transaction_id' => $transaction['transaction_id'],
                        ];

                        $this->updateTransactionRecord($where, $data);
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
        $this->CI->load->model(['original_game_logs_model']);

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
valid_bet_amount,
win_amount,
result_amount,
before_balance,
after_balance,
extra_info,
bet_transaction_id,
reference_transaction_id
FROM {$transaction_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND is_processed = ? AND api_method = ? AND transaction_type != ''
AND api_method NOT IN ('endRound', 'deposit-endRound') AND {$sqlTime} {$and_transaction_type}
EOD;

        $params = [
            $this->getPlatformCode(),
            self::FLAG_NOT_UPDATED,
            self::IS_PROCESSED,
            self::API_METHOD_DEPOSIT,
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

    public function queryTransactionsByRoundId($round_id, $player_id) {
        $this->CI->load->model(['original_game_logs_model']);

        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $result = [];
        $from_table = null;

        foreach ($table_names as $table_name) {
            $from_table = $table_name;

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
valid_bet_amount,
win_amount,
result_amount,
before_balance,
after_balance,
extra_info,
bet_transaction_id,
reference_transaction_id
FROM {$table_name}
WHERE game_platform_id = ? AND is_processed = ? AND transaction_type != '' AND api_method NOT IN ('endRound', 'deposit-endRound') AND round_id = ? AND player_id = ?
EOD;

            $params = [
                $this->getPlatformCode(),
                self::IS_PROCESSED,
                $round_id,
                $player_id,
            ];

            if ($this->show_logs) {
                $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
            }

            $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

            if (!empty($result['id'])) {
                break;
            }
        }

        return [$result, $from_table];
    }

    public function queryPlayerTransactionByTransactionId($api_method, $player_id, $game_code, $round_id, $transaction_id = null, $is_sum = true) {
        $this->CI->load->model(['original_game_logs_model']);

        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $and_transaction_id = !empty($transaction_id) ? "and transaction_id = ?" : '';
        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $valid_bet_amount = $is_sum ? 'SUM(valid_bet_amount) as valid_bet_amount' : 'valid_bet_amount';
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
transaction_id,
{$amount},
status,
request,
external_unique_id,
{$bet_amount},
{$valid_bet_amount},
{$win_amount},
{$result_amount},
extra_info,
api_method
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ?
AND is_processed = ? AND transaction_type != '' $and_transaction_id
EOD;

            $params = [
                $this->getPlatformCode(),
                $api_method,
                $player_id,
                $game_code,
                $round_id,
                self::IS_PROCESSED,
            ];

            if (!empty($transaction_id)) {
                array_push($params, $transaction_id);
            }

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

    public function queryPlayerTransactionsByRoundId($api_method, $player_id, $game_code, $round_id, $bet_transaction_id = null, $is_sum = true) {
        $this->CI->load->model(['original_game_logs_model']);

        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $and_bet_transaction_id = !empty($bet_transaction_id) ? "and bet_transaction_id = ?" : '';
        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $valid_bet_amount = $is_sum ? 'SUM(valid_bet_amount) as valid_bet_amount' : 'valid_bet_amount';
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
transaction_id,
{$amount},
status,
request,
external_unique_id,
{$bet_amount},
{$valid_bet_amount},
{$win_amount},
{$result_amount},
extra_info,
api_method,
bet_transaction_id
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ?
AND is_processed = ? AND transaction_type != '' $and_bet_transaction_id
EOD;

            $params = [
                $this->getPlatformCode(),
                $api_method,
                $player_id,
                $game_code,
                $round_id,
                self::IS_PROCESSED,
            ];

            if (!empty($bet_transaction_id)) {
                array_push($params, $bet_transaction_id);
            }

            if ($this->show_logs) {
                $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
            }

            $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

            if (!empty($result['id'])) {
                break;
            }
        }

        return [$result, $from_table];
    }

    public function queryPlayerTransactionByBetId($api_method, $player_id, $game_code, $round_id, $bet_transaction_id = null, $is_sum = true) {
        $this->CI->load->model(['original_game_logs_model']);

        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $and_bet_transaction_id = !empty($bet_transaction_id) ? "and bet_transaction_id = ?" : '';
        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $valid_bet_amount = $is_sum ? 'SUM(valid_bet_amount) as valid_bet_amount' : 'valid_bet_amount';
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
transaction_id,
{$amount},
status,
request,
external_unique_id,
{$bet_amount},
{$valid_bet_amount},
{$win_amount},
{$result_amount},
extra_info,
api_method
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ?
AND is_processed = ? AND transaction_type != '' $and_bet_transaction_id
EOD;

            $params = [
                $this->getPlatformCode(),
                $api_method,
                $player_id,
                $game_code,
                $round_id,
                self::IS_PROCESSED,
            ];

            if (!empty($bet_transaction_id)) {
                array_push($params, $bet_transaction_id);
            }

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

    public function queryPlayerTransactionByReferenceId($api_method, $player_id, $game_code, $round_id, $reference_transaction_id = null, $is_sum = true) {
        $this->CI->load->model(['original_game_logs_model']);

        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $valid_bet_amount = $is_sum ? 'SUM(valid_bet_amount) as valid_bet_amount' : 'valid_bet_amount';
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
transaction_id,
{$amount},
status,
request,
external_unique_id,
{$bet_amount},
{$valid_bet_amount},
{$win_amount},
{$result_amount},
extra_info,
api_method
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ? AND reference_transaction_id = ?
AND is_processed = ? AND transaction_type != '' AND api_method NOT IN ('endRound', 'deposit-endRound')
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

        if ($this->include_sync_original_game_logs) {
            $this->syncOriginalGameLogs($token);
        }

        if ($this->use_transaction_data) {
            $this->syncOriginalGameLogsFromTrans($token);
        }

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryTransactions'],
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
        $this->CI->load->model(['original_game_logs_model']);

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
transaction.valid_bet_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.is_processed,
transaction.request,
transaction.response,
transaction.extra_info,
transaction.round_id,
transaction.bet_transaction_id,
transaction.reference_transaction_id,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' AND api_method NOT IN ('endRound', 'deposit-endRound')
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

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $results;
    }

    public function getTransactionDetailsFromOriginalGameLogs($where) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $data = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_game_logs_table, $where);

        return $data;
    }

    public function rebuildTransactions($transactions) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $data = $round_data = [];

        foreach ($transactions as $key => $transaction) {
            $round_data = $transaction;

            if ($this->enable_merging_rows) {
                if ($transaction['transaction_id']) {
                    $where = [
                        'player_id' => $transaction['player_id'],
                        'transaction_id' => $transaction['transaction_id'],
                    ];

                    $round_transaction = $this->getTransactionDetailsFromOriginalGameLogs($where);

                    $response_data = !empty($round_transaction['response']) ? json_decode($round_transaction['response'], true) : [];
                    $round_data['round_id'] = $transaction['round_id'];
                    $round_data['game_code'] = $transaction['game_code'];
                    $round_data['after_balance'] = $transaction['after_balance'];
                    $round_data['bet_amount'] = !empty($response_data['AllBet']) ? $response_data['AllBet'] : 0;
                    $round_data['valid_bet_amount'] = !empty($response_data['CellScore']) ? $response_data['CellScore'] : 0;
                    $round_data['result_amount'] = !empty($response_data['Profit']) ? $response_data['Profit'] : 0;
                    $round_data['win_amount'] = $round_data['bet_amount'] + $round_data['result_amount'];
                    $round_data['status'] = Game_logs::STATUS_SETTLED;
                    $round_data['external_uniqueid'] = $transaction['reference_transaction_id'];
                    
                    $round_data['md5_sum'] = md5(json_encode($round_data));
                    $data[$transaction['round_id']] = $round_data;
                    unset($round_data);
                }
            } else {
                if ($transaction['api_method'] == self::API_METHOD_WITHDRAW) {
                    $round_data['bet_amount'] = $transaction['amount'];
                    $round_data['valid_bet_amount'] = $transaction['amount'];
                    $round_data['result_amount'] = -$transaction['amount'];
                }

                if ($transaction['api_method'] == self::API_METHOD_DEPOSIT) {
                    $round_data['win_amount'] = $transaction['amount'];
                    $round_data['result_amount'] = $transaction['amount'];
                    $this->updateStatus($transaction['player_id'], $transaction['round_id'], $transaction['status']);
                }

                if ($transaction['api_method'] == self::API_METHOD_CANCEL) {
                    $round_data['result_amount'] = $transaction['amount'];
                }

                $round_data['md5_sum'] = md5(json_encode($round_data));
                $data[$key] = $round_data;
                unset($round_data);
            }
        }

        $data = array_values($data);

        return $data;
    }

    public function rebuildTransactions_old($transactions) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $data = $round_data = [];

        foreach ($transactions as $key => $transaction) {
            $round_data = $transaction;

            if ($this->enable_merging_rows) {
                if ($transaction['round_id']) {
                    list($round_transactions, $table) = $this->queryTransactionsByRoundId($transaction['round_id'], $transaction['player_id']);
                    $round_data['round_id'] = $transaction['round_id'];
                    $round_data['game_code'] = $transaction['game_code'];
                    $round_data['after_balance'] = $transaction['after_balance'];
                    $round_data['bet_amount'] = 0;
                    $round_data['valid_bet_amount'] = 0;
                    $round_data['win_amount'] = 0;
                    $round_data['status'] = Game_logs::STATUS_PENDING;
                    $round_data['external_uniqueid'] = $transaction['reference_transaction_id'];
    
                    foreach ($round_transactions as $round) {
                        if ($round['api_method'] == self::API_METHOD_WITHDRAW) {
                            $round_data['bet_amount'] += $round['amount'];
                            $round_data['valid_bet_amount'] += $round['amount'];
                        }
    
                        if ($round['api_method'] == self::API_METHOD_DEPOSIT) {
                            $round_data['win_amount'] += $round['amount'];
                            $round_data['status'] = Game_logs::STATUS_SETTLED;
                        }

                        $round_data['result_amount'] = $round_data['win_amount'] - $round_data['bet_amount'];
    
                        if ($round['api_method'] == self::API_METHOD_CANCEL) {
                            $round_data['result_amount'] = $round['amount'];
                            $round_data['status'] = Game_logs::STATUS_REFUND;
                        }
                    }

                    $round_data['md5_sum'] = md5(json_encode($round_data));
                    $data[$transaction['round_id']] = $round_data;
                    unset($round_data);
                }
            } else {
                if ($transaction['api_method'] == self::API_METHOD_WITHDRAW) {
                    $round_data['bet_amount'] = $transaction['amount'];
                    $round_data['valid_bet_amount'] = $transaction['amount'];
                    $round_data['result_amount'] = -$transaction['amount'];
                }

                if ($transaction['api_method'] == self::API_METHOD_DEPOSIT) {
                    $round_data['win_amount'] = $transaction['amount'];
                    $round_data['result_amount'] = $transaction['amount'];
                    $this->updateStatus($transaction['player_id'], $transaction['round_id'], $transaction['status']);
                }

                if ($transaction['api_method'] == self::API_METHOD_CANCEL) {
                    $round_data['result_amount'] = $transaction['amount'];
                }

                $round_data['md5_sum'] = md5(json_encode($round_data));
                $data[$key] = $round_data;
                unset($round_data);
            }
        }

        return array_values($data);
    }

    public function updateStatus($player_id, $round_id, $status) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
    
            $where = [
                'player_id' => $player_id,
                'round_id' => $round_id,
            ];

            $update_data = [
                'status' => $status,
            ];

            $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $update_data);

            if ($this->use_monthly_transactions_table) {
                if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                    if (!$is_updated) {
                        $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->previous_table, $where, $update_data);
                    }
                }
            }
    
            return $is_updated;
    }

    public function queryTransactions($dateFrom, $dateTo, $use_bet_time) {
        $this->CI->load->model(['original_game_logs_model']);

        $sqlTime = "AND transaction.updated_at BETWEEN ? AND ?";

        if ($use_bet_time) {
            $sqlTime = "AND transaction.start_at BETWEEN ? AND ?";
        }

        $and_api_method = $this->enable_merging_rows ? "AND transaction.api_method = ?" : '';

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
transaction.valid_bet_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.is_processed,
transaction.request,
transaction.response,
transaction.extra_info,
transaction.round_id,
transaction.bet_transaction_id,
transaction.reference_transaction_id,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND transaction.flag_of_updated_result = ? AND transaction.transaction_type != '' AND api_method NOT IN ('endRound', 'deposit-endRound')
{$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::IS_PROCESSED,
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            $dateFrom,
            $dateTo,
        ];

       /*  if ($this->enable_merging_rows) {
            array_push($params, self::API_METHOD_DEPOSIT);
        } */

        if ($this->show_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        // $results = $this->rebuildTransactions($results);

        return $results;
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
                'bet_amount' => !empty($row['valid_bet_amount']) ? $row['valid_bet_amount'] : 0,
                'result_amount' => !empty($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => !empty($row['valid_bet_amount']) ? $row['valid_bet_amount'] : 0,
                'real_betting_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
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

        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        // $row['after_balance'] = $this->getAfterBalance($row['player_id'], $row['game_code'], $row['bet_id'], $row['after_balance']);

        if (!empty($row['extra_info']) && !is_array($row['extra_info'])) {
            $row['extra_info'] = json_decode($row['extra_info'], true);
        }

        if ($this->enable_merging_rows) {
            $row['after_balance'] = isset($row['extra_info']['after_balance']) ? $row['extra_info']['after_balance'] : $row['after_balance'];
        }

        $row['note'] = $this->getResult($row['status'], $result_amount, $row);
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
        $this->CI->load->model(['original_game_logs_model']);

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
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' AND api_method NOT IN ('endRound', 'deposit-endRound') {$query_time}
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

        if (isset($row['valid_bet_amount'])) {
            $bet_details['valid_bet_amount'] = $row['valid_bet_amount'];
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

        if (!$this->enable_merging_rows) {
            if ($row['api_method'] == self::API_METHOD_WITHDRAW) {
                // get win amount
                list($win_transaction, $win_table) = $this->queryPlayerTransactionByReferenceId(self::API_METHOD_DEPOSIT, $row['player_id'], $row['game_code'], $row['round_id'], $row['bet_transaction_id'], false);
                $bet_details['win_amount'] = !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0;
            }

            if (in_array($row['api_method'], [self::API_METHOD_DEPOSIT])) {
                // get bet amount
                list($bet_transaction, $bet_table) = $this->queryPlayerTransactionByBetId(self::API_METHOD_WITHDRAW, $row['player_id'], $row['game_code'], $row['round_id'], $row['reference_transaction_id'], false);
                $bet_details['bet_amount'] = !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                $bet_details['valid_bet_amount'] = !empty($bet_transaction['valid_bet_amount']) ? $bet_transaction['valid_bet_amount'] : 0;
            }

            if ($row['status'] == Game_logs::STATUS_REFUND) {
                $bet_details['refund_amount'] = $bet_details['bet_amount'] = $bet_details['valid_bet_amount'] = $row['amount'];
                unset($bet_details['win_amount']);
            }
        } else {
            list($rollback_transaction, $rollback_table) = $this->queryPlayerTransactionByReferenceId(self::API_METHOD_CANCEL, $row['player_id'], $row['game_code'], $row['round_id'], $row['reference_transaction_id'], true);

            if (!empty($rollback_transaction['player_id'])) {
                $bet_details['refund_amount'] = !empty($rollback_transaction['amount']) ? $rollback_transaction['amount'] : 0;
            }
        }

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

    public function encrypt($data, $key = null) {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        if (empty($key)) {
            $key = $this->des_key;
        }

        $encrypt = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return base64_encode($encrypt);
    }

    public function decrypt($data, $key = null) {
        $encrypted = base64_decode($data);

        if (empty($key)) {
            $key = $this->des_key;
        }

        return openssl_decrypt($encrypted, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    public function generateSignature($param, $timestamp, $key = null) {
        if (is_array($param)) {
            $param = json_encode($param);
        }

        if (empty($key)) {
            $key = $this->md5_key;
        }

        return md5($param.$timestamp.$key);
    }
}
//end of class