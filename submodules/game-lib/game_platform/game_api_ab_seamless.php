<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: OneTouch
 * Game Type: Slots, Live Dealer
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2024 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -ab_seamless_service_api.php
 **/

class Game_api_ab_seamless extends Abstract_game_api {
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

    const RESPONSE_CODE_SUCCESS = 'OK';

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
    public $seamless_game_api_name = 'AB_SEAMLESS_GAME_API';

    const URI_MAP = [
        self::API_createPlayer => '/CheckOrCreate',
        self::API_queryForwardGame => '/Login',
        self::API_queryForwardGameDemo => '/LoginTrial',
        self::API_queryGameListFromGameProvider => '/GetGameTables',
    ];

    // API METHODS HERE
    const TRANSFER_TYPE_METHOD_BET = 'bet';
    const TRANSFER_TYPE_METHOD_SETTLE = 'settle';
    const TRANSFER_TYPE_METHOD_MANUAL_SETTLE = 'manualSettle';
    const TRANSFER_TYPE_METHOD_TRANSFER_IN = 'transferIn';
    const TRANSFER_TYPE_METHOD_TRANSFER_OUT = 'transferOut';
    const TRANSFER_TYPE_METHOD_EVENT_SETTLE = 'eventSettle';
    const API_METHOD_CANCEL_TRANSFER = 'CancelTransfer';

    // additional
    public $agent;
    public $operator_id;
    public $allbet_key;
    public $partner_key;
    public $target_url;
    public $game_hall;
    public $add_event_transaction_to_game_logs;

    const OPERATOR_GENERIC = '/operator/generic';

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
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->force_settle_game_logs = $this->getSystemInfo('force_settle_game_logs', false);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        // default tables
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'ab_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'ab_seamless_service_logs');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'ab_seamless_game_logs');

        $this->ymt_init();

        // for t1 api
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'lobbyAndSingle');
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', true);
        $this->game_type_demo_lobby_supported = $this->getSystemInfo('game_type_demo_lobby_supported', ['live_dealer']);
        $this->game_type_lobby_supported = $this->getSystemInfo('game_type_lobby_supported', ['live_dealer']);
        $this->game_image_directory = $this->getSystemInfo('game_image_directory', '/gamegatewayincludes/images/game-vendor-icon/allbet/');

        // additional
        $this->agent = $this->getSystemInfo('agent');
        $this->operator_id = $this->getSystemInfo('operator_id');
        $this->allbet_key = $this->getSystemInfo('allbet_key');
        $this->partner_key = $this->getSystemInfo('partner_key');
        $this->target_url = $this->getSystemInfo('target_url');
        $this->game_hall = $this->getSystemInfo('game_hall');
        $this->add_event_transaction_to_game_logs = $this->getSystemInfo('add_event_transaction_to_game_logs', false);
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
        return AB_SEAMLESS_GAME_API;
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
        $path = self::URI_MAP[$this->api_name];
        $date = date('D, d M Y H:m:s T', strtotime($this->utils->getNowForMysql()));

        if (!empty($params)) {
            $request_params_string = json_encode($params);
            $content_md5 =  base64_encode(md5($request_params_string, true));
            $content_type = 'application/json';
        } else {
            $content_md5 =  '';
            $content_type = '';
        }

        $string_to_sign = $this->http_method . "\n"
        . $content_md5 . "\n"
        . $content_type . "\n"
        . $date . "\n"
        . $path;

        $headers = [
            'Content-Type' => $content_type,
            'Accept' => 'application/json',
            'Authorization' => $this->generateAuthorization($string_to_sign),
            'Date' => $date,
            'Content-MD5' => $content_md5,
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

        if (isset($result_arr['resultCode']) && $result_arr['resultCode'] != self::RESPONSE_CODE_SUCCESS) {
            $success = false;
        }

        if (!$success) {
            $this->setResponseResultToError($response_result_id);
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name . ' API got error ', $response_result_id, 'status_code', $status_code, 'player_name', $player_name, 'result', $result_arr);
        }

        return $success;
    }

    /* public function createPlayer($player_name, $player_id, $password, $email = null, $extra = null) {
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
    } */

    public function createPlayer($player_name, $player_id, $password, $email = null, $extra = null) {
        parent::createPlayer($player_name, $player_id, $password, $email, $extra);
        $this->http_method = self::HTTP_METHOD_POST;
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
            'agent' => $this->agent,
            'player' => $game_username . $this->suffix_for_username,
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
            'en_us' => 'en',
            'zh_cn' => 'zh_CN',
            'id_id' => 'id',
            'vi_vn' => 'vi',
            'ko_kr' => 'ko',
            'th_th' => 'th',
            'hi_in' => 'hi',
            'pt_pt' => 'pt',
            'es_es' => 'es',
            'kk_kz' => 'kk',
            'pt_br' => 'pt_br',
            'ja_jp' => 'ja',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->http_method = self::HTTP_METHOD_POST;

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
        ];

        $params = [
            'language' => $language,
            'returnUrl' => $home_url,
        ];

        $force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        if($force_disable_home_link){
            unset($params['returnUrl']);
        }

        if (!$is_demo_mode) {
            $this->api_name = self::API_queryForwardGame;
            $params['player'] = $game_username . $this->suffix_for_username;

            if (!empty($game_code)) {
                $params['tableName'] = $game_code;
            }
    
            if (!empty($this->target_url)) {
                $params['targetUrl'] = $this->target_url;
            }
    
            if (!empty($this->game_hall)) {
                $params['gameHall'] = $this->game_hall;
            }
        } else {
            $this->api_name = self::API_queryForwardGameDemo;
        }

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'params', $params);

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
            $result['url'] = isset($result_arr['data']['gameLoginUrl']) ? $result_arr['data']['gameLoginUrl'] : null;
        }

        $result['success'] = $success;

        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_queryGameListFromGameProvider;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        $params = [
            'agent' => isset($extra['agent']) ? $extra['agent'] : $this->agent,
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
                $request = !empty($transaction['request']) ? json_decode($transaction['request'], true) : [];
                $details = !empty($request['details'][0]) ? $request['details'][0] : [];
                $extra_info = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];

                if (!empty($transaction['api_method'])) {
                    switch ($transaction['api_method']) {
                        case self::TRANSFER_TYPE_METHOD_BET:
                            $bet_amount = isset($details['betAmount']) ? $details['betAmount'] : 0;
                            $extra_info['bet_amount'] = $bet_amount;

                            $bet_data = [
                                'status' => $this->force_settle_game_logs ? Game_logs::STATUS_SETTLED : $transaction['status'],
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                                'extra_info' => json_encode($extra_info),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction['external_unique_id']);
                            break;
                        case self::TRANSFER_TYPE_METHOD_SETTLE:
                        case self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE:
                            list($bet_transactions, $bet_table) = $this->queryPlayerTransactionsByRoundId(self::TRANSFER_TYPE_METHOD_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], $transaction['bet_transaction_id'], false);
                            $bet_amount = isset($details['betAmount']) ? $details['betAmount'] : 0;
                            $valid_bet_amount = isset($details['validAmount']) ? $details['validAmount'] : 0;
                            $win_amount = 0;
                            $result_amount = isset($details['winOrLossAmount']) ? $details['winOrLossAmount'] : 0;
                            $extra_info['after_balance'] = $transaction['after_balance'];
                            $extra_info['bet_amount'] = $bet_amount;
                            $extra_info['valid_bet_amount'] = $valid_bet_amount;
                            $extra_info['result_amount'] = $result_amount;
                            $bet_count = count($bet_transactions);

                            if ($result_amount > 0) {
                                $win_amount = $result_amount;
                            }

                            if ($this->enable_merging_rows) {
                                $win_data = [
                                    'status' => $transaction['status'],
                                    'bet_amount' => $bet_amount,
                                    'win_amount' => $win_amount,
                                    'result_amount' => $result_amount,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                    'extra_info' => json_encode($extra_info),
                                ];

                                /* foreach ($bet_transactions as $key => $bet_transaction) {
                                    $bet_data['result_amount'] = $transaction['amount'] - $bet_transaction['amount'];

                                    if ($bet_count > 1) {
                                        $bet_data['bet_amount'] = $bet_transaction['amount'];

                                        if ($key == 0) {
                                            $bet_data['result_amount'] = $transaction['amount'] - $bet_transaction['amount'];
                                        } else {
                                            $bet_data['result_amount'] = -$bet_transaction['amount'];
                                        }
                                    }

                                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($bet_table, "(external_unique_id='{$bet_transaction['external_unique_id']}' AND status!='7')", $bet_data);
                                    $bet_data['win_amount'] = 0;
                                } */
                            } else {
                                $extra_info['after_balance'] = $transaction['after_balance'];
                                $extra_info['bet_amount'] = 0;
                                $extra_info['valid_bet_amount'] = 0;
                                $extra_info['win_amount'] = $transaction['amount'];
                                $extra_info['result_amount'] = $transaction['amount'];

                                $win_data = [
                                    'status' => $transaction['status'],
                                    'bet_amount' => 0,
                                    'win_amount' => $transaction['amount'],
                                    'result_amount' => $transaction['amount'],
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                    'extra_info' => json_encode($extra_info),
                                ];

                                $bet_data = [
                                    'status' => $transaction['status'],
                                    'win_amount' => 0,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                ];

                                foreach ($bet_transactions as $bet_transaction) {
                                    $extra_info['after_balance'] = $bet_transaction['after_balance'];
                                    $extra_info['bet_amount'] = $bet_transaction['amount'];
                                    $extra_info['valid_bet_amount'] = $bet_transaction['amount'];
                                    $extra_info['win_amount'] = 0;
                                    $extra_info['result_amount'] = -$bet_transaction['amount'];

                                    $bet_data['bet_amount'] = $bet_transaction['amount'];
                                    $bet_data['result_amount'] = -$bet_transaction['amount'];

                                    /* if ($bet_count > 1) {
                                        $bet_data['bet_amount'] = $bet_transaction['amount'];
                                        $bet_data['result_amount'] = -$bet_transaction['amount'];
                                    } */

                                    $bet_data['extra_info'] = json_encode($extra_info);
                                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($bet_table, "(external_unique_id='{$bet_transaction['external_unique_id']}' AND status!='7')", $bet_data);
                                }
                            }

                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction['external_unique_id']);
                            break;
                        case self::TRANSFER_TYPE_METHOD_EVENT_SETTLE:
                            $win_data = [
                                'status' => $transaction['status'],
                                'flag_of_updated_result' => $this->add_event_transaction_to_game_logs ? self::FLAG_UPDATED_FOR_GAME_LOGS : self::FLAG_UPDATED,
                                'bet_amount' => 0,
                                'win_amount' => $transaction['amount'],
                                'result_amount' => $transaction['amount'],
                            ];

                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction['external_unique_id']);
                            break;
                        case self::API_METHOD_CANCEL_TRANSFER:
                            list($bet_transaction, $bet_table) = $this->queryPlayerTransactionByTransactionId(self::TRANSFER_TYPE_METHOD_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], $transaction['rollback_transaction_id'], $is_sum);

                            if (!empty($bet_transaction)) {
                                $bet_amount = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                                $extra_info['after_balance'] = $transaction['after_balance'];
    
                                $rollback_data = [
                                    'status' => $transaction['status'],
                                    'flag_of_updated_result' => self::FLAG_UPDATED,
                                ];
    
                                $bet_data = [
                                    'status' => $transaction['status'],
                                    'bet_amount' => 0,
                                    'win_amount' => 0,
                                    'result_amount' => $bet_amount,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                    'extra_info' => json_encode($extra_info),
                                ];
    
                                if (!$this->enable_merging_rows) {
                                    $rollback_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                    $rollback_data['bet_amount'] = 0;
                                    $rollback_data['win_amount'] = 0;
                                    $rollback_data['result_amount'] = $transaction['amount'];

                                    $bet_data['bet_amount'] = $bet_amount;
                                    $bet_data['win_amount'] = 0;
                                    $bet_data['result_amount'] = -$bet_amount;
                                }

                                $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                                $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollback_data, 'external_unique_id', $transaction['external_unique_id']);
                                $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($bet_table, $bet_data, 'transaction_id', $bet_transaction['transaction_id']);
                            }
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
win_amount,
result_amount,
before_balance,
after_balance,
extra_info,
bet_transaction_id,
reference_transaction_id,
rollback_transaction_id
FROM {$transaction_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND is_processed = ? AND transaction_type != '' AND api_method NOT IN ('endRound') AND {$sqlTime} {$and_transaction_type}
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
after_balance,
{$bet_amount},
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

    public function queryPlayerTransactionBetId($api_method, $player_id, $game_code, $round_id, $bet_transaction_id = null, $is_sum = true) {
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
{$win_amount},
{$result_amount},
extra_info,
api_method
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ? AND reference_transaction_id = ?
AND is_processed = ? AND transaction_type != '' AND api_method NOT IN ('endRound')
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

    public function queryPlayerTransactionByRollbackId($api_method, $player_id, $game_code, $round_id, $rollback_transaction_id = null, $is_sum = true) {
        $this->CI->load->model(['original_game_logs_model']);

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
transaction_id,
{$amount},
status,
request,
external_unique_id,
{$bet_amount},
{$win_amount},
{$result_amount},
extra_info,
api_method
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ? AND rollback_transaction_id = ?
AND is_processed = ? AND transaction_type != '' AND api_method NOT IN ('endRound')
EOD;

            $params = [
                $this->getPlatformCode(),
                $api_method,
                $player_id,
                $game_code,
                $round_id,
                $rollback_transaction_id,
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
IF(transaction.api_method = 'bet', transaction.amount, 0) AS bet_amount,
transaction.bet_amount AS valid_bet_amount,
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
transaction.rollback_transaction_id,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' AND api_method NOT IN ('endRound')
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

        /* if ($this->enable_merging_rows) {
            if (isset($row['extra_info']['bet_amount'])) {
                $row['bet_amount'] = $row['extra_info']['bet_amount'];
            }
    
            if (isset($row['extra_info']['valid_bet_amount'])) {
                $row['valid_bet_amount'] = $row['extra_info']['valid_bet_amount'];
            }
    
            if (isset($row['extra_info']['result_amount'])) {
                $row['result_amount'] = $row['extra_info']['result_amount'];
            }
        } else {
            if ($row['api_method'] == self::TRANSFER_TYPE_METHOD_BET) {
                if (isset($row['extra_info']['bet_amount'])) {
                    $row['bet_amount'] = $row['extra_info']['bet_amount'];
                }
        
                if (isset($row['extra_info']['valid_bet_amount'])) {
                    $row['valid_bet_amount'] = $row['extra_info']['valid_bet_amount'];
                }
            }

            if (in_array($row['api_method'], [self::TRANSFER_TYPE_METHOD_SETTLE, self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE])) {
                if (isset($row['extra_info']['result_amount'])) {
                    $row['result_amount'] = $row['extra_info']['result_amount'];
                }
            }
        } */

        if (isset($row['extra_info']['bet_amount'])) {
            $row['bet_amount'] = $row['extra_info']['bet_amount'];
        }

        if (isset($row['extra_info']['valid_bet_amount'])) {
            $row['valid_bet_amount'] = $row['extra_info']['valid_bet_amount'];
        }

        if (isset($row['extra_info']['result_amount'])) {
            $row['result_amount'] = $row['extra_info']['result_amount'];
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

            if ($row['api_method'] == self::TRANSFER_TYPE_METHOD_EVENT_SETTLE) {
                $result .= ' Event';
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
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' AND api_method NOT IN ('endRound') {$query_time}
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

        if (isset($row['extra_info']['bet_amount'])) {
            $bet_details['bet_amount'] = $row['extra_info']['bet_amount'];
        }

        if (isset($row['extra_info']['valid_bet_amount'])) {
            $bet_details['valid_bet_amount'] = $row['extra_info']['valid_bet_amount'];
        }

        if (isset($row['extra_info']['win_amount'])) {
            $bet_details['win_amount'] = $row['extra_info']['win_amount'];
        }

        if (isset($row['extra_info']['result_amount'])) {
            $bet_details['result_amount'] = $row['extra_info']['result_amount'];
        }

        if (isset($row['extra_info']['refund_amount'])) {
            $bet_details['refund_amount'] = $row['extra_info']['refund_amount'];
        }

        /* if (!$this->enable_merging_rows) {
            if ($row['api_method'] == self::TRANSFER_TYPE_METHOD_BET) {
                // get win amount
                list($win_transaction, $win_table) = $this->queryPlayerTransactionByReferenceId(self::TRANSFER_TYPE_METHOD_SETTLE, $row['player_id'], $row['game_code'], $row['round_id'], $row['bet_transaction_id'], false);
                $request = !empty($win_transaction['request']) ? json_decode($win_transaction['request'], true) : [];
                $details = !empty($request['details'][0]) ? $request['details'][0] : [];
                $bet_amount = isset($details['betAmount']) ? $details['betAmount'] : 0;
                $valid_bet_amount = isset($details['validAmount']) ? $details['validAmount'] : 0;
                $result_amount = isset($details['winOrLossAmount']) ? $details['winOrLossAmount'] : 0;
                $bet_details['win_amount'] = $result_amount > 0 ? $result_amount : 0;
            }

            if (in_array($row['api_method'], [self::TRANSFER_TYPE_METHOD_SETTLE, self::TRANSFER_TYPE_METHOD_MANUAL_SETTLE, self::TRANSFER_TYPE_METHOD_EVENT_SETTLE])) {
                $bet_details['bet_amount'] = $row['extra_info']['bet_amount'];
                $bet_details['valid_bet_amount'] = $row['extra_info']['valid_bet_amount'];
            }

            if ($row['status'] == Game_logs::STATUS_REFUND) {
                $bet_details['refund_amount'] = $bet_details['bet_amount'] = $row['amount'];
                unset($bet_details['win_amount']);
            }
        } else {
            list($rollback_transaction, $rollback_table) = $this->queryPlayerTransactionByRollbackId(self::API_METHOD_CANCEL_TRANSFER, $row['player_id'], $row['game_code'], $row['round_id'], $row['transaction_id'], true);
            
            if (!empty($rollback_transaction['external_unique_id'])) {
                $bet_details['refund_amount'] = !empty($rollback_transaction['amount']) ? $rollback_transaction['amount'] : 0;
            }
        } */

        if ($row['api_method'] == self::TRANSFER_TYPE_METHOD_EVENT_SETTLE) {
            $bet_details = [];
            $request = !empty($row['request']) ? json_decode($row['request'], true) : [];
            $details = !empty($request['details'][0]) ? $request['details'][0] : [];

            if (isset($details['eventRecordNum'])) {
                $bet_details['event_id'] = $details['eventRecordNum'];
            }

            if (isset($details['eventCode'])) {
                $bet_details['event_name'] = $details['eventCode'];
            }

            if (isset($details['amount'])) {
                $bet_details['win_amount'] = $details['amount'];
            }

            if (isset($details['settleTime'])) {
                $bet_details['event_datetime'] = $details['settleTime'];
            }

            if (isset($details['settleTime'])) {
                $bet_details['settlement_datetime'] = $details['settleTime'];
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

    public function generateAuthorization($data) {
        $deKey = base64_decode($this->allbet_key);
        $hash_hmac = hash_hmac("sha1", $data, $deKey, true);
        $encrypted = base64_encode($hash_hmac);
        $authorization = "AB" . " " . $this->operator_id . ":" . $encrypted;

        return $authorization;
    }

    public function getValidBetAmount($player_id, $game_code, $round_id, $reference_transaction_id) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $where = "api_method IN ('settle', 'manualSettle') AND (player_id='{$player_id}' AND game_code='{$game_code}' AND round_id='{$round_id}') AND reference_transaction_id='{$reference_transaction_id}'";
        $order_by = ['field_name' => 'version', 'is_desc' => true];

        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, $where, [], $order_by);

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($transaction)) {
                    $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, $where);
                }
            }
        }

        $request = !empty($transaction['request']) ? json_decode($transaction['request'], true) : [];
        $details = !empty($request['details'][0]) ? $request['details'][0] : [];
        $valid_bet_amount = !empty($details['validAmount']) ? $details['validAmount'] : 0;

        return $valid_bet_amount;
    }
}
//end of class