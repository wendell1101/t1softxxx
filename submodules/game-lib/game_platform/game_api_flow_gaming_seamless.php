<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: Flow Gaming
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2024 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -flow_gaming_seamless_service_api.php
 **/

class Game_api_flow_gaming_seamless extends Abstract_game_api {
    use Year_month_table_module;

    // default
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
    public $CI;
    public $api_name;
    public $token_timeout_seconds;
    public $force_refresh_token_timeout;
    public $get_token_test_accounts;

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

    public $seamless_game_api_name = 'FLOW_GAMING_SEAMLESS_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';

    const RESPONSE_CODE_SUCCESS = 0;

    const IS_PROCESSED = 1;

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const URI_MAP = [
        self::API_generateToken => '/oauth/token',
        self::API_queryForwardGame => '/v1/launcher/item',
        self::API_queryBetDetailLink => '/v1/launcher/tx',
        self::API_syncGameRecords => '/v1/feed/transaction',
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'wallet_code',
        'external_ref',
        'category',
        'balance_type',
        'type',
        'balance',
        'amount',
        'pool_amount',
        'round_id',
        'ext_item_id',
        'item_id',
        'vendor',
        'ext_w_tx_id',
        'tx_round_id',
        'context',
        'transaction_id',
        'parent_transaction_id',
        'account_id',
        'account_ext_ref',
        'application_id',
        'currency_unit',
        'transaction_time',
        'created_by',
        'created',
        'session',
        'ip',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'balance',
        'amount',
        'pool_amount',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'end_at',
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

    // additional
    public $access_token;
    public $company_id;
    public $auth_username;
    public $auth_secret;
    public $api_username;
    public $api_password;
    public $lobby_url;
    public $banking_url;
    public $logout_url;
    public $failed_url;
    public $utc;
    public $country;

    # TRANSACTION TYPES
    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    # API METHODS HERE
    const CATEGORY_WAGER = 'wager';
    const CATEGORY_PAYOUT = 'payout';
    const CATEGORY_REFUND = 'refund';

    const GRANT_TYPE = 'password';

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);

        // default
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url');
        $this->language = $this->getSystemInfo('language');
        $this->force_language = $this->getSystemInfo('force_language', false);
        $this->currency = $this->getSystemInfo('currency');
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
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        // default tables
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'flow_gaming_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'flow_gaming_seamless_service_logs');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'flow_gaming_seamless_game_logs');

        $this->ymt_init();

        // additional
        $this->company_id = $this->getSystemInfo('company_id');
        $this->auth_username = $this->getSystemInfo('auth_username');
        $this->auth_secret = $this->getSystemInfo('auth_secret');
        $this->api_username = $this->getSystemInfo('api_username');
        $this->api_password = $this->getSystemInfo('api_password');
        $this->lobby_url = $this->getSystemInfo('lobby_url', $this->utils->getUrl());
        $this->banking_url = $this->getSystemInfo('banking_url', $this->lobby_url);
        $this->logout_url = $this->getSystemInfo('logout_url', $this->lobby_url);
        $this->failed_url = $this->getSystemInfo('failed_url', $this->lobby_url);
        $this->utc = $this->getSystemInfo('utc', 'UTC+8');
        $this->country = $this->getSystemInfo('country');
    }

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', true);
        $this->use_monthly_game_logs_table = $this->getSystemInfo('use_monthly_game_logs_table', true);

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
        return FLOW_GAMING_SEAMLESS_API;
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

    public function generateToken() {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_generateToken;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultGenerateToken',
        ];

        $params = [
            'grant_type' => self::GRANT_TYPE,
            'username' => $this->api_username,
            'password' => $this->api_password,
        ];

        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultGenerateToken($params) {
        $status_code = $this->getStatusCodeFromParams($params);
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        if ($success) {
            $this->access_token = isset($result_arr['access_token']) ? $result_arr['access_token'] : null;
            $result['access_token'] = $this->access_token;
            $result['token_type'] = isset($result_arr['token_type']) ? $result_arr['token_type'] : null;
            $result['refresh_token'] = isset($result_arr['refresh_token']) ? $result_arr['refresh_token'] : null;
        }

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'result', $result);

        return array($success, $result);
    }

    public function getHttpHeaders($params) {
        $headers = [
            'X-DAS-TZ' => $this->utc,
            'X-DAS-LANG' => $this->language,
            'X-DAS-CURRENCY' => $this->currency,
            'X-DAS-TX-ID' => $this->utils->getGUIDv4(),
        ];

        switch ($this->api_name) {
            case self::API_generateToken:
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $headers['Authorization'] = 'Basic '. base64_encode($this->auth_username . ':' . $this->auth_secret);
                $headers['Accept'] = 'application/json; charset=UTF-8';
                break;
            case self::API_queryForwardGame:
            case self::API_queryBetDetailLink:
            case self::API_syncGameRecords:
                $headers['Content-Type'] = 'application/json';
                $headers['Authorization'] = 'Bearer '. $this->access_token;
                break;
            default:
                break;
        }

        $this->utils->debug_log($this->seamless_game_api_name, __METHOD__, 'api_name', $this->api_name, 'headers', $headers);

        return $headers;
    }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST || $this->http_method == self::HTTP_METHOD_PUT) {
            if ($this->http_method == self::HTTP_METHOD_PUT) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::HTTP_METHOD_PUT);
            } else {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            if ($this->api_name == self::API_generateToken) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryBetDetailLink:
                    $transaction_id = isset($params['transaction_id']) ? $params['transaction_id'] : null;
                    unset($params['transaction_id']);

                    $url .= '/' . ltrim($api_uri, '/') . "/{$transaction_id}?" . http_build_query($params);
                    break;
                case self::API_syncGameRecords:
                    $url .= '/' . ltrim($api_uri, '/') . '?' . http_build_query($params);
                    break;
                default:
                    $url .= '/' . ltrim($api_uri, '/') . '?' . http_build_query($params);
                    break;
            }
        } else {
            $url .= '/' . ltrim($api_uri, '/');
        }

        return $url;
    }

    public function processResultBoolean($response_result_id, $result_arr, $status_code, $player_name = null) {
        $success = false;

        if (($status_code == 200 || $status_code == 201) && !isset($result_arr['error'])) {
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
            'en_us' => 'en_US',
            'zh_cn' => 'zh_CN',
            'id_id' => 'in_ID',
            'vi_vn' => 'vi_VN',
            'ko_kr' => 'ko_KR',
            'th_th' => 'th_TH',
            'hi_in' => 'hi_IN',
            'pt_pt' => 'pt_PT',
            'es_es' => 'es_ES',
            'kk_kz' => 'kk_KZ',
            'pt_br' => 'pt_BR',
            'ja_jp' => 'ja_JP',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->generateToken();
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_queryForwardGame;
        $this->CI->load->model(['common_token']);

        $player_id = $this->getPlayerIdFromUsername($player_name);
        $token = $this->getPlayerToken($player_id);
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        $this->CI->common_token->updatePlayerToken($player_id, $token, $this->token_timeout_seconds);

        if (!empty($this->language)) {
            $language = $this->language;
        } else {
            if (isset($extra['language'])) {
                $language = $extra['language'];
            } else {
                $language = null;
            }
        }

        $language = $this->getLauncherLanguage($language);

        if ($this->force_language) {
            $language = $this->language;
        }

        if (!empty($this->lobby_url)) {
            $lobby_url = $this->lobby_url;
        } else {
            if (!empty($extra['home_link'])) {
                $lobby_url = $extra['home_link'];
            } elseif (!empty($extra['extra']['home_link'])) {
                $lobby_url = $extra['extra']['home_link'];
            } else {
                $lobby_url = null;
            }
        }

        if (!empty($this->banking_url)) {
            $banking_url = $this->banking_url;
        } else {
            if (!empty($extra['cashier_link'])) {
                $banking_url = $extra['cashier_link'];
            } elseif (!empty($extra['extra']['cashier_link'])) {
                $banking_url = $extra['extra']['cashier_link'];
            } else {
                $banking_url = null;
            }
        }

        if (!empty($this->logout_url)) {
            $logout_url = $this->logout_url;
        } else {
            $logout_url = null;
        }

        if (!empty($this->failed_url)) {
            $failed_url = $this->failed_url;
        } else {
            $failed_url = null;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_id' => $player_id,
            'game_username' => $game_username,
        ];

        $params = [
            'token' => $token,
        ];

        if ($is_demo_mode) {
            $params['token'] = null;
            $params['demo'] = true;
        }

        $params['external'] = true;
        $params['game_id'] = intval($game_code);

        $params['login_context'] = [
            'lang' => $language,
            'country_id' => $this->country,
        ];

        $params['conf_params'] = [
            'lobby_url' => $lobby_url,
            'banking_url' => $banking_url,
            'logout_url' => $logout_url,
            'failed_url' => $failed_url,
        ];

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'params', $params);

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $result = [
            'url' => null,
        ];

        if ($success) {
            $result['url'] = isset($result_arr['data']) ? $result_arr['data'] : null;
        }

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'result', $result);

        return array($success, $result);
    }

    public function queryBetDetailLink($player_username, $transaction_id = null, $extra = []) {
        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($player_username, $transaction_id, $extra);
        }

        $this->generateToken();
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_queryBetDetailLink;
        $game_username = $this->getGameUsernameByPlayerUsername($player_username);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'player_username' => $player_username,
            'game_username' => $game_username,
        ];

        $params = [
            'transaction_id' => $transaction_id,
            'lang' => $this->language,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $result = [
            'url' => null,
        ];

        if ($success) {
            $result['url'] = $result_arr['data'];
        }

        return [$success, $result];
    }

    public function syncOriginalGameLogs($token) {
        $this->generateToken();
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_syncGameRecords;
        $date_time_from = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $date_time_to = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $start_date_time_modified = $date_time_from->modify($this->getDatetimeAdjust());
        $date_time_from = new DateTime($this->serverTimeToGameTime($start_date_time_modified->format('Y-m-d H:i:s')));
        $date_time_to = new DateTime($date_time_to->format('Y-m-d H:i:s'));
        $start_date_time = $date_time_from->format('Y-m-d\TH:i:s');
        $end_date_time = $date_time_to->format('Y-m-d\TH:i:s');

        $result = [
            'success' => true,
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0,
            'sync_time_interval' => $this->sync_time_interval,
            'sleep_time' => $this->sleep_time
        ];

        while ($start_date_time <= $end_date_time) {
            $end_date_time_modified = (new DateTime($start_date_time))->modify($this->sync_time_interval)->format('Y-m-d\TH:i:s');

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
                'start_date' => $start_date_time,
                'end_date' => $end_date_time_modified
            );

            $params = [
                'company_id' => $this->company_id,
                'start_time' => $start_date_time,
                'end_time' => $end_date_time_modified,
            ];

            $data = $this->callApi($this->api_name, $params, $context);

            $success = isset($data['success']) && $data['success'] ? true : false;

            if ($success) {
                $game_records = $this->rebuildGameRecords($data);

                list($insert_rows, $update_rows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_seamless_game_logs_table,
                    $game_records,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('after process available rows ----->', 'gamerecords-> ' . count($game_records), 'insertrows-> ' . count($insert_rows), 'updaterows-> ' . count($update_rows));
                
                $result['data_count'] += is_array($game_records) ? count($game_records): 0;

                if (!empty($insert_rows)) {
                    $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insert_rows, 'insert');
                }

                unset($insert_rows);

                if (!empty($update_rows)) {
                    $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($update_rows, 'update');
                }

                unset($update_rows);
            }

            $this->CI->utils->debug_log('<--------------- processResultForSyncOriginalGameLogs --------------->', 'start_date_time: ' . $start_date_time, 'end_date_time_modified: ' . $end_date_time_modified);

            $success = false;
            sleep($this->sleep_time);

            $start_date_time = (new DateTime($start_date_time))->modify($this->sync_time_interval)->format('Y-m-d\TH:i:s');
        }

        return $result;
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $result = [
            'response_result_id' => $response_result_id,
            'game_records' => [],
        ];

        if ($success && !empty($result_arr['data'])) {
            $result['response_result'] = $result_arr['data'];
            $result['game_records'] = $result_arr['data'];
        }

        return array($success, $result);
    }

    public function rebuildGameRecords($data) {
        $game_records = !empty($data['game_records']) ? $data['game_records'] : [];
        $response_result_id = !empty($data['response_result_id']) ? $data['response_result_id'] : null;
        $new_game_records = $record = [];

        foreach ($game_records as $game_record) {
            $meta_data = !empty($game_record['meta_data']) ? $game_record['meta_data'] : [];

            $record['wallet_code'] = isset($game_record['wallet_code']) ? $game_record['wallet_code'] : null;
            $record['external_ref']= isset($game_record['external_ref']) ? $game_record['external_ref'] : null;
            $record['category'] = isset($game_record['category']) ? $game_record['category'] : null;
            $record['balance_type'] = isset($game_record['balance_type']) ? $game_record['balance_type'] : null;
            $record['type'] = isset($game_record['type']) ? $game_record['type'] : null;
            $record['balance'] = isset($game_record['balance']) ? $game_record['balance'] : null;
            $record['amount'] = isset($game_record['amount']) ? $game_record['amount'] : null;
            $record['pool_amount'] = isset($game_record['pool_amount']) ? $game_record['pool_amount'] : null;
            $record['round_id'] = isset($meta_data['round_id']) ? $meta_data['round_id'] : null;
            $record['ext_item_id'] = isset($meta_data['ext_item_id']) ? $meta_data['ext_item_id'] : null;
            $record['item_id'] = isset($meta_data['item_id']) ? $meta_data['item_id'] : null;
            $record['vendor'] = isset($meta_data['vendor']) ? json_encode($meta_data['vendor']) : null;
            $record['ext_w_tx_id'] = isset($meta_data['ext_w_tx_id']) ? $meta_data['ext_w_tx_id'] : null;
            $record['tx_round_id'] = isset($meta_data['tx_round_id']) ? $meta_data['tx_round_id'] : null;
            $record['context'] = isset($meta_data['context']) ? json_encode($meta_data['context']) : null;
            $record['transaction_id'] = isset($game_record['id']) ? $game_record['id'] : null;
            $record['parent_transaction_id'] = isset($game_record['parent_transaction_id']) ? $game_record['parent_transaction_id'] : null;
            $record['account_id'] = isset($game_record['account_id']) ? $game_record['account_id'] : null;
            $record['account_ext_ref'] = isset($game_record['account_ext_ref']) ? $game_record['account_ext_ref'] : null;
            $record['application_id'] = isset($game_record['application_id']) ? $game_record['application_id'] : null;
            $record['currency_unit'] = isset($game_record['currency_unit']) ? $game_record['currency_unit'] : null;
            $record['transaction_time'] = isset($game_record['transaction_time']) ? $game_record['transaction_time'] : null;
            $record['created_by'] = isset($game_record['created_by']) ? $game_record['created_by'] : null;
            $record['created'] = isset($game_record['created']) ? $game_record['created'] : null;
            $record['session'] = isset($game_record['session']) ? $game_record['session'] : null;
            $record['ip'] = isset($game_record['ip']) ? $game_record['ip'] : null;
            $record['extra_info'] = !empty($game_record) && is_array($game_record) ? json_encode($game_record) : null;
            $record['response_result_id'] = $response_result_id;
            $record['external_uniqueid'] = strtolower($record['category']) . '-' . $record['transaction_id'];

            array_push($new_game_records, $record);
        }

        return $new_game_records;
    }

    private function updateOrInsertOriginalGameLogs($data, $query_type) {
        $data_count = 0;

        if (!empty($data)) {
            foreach ($data as $record) {
                if ($query_type == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_seamless_game_logs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_seamless_game_logs_table, $record);
                }

                $data_count++;
                unset($record);
            }
        }

        return $data_count;
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
                $validated_transaction = [
                    // default
                    'api_method' => !empty($transaction['api_method']) ? $transaction['api_method'] : null,
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'end_at' => !empty($transaction['end_at']) ? $transaction['end_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : Game_logs::STATUS_PENDING,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                    'amount' => !empty($transaction['amount']) ? $transaction['amount'] : 0,
                    'bet_amount' => !empty($transaction['bet_amount']) ? $transaction['bet_amount'] : 0,
                    'win_amount' => !empty($transaction['win_amount']) ? $transaction['win_amount'] : 0,
                    'result_amount' => !empty($transaction['result_amount']) ? $transaction['result_amount'] : 0,
                    'before_balance' => !empty($transaction['before_balance']) ? $transaction['before_balance'] : 0,
                    'after_balance' => !empty($transaction['after_balance']) ? $transaction['after_balance'] : 0,
                    'extra_info' => !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [],

                    // additional
                    'tx_round_id' => !empty($transaction['tx_round_id']) ? $transaction['tx_round_id'] : null,
                    'refund_tx_id' => !empty($transaction['refund_tx_id']) ? $transaction['refund_tx_id'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                if ($this->enable_merging_rows) {
                    $is_sum = true;
                } else {
                    $is_sum = false;
                }

                $bet_transaction_id = null;

                if ($transaction_api_method == self::CATEGORY_PAYOUT) {
                    $bet_transaction_id = $transaction_tx_round_id;
                }

                if ($transaction_api_method == self::CATEGORY_REFUND) {
                    $bet_transaction_id = $transaction_refund_tx_id;
                }

                list($wager_transaction, $wager_table) = $this->queryPlayerTransaction(self::CATEGORY_WAGER, $transaction_player_id, $transaction_game_code, $transaction_round_id, $bet_transaction_id, $is_sum);
                list($payout_transaction, $payout_table) = $this->queryPlayerTransaction(self::CATEGORY_PAYOUT, $transaction_player_id, $transaction_game_code, $transaction_round_id, null, $is_sum);
                list($refund_transaction, $refund_table) = $this->queryPlayerTransaction(self::CATEGORY_REFUND, $transaction_player_id, $transaction_game_code, $transaction_round_id, null, $is_sum);

                $validated_wager_transaction = [
                    'amount' => !empty($wager_transaction['amount']) ? $wager_transaction['amount'] : 0,
                    'real_bet_amount' => !empty($wager_transaction['bet_amount']) ? $wager_transaction['bet_amount'] : 0,
                    'real_win_amount' => !empty($wager_transaction['win_amount']) ? $wager_transaction['win_amount'] : 0,
                    'result_amount' => !empty($wager_transaction['result_amount']) ? $wager_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($wager_transaction['external_unique_id']) ? $wager_transaction['external_unique_id'] : null,
                    'end_at' => !empty($wager_transaction['end_at']) ? $wager_transaction['end_at'] : null,
                    'extra_info' => !empty($wager_transaction['extra_info']) ? json_decode($wager_transaction['extra_info'], true) : [],
                ];

                $validated_payout_transaction = [
                    'amount' => !empty($payout_transaction['amount']) ? $payout_transaction['amount'] : 0,
                    'real_bet_amount' => !empty($payout_transaction['bet_amount']) ? $payout_transaction['bet_amount'] : 0,
                    'real_win_amount' => !empty($payout_transaction['win_amount']) ? $payout_transaction['win_amount'] : 0,
                    'result_amount' => !empty($payout_transaction['result_amount']) ? $payout_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($payout_transaction['external_unique_id']) ? $payout_transaction['external_unique_id'] : null,
                    'end_at' => !empty($payout_transaction['end_at']) ? $payout_transaction['end_at'] : null,
                    'extra_info' => !empty($payout_transaction['extra_info']) ? json_decode($payout_transaction['extra_info'], true) : [],
                ];

                $validated_refund_transaction = [
                    'amount' => !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0,
                    'real_bet_amount' => !empty($refund_transaction['bet_amount']) ? $refund_transaction['bet_amount'] : 0,
                    'real_win_amount' => !empty($refund_transaction['win_amount']) ? $refund_transaction['win_amount'] : 0,
                    'result_amount' => !empty($refund_transaction['result_amount']) ? $refund_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($refund_transaction['external_unique_id']) ? $refund_transaction['external_unique_id'] : null,
                    'end_at' => !empty($refund_transaction['end_at']) ? $refund_transaction['end_at'] : null,
                    'extra_info' => !empty($refund_transaction['extra_info']) ? json_decode($refund_transaction['extra_info'], true) : [],
                ];

                extract($validated_wager_transaction, EXTR_PREFIX_ALL, 'wager');
                extract($validated_payout_transaction, EXTR_PREFIX_ALL, 'payout');
                extract($validated_refund_transaction, EXTR_PREFIX_ALL, 'refund');

                if (array_key_exists('api_method', $transaction)) {
                    switch ($transaction_api_method) {
                        case self::CATEGORY_WAGER:
                            $extra_info['after_balance'] = $transaction_after_balance;

                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $transaction_amount,
                                'win_amount' => 0,
                                'result_amount' => -$transaction_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($extra_info),
                            ];

                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::CATEGORY_PAYOUT:
                            unset($extra_info['after_balance']);

                            if ($transaction_amount > 0) {
                                $extra_info['after_balance'] = $transaction_after_balance;
                            }

                            $payout_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => $transaction_amount,
                                'result_amount' => $transaction_amount - $wager_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($extra_info),
                            ];

                            if (!$this->enable_merging_rows) {
                                $win_amount = $transaction_amount;

                                $payout_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $payout_data['bet_amount'] = 0;
                                $payout_data['win_amount'] = $win_amount;
                                $payout_data['result_amount'] = $win_amount;

                                $wager_data['bet_amount'] = $wager_amount;
                                $wager_data['win_amount'] = 0;
                                $wager_data['result_amount'] = -$wager_amount;
                            }

                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $payout_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $wager_external_unique_id);
                            break;
                        case self::CATEGORY_REFUND:
                            $extra_info['after_balance'] = $transaction_after_balance;

                            $refund_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => 0,
                                'result_amount' => $transaction_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($extra_info),
                            ];

                            if (!$this->enable_merging_rows) {
                                $cancel_wager_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $cancel_wager_data['bet_amount'] = 0;
                                $cancel_wager_data['win_amount'] = 0;
                                $cancel_wager_data['result_amount'] = $transaction_amoun;

                                $wager_data['bet_amount'] = $wager_amount;
                                $wager_data['win_amount'] = 0;
                                $wager_data['result_amount'] = -$wager_amount;
                            }


                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $refund_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $wager_external_unique_id);
                            break;
                        default:
                            break;
                    }
                }

                $this->utils->info_log(__METHOD__, $this->seamless_game_api_name, 'transaction_start_at', $transaction_start_at, 'transaction_updated_at', $transaction_updated_at);
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
tx_round_id,
refund_tx_id
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

    public function queryPlayerTransaction($api_method, $player_id, $game_code, $round_id, $transaction_id = null, $is_sum = true) {
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
        $and_transaction_id = !empty($transaction_id) ? "AND transaction_id = ?" : '';

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
end_at,
extra_info
FROM {$table_name}
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ? AND is_processed = ? AND transaction_type != '' {$and_transaction_id}
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

        return array($result, $from_table);
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
        $sqlTime = 'updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'start_at BETWEEN ? AND ?';
        }

        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }

        if ($this->enable_merging_rows) {
            $sql = <<<EOD
SELECT
player_id,
round_id,
game_code
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result != ? AND is_processed = ? AND transaction_type != '' AND {$sqlTime}
EOD;

            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                self::IS_PROCESSED,
                $dateFrom,
                $dateTo,
            ];

            if ($this->show_logs) {
                $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
            }

            $transactions = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

            $results = [];
            foreach ($transactions as $transaction) {
                $wager_transaction = $this->queryTransactionForMergeGameLogs(self::CATEGORY_WAGER, $transaction['player_id'], $transaction['game_code'], $transaction['round_id']);

                if (!empty($wager_transaction['sync_index'])) {
                    $results[$wager_transaction['external_uniqueid']] = $wager_transaction;
                }
            }

            $results = array_values($results);
        } else {
            $results = $this->queryTransactionForSingleGameLogs($this->original_seamless_wallet_transactions_table, $dateFrom, $dateTo, $use_bet_time);
        }

        return $results;
    }

    public function queryTransactionForMergeGameLogs($api_method, $player_id, $game_code, $round_id) {
        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $result = [];

        foreach ($table_names as $table_name) {
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
SUM(transaction.bet_amount) AS bet_amount,
SUM(transaction.bet_amount) AS real_betting_amount,
SUM(transaction.win_amount) AS win_amount,
SUM(transaction.win_amount) - SUM(transaction.bet_amount) AS result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.is_processed,
transaction.request,
transaction.response,
transaction.extra_info,
transaction.round_id,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$table_name} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ? AND transaction.transaction_type != ''
AND transaction.api_method = ? AND transaction.player_id = ? AND transaction.game_code = ? AND transaction.round_id = ?
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            self::IS_PROCESSED,
            $api_method,
            $player_id,
            $game_code,
            $round_id,
        ];

        if ($this->show_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if (!empty($result['sync_index'])) {
            break;
        }
    }

        return $result;
    }

    public function queryTransactionForSingleGameLogs($table_name, $dateFrom, $dateTo, $use_bet_time = true) {
        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at BETWEEN ? AND ?';
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
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$table_name} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            self::IS_PROCESSED,
            $dateFrom,
            $dateTo,
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
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
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at' => !empty($row['bet_at']) ? $row['bet_at'] : '0000-00-00 00:00:00',
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
                'odds' =>  isset($row['odds']) ? $row['odds'] : null,
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

        return array($game_description_id, $game_type_id);
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

        $md5_fields = implode(", ", array('transaction.amount', 'transaction.before_balance', 'transaction.after_balance', 'transaction.result_amount', 'transaction.updated_at'));

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

        // print_r($bet_details);exit;
        return $bet_details;
    }
}
//end of class