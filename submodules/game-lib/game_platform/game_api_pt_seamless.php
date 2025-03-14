<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: PT
 * Game Type: Slots, Live Games, Virtual Sports, Card Games, Mini Games, Table Games
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2025 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -pt_seamless_service_api.php
 **/

class Game_api_pt_seamless extends Abstract_game_api {
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
    public $demo_game_launcher_url;
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

    // for t1 API
    public $launcher_mode;
    public $is_support_lobby;
    public $game_type_demo_lobby_supported;
    public $game_type_lobby_supported;

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
    public $use_failed_transaction_monthly_table;

    // default tables
    public $original_seamless_wallet_transactions_table;
    public $original_seamless_game_logs_table;
    public $game_seamless_service_logs_table;

    const ORIGINAL_SEAMLESS_WALLET_TRANSACTIONS_TABLE = 'pt_seamless_wallet_transactions';
    const ORIGINAL_SEAMLESS_GAME_LOGS_TABLE = 'pt_seamless_game_logs';
    const GAME_SEAMLESS_SERVICE_LOGS_TABLE = 'pt_seamless_service_logs';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';

    const RESPONSE_CODE_SUCCESS = 200;

    const IS_PROCESSED = 1;

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'end_at',
        'flag_of_updated_result',
        'extra_info',
        'valid_bet_amount',
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS = [
        'valid_bet_amount',
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
        'end_at',
        'created_at',
        'updated_at',
        'transaction_id',
        'round_id',
        'valid_bet_amount',
        'bet_amount',
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
        'valid_bet_amount',
        'bet_amount',
        'result_amount',
    ];

    // TRANSACTION TYPES
    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    // GAME API NAME
    public $seamless_game_api_name = 'PT_SEAMLESS_GAME_API';

    const URI_MAP = [
        self::API_queryForwardGame => '/from-operator/getGameLaunchUrl',
    ];

    // API METHODS HERE

    // additional
    public $server_name;
    public $kiosk_key;
    public $kiosk_name;
    public $kiosk_prefix;
    public $country_code;

    const CLIENT_PLATFORM_WEB = 'web';
    const CLIENT_PLATFORM_MOBILE = 'mobile';
    const PLAY_MODE_REAL = 1;
    const PLAY_MODE_DEMO = 0;

    const API_METHOD_BET = 'bet';
    const API_METHOD_GAME_ROUND_RESULT = 'gameroundresult';

    const TYPE_WIN = 'WIN';
    const TYPE_REFUND = 'REFUND';
    const TYPE_BONUS = 'BONUS';

    public function __construct() {
        parent::__construct();
        $this->CI->load->library('api_lib');
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
        $this->demo_game_launcher_url = $this->getSystemInfo('game_launcher_url', '');
        $this->use_utils_get_url = $this->getSystemInfo('use_utils_get_url', false);
        $this->home_url = $this->getSystemInfo('home_url', '');
        $this->cashier_url = $this->getSystemInfo('cashier_url', $this->home_url);
        $this->logout_url = $this->getSystemInfo('logout_url', $this->home_url);
        $this->failed_url = $this->getSystemInfo('failed_url', $this->home_url);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->use_failed_transaction_monthly_table = $this->getSystemInfo('use_failed_transaction_monthly_table', true);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        // tables
        $this->original_seamless_wallet_transactions_table = self::ORIGINAL_SEAMLESS_WALLET_TRANSACTIONS_TABLE;
        $this->original_seamless_game_logs_table = self::ORIGINAL_SEAMLESS_GAME_LOGS_TABLE;
        $this->game_seamless_service_logs_table = self::GAME_SEAMLESS_SERVICE_LOGS_TABLE;

        // initiate year month table
        $this->ymt_init();

        // for t1 api
        $this->launcher_mode = $this->getSystemInfo('launcher_mode', 'lobbyAndSingle');
        $this->is_support_lobby = $this->getSystemInfo('is_support_lobby', true);
        $this->game_type_demo_lobby_supported = $this->getSystemInfo('game_type_demo_lobby_supported', []);
        $this->game_type_lobby_supported = $this->getSystemInfo('game_type_lobby_supported', ['live_dealer']);
        $this->support_bet_detail_link = $this->getSystemInfo('support_bet_detail_link', true);

        // additional
        $this->server_name = $this->getSystemInfo('server_name');
        $this->kiosk_key = $this->getSystemInfo('kiosk_key');
        $this->kiosk_name = $this->getSystemInfo('kiosk_name');
        $this->kiosk_prefix = $this->getSystemInfo('kiosk_prefix');
        $this->country_code = $this->getSystemInfo('country_code');

        # fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 32);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);
    }

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', false);
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
        return PT_SEAMLESS_GAME_API;
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
            'x-auth-kiosk-key' => $this->kiosk_key,
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

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            $url .= "{$apiUri}" . '?' . http_build_query($params);
        } else {
            $url .= "{$apiUri}";
        }

        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null) {
        $success = false;

        if (in_array($statusCode, [200, 201]) && isset($resultArr['code']) && $resultArr['code'] == self::RESPONSE_CODE_SUCCESS) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name . ' API got error ', $responseResultId, 'status_code', $statusCode, 'player_name', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = 'Unable to create account for ' . $this->seamless_game_api_name;

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for ' . $this->seamless_game_api_name;
        }

        $result = [
            'success' => $success, 
            'message' => $message,
        ];

        return $result;
    }

    public function depositToGame($playerName, $amount, $transferSecureId = null) {
        $externalTransactionId = $transferSecureId;

        $result = [
            'success' => true,
            'external_transaction_id' => $externalTransactionId,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function withdrawFromGame($playerName, $amount, $transferSecureId = null) {
        $externalTransactionId = $transferSecureId;

        $result = [
            'success' => true,
            'external_transaction_id' => $externalTransactionId,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en',
            'zh_cn' => 'zh',
            'id_id' => 'id',
            'vi_vn' => 'vi',
            'ko_kr' => 'ko',
            'th_th' => 'th',
            'hi_in' => 'hi',
            'pt_pt' => 'pt',
            'es_es' => 'es',
            'kk_kz' => 'kk',
            'pt_br' => 'pt',
            'ja_jp' => 'ja',
            'es_mx' => 'es',
            'zh_hk' => 'zh',
            'fil_ph' => 'tl',
        ]);
    }

    public function queryForwardGame($playerName, $extra = null) {
        if (!$this->validateWhitePlayer($playerName)) {
            return ['success' => false];
        }

        $this->CI->load->model(['common_token']);
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_queryForwardGame;
        $playerDetails = $this->CI->common_token->getPlayerCompleteDetailsByUsername($playerName, $this->getPlatformCode());
        $playerId = !empty($playerDetails->player_id) ? $playerDetails->player_id : null;
        $gameUsername = !empty($playerDetails->game_username) ? $playerDetails->game_username : null;
        $isMobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $gameCode = isset($extra['game_code']) ? $extra['game_code'] : null;
        $gameMode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $isDemoMode = $this->utils->isDemoMode($gameMode);

        if (!empty($playerDetails->token)) {
            $token = $playerDetails->token;
        } else {
            $token = $this->getPlayerToken($playerId);
        }

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
            $homeUrl = $this->home_url;
        } else {
            if (!empty($extra['home_link'])) {
                $homeUrl = $extra['home_link'];
            } elseif (!empty($extra['extra']['home_link'])) {
                $homeUrl = $extra['extra']['home_link'];
            } else {
                if ($isMobile) {
                    $homeUrl = $this->getHomeLink(true);
                } else {
                    $homeUrl = $this->getHomeLink(false);
                }
            }
        }

        if (!empty($this->cashier_url)) {
            $cashierUrl = $this->cashier_url;
        } else {
            if (!empty($extra['cashier_link'])) {
                $cashierUrl = $extra['cashier_link'];
            } elseif (!empty($extra['extra']['cashier_link'])) {
                $cashierUrl = $extra['extra']['cashier_link'];
            } else {
                $cashierUrl = $homeUrl;
            }
        }

        if (!empty($this->logout_url)) {
            $logoutUrl = $this->logout_url;
        } else {
            $logoutUrl = $homeUrl;
        }

        if (!empty($this->failed_url)) {
            $failedUrl = $this->failed_url;
        } else {
            $failedUrl = $homeUrl;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerId' => $playerId,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'requestId' => $this->utils->getGUIDv4(),
            'serverName' => $this->server_name,
            'username' => $gameUsername,
            'gameCodeName' => $gameCode,
            'clientPlatform' => $isMobile ? self::CLIENT_PLATFORM_MOBILE : self::CLIENT_PLATFORM_WEB,
            'externalToken' => $this->utils->mergeArrayValues([$this->kiosk_prefix, $token], '_'),
            'language' => $language,
            'playMode' => $isDemoMode ? self::PLAY_MODE_DEMO : self::PLAY_MODE_REAL,
            'lobbyUrl' => $homeUrl,
            'depositUrl' => $cashierUrl,
        ];

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'params', $params);

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [
            'url' => null,
        ];

        if ($success) {
            $result['url'] = isset($resultArr['data']['url']) ? $resultArr['data']['url'] : null;
        }

        $result['success'] = $success;

        return array($success, $result);
    }

    public function queryBetDetailLink($playerUsername, $externalUniqueId = null, $extra = null) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $url = null;

        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $externalUniqueId, $extra);
        }

        $where = [
            'external_unique_id' => $externalUniqueId,
        ];

        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, $where);

        if (empty($transaction)) {
            $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, $where);
        }

        if (!empty($transaction)) {
            $where = [
                'api_method' => self::API_METHOD_GAME_ROUND_RESULT,
                'round_id' => $transaction['round_id'],
                'is_end_round' => true,
            ];

            $url = $this->CI->original_seamless_wallet_transactions->getSpecificField($this->original_seamless_wallet_transactions_table, 'game_history_url', $where);
        }

        if (empty($url)) {
            $gamePlatformId = $this->getPlatformCode();
            $baseUrl = $this->utils->getBaseUrlWithHost();
            $path = site_url("bet_detail/{$gamePlatformId}/{$externalUniqueId}");
            $url = rtrim($baseUrl, '/') . $path;
        }

        $result = [
            'success' => true,
            'url' => !empty($url) ? $url : null,
        ];

        return $result;
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

        $queryApiMethod = $this->enable_merging_rows ? self::API_METHOD_BET : null;
        $transactions = $this->queryTransactionsForUpdate($this->original_seamless_wallet_transactions_table, $queryDateTimeStart, $queryDateTimeEnd, $queryApiMethod, $this->use_bet_time);
        $updatedCount = 0;

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $gameRoundResult = $this->getPlayerGameRoundResult($transaction['player_id'], $transaction['round_id'], $transaction['status']);

                $extraInfo = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];
                $extraInfo['after_balance'] = $gameRoundResult['after_balance'];
                $extraInfo['bet_details'] = $this->buildBetDetails($transaction, $gameRoundResult);

                $updateData = [
                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                    'extra_info' => json_encode($extraInfo),
                ];

                if ($this->enable_merging_rows) {
                    $updateData['valid_bet_amount'] = $gameRoundResult['valid_bet_amount'];
                    $updateData['bet_amount'] = $gameRoundResult['bet_amount'];
                    $updateData['win_amount'] = $gameRoundResult['win_amount'];
                    $updateData['result_amount'] = $gameRoundResult['result_amount'];
                } else {
                    if ($transaction['api_method'] == self::API_METHOD_BET) {
                        $updateData['valid_bet_amount'] = $transaction['amount'];
                        $updateData['bet_amount'] = $transaction['amount'];
                        $updateData['win_amount'] = 0;
                        $updateData['result_amount'] = -$transaction['amount'];
                    } else {
                        if (empty($transaction['amount']) && $transaction['is_end_round']) {
                            continue; //? skip update
                        }

                        $updateData['valid_bet_amount'] = 0;
                        $updateData['bet_amount'] = 0;
                        $updateData['win_amount'] = $transaction['amount'];
                        $updateData['result_amount'] = $transaction['amount'];
                    }
                }

                $md5SumData = [
                    'status' => $transaction['status'],
                    'end_at' => $transaction['end_at'],
                    'flag_of_updated_result' => $updateData['flag_of_updated_result'],
                    'extra_info' => $updateData['extra_info'],
                    'valid_bet_amount' => $updateData['valid_bet_amount'],
                    'bet_amount' => $updateData['bet_amount'],
                    'win_amount' => $updateData['win_amount'],
                    'result_amount' => $updateData['result_amount'],
                ];

                $updateData['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow(
                    $md5SumData,
                    self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS,
                    self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS
                );

                $where = [
                    'external_unique_id' => $transaction['external_unique_id'],
                ];

                $isUpdated = $this->updateTransactionRecord($where, $updateData);

                if ($isUpdated) {
                    $updatedCount++;
                }
            }
        } else {
            $this->utils->debug_log(__METHOD__, 'No transactions found or query failed', 'table_name', $this->original_seamless_wallet_transactions_table);
        }

        $result = [
            'table_name' => $this->original_seamless_wallet_transactions_table,
            'enable_merging_rows' => $this->enable_merging_rows,
            'transactionsCount' => count($transactions),
            'updatedCount' => $updatedCount,
        ];

        $this->utils->info_log(__METHOD__, $this->seamless_game_api_name, 'result', $result);

        return ['success' => true, $result];
    }

    private function buildBetDetails($transaction, $gameRoundResult) {
        $betDetails = [
            'game_name' => $transaction['game_name'],
            'round_id' => $transaction['round_id'],
            'valid_bet_amount' => $gameRoundResult['valid_bet_amount'],
            'bet_amount' => $gameRoundResult['bet_amount'],
            'result_amount' => $gameRoundResult['result_amount'],
            'betting_datetime' => $transaction['start_at'],
            'settlement_datetime' => $transaction['end_at'],
        ];
    
        if ($transaction['status'] == Game_logs::STATUS_REFUND) {
            $betDetails['refund_amount'] = $gameRoundResult['refund_amount'];
        } else {
            $betDetails['win_amount'] = $gameRoundResult['win_amount'];
        }
    
        return $betDetails;
    }

    public function updateTransactionRecord($where, $data) {
        $isUpdated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->original_seamless_wallet_transactions_table, $where, $data);

        if ($this->use_monthly_transactions_table && $this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
            if (!$isUpdated) {
                $isUpdated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($this->previous_table, $where, $data);
            }
        }

        return $isUpdated;
    }

    public function queryTransactionsForUpdate($transactionTable, $dateFrom, $dateTo, $apiMethod = null, $useBetTime = true) {
        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($useBetTime) {
            $sqlTime = 'transaction.start_at BETWEEN ? AND ?';
        }

        if (!empty($apiMethod)) {
            $andApiMethod = 'AND transaction.api_method = ?';
        } else {
            $andApiMethod = '';
        }

        $sql = <<<EOD
SELECT
transaction.id,
transaction.game_platform_id,
transaction.player_id,
transaction.api_method,
transaction.transaction_type,
transaction.transaction_id,
transaction.game_code,
transaction.round_id,
transaction.amount,
transaction.start_at,
transaction.end_at,
transaction.status,
transaction.request,
transaction.external_unique_id,
transaction.updated_at,
transaction.bet_amount,
transaction.win_amount,
transaction.result_amount,
transaction.before_balance,
transaction.after_balance,
transaction.is_end_round,
transaction.extra_info,
transaction.reference_transaction_id,
game_description.english_name AS game_name
FROM {$transactionTable} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND {$sqlTime} {$andApiMethod}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::IS_PROCESSED,
            $dateFrom,
            $dateTo
        ];

        if (!empty($apiMethod)) {
            array_push($params, $apiMethod);
        }

        if ($this->show_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function getPlayerGameRoundResult($playerId, $roundId, $status) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $select = [
            'api_method',
            'amount',
            'after_balance',
        ];

        $where = [
            'player_id' => $playerId,
            'round_id' => $roundId,
            'status' => $status,
        ];

        $transactions = $this->CI->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, $where, $select);

        if (empty($transactions)) {
            $transactions = $this->CI->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->previous_table, $where, $select);
        }

        $result = [
            'valid_bet_amount' => 0,
            'bet_amount' => 0,
            'win_amount' => 0,
            'refund_amount' => 0,
            'result_amount' => 0,
            'after_balance' => 0,
        ];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                if ($transaction['api_method'] == self::API_METHOD_BET) {
                    $result['valid_bet_amount'] += $transaction['amount'];
                    $result['bet_amount'] += $transaction['amount'];
                } else {
                    if ($status == Game_logs::STATUS_REFUND) {
                        $result['refund_amount'] += $transaction['amount'];
                    } else {
                        $result['win_amount'] += $transaction['amount'];
                    }

                    $result['after_balance'] = $transaction['after_balance'];
                }

                if ($status == Game_logs::STATUS_REFUND) {
                    $result['result_amount'] = -$result['refund_amount'];
                } else {
                    $result['result_amount'] = $result['win_amount'] - $result['bet_amount'];
                }
            }
        }

        return $result;
    }

    public function syncMergeToGameLogs($token) {
        $enabledGameLogsUnsettle = true;

        if ($this->use_transaction_data) {
            $this->syncOriginalGameLogsFromTrans($token);
        }

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabledGameLogsUnsettle
        );
    }

    /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $useBetTime
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $useBetTime) {
        $sqlTime = "AND transaction.updated_at BETWEEN ? AND ?";

        if ($useBetTime) {
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
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id,
transaction.amount,
transaction.valid_bet_amount,
transaction.bet_amount,
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
                'bet_amount' => !empty($row['valid_bet_amount']) ? $row['valid_bet_amount'] : 0,
                'result_amount' => !empty($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => !empty($row['valid_bet_amount']) ? $row['valid_bet_amount'] : 0,
                'real_betting_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $this->enable_merging_rows ? $this->CI->api_lib->lookup($row, 'extra_info.after_balance') : $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : null,
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : null,
                'bet_at' => !empty($row['start_at']) ? $row['start_at'] : null,
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
            'bet_details' => $this->CI->api_lib->lookup($row, 'extra_info.bet_details', $this->preprocessBetDetails($row, null, true)),
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

        $row['note'] = $this->getResult($row);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $gameCode = !empty($row['game_code']) ? $row['game_code'] : null;
        $gameTypeId = !empty($row['game_type_id']) ? $row['game_type_id'] : $unknownGame->game_type_id;

        if (!empty($row['game_description_id'])) {
            $gameDescriptionId = $row['game_description_id'];
        } else {
            $gameDescriptionId = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $gameCode, $gameCode);
        }

        return [$gameDescriptionId, $gameTypeId];
    }

    public function getResult($row = []) {
        $result = 'Unknown';
        $request = json_decode($row['request'], true);

        if ($row['status'] == Game_logs::STATUS_SETTLED) {
            if ($this->enable_merging_rows) {
                if ($row['result_amount'] > 0) {
                    $result = 'Win';
                } elseif ($row['result_amount'] < 0) {
                    $result = 'Lose';
                } elseif ($row['result_amount'] == 0) {
                    $result = 'Draw';
                } else {
                    $result = 'Free Game';
                }
            } else {
                $result = $row['transaction_type'];
            }

            if ($this->CI->api_lib->lookup($request, 'pay.internalFundChanges.0.type', $this->CI->api_lib->lookup($request, 'internalFundChanges.0.type')) == self::TYPE_BONUS) {
                $result .= ' Free spin';
            }
        } elseif ($row['status'] == Game_logs::STATUS_PENDING) {
            $result = 'Pending';
        } elseif ($row['status'] == Game_logs::STATUS_ACCEPTED) {
            $result = 'Accepted';
        } elseif ($row['status'] == Game_logs::STATUS_REJECTED) {
            $result = 'Rejected';
        } elseif ($row['status'] == Game_logs::STATUS_CANCELLED) {
            $result = 'Cancelled';
        } elseif ($row['status'] == Game_logs::STATUS_VOID) {
            $result = 'Void';
        } elseif ($row['status'] == Game_logs::STATUS_REFUND) {
            $result = 'Refund';
        } elseif ($row['status'] == Game_logs::STATUS_SETTLED_NO_PAYOUT) {
            $result = 'Settled no payout';
        } elseif ($row['status'] == Game_logs::STATUS_UNSETTLED) {
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
            $queryTime = "AND transaction.updated_at BETWEEN ? AND ?";
        } else {
            $queryTime = "AND transaction.created_at BETWEEN ? AND ?";
        }

        $md5Fields = implode(", ", ['transaction.amount', 'transaction.before_balance', 'transaction.after_balance', 'transaction.result_amount', 'transaction.updated_at']);

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
MD5(CONCAT({$md5Fields})) AS md5_sum,
transaction.result_amount,
transaction.request
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND transaction.transaction_type != '' {$queryTime}
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
        $tempGameRecords = [];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $tempGameRecord = [];
                $tempGameRecord['player_id'] = $transaction['player_id'];
                $tempGameRecord['game_platform_id'] = $this->getPlatformCode();
                $tempGameRecord['transaction_date'] = $transaction['transaction_date'];
                $tempGameRecord['amount'] = abs($transaction['amount']);
                $tempGameRecord['before_balance'] = $transaction['before_balance'];
                $tempGameRecord['after_balance'] = $transaction['after_balance'];
                $tempGameRecord['round_no'] = $transaction['round_no'];
                $tempGameRecord['md5_sum'] = $transaction['md5_sum'];

                if (empty($tempGameRecord['round_no']) && isset($transaction['transaction_id'])) {
                    $tempGameRecord['round_no'] = $transaction['transaction_id'];
                }

                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $tempGameRecord['extra_info'] = json_encode($extra);
                $tempGameRecord['external_uniqueid'] = $transaction['external_uniqueid'];
                $tempGameRecord['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_DEBIT) {
                    $tempGameRecord['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $tempGameRecords[] = $tempGameRecord;
                unset($tempGameRecord);
            }
        }

        $transactions = $tempGameRecords;
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
}
//end of class