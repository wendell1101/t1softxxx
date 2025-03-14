<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: Hacksaw
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2024 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -hacksaw_seamless_service_api.php
 **/

class Game_api_hacksaw_seamless extends Abstract_game_api {
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
    public $lobby_url;
    public $banking_url;
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

    // free spin API
    public $free_spin_reference_id_prefix;
    public $free_spin_reference_id_length;
    public $free_spin_default_number_of_rounds;
    public $free_spin_default_game_ids;
    public $free_spin_default_bet_value;
    public $free_spin_default_validity_hours;

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

    // TRANSACTION TYPES
    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    // GAME API NAME
    public $seamless_game_api_name = 'HACKSAW_SEAMLESS_GAME_API';

    const URI_MAP = [
        self::API_queryForwardGame => '',
        self::API_queryGameListFromGameProvider => '/gameList',
        self::API_queryBetDetailLink => '/getReplay',
        self::API_checkTicketStatus => '/getRoundStatus',
        self::API_createFreeRoundBonus => '/awardFreeRounds',
        self::API_cancelFreeRoundBonus => '/removeFreeRounds',
        self::API_queryFreeRoundBonus => '/getPendingFreeRoundsForPlayer',
    ];

    // API METHODS HERE
    const API_METHOD_BET = 'Bet';
    const API_METHOD_WIN = 'Win';
    const API_METHOD_ROLLBACK = 'Rollback';

    // additional
    public $environment;
    public $version;
    public $partner_id;
    public $casino_id;
    public $secret_key;
    public $api_username;
    public $api_password;

    const ENVIRONMENT_STAGING = 'staging';
    const ENVIRONMENT_PRODUCTION = 'production';
    const VERSION_1 = 'v1';
    const PARTNER_API = '/partner';
    const META_API = '/meta';

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
        $this->lobby_url = $this->getSystemInfo('lobby_url', $this->getHomeLink());
        $this->banking_url = $this->getSystemInfo('banking_url', $this->lobby_url);
        $this->logout_url = $this->getSystemInfo('logout_url', $this->lobby_url);
        $this->failed_url = $this->getSystemInfo('failed_url', $this->lobby_url);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        // default tables
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'hacksaw_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'hacksaw_seamless_service_logs');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'hacksaw_seamless_game_logs');

        $this->ymt_init();

        // free spin API
        $this->free_spin_reference_id_prefix = $this->getSystemInfo('free_spin_reference_id_prefix', 'HS');
        $this->free_spin_reference_id_length = $this->getSystemInfo('free_spin_reference_id_length', 12);
        $this->free_spin_default_number_of_rounds = $this->getSystemInfo('free_spin_default_number_of_rounds', 1);
        $this->free_spin_default_game_ids = $this->getSystemInfo('free_spin_default_game_ids', '');
        $this->free_spin_default_bet_value = $this->getSystemInfo('free_spin_default_bet_value', '');
        $this->free_spin_default_validity_hours = $this->getSystemInfo('free_spin_default_validity_hours', '+2 hours');

        // additional
        $this->environment = $this->getSystemInfo('environment', self::ENVIRONMENT_STAGING);
        $this->version = $this->getSystemInfo('version', self::VERSION_1);
        $this->partner_id = $this->getSystemInfo('partner_id');
        $this->casino_id = $this->getSystemInfo('casino_id');
        $this->secret_key = $this->getSystemInfo('secret_key');
        $this->api_username = $this->getSystemInfo('api_username');
        $this->api_password = $this->getSystemInfo('api_password');

        // for staging environment
        if ($this->environment == self::ENVIRONMENT_STAGING) {
            // API URL
            if (empty($this->api_url)) {
                $this->api_url = 'https://api-stg.hacksawgaming.com/api/' . $this->version;
            }

            // Game launcher URL
            if (empty($this->game_launcher_url)) {
                $this->game_launcher_url = 'https://static-stg.hacksawgaming.com/launcher/static-launcher.html';
            }
        }

        // for production environment
        if ($this->environment == self::ENVIRONMENT_PRODUCTION) {
            // API URL
            if (empty($this->api_url)) {
                $this->api_url = 'https://api.hacksawgaming.com/api/' . $this->version;
            }

            // Game launcher URL
            if (empty($this->game_launcher_url)) {
                $this->game_launcher_url = 'https://static-live.hacksawgaming.com/launcher/static-launcher.html';
            }
        }
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
        return HACKSAW_SEAMLESS_GAME_API;
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
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryForwardGame:
                    $url = $this->game_launcher_url . '?' . http_build_query($params);
                    break;
                case self::API_queryGameListFromGameProvider:
                    $url .= self::META_API . '/' . $this->partner_id . $api_uri . '?' . http_build_query($params);
                    break;
                default:
                    $url .= '/' . ltrim($api_uri, '/') . '?' . http_build_query($params);
                    break;
            }
        } else {
            switch ($api_name) {
                case self::API_queryBetDetailLink:
                case self::API_checkTicketStatus:
                case self::API_createFreeRoundBonus:
                case self::API_cancelFreeRoundBonus:
                case self::API_queryFreeRoundBonus:
                    $url .= self::PARTNER_API . '/' . $this->partner_id . $api_uri;
                    break;
                default:
                    $url .= '/' . ltrim($api_uri, '/');
                    break;
            }
        }

        return $url;
    }

    public function processResultBoolean($response_result_id, $result_arr, $status_code, $player_name = null) {
        $success = false;

        if (($status_code == 200 || $status_code == 201) && $result_arr['statusCode'] == self::RESPONSE_CODE_SUCCESS) {
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
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->utils->debug_log('Hacksaw-queryForwardGame', $extra);
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_queryForwardGame;

        $player_id = $this->getPlayerIdFromUsername($player_name);
        $token = $this->getPlayerToken($player_id);
        // $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

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

        if ($is_mobile) {
            $this->lobby_url = $this->getHomeLink(true);
        } else {
            $this->lobby_url = $this->getHomeLink(false);
        }

        if ($this->use_utils_get_url) {
            $this->lobby_url = $this->utils->getUrl();
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

        if(isset($extra['extra']['home_link'])){
            $lobby_url = $extra['extra']['home_link'];
        }

        $params = [
            'gameid' => $game_code,
            'channel' => $is_mobile ? 'mobile' : 'desktop',
            'mode' => $is_demo_mode ? 'demo' : 'live',
            'currency' => $this->currency,
            'partner' => $this->partner_id,
            'language' => $language,
            'lobbyurl' => $lobby_url,
        ];

        if (!$is_demo_mode) {
            $params['token'] = $token;
        }

        $url = $this->generateUrl($this->api_name, $params);

        $result = [
            'success' => true,
            'params' => $params,
            'url' => $url,
        ];

        $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'params', $params);

        return $result;
    }

    public function queryGameListFromGameProvider($extra = []) {
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_name = self::API_queryGameListFromGameProvider;

        $gameId = isset($extra['gameId']) ? $extra['gameId'] : null;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $params = array(
            'partner' => $this->partner_id,
            'currency' => $this->currency,
        );

        if (!empty($gameId)) {
            $params['gameId'] = $gameId;
        }

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        }

        return array($success, $result);
    }

    public function queryBetDetailLink($player_username, $round_id = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_queryBetDetailLink;
        $game_username = $this->getGameUsernameByPlayerUsername($player_username);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'player_username' => $player_username,
            'game_username' => $game_username,
        ];

        $params = [
            'username' => $this->api_username,
            'password' => $this->api_password,
            'roundId' => $round_id,
        ];

        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($player_username, $round_id, $extra);
        }

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
            $result['url'] = $result_arr['url'];
        }

        return [$success, $result];
    }

    public function checkTicketStatus($round_id) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_checkTicketStatus;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCheckTicketStatus',
        ];

        $params = [
            'username' => $this->api_username,
            'password' => $this->api_password,
            'roundId' => $round_id,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForCheckTicketStatus($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        return [$success, $result_arr];
    }

    public function createFreeRound($playerName, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_createFreeRoundBonus;

        $gameUsername = !empty($extra['externalId']) ? $extra['externalId'] : $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $gameId = !empty($extra['gameId']) ? $extra['gameId'] : $this->free_spin_default_game_ids;
        $numberOfRounds = !empty($extra['nbRounds']) ? $extra['nbRounds'] : $this->free_spin_default_number_of_rounds;
        $betLevel = isset($extra['betLevel']) ? $extra['betLevel'] : $this->free_spin_default_bet_value;
        $currency = !empty($extra['currencyCode']) ? $extra['currencyCode'] : $this->currency;
        $transactionId = !empty($extra['externalOfferId']) ? $extra['externalOfferId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_spin_reference_id_prefix, $this->free_spin_reference_id_length);
        $turnoverRequirement = !empty($extra['turnoverRequirement']) ? $extra['turnoverRequirement'] : ''; // optional
        $channel = !empty($extra['channel']) ? $extra['channel'] : ''; // optional
        $expiryDate = !empty($extra['expiryDate']) ? $extra['expiryDate'] : $this->gameApiDateTime('now', 'Y-m-d\TH:i:s\Z', $this->free_spin_default_validity_hours); // optional
        $buyBonus = !empty($extra['buyBonus']) ? $extra['buyBonus'] : ''; // optional // If this parameter is used, the nbRounds parameter must be 1
        $rejectable = !empty($extra['rejectable']) ? $extra['rejectable'] : true; // optional // default true
        $permanentlyRejectable = !empty($extra['permanentlyRejectable']) ? $extra['permanentlyRejectable'] : true; // optional // default true

        $params = [
            'username' => $this->api_username,
            'password' => $this->api_password,
            'externalId' => $gameUsername,
            'gameId' => $gameId,
            'nbRounds' => $numberOfRounds,
            'betLevel' => $betLevel,
            'currencyCode' => $currency,
            'externalOfferId' => $transactionId,
        ];

        if ($turnoverRequirement != '') {
            $params['turnoverRequirement'] = $turnoverRequirement;
        }

        if (!empty($channel)) {
            $params['channel'] = $channel;
        }

        if (!empty($expiryDate)) {
            $params['expiryDate'] = $expiryDate;
        }

        // If this parameter is used, the nbRounds parameter must be 1
        if (!empty($buyBonus)) {
            $params['buyBonus'] = $buyBonus;
            $params['nbRounds'] = 1; //
        }

        if (isset($rejectable)) {
            $params['rejectable'] = $rejectable;
        }

        if (isset($permanentlyRejectable)) {
            $params['permanentlyRejectable'] = $permanentlyRejectable;
        }


        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'game_username' => $gameUsername,
            'player_id' => $playerId,
            'free_rounds' => $numberOfRounds,
            'transaction_id' => $transactionId,
            'currency' => $currency,
            'expired_at' => $expiryDate,
            'extra' => $extra,
            'request' => $params,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForCreateFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $free_rounds = $this->getVariableFromContext($params, 'free_rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');
        $request = $this->getVariableFromContext($params, 'request');

        if ($success) {
            $result = [
                'transaction_id' => $transaction_id,
                'expiration_date' => $expired_at,
            ];

            $data = [
                'player_id' => $player_id,
                'game_platform_id' => $this->getPlatformCode(),
                'free_rounds' => $free_rounds,
                'transaction_id' => $transaction_id,
                'currency' => $currency,
                'expired_at' => $expired_at,
                'extra' => json_encode($extra),
                'raw_data' => json_encode($request),
            ];

            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->insertTransaction($data);
        } else {
            $result = [
                'message' => isset($resultArr['statusMessage']) ? $resultArr['statusMessage'] : null,
            ];
        }

        return [$success, $result];
    }

    public function cancelFreeRound($transaction_id, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_cancelFreeRoundBonus;
        $transaction_id = !empty($extra['externalOfferId']) ? $extra['externalOfferId'] : $transaction_id;

        if (!empty($extra['externalId'])) {
            $playerId = null;
            $gameUsername = $extra['externalId'];
        } else {
            $this->CI->load->model(['free_round_bonus_model']);
            $playerId = $this->CI->free_round_bonus_model->getSpecificColumn('free_round_bonuses', 'player_id', ['transaction_id' => $transaction_id]);
            $gameUsername = $this->getGameUsernameByPlayerId($playerId);
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'gameUsername' => $gameUsername,
            'transaction_id' => $transaction_id,
        ];

        if (!empty($playerId)) {
            $context['playerId'] = $playerId;
        }

        $params = [
            'username' => $this->api_username,
            'password' => $this->api_password,
            'externalId' => $gameUsername,
            'ReferenceId' => $transaction_id,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForCancelFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');

        if ($success) {
            $result = [
                'transaction_id' => $transaction_id,
            ];

            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->cancelTransaction($transaction_id, $this->getPlatformCode());
        } else {
            $result = [
                'message' => isset($resultArr['statusMessage']) ? $resultArr['statusMessage'] : null,
            ];
        }

        return [$success, $result];
    }

    public function queryFreeRound($playerName, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->api_name = self::API_queryFreeRoundBonus;
        $gameUsername = !empty($extra['externalId']) ? $extra['externalId'] : $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
            'game_username' => $gameUsername,
        ];

        $params = [
            'username' => $this->api_username,
            'password' => $this->api_password,
            'externalId' => $gameUsername,
        ];

        return $this->callApi($this->api_name, $params, $context);
    }

    public function processResultForQueryFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($success) {
            $result = [
                'free_round_list' => !empty($resultArr['freeRounds']) ? $resultArr['freeRounds'] : [],
            ];
        }
        else {
            $result = [
                'message' => isset($resultArr['statusMessage']) ? $resultArr['statusMessage'] : null,
            ];
        }

        return [$success, $result];
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
                        case self::API_METHOD_BET:
                            $extra_info['after_balance'] = $transaction['after_balance'];

                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => $transaction['amount'],
                                'win_amount' => 0,
                                'result_amount' => -$transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction['end_at'],
                                'extra_info' => json_encode($extra_info),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction['external_unique_id']);
                            break;
                        case self::API_METHOD_WIN:
                            list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::API_METHOD_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], $transaction['bet_transaction_id'], $is_sum);
                            unset($extra_info['after_balance']);

                            if ($transaction['amount'] > 0) {
                                $extra_info['after_balance'] = $transaction['after_balance'];
                            }

                            $win_data = [
                                'status' => $transaction['status'],
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => $bet_transaction['amount'],
                                'win_amount' => $transaction['amount'],
                                'result_amount' => $transaction['amount'] - $bet_transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction['end_at'],
                                'extra_info' => json_encode($extra_info),
                            ];

                            if (!$this->enable_merging_rows) {
                                $win_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $win_data['win_amount'] = $transaction['amount'];
                                $win_data['result_amount'] = $transaction['amount'];

                                $bet_data['bet_amount'] = $bet_transaction['amount'];
                                $bet_data['result_amount'] = -$bet_transaction['amount'];
                            }

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction['external_unique_id']);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($bet_table, $bet_data, 'external_unique_id', $bet_transaction['external_unique_id']);
                            break;
                        case self::API_METHOD_ROLLBACK:
                            list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::API_METHOD_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], $transaction['rolled_back_transaction_id'], $is_sum);

                            $extra_info['after_balance'] = $transaction['after_balance'];

                            $refund_data = [
                                'status' => $transaction['status'],
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => 0,
                                'win_amount' => 0,
                                'result_amount' => $transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction['end_at'],
                                'extra_info' => json_encode($extra_info),
                            ];

                            if (!$this->enable_merging_rows) {
                                $refund_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $refund_data['result_amount'] = $transaction['amount'];

                                $bet_data['bet_amount'] = $bet_transaction['amount'];
                                $bet_data['result_amount'] = -$bet_transaction['amount'];
                            }

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $refund_data, 'external_unique_id', $transaction['external_unique_id']);
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
bet_transaction_id,
rolled_back_transaction_id
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
WHERE game_platform_id = ? AND api_method = ? AND player_id = ? AND game_code = ? AND round_id = ? AND transaction_id = ?
AND is_processed = ? AND transaction_type != ''
EOD;

            $params = [
                $this->getPlatformCode(),
                $api_method,
                $player_id,
                $game_code,
                $round_id,
                $transaction_id,
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

            if ($row['bet_amount'] == 0) {
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

        // print_r($bet_details);exit;
        return $bet_details;
    }
}
//end of class