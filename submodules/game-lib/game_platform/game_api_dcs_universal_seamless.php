<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: DCS
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -dcs_universal_seamless_service_api.php
 **/

class Game_api_dcs_universal_seamless extends Abstract_game_api {
    use Year_month_table_module;

    // default
    public $http_method;
    public $api_url;
    public $language;
    public $force_language;
    public $currency;
    public $prefix_for_username;
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $game_api_player_blocked_validate_api_methods;
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $game_seamless_service_logs_table;
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
    public $enable_merging_rows;

    // monthly transactions table
    public $init_ymt;
    public $initialize_monthly_transactions_table;
    public $use_monthly_transactions_table;
    public $force_check_previous_transactions_table;
    public $force_check_other_transactions_table;
    public $previous_table = null;
    public $show_logs;
    public $use_monthly_service_logs_table;
    public $use_monthly_game_logs_table;

    // additional
    public $api_key;
    public $brand;
    public $brand_id;
    public $brand_api_url;
    public $get_bet_data_url;
    public $country_code;
    public $return_url;
    public $full_screen;
    public $provider;
    public $validate_sign;
    public $token_timeout_seconds;
    public $force_refresh_token_timeout;

    const SEAMLESS_GAME_API_NAME = 'DCS_UNIVERSAL';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCESS = 1000;

    const API_METHOD_WAGER = 'wager';
    const API_METHOD_CANCEL_WAGER = 'cancelWager';
    const API_METHOD_APPEND_WAGER = 'appendWager';
    const API_METHOD_END_WAGER = 'endWager';
    const API_METHOD_FREE_SPIN_RESULT = 'freeSpinResult';
    const API_METHOD_PROMO_PAYOUT = 'promoPayout';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const API_queryForwardGameDemo = 'queryForwardGameDemo';

    const URI_MAP = [
        self::API_queryForwardGame => '/dcs/loginGame',
        self::API_queryForwardGameDemo => '/dcs/tryGame',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
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
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    const PROVIDER_LIST = [
        'yg' => [
            'code' => 'yg',
            'description' => 'Yggdrasil'
        ],
        'relax' => [
            'code' => 'relax',
            'description' => 'Relax gaming'
        ],
        'nlc' => [
            'code' => 'nlc',
            'description' => 'Nolimit City'
        ],
        'png' => [
            'code' => 'png',
            'description' => "Play'n Go"
        ],
        'hs' => [
            'code' => 'hs',
            'description' => 'Hacksaw Gaming'
        ],
        'aux' => [
            'code' => 'aux',
            'description' => 'Avatar UX'
        ],
        'evo' => [
            'code' => 'evo',
            'description' => 'Evoplay'
        ],
        'gam' => [
            'code' => 'gam',
            'description' => 'Gamomat'
        ],
        'ghg' => [
            'code' => 'ghg',
            'description' => 'Golden Hero'
        ],
        'psh' => [
            'code' => 'psh',
            'description' => 'Push Gaming'
        ],
        'ezugi' => [
            'code' => 'ezugi',
            'description' => 'Ezugi'
        ],
        'swf' => [
            'code' => 'swf',
            'description' => 'Win Fast'
        ],
        'funta' => [
            'code' => 'funta',
            'description' => 'FunTa Gaming'
        ],
        'stm' => [
            'code' => 'stm',
            'description' => 'Slotmill'
        ],
    ];

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
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 4);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'dcs_universal_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'dcs_universal_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'dcs_universal_seamless_service_logs');
        $this->save_game_seamless_service_logs = $this->getSystemInfo('save_game_seamless_service_logs', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->get_usec = $this->getSystemInfo('get_usec', true);
        $this->use_bet_time = $this->getSystemInfo('use_bet_time', true);
        $this->show_hint = $this->getSystemInfo('show_hint', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->token_timeout_seconds = $this->getSystemInfo('token_timeout_seconds', 3600); // 1 minute (60), 1 hour (3600)
        $this->force_refresh_token_timeout = $this->getSystemInfo('force_refresh_token_timeout', false);

        $this->ymt_init();

        // additional
        $this->api_key = $this->getSystemInfo('api_key');
        $this->brand = $this->getSystemInfo('brand');
        $this->brand_id = $this->getSystemInfo('brand_id');
        $this->brand_api_url = $this->getSystemInfo('brand_api_url');
        $this->get_bet_data_url = $this->getSystemInfo('get_bet_data_url');
        $this->country_code = $this->getSystemInfo('country_code', 'CN'); // https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
        $this->return_url = $this->getSystemInfo('return_url', $this->getHomeLink());
        $this->full_screen = $this->getSystemInfo('full_screen', true);
        $this->provider = $this->getSystemInfo('provider', '');
        $this->validate_sign = $this->getSystemInfo('validate_sign', true);
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 20);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);
    }

    public function ymt_init() {
        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
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
        return DCS_UNIVERSAL_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
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

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            $url .= $api_uri . '?' . http_build_query($params);
        } else {
            $url .= $api_uri;
        }

        return $url;
    }

    public function getHttpHeaders($params) {
        $headers = [
            'Content-type' => 'application/json',
        ];

        return $headers;
    }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null) {
        $success = false;

        if (isset($resultArr['code']) && $resultArr['code'] == self::RESPONSE_CODE_SUCCESS) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function generatedSign($params) {
        array_push($params, $this->api_key);

        return strtoupper(md5($this->utils->mergeArrayValues($params, '')));
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = 'Unable to create account for ' . self::SEAMLESS_GAME_API_NAME;

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for ' . self::SEAMLESS_GAME_API_NAME;
        }

        $result = [
            'success' => $success, 
            'message' => $message,
        ];

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
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

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
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

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en',
            'zh_cn' => 'zh_hans',
            'id_id' => 'id',
            'vi_vn' => 'vi',
            'ko_kr' => 'kr',
            'th_th' => 'th',
            'hi_in' => 'hi',
            'pt_pt' => 'pt',
            'es_es' => 'es',
            'kk_kz' => 'kk',
            'pt_br' => 'pt_BR',
            'ja_jp' => 'ja',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->CI->load->model('external_common_tokens');
        $this->http_method = self::HTTP_METHOD_POST;
        $api_name = self::API_queryForwardGame;
        $player_id = $this->getPlayerIdFromUsername($player_name);
        list($token, $sign_key) = $this->CI->common_token->createTokenWithSignKeyBy($player_id, 'player_id', $this->token_timeout_seconds);
        //$token = $this->getPlayerToken($player_id);
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);
        $by_token_only = true;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_id' => $player_id,
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

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
            $channel = 'mobile';
        } else {
            $channel = 'pc';
        }

        if (!empty($extra['home_link'])) {
            $return_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $return_url = $extra['extra']['home_link'];
        } else {
            $return_url = $this->return_url;
        }

        $sign_params = [
            $this->brand_id,
            $game_username,
        ];

        $params = [
            'brand_id' => $this->brand_id,
            'sign' => $this->generatedSign($sign_params),
            'brand_uid' => $game_username,
            'token' => $token,
            'game_id' => $game_code,
            'currency' => $this->currency,
            'language' => $language,
            'channel' => $channel,
            'country_code' => $this->country_code,
            'return_url' => $return_url,
            'full_screen' => $this->full_screen,
        ];


        #removes home link if disable_home_link is set to TRUE
        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['return_url']);
        }

        if ($is_demo_mode) {
            $api_name = self::API_queryForwardGameDemo;
            unset($params['brand_uid'], $params['token'], $params['country_code']);

            $sign_params = [
                $this->brand_id,
                $game_code,
            ];

            $params['sign'] = $this->generatedSign($sign_params);
        } else {
            $this->CI->external_common_tokens->addPlayerToken($player_id, $token, $this->getPlatformCode(), $this->currency, $by_token_only);
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'extra', $extra);

        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'url' => '',
        ];

        if ($success) {
            $result['url'] = isset($resultArr['data']['game_url']) ? $resultArr['data']['game_url'] : '';
            $this->utils->debug_log(__METHOD__, $resultArr);
        }

        return [$success, $result];
    }

    public function syncOriginalGameLogs($token = false) {
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

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $validated_transaction = [
                    // default
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : Game_logs::STATUS_PENDING,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                    'amount' => !empty($transaction['amount']) ? $transaction['amount'] : 0,
                    'bet_amount' => !empty($transaction['bet_amount']) ? $transaction['bet_amount'] : 0,
                    'win_amount' => !empty($transaction['win_amount']) ? $transaction['win_amount'] : 0,
                    'result_amount' => !empty($transaction['result_amount']) ? $transaction['result_amount'] : 0,
                    'after_balance' => !empty($transaction['after_balance']) ? $transaction['after_balance'] : 0,

                    // additional
                    'wager_type' => !empty($transaction['wager_type']) ? $transaction['wager_type'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');
                $query_transaction_id = null;

                if ($this->enable_merging_rows) {
                    $is_sum = true;
                } else {
                    // $query_transaction_id = $transaction_id;
                    $is_sum = false;
                }

                list($wager_transaction, $wager_table) = $this->queryPlayerTransaction(self::API_METHOD_WAGER, $transaction_player_id, $transaction_game_code, $transaction_round_id, $query_transaction_id, $is_sum);
                list($cancel_wager_transaction, $cancel_wager_table) = $this->queryPlayerTransaction(self::API_METHOD_CANCEL_WAGER, $transaction_player_id, $transaction_game_code, $transaction_round_id, $query_transaction_id, $is_sum);
                list($append_wager_transaction, $append_wager_table) = $this->queryPlayerTransaction(self::API_METHOD_APPEND_WAGER, $transaction_player_id, $transaction_game_code, $transaction_round_id, $query_transaction_id, $is_sum);
                list($end_wager_transaction, $end_wager_table) = $this->queryPlayerTransaction(self::API_METHOD_END_WAGER, $transaction_player_id, $transaction_game_code, $transaction_round_id, $query_transaction_id, $is_sum);
                list($free_spin_result_transaction, $free_spin_result_table) = $this->queryPlayerTransaction(self::API_METHOD_FREE_SPIN_RESULT, $transaction_player_id, $transaction_game_code, $transaction_round_id, $query_transaction_id, $is_sum);
                list($promo_payout_transaction, $promo_payout_table) = $this->queryPlayerTransaction(self::API_METHOD_PROMO_PAYOUT, $transaction_player_id, $transaction_game_code, $transaction_round_id, $query_transaction_id, $is_sum);

                $validated_wager_transaction = [
                    'amount' => !empty($wager_transaction['amount']) ? $wager_transaction['amount'] : 0,
                    'result_amount' => !empty($wager_transaction['result_amount']) ? $wager_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($wager_transaction['external_unique_id']) ? $wager_transaction['external_unique_id'] : null,
                ];

                $validated_cancel_wager_transaction = [
                    'amount' => !empty($cancel_wager_transaction['amount']) ? $cancel_wager_transaction['amount'] : 0,
                    'external_unique_id' => !empty($cancel_wager_transaction['external_unique_id']) ? $cancel_wager_transaction['external_unique_id'] : null,
                    'type' => !empty($cancel_wager_transaction['wager_type']) ? $cancel_wager_transaction['wager_type'] : null,
                ];

                $validated_append_wager_transaction = [
                    'amount' => !empty($append_wager_transaction['amount']) ? $append_wager_transaction['amount'] : 0,
                    'external_unique_id' => !empty($append_wager_transaction['external_unique_id']) ? $append_wager_transaction['external_unique_id'] : null,
                ];

                $validated_end_wager_transaction = [
                    'amount' => !empty($end_wager_transaction['amount']) ? $end_wager_transaction['amount'] : 0,
                    'external_unique_id' => !empty($end_wager_transaction['external_unique_id']) ? $end_wager_transaction['external_unique_id'] : null,
                ];

                $validated_free_spin_result_transaction = [
                    'amount' => !empty($free_spin_result_transaction['amount']) ? $free_spin_result_transaction['amount'] : 0,
                    'external_unique_id' => !empty($free_spin_result_transaction['external_unique_id']) ? $free_spin_result_transaction['external_unique_id'] : null,
                ];

                $validated_promo_payout_transaction = [
                    'amount' => !empty($promo_payout_transaction['amount']) ? $promo_payout_transaction['amount'] : 0,
                    'external_unique_id' => !empty($promo_payout_transaction['external_unique_id']) ? $promo_payout_transaction['external_unique_id'] : null,
                ];

                extract($validated_wager_transaction, EXTR_PREFIX_ALL, 'wager');
                extract($validated_cancel_wager_transaction, EXTR_PREFIX_ALL, 'cancel_wager');
                extract($validated_append_wager_transaction, EXTR_PREFIX_ALL, 'append_wager');
                extract($validated_end_wager_transaction, EXTR_PREFIX_ALL, 'end_wager');
                extract($validated_free_spin_result_transaction, EXTR_PREFIX_ALL, 'free_spin_result');
                extract($validated_promo_payout_transaction, EXTR_PREFIX_ALL, 'promo_payout');

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::API_METHOD_WAGER:
                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => 0,
                                'result_amount' => -$wager_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::API_METHOD_CANCEL_WAGER:
                            $cancel_wager_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => 0,
                                'result_amount' => $cancel_wager_type == 1 ? $cancel_wager_amount : -$cancel_wager_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            if (!$this->enable_merging_rows) {
                                $cancel_wager_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $cancel_wager_data['bet_amount'] = 0;
                                $cancel_wager_data['win_amount'] = 0;
                                $cancel_wager_data['result_amount'] = $transaction_wager_type == 1 ? $transaction_amount : -$transaction_amount;

                                $wager_data['bet_amount'] = $wager_amount;
                                $wager_data['win_amount'] = 0;
                                $wager_data['result_amount'] = -$wager_amount;
                            }

                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $cancel_wager_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $wager_external_unique_id);
                            break;
                        case self::API_METHOD_APPEND_WAGER:
                            $append_wager_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => 0,
                                'result_amount' => $append_wager_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            if (!$this->enable_merging_rows) {
                                $append_wager_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $append_wager_data['bet_amount'] = 0;
                                $append_wager_data['win_amount'] = 0;
                                $append_wager_data['result_amount'] = $transaction_amount;

                                $wager_data['bet_amount'] = $wager_amount;
                                $wager_data['win_amount'] = 0;
                                $wager_data['result_amount'] = -$wager_amount;
                            }

                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $append_wager_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $wager_external_unique_id);
                            break;
                        case self::API_METHOD_END_WAGER:
                            $end_wager_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $wager_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => $end_wager_amount,
                                'result_amount' => $end_wager_amount - $wager_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            if (!$this->enable_merging_rows) {
                                $end_wager_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $end_wager_data['bet_amount'] = 0;
                                $end_wager_data['win_amount'] = $transaction_amount;
                                $end_wager_data['result_amount'] = $transaction_amount;

                                $wager_data['bet_amount'] = $wager_amount;
                                $wager_data['win_amount'] = 0;
                                $wager_data['result_amount'] = -$wager_amount;
                            }

                            $wager_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($wager_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $end_wager_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($wager_table, $wager_data, 'external_unique_id', $wager_external_unique_id);
                            break;
                        case self::API_METHOD_FREE_SPIN_RESULT:
                            $free_spin_result_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $wager_amount,
                                'win_amount' => $free_spin_result_amount,
                                'result_amount' => $free_spin_result_amount - $wager_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            if (!$this->enable_merging_rows) {
                                $free_spin_result_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $free_spin_result_data['bet_amount'] = 0;
                                $free_spin_result_data['win_amount'] = $transaction_amount;
                                $free_spin_result_data['result_amount'] = $transaction_amount;
                            }

                            $free_spin_result_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($free_spin_result_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $free_spin_result_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::API_METHOD_PROMO_PAYOUT:
                            $promo_payout_data = [
                                'status' => $transaction_status,
                                'bet_amount' => 0,
                                'win_amount' => 0,
                                'result_amount' => $promo_payout_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            if (!$this->enable_merging_rows) {
                                $promo_payout_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $promo_payout_data['bet_amount'] = 0;
                                $promo_payout_data['win_amount'] = $transaction_amount;
                                $promo_payout_data['result_amount'] = $transaction_amount;
                            }

                            $promo_payout_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($promo_payout_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $promo_payout_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        default:
                            break;
                    }
                }

                $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'transaction_start_at', $transaction_start_at, 'transaction_updated_at', $transaction_updated_at, 'wager_table', $wager_table);
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result, 'table', $this->original_seamless_wallet_transactions_table);

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
transaction_type,
transaction_id,
game_code,
round_id,
amount,
start_at,
status,
request,
external_unique_id,
updated_at,
bet_amount,
win_amount,
result_amount,
promotion_id,
after_balance,
wager_type
FROM {$transaction_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND wallet_adjustment_status NOT IN ('failed', 'preserved') AND {$sqlTime} {$and_transaction_type}
EOD;

        if (!empty($transaction_type)) {
            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                $dateFrom,
                $dateTo,
                $transaction_type,
            ];
        } else {
            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                $dateFrom,
                $dateTo
            ];
        }

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null, $is_sum = true) {
        $and_transaction_id = !empty($transaction_id) ? "AND transaction_id = ?" : '';

        $table_names = [$this->original_seamless_wallet_transactions_table];

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        $result = [];
        $from_table = null;

        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $win_amount = $is_sum ? 'SUM(win_amount) as win_amount' : 'win_amount';
        $result_amount = $is_sum ? 'SUM(result_amount) as result_amount' : 'result_amount';

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
wager_type
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND round_id = ? AND wallet_adjustment_status NOT IN ('failed', 'preserved') {$and_transaction_id}
EOD;

            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $round_id,
            ];

            if (!empty($transaction_id)) {
                array_push($params, $transaction_id);
            }

            // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
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
WHERE game_platform_id = ? AND flag_of_updated_result != ? AND transaction_type != '' AND wallet_adjustment_status NOT IN ('failed', 'preserved') AND {$sqlTime}
EOD;

            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                $dateFrom,
                $dateTo,
            ];
    
            $transactions = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    
            $results = [];
            foreach ($transactions as $transaction) {
                $wager_transaction = $this->queryTransactionForMergeGameLogs(self::API_METHOD_WAGER, $transaction['player_id'], $transaction['game_code'], $transaction['round_id']);
    
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

    public function queryTransactionForMergeGameLogs($transaction_type, $player_id, $game_code, $round_id) {
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
transaction.transaction_type,
transaction.status,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.extra_info,
transaction.request,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$table_name} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.transaction_type = ? AND transaction.player_id = ? 
AND transaction.game_code = ? AND transaction.round_id = ? AND transaction.wallet_adjustment_status NOT IN ('failed', 'preserved')
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            $transaction_type,
            $player_id,
            $game_code,
            $round_id,
        ];

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
transaction.transaction_type,
transaction.status,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.extra_info,
transaction.request,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$table_name} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.wallet_adjustment_status NOT IN ('failed', 'preserved') AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
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

        $bet_details = json_decode($row['request']);
        $bet_details->win_amount = $row['result_amount'] > 0 ? $row['result_amount'] : 0;

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
            'status' => !empty($row['status']) ? $row['status'] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => !empty($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => !empty($row['round_id']) ? $row['round_id'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            // 'bet_details' => $this->preprocessBetDetails($row),
            'bet_details' => $this->formatBetDetails($bet_details),
            'extra' => [
                'note' => !empty($row['note']) ? $row['note'] : null,
                'odds' =>  isset($row['odds']) ? $row['odds'] : null,
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        //$this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

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
        $status = !empty($row['status']) ? $row['status'] : null;

        if (!empty($row['extra_info']) && !is_array($row['extra_info'])) {
            $row['extra_info'] = json_decode($row['extra_info'], true);
        }

        if ($this->enable_merging_rows) {
            $row['after_balance'] = isset($row['extra_info']['after_balance']) ? $row['extra_info']['after_balance'] : null;
        }

        $row['note'] = $this->getResult($status, $result_amount, $row);
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

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['transaction_id'])) {
            $bet_details['transaction_id'] = $row['transaction_id'];
        }

        if (isset($row['game_code'])) {
            $bet_details['game_code'] = $row['game_code'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        return $bet_details;
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
transaction.player_id as player_id,
transaction.start_at as transaction_date,
transaction.amount as amount,
transaction.after_balance,
transaction.before_balance,
transaction.result_amount,
transaction.round_id as round_no,
transaction.transaction_id,
transaction.external_unique_id as external_uniqueid,
transaction.transaction_type as trans_type,
transaction.updated_at,
MD5(CONCAT({$md5_fields})) as md5_sum,
transaction.request,
transaction.wager_type
FROM {$this->original_seamless_wallet_transactions_table} as transaction
WHERE transaction.game_platform_id = ? AND transaction.wallet_adjustment_status NOT IN ('failed', 'preserved') {$query_time}
ORDER BY transaction.updated_at asc, transaction.id asc;
EOD;

        $params = [
            $this->getPlatformCode(),
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

                if ($transaction['trans_type'] == self::API_METHOD_WAGER) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                if ($transaction['trans_type'] == self::API_METHOD_CANCEL_WAGER && $transaction['wager_type'] == 2) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function formatBetDetails($extra_info){
        $bet_details = [];
        if($extra_info){
            $bet_details = [
                'bet_amount'    => isset($extra_info->amount) ? $extra_info->amount : 0,
                'win_amount'    => $extra_info->win_amount,
                'round_id'      => $extra_info->round_id,
                'game_name'     => isset($extra_info->game_name) ?  $extra_info->game_name : null,
                'others'        => $extra_info,
            ];
        }
        return $bet_details;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getTransactionsTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }
        $STATUS_PENDING = Game_logs::STATUS_PENDING;
        $STATUS_ACCEPTED = Game_logs::STATUS_ACCEPTED;

        $sqlTime="DCU.created_at >= ? AND DCU.created_at <= ? AND DCU.transaction_type = ? AND DCU.status in ('{$STATUS_PENDING}', '{$STATUS_ACCEPTED}')";
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
DCU.round_id as round_id, 
DCU.transaction_id as transaction_id, 
DCU.created_at as transaction_date,
DCU.external_unique_id as external_uniqueid,
DCU.player_id,
DCU.transaction_type,
DCU.amount,
DCU.amount as deducted_amount,
0 as added_amount,
DCU.game_platform_id as game_platform_id,
gd.id as game_description_id,
gd.game_type_id

from {$original_transactions_table} as DCU
LEFT JOIN game_description as gd ON DCU.game_code = gd.external_game_id and gd.game_platform_id = ?
where
{$sqlTime}
EOD;

        $transaction_type = "wager";
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $transaction_type,

        ];
        $this->CI->utils->debug_log('==> DCU getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $original_transactions_table = $this->getTransactionsTable();
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
                    $this->CI->utils->error_log('DCU SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $original_transactions_table = $this->getTransactionsTable();
        $row = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($original_transactions_table, ['external_unique_id'=> $external_uniqueid]);
        if(!empty($row)){
            return array('success'=>true, 'status'=> $row['status']);
        }
        
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }
}
//end of class