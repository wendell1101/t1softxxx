<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: Betgames
 * Game Type: Live Dealer
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -betgames_seamless_service_api.php
 **/

class Game_api_betgames_seamless extends Abstract_game_api {
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
    public $show_game_logs;
    public $use_monthly_service_logs_table;

    public $seamless_game_api_name = 'BETGAMES_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCESS = 0;

    const IS_PROCESSED = 1;

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const URI_MAP = [
        self::API_queryForwardGame => '/auth',
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
    public $provider;
    public $client_url;
    public $partner_code;
    public $secret_key;
    public $timezone;
    public $odds_format;
    public $token_timeout_seconds;
    public $force_refresh_token_timeout;
    public $get_token_test_accounts;
    public $home_url;

    # TRANSACTION TYPES
    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_CREDIT = 'credit';

    # API METHODS HERE
    const API_METHOD_PING = 'ping';
    const API_METHOD_GET_ACCOUNT_DETAILS = 'get_account_details';
    const API_METHOD_REFRESH_TOKEN = 'refresh_token';
    const API_METHOD_REQUEST_NEW_TOKEN = 'request_new_token';
    const API_METHOD_GET_BALANCE = 'get_balance';
    const API_METHOD_TRANSACTION_BET_PAYIN = 'transaction_bet_payin';
    const API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN = 'transaction_bet_subscription_payin';
    const API_METHOD_TRANSACTION_BET_PAYOUT = 'transaction_bet_payout';
    const API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN = 'transaction_bet_combination_payin';
    const API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT = 'transaction_bet_combination_payout';
    const API_METHOD_TRANSACTION_PROMO_PAYOUT = 'transaction_promo_payout';
    const API_METHOD_TRANSACTION_BET_MULTI_PAYIN = 'transaction_bet_multi_payin';

    const BETGAMES_LOBBY = '-1';

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
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->get_usec = $this->getSystemInfo('get_usec', true);
        $this->use_bet_time = $this->getSystemInfo('use_bet_time', true);
        $this->show_hint = $this->getSystemInfo('show_hint', false);
        $this->show_game_logs = $this->getSystemInfo('show_game_logs', false);
        $this->allow_launch_demo_without_authentication = $this->getSystemInfo('allow_launch_demo_without_authentication', true);

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');

        // default tables
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'betgames_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'betgames_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'betgames_seamless_service_logs');

        // start monthly tables
        $this->initialize_monthly_transactions_table = $this->getSystemInfo('initialize_monthly_transactions_table', true);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->use_monthly_service_logs_table = $this->getSystemInfo('use_monthly_service_logs_table', true);

        $this->ymt_initialize($this->original_seamless_wallet_transactions_table, $this->use_monthly_transactions_table ? $this->use_monthly_transactions_table : $this->initialize_monthly_transactions_table);

        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_current_year_month_table();
            $this->previous_table = $this->ymt_get_previous_year_month_table();
        }

        if ($this->use_monthly_service_logs_table) {
            $this->game_seamless_service_logs_table = $this->ymt_get_current_year_month_table($this->game_seamless_service_logs_table);
        }
        // end monthly tables

        // additional
        $this->provider = $this->getSystemInfo('provider', 'betgames');
        $this->client_url = $this->getSystemInfo('client_url', '');
        $this->partner_code = $this->getSystemInfo('partner_code', '');
        $this->secret_key = $this->getSystemInfo('secret_key', '');
        $this->timezone = $this->getSystemInfo('timezone', '0');
        $this->odds_format = $this->getSystemInfo('odds_format', 'decimal');
        $this->token_timeout_seconds = $this->getSystemInfo('token_timeout_seconds', 3600); // 1 minute (60), 1 hour (3600)
        $this->force_refresh_token_timeout = $this->getSystemInfo('force_refresh_token_timeout', false);
        $this->get_token_test_accounts = $this->getSystemInfo('get_token_test_accounts', []);
        $this->home_url = $this->getSystemInfo('home_url', $this->utils->getUrl());
    }

    public function getPlatformCode() {
        return BETGAMES_SEAMLESS_GAME_API;
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

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->client_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryForwardGame:
                    $url = ltrim($this->client_url, '/') . '/' . ltrim($api_uri, '/') . '?' . http_build_query($params);
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

    public function getHttpHeaders($params) {
        $headers = [
            'Content-type' => 'application/json',
            'Accept' => 'application/json',
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

        if (!isset($resultArr['err']) || $resultArr['err'] == self::RESPONSE_CODE_SUCCESS) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log($this->seamless_game_api_name . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
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
            'zh_cn' => 'zh',
            'id_id' => 'id',
            'vi_vn' => 'vi',
            'ko_kr' => 'ko',
            'th_th' => 'th',
            'hi_in' => 'hi',
            'pt_pt' => 'pt',
            'es_es' => 'es',
            'kk_kz' => 'kk',
            'pt_br' => 'pt-br',
            'ja_jp' => 'jp',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->CI->load->model(['common_token']);

        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        if (!empty($player_name) && !$is_demo_mode) {
            $player_id = $this->getPlayerIdFromUsername($player_name);
            $token = $this->getPlayerToken($player_id);
            $this->CI->common_token->updatePlayerToken($player_id, $token, $this->token_timeout_seconds);
        } else {
            $token = "-";
        }

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

        if (!empty($extra['home_link'])) {
            $home_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $home_url = $extra['extra']['home_link'];
        } else {
            $home_url = $this->home_url;
        }

        if (!empty($extra['cashier_link'])) {
            $cashier_url = $extra['cashier_link'];
        } elseif (!empty($extra['extra']['cashier_link'])) {
            $cashier_url = $extra['extra']['cashier_link'];
        } else {
            $cashier_url = '';
        }

        $params = [
            'partnerCode' => $this->partner_code,
            'token' => $token,
            'locale' => $language,
            'oddsFormat' => $this->odds_format,
            'homeUrl' => $home_url,
        ];

        if (!empty($game_code)) {
            $params['gameId'] = $game_code;
        }

        if (isset($this->timezone)) {
            $params['timezone'] = $this->timezone;
        }


        $this->http_method = self::HTTP_METHOD_GET;
        $url = $this->generateUrl(self::API_queryForwardGame, $params);

        $result = [
            'url' => $url,
            'params' => $params,
        ];

        return $result;
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
                    'bet_id' => !empty($transaction['bet_id']) ? $transaction['bet_id'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                list($debit_transaction, $debit_table) = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_DEBIT, $transaction_player_id, $transaction_game_code, $transaction_bet_id);
                list($credit_transaction, $credit_table) = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_CREDIT, $transaction_player_id, $transaction_game_code, $transaction_bet_id);

                $validated_debit_transaction = [
                    'amount' => !empty($debit_transaction['amount']) ? $debit_transaction['amount'] : 0,
                    'real_bet_amount' => !empty($debit_transaction['bet_amount']) ? $debit_transaction['bet_amount'] : 0,
                    'real_win_amount' => !empty($debit_transaction['win_amount']) ? $debit_transaction['win_amount'] : 0,
                    'result_amount' => !empty($debit_transaction['result_amount']) ? $debit_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($debit_transaction['external_unique_id']) ? $debit_transaction['external_unique_id'] : null,
                    'end_at' => !empty($debit_transaction['end_at']) ? $debit_transaction['end_at'] : null,
                    'extra_info' => !empty($debit_transaction['extra_info']) ? json_decode($debit_transaction['extra_info'], true) : [],
                ];

                $validated_credit_transaction = [
                    'amount' => !empty($credit_transaction['amount']) ? $credit_transaction['amount'] : 0,
                    'real_bet_amount' => !empty($credit_transaction['bet_amount']) ? $credit_transaction['bet_amount'] : 0,
                    'real_win_amount' => !empty($credit_transaction['win_amount']) ? $credit_transaction['win_amount'] : 0,
                    'result_amount' => !empty($credit_transaction['result_amount']) ? $credit_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($credit_transaction['external_unique_id']) ? $credit_transaction['external_unique_id'] : null,
                    'end_at' => !empty($credit_transaction['end_at']) ? $credit_transaction['end_at'] : null,
                    'extra_info' => !empty($credit_transaction['extra_info']) ? json_decode($credit_transaction['extra_info'], true) : [],
                ];

                extract($validated_debit_transaction, EXTR_PREFIX_ALL, 'debit');
                extract($validated_credit_transaction, EXTR_PREFIX_ALL, 'credit');

                if (array_key_exists('api_method', $transaction)) {
                    switch ($transaction_api_method) {
                        case self::API_METHOD_TRANSACTION_BET_PAYIN:
                        case self::API_METHOD_TRANSACTION_BET_SUBSCRIPTION_PAYIN:
                        case self::API_METHOD_TRANSACTION_BET_MULTI_PAYIN:
                        case self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN:
                            $extra_info['after_balance'] = $transaction_after_balance;

                            $debit_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $transaction_amount,
                                'win_amount' => 0,
                                'result_amount' => -$transaction_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($extra_info),
                            ];

                            $debit_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($debit_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($debit_table, $debit_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::API_METHOD_TRANSACTION_BET_PAYOUT:
                        case self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYOUT:
                        case self::API_METHOD_TRANSACTION_PROMO_PAYOUT:
                            if ($transaction_amount > 0) {
                                $extra_info['after_balance'] = $transaction_after_balance;
                            }

                            $credit_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $debit_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $debit_amount,
                                'win_amount' => $transaction_amount,
                                'result_amount' => $transaction_amount - $debit_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($extra_info),
                            ];

                            $debit_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($debit_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $credit_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($debit_table, $debit_data, 'external_unique_id', $debit_external_unique_id);
                            break;
                        default:
                            break;
                    }
                }

                $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'transaction_start_at', $transaction_start_at, 'transaction_updated_at', $transaction_updated_at);
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
bet_id
FROM {$transaction_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND is_processed = ? AND {$sqlTime} {$and_transaction_type}
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

        if ($this->show_game_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $bet_id) {
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
SELECT DISTINCT 
player_id,
id,
SUM(amount) AS amount,
status,
request,
external_unique_id,
SUM(bet_amount) AS bet_amount,
SUM(win_amount) AS win_amount,
SUM(result_amount) AS result_amount,
end_at,
extra_info
FROM {$table_name}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND bet_id = ? AND is_processed = ?
EOD;

            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $bet_id,
                self::IS_PROCESSED,
            ];

            if ($this->show_game_logs) {
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

        $sql = <<<EOD
SELECT
player_id,
round_id,
game_code,
bet_id
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result != ? AND is_processed = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            self::FLAG_NOT_UPDATED,
            self::IS_PROCESSED,
            $dateFrom,
            $dateTo,
        ];

        if ($this->show_game_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $transactions = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        $results = [];
        foreach ($transactions as $transaction) {
            $debit_transaction = $this->queryPlaceBetTransactionForGameLogs(self::TRANSACTION_TYPE_DEBIT, $transaction['player_id'], $transaction['game_code'], $transaction['bet_id']);

            if (!empty($debit_transaction['sync_index'])) {
                $results[$debit_transaction['external_uniqueid']] = $debit_transaction;
            }
        }

        $results = array_values($results);

        return $results;
    }

    public function queryPlaceBetTransactionForGameLogs($transaction_type, $player_id, $game_code, $bet_id) {
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
transaction.bet_id,
transaction.subscription_id,
transaction.combination_id,
transaction.promo_transaction_id,
transaction.promo_type,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$table_name} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ?
AND transaction.transaction_type = ? AND transaction.player_id = ? AND transaction.game_code = ? AND transaction.bet_id = ?
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            self::IS_PROCESSED,
            $transaction_type,
            $player_id,
            $game_code,
            $bet_id,
        ];

        if ($this->show_game_logs) {
            $this->utils->debug_log(__METHOD__, $this->seamless_game_api_name, 'sql', $sql, 'params', $params);
        }

        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if (!empty($result['sync_index'])) {
            break;
        }
    }

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

        $row['after_balance'] = isset($row['extra_info']['after_balance']) ? $row['extra_info']['after_balance'] : $row['after_balance'];
        $row['note'] = $this->getResult($row['status'], $result_amount);
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

    public function getAfterBalance($player_id, $game_code, $bet_id, $after_balance) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $where = [
            'player_id' => $player_id,
            'game_code' => $game_code,
            'bet_id' => $bet_id
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

    public function getResult($status, $result_amount) {
        $result = 'Unknown';

        if ($status == Game_logs::STATUS_SETTLED) {
            if ($result_amount > 0) {
                $result = 'Win';
            } elseif ($result_amount < 0) {
                $result = 'Lose';
            } elseif ($result_amount == 0) {
                $result = 'Draw';
            } else {
                $result = 'Free Game';
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
        $request = !empty($row['request']) ? json_decode($row['request'], true) : [];
        $params = !empty($request['params']) ? $request['params'] : [];
        $bet_params = !empty($params['bet']) ? $params['bet'] : [];

        if (isset($row['transaction_id'])) {
            $bet_details['transaction_id'] = $row['transaction_id'];
        }

        if (isset($row['game_code'])) {
            $bet_details['game_code'] = $row['game_code'];
        }

        if (isset($row['game_name'])) {
            $bet_details['game_name'] = $row['game_name'];
        }

        if (isset($row['bet_id'])) {
            $bet_details['bet_id'] = $row['bet_id'];
        }

        if (isset($row['subscription_id'])) {
            $bet_details['subscription_id'] = $row['subscription_id'];
        }

        if (isset($row['promo_transaction_id'])) {
            $bet_details['promo_transaction_id'] = $row['promo_transaction_id'];
        }

        if (isset($row['promo_type'])) {
            $bet_details['promo_type'] = $row['promo_type'];
        }

        if (!empty($bet_params) && $row['api_method'] == self::API_METHOD_TRANSACTION_BET_COMBINATION_PAYIN) {
            $bet_details = [];

            if (isset($row['combination_id'])) {
                $bet_details['combination_id'] = $row['combination_id'];
            }

            if (!empty($bet_params)) {
                $bet_details['bet'] = $bet_params;

                foreach ($bet_details['bet'] as $key => $value) {
                    unset($bet_details['bet'][$key]['odd'], $bet_details['bet'][$key]['draw'], $bet_details['bet'][$key]['game']);
                }
            } else {

            }
            
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
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? {$query_time}
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

    public function preprocessBetDetails($row, $game_type = null, $use_default = false) {
        $bet_details = parent::preprocessBetDetails($row, $game_type, $use_default);

        if (isset($row['subscription_id'])) {
            $bet_details['subscription_id'] = $row['subscription_id'];
        }

        if (isset($row['combination_id'])) {
            $bet_details['combination_id'] = $row['combination_id'];
        }

        if (isset($row['promo_transaction_id'])) {
            $bet_details['promo_transaction_id'] = $row['promo_transaction_id'];
        }

        return $bet_details;
    }
}
//end of class