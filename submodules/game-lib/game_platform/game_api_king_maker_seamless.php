<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: KingMaker
 * Game Type: Live Table
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -king_maker_seamless_service_api.php
 **/

class Game_api_king_maker_seamless extends Abstract_game_api {
    use Year_month_table_module;

    // default
    public $http_method;
    public $api_url;
    public $language;
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

    // additional
    public $brand_code;
    public $client_id;
    public $client_secret;
    public $game_url;
    public $gpcode;
    public $bet_limit_id;
    public $login_url;
    public $cashier_url;
    public $is_lobby;
    public $lobby_path_desktop;
    public $lobby_path_mobile;
    public $platform_type;
    public $test_player = [];

    const SEAMLESS_GAME_API_NAME = 'KING_MAKER_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCESS = 0;

    const API_METHOD_DEBIT = 'debit';
    const API_METHOD_CREDIT = 'credit';
    const API_METHOD_REWARD = 'reward';

    const TRANSACTION_TYPE_PLACE_BET = 'placeBet';
    const TRANSACTION_TYPE_WIN_BET = 'winBet';
    const TRANSACTION_TYPE_WIN_JACKPOT = 'winJackpot';
    const TRANSACTION_TYPE_LOSE_BET = 'loseBet';
    const TRANSACTION_TYPE_FREE_BET = 'freeBet';
    const TRANSACTION_TYPE_TIE_BET = 'tieBet';
    const TRANSACTION_TYPE_CANCEL_TRANSACTION = 'cancelTransaction';
    const TRANSACTION_TYPE_END_ROUND = 'endRound';
    const TRANSACTION_TYPE_FUND_IN = 'fundIn';
    const TRANSACTION_TYPE_FUND_OUT = 'fundOut';
    const TRANSACTION_TYPE_CANCEL_FUND_OUT = 'cancelFundOut';

    const IS_PROCESSED = 1;

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const API_authorize = 'authorize';
    const API_queryForwardGameDemo = 'queryForwardGameDemo';

    const URI_MAP = [
        self::API_authorize => '/api/player/authorize',
        self::API_queryGameListFromGameProvider => '/api/games',
        self::API_queryForwardGame => '/gamelauncher',
        self::API_queryForwardGameDemo => '/demolauncher',
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

    const LOBBY = 'lobby';

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);

        // default
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url');
        $this->language = $this->getSystemInfo('language');
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

        // conversions
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', '');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', $this->arithmetic_name);

        // default tables
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'king_maker_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'king_maker_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'king_maker_seamless_service_logs');

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
        $this->brand_code = $this->getSystemInfo('brand_code');
        $this->client_id = $this->getSystemInfo('client_id');
        $this->client_secret = $this->getSystemInfo('client_secret');
        $this->game_url = $this->getSystemInfo('game_url');
        $this->gpcode = $this->getSystemInfo('gpcode', 'KMQM');
        $this->bet_limit_id = $this->getSystemInfo('bet_limit_id', 1);
        $this->login_url = $this->getSystemInfo('login_url', $this->getHomeLink());
        $this->cashier_url = $this->getSystemInfo('cashier_url', '');
        $this->lobby_path_desktop = $this->getSystemInfo('lobby_path_desktop', '/T1SW_KM_Desktop');
        $this->lobby_path_mobile = $this->getSystemInfo('lobby_path_mobile', '/T1SW_KM_Mobile');
        $this->test_player = $this->getSystemInfo('test_player', []);
    }

    public function getPlatformCode() {
        return KING_MAKER_SEAMLESS_GAME_API;
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
        $url = $this->api_url;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryForwardGame:
                case self::API_queryForwardGameDemo:
                    if ($this->is_lobby) {
                        $api_uri = $this->platform_type ? $this->lobby_path_mobile : $this->lobby_path_desktop;
                    }

                    $url = $this->game_url . '/' . ltrim($api_uri, '/') . '?' . http_build_query($params);
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
            'X-QM-ClientId' => $this->client_id,
            'X-QM-ClientSecret' => $this->client_secret,
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
            $this->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
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

    public function queryGameListFromGameProvider($extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $params = array(
            'lang' => $this->language,
            'platform' => 0
        );

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = isset($resultArr['games']) ? $resultArr['games'] : [];
        }

        return array($success, $result);
    }

    public function authorize($params) {
        $this->http_method = self::HTTP_METHOD_POST;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForAuthorize',
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_authorize, $params, $context);
    }

    public function processResultForAuthorize($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [];

        if ($success) {
            $result = $resultArr;
            $this->utils->debug_log(__METHOD__, $resultArr);
        }

        return [$success, $result];
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
            'hi_in' => 'en-US',
            'pt_pt' => 'pt-PT',
            'es_es' => 'es-ES',
            'kk_kz' => 'kk-KZ',
            'pt_br' => 'pt-BR',
        ]);
    }

    public function queryForwardGame($player_name, $extra = null) {
        $this->CI->load->model('external_common_tokens');
        /* $player_id = $this->getPlayerIdFromUsername($player_name);
        $token = $this->getPlayerToken($player_id); */
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
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

        if ($is_mobile) {
            $this->platform_type = 1;
        } else {
            $this->platform_type = 0;
        }

        if (!empty($extra['home_link'])) {
            $login_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $login_url = $extra['extra']['home_link'];
        } else {
            $login_url = $this->login_url;
        }

        if (!empty($extra['cashier_link'])) {
            $cashier_url = $extra['cashier_link'];
        } elseif (!empty($extra['extra']['cashier_link'])) {
            $cashier_url = $extra['extra']['cashier_link'];
        } else {
            $cashier_url = $this->cashier_url;
        }

        /* if (strpos($player_name, 'test') !== false) {
            $is_test_player = true;
        } else {
            $is_test_player = false;
        } */

        if (in_array($player_name, $this->test_player)) {
            $is_test_player = true;
        } else {
            $is_test_player = false;
        }

        $auth_params = [
            'ipaddress' => $this->CI->utils->getIP(),
            'username' => $game_username,
            'userid' => $game_username,
            'lang' => $language,
            'cur' => $this->currency,
            'betlimitid' => $this->bet_limit_id,
            'istestplayer' => $is_test_player,
            'platformtype' => $this->platform_type,
            'loginurl' => $login_url,
            'cashierurl' => $cashier_url,
        ];

        $auth_result = $this->authorize($auth_params);
        $success = isset($auth_result['success']) && $auth_result['success'] ? true : false;
        $auth_token = isset($auth_result['authtoken']) ? $auth_result['authtoken'] : null;

        $this->is_lobby = false;

        $game_launch_params = [
            'gpcode' => $this->gpcode,
            'token' => $auth_token,
            'lang' => $language,
            'gcode' => $game_code,
        ];

        if (empty($game_code) || $game_code == self::LOBBY) {
            unset($game_launch_params['gcode']);
            $this->is_lobby = true;
        }

        if ($is_demo_mode) {
            $api_name = self::API_queryForwardGameDemo;
            unset($game_launch_params['token']);
        } else {
            $api_name = self::API_queryForwardGame;
        }

        $this->http_method = self::HTTP_METHOD_GET;
        $url = $this->generateUrl($api_name, $game_launch_params);

        $result = [
            'success' => $success,
            'url' => $url,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'auth_params', $auth_params, 'game_launch_params', $game_launch_params, 'result', $result);

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
                    'bet_id' => !empty($transaction['betid']) ? $transaction['betid'] : null,
                    'external_bet_id' => !empty($transaction['externalbetid']) ? $transaction['externalbetid'] : null,
                    'external_round_id' => !empty($transaction['externalroundid']) ? $transaction['externalroundid'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                // main
                list($place_bet_transaction, $place_bet_table) = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_PLACE_BET, $transaction_player_id, $transaction_game_code, $transaction_bet_id, $transaction_round_id);

                $validated_place_bet_transaction = [
                    'amount' => !empty($place_bet_transaction['amount']) ? $place_bet_transaction['amount'] : 0,
                    'real_bet_amount' => !empty($place_bet_transaction['bet_amount']) ? $place_bet_transaction['bet_amount'] : 0,
                    'real_win_amount' => !empty($place_bet_transaction['win_amount']) ? $place_bet_transaction['win_amount'] : 0,
                    'result_amount' => !empty($place_bet_transaction['result_amount']) ? $place_bet_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($place_bet_transaction['external_unique_id']) ? $place_bet_transaction['external_unique_id'] : null,
                    'end_at' => !empty($place_bet_transaction['end_at']) ? $place_bet_transaction['end_at'] : null,
                    'extra_info' => !empty($place_bet_transaction['extra_info']) ? json_decode($place_bet_transaction['extra_info'], true) : [],
                ];

                extract($validated_place_bet_transaction, EXTR_PREFIX_ALL, 'place_bet');

                list($player_sum_transaction, $place_bet_table) = $this->queryPlayerSumTransaction($transaction_player_id, $transaction_bet_id, $transaction_round_id);

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::TRANSACTION_TYPE_PLACE_BET:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $place_bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $transaction_amount,
                                'win_amount' => 0,
                                'result_amount' => -$transaction_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_WIN_BET:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;
                            $win_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $place_bet_amount,
                                'win_amount' => $player_sum_transaction['total_win_amount'],
                                'result_amount' => $player_sum_transaction['total_win_amount'] - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $transaction_bet_id);
                            break;
                        case self::TRANSACTION_TYPE_LOSE_BET:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $lose_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $place_bet_amount,
                                 'win_amount' => $player_sum_transaction['total_win_amount'],
                                'result_amount' => $player_sum_transaction['total_win_amount'] - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $lose_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $place_bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_FREE_BET:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $free_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $place_bet_amount,
                                'win_amount' => $player_sum_transaction['total_win_amount'],
                                'result_amount' => $player_sum_transaction['total_win_amount'] - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $free_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $transaction_bet_id);
                            break;
                        case self::TRANSACTION_TYPE_CANCEL_TRANSACTION:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $cancel_transaction_data = [
                                'status' => Game_logs::STATUS_CANCELLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                                'externalroundid' => 'cancelled-' . $transaction_external_round_id,
                            ];

                            $place_bet_data = [
                                'status' => Game_logs::STATUS_CANCELLED,
                                'bet_amount' => $place_bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $transaction_amount - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => 'cancelled-' . $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $cancel_transaction_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $place_bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_WIN_JACKPOT:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $win_jackpot_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $place_bet_amount,
                                'win_amount' => $transaction_amount,
                                'result_amount' => $transaction_amount - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_jackpot_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $place_bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_TIE_BET:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $tie_bet_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $place_bet_amount,
                                'win_amount' => $transaction_amount,
                                'result_amount' => $transaction_amount - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $tie_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $place_bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_END_ROUND:
                            $place_bet_extra_info['after_balance'] = $transaction_after_balance;

                            $end_round_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $place_bet_amount,
                                'win_amount' => $transaction_amount,
                                'result_amount' => $transaction_amount - $place_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $transaction_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                                'externalroundid' => $transaction_external_round_id,
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $end_round_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $place_bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_FUND_OUT:
                        case self::TRANSACTION_TYPE_FUND_IN:
                        case self::TRANSACTION_TYPE_CANCEL_FUND_OUT:
                            if ($transaction_type == self::TRANSACTION_TYPE_FUND_OUT) {
                                $place_bet_extra_info['before_balance'] = $transaction_before_balance;
                            } else {
                                $place_bet_extra_info['after_balance'] = $transaction_after_balance;
                            }

                            $fund_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $place_bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $place_bet_real_bet_amount,
                                'win_amount' => $place_bet_real_win_amount,
                                'result_amount' => $place_bet_result_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'end_at' => $place_bet_end_at,
                                'extra_info' => json_encode($place_bet_extra_info),
                            ];

                            $place_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($place_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $fund_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($place_bet_table, $place_bet_data, 'external_unique_id', $place_bet_external_unique_id);
                            break;
                        default:
                            break;
                    }
                }

                $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'transaction_start_at', $transaction_start_at, 'transaction_updated_at', $transaction_updated_at);
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->utils->info_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

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
betid,
externalbetid,
externalroundid
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
            $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        }

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $bet_id, $round_id) {
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
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND betid = ? AND round_id = ? AND is_processed = ?
EOD;

            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $bet_id,
                $round_id,
                self::IS_PROCESSED,
            ];

            if ($this->show_game_logs) {
                $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
            }

            $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

            if (!empty($result['id'])) {
                break;
            }
        }

        return array($result, $from_table);
    }

    public function queryPlayerSumTransaction($player_id, $bet_id, $round_id) {
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
SUM(amount) AS total_win_amount
FROM {$table_name}
WHERE game_platform_id = ? AND player_id = ? AND betid = ? AND round_id = ? AND is_processed = ? AND wallet_adjustment_status = ?
EOD;


            $params = [
                $this->getPlatformCode(),
                $player_id,
                $bet_id,
                $round_id,
                self::IS_PROCESSED,
                'increased',
            ];

            if ($this->show_game_logs) {
                $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
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
externalbetid,
externalroundid
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
            $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        }

        $transactions = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        $results = [];
        foreach ($transactions as $transaction) {
            $place_bet_transaction = $this->queryPlaceBetTransactionForGameLogs(self::TRANSACTION_TYPE_PLACE_BET, $transaction['player_id'], $transaction['game_code'], $transaction['externalbetid']);

            if (!empty($place_bet_transaction['external_uniqueid'])) {
                $results[$place_bet_transaction['external_uniqueid']] = $place_bet_transaction;
            }
        }

        $results = array_values($results);

        return $results;
    }

    public function queryPlaceBetTransactionForGameLogs($transaction_type, $player_id, $game_code, $external_bet_id) {
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
transaction.extra_info,
transaction.externalbetid,
transaction.externalroundid,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$table_name} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.is_processed = ?
AND transaction.transaction_type = ? AND transaction.player_id = ? AND transaction.game_code = ? AND transaction.externalbetid = ?
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            self::IS_PROCESSED,
            $transaction_type,
            $player_id,
            $game_code,
            $external_bet_id,
        ];

        if ($this->show_game_logs) {
            $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
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
        list($row['status'], $row['after_balance']) = $this->getStatusAndAfterBalance($row['player_id'], $row['game_code'], $row['externalroundid'], $row['status'], $row['after_balance']);

        if (!empty($row['extra_info']) && !is_array($row['extra_info'])) {
            $row['extra_info'] = json_decode($row['extra_info'], true);
        }

        // $row['after_balance'] = isset($row['extra_info']['after_balance']) ? $row['extra_info']['after_balance'] : null;
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

    public function getStatusAndAfterBalance($player_id, $game_code, $external_round_id, $status, $after_balance) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $settled = Game_logs::STATUS_SETTLED;

        $where = [
            'player_id' => $player_id,
            'game_code' => $game_code,
            'externalroundid' => $external_round_id,
            'isclosinground' => $settled,
        ];

        $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, $where);

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                if (empty($transaction)) {
                    $transaction = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, $where);
                }
            }
        }

        $status = isset($transaction['isclosinground']) && $transaction['isclosinground'] ? $settled : $status;
        $after_balance = isset($transaction['after_balance']) ? $transaction['after_balance'] : $after_balance;

        return array($status, $after_balance);
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

        if (isset($row['externalbetid'])) {
            $bet_details['externalbetid'] = $row['externalbetid'];
        }

        if (isset($row['externalroundid'])) {
            $bet_details['externalroundid'] = $row['externalroundid'];
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

                if (in_array($transaction['trans_type'], [self::TRANSACTION_TYPE_PLACE_BET, self::TRANSACTION_TYPE_FUND_OUT])) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $original_transactions_table = $this->getSeamlessTransactionTable();
        if(!$original_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }
        $STATUS_PENDING = Game_logs::STATUS_PENDING;
        $STATUS_ACCEPTED = Game_logs::STATUS_ACCEPTED;

        $sqlTime="king.created_at >= ? AND king.created_at <= ? AND king.transaction_type = ? AND king.status in ('{$STATUS_PENDING}', '{$STATUS_ACCEPTED}')";
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
king.round_id as round_id, 
king.transaction_id as transaction_id, 
king.created_at as transaction_date,
king.external_unique_id as external_uniqueid,
king.player_id,
king.transaction_type,
king.amount,
king.amount as deducted_amount,
0 as added_amount,
king.game_platform_id as game_platform_id,
gd.id as game_description_id,
gd.game_type_id

from {$original_transactions_table} as king
LEFT JOIN game_description as gd ON king.game_code = gd.external_game_id and gd.game_platform_id = ?
where
{$sqlTime}
EOD;

        $transaction_type = "placeBet";
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $transaction_type,

        ];
        $this->CI->utils->debug_log('==> king getUnsettledRounds sql', $sql, $params);
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
                    $this->CI->utils->error_log('king SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
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