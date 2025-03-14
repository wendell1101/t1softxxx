<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: WAZDAN
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -wazdan_seamless_service_api.php
 **/

class Game_api_wazdan_seamless extends Abstract_game_api {
    public $api_url;
    public $partner_code;
    public $operator;
    public $license;
    public $hmac_secret_key;
    public $username;
    public $password;
    public $game_launcher_url;
    public $demo_mode;
    public $lobby_url;
    public $country;
    public $language;
    public $currency;
    public $prefix_for_username;
    public $sync_time_interval;
    public $sleep_time;
    public $http_method;
    public $seamless_service_callback_url;
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $show_request_params_guide;
    public $use_transaction_data;
    public $precision;
    public $whitelist_ip_validate_api_methods;
    public $game_api_active_validate_api_methods;
    public $game_api_maintenance_validate_api_methods;
    public $game_api_player_blocked_validate_api_methods;
    public $free_spins_mode;
    public $free_rounds_mode;
    public $free_rounds_type;
    public $enable_merging_rows;

    // GAME API NAME
    public $seamless_game_api_name = 'WAZDAN_SEAMLESS_GAME_API';
    const SEAMLESS_GAME_API_NAME = 'WAZDAN_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const STATUS_OK = 'ok';

    const LANGUAGE_CODE_ENGLISH = 'en';
    const LANGUAGE_CODE_CHINESE = 'zh-cn';
    const LANGUAGE_CODE_INDONESIAN = 'id';
    const LANGUAGE_CODE_VIETNAMESE = 'vi';
    const LANGUAGE_CODE_KOREAN = 'ko';
    const LANGUAGE_CODE_THAI = 'th';
    const LANGUAGE_CODE_HINDI = 'hi';
    const LANGUAGE_CODE_PORTUGUESE = 'pt';

    const COUNTRY_CODE_US = 'US';
    const COUNTRY_CODE_CN = 'CN';
    const COUNTRY_CODE_ID = 'ID';
    const COUNTRY_CODE_VN = 'VN';
    const COUNTRY_CODE_KR = 'KR';
    const COUNTRY_CODE_TH = 'TH';
    const COUNTRY_CODE_IN = 'IN';
    const COUNTRY_CODE_BR = 'BR';

    const TRANSACTION_TYPE_GETSTAKE = 'getStake';
    const TRANSACTION_TYPE_RETURNWIN = 'returnWin';
    const TRANSACTION_TYPE_ROLLBACKSTAKE = 'rollbackStake';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const API_getStakes = '/games/stakes/'; // get a list of stakes available for game
    const API_getChecksums =  '/games/checksums/'; // get a list of checksums (available in selected licenses)

    const URI_MAP = [
        self::API_queryForwardGame => '/gamelauncher',
        self::API_queryGameListFromGameProvider => '/games/list/', // get a list of games available
        self::API_syncGameRecords =>  '/games/history/', // get a detailed list of rounds played by chosen player
        self::API_createFreeRoundBonus => '/add/',
        self::API_cancelFreeRoundBonus => '/forfeit/',
        self::API_queryFreeRoundBonus => '/list/',
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
        'round_number',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    const FREE_SPIN_METHODS = [
        'createFreeRound',
        'cancelFreeRound',
        'queryFreeRound',
    ];

    const RESPONSE_CODE_FAILED = 'FAILED';

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'wazdan_seamless_wallet_transactions');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'wazdan_seamless_game_logs');
        $this->seamless_service_callback_url = $this->getSystemInfo('seamless_service_callback_url', '');
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url');
        $this->partner_code = $this->getSystemInfo('partner_code');
        $this->operator = $this->getSystemInfo('operator');
        $this->license = $this->getSystemInfo('license');
        $this->hmac_secret_key = $this->getSystemInfo('hmac_secret_key');
        $this->username = $this->getSystemInfo('username', 'wazdan');
        $this->password = $this->getSystemInfo('password', 'staging');
        $this->game_launcher_url = $this->getSystemInfo('game_launcher_url');
        $this->demo_mode = $this->getSystemInfo('demo_mode', 'demo');
        $this->lobby_url = $this->getSystemInfo('lobby_url', $this->getHomeLink());
        $this->country = $this->getSystemInfo('country', '');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->show_request_params_guide = $this->getSystemInfo('show_request_params_guide', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->whitelist_ip_validate_api_methods = $this->getSystemInfo('whitelist_ip_validate_api_methods', []);
        $this->game_api_active_validate_api_methods = $this->getSystemInfo('game_api_active_validate_api_methods', []);
        $this->game_api_maintenance_validate_api_methods = $this->getSystemInfo('game_api_maintenance_validate_api_methods', []);
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->free_spins_mode = $this->getSystemInfo('free_spins_mode', 1);
        $this->free_rounds_mode = $this->getSystemInfo('free_rounds_mode', 1);
        $this->free_rounds_type = $this->getSystemInfo('free_rounds_type', 'regular');
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    public function getPlatformCode() {
        return WAZDAN_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getSeamlessTransactionTable() {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getTransactionsTable()
    {
        return $this->getSeamlessTransactionTable();
    }

    public function getSeamlessGameLogsTable() {
        return $this->original_seamless_game_logs_table;
    }

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url . $api_uri;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryForwardGame:
                    $url = $this->game_launcher_url . '/' . $this->partner_code . $api_uri . '?' . http_build_query($params);
                    break;
                default:
                    $url .= '?' . http_build_query($params);
                    break;
            }
        }

        return $url;
    }

    public function generateRequestSignature($data, $key, $algo = 'sha256', $json_encode = true) {
        if ($json_encode) {
            $data = json_encode($data);
        }

        return hash_hmac($algo, $data, $key);
    }

    protected function getHttpHeaders($params) {
        $headers = [
            'Content-type' => 'application/json',
            'Signature' => $this->generateRequestSignature($params, $this->hmac_secret_key),
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

        if (isset($resultArr['status']) && $resultArr['status'] == self::STATUS_OK) {
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

    public function queryGameListFromGameProvider($extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $params = array(
            'operator' => $this->operator,
            'license' => $this->license,
        );

        if (!empty($extra)) {
            $extra = is_array($extra) ? $extra : json_decode($extra, true);
            $params = array_merge($params, $extra);
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
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

    public function getStakes($extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetStakes',
        );

        $params = array(
            'operator' => $this->operator,
            'license' => $this->license,
        );

        if (!empty($extra)) {
            $extra = is_array($extra) ? $extra : json_decode($extra, true);
            $params = array_merge($params, $extra);
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_getStakes, $params, $context);
    }

    public function processResultForGetStakes($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['data'] = [];

        if ($success) {
            $result['data'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        }

        return array($success, $result);
    }

    public function getChecksums($extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetChecksums',
        );

        $params = array(
            'operator' => $this->operator,
            'license' => $this->license,
        );

        if (!empty($extra)) {
            $extra = is_array($extra) ? $extra : json_decode($extra, true);
            $params = array_merge($params, $extra);
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_getChecksums, $params, $context);
    }

    public function processResultForGetChecksums($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['data'] = [];

        if ($success) {
            $result['data'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        }

        return array($success, $result);
    }

    public function getLauncherCountry($language) {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
                $country = self::COUNTRY_CODE_US;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $country = self::COUNTRY_CODE_CN;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $country = self::COUNTRY_CODE_ID;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $country = self::COUNTRY_CODE_VN;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $country = self::COUNTRY_CODE_KR;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $country = self::COUNTRY_CODE_TH;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $country = self::COUNTRY_CODE_IN;
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-BR':
                $country = self::COUNTRY_CODE_BR;
                break;
            default:
                $country = self::COUNTRY_CODE_US;
                break;
        }

        return $country;
    }

    /* public function getLauncherLanguage($language) {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
                $language = self::LANGUAGE_CODE_ENGLISH;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $language = self::LANGUAGE_CODE_CHINESE;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $language = self::LANGUAGE_CODE_INDONESIAN;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $language = self::LANGUAGE_CODE_VIETNAMESE;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $language = self::LANGUAGE_CODE_KOREAN;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $language = self::LANGUAGE_CODE_THAI;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $language = self::LANGUAGE_CODE_HINDI;
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-BR':
                $language = self::LANGUAGE_CODE_PORTUGUESE;
                break;
            default:
                $language = self::LANGUAGE_CODE_ENGLISH;
                break;
        }

        return $language;
    } */

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en',
            'zh_cn' => 'zh-cn',
            'id_id' => 'id',
            'vi_vn' => 'vi',
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

    public function queryForwardGame($playerName, $extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;
        $success = true;
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $language = !empty($this->language) ? $this->language : $this->getLauncherLanguage($extra['language']);
        $platform = $is_mobile ? 'mobile' : 'desktop';
        $lobby_url = !empty($extra['home_link']) ? $extra['home_link'] :  $this->lobby_url;

        $params = [
            'operator' => $this->operator,
            'game' => $game_code,
            'lang' => $language,
            'platform' => $platform,
            'lobbyUrl' => $lobby_url,
        ];

        if ($game_mode == 'real') {
            $params['license'] = $this->license;
            $params['token'] = $token;
            $params['mode'] = $game_mode;
        } else {
            $params['mode'] = $this->demo_mode;
        }

        $url = $this->generateUrl(self::API_queryForwardGame, $params);

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        $result = [
            'success' => $success,
            'url' => $url,
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
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $validated_transaction = [
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : Game_logs::STATUS_PENDING,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $getStake_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_GETSTAKE, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $returnWin_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_RETURNWIN, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $rollbackStake_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_ROLLBACKSTAKE, $transaction_player_id, $transaction_game_code, $transaction_round_id);

                $validated_getStake_transaction = [
                    'amount' => !empty($getStake_transaction['amount']) ? $getStake_transaction['amount'] : 0,
                    'external_unique_id' => !empty($getStake_transaction['external_unique_id']) ? $getStake_transaction['external_unique_id'] : null,
                ];

                $validated_win_transaction = [
                    'amount' => !empty($returnWin_transaction['amount']) ? $returnWin_transaction['amount'] : 0,
                    'external_unique_id' => !empty($returnWin_transaction['external_unique_id']) ? $returnWin_transaction['external_unique_id'] : null,
                ];

                $validated_rollbackStake_transaction = [
                    'amount' => !empty($rollbackStake_transaction['amount']) ? $rollbackStake_transaction['amount'] : 0,
                    'external_unique_id' => !empty($rollbackStake_transaction['external_unique_id']) ? $rollbackStake_transaction['external_unique_id'] : null,
                ];

                extract($validated_getStake_transaction, EXTR_PREFIX_ALL, 'bet');
                extract($validated_win_transaction, EXTR_PREFIX_ALL, 'win');
                extract($validated_rollbackStake_transaction, EXTR_PREFIX_ALL, 'rollback_bet');

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::TRANSACTION_TYPE_GETSTAKE:
                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $win_amount,
                                'result_amount' => $win_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_RETURNWIN:
                            $win_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $win_amount,
                                'result_amount' => $win_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_ROLLBACKSTAKE:
                            $rollback_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $rollback_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollback_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
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

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return ['success' => true, $result];
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null) {
        $sqlTime = 'start_at >= ? AND end_at <= ?';

        if (!empty($transaction_type)) {
            $and_transaction_type = `AND transaction_type = ?`;
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
extra_info,
external_unique_id,
updated_at,
original_transaction_id
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND {$sqlTime} {$and_transaction_type}
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

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null) {
        if (!empty($transaction_id)) {
            $and_transaction_id = `AND transaction_id = ?`;
        } else {
            $and_transaction_id = '';
        }

        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
extra_info,
external_unique_id,
original_transaction_id
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND round_id = ? {$and_transaction_id}
EOD;

        if (!empty($transaction_id)) {
            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $round_id,
                $transaction_id,
            ];
        } else {
            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $round_id,
            ];
        }

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }

    public function syncMergeToGameLogs($token) {
        $this->syncOriginalGameLogsFromTrans($token);
        $enabled_game_logs_unsettle = true;
        
        $queryMethod = "queryOriginalGameLogsFromTransMerge";

        if(!$this->enable_merging_rows){
            $queryMethod = "queryOriginalGameLogsFromTransUnmerged";
        } 

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, $queryMethod],
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
    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
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
transaction.round_id AS round_number,
transaction.amount,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            $dateFrom,
            $dateTo,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * queryOriginalGameLogsFromTransUnmerged
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTransUnmerged($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
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
transaction.round_id AS round_number,
transaction.amount,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
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
        $transaction_type = !empty($row['transaction_type']) ? $row['transaction_type'] : null;
        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        $bet_amount = !empty($row['bet_amount']) ? $row['bet_amount'] : 0;

        $extra = !empty($row['note']) ? $row['note'] : null;
       
        if(!$this->enable_merging_rows){
            $extra = null;
            if($transaction_type == self::TRANSACTION_TYPE_GETSTAKE){ //bet
                $bet_amount = !empty($row['amount']) ? $row['amount'] : 0;
                $win_amount = 0;
                $result_amount = $win_amount - $bet_amount;
            }elseif($transaction_type == self::TRANSACTION_TYPE_RETURNWIN){//payout
                $win_amount = !empty($row['amount']) ? $row['amount'] : 0;
                $bet_amount = 0;
                $result_amount = $win_amount;
            }
   
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
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
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
                'round_number' => !empty($row['round_number']) ? $row['round_number'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            'bet_details' => [
                'Transaction Id' => !empty($row['transaction_id']) ? $row['transaction_id'] : null,
                'Game Code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'Round Id' => !empty($row['round_number']) ? $row['round_number'] : null,
            ],
            'extra' => [
                'note' => $extra,
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
        $row['after_balance'] += $row['win_amount'];
        $status = !empty($row['status']) ? $row['status'] : null;

        if ($status == Game_logs::STATUS_SETTLED) {
            if ($result_amount > 0) {
                $row['note'] = 'Win';
            } elseif ($result_amount < 0) {
                $row['note'] = 'Lose';
            } elseif ($result_amount == 0) {
                $row['note'] = 'Draw';
            } else {
                $row['note'] = 'Free Game';
            }
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $row['note'] = 'Refund';
        } else {
            $row['note'] = 'Unknown';
        }
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

    public function createFreeRound($playerName = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $operator = !empty($extra['operator']) ? $extra['operator'] : $this->operator;
        $license = !empty($extra['license']) ? $extra['license'] : $this->license;
        $gameUsername = !empty($extra['playerId']) ? $extra['playerId'] : $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $skinId = !empty($extra['skinId']) ? $extra['skinId'] : null;
        $txId = !empty($extra['txId']) ? $extra['txId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_round_transaction_id_prefix, $this->free_round_transaction_id_length);
        $campaignId = !empty($extra['campaignId']) ? $extra['campaignId'] : null;
        $type = !empty($extra['type']) ? $extra['type'] : $this->free_rounds_type;
        $currency = !empty($extra['currency']) ? $extra['currency'] : $this->currency;
        $count = !empty($extra['count']) ? $extra['count'] : $this->free_round_number_of_rounds;
        $stake = isset($extra['stake']) ? $extra['stake'] : $this->free_round_bet_value;
        $value = isset($extra['value']) ? $extra['value'] : null;
        $gameId = !empty($extra['gameId']) ? $extra['gameId'] : $this->free_round_game_ids;
        $startDate = !empty($extra['startDate']) ? $extra['startDate'] : $this->gameApiDateTime('now', 'Y-m-d H:i:s');
        $endDate = !empty($extra['endDate']) ? $extra['endDate'] : $this->gameApiDateTime('now', 'Y-m-d H:i:s', $this->free_round_validity_hours);

        $params = [
            'operator' => $operator,
            'license' => $license,
            'playerId' => $gameUsername,
            'txId' => $txId,
            'type' => $type,
            'currency' => $currency,
            'gameId' => $gameId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        if ($type == 'regular') {
            $params['count'] = $count;
        }

        if ($type == 'variable') {
            $params['stake'] = $stake;
            $params['value'] = $value;
        }

        if (!empty($skinId)) {
            $params['skinId'] = $skinId;
        }

        if (!empty($campaignId)) {
            $params['campaignId'] = $campaignId;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'game_username' => $gameUsername,
            'player_id' => $playerId,
            'free_rounds' => $count,
            'transaction_id' => $txId,
            'currency' => $currency,
            'expired_at' => $endDate,
            'extra' => $extra,
            'request' => $params,
        ];

        return $this->callApi(self::API_createFreeRoundBonus, $params, $context);
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
                'result' => $resultArr,
            ];

            $data = [
                'player_id' => $player_id,
                'game_platform_id' => $this->getPlatformCode(),
                'free_rounds' => $free_rounds,
                'transaction_id' => $transaction_id,
                'currency' => $currency,
                'expired_at' => $expired_at,
                'extra' => json_encode($extra),
                'raw_data' => json_encode($resultArr),
            ];

            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->insertTransaction($data);
        } else {
            $result = [
                'message' => isset($resultArr['message']) ? $resultArr['message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function cancelFreeRound($transaction_id = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $this->CI->load->model(['free_round_bonus_model']);
        $get_transaction = $this->CI->free_round_bonus_model->queryTransaction($transaction_id, $this->getPlatformCode());
        $playerId = !empty($get_transaction['player_id']) ? $get_transaction['player_id'] : null;
        $rawData = !empty($get_transaction['raw_data']) ? json_decode($get_transaction['raw_data'], true) : [];
        $freeRoundId = !empty($rawData['freeRoundId']) ? $get_transaction['freeRoundId'] : null;
        $operator = !empty($extra['operator']) ? $extra['operator'] : $this->operator;
        $license = !empty($extra['license']) ? $extra['license'] : $this->license;
        $gameUsername = !empty($extra['playerId']) ? $extra['playerId'] : $this->getGameUsernameByPlayerId($playerId);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $skinId = !empty($extra['skinId']) ? $extra['skinId'] : null;
        $txId = !empty($extra['txId']) ? $extra['txId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_round_transaction_id_prefix, $this->free_round_transaction_id_length);
        $freeRoundId = !empty($extra['freeRoundId']) ? $extra['freeRoundId'] : $freeRoundId;

        $params = [
            'operator' => $operator,
            'license' => $license,
            'playerId' => $gameUsername,
            'freeroundId' => $freeRoundId,
            'txId' => $txId,
        ];

        if (!empty($skinId)) {
            $params['skinId'] = $skinId;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'player_id' => $playerId,
            'transaction_id' => $transaction_id,
        ];

        return $this->callApi(self::API_cancelFreeRoundBonus, $params, $context);
    }

    public function processResultForCancelFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');

        $result = [
            'message' => '',
            'result' => $resultArr,
        ];

        if ($success) {
            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->cancelTransaction($transaction_id, $this->getPlatformCode());

            if (!empty($transaction_id)) {
                $result['transaction_id'] = $transaction_id;
            }

            $result['message'] = 'Cancelled successfully';
        } else {
            $result = [
                'message' => isset($resultArr['message']) ? $resultArr['message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function queryFreeRound($playerName = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $operator = !empty($extra['operator']) ? $extra['operator'] : $this->operator;
        $license = !empty($extra['license']) ? $extra['license'] : $this->license;
        $gameUsername = !empty($extra['playerId']) ? $extra['playerId'] : $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $skinId = !empty($extra['skinId']) ? $extra['skinId'] : null;
        $txId = !empty($extra['txId']) ? $extra['txId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_round_transaction_id_prefix, $this->free_round_transaction_id_length);
        $playerIds = !empty($extra['playerIds']) ? $extra['playerIds'] : [];

        $params = [
            'operator' => $operator,
            'license' => $license,
            'playerIds' => $playerIds,
        ];

        if (!empty($skinId)) {
            $params['skinId'] = $skinId;
        }

        // $this->CI->load->model(['free_round_bonus_model']);
        // $playerId = $this->CI->free_round_bonus_model->getSpecificColumn('free_round_bonuses', 'player_id', ['transaction_id' => $ReferenceID]);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
            'game_username' => $gameUsername,
            // 'playerId' => $playerId,
        ];

        return $this->callApi(self::API_queryFreeRoundBonus, $params, $context);
    }

    public function processResultForQueryFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($success) {
            $result = [
                'free_round_list' => !empty($resultArr['data']) ? $resultArr['data'] : [],
            ];
        }
        else {
            $result = [
                'message' => isset($resultArr['message']) ? $resultArr['message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function callback($request, $method) {
        if (!in_array($method, self::FREE_SPIN_METHODS)) {
            return [
                'returnCode' => self::RESPONSE_CODE_FAILED,
                'message' => 'Invalid method',
            ];
        }

        return $this->$method('', $request);
    }
}
//end of class