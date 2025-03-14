<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/year_month_table_module.php';

/**
 * Game Provider: Tada
 * Game Type: Fishing, Arcade, Slots, Table and Cards, Bingo
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -tada_seamless_service_api.php
 **/

class Game_api_tada_seamless extends Abstract_game_api {
    use Year_month_table_module;

    // default
    public $http_method;
    public $api_url;
    public $language;
    public $currency;
    public $prefix_for_username;
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $whitelist_ip_validate_api_methods;
    public $game_api_active_validate_api_methods;
    public $game_api_maintenance_validate_api_methods;
    public $game_api_player_blocked_validate_api_methods;
    public $sync_time_interval;
    public $sleep_time;
    public $use_transaction_data;
    public $use_remote_wallet_failed_transaction_monthly_table;

    // additional
    public $agent_id;
    public $agent_key;
    public $demo_url;
    public $home_url;
    public $random_text_prefix;
    public $random_text_suffix;
    public $use_login_without_redirect_api;
    public $provider_gmt;
    public $CI;
    public $override_home_link;
    public $force_language;
    public $default_language;
    public $timezone;

    // free spin API
    public $free_spin_reference_id_prefix;
    public $free_spin_reference_id_length;
    public $free_spin_default_number_of_rounds;
    public $free_spin_default_game_ids;
    public $free_spin_default_bet_value;
    public $free_spin_default_validity_hours;

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

    const SEAMLESS_GAME_API_NAME = 'TADA_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCESS = 0;
    const RESPONSE_CODE_FAILED = 'FAILED';

    const LANGUAGE_CODE_ENGLISH = 'en-US';
    const LANGUAGE_CODE_CHINESE = 'zh-CN';
    const LANGUAGE_CODE_INDONESIAN = 'id-ID';
    const LANGUAGE_CODE_VIETNAMESE = 'vi-VN';
    const LANGUAGE_CODE_KOREAN = 'en-US';
    const LANGUAGE_CODE_THAI = 'th-TH';
    const LANGUAGE_CODE_HINDI = 'hi-IN';
    const LANGUAGE_CODE_PORTUGUESE = 'pt-BR';
    const LANGUAGE_CODE_TAIWANESE = 'zh-TW';

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_CANCEL_BET = 'cancelBet';
    const TRANSACTION_TYPE_SESSION_BET = 'sessionBet';
    const TRANSACTION_TYPE_CANCEL_SESSION_BET = 'cancelSessionBet';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const API_queryForwardGame2 = 'queryForwardGame2';

    const URI_MAP = [
        self::API_queryForwardGame => '/api1/singleWallet/Login',
        self::API_queryForwardGame2 => '/api1/singleWallet/LoginWithoutRedirect',
        self::API_createFreeRoundBonus => '/api1/CreateFreeSpin',
        self::API_cancelFreeRoundBonus => '/api1/CancelFreeSpin',
        self::API_queryFreeRoundBonus => '/api1/GetFreeSpinRecordByReferenceID',
        self::API_queryGameListFromGameProvider => '/api1/GetGameList',
    ];

    const FREE_SPIN_METHODS = [
        'createFreeRound',
        'cancelFreeRound',
        'queryFreeRound',
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
        'preserve',
        'turnover',
        'type',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'preserve',
        'turnover',
    ];

    const BET_CODE = 1;
    const SETTLE_CODE = 2;
    // extra only, not included in the API docs
    const REFUND_CODE = 3;
    const BET_SETTLE_CODE = 4;

    const IS_PROCESSED = 1;

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);

        // default
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 4);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->whitelist_ip_validate_api_methods = $this->getSystemInfo('whitelist_ip_validate_api_methods', []);
        $this->game_api_active_validate_api_methods = $this->getSystemInfo('game_api_active_validate_api_methods', []);
        $this->game_api_maintenance_validate_api_methods = $this->getSystemInfo('game_api_maintenance_validate_api_methods', []);
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->timezone = $this->getSystemInfo('timezone', 'America/Puerto_Rico');

        // default tables
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'tada_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'tada_seamless_service_logs');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'tada_seamless_game_logs');
        
        $this->ymt_init();

        // additional
        $this->agent_id = $this->getSystemInfo('agent_id');
        $this->agent_key = $this->getSystemInfo('agent_key');
        $this->random_text_prefix = $this->getSystemInfo('random_text_prefix', '000000');
        $this->random_text_suffix = $this->getSystemInfo('random_text_suffix', '000000');
        $this->demo_url = $this->getSystemInfo('demo_url', 'https://tadagaming.com/plusplayer/PlusTrial'); // https://tadagaming.com/plusplayer/PlusTrial/{gameId}/{lang}
        $this->home_url = $this->getSystemInfo('home_url', $this->getHomeLink());
        $this->use_login_without_redirect_api = $this->getSystemInfo('use_login_without_redirect_api', true);
        $this->provider_gmt = $this->getSystemInfo('provider_gmt', '-12');
        
        // free spin API
        $this->free_spin_reference_id_prefix = $this->getSystemInfo('free_spin_reference_id_prefix', 'FS');
        $this->free_spin_reference_id_length = $this->getSystemInfo('free_spin_reference_id_length', 12);
        $this->free_spin_default_number_of_rounds = $this->getSystemInfo('free_spin_default_number_of_rounds', 1);
        $this->free_spin_default_game_ids = $this->getSystemInfo('free_spin_default_game_ids', '');
        $this->free_spin_default_bet_value = $this->getSystemInfo('free_spin_default_bet_value', '');
        $this->free_spin_default_validity_hours = $this->getSystemInfo('free_spin_default_validity_hours', '+2 hours');


        $this->override_home_link = $this->getSystemInfo('override_home_link', false);

		$this->force_language = $this->getSystemInfo('force_language', '');
        
        $this->default_language = $this->getSystemInfo('default_language', self::LANGUAGE_CODE_ENGLISH);

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->use_remote_wallet_failed_transaction_monthly_table = $this->getSystemInfo('use_remote_wallet_failed_transaction_monthly_table', true);
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

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
    }

    public function getPlatformCode() {
        return TADA_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getSeamlessTransactionTable() {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getSeamlessGameLogsTable() {
        return $this->original_seamless_game_logs_table;
    }

    public function generateUrl($api_name, $params) {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url . $api_uri;
        $params['AgentId'] = $this->agent_id;
        $params['Key'] = $this->generateKey($params);

        if ($this->http_method == self::HTTP_METHOD_GET) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    public function generateKey($params) {
        unset($params['HomeUrl'], $params['Platform'], $params['BetValue'], $params['StartTime']);

        // $now = date('ymj', strtotime($this->utils->getNowForMysql()));
        /* $now = new DateTime($this->utils->getNowForMysql());
        date_modify($now, $this->provider_gmt . ' hours');
        $now = date_format($now, 'ymj'); */

        $now = $this->apiGameTime('now', 'ymj');
        $keyg = md5($now . $this->agent_id . $this->agent_key);
        $query_string = urldecode(http_build_query($params));
        $md5_string = md5($query_string . $keyg);
        $random_text_prefix = !empty($this->random_text_prefix) ? $this->random_text_prefix : random_string('alnum', 6);
        $random_text_suffix = !empty($this->random_text_suffix) ? $this->random_text_suffix : random_string('alnum', 6);
        $key = $random_text_prefix . $md5_string . $random_text_suffix;

        return $key;
    }

    protected function getHttpHeaders($params) {
        $headers = [
            'Content-type' => $this->http_method == self::HTTP_METHOD_GET ? 'application/json' : 'application/x-www-form-urlencoded',
        ];

        return $headers;
    }

    public function randomText($offset = 6, $length = 6) {
        return substr(sha1(uniqid(mt_rand(), TRUE)), $offset, $length);
    }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            $params['AgentId'] = $this->agent_id;
            $params['Key'] = $this->generateKey($params);

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null) {
        $success = false;

        if (isset($resultArr['ErrorCode']) && $resultArr['ErrorCode'] == self::RESPONSE_CODE_SUCCESS) {
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


    public function getLauncherLanguage($language) {


		if($this->force_language && !empty($this->force_language)){
            return $this->force_language;
        }

        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
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
            case 'pt-br':
            case 'pt-BR':
            case 'pt-PT':
                $language = self::LANGUAGE_CODE_PORTUGUESE;
                break;
            case 'zh-tw':
                $language = self::LANGUAGE_CODE_TAIWANESE;
                break;
            default:
                #$language = self::LANGUAGE_CODE_ENGLISH;
                $language = $this->default_language;
                break;
        }

        return $language;
    }

    public function queryForwardGame($playerName, $extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        );

        $success = true;
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $language = $this->getLauncherLanguage($extra['language']);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $platform = $is_mobile ? 'mobile' : 'desktop';
        $home_url = !empty($extra['home_link']) ? $extra['home_link'] :  $this->home_url;

        if (isset($extra['extra']['home_link'])) {
            $home_url = $extra['extra']['home_link'];
        }

        $params = [
            'Token' => $token,
            'GameId' => $game_code,
            'Lang' => $language,
            'HomeUrl' => $home_url,
            // 'Platform' => $platform,
        ];

        if($this->override_home_link){
            $params['HomeUrl'] = $this->override_home_link;
        }

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['HomeUrl']);
        }

        if ($game_mode != 'real' && !empty($this->demo_url)) {
            $url = $this->demo_url . "/" . $game_code."/".$language;

            $result = [
                'success' => $success,
                'url' => $url,
            ];
        } else {
            if ($this->use_login_without_redirect_api) {
                $result = $this->callApi(self::API_queryForwardGame2, $params, $context);
            } else {
                $url = $this->generateUrl(self::API_queryForwardGame, $params);
    
                $result = [
                    'success' => $success,
                    'url' => $url,
                ];
            }
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'result', $result);

        return $result;
    }

    public function processResultForQueryForwardGame($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'success' => false,
            'url' => null
        ];

        if ($success && !empty($resultArr['Data'])) {
            $result['success'] = true;
            $result['url'] = $resultArr['Data'];
        }

        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra = null) {
        $this->http_method = self::HTTP_METHOD_GET;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );
        $params = array(                                
            "AgentId" => $this->agent_id,
        );

        $this->utils->debug_log("TADA SEAMLESS: queryGameListFromGameProvider PARAMS", $params);  
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId, 'url' => null];
        $this->utils->debug_log("TADA SEAMLESS: (processResultForQueryGameListFromGameProvider)", 'resultArr', $resultArr);

        if($success){
            if(isset($resultArr['Data']) && isset($resultArr['Data'])){
                $result['games']=$resultArr['Data'];
            }else{
                $success=false;
            }
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

        $transactions = $this->queryTransactionsForUpdate($this->original_seamless_wallet_transactions_table, $queryDateTimeStart, $queryDateTimeEnd);

        if ($this->enable_merging_rows) {
            $is_sum = true;
        } else {
            $is_sum = false;
        }
        
        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                switch ($transaction['transaction_type']) {
                    case self::TRANSACTION_TYPE_BET:
                        $bet_data = [
                            'status' => $transaction['status'],
                            'bet_amount' => $transaction['bet_amount'],
                            'win_amount' => $transaction['win_amount'],
                            'result_amount' => $transaction['result_amount'],
                            'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                        ];

                        $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $this->updateTransactionData($this->original_seamless_wallet_transactions_table, $bet_data, ['external_unique_id' => $transaction['external_unique_id']]);
                        break;
                    case self::TRANSACTION_TYPE_CANCEL_BET:
                        list($bet_transaction, $bet_table) = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], null, $is_sum);

                        $cancel_bet_data = [
                            'status' => $transaction['status'],
                            'flag_of_updated_result' => self::FLAG_UPDATED,
                        ];

                        $bet_data = [
                            'status' => $transaction['status'],
                            'bet_amount' => $bet_transaction['bet_amount'],
                            'win_amount' => 0,
                            'result_amount' => $bet_transaction['bet_amount'],
                            'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                        ];

                        if (!$this->enable_merging_rows) {
                            $cancel_bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => 0,
                                'win_amount' => 0,
                                'result_amount' => $bet_transaction['bet_amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];
    
                            $bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => $bet_transaction['bet_amount'],
                                'win_amount' => 0,
                                'result_amount' => -$bet_transaction['bet_amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];
                        }

                        $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $this->updateTransactionData($this->original_seamless_wallet_transactions_table, $cancel_bet_data, ['external_unique_id' => $transaction['external_unique_id']]);
                        $this->updateTransactionData($bet_table, $bet_data, ['external_unique_id' => $bet_transaction['external_unique_id']]);
                        break;
                    case self::TRANSACTION_TYPE_SESSION_BET:
                        $extra_info = !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];
                        $preserve_amount = isset($extra_info['preserve']) ? $extra_info['preserve'] : 0;
                        $bet_amount = isset($extra_info['betAmount']) ? $extra_info['betAmount'] : 0;
                        $win_amount = isset($extra_info['winloseAmount']) ? $extra_info['winloseAmount'] : 0;
                        $status = $transaction['status'];

                        if ($transaction['type'] == self::BET_CODE) {
                            if (!$this->enable_merging_rows) {
                                list($session_settle_transaction, $session_settle_table) = $this->queryPlayerTransactionByType(self::TRANSACTION_TYPE_SESSION_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], self::SETTLE_CODE, null, $is_sum);

                                if ($preserve_amount > 0) {
                                    if ($session_settle_transaction) {
                                        $extra_info = !empty($session_settle_transaction['extra_info']) ? json_decode($session_settle_transaction['extra_info'], true) : [];
                                        $bet_amount = isset($extra_info['betAmount']) ? $extra_info['betAmount'] : 0;
                                        $status = !empty($session_settle_transaction['status']) ? $session_settle_transaction['status'] : $status;
                                    }
                                } else {
                                    $bet_amount = $transaction['amount'];
                                }

                                $session_bet_data = [
                                    'status' => $status,
                                    'bet_amount' => $bet_amount,
                                    'win_amount' => 0,
                                    'result_amount' => !empty($bet_amount) ? -$bet_amount : 0,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                ];

                                $session_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($session_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            } else {
                                $session_bet_data = [
                                    'status' => $transaction['status'],
                                    'flag_of_updated_result' => self::FLAG_UPDATED,
                                ];
                            }

                            $this->updateTransactionData($this->original_seamless_wallet_transactions_table, $session_bet_data, ['external_unique_id' => $transaction['external_unique_id']]);
                        }

                        if ($transaction['type'] == self::SETTLE_CODE) {
                            list($session_bet_transaction, $session_bet_table) = $this->queryPlayerTransactionByType(self::TRANSACTION_TYPE_SESSION_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], self::BET_CODE, null, $is_sum);

                            if ($preserve_amount <= 0) {
                                $win_amount = $transaction['amount'];

                                if (empty($bet_amount) && !empty($session_bet_transaction['amount'])) {
                                    $bet_amount = $session_bet_transaction['amount'];
                                }
                            }

                            if (!$this->enable_merging_rows) {
                                $session_settle_data = [
                                    'status' => $transaction['status'],
                                    'bet_amount' => 0,
                                    'win_amount' => $win_amount,
                                    'result_amount' => $win_amount,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                ];

                                // update bet transaction
                                if (!empty($session_bet_transaction)) {
                                    $session_bet_data = [
                                        'status' => $transaction['status'],
                                        'bet_amount' => $bet_amount,
                                        'win_amount' => 0,
                                        'result_amount' => !empty($bet_amount) ? -$bet_amount : 0,
                                        'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                    ];
    
                                    $this->updateTransactionData($this->original_seamless_wallet_transactions_table, $session_bet_data, ['external_unique_id' => $session_bet_transaction['external_unique_id']]);
                                }
                            } else {
                                if ($preserve_amount <= 0) {
                                    $bet_amount = isset($session_bet_transaction['amount']) ? $session_bet_transaction['amount'] : 0;
                                }

                                $session_settle_data = [
                                    'status' => $transaction['status'],
                                    'bet_amount' => $bet_amount,
                                    'win_amount' => $win_amount,
                                    'result_amount' => $win_amount - $bet_amount,
                                    'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                ];
                            }

                            $session_settle_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($session_settle_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                            $this->updateTransactionData($this->original_seamless_wallet_transactions_table, $session_settle_data, ['external_unique_id' => $transaction['external_unique_id']]);
                        }
                        break;
                    case self::TRANSACTION_TYPE_CANCEL_SESSION_BET:
                        list($session_bet_transaction, $session_bet_table) = $this->queryPlayerTransactionByType(self::TRANSACTION_TYPE_SESSION_BET, $transaction['player_id'], $transaction['game_code'], $transaction['round_id'], self::BET_CODE, null, $is_sum);

                        $session_bet_data = [
                            'status' => $transaction['status'],
                            'flag_of_updated_result' => self::FLAG_UPDATED,
                        ];

                        $cancel_session_bet_data = [
                            'status' => $transaction['status'],
                            'bet_amount' => $session_bet_transaction['amount'],
                            'win_amount' => 0,
                            'result_amount' => $session_bet_transaction['amount'],
                            'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                        ];

                        if (!$this->enable_merging_rows) {
                            $session_bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => $session_bet_transaction['amount'],
                                'win_amount' => 0,
                                'result_amount' => -$session_bet_transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $cancel_session_bet_data = [
                                'status' => $transaction['status'],
                                'bet_amount' => 0,
                                'win_amount' => 0,
                                'result_amount' => $transaction['amount'],
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];
                        }

                        $session_settle_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($cancel_session_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                        
                        $this->updateTransactionData($session_bet_table, $session_bet_data, ['external_unique_id' => $session_bet_transaction['external_unique_id']]);
                        $this->updateTransactionData($this->original_seamless_wallet_transactions_table, $cancel_session_bet_data, ['external_unique_id' => $transaction['external_unique_id']]);
                        break;
                    default:
                        break;
                }

                $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'transaction_start_at', $transaction['start_at'], 'transaction_updated_at', $transaction['updated_at']);
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return ['success' => true, $result];
    }

    public function updateTransactionData($table, $data, $where) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $is_updated = $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($table, $where, $data);

        return $is_updated;
    }

    public function queryTransactionsForUpdate($table, $dateFrom, $dateTo, $transaction_type = null) {
        $sqlTime = 'start_at >= ? AND end_at <= ?';

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
extra_info,
external_unique_id,
updated_at,
bet_amount,
win_amount,
result_amount,
session_id,
type
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND is_processed = ? AND {$sqlTime} {$and_transaction_type}
EOD;

        if (!empty($transaction_type)) {
            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                self::IS_PROCESSED,
                $dateFrom,
                $dateTo,
                $transaction_type,
            ];
        } else {
            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                self::IS_PROCESSED,
                $dateFrom,
                $dateTo
            ];
        }

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null, $is_sum = false) {
        $table_names = [$this->original_seamless_wallet_transactions_table];
        $result = [];
        $from_table = null;
        $and_transaction_id = !empty($transaction_id) ? 'AND transaction_id = ?' : '';
        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $win_amount = $is_sum ? 'SUM(win_amount) as win_amount' : 'win_amount';
        $result_amount = $is_sum ? 'SUM(result_amount) as result_amount' : 'result_amount';

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        foreach ($table_names as $table_name) {
            $from_table = $table_name;

            $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
{$amount},
status,
extra_info,
external_unique_id,
{$bet_amount},
{$win_amount},
{$result_amount},
type
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND is_processed = ? AND player_id = ? AND game_code = ? AND round_id = ? {$and_transaction_id}
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            self::IS_PROCESSED,
            $player_id,
            $game_code,
            $round_id,
        ];

            if (!empty($transaction_id)) {
                array_push($params, $transaction_id);
            }

            $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

            if (!empty($result['id'])) {
                break;
            }
        }

        return [$result, $from_table];
    }

    public function queryPlayerTransactionByType($transaction_type, $player_id, $game_code, $round_id, $type, $transaction_id = null, $is_sum = false) {
        $table_names = [$this->original_seamless_wallet_transactions_table];
        $result = [];
        $from_table = null;
        $and_transaction_id = !empty($transaction_id) ? 'AND transaction_id = ?' : '';
        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';
        $bet_amount = $is_sum ? 'SUM(bet_amount) as bet_amount' : 'bet_amount';
        $win_amount = $is_sum ? 'SUM(win_amount) as win_amount' : 'win_amount';
        $result_amount = $is_sum ? 'SUM(result_amount) as result_amount' : 'result_amount';

        if ($this->use_monthly_transactions_table) {
            if ($this->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
                array_push($table_names, $this->previous_table);
            }
        }

        foreach ($table_names as $table_name) {
            $from_table = $table_name;

            $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
{$amount},
status,
extra_info,
external_unique_id,
{$bet_amount},
{$win_amount},
{$result_amount},
type
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND is_processed = ? AND player_id = ? AND game_code = ? AND round_id = ? AND type = ? {$and_transaction_id}
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            self::IS_PROCESSED,
            $player_id,
            $game_code,
            $round_id,
            $type,
        ];

            if (!empty($transaction_id)) {
                array_push($params, $transaction_id);
            }

            $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

            if (!empty($result['id'])) {
                break;
            }
        }

        return [$result, $from_table];
    }

    public function syncMergeToGameLogs($token) {
        $this->syncOriginalGameLogsFromTrans($token);
        $enabled_game_logs_unsettle = true;

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
        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_year_month_table_by_date(null, $dateFrom);
            $this->previous_table = $this->ymt_get_previous_year_month_table(null, $dateFrom);
        }
        
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        /* $flagUpdatedResult = '';
        if($this->enable_merging_rows){
            $flagUpdatedResult = 'AND transaction.flag_of_updated_result = '.self::FLAG_UPDATED_FOR_GAME_LOGS;
        } */

        $flagUpdatedResult = 'AND transaction.flag_of_updated_result = ' . self::FLAG_UPDATED_FOR_GAME_LOGS;

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
transaction.round_id AS round_number,
transaction.amount,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.preserve,
transaction.turnover,
transaction.flag_of_updated_result,
transaction.extra_info,
transaction.type,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? {$flagUpdatedResult} AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::IS_PROCESSED,
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

        $bet_details = json_decode($row['extra_info'],true);
        $bet_details['game'] = isset($row['game']) ? $row['game'] : null;
        /* if(!$this->enable_merging_rows && $row['transaction_type'] == self::TRANSACTION_TYPE_SESSION_BET){
            if($row['flag_of_updated_result'] == self::FLAG_UPDATED_FOR_GAME_LOGS){
                $row['bet_amount'] = 0;
                $row['real_betting_amount'] = 0;
                $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
            }else{
                $row['win_amount'] = 0;
                $row['bet_amount'] = $row['amount']; #amount as bet_amount
                $row['real_betting_amount'] = $row['amount']; #amount as bet_amount
                $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
            }
        } */
        
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
                'round_number' => !empty($row['round_number']) ? $row['round_number'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            //'bet_details' => $this->formatBetDetails($bet_details),
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [
                'note' => !empty($row['note']) ? $row['note'] : null,
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
        
        if ($row['transaction_type'] == self::TRANSACTION_TYPE_SESSION_BET) {
            // $row['after_balance'] += $row['win_amount'];
            // $row['bet_amount'] = $row['real_betting_amount'] = $row['turnover'];
        }

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
        } elseif ($status == Game_logs::STATUS_PENDING) {
            $row['note'] = 'Pending';
        } else {
            $row['note'] = 'Unknown';
        }

        if( $row['type'] == self::SETTLE_CODE && $result_amount == 0){
            $row['note'] = 'N/A';
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

    public function queryTransactionByDateTime($startDate, $endDate) {
        if ($this->use_monthly_transactions_table) {
            $this->original_seamless_wallet_transactions_table = $this->ymt_get_year_month_table_by_date(null, $startDate);
        }

        if ($this->utils->getYearMonthByDate($startDate) == $this->utils->getThisYearMonth()) {
            $query_time = "AND transaction.updated_at BETWEEN ? AND ?";
        } else {
            $query_time = "AND transaction.created_at BETWEEN ? AND ?";
        }

        if (empty($this->original_seamless_wallet_transactions_table)) {
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
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
transaction.extra_info,
transaction.type
FROM {$this->original_seamless_wallet_transactions_table} as transaction
WHERE transaction.game_platform_id = ? AND transaction.is_processed = ? AND transaction.updated_at >= ? AND transaction.updated_at <= ?
ORDER BY transaction.updated_at asc, transaction.id asc;
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

                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_BET) {
                    if ($transaction['result_amount'] < 0) {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    } else {
                        $extra['trans_type'] = 'payout';
                    }
                }

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_SESSION_BET) {
                    if ($transaction['type'] == self::BET_CODE) {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }
                }

                $temp_game_record['extra_info'] = json_encode($extra);

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function formatBetDetails($extra_info){
        $bet_details = [];
        if($extra_info){
            $datetime = date('Y-m-d H:i:s', $extra_info['wagersTime']);
            $bet_details = [
                'bet_amount' => $extra_info['betAmount'],
                'win_amount' => $extra_info['winloseAmount'],
                'round_id' => $extra_info['round'],
                'event_time' => $datetime,
                'game_name' => $extra_info['game'],
                'others' => $extra_info,
            ];
        }
        return $bet_details;
    }

    public function apiGameTime($dateTime = 'now', $format = 'Y-m-d H:i:s', $modify = '+0 hours') {
        $dateTime = new DateTime($dateTime, new DateTimeZone($this->timezone));
        $dateTime->modify($modify);
        return $dateTime->format($format);
    }

    public function createFreeRound($playerName = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $gameUsername = !empty($extra['Account']) ? $extra['Account'] : $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $currency = !empty($extra['currency']) ? $extra['currency'] : $this->currency;
        $referenceId = !empty($extra['ReferenceId']) ? $extra['ReferenceId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_spin_reference_id_prefix, $this->free_spin_reference_id_length);
        $freeSpinValidity = !empty($extra['FreeSpinValidity']) ? $extra['FreeSpinValidity'] : $this->apiGameTime('now', 'Y-m-d\TH:i:s', $this->free_spin_default_validity_hours);
        $numberOfRounds = !empty($extra['NumberOfRounds']) ? $extra['NumberOfRounds'] : $this->free_spin_default_number_of_rounds;
        $gameIds = !empty($extra['GameIds']) ? $extra['GameIds'] : $this->free_spin_default_game_ids;
        $betValue = isset($extra['BetValue']) ? $extra['BetValue'] : $this->free_spin_default_bet_value;
        $startTime = !empty($extra['StartTime']) ? $extra['StartTime'] : $this->apiGameTime('now', 'Y-m-d\TH:i:s');

        $params = [
            'Account' => $gameUsername,
            'Currency' => $currency,
            'ReferenceId' => $referenceId,
            'FreeSpinValidity' => $freeSpinValidity,
            'NumberOfRounds' => $numberOfRounds,
            'GameIds' => $gameIds,
            'StartTime' => $startTime,
        ];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'game_username' => $gameUsername,
            'player_id' => $playerId,
            'free_rounds' => $numberOfRounds,
            'transaction_id' => $referenceId,
            'currency' => $currency,
            'expired_at' => $freeSpinValidity,
            'extra' => $extra,
            'request' => $params,
        ];

        if ($betValue != '') {
            $params['BetValue'] = $betValue;
        }

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
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function cancelFreeRound($transaction_id = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;

        if (!empty($extra['ReferenceId'])) {
            $transaction_id = $extra['ReferenceId'];
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'transaction_id' => $transaction_id,
        ];

        $params = [
            'ReferenceId' => $transaction_id,
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
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function queryFreeRound($playerName = null, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $ReferenceID = isset($extra['ReferenceID']) ? $extra['ReferenceID'] : null;
        
        // $this->CI->load->model(['free_round_bonus_model']);
        // $playerId = $this->CI->free_round_bonus_model->getSpecificColumn('free_round_bonuses', 'player_id', ['transaction_id' => $ReferenceID]);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
            'game_username' => $gameUsername,
            // 'playerId' => $playerId,
        ];

        $params = [
            'ReferenceID' => $ReferenceID,
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
                'free_round_list' => !empty($resultArr['Data']) ? $resultArr['Data'] : [],
            ];
        }
        else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
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

        if (isset($row['type'])) {
            $bet_details['type'] = $this->typeMapping($row['type']);
        }

        if (!$this->enable_merging_rows) {
            if ($row['transaction_type'] == self::TRANSACTION_TYPE_SESSION_BET && $row['type'] == self::BET_CODE) {
                // get win amount
                list($session_settle_transaction, $session_settle_table) = $this->queryPlayerTransactionByType(self::TRANSACTION_TYPE_SESSION_BET, $row['player_id'], $row['game_code'], $row['round_id'], self::SETTLE_CODE, null, false);
                $extra_info = !empty($session_settle_transaction['extra_info']) ? json_decode($session_settle_transaction['extra_info'], true) : [];
                $preserve_amount = !empty($extra_info['preserve']) ? $extra_info['preserve'] : 0;

                if ($preserve_amount > 0) {
                    $bet_details['bet_amount'] = !empty($extra_info['betAmount']) ? $extra_info['betAmount'] : 0;
                    $bet_details['win_amount'] = !empty($extra_info['winloseAmount']) ? $extra_info['winloseAmount'] : 0;
                } else {
                    $bet_details['win_amount'] = $session_settle_transaction['amount'];
                }
            }

            if ($row['transaction_type'] == self::TRANSACTION_TYPE_SESSION_BET && $row['type'] == self::SETTLE_CODE) {
                // get bet amount
                list($session_bet_transaction, $session_bet_table) =  $this->queryPlayerTransactionByType(self::TRANSACTION_TYPE_SESSION_BET, $row['player_id'], $row['game_code'], $row['round_id'], self::BET_CODE, null, false);
                $extra_info = !empty($session_bet_transaction['extra_info']) ? json_decode($session_bet_transaction['extra_info'], true) : [];
                $preserve_amount = !empty($extra_info['preserve']) ? $extra_info['preserve'] : 0;

                if ($preserve_amount > 0) {
                    $extra_info = !empty($row['extra_info']) ? json_decode($row['extra_info'], true) : [];
                    $bet_details['bet_amount'] = !empty($extra_info['betAmount']) ? $extra_info['betAmount'] : 0;
                    $bet_details['win_amount'] = !empty($extra_info['winloseAmount']) ? $extra_info['winloseAmount'] : 0;
                } else {
                    $bet_details['bet_amount'] = $session_bet_transaction['amount'];
                }
            }

            if ($row['status'] == Game_logs::STATUS_REFUND) {
                $bet_details['bet_amount'] = $row['amount'];

                $bet_details['refund_amount'] = $row['amount'];

                unset($bet_details['win_amount']);
            }
        } else {
            list($cancel_bet_transaction, $cancel_bet_table) = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_CANCEL_BET, $row['player_id'], $row['game_code'], $row['round_id'], null, true);
            list($cancel_session_bet_transaction, $cancel_session_bet_table) =  $this->queryPlayerTransactionByType(self::TRANSACTION_TYPE_CANCEL_SESSION_BET, $row['player_id'], $row['game_code'], $row['round_id'], self::BET_CODE, null, true);

            if (!empty($cancel_bet_transaction['external_unique_id'])) {
                $bet_details['refund_amount'] = $cancel_bet_transaction['amount'];
            }

            if (!empty($cancel_session_bet_transaction['external_unique_id'])) {
                $bet_details['refund_amount'] = $cancel_session_bet_transaction['amount'];
            }
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='original.created_at >= ? AND original.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $pendingStatus = Game_logs::STATUS_PENDING;
        $transTypeBet = 'bet';
        $transTypeSessionBet = 'sessionBet';

        $sql = <<<EOD
SELECT 
original.round_id, original.transaction_id, game_platform_id
from {$this->original_transactions_table} as original
where
original.status=?
and (original.transaction_type=? or original.transaction_type=?)
and {$sqlTime}
EOD;


        $params=[
            $pendingStatus,
            $transTypeBet,
            $transTypeSessionBet,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('TADA_SEAMLESS_GAME_API-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($data){
        $this->CI->load->model(['seamless_missing_payout']);
        $this->original_transactions_table = $this->getTransactionsTable();

        $roundId = $data['round_id'];
        $transactionId = $data['transaction_id'];
        $transStatus = Game_logs::STATUS_PENDING;
        $baseAmount = 0;
     
        $sql = <<<EOD
SELECT 
original.created_at as transaction_date,
original.transaction_type,
original.status,
original.game_platform_id,
original.player_id,
original.round_id,
original.transaction_id,
ABS(SUM(original.amount)) as amount,
ABS(SUM(original.result_amount)) as deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
original.external_unique_id as external_uniqueid
from {$this->original_transactions_table} as original
left JOIN game_description as gd ON original.game_code = gd.external_game_id and gd.game_platform_id=?
where
round_id=? and transaction_id=? and original.game_platform_id=?
EOD;
        
        $params=[$this->getPlatformCode(), $roundId, $transactionId, $this->getPlatformCode()];

        $transactions  = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        foreach($transactions as $transaction){
            if($transaction['game_platform_id']){
                $transaction['transaction_status'] = $transStatus;
                $transaction['added_amount'] = $baseAmount;
                $transaction['status'] = Seamless_missing_payout::NOT_FIXED;

                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$transaction);
                if($result===false){
                    $this->CI->utils->error_log('TADA_SEAMLESS_GAME_API' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
                }
            }
        }
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false);
        }
    }
    
    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_transactions_table = $this->getTransactionsTable();
        $this->CI->load->model(['seamless_missing_payout']);

        $sql = <<<EOD
SELECT 
status
FROM {$this->original_transactions_table}
WHERE
game_platform_id=? AND external_unique_id=? 
EOD;
     
        $params=[$game_platform_id, $external_uniqueid];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($trans)){
            return array('success'=>true, 'status'=>$trans['status']);
        }
        return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
    }

    public function typeMapping($type) {
        switch ($type) {
            case self::BET_CODE:
                $desc = 'Bet';
                break;
            case self::SETTLE_CODE:
                $desc = 'Settle';

                if ($this->enable_merging_rows) {
                    $desc = 'Bet and Settle';
                }
                break;
            case self::REFUND_CODE:
                $desc = 'Refund';
                break;
            case self::BET_SETTLE_CODE:
                $desc = 'Bet and Settle';
                break;
            default:
                $desc = 'Unknown';
                break;
        }

        return $desc;
    }
}
//end of class