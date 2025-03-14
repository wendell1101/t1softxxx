<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: CQ9
 * Game Type: Live Casino, Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -cq9_seamless_service_api.php
 **/

class Game_api_cq9_seamless extends Abstract_game_api
{
    public $api_url;
    public $api_token;
    public $gamehall;
    public $language;
    public $currency;
    public $prefix_for_username;
    public $app;
    public $detect;
    public $sync_time_interval;
    public $sleep_time;
    public $http_method;
    public $seamless_service_callback_url;
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $use_session_token;
    public $show_request_params_guide;
    public $check_api_token;
    public $check_datetime_format;
    public $check_gamehall;
    public $session_methods;
    public $utc;
    public $use_transaction_data;
    public $use_game_lobby_link;
    public $page_size;
    public $leave_url;
    public $staging_api_url_for_demo;
    public $staging_api_token_for_demo;
    public $staging_test_game_account_for_demo;
    public $whitelist_ip_validate_api_methods;
    public $game_api_active_validate_api_methods;
    public $game_api_maintenance_validate_api_methods;
    public $check_same_gamehall;
    public $page;
    public $conversion;
    public $precision;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $live_dealer_game_language;
    public $force_disable_home_link;
    public $enable_merging_rows;
    public $show_demo_dollar_sign;

    const SEAMLESS_GAME_API_NAME = 'CQ9_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const API_SUCCESS_RESPONSE = '0';

    const LANGUAGE_CODE_ENGLISH = 'en';
    const LANGUAGE_CODE_CHINESE = 'zh-cn';
    const LANGUAGE_CODE_INDONESIAN = 'id';
    const LANGUAGE_CODE_VIETNAMESE = 'vi';
    const LANGUAGE_CODE_KOREAN = 'ko';
    const LANGUAGE_CODE_THAI = 'th';
    const LANGUAGE_CODE_HINDI = 'hi';
    const LANGUAGE_CODE_PORTUGUESE = 'pt-br';
    const LANGUAGE_CODE_TAIWANESE = 'zh-tw';

    const TRANSACTION_BET = 'bet';
    const TRANSACTION_ENDROUND = 'endround';
    const TRANSACTION_ROLLOUT = 'rollout';
    const TRANSACTION_TAKEALL = 'takeall';
    const TRANSACTION_ROLLIN = 'rollin';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_PAYOFF = 'payoff';
    const TRANSACTION_REFUND = 'refund';
    const TRANSACTION_BETS = 'bets';
    const TRANSACTION_REFUNDS = 'refunds';
    const TRANSACTION_CANCEL = 'cancel';
    const TRANSACTION_WIN = 'win';
    const TRANSACTION_WINS = 'wins';
    const TRANSACTION_AMENDS = 'amends';
    const TRANSACTION_AMEND = 'amend';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;

    const API_queryForwardGameLobby = 'queryForwardGameLobby';
    const API_getGameProviderList = 'getGameProviderList';

    const URI_MAP = [
        self::API_queryForwardGame => '/gameboy/player/sw/gamelink',
        self::API_queryForwardGameLobby => '/gameboy/player/sw/lobbylink',
        self::API_getGameProviderList => '/gameboy/game/halls',
        self::API_logout => '/gameboy/player/logout',
        self::API_queryGameListFromGameProvider => '/gameboy/game/list/',
        self::API_queryBetDetailLink => '/gameboy/order/detail/v2',
        self::API_syncGameRecords => '/gameboy/order/view',
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'gamehall',
        'gamecode',
        'gametype',
        'gameplat',
        'account',
        'round',
        'bet',
        'validbet',
        'win',
        'jackpot',
        'jackpotcontribution',
        'jackpottype',
        'balance',
        'status',
        'bettime',
        'endroundtime',
        'createtime',
        'detail',
        'rake',
        'singlerowbet',
        'ticketid',
        'tickettype',
        'giventype',
        'ticketbets',
        'gamerole',
        'bankertype',
        'roomfee',
        'bettype',
        'gameresult',
        'tabletype',
        'tableid',
        'roundnumber',
        'currency',
        'external_unique_id',
        'response_result_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet',
        'validbet',
        'win',
        'jackpot',
        'rake',
        'ticketbets',
        'roomfee',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_code',
        'account',
        'round_number',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'jackpot',
        'ticketbets',
        'rake',
        'roomfee',
        'after_balance',
        'status',
        'bet_at',
        'end_at',
        'start_at',
        'response_result_id',
        'external_uniqueid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'jackpot',
        'rake',
        'ticketbets',
        'roomfee',
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

    const SEAMLESS_DEBIT_TRANSACTION_TYPES = ["bet", "rollout", "takeall", "debit", "bets"];

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'cq9_seamless_wallet_transactions');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'cq9_seamless_game_logs');
        $this->seamless_service_callback_url = $this->getSystemInfo('seamless_service_callback_url', '');
        $this->http_method = self::HTTP_METHOD_GET;
        $this->api_url = $this->getSystemInfo('url');
        $this->api_token = $this->getSystemInfo('api_token');
        $this->gamehall = $this->getSystemInfo('gamehall');
        $this->language = $this->getSystemInfo('language', '');
        $this->currency = $this->getSystemInfo('currency');
        $this->app = $this->getSystemInfo('app', 'N');
        $this->detect = $this->getSystemInfo('detect', 'N');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->use_session_token = $this->getSystemInfo('use_session_token', true);
        $this->show_request_params_guide = $this->getSystemInfo('show_request_params_guide', false);
        $this->check_api_token = $this->getSystemInfo('check_api_token', true);
        $this->check_datetime_format = $this->getSystemInfo('check_datetime_format', true);
        $this->check_same_gamehall = $this->getSystemInfo('check_same_gamehall', false);
        $this->session_methods = $this->getSystemInfo('session_methods', ['bet', 'rollout', 'takeall', 'bets']);
        $this->utc = $this->getSystemInfo('utc', '-04:00');
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->use_game_lobby_link = $this->getSystemInfo('use_game_lobby_link', false);
        $this->page = $this->getSystemInfo('page', 1);
        $this->page_size = $this->getSystemInfo('page_size', 20000);
        $this->leave_url = $this->getSystemInfo('leave_url', $this->getHomeLink());
        $this->staging_api_url_for_demo = $this->getSystemInfo('staging_api_url_for_demo', '');
        $this->staging_api_token_for_demo = $this->getSystemInfo('staging_api_token_for_demo', '');
        $this->staging_test_game_account_for_demo = $this->getSystemInfo('staging_test_game_account_for_demo', ''); //game_username
        $this->whitelist_ip_validate_api_methods = $this->getSystemInfo('whitelist_ip_validate_api_methods', []);
        $this->game_api_active_validate_api_methods = $this->getSystemInfo('game_api_active_validate_api_methods', []);
        $this->game_api_maintenance_validate_api_methods = $this->getSystemInfo('game_api_maintenance_validate_api_methods', []);
        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', self::SEAMLESS_DEBIT_TRANSACTION_TYPES);
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->precision = $this->getSystemInfo('precision', 4);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', '');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->live_dealer_game_language = $this->getSystemInfo('live_dealer_game_language', '');
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        $this->show_demo_dollar_sign = $this->getSystemInfo('show_demo_dollar_sign', 'N');
        $this->live_dealer_lobby_game_code = $this->getSystemInfo('live_dealer_lobby_game_code', 'GINKGO01');
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 36);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', $this->minimum_user_length);
    }

    public function getPlatformCode()
    {
        return CQ9_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getTransactionsTable()
    {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getSeamlessTransactionTable()
    {
        return $this->getTransactionsTable();
    }

    public function getSeamlessGameLogsTable()
    {
        return $this->original_seamless_game_logs_table;
    }

    public function generateUrl($api_name, $params)
    {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->api_url . $api_uri;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryGameListFromGameProvider:
                    $url .= '/' . $params['gamehall'];
                    break;
                default:
                    $url .= '?' . http_build_query($params);
                    break;
            }
        }

        return $url;
    }

    protected function getHttpHeaders($params)
    {
        switch ($this->http_method) {
            case self::HTTP_METHOD_GET:
                $contentType = 'application/json';
                break;
            default:
                $contentType = 'application/x-www-form-urlencoded';
                break;
        }

        $headers = [
            'Authorization' => $this->api_token,
            'Content-type' => $contentType,
        ];

        return $headers;
    }

    protected function customHttpCall($ch, $params)
    {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null)
    {
        $success = false;

        if (isset($resultArr['status']['code']) && $resultArr['status']['code'] == self::API_SUCCESS_RESPONSE) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = 'Unable to create account for ' . self::SEAMLESS_GAME_API_NAME;

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for ' . self::SEAMLESS_GAME_API_NAME;
        }

        return ['success' => $success, 'message' => $message];
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];
    }

    public function queryPlayerBalance($playerName)
    {
        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = [
            'success' => true,
            'balance' => $balance
        ];

        return $result;
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 'en', // 1
            'zh_cn' => 'zh-cn', // 2
            'id_id' => 'id', // 3
            'vi_vn' => 'vn', // 4
            'ko_kr' => 'ko', // 5
            'th_th' => 'th', // 6
            'hi_in' => 'hi', // 7
            'pt_pt' => 'pt-br', // 8
            'es_es' => 'es', // 9
            'kk_kz' => 'kk', // 10
            'pt_br' => 'pt-br', // 11
            'ja_jp' => 'jp', // 12
            'es_mx' => 'es-mx', // 13
        ]);
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $this->CI->utils->debug_log("queryForwardGame cq9seamless extra-param", $extra);
        $this->http_method = self::HTTP_METHOD_POST;
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $game_code = isset($extra['game_code']) && !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) && !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $game_type = isset($extra['game_type']) && !empty($extra['game_type']) ? $extra['game_type'] : null;
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $language = !empty($this->language) ? $this->language : $this->getLauncherLanguage($extra['language']);
        $token = $this->getPlayerTokenByUsername($playerName);

        if (!empty($this->live_dealer_game_language)) {
            if ($game_type == self::GAME_TYPE_LIVE_DEALER) {
                $language = $this->live_dealer_game_language;
            }
        }

        if($game_type == self::GAME_TYPE_LIVE_DEALER && empty($game_code)){
            $game_code = $this->live_dealer_lobby_game_code;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'game_mode' => $game_mode,
        ];

        $params = [
            'account' => $gameUsername,
            'lang' => $language,
        ];

        if(isset($extra['home_link']) && !empty($extra['home_link'])) {
            $this->leave_url = $extra['home_link'];
        }

        if(isset($extra['extra']['home_link']) && !empty($extra['extra']['home_link'])) {
            $this->leave_url = $extra['extra']['home_link'];
        }

		if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($this->leave_url);
        }

        if($this->force_disable_home_link){
            unset($this->leave_url);
        }


        if ($is_mobile) {
            $game_plat = 'MOBILE';
        } else {
            $game_plat = 'WEB';
        }

        if (!empty($this->staging_api_url_for_demo) && $game_mode != 'real') {
            $this->api_url = $this->staging_api_url_for_demo;

            if (!empty($this->staging_api_token_for_demo)) {
                $this->api_token = $this->staging_api_token_for_demo;
            }

            $params['account'] = $this->staging_test_game_account_for_demo;
        } else {
            $params['session'] = $token;
        }

        if ($this->use_game_lobby_link) {
            $api_name = self::API_queryForwardGameLobby;
        } else {
            $api_name = self::API_queryForwardGame;
            $params['gamehall'] = $this->gamehall;
            $params['gamecode'] = $game_code;
            $params['gameplat'] = $game_plat;
            $params['app'] = $this->app;
            $params['detect'] = $this->detect;
        }

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $game_mode = $this->getVariableFromContext($params, 'game_mode');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);

        $result = [
            'responseResultId' => $responseResultId,
        ];

        if ($success && isset($resultArr['data'])) {
            if (isset($resultArr['data']['url']) && !empty($resultArr['data']['url'])) {
                if ($game_mode == 'real') {
                    if (!empty($this->leave_url)) {
                        $result['url'] = $resultArr['data']['url'] . '&leaveUrl=' . $this->leave_url;
                    } else {
                        $result['url'] = $resultArr['data']['url'];
                    }
                } else {
                    $result['url'] = $this->change_url_parameter_value($resultArr['data']['url'], [
                        'token' => 'guest',
                        'dollarsign' => $this->show_demo_dollar_sign,
                    ]);
                }
            } else {
                $result['url'] = null;
            }

            $result['token'] = isset($resultArr['data']['token']) ? $resultArr['data']['token'] : null;
        }

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return [$success, $result];
    }

    public function change_url_parameter_value($url, $params = [])
    {
        $url = parse_url($url);
        parse_str($url['query'], $parameters);
        
        foreach ($params as $key => $value) {
            if (isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        if (!empty($this->leave_url)) {
            $parameters['leaveUrl'] = $this->leave_url;
        }

        return sprintf('%s://%s%s?%s', $url['scheme'], $url['host'], $url['path'], http_build_query($parameters));
    }

    public function getGameProviderList()
    {
        $this->http_method = self::HTTP_METHOD_GET;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameProviderList',
        ];

        return $this->callApi(self::API_getGameProviderList, array(), $context);
    }

    public function processResultForGetGameProviderList($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);
        $result = [];

        if ($success) {
            $result['providers'] = $resultArr['data'];
        }

        return [$success, $result];
    }

    public function logout($playerName, $password = null)
    {
        $this->http_method = self::HTTP_METHOD_POST;
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName
        ];

        $params = [
            'account' => $gameUsername,
        ];

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);

        return [$success, $resultArr];
    }

    public function queryGameListFromGameProvider($extra = null)
    {
        $this->http_method = self::HTTP_METHOD_GET;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        $params = [
            'gamehall' => $this->gamehall,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        }

        return [$success, $result];
    }

    public function queryBetDetailLink($player_username, $external_unique_id = null, $round_id = null)
    {
        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($player_username, $external_unique_id, $round_id);
        }
        
        $this->http_method = self::HTTP_METHOD_GET;
        $gameUsername = $this->getGameUsernameByPlayerUsername($player_username);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'roundid' => $round_id,
            'account' => $gameUsername,
        ];

        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    public function processResultForQueryBetDetailLink($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'url' => null
        ];

        if ($success) {
            $result['url'] = $resultArr['data'];
        }

        return [$success, $result];
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false)
    {
        $this->http_method = self::HTTP_METHOD_GET;
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        #timestamp format
        $starttime = $startDate->format('Y-m-d\TH:i:s-04:00');
        $endtime = $endDate->format('Y-m-d\TH:i:s-04:00');

        $result = [];
        $continueSync = true;
        $page = $this->page;

        while ($continueSync) {
            $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
                'starttime' => $starttime,
                'endtime' => $endtime,
            ];

            $params = [
                'starttime' => $starttime,
                'endtime' => $endtime,
                'page' => $page,
                'pagesize' => $this->page_size,
            ];

            $resultArr[] = $result = $this->callApi(self::API_syncGameRecords, $params, $context);
            $continueSync = @$result['continueSync'];

            $this->CI->utils->debug_log(__METHOD__ . ' info ---------->', $params, 'continueSync', $continueSync);

            $page++;
        }

        return ['success' => true, $resultArr];
    }

    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model(['original_game_logs_model']);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0,
            'continueSync' => false,
        ];

        if($success && isset($resultArr['data']['Data']) && !empty($resultArr['data']['Data'])) {
            $extra['response_result_id'] = $responseResultId;
            $totalSize = isset($resultArr['data']['TotalSize']) ? $resultArr['data']['TotalSize'] : 0;
            $result['continueSync'] = $totalSize >= $this->page_size;
            $gameRecords = $this->rebuildGameRecords($resultArr['data']['Data'], $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_seamless_game_logs_table,
                $gameRecords,
                'external_unique_id',
                'external_unique_id',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log(__METHOD__ . ' after process available rows ---------->', 'gamerecords', count($gameRecords), 'insertrows', count($insertRows), 'updaterows', count($updateRows));

            $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

            if(!empty($insertRows)) {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }

            unset($insertRows);

            if(!empty($updateRows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }

            unset($updateRows);
        }

        return array($success, $result);
    }

    public function rebuildGameRecords($gameRecords, $extra)
    {
        foreach($gameRecords as $record) {

            # don't record if in progress bet
            /* if ($record['status'] != 'complete') {
                continue;
            } */

            $insertRecord = [];
            $insertRecord['gamehall'] = isset($record['gamehall']) ? $record['gamehall'] : null;
            $insertRecord['gamecode'] = isset($record['gamecode']) ? $record['gamecode'] : null;
            $insertRecord['gametype'] = isset($record['gametype']) ? $record['gametype'] : null;
            $insertRecord['gameplat'] = isset($record['gameplat']) ? $record['gameplat'] : null;
            $insertRecord['account'] = isset($record['account']) ? $record['account'] : null;
            $insertRecord['round'] = isset($record['round']) ? $record['round'] : null;
            $insertRecord['bet'] = isset($record['bet']) && !empty($record['bet']) ? $record['bet'] : 0;
            $insertRecord['validbet'] = isset($record['validbet']) && !empty($record['validbet']) ? $record['validbet'] : 0;
            $insertRecord['win'] = isset($record['win']) && !empty($record['win']) ? $record['win'] : 0;
            $insertRecord['jackpot'] = isset($record['jackpot']) && !empty($record['jackpot']) ? $record['jackpot'] : 0;
            $insertRecord['jackpotcontribution'] = isset($record['jackpotcontribution']) ? json_encode($record['jackpotcontribution']) : null;
            $insertRecord['jackpottype'] = isset($record['jackpottype']) ? $record['jackpottype'] : null;
            $insertRecord['balance'] = isset($record['balance']) && !empty($record['balance']) ? $record['balance'] : 0;
            $insertRecord['status'] = isset($record['status']) ? $record['status'] : null;
            $insertRecord['bettime'] = isset($record['bettime']) ? date('Y-m-d H:i:s', strtotime($record['bettime'])) : null;
            $insertRecord['endroundtime'] = isset($record['endroundtime']) ? date('Y-m-d H:i:s', strtotime($record['endroundtime'])) : null;
            $insertRecord['createtime'] = isset($record['createtime']) ? date('Y-m-d H:i:s', strtotime($record['createtime'])) : null;
            $insertRecord['detail'] = isset($record['detail']) ? json_encode($record['detail']) : null;
            $insertRecord['rake'] = isset($record['rake']) && !empty($record['rake']) ? $record['rake'] : 0;
            $insertRecord['singlerowbet'] = isset($record['singlerowbet']) ? $record['singlerowbet'] : null;
            $insertRecord['ticketid'] = isset($record['ticketid']) ? $record['ticketid'] : null;
            $insertRecord['tickettype'] = isset($record['tickettype']) ? $record['tickettype'] : null;
            $insertRecord['giventype'] = isset($record['giventype']) ? $record['giventype'] : null;
            $insertRecord['ticketbets'] = isset($record['ticketbets']) && !empty($record['ticketbets']) ? $record['ticketbets'] : 0;
            $insertRecord['gamerole'] = isset($record['gameRole']) ? $record['gameRole'] : null;
            $insertRecord['bankertype'] = isset($record['bankerType']) ? $record['bankerType'] : null;
            $insertRecord['roomfee'] = isset($record['roomfee']) && !empty($record['roomfee']) ? $record['roomfee'] : 0;
            $insertRecord['bettype'] = isset($record['bettype']) ? json_encode($record['bettype']) : null;
            $insertRecord['gameresult'] = isset($record['gameresult']) ? json_encode($record['gameresult']) : null;
            $insertRecord['tabletype'] = isset($record['tabletype']) ? $record['tabletype'] : null;
            $insertRecord['tableid'] = isset($record['tableid']) ? $record['tableid'] : null;
            $insertRecord['roundnumber'] = isset($record['roundnumber']) ? $record['roundnumber'] : null;
            $insertRecord['currency'] = isset($record['currency']) ? $record['currency'] : null;

            //extra info from SBE
            $insertRecord['external_unique_id'] = isset($record['gamehall']) && isset($record['round']) ? $record['round'] . ':' . $record['gamehall'] : null;
            $insertRecord['response_result_id'] = $extra['response_result_id'];

            $dataRecords[] = $insertRecord;
        }

        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType) {
        $dataCount = 0;

        if(!empty($data)) {
            foreach($data as $record) {
                if($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_seamless_game_logs_table, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_seamless_game_logs_table, $record);
                }

                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function syncOriginalGameLogsFromTrans($token = false)
    {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $endDate->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_BET, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $endround_transaction = $this->queryPlayerTransaction(self::TRANSACTION_ENDROUND, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $win_transaction = $this->queryPlayerTransaction(self::TRANSACTION_WIN, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $rollout_transaction = $this->queryPlayerTransaction(self::TRANSACTION_ROLLOUT, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $takeall_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TAKEALL, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $rollin_transaction = $this->queryPlayerTransaction(self::TRANSACTION_ROLLIN, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $refund_transaction = $this->queryPlayerTransaction(self::TRANSACTION_REFUND, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $cancel_transaction = $this->queryPlayerTransaction(self::TRANSACTION_CANCEL, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $debit_transaction = $this->queryPlayerTransaction(self::TRANSACTION_DEBIT, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $credit_transaction = $this->queryPlayerTransaction(self::TRANSACTION_CREDIT, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);
                $amend_transaction = $this->queryPlayerTransaction(self::TRANSACTION_AMEND, $transaction['player_id'], $transaction['game_id'], $transaction['round_id']);

                $transaction_type = $transaction['transaction_type'];
                $transaction_action = $transaction['action'];
                $transaction_amount = isset($transaction['amount']) ? $transaction['amount'] : 0;
                $transaction_extra_info = isset($transaction['extra_info']) && !empty($transaction['extra_info']) ? json_decode($transaction['extra_info'], true) : [];
                $transaction_rollin_bet = isset($transaction_extra_info['bet']) && !empty($transaction_extra_info['bet']) ? $transaction_extra_info['bet'] / $this->adjustment_conversion : 0;
                $transaction_rollin_result = isset($transaction_extra_info['win']) && !empty($transaction_extra_info['win']) ? $transaction_extra_info['win'] / $this->adjustment_conversion : 0;

                $rollin_extra_info = isset($rollin_transaction['extra_info']) && !empty($rollin_transaction['extra_info']) ? json_decode($rollin_transaction['extra_info'], true) : [];
                $rollin_bet_amount = isset($rollin_extra_info['bet']) && !empty($rollin_extra_info['bet']) ? $rollin_extra_info['bet'] / $this->adjustment_conversion : 0;
                $rollin_win_amount = isset($rollin_extra_info['win']) && !empty($rollin_extra_info['win']) ? $rollin_extra_info['win'] / $this->adjustment_conversion : 0;

                $bet_amount = isset($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                $endround_amount = isset($endround_transaction['amount']) ? $endround_transaction['amount'] : 0;
                $win_amount = isset($win_transaction['amount']) ? $win_transaction['amount'] : 0;
                $rollout_amount = isset($rollout_transaction['amount']) ? $rollout_transaction['amount'] : 0;
                $takeall_amount = isset($takeall_transaction['amount']) ? $takeall_transaction['amount'] : 0;
                $rollin_amount = isset($rollin_transaction['amount']) ? $rollin_transaction['amount'] : 0;
                $refund_amount = isset($refund_transaction['amount']) ? $refund_transaction['amount'] : 0;
                $cancel_amount = isset($cancel_transaction['amount']) ? $cancel_transaction['amount'] : 0;
                $debit_amount = isset($debit_transaction['amount']) ? $debit_transaction['amount'] : 0;
                $credit_amount = isset($credit_transaction['amount']) ? $credit_transaction['amount'] : 0;
                $amend_amount = isset($amend_transaction['amount']) ? $amend_transaction['amount'] : 0;

                $transaction_external_unique_id = isset($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null;
                $bet_external_unique_id = isset($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null;
                $endround_external_unique_id = isset($endround_transaction['external_unique_id']) ? $endround_transaction['external_unique_id'] : null;
                $win_external_unique_id = isset($win_transaction['external_unique_id']) ? $win_transaction['external_unique_id'] : null;
                $rollout_external_unique_id = isset($rollout_transaction['external_unique_id']) ? $rollout_transaction['external_unique_id'] : null;
                $takeall_external_unique_id = isset($takeall_transaction['external_unique_id']) ? $takeall_transaction['external_unique_id'] : null;
                $rollin_external_unique_id = isset($rollin_transaction['external_unique_id']) ? $rollin_transaction['external_unique_id'] : null;
                $refund_external_unique_id = isset($refund_transaction['external_unique_id']) ? $refund_transaction['external_unique_id'] : null;
                $debit_external_unique_id = isset($debit_transaction['external_unique_id']) ? $debit_transaction['external_unique_id'] : null;
                $credit_external_unique_id = isset($credit_transaction['external_unique_id']) ? $credit_transaction['external_unique_id'] : null;
                $amend_external_unique_id = isset($amend_transaction['external_unique_id']) ? $amend_transaction['external_unique_id'] : null;

                if ($transaction_type == self::TRANSACTION_BET) {
                    $bet_data['status'] = $transaction['status'];
                    $bet_data['bet_amount'] = $transaction_amount;
                    $bet_data['win_amount'] = 0;
                    $bet_data['result_amount'] = -$transaction_amount;
                    $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_ENDROUND) {
                    if ($this->enable_merging_rows) {
                        $endround_data['flag_of_updated_result'] = self::FLAG_UPDATED;

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = $transaction_amount;
                        $bet_data['result_amount'] = $transaction_amount - $bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    } else {
                        $endround_data['status'] = $transaction['status'];
                        $endround_data['bet_amount'] = 0;
                        $endround_data['win_amount'] = $transaction_amount;
                        $endround_data['result_amount'] = $transaction_amount;
                        $endround_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        $endround_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($endround_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = -$bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    }

                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $endround_data, 'external_unique_id', $transaction_external_unique_id);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_REFUND) {
                    if ($this->enable_merging_rows) {
                        $refund_data['flag_of_updated_result'] = self::FLAG_UPDATED;

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = $transaction_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    } else {
                        $refund_data['status'] = $transaction['status'];
                        $refund_data['bet_amount'] = 0;
                        $refund_data['win_amount'] = 0;
                        $refund_data['result_amount'] = $transaction_amount;
                        $refund_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        $refund_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($refund_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = -$bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    }

                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $refund_data, 'external_unique_id', $transaction_external_unique_id);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_WIN) {
                    if ($this->enable_merging_rows) {
                        $win_data['flag_of_updated_result'] = self::FLAG_UPDATED;
                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = $transaction_amount;
                        $bet_data['result_amount'] = $transaction_amount - $bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    } else {
                        $win_data['status'] = $transaction['status'];
                        $win_data['bet_amount'] = 0;
                        $win_data['win_amount'] = $transaction_amount;
                        $win_data['result_amount'] = $transaction_amount;
                        $win_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        $win_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($win_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = -$bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    }

                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction_external_unique_id);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_ROLLOUT) {
                    $rollout_data['status'] = $transaction['status'];
                    $rollout_data['bet_amount'] = $transaction_amount;
                    $rollout_data['win_amount'] = 0;
                    $rollout_data['result_amount'] = -$transaction_amount;
                    $rollout_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    $rollout_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($rollout_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollout_data, 'external_unique_id', $transaction_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_TAKEALL) {
                    $takeall_data['status'] = $transaction['status'];
                    $takeall_data['bet_amount'] = $transaction_amount;
                    $takeall_data['win_amount'] = 0;
                    $takeall_data['result_amount'] = -$transaction_amount;
                    $takeall_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    $takeall_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($takeall_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $takeall_data, 'external_unique_id', $transaction_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_ROLLIN) {
                    $rollin_data['flag_of_updated_result'] = self::FLAG_UPDATED;
                    $bet_amount = $transaction_rollin_bet;
                    $win_amount = $transaction_amount;
                    $result_amount = $transaction_rollin_result;

                    if ($win_amount == 0 && $result_amount > 0) {
                        $win_amount = $result_amount;
                        $result_amount -= $bet_amount;
                    }

                    if (!empty($rollout_transaction['external_unique_id'])) {
                        if ($this->enable_merging_rows) {
                            $rollout_data['status'] = $transaction['status'];
                            $rollout_data['bet_amount'] = $bet_amount;
                            $rollout_data['win_amount'] = $win_amount;
                            $rollout_data['result_amount'] = $result_amount;
                            $rollout_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        } else {
                            $rollin_data['status'] = $transaction['status'];
                            $rollin_data['bet_amount'] = 0;
                            $rollin_data['win_amount'] = $win_amount;
                            $rollin_data['result_amount'] = $win_amount;
                            $rollin_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                            $rollin_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($rollin_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                            $rollout_data['status'] = $transaction['status'];
                            $rollout_data['bet_amount'] = $bet_amount;
                            $rollout_data['win_amount'] = 0;
                            $rollout_data['result_amount'] = -$bet_amount;
                            $rollout_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        }

                        $rollout_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($rollout_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                        $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollin_data, 'external_unique_id', $transaction_external_unique_id);
                        $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollout_data, 'external_unique_id', $rollout_external_unique_id);
                    }

                    if (!empty($takeall_transaction['external_unique_id'])) {
                        if ($this->enable_merging_rows) {
                            $rollout_data['status'] = $transaction['status'];
                            $rollout_data['bet_amount'] = $bet_amount;
                            $rollout_data['win_amount'] = $win_amount;
                            $rollout_data['result_amount'] = $result_amount;
                            $rollout_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        } else {
                            $rollin_data['status'] = $transaction['status'];
                            $rollin_data['bet_amount'] = 0;
                            $rollin_data['win_amount'] = $win_amount;
                            $rollin_data['result_amount'] = $win_amount;
                            $rollin_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                            $rollin_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($rollin_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                            $takeall_data['status'] = $transaction['status'];
                            $takeall_data['bet_amount'] = $bet_amount;
                            $takeall_data['win_amount'] = 0;
                            $takeall_data['result_amount'] = -$bet_amount;
                            $takeall_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        }

                        $takeall_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($takeall_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                        $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollin_data, 'external_unique_id', $transaction_external_unique_id);
                        $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $takeall_data, 'external_unique_id', $takeall_external_unique_id);
                    }
                }

                // This is like adjustment API
                if ($transaction_type == self::TRANSACTION_DEBIT) {
                    if ($this->enable_merging_rows) {
                        $debit_data['flag_of_updated_result'] = self::FLAG_UPDATED;
                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = ($endround_amount + $win_amount + $credit_amount) - $transaction_amount;
                        $bet_data['result_amount'] = $bet_data['win_amount'] - $bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    } else {
                        $debit_data['status'] = $transaction['status'];
                        $debit_data['bet_amount'] = 0;
                        $debit_data['win_amount'] = 0;
                        $debit_data['result_amount'] = -$transaction_amount;
                        $debit_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        $debit_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($debit_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = -$bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    }

                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $debit_data, 'external_unique_id', $transaction_external_unique_id);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                }

                // This is like adjustment API
                if ($transaction_type == self::TRANSACTION_CREDIT) {
                    if ($this->enable_merging_rows) {
                        $credit_data['flag_of_updated_result'] = self::FLAG_UPDATED;
                        $bet_data['status'] = Game_logs::STATUS_SETTLED;
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = ($endround_amount + $win_amount + $transaction_amount) - $debit_amount;
                        $bet_data['result_amount'] = $bet_data['win_amount'] - $bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    } else {
                        $credit_data['status'] = $transaction['status'];
                        $credit_data['bet_amount'] = 0;
                        $credit_data['win_amount'] = $transaction_amount;
                        $credit_data['result_amount'] = $transaction_amount;
                        $credit_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        $credit_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($credit_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = -$bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    }
                    
                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $credit_data, 'external_unique_id', $transaction_external_unique_id);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                }

                if ($transaction_type == self::TRANSACTION_AMEND) {
                    if ($this->enable_merging_rows) {
                        $amend_data['flag_of_updated_result'] = self::FLAG_UPDATED;
                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;

                        if ($transaction_action == self::TRANSACTION_DEBIT) {
                            $bet_data['win_amount'] = ($endround_amount + $win_amount + $credit_amount) - $transaction_amount;
                        } else {
                            $bet_data['win_amount'] = ($endround_amount + $win_amount + $transaction_amount) - $debit_amount;
                        }

                        $bet_data['result_amount'] = $bet_data['win_amount'] - $bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                     } else {
                        if ($transaction_action == self::TRANSACTION_DEBIT) {
                            $amend_data['win_amount'] = 0;
                            $amend_data['result_amount'] = -$transaction_amount;
                        } else {
                            $amend_data['win_amount'] = $transaction_amount;
                            $amend_data['result_amount'] = $transaction_amount;
                        }

                        $amend_data['status'] = $transaction['status'];
                        $amend_data['bet_amount'] = 0;
                        $amend_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                        $amend_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($amend_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);

                        $bet_data['status'] = $transaction['status'];
                        $bet_data['bet_amount'] = $bet_amount;
                        $bet_data['win_amount'] = 0;
                        $bet_data['result_amount'] = -$bet_amount;
                        $bet_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                    }
                    
                    $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $amend_data, 'external_unique_id', $transaction_external_unique_id);
                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                }

                $start_at = isset($transaction['start_at']) ? $transaction['start_at'] : null;
                $updated_at = isset($transaction['updated_at']) ? $transaction['updated_at'] : null;

                $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'start_at', $start_at, 'updated_at', $updated_at);
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->CI->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        return ['success' => true, $result];
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null)
    {
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
status,
game_id,
round_id,
amount,
action,
external_unique_id,
start_at,
updated_at,
extra_info
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

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_id, $round_id)
    {
        $sql = <<<EOD
SELECT DISTINCT
player_id,
id,
sum(amount) as amount,
action,
status,
external_unique_id,
extra_info
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_id = ? AND round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $player_id,
            $game_id,
            $round_id,
        ];

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }

    public function queryTransactionAfterBalance($player_id, $game_id, $round_id)
    {
        $sql = <<<EOD
SELECT
id,
after_balance
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND player_id = ? AND game_id = ? AND round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $player_id,
            $game_id,
            $round_id,
        ];

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params); #query all
        if(!empty($results)){
            $keys = array_column($results, 'id');
            array_multisort($keys, SORT_ASC, $results); #sort array
            $last_row = end($results); #get last row
            $after_balance = isset($last_row['after_balance']) ? $last_row['after_balance'] : null;
        } else {
            $after_balance =  null;
        }
        return $after_balance;
    }

    public function syncMergeToGameLogs($token)
    {
        $this->syncOriginalGameLogsFromTrans($token);
        $enabled_game_logs_unsettle = true;

        if ($this->use_transaction_data) {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTransMerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                [$this, 'preprocessOriginalRowForGameLogsFromTransMerge'],
                $enabled_game_logs_unsettle
            );
            /* if ($this->enable_merging_rows) {
                return $this->commonSyncMergeToGameLogs(
                    $token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTransMerge'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                    [$this, 'preprocessOriginalRowForGameLogsFromTransMerge'],
                    $enabled_game_logs_unsettle
                );
            }else{
                return $this->commonSyncMergeToGameLogs(
                    $token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTrans'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                    [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                    $enabled_game_logs_unsettle
                );
            } */
        } else {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogs'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
                [$this, 'preprocessOriginalRowForGameLogs'],
                $enabled_game_logs_unsettle
            );
        }
    }

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'osgl.updated_at >= ? AND osgl.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'osgl.bettime >= ? AND osgl.bettime <= ?';
        }

        $sql = <<<EOD
SELECT
osgl.id AS sync_index,
osgl.gamecode AS game_code,
osgl.account,
osgl.round AS round_number,
osgl.bet as bet_amount,
osgl.validbet as real_betting_amount,
osgl.win as win_amount,
osgl.jackpot,
osgl.ticketbets,
osgl.rake,
osgl.roomfee,
osgl.balance as after_balance,
osgl.status,
osgl.bettime as bet_at,
osgl.endroundtime as end_at,
osgl.createtime as start_at,
osgl.response_result_id,
osgl.external_unique_id as external_uniqueid,
osgl.created_at,
osgl.updated_at,
osgl.md5_sum,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game,
game_provider_auth.player_id,
game_provider_auth.login_name AS player_username
FROM {$this->original_seamless_game_logs_table} AS osgl
LEFT JOIN game_description ON osgl.gamecode = game_description.external_game_id AND game_description.game_platform_id = ?
JOIN game_provider_auth ON osgl.account = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

        return $data;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];

        if ($row['status'] == 'complete') {
            if ($row['result_amount'] > 0) {
                $row['note'] = 'Win';
            } elseif ($row['result_amount'] < 0) {
                $row['note'] = 'Lose';
            } elseif ($row['result_amount'] == 0) {
                $row['note'] = 'Draw';
            } else {
                $row['note'] = 'Free Game';
            }
            $row['status'] = Game_logs::STATUS_SETTLED;
        } else {
            $row['note'] = $row['status'];
            $row['status'] = Game_logs::STATUS_PENDING;
        }
    }

    /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
transaction.id AS sync_index,
transaction.player_id,
transaction.before_balance,
transaction.after_balance,
transaction.game_id AS game_code,
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
transaction.extra_info,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game,
game_provider_auth.login_name AS player_username
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row)
    {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS);
        }

        $extra_info = isset($row['extra_info'])?json_decode($row['extra_info'], true):[];

        $row['extra'] = [
            'jackpot_wins'=> [isset($extra_info['jackpot'])?$extra_info['jackpot']:0], 
            'progressive_contributions'=>[isset($extra_info['jackpotcontribution'])?$extra_info['jackpotcontribution']:0]
        ];
        $betDetails =$this->preprocessBetDetails($row,null,true);


        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            'bet_details' => $betDetails,
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

        return $data;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogsFromTransMerge(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        // if ($row['transaction_type'] == self::TRANSACTION_ROLLOUT || $row['transaction_type'] == self::TRANSACTION_TAKEALL) {
        //     if ($row['after_balance'] == 0) {
        //         $row['after_balance'] = $this->queryTransactionAfterBalance(self::TRANSACTION_ROLLIN, $row['player_id'], $row['game_code'], $row['round_number']);
        //     } else {
        //         $row['after_balance'] += $row['result_amount'];
        //     }
        // } else {
        //     $row['after_balance'] += $row['result_amount'];
        // }
        // 

        if ($this->enable_merging_rows) {
            $row['after_balance'] = $this->queryTransactionAfterBalance($row['player_id'], $row['game_code'], $row['round_number']);
        }

        if ($row['status'] == Game_logs::STATUS_SETTLED) {
            if ($row['result_amount'] > 0) {
                $row['note'] = 'Win';
            } elseif ($row['result_amount'] < 0) {
                $row['note'] = 'Lose';
            } elseif ($row['result_amount'] == 0) {
                $row['note'] = 'Draw';
            } else {
                $row['note'] = 'Free Game';
            }
        } elseif ($row['status'] == Game_logs::STATUS_REFUND) {
            $row['note'] = 'Refund';
        } elseif ($row['status'] == Game_logs::STATUS_CANCELLED) {
            $row['note'] = 'Cancel Refund';
        } else {
            $row['status'] = Game_logs::STATUS_SETTLED;
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;

        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if (empty($game_description_id)) {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function queryBetWinFromExtraInfo($transaction_type, $round_id) {
        $sql = <<<EOD
SELECT
JSON_UNQUOTE(extra_info->'$.bet') as bet_amount,
JSON_UNQUOTE(extra_info->'$.win') as win_amount
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $round_id,
        ];

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $bet_amount = !empty($results[0]['bet_amount']) ? $results[0]['bet_amount'] : 0;
        $win_amount = !empty($results[0]['win_amount']) ? $results[0]['win_amount'] : 0;

        return array($bet_amount, $win_amount);
    }
    
    /* public function processTransactions(&$transactions) {
        $temp_game_records = [];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                list($bet_amount, $win_amount) = $this->queryBetWinFromExtraInfo($transaction['trans_type'], $transaction['round_no']);
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];

                if ($transaction['trans_type'] == self::TRANSACTION_ROLLOUT) {
                    $temp_game_record['amount'] = abs($bet_amount);
                } elseif ($transaction['trans_type'] == self::TRANSACTION_ROLLIN) {
                    $temp_game_record['amount'] = abs($win_amount);
                } else {
                    $temp_game_record['amount'] = abs($transaction['amount']);
                }

                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];

                if (empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])) {
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                //$extra_info = @json_encode($transaction['extra_info'], true);
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if (in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                if (isset($transaction['transaction_type'])) {
                    $temp_game_record['transaction_type'] = $transaction['transaction_type'];
                }

                if (isset($transaction['md5_sum'])) {
                    $temp_game_record['md5_sum'] = $transaction['md5_sum'];
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    } */

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['game'])) {
            $bet_details['game_name'] = $row['game'];
        }
        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }
        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }
        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }

        if (isset($row['extra'])) {
            $bet_details['extra'] = $row['extra'];
        }

        return $bet_details;
     }

    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('amount', 'transaction.after_balance', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
    game_description.game_type_id,
    game_description.id AS game_description_id,
    transaction.game_id,
    game_description.english_name AS game,

    transaction.player_id,
    transaction.account AS player_username,

    transaction.bet_amount as bet_amount, 
    transaction.amount as result_amount,
    transaction.after_balance,

    transaction.start_at,
    transaction.end_at,
    transaction.updated_at,
    
    transaction.status,
    transaction.external_unique_id as external_uniqueid,
    transaction.round_id,
    MD5(CONCAT({$md5Fields})) AS md5_sum,
    transaction.response_result_id,
    transaction.id as sync_index,

    transaction.transaction_type

FROM
    {$this->original_seamless_wallet_transactions_table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?

WHERE
    transaction.game_platform_id = ? AND 
    transaction.transaction_type != 'refund' AND {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set result amount
        if ($row['transaction_type'] == self::TRANSACTION_BET) {
            $row['result_amount'] = -$row['result_amount'];
        }
    }

        
    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='T.created_at >= ? AND T.created_at <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $transType = 'bet';
        $notExistsTransType = 'endround';

        $sql = <<<EOD
SELECT 
	T.status, 
	T.round_id, 
	T.transaction_id, 
	T.game_platform_id, 
	T.transaction_type
FROM {$this->original_transactions_table} AS T
WHERE NOT EXISTS (
        SELECT 'exists'
        FROM {$this->original_transactions_table} AS T2
        WHERE T2.round_id = T.round_id
        AND T2.transaction_type=?
    )
AND T.transaction_type=?
AND {$sqlTime}
EOD;


        $params=[
            $notExistsTransType,
            $transType,
            $dateFrom,
            $dateTo
		];
        $platformCode = $this->getPlatformCode();
	    $this->CI->utils->debug_log('CQ9 SEAMLESS-' .$platformCode.' (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
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
	T.created_at as transaction_date,
	T.transaction_type,
	T.status, 
	T.game_platform_id,
	T.player_id,
	T.round_id,
	T.transaction_id,
	ABS(SUM(T.bet_amount)) as amount,
	ABS(SUM(T.bet_amount)) as deducted_amount,
	GD.id as game_description_id,
	GD.game_type_id,
	T.external_unique_id as external_uniqueid
FROM {$this->original_transactions_table} as T
LEFT JOIN game_description AS GD ON T.game_id = GD.external_game_id AND GD.game_platform_id=?
WHERE  T.round_id=?
AND T.transaction_id =?
AND T.game_platform_id=?
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
                    $this->CI->utils->error_log('CQ9 SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $transaction);
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
	T.status, 
	T.round_id, 
	T.transaction_id, 
	T.game_platform_id, 
	T.transaction_type
FROM {$this->original_transactions_table} AS T
WHERE NOT EXISTS (
        SELECT 'exists'
        FROM {$this->original_transactions_table} AS T2
        WHERE T2.round_id = T.round_id
        AND T2.transaction_type = 'endround'
        OR T2.transaction_type = 'refund'
    )
		
AND T.external_unique_id=?
AND T.game_platform_id=?
EOD;
     
        $params=[$external_uniqueid, $game_platform_id ];

        $trans = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
	    $this->CI->utils->debug_log('CQ9 SEAMLESS-' .$this->getPlatformCode().' (queryBetTransactionStatus)', 'params',$params,'sql',$sql);

        if(!empty($trans)){
            return array('success'=>false, 'status'=>Game_logs::STATUS_PENDING);
        }
        
        return array('success'=>true, 'status'=>Game_logs::STATUS_SETTLED);
    }

}
//end of class