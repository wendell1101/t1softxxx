<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: AWC
 * Game Type: Cockfighting, Slots, Live Casino
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -awc_universal_seamless_service_api.php
 **/

class Game_api_awc_universal_seamless extends Abstract_game_api {
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

    // additional
    public $agent_id;
    public $cert;
    public $platform;
    public $game_type;
    public $has_bet_limit_setting;
    public $bet_limit;
    public $lobby_url;
    public $game_forbidden;
    public $auto_bet_mode;
    public $is_auto_bet_mode_supported;
    public $game_lobby_code;
    public $hall;
    public $is_hall_supported;
    public $is_launch_game_table;
    public $game_table_id;

    public $fix_username_limit;
    public $minimum_user_length;
    public $maximum_user_length;
    public $default_fix_name_length;

    const SEAMLESS_GAME_API_NAME = 'AWC_UNIVERSAL';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCESS = 0000;
    const RESPONSE_CODE_USER_ALREADY_EXISTS = 1001;

    const API_METHOD_BET = 'bet';
    const API_METHOD_CANCEL_BET = 'cancelBet';
    const API_METHOD_ADJUST_BET = 'adjustBet';
    const API_METHOD_VOID_BET = 'voidBet';
    const API_METHOD_UNVOID_BET = 'unvoidBet';
    const API_METHOD_REFUND = 'refund';
    const API_METHOD_SETTLE = 'settle';
    const API_METHOD_UNSETTLE = 'unsettle';
    const API_METHOD_VOID_SETTLE = 'voidSettle';
    const API_METHOD_UNVOID_SETTLE = 'unvoidSettle';
    const API_METHOD_BET_AND_SETTLE = 'betNSettle';
    const API_METHOD_CANCEL_BET_AND_SETTLE = 'cancelBetNSettle';
    const API_METHOD_FREE_SPIN = 'freeSpin';
    const API_METHOD_GIVE = 'give';
    const API_METHOD_RESETTLE = 'resettle';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const API_updateBetLimit = 'updateBetLimit';
    const API_queryForwardGameLobby = 'queryForwardGameLobby';

    const URI_MAP = [
        self::API_createPlayer => '/wallet/createMember',
        self::API_updateBetLimit => '/wallet/updateBetLimit',
        self::API_queryForwardGame => '/wallet/doLoginAndLaunchGame',
        self::API_queryForwardGameLobby => '/wallet/login',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',

        // additional
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

    const AUTO_BET_MODE_SUPPORTED_PLATFORMS = ['SEXYBCRT', 'VENUS'];

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
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'awc_universal_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'awc_universal_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'awc_universal_seamless_service_logs');
        $this->save_game_seamless_service_logs = $this->getSystemInfo('save_game_seamless_service_logs', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');
        $this->get_usec = $this->getSystemInfo('get_usec', true);
        $this->use_bet_time = $this->getSystemInfo('use_bet_time', true);

        // additional
        $this->agent_id = $this->getSystemInfo('agent_id');
        $this->cert = $this->getSystemInfo('cert');
        $this->platform = $this->getSystemInfo('platform');
        $this->game_type = $this->getSystemInfo('game_type');
        $this->has_bet_limit_setting = $this->getSystemInfo('has_bet_limit_setting', false);
        $this->bet_limit = $this->getSystemInfo('bet_limit', '');
        $this->lobby_url = $this->getSystemInfo('lobby_url', '');
        $this->game_forbidden = $this->getSystemInfo('game_forbidden', '');
        $this->auto_bet_mode = $this->getSystemInfo('auto_bet_mode', true);
        $this->is_auto_bet_mode_supported = $this->getSystemInfo('is_auto_bet_mode_supported', false);
        $this->game_lobby_code = $this->getSystemInfo('game_lobby_code', 'lobby');
        $this->is_hall_supported = $this->getSystemInfo('is_hall_supported', false);
        $this->hall = $this->getSystemInfo('hall', 'SEXY');
        $this->is_launch_game_table = $this->getSystemInfo('is_launch_game_table', false);
        $this->game_table_id = $this->getSystemInfo('game_table_id', '');

        // fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 16);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 8);
    }

    public function getPlatformCode() {
        return null;
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
            'Content-type' => 'application/x-www-form-urlencoded',
        ];

        return $headers;
    }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null) {
        $success = false;

        if (isset($resultArr['status']) && ($resultArr['status'] == self::RESPONSE_CODE_SUCCESS || $resultArr['status'] == self::RESPONSE_CODE_USER_ALREADY_EXISTS)) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
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

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'userId' => $gameUsername,
            'currency' => $this->currency,
            'language' => $this->language,
        ];

        if ($this->has_bet_limit_setting) {
            $params['betLimit'] = $this->bet_limit;
        }

        $this->http_method = self::HTTP_METHOD_POST;
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
        $result = [
            'response_result_id' => $responseResultId,
            'player_name' => $playerName,
            'player_game_username' => $gameUsername,
        ];
        
        if ($success) {
            if (!empty($resultArr)) {
                $result['response_result'] = $resultArr;
            }

            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        $this->utils->debug_log('<---------- processResultForCreatePlayer ---------->', 'processResultForCreatePlayer_result', 'result: ' . json_encode($result));

        return [$success, $result];
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
            'zh_cn' => 'cn',
            'id_id' => 'id',
            'vi_vn' => 'vn',
            'ko_kr' => 'kr',
            'th_th' => 'th',
            'hi_in' => 'hi',
            'pt_br' => 'pt',
        ]);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
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

        if (!empty($extra['home_link'])) {
            $lobby_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $lobby_url = $extra['extra']['home_link'];
        } else {
            $lobby_url = $this->lobby_url;
        }

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'userId' => $gameUsername,
            'isMobileLogin' => $is_mobile,
            'externalURL' => $lobby_url,
            'platform' => $this->platform,
            'gameType' => $this->game_type,
            'language' => $language,
        ];

        if (!empty($this->game_forbidden)) {
            $params['gameForbidden'] = $this->game_forbidden;
        }

        if (!empty($this->bet_limit)) {
            $params['betLimit'] = $this->bet_limit;
        }

        if ($this->is_auto_bet_mode_supported) {
            $params['autoBetMode'] = $this->auto_bet_mode;
        }

        if (!empty($game_code) && $game_code != $this->game_lobby_code) {
            $api_name = self::API_queryForwardGame;
            $params['gameCode'] = $game_code;
        } else {
            $api_name = self::API_queryForwardGameLobby;
        }

        if ($this->is_hall_supported) {
            $params['hall'] = $this->hall;
        }

        if ($this->is_launch_game_table) {
            $params['isLaunchGameTable'] = $this->is_launch_game_table;
        }

        if (!empty($this->game_table_id)) {
            $params['gameTableId'] = $this->game_table_id;
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'extra', $extra);

        $this->http_method = self::HTTP_METHOD_POST;
        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'url' => '',
        ];

        if ($success) {
            $result['url'] = isset($resultArr['url']) ? $resultArr['url'] : '';
            $this->utils->debug_log(__METHOD__, $resultArr);
        }

        return [$success, $result];
    }

    public function updateBetLimit($playerName, $params) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdateBetLimit',
            'playerName' => $playerName,
        ];

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'userId' => $gameUsername,
            'betLimit' => $this->bet_limit,
        ];

        $this->http_method = self::HTTP_METHOD_POST;
        return $this->callApi(self::API_updateBetLimit, $params, $context);
    }

    public function processResultForUpdateBetLimit($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $statusCode, $playerName);

        return array($success, $resultJson);
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
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd, null, $this->use_bet_time);

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

                    // additional
                    'reference_id' => !empty($transaction['reference_transaction_id']) ? $transaction['reference_transaction_id'] : null,
                    'refund_id' => !empty($transaction['refund_transaction_id']) ? $transaction['refund_transaction_id'] : null,
                    'promotion_id' => !empty($transaction['promotion_id']) ? $transaction['promotion_id'] : null,
                    'after_balance' => !empty($transaction['after_balance']) ? $transaction['after_balance'] : 0,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $transaction_id_type = 'transaction_id';

                if ($transaction_type == self::API_METHOD_REFUND) {
                    if (!empty($transaction_refund_id)) {
                        $transaction_id = $transaction_refund_id;
                        $transaction_id_type = 'refund_transaction_id';
                    }
                }

                if ($transaction_type == self::API_METHOD_SETTLE || $transaction_type == self::API_METHOD_FREE_SPIN || $transaction_type == self::API_METHOD_RESETTLE) {
                    if (!empty($transaction_reference_id)) {
                        $transaction_id = $transaction_reference_id;
                        $transaction_id_type = 'reference_transaction_id';
                    }
                }

                $bet_transaction = $this->queryPlayerTransaction(self::API_METHOD_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $cancel_bet_transaction = $this->queryPlayerTransaction(self::API_METHOD_CANCEL_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $adjust_bet_transaction = $this->queryPlayerTransaction(self::API_METHOD_ADJUST_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $void_bet_transaction = $this->queryPlayerTransaction(self::API_METHOD_VOID_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $unvoid_bet_transaction = $this->queryPlayerTransaction(self::API_METHOD_UNVOID_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $refund_transaction = $this->queryPlayerTransaction(self::API_METHOD_REFUND, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $settle_transaction = $this->queryPlayerTransaction(self::API_METHOD_SETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $unsettle_transaction = $this->queryPlayerTransaction(self::API_METHOD_UNSETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $void_settle_transaction = $this->queryPlayerTransaction(self::API_METHOD_VOID_SETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $unvoid_settle_transaction = $this->queryPlayerTransaction(self::API_METHOD_UNVOID_SETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $bet_and_settle_transaction = $this->queryPlayerTransaction(self::API_METHOD_BET_AND_SETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $cancel_bet_and_settle_transaction = $this->queryPlayerTransaction(self::API_METHOD_CANCEL_BET_AND_SETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $free_spin_transaction = $this->queryPlayerTransaction(self::API_METHOD_FREE_SPIN, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $give_transaction = $this->queryPlayerTransaction(self::API_METHOD_GIVE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);
                $resettle_transaction = $this->queryPlayerTransaction(self::API_METHOD_RESETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id, $transaction_id_type);

                $validated_bet_transaction = [
                    'amount' => !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0,
                    'result_amount' => !empty($bet_transaction['result_amount']) ? $bet_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null,
                ];

                $validated_cancel_bet_transaction = [
                    'amount' => !empty($cancel_bet_transaction['amount']) ? $cancel_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($cancel_bet_transaction['external_unique_id']) ? $cancel_bet_transaction['external_unique_id'] : null,
                ];

                $validated_adjust_bet_transaction = [
                    'amount' => !empty($adjust_bet_transaction['amount']) ? $adjust_bet_transaction['amount'] : 0,
                    'bet_amount' => !empty($adjust_bet_transaction['bet_amount']) ? $adjust_bet_transaction['bet_amount'] : 0,
                    'external_unique_id' => !empty($adjust_bet_transaction['external_unique_id']) ? $adjust_bet_transaction['external_unique_id'] : null,
                ];

                $validated_void_bet_transaction = [
                    'amount' => !empty($void_bet_transaction['amount']) ? $void_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($void_bet_transaction['external_unique_id']) ? $void_bet_transaction['external_unique_id'] : null,
                ];

                $validated_unvoid_bet_transaction = [
                    'amount' => !empty($unvoid_bet_transaction['amount']) ? $unvoid_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($unvoid_bet_transaction['external_unique_id']) ? $unvoid_bet_transaction['external_unique_id'] : null,
                ];

                $validated_refund_transaction = [
                    'amount' => !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0,
                    'external_unique_id' => !empty($refund_transaction['external_unique_id']) ? $refund_transaction['external_unique_id'] : null,
                ];

                $validated_settle_transaction = [
                    'amount' => !empty($settle_transaction['amount']) ? $settle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($settle_transaction['external_unique_id']) ? $settle_transaction['external_unique_id'] : null,
                ];

                $validated_unsettle_transaction = [
                    'amount' => !empty($unsettle_transaction['amount']) ? $unsettle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($unsettle_transaction['external_unique_id']) ? $unsettle_transaction['external_unique_id'] : null,
                ];

                $validated_void_settle_transaction = [
                    'amount' => !empty($void_settle_transaction['amount']) ? $void_settle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($void_settle_transaction['external_unique_id']) ? $void_settle_transaction['external_unique_id'] : null,
                ];

                $validated_unvoid_settle_transaction = [
                    'amount' => !empty($unvoid_settle_transaction['amount']) ? $unvoid_settle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($unvoid_settle_transaction['external_unique_id']) ? $unvoid_settle_transaction['external_unique_id'] : null,
                ];

                $validated_bet_and_settle_transaction = [
                    'amount' => !empty($bet_and_settle_transaction['amount']) ? $bet_and_settle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($bet_and_settle_transaction['external_unique_id']) ? $bet_and_settle_transaction['external_unique_id'] : null,
                ];

                $validated_cancel_bet_and_settle_transaction = [
                    'amount' => !empty($cancel_bet_and_settle_transaction['amount']) ? $cancel_bet_and_settle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($cancel_bet_and_settle_transaction['external_unique_id']) ? $cancel_bet_and_settle_transaction['external_unique_id'] : null,
                ];

                $validated_free_spin_transaction = [
                    'amount' => !empty($free_spin_transaction['amount']) ? $free_spin_transaction['amount'] : 0,
                    'external_unique_id' => !empty($free_spin_transaction['external_unique_id']) ? $free_spin_transaction['external_unique_id'] : null,
                ];

                $validated_give_transaction = [
                    'amount' => !empty($give_transaction['amount']) ? $give_transaction['amount'] : 0,
                    'external_unique_id' => !empty($give_transaction['external_unique_id']) ? $give_transaction['external_unique_id'] : null,
                ];

                $validated_resettle_transaction = [
                    'amount' => !empty($resettle_transaction['amount']) ? $resettle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($resettle_transaction['external_unique_id']) ? $resettle_transaction['external_unique_id'] : null,
                ];

                extract($validated_bet_transaction, EXTR_PREFIX_ALL, 'bet');
                extract($validated_cancel_bet_transaction, EXTR_PREFIX_ALL, 'cancel_bet');
                extract($validated_adjust_bet_transaction, EXTR_PREFIX_ALL, 'adjust_bet');
                extract($validated_void_bet_transaction, EXTR_PREFIX_ALL, 'void_bet');
                extract($validated_unvoid_bet_transaction, EXTR_PREFIX_ALL, 'unvoid_bet');
                extract($validated_refund_transaction, EXTR_PREFIX_ALL, 'refund');
                extract($validated_settle_transaction, EXTR_PREFIX_ALL, 'settle');
                extract($validated_unsettle_transaction, EXTR_PREFIX_ALL, 'unsettle');
                extract($validated_void_settle_transaction, EXTR_PREFIX_ALL, 'void_settle');
                extract($validated_unvoid_settle_transaction, EXTR_PREFIX_ALL, 'unvoid_settle');
                extract($validated_bet_and_settle_transaction, EXTR_PREFIX_ALL, 'bet_and_settle');
                extract($validated_cancel_bet_and_settle_transaction, EXTR_PREFIX_ALL, 'cancel_bet_and_settle');
                extract($validated_free_spin_transaction, EXTR_PREFIX_ALL, 'free_spin');
                extract($validated_give_transaction, EXTR_PREFIX_ALL, 'give');
                extract($validated_resettle_transaction, EXTR_PREFIX_ALL, 'resettle');

                if ($transaction_type != self::API_METHOD_BET && !empty($bet_amount)) {
                    $bet_amount = !empty($adjust_bet_amount) ? $bet_amount - $adjust_bet_amount : $bet_amount;
                }

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::API_METHOD_BET:
                            // $result_amount = !empty($transaction_amount) ? -$transaction_amount : $transaction_amount;

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::API_METHOD_CANCEL_BET:
                            $cancel_bet_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $cancel_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $cancel_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_ADJUST_BET:
                            /* $result_amount = !empty($transaction_amount) ? -$transaction_amount : $transaction_amount; */

                            $adjust_bet_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $adjust_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_VOID_BET:
                            $void_bet_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $void_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $void_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_UNVOID_BET:
                            $unvoid_bet_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$unvoid_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $unvoid_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_REFUND:
                            $refund_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $refund_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $refund_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_SETTLE:
                            $settle_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $settle_amount,
                                'result_amount' => $settle_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $settle_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_UNSETTLE:
                            $settle_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $settle_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_VOID_SETTLE:
                            $void_settle_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $void_settle_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_UNVOID_SETTLE:
                            $unvoid_settle_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $unvoid_settle_amount,
                                'result_amount' => $unvoid_settle_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $unvoid_settle_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_BET_AND_SETTLE:
                            $bet_amount = !empty($transaction_bet_amount) ? $transaction_bet_amount : $transaction_amount;

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $transaction_win_amount,
                                'result_amount' => !empty($transaction_result_amount) ? $transaction_result_amount : $transaction_win_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::API_METHOD_CANCEL_BET_AND_SETTLE:
                            $cancel_bet_and_settle_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_and_settle_amount,
                                'win_amount' => 0,
                                'result_amount' => $cancel_bet_and_settle_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $cancel_bet_and_settle_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::API_METHOD_FREE_SPIN:
                            $free_spin_data = [
                                'status' => $transaction_status,
                                'bet_amount' => 0,
                                'win_amount' => $transaction_win_amount,
                                'result_amount' => $transaction_win_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $free_spin_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($free_spin_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $free_spin_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::API_METHOD_GIVE:
                            /* $give_data = [
                                'status' => $transaction_status,
                                'bet_amount' => 0,
                                'win_amount' => $transaction_win_amount,
                                'result_amount' => $transaction_win_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $free_spin_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($give_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $give_data, 'external_unique_id', $transaction_external_unique_id); */
                            break;
                        case self::API_METHOD_RESETTLE:
                            $resettle_data = [
                                'status' => $transaction_status,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $resettle_amount,
                                'result_amount' => $resettle_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                                'extra_info' => json_encode(['after_balance' => $transaction_after_balance]),
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $resettle_data, 'external_unique_id', $transaction_external_unique_id);
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

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null, $use_bet_time = true) {
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
reference_transaction_id,
refund_transaction_id,
promotion_id,
after_balance
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND wallet_adjustment_status IN ('decreased', 'increased', 'retained') AND {$sqlTime} {$and_transaction_type}
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

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null, $transaction_id_type = 'transaction_id') {
        $and_transaction_id = !empty($transaction_id) ? "AND {$transaction_id_type} = ?" : '';

        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
request,
external_unique_id,
sum(bet_amount) as bet_amount,
sum(win_amount) as win_amount,
sum(result_amount) as result_amount
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND round_id = ? AND wallet_adjustment_status NOT IN ('preserved', 'failed') {$and_transaction_id}
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
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
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
transaction.round_id,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.wallet_adjustment_status,
transaction.extra_info,
transaction.game_info,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND transaction.wallet_adjustment_status NOT IN ('preserved', 'failed') AND {$sqlTime}
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
        $status = !empty($row['status']) ? $row['status'] : null;

        if (!empty($row['extra_info']) && !is_array($row['extra_info'])) {
            $row['extra_info'] = json_decode($row['extra_info'], true);
        }

        $row['after_balance'] = isset($row['extra_info']['after_balance']) ? $row['extra_info']['after_balance'] : null;
        $row['note'] = $this->getResult($status, $result_amount);

        $game_info = !empty($row['game_info']) ? json_decode($row['game_info'], true) : [];
        $row['odds'] = !empty($game_info['odds']) ? $game_info['odds'] : null;
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

        return $bet_details;
     }

    public function queryTransactionByDateTime($startDate, $endDate) {
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
transaction.request
FROM {$this->original_seamless_wallet_transactions_table} as transaction
WHERE transaction.game_platform_id = ? and transaction.updated_at >= ? AND transaction.updated_at <= ?
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

                if ($transaction['trans_type'] == self::API_METHOD_BET || $transaction['trans_type'] == self::API_METHOD_UNVOID_BET
                    || $transaction['trans_type'] == self::API_METHOD_UNSETTLE || $transaction['trans_type'] == self::API_METHOD_VOID_SETTLE) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                if ($transaction['trans_type'] == self::API_METHOD_BET_AND_SETTLE || $transaction['trans_type'] == self::API_METHOD_RESETTLE) {
                    if ($transaction['result_amount'] < 0) {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}
//end of class