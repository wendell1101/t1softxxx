<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: Spadegaming
 * Game Type: Fishing, Arcade, Slots, Table
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -spadegaming_seamless_service_api.php
 **/

class Game_api_spadegaming_seamless extends Abstract_game_api {
    // default
    public $CI;
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

    // additional
    public $merchant_name;
    public $merchant_code;
    public $secret_key;
    public $exit_url;
    public $full_screen;
    public $menu_mode;
    public $game_url;
    public $lobby_code;
    public $site_id;
    public $status;

    const SEAMLESS_GAME_API_NAME = 'SPADEGAMING_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const ERROR_CODE_SUCCESS = 0;

    const TRANSACTION_TYPE_PLACE_BET = 'placeBet';
    const TRANSACTION_TYPE_PAYOUT = 'payout';
    const TRANSACTION_TYPE_CANCEL_BET = 'cancelBet';
    const TRANSACTION_TYPE_BONUS = 'bonus';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const URI_MAP = [
        self::API_queryForwardGame => '/auth/',
        self::API_queryForwardGameV2 => '/getAuthorize',
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
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'spadegaming_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'spadegaming_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'spadegaming_seamless_service_logs');
        $this->save_game_seamless_service_logs = $this->getSystemInfo('save_game_seamless_service_logs', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');

        // additional
        $this->merchant_name = $this->getSystemInfo('merchant_name');
        $this->merchant_code = $this->getSystemInfo('merchant_code');
        $this->secret_key = $this->getSystemInfo('secret_key'); // provided by GP
        $this->exit_url = $this->getSystemInfo('exit_url', $this->getHomeLink());
        $this->menu_mode = $this->getSystemInfo('menu_mode', 'on'); // on/off
        $this->full_screen = $this->getSystemInfo('full_screen', 'on'); // on/off
        $this->game_url = $this->getSystemInfo('game_url');
        $this->lobby_code = $this->getSystemInfo('lobby_code', 'SG');
        $this->site_id = $this->getSystemInfo('site_id', '');

        $this->enabled_new_queryforward = $this->getSystemInfo('enabled_new_queryforward', false);
    }

    public function getPlatformCode() {
        return SPADEGAMING_SEAMLESS_GAME_API;
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
        $url = $this->api_url . $api_uri;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            switch ($api_name) {
                case self::API_queryForwardGame:
                    $url = $this->game_url . '/' . $this->merchant_code . $api_uri . '?' . http_build_query($params);
                    break;
                default:
                    $url .= '?' . http_build_query($params);
                    break;
            }
        }

        return $url;
    }

    public function getHttpHeaders($params) {
            $method = isset($params['method']) ? str_replace("/", "", $params['method']) : null;
            $headers = [
                'API' => $method,
                'DataType' => 'JSON',
                'Content-type' => 'application/json',
            ];

            if (!empty($this->secret_key)) {
                $hash = md5(json_encode($params) . $this->secret_key);
                $headers['Digest'] = $hash;
            }

            return $headers;
        }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null) {
        $success = false;

        if (isset($resultArr['code']) && $resultArr['code'] == self::ERROR_CODE_SUCCESS) {
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

        if ($createPlayer) {
            $success = true;
            $message = 'Successfully create account for ' . self::SEAMLESS_GAME_API_NAME;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        } else {
            $success = false;
            $message = 'Unable to create account for ' . self::SEAMLESS_GAME_API_NAME;
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

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $params = array(
            'serialNo' => $this->CI->utils->getGUIDv4(),
            'merchantCode' => $this->merchant_code,
            'currency' => $this->currency,
            'language' => $language,
        );

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'extra', $extra);

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

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'resultArr', $resultArr);

        return array($success, $result);
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
            'en_us' => 'en_US',
            'zh_cn' => 'zh_CN',
            'id_id' => 'id_ID',
            'vi_vn' => 'vi_VN',
            'ko_kr' => 'ko_KR',
            'th_th' => 'th_TH',
            'hi_in' => 'hi_IN',
            'pt_br' => 'pt_BR',
        ]);
    }

    private function queryForwardGameV2($playerName, $extra){
        $this->http_method = self::HTTP_METHOD_POST;
        $this->CI->load->model('common_token');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $gameCode = isset($extra['game_code']) ? $extra['game_code'] : null;
        $gameMode = $extra['game_mode'];
        $playerIp = $this->utils->getIP();

        if (!empty($this->language)) {
            $language = $this->language;
        } else {
            if (isset($extra['language'])) {
                $language = $extra['language'];
            } else {
                $language = null;
            }
        }

        $exitUrl = !empty($extra['home_link']) ? $extra['home_link'] :  $this->exit_url;
        if(!empty($extra['extra']['home_link'])){
            $exitUrl =$extra['extra']['home_link'];
        }

        $language = $this->getLauncherLanguage($language);

        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForQueryForwardGameV2',
        );

        $account_info = array(
            "acctId"            => $gameUsername,
            "currency"          => $this->currency,
        );

        $params = array(
            "acctInfo" => $account_info,
            "merchantCode" => $this->merchant_code,
            "token" => $token,
            "acctIp" => $playerIp,
            "game" => $gameCode,
            "language" => $language,
            "exitUrl" => $exitUrl,
            "serialNo" => $this->CI->utils->getGUIDv4(),
            "fullScreen" => $this->full_screen,
            "menumode" => $this->menu_mode,
            "method" => self::URI_MAP[self::API_queryForwardGameV2],
        );

        if(in_array($gameMode, $this->demo_game_identifier)){
            $params['fun'] = 'true';
        } else {
            if(@$extra['is_mobile']){
                $params["mobile"] = "true";
            } else {
                $params["mobile"] = "false";
            };
        }

        if ($gameCode == $this->lobby_code) {
            $params['lobby'] = $gameCode;
        } else {
            $params['game'] = $gameCode;
        }

        if($gameCode=='_null'||empty($gameCode)){
            $params['lobby'] = $this->lobby_code;
            if(isset($params['game'])){
                unset($params['game']);
            }
        }

        return $this->callApi(self::API_queryForwardGameV2, $params, $context);
    }

    public function processResultForQueryForwardGameV2($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array('url'=>'');

        if($success){
            $result['url'] = @$resultArr['gameUrl'];
        }

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = null) {
        if ($this->enabled_new_queryforward) {
            return $this->queryForwardGameV2($playerName, $extra);
        }

        $this->http_method = self::HTTP_METHOD_GET;

        $success = true;
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $exit_url = !empty($extra['home_link']) ? $extra['home_link'] :  $this->exit_url;
        $is_fun = $this->utils->isDemoMode($game_mode);

        if (!empty($this->language)) {
            $language = $this->language;
        } else {
            if (isset($extra['language'])) {
                $language = $extra['language'];
            } else {
                $language = null;
            }
        }

        if(!empty($extra['extra']['home_link'])){
            $exit_url =$extra['extra']['home_link'];
        }

        $language = $this->getLauncherLanguage($language);

        $params = [
            'acctId' => $gameUsername,
            'token' => $token,
            'language' => $language,
            'mobile' => $is_mobile,
            'menumode' => $this->menu_mode,
            'exitUrl' => $exit_url,
            'fullScreen' => $this->full_screen,
        ];

        if ($game_code == $this->lobby_code) {
            $params['lobby'] = $game_code;
        } else {
            $params['game'] = $game_code;
        }

        if($game_code=='_null'||empty($game_code)){
            $params['lobby'] = $this->lobby_code;
            if(isset($params['game'])){
                unset($params['game']);
            }
        }

        if ($is_fun) {
            $params['fun'] = $is_fun;
        }

        $url = $this->generateUrl(self::API_queryForwardGame, $params);

        $result = [
            'success' => $success,
            'url' => $url,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'result', $result);

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
                    'amount' => !empty($transaction['amount']) ? $transaction['amount'] : 0,
                    'bet_amount' => !empty($transaction['bet_amount']) ? $transaction['bet_amount'] : 0,
                    'win_amount' => !empty($transaction['win_amount']) ? $transaction['win_amount'] : 0,
                    'result_amount' => !empty($transaction['result_amount']) ? $transaction['result_amount'] : 0,
                    'session_type' => !empty($transaction['type']) ? $transaction['type'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $place_bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_PLACE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $payout_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_PAYOUT, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $cancel_bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_CANCEL_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $bonus_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BONUS, $transaction_player_id, $transaction_game_code, $transaction_round_id);

                $validated_place_bet_transaction = [
                    'amount' => !empty($place_bet_transaction['amount']) ? $place_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($place_bet_transaction['external_unique_id']) ? $place_bet_transaction['external_unique_id'] : null,
                ];

                $validated_payout_transaction = [
                    'amount' => !empty($payout_transaction['amount']) ? $payout_transaction['amount'] : 0,
                    'external_unique_id' => !empty($payout_transaction['external_unique_id']) ? $payout_transaction['external_unique_id'] : null,
                ];

                $validated_cancel_bet_transaction = [
                    'amount' => !empty($cancel_bet_transaction['amount']) ? $cancel_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($cancel_bet_transaction['external_unique_id']) ? $cancel_bet_transaction['external_unique_id'] : null,
                ];

                $validated_bonus_transaction = [
                    'amount' => !empty($bonus_transaction['amount']) ? $bonus_transaction['amount'] : 0,
                    'external_unique_id' => !empty($bonus_transaction['external_unique_id']) ? $bonus_transaction['external_unique_id'] : null,
                ];

                extract($validated_place_bet_transaction, EXTR_PREFIX_ALL, 'bet');
                extract($validated_payout_transaction, EXTR_PREFIX_ALL, 'payout');
                extract($validated_cancel_bet_transaction, EXTR_PREFIX_ALL, 'cancel_bet');
                extract($validated_bonus_transaction, EXTR_PREFIX_ALL, 'bonus');

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::TRANSACTION_TYPE_PLACE_BET:
                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $transaction_bet_amount,
                                'win_amount' => $transaction_win_amount,
                                'result_amount' => $transaction_result_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_PAYOUT:
                            $payout_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $payout_amount,
                                'result_amount' => $payout_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $payout_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_CANCEL_BET:
                            $cancel_bet_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $cancel_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $cancel_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_BONUS:
                            $win_amount = $transaction_win_amount + $transaction_amount;

                            $bonus_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $transaction_bet_amount,
                                'win_amount' => $win_amount,
                                'result_amount' => $win_amount - $transaction_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bonus_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bonus_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bonus_data, 'external_unique_id', $transaction_external_unique_id);
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
type
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND wallet_adjustment_status NOT IN ('preserved', 'failed') AND {$sqlTime} {$and_transaction_type}
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
        $and_transaction_id = !empty($transaction_id) ? 'AND transaction_id = ?' : '';

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
sum(result_amount) as result_amount,
type
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

    public function queryTransactionDetailsByRoundId($transaction_type, $round_id) {

        $sql = <<<EOD
SELECT
transaction_type,
ticket_id,
reference_id
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND round_id = ?
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $round_id,
        ];

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
transaction.ticket_id,
transaction.reference_id,
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
        $this->status = $status = !empty($row['status']) ? $row['status'] : null;
        $is_bet_transaction_type = $row['transaction_type'] == self::TRANSACTION_TYPE_PLACE_BET;

        if ($status == Game_logs::STATUS_REFUND) {
            if ($is_bet_transaction_type) {
                $row['after_balance'] += $row['result_amount'];
            }
        } else {
            if ($is_bet_transaction_type) {
                $row['after_balance'] += $row['win_amount'];
            }
        }

        $row['note'] = $this->getResult($status, $result_amount);
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
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $result = 'Refund';
        } elseif ($status == Game_logs::STATUS_PENDING) {
            $result = 'Pending';
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

            $payout_info = $this->queryTransactionDetailsByRoundId(self::TRANSACTION_TYPE_PAYOUT, $row['round_id']);
            $refund_info = $this->queryTransactionDetailsByRoundId(self::TRANSACTION_TYPE_CANCEL_BET, $row['round_id']);

            if ($this->status == Game_logs::STATUS_SETTLED) {
                if (isset($payout_info['ticket_id'])) {
                    $bet_details['ticket_id'] = $payout_info['ticket_id'];
                }

                if (isset($payout_info['reference_id'])) {
                    $bet_details['reference_id'] = $payout_info['reference_id'];
                }
            } elseif ($this->status == Game_logs::STATUS_REFUND) {
                if (isset($refund_info['ticket_id'])) {
                    $bet_details['ticket_id'] = $refund_info['ticket_id'];
                }

                if (isset($refund_info['reference_id'])) {
                    $bet_details['reference_id'] = $refund_info['reference_id'];
                }
            } elseif ($this->status == Game_logs::STATUS_PENDING) {
                $bet_details['status'] = 'pending';
            } else {
                $bet_details['status'] = 'unknown';
            }
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
transaction.request,
transaction.type
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
                $temp_game_record['transaction_type'] = $transaction['trans_type'] == self::TRANSACTION_TYPE_PLACE_BET ? Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE : Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}
//end of class