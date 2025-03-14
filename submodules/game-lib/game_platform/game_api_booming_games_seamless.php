<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: Booming
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -booming_seamless_service_api.php
 **/

class Game_api_booming_games_seamless extends Abstract_game_api {
    // default
    public $CI;
    public $http_method;
    public $api_url;
    public $api_path;
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
    public $game_platform_id;

    // additional
    public $brand;
    public $api_key;
    public $api_secret;
    public $admin_domain_url;
    public $default_demo_balance;
    public $exit_url;
    public $cashier_url;
    public $allow_rollback_request_with_payout;

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const SEAMLESS_GAME_API_NAME = 'BOOMING_SEAMLESS_GAME_API';
    const SEAMLESS_SERVICE_API_PATH = '/booming_seamless_service_api/';
    const API_METHOD_CALLBACK = 'callback';
    const API_METHOD_ROLLBACK_CALLBACK = 'rollback_callback';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const URI_MAP = [
        self::API_queryForwardGame => '/v3/session',
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
        'seamless_service_unique_id',
        'external_game_id',
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
        $this->api_path = null;
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
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'booming_seamless_game_logs');
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'booming_seamless_wallet_transactions');
        $this->game_seamless_service_logs_table = $this->getSystemInfo('game_seamless_service_logs_table', 'booming_seamless_service_logs');
        $this->save_game_seamless_service_logs = $this->getSystemInfo('save_game_seamless_service_logs', true);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->enable_sync_original_game_logs = $this->getSystemInfo('enable_sync_original_game_logs', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->game_provider_gmt = $this->getSystemInfo('game_provider_gmt', '+0 hours');
        $this->game_provider_date_time_format = $this->getSystemInfo('game_provider_date_time_format', 'Y-m-d H:i:s');

        // additional
        $this->brand = $this->getSystemInfo('brand');
        $this->api_key = $this->getSystemInfo('api_key');
        $this->api_secret = $this->getSystemInfo('api_secret');
        $this->admin_domain_url = $this->getSystemInfo('admin_domain_url');
        $this->default_demo_balance = $this->getSystemInfo('default_demo_balance', '1000');
        $this->exit_url = $this->getSystemInfo('exit_url', $this->getHomeLink()); 
        $this->cashier_url = $this->getSystemInfo('cashier_url', ''); 
        $this->allow_rollback_request_with_payout = $this->getSystemInfo('allow_rollback_request_with_payout', true); 
    }

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
    }

    public function getPlatformCode() {
        return BOOMING_SEAMLESS_GAME_API;
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
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    public function getHttpHeaders($params) {
        $request_body = json_encode($params);
        $hashed_params = hash('sha256', $request_body);
        $generated_nonce = hexdec(uniqid());

        $to_hash = $this->api_path . $generated_nonce . $hashed_params;
        $generated_signature = hash_hmac('sha512', $to_hash, $this->api_secret);

        $this->CI->utils->debug_log('NONCE ====>', $generated_nonce);
        $this->CI->utils->debug_log('SIGNATURE ====>', $generated_signature);

        $headers = [
            'Content-Type' => 'application/json+vnd.api',
            'X-Bg-Api-Key' => $this->api_key,
            'X-Bg-Nonce' => $generated_nonce,
            'X-Bg-Signature' => $generated_signature,
        ];

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

        if ($statusCode == 200 || $statusCode == 201) {
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

    // public function getLauncherLanguage($language) {
    //     return $this->getGameLauncherLanguage($language, [
    //         'en_us' => 'en',
    //         'en-us' => 'en',
    //         'zh_cn' => 'zh',
    //         'id_id' => 'id',
    //         'vi_vn' => 'vi',
    //         'ko_kr' => 'ko',
    //         'th_th' => 'th',
    //         'hi_in' => 'hi',
    //         'pt_br' => 'pt',
    //         'pt-br' => 'pt',
    //     ]);
    // }

    public function getLauncherLanguage($language) {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en-US':
                $language = 'en';
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $language = 'zh';
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $language = 'id';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $language = 'vi';
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $language = 'th';
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-br':
            case 'pt-BR':
            case 'pt-PT':
                $language = 'pt';
                break;
            default:
                $language = 'en';
                break;
        }

        return $language;
    }

    public function saveSession($player_id, $token) {
        $this->CI->load->model(['external_common_tokens']);
        $this->CI->external_common_tokens->setPlayerToken($player_id, $token, $this->getPlatformCode());
    }

    public function queryForwardGame($playerName, $extra = null) {
        $this->api_path = self::URI_MAP[self::API_queryForwardGame];
        $this->http_method = self::HTTP_METHOD_POST;
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $balance = $this->queryPlayerBalance($playerName);
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $is_fun = $this->utils->isDemoMode($game_mode);

        if (!empty($extra['home_link'])) {
            $exit_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $exit_url = $extra['extra']['home_link'];
        } else {
            $exit_url = $this->exit_url;
        }

        if (!empty($extra['cashier_link'])) {
            $cashier_url = $extra['cashier_link'];
        } elseif (!empty($extra['extra']['cashier_link'])) {
            $cashier_url = $extra['extra']['cashier_link'];
        } else {
            $cashier_url = $this->cashier_url;
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

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $operator_url = rtrim($this->admin_domain_url, '/') . self::SEAMLESS_SERVICE_API_PATH . $this->getPlatformCode() . '/';

        $params = [
            'game_id' => $game_code,
            'balance' => $is_fun ? $this->default_demo_balance : $balance['balance'],
            'currency' => $this->currency,
            'locale' => $language,
            'variant' => $is_mobile ? 'mobile' : 'desktop',
            'player_id' => $gameUsername,
            'player_ip' => $this->utils->getIP(),
            'callback' =>  $operator_url . self::API_METHOD_CALLBACK . '/' . $game_code,
            'rollback_callback' => $operator_url . self::API_METHOD_ROLLBACK_CALLBACK . '/' . $game_code,
            // 'bonus_callback' => $operator_url . self::API_METHOD_BONUS_CALLBACK . '/' . $game_code,
            'demo' => $is_fun,
            'exit' => $exit_url,
            'cashier' => $cashier_url,
        ];

		#removes home url if disable_home_link is set to TRUE
		if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
			unset($params['cashier']);
			unset($params['exit']);
		}

        if($this->force_disable_home_link){
            if(isset($params['cashier']) && !empty($params['cashier'])){
                unset($params['cashier']);
            }
            if(isset($params['exit']) && !empty($params['exit'])){
                unset($params['exit']);
            }
        }

        $this->utils->debug_log(self::SEAMLESS_GAME_API_NAME, __FUNCTION__, 'params', $params, 'extra', $extra);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'url' => '',
        ];

        if ($success) {
            $session_id = isset($resultArr['session_id']) ? $resultArr['session_id'] : '';
            $result['url'] = isset($resultArr['play_url']) ? $resultArr['play_url'] : '';
            $this->saveSession($playerId, $session_id);

            $this->CI->utils->debug_log('URL RESULT ==>', $result['url']);
            $this->CI->utils->debug_log('SESSION RESULT ==>', $session_id);
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
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $validated_transaction = [
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => isset($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : Game_logs::STATUS_PENDING,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                    'amount' => !empty($transaction['amount']) ? $transaction['amount'] : 0,
                    'bet_amount' => !empty($transaction['bet_amount']) ? $transaction['bet_amount'] : 0,
                    'win_amount' => !empty($transaction['win_amount']) ? $transaction['win_amount'] : 0,
                    'result_amount' => !empty($transaction['result_amount']) ? $transaction['result_amount'] : 0,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $bet_transaction = $this->queryPlayerTransaction(self::API_METHOD_CALLBACK, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);
                $refund_transaction = $this->queryPlayerTransaction(self::API_METHOD_ROLLBACK_CALLBACK, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);

                $validated_bet_transaction = [
                    'amount' => !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null,
                ];

                $validated_refund_transaction = [
                    'amount' => !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0,
                    'win_amount' => !empty($refund_transaction['win_amount']) ? $refund_transaction['win_amount'] : 0,
                    'result_amount' => !empty($refund_transaction['result_amount']) ? $refund_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($refund_transaction['external_unique_id']) ? $refund_transaction['external_unique_id'] : null,
                ];

                extract($validated_bet_transaction, EXTR_PREFIX_ALL, 'bet');
                extract($validated_refund_transaction, EXTR_PREFIX_ALL, 'refund');

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::API_METHOD_CALLBACK:
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
                        case self::API_METHOD_ROLLBACK_CALLBACK:
                            $refund_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $refund_win_amount,
                                'result_amount' => $refund_result_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $refund_data, 'external_unique_id', $transaction_external_unique_id);
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
result_amount
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
                'round_number' => isset($row['round_id']) ? $row['round_id'] : null,
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

        $row['note'] = $this->getResult($row['status'], $row['result_amount']);
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
                $temp_game_record['transaction_type'] = ($transaction['result_amount'] < 0) ? Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE : Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}
//end of class