<?php
require_once dirname(__FILE__) . '/game_api_yl_nttech.php';

/**
 * Game Provider: YL NT-TECH
 * Game Type: Fishing
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -yl_nttech_seamless_service_api.php
 **/

class Game_api_yl_nttech_seamless extends Game_api_yl_nttech {
    public $original_seamless_wallet_transactions_table;
    public $original_seamless_game_logs_table;
    public $login_version;
    public $language;
    public $precision;
    public $conversion;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $whitelist_ip_validate_api_methods;
    public $game_api_active_validate_api_methods;
    public $game_api_maintenance_validate_api_methods;
    public $game_api_player_blocked_validate_api_methods;
    public $use_seamless_query_player_balance;
    public $use_operator_response_message_code_only;
    public $sync_original_game_logs;
    public $enable_merging_rows;

    const SEAMLESS_GAME_API_NAME = 'YL_NTTECH_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_SETTLE = 'settle';
    const TRANSACTION_TYPE_CANCELBET = 'cancelBet';
    const TRANSACTION_TYPE_VOIDGAME = 'voidGame';
    const TRANSACTION_TYPE_SETTLEFISHBET = 'settleFishBet';
    const TRANSACTION_TYPE_VOIDFISHBET = 'voidFishBet';

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

    const CURRENCY_LIST = [
        'SGD' => 0,
        'MYR' => 1,
        'HKD' => 2,
        'CNY' => 3,
        'JPY' => 4,
        'AUD' => 5,
        'IDR' => 6,
        'USD' => 7,
        'KRW' => 8,
        'THB' => 9,
        'VND' => 11,
        'NZD' => 12,
        'INR' => 15,
        'BND' => 16,
        'GBP' => 17,
        'KHR' => 18,
        'PHP' => 20,
        'EUR' => 22,
        'RUB' => 24,
        'MMKK' => 25,
        'MMK' => 28,
        'USDT' => 30,
        'VND2' => 55,
        'IDR2' => 56,
        'CAD' => 80,
        'BDT' => 81,
        'ILS' => 82,
        'ZAR' => 83,
        'UAH' => 84,
        'BRL' => 88,
        'LAK' => 90,
        'NPR' => 91,
        'MXN' => 92,
        'LAKK' => 93,
        'KHRK' => 94,
        'TRY' => 99,
        'TND' => 100,
        'ZMW' => 101,
        'NAD' => 102,
        'CHF' => 103,
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'yl_nttech_seamless_wallet_transactions');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'yl_nttech_seamless_game_logs');
        $this->login_version = $this->getSystemInfo('login_version', 'loginV2');
        $this->language = $this->getSystemInfo('language', 'en');
        $this->precision = $this->getSystemInfo('precision', 2);
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->whitelist_ip_validate_api_methods = $this->getSystemInfo('whitelist_ip_validate_api_methods', []);
        $this->game_api_active_validate_api_methods = $this->getSystemInfo('game_api_active_validate_api_methods', []);
        $this->game_api_maintenance_validate_api_methods = $this->getSystemInfo('game_api_maintenance_validate_api_methods', []);
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->use_seamless_query_player_balance = $this->getSystemInfo('use_seamless_query_player_balance', true);
        $this->use_operator_response_message_code_only = $this->getSystemInfo('use_operator_response_message_code_only', false);
        $this->sync_original_game_logs = $this->getSystemInfo('sync_original_game_logs', false);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
    }

    public function getPlatformCode() {
        return YL_NTTECH_SEAMLESS_GAME_API;
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

    public function queryPlayerBalance($player_id) {
        if ($this->use_seamless_query_player_balance) {
            $this->CI->load->model(['player_model']);
            return $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());
        } else {
            parent::queryPlayerBalance($player_id);
        }
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    protected function getAPIKey($gameUsername) {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetKey',
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'cert' => $this->cert,
            'user' => $gameUsername,
            'userName' => $gameUsername,
            'currency' => $this->currencyId(),
            'extension1' => $this->extension1
        ];

        return $this->callApi(self::API_generateToken, $params, $context);
    }
    
    public function processResultForGetKey($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result= [];

        if ($success) {
            $result['status'] = isset($resultArr['status']) ? $resultArr['status'] : null;
            $result['key'] = isset($resultArr['key']) ? $resultArr['key'] : null;
        }

        return array($success, $result);
    }

    public function currencyId() {
        return !empty(self::CURRENCY_LIST[$this->currency]) ? self::CURRENCY_LIST[$this->currency] : null;
    }

    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $result = $this->getAPIKey($gameUsername);
        $success = isset($result['success']) && $result['success'] ? $result['success'] : false;
        $language = $this->getSystemInfo('language', $extra['language']);
        $url = null;

        if (!$this->validateWhitePlayer($playerName)) {
            return ['success' => false];
        }

        if ($success) {
            $params = [
                'user' => $gameUsername,
                'key' => $result['key'],
                'extension1' => $this->extension1,
                'userName' => $gameUsername,
                'language' => $this->getLauncherLanguage($language),
                'returnURL' => $this->getHomeLink()
            ];

            if(isset($extra['extra']['home_link'])){
                $params['returnURL'] = $extra['extra']['home_link'];
            }

            if (isset($extra['game_code'])) {
                $params['gameId'] = $extra['game_code'];
            }

            if (isset($extra['extra']['home_link'])) {
                $params['returnURL'] = $extra['extra']['home_link'];
            }

            
            #remove returnURL when disable_home_link is set to TRUE
            if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
                unset($params['returnURL']);
            }

            $url = $this->api_url . '/api/' . $this->site . '/' . $this->login_version . '?' . http_build_query($params);
        }

        return ['success' => $success, 'url' => $url];
    }

    public function syncOriginalGameLogs($token = false) {
        if ($this->sync_original_game_logs) {
            parent::syncOriginalGameLogs($token);
        } else {
            return $this->returnUnimplemented();
        }
    }

    public function syncOriginalGameLogsFromTrans($token = false) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $start_date = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $end_date = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $start_date->modify($this->getDatetimeAdjust());
        $query_datetime_start = $start_date->format('Y-m-d H:i:s');
        $query_datetime_end = $end_date->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($query_datetime_start, $query_datetime_end);

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $validated_transaction = [
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : Game_logs::STATUS_UNSETTLED,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                ];

                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $settle_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_SETTLE, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $cancel_bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_CANCELBET, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $void_game_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_VOIDGAME, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $settle_fish_bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_SETTLEFISHBET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);
                $void_fish_bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_VOIDFISHBET, $transaction_player_id, $transaction_game_code, $transaction_round_id);

                $validated_bet_transaction = [
                    'amount' => !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null,
                ];

                $validated_settle_transaction = [
                    'amount' => !empty($settle_transaction['amount']) ? $settle_transaction['amount'] : 0,
                    'external_unique_id' => !empty($settle_transaction['external_unique_id']) ? $settle_transaction['external_unique_id'] : null,
                ];

                $validated_cancel_bet_transaction = [
                    'amount' => !empty($cancel_bet_transaction['amount']) ? $cancel_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($cancel_bet_transaction['external_unique_id']) ? $cancel_bet_transaction['external_unique_id'] : null,
                ];

                $validated_void_game_transaction = [
                    'amount' => !empty($void_game_transaction['amount']) ? $void_game_transaction['amount'] : 0,
                    'external_unique_id' => !empty($void_game_transaction['external_unique_id']) ? $void_game_transaction['external_unique_id'] : null,
                ];

                $validated_settle_fish_bet_transaction = [
                    'amount' => !empty($settle_fish_bet_transaction['amount']) ? $settle_fish_bet_transaction['amount'] : 0,
                    'bet_amount' => !empty($settle_fish_bet_transaction['bet_amount']) ? $settle_fish_bet_transaction['bet_amount'] : 0,
                    'win_amount' => !empty($settle_fish_bet_transaction['win_amount']) ? $settle_fish_bet_transaction['win_amount'] : 0,
                    'result_amount' => !empty($settle_fish_bet_transaction['result_amount']) ? $settle_fish_bet_transaction['result_amount'] : 0,
                    'external_unique_id' => !empty($settle_fish_bet_transaction['external_unique_id']) ? $settle_fish_bet_transaction['external_unique_id'] : null,
                ];

                $validated_void_fish_bet_transaction = [
                    'amount' => !empty($void_fish_bet_transaction['amount']) ? $void_fish_bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($void_fish_bet_transaction['external_unique_id']) ? $void_fish_bet_transaction['external_unique_id'] : null,
                ];

                extract($validated_bet_transaction, EXTR_PREFIX_ALL, 'bet');
                extract($validated_settle_transaction, EXTR_PREFIX_ALL, 'settle');
                extract($validated_cancel_bet_transaction, EXTR_PREFIX_ALL, 'cancel_bet');
                extract($validated_void_game_transaction, EXTR_PREFIX_ALL, 'void_game');
                extract($validated_settle_fish_bet_transaction, EXTR_PREFIX_ALL, 'settle_fish_bet');
                extract($validated_void_fish_bet_transaction, EXTR_PREFIX_ALL, 'void_fish_bet');

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::TRANSACTION_TYPE_BET:
                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $settle_amount,
                                'result_amount' => $settle_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_SETTLE:
                            $settle_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $settle_amount,
                                'result_amount' => $settle_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $settle_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_CANCELBET:
                            $cancel_bet_data = [
                                'status' => Game_logs::STATUS_CANCELLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_CANCELLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $cancel_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $cancel_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_VOIDGAME:
                            $void_game_data = [
                                'status' => Game_logs::STATUS_CANCELLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_CANCELLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $void_game_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $void_game_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_SETTLEFISHBET:
                            $settle_fish_bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $settle_fish_bet_bet_amount,
                                'win_amount' => $settle_fish_bet_win_amount,
                                'result_amount' => $settle_fish_bet_result_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $settle_fish_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($settle_fish_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $settle_fish_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_VOIDFISHBET:
                            $void_fish_bet_data = [
                                'status' => Game_logs::STATUS_VOID,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $settle_fish_bet_data = [
                                'status' => Game_logs::STATUS_VOID,
                                'bet_amount' => $settle_fish_bet_bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $void_fish_bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $settle_fish_bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($settle_fish_bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $void_fish_bet_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $settle_fish_bet_data, 'external_unique_id', $settle_fish_bet_external_unique_id);
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
            $and_transaction_type = "AND transaction_type = ?";
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
updated_at
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
            $and_transaction_id = 'AND transaction_id = ?';
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
bet_amount,
win_amount,
result_amount
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
        $flag_of_updated_result = 'AND transaction.flag_of_updated_result = '.self::FLAG_UPDATED_FOR_GAME_LOGS;

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        if(!$this->enable_merging_rows){
            $flag_of_updated_result = " AND transaction.transaction_type in ('bet','settle','settleFishBet')";
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
WHERE transaction.game_platform_id = ? {$flag_of_updated_result} AND {$sqlTime}
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
            'bet_details' => $this->preprocessBetDetails($row,null,true),
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

        if($this->enable_merging_rows || $row['transaction_type'] == 'settleFishBet'){
            if($row['transaction_type']=='bet'){
                $row['after_balance'] += $row['win_amount'];
            }

            $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
            $status = !empty($row['status']) ? $row['status'] : null;

            if ($status == Game_logs::STATUS_UNSETTLED) {
                $row['note'] = 'Unsettled';
            } elseif ($status == Game_logs::STATUS_SETTLED) {
                if ($result_amount > 0) {
                    $row['note'] = 'Win';
                } elseif ($result_amount < 0) {
                    $row['note'] = 'Lose';
                } elseif ($result_amount == 0) {
                    $row['note'] = 'Draw';
                } else {
                    $row['note'] = 'Free Game';
                }
            } elseif ($status == Game_logs::STATUS_CANCELLED) {
                $row['note'] = 'Cancelled';
            } elseif ($status == Game_logs::STATUS_VOID) {
                $row['note'] = 'Voided';
            } elseif ($status == Game_logs::STATUS_PENDING) {
                $row['note'] = 'Pending';
            } else {
                $row['note'] = 'Unknown';
            }
        }else{
            if($row['transaction_type'] == 'bet'){
                $win_amount = 0;
                $row['bet_amount'] = $row['amount']; #amount as bet_amount
                $row['real_betting_amount'] = $row['amount']; #amount as real_bet_amount
            }else{
                $win_amount = $row['amount']; #amount as win_amount
            }
            $row['result_amount'] = $win_amount - $row['bet_amount'];
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

    public function logoutAllPlayer($gameUsername, $password = null) {
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogoutAllPlayer',
            'gameUsername' => $gameUsername,
        );

        $params = [
            'cert' => $this->cert,
        ];

        return $this->callApi(self::API_logoutAllPlayer, $params, $context);
    }

    public function processResultForLogoutAllPlayer($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

        return array($success, ['logout' => true]);
    }

    public function queryTransactionByDateTime($startDate, $endDate) {
        $original_transactions_table = $this->getSeamlessTransactionTable();

        if (!$original_transactions_table) {
            $this->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $md5_fields = implode(", ", array('t.amount', 't.before_balance', 't.after_balance', 't.result_amount', 't.updated_at'));

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.start_at as transaction_date,
t.amount as amount,
t.after_balance,
t.before_balance,
t.result_amount,
t.round_id as round_no,
t.transaction_id,
t.external_unique_id as external_uniqueid,
t.transaction_type as trans_type,
t.updated_at,
MD5(CONCAT({$md5_fields})) as md5_sum,
t.extra_info
FROM {$original_transactions_table} as t
WHERE t.game_platform_id = ? and t.updated_at >= ? AND t.updated_at <= ?
ORDER BY t.updated_at asc, t.id asc;
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

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_BET) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_SETTLEFISHBET) {
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


        return $bet_details;
     }

}
//end of class