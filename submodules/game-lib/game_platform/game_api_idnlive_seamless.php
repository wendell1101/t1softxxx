<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: IDNLIVE
* Game Type: Live Dealer
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @melvin.php.ph

    Related File
    -routes.php
    -idnlive_seamless_service_api.php
**/

class Game_api_idnlive_seamless extends Abstract_game_api
{
    public $api_url;
    public $provider;
    public $operator_id;
    public $api_key;
    public $web_cookie_name;
    public $auth_type;
    public $auth_user;
    public $auth_password;
    public $language;
    public $currency;
    public $table;
    public $sync_time_interval;
    public $sleep_time;
    public $prefix_for_username;
    public $http_method;
    public $original_transactions_table;
    public $check_credit_exist;
    public $check_refund_exist;

    const SEAMLESS_GAME_API_NAME = 'IDNLIVE Seamless Game API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const URI_MAP = [
        self::API_queryForwardGame => '/game/auth',
        self::API_queryGameListFromGameProvider => '/api/seamless/list-game-idnlive',
        self::API_syncGameRecords => '/api/bet-history',
    ];

    const LANGUAGE_CODE_ENGLISH = 'en';
    const LANGUAGE_CODE_CHINESE = 'zh';
    const LANGUAGE_CODE_INDONESIAN = 'id';
    const LANGUAGE_CODE_VIETNAMESE = 'vi';
    const LANGUAGE_CODE_KOREAN = 'ko';
    const LANGUAGE_CODE_THAI = 'th';
    const LANGUAGE_CODE_HINDI = 'hi';
    const LANGUAGE_CODE_PORTUGUESE = 'pt';

    const TRANSACTION_TYPE_DEBIT = 'debit';
    const TRANSACTION_TYPE_REVISION = 'revision';
    const TRANSACTION_TYPE_CREDIT = 'credit';
    const TRANSACTION_TYPE_REFUND = 'refund';

    const STATUS_BETTING = 'betting';
    const STATUS_BET = 'bet';
    const STATUS_WIN = 'win';
    const STATUS_LOSE = 'lose';
    const STATUS_REFUND = 'refund';

    const BET_TYPE_BUY = 'Buy';
    const BET_TYPE_CORRECTION = 'Correction';
    const BET_TYPE_WIN = 'Win';
    const BET_TYPE_REFUND = 'Refund';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_SETTLED = 1;
    const FLAG_PENDING = 2;
    const FLAG_REFUNDED = 7;

    const MD5_FIELDS_FOR_ORIGINAL = [
        'bet_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_platform_id',
        'amount',
        'before_balance',
        'after_balance',
        'player_id',
        'game_code',
        'transaction_type',
        'transaction_status',
        'response_result_id',
        'external_uniqueid',
        'extra_info',
        'start_at',
        'bet_at',
        'end_at',
        'transaction_id',
        'round_number',
        'bet_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'result_amount',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model', 'common_seamless_wallet_transactions']);
        $this->api_url = $this->getSystemInfo('url');
        $this->provider = $this->getSystemInfo('provider', 'idnlive');
        $this->operator_id = $this->getSystemInfo('operator_id');
        $this->api_key = $this->getSystemInfo('api_key');
        $this->web_cookie_name = $this->getSystemInfo('web_cookie_name');
        $this->auth_type = $this->getSystemInfo('auth_type', 'basic_auth');
        $this->auth_user = $this->getSystemInfo('auth_user');
        $this->auth_password = $this->getSystemInfo('auth_password');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->table = $this->getSystemInfo('table', 'B');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
		$this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->http_method = self::HTTP_METHOD_GET;
        $this->original_transactions_table = 'common_seamless_wallet_transactions';
        $this->adjust_datetime = $this->getSystemInfo('adjust_datetime', $this->CI->utils->inverseDateTimeModification($this->getDatetimeAdjustSyncMerge()));
        $this->check_credit_exist = $this->getSystemInfo('check_credit_exist', true);
        $this->check_refund_exist = $this->getSystemInfo('check_refund_exist', true);
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return IDNLIVE_SEAMLESS_GAME_API;
    }

    public function generateUrl($apiName, $params)
    {
        $url = $this->api_url . self::URI_MAP[$apiName];

        if ($this->http_method == self::HTTP_METHOD_GET) {
            $url .= '?'. http_build_query($params);
        }

        return $url;
    }

    protected function customHttpCall($ch, $params)
	{
		if ($this->http_method == self::HTTP_METHOD_POST) {
			curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode)
    {
        $success = false;

        if (@$statusCode == 200) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' got error ', $responseResultId, 'result', $resultArr);
        }

        return $success;
    }

    public function getTransactionsTable()
    {
        return $this->original_transactions_table;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = 'Unable to create account for '. self::SEAMLESS_GAME_API_NAME .'.';

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for '. self::SEAMLESS_GAME_API_NAME .'.';
        }

        $result = [
            'success' => $success,
            'message' => $message,
        ];
        
        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
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

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
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

    public function getLauncherLanguage($language)
    {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
                $result = self::LANGUAGE_CODE_ENGLISH;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $result = self::LANGUAGE_CODE_CHINESE;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $result = self::LANGUAGE_CODE_INDONESIAN;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $result = self::LANGUAGE_CODE_VIETNAMESE;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $result = self::LANGUAGE_CODE_KOREAN;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $result = self::LANGUAGE_CODE_THAI;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $result = self::LANGUAGE_CODE_HINDI;
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-BR':
                $result = self::LANGUAGE_CODE_PORTUGUESE;
                break;
            default:
                $result = self::LANGUAGE_CODE_ENGLISH;
                break;
        }

        return $result;
	}

    public function queryForwardGame($playerName, $extra = null)
    {
        $this->http_method = self::HTTP_METHOD_GET;
        $success = true;
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $game_code = $extra['game_code'];
        $this->language = $this->getSystemInfo('language', $this->getLauncherLanguage($extra['language']));

        $params = [
            'operatorId' => $this->operator_id,
            'token' => $token,
            'currency' => $this->currency,
            'language' => $this->language,
            'gameId' => $game_code,
        ];

        $url = $this->generateUrl(self::API_queryForwardGame, $params);

        $result = [
            'success' => $success,
            'url' => $url,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'queryForwardGame_result', $result);

        return $result;
    }

    public function queryGameListFromGameProvider($extra = null)
    {
        $this->http_method = self::HTTP_METHOD_GET;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        ];

        $params = [
            'provider' => $this->provider,
        ];

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params)
    {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = !empty($resultArr) ? $resultArr : [];
        }

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params, 'queryGameListFromGameProvider_result', $result);

        return [$success, $result];
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogsFromTrans($token = false)
    {
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        $dateTimeFrom = $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeFrom = $dateTimeFrom->format('Y-m-d H:i:s');
        $dateTimeTo = $dateTimeTo->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($dateTimeFrom, $dateTimeTo);

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $player_id = $transaction['player_id'];
                $game_id = $transaction['game_id'];
                $round_id = $transaction['round_id'];
                $transaction_amount = $transaction['amount'];

                $debit_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_TYPE_DEBIT, $player_id, $game_id, $round_id);
                $credit_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_TYPE_CREDIT, $player_id, $game_id, $round_id);
                $revision_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_TYPE_REVISION, $player_id, $game_id, $round_id);
                $refund_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_TYPE_REFUND, $player_id, $game_id, $round_id);

                if (isset($debit_transaction['external_unique_id']) && !empty($debit_transaction['external_unique_id'])) {
                    $debit_amount = isset($debit_transaction['amount']) && !empty($debit_transaction['amount']) ? $debit_transaction['amount'] : 0;

                    if ($transaction['transaction_type'] == self::TRANSACTION_TYPE_DEBIT) {
                        $debit_transaction['bet_amount'] = $debit_amount;
                        $debit_transaction['result_amount'] = -$debit_amount;
                        $debit_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
    
                        if ($debit_transaction['status'] == self::STATUS_BETTING) {
                            $debit_transaction['flag_of_updated_result'] = Game_logs::STATUS_PENDING;
                        } else {
                            if (isset($credit_transaction['external_unique_id']) && !empty($credit_transaction['external_unique_id'])) {
                                $credit_amount = $credit_transaction['amount'];
                                $debit_transaction['bet_amount'] = $debit_amount;
                                $debit_transaction['result_amount'] = $credit_amount - $debit_amount;

                                $credit_transaction['bet_amount'] = 0;
                                $credit_transaction['result_amount'] = 0;
                                $credit_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
                                $credit_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                                $credit_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($credit_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                                $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $credit_transaction);
                            }
                        }
                    }
    
                    if ($transaction['transaction_type'] == self::TRANSACTION_TYPE_CREDIT) {
                        $debit_transaction['bet_amount'] = $debit_amount;
                        $debit_transaction['result_amount'] = $transaction_amount - $debit_amount;
                        $debit_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;

                        if (isset($credit_transaction['external_unique_id']) && !empty($credit_transaction['external_unique_id'])) {
                            $credit_transaction['bet_amount'] = 0;
                            $credit_transaction['result_amount'] = 0;
                            $credit_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
                            $credit_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                            $credit_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($credit_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                            $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $credit_transaction);
                        }
                    }
    
                    if ($transaction['transaction_type'] == self::TRANSACTION_TYPE_REVISION) {
                        if ($transaction['status'] == self::STATUS_WIN) {
                            $debit_transaction['bet_amount'] = $debit_amount;
                            $debit_transaction['result_amount'] = $transaction_amount - $debit_amount;
                            $debit_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
                        } else {
                            $debit_transaction['bet_amount'] = $debit_amount;
                            $debit_transaction['result_amount'] = -$transaction_amount;
                            $debit_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
                        }

                        if (isset($revision_transaction['external_unique_id']) && !empty($revision_transaction['external_unique_id'])) {
                            $revision_transaction['bet_amount'] = 0;
                            $revision_transaction['result_amount'] = 0;
                            $revision_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
                            $revision_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                            $revision_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($revision_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                            $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $revision_transaction);
                        }
                    }

                    if ($transaction['transaction_type'] == self::TRANSACTION_TYPE_REFUND) {
                        $debit_transaction['bet_amount'] = $debit_amount;
                        $debit_transaction['result_amount'] = $transaction_amount;
                        $debit_transaction['flag_of_updated_result'] = Game_logs::STATUS_REFUND;

                        if (isset($refund_transaction['external_unique_id']) && !empty($refund_transaction['external_unique_id'])) {
                            $refund_transaction['bet_amount'] = 0;
                            $refund_transaction['result_amount'] = 0;
                            $refund_transaction['flag_of_updated_result'] = Game_logs::STATUS_SETTLED;
                            $refund_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                            $refund_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($refund_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                            $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $refund_transaction);
                        }
                    }

                    $debit_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                    $debit_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($debit_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $debit_transaction);
                }
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->CI->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return array('success'=> true, $result);
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
amount,
player_id,
game_id,
transaction_type,
status,
external_unique_id,
transaction_id,
round_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND {$sqlTime} {$and_transaction_type}
EOD;
        $params = [
            $this->getPlatformCode(),
            self::FLAG_NOT_UPDATED,
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function getPlayerTransactionByType($transaction_type, $player_id, $game_id, $round_id)
    {
        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
external_unique_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_id = ? AND round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $player_id,
            $game_id,
            $round_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function isPlayerTransactionExistByTypeAndRound($transaction_type, $player_id, $game_id, $round_id)
    {
        $this->CI->db->from($this->original_transactions_table)
        ->where('game_platform_id', $this->getPlatformCode())
        ->where('transaction_type', $transaction_type)
        ->where('player_id', $player_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id);

        return $this->CI->original_game_logs_model->runExistsResult();
    }

    public function queryPlayersByGameAndRound($game_id, $round_id)
    {
        $sql = <<<EOD
SELECT DISTINCT player_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND game_id = ? AND round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $game_id,
            $round_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function getRoundAndGame($transaction_type, $round_id)
    {
        $sql = <<<EOD
SELECT
round_id,
game_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? and transaction_type = ? and round_id = ? 
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $round_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;
        $this->syncOriginalGameLogsFromTrans($token);
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
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
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
transaction.id AS sync_index,
transaction.game_platform_id,
transaction.amount,
transaction.before_balance,
transaction.after_balance,
transaction.player_id,
transaction.game_id AS game_code,
transaction.transaction_type,
transaction.status as transaction_status,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.extra_info,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id AS round_number,
transaction.bet_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game,
game_provider_auth.login_name AS player_username

FROM {$this->original_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE transaction.game_platform_id = ? AND transaction.transaction_type = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::TRANSACTION_TYPE_DEBIT,
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
                'bet_amount' => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']),
                'result_amount' => $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount']),
                'bet_for_cashback' => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']),
                'real_betting_amount' => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']),
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $this->gameAmountToDBGameLogsTruncateNumber($row['after_balance']),
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
            'bet_details' => [
                'Transaction Id' => $row['transaction_id'],
                'Game Id' => $row['game_code'],
                'Round Id' => $row['round_number'],
                'Status' => $row['transaction_status'],
            ],
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

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

        $row['after_balance'] += ($row['bet_amount'] + $row['result_amount']);

        switch ($row['flag_of_updated_result']) {
            case Game_logs::STATUS_SETTLED:
                if ($row['result_amount'] > 0) {
                    $row['note'] = 'Win';
                } elseif($row['result_amount'] < 0) {
                    $row['note'] = 'Lose';
                } elseif($row['result_amount'] == 0) {
                    $row['note'] = 'Tie';
                } else {
                    $row['note'] = 'Free Game';
                }
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case Game_logs::STATUS_PENDING:
                $row['note'] = 'Betting';
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
            case Game_logs::STATUS_REFUND:
                $row['note'] = 'Refund';
                $row['status'] = Game_logs::STATUS_REFUND;
                break;
            default:
                $row['note'] = 'Unknown';
                $row['status'] = Game_logs::STATUS_UNSETTLED;
                break;
        }
    }

    public function processTransactions(&$transactions)
    {
        $temp_game_records = [];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = $this->gameAmountToDB(floatval(abs($transaction['amount'])));
                $temp_game_record['before_balance'] = $this->gameAmountToDB(floatval($transaction['before_balance']));
                $temp_game_record['after_balance'] = $this->gameAmountToDB(floatval($transaction['after_balance']));
                $temp_game_record['round_no'] = $transaction['round_no'];

                if (empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])) {
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                $extra = [];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if (in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;

        if(isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)) {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }
}
//end of class
