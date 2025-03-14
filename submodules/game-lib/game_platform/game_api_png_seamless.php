<?php
require_once dirname(__FILE__) . '/game_api_png.php';

class Game_api_png_seamless extends game_api_png {
    public $origin;
    public $brand;

    const MD5_FIELDS_FOR_MERGE = [
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCELLED = 'cancel';
    const TRANSACTION_ROLLBACK = 'rollback';

    public function __construct() {
        parent::__construct();
        $this->transaction_table_name = 'png_seamless_wallet_transactions';
        $this->origin = $this->getSystemInfo('origin', $this->utils->getUrl());
        $this->is_enabled_direct_launcher_url = $this->getSystemInfo('is_enabled_direct_launcher_url', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);
        $this->brand = $this->getSystemInfo('brand', ''); // for multi currency
    }

    public function getTransactionsTable()
    {
        return $this->transaction_table_name;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getPlatformCode() {
        return PNG_SEAMLESS_GAME_API;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for PNG Seamless";
        if($return) {
            $success = true;
            $this->setGameAccountRegistered($playerId);
            $message = "Successfully created account for PNG Seamless";
        }

        return [
            "success" => $success,
            "message" => $message
        ];
    }

    public function isPlayerExist($playerName) {
        return [
            'success' => true,
            'exists'=> $this->isPlayerExistInDB($playerName)
        ];
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = [
            'success' => true,
            'balance' => $balance
        ];

        return $result;
    }

    public function queryPlayerBalanceByPlayerId($playerId) {
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );
        
        return $result;
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        ];
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        ];
    }

    // /casino/ContainerLauncher?pid=2&gid=[GAME_TO_LAUNCH]&lang=en_GB&ticket=demo&practice=0&channel=[CHANNEL_TO_USE]&origin=[ORIGIN_OF_HOST]
   
    public function queryForwardGame($playerName, $extra = null) {
        $token = $this->getPlayerTokenByUsername($playerName);

        $extraLanguage = '';
        
        if( isset($extra['language']) && !empty($extra['language']) ){
            $extraLanguage = $extra['language'];
        }
        $language = $this->getLanguagePrecedence( $this->force_game_launch_language,  $extraLanguage, $this->language );

        $origin = !empty($this->origin) ? $this->origin : $this->getHomeLink();
        $lobby_url = $this->getSystemInfo('lobby_url', $origin);
        $this->api_domain;

        //extra checking for home link
        if(isset($extra['home_link'])) {
            $lobby_url = $extra['home_link'];
        }

        //extra checking for home link
        if(isset($extra['extra']['home_link'])) {
            $origin = $lobby_url = $extra['extra']['home_link'];
        }

        $gameMode = isset($extra['game_mode']) ? $extra['game_mode'] : 'real';
        if(in_array($gameMode, $this->demo_game_identifier)){
            $gameMode = 'demo';
        }
        
        $params = [
            'gid' => $extra['game_code'],
            'lang' => $language,
            'pid' => $this->pid,
            'practice' => 0,
            //'ticket' => ($gameMode == 'real' ? $this->currency.'-'.$token : 'demo'),
            'channel' => $extra['is_mobile'] ?  'mobile' : 'desktop',
            'origin' => $origin,
        ];

        if($gameMode == 'demo'){
            $params['practice'] = 1;
            $params['ticket'] = 'demo';
        }else{
            $params['practice'] = 0;
            $params['ticket'] = $this->currency.'-'.$token;
        }

        if ($this->force_game_launch_language !== false) {
            $params['lang'] = $this->force_game_launch_language;
        }

        if (!empty($this->brand)) {
            $params['brand'] = $this->brand;
        }

        $url = $this->api_domain . '/casino/ContainerLauncher?'. http_build_query($params);

        $this->CI->utils->debug_log("Game_api_png_seamless @queryForwardGame API DOMAIN: " , $this->api_domain);
        $this->CI->utils->debug_log("Game_api_png_seamless @queryForwardGame params: " , $params);
        $this->CI->utils->debug_log("Game_api_png_seamless @queryForwardGame URL: " , $url);

        return ['success' => true, 'url' => $url, 'params' => $params, 'api_domain' => $this->api_domain, 'lobby_url' => $lobby_url];
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;

        if ($this->enable_merging_rows) {
            return $this->commonSyncMergeToGameLogs(
                $token,
                $this,
                [$this, 'queryOriginalGameLogsFromTransMerge'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                [$this, 'preprocessOriginalRowForGameLogsMerge'],
                $enabled_game_logs_unsettle
            );
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $md5Fields = implode(", ", array('transaction.amount', 'transaction.after_balance', 'transaction.round_id', 'transaction.game_id', 'transaction.start_at', 'transaction.end_at', 'transaction.updated_at'));
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.amount as bet_amount,
    transaction.amount as result_amount,
    transaction.after_balance,
    transaction.status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.round_id,
    MD5(CONCAT({$md5Fields})) as md5_sum,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.english_name AS game,
    game_description.game_name as game_description_name,
    game_description.english_name as game_english_name,
    game_description.game_code as game_code,
    game_description.game_type_id

    
FROM
    {$this->transaction_table_name} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction.transaction_type != 'rollback' and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;


    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        } else {
            if(empty($row['md5_sum'])){
                $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
                $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                    self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
            }
        }

        $bet_amount = ($row['transaction_type'] == self::TRANSACTION_DEBIT) ? abs($row['bet_amount']) : 0;
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
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
            'bet_details'               => $this->formatBetDetails($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('EZUGI ', $data);
        return $data;

    }

    /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        switch($row['transaction_type']) {
            case self::TRANSACTION_CREDIT:
            case self::TRANSACTION_DEBIT:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case self::TRANSACTION_CANCELLED:
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function triggerInternalPayoutRound($transaction) {
        $this->CI->load->model('common_token');
        $this->CI->utils->debug_log('EZUGI SEAMLESS (triggerInternalPayoutRound)', 'transaction', $transaction);

        $transaction = json_decode($transaction);
        $transaction->token = $this->CI->common_token->getPlayerCommonTokenByGameUsername($transaction->uid);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
            'gameUsername' => $transaction->uid,
            'transaction' => $transaction
        ];

        return $this->callApi(self::API_triggerInternalPayoutRound, $transaction, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params){
        $resultArr = $this->getResultJsonFromParams($params);

        $this->CI->utils->debug_log('EZUGI SEAMLESS (processResultForTriggerInternalPayoutRound)', $params, $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
        $result = array(
            'gameUsername' => $gameUsername,
            'transaction' => $transaction,
            'status' => null,
            'exists' => null,
            'triggered_payout' => false
        );
        if($success){
            $result['status'] = true;
            $result['triggered_payout'] = true;
            $success = true;
        }
        $result['message'] = isset($resultArr['errorDescription']) ? $resultArr['errorDescription'] : '';

        return [$success, $result];
    }

    public function queryTransactionByDateTime($startDate, $endDate){

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->transaction_table_name} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
and transaction_type != 'cancel'
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

                $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                return $result;
         }

        public function processTransactions(&$transactions){
            $temp_game_records = [];

            if(!empty($transactions)){
                foreach($transactions as $transaction){

                    $temp_game_record = [];
                    $temp_game_record['player_id'] = $transaction['player_id'];
                    $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                    $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                    $temp_game_record['amount'] = abs($transaction['amount']);
                    $temp_game_record['before_balance'] = $transaction['before_balance'];
                    $temp_game_record['after_balance'] = $transaction['after_balance'];
                    $temp_game_record['round_no'] = $transaction['round_no'];
                    $extra_info = @json_decode($transaction['extra_info'], true);
                    $extra=[];
                    $extra['trans_type'] = $transaction['trans_type'];
                    $extra['extra'] = $extra_info;
                    $temp_game_record['extra_info'] = json_encode($extra);
                    $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                    if($transaction['trans_type'] == SELF::TRANSACTION_CREDIT || $transaction['trans_type'] == SELF::TRANSACTION_ROLLBACK) {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    } else {
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;
        }

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['game_english_name'])) {
            $bet_details['game_name'] = $row['game_english_name'];
        }
        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }
        if (isset($row['external_uniqueid'])) {
            $bet_details['bet_id'] = $row['external_uniqueid'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        return $bet_details;
    }

    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'transaction.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at BETWEEN ? AND ?';
        }

        $md5Fields = implode(", ", array('amount', 'transaction.after_balance', 'transaction.updated_at'));

        $sql = <<<EOD
SELECT
    transaction.id AS sync_index,
    transaction.amount AS bet_amount,
    SUM(transaction.amount) AS result_amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.player_id,
    transaction.game_id,
    transaction.transaction_type,
    transaction.status,
    transaction.response_result_id,
    transaction.external_unique_id AS external_uniqueid,
    transaction.round_id,
    transaction.extra_info,
    transaction.start_at,
    transaction.end_at,
    transaction.updated_at,
    MD5(CONCAT({$md5Fields})) AS md5_sum,

    game_description.id AS game_description_id,
    game_description.game_type_id,
    game_description.english_name AS game,
    game_description.game_name as game_description_name,
    game_description.english_name as game_english_name,
    game_description.game_code as game_code,
    game_description.game_type_id

    game_provider_auth.login_name as player_username,

    CASE WHEN COUNT(CASE WHEN transaction_type = 'credit' THEN 1 END) > 0 THEN 1 ELSE 0 END AS settled

FROM 
    {$this->transaction_table_name} AS transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id AND game_provider_auth.game_provider_id = ?
WHERE 
    transaction.game_platform_id = ? AND {$sqlTime}
    GROUP BY round_id;
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__ . '===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }


    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row)
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
            'status' => $row['settled'] ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details'               => $this->formatBetDetails($row),
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    private function formatBetDetails($row){

        if($row['transaction_type'] == self::TRANSACTION_DEBIT){
            return  [
                'round_id' => $row['round_id'],
                'bet_type' => $row['transaction_type'],
                'bet_amount' => $row['bet_amount'],
                'betting_time' => $row['start_at'],
            ];
        }

        return  [
             'round_id' => $row['round_id'],
             'bet_type' => $row['transaction_type'],
             'win_amount' => $row['result_amount'],
             'betting_time' => $row['start_at'],
         ];
     }


    public function preprocessOriginalRowForGameLogsMerge(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #set after balance
        $row['after_balance'] = $row['before_balance'] + $row['result_amount'];
    }


    /*
        @param mixed $forcedGameLang

        @param string $playerDefaultLang

        @param string $extraLang

        @return string
    */
    private function getLanguagePrecedence($forcedGameLang, $extraLang, $playerDefaultLang) {

        /* Language Hierarchy 
            3. force language
            2. extra settings language
            1. Player Default Language
        */
    
        if (!empty($forcedGameLang)) {
            return $this->getLauncherLanguage($forcedGameLang);
        }
    
        $language = $playerDefaultLang;
        
        if (!empty($extraLang)) {
            $language = $this->getSystemInfo('language', $extraLang);
        }
    
        return $this->getLauncherLanguage($language);
    }
    
    
}