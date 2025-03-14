<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/game_api_mpoker.php';

/**
* Game Provider: MPoker
* Game Type: Poker
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @derrick.php.ph

    Related File
    -routes.php
    -mpoker_seamless_service_api.php
**/

class Game_api_mpoker_seamless extends Game_api_mpoker
{

    public $originalTable;
    public $original_trans_table;

    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_ROLLBACK = 'rollback';
    const TRANSACTION_CANCEL = 'cancel';

    // const MD5_FIELDS_FOR_MERGE=['start_at', 'end_at', 'status'];
    // const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];
    // const MD5_FIELDS_FOR_ORIGINAL = ['GameID', 'Accounts'];
    // const MD5_FLOAT_AMOUNT_FIELDS=['NewScore'];

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);
        $this->api_url = $this->getSystemInfo('url');
        $this->timeStamp = $this->CI->utils->getTimestampNow();
        $this->use_ogl_sync_version = $this->getSystemInfo('use_ogl_sync_version', true);

        // for encryption
        $this->desKey = $this->getSystemInfo('desKey', '');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-128-ECB');
        $this->originalTable = 'mpoker_game_logs';
        $this->original_trans_table = 'mpoker_seamless_wallet_transactions';
    }

    public function getPlatformCode()
    {
        return MPOKER_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame(){
        return true;
    }

    public function timestamp_str($format, $timezone = null)
    {
        $micro_date = microtime();
        $date_array = explode(" ",$micro_date);
        return date($format,$date_array[1]);
    }

    public function decrypt($data)
    {
		$encrypted = base64_decode($data);

		return openssl_decrypt($encrypted, $this->encrypt_method, $this->desKey, OPENSSL_RAW_DATA);
    }

    public function encrypt($data)
    {
        $output = false;
        $encrypt = openssl_encrypt($data, $this->encrypt_method, $this->desKey, OPENSSL_RAW_DATA);
        $output = base64_encode($encrypt);

        return $output;
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $this->utils->debug_log("MPoker Seamless" . __FUNCTION__ . "=====>");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
    {
        $this->utils->debug_log("MPoker Seamless" . __FUNCTION__ . "=====>");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false)
    {
        if($this->use_ogl_sync_version){
            return $this->syncOriginalGameLogsFromOGL($token);
        }
    }

    public function syncOriginalGameLogsFromOGL($token)
    {
        $this->CI->load->model('original_game_logs_model');
        $this->CI->utils->debug_log('syncOriginalGameLogs token', $token);

        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
        $dateTimeTo = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDate: " . $dateTimeFrom->format('Y-m-d H:i:s'), "endDate: " . $dateTimeTo->format('Y-m-d H:i:s'));

        $temp = $this->syncMPokerGameLogs($dateTimeFrom,$dateTimeTo);
        $result = [];

            if(!empty($temp['list'])){
                sleep($this->sync_sleep_time);
                $result['data_count'] += $temp['data_count'];
            }

            return array_merge(array("success"=>true), array($result));
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
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
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time) {
        $table = 'mpoker_seamless_wallet_transactions';
        $sqlTime= 'transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $md5Fields = implode(", ", array('transaction.amount', 'transaction.after_balance', 'transaction.round_id', 'transaction.game_id', 'transaction.start_at', 'transaction.end_at'));
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.amount,
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
    transaction.cost,
    transaction.extra_info,
    MD5(CONCAT({$md5Fields})) as md5_sum,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$table} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction.transaction_type = 'credit' and {$sqlTime} and transaction.game_platform_id = ?

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

        $extra = [
            'table' =>  $row['GameID']
        ];

        //$bet_amount = ($row['transaction_type'] == self::TRANSACTION_DEBIT) ? abs($row['bet_amount']) : 0;
        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_code']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['Accounts']
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
                'updated_at'            => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'              => $row['GameID'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => [
                'Round ID' => $row['GameID']],
            'extra' => $extra,

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('MPoker Seamless ' . __FUNCTION__ . "=====>", $data);
        return $data;

    }

    /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        $this->CI->load->model(array('game_logs'));
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    private function getBetStatus($transaction_type){
        switch($transaction_type) {
            case self::TRANSACTION_CANCEL:
                $status = Game_logs::STATUS_CANCELLED;
                break;
            case self::TRANSACTION_CREDIT:
                $status = Game_logs::STATUS_SETTLED;
                break;
            case self::TRANSACTION_DEBIT:
                $status = Game_logs::STATUS_SETTLED;
                break;
            default:
                $status = Game_logs::STATUS_PENDING;
                break;
        }

        return $status;
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime="mpoker.created_at >= ? and mpoker.created_at <= ?";

        if($use_bet_time){
            $sqlTime="mpoker.GameStartTime >= ? and mpoker.GameStartTime <= ?";
        }

        $sql = <<<EOD
SELECT
    mpoker.id AS sync_index,
    mpoker.GameID,
    mpoker.Accounts,
    mpoker.ServerID,
    mpoker.KindID AS game_code,
    mpoker.TableID,
    mpoker.ChairID,
    mpoker.UserCount,
    mpoker.CardValue,
    mpoker.CellScore AS valid_bet_amount,
    mpoker.AllBet AS bet_amount,
    mpoker.Profit AS result_amount,
    mpoker.Revenue AS revenue,
    mpoker.NewScore AS after_balance,
    mpoker.GameStartTime AS start_at,
    mpoker.GameEndTime AS end_at,
    mpoker.ChannelID,
    mpoker.LineCode,

    mpoker.external_uniqueid,
    mpoker.response_result_id,
    mpoker.created_at,
    mpoker.updated_at,
    mpoker.md5_sum,
    game_description.id as game_description_id,
    game_description.game_type_id,
    game_provider_auth.player_id
    FROM
    mpoker_game_logs as mpoker
    LEFT JOIN game_description ON mpoker.KindID = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON mpoker.Accounts = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
    WHERE
    {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

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
FROM mpoker_seamless_wallet_transactions
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

    public function queryTransactionByDateTime($startDate, $endDate){
        $add_balance_code = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
        $subtract_balance_code = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;

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
if(amount > 0, '{$add_balance_code}', '{$subtract_balance_code}') as transaction_type,
t.extra_info extra_info
FROM {$this->originalTable} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
}

