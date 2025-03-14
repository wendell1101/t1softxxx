<?php
require_once dirname(__FILE__) . '/game_api_oneapi_seamless.php';

class Game_api_oneapi_spinomenal_seamless extends Game_api_oneapi_seamless {
    public $game_platform_id, $subprovider_username_prefix;
    public function getPlatformCode(){
        return ONEAPI_SPINOMENAL_SEAMLESS_GAME_API;
    }

    public function __construct(){
        parent::__construct();
        $this->game_platform_id = $this->getPlatformCode();
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

            /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        #get only bet result since rollback on service updating the status of bet
        $sqlTime="oneapi.updated_at >= ? AND oneapi.updated_at <= ? AND oneapi.game_platform_id = ? AND oneapi.trans_type = 'bet_result'";
        $md5Fields = implode(", ", array('oneapi.bet_amount', 'oneapi.win_amount', 'oneapi.after_balance', 'oneapi.updated_at', 'oneapi.is_endround', 'oneapi.status'));
        $original_transactions_table = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT
oneapi.id as sync_index,
oneapi.response_result_id,
oneapi.bet_id as external_uniqueid,
MD5(CONCAT({$md5Fields})) as md5_sum,

oneapi.player_id,
oneapi.game_platform_id,
oneapi.effective_turnover as bet_amount,
oneapi.bet_amount as real_betting_amount,
oneapi.winloss as result_amount,
oneapi.win_amount,
oneapi.trans_type,
oneapi.game_code,
oneapi.game_code as game,
oneapi.game_code as game_name,
oneapi.round_id as round_number,
oneapi.response_result_id,
oneapi.extra_info as bet_details,
oneapi.bet_time as start_at,
oneapi.settled_time as end_at,
oneapi.before_balance,
oneapi.after_balance,
oneapi.status,
oneapi.updated_at,
oneapi.transaction_id,

gd.id as game_description_id,
gd.game_type_id

FROM {$original_transactions_table} as oneapi
LEFT JOIN game_description as gd ON oneapi.game_code = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

         /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
    }

    public function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_name'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
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
                'start_at' => $this->gameTimeToServerTime($row['start_at']),
                'end_at' => $this->gameTimeToServerTime($row['end_at']),
                'bet_at' => $this->gameTimeToServerTime($row['start_at']),
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
                'bet_type' => null
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }
}

/*end of file*/

        
