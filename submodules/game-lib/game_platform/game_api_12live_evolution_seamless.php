<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_12live.php';

class Game_api_12live_evolution_seamless extends Abstract_game_api_common_12live {

    const SUB_PROVIDER_ID = 16;
    const ORIGINAL_TRANSACTION_TABLE = 'common_seamless_wallet_transactions';

    public function getPlatformCode(){
        return LIVE12_EVOLUTION_SEAMLESS_API;
    }

    public function getCurrency() {
        return $this->getSystemInfo('currency', 'THB');
    }

    public function __construct(){
        parent::__construct();
        $this->provider_id = self::SUB_PROVIDER_ID;
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
                $this,
                [$this, 'queryOriginalGameLogsFromTrans'],
                [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
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
        #query bet only
        $sqlTime="pg.end_at >= ? AND pg.end_at <= ? AND pg.game_platform_id = ? AND transaction_type in ('settle','cancelbet')";

        $sql = <<<EOD
SELECT
pg.id as sync_index,
pg.response_result_id,
pg.external_unique_id as external_uniqueid,
pg.md5_sum,
game_provider_auth.login_name as player_username,
pg.player_id,
pg.game_platform_id,
pg.game_id as game_code, 
pg.game_id as game_name,
pg.status,
pg.round_id as round_number,
pg.response_result_id,
pg.extra_info,
pg.start_at,
pg.start_at as bet_at,
pg.end_at,
pg.before_balance,
pg.after_balance,
pg.transaction_type,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as pg
LEFT JOIN game_description as gd ON pg.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_provider_auth 
ON 
    pg.player_id = game_provider_auth.player_id AND 
    game_provider_auth.game_provider_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['status'] = $this->getGameRecordsStatus($row['transaction_type']);
        $bet_amount = 0;
        $payout = 0;
        if(isset($row['extra_info'])){
            $extra_info = json_decode($row['extra_info'], true);
            if(isset($extra_info['Stake'])){
                $bet_amount = $extra_info['Stake'];
            }

            if(isset($extra_info['Winlost'])){
                $payout = $extra_info['Winlost'];
            }
        }

        $result_amount = $payout - $bet_amount;
        $row['bet_amount'] = $bet_amount;
        $row['real_betting_amount'] = $bet_amount;
        $row['result_amount'] = $result_amount;
    }

}

/*end of file*/

        
