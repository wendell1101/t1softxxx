<?php
require_once dirname(__FILE__) . '/game_api_simpleplay.php';

class Game_api_simpleplay_seamless extends Game_api_simpleplay {

    public function __construct() {
        parent::__construct();
        $this->list_of_method_for_force_error = $this->getSystemInfo('list_of_method_for_force_error', []);
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', true);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->original_transaction_table = 'simpleplay_seamless_wallet_transactions';
        $this->use_bet_detail_ui = $this->getSystemInfo('use_bet_detail_ui', true);
        $this->show_player_center_bet_details_ui = $this->getSystemInfo('show_player_center_bet_details_ui', true);
        
        $this->get_all_trans = true;
    }

    public function isSeamLessGame()
    {
        return true;
    }
    
	public function getPlatformCode(){
        return SIMPLEPLAY_SEAMLESS_GAME_API;
    }

    public function getTransactionsTable(){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transaction_table;
        }

        $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName.' like simpleplay_seamless_wallet_transactions');

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncMergeToGameLogs($token) {
        $this->get_all_trans = false;
        if(!$this->enable_merging_rows){
            $this->get_all_trans = true;
        }
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
    }

      /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false){
        $md5Fields = implode(", ", array('sp.sbe_status', 'sp.amount', 'sp.timestamp', 'sp.updated_at', 'sp.payoutdetails'));
        $reFundStatus = Game_logs::STATUS_REFUND;
        $settledStatus = Game_logs::STATUS_SETTLED;
        if($this->use_monthly_transactions_table){            
            $start = (new DateTime($dateFrom))->modify('first day of this month');
            $end = (new DateTime($dateTo))->modify('last day of this month');
            $interval = DateInterval::createFromDateString('1 month');
            $period = new DatePeriod($start, $interval, $end);
            $results = [];
            foreach ($period as $dt) {
                $yearMonthStr =  $dt->format("Ym");
                $tableName=$this->original_transaction_table.'_'.$yearMonthStr;
                $sqlTime="sp.updated_at >= ? AND sp.updated_at <= ?";

        $sql = <<<EOD
SELECT
sp.id as sync_index,
sp.external_uniqueid,
MD5(CONCAT({$md5Fields})) AS md5_sum,
sp.player_id,
sp.method,
sp.amount,
sp.gamecode as game_code,
sp.gamecode as game,
sp.gamecode as game_name,
sp.gameid as round_number,
sp.timestamp as start_at,
sp.timestamp as bet_at,
IFNULL(sp.Payouttime,sp.timestamp) as end_at,
sp.before_balance,
sp.after_balance,
sp.sbe_status as status,
sp.payoutdetails,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as sp
LEFT JOIN game_description as gd ON sp.gamecode = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
                $params=[
                    $this->getPlatformCode(),
                    $dateFrom,
                    $dateTo
                ];

                $this->CI->utils->debug_log('merge sql', $sql, $params);

                $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                $results = array_merge($results, $monthlyResults);
            }
            
            if(!$this->get_all_trans){
                $results = array_values(array_filter($results, function($row) { return ($row['method'] == 'playerLost' || $row['method'] == 'playerWin'); }));
            }
            return $results;
        }
        
        $tableName = $this->getTransactionsTable();
        $sqlTime="sp.updated_at >= ? AND sp.updated_at <= ?";

        $sql = <<<EOD
SELECT
sp.id as sync_index,
sp.external_uniqueid,
MD5(CONCAT({$md5Fields})) AS md5_sum,
sp.player_id,
sp.method,
sp.amount,
sp.gamecode as game_code,
sp.gamecode as game,
sp.gamecode as game_name,
sp.gameid as round_number,
sp.timestamp as start_at,
sp.timestamp as bet_at,
IFNULL(sp.Payouttime,sp.timestamp) as end_at,
sp.before_balance,
sp.after_balance,
sp.sbe_status as status,
sp.payoutdetails,
gd.id as game_description_id,
gd.game_type_id

FROM {$tableName} as sp
LEFT JOIN game_description as gd ON sp.gamecode = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        
        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        if(!$this->get_all_trans){
            $results = array_values(array_filter($results, function($row) { return ($row['method'] == 'playerLost' || $row['method'] == 'playerWin'); }));
        }
        return $results;
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

        $payoutdetails = isset($row['payoutdetails']) ? $row['payoutdetails'] : [];
        if(!empty($payoutdetails) && $this->enable_merging_rows){
            $payoutdetails = json_decode(json_decode($payoutdetails), true);
            $betlist = isset($payoutdetails['betlist']) ? $payoutdetails['betlist'] : [];
            if(!empty($betlist)){
                $row['bet_amount'] = array_sum(array_column($betlist, 'rolling'));
                $row['real_betting_amount'] = array_sum(array_column($betlist, 'betamount'));
                $row['result_amount'] = array_sum(array_column($betlist, 'resultamount'));
                $row['bet_details'] = $betlist;
            }
        }

        if(!$this->enable_merging_rows){
            if($row['method'] == 'placeBet'){
                $row['real_betting_amount'] = $row['bet_amount'] = $row['amount'];
            } else {
                $row['result_amount'] = $row['amount'];
            }
            $row['status'] = GAME_LOGS::STATUS_SETTLED;
        }
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, ['status', 'end_at'],
                ['amount_currency', ' amount']);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => null,
            ],
            'amount_info' => [
                'bet_amount' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => isset($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => isset($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => isset($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['end_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => $row['sync_index'],
                // 'bet_type' => $this->getBetTypeIdString($row['bet_type_id']),
                'bet_type' => null
            ],
            'bet_details' => isset($row['bet_details']) ? $row['bet_details'] : [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
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

    public function queryTransactionByDateTime($startDate, $endDate){
        $results = $this->queryOriginalGameLogs($startDate, $endDate);
        array_walk($results, function($rows, $key) use(&$results){
            $results[$key]['transaction_date'] = $rows['start_at'];
            $results[$key]['round_no'] = $rows['round_number'];
            $results[$key]['trans_type'] = $rows['method'];
            $results[$key]['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
            if($rows['method'] == "placeBet"){
                $results[$key]['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
            }
        });
        return $results;
    }
}