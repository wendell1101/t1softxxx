<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/game_api_bgsoft.php';

/**
 * Game Provider: BGSOFT
 * Game Type: Mini Games
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @emil.php.ph

    Related File
    -routes.php
    -bgsoft_seamless_service_api.php
 **/

class Game_api_bgsoft_seamless extends Game_api_bgsoft
{
    public $original_game_logs;
    public $original_transactions;
    public $enable_merging_rows;

    const ORIGINAL_GAMELOGS_TABLE = 'bgsoft_seamless_game_logs';
    const ORIGINAL_TRANSACTION_TABLE = 'bgsoft_transactions';

    const TRANSTYPE_BET = 'bet';
    const TRANSTYPE_PAYOUT = 'payout';
    const TRANSTYPE_REFUND = 'refund';
    const TRANSTYPE_SETTLE = 'settle';
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','game_code'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance'];

    const ODDS_MAP = [
        'red' => 2,
        'black' => 2,
        'white' => 14
    ];

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->use_bgsoft_seamless_sync = $this->getSystemInfo('use_bgsoft_seamless_sync', true);
        $this->merchant_code = $this->getSystemInfo('merchant_code', 'Ju0gHiYgjNKP48yDO2KC0');
        $this->secure_key = $this->getSystemInfo('secure_key', 'fe8LNXi9oXCRkxd2KVDp2');
        $this->sign_key = $this->getSystemInfo('sign_key', 'BFBS3EZyTDZLPGpO3eqAu');
        $this->enable_use_readonly_in_get_balance = $this->getSystemInfo('enable_use_readonly_in_get_balance', false);
        $this->flag_bet_transaction_settled      = $this->getSystemInfo('flag_bet_transaction_settled', true);
        $this->currency = $this->getSystemInfo('currency', '');

        //token ecryption
        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');

        $this->request = null;
        $this->opencode_list =[];
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->original_transactions_table = self::ORIGINAL_TRANSACTION_TABLE;

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', false);

        $this->enable_mm_channel_nofifications = $this->getSystemInfo('enable_mm_channel_nofifications', false);
        $this->mm_channel = $this->getSystemInfo('mm_channel', 'test_mattermost_notif');
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
    }

    public function getTransactionsTable(){
        // return $this->original_transactions_table;
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        $date=new DateTime();
        $monthStr=$date->format('Ym');
        
        return $this->initGameTransactionsMonthlyTableByDate($monthStr); 
    }

    public function initGameTransactionsMonthlyTableByDate($yearMonthStr){
        if(!$this->use_monthly_transactions_table){            
            return $this->original_transactions_table;
        }

        $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
        if (!$this->CI->utils->table_really_exists($tableName)) {
            try{
                $this->CI->load->model(['player_model']);
                $this->CI->player_model->runRawUpdateInsertSQL('create table '.$tableName." like {$this->original_transactions_table}");

            }catch(Exception $e){
                $this->CI->utils->error_log('create table failed: '.$tableName, $e);
                return null;
            }
        }

        return $tableName;
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return BGSOFT_SEAMLESS_GAME_API;
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
        $this->utils->debug_log("BGSOFT Seamless" . __FUNCTION__ . "=====>");

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
        $this->utils->debug_log("BGSOFT Seamless" . __FUNCTION__ . "=====>");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    public function syncBgsoft($startDate, $endDate, $page, $extra)
    {
        $token = $this->getAvailableApiToken();
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;

        if(empty($token)){
            return ['success'=>false, 'error_message'=>'no auth token'];
        }

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForSyncBgsoft',
            'startDate'       => $startDate,
            'endDate'         => $endDate
        );

        $params = array(
            'auth_token'    => $token,
            'merchant_code' => $this->merchant_code,
            'game_code'     => $game_code,
            'from'          => $startDate,
            'to'            => $endDate,
            'page_number'   => $page
        );

        $this->method = self::METHOD_GET;

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncBgsoft($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $from = $this->getVariableFromContext($params, 'from');
        $to = $this->getVariableFromContext($params, 'to');
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $data = !empty($resultArr['detail']['game_history']) ? $resultArr['detail']['game_history'] : null;
        $extra['response_result_id'] = $responseResultId;

        $result = array('data_count'=>0);

        if($success && !empty($data)){
            $gameRecords = $this->rebuildGameRecords($data, $extra);

            if(!empty($gameRecords)){

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'bgsoft_game_logs',
                    $gameRecords,
                    'uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS);

                    unset($gameRecords);

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }

                unset($updateRows);

            }
            $result['total_pages']=isset($detail['total_pages']) ? $detail['total_pages'] : null;
            $result['current_page']=isset($detail['current_page']) ? $detail['current_page'] : null;
            $result['total_rows_current_page']=isset($detail['total_rows_current_page']) ? $detail['total_rows_current_page'] : null;
            unset($detail);
        }else{
            $success=false;
        }
            unset($resultArr);

        return array($success, $result);
    }

    public function rebuildGameRecords($gameRecords, $extra)
    {
        if(!empty($gameRecords)){

            // for unfinished transactions
            $replace = ["mine", "tower", "coin", "hilo", "limbo", "crypto", "plinko", "dice", "double", "crash", "_"];

            foreach($gameRecords as $index => $record){
                $data["uniqueid"] = isset($record["uniqueid"]) ? str_replace($replace, "", $record["uniqueid"]) : null;
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                $data['bet_time'] = isset($record['bet_time']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['bet_time'])) : null;
                $data['payout_time'] = isset($record['payout_time']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['payout_time'])) : null;
                $data['game_finish_time'] = isset($record['game_finish_time']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['game_finish_time'])) : null;
                $data['bet_amount'] = isset($record['bet_amount']) ? $record['bet_amount'] : null;
                $data['payout_amount'] = isset($record['payout_amount']) ? $record['payout_amount'] : null;
                $data['period'] = isset($record['period']) ? $record['period'] : null;
                $data['bet_status'] = isset($record['status']) ? $record['status'] : null;
                $data['bet_details'] = isset($record['bet_details']) ? json_encode($record['bet_details']) : null;
                $data['result_details'] = isset($record['result_details']) ? json_encode($record['result_details']) : null;

                //extra info from SBE
                $data["external_uniqueid"] = isset($record["uniqueid"]) ? str_replace($replace, "", $record["uniqueid"]) : null;
                $data['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
                $dataRecords[] = $data;
            }

            return $dataRecords;
        }
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType,  $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($data))
        {
            foreach ($data as $record)
            {
                if ($queryType == 'update')
                {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function syncPaginate($startDate, $endDate, $page, &$rows_count=0)
    {
        $this->CI->utils->debug_log('start syncPaginate================',$startDate, $endDate, $page);

        $data_count = 0;
        $success=true;
        $done=false;

        while(!$done) {

            $sync_results = [
                'syncBgsoft' => $this->syncBgsoft($startDate, $endDate, $page, $extra=null),
            ];

            foreach($sync_results as $sync_method_key => $sync_result) {
                $rlt = $sync_result;
                if($rlt['success']) {

                    if(isset($rlt['total_rows_current_page'])) {
                        $rows_count += $rlt['total_rows_current_page'];
                    }

                    if(isset($rlt['data_count'])) {
                        $data_count += $rlt['data_count'];
                    }

                    $this->CI->utils->debug_log("sync game logs api result for {$sync_method_key} ------------------>", $rlt);

                    if($rlt['total_pages'] > $rlt['current_page']) {
                        $page = $rlt['current_page'] + 1;
                        $this->CI->utils->debug_log($sync_method_key . ' not done ================', $rlt['total_pages'], $rlt['current_page']);
                    }else{
                        $done = true;
                        $this->CI->utils->debug_log($sync_method_key . ' done ===================', $rlt['total_pages'], $rlt['current_page']);
                    }

                    $success=true;
                }else{
                    $success=false;
                    $done=true;
                    $this->CI->utils->error_log($sync_method_key . 'sync game logs api error', $rlt);
                }
            }

            $result = [
                'success' => $success,
                'data_count' => $data_count,
                'page' => $page,
                'rows_count' => $rows_count
            ];

            $this->CI->utils->debug_log('Overall sync game logs api result ------------------>', $result);
        }

        return $success;
    }

    public function syncOriginalGameLogs($token = false)
    {

        if($this->use_bgsoft_seamless_sync){
            return $this->syncOriginalGameLogsV2($token);
        }
    }

    public function syncOriginalGameLogsV2($token = false)
    {
        $this->CI->load->model(['original_game_logs_model']);

        $startDate = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDateStr=$startDate->format('Y-m-d H:i:s');
        $endDateStr=$endDate->format('Y-m-d H:i:s');
        $page = self::START_PAGE;

        $rows_count=0;
        $success= $this->syncPaginate( $startDateStr, $endDateStr, $page, $rows_count );

        $this->CI->utils->debug_log('result rows_count', $rows_count);

        return array('success'=>$success, 'rows_count'=>$rows_count);

    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

    public function queryOriginalGameLogsByMonthlyTable($dateFrom, $dateTo, $use_bet_time){
        $start = (new DateTime($dateFrom))->modify('first day of this month');
        $end = (new DateTime($dateTo))->modify('last day of this month');
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);
        $results = [];
        foreach ($period as $dt) {
            $yearMonthStr =  $dt->format("Ym");
            $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
            $sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

            if ($use_bet_time) {
                $sqlTime = '`original`.`timestamp_parsed` >= ? AND `original`.`timestamp_parsed` <= ?';
            }
            $this->CI->utils->debug_log('BGSOFT SEAMLESS sqlTime', $sqlTime);

            //start >> enable_merging_rows
            if($this->enable_merging_rows){
                $sql = <<<EOD
SELECT
DISTINCT original.bet_id, original.game_code
FROM {$tableName} as original
WHERE
{$sqlTime}
AND bet_id is not null
EOD;
                $params=[
                    $dateFrom,
                    $dateTo
                ];
                $this->CI->utils->debug_log('query distinct bet transacstion belatra merge sql', $sql, $params);

                $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                $results = array_merge($results, $monthlyResults);
                continue;
            }
            //end enable_merging_rows

            $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.timestamp_parsed', 'original.updated_at', 'original.status'));
            
            $selectAmounts = 'IF(original.trans_type="bet",original.amount,0) bet_amount, IF(original.trans_type="payout",original.amount,0) payout_amount';
            $where_transaction_type = "original.trans_type IN ('bet', 'payout', 'refund')";
            $groupby = '';
            $typeBet = self::TRANSTYPE_BET;
            $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.timestamp_parsed as start_at,
    original.timestamp_parsed as end_at,
    original.timestamp_parsed as bet_at,
    original.updated_at as updated_at,
    original.player_id as player_id,
    original.bet_id as bet_id,
    original.round_id as round,
    original.username as username,
    original.trans_type as trans_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    IF(original.trans_type = '{$typeBet}', -original.amount, original.amount) as result_amount,
    original.amount,
    {$selectAmounts},
    original.status,
    original.`status` as `is_settled`,
    original.`status` as trans_status,
    original.game_code as game,
    original.raw_data,
    original.number,
    original.opencode,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$tableName} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE {$where_transaction_type} AND
{$sqlTime}
{$groupby};
EOD;
            $params=[
                $this->getPlatformCode(),
                $dateFrom,
                $dateTo
            ];

            $this->CI->utils->debug_log('BGSOFT SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);
            $gameRecords = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            $results = array_merge($results, $gameRecords);
        }

        
        if(!empty($results)){
            if($this->enable_merging_rows){
                $results = array_unique($results, SORT_REGULAR);
                $this->preProcessBetIdsFromMonthlyTrans($results, $dateFrom, $dateTo);
                $results = array_values($results);
            }
        } 
        return $results;
    }

    public function queryTransactionsByBetIdAndGame($betId, $gameCode, $dateFrom, $dateTo){
        $start = (new DateTime($dateFrom))->modify('first day of this month');
        $end = (new DateTime($dateTo))->modify('last day of this month');
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($start, $interval, $end);
        $results = [];
        
        foreach ($period as $dt) {
            $yearMonthStr =  $dt->format("Ym");
            $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
            $this->CI->load->model('original_game_logs_model');
        $sql = <<<EOD
SELECT
    original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
    original.timestamp_parsed as start_at,
    original.timestamp_parsed as end_at,
    original.timestamp_parsed as bet_at,
    original.updated_at as updated_at,
    original.player_id as player_id,
    original.bet_id as bet_id,
    original.round_id as round,
    original.username as username,
    original.trans_type as trans_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    original.amount,
    original.status,
    original.`status` as `is_settled`,
    original.`status` as trans_status,
    original.game_code as game,
    original.raw_data,
    original.number,
    original.opencode,
    gd.game_code as game_code,
    gd.game_name as game_name,
    gd.id as game_description_id,
    gd.game_name as game_description_name,
    gd.game_type_id
FROM {$tableName} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE
original.bet_id = ? and original.game_code = ?
EOD;

            $params=[
                $this->getPlatformCode(),
                $betId,
                $gameCode
            ];

            $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
            $results = array_merge($results, $monthlyResults);
        }
        return $results;
    }

    public function preProcessBetIdsFromMonthlyTrans(array &$results, $dateFrom, $dateTo){
        if(!empty($results)){
            foreach ($results as $key => $result) {
                $betId = $result['bet_id'];
                $gameCode = $result['game_code'];
                $rows = $this->queryTransactionsByBetIdAndGame($betId, $gameCode, $dateFrom, $dateTo);
                $lastRow = end($rows);
                $betAmount = 0;
                $resultAmount = 0;
                array_walk($rows, function($data, $key) use(&$betAmount, &$resultAmount) {
                    if($data['trans_type'] == "bet"){
                        $betAmount += abs($data['amount']);
                        $resultAmount += -$data['amount'];
                    } elseif($data['trans_type'] == "payout"){
                        $betAmount += 0;
                        $resultAmount += $data['amount'];
                    }
                });

                $gameData = $lastRow;
                $gameData['bet_amount'] = $betAmount;
                $gameData['result_amount'] = $resultAmount;
                $gameData['start_at'] = $rows[0]['start_at'];
                $gameData['external_uniqueid'] = $lastRow['game_code'] . "-" . $lastRow['bet_id'];
                $gameData['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($gameData, ['end_at', 'status'], ['bet_amount', 'result_amount']);
                $results[$key] = $gameData;
                // print_r($results);exit();
            }
        }
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        if($this->use_monthly_transactions_table){
            return $this->queryOriginalGameLogsByMonthlyTable($dateFrom, $dateTo, $use_bet_time);
        }

		$sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`timestamp_parsed` >= ? AND `original`.`timestamp_parsed` <= ?';
        }
        $this->CI->utils->debug_log('BGSOFT SEAMLESS sqlTime', $sqlTime);

        $md5Fields = implode(", ", array('original.amount', 'original.after_balance', 'original.timestamp_parsed', 'original.updated_at'));
        
        $selectAmounts = 'IF(original.trans_type="bet",original.amount,0) bet_amount, IF(original.trans_type="payout",original.amount,0) payout_amount,';
        // $where_transaction_type = "(original.trans_type='bet' OR original.trans_type='payout')";
        $where_transaction_type = "original.trans_type IN ('bet', 'payout', 'refund')";
        $groupby = '';

        if($this->enable_merging_rows){
            $selectBetAmount    = 'MAX(CASE WHEN original.trans_type = "bet" THEN original.amount END)';
            $selectPayoutAmount = 'MAX(CASE WHEN original.trans_type = "payout" THEN original.amount END)';
            $md5Fields          = implode(", ", array('original.amount', 'original.after_balance', 'original.timestamp_parsed', 'original.updated_at',$selectBetAmount,$selectPayoutAmount));
            $selectAmounts      = $selectBetAmount.' AS bet_amount,'.$selectPayoutAmount.' AS payout_amount,';
            // $where_transaction_type = "original.trans_type='bet'";
            $groupby            = 'group by original.bet_id';
        }

        //result amount = win - bet
        $sql = <<<EOD
SELECT
	original.id as sync_index,
    original.response_result_id,
    original.external_uniqueid,
	original.timestamp_parsed as start_at,
    original.timestamp_parsed as end_at,
    original.timestamp_parsed as bet_at,
    original.updated_at as updated_at,
    original.player_id as player_id,
    original.bet_id as bet_id,
    original.round_id as round,
    original.username as username,
    original.trans_type as trans_type,
    original.after_balance as after_balance,
    original.before_balance as before_balance,
    original.amount,
    {$selectAmounts}
    original.`status` as `is_settled`,
    original.`status` as trans_status,
    original.game_code as game,
    original.raw_data,
    original.number,
    original.opencode,
    MD5(CONCAT({$md5Fields})) as md5_sum,
    gd.game_code as game_code,
    gd.game_name as game_name,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_transactions_table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
WHERE {$where_transaction_type} AND
{$sqlTime}
{$groupby};
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];

		$this->CI->utils->debug_log('BGSOFT SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);

        $gameRecords = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        $this->processGameRecordsFromTrans($gameRecords);

        return $gameRecords;
    }

    private function processGameRecordsFromTrans(&$gameRecords){
        foreach($gameRecords as $index => &$record) {
            if ($this->enable_merging_rows) {
                $this->CI->load->model(array('bgsoft_transactions'));
                $this->CI->bgsoft_transactions->tableName = $this->getTransactionsTable();
                $whereParams = ['bet_id' => $record['bet_id'],'round_id'=>$record['round'], 'game_code'=>$record['game'], 'trans_type'=>self::TRANSTYPE_BET, 'player_id'=>$record['player_id']];
                $betDetails = $this->CI->bgsoft_transactions->getTransactionByParamsArray($whereParams);
                $whereParams['trans_type'] = self::TRANSTYPE_PAYOUT;
                $payoutDetails = $this->CI->bgsoft_transactions->getTransactionByParamsArray($whereParams);
                $whereParams['trans_type'] = self::TRANSTYPE_REFUND;
                $refundDetails = $this->CI->bgsoft_transactions->getTransactionByParamsArray($whereParams);

                if (empty($record['bet_amount'])) {
                    if (isset($betDetails['amount'])) {
                        $gameRecords[$index]['bet_amount'] = $betDetails['amount'];
                        $gameRecords[$index]['start_at'] = $betDetails['timestamp_parsed'];
                        $gameRecords[$index]['bet_at'] = $betDetails['timestamp_parsed'];

                        $this->CI->utils->debug_log('BGSOFT SEAMLESS (processGameRecordsFromTrans)', 'whereParams',$whereParams, 'betDetails', $betDetails,
                        'tableName', $this->CI->bgsoft_transactions->tableName,
                        'record', $gameRecords[$index]);
                    }
                }

                if (!empty($gameRecords[$index]['payout_amount'])) {
                    $gameRecords[$index]['after_balance'] = $payoutDetails['after_balance'];
                } else {
                    $gameRecords[$index]['after_balance'] = $betDetails['after_balance'];
                }

                $gameRecords[$index]['external_uniqueid'] = $betDetails['external_uniqueid'];
                $gameRecords[$index]['result_amount'] = floatval($gameRecords[$index]['payout_amount']) - floatval($gameRecords[$index]['bet_amount']);

                if ($gameRecords[$index]['trans_status'] == Game_logs::STATUS_REFUND) {
                    $gameRecords[$index]['after_balance'] = $refundDetails['after_balance'];
                    $gameRecords[$index]['result_amount'] = floatval($refundDetails['amount']) - floatval($gameRecords[$index]['bet_amount']);
                }
            } else {
                $gameRecords[$index]['result_amount'] = $record['trans_type'] == self::TRANSTYPE_BET ? -$record['amount'] : $record['amount'];
            }

            $gameRecords[$index]['status'] = $this->preprocessStatus($gameRecords[$index]['trans_status']);

            $md5_sum = [
                $gameRecords[$index]['trans_type'],
                $gameRecords[$index]['bet_amount'],
                $gameRecords[$index]['payout_amount'],
                $gameRecords[$index]['result_amount'],
                $gameRecords[$index]['after_balance'],
                $gameRecords[$index]['start_at'],
                $gameRecords[$index]['end_at'],
                $gameRecords[$index]['bet_at'],
                $gameRecords[$index]['updated_at'],
                $gameRecords[$index]['status'],
            ];

            $gameRecords[$index]['md5_sum'] = md5(json_encode($md5_sum));
        }
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        $extra = [
            'table' =>  $row['round'],
            'odds' =>  $this->processOdds($row),
        ];

        // $row['result_amount'] = floatval($row['payout_amount']) - floatval($row['bet_amount']);

        if(!isset($row['md5_sum']) || empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $after_balance = $row['after_balance'];

        if($this->enable_merging_rows){
            $after_balance = null;
        }
        
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance,
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            // 'status' => $this->getStatus($row),
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            //'bet_details' => $betDetails,
            'bet_details' => $this->preprocessBetDetails($row, null, true),
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){

        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        // $row['status'] = Game_logs::STATUS_SETTLED;
    }

    public function processOdds($row){

        $odds = isset($row['opencode'])?$row['opencode']:null;
        $roundId = $row['round'];
        $game_code = $row['game'];
        $key = $game_code.$roundId;

        if(empty($odds) && array_key_exists($key,$this->opencode_list)){
            $odds = $this->opencode_list[$key];
        }

        //get opencode if empty
        if(empty($odds)){
            $this->CI->load->model(array('bgsoft_transactions'));
            $whereParams = ['round_id'=>$roundId, 'game_code'=>$game_code, 'trans_type'=>self::TRANSTYPE_SETTLE];
            $this->CI->bgsoft_transactions->tableName = $this->getTransactionsTable();
            $settleDatails = $this->CI->bgsoft_transactions->getTransactionByParamsArray($whereParams);
            if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                $this->opencode_list[$key] = $settleDatails['opencode'];
                $odds = $settleDatails['opencode'];
            }
        }

        if(array_key_exists($odds, self::ODDS_MAP)) {
            return self::ODDS_MAP[$odds];
        }

        return $odds;
    }

    public function getStatus($row){

        $round = $row['round'];
        $player_id = $row['player_id'];
        $gameusername = $this->getGameUsernameByPlayerId($player_id);

        $query_status = $this->queryStatusFromOGL($gameusername, $round);

        $query_status['uniqueid'] = $round;

        if($query_status['bet_status']==1){
            $status = Game_logs::STATUS_PENDING;
        }elseif($query_status['bet_status']==2){
            $status = Game_logs::STATUS_REFUND;
        }else{
            $status = Game_logs::STATUS_SETTLED;
        }

        return $status;
    }

    public function processBetDetails($row){
        $opencode = $row['opencode'];
        $roundId = $row['round'];

        if(empty($opencode) && array_key_exists((string)$roundId,$this->opencode_list)){
            $opencode = $this->opencode_list[$roundId];
        }

        if(empty($opencode)){
            $this->CI->load->model(array('bgsoft_transactions'));
            $whereParams = ['round_id'=>$roundId, 'trans_type'=>self::TRANSTYPE_SETTLE];
            $settleDatails = $this->CI->bgsoft_transactions->getTransactionByParamsArray($whereParams);
            if(!empty($settleDatails) && isset($settleDatails['opencode'])){
                $this->opencode_list[] = [(string)$roundId=>$settleDatails['opencode']];
                $opencode = $settleDatails['opencode'];
            }

        }

        $row['opencode'] = $opencode;

        $result = [
            'round_id'=>$row['round'],
            'bet_id'=>$row['bet_id'],
            'transaction_type'=>$row['trans_type'],
            'number'=>$row['number'],
            'opencode'=>$row['opencode'],
            'odds'=>$this->processOdds($row)
        ];

        return $result;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($gameUsername);
        return array('success' => $success);
    }

    public function unblockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($gameUsername);
        return array('success' => $success);
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        if($this->use_monthly_transactions_table){
                $start = (new DateTime($startDate))->modify('first day of this month');
                $end = (new DateTime($endDate))->modify('last day of this month');
                $interval = DateInterval::createFromDateString('1 month');
                $period = new DatePeriod($start, $interval, $end);
                $results = [];
                foreach ($period as $dt) {
                    $yearMonthStr =  $dt->format("Ym");
                    $tableName=$this->original_transactions_table.'_'.$yearMonthStr;
                    $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type trans_type,
t.raw_data extra_info,
t.bet_id bet_id,
t.game_code game_code,
t.number number,
t.opencode opencode
FROM {$tableName} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?  AND `t`.`trans_type`<>'settle'
ORDER BY t.updated_at asc;

EOD;
                    $params=[$this->getPlatformCode(),$startDate, $endDate];
                    $monthlyResults = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
                    $results = array_merge($results, $monthlyResults);
                }
                return $results;
        }
        //end if enabled use_monthly_transactions_table
$sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type trans_type,
t.raw_data extra_info,
t.bet_id bet_id,
t.game_code game_code,
t.number number,
t.opencode opencode
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?  AND `t`.`trans_type`<>'settle'
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

                $temp_game_record = array();
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [
                    'bet_id'=>$transaction['bet_id'],
                    'game_code'=>$transaction['game_code'],
                    'number'=>$transaction['number'],
                    'opencode'=>$transaction['opencode']
                ];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function debitCreditAmountToWallet($params, $request, &$previousBalance, &$afterBalance){
        $this->checkPreviousMonth = false;
        if(date('j', $this->utils->getTimestampNow()) <= $this->getSystemInfo('allowed_days_to_check_previous_monthly_table', '1')) {
            $this->checkPreviousMonth = true;
        }

        if(empty($this->request)){
            $this->request = $request;
        }

		$this->CI->utils->debug_log("BGSOFT SEAMLESS SERVICE: (debitCreditAmount)", $params, $previousBalance, $afterBalance);
        $this->CI->load->model(array('bgsoft_transactions'));
        $this->CI->bgsoft_transactions->tableName = $this->getTransactionsTable();
		//initialize params
		$player_id			= $params['player_id'];
		$amount 			= abs($params['amount']);

		//initialize response
		$success = false;
		$isValidAmount = true;
		$insufficientBalance = false;
		$isAlreadyExists = false;
		$isTransactionAdded = false;
		$flagrefunded = false;
		$additionalResponse	= [];
        $trans_type = isset($params['trans_type']) ? $params['trans_type'] : null;
        $externalUniqueId = $uniqueid = $this->generateUniqueId($params);
        $external_game_id = isset($params['game_code']) ? $params['game_code'] : null;
        $isBetOnPreviousMonth = false;
        $round_id=isset($params['round_id'])?(string)$params['round_id']:null;

        if (method_exists($this->CI->wallet_model, 'setUniqueidOfSeamlessService')) {
            $uniqueid = $this->getPlatformCode().'-'.$uniqueid;
            $this->CI->wallet_model->setUniqueidOfSeamlessService($uniqueid, $external_game_id);
        }

        if (method_exists($this->CI->wallet_model, 'setGameProviderActionType')) {
            $this->CI->wallet_model->setGameProviderActionType($trans_type);
        }

        if (method_exists($this->CI->wallet_model, 'setGameProviderRoundId')) {
            $this->CI->wallet_model->setGameProviderRoundId($round_id);
        }

        if ( ($params['trans_type']=='payout'  || $params['trans_type']=='refund') && method_exists($this->CI->wallet_model, 'setGameProviderIsEndRound') ) {
            $this->CI->wallet_model->setGameProviderIsEndRound(true);
        }

		if($params['trans_type']=='bet'){
			$mode = 'debit';
		}elseif($params['trans_type']=='payout'){
			$mode = 'credit';
		}elseif($params['trans_type']=='refund'){
			$mode = 'credit';
			$flagrefunded = true;
		}else{
			return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
		}

		if($amount>=0){

			//get and process balance
			$get_balance = $this->getPlayerBalance($params['player_name'], $player_id);

			if($get_balance!==false){
				$afterBalance = $previousBalance = $get_balance;
				if($mode=='debit'){
					$afterBalance = $afterBalance - $amount;
				}else{
					$afterBalance = $afterBalance + $amount;
				}

			}else{
				$this->utils->error_log("BGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) ERROR: getBalance", $get_balance, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}
            $isAlreadyExists = $this->CI->bgsoft_transactions->isTransactionExistInOtherTable($this->getTransactionsTable(), $externalUniqueId, $params['trans_type']);
            if(!$isAlreadyExists){
                if($this->checkPreviousMonth && $this->use_monthly_transactions_table){
                    $isAlreadyExists = $this->CI->bgsoft_transactions->isTransactionExistInOtherTable($this->getTransactionsPreviousTable(), $externalUniqueId, $params['trans_type']);
                }

            }
            
            if($isAlreadyExists){
                return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
            }

			//check if bet transaction exists
			if($params['trans_type']=='refund' || $params['trans_type']=='payout'){
				$flagrefunded = true;
				$check_bet_params = ['bet_id'=>(string)$params['bet_id'],
                'round_id'=>(string)$params['round_id'],
                'player_id'=>$player_id,
                'trans_type'=>'bet'];
				$betExist = $this->CI->bgsoft_transactions->getTransactionByParamsArray($check_bet_params, $this->getTransactionsTable());

                if(empty($betExist)){   
                    if($this->checkPreviousMonth && $this->use_monthly_transactions_table){
                        $betExist = $this->CI->bgsoft_transactions->getTransactionByParamsArray($check_bet_params, $this->getTransactionsPreviousTable());
                        if(!empty($betExist)){ #if bet  exist on previous month, update it using previous month table
                            $isBetOnPreviousMonth = true;
                        }
                    }
                }

				if(empty($betExist)){
					$additionalResponse['betExist']=false;
					$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet) DOES NOT EXIST BET TRANSACTION betExist",
                    'betExist', $betExist,
                    'params',$params,
                    'check_bet_params', $check_bet_params,
                    'prevTranstable');
					$afterBalance = $previousBalance;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
				}

				$additionalResponse['betExist']=true; 

                $seamless_service_related_unique_id = isset($betExist['external_uniqueid']) ? $this->utils->mergeArrayValues(['game', $this->getPlatformCode(), $betExist['external_uniqueid']]) : null;
                if (method_exists($this->CI->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->CI->wallet_model->setRelatedUniqueidOfSeamlessService($seamless_service_related_unique_id);
                }

                if (method_exists($this->CI->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->CI->wallet_model->setRelatedActionOfSeamlessService($this->relatedActionsMap(isset($betExist['trans_type']) ? $betExist['trans_type'] : null));
                }

                if($params['trans_type']=='payout' && $betExist['status']==Game_logs::STATUS_REFUND){                    
					$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (debitCreditAmountToWallet)  BET TRANSACTION already refunded");
					$afterBalance = $previousBalance;                    
                    $additionalResponse['refundExist']=true;
					return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }
			}

            if($params['trans_type']=='refund'){
                $check_payout_params = ['bet_id'=>(string)$params['bet_id'], 
                'player_id'=>$player_id, 
                'trans_type'=>'payout'];
                $payoutExist = $this->CI->bgsoft_transactions->getTransactionByParamsArray($check_payout_params, $this->getTransactionsTable());
                if(empty($payoutExist)){
                    if($this->checkPreviousMonth && $this->use_monthly_transactions_table){                    
                        $payoutExist = $this->CI->bgsoft_transactions->getTransactionByParamsArray($check_payout_params, $this->getTransactionsPreviousTable());
                    }
                }

                if(!empty($payoutExist)){
                    $afterBalance = $previousBalance;
                    $additionalResponse['payoutExist']=true;
                    return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
                }

                if($isBetOnPreviousMonth){
                    $this->CI->bgsoft_transactions->flagTransactionRefunded($betExist['external_uniqueid'], $this->getTransactionsPreviousTable());
                } else {
                    $this->CI->bgsoft_transactions->flagTransactionRefunded($betExist['external_uniqueid'], $this->getTransactionsTable());
                }
            } 

            if($params['trans_type']=='payout' && $this->flag_bet_transaction_settled){
                //mark bet as settled
                if($isBetOnPreviousMonth){
                    $this->CI->bgsoft_transactions->flagBetTransactionSettled($betExist, $this->getTransactionsPreviousTable());
                } else {
                    $this->CI->bgsoft_transactions->flagBetTransactionSettled($betExist, $this->getTransactionsTable());
                }
            }

			if($mode=='debit' && $previousBalance < $amount ){
				$afterBalance = $previousBalance;
				$insufficientBalance = true;
				$this->utils->debug_log("BGSOFT SEAMLESS SERVICE API: (debitCreditAmountToWallet) insufficientBalance", $insufficientBalance);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//insert transaction
			$isAdded = $this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance, $flagrefunded);

			if($isAdded===false){
				$this->utils->error_log("BGSOFT SEAMLESS SERVICE API: (debitCreditAmountToWallet) ERROR: isAdded=false saving error", $isAdded, $this->request);
				return array(false, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}

			//rollback amount because it already been processed
			if($isAdded==0){
				$this->utils->debug_log("BGSOFT SEAMLESS SERVICE API: (debitCreditAmountToWallet) isAdded already", $isAdded, $this->trans_records);
				$isAlreadyExists = true;
				$afterBalance = $previousBalance;
				return array(true, $previousBalance, $afterBalance, $insufficientBalance, $isAlreadyExists, $additionalResponse, $isTransactionAdded);
			}else{
				$isTransactionAdded = true;
			}

            if($amount > 0){
                $success = $this->transferGameWallet($player_id, $this->getPlatformCode(), $mode, $amount, $params);
            } else if($amount == 0){
                $success = true;
            } else {
                $success = false;
            }

			if(!$success){
				$this->utils->error_log("BGSOFT SEAMLESS SERVICE API: (debitCreditAmountToWallet) ERROR: debit/credit", $this->request);
			}

		}else{
            $success = false;
			// $get_balance = $this->getPlayerBalance($params['player_name'], $player_id);
			// if($get_balance!==false){
			// 	$afterBalance = $previousBalance = $get_balance;
			// 	$success = true;

			// 	//insert transaction
			// 	$this->insertIgnoreTransactionRecord($params, $previousBalance, $afterBalance);
			// }else{
			// 	$success = false;
			// }
		}
        
        if($this->utils->compareResultFloat($amount, '=', 0) && ($params['trans_type']=='payout' || $params['trans_type']=='refund')){
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
				$this->utils->debug_log("BGSOFT SEAMLESS SERVICE API: (debitCreditAmountToWallet) amount 0 call remote wallet", $this->request, 'param', $param);
                $succ=$this->CI->wallet_model->incRemoteWallet($player_id, $amount, $this->getPlatformCode(), $afterBalance);
            } 
        }

		return array($success,
						$previousBalance,
						$afterBalance,
						$insufficientBalance,
						$isAlreadyExists,
						$additionalResponse,
						$isTransactionAdded);
	}

    public function getPlayerByUsername($gameUsername){
		$player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->getPlatformCode());

		if(!$player){
			return [false, null, null, null];
		}
		$this->player = $player;
		return [true, $player, $player->game_username, $player->username];
	}

    public function insertIgnoreTransactionRecord($data, $previous_balance, $after_balance){
		$result = false;
		$data['after_balance'] = $after_balance;
		$data['before_balance'] = $previous_balance;
		$this->trans_records[] = $trans_record = $this->makeTransactionRecord($data);
		// if($trans_record['trans_type']=='payout' && $this->flag_bet_transaction_settled){
		// 	//mark bet as settled
		// 	$this->CI->bgsoft_transactions->flagBetTransactionSettled($trans_record);
		// }

        $tableName = $this->getTransactionsTable();
        $this->CI->bgsoft_transactions->setTableName($tableName);
		return $this->CI->bgsoft_transactions->insertIgnoreRow($trans_record);
	}

    public function makeTransactionRecord($raw_data){
		$data = [];
		$data['username'] 			= isset($raw_data['username'])?$raw_data['username']:null;//string
		$data['timestamp'] 			= isset($raw_data['timestamp'])?$raw_data['timestamp']:null;//string
		$data['timestamp_parsed'] 	= isset($raw_data['timestamp_parsed'])?$raw_data['timestamp_parsed']:null;//datetime
		$data['merchant_code'] 		= isset($raw_data['merchant_code'])?$raw_data['merchant_code']:null;//string
		$data['amount'] 			= isset($raw_data['amount'])?floatVal($raw_data['amount']):0;//double
		$data['currency'] 			= isset($raw_data['currency'])?$raw_data['currency']:null;//string
		$data['game_code'] 			= isset($raw_data['game_code'])?$raw_data['game_code']:null;//string
		$data['bet_id'] 			= isset($raw_data['bet_id'])?$raw_data['bet_id']:null;//string
		$data['round_id'] 			= isset($raw_data['round_id'])?$raw_data['round_id']:null;//string
		$data['player_id'] 			= isset($raw_data['player_id'])?$raw_data['player_id']:null;//string
		$data['trans_type'] 		= isset($raw_data['trans_type'])?$raw_data['trans_type']:null;//string
		$data['before_balance'] 	= isset($raw_data['before_balance'])?floatVal($raw_data['before_balance']):0;
		$data['after_balance'] 		= isset($raw_data['after_balance'])?floatVal($raw_data['after_balance']):0;
		$data['game_platform_id'] 	= $this->getPlatformCode();
		$data['status'] 			= $this->getTransactionStatus($raw_data);
		$data['raw_data'] 			= @json_encode($this->request);//text
		$data['external_uniqueid'] 	= $this->generateUniqueId($raw_data);
		$data['response_result_id'] = isset($raw_data['response_result_id'])?$raw_data['response_result_id']:null;
		$data['game_platform_id'] 	= $this->getPlatformCode();
		//$data['number'] 		= isset($raw_data['number'])?$raw_data['number']:null;
		//$data['opencode'] 		= isset($raw_data['opencode'])?$raw_data['opencode']:null;
        $data['number'] 		= null;
        $data['opencode'] 		= null;

		$data['elapsed_time'] 		= intval($this->utils->getExecutionTimeToNow()*1000);

		return $data;
	}

	private function getTransactionStatus($data){

		if($data['trans_type']=='payout'){
			return Game_logs::STATUS_SETTLED;
		}elseif($data['trans_type']=='refund'){
			return Game_logs::STATUS_REFUND;
		}elseif($data['trans_type']=='settle'){//initially pending if processed by cronjob then it will be flag as processed
			return Game_logs::STATUS_PENDING;
		}else{
			return Game_logs::STATUS_PENDING;
		}
	}

    public function getPlayerBalance($playerName, $player_id){
		$get_bal_req = $this->queryPlayerBalanceByPlayerId($player_id);
		$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (getPlayerBalance) get_bal_req: " , $get_bal_req);
		if($get_bal_req['success']){
			return $get_bal_req['balance'];
		}else{
			return false;
		}
	}

    private function generateUniqueId($data){
		if(empty($data['bet_id'])){
			return $data['game_code'] .'-'. $data['round_id'] .'-'. $data['trans_type'];
		}
		return $data['game_code'] .'-'. $data['bet_id'] .'-'. $data['trans_type'];
	}

    public function queryPlayerBalanceByPlayerId($playerId){
        $this->utils->debug_log("BGSOFT SEAMLESS: (queryPlayerBalance)");
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode(), $this->use_readonly_wallet);

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function transferGameWallet($player_id, $game_platform_id, $mode, $amount, $params=[]){
		$success = false;
		//not using transferSeamlessSingleWallet this function is for seamless wallet only applicable in GW
		if($mode=='debit'){
			$success = $this->CI->wallet_model->decSubWallet($player_id, $game_platform_id, $amount);
		}elseif($mode=='credit'){
			$success = $this->CI->wallet_model->incSubWallet($player_id, $game_platform_id, $amount);
		}

		$remoteErrorCode = $this->CI->wallet_model->getRemoteWalletErrorCode();

        if(!empty($params)){
            $this->CI->utils->debug_log("BGSOFT SEAMLESS: remoteWalletErrorCode", $remoteErrorCode);
            if($remoteErrorCode){
                $failed_transaction_data = $md5_data = [
                    'round_id' => isset($params['round_id']) ? $params['round_id'] : null,
                    'transaction_id' => isset($params['bet_id']) ? $params['bet_id'] : null,
                    'external_game_id' => isset($params['game_code']) ? $params['game_code'] : null,
                    'player_id' => isset($params['player_id']) ? $params['player_id'] : null,
                    'game_username' => isset($params['player_name']) ? $params['player_name'] : null,
                    'amount' => isset($params['amount']) ? $params['amount'] : null,
                    'balance_adjustment_type' => $mode,
                    'action' => isset($params['trans_type']) ? $params['trans_type'] : null,
                    'game_platform_id' => $this->getPlatformCode(),
                    'transaction_raw_data' => json_encode($this->request),
                    'remote_raw_data' => null,
                    'remote_wallet_status' => $remoteErrorCode,
                    'transaction_date' => isset($params['timestamp_parsed']) ? $params['timestamp_parsed'] : null,
                    'request_id' => $this->CI->utils->getRequestId(),
                    'full_url' => $this->CI->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
                    'headers' => json_encode(getallheaders()),
                    'external_uniqueid' => $this->generateUniqueId($params),
                ];
                
                $failed_transaction_data['md5_sum'] = md5(json_encode($md5_data));

                $where = ['external_uniqueid' => $this->generateUniqueId($params)];
                if($this->isFailedTransactionExist($where)){
                    $this->saveFailedTransaction('update',$failed_transaction_data, $where);
                }else{
                    $this->saveFailedTransaction('insert',$failed_transaction_data);
                }
            }
        }

        if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_DOUBLE_UNIQUEID){
			$this->utils->debug_log("BGSOFT SEAMLESS SERVICE: (transferGameWallet) treated as success remoteErrorCode: " , $remoteErrorCode);
            return true;
        }

		return $success;
	}

    public function saveFailedTransaction($query_type='insert', $data=[], $where=[]){
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->CI->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $this->CI->utils->debug_log("PP SEAMLESS SERVICE API: saveFailedTransaction",$query_type, $table_name, $data, $where);
        $this->CI->original_seamless_wallet_transactions->saveTransactionData($table_name, $query_type, $data, $where);
    }

    public function isFailedTransactionExist($where=[]){
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $failed_transaction_table = 'failed_remote_common_seamless_transactions';
        $year_month = $this->CI->utils->getThisYearMonth();
        $table_name = $failed_transaction_table.'_'.$year_month;
        $isExisting = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
        $this->CI->utils->debug_log("PP SEAMLESS SERVICE API: isFailedTransactionExist",$table_name, $where, $isExisting);
        return $isExisting;
    }

    public function getTransactions($transaction_type, $round_id)
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

        $this->CI->utils->debug_log(__METHOD__, 'BGSOFT SEAMLESS GAME API sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function playerTransactionExist($transaction_type, $player_id, $game_id, $round_id)
    {
        $this->CI->db->from($this->original_transactions_table)
        ->where('game_platform_id', $this->getPlatformCode())
        ->where('transaction_type', $transaction_type)
        ->where('player_id', $player_id)
        ->where('game_id', $game_id)
        ->where('round_id', $round_id);

        return $this->CI->original_game_logs_model->runExistsResult();
    }

    public function getCurrency(){
        return $this->currency;
    }

    public function getApiSignKey(){
        return $this->sign_key;
    }

    public function queryStatusFromOGL($gameusername, $game_id)
    {
        $bet_time = 'bet_time >= ? AND game_finish_time <= ?';

        if (!empty($payout_time)) {
            $and_payout_time = `AND payout_time = ?`;
        } else {
            $and_payout_time = '';
        }

        $sql = <<<EOD

SELECT
id,
uniqueid,
username,
bet_time,
bet_amount,
payout_amount,
payout_time,
game_finish_time,
bet_status
FROM bgsoft_game_logs
WHERE username = ? AND uniqueid = ?
EOD;

        $params = [
            $gameusername,
            $game_id,
        ];

        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }   
    
    public function batchRefund($data = [], $extra = [])
    {
        $baseUrl = $this->getSystemInfo("batch_refund_api_url", "http://admin.og.local");

        $game_platform_id = $this->getPlatformCode();
        $table = $this->getTransactionsTable();
        // Fetch all records in a single query
        if(empty($data)) return false;

        $sql = "SELECT * FROM $table WHERE bet_id IN (" . implode(',', array_fill(0, count($data), '?')) . ") AND game_platform_id = ?";
        $params = array_merge($data, [$this->getPlatformCode()]);
        $query = $this->CI->db->query($sql, $params);
        

        if ($query) {
            foreach ($query->result() as $result) {
                if (empty($result)) {
                    continue;
                }
                $raw_data = isset($result->raw_data) ? json_decode($result->raw_data) : [];

                $params = array(
                    "amount"        => isset($raw_data->amount) ? (double) $raw_data->amount : null,
                    "bet_id"        => isset($raw_data->bet_id) ? $raw_data->bet_id : null,
                    "currency"      => isset($raw_data->currency) ? $raw_data->currency : null,
                    "game_code"     => isset($raw_data->game_code) ? $raw_data->game_code : null,
                    "merchant_code" => isset($raw_data->merchant_code) ? $raw_data->merchant_code : null,
                    "round_id"      => isset($raw_data->round_id) ? $raw_data->round_id : null,
                    "unique_id"     => isset($raw_data->unique_id) ? $raw_data->unique_id : null,
                    "username"      => isset($raw_data->username) ? $raw_data->username : null,
                );

                // Add the signature and timestamp separately
                $params['sign'] = $this->generateSignatureByParams($params);
                $params['timestamp'] = isset($raw_data->timestamp) ? $raw_data->timestamp : null;
                $params['type'] = 1;


                $api_url = $baseUrl . site_url("bgsoft_service_api/$game_platform_id/refund");
                $ch = curl_init($api_url);
                $this->customHttpCall($ch, $params);            
                $api_response = curl_exec($ch);
                $bet_id = $params['bet_id'];
                $this->CI->utils->debug_log("response: $bet_id", $api_response);
            }
        } else {
            return false;
        }
    }

    public function preprocessStatus($status) {
        if ($status == Game_logs::STATUS_REFUND) {
            $status = Game_logs::STATUS_REFUND;
        } else {
            $status = Game_logs::STATUS_SETTLED;
        }

        return $status;
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['bet_id'])) {
            $bet_details['bet_id'] = $row['bet_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['payout_amount'])) {
            $bet_details['win_amount'] = $row['payout_amount'];
        }

        if (isset($row['game'])) {
            $bet_details['game_name'] = $row['game'];
        }

        if (isset($row['round'])) {
            $bet_details['round_id'] = $row['round'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }

    public function getUnsettledRounds($dateFrom, $dateTo)
    {
        $sqlTime = 'trans.updated_at BETWEEN ? AND ?';
        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT 
    trans.timestamp_parsed AS transaction_date,
    trans.trans_type AS transaction_type,
    trans.status AS transaction_status,
    trans.game_platform_id,
    trans.player_id,
    trans.round_id,
    trans.external_uniqueid,
    trans.external_uniqueid AS transaction_id,
    trans.amount,
    trans.updated_at,

    gd.id AS game_description_id,
    gd.game_type_id

FROM {$this->original_transactions_table} AS trans
LEFT JOIN game_description AS gd ON trans.game_code = gd.external_game_id
WHERE trans.round_id NOT IN (
        SELECT round_id 
        FROM {$this->original_transactions_table} 
        WHERE trans_type IN ('settle', 'refund')
    )
    AND trans.trans_type = 'bet'
    AND trans.game_platform_id = ?
    AND {$sqlTime};
EOD;

        $params = [
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];
        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function checkBetStatus($bet_round)
    {
        $this->CI->load->model(array('original_game_logs_model', 'seamless_missing_payout'));
        $bet_round['status'] = Seamless_missing_payout::NOT_FIXED;
        $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $bet_round);
        if ($result === false) {
            return array('success' => false, 'exists' => false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid)
    {
        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();

        #parse the external_uniqueid to get the settle/refund external_uniqueid
        $settle_external_uniqueid = str_replace('bet', 'settle', $external_uniqueid);
        $refund_external_uniqueid = str_replace('bet', 'refund', $external_uniqueid);

        $sql = <<<EOD
SELECT
    external_uniqueid

FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND (external_uniqueid = ? OR external_uniqueid = ?)
EOD;

        $params = [
            $game_platform_id,
            $settle_external_uniqueid,
            $refund_external_uniqueid,
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        if(!empty($result)){
            if($result['external_uniqueid'] == $settle_external_uniqueid){
                $round_status = Game_logs::STATUS_SETTLED;
            }elseif($result['external_uniqueid'] == $refund_external_uniqueid){
                $round_status = Game_logs::STATUS_REFUND;
            }

            return array('success'=>true, 'status'=>$round_status);
        }
        return array('success'=>false, 'status'=>GAME_LOGS::STATUS_PENDING);
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
    }

}