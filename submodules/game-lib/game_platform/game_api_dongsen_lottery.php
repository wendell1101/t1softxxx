<?php
require_once dirname(__FILE__) . '/abstract_game_api_common_dongsen.php';

class Game_api_dongsen_lottery extends Abstract_game_api_common_dongsen {
	
    const ORIGINAL_GAME_LOGS = 'dongsen_lottery_game_logs';

    // Fields in dongsen_lottery_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL = [
        'userName',
        'userId',
        'gameName',
        'projectId',
        'betAmount',
        'realAmount',
        'prize',
        'profit',
        'betTime',
        'settleTime',
        'code',
        'issue',
        'prizeCode',
        'methodId',
        'methodName',
        'mode',
        'dypointDec',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'betAmount',
        'realAmount',
        'prize',
        'profit',
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'real_bet',
        'result_amount',
        'after_balance',
        'round_number',
        'game_code',
        'game_name',
        'player_username',
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'real_bet',
        'result_amount',
        'after_balance',
    ];
    
    public function getPlatformCode(){
        return DONGSEN_LOTTERY_API;
    }

    public function __construct(){
        $this->original_gamelogs_table = self::ORIGINAL_GAME_LOGS;
        parent::__construct();
    }

    public function syncOriginalGameLogs($token = false){

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startTime = $startDateTime->format('Y-m-d H:i:s');
        $endTime = $endDateTime->format('Y-m-d H:i:s');

        $result = array();
        $result [] = $this->CI->utils->loopDateTimeStartEnd($startTime, $endTime, '+15 minutes', function($startDate, $endDate)  {

            $startTime = strtotime($startDate->format('Y-m-d H:i:s')) * 1000;
            $endTime = strtotime($endDate->format('Y-m-d H:i:s')) * 1000;

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
            );

            $uuid = uniqid();
            $token = md5($uuid.$this->platform_ID.$this->agentCode.$endTime.$this->md5key);

            $params = json_encode([
                'uuid' => $uuid,
                'platId' => $this->platform_ID,
                'agentCode' => $this->agentCode,
                'endTime' => $endTime,
                'beginTime' => $startTime,
                'token' => $token
            ]);

            $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token, $params);

            $result = $this->callApi(self::API_syncGameRecords, $params, $context);

            sleep(5);

            return true;


        });


        return $result;

    }

    public function processResultForSyncOriginalGameLogs($params) {

        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = ['data_count' => 0];
        $gameRecords = isset($resultArr['data']) ? $resultArr['data'] : null;

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $this->processGameRecords($gameRecords, $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);

        }
        if(isset($resultArr['success']) && $resultArr['success'] !== self::CODE_SUCCESS){
            $this->debug_log('no any record', $resultArr);
        }

        return array($success, $result);
    }

    private function processGameRecords(&$gameRecords, $extra) {

        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['agentCode'] = isset($record['agentCode']) ? $record['agentCode'] : null;
                $data['userName'] = isset($record['userName']) ? $record['userName'] : null;
                $data['userId'] = isset($record['userId']) ? $record['userId'] : null;
                $data['gameOId'] = isset($record['gameOId']) ? $record['gameOId'] : null;
                $data['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
                $data['projectId'] = isset($record['projectId']) ? $record['projectId'] : null;
                $data['taskId'] = isset($record['taskId']) ? $record['taskId'] : null;
                $data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
                $data['realAmount'] = isset($record['realAmount']) ? $record['realAmount'] : null;
                $data['prize'] = isset($record['prize']) ? $record['prize'] : null;
                $data['profit'] = isset($record['profit']) ? $record['profit'] : null;
                $data['stat'] = isset($record['stat']) ? $record['stat'] : null;
                $data['betTime'] = isset($record['betTime']) ? $this->gameTimeToServerTime($record['betTime']) : null;
                $data['settleTime'] = isset($record['settleTime']) ? $this->gameTimeToServerTime($record['settleTime']) : null;
                $data['betType'] = isset($record['betType']) ? $record['betType'] : null;
                $data['code'] = isset($record['code']) ? $record['code'] : null;
                $data['issue'] = isset($record['issue']) ? $record['issue'] : null;
                $data['prizeCode'] = isset($record['prizeCode']) ? $record['prizeCode'] : null;
                $data['methodId'] = isset($record['methodId']) ? $record['methodId'] : null;
                $data['methodName'] = isset($record['methodName']) ? $record['methodName'] : null;
                $data['multiple'] = isset($record['multiple']) ? $record['multiple'] : null;
                $data['mode'] = isset($record['mode']) ? $record['mode'] : null;
                $data['isFast'] = isset($record['isFast']) ? $record['isFast'] : null;
                $data['dypointDec'] = isset($record['dypointDec']) ? $record['dypointDec'] : null;
                //default data
                $data['external_uniqueid'] = $record['projectId'];
                $data['response_result_id'] = $extra['response_result_id'];
                $gameRecords[$index] = $data;
                unset($data);

            }
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }


    public function syncMergeToGameLogs($token){
        $enabled_game_logs_unsettle=false;
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='ds.betTime >= ? AND ds.betTime <= ?';
        // if($use_bet_time){
        //     $sqlTime='ds.betTime >= ? AND ds.betTime <= ?';
        // }

        $sql = <<<EOD
SELECT
ds.id as sync_index,
ds.response_result_id,
ds.external_uniqueid,
ds.md5_sum,

ds.agentCode,
ds.userName,
ds.userId as player_username,
ds.gameOId as game_code,
ds.gameName as game_name,
ds.gameName as game_type,
ds.projectId as round_number,
ds.taskId,
ds.betAmount as bet_amount,
ds.realAmount as real_betting_amount,
ds.prize as win_amount,
ds.profit as result_amount,
ds.stat,
ds.betTime as bet_at,
ds.betTime as start_at,
ds.settleTime as end_at,
ds.betType,
ds.code,
ds.issue,
ds.prizeCode as bet_details,
ds.methodId,
ds.methodName,
ds.multiple,
ds.mode,
ds.isFast,
ds.dypointDec,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM $this->original_gamelogs_table as ds
LEFT JOIN game_description as gd ON ds.gameName = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON ds.userId = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


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
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => $row['win_amount'],
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => null,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

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
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

}

/*end of file*/

        
