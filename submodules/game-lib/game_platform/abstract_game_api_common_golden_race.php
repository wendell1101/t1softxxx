<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Golden Race 
* Game Type: vSports
* Wallet Type: Seamless
* Asian Brand: V2G
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @jerbey.php.ph

    Related File
    -routes.php
    -golden_race_service_api.php
**/

abstract class Abstract_game_api_common_golden_race extends Abstract_game_api {
    const MD5_FIELDS_FOR_ORIGINAL = ['bet_amount','real_bet_amount','game_id','round','start_at','end_at','after_balance','before_balance','result_amount'];
    const MD5_FLOAT_AMOUNT_FIELDS = ['bet_amount','real_bet_amount','after_balance','before_balance','result_amount'];
    const MD5_FIELDS_FOR_MERGE=['token','game_code','round_number','bet_amount','result_amount','start_at','status','before_balance','after_balance'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount','after_balance','before_balance'];

    const ACTION_DEBIT = "debit";
    const ACTION_CREDIT = "credit";
    const ACTION_ROLLBACK = "rollback";
    const ALL_GAMES_ID = 10100;

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->api_url = $this->getSystemInfo('url');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url','https://tripleone-lobby.staging-hub.xpressgaming.net');
        $this->private_key = $this->getSystemInfo('private_key','sDeUx9AcAEq0bHl7KEjZ');
        $this->public_key = $this->getSystemInfo('public_key','sPdqe1lpkjoUh6s');
        $this->site_id = $this->getSystemInfo('site_id',9635);
        $this->currency = $this->getSystemInfo('currency');
        $this->group = $this->getSystemInfo('group','dev');
        $this->language = $this->getSystemInfo('language','en');
        $this->backurl = $this->getSystemInfo('backurl','http://www.staging.scs188.t1t.in');
        $this->cashierurl = $this->getSystemInfo('cashierurl','http://www.staging.scs188.t1t.in');
        $this->redirect = $this->getSystemInfo('redirect',false);
        $this->use_parent_agent = $this->getSystemInfo('use_parent_agent',false);
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        return $this->returnUnimplemented();
    }



    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        return $this->returnUnimplemented();
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for golden race api";
        if($return){
            $success = true;
            $message = "Successfull create account for golden race api";
        }
        
        return array("success" => $success, "message" => $message);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function processResultForQueryTransaction($params) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        return $result;
    }

    public function processResultForQueryPlayerBalance($params) {
        return $this->returnUnimplemented();
    }

    public function queryForwardGame($playerName, $extra){

        $player_id = $this->getPlayerIdInPlayer($playerName);
        $agent = $this->CI->player_model->getAgentNameByPlayerId($player_id);

        if(empty($agent) && $this->use_parent_agent){
            $this->group = $agent;
        }

        # check if demo game
        if(is_null($playerName) && isset($extra['game_mode']) && $extra['game_mode'] == 'trial'){

            $data = array(
                "token" => '',
                "game" => (isset($extra['game_code']) &&  $extra['game_code'] != 'null')  ? $extra['game_code'] : self::ALL_GAMES_ID,
                "backurl" => $this->backurl,
                "mode" => 0,
                "language" => isset($extra['language']) ? $extra['language'] : $this->language,
                "group" => $this->group,
                "clientPlatform" => $extra['is_mobile'] ? "mobile": "desktop",
                "cashierurl" => $this->cashierurl,
            );

        }else{

            $data = array(
                "token" => $this->getPlayerTokenByUsername($playerName),
                "game" => !empty($extra['game_code'])  ? $extra['game_code'] : self::ALL_GAMES_ID,
                "backurl" => $this->backurl,
                "mode" => $extra['game_mode'] == "real" ? 1 : 0,
                "language" => isset($extra['language']) ? $extra['language'] : $this->language,
                "group" => $this->group,
                "clientPlatform" => $extra['is_mobile'] ? "mobile": "desktop",
                "cashierurl" => $this->cashierurl,
            );
        }
 
        $data_string= implode('', $data);
        $data['h'] = md5($data_string.$this->private_key);
        $params = $data;
        $params = http_build_query($params);
        $url = $this->game_launch_url."?".$params;

        $result = array(
            "success" => true,
            "data_string" => $data_string,
            "data" =>$data,
            "url" => $url,
            "redirect" => $this->redirect
        );
        $this->utils->debug_log("GR_QUERYFORWARD ============================>", json_encode($result));
        return $result;
    }

    public function syncOriginalGameLogs($token = false){
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $gameRecords = $this->queryBetTransactions($startDate, $endDate);
        if(!empty($gameRecords)){
            $this->processGameRecords($gameRecords);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            $dataResult['data_count'] = count($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
        }
        return array(true, $dataResult);
    }

    public function processGameRecords(&$gameRecords){
        // echo "<pre>";
        //         print_r($gameRecords);exit();
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
                $data['game'] = isset($record['game']) ? $record['game'] : null;
                $data['session_id'] = isset($record['session_id']) ? $record['session_id'] : null;
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['round'] = isset($record['round']) ? $record['round'] : null;
                $data['bet_amount'] = isset($record['bet_amount']) ? $record['bet_amount'] : null;
                $data['result_amount'] = -$data['bet_amount'];
                $data['real_bet_amount'] = isset($record['real_bet_amount']) ? $record['real_bet_amount'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['start_at'] = isset($record['start_at']) ? $this->gameTimeToServerTime($record['start_at']) : null;
                $data['end_at'] = isset($record['end_at']) ? $this->gameTimeToServerTime($record['end_at']) : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                $data['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                $data['status'] = Game_logs::STATUS_SETTLED;//make settled by default for those game without winning amount request ex: instant sports
                $result = $this->queryResultTransaction($data['session_id'], $data['round']);
                if(!empty($result)){
                    foreach ($result as $key => $value) {
                        $data['end_at'] = isset($value['end_at']) ? $this->gameTimeToServerTime($value['end_at']) : null;

                        if(isset($value['action']) && $value['action'] == self::ACTION_ROLLBACK){
                            $data['status'] = Game_logs::STATUS_CANCELLED;
                        } else {
                            //update after balance only on those data with winning amount
                            if($this->utils->compareResultFloat($value['amount'], '>', 0)){
                                $data['after_balance'] = isset($value['after_balance']) ? $value['after_balance'] : null;
                            }
                            $data['result_amount'] += isset($value['amount']) ? $value['amount'] : 0;
                        }
                    }    
                }
                
                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }


    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token){
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='gr.start_at >= ? and gr.end_at <= ?';
        $sql = <<<EOD
SELECT gr.id as sync_index,
gr.username as player_username,
gr.session_id as token,
gr.game_id as game, 
gr.game_id as game_code, 
gr.round as round_number,
gr.bet_amount,
gr.result_amount,
gr.real_bet_amount,
gr.start_at as bet_at,
gr.start_at,
gr.end_at,
gr.response_result_id,
gr.external_uniqueid,
gr.created_at,
gr.updated_at,
gr.md5_sum,
gr.status,
gr.before_balance,
gr.after_balance,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM golden_race_game_logs as gr
LEFT JOIN game_description as gd ON gr.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON gr.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
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
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        //game description
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['bet_details']= [];
        // $row['status'] = Game_logs::STATUS_SETTLED;

        //datetime
        // $timestamp = isset($row['timestamp']) ? $this->gameTimeToServerTime($row['timestamp']) : null;
        // $row['start_at'] = $timestamp;
        // $row['end_at'] = $timestamp;
        // $row['bet_at'] = $timestamp;
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $extra_info=[];
        $has_both_side=0;

        if(empty($row['md5_sum'])){
            //genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            //set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    // public function getGameList(){
        //https://{SiteID}-api.hub.xpressgaming.net/api/v3/get-game-list
        //9635
        //https://9635-api.hub.xpressgaming.net/api/v3/get-game-list
    // }


            /**
     * queryBetTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryBetTransactions($dateFrom, $dateTo){
        $sqlTime='qb.created_at >= ? and qb.created_at <= ? and action="debit"';
        $sql = <<<EOD
SELECT qb.id as sync_index,
qb.playerId as username,
qb.action,
qb.sessionId as session_id,
qb.gameId as game, 
qb.gameId as game_id, 
qb.gameCycle as round,
qb.transactionId as external_uniqueid,
qb.transactionAmount as bet_amount,
qb.transactionAmount as real_bet_amount,
qb.timestamp as start_at,
qb.timestamp as end_at,
qb.response_result_id,
qb.created_at,
qb.updated_at,
qb.md5_sum,
qb.currency,
qb.before_balance,
qb.after_balance,
qb.response_result_id

FROM golden_race_transactions as qb
WHERE

{$sqlTime}

EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * queryBetTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryResultTransaction($sessionId, $roundNo){
        $sqlTime='qrt.sessionId = ? and qrt.gameCycle = ? and (action="credit" or action="rollback")';
        $sql = <<<EOD
SELECT qrt.id as sync_index,
qrt.playerId as username,
qrt.action,
qrt.sessionId as session_id,
qrt.gameCycle as round,
qrt.transactionAmount as amount,
qrt.response_result_id,
qrt.currency,
qrt.before_balance,
qrt.after_balance,
qrt.timestamp as end_at,
qrt.response_result_id

FROM golden_race_transactions as qrt
WHERE

{$sqlTime}

EOD;

        $params=[$sessionId,$roundNo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
}