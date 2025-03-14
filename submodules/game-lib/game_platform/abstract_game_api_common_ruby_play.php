<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: Ruby Play
* Game Type: Live Casino
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph

    Related File
     -
    
**/

abstract class Abstract_game_api_common_ruby_play extends Abstract_game_api {

    const MD5_FIELDS_FOR_ORIGINAL= [
        'playerId',
        'currencyCode',
        'gameId',
        'amount',
        'roundId',
        'transactionId',
        'gameRoundEnd',
        'referenceTransactionId',
        'action',
        'before_balance',
        'after_balance',
        'start_at',
        'end_at'
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'before_balance',
        'after_balance'
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount'
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->rubyplay_game_url = $this->getSystemInfo('rubyplay_game_url','https://stage.rpl4y.com/launcher');
        $this->operator_id = $this->getSystemInfo('operator_id','scs188');
        $this->server_url = $this->getSystemInfo('server_url','https://asia.test.rubyplay.io');
        $this->demo_game_url = $this->getSystemInfo('demo_game_url');
    }

    public function isSeamLessGame()
    {
       return true;
    }

    public function getOriginalTable() {
        return $this->returnUnimplemented();
    }

    public function getCurrency() {
        return $this->returnUnimplemented();
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        return $this->returnUnimplemented();
    }

    public function getHttpHeaders($params){

        return $this->returnUnimplemented();

    }

    protected function customHttpCall($ch, $params) {
        return $this->returnUnimplemented();
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for ruby play api";
        if($return){
            $success = true;
            $message = "Successfull create account for ruby play api";
        }

        $this->utils->debug_log('<---------------RubyPlay------------> Succes: ', $success, 'Message: ', $message);
        
        return array("success" => $success, "message" => $message);
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null) {

        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($userName);
        $playerBalance = $this->queryPlayerBalance($userName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $transaction = $this->insertTransactionToGameLogs($player_id, $userName, $afterBalance, $amount, NULL,$this->transTypeMainWalletToSubWallet());

        $this->utils->debug_log('<---------------RubyPlay------------> External Transaction ID: ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );

    
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null) {
    
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($userName);
        $playerBalance = $this->queryPlayerBalance($userName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $this->insertTransactionToGameLogs($player_id, $userName, $afterBalance, $amount, NULL,$this->transTypeSubWalletToMainWallet());

        $this->utils->debug_log('<---------------RubyPlay------------> External Transaction ID: ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );

    }


    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->utils->debug_log('<---------------RubyPlay------------> Query Player Balance: ', $result);

        return $result;

    }

    public function queryForwardGame($playerName, $extra) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $token = $this->getPlayerToken($playerId);
        $game_code = isset($extra['game_code'])?$extra['game_code']:null;
        
        if($extra['game_mode'] == 'trial' || $extra['game_mode'] == 'fun') {
            $url = $this->demo_game_url . "?gamename=".$game_code."&mode=offline";
            $this->CI->utils->debug_log('Ruby Play (Demo game url)', 'url', $url);
            return array('success' => true, 'url' => $url, 'redirect' => true);
        }

        $params = [
            'server_url' => $this->server_url,
            'gamename' => $game_code,
            'operator' => $this->operator_id,
            'playerSession' => $token
        ];

        $url = $this->rubyplay_game_url . "?" . http_build_query($params);

        $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

        return array('success' => true, 'url' => urldecode($url));
    }

    public function syncOriginalGameLogs($token) {
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

        $gameRecords = $this->queryTransactions($startDate, $endDate);
        // print_r($gameRecords);exit;
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

    public function processGameRecords(&$gameRecords) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['sessionToken'] = isset($record['sessionToken']) ? $record['sessionToken'] : null;
                $data['playerId'] = isset($record['playerId']) ? $record['playerId'] : null;
                $data['currencyCode'] = isset($record['currencyCode']) ? $record['currencyCode'] : $this->getCurrency();
                $data['gameId'] = isset($record['gameId']) ? $record['gameId'] : null;
                $data['amount'] = isset($record['amount']) ? $record['amount'] : null;
                $data['result_amount'] = -$record['amount'];
                $data['roundId'] = isset($record['roundId']) ? $record['roundId'] : null;
                $data['transactionId'] = isset($record['transactionId']) ? $record['transactionId'] : null;
                $data['deviceType'] = isset($record['deviceType']) ? $record['deviceType'] : null;
                $data['gameRoundEnd'] = isset($record['gameRoundEnd']) ? $record['gameRoundEnd'] : null;
                $data['referenceTransactionId'] = isset($record['referenceTransactionId']) ? $record['referenceTransactionId'] : null;
                $data['action'] = isset($record['action']) ? $record['action'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['start_at'] = isset($record['start_at']) ? $this->gameTimeToServerTime($record['start_at']) : null;
                $data['end_at'] = isset($record['end_at']) ? $this->gameTimeToServerTime($record['end_at']) : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;

                $result = $this->queryResultTransactions($data['roundId']);
                if(!empty($result)) {
                    $data['end_at'] = isset($result['end_at']) ? $this->gameTimeToServerTime($result['end_at']) : null;
                    if($result['action'] == 'cancel') {
                        $data['result_amount'] = $result['result_amount'];
                        $data['status'] = Game_logs::STATUS_CANCELLED;
                    }
                    if($result['amount'] > 0) {
                        $data['result_amount'] = isset($result['amount']) ? $result['amount'] : 0;
                        $data['before_balance'] = isset($result['before_balance']) ? $result['before_balance'] : null;

                    }
                    $data['after_balance'] = isset($result['after_balance']) ? $result['after_balance'] : null;
                }
                
                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

    /**
     * queryBetTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryTransactions($dateFrom, $dateTo){
        $sqlTime='rp.start_at >= ? and rp.end_at <= ? and action = "debit"';
        $sql = <<<EOD
SELECT 
rp.id as sync_index,
rp.sessionToken,
rp.playerId,
rp.currencyCode,
rp.gameId,
rp.amount,
rp.roundId,
rp.transactionId,
rp.deviceType,
rp.gameRoundEnd,
rp.referenceTransactionId,
rp.action,
rp.before_balance,
rp.after_balance,
rp.start_at,
rp.end_at,
rp.created_at,
rp.updated_at,
rp.external_uniqueid,
rp.md5_sum,
rp.response_result_id

FROM ruby_play_transactions as rp
WHERE

{$sqlTime}

EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

        /**
     * queryResultTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryResultTransactions($roundId){
        $sqlTime='rp.roundId = ? and (action="credit" or action="cancel")';
        $sql = <<<EOD
SELECT 
rp.id as sync_index,
rp.sessionToken,
rp.playerId,
rp.currencyCode,
rp.gameId,
rp.amount,
rp.roundId,
rp.transactionId,
rp.deviceType,
rp.gameRoundEnd,
rp.referenceTransactionId,
rp.action,
rp.before_balance,
rp.after_balance,
rp.start_at,
rp.end_at,
rp.created_at,
rp.updated_at,
rp.external_uniqueid,
rp.md5_sum,
rp.response_result_id

FROM ruby_play_transactions as rp
WHERE

{$sqlTime}

EOD;

        $params=[$roundId];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return end($result);
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /** queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='rp.start_at >= ? AND rp.end_at <= ?';

        $sql = <<<EOD
SELECT
rp.id as sync_index,
rp.response_result_id,
rp.external_uniqueid,
rp.md5_sum,

rp.sessionToken,
rp.playerId as player_id,
rp.currencyCode,
rp.gameId as game_name,
rp.gameId as game_code,
rp.amount as bet_amount,
rp.amount as real_betting_amount,
rp.result_amount,
rp.roundId as round_number,
rp.transactionId,
rp.deviceType,
rp.gameRoundEnd,
rp.referenceTransactionId,
rp.action,
rp.before_balance,
rp.after_balance,
rp.start_at as bet_at,
rp.start_at,
rp.end_at,
rp.created_at,
rp.updated_at,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id,
game_provider_auth.login_name as player_username

FROM $this->original_gamelogs_table as rp
LEFT JOIN game_description as gd ON rp.gameId = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON rp.playerId = game_provider_auth.player_id
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
                'game_type' => null,
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
                'win_amount' => null,
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
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if(isset($row['amount']) && isset($row['debit'])) {

        }

    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    protected function getLauncherLanguage($language) {
        return $this->returnUnimplemented();
    }

}