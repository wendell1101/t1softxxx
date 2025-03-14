<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: 97lotto
* Game Type: Lottery
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph

    Related File
     - Controller : lotto97_seamless_service_api.php
     - Model : common_seamless_wallet_transactons.php 
    
**/

abstract class Abstract_game_api_common_97lotto extends Abstract_game_api {

    const MD5_FIELDS_FOR_ORIGINAL= [
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
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
        'result_amount',
        'after_balance',
        'before_balance'
    ];

    const URI_MAP = [
        self::API_createPlayer => '/api/addPlayer',
        self::API_isPlayerExist => '/api/isPlayerExist',
        self::API_login => '/api/playerLogin'
    ];
    const SUCCESS=1;

    public function __construct() {
        parent::__construct();
        $this->api_url=$this->getSystemInfo('url');
        $this->banker_id=$this->getSystemInfo('banker_id');
        $this->banker_token=$this->getSystemInfo('banker_token');
        $this->game_url=$this->getSystemInfo('game_url', 'http://api.97lotto.com/login.php');
        $this->disabled_bet_response=$this->getSystemInfo('disabled_bet_response', false);
        $this->disabled_bet_result=$this->getSystemInfo('disabled_bet_result', false);

        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['Bet']);
    }

    public function isSeamLessGame() {
       return true;
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {

        $url=$this->api_url.self::URI_MAP[$apiName];
        $this->debug_log('generateUrl by '.$apiName, $url);

        return $url;
    }

    public function getHttpHeaders($params){
        $header = [];
        $header['Content-Type'] = 'application/x-www-form-urlencoded';

        $this->utils->debug_log(__FUNCTION__,'97lotto: ',$header);

        return $header;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode) {
        $success = false;
        if(!empty($resultArr) && $resultArr['status']==self::SUCCESS && $statusCode==200){
            $success=true;
        }

        // if($resultArr['status']==0 && $resultArr['message'] == self::PLAYER_EXIST) {
        //     $success=true;
        // }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
        }
        
        $this->utils->debug_log(__FUNCTION__,'97lotto (processResultBoolean): ','success',$success,'result',$resultArr,'statusCode',$statusCode);
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        //create player in db
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $time=time();
        $signature=md5($time.$this->banker_token);

        $params=array(
            'bid' => $this->banker_id,
            'time' => $time,
            'signature' => $signature,
            'username' => $gameUsername,
            'password' => $password,
            'fullname' => $gameUsername
        );
        
        $this->utils->debug_log(__FUNCTION__,'97lotto (createPlayer): ', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {

        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);

    }

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );

        $time=time()+5;
        $signature=md5($time.$this->banker_token);

        $params = array(
            'bid' => $this->banker_id,
            'time' => $time,
            'signature' => $signature,
            'username' => $gameUsername
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){

        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            if($resultArr['status'] == self::SUCCESS) {
                $result = ['exists' => true];
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            } else {
                $result = ['exists' => false];
            }
        } else {
            $result = ['exists' => false];
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $playerBalance = $this->queryPlayerBalance($playerName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $transaction = $this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, NULL,$this->transTypeMainWalletToSubWallet());

        $this->utils->debug_log(__FUNCTION__,'97lotto (depositToGame): ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $playerBalance = $this->queryPlayerBalance($playerName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, NULL,$this->transTypeSubWalletToMainWallet());

        $this->utils->debug_log(__FUNCTION__,'97lotto (withdrawFromGame): ', $external_transaction_id);

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

        $this->utils->debug_log(__FUNCTION__,'97lotto (Query Player Balance): ', $result);

        return $result;

    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    protected function getLauncherLanguage($language) {
       return $this->returnUnimplemented();
    }

    public function queryForwardGame($playerName, $extra) {

        $this->CI->load->model('external_common_tokens');

        $player_id=$this->getPlayerIdFromUsername($playerName);
        $token=$this->login($playerName);

        $url = $this->game_url;
        if(isset($token['token']) && isset($token['player_id'])) {
            $this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($player_id,$token['token'],$token['player_id'],$this->getPlatformCode(),$this->currency);
            $url .= '?token='.$token['token'];
        } else {
            $url .= '?token='.'null';
        }

        $this->utils->debug_log(__FUNCTION__,'97lotto (queryForward url): ', $url);

        return array('success' => true, 'url' => $url);
    }

    public function login($playerName, $password=null) {

        $gameUsername=$this->getGameUsernameByPlayerUsername($playerName);
        $password=$this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $time=time()+3;
        $signature=md5($time.$this->banker_token);

        $params = array(
            'bid' => $this->banker_id,
            'time' => $time,
            'signature' => $signature,
            'username' => $gameUsername,
            'password' => $password
        );

        if(isset($this->return_url) && !empty($this->return_url)) {
            $params['ret_url'] = $this->return_url;
        }

        $this->utils->debug_log(__FUNCTION__,'97lotto (login): ', $params);

        return $this->callApi(self::API_login, $params, $context);

    }

    public function processResultForLogin($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            $result['token'] = $resultArr['token'];
            $result['player_id'] = $resultArr['pid'];
        }

        return array($success, $result);

    }

    public function processResultForQueryForwardGame($params) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token) {
        return $this->returnUnimplemented();
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

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        
        $gameRecords = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time);
        // print_r($gameRecords);exit;

        $this->processGameRecordsFromTrans($gameRecords);

        return $gameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime="lg.created_at >= ? AND lg.created_at <= ? AND lg.game_platform_id = ? AND transaction_type = 'BetResponse'";

        $sql = <<<EOD
SELECT
lg.id as sync_index,
lg.response_result_id,
lg.external_unique_id as external_uniqueid,
lg.md5_sum,

lg.player_id,
lg.game_platform_id,
lg.amount as bet_amount,
lg.amount as real_betting_amount,
lg.game_id as game_code,
lg.transaction_type,
lg.status,
lg.round_id as round_number,
lg.transaction_id,
lg.response_result_id,
lg.extra_info,
lg.start_at,
lg.start_at as bet_at,
lg.end_at,
lg.before_balance,
lg.after_balance,

gd.id as game_description_id,
gd.game_type_id

FROM common_seamless_wallet_transactions as lg
LEFT JOIN game_description as gd ON lg.game_id = gd.external_game_id AND gd.game_platform_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
        
    }

    public function processGameRecordsFromTrans(&$gameRecords) {

        $temp_game_records = [];

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {
                $bet_details = isset($record['extra_info']) ? json_decode($record['extra_info'], true) : null;
                $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                $temp_game_records['player_username'] = isset($record['player_username']) ? $record['player_username'] : null;
                $temp_game_records['bet_amount'] = isset($record['bet_amount']) ? $record['bet_amount'] : null;
                $temp_game_records['real_betting_amount'] = isset($record['real_betting_amount']) ? $record['real_betting_amount'] : null;
                $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                $temp_game_records['round_number'] = isset($record['round_number']) ? $record['round_number'] : null;
                $temp_game_records['transaction_id'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                $temp_game_records['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $temp_game_records['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                $temp_game_records['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                $temp_game_records['bet_at'] = isset($record['bet_at']) ? $record['bet_at'] : null;
                $temp_game_records['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                $temp_game_records['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                $temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                $temp_game_records['bet_details'] = "Bet Type: {$bet_details['betType']}, Bet No. : {$bet_details['betNumber']}";

                $result = $this->queryBetResult($temp_game_records['transaction_id']);
                if(!empty($result)) {
                    $temp_game_records['win_amount'] = isset($result['amount']) ? $result['amount'] : null;
                    $temp_game_records['result_amount'] = isset($result['amount']) ? $temp_game_records['win_amount'] - $temp_game_records['bet_amount'] : null;
                    $temp_game_records['end_at'] = isset($result['end_at']) ? $result['end_at'] : null;
                    $temp_game_records['transaction_type'] = isset($result['transaction_type']) ? $result['transaction_type'] : null;
                } else {
                    $temp_game_records['result_amount'] = -$temp_game_records['bet_amount'];
                }

                $gameRecords[$index] = $temp_game_records;
                unset($data);
            }
        }

    }

    public function queryBetResult($trans_id) {

        $sqlTime='lg.transaction_id = ? and lg.game_platform_id = ? and lg.transaction_type in ("BetResult", "BetRefund")';

        $sql = <<<EOD
SELECT
lg.player_id,
lg.game_platform_id,
lg.amount,
lg.transaction_type,
lg.end_at,
lg.after_balance
FROM common_seamless_wallet_transactions as lg
WHERE
{$sqlTime}
EOD;

        $params=[
            $trans_id,
            $this->getPlatformCode()
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        return $result;


    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
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
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [],
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
            $status = Game_logs::STATUS_SETTLED;
            if($row['transaction_type'] == 'BetRefund') {
                $status = Game_logs::STATUS_REFUND;
            }
            else if(strtotime($row['end_at']) > strtotime('now')) {
                $status = Game_logs::STATUS_PENDING;
                $row['end_at'] = $row['start_at'];
            }
            else  {
                $status = Game_logs::STATUS_SETTLED;
            }
            $row['status'] = $status;
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

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        return $this->returnUnimplemented();
    }

    public function processGameRecords(&$gameRecords) {
        return $this->returnUnimplemented();
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
      
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                if($transaction['trans_type']=='BetResponse'){
                 continue;
                }
                
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);                
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
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

}