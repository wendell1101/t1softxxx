<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: Bet Games
* Game Type: Live Casino
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph

    Related File
     - betgames_seamless_service_api.php
     - 
    
**/

abstract class Abstract_game_api_common_betgames extends Abstract_game_api {

	const MD5_FIELDS_FOR_ORIGINAL= [
        'player_name',
        'amount',
        'bet_id',
        'transaction_id',
        'promo_transaction_id',
        'bet',
        'bet_type',
        'bet_time',
        'game',
        'draw_code',
        'draw_time',
        'subscription_id',
        'subscription_time',
        'combination_id',
        'combination_time',
        'action',
        'before_balance',
        'after_balance'
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
        'result_amount',
        'after_balance',
        'before_balance'
    ];

    const URI_MAP = [
    ];


    public function __construct() {
    	parent::__construct();
    	$this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->secret_key = $this->getSystemInfo('secret_key', '9U2VE-J81MW-W5QKO-QCOII');
        $this->production_server = $this->getSystemInfo('production_server', 'https://integrations01.betgames.tv');
        $this->partner_code = $this->getSystemInfo('partner_code', 'entaplay');
        $this->conversion_rate = $this->getSystemInfo('conversion_rate');
        $this->language = $this->getSystemInfo('language');
    }

    public function isSeamLessGame()
    {
       return true;
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        $this->returnUnimplemented();
    }

    public function getHttpHeaders($params){
        $this->returnUnimplemented();
    }

    protected function customHttpCall($ch, $params) {
		$this->returnUnimplemented();
	}

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $this->returnUnimplemented();
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for slot factory api";
        if($return){
            $success = true;
            $message = "Successfull create account for slot factory api";
        }

        $this->utils->debug_log('<---------------Slot Factory------------> Succes: ', $success, 'Message: ', $message);
        
        return array("success" => $success, "message" => $message);
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

        $this->utils->debug_log('<---------------Sexy Baccarat------------> External Transaction ID: ', $external_transaction_id);

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

        $this->utils->debug_log('<---------------Sexy Baccarat------------> External Transaction ID: ', $external_transaction_id);

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

        $this->utils->debug_log(__FUNCTION__,'BetGames (Query Player Balance): ', $result);

        return $result;

    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    protected function getLauncherLanguage($language)
    {
        $lang='';
        switch ($language) {
            case 1: case 'en': case 'EN': case "English": $lang = 'en'; break;
            case 2: case 'cn': case 'CN': case "Chinese": $lang = 'cn'; break;
            case 4: case 'vn': case 'VN': case "Vietnamese": $lang = 'vn'; break;
            case 5: case 'ko-kr': case 'KO-KR': case "Korean": $lang = 'ko'; break;
            case 6: case 'th': case 'TH': case "Thai": $lang = 'th'; break;
            case 7: case 'id': case 'ID': case "Indonesian": $lang = 'id'; break;
            default: $lang = 'en'; break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName, $extra) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $token = $this->getPlayerToken($playerId);
        if(!empty($this->language)) {
            $language = $this->language;
        } else {
            $language = $this->getLauncherLanguage($extra['language']);
        }

        if($extra['token'] == true) {
            return array('success'=> true, 'token' => $token);
        }

        $params = [
            'your_production_server' => $this->production_server,
            'your_partner_code' => $this->partner_code,
            'player_token' => $token,
            'language_code' => $language,
            'timezone_utc' => "8"
        ];

        return array('success' => true, 'params' => $params);
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

        $gameRecords = $this->queryBetTransactions($startDate, $endDate);
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
        return array('success'=>true, $dataResult);
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

    /**
     * queryBetTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryBetTransactions($dateFrom, $dateTo){
        $sqlTime='bg.created_at >= ? and bg.created_at <= ? and action in ("debit","debit_subscription","debit_combination")';
        $sql = <<<EOD
SELECT 
bg.id as sync_index,
bg.player_name,
bg.method,
bg.amount,
bg.currency,
bg.bet_id,
bg.transaction_id,
bg.promo_transaction_id,
bg.retrying,
bg.bet,
bg.bet_type,
bg.type,
bg.odd,
bg.bet_time,
bg.game,
bg.draw_code,
bg.draw_time,
bg.subscription_id,
bg.subscription_time,
bg.combination_id,
bg.combination_time,
bg.is_mobile,
bg.action,
bg.before_balance,
bg.after_balance,
bg.created_at,
bg.updated_at,
bg.external_uniqueid,
bg.md5_sum,
bg.response_result_id,
bg.odd_name

FROM betgames_wallet_transactions as bg
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
    public function queryResultTransactions($bet_id){
        $sqlTime='bg.bet_id = ? and action in ("credit","credit_subscription","credit_combination")';
        $sql = <<<EOD
SELECT 
bg.id as sync_index,
bg.player_name,
bg.method,
bg.amount,
bg.currency,
bg.bet_id,
bg.transaction_id,
bg.promo_transaction_id,
bg.retrying,
bg.bet,
bg.bet_type,
bg.type,
bg.odd,
bg.bet_time,
bg.game,
bg.draw_code,
bg.draw_time,
bg.subscription_id,
bg.subscription_time,
bg.combination_id,
bg.combination_time,
bg.is_mobile,
bg.action,
bg.before_balance,
bg.after_balance,
bg.created_at,
bg.updated_at,
bg.external_uniqueid,
bg.md5_sum,
bg.response_result_id

FROM betgames_wallet_transactions as bg
WHERE

{$sqlTime}

EOD;

        $params=[$bet_id];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processGameRecords(&$gameRecords) {
        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {
                $data['player_name'] = isset($record['player_name']) ? $record['player_name'] : null;
                $data['method'] = isset($record['method']) ? $record['method'] : null;
                $data['amount'] = isset($record['amount']) ? $record['amount'] : null;
                $data['result_amount'] = isset($record['amount']) ? -$record['amount'] : 0;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['bet_id'] = isset($record['bet_id']) ? $record['bet_id'] : null;
                $data['transaction_id'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                $data['promo_transaction_id'] = isset($record['promo_transaction_id']) ? $record['promo_transaction_id'] : null;
                $data['retrying'] = isset($record['retrying']) ? $record['retrying'] : null;
                $data['bet_type'] = isset($record['bet_type']) ? $record['bet_type'] : null;
                $data['type'] = isset($record['type']) ? $record['type'] : null;
                $data['odd'] = isset($record['odd']) ? $record['odd'] : null;
                $data['bet_time'] = isset($record['bet_time']) ? $this->gameTimeToServerTime($record['bet_time']) : null;
                $data['game'] = isset($record['game']) ? $record['game'] : null;
                $data['draw_code'] = isset($record['draw_code']) ? $record['draw_code'] : null;
                $data['draw_time'] = isset($record['draw_time']) ? $this->gameTimeToServerTime($record['draw_time']) : null;
                $data['subscription_id'] = isset($record['subscription_id']) ? $record['subscription_id'] : null;
                $data['subscription_time'] = isset($record['subscription_time']) ? $this->gameTimeToServerTime($record['subscription_time']) : null;
                $data['combination_id'] = isset($record['combination_id']) ? $record['combination_id'] : null;
                $data['combination_time'] = isset($record['combination_time']) ? $this->gameTimeToServerTime($record['combination_time']) : null;
                $data['is_mobile'] = isset($record['is_mobile']) ? $record['is_mobile'] : null;
                $data['action'] = isset($record['action']) ? $record['action'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null; 
                $data['start_at'] = isset($record['created_at']) ? $this->gameTimeToServerTime($record['created_at']) : null;
                $data['end_at'] = isset($record['created_at']) ? $this->gameTimeToServerTime($record['created_at']) : null;
                
                if(isset($record['bet']) && json_decode($record['bet'],true) == null && empty($record['odd_name'])) {
                    $data['bet'] = $record['bet'];
                } else {
                    if(!empty($record['odd_name'])) {
                        $data['bet'] = $record['odd_name'];
                    } else {
                        $decodeBet = json_decode($record['bet'],true);
                        if(is_array($decodeBet) && count($decodeBet) > 1) {
                            $data['bet'] = json_encode($decodeBet, true);
                        } elseif(is_array($decodeBet) && count($decodeBet) < 1){
                            $data['bet'] = !empty($decodeBet) && isset($decodeBet['odd']['name'])?$decodeBet['odd']['name']:null;
                        } else {
                            $data['bet'] = $decodeBet;
                        }
                    }
                }

                $results = $this->queryResultTransactions($data['bet_id'], $data['transaction_id']);
                if(!empty($results)) {
                    foreach ($results as $result) {
                        $data['end_at'] = isset($result['created_at']) ? $this->gameTimeToServerTime($result['created_at']) : null;
                        if($result['amount'] > 0) {
                            $data['result_amount'] = isset($result['amount']) ? $result['amount'] : 0;
                            $data['before_balance'] = isset($result['before_balance']) ? $result['before_balance'] : null;

                        }
                        $data['after_balance'] = isset($result['after_balance']) ? $result['after_balance'] : null;
                    }
                }
                // print_r($data);exit;

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
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
        $sqlTime='bg.start_at >= ? AND bg.end_at <= ?';

        $sql = <<<EOD
SELECT
bg.id as sync_index,
bg.response_result_id,
bg.external_uniqueid,
bg.md5_sum,

bg.player_name as player_id,
bg.method,
bg.amount as bet_amount,
bg.amount as real_betting_amount,
bg.result_amount,
bg.currency,
bg.bet_id as round_number,
bg.transaction_id,
bg.promo_transaction_id,
bg.retrying,
bg.bet,
bg.bet_type,
bg.type,
bg.odd,
bg.bet_time as bet_at,
bg.start_at,
bg.end_at,
bg.game as game_code,
bg.game as game_name,
bg.draw_code,
bg.draw_time,
bg.subscription_id,
bg.subscription_time,
bg.combination_id,
bg.combination_time,
bg.is_mobile,
bg.action,
bg.before_balance,
bg.after_balance,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id,
game_provider_auth.login_name as player_username

FROM $this->original_gamelogs_table as bg
LEFT JOIN game_description as gd ON bg.game = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON bg.player_name = game_provider_auth.player_id
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

        $decodeJson = json_decode($row['game_code'],true);
        if(is_array($decodeJson)) {
            $game_code=$decodeJson['id'];
            $game_name=$decodeJson['name'];
        } else {
            $game_code=$decodeJson;
        }
        
        $row['game_code']=intval($game_code);
        $game_name=isset($game_name)?$game_name:$row['game_code'];
        $row['game_name']=$game_name;

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
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
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
            'extra' => [
                'odds' => $row['odd']
            ],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

        $decodeJson = json_decode($row['game_code'],true);
        if(is_array($decodeJson)) {
            $game_code=$decodeJson['id'];
            $game_name=$decodeJson['name'];
        } else {
            $game_code=$decodeJson;
        }
        
        $row['game_code']=intval($game_code);
        $game_name=isset($game_name)?$game_name:$row['game_code'];
        $row['game_name']=$game_name;

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $bet_details=$this->processBetDetails($row);
        $row['bet_details'] = $bet_details;
        $row['round_number'] = empty($row['round_number']) ? $row['combination_id'] : $row['round_number'];

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

    private function processBetDetails($gameRecords) {
        if(!empty($gameRecords)) {
            $details = json_decode($gameRecords['bet'],true);
            if($details != null) {
                foreach ($details as $detail) {
                    $bet_details[]=[
                        'bet_id' => $detail['bet_id'],
                        'transaction_id' => $detail['transaction_id'],
                        'odd_name' => $detail['odd']['name']
                    ];
                }

                return $bet_details;

            } else {
                return $gameRecords['bet'];
            }
        }
        
    }

}