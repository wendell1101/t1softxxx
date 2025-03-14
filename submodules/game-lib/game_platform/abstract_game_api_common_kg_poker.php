<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: KG Poker
* Game Type: Poker
* Wallet Type: Transfer Wallet
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph
**/

abstract class Abstract_game_api_common_kg_poker extends Abstract_game_api {

	const CODE_SUCCESS = 1;
    const INVALID_USERNAME = 'invalid xpid or puid';
    const DEFAULT_LIMIT = "500";
	const URI_MAP = array(
		self::API_createPlayer => '/login',
		self::API_depositToGame => '/increase_coins',
		self::API_withdrawFromGame => '/reduce_coins',
		// self::API_login => '/login',
		self::API_queryPlayerBalance => '/userinfo',
		self::API_queryTransaction => '/user_activity',
		self::API_syncGameRecords => '/platform_activity',
		self::API_queryForwardGame => '/login',
	);

	const MD5_FIELDS_FOR_ORIGINAL = [
        'userid',
        'reason',
        'gold_change',
        'kickback',
        'roomid',
        'gold_remain',
        'log_time',
        'roomid_tableid_time',
        'xpid',
        'puid',
        'json'
    ];
	const MD5_FIELD_FOR_MERGE = [
        'external_uniqueid',
        //money
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        //game
        'round_number',
        'game_code',
        'game_name',
        //player
        'player_username',
        //date time
        'start_at',
        'end_at',
        'bet_at',
    ];
	const MD5_FLOAT_AMOUNT_FIELDS = [
        'gold_change',
        'gold_remain'
    ];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function getOriginalTable() {
		return $this->returnUnimplemented();
	}

	public function __construct(){
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->xpid = $this->getSystemInfo('xpid', '6001');
		$this->key = $this->getSystemInfo('key', '37a7a2a2aaf889f0c08dbdb9dd8afc17');
		$this->original_gamelogs_table = $this->getOriginalTable();
		$this->getPlatformID = $this->getPlatformCode();
        $this->transfer_max_amount = $this->getSystemInfo('transfer_max_amount');
        $this->deposit_max_balance_on_auto_transfer = $this->getSystemInfo('deposit_max_balance_on_auto_transfer', false);
	}

	public function getHttpHeaders($params) {

		$header = array('Content-Type' => 'application/json');

		return $header;

	}

	public function generateUrl($apiName, $params) {
		$url = $this->api_url . self::URI_MAP[$apiName];
		$par = array(
			'xpid' => $this->xpid,
			'timestamp' => $this->CI->utils->getTimestampNow(),
			'hash' => $this->hash($params)
		);
		
		$this->utils->debug_log('<---------------HASH VALUES------------> Hash Values: ', $par);

		$url .= '?' . http_build_query($par);

		return $url;

	}

	public function customHttpCall($ch, $params) {

		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));

	}

	private function hash($params) {
		$hash = md5(json_encode($params) . "|"  . time() . "|" . $this->xpid . "|" . $this->key);

		$this->utils->debug_log('<---------------HASH VALUES------------> Hash Values: ', $hash, 'Json: ', json_encode($params), 'timestamp: ', time(), 'xpid: ', $this->xpid, 'Key: ', $this->key);

		return $hash;
	}

	public function processResultBoolean($responseResultId, $resultArr, $username = null){

        $success = false;
        if(!empty($resultArr) && $resultArr['ok'] == self::CODE_SUCCESS){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('KG Poker Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;

    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = array(
        	'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername,
        );

        $params = array(
        	'puid' => $gameUsername,
        	'ip' => $this->CI->utils->getIP(),
        	'nickname' => $gameUsername,
        );

        return $this->callApi(self::API_createPlayer, $params, $context);

    }

    public function processResultForCreatePlayer($params) {

    	$resultArr = $this->getResultJsonFromParams($params);
    	$statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
    	$responseResultId = $this->getResponseResultIdFromParams($params);
    	$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

    	if($success){

            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

        }

        return [$success, $result];

    }

	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
            'playerName' => 'testt1dev',
            'external_transaction_id' => $external_transaction_id
		);

        $amount = $this->convertBalance($amount);

		$params = array(
			'puid' => $gameUsername,
			'coins' => strval($amount),
			'seqid' => $external_transaction_id
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

    public function preDepositToGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {
        $checkBal = $this->queryPlayerBalance($playerName);
        if($checkBal['success'] && isset($checkBal['balance'])){
            $checkMaxLimit = $this->getTransferMaxAmount();
            if($checkBal['balance'] >= $checkMaxLimit) {
            return array(
                    'success' => false,
                    'message' => 'The amount exceeds the maximum limit transfer'
                );
            }
        }else{
         return array(
                'success' => false,
                'message' => 'API Query Balance failed'
            );
        }
        return $this->returnUnimplemented();
    }

    public function convertTransactionAmount($amount) {
        if($amount >= $this->transfer_max_amount) {
            $amount = $this->transfer_max_amount;
        } else {
            return $amount;
        }

        return $amount;
    }

	public function processResultForDepositToGame($params) {

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => 'testt1dev',
            'external_transaction_id' => $external_transaction_id
		);

        $amount = $this->convertBalance($amount);

		$params = array(
			'puid' => $gameUsername,
			'coins' => strval($amount),
			'seqid' => $external_transaction_id
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);

	}

	public function processResultForWithdrawFromGame($params) {

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

	}

    public function queryPlayerBalance($playerName) {
    	
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
    		'callback_obj' => $this,
    		'callback_method' => 'processResultForQueryPlayerBalance',
    		'playerName' => $playerName
    	);

    	$params = array(
    		'puid' => $gameUsername
    	);

    	return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
    	$statusCode = $this->getStatusCodeFromParams($params);
    	$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
    	$balance = 0;

    	if($success){
            if(isset($resultArr['coins'])){
                $coins = $this->convertBalanceAmount($resultArr['coins']);
                $balance = $coins;
            }else{
                //wrong result, call failed
                $success=false;
            }
        } else {
            if($resultArr['err'] == self::INVALID_USERNAME) {
                $balance = null;
            }
            $sucess=false;
        }

        return [$success, ['response_result_id'=>$responseResultId,'balance'=>$balance]];

    }

    public function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        $isError = true;
        switch($apiName) {
            case self::API_queryPlayerBalance:
                $isError = $errCode || intval($statusCode, 10) > 401;
                break;
            default:
                $isError =  parent::isErrorCode($apiName, $params, $statusCode, $errCode, $error);
        }

        return $isError;
    }

    public function queryTransaction($transactionId, $extra) {
    	return $this->returnUnimplemented();
    }

    public function queryForwardGame($playerName, $extra) {
    	
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
    		'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
    	);

    	$params = array(
    		'puid' => $gameUsername,
    		'ip' => $this->CI->utils->getIP(),
    		'nickname' => $gameUsername
    	);

    	$this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $params);

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params) {

    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            if(isset($resultArr['url'])){
                $result['url']=$resultArr['url'];
            }else{
                //missing address
                $success=false;
            }
        }

        return [$success, $result];

    }

    public function syncOriginalGameLogs($token) {
    	
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startTime = $startDateTime->format('Y-m-d H:i:s');
        $endTime = $endDateTime->format('Y-m-d H:i:s');

        $result = array();
        $result [] = $this->CI->utils->loopDateTimeStartEnd($startTime, $endTime, '+60 minutes', function($startDate, $endDate)  {

            $startTime = strtotime($startDate->format('Y-m-d H:i:s'));
            $endTime = strtotime($endDate->format('Y-m-d H:i:s'));

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
                'startDate' => $startDate,
                'endDate' => $endDate
            );

            $params = array(
                'before' => strval($endTime),
                'after' => strval($startTime),
                'limit' => self::DEFAULT_LIMIT,
            );

            $this->CI->utils->debug_log('<-------------- PARAMS -------------->', $params);

            return $this->callApi(self::API_syncGameRecords, $params, $context);

        });

        return array(true, $result);

    }

    public function processResultForSyncOriginalGameLogs($params) {

        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $startDate = $this->getVariableFromContext($params, 'startDate');
        $endDate = $this->getVariableFromContext($params, 'endDate');
        $result = ['data_count' => 0];
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $gameRecords = isset($resultArr['logs']) ? $resultArr['logs'] : null;

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

        return array($success, $result);

    }

    private function processGameRecords(&$gameRecords, $extra) {
        // print_r($gameRecords);exit();
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['userid'] = isset($record['userid']) ? $record['userid'] : null;
                $data['reason'] = isset($record['reason']) ? $record['reason'] : null;
                $data['gold_change'] = isset($record['gold_change']) ? $this->convertBalanceAmount($record['gold_change']) : null;
                $data['kickback'] = isset($record['kickback']) ? $record['kickback'] : null;
                $data['roomid'] = isset($record['roomid']) ? $record['roomid'] : null;
                $data['gold_remain'] = isset($record['gold_remain']) ? $this->convertBalanceAmount($record['gold_remain']) : null;
                $data['log_time'] = isset($record['log_time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $record['log_time'])) : null;
                $jsonValues = isset($record['json']) ? json_decode($record['json'], true) : null;
                $data['starttime'] = isset($record['json']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $jsonValues['starttime'])) : null;
                $data['endtime'] = isset($record['json']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $jsonValues['endtime'])) : null;
                $data['game_name'] = isset($record['json']) ? $jsonValues['name'] : null;
                // if(isset($record['roomid_tableid_time'])) {
                //     $game_code = explode("-", $record['roomid_tableid_time']);
                //     $dateTime = date('Y/m/d H:i:s', end($timeStamp));
                //     array_splice($timeStamp, 2);
                //     $idAndDate = implode("-", $timeStamp) . "-" . $dateTime;
                // }
                $data['roomid_tableid_time'] = isset($record['roomid_tableid_time']) ? $record['roomid_tableid_time'] : null;
                $data['xpid'] = isset($record['xpid']) ? $record['xpid'] : null;
                $data['puid'] = isset($record['puid']) ? $record['puid'] : null;
                $data['json'] = isset($record['json']) ? $record['json'] : null;            
                // //default data
                $data['response_result_id'] = $extra['response_result_id'];
                $data['external_uniqueid'] = $record['roomid_tableid_time'] . "-" . $record['userid'] . "-" . $record['gold_remain'];
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

	public function syncMergeToGameLogs($token) {

		$enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);

	}

    /* queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='kg.starttime >= ? AND kg.starttime <= ?';

        $sql = <<<EOD
SELECT
kg.id as sync_index,
kg.response_result_id,
kg.external_uniqueid,
kg.md5_sum,

kg.userid,
kg.reason,
kg.gold_change as result_amount,
kg.gold_change as bet_amount,
kg.gold_change as real_betting_amount,
kg.kickback,
kg.roomid as game_code,
kg.gold_remain as after_balance,
kg.log_time,
kg.starttime as bet_at,
kg.starttime as start_at,
kg.endtime as end_at,
kg.game_name as game_name,
kg.roomid_tableid_time as round_number,
kg.xpid,
kg.puid as player_username,
kg.json,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM $this->original_gamelogs_table as kg
LEFT JOIN game_description as gd ON kg.roomid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON kg.puid = game_provider_auth.login_name
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
                'bet_amount' => abs($row['bet_amount']),
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => abs($row['bet_amount']),
                'real_betting_amount' => abs($row['real_betting_amount']),
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

    public function preprocessOriginalRowForGameLogs(array &$row) {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $bet_details = $this->processBetDetails($row);
        $row['bet_details'] = $bet_details;

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

    private function processBetDetails($gameRecords) {

        if(!empty($gameRecords)) {
            if(isset($gameRecords['json']) && !empty($gameRecords['json'])) {
                $jsonValues = json_decode($gameRecords['json'], true);
                $jsonValues['starttime'] = date('Y-m-d H:i:s', $jsonValues['starttime']);
                $jsonValues['endtime'] = date('Y-m-d H:i:s', $jsonValues['endtime']);
                $jsonValues['banker'] = $jsonValues['banker'] == 1 ? 'True' : 'False';

            }
                return $jsonValues;
        }
        
    }

    private function convertBalance($amount) {

        $balance = $amount * 100;

        return $balance;

    }

    private function convertBalanceAmount($amount) {

        $balanceAmount = $amount / 100;

        return $balanceAmount;

    }

}


?>