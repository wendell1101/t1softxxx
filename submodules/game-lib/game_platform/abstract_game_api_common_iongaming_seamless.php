<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: ION Gaming 
* Game Type: Live Dealer
* Wallet Type: Seamless
*
/**
* API NAME: ION GAMING
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @renz.php.ph
**/

abstract class Abstract_game_api_common_iongaming_seamless extends abstract_game_api {

	# Fields in fg_seamless_gamelogs we want to detect changes for update
    const MD5_FIELDS_FOR_TRANSACTION_LOGS = [
        'RefNo',
        'SeqNo',
        'OrderId',
        'AccountId',
        'OrderTime',
        'Stake',
        'WinningStake',
        'PlayerWinLoss',
        'SettlementStatus',
        'GameId',
        'GameStartTime',
        'SettleTime',
        'TableName',
        'GroupBetOptions',
        'BetOptions',
        'Timestamp',
        'transaction_type'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS = [
        'Stake',
        'WinningStake',
        'PlayerWinLoss'
    ];

    const TRANSACTION_LOGS = 'transaction';

    const API_otherQueryForwardGame = '/web-page/general/dispatch.aspx';
    const URI_MAP = array(
        self::API_createPlayer => '/api/v2/Player/CreatePlayer/',
        self::API_queryForwardGame => '/middleware/v2/Dispatch/Game/',
        self::API_syncGameRecords => '/api/v2/BetDetail/Date/',
        self::API_checkLoginToken => '/api/v2/Player/RefreshLoginSession/',
    );

	public function __construct() {
		parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));

        $this->api_url = $this->getSystemInfo('url');
        $this->key = $this->getSystemInfo('key');
        $this->salt = $this->getSystemInfo('secret');
        $this->product_group = $this->getSystemInfo('product_group', 'ION');
        $this->product_type = $this->getSystemInfo('product_type', 'ION');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');
        $this->white_label_id = $this->getSystemInfo('white_label_id', 'IDS');
        $this->table_limit = $this->getSystemInfo('table_limit', 'LOW');
        $this->currency = $this->getSystemInfo('currency');
        $this->enable_check_status = $this->getSystemInfo('enable_check_status' , true);
        $this->try_other_game_launch = $this->getSystemInfo('try_other_game_launch' , true);
        $this->method = '';
        $this->adjust_dateto_minutes_sync_merge = $this->getSystemInfo('adjust_dateto_minutes_sync_merge', '0');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+6 hours'); // 6 hours max timeframe per call
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '300');
	}

    public function getUniqueId() {
        return hexdec(uniqid());
    }

    public function generateUrl($apiName, $params)
    {
        $apiUri = self::URI_MAP[$apiName];

        $url = $this->api_url . $apiUri;

        if($this->method == self::API_createPlayer){
            $url .= $this->white_label_id;
        }

        return $url;
    }

    protected function customHttpCall($ch, $params)
    {
        $data_json = json_encode($params);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    }

    protected function getHttpHeaders($params)
    {
        return array('Content-Type' => 'application/json');
    }

    public function isSeamLessGame()
    {
       return true;
    }

    private function generateTimestamp()
    {
        $date = new DateTime('now', new DateTimeZone('GMT-5'));
        return $timestamp = $date->format('Y-m-d\TH:i:s');
    }

    private function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            case 3:
            case 'id-id':
                $lang = 'id-ID'; // indonesian
                break;
            case 4:
            case 'vi-vn':
                $lang = 'vi-VN'; // vietnamese
                break;
            case 5:
            case 'ko-kr':
                $lang = 'ko-KR'; // korean
                break;
            case 6:
            case 'th-th':
                $lang = 'th-TH'; // thailand
                break;
            case 'my-mm':
                $lang = 'my-MM'; // burmese
                break;
            default:
                $lang = 'en-US'; // default as english
                break;
        }
        return $lang;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode)
    {
        $success = false;
        $success = $resultArr['errorCode'] == 0 ? true : false;

        # we check here if errorCode is 404 meaning game logs API response is success but empty
        if(isset($resultArr['errorCode']) && $resultArr['errorCode'] == 404 && $statusCode == 200){
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('ION GAMING got error ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }


    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'AccountId' => $gameUsername,
            'Currency'  => $this->currency,
            'Timestamp' => $this->generateTimestamp(),
            'Checksum'  => base64_encode(md5($gameUsername . '.' . $this->currency . '.' . $this->generateTimestamp() . '.' . $this->salt, TRUE))
        );

        $this->method = self::API_createPlayer;

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        $result = ['player' => $gameUsername];

        if($success){
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        return array($success, $result);
    }

	public function queryPlayerBalance($playerName)
	{
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

		$result = array(
			'success' => true, 
            'balance' => $balance
            // 'balance' => $this->dBtoGameAmount($balance)
		);

		return $result;
	}

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    /*
    * Example URL :
    * http://wwwidg1.ionclubtry.com/middleware/v1/Dispatch/Game/TRG/TRG/BACCARAT/DESKTOP/en-US
    */
    public function queryForwardGame($playerName = null, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $platformtype = !$extra['is_mobile'] ? 'DESKTOP' : 'MOBILE';
        $lang = $this->getLauncherLanguage($extra['language']);

        $str = $gameUsername . "." . $this->currency . "." . $this->white_label_id . "." . $this->generateTimestamp();
        $strMd5 = md5($str . "." . $this->salt, true);
        $checksum = base64_encode($strMd5);
        $getFields["Checksum"] = $checksum;
        $data_string = json_encode($getFields);
        $strPlainText = $str . "." . $checksum;

        $encrypted_text = openssl_encrypt($strPlainText, 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA);
        $payload = base64_encode($encrypted_text);
        
        $url = $this->game_launch_url . self::URI_MAP['queryForwardGame'] . $this->product_type . '/' . $extra['game_code'] . '/' . $platformtype . '/' . $lang;

        $this->CI->utils->debug_log('IONGAMING SEAMLESS queryForwardGame PARAMS ===>', 'PAYLOAD ===>', $payload, 'PREPROCESS_PAYLOAD ===>', $strPlainText, 'CHECKSUM ===>', $checksum, 'PREPROCESS_CHECKSUM', $str);
        
        return ['success' => true,'url' => $url, 'payload' => $payload];
    }

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
    }

	public function doSyncOriginal($data, $table_name, $process) {
        $success = false;
		$result = ['data_count' => 0];

		if ($process == self::TRANSACTION_LOGS) {
			$md5_fields = self::MD5_FIELDS_FOR_TRANSACTION_LOGS;
			$md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS;
		}

        if (!empty($data)) {
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $table_name,
                $data,
                'external_uniqueid',
                'external_uniqueid',
                $md5_fields,
                'md5_sum',
                'id',
                $md5_float
            );

			// $this->CI->utils->debug_log('after process available rows', !empty($gameRecords) ? count($gameRecords) : 0, !empty($insertRows) ? count($insertRows) : 0, !empty($updateRows) ? count($updateRows) : 0);

            unset($data);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', [], $table_name);
                $success = true;
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', [], $table_name);
                $success = true;
            }
            unset($updateRows);
        }
        return array('success' => $success);
	}

    /*
     * Note: You can only search data within the past 60 days.
     * 7.6.3 Game History
     */
    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
    }

    public function checkRollbackTrans($refNo) {
                $sql = <<<EOD
SELECT 
    id
    FROM {$this->original_transaction_table} AS ig
WHERE
    ig.RefNo = ? AND ig.transaction_type = ?
EOD;

        $params=[
            $refNo,
            'RollbackBalance'
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    // This syncMerge is for bulk records which date time can be applied
    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

   public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time = false )
    {
        $dateFrom = new DateTime($dateFrom);
        $dateFrom = $dateFrom->modify($this->getDatetimeAdjustSyncMerge());
        $dateFrom = $dateFrom->format('Y-m-d H:i:s');

        $modify_datetto = '+' . $this->adjust_dateto_minutes_sync_merge . ' minutes';
        $dateTo = new DateTime($dateTo);
        $dateTo = $dateTo->modify($modify_datetto);
        $dateTo = $dateTo->format('Y-m-d H:i:s');

        $sqlTime = '`ig`.`Timestamp` >= ?
          AND `ig`.`Timestamp` <= ?';

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    ig.ProductType as game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    ig.AccountId AS player_username,
    ig.RefNo AS RefNo,
    ig.OrderId AS OrderId,
    ig.WinningStake AS bet_amount,
    ig.WinningStake AS real_betting_amount,
    ig.PlayerWinLoss AS win_amount,
    ig.Timestamp AS start_at,
    ig.Timestamp AS end_at,
    ig.external_uniqueid AS external_uniqueid,
    ig.transaction_type AS transaction_type,
    ig.before_balance AS before_balance,
    ig.after_balance AS after_balance,
    ig.extra AS extra,
    ig.md5_sum AS md5_sum
    FROM {$this->original_transaction_table} AS ig
    LEFT JOIN game_description AS gd ON ig.ProductType = gd.external_game_id AND gd.game_platform_id = ?
    LEFT JOIN game_type AS gt ON gd.game_type_id = gt.id
    LEFT JOIN game_provider_auth AS gpa ON ig.AccountId = gpa.login_name
    AND gpa.game_provider_id = ?
WHERE
    {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $data = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        $rollback_ids = [];
        $deductbalance_ids = [];
        $insert_record = [];
        $settle_ids = [];

        $temp_data = [];
        $new_record = [];

        foreach ($data as $record) {
            ## SETTLE RECORD MUST HAVE DEDUCT BALANCE RECORD
            if ($record['transaction_type'] == 'DeductBalance') {
                $deductbalance_ids[] = $record['RefNo'];
            }
            if ($record['transaction_type'] == 'Insert') {
                $insert_record[] = ['RefNo' => $record['RefNo'], 'game_code' => $record['game_code']];
            }
            if ($record['transaction_type'] == 'Settle') {
                $settle_ids[] = $record['RefNo'];
                $record['bet_details'] = $record['extra'];
                $temp_data[] = $record;
            }
            if ($record['transaction_type'] == 'RollbackBalance') {
                $rollback_ids[] = $record['RefNo'];
            }
        }

        foreach ($temp_data as $record) {
            ## IF SETTLE RECORD HAS DEDUCT BALANCE RECORD and DONT HAVE ROLLBACK BALANCE RECORD
            
            ## IF REFNO HAS ROLLBACK SKIP THIS ON MERGE
            $is_rollback = $this->checkRollbackTrans($record['RefNo']);
            if (!empty($is_rollback)) {
                continue;
            }

            if (in_array($record['RefNo'], $deductbalance_ids)) {
                foreach ($insert_record as $rec) {
                    if ($rec['RefNo'] == $record['RefNo']) {
                        $record['game_code'] = $rec['game_code'];
                    }
                }
                $new_record[] = $record;
            }
        }

        return $new_record;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' =>  $row['RefNo'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $bet_details = json_decode($row['bet_details'], true);
        $remove = ['Guid', 'AccountId', 'Timestamp', 'SettleTime'];
        foreach ($remove as $val) {
            unset($bet_details[$val]);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => abs($row['real_betting_amount']),
                'result_amount' => abs($row['win_amount']) - abs($row['bet_amount']),
                'bet_for_cashback' => abs($row['bet_amount']),
                'real_betting_amount' => abs($row['bet_amount']),
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
                'round_number' => $row['RefNo'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => $bet_details,
            'extra' => $extra,
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

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name)
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($table_name, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($table_name, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function saveToResponseResult($success, $callMethod, $params, $response){
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag, $callMethod, json_encode($params), $response, 200, null, null);
    } 

    public function blockPlayer($playerName)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $success=true;
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array('success' => $success);
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function syncPlayerAccount($playerName, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }
}
/*end of file*/
