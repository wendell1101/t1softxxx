<?php

trait habanero_game_syncing_utils
{

    /**
	 * overview : sync original game logs
	 *
	 * Rate Limiting - Reporting methods are Rate Limited to 25 requests per minute and 1 request per second by ReportType + (BrandId or PlayerUsername)
	 * where applicable. If you are rate limited you will receive an exception and you should retry the same method again using the same date range to ensure you do not miss data.
	 *
	 * Reporting Constraints - Some reports are restricted to 90 days of historical data.
	 *
	 * @param $token
	 * @return array
	 */
	public function syncOriginalGameLogs($token)
	{
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$syncId = parent::getValueFromSyncInfo($token, 'syncId');

		$startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
		$endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo));

		$startDate->modify($this->getDatetimeAdjust());
		$rows_count = 0;
		$self = $this;
		$this->currentApi = $this->getNameSyncGameRecords();

		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate,$endDate,'+60 minutes',function($from,$to,$step) use($syncId,$self,&$rows_count){

			$while = true;
			$success = false;
			$context = [
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'syncId' => $syncId
			];
            $cnt=0;

			while($while){

				$params = array(
					'BrandId' => $self->brandId,
					'APIKey' => $self->APIKey,
					'DtStartUTC' => $from->format("YmdHis"),
					'DtEndUTC' => $to->format("YmdHis"),
				);

				$this->CI->utils->debug_log(__METHOD__.' HB params >>>>>>>>',$params);

				$apiResult = $this->callApi($self->currentApi, $params, $context);

				$rows_count = isset($apiResult['data_count']) ? $apiResult['data_count'] : 0;
				$response_result_id = isset($apiResult['response_result_id']) ? $apiResult['response_result_id'] : null;

				# too many request, continue with same date param
				if(isset($apiResult['success']) && !$apiResult['success'] && isset($apiResult['is_too_many_request']) && $apiResult['is_too_many_request']){

                    if($this->sync_sleep > 0){

                        $this->CI->utils->debug_log(__METHOD__. ' HB is sleeping in seconds: ',$this->sync_sleep);

                        sleep($this->sync_sleep);
                        # since we sleep, need to reconnect in DB
                        $this->CI->db->_reset_select();
                        $this->CI->db->reconnect();
                        $this->CI->db->initialize();
                    }

					$params['DtStartUTC'] = $from->format("YmdHis");
					$params['DtEndUTC'] = $to->format("YmdHis");

					$this->CI->utils->error_log(__METHOD__. ' HB have error, too many request when it params is: ',$params,'response_result_id',$response_result_id);
                    $cnt++;
                    if($cnt<=$this->max_retry_sync_original){
                        continue;
                    }else{
                        $this->CI->utils->error_log('HB have error, too many request, more than max_retry_sync_original', $cnt, $this->max_retry_sync_original);
                        $while = false;
                        $success = true;
                    }
				}else{
					$while = false;
					$success = true;
				}
			}

			return $success;
		});

		$callResult = isset($result[0]) ? $result[0] : false;

		return [
			'success' => $callResult,
			'rows_count' => $rows_count
		];
	}

	/**
	 * Callback method of syncOriginalGameLogs
	 *
	 * @param array $params
	 * @return array
	*/
	public function processResultForSyncOriginalGameLogs($params)
	{
		$this->CI->load->model(['original_game_logs_model']);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult,null,$statusCode);
		$dataResult = [
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
			'is_too_many_request' => false
		];

        # too many request, re-try again with same params date
        $getErrorCodeTooManyRequest = $this->getErrorCodeTooManyRequest();
		if(!$success && $statusCode === $getErrorCodeTooManyRequest){
			return [
				true,
				[
					'is_too_many_request' => true
				]
			];
		}

		if($success && !empty($arrayResult)){
			$gameRecords = is_array($arrayResult) ? $arrayResult : [];
            $this->processGameRecords($gameRecords, $responseResultId);
            $getMd5FieldsForOriginal = $this->getMd5FieldsForOriginal();
            $getMd5FloatAmountFields = $this->getMd5FloatAmountFields();

			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
				$this->original_table,
				$gameRecords,
				'external_uniqueid',
				'external_uniqueid',
				$getMd5FieldsForOriginal,
				'md5_sum',
				'id',
				$getMd5FloatAmountFields
			);

			$insertRowsCount = is_array($insertRows) ? count($insertRows) : 0;
			$updateRowsCount = is_array($updateRows) ? count($updateRows) : 0;
			$gameRecordsCount = count($gameRecords);
			$dataResult['data_count'] = $gameRecordsCount;

			$this->CI->utils->debug_log(__METHOD__. ' after process available rows [insertRows,updateRows,gameRecords,HTTP status code]',[$insertRowsCount,$updateRowsCount,$gameRecordsCount,$statusCode]);

			if (!empty($insertRows)) {
				$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
		}
		return [$success,$dataResult];
    }

    public function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updatedAt'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
                    unset($record['id']);
                    $record['createdAt'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

	/**
	 * Process Records From API response
	 * @param array $gameRecords
	 * @param int $responseResultId
	 * @return void
	*/
	public function processGameRecords(&$gameRecords, $responseResultId)
	{
		if(!empty($gameRecords)){

			foreach($gameRecords as $index => $record){
                $username = isset($record['Username']) ? $record['Username'] : null;
                $playerId = $this->getPlayerIdInGameProviderAuth($username);
                $data['PlayerId'] = !empty($playerId) ? $playerId : '';
				$data['BrandId'] = isset($record['BrandId']) ? $record['BrandId'] : null;
				$data['Username'] = $username;
				$data['BrandGameId'] = isset($record['BrandGameId']) ? $record['BrandGameId'] : null;
				$data['GameKeyName'] = isset($record['GameKeyName']) ? $record['GameKeyName'] : null;
				null;
				$data['GameTypeId'] = isset($record['GameTypeId']) ? $record['GameTypeId'] : null;
				$data['DtStarted'] = isset($record['DtStarted']) ? $this->gameTimeToServerTime($record['DtStarted']) : null;
				$data['DtCompleted'] = isset($record['DtCompleted']) ? $this->gameTimeToServerTime($record['DtCompleted']) : null;
				$data['FriendlyGameInstanceId'] = isset($record['FriendlyGameInstanceId']) ? $record['FriendlyGameInstanceId'] : null;
				$data['GameInstanceId'] = isset($record['GameInstanceId']) ? $record['GameInstanceId'] : null;
				$data['GameStateId'] = isset($record['GameStateId']) ? $record['GameStateId'] : null;
				$data['Stake'] = isset($record['Stake']) ? $record['Stake'] : null;
				$data['Payout'] = isset($record['Payout']) ? $record['Payout'] : null;
				$data['JackpotWin'] = isset($record['JackpotWin']) ? $record['JackpotWin'] : null;
				$data['JackpotContribution'] = isset($record['JackpotContribution']) ? $record['JackpotContribution'] : null;
				$data['CurrencyCode'] = isset($record['CurrencyCode']) ? $record['CurrencyCode'] : null;
				$data['ChannelTypeId'] = isset($record['ChannelTypeId']) ? $record['ChannelTypeId'] : null;
				$data['BalanceAfter'] = isset($record['BalanceAfter']) ? $record['BalanceAfter'] : null;
				$data['BonusStake'] = isset($record['BonusStake']) ? $record['BonusStake'] : null;
				$data['BonusPayout'] = isset($record['BonusPayout']) ? $record['BonusPayout'] : null;
				$data['BonusToReal'] = (isset($record['BonusToReal']) && !empty($record['BonusToReal'])) ? $record['BonusToReal'] : null;
				$data['BonusCoupon'] = isset($record['BonusCoupon']) ? $record['BonusCoupon'] : null;
				$data['BonusToRealCoupon'] = isset($record['BonusCoupon']) ? $record['BonusCoupon'] : null;
				$data['external_uniqueid'] = isset($record['FriendlyGameInstanceId']) ? $record['FriendlyGameInstanceId'] : null;
				$data['response_result_id'] = $responseResultId;

				$gameRecords[$index] = $data;
                unset($data);
			}

		}
    }

    /**
     * Merge Game Logs from Original Game Logs Table
    */
    public function syncMergeToGameLogs($token)
    {
       $enabled_game_logs_unsettle = true;
       return $this->commonSyncMergeToGameLogs($token,
        $this,
        [$this,'queryOriginalGameLogs'],
        [$this,'makeParamsForInsertOrUpdateGameLogsRow'],
        [$this, 'preprocessOriginalRowForGameLogs'],
        $enabled_game_logs_unsettle
       );
    }

    /**
     * Query Original Game Logs for Merging
     *
     * @param string $dateFrom where the date start for sync original
     * @param string $dateTo where the date end
     *
     * @return array
    */
    public function queryOriginalGameLogs($dateFrom,$dateTo,$use_bet_time)
    {

        $sqlTime = 'original.DtCompleted >= ? AND original.DtCompleted <= ?';

        if($use_bet_time){
            $sqlTime = 'original.DtStarted >= ? AND original.DtStarted <= ?';
        }

        $sql = <<<EOD
            SELECT
                original.id AS sync_index,
                original.id AS id,
                original.Username,
                original.BrandGameId,
                original.external_uniqueid,
                original.FriendlyGameInstanceId,
                original.GameStateId,
                original.DtCompleted AS completed_at,
                original.DtStarted AS started_at,
                original.GameKeyName AS game_code,
                original.Payout AS result_amount,
                IFNULL(`original`.`BonusToReal`,0) AS bonus_amount,
                original.JackpotWin,
                original.Stake AS bet_amount,
                original.response_result_id,
                original.BalanceAfter AS after_balance,
                original.md5_sum,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game,
                gd.game_type_id,
                gt.game_type
            FROM {$this->original_table} as original
            LEFT JOIN game_description as gd ON original.GameKeyName = gd.external_game_id AND
            gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id AND
            gd.game_platform_id = ?
            JOIN game_provider_auth ON original.Username = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id = ?
            WHERE
            {$sqlTime}
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    }

    /**
     * @param array $row
     * @return array the records to save in game_logs
    */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' => $row['external_uniqueid'],
            'trans_amount' => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount'])
        ];
        $getMd5FieldsForMerge = $this->getMd5FieldsForMerge();
        $getMd5FloatAmountFieldsForMerge = $this->getMd5FloatAmountFieldsForMerge();
        $resultAmount = $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount'] - $row['bet_amount']);

        if($this->enable_freespin_in_merging){
            $resultAmount = $this->gameAmountToDBGameLogsTruncateNumber(($row['result_amount']  + $row['bonus_amount']) - $row['bet_amount']);
        }

        $betAmount = $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']);
        $afterBalance = $this->gameAmountToDBGameLogsTruncateNumber($row['after_balance']);

        if(empty($row['md5_sum'])){
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row,
                $getMd5FieldsForMerge,
                $getMd5FloatAmountFieldsForMerge
            );
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['Username']
            ],
            'amount_info' => [
                'bet_amount' => $betAmount,
                'result_amount' => $resultAmount,
                'bet_for_cashback' => $betAmount,
                'real_betting_amount' => $betAmount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $afterBalance
            ],
            'date_info' => [
                'start_at' => $row['started_at'],
                'end_at' => $row['completed_at'],
                'bet_at' => $row['started_at'],
                'updated_at' => $this->CI->utils->getNowForMysql()
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null # BET_TYPE_MULTI_BET or BET_TYPE_SINGLE_BET
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            // from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null
        ];
    }

    /**
     * Prepare Original rows, include process unknown game, pack bet details, convert game status
     *
     * @param array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {

        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        # we process unknown game here
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
            $row['game_description_id'] = $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $is_game_transaction = ($row['game_code'] == 'game transaction') ? false : true;

        $bet_details = [
            'roundId' => $row['external_uniqueid'],
            'gameUsername' => $row['Username'],
            'isBet' => $is_game_transaction
        ];

        $row['bet_details'] = $bet_details;
        $GameStateId = isset($row['GameStateId']) ? $row['GameStateId'] : null;
        $row['status'] = $this->processGameStatus($GameStateId);
    }

    /**
     * 3 – Completed, 4 – Voided (Insufficient funds), 11 - Expired
     * @return int
    */
    public function processGameStatus($status)
    {
        $this->CI->load->model(array('game_logs'));
        switch($status){
            case 3:
                $status  = Game_logs::STATUS_SETTLED;
            break;
            case 4:
                $status  = Game_logs::STATUS_CANCELLED;
            break;
            case 11:
                // $status  = Game_logs::STATUS_PENDING;
                // OGP-18011 - as per game provider, even record is expired, it's included in the balance sheet computation in BO
                $status  = Game_logs::STATUS_SETTLED;
            break;
            default:
                $status  = Game_logs::STATUS_SETTLED;
            break;
        }
        return $status;
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    public function getGameDescriptionInfo($row, $unknownGame)
    {
        $getTagCodeUnknownGame = $this->getTagCodeUnknownGame();
        $game_description_id = null;
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : $getTagCodeUnknownGame;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
   }

   public function getTagCodeUnknownGame()
   {
       return 'unknown';
   }

    /**
     * @return string
    */
    public function getNameSyncGameRecords()
    {
        return 'syncGameRecords';
    }

    /**
     * @return string
    */
    public function getErrorCodeTooManyRequest()
    {
        return 429;
    }

    /**
     * Fields in original table, we want to detect changes for update in fields
     *
	 * PlayerId - Internal Habanero GUID for player
	 * BrandId - NOTE: Brandid is populated for
	 *  GetGroupCompletedGameResultsV2 method
	 * Username - Player Username
	 * BrandGameId - Game BrandGame Id
	 * GameKeyName - Game Indentifier
	 * GameTypeId - See gametype addendum
	 * DtStarted - Start date of game
	 * DtCompleted - Completed date of game round
	 * FriendlyGameInstanceId - Unique Game Id as an integer
	 * GameInstanceId - Unique Game Id as a GUID
	 * GameStateId - 3 – Completed, 4 – Voided (Insufficient funds), 11 - Expired
	 * * 11 - Expired - It means if a player left an open round and did not finish that game round within the default day of 15 days. The game round will be automatically expired.
	 * Stake - Real money stake amount
	 * Payout - Real money payout amount
	 * JackpotWin- Portion of the Payout which was from a Jackpot Win
	 * JackpotContribution - Jackpot contribution amount
	 * CurrencyCode - Currency code of Player
	 * ChannelTypeId - See addendum of Channels
	 * BalanceAfter - Real balance after game completed
	 * BonusStake - Bonus Stake amount (if the game used bonus)
	 * BonusPayout - Bonus Payout amount (if the game used bonus)
	 * BonusToReal - Converted amount from Bonus to Real Balance (IMPORTANT FOR
	 * BONUSING!) Identifies how much money was converted from Free
	 * Spin Bonus to Real Balance. It is not shown elsewhere
	 * BonusCoupon - Coupon Code used for the Bonus (if the game used bonus)
	 *
     * @return array
    */
    public function getMd5FieldsForOriginal()
    {

        if($this->use_simplified_md5){
            return [
                'PlayerId',
                'BrandId',
                'Username',
                'BrandGameId',
                'DtCompleted',
                'Stake',
                'Payout',
                'JackpotWin',
            ];
        }

        return [
            'PlayerId',
            'BrandId',
            'Username',
            'BrandGameId',
            'GameKeyName',
            'GameTypeId',
            'DtStarted',
            'DtCompleted',
            'FriendlyGameInstanceId',
            'GameInstanceId',
            'GameStateId',
            'Stake',
            'Payout',
            'JackpotWin',
            'JackpotContribution',
            'CurrencyCode',
            'ChannelTypeId',
            'BalanceAfter',
            'BonusStake',
            'BonusPayout',
            'BonusToReal',
            'BonusCoupon'
        ];
    }

    /**
    * Values of these fields will be rounded when calculating MD5
    * @return array
    */
    public function getMd5FloatAmountFields()
    {
        return [
            'Stake',
            'Payout',
            'JackpotWin',
            'JackpotContribution',
            'BalanceAfter',
            'BonusStake',
            'BonusPayout',
            'BonusToReal'
        ];
    }

    /**
     * Fields in game_logs table, we want to detect changes for merge, and when original.md5_sum is empty
     * @return array
    */
    public function getMd5FieldsForMerge()
    {
        return [
            'external_uniqueid',
            'Username',
            'GameStateId',
            'BrandGameId',
            'game_code',
            'game',
            'completed_at',
            'started_at',
            'FriendlyGameInstanceId',
            'bet_amount',
            'bonus_amount',
            'result_amount',
            'after_balance'
        ];
    }

    /**
     * @return array
    */
    public function getMd5FloatAmountFieldsForMerge()
    {
        return [
            'bet_amount',
            'bonus_amount',
            'result_amount',
            'after_balance'
        ];
    }

    /**
     * overview : sync CC event winners
     *
     *
     * @param $token
     * @return array
     */
    public function getBrandCCWinners($token)
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = $startDate->format('YmdHis');
        $endDate   = $endDate->format('YmdHis');

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetBrandCCWinners',
        ];

        $params = array(
            'BrandId' => $this->brandId,
            'APIKey' => $this->APIKey,
            'DtStartUTC' => $startDate,
            'DtEndUTC' => $endDate,
        );

        return  $this->callApi('GetBrandCCWinners', $params, $context);
        
    }

    public function processResultForGetBrandCCWinners($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult,null,$statusCode);
        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
        );
        if($success){
            if(!empty($arrayResult)){
                $this->processEventRecords($arrayResult, $responseResultId);
                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_table,
                    $arrayResult,
                    'external_uniqueid',
                    'external_uniqueid',
                    ['DtStarted','DtCompleted'],
                    'md5_sum',
                    'id',
                    ['Payout']
                );

                $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($arrayResult), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

                $dataResult['data_count'] = count($arrayResult);
                if (!empty($insertRows)) {
                    $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);
            }
        }
        return array($success, $dataResult);
    }

    public function processEventRecords(&$arrayResult, $responseResultId) {
        
        if(!empty($arrayResult)){
            foreach($arrayResult as $index => $result) {
                $username = isset($result['Username']) ? $result['Username'] : null;
                $playerId = $this->getPlayerIdInGameProviderAuth($username);
                if(empty($playerId)){
                    unset($arrayResult[$index]);
                    continue;
                }
                $data['PlayerId'] = !empty($playerId) ? $playerId : '';
                $data['Username'] = $username;
                $data['GameKeyName'] = isset($result['TournamentInfo']['TournamentEventTypeId']) ? $result['TournamentInfo']['TournamentEventTypeId'] : null;
                $data['DtStarted'] = isset($result['DtAwarded']) ? $this->gameTimeToServerTime($result['DtAwarded']) : null;
                $data['DtCompleted'] = isset($result['DtAwarded']) ? $this->gameTimeToServerTime($result['DtAwarded']) : null;
                $data['Payout'] = isset($result['AmountAwarded']) ? $result['AmountAwarded'] : null;
                $data['CurrencyCode'] = isset($result['PlayerCurrency']) ? $result['PlayerCurrency'] : null;
                $data['external_uniqueid'] = isset($result['WinnerId']) ? $result['WinnerId'] : null;
                $data['response_result_id'] = $responseResultId;
                $arrayResult[$index] = $data;
                unset($data);
            }
        }
    }

    public function syncLostAndFound($token) {
        if($this->allow_sync_BrandCCWinners){
            return $this->getBrandCCWinners($token);
        }
        return $this->returnUnimplemented();
    }
}