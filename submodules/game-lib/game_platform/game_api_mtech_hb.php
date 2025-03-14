<?php
require_once dirname(__FILE__) . '/game_api_common_mtech.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Gets platform code
 * * Login/logout to the website
 * * Create Player
 * * Update Player's info
 * * Delete Player
 * * Block/Unblock Player
 * * Deposit to Game
 * * Withdraw from Game
 * * Check Player's balance
 * * Check Game Records
 * * Computes Total Betting Amount
 * * Check Transaction
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Get BBIN Records
 * * Extract xml record
 * * Synchronize Game Records
 * * Check Player's Balance
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */

class Game_api_mtech_hb extends Game_api_common_mtech {

	const ORIGINAL_LOGS_TABLE_NAME = "mtech_hb_game_logs";

    const MD5_FIELDS_FOR_ORIGINAL = [
        'playerid',
        'brandid',
        'username',
        'gamekeyname',
        'dtstarted',
        'dtcompleted',
        'friendlygameinstanceid',
        'stake',
        'payout',
        'jackpotwin',
        'jackpotcontribution',
        'balanceafter'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'stake',
        'payout',
        'jackpotwin',
        'jackpotcontribution',
        'balanceafter'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'round',
        'bet_amount',
        'valid_bet',
        'after_balance',
        'result_amount',
        'bet_at',
        'game_code',
        'response_result_id',
        'external_uniqueid',
        'end_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'valid_bet',
        'after_balance',
        'result_amount',
    ];

	public function __construct() {
		parent::__construct();
	}

	public function getPlatformCode() {
		return MTECH_HB_API;
	}

	public function getMTechGameProviderId () {
        # 8 - HABANERO
		return $this->getSystemInfo('mtech_game_provider_id', 8);
	}

	public function syncOriginalGameLogsToDB($resultJsonArr, $extra = null) {
		$success = $extra["success"];
		$responseResultId = $extra["response_result_id"];
        $gameRecords = !empty($resultJsonArr['Params'])?$resultJsonArr['Params']:[];
        $total_pages = 0;

        $result = [
            'success' => $success,
            'response_record_count' => count($gameRecords),
            'total_pages' => 0,
            'total_items' => 0,
            'data_count' => 0,
        ];        

        if($success) {
            if (!empty($gameRecords)) {
                # change api response field to MTECH HB game logs column                
                $this->rebuildOriginalLogs($gameRecords, $responseResultId);

                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    self::ORIGINAL_LOGS_TABLE_NAME,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('MTECH HB after process >>>>>>>>> ', count($gameRecords), count($insertRows), count($updateRows));
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
        }

        return $result;
	}

    public function rebuildOriginalLogs(&$gameRecords, $responseResultId) {
        $data = array();

        foreach ($gameRecords as $key => $record) {
            $insertRecord = array();
            # Data from HB API
            $insertRecord['playerid'] = isset($record['PlayerId']) ? $record['PlayerId'] : NULL;
            $insertRecord['brandid'] = isset($record['BrandId']) ? $record['BrandId'] : NULL;
            $insertRecord['username'] = isset($record['Username']) ? $record['Username'] : NULL;
            $insertRecord['brandgameid'] = isset($record['BrandGameId']) ? $record['BrandGameId'] : NULL;
            $insertRecord['gamekeyname'] = isset($record['GameKeyName']) ? $record['GameKeyName'] : NULL;
            $insertRecord['gametypeid'] = isset($record['GameTypeId']) ? $record['GameTypeId'] : NULL;
            $insertRecord['dtstarted'] = isset($record['DtStarted']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['DtStarted']))) : NULL;
            $insertRecord['dtcompleted'] = isset($record['DtCompleted']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['DtCompleted']))) : NULL;
            $insertRecord['friendlygameinstanceid'] = isset($record['FriendlyGameInstanceId']) ? $record['FriendlyGameInstanceId'] : NULL;
            $insertRecord['gameinstanceid'] = isset($record['GameInstanceId']) ? $record['GameInstanceId'] : NULL;
            $insertRecord['stake'] = isset($record['Stake']) ? $record['Stake'] : NULL;
            $insertRecord['payout'] = isset($record['Payout']) ? $record['Payout'] : NULL;
            $insertRecord['jackpotwin'] = isset($record['JackpotWin']) ? $record['JackpotWin'] : NULL;
            $insertRecord['jackpotcontribution'] = isset($record['JackpotContribution']) ? $record['JackpotContribution'] : NULL;
            $insertRecord['currencycode'] = isset($record['CurrencyCode']) ? $record['CurrencyCode'] : NULL;
            $insertRecord['channeltypeid'] = isset($record['ChannelTypeId']) ? $record['ChannelTypeId'] : NULL;
            $insertRecord['balanceafter'] = isset($record['BalanceAfter']) ? $record['BalanceAfter'] : NULL;

            # extra info from SBE
            $insertRecord['external_uniqueid'] = $insertRecord['friendlygameinstanceid']; //add external_uniueid for og purposes
            $insertRecord['response_result_id'] = $responseResultId;
            array_push($data, $insertRecord);
        }

        $gameRecords = $data;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $key => $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='hb.updated_at >= ?
          AND hb.updated_at <= ?';
        if($use_bet_time){
            $sqlTime='hb.dtstarted >= ?
          AND hb.dtstarted <= ?';
        }

        $sql = <<<EOD
SELECT
    hb.id as sync_index,
    hb.username,
    hb.friendlygameinstanceid as round,
    hb.stake as bet_amount,
    hb.stake as valid_bet,
    hb.balanceafter as after_balance,
    hb.payout as result_amount,
    hb.dtstarted as start_at,
    hb.dtcompleted as end_at,
    hb.dtstarted as bet_at,
    hb.external_uniqueid,
    hb.gamekeyname as game_code,
    hb.gamekeyname as game_name,
    hb.updated_at,
    hb.response_result_id,
    hb.external_uniqueid,
    hb.md5_sum,
    game_provider_auth.player_id,
    gd.id AS game_description_id,
    gd.game_type_id
FROM
    mtech_hb_game_logs hb
    LEFT JOIN game_description as gd ON hb.gamekeyname = gd.external_game_id AND gd.game_platform_id = ?
    LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
    JOIN game_provider_auth ON hb.username = game_provider_auth.login_name 
    AND game_provider_auth.game_provider_id=?
    WHERE
    {$sqlTime}
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
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
                'bet_amount' => $row['valid_bet'],
                'result_amount' => ($row['result_amount'] - $row['bet_amount']),
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'], 
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
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
        $row['status'] = Game_logs::STATUS_SETTLED;
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

}

/*end of file*/
