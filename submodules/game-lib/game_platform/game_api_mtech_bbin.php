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

class Game_api_mtech_bbin extends Game_api_common_mtech {
	const GAME_KINDS = [
        "sports" => 109, // as per game provider, game kind id 1 is no longer used. use gamekind=109 to get datas of BB sport.
        "live" => 3,
        "slots" => 5,
        "lottery" => 12,
        "newbbsports" => 20,
        "fishhunter" => 21,
        "fishmaster" => 22,
        "bbtips" => 99,
        "xbblive" => 75
        // "jackpot" => "JP",
    ];

	const BET_DETAIL_GAME_CODE = array(
		"bac"=>["3001","3017"],
		"mahjong_tiles"=>"3002",
		"dragon_tiger"=>"3003",
		"three_face"=>"3005",
		"wenzhou_pai_gow"=>"3006",
		"roulette"=>"3007",
		"sicbo"=>"3008",
		"texas_holdem"=>"3010",
		"se_die"=>"3011",
		"bull_bull"=>"3012",
		"unlimited_blackjack"=>"3014",
		"fan_tan"=>"3015",
	);

	const ORIGINAL_LOGS_TABLE_NAME = "mtech_bbin_game_logs";

    const MD5_FIELDS_FOR_ORIGINAL = [
        'username',
        'wagers_id',
        'wagers_date',
        'game_type',
        'exchange_rate',
        'result',
        'bet_amount',
        'payoff',
        'currency',
        'commissionable',
        'origin',
        'uptime',
        'order_date',
        'payout_time',
        'account_date',
        'serial_id',
        'modified_date',
        'round_no',
        'wager_detail',
        'game_code',
        'card',
        'result_type',
        'is_paid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'exchange_rate',
        'bet_amount',
        'payoff',
        'commissionable',

    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'wagers_id',
        'wagers_date',
        'game_type',
        'exchange_rate',
        'result',
        'bet_amount',
        'payoff',
        'currency',
        'commissionable',
        'origin',
        'uptime',
        'order_date',
        'payout_time',
        'account_date',
        'serial_id',
        'modified_date',
        'round_no',
        'wager_detail',
        'game_code',
        'card',
        'result_type',
        'is_paid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'exchange_rate',
        'bet_amount',
        'payoff',
        'commissionable',
    ];

	public function __construct() {
		parent::__construct();
	}

	public function getPlatformCode() {
		return MTECH_BBIN_API;
	}

	public function getMTechGameProviderId () {
		return $this->getSystemInfo('mtech_game_provider_id', 11);
	}

	public function syncOriginalGameLogsToDB($resultJsonArr, $extra = null) {
		$success = $extra["success"];
		$gameKind = $extra["game_kind"];
		$responseResultId = $extra["response_result_id"];

		// $record_count = 0;
        $data = array();
        $total_pages = 0;
        $result = [
            'response_record_count' => 0,
            'total_pages' => 0,
            'total_items' => 0,
            'data_count' => 0,
        ];

        if($success) {
            $result['total_pages']  = $resultJsonArr['Params']['pagination']['TotalPage'];
            $result['total_items']  = $resultJsonArr['Params']['pagination']['TotalNumber'];

            if (!empty($resultJsonArr['Params']['data'])) {
                $data = $resultJsonArr['Params']['data'];
                $result['response_record_count'] = count($data);

                # change api response field to MTECH BBIN game logs column
                $this->rebuildOriginalLogs($data, $responseResultId, $gameKind);

                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    self::ORIGINAL_LOGS_TABLE_NAME,
                    $data,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('MTECH BBIN after process >>>>>>>>> ', count($data), count($insertRows), count($updateRows));
                unset($gameRecords);
                unset($data);

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

    public function rebuildOriginalLogs(&$records, $responseResultId, $gameKind) {
        $data = array();

        foreach ($records as $key => $record) {
            if (!isset($record["UserName"])) {   continue;   }

            $logs = [
                'game_kind'         => $gameKind,
                'username'          => !isset($record["UserName"]) ? null : $record["UserName"],
                'wagers_id'         => !isset($record["WagersID"]) ? null : $record["WagersID"],
                'wagers_date'       => !isset($record["WagersDate"]) ? null : $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record["WagersDate"]))),
                'game_type'         => !isset($record["GameType"]) ? null : $record["GameType"],
                'exchange_rate'     => !isset($record["ExchangeRate"]) ? null : $record["ExchangeRate"],
                'result'            => !isset($record["Result"]) ? null : $record["Result"],
                'bet_amount'        => !isset($record["BetAmount"]) ? null : $this->gameAmountToDB($record["BetAmount"]),
                'payoff'            => !isset($record["Payoff"]) ? null : $this->gameAmountToDB($record["Payoff"]),
                'currency'          => !isset($record["Currency"]) ? null : $record["Currency"],
                'commissionable'    => !isset($record["Commissionable"]) ? null : $record["Commissionable"],
                'origin'            => !isset($record["Origin"]) ? null : $record["Origin"],
                'uptime'            => !isset($record["UPTIME"]) ? null :$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record["UPTIME"]))),
                'order_date'        => !isset($record["OrderDate"]) ? null : $this->gameTimeToServerTime(date('Y-m-d', strtotime($record["OrderDate"]))),
                'payout_time'       => !isset($record["PayoutTime"]) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record["WagersDate"]))) : $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record["PayoutTime"]))),
                'account_date'      => !isset($record["AccountDate"]) ? null : $this->gameTimeToServerTime(date('Y-m-d', strtotime($record["AccountDate"]))),
                'serial_id'         => !isset($record["SerialID"]) ? null : $record["SerialID"],
                'modified_date'     => !isset($record["ModifiedDate"]) ? null : $record["ModifiedDate"],
                'round_no'          => !isset($record["RoundNo"]) ? null : $record["RoundNo"],
                'wager_detail'      => !isset($record["WagerDetail"]) ? null : $record["WagerDetail"],
                'game_code'         => !isset($record["GameCode"]) ? null : $record["GameCode"],
                'card'              => !isset($record["Card"]) ? null : $record["Card"],
                'result_type'       => !isset($record["ResultType"]) ? null : $record["ResultType"],
                'is_paid'      		=> !isset($record["IsPaid"]) ? null : $record["IsPaid"],

                'external_uniqueid'     => $record['WagersID'],
                'response_result_id'    => $responseResultId,
            ];
            array_push($data, $logs);
        }

        $records = $data;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
            	$record["last_sync_time"] = $this->CI->utils->getNowForMysql();
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
        $enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime =  'bbin.payout_time >= ? and bbin.payout_time <= ?';

        if ($use_bet_time) {
            $sqlTime =  'bbin.wagers_date >= ? and bbin.wagers_date <= ?';
        }

        $sql = <<<EOD
SELECT
    bbin.id as sync_index,
    bbin.wagers_id,
    bbin.external_uniqueid,
    bbin.game_kind,
    bbin.username AS player_username,
    bbin.wagers_date,
    bbin.wagers_date as bet_time,
    bbin.game_type,
    bbin.exchange_rate,
    bbin.result,
    bbin.bet_amount,
    bbin.bet_amount as real_bet_amount,
    bbin.payoff,
    bbin.currency,
    bbin.commissionable,
    bbin.origin,
    bbin.uptime,
    bbin.order_date,
    bbin.payout_time,
    bbin.account_date,
    bbin.serial_id,
    bbin.modified_date,
    bbin.round_no,
    bbin.wager_detail,
    bbin.game_code,
    bbin.card,
    bbin.result_type,
    bbin.is_paid,
    bbin.md5_sum,
    bbin.last_sync_time,
    bbin.response_result_id,
    bbin.game_type as game_code,
    bbin.game_type as game,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_type_id
FROM
    mtech_bbin_game_logs bbin
    JOIN game_provider_auth
        ON bbin.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
    LEFT JOIN game_description
        ON game_description.external_game_id = bbin.game_type
        AND game_description.void_bet != 1
        AND game_description.game_platform_id = ?
WHERE
    {$sqlTime}
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['payoff'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['real_bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null
            ],
            'date_info' => [
                'start_at'              => $row['bet_time'],
                'end_at'                => $row['bet_time'],
                'bet_at'                => $row['bet_time'],
                'updated_at'            => $row['last_sync_time']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['serial_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if ($row["game_kind"] == self::GAME_KINDS["sports"]) {
        	$row['status'] = $this->getSportstGameRecordsStatus($row["result"]);
        } else if ($row["game_kind"] == self::GAME_KINDS["lottery"]) {
        	if (strtoupper($row["result"]) == "N2") {
        		$row['status'] = Game_logs::STATUS_CANCELLED;
        	} else {
        		$row['status'] = strtoupper($row["is_paid"]) == "Y" ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;
        	}
        } else if ($row["game_kind"] == self::GAME_KINDS["live"]) {
        	$row['status'] = $row["is_paid"] == -1 ? Game_logs::STATUS_CANCELLED : Game_logs::STATUS_SETTLED;
        } else if ($row["game_kind"] == self::GAME_KINDS["slots"]) {
        	$row['status'] = $this->getSlotsGameRecordsStatus($row["result"]);
        } else if ($row["game_kind"] == self::GAME_KINDS["newbbsports"] || $row["game_kind"] == self::NEW_VERSION_BBSPORTS) {
        	$row['status'] = $this->getNewBBSportstGameRecordsStatus($row["result"]);
        } else if ($row["game_kind"] == self::GAME_KINDS["fishhunter"] || $row["game_kind"] == self::GAME_KINDS["fishmaster"] || $row["game_kind"] == self::NEW_VERSION_FISHHUNTER || $row["game_kind"] == self::NEW_VERSION_FISHMASTER) {
        	$row['status'] = $this->getFishingRecordsStatus($row["result"]);
        } else {
        	$row['status'] = Game_logs::STATUS_SETTLED;
        }

        $bet_details = $this->processGameBetDetail($row['card'], $row['wager_detail']);
        $row['bet_details'] = $bet_details;

    }

    private function getSportstGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtoupper($status);
        $gameLogsStatus = Game_logs::STATUS_PENDING;

    	switch ($status) {
    		case 'C':		# Win
    			$gameLogsStatus = Game_logs::STATUS_CANCELLED;
	            break;
	        case 'W':		# Win
	        case 'L':		# Lose
	        case 'LW':		# Win Half
	        case 'LL':		# Lose Half
	        case '0':		# Tie
	            $gameLogsStatus = Game_logs::STATUS_SETTLED;
	            break;
        }

        return $gameLogsStatus;
    }

    private function getNewBBSportstGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);
        $gameLogsStatus = Game_logs::STATUS_PENDING;

    	switch ($status) {
	        case -1:	# invalid
	        	$gameLogsStatus = Game_logs::STATUS_REJECTED;
	            break;
	        case 5:	# invalid
	        	$gameLogsStatus = Game_logs::STATUS_CANCELLED;
	            break;
	        case 2:		# tie/void
	        case 4:		# win
	        case 4:		# lose
	            $gameLogsStatus = Game_logs::STATUS_SETTLED;
	            break;
        }

        return $gameLogsStatus;
    }

    private function getSlotsGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);
        $gameLogsStatus = Game_logs::STATUS_PENDING;

    	switch ($status) {
    		case -1:	# Cancel
	            $gameLogsStatus = Game_logs::STATUS_CANCELLED;
	        case 1:		# Win
	        case 200:	# Lose
            case 'w':   # Win
            case 'l':   # Lose
	            $gameLogsStatus = Game_logs::STATUS_SETTLED;
	            break;
        }

        return $gameLogsStatus;
    }

    private function getFishingRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtoupper($status);
        $gameLogsStatus = Game_logs::STATUS_PENDING;

    	switch ($status) {
    		case 'C':	# Cancel
	            $gameLogsStatus = Game_logs::STATUS_CANCELLED;
	        case "W":		# Win
	        case "L":	# Lose
	            $gameLogsStatus = Game_logs::STATUS_SETTLED;
	            break;
        }

        return $gameLogsStatus;
    }

    public function processGameBetDetail($card, $wager_detail){
            return $bet_details = array(
                "Card" => $card,
                "Wager Detail" => $wager_detail,
            );

    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $externalGameId = $row['game'];
        $extra = array('game_code' => $row['game_code']);
        return $this->processUnknownGame(
            $row['game_description_id'], $row['game_type_id'],
            $row['game'], $row['game_type'], $externalGameId, $extra,
            $unknownGame);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $sign = $this->md5ToUpper($this->operator_code."&".$password."&".$gameUsername."&".$this->secret_key);

        $params = array(
            'gameprovider' =>  $this->getMTechGameProviderId(),
            'command' =>  "GET_BALANCE",
            'sign' =>  $sign,
            'params' =>  [
                'username' =>  $gameUsername,
                'operatorcode' =>  $this->operator_code,
                'password' =>  $password,
            ]
        );

        if(!empty($this->extraparameter)){
            $params['params']['extraparameter'] = $this->extraparameter;
        }

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    /**
     * processResultForQueryPlayerBalance
     * reset to 0 if <1
     * @param  array $params
     * @return
     */
    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if ($success) {
            // $result['balance'] = $resultJsonArr["Params"]["Balance"];
            $balance = $resultJsonArr["Params"]["Balance"];
            $result['balance'] = floatval($this->gameAmountToDB($balance));
            //reset to 0 if <1
            if($result['balance']<1){
                $result['balance']=0;
            }
        }

        return array($success, $result);
    }

}

/*end of file*/
