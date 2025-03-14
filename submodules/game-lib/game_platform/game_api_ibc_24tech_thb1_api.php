<?php
require_once dirname(__FILE__) . '/game_api_common_24tech_game.php';

class Game_api_ibc_24tech_thb1_api extends Game_api_common_24tech_game {
	const CURRENCY_TYPE = "THB";
	const IBC24TECH_ORIGINAL_GAMELOGS_TABLE = "ibc24tech_idr1_game_logs";

	const SKINCOLOR_TYPES = [
		'blue1' => 'bl001',
		'blue2' => 'bl002',
		'blue3' => 'bl003',
		'red1' => 'rd001',
		'red2' => 'rd002',
		'red3' => 'rd003',
		'green1' => 'gn001',
		'green2' => 'gn002',
		'orange1' => 'or001',
		'purple1' => 'pp001',
	];

	const COUNTRY_ODD_TYPES = [
		'1' => 'Malay Odds',
		'2' => 'HongKong Odds',
		'3' => 'Decimal Odds',
		'4' => 'Indo Odds',
		'5' => 'American Odds'
	];

	const IBC_GAME_TYPES = [
		'0' => 'Sports',
		'1' => 'NumberGame',
		'2' => '2 BA',
		'3' => 'Virtual Sports 1',
		'4' => 'Cash-in-time',
		'5' => 'Virtual Sports 2',
		'6' => 'Keno'
	];

	const BET_TYPES = [
		'1' => 'Handicap',
		'2' => 'Over/Under',
		'3' => 'In-play Handicap',
		'4' => 'In-play O/U',
		'5' => 'O/E',
		'6' => '1X2',
		'7' => '1X2 P.',
		'8' => 'Mix Parlay',
		'9' => 'C.Score',
		'10' => 'T/G',
		'11' => 'HF/FT',
		'12' => '1st Half Handicap',
		'13' => '1st Half O/U',
		'14' => 'Early Handicap',
		'15' => 'Early O/U',
		'16' => 'Correct score P.',
		'17' => '1st Half P',
		'28' => 'In-Play O/E',
	];

	const GAMELOG_RESULT_KEY_ID = [
		'ballid' => 0,
		'balltime' => 1,
		'curpl' => 2,
		'isbk' => 3,
		'iscancel' => 4,
		'isjs' => 5,
		'win' => 6,
		'lose' => 7,
		'moneyrate' => 8,
		'orderid' => 9,
		'result' => 10,
		'sportid' => 11,
		'truewin' => 12,
		'tzip' => 13,
		'tzmoney' => 14,
		'tztype' => 15,
		'updatetime' => 16,
		'username' => 17,
		'content' => 18,
		'vendorid' => 19,
		'validamount' => 20,
		'abc' => 21
	];

	public function getPlatformCode(){
		return IBC_24TECH_THB_B1_API;
    }

    public function __construct(){
    	parent::__construct();
    	$this->original_gamelogs_table = self::IBC24TECH_ORIGINAL_GAMELOGS_TABLE;
    	$this->currency_type = self::CURRENCY_TYPE;
    	$this->platformname = self::PLATFORMNAME_TYPES['ibc'];
    	$this->ibc_24tech_gametype = self::THE24TECH_GAME_TYPES['ibcsports_or_bbin_old_sports'];
    }

	/*
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  player_center/goto_24tech_game/<game_platform_id>/<lang>/<is_mobile>/<skinid>
	 *
	 *  Ex. Web version with default skin color:
	 *  player_center/goto_24tech_game/2111/th
	 *
	 *  Ex. Launch mobile version with default skin color:
	 *  player_center/goto_24tech_game/2111/th/true
	 *
	 * 	Ex. Web version with defining skin color:
	 *  player_center/goto_24tech_game/2111/th/null/rd003
	 *
	 * 	Ex. Mobile version with defining skin color:
	 *  player_center/goto_24tech_game/2111/th/true/rd003
	 *
	 *  Sample Raw URL:
	 *  http://api.tcy789.com/direct.aspx?params=YWdlbnQ9YmlnYmV0OTk5JHVzZXJuYW1lPW9zZGVtbzEkcGFzc3dvcmQ9MTIzNDU2JGRvbWFpbj0kZ2FtZXR5cGU9MiRza2luY29sb3I9cmQwMDEkaWZyYW1lPTAkcGxhdGZvcm1uYW1lPWliYyRsYW5nPWVuJG1ldGhvZD10ZyRvZGR0eXBlPUE%3D&key=23cf03dbabb19e51578af31ff22e863b
	 *
	 */
	public function queryForwardGame($playerName, $extra = null)
	{
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);

		#IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL ELSE GET LANG FROM PLAYER DETAILS
		$lang = isset($extra['language']) ? $this->processPlayerLanguageForParams($extra['language']) : $this->processPlayerLanguageForParams($this->getPlayerDetails($playerId)->language);

		#IDENTIFY IF SKINCOLOR IS INVOKED IN GAME URL ELSE USE DEFAULT SKIN IN SYSTEM INFO PARAM
		$skincolor = isset($extra['extra']['skincolor']) ? $extra['extra']['skincolor'] :null;

		//for iframe param, default value 0,if use iframe set 1,https use 2,and iframe use3
		$data = [
				 'agent' => $this->agent,
				 'username' => $gameUsername,
				 'password' => $password,
				 'domain' => $this->getSystemInfo('domain'),
				 'iframe' => $this->getSystemInfo('iframe',0),
				 'lang' => $lang,
				 'platformname' => $this->platformname,
				 'gametype' => $this->ibc_24tech_gametype,
				 'skincolor' => $skincolor ?: $this->getSystemInfo('skincolor',self::SKINCOLOR_TYPES['blue1']),
				 'method' => self::API_METHOD_MAP[self::API_queryForwardGame],
				];

		#Commented (oddtype and gamekind) param, because ibc sportsbook dont need this param,
		#but may use this as reference when we integrate other platform

		#IDENTIFY IF ODDTYPE IS AVAILABLE IN PARAMS
		// if(isset($extra['oddtype'])){
		// 	$data['oddtype'] = $extra['oddtype'];
		// }

		#IDENTIFY IF GAMEKIND IS AVAILABLE IN PARAMS
		// if(isset($extra['game_kind'])){
		// 	$data['game_kind'] = $extra['game_kind'];
		// }

		#IDENTIFY MOBILE GAME
        if(isset($extra['is_mobile']) && $extra['is_mobile']){
        	if($this->ibc_24tech_gametype == self::THE24TECH_GAME_TYPES['ibcsports_or_bbin_old_sports']){
				$data['gametype'] = self::THE24TECH_GAME_TYPES['ibc_mobile'];
			}
        }
		list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);
		$params = [
				   'params' => $encodedParams,
				   'key' => $key
				  ];
		$this->debugLogParams('Launch Game',$data,$params);
		$url = $this->api_url."/direct.aspx?".http_build_query($params);
		return ['success' => true,'url' => $url];
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		# get the last version key in db
		# check if last version key exist,
		# if null it means it needs to call the first record which is 0 version key
		$vendor_id = $this->CI->external_system->getLastSyncIdByGamePlatform($this->getPlatformCode()) ?: 0;
		$this->syncOriginalGameLogsByVendorId($vendor_id);

		# get the oldest vendor id of unsettle record in orig game logs, if empty then skip sync
		$record = $this->queryTheOldestVendorIdOfUnsettleOrigGameLogs();
		if(!empty($record)){
			$vendor_id = $record[0]['vendorid']-1;
			$this->syncOriginalGameLogsByVendorId($vendor_id);
		}else{
			$this->debugLogParams(self::API_syncGameRecords,['message'=>'no unsettle game logs to sync'],[]);
		}
		return ['success'=>true];
	}

	private function syncOriginalGameLogsByVendorId($vendor_id)
	{
		$apiType = self::API_syncGameRecords;
		$context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
        ];

		$done = false;
		$result = ["success" => false];

		// while (!$done) {
			$data = [
					 'agent' => $this->agent,
					 'vendorid' => $vendor_id,
					 'method' => self::API_METHOD_MAP[$apiType],
					];
			list($encodedParams,$key) = $this->encodeParamsToBase64Utf8($data);
			$params = [
					   'params' => $encodedParams,
					   'key' => $key
					  ];
			$this->debugLogParams($apiType,$data,$params);

			$resultData = $this->callApi(self::API_syncGameRecords, $params, $context);
			$result = ["success" => $resultData['success']];

			//error or done
			// $done = $resultData['success'];
			if(!$resultData['success']){
				$this->CI->utils->error_log('wrong result', $resultData);
				$result['error_message']=@$resultData['error_message'];
			}
		// }
		return $result;
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = $this->convertXmlToArray($resultXml);
		$success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);

		$result = ['data_count' => 0];
		$gameRecords = isset($resultArr['Data'])?$resultArr['Data']:[];

		if($success&&!empty($gameRecords))
		{
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

            $last_version_id = end($gameRecords)['vendorid'];
            $this->CI->external_system->setLastSyncId($this->getPlatformCode(), $last_version_id);

            $this->CI->load->model('original_game_logs_model');
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

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId,'lastVersionKey'=>$last_version_id]);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId,'lastVersionKey'=>$last_version_id]);
            }
            unset($updateRows);
		}

		return array($success, $result);
	}

	private function queryTheOldestVendorIdOfUnsettleOrigGameLogs()
	{
        $sql = <<<EOD
			SELECT
				ibc.vendorid
			FROM $this->original_gamelogs_table as ibc
			WHERE ibc.validamount <= 0
			ORDER BY ibc.vendorid asc
			LIMIT 1
EOD;

		$this->CI->load->model('original_game_logs_model');
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,[]);
	}

	/*
	 * Remarks:
	 * US/THB Odds
	 *	(Positive Odds): Won = Odds /100 x Stake ; Lose = Stake
	 *	(Negative Odds): Won = Stake ; Lose = Odds /100 x Stake
	 */
	private function rebuildGameRecords(&$gameRecords,$extra)
	{
		if(count($gameRecords) == 1){
			$newGr[] = $gameRecords;
			$gameRecords = $newGr;
		}
		$newGameRecords = [];
		array_walk($gameRecords, function (&$item,&$key) use (&$newGameRecords,$extra)
			{
				#as per game provider, balltime refers to settlement time
				$balltime = date("Y-m-d H:i:s",strtotime($item['properties'][self::GAMELOG_RESULT_KEY_ID['balltime']]));

				#as per game provider, updatetime refers to bet time
            	$updatetime = date("Y-m-d H:i:s",strtotime($item['properties'][self::GAMELOG_RESULT_KEY_ID['updatetime']]));

            	$this->useServerTimeToGameTimeInOrigSync = $this->getSystemInfo('useServerTimeToGameTimeInOrigSync',true);
            	if($this->useServerTimeToGameTimeInOrigSync){
            		$balltime = $this->utils->modifyDateTime($balltime, $this->gameTimeToServerTime);
            		$updatetime = $this->utils->modifyDateTime($updatetime, $this->gameTimeToServerTime);
            	}

				$ballid = $item['properties'][self::GAMELOG_RESULT_KEY_ID['ballid']];#unique
				$win = $item['properties'][self::GAMELOG_RESULT_KEY_ID['win']];
				$lose = $item['properties'][self::GAMELOG_RESULT_KEY_ID['lose']];
				$bet_amount = $item['properties'][self::GAMELOG_RESULT_KEY_ID['validamount']];
				$country_odds_id = $item['properties'][self::GAMELOG_RESULT_KEY_ID['abc']];
				$result_amount = $win > 0 ? $win : $lose * -1;
				$isbk = $item['properties'][self::GAMELOG_RESULT_KEY_ID['isbk']];

	            $records = [
	    			 'ballid' => $ballid,
	    			 'balltime' => $balltime,#settlement time
	    			 'curpl' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['curpl']],#odds val
	    			 'isbk' => $isbk,
	    			 'game_type' => self::IBC_GAME_TYPES[$isbk],
	    			 'iscancel' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['iscancel']],
	    			 'isjs' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['isjs']],
	    			 'win' => $win,
	    			 'lose' => $lose,
	    			 'result_amount' => $result_amount,
	    			 'currency' => $this->currency_type,
	    			 'moneyrate' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['moneyrate']],
	    			 'orderid' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['orderid']],
	    			 'result' => json_encode($item['properties'][self::GAMELOG_RESULT_KEY_ID['result']]),
	    			 'sportid' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['sportid']],
	    			 'truewin' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['truewin']],
	    			 'tzip' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['tzip']]['@attributes']['name'],
	    			 'tzmoney' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['tzmoney']],
	    			 'tztype' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['tztype']],#bet type id
	    			 'bet_type' => self::BET_TYPES[$item['properties'][self::GAMELOG_RESULT_KEY_ID['tztype']]],#bet type id
	    			 'updatetime' => $updatetime,#betting time
	    			 'username' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['username']],
	    			 'content' => urldecode($item['properties'][self::GAMELOG_RESULT_KEY_ID['content']]),#match details
	    			 'vendorid' => $item['properties'][self::GAMELOG_RESULT_KEY_ID['vendorid']],
	    			 'validamount' => $bet_amount,
	    			 'abc' => $country_odds_id,#odds type
	    			 'oddstype' => self::COUNTRY_ODD_TYPES[$country_odds_id],
	    			 'external_uniqueid' => $ballid,
					 'response_result_id' => $extra['response_result_id']
	            ];
	           	array_push($newGameRecords,$records);
            }
        );
        $gameRecords = $newGameRecords;
        unset($newGameRecords);
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`ibc`.`balltime` >= ?
          AND `ibc`.`balltime` <= ?';
        if($use_bet_time){
            $sqlTime='`ibc`.`updatetime` >= ?
          AND `ibc`.`updatetime` <= ?';
        }

        $sql = <<<EOD
			SELECT
				ibc.id as sync_index,
				ibc.response_result_id,
				ibc.ballid as round,
				ibc.username,
				ibc.validamount as bet_amount,
				ibc.validamount as valid_bet,
				ibc.result_amount,
				ibc.updatetime as start_at,
				ibc.balltime as end_at,
				ibc.updatetime as bet_at,
				ibc.sportid as game_code,
				ibc.content,
				ibc.iscancel,
				ibc.external_uniqueid,
				ibc.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as ibc
			LEFT JOIN game_description as gd ON ibc.bet_type = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON ibc.username = game_provider_auth.login_name
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

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
        $extra = null;
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type_id'],
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['valid_bet'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
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

        $row['bet_details'] = $row['content'];

        if($row['result_amount']){
        	$row['status'] = Game_logs::STATUS_SETTLED;
        }else{
        	$row['status'] = Game_logs::STATUS_PENDING;
        }

        #if iscancel = true then update status in syncmerge
        if($row['iscancel']){
        	$row['status'] = Game_logs::STATUS_CANCELLED;
        }
    }
}
/*end of file*/