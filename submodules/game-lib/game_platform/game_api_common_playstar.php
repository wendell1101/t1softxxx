<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: PLAYSTAR
	*
	* @category Game_platform
	* @version 1.31
	* @copyright 2013-2022 tot
	* @integrator @andy.php.ph
**/

class Game_api_common_playstar extends Abstract_game_api {
	# Fields in playstar_idr_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
    	'member_id',//(string) player id
        'game_round_id',//sn (uint64) Unique id of the game round
        'game_id',//gid (string) Unique id of PS Games
        'sub_game_id',//sid (uint16) Sub-Game ID of PS Games
        'gameround_end_time',//tm (string) The end time of the game round. Format HH:mm:ss
        'amount',//bet (uint64) The amount of money bet
        'game_round_denomination',//dm (uint64) The denomination of the game round
        'win_amount',//win (uint64) The amount of money win (in cents, including bonus win, gamble win)
        'result_amount',//bn (uint64) The amount of bonus win (in cents)
        'win_bonus_amount',//bd (array) The bonus data of the game round that only exists if bonus is triggered
        'win_gamble_amount',//gb (uint64) The amount of gamble win (in cents)
        'win_jackpot_amount',//jp (uint64) The amount of jackpot win (in cents)
        'result_data',//rd (string) The result data of the game round. (The data scheme is depend on different game types.
        'extra_data',//ex (string) The extra data of the game round.
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'win_amount',
        'result_amount',
        'win_gamble_amount',
        'win_jackpot_amount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when playstar_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	const URI_MAP = array(
		self::API_createPlayer => '/funds/createplayer/',
		self::API_queryPlayerBalance => '/funds/getbalance/',
		self::API_depositToGame => '/funds/deposit/',
		self::API_withdrawFromGame => '/funds/withdraw/',
		self::API_login => '/api/authenticate/',
		self::API_logout => '/api/logout/',
		self::API_queryForwardGame => '/launch/',
		self::API_syncGameRecords => '/feed/gamehistory/',
		self::API_queryTransaction => '/funds/log',
	);

	const ORIGINAL_GAMELOGS_TABLE = "playstar_game_logs";
	const SUCCESS_CODE = 0;
	const FAILED_CODE = 1;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->host_id = $this->getSystemInfo('key');
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->token = $this->getSystemInfo('token');
		$this->api_url_for_bet_history = $this->getSystemInfo('api_url_for_bet_history');
	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		$req_params = http_build_query($params);
		$url = $this->api_url.$apiUri."?".$req_params;
		return $url;
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if(@$statusCode == 200 || @$statusCode == 201){
			$success = true;
		}

		if(isset($resultArr['status_code']) && $resultArr['status_code']<>self::SUCCESS_CODE){
			$success = false;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PLAYSTAR got error ', $responseResultId,'result', $resultArr);
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
			'host_id' => $this->host_id,
			'member_id' => $gameUsername,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

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

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'host_id' => $this->host_id,
			'member_id' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

		if($success){
			$result['balance'] = $this->gameAmountToDb($resultArr['balance']);
		}

		return array($success, $result);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null)
	{
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }
        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$external_transaction_id = empty($transfer_secure_id) ? 'TD'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'host_id' => $this->host_id,
			'member_id' => $gameUsername,
			'txn_id' => $external_transaction_id,
			'amount' => $this->dBtoGameAmount($amount),
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons($statusCode);
			}
        }

        return array($success, $result);
	}

	private function getReasons($statusCode)
	{
		switch ($statusCode) {
			case 400:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 401:
				return self::REASON_INVALID_KEY;
				break;
			case 404:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 409:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 500:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;

			case '1':
			case 1:
				return self::REASON_ACCOUNT_NOT_EXIST;
				break;
			case '2':
			case 2:
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;
			case '3':
			case 3:
				return self::REASON_TRANSACTION_NOT_FOUND;
				break;
			case '5':
			case 5:
				return self::REASON_SERVER_EXCEPTION;
				break;
			case '7':
			case 7:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case '8':
			case 8:
				return self::REASON_NO_ENOUGH_BALANCE;
				break;
			case '9':
			case 9:
				return self::REASON_INVALID_PRODUCT_WALLET;
				break;
			case '10':
			case 10:
				return self::REASON_FAILED_FROM_API;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$external_transaction_id = empty($transfer_secure_id) ? 'TW'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'host_id' => $this->host_id,
			'member_id' => $gameUsername,
			'txn_id' => $external_transaction_id,
			'amount' => $this->dBtoGameAmount($amount),
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
			$result['external_transaction_id'] = $external_transaction_id;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	/*
	 *	To Launch Game, For Real Mode, game provider sends data to our callback url to authenticate user
	 *	while demo mode does not send authenticate anymore, it will launch directly the game in demo mode
	 *
	 *  Sample
	 *  http://admin.domain.t1t.in/callback/game/93/auth?access_token=0630c7bc349922ac71e4d2fb2fbea26e&step=0
	 *
	 *  Our system response with data in json format 'status_code' and 'member_id'
	 *  0 means success, 1 means invalid token
	 *
	 *  Player Real Url: http://player.domain.t1t.in/iframe_module/gotogame/93/<game_code>/real
	 *  Player Demo Url: http://player.domain.t1t.in/iframe_module/gotogame/93/<game_code>/demo
	 *  Ex. Url: http://player.domain.t1t.in/iframe_module/gotogame/93/PSS-ON-00001/real
	 */
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForQueryForwardGame',
				'playerName' => $playerName,
				'gameUsername' => $gameUsername
			);

		#GET LANG FROM PLAYER DETAILS
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);

		$params = array(
			'host_id' => $this->host_id,
			'game_id' => $extra['game_code'],
			'member_id' => $gameUsername,
			'lang' => $language,
			'access_token' => $this->getPlayerToken($playerId)
		);

		#IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL, THEN INCLUDE IN LOGIN TOKEN
		if(isset($extra['language'])){
			$params['lang'] = $this->getLauncherLanguage($extra['language']);
		}

		if($extra['game_mode'] != 'real') {
			unset($params['access_token']);
		}

		if(isset($extra['sub_game_id']) && !empty($extra['sub_game_id'])){
			$params['sub_game_id'] = $extra['sub_game_id'];
		}

		if($this->getSystemInfo('return_url')){
			$params['return_url'] = $this->getSystemInfo('return_url');
		}

		$apiUri = self::URI_MAP[self::API_queryForwardGame];
		$req_params = http_build_query($params);
		$url = $this->api_url.$apiUri."?".$req_params;
		$result = ['success' => true,'url' => $url];
		return $result;
	}

	public function getLauncherLanguage($language){
		$lang='';
		switch ($language) {
			case 1: case 'en-US': case 'en': case 'EN': case "English": $lang = 'en-US'; break;
			case 2: case 'zh-CN': case 'cn': case 'CN': case "Chinese": $lang = 'zh-CN'; break;
			case 4: case 'vi-VN': case 'vn': case 'VN': case "Vietnamese": $lang = 'vi-VN'; break;
			case 5: case 'ko-kr': case 'ko': case 'KO': case "Korean": $lang = 'ko-KR'; break;
			case 6: case 'th-TH': case 'th': case 'TH': case "Thai": $lang = 'th-TH'; break;
			case 7: case 'id-ID': case 'id': case 'ID': case "Indonesian": $lang = 'id-ID'; break;
			default: $lang = 'en-US'; break;
		}
		return $lang;
	}

	public function callback($request)
	{
		$playerId = $this->getPlayerIdByToken($request['access_token']);
		$gameUsername = $this->getGameUsernameByPlayerId($playerId);
		if (!empty($playerId)) {
			return ['status_code'=>self::SUCCESS_CODE,'member_id'=>$gameUsername];
		}
		return ['status_code'=>self::FAILED_CODE,'member_id'=>$gameUsername];
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$queryDateTimeStart = $startDateTime->format("Y-m-d\TH:i:s");
		$queryDateTimeEnd = $endDateTime->format('Y-m-d\TH:i:s');

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

		$params = array(
			'host_id' => $this->host_id,
			'start_dtm' => $queryDateTimeStart,
			'end_dtm' => $queryDateTimeEnd,
			'type' => 1,// 0 - Query without Game Result / Extra Data (Default),1 - Query with Game Result / Extra Data
		);
		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['data_count' => 0];
		$gameRecords = isset($resultArr)?$resultArr:[];
		if($success&&!empty($gameRecords))
		{
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

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
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
		}

		return array($success, $result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra)
	{
		$perDateRecordTempArr = [];
		foreach ($gameRecords as $key => $val) {
			array_push($perDateRecordTempArr, $key);
		}

		$arrangedRecords = [];
		foreach ($perDateRecordTempArr as $dateKey) {
			foreach ($gameRecords[$dateKey] as $playerKey => $val) {
				foreach ($val as $pgr) {
					$bet_amount = isset($pgr['bet'])?$this->processRecordsForReport($pgr['bet']):null;
					$win_amount = isset($pgr['win'])?$this->processRecordsForReport($pgr['win']):null;
					$game_round_id = isset($pgr['sn'])?$pgr['sn']:null;
					array_push($arrangedRecords,[
						  "member_id" => $playerKey,
						  "game_round_id" => $game_round_id,
						  "game_id" => isset($pgr['gid'])?$pgr['gid']:null,
						  "sub_game_id" => isset($pgr['sid'])?$pgr['sid']:null,
						  "gameround_end_time" => isset($pgr['tm'])?$dateKey." ".$pgr['tm']:null,
						  "amount" => $bet_amount,
						  "currency" => $this->currency_type,
						  "game_round_denomination" => isset($pgr['dm'])?$pgr['dm']:null,
						  "win_amount" => $win_amount,
						  "result_amount" => $win_amount - $bet_amount,
						  "win_bonus_amount" => isset($pgr['bn'])?$this->processRecordsForReport($pgr['bn']):null,
						  "bonus_data" => isset($pgr['bd'])&&!empty($pgr['bd'])?json_encode($pgr['bd']):null,
						  "win_gamble_amount" => isset($pgr['gb'])?$this->processRecordsForReport($pgr['gb']):null,
						  "win_jackpot_amount" => isset($pgr['jp'])?$this->processRecordsForReport($pgr['jp']):null,
						  "result_data" => isset($pgr['rd'])?$pgr['rd']:null,
						  "extra_data" => isset($pgr['ex'])?$pgr['ex']:null,
						  "external_uniqueid" => $game_round_id,
						  "response_result_id" => $extra['response_result_id'],
						]
					);
				}
			}
		}
		$gameRecords = $arrangedRecords;
		unset($arrangedRecords,$perDateRecordTempArr);
	}

	private function processRecordsForReport($amount){
		$use_currency_conversion_in_report = $this->getSystemInfo('use_currency_convertion_in_report',false);
		$conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
		if($use_currency_conversion_in_report){
			return $amount;
		}
		return $amount/$conversion_rate;
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
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

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`ps`.`gameround_end_time` >= ?
          AND `ps`.`gameround_end_time` <= ?';

        $sql = <<<EOD
			SELECT
				ps.id as sync_index,
				ps.response_result_id,
				ps.game_round_id as round,
				ps.member_id as username,
				ps.amount as bet_amount,
				ps.amount as valid_bet,
				ps.result_amount,
				ps.gameround_end_time as start_at,
				ps.gameround_end_time as end_at,
				ps.gameround_end_time as bet_at,
				ps.game_id as game_code,
				ps.external_uniqueid,
				ps.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as ps
			LEFT JOIN game_description as gd ON ps.game_id = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON ps.member_id = game_provider_auth.login_name
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

	public function queryBetDetailLink($playerId, $round_id = null, $extra = null)
    {
    	$gameUsername = $this->getGameUsernameByPlayerId($playerId);
    	
    	$params = array(
            'token' => $this->token,
            'sn' => $round_id,
            'player' => $gameUsername,
        );

        $url = $this->api_url_for_bet_history . "/Resource/game_history?" . http_build_query($params);

        return array('success' => true, 'url' => $url);
    }

	public function login($playerName, $password = null, $extra = null)
	{
		return $this->unimplemented();
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

    public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}
}
/*end of file*/