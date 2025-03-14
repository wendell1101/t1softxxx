<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: CS SPORTS
	*
	* @category Game_platform
	* @version not specified
	* @copyright 2013-2022 tot
	* @integrator @ivan.php.ph
**/

class Game_api_common_cs_sports extends Abstract_game_api {
	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';

	const ORIGINAL_LOGS_TABLE_NAME = 'cs_sports_game_logs';

	# Fields in cs_sports_game_logs we want to detect changes for update
	const MD5_FIELDS_FOR_ORIGINAL=[
		"platform",
		"platformname",
		"bet",
		"payout",
		"result",
		"bettime",
		"settletime",
		"synctime",
		"gamesn",
		"roundno",
		"gamename",
		"rule",
		"played"
	];

	# Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
    	"bet",
		"payout",
		"result"
    ];

    # Fields in cs_sports_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_MERGE=[
        "roundtime",
        "roundstarttime",
        "update_at",
        "bettime",
        "betamount",
        "updatetime",
        "settletime",
        "synctime",
        "roundno",
        "gamesn",
        "rule",
		"played"
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
    	"bet",
		"payout",
		"result"
    ];


	const URI_MAP = array(
		self::API_createPlayer => '/v1/login',
		self::API_queryPlayerBalance => '/v1/balance',
		self::API_depositToGame => '/v1/recharge',
		self::API_withdrawFromGame => '/v1/withdrawal',
		self::API_queryForwardGame => '/v1/login',
		self::API_syncGameRecords => '/v1/records',
		self::API_getGameProviderGamelist => '/v1/games',
	);

	const DESKTOP_PLATFORM_TYPE = 0;
	const MOBILE_PLATFORM_TYPE = 1;

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url'); //url
		$this->key = $this->getSystemInfo('key'); //key
		$this->mid = $this->getSystemInfo('secret'); // mid
		$this->gameid = $this->getSystemInfo('gameid'); // game id
		$this->method = self::METHOD_POST;
	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url.$apiUri;

		if($this->method == self::METHOD_GET){
			return $url.'?'.http_build_query($params);
		}

		$this->CI->utils->debug_log('generateUrl ========= url', $url);
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		switch ($this->method){
			case self::METHOD_POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
		}
		$this->utils->debug_log('CS SPOTRS REQUEST FIELD: ',http_build_query($params));
	}

	public function getSigntrue($data, $key) {
		$signPars = "";
		ksort($data);
		foreach ($data as $k => $v) {
			if ("" != $v && "signature" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars .= $key;
		$sign = md5($signPars);
		return $sign;
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201)){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('CS SPORTS got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"Mid" => $this->mid,
			"Account" => $gameUsername,
			"IsDemo" => "false",
			"Chou" => "1",
			"IsMobile" => "false",
			"TimeZone" => "1",
			"RequestTime" => date("Y-m-d H:i:s"),
			"Game" => "nupIJ728",
			"Ip" => $this->CI->utils->getIP(),
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('createPlayer ========= Params', $params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ========= resultArr', $resultArr, $statusCode);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		$this->CI->utils->debug_log('processResultForCreatePlayer ========= success', $success);


		$result = ['player' => $gameUsername];
		if ($success) {
			# update flag to registered = true
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

	        # update external id
	        $this->updateExternalAccountIdForPlayer($playerId, $resultArr['user']['UserId']);
	        $result['userid'] = $resultArr['user']['UserId'];
	        $result['exists'] = true;
		} else {
			$result['message'] = $resultArr['message'];
		}

		return array($success, $result);
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
	    // $statusCode = intval($statusCode, 10);
	    //return $errCode || intval($statusCode, 10) >= 400;
	    return $errCode || intval($statusCode, 10) > 400;
	}

	public function getListOfGames(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetListOfGames'
		);

		$params = array(
			"Mid" => $this->mid,
			"RequestTime" => date("Y-m-d H:i:s"),
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('getListOfGames ========= Params', $params);
		$this->method = self::METHOD_GET;
		return $this->callApi(self::API_getGameProviderGamelist, $params, $context);

	}

	public function processResultForGetListOfGames($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForGetListOfGames ========= resultArr', $resultArr);

		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$gameList = !empty($resultArr)?$resultArr['games']:[];
		if ($success) {
			$this->rebuildGameList($gameList);
			$result = $gameList;
		}

		return array($success, $result);
	}

	private function rebuildGameList(&$gameList) {

		$newGL =[];
		foreach($gameList as $i => $gl) {
			$newGL[$i]['game_id'] = $gl['Game'];
			$newGL[$i]['game_name'] = $gl['EnName'];
			$newGL[$i]['game_platform_name'] = $gl['PlatformName'];
			$newGL[$i]['type_id'] = $gl['Type'];
			$newGL[$i]['type_name'] = $gl['TypeName'];
        }
        $gameList = $newGL;
	}

	public function isPlayerExist($playerName){
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = null, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'gameUsername' => $gameUsername
		);

		$params = array(
			"Mid" => $this->mid,
			"Account" => $gameUsername,
			"IsDemo" => "false",
			"Chou" => "1",
			"IsMobile" => "false",
			"TimeZone" => "1",
			"RequestTime" => date("Y-m-d H:i:s"),
			"Game" => "nupIJ728",
			"Ip" => $this->CI->utils->getIP(),
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('login ========= Params', $params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForLogin($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForLogin ========= resultArr', $resultArr, $statusCode);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		$this->CI->utils->debug_log('processResultForLogin ========= success', $success);
		$result = ['player' => $gameUsername];
		if ($success) {
	        $result['userid'] = $resultArr['user']['UserId'];
	        $result['playerurl'] = $resultArr['user']['PlayUrl'];
		} else {
			$result['message'] = $resultArr['message'];
		}

		return array($success, $result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'TD'.uniqid() : $transfer_secure_id;

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$userid = $this->getExternalAccountIdByPlayerUsername($playerName);
		$this->CI->utils->debug_log('depositToGame ========= userid', $userid);

		$params = array(
			"Mid" => $this->mid,
			"UserId" => $userid,
			"Amount" => $this->dBtoGameAmount($amount),
			"OrderSn" => $external_transaction_id,
			"IsMobile" => "false",
			"RequestTime" => date("Y-m-d H:i:s"),
			"Ip" => $this->CI->utils->getIP(),
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('depositToGame ========= Params', $params);
		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	public function processResultForDepositToGame($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$this->CI->utils->debug_log('processResultForDepositToGame ========= resultArr', $resultArr, $statusCode);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);


		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $resultArr['order'],
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
	        $result['message'] = $resultArr['message'];
	        $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
			$result['message'] = $resultArr['message'];
		}

		return array($success, $result);
	}

	private function getReasons($statusCode)
	{
		switch ($statusCode) {
			case "UserId不能为空":
				return self::REASON_INCOMPLETE_INFORMATION;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'TW'.uniqid() : $transfer_secure_id;

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$userid = $this->getExternalAccountIdByPlayerUsername($playerName);

		$params = array(
			"Mid" => $this->mid,
			"UserId" => $userid,
			"Amount" => $this->dBtoGameAmount($amount),
			"OrderSn" => $external_transaction_id,
			"IsMobile" => "false",
			"RequestTime" => date("Y-m-d H:i:s"),
			"Ip" => $this->CI->utils->getIP(), //'203.177.31.194'
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('withdrawFromGame ========= Params', $params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);

	}

	public function processResultForWithdrawFromGame($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$this->CI->utils->debug_log('processResultForWithdrawFromGame ========= resultArr', $resultArr, $statusCode);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);


		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $resultArr['order'],
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);
		$bal = $this->queryPlayerBalance($gameUsername);
		$this->CI->utils->debug_log('processResultForWithdrawFromGame ========= resultArr', $bal);
		if ($success) {
			$result['balance'] = $bal['balance'];
	        $result['message'] = $resultArr['message'];
	        $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
			$result['message'] = $resultArr['message'];
		}

		return array($success, $result);
	}

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function gameAmountToDB($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
        // return $amount / $conversion_rate;
    }

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$userid = $this->getExternalAccountIdByPlayerUsername($playerName);

		$params = array(
			"Mid" => $this->mid,
			"UserId" => $userid,
			"IsMobile" => "false",
			"RequestTime" => date("Y-m-d H:i:s"),
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('queryPlayerBalance ========= Params', $params);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$this->CI->utils->debug_log('processResultForQueryPlayerBalance ========= resultArr', $resultArr, $statusCode);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		if($success){
			$result['balance'] = @floatval($this->gameAmountToDB($resultArr['balance']));
			$result['message'] = $resultArr['message'];
		}

		return array($success, $result);
	}

	// goto game format http://player.og.local/iframe_module/gotogame/5476/
	public function queryForwardgame($playerName, $extra = null) {
		$playerURL = $this->login($playerName);
		return  ['success'=>true, 'url'=> $playerURL['playerurl']];
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate->modify($this->getDatetimeAdjust());
        $startDate = $this->serverTimeToGameTime($startDate);
        $endDate = $this->serverTimeToGameTime($endDate);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

        $params = array(
			"Mid" => $this->mid,
			"RequestTime" => date("Y-m-d H:i:s"),
			"From" => $startDate,
			"End" => $endDate,
			"Page" => 1,
			"Num" => 1000,
		);

		$req_data = array_filter($params);
		$params['sign'] = $this->getSigntrue($req_data, $this->key);
		$this->CI->utils->debug_log('queryPlayerBalance ========= Params', $params);
		$this->method = self::METHOD_GET;
		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$this->CI->utils->debug_log('processResultForSyncOriginalGameLogs ========= resultArr', $resultArr, $statusCode);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = ['data_count' => 0];
		$gameRecords = !empty($resultArr)?$resultArr['result']['logs']:[];

		if ($success && !empty($gameRecords)) {
			$extra = ['response_result_id' => $responseResultId];
			$this->rebuildGameRecords($gameRecords,$extra);

			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    self::ORIGINAL_LOGS_TABLE_NAME,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );
			$this->CI->utils->debug_log('CS SPORTS after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
            $insertRows = json_encode($insertRows);
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
		return array($success, $result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra) {

		$newGR =[];
		foreach($gameRecords as $i => $gr) {
			$newGR[$i]['userId'] = isset($gr['UserId']) ? $gr['UserId'] : null;
			$newGR[$i]['platform'] = isset($gr['Platform']) ? $gr['Platform'] : null;
			$newGR[$i]['platformname'] = isset($gr['PlatformName']) ? $gr['PlatformName'] : null;
			$newGR[$i]['bet'] = isset($gr['Bet']) ? $gr['Bet'] : null;
			$newGR[$i]['odds'] = isset($gr['Odds']) ? $gr['Odds'] : null;
			$newGR[$i]['payout'] = isset($gr['Payout']) ? $gr['Payout'] : null;
			$newGR[$i]['result'] = isset($gr['Result']) ? $gr['Result'] : null;
			$newGR[$i]['bettime'] = isset($gr['BetTime']) ? date("Y-m-d H:i:s",strtotime($gr['BetTime'])) : null;
			$newGR[$i]['settletime'] = isset($gr['SettleTime']) ? date("Y-m-d H:i:s",strtotime($gr['SettleTime'])) : null;
			$newGR[$i]['synctime'] = isset($gr['SyncTime']) ? date("Y-m-d H:i:s",strtotime($gr['SyncTime'])) : null;
			$newGR[$i]['gamesn'] = isset($gr['GameSn']) ? $gr['GameSn'] : null;
			$newGR[$i]['roundno'] = isset($gr['RoundNo']) ? $gr['RoundNo'] : null;
			$newGR[$i]['gamename'] = isset($gr['GameName']) ? $gr['GameName'] : null;
			$newGR[$i]['rule'] = isset($gr['Rule']) ? $gr['Rule'] : null;
			$newGR[$i]['played'] = isset($gr['Played']) ? $gr['Played'] : null;
			$newGR[$i]['response_result_id']= $extra['response_result_id'];
			$newGR[$i]['external_uniqueid']= $gr['GameSn']."-".$gr['RoundNo'];
			$newGR[$i]['updated_at']= date("Y-m-d H:i:s");
			$newGR[$i]['creationtime']= date("Y-m-d H:i:s");
        }
        $gameRecords = $newGR;
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            if (!is_array($data)) {
                $data = json_decode($data,true);
            }
            if (is_array($data)) {
                foreach ($data as $record) {
                    if ($queryType == 'update') {
                        $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                    } else {
                        unset($record['id']);
                        $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                    }
                    $dataCount++;
                    unset($record);
                }
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
    	$sqlTime='`cs`.`settletime` >= ?
          AND `cs`.`settletime` <= ?';
        if($use_bet_time){
            $sqlTime='`cs`.`bettime` >= ?
          AND `cs`.`bettime` <= ?';
        }

        $sql = <<<EOD
			SELECT
				cs.id as sync_index,
				cs.userId as username,
				cs.platform as game_code,
				cs.platformname as game,
				cs.bet as bet_amount,
				cs.payout as result_amount,
				cs.result as valid_bet,
				cs.bettime as start_at,
				cs.settletime as end_at,
				cs.bettime as bet_at,
				cs.synctime,
				cs.gamesn,
				cs.roundno as round,
				cs.gamename,
				cs.rule,
				cs.odds,
				cs.played as bet_details,
				cs.response_result_id,
				cs.external_uniqueid,
				cs.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM cs_sports_game_logs as cs
			LEFT JOIN game_description as gd ON cs.platform = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON cs.userid = game_provider_auth.external_account_id
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
            'odds' => $row['odds'],

        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $this->CI->utils->debug_log('makeParamsForInsertOrUpdateGameLogsRow ================================', $row);
        $playerId = $this->getPlayerIdByExternalAccountId($row['username']);
        $gameUsername = $this->getGameUsernameByPlayerId($playerId);
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => 'sports',
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $playerId,
                'player_username' => $gameUsername
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
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
	{
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
	}


	public function logout($gameUsername, $password = null) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
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

}