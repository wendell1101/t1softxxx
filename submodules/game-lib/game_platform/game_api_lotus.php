<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: RTG AS API Specifications with Transfer Wallet
	* Document Number: 


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
	* @integrator @garry.php.ph
**/

class game_api_lotus extends Abstract_game_api {
	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';
	# Fields in tianhao_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'uid',
        'user_id',
        'game_code',
        'start_balance',
        'end_balance',
        'total_betting',
        'betting_log',
        'betting_date',
        'result_log',
        'result_date',
        'result',
        'result_amount',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'start_balance',
        'end_balance',
        'total_betting',
        'result_amount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when tianhao_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'gameUsername',
        'bet_time',
        'game_code',
        'result_amount',
        'bet_amount',
        'after_balance',
        'round',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'result_amount',
        'bet_amount',
        'after_balance'
    ];

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->agent_id = $this->getSystemInfo('agent_id');
		$this->api_pwd = $this->getSystemInfo('api_pwd');
		$this->slots_playable = $this->getSystemInfo('slots_playable',true);
		$this->method = self::POST; # default as POST

		$this->URI_MAP = array(
			self::API_createPlayer => '/api/createuid/',
			self::API_queryPlayerBalance => '/api/balance/',
			self::API_isPlayerExist => '/api/balance/',
	        self::API_depositToGame => '/api/deposit/',
	        self::API_withdrawFromGame => '/api/withdrawal/',
	        self::API_logout => '/api/kickout/',
	        self::API_queryForwardGame => '/api/launch/',
			self::API_syncGameRecords => '/api/history_agent/'
		);
	}

	public function getPlatformCode() {
		return LOTUS_API;
	}

	protected function getHttpHeaders($params){
		$headers = array(
			'Content-type' => 'application/x-www-form-urlencoded',
		    'Cache-control' => 'no-cache'
		);
		return $headers;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		# IF GET 
		if($this->method == self::GET){
			$url = $url.'?'.http_build_query($params);
		}

		return $url;
	}

	// protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
 //        return $errCode || intval($statusCode, 10) >= 302;
 //    }

	protected function customHttpCall($ch, $params) {
		switch ($this->method){
			case self::POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
			// case self::GET:
			// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			// 	break;
		}
		$this->utils->debug_log('LOTUS POSTFEILD: ',http_build_query($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(@$resultArr['code'] == "OK"){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('LOTUS API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
			'hash' => md5($this->agent_id.'|'.$this->api_pwd.'|'.$gameUsername)
		);
		$this->method = self::GET;

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
	        $result['exists'] = true;
		}

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = [];

		if($success){
			$result['balance'] = @floatval($resultArr['balance']);
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['exists'] = true;
		}else{
			$result['exists'] = false;
		}

		return array($success, $result);
    }

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }
        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount
        );

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
			'cash' => $amount,
			'hash' => md5($this->agent_id.'|'.$this->api_pwd.'|'.$gameUsername)
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {			
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
        	$error_msg = @$resultArr['message'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($error_msg);
        }

        return array($success, $result);
	}

	private function getReasons($error_msg){
		switch ($error_msg) {
			case 'OVER_MONEY':
				return self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
				break;
			case 'NOT_Hash':
				return self::REASON_INVALID_KEY;
				break;
			case 'NO_Member':
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;
			
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
        );

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
			'cash' => $amount,
			'hash' => md5($this->agent_id.'|'.$this->api_pwd.'|'.$gameUsername)
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
        	$error_msg = @$resultArr['message'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($error_msg);
        }

        return array($success, $result);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array('success' => true);
	}

	public function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
			'hash' => md5($this->agent_id.'|'.$this->api_pwd.'|'.$gameUsername)
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		return array($success, null);
	}

	const INT_LANG_KOREAN = '5';
	const INT_LANG_THAI = '6';
	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case 1:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case 3:
            case 'id-id':
                $lang = 'id'; // chinese
                break;
            case 4:
            case 'vi-vn':
                $lang = 'vi'; // chinese
                break;
            case 5:
            case 'ko-kr':
                $lang = 'ko'; // chinese
                break;
            case 6:
            case 'th-th':
                $lang = 'th'; // chinese
                break;
            default:
                $lang = 'zh'; // default as chinese
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$params = array(
			'agent_id' => $this->agent_id,
			'account' => $gameUsername,
			'lang' => $this->getLauncherLanguage(@$extra['language']),
			'hash' => md5($this->agent_id.'|'.$this->api_pwd.'|'.$gameUsername),
			'slot' => $this->slots_playable?"on":"off"
		);
		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $params['statusCode']==302?true:false;
		$result = array('url'=>'');
		if($success){
			$headers = array_filter(explode("\n", $params['extra']));
			$redirect_url = NULL;
			foreach ($headers as $header) {
				$temp = explode(':', $header, 2);
				if ($temp[0] == 'Location') {
					$redirect_url = trim($temp[1]);
					break;
				}
			}
			$result['url'] = $redirect_url;
		}

		return array($success, $result);
	}
                
	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$startDateTime->modify($this->getDatetimeAdjust());
		$startDate = $startDateTime->format('Y-m-d');
		$startTime = $startDateTime->format('H:i:s');
    	$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));
    	$endDate = $endDateTime->format('Y-m-d');
    	$endTime = $endDateTime->format('H:i:s');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'api_pwd' => $this->api_pwd,
			'startDate' => $startDate,
			'startTime' => $startTime,
			'endDate' => $endDate,
			'endTime' => $endTime,
			'hash' => md5($this->agent_id.'|'.$this->api_pwd)
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	private function rebuildLotusGameRecords(&$gameRecords,$extra){
        foreach($gameRecords as $index => $record) {
        	# lower case feild
			$new_gameRecords[$index] = array_change_key_case($gameRecords[$index], CASE_LOWER);
        	# PROCESS NEEDED FIELDS
            $new_gameRecords[$index]['result_amount'] = !empty($new_gameRecords[$index]['result_log'][0]['result'])?$new_gameRecords[$index]['result_log'][0]['result']:0;
        	$new_gameRecords[$index]['betting_date'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($new_gameRecords[$index]['betting_date'])));
            $new_gameRecords[$index]['betting_log'] = is_array($new_gameRecords[$index]['betting_log'])?json_encode(reset($new_gameRecords[$index]['betting_log'])):null;
            $new_gameRecords[$index]['result_log'] = is_array($new_gameRecords[$index]['result_log'])?json_encode(reset($new_gameRecords[$index]['result_log'])):null;
            # USED external_uniqueid CONCAT uid+user_id+game_id+game_code
            $external_uniqueid = $new_gameRecords[$index]['uid'].$new_gameRecords[$index]['user_id'].$new_gameRecords[$index]['game_id'].$new_gameRecords[$index]['game_code'];
            $new_gameRecords[$index]['external_uniqueid'] = $external_uniqueid;
            $new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
        }
        $gameRecords = $new_gameRecords;
	}

	public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array('data_count'=>0);

		if($success){
			$gameRecords = $resultArr['detail'];
            $extra = ['response_result_id'=>$responseResultId];
            $this->rebuildLotusGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                'lotus_game_logs',
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

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
		}

		return array($success, $result);
	}


    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('lotus_game_logs', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('lotus_game_logs', $record);
                }
                $dataCount++;
                unset($data);
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
        $sqlTime='`lotus_game_logs`.`updated_at` >= ?
          AND `lotus_game_logs`.`updated_at` <= ?';
        if($use_bet_time){
            $sqlTime='`lotus_game_logs`.`betting_date` >= ?
          AND `lotus_game_logs`.`betting_date` <= ?';
        }

        $sql = <<<EOD
        SELECT 
          `lotus_game_logs`.`id` AS sync_index,
          `lotus_game_logs`.`user_id` AS gameUsername,
          `lotus_game_logs`.`external_uniqueid`,
          `lotus_game_logs`.`betting_date` as bet_time,
          `lotus_game_logs`.`updated_at` AS game_date,
          `lotus_game_logs`.`game_code`,
          `lotus_game_logs`.`response_result_id`,
          `lotus_game_logs`.`result_amount`,
          `lotus_game_logs`.`total_betting` AS bet_amount,
          `lotus_game_logs`.`end_balance` AS after_balance,
          `lotus_game_logs`.`round`,
          `lotus_game_logs`.`md5_sum`, 
    	  `lotus_game_logs`.`response_result_id`,
          `game_provider_auth`.`player_id`,
          `game_description`.`id` AS game_description_id,
          `game_description`.`game_type_id`
        FROM
          `lotus_game_logs`
          LEFT JOIN `game_description` 
            ON lotus_game_logs.game_code = game_description.external_game_id 
            AND game_description.game_platform_id = ? 
            AND game_description.void_bet != 1 
          LEFT JOIN `game_type` 
            ON game_description.game_type_id = game_type.id 
          JOIN `game_provider_auth` 
            ON (
              `lotus_game_logs`.`user_id` = `game_provider_auth`.`login_name` 
              AND `game_provider_auth`.`game_provider_id` = ?
            ) 
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

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
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
                'player_username' => $row['gameUsername']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount']-$row['bet_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' =>$row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['bet_time'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['bet_time'],
                'updated_at' => $row['game_date']
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
            'bet_details' => [],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            $row['game_description_id']= $unknownGame->id;
            $row['game_type_id'] = $unknownGame->game_type_id;
        }
        $row['status'] = Game_logs::STATUS_SETTLED;
    }

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row->gameid;
        $extra = array('game_code' => $external_game_id,'game_name' => $row->originalGameName);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function login($playerName, $password = null, $extra = null) {
    	return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
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

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/