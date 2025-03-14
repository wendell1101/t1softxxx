<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: AVIA-ESportAPI(EN)
	* Document Number / Version Number: 0.1.8


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
	* @integrator @garry.php.ph
**/

class Game_api_aviaesport extends Abstract_game_api {

	# Fields in aviaesport_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'order_id',
        'username',
        'bet',
        'content',
        'result',
        'bet_amount',
        'bet_money',
        'money',
        'status',
        'update_at',
        'end_at',
        'result_at',
        'reward_at',
        'odds',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'bet_money',
        'money',
    ];

    # Fields in game_logs we want to detect changes for merge, and when aviaesport_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'status',
        'bet_place',
        'bet_place_content',
        'bet_amount',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at',
        'odds',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	# Don't ignore on refresh 
	const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+24 hours');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '1');

		$this->URI_MAP = array(
			self::API_createPlayer => '/api/user/register',
			self::API_queryPlayerBalance => '/api/user/balance',
			self::API_isPlayerExist => '/api/user/balance',
	        self::API_depositToGame => '/api/user/transfer',
	        self::API_withdrawFromGame => '/api/user/transfer',
	        self::API_queryTransaction => '/api/user/transferinfo',
	        self::API_queryForwardGame => '/api/user/login',
			self::API_syncGameRecords => '/api/log/get'
		);
	}

	public function getPlatformCode() {
		return AVIA_ESPORT_API;
	}

	protected function getHttpHeaders($params){
		$headers = array(
		    'Authorization' => $this->getSystemInfo('key')
		);
		return $headers;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$this->utils->debug_log('AVIA ESPORT POSTFEILD: ',http_build_query($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(@$resultArr['success'] == 1){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('AVIA ESPORT got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
			'UserName' => $gameUsername,
			'Password' => $password,
		);

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

	private function round_down($number, $precision = 3){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
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
			'UserName' => $gameUsername,
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
			$result['balance'] = $this->round_down(@floatval($resultArr['info']['Money']));
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
			'UserName' => $gameUsername,
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
			if(@$resultArr['info']['Error'] == 'NOUSER'){
				$result['exists'] = false;
			}else{
				$result['exists'] = null;
			}
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
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'UserName' => $gameUsername,
			'Money' => $amount,
			'Type' => 'IN',
			'ID' => $external_transaction_id
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
        	$error_msg = @$resultArr['info']['Error'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($error_msg);
        }

        return array($success, $result);
	}

	private function getReasons($error_msg){
		switch ($error_msg) {
			case 'EXISTSORDER':
			case 'BANORDER':
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 'BADMONEY':
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 'NOUSER':
			case 'BADNAME':
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;
			
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'UserName' => $gameUsername,
			'Money' => $amount,
			'Type' => 'OUT',
			'ID' => $external_transaction_id
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
        	$error_msg = @$resultArr['info']['Error'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($error_msg);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {

		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);

		$params = array(
			'ID' => $transactionId
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername, true);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$trans_status = @$resultJsonArr['info']['Status'];
			if($trans_status == 'Finish') {
				$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			} else {
				$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
				$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
		} else {
			$error_code = @$resultArr['info']['Error'];
			$result['reason_id'] = $this->getReasons($error_code);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
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

	public function queryForwardGame($playerName, $extra = null) {
		# if guest mode
		if($extra['game_mode'] == 'trial'||$extra['game_mode'] == 'demo'||$extra['game_mode'] == 'fun'){
			$this->URI_MAP[self::API_queryForwardGame] = '/api/user/guest';
			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForQueryForwardGame',
	        );

			return $this->callApi(self::API_queryForwardGame, [], $context);
		}

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$params = array(
			'username' => $gameUsername,
			'Password' => $password
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array('url'=>'');

		if($success){
			$result['url'] = @$resultArr['info']['Url'];
		}

		return array($success, $result);
	}
    	
    # notes: Time range cannot exceed 24 hours

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));


    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd   = $endDate->format('Y-m-d H:i:s');
    	$maxDateTimeEnd = $startDateTime->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    	if($queryDateTimeEnd < $maxDateTimeEnd){
    		$page_index = 0;
	    	$record_count = 0;
	    	$total_page = 0;
	    	do {
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'startDate' => $queryDateTimeStart,
					'endDate' => $queryDateTimeEnd
				);

				$page_index++;
				$params = array(
					'OrderType' => $this->getSystemInfo('order_type', 'All'),
					'Type' => 'UpdateAt',
					'StartAt' => $queryDateTimeStart,
					'EndAt' => $queryDateTimeEnd,
					'PageIndex' => $page_index,
				);

				$result = $this->callApi(self::API_syncGameRecords, $params, $context);
				sleep($this->sync_sleep_time);
				
				if($result['success']){
					$total_page = ($result['record_count'] + $result['page_size'] -1) / $result['page_size'];
					if($result['record_count'] == 0){
						$page_index = $total_page;
					}
				}else{
					$page_index = $total_page;
				}
			} while ($page_index  < $total_page);
			return array("success" => true, "results"=>$result);
    	}
    	$this->CI->utils->debug_log('================> aviasyncgamelogs error : exceed 24 hours date time range');
		return array("success" => false, "result" => "exceed 24 hours date time range");
	}

	private function rebuildGameRecords(&$gameRecords,$extra){
		$new_gameRecords =[];
        foreach($gameRecords as $index => $record) 
        {
        	list($cateId,$category,$match) = $this->processCategory($record);	

			$new_gameRecords[$index]['order_id'] = isset($record['OrderID'])?$record['OrderID']:null;
			$new_gameRecords[$index]['username'] = isset($record['UserName'])?$record['UserName']:null;
			$new_gameRecords[$index]['cate_id'] = $cateId;
			$new_gameRecords[$index]['category'] = $category;
			$new_gameRecords[$index]['league_id'] = isset($record['LeagueID'])?$record['LeagueID']:null;
			$new_gameRecords[$index]['league'] = isset($record['League'])?$record['League']:null;
			$new_gameRecords[$index]['match_id'] = isset($record['MatchID'])?$record['MatchID']:null;
			$new_gameRecords[$index]['match'] = $match?:null;
			$new_gameRecords[$index]['bet_id'] = isset($record['BetID'])?$record['BetID']:null;
			$new_gameRecords[$index]['bet'] = isset($record['Bet'])?$record['Bet']:null;
			$new_gameRecords[$index]['content'] = isset($record['Content'])?$record['Content']:null;
			$new_gameRecords[$index]['result'] = isset($record['Result'])?$record['Result']:null;
			$new_gameRecords[$index]['bet_amount'] = isset($record['BetAmount'])?$record['BetAmount']:null;
			$new_gameRecords[$index]['bet_money'] = isset($record['BetMoney'])?$record['BetMoney']:null;
			$new_gameRecords[$index]['money'] = isset($record['Money'])?$record['Money']:null;
			$status = isset($record['Status'])?$record['Status']:null;
			$new_gameRecords[$index]['status'] = isset($record['Status'])?$record['Status']:null;
			$createAt = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['CreateAt'])));
			$new_gameRecords[$index]['create_at'] = $createAt;
			$new_gameRecords[$index]['update_at'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['UpdateAt'])));
			$new_gameRecords[$index]['start_at'] = isset($record['StartAt'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['StartAt']))):null;
			$new_gameRecords[$index]['end_at'] = isset($record['EndAt'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['EndAt']))):null;

			if($status == "None"){
				$record['ResultAt'] = $record['RewardAt'] = $createAt;
			}
			$new_gameRecords[$index]['result_at'] = isset($record['ResultAt'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['ResultAt']))):null;
			$new_gameRecords[$index]['reward_at'] = isset($record['RewardAt'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['RewardAt']))):null;
			$new_gameRecords[$index]['odds'] = isset($record['Odds'])?$record['Odds']:null;
			$new_gameRecords[$index]['ip'] = isset($record['IP'])?$record['IP']:null;
            $new_gameRecords[$index]['external_uniqueid'] = isset($record['OrderID'])?$record['OrderID']:null;
            $new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
        }
        $gameRecords = $new_gameRecords;
	}

	private function processCategory($data){
		switch ($data['Type']) {
			case "Smart":
				$cateId = isset($data['Player'])?$data['Player']:null;
				$smartTypeGameDesc = [
									   "Smart.LOL.OddEven" => "Hero number single and double",
									   "Smart.LOL.Gender" => "Hero Gender",
									   "Smart.LOL.Magic" => "With or without magic value",
									   "Smart.LOL.Attack" => "Hero Attacks",
									   "Smart.PVP.OddEven" => "Hero number single and double",
									   "Smart.PVP.Gender" => "Hero Gender",
									   "Smart.PVP.Magic" => "With or without magic value",
									   "Smart.PVP.Attack" => "Hero Attacks",
									   "Smart.Dota.OddEven" => "Hero Number Single and Double",
									   "Smart.Dota.Camp" => "Campus",
									   "Smart.Dota.Abilities" => "Hero Properties",
									   "Smart.Dota.Attack" => "Hero Attacks",
									 ];
				$category = "(".$data['Code'].")".$smartTypeGameDesc[$cateId];
				$match = isset($data['Match'])?$data['Match']:null;
				break;

			case "Single":
				$cateId = isset($data['CateID'])?$data['CateID']:null;
				$category = isset($data['Category'])?$data['Category']:null;
				$match = isset($data['Match'])?$data['Match']:null;
				break;

			case "Combo":
				$cateId = "mixparlay";
				$category = "Sports";
				$match = $this->generateMatchDetailsFromMixParlay($data['Details']);
				break;
			
			default:
				$cateId = isset($data['CateID'])?$data['CateID']:null;
				$category = isset($data['Category'])?$data['Category']:null;
				$match = isset($data['Match'])?$data['Match']:null;
				break;
		}
		return [$cateId,$category,$match];
	}

	private function generateMatchDetailsFromMixParlay($matchRawDetails)
	{
		$matchDetails = "";
		if(!empty($matchRawDetails) && is_array($matchRawDetails)){
			foreach ($matchRawDetails as $value) {
				$matchDetails .= $value['Match'].",";
			}
		}
		return $matchDetails;
	}

	public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);

		$record_count = isset($resultArr['info']['RecordCount'])?$resultArr['info']['RecordCount']:0;
		$page_index = isset($resultArr['info']['PageIndex'])?$resultArr['info']['PageIndex']:0;
		$page_size = isset($resultArr['info']['PageSize'])?$resultArr['info']['PageSize']:0;
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array(
			'data_count'=>0,
			'record_count'=>$record_count,
			'page_index'=>$page_index,
			'page_size'=>$page_size,
		);
		if($success){
			$gameRecords = isset($resultArr['info']['list'])?$resultArr['info']['list']:[];

            $extra = ['response_result_id'=>$responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                'aviasport_game_logs',
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
                    $this->CI->original_game_logs_model->updateRowsToOriginal('aviasport_game_logs', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('aviasport_game_logs', $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`aviasport`.`updated_at` >= ?
          AND `aviasport`.`updated_at` <= ?';
        if($use_bet_time){
            $sqlTime='`aviasport`.`create_at` >= ?
          AND `aviasport`.`create_at` <= ?';
        }

        $sql = <<<EOD
			SELECT 
				aviasport.id as sync_index,
				aviasport.response_result_id,
				aviasport.order_id as round,
				aviasport.username,
				aviasport.league,
				aviasport.match,
				aviasport.bet as bet_place,
				aviasport.content as bet_place_content,
				aviasport.bet_amount as bet_amount,
				aviasport.bet_money as valid_bet,
				aviasport.money as result_amount,
				aviasport.create_at as start_at,
				aviasport.reward_at as end_at,
				aviasport.create_at as bet_at,
				aviasport.status,
				aviasport.odds,
				aviasport.cate_id as game_code,
				aviasport.category as game_name,
				aviasport.updated_at,
				aviasport.external_uniqueid,
				aviasport.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM aviasport_game_logs as aviasport
			LEFT JOIN game_description as gd ON aviasport.cate_id = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON aviasport.username = game_provider_auth.login_name 
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

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);
        switch ($status) {
	        case 'none':
	        case 'settlement':
	            $status = Game_logs::STATUS_PENDING;
	            break;
	        case 'revoke':
	            $status = Game_logs::STATUS_REJECTED;
	            break;
	        case 'cancel':
	            $status = Game_logs::STATUS_CANCELLED;
	            break;
	        case 'win':
	        case 'lose':
	        case 'winhalf':
	        case 'losehalf':
	            $status = Game_logs::STATUS_SETTLED;
	            break;
        }
        return $status;
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        $betDetails = [
    		'match_details'=> $row['match'],
    		'bet'=> $row['bet_amount'],
            'yourBet' => $row['bet_place'].' : '.$row['bet_place_content'],
            'isLive' => null,
            'odd' => $row['odds'],
            'htScore'=> null,
            'eventName' => $row['league'],
            'league' => $row['league'],
     	];


        $extra = [
            'odds' => $row['odds'],
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
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getGameRecordsStatus($row['status']),
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $betDetails,
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
        $row['status'] = $this->getGameRecordsStatus($row['status']);
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

	public function logout($playerName, $password = null) {
    	return $this->returnUnimplemented();
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	//can transfer decimal
	/*public function onlyTransferPositiveInteger(){
		return true;
	}*/

}

/*end of file*/