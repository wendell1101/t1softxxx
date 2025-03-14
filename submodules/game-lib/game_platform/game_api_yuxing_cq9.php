<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API DOCS NAME: Transfer Wallet GameBoy
	* Document Number: none
	* API Doc: https://hackmd.io/s/ryWBwyI4M#Header
	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_yuxing_cq9 extends Abstract_game_api {
    //? this api is copied over from game_api_cq9 and modified to cater to yuxing_cq9

    const ORIGINAL_GAMELOGS_TABLE = 'yuxing_cq9_game_logs';

    const API_getYuxingTrialGameList = '/GetTrial';

	// const POST = 'POST'; No post methods in docs
	const GET = 'GET';
	const API_SUCCESS_RESPONSE = '0'; //? any other is error

    // player status
    const PLAYER_NORMAL = 0;
    const PLAYER_LOGIN_DISABLED = 1;
    const PLAYER_CHIP_IN_DISABLED = 2;

    const MD5_FIELDS_FOR_GAME_LOGS = [
        'roundid',
        'money',
        'tableid',
        'betamount',
        'validbetamount'
    ];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = [
        'betamount',
        'validbetamount',
        'money'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'begintime',
        'createtime',
        'endtime',
        'money',
        'validbetamount',
        'betamount',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'money',
        'validbetamount',
        'betamount',
    ];

	public function __construct() {
		parent::__construct();
        $this->method = self::GET;
        $this->channel = $this->getSystemInfo('channel', 'TS');
		$this->api_url = $this->getSystemInfo('url') . '/ChannelApi/API/' . $this->channel;
		$this->api_token = $this->getSystemInfo('api_token');
		$this->agent = $this->getSystemInfo('agent');
		$this->currency = $this->getSystemInfo('currency', 'CNY');
		$this->language = $this->getSystemInfo('language', 'en');

		$this->method = "POST";
		$this->URI_MAP = array(
			self::API_createPlayer => '/CreateUser',
            self::API_blockPlayer => '/SetUserAvailable',
            self::API_unblockPlayer => '/SetUserAvailable',
			self::API_queryPlayerBalance => '/GetBalance',
			self::API_isPlayerExist => '/getUserAvailable', //! provider said to ignore this method
			self::API_depositToGame => '/Deposit',
			self::API_withdrawFromGame => '/Withdraw',
			self::API_queryTransaction => '/GetTrans',
            self::API_getYuxingTrialGameList => '/GetTrial',
			self::API_queryForwardGame => '/LoginWithChannel',
			self::API_syncGameRecords => '/GetRecordByTime',
		);
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
	}

	public function getPlatformCode() {
		return YUXING_CQ9_GAME_API;
	}

	// protected function customHttpCall($ch, $params) {
	// 	if($this->method == self::GET){
	// 		curl_setopt($ch, CURLOPT_GET, TRUE);
	// 		curl_setopt($ch, CURLOPT_GETFIELDS, http_build_query($params));
	// 	}
	// }

	protected function getHttpHeaders($params){
		$headers = array(
			'Authorization' => 'Bearer ' . $this->api_token
		);

		return $headers;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];


        switch ($apiName){
            case self::API_syncGameRecords:
                $url = $this->api_url.$apiUri.'?'. str_replace('%3A', ':', http_build_query($params, '', '&', PHP_QUERY_RFC3986));
                break;
            case self::API_getYuxingTrialGameList:
                $url = $this->api_url.$apiUri;
                break;
            default:
                $url = $this->api_url.$apiUri.'?'.http_build_query($params);
        }

		return $url;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(isset($resultArr['state']) && $resultArr['state'] == self::API_SUCCESS_RESPONSE){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('YUXING_CQ9_GAME_API API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
			'username' => $gameUsername
		);

		$params = array(
			'username' => $gameUsername,
			'agent' => $this->agent,
			'currency' => $this->currency,
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
			"player" => $gameUsername,
			"exists" => false
		);

		if($success){
			# update flag to registered = truer
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
	        $result["exists"] = true;
		}else{
			$result["message"] = @$resultArr["message"];
		}

		return array($success, $result);
	}


	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			'username' => $gameUsername
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();
		if($success){
            if(isset($resultArr['value']['balance'])) {
                $result['balance'] = $this->gameAmountToDB(floatval($resultArr['value']['balance']));
            }
            else {
                $success = false;
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
   	 	$external_transaction_id =  $this->generateYuxingSerial('deposit');
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id,
        );

		$params = array( 
			'username' => $gameUsername,
			'serial' => $external_transaction_id,
			'amount' => $amount
		);



		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$serial = $this->getVariableFromContext($params, 'serial');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'serial' => $serial,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {			
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
    //             $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['status']['code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']= $error_code;
        }

        return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id =  $this->generateYuxingSerial('withdraw');
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array( 
			'username' => $gameUsername,
			'serial' => $external_transaction_id,
			'amount' => $amount
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$serial = $this->getVariableFromContext($params, 'serial');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'serial'=>$serial,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
    //         if ($playerId) {
	   //          $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
    //         } else {
    //             $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
    //             $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         }
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_code = @$resultArr['status']['code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']= $error_code;
        }

        return array($success, $result); 
	}

	public function queryTransaction($external_transaction_id, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $external_transaction_id,
		);

		$params = array(
            'username' => $gameUsername,
			'serial' => $external_transaction_id
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'state'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=> $external_transaction_id
		);

		if($success){
			switch ($resultArr['state']) {
				case '0':
					$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
					break;
			}
		}else{
			$error_code = @$resultArr['state'];
            $result['reason_id']= $error_code;
			$result['state'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );



		$params = array( 
			'username' => $gameUsername,
			'available' => '1' //? disables login
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);


    }

    public function processResultForBlockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $success = $this->blockUsernameInDB($gameUsername);

		return array("success" => true);
    }


	public function unblockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnblockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

		$params = array( 
			'username' => $gameUsername,
			'available' => '0' //? normal
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $success = $this->unblockUsernameInDB($gameUsername);

		return array("success" => true);
    }


    public function login($playerName, $extra = null) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName
        );

        

		$params = array(
			'username' => $gameUsername,
			'language' => $this->language
		);


    	return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success){
			$result['game_url'] = $resultArr['value'];
		}

		return array($success, $result);
	}	


    //? according to docs: only chinese and english are available
	public function getLauncherLanguage($language){
		switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
            case "en-us":
            case "en_us":
                return "en";
                break;
            case Language_function::INT_LANG_CHINESE:
            case "zh-cn":
            case "zh_cn":
                return "zh-cn";
                break;
            default:
                return "en";
                break;
        }
    }


    public function getYuxingTrialGameList(){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetYuxingTrialGameList',
        );

		return $this->callApi(self::API_getYuxingTrialGameList, [], $context);
	}

	public function processResultForGetYuxingTrialGameList($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];
		if($success){
			$result['url'] = $resultArr['value'];
		}

		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        if (isset($extra['language'])){
            $this->language = $this->language ? $this->language : $this->getLauncherLanguage($extra['language']);
        }

        $params = array(
            'username' => $gameUsername,
            'language' => $this->language
        );

        return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        $result = array();
        $result['url'] = '';
        
       	if($success){
			$result['url'] =  $resultArr['value'];
       	}

        return array($success, $result);
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());
        
        $result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+12 hours', function($startDate, $endDate) {

			$queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
		    $queryDateTimeEnd = $endDate->format('Y-m-d H:i:s');
            $page = 1;

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
                'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
			);

            $params = array(
				'starttime' => $queryDateTimeStart,
				'endtime' => $queryDateTimeEnd,
				'pageindex' => $page
			);

			sleep($this->common_wait_seconds);

			return $this->callApi(self::API_syncGameRecords, $params, $context);
		});

	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('yuxing_cq9_game_logs','original_game_logs_model'));

        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = ['data_count' => 0];

        $responseRecords = isset($resultArr['value']['gameRecords']) ? $resultArr['value']['gameRecords'] : [];
        $gameRecords = !empty($responseRecords) ? $this->CI->yuxing_cq9_game_logs->getAvailableRows($responseRecords): [];
		
        if($success && !empty($gameRecords)){	
            $extra = ['response_result_id' => $responseResultId];
            $gameRecords = $this->rebuildGameRecords($gameRecords,$extra);
            
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_GAME_LOGS,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS
            );

            unset($gameRecords);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($updateRows);

		}

		return array($success, $result);
	}

    private function rebuildGameRecords($game_records, $extra) {

        foreach($game_records as $i => $record) {

            $new_game_records[$i]['id'] =           isset($record['id'])? $record['id'] : null;
            $new_game_records[$i]['status'] =           isset($record['status'])? $record['status'] : null;
            $new_game_records[$i]['username'] =         isset($record['username'])? $this->removeChannelPrefix($record['username']) : null;
            $new_game_records[$i]['channel'] =          isset($record['channel'])? $record['channel'] : null;
            $new_game_records[$i]['agent'] =            isset($record['agent'])? $record['agent'] : null;
            $new_game_records[$i]['createtime'] =       isset($record['createtime'])? $record['createtime'] : null;
            $new_game_records[$i]['groupfor'] =         isset($record['groupfor'])? $record['groupfor'] : null;
            $new_game_records[$i]['gametype'] =         isset($record['gametype'])? $record['gametype'] : null;
            $new_game_records[$i]['roomid'] =           isset($record['roomid'])? $record['roomid'] : null;
            $new_game_records[$i]['tableid'] =          isset($record['tableid'])? $record['tableid'] : null;
            $new_game_records[$i]['roundid'] =          isset($record['roundid'])? $record['roundid'] : null;
            $new_game_records[$i]['betamount'] =        isset($record['betamount'])? $record['betamount'] : null;
            $new_game_records[$i]['validbetamount'] =   isset($record['validbetamount'])? $record['validbetamount'] : null;
            $new_game_records[$i]['betpoint'] =         isset($record['betpoint'])? $record['betpoint'] : null;
            $new_game_records[$i]['odds'] =             isset($record['odds'])? $record['odds'] : null;
            $new_game_records[$i]['money'] =            isset($record['money'])? $record['money'] : null;
            $new_game_records[$i]['servicemoney'] =     isset($record['servicemoney'])? $record['servicemoney'] : null;
            $new_game_records[$i]['begintime'] =        isset($record['begintime'])? $record['begintime'] : null;
            $new_game_records[$i]['endtime'] =          isset($record['endtime'])? $record['endtime'] : null;
            $new_game_records[$i]['isbanker'] =         isset($record['isbanker'])? $record['isbanker'] : null;
            $new_game_records[$i]['gameinfo'] =         isset($record['gameinfo'])? $record['gameinfo'] : null;
            $new_game_records[$i]['gameresult'] =       isset($record['gameresult'])? $record['gameresult'] : null;
            $new_game_records[$i]['jp'] =               isset($record['jp'])? $record['jp'] : null;
            $new_game_records[$i]['info1'] =            isset($record['info1'])? $record['info1'] : null;

            //extra info from SBE
            $new_game_records[$i]['external_uniqueid'] = isset($record['id'])? $record['id'] : null;
            $new_game_records[$i]['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
            $new_game_records[$i]['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
            $new_game_records[$i]['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
        }

        return $new_game_records;
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name) {

        $dataCount = 0;
        if (!empty($rows)) {
            foreach ($rows as $key => $record) {
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


    public function syncMergeToGameLogs($token) {

        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {

        $sql = <<<EOD
            SELECT
            yuxing_cq9_game_logs.id,
            yuxing_cq9_game_logs.status,
            yuxing_cq9_game_logs.username,
            yuxing_cq9_game_logs.channel,
            yuxing_cq9_game_logs.agent,
            yuxing_cq9_game_logs.createtime,
            yuxing_cq9_game_logs.groupfor,
            yuxing_cq9_game_logs.gametype,
            yuxing_cq9_game_logs.roomid,
            yuxing_cq9_game_logs.tableid,
            yuxing_cq9_game_logs.roundid,
            yuxing_cq9_game_logs.betamount,
            yuxing_cq9_game_logs.validbetamount,
            yuxing_cq9_game_logs.betpoint,
            yuxing_cq9_game_logs.odds,
            yuxing_cq9_game_logs.money,
            yuxing_cq9_game_logs.servicemoney,
            yuxing_cq9_game_logs.begintime,
            yuxing_cq9_game_logs.endtime,
            yuxing_cq9_game_logs.isbanker,
            yuxing_cq9_game_logs.gameinfo,
            yuxing_cq9_game_logs.gameresult,
            yuxing_cq9_game_logs.jp,
            yuxing_cq9_game_logs.info1,
            yuxing_cq9_game_logs.md5_sum,

            yuxing_cq9_game_logs.external_uniqueid,
            yuxing_cq9_game_logs.response_result_id,
            game_provider_auth.player_id
            FROM
            `yuxing_cq9_game_logs`
            JOIN `game_provider_auth`
                ON (
                `yuxing_cq9_game_logs`.`username` = `game_provider_auth`.`login_name`
                )
            WHERE (
                yuxing_cq9_game_logs.createtime >= ?
                AND yuxing_cq9_game_logs.createtime <= ?
            )

EOD;

        $params = [
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $extra = [
            'table' =>  $row['roundid'],
        ];

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => isset($row['game_type']) ? $row['game_type'] : null,
                'game'                  => isset($row['game_name']) ? $row['game_name'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['username']) ? $row['username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['validbetamount']) ? $row['validbetamount'] : null,
                'result_amount'         => isset($row['money']) ? $row['money'] : null,
                'bet_for_cashback'      => null,
                'real_betting_amount'   => isset($row['validbetamount']) ? $row['validbetamount'] : null,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => 0, // no after_balance implemented with provider. v20210922
            ],
            'date_info' => [
                'start_at'              => isset($row['begintime']) ? $row['begintime'] : null,
                'end_at'                => isset($row['endtime']) ? $row['endtime'] : null,
                'bet_at'                => isset($row['createtime']) ? $row['createtime'] : null,
                'updated_at'            => $this->CI->utils->getNowForMysql(),
            ],
            'flag'                      => Game_logs::FLAG_GAME,
            'status'                    => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['roundid']) ? $row['roundid'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => null,
                'sync_index'            => null,
                'bet_type'              => null
            ],
            'bet_details'               => isset($row['gametype']) && isset($row['gameinfo']) ? $this->translateGameInfo($row['gametype'], $row['gameinfo']) : null,
            'extra'                     => $extra,
            //from exists game logs
            'game_logs_id'              =>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'     =>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

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
		$game_name = isset($row['gametype']) ? $this->getYuxingGameName(isset($row['gametype'])) : null;
		$external_game_id = isset($row['gametype']) ? $row['gametype'] : null;
        
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}



    public function generateYuxingSerial($action){
        //? serial; (Rules: [Channel Name + action + Random combination of letter & number], the length must be within 30 characters.)
        //? action can be withdraw or deposit
        return $this->channel . $action . uniqid();
    }

    public function removeChannelPrefix($username){
        $username = preg_replace('/' . $this->channel . '/', '', $username, 1);
        return $username;
    }


    //* Games Information

    const GAME_TYPE_BACCARAT = 1;
    const GAME_TYPE_DRAGON_AND_TIGER_POKER = 2;
    const GAME_TYPE_ROULETTE = 3;
    const GAME_TYPE_POKER_BULL = 5;

    public function getYuxingGameName($gametype){
        $game_name = '';
        switch($gametype){
            case self::GAME_TYPE_BACCARAT:
                $game_name = 'Baccarat';
                break;
            case self::GAME_TYPE_DRAGON_AND_TIGER_POKER:
                $game_name = 'Dragon & Tiger Poker';
                break;
            case self::GAME_TYPE_ROULETTE:
                $game_name = 'Roulette';
                break;
            case self::GAME_TYPE_POKER_BULL:
                $game_name = 'Poker Bull';
                break;
        }
        return $game_name;
    }


    /**
     * This function translates the provider's gameinfo column into a json-formatted string appropriate for SBE bet_details
     * based on serial specification given by the provider. v20210922
     * 
     *  @param string $gametype - taken from provider's original logs
     *  @param string $gameinfo - taken from provider's original logs
     *  @return string $bet_details - json formatted translated bet details according to $gametype
     */
    public function translateGameInfo($gametype, $gameinfo){
        $bet_details = '';
        switch ($gametype){
            case self::GAME_TYPE_BACCARAT:
                $bet_details = $this->convertBaccaratInfo($gameinfo);
                break;
            case self::GAME_TYPE_DRAGON_AND_TIGER_POKER:
                $bet_details = $this->convertDragonTigerInfo($gameinfo);
                break;
            case self::GAME_TYPE_ROULETTE:
                $bet_details['Result'] = $gameinfo;
                break;
            case self::GAME_TYPE_POKER_BULL:
                $bet_details = $this->convertPokerBullInfo($gameinfo);
                break;
        }
        return json_encode($bet_details);
    }

    public function convertSuit($number){
        $suit = '';
        switch($number){
            case 1:
                $suit = '♠';
                break;
            case 2:
                $suit = '♥';
                break;
            case 3:
                $suit = '♣';
                break;
            case 4:
                $suit = '♦';
                break;
        }
        return $suit;
    }

    public function convertCardNumber($number){
        switch($number){
            case 1:
                $number = 'A';
                break;
            case 11:
                $number = 'J';
                break;
            case 12:
                $number = 'Q';
                break;
            case 13:
                $number = 'K';
                break;
        }
        return $number;
    }

    public function getCardFromNumbers($card_number, $suit_number){
        return $this->convertCardNumber($card_number) . $this->convertSuit($suit_number);
    }

    public function convertBaccaratInfo($gameinfo){
        $baccarat_info_array = explode(';', $gameinfo);
        $converted_game_info = [];
        foreach($baccarat_info_array as $key => $baccarat_info){
            $baccarat_info = explode('-', $baccarat_info);
            $card = $this->getCardFromNumbers($baccarat_info[0], $baccarat_info[1]);
            if($key % 2 == 1){
                $converted_game_info['Banker'][] = $card;
            } else {
                $converted_game_info['Player'][] = $card;
            }
        }
        return $converted_game_info;
    }

    public function convertDragonTigerInfo($gameinfo){
        $dragon_and_tiger_info_array = explode(';', $gameinfo);
        $converted_game_info = [];
        foreach($dragon_and_tiger_info_array as $key => $dragon_and_tiger_info){
            $dragon_and_tiger_info = explode('-', $dragon_and_tiger_info);
            $card = $this->getCardFromNumbers($dragon_and_tiger_info[0], $dragon_and_tiger_info[1]);
            if($dragon_and_tiger_info[2] == 1){
                $converted_game_info['Dragon'][] = $card;
            } else {
                $converted_game_info['Tiger'][] = $card;
            }
        }
        return $converted_game_info;
    }

    public function convertPokerBullInfo($gameinfo){
        $poker_bull_info_array = explode(';', $gameinfo);
        $converted_game_info = [];
        foreach($poker_bull_info_array as $key => $poker_bull_info){
            $poker_bull_info = explode('-', $poker_bull_info);
            $card = $this->getCardFromNumbers($poker_bull_info[0], $poker_bull_info[1]);

            if($key == 0){
                $converted_game_info['First Card'][] = $card;
            } else if($key >= 1 && 5 >= $key){
                $converted_game_info['Banker'][] = $card;
            } else if($key >= 6 && 10 >= $key){
                $converted_game_info['Player 1'][] = $card;
            } else if($key >= 11 && 15 >= $key){
                $converted_game_info['Player 2'][] = $card;
            } else if($key >= 16 && 20 >= $key){
                $converted_game_info['Player 3'][] = $card;
            } 
        }
        return $converted_game_info;
    }



}

/*end of file*/