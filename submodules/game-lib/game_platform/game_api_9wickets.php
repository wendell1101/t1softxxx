<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_9wickets extends Abstract_game_api {

    public $originalTable = 'wickets9_game_logs';

    const MD5_FIELDS_FOR_ORIGINAL = [
        "eventTypeName",
        "eventName",
        "eventId",
        "currencyType",
        "userId",
        "betPlaced",
        "betStatus",
        "betId",
        "matchSettledDate",
        "matchAmount",
        "matchStake",
        "matchOddsReq",
        "marketName",
        "profitLoss",
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        "matchAmount",
        "matchStake",
        "matchOddsReq",
        "profitLoss",
    ];

    const MD5_FIELDS_FOR_MERGE = [
        "game_code",
        "player_username",
        "status",
        "start_at",
        "bet_at",
        "end_at",
        "result_amount",
        "matchOddsReq",
        "marketName",
        "bet_result",
        "response_result_id",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        "result_amount",
        "bet_result",
    ];

    const GAME_LOGS_STATUS_INPUT = [
        "MATCHED" => 1,
        "UNMATCHED" => 0,
        "ALL" => -1,
        "SETTLED" => 2,
        "CANCELLED" => 3,
        "VOIDED" => 4,
    ];

    const GAME_LOGS_STATUS_OUTPUT = [
        "Settled",
        "Cancelled",    
        "Voided",
        "Not Settled"
    ];

    const REPORT_TYPE = [
        "EXCHANGE" => 0,
        "SPORTSBOOK" => 2,
        "BOOKMAKER" => 3,
        "FANCY BET" => 7,
    ];

    const STATUS_CODE = [
        "STATUS_FAIL" => "0",
        "STATUS_SUCCESS" => "1",
        "STATUS_INVALID_USERID" => "1005",
        "STATUS_ACTION_COMPLETED" => "1012",
        "STATUS_ACCOUNT_LOCKED" => "1031",
    ];

    // timeZones
    const ASIA_HK = 1; // GMT+8
    const IST = 0; // GMT+5:30 

    const API_getKey = "GetKey";
    const API_queryBetHistoryAllStatus = "QueryBetHistory";
    const API_getCurrentBetsByLastUpdate = "GetCurrentBets";

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->agent_id = $this->getSystemInfo('agent_id', 'testtotma');
        $this->cert = $this->getSystemInfo('cert');
        $this->currency = $this->getSystemInfo('currency');
        $this->timezone = $this->getSystemInfo('timezone', self::ASIA_HK);
        $this->default_lang = $this->getSystemInfo('default_lang', '1'); //1 for English
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->return_url = $this->getSystemInfo('return_url');
        $this->website = $this->getSystemInfo('website', 'WICKETSPRO');
        // $this->reportType = $this->getSystemInfo('reportType', '0');
        $this->reportTypes = $this->getSystemInfo('reportTypes', [0,2,3]);
        $this->gameLobby =  "apiWallet/player/{$this->website}/login?";
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 16);

        $this-> URI_MAP = array(
            self::API_createPlayer => "apiWallet/player/{$this->website}/getKey",
            self::API_getKey => "apiWallet/player/{$this->website}/getKey",
            self::API_logout => "apiWallet/player/{$this->website}/logout",
            self::API_isPlayerExist => "apiWallet/{$this->website}/getProfile",
            self::API_queryPlayerBalance => "apiWallet/{$this->website}/getBalance",
            self::API_depositToGame => "apiWallet/{$this->website}/deposit",
            self::API_withdrawFromGame => "apiWallet/{$this->website}/withdraw",
            self::API_queryTransaction => "apiWallet/{$this->website}/checkBalanceOperation",
          
            self::API_queryBetHistoryAllStatus => "apiWallet/{$this->website}/queryBetHistoryForAllStatus",
            self::API_getCurrentBetsByLastUpdate => "apiWallet/{$this->website}/queryCurrentBetsByLastUpdateDate",
        );

    }

	public function getPlatformCode(){
		return WICKETS9_API;
    }

    public function generateUrl($apiName, $params) {
        $apiUri = $this->URI_MAP[$apiName];
        $url = $this->api_url . $apiUri;
        $this->CI->utils->debug_log('9Wickets (generateUrl)', $url);		

        return $url;
    }

    protected function getHttpHeaders($params){
		$this->CI->utils->debug_log('9Wickets (getHttpHeaders)', $params);		

		$headers = [];
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';
    }

    protected function customHttpCall($ch, $params) {        
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }

    public function processResultBoolean($responseResultId, $resultArr)
    {
        $success = false;

        if(($resultArr['status'] == self::STATUS_CODE["STATUS_ACTION_COMPLETED"] || $resultArr['status'] == self::STATUS_CODE["STATUS_SUCCESS"])){
            $success = true;
        }

        if (!$success) 
        {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('9Wickets API got error ', $responseResultId, 'result', $resultArr);
        }
    
        return $success;
    }
 
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$this->CI->utils->debug_log('9Wickets (createPlayer)', $playerName);	

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $extra = [
            'prefix' => $this->prefix_for_username,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
        ];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'cert' => $this->cert,
            'currency' => $this->currency,
            'userId' => $gameUsername,
            'agent' => $this->agent_id,
            'timeZone' => $this->timezone,
			'userName' => $gameUsername
		);
		
		$this->CI->utils->debug_log('9Wickets (createPlayer) :', $params);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params){
		$this->CI->utils->debug_log('9Wickets (processResultForCreatePlayer)', $params);	

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array(
			'response_result_id' => $responseResultId,
            'status' => $resultArr['status'],
            'key' => $resultArr['key'],
		);

        $result['key'] = NULL;
		if($success){
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['key'] = isset($resultArr['key']) ? $resultArr['key'] : NULL;
		}

		return array($success, $result);
	}
    
    public function isPlayerExist($playerName)
    {
        $username = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($username);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

        $params = array(
            "cert" => $this->cert,
            "userId" => empty($username)?$playerName:$username,
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array();
            if ($success) {
                $result = array(
                    "success" => true,
                    "exists" => true
                );
            } 
            else {
                switch ($resultArr['status']) {
                    case self::STATUS_CODE["STATUS_SUCCESS"]:
                    case self::STATUS_CODE["STATUS_ACCOUNT_LOCKED"]:
                        $result = array(
                            "success" => true,
                            "exists" => true
                        );
                        break;
                    case self::STATUS_CODE["STATUS_INVALID_USERID"]:
                        $result = array(
                            "success" => true,
                            "exists" => false
                        );
                        break;
                    default:
                        $result = array(
                            "success" => false,
                            "exists" => null
                        );
                }
            }
            
            return array($success, $result);
    }   

    public function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            "cert" => $this->cert,
			"userId" => $gameUsername,
        );

        return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        return array($success, $resultArr);
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
            "cert" => $this->cert,
            "alluser" => 0, //single user
			"userIds" => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

    public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('9Wickets (processResultForQueryPlayerBalance)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];
		if($success){
			$result['balance'] = $this->gameAmountToDB(($resultArr['results']['0']['balance']));
		}

		return array($success, $result);
	}

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('9Wickets (depositToGame)', $playerName);	

        $amount = $this->dBtoGameAmount($amount);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id,
        );

		$params = array(
			'userId' => $gameUsername,
			'balance' => $amount,
			'cert' => $this->cert,
			'tsCode' => $transfer_secure_id,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

    public function processResultForDepositToGame($params) {
		$this->CI->utils->debug_log('9Wickets (processResultForDepositToGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null){
        $this->CI->utils->debug_log('9Wickets (withdrawFromGame)', $playerName);	

        $withdrawType = 0; //single user
        $amount = $this->dBtoGameAmount($amount);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
      
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id,
        );

		$params = array(
			'userId' => $gameUsername,
			'balance' => $amount,
            'withdrawtype' => $withdrawType,
			'cert' => $this->cert,
			'tsCode' => $transfer_secure_id,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

    public function processResultForWithdrawFromGame($params) {
		$this->CI->utils->debug_log('9Wickets (processResultForWithdrawFromGame)', $params);	

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function queryTransaction($transactionId, $extra){
        $this->CI->utils->debug_log('9Wickets (queryTransaction)', $transactionId, $extra);	
		
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
            "cert" => $this->cert,
            "tsCode" => $transactionId,
			"userId" => $gameUsername,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params){
		$this->CI->utils->debug_log('9Wickets (processResultForQueryTransaction)', $params);	

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
	
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($resultArr['status'] = self::STATUS_CODE["STATUS_ACTION_COMPLETED"]){
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			if(empty($resultArr)){
				$result['reason_id'] = self::REASON_UNKNOWN;
				$result['status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
		} else {
			$result['reason_id'] = self::REASON_UNKNOWN;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($result);
	}

    //getKey for gameLaunch
    public function getKey($playerName, $extra){
        $this->CI->utils->debug_log('9Wickets (getKey)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'cert' => $this->cert,
            'currency' => $this->currency,
            'userId' => $gameUsername,
            'agent' => $this->agent_id,
            'timeZone' => $this->timezone,
			'userName' => $gameUsername
		);
		
		$this->CI->utils->debug_log('9Wickets (getKey) :', $params);

		return $this->callApi(self::API_getKey, $params, $context);
    }

    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $getKey = $this->getKey($playerName, $extra);
        $this->utils->debug_log("9Wickets: (QueryForwardGame) key: ", $getKey);
        $key = isset($getKey['key']) ? $getKey['key'] : NULL;
        
        if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])){
            $this->return_url = $extra['extra']['t1_lobby_url'];
        }else if (!empty($this->return_url)){ 
            $params['returnUrl'] = $this->return_url;
        }else {
            $this->return_url = $this->getHomeLink();
        }
        
        $params = array(
            "cert" => $this->cert,
            "userId" => $gameUsername,
            "key" => $key,
			"returnUrl" => $this->return_url,
        );
        
        $gameUrl = $this->api_url . $this->gameLobby. http_build_query($params);
        $this->utils->debug_log("9Wickets: (QueryForwardGame) gameUrl: ", $gameUrl);

        redirect($gameUrl);
    }

    //for getting unsettled bets
    public function getCurrentBetsByLastUpdate($token){
        $this->CI->utils->debug_log('9Wickets (getCurrentBetsByLastUpdate)', $token, $this->originalTable);	

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $lastUpdateDate = strtotime($queryDateTimeStart) * 1000; //date to ms
        $this->CI->utils->debug_log('9Wickets (milliseconds)', $lastUpdateDate);	

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $queryDateTimeStart,
            'endDate' => $queryDateTimeEnd
        );

        foreach($this->reportTypes as $report_type){
            $page = 1;
            $done = false;
            while (!$done){
                $params = array(
                    'cert' => $this->cert,
                    'lastUpdateDate' => $lastUpdateDate,
                    "isTxnDetail" => 0, //Default
                    "timeZone" => $this->timezone,
                    "pageNumber" => $page,
                    "isDateISO8601" => 0, //Default
                    "reportType" => $report_type,
                );
        
                    $unsettled_result = $this->callApi(self::API_getCurrentBetsByLastUpdate, $params, $context);
                    $this->CI->utils->debug_log('9wickets unsettled_result', $unsettled_result);
                    $done = true;
                
                    if ($unsettled_result && $unsettled_result['success'])
                    {   
                        $total_page = isset($unsettled_result['all']['page']) ? $unsettled_result['all']['page'] : NULL;
                        $done = $page >= $total_page;
                        $page += 1;
                        $this->CI->utils->debug_log("Current Bet Result", 'page: ', $page, 'total_page:', $total_page, 'done', $done, 'result', $unsettled_result);
                    }
                    if ($done)
                    {
                        $success = true;
                    }
            }
        }
        return array('success' => $success, $unsettled_result);
    }

    //for getting settled bets
    public function queryBetHistoryAllStatus($token){
        $this->CI->utils->debug_log('9Wickets (queryBetHistoryAllStatus)', $token, $this->originalTable);	

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        
        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i");
		$queryDateTimeEnd = $endDateTime->format('Y-m-d H:i');
    
        $this->CI->utils->debug_log('queryBetHistoryAllStatus -------------------------------------> ', "startDateTime: " . $startDateTime->format('Y-m-d H:i:s'), "endDateTime: " . $endDateTime->format('Y-m-d H:i:s'), "startDate: " . $startDate->format('Y-m-d H:i:s'), "endDate: " . $endDate->format('Y-m-d H:i:s'));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $queryDateTimeStart,
            'endDate' => $queryDateTimeEnd
        );

        foreach($this->reportTypes as $report_type){
            $page = 1;
            $done = false;
            while (!$done){
                $params = array(
                    'cert' => $this->cert,
                    'userId' => "",//empty means for all players
                    'startDate' => $queryDateTimeStart,
                    'endDate' => $queryDateTimeEnd,
                    'betStatus' => self::GAME_LOGS_STATUS_INPUT["ALL"],
                    "isTxnDetail" => 0, //Default
                    "pageNumber" => $page,
                    "reportType" => $report_type,
                );
    
                    $settled_result = $this->callApi(self::API_queryBetHistoryAllStatus, $params, $context);
                    $this->CI->utils->debug_log('9wickets settled_result', $settled_result);
                    $done = true;

                    if ($settled_result && $settled_result['success'])
                    {   
                        $total_page = isset($settled_result['settled']['totalPage']) ? $settled_result['settled']['totalPage'] : NULL;
                        $done = $page >= $total_page;
                        $page += 1;
                        $this->CI->utils->debug_log("Bet History Result", 'page: ', $page, 'total_page:', $total_page, 'done', $done, 'result', $settled_result);
                    }
                    if ($done)
                    {
                        $success = true;
                    }
            }
        }
        return array('success' => $success);
    }

    public function syncOriginalGameLogs($token = false){
        $this->queryBetHistoryAllStatus($token); 
        $this->getCurrentBetsByLastUpdate($token);

        return array('success' => true);
    }

    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('9Wickets AllGameRecords', $resultArr);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = ['data_count' => 0];

        $gameRecords = !empty($resultArr['matched']['resultList'])?$resultArr['matched']['resultList']:$resultArr['settled']['resultList'];

            if($success && !empty($gameRecords)){	
                $extra = ['response_result_id' => $responseResultId];
                $gameRecords = $this->rebuildGameRecords($gameRecords,$extra);
                $this->CI->utils->debug_log('9Wickets rebuildGR', $gameRecords);
                
                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->originalTable,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                unset($gameRecords);

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId], $this->originalTable);
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId], $this->originalTable);
                }
                unset($updateRows);
        
                $result['all']['page'] = !empty($resultArr['totalPage'])?$resultArr['totalPage']:$resultArr['settled']['totalPage'];
                $this->CI->utils->debug_log('9Wickets gameRecords4',  $result['all']['page']);
		}

		return array($success, $result);
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name) 
    {
        $table_name = $this->originalTable;

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

    public function rebuildGameRecords($gameRecords, $extra)
    {
        foreach($gameRecords as $index => $record) {
          $new_game_records = array();
          $new_game_records['eventId']       =    isset($record['eventId'])? ($record['eventId']) : null;
          $new_game_records['eventName']       =    isset($record['eventName'])? ($record['eventName']) : null;
          $new_game_records['eventTypeName']       =    isset($record['eventTypeName'])? ($record['eventTypeName']) : null;
          $new_game_records['currencyType']     =    isset($record['currencyType'])? $record['currencyType'] : null;
          $new_game_records['userId']     =    isset($record['userId'])? $record['userId'] : null;
          $new_game_records['betId']     =    isset($record['betId'])? $record['betId'] : null;
          $new_game_records['betStatus']       =    isset($record['betStatus'])? ($record['betStatus']) : null;
          $new_game_records['matchAmount']     =    isset($record['matchAmount'])? $record['matchAmount'] : null;
          $new_game_records['matchStake']     =    isset($record['matchStake'])? $record['matchStake'] : null;
          $new_game_records['matchOddsReq']     =    isset($record['matchOddsReq'])? $record['matchOddsReq'] : null;
          $new_game_records['marketName']     =    isset($record['marketName'])? $record['marketName'] : null;
          $new_game_records['profitLoss']   =    isset($record['profitLoss'])? $record['profitLoss'] : null;
          $new_game_records['betPlaced']     =    isset($record['betPlaced'])? $this->gameTimeToServerTime($record['betPlaced']) : null;
          $new_game_records['matchSettledDate']     =    isset($record['matchSettledDate'])? $this->gameTimeToServerTime($record['matchSettledDate']) : null;
        
          //extra info from SBE
          $new_game_records['external_uniqueid'] = isset($record['betId'])? $record['betId'] : null;
          $new_game_records['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
          $new_game_records['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
          $new_game_records['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
          $newGameRecords[] = $new_game_records;
        }
            return $newGameRecords;
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = '`original`.`betPlaced` >= ? AND `original`.`betPlaced` <= ?';

        if($use_bet_time)
        {
            $sqlTime = '`original`.`betPlaced` >= ? AND `original`.`betPlaced` <= ?';
        }

        $this->CI->utils->debug_log('9wickets sqlTime ===>', $sqlTime);
        
        $sql = <<<EOD
        SELECT
            original.id as sync_index,
            original.userId as player_username,
            original.eventId,
            original.betId,
            original.eventTypeName,
            original.eventName,
            original.betStatus as status,
            original.matchStake as bet_amount,
            original.matchAmount as current_bet,
            original.matchOddsReq,
            original.marketName,
            original.profitLoss as result_amount,
            original.betPlaced as start_at,
            original.betPlaced as bet_at,
            original.matchSettledDate as end_at,
            original.response_result_id,
            original.external_uniqueid,
            original.updated_at,
            original.md5_sum,
            game_provider_auth.player_id,
	        gd.id as game_description_id,
	        gd.english_name as game_description_name,
            gd.game_code as game_code,
	        gd.game_type_id
        FROM {$this->originalTable} as original
            LEFT JOIN game_description as gd ON original.eventTypeName = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.userId = game_provider_auth.login_name
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

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}
        
        $extra = [ 'odds' => $row['matchOddsReq'] ];
        
        return [

            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : $this->gameAmountToDB($row['current_bet']),
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => null,
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : null,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag'                      => Game_logs::FLAG_GAME,
            'status'                    => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['betId']) ? $row['betId'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => [],
            'extra'                     => $extra,
            //from exists game logs
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        
        }
                
        $row['status'] = $this->getNoteDetailsForGameLogs($row);

        if ($row['status'] != Game_logs::STATUS_SETTLED){
            $row['end_at'] = isset($row['start_at']) ? $row['start_at'] : $row['bet_at'];
            $this->CI->utils->debug_log('9Wickets (end_at preProcess)', $row['end_at']);	
        }
    }

    private function getNoteDetailsForGameLogs($row)
    {
        $status = $row['status'];

        switch($status)
        {
            case "Not Settled":
                $game_logs_status = Game_logs::STATUS_PENDING;
                break;
            case "Settled":
                $game_logs_status = Game_logs::STATUS_SETTLED;
                break;
            case "Cancelled":
                $game_logs_status = Game_logs::STATUS_REJECTED;
                break;
            case "Voided":
                $game_logs_status = Game_logs::STATUS_VOID;
                break;
            default:
                $game_logs_status = Game_logs::STATUS_PENDING;
                break;
        }

        return $game_logs_status;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['eventTypeName'], $row['eventTypeName']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

}
/*end of file*/