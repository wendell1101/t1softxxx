<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_common_nttech extends Abstract_game_api {
	const SUCCESS_CODE = 1;	
	const DESKTOP_PLATFORM_TYPE = 0;
	const MOBILE_PLATFORM_TYPE = 1;
	const SYNC_GAME_LOG_START_PAGE = 1;
	const SYNC_GAME_LOG_DEFAULT_PAGE_SIZE = 2000;
	const ORIGINAL_GAMELOGS_TABLE = 'nttech_game_logs';
	const API_getBaccaratTransactionByLastUpdateTime = 'getBaccaratTransactionByLastUpdateTime';
	const API_getLongHuTransactionByLastUpdateTime = 'getLongHuTransactionByLastUpdateDate'; // game type Dragon Tiger is belongs here
	const API_getSicTransactionByLastUpdateTime =  'getSicTransactionByLastUpdateDate';
	const API_getRouTransactionByLastUpdateTime = 'getRouTransactionByLastUpdateDate';
	const API_getRBSicTransactionByLastUpdateDate = 'getRBSicTransactionByLastUpdateDate';

	const API_getSettledMyTransactionsByBetTime = 'getSettledMyTransactionsByBetTime';
	const API_getSettledLongHuTransactionsByBetTime = 'getSettledLongHuTransactionsByBetTime';
	const API_getSettledSicTransactionsByBetTime = 'getSettledSicTransactionsByBetTime';
	const API_getSettledFpcTransactionsByBetTime = 'getSettledFpcTransactionsByBetTime';
	const API_getSettledRouTransactionsByBetTime = 'getSettledRouTransactionsByBetTime';
	const API_getSettledRBSicTransactionsByBetTime = 'getSettledRBSicTransactionsByBetTime';

	const API_getSettledArchiveMyTransactionsByBetTime = 'getSettledArchiveMyTransactionsByBetTime';
	const API_getSettledArchiveLongHuTransactionsByBetTime = 'getSettledArchiveLongHuTransactionsByBetTime';
	const API_getSettledArchiveSicTransactionsByBetTime = 'getSettledArchiveSicTransactionsByBetTime';
	const API_getSettledArchiveFpcTransactionsByBetTime = 'getSettledArchiveFpcTransactionsByBetTime';
	const API_getSettledArchiveRouTransactionsByBetTime = 'getSettledArchiveRouTransactionsByBetTime';
	const API_getSettledArchiveRBSicTransactionsByBetTime = 'getSettledArchiveRBSicTransactionsByBetTime';

    const API_getTransactionsByLastUpdateDate = 'getTransactionsByLastUpdateDate'; // YL
    const API_logoutAllPlayer = 'logoutAllPlayer'; // YL

	const STATUS_NO_DATA = '1003';

	protected $apiUris = [
		self::API_getBaccaratTransactionByLastUpdateTime,
		self::API_getLongHuTransactionByLastUpdateTime,
		self::API_getSicTransactionByLastUpdateTime,
		self::API_getRouTransactionByLastUpdateTime,
		self::API_getRBSicTransactionByLastUpdateDate
	];

	protected $byBetTimeApiUris = [
		self::API_getSettledMyTransactionsByBetTime,
		self::API_getSettledLongHuTransactionsByBetTime,
		self::API_getSettledSicTransactionsByBetTime,
		self::API_getSettledFpcTransactionsByBetTime,
		self::API_getSettledRouTransactionsByBetTime,
		self::API_getSettledRBSicTransactionsByBetTime
	];

	protected $byBetArchivedApiUris = [
		self::API_getSettledArchiveMyTransactionsByBetTime,
		self::API_getSettledArchiveLongHuTransactionsByBetTime,
		self::API_getSettledArchiveSicTransactionsByBetTime,
		self::API_getSettledArchiveFpcTransactionsByBetTime,
		self::API_getSettledArchiveRouTransactionsByBetTime,
		self::API_getSettledArchiveRBSicTransactionsByBetTime
	];

	# Fields in nttech_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
    	"txid",
    	"gametype",
    	"betamount",
    	"updatetime",
    	"bettime",
    	"userid",
    	"category",
    	"dealerdomain",
    	"tableid",
    	"roundid",
    	"roundstarttime",
    	"result",
    	"winloss",
    	"odds",
    	"status",
        "gameshoe",
		"gameround",
		"extension1",
		"currency",
		"lossamount",
		"txnamount",
		"validbet"
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
    	"betamount",
    	"winloss",
    	"lossamount",
    	"txnamount",
		"validbet"
    ];

    # Fields in nttech_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        // 'game_name',
        // 'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'bet_at',
		'end_at',
		'status'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    const URI_MAP = array(
			self::API_generateToken => '/getKey',
			self::API_queryPlayerBalance => '/getBalance',
	        self::API_depositToGame => '/deposit',
	        self::API_withdrawFromGame => '/withdraw',
	        self::API_queryTransaction => '/getSettledTransactions',
	        self::API_logout => '/logout',
	        self::API_logoutAllPlayer => '/logoutAllPlayer',
			self::API_syncGameRecords => '/getWebsitePlayerReportByRoundStartTime',
			self::API_setMemberBetSetting => '/updateBetLimitIds',
			self::API_getBaccaratTransactionByLastUpdateTime => '/getMyTransactionByLastUpdateDate',
			self::API_getLongHuTransactionByLastUpdateTime => '/getLongHuTransactionByLastUpdateDate',
			self::API_getSicTransactionByLastUpdateTime => '/getSicTransactionByLastUpdateDate',
			self::API_getRouTransactionByLastUpdateTime => '/getRouTransactionByLastUpdateDate',
			self::API_getRBSicTransactionByLastUpdateDate => '/getRBSicTransactionByLastUpdateDate',
			//by bet time
			self::API_getSettledMyTransactionsByBetTime => '/getSettledMyTransactionsByBetTime',
			self::API_getSettledLongHuTransactionsByBetTime => '/getSettledLongHuTransactionsByBetTime',
			self::API_getSettledSicTransactionsByBetTime => '/getSettledSicTransactionsByBetTime',
			self::API_getSettledFpcTransactionsByBetTime => '/getSettledFpcTransactionsByBetTime',
			self::API_getSettledRouTransactionsByBetTime => '/getSettledRouTransactionsByBetTime',
			self::API_getSettledRBSicTransactionsByBetTime => '/getSettledRBSicTransactionsByBetTime',
			//by archive bet time
			self::API_getSettledArchiveMyTransactionsByBetTime => '/getSettledArchiveMyTransactionsByBetTime',
			self::API_getSettledArchiveLongHuTransactionsByBetTime => '/getSettledArchiveLongHuTransactionsByBetTime',
			self::API_getSettledArchiveSicTransactionsByBetTime => '/getSettledArchiveSicTransactionsByBetTime',
			self::API_getSettledArchiveFpcTransactionsByBetTime => '/getSettledArchiveFpcTransactionsByBetTime',
			self::API_getSettledArchiveRouTransactionsByBetTime => '/getSettledArchiveRouTransactionsByBetTime',
			self::API_getSettledArchiveRBSicTransactionsByBetTime => '/getSettledArchiveRBSicTransactionsByBetTime',

            // YL
            self::API_getTransactionsByLastUpdateDate => '/getTransactionsByLastUpdateDate'
		);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->site = $this->getSystemInfo('site');
		$this->cert = $this->getSystemInfo('cert');
		$this->betlimitids = $this->getSystemInfo('betlimitids');
		$this->currency = $this->getSystemInfo('currency');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '1');

		// Remark：Agent ID must be use as the extension1 ID when login
		// extension 1 parameter is your BO Agent UID
		$this->extension1 = $this->getSystemInfo('extension1');
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->sync_by_betting_time = $this->getSystemInfo('sync_by_betting_time', false);
		$this->sync_by_archived = $this->getSystemInfo('sync_by_archived', false);
		$this->maxCount = $this->getSystemInfo('maxCount', 2000);
		$this->sync_step_in_seconds = $this->getSystemInfo("sync_step_in_seconds",3600);
	}

	public function getPlatformCode() 
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		$this->api_call_name = $apiName;
		$apiUri = self::URI_MAP[$this->api_call_name];
		
		$url = $this->api_url . "/api/" . $this->site . $apiUri;
		return $url;
	}

	protected function customHttpCall($ch, $params) 
	{
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode) 
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201) && $resultArr['status'] == self::SUCCESS_CODE){
			$success = true;
		}
		# 1003 means no data in API
		if(isset($resultArr['status']) && $resultArr['status'] == self::STATUS_NO_DATA){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('NTTECH got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) 
	{
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
		$success = false;
		$message = "Unable to create Account for NTTECH";
		if($return){
			$success = true;
            $this->setGameAccountRegistered($playerId);
			$message = "Successfull create account for NTTECH";
		}
		return array("success"=>$success,"message"=>$message); 
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
			'cert' => $this->cert,
			'alluser' => 0,
			'users' => $gameUsername
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function setMemberBetSetting($playerName,$betLimitsIds=null) 
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerBetDetails',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'cert' => $this->cert,
			'user' => $gameUsername,
			'betLimitIds' => $this->prepareBetLimitIdsFormat($this->betlimitids)
		);

		if($betLimitsIds){
			$params['betLimitIds'] = $this->prepareBetLimitIdsFormat($betLimitsIds);
		}
		return $this->callApi(self::API_setMemberBetSetting, $params, $context);
	}

	private function prepareBetLimitIdsFormat($betLimitIdsArr){
		if(!empty($betLimitIdsArr)){	
			if(count($betLimitIdsArr) > 1){
				$result = "[".implode(',',$betLimitIdsArr)."]";
			}
			else{
				$result = "[".$betLimitIdsArr."]";
			}
		}
		return $result;
	}

	public function processResultForUpdatePlayerBetDetails($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		return array($success, $resultArr);
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
            if(!empty($resultArr['results'])) {
                $result['balance'] = $this->gameAmountToDB($resultArr['results'][0]['balance']);
            }
            else {
                $success = false;
            }
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
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->generateUnique() : $transfer_secure_id;
		$result = $this->getAPIKey($gameUsername);
		$success = (isset($result['success']) && $result['success']) ? $result['success'] : false;
		if($success)
		{
			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForDepositToGame',
	            'amount' => $amount,
				'external_transaction_id' => $external_transaction_id
	        );

	        $params = array(
				'cert' => $this->cert,
				'user' => $gameUsername,
				'balance' => $this->dBtoGameAmount($amount), // A positive number. E.g. 1000.00
				'ts_code' => $external_transaction_id,
				'extension1'=> $this->extension1
			);
			return $this->callApi(self::API_depositToGame, $params, $context);
		}

		return [$success,$result];
	}

	public function processResultForDepositToGame($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
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

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'W'.$this->generateUnique() : $transfer_secure_id;
		$result = $this->getAPIKey($gameUsername);
		$success = (isset($result['success']) && $result['success']) ? $result['success'] : false;
		if($success)
		{
			$context = array(
	            'callback_obj' => $this,
	            'callback_method' => 'processResultForWithdrawFromGame',
	            'amount' => $amount,
				'external_transaction_id' => $external_transaction_id
	        );

	        $params = array(
				'cert' => $this->cert,
				'user' => $gameUsername,
				'withdrawtype' => 0, // 1: withdraw account all balance, 0: withdraw account balance with parameter "balance" 
				'balance' => $this->dBtoGameAmount($amount), // A positive number. E.g. 1000.00
				'ts_code' => $external_transaction_id,
				'extension1'=> $this->extension1
			);
	        return $this->callApi(self::API_withdrawFromGame, $params, $context);
		}
		return [$success,$result];
	}

	public function processResultForWithdrawFromGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
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
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	/*
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  player_center/goto_nttech_game/<game_platform_id>/<language>/<is_mobile>
	 *  Default: player_center/goto_nttech_game/2103/
	 *  Mobile: player_center/goto_nttech_game/2103/en/true
	 *
	 * 	Ex Return URL: http://test.bikimex.com:10023/api/MexMxOle/login?user=testt1dev&userName=testt1dev&key=uW%2B3k%2BeC3%2BDidcEF5%2F69%2FE2hWAC6LxysdksaK%2Bp4a2k%3D&language=EN&mb=0&allowHedgeBetting=1&extension1=acapiole
	 *
	 */
	public function queryForwardgame($playerName, $extra = null) 
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->getAPIKey($gameUsername);

		if($result["success"])
		{
			#IDENTIFY MOBILE GAME
			$platform = self::DESKTOP_PLATFORM_TYPE;
			if(isset($extra['is_mobile'])){
				$ismobile = $extra['is_mobile'] ? true : false;	
				if($ismobile){
					$platform = self::MOBILE_PLATFORM_TYPE;	
				}
			}

			#GET LANG FROM PLAYER DETAILS
			$playerId = $this->getPlayerIdFromUsername($playerName);
			$language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);

	        $params = array(
	        	'user'  => $gameUsername,
	        	'userName' => $gameUsername,
	        	'key' => $result['key'],
	        	'language' => $language,
	        	'mb' => $platform,
	        	'allowHedgeBetting' => true, 
				'extension1' => $this->extension1
			);

			#IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL, THEN INCLUDE IN LOGIN TOKEN
			if(isset($extra['language'])){
				$params['language'] = $this->getLauncherLanguage($extra['language']);
			}
			$url = $result["url"].http_build_query($params);
			$this->CI->utils->debug_log('NTTECH queryForwardgame ========= URL:', $url);
			return ['success'=>true, 'url'=> $url];
		}
		return ['success'=>false, 'url'=> null];
	}

	public function logout($gameUsername, $password = null) 
	{
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
        );

        $params = array(
			"cert" => $this->cert,
			"user" => $gameUsername
		);

		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		return array($success,["logout"=>true]);
	}

	/**
	 * Convert DateTime to Unix Timestamp
	 * 
	 * @param datetime $dateTime
	 * 
	 * @return string
	 */
	public function convertDateTimeToUnix($dateTime)
	{
		$now  = new DateTime();

		if(empty($dateTime) || $dateTime > $now){
			$lastUpdateDate = strtotime($now->format("Y-m-d H:i:s"))*1000;
		}else{
			$lastUpdateDate = strtotime($dateTime->format("Y-m-d H:i:s"))*1000;
		}

		return $lastUpdateDate;
	}

	/** 
	 * Process Game Status
	 * 
	 * @param string $gameStatus
	 * 
	 * @return int
	*/
	public function processGameStatus($gameStatus)
	{
		switch($gameStatus){
			case "VOID":
				$status = Game_logs::STATUS_VOID;
				break;
			default:
				$status = Game_logs::STATUS_SETTLED;
				break;
		}

		return $status;
	}

	
	public function syncOriginalGameLogsByDateRange($token){
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
    	$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));

    	$apiResult = array();
		$betTimeByApi = $this->sync_by_archived ? $this->byBetArchivedApiUris : $this->byBetTimeApiUris;

		if(!empty($betTimeByApi)){

			$start = clone $startDateTime;
			$end = clone $endDateTime;
			$now=new DateTime();

			if($end > $now){
				$end = $now;
			}

			$step=$this->sync_step_in_seconds; # steps in seconds
			$rows_count = 0;

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'checkMaxReturn' => false
			);

			foreach($betTimeByApi as $api){

				while($start < $end){
					$endDate = $this->CI->utils->getNextTimeBySeconds($start,$step);

					if($endDate > $end){
						$endDate = $end;
					}
					$params = [
						"cert" => $this->cert,
						"st" => $this->convertDateTimeToUnix($start),
						"et" => $this->convertDateTimeToUnix($endDate),
						"extension1" => $this->extension1
					];

					$this->CI->utils->debug_log(__METHOD__.' params >>>>>>>>',$params);

					$apiResult = $this->callApi($api, $params, $context);
					sleep($this->common_wait_seconds);

					# we check if API call is success
					if(isset($apiResult["success"]) && ! $apiResult["success"]){

						$this->CI->utils->debug_log(__METHOD__.' ERROR in calling API: ',$apiResult);
						break;
					}

					#check if max return
					$rows_count += isset($apiResult['data_count']) ? $apiResult['data_count'] : 0;

					if(isset($apiResult["is_max_return"]) && $apiResult["is_max_return"]){

						$this->CI->utils->debug_log(__METHOD__.' is max return of API ',$apiResult["is_max_return"]);
			
						$step = $step / 2; # we divide by two the step here, meaning cut to half the end date
					}else{
						$start = $endDate;
					}
				}
			}
		}
    	return array('success' => true,'result' => $apiResult,'rows_count'=>$rows_count);
	}

	public function syncOriginalGameLogsByUpdateTime($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
		$startDateTime->modify($this->getDatetimeAdjust());
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));
		$rows_count = 0;

		$context = array(
            'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'checkMaxReturn' => false
		);

		foreach($this->apiUris as $api){

			while($startDateTime < $endDateTime){
				$lastUpdateDate = $this->convertDateTimeToUnix($startDateTime);
		
				$params = [
					"cert" => $this->cert,
					"lastupdatedate" => $lastUpdateDate,
					"extension1" => $this->extension1,
				];
		
				$this->CI->utils->info_log(__METHOD__. ' params', $params,'startDateTime',$startDateTime,'endDateTime',$endDateTime);
	
				$result = $this->callApi($api, $params, $context);
				sleep($this->common_wait_seconds);
				$rows_count += isset($result['data_count']) ? $result['data_count'] : 0;
				$startDateTime->modify('+1 second');
			}
		}

		return array('success' => $result['success'],'result' => $result,'rows_count'=>$rows_count);
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
    	$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
		$startDateTime->modify($this->getDatetimeAdjust());
		$isManualSyncedByOtherMethod = parent::getValueFromSyncInfo($token, 'isManualSyncedByOtherMethod');
		$rows_count = 0;

    	if($isManualSyncedByOtherMethod){
    		$this->CI->utils->debug_log('NTTECH sync by betting_time', true);
    		return $this->syncOriginalGameLogsByDateRange($token);
		}

		$context = array(
            'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'checkMaxReturn' => false
		);
		
		$lastUpdateDate = $this->convertDateTimeToUnix($startDateTime);
		
		$params = [
			"cert" => $this->cert,
			"lastupdatedate" => $lastUpdateDate,
			"extension1" => $this->extension1,
		];

		$this->CI->utils->debug_log(__METHOD__. ' params', $params);

		foreach($this->apiUris as $api){

			$result = $this->callApi($api, $params, $context);
			sleep($this->common_wait_seconds);
		}

		return array('success' => $result['success'],'result' => $result,'rows_count'=>$rows_count);
	}

	public function processResultForSyncOriginalGameLogs($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$checkMaxReturn = $this->getVariableFromContext($params, 'checkMaxReturn');
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);
		$result = array('data_count'=>0,'is_max_return'=>false);
		$gameRecords= isset($resultArr["transactions"]) ? $resultArr["transactions"] : [];
		$gameRecordsCount = (is_array($gameRecords) && count($gameRecords) > 0) ? count($gameRecords) : 0;
		

		if($success){
			# check here if max records count from API
			if($gameRecordsCount == $this->maxCount && $checkMaxReturn){
				return [
					true,
					[
						'is_max_return' => true,
						'data_count' => $gameRecordsCount
					]
				];
			}

			$this->processGameRecords($gameRecords,$responseResultId);

			if ($gameRecords) {
				$this->CI->load->model('original_game_logs_model');
				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                    $this->original_gamelogs_table,
	                    $gameRecords,
	                    'external_uniqueid',
	                    'external_uniqueid',
	                    self::MD5_FIELDS_FOR_ORIGINAL,
	                    'md5_sum',
	                    'id',
	                    self::MD5_FLOAT_AMOUNT_FIELDS
	                );
				$this->CI->utils->debug_log('NTTECH after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
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
		}
		return array($success, $result);
	}


	/**
	 * return Report API URI based in game type
	 * 
	 * @param string $gameType
	 * 
	 * @return string
	 */
	public function getApiUriOfGametype($gameType)
	{
		switch($gameType){
			case "Baccarat":
				$uri = self::API_getBaccaratTransactionByLastUpdateTime;
				break;
			case "Roulette":
				$uri = self:: API_getRouTransactionByLastUpdateTime;
				break;
			case "Sicbo":
				$uri = self:: API_getSicTransactionByLastUpdateTime;
				break;
			case "DragonTiger":
				$uri = self:: API_getLongHuTransactionByLastUpdateTime;
				break;
			case "RBSicbo":
				$uri = self::API_getRBSicTransactionByLastUpdateDate;
				break;
			default:
				$uri = self::API_queryTransaction;
				break;
		}

		return $uri;
	}

	public function processGameRecords(&$gameRecords, $responseResultId) 
	{
		if(!empty($gameRecords)){

			foreach ($gameRecords as $index => $row){
				$roundtime = isset($row['updateTime']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s",strtotime($row['updateTime']))) : '';
				$newRecords['dealerdomain'] = isset($row["dealerDomain"]) ? $row["dealerDomain"] : '';
				$newRecords['tableid'] = isset($row["tableId"]) ? $row["tableId"] : '';
				$newRecords['gameshoe'] = isset($row["gameShoe"]) ? $row["gameShoe"] : '';
				$newRecords['gameround'] = isset($row["gameRound"]) ? $row["gameRound"] : '';
				$newRecords['userid'] = isset($row["userId"]) ? $row["userId"] : '';
				$newRecords['gametype'] = isset($row["gameType"]) ? $row["gameType"] : '';
				$newRecords['extension1'] = isset($row["extension1"]) ? $row["extension1"] : $this->extension1;
				$newRecords['currency'] = isset($row["currency"]) ? $row["currency"] : $this->currency_type;
				$newRecords['txnamount'] = isset($row["txnAmount"]) ? $row["txnAmount"] : '';
				$newRecords['lossamount']= isset($row["lossAmount"]) ? $row["lossAmount"] : '';
				$newRecords['roundtime']= $roundtime;
				$newRecords['txid']= isset($row['txId']) ? $row["txId"] : '';
				$newRecords['betamount']= isset($row['betAmount']) ? $row['betAmount'] : '';
				$newRecords['updatetime']= $roundtime;
				$newRecords['bettime']= isset($row['betTime']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s",strtotime($row['betTime']))) : '';
				$newRecords['category']= isset($row['category']) ? $row['category'] : '';
				$newRecords['roundid']= isset($row['roundId']) ? $row['roundId'] : '';
				$newRecords['roundstarttime']= isset($row['roundStartTime']) ? $this->gameTimeToServerTime(date("Y-m-d H:i:s",strtotime($row['roundStartTime']))) : '';
				$newRecords['result']=  isset($row['result']) ? $row['result'] : '';
				$newRecords['winloss']= isset($row['winLoss']) ? $row['winLoss'] : '';
				$newRecords['odds']= isset($row['odds']) ? $row['odds'] : '';
				$newRecords['status']= isset($row['status']) ? $row['status'] : '';
				$newRecords['validbet']= isset($row['validBet']) ? $row['validBet'] : '';
				$newRecords['response_result_id']= $responseResultId;
				$newRecords['external_uniqueid']= isset($row['txId']) ? $row['txId'] : '';
				$newRecords['updated_at']= date("Y-m-d H:i:s");
				$newRecords['creationtime']= date("Y-m-d H:i:s");
				$gameRecords[$index] = $newRecords;
				unset($newRecords);
			}
			
		}
	}

	protected function updateOrInsertOriginalGameLogs($data, $queryType)
	{
        $dataCount=0;
        if(!empty($data)){
            if (!is_array($data)) {
                $data = json_decode($data,true);
            }
            if (is_array($data)) {
                foreach ($data as $record) {
                    if ($queryType == 'update') {
                        $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                    } else {
                        unset($record['id']);
                        $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                    }
                    $dataCount++;
                    unset($record);
                }
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

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`nt`.`updatetime` >= ?
          AND `nt`.`updatetime` <= ?';
        if($use_bet_time){
            $sqlTime='`nt`.`bettime` >= ?
          AND `nt`.`bettime` <= ?';
        }

        $sql = <<<EOD
			SELECT
				nt.id as sync_index,
				nt.response_result_id,
				nt.roundId as round,
				nt.userid as username,
				nt.betamount as bet_amount,
				nt.validbet as valid_bet,
				nt.winloss as result_amount,
				nt.bettime as bet_at,
				nt.updatetime as end_at,
				nt.gametype as game_code,
				nt.external_uniqueid,
				nt.md5_sum,
				nt.status,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as nt
			LEFT JOIN game_description as gd ON nt.gametype = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON nt.userid = game_provider_auth.login_name
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
		
		// process game status
		$status = isset($row["status"]) ? $row["status"] : null;
		$gameStatus = $this->processGameStatus($status);

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
            'status' => $gameStatus,
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

    /*
     * This method is use to get key value that needs in game launch and transfer fund
     */
    protected function getAPIKey($gameUsername) 
	{
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetKey',
            'gameUsername' => $gameUsername,
        );

		$params = array(
			"cert" => $this->cert,
			"user" => $gameUsername,
			"betLimitIds" => $this->prepareBetLimitIdsFormat($this->betlimitids),
			"currency" => $this->currency
		);

		return $this->callApi(self::API_generateToken, $params, $context);
	}

	public function processResultForGetKey($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		$result= [];

		if($success){
			$result['status'] = $resultArr['status'];
	        $result['key'] = $resultArr['key'];
	        $result['url'] = $resultArr['url'];
		}
		return array($success, $result);
	}

	private function generateUnique()
	{
		$dt = new DateTime($this->utils->getNowForMysql());
		return $dt->format('YmdHis').random_string('numeric', 6);
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
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

    protected function getLauncherLanguage($language){
        $lang='en';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'EN':
			case 'en-us':
			case 'en-US':
            case "English":
                $lang = 'EN'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'CN':
            case "Chinese":
                $lang = 'CN'; // chinese
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
            case 'vn':
            case 'VN':
            case "Vietnamese":
                $lang = 'VN'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
            case 'ko':
            case 'KO':
            case "Korean":
                $lang = 'KO'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'TH':
            case "Thai":
                $lang = 'TH'; // thai
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'id':
            case 'ID':
            case "Indonesian":
                $lang = 'ID'; // indonesian
                break;
			case Language_function::INT_LANG_PORTUGUESE:
			case 'pt':
			case 'PT':
			case 'pt-br':
			case 'pt-BR':
			case "Portuguese":
				$lang = 'PT'; // indonesian
				break;
            default:
                $lang = 'EN'; // default as english
                break;
        }
        return $lang;
    }

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos=null) {
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
