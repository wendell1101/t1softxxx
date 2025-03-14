<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_common_nttech_v2 extends Abstract_game_api {

	/**
	 * @var int $syncStepInterval
	 */
	protected $syncStepInterval;
    public $currency_type;
    public $player_lang;
    public $original_gamelogs_table;
    public $sync_all_currency_game_logs;
	public $platform;
	public $platform_type;
	public $cert;
	public $agent_id;

	const SUCCESS_CODE = 0000;
	const ACCOUNT_EXISTS_CODE = 1001;
	const ORIGINAL_GAMELOGS_TABLE = 'nttech_v2_game_logs';
	const WITHDRAW_PARTIAL = 0;
	const ORIG_CANCELLED_STATUS = 2;

    //? [Update info]
    //? Newly add txstatus:5, which refers to the refund of the bet in "Place" due to horse racing restrictions.
	const ORIG_REFUND_STATUS = 5; // OGP-24812

	// status code
	const STATUS_SYSTEM_BUSY = 9998;
	const STATUS_FAIL = 9999;
	const STATUS_SUCCESS = 0;
	const STATUS_PLEASE_INPUT_ALL_DATA = 10;
	const STATUS_INVALID_USER_ID = 1000;
	const STATUS_ACCOUNT_EXISTED = 1001;
	const STATUS_ACCOUNT_IS_NOT_EXISTS = 1002;
	const STATUS_INVALID_CURRENCY = 1004;
	const STATUS_LANGUAGE_IS_NOT_EXISTS = 1005;
	const STATUS_PT_SETTING_IS_EMPTY = 1006;
	const STATUS_INVALID_PT_SETTING_WITH_PARENT = 1007;
	const STATUS_INVALID_TOKEN = 1008;
	const STATUS_INVALID_TIMEZONE = 1009;
	const STATUS_INVALID_AMOUNT = 1010;
	const STATUS_INVALID_TXCODE = 1011;
	const STATUS_HAS_PENDING_TRANSFER = 1012;
	const STATUS_ACCOUNT_IS_LOCK = 1013;
	const STATUS_ACCOUNT_IS_SUSPEND = 1014;
	const STATUS_TXCODE_ALREADY_OPERATION = 1016;
	const STATUS_TXCODE_IS_NOT_EXIST = 1017;
	const STATUS_NOT_ENOUGH_BALANCE = 1018;
	const STATUS_NO_DATA = 1019;
	const STATUS_INVALID_DATE_TIME_FORMAT = 1024;
	const STATUS_INVALID_TRANSACTION_STATUS = 1025;
	const STATUS_INVALID_BET_LIMIT_SETTING = 1026;
	const STATUS_INVALID_CERTIFICATE = 1027;
	const STATUS_UNABLE_TO_PROCEED = 1028;
	const STATUS_IP_ADDRESS_DID_NOT_WHITELIST_YET = 1029;
	const STATUS_INVALID_DEVICE_TO_CALL_API = 1030;
	const STATUS_SYSTEM_IS_UNDER_MAINTENANCE = 1031;
	const STATUS_DUPLICATE_LOGIN = 1032;
	const STATUS_INVALID_GAMECODE = 1033;
	const STATUS_TIME_DOES_NOT_MEET = 1034;
	const STATUS_INVALID_AGENT_ID = 1035;
	const STATUS_INVALID_PARAMETERS = 1036;
	const STATUS_DUPLICATE_TRANSACTION = 1038;
	const STATUS_TRANSACTION_NOT_FOUND = 1039;
	const STATUS_REQUEST_TIMEOUT = 1040;
	const STATUS_HTTP_STATUS_ERROR = 1041;
	const STATUS_HTTP_RESPONSE_IS_EMPTY = 1042;
	const STATUS_BET_HAS_CANCELED = 1043;
	const STATUS_INVALID_BET = 1044;
	const STATUS_ADD_ACCOUNT_STATEMENT_FAILED = 1045;
	const STATUS_TRANSFER_FAILED= 1046;
	const STATUS_GAME_IS_UNDER_MAINTENANCE = 1047;

	# Fields in nttechv2_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
		"gameType",
		// "comm",
		"txTime",
		// "bizDate",
		"winAmt",
		"gameInfo",
		"betAmt",
		"updateTime",
		"jackpotWinAmt",
		"turnOver",
		"userId",
		"betType",
		"platform",
		"txStatus",
		"jackpotBetAmt",
		// "createTime",
		"platformTxId",#unique
		"realBetAmt",
		"gameCode",
		"currency",
		// "ID",#transid
		"realWinAmt",
		"roundId",
		"settleStatus"
	];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
    	"winamt",
    	"betamt",
    	"jackpotbetamt",
    	"jackpotwinamt",
		"realbetamt",
		"realwinamt"
    ];

    # Fields in nttech_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'real_betting_amount',
        'round',
        'game_code',
        // 'game_name',
        // 'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'bet_at',
        'end_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'real_betting_amount',
        'valid_bet',
        'result_amount',
	];

	/**
	 * Use this for syncing game history by transaction time
	 *
	 * @var const API_syncByTransactionTime
	 */
	const API_syncByTransactionTime = 'syncByTransactionTime';

    const URI_MAP = array(
			self::API_createPlayer => 'wallet/createMember',
			self::API_queryPlayerBalance => 'wallet/getBalance',
	        self::API_depositToGame => 'wallet/deposit',
	        self::API_withdrawFromGame => 'wallet/withdraw',
	        self::API_queryTransaction => 'wallet/checkTransferOperation',
	        self::API_login => 'wallet/doLoginAndLaunchGame',
	        self::API_logout => 'wallet/logout',
			self::API_setMemberBetSetting => 'wallet/updateBetLimit',

			self::API_syncGameRecords => 'fetch/gzip/getTransactionByUpdateDate',
			self::API_syncByTransactionTime => 'fetch/gzip/getTransactionByTxTime',
			self::API_syncLostAndFound => 'fetch/gzip/getTransactionByTxTime',
			self::API_syncTipRecords => 'fetch/gzip/getTipTxnByTxTime',
		);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->data_url = $this->getSystemInfo('data_url');
		$this->agent_id = $this->getSystemInfo('agent_id');
		$this->cert = $this->getSystemInfo('cert');
		$this->betlimitids = $this->getSystemInfo('betlimitids');
		$this->external_url = $this->getSystemInfo('external_url');
		$this->game_forbidden = $this->getSystemInfo('game_forbidden');
		$this->platform = $this->getSystemInfo('gamelaunch_platform','SEXYBCRT');
		$this->platform_type = $this->getSystemInfo('gamelaunch_platform_type','LIVE');
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '20');
		$this->syncStepInterval = $this->getSystemInfo('syncStepInterval','+1 hour');


		# fix exceed game username length
		$this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 0);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 16);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 6);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->allow_sync_data_by_transacion_time = $this->getSystemInfo('allow_sync_data_by_transacion_time',true);
        $this->allow_sync_tip = $this->getSystemInfo('allow_sync_tip', false);
        $this->default_game_code = $this->getSystemInfo('default_game_code', 'MX-LIVE-001');
        $this->sync_all_currency_game_logs = $this->getSystemInfo('sync_all_currency_game_logs', false);

	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params)
	{
		$this->api_call_name = $apiName;
		$apiUri = self::URI_MAP[$this->api_call_name];

		$apiUrl = $this->api_url;

		if($apiName==self::API_syncGameRecords
			||$apiName==self::API_syncByTransactionTime
			||$apiName==self::API_syncLostAndFound
			||$apiName==self::API_syncTipRecords
		){
			if(!empty($this->data_url)){
				$apiUrl = $this->data_url;
			}
		}

		$url = $apiUrl . "/" . $apiUri;

		return $url;
	}

    public function getHttpHeaders($params) {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return $headers;
    }

	protected function customHttpCall($ch, $params)
	{
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201) && ($resultArr['status'] == self::SUCCESS_CODE || $resultArr['status'] == self::ACCOUNT_EXISTS_CODE)){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('NTTECH V2 got error: ', $responseResultId,' Result:', $resultArr);
		}
		$this->CI->utils->debug_log('NTTECH V2 RawSuccessResponse: ', $responseResultId,'Success:', $success);
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
	{

		$extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true,
            'strict_username_with_prefix_length' => true,
            'force_lowercase' => true
        ];

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
            'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'userId' => $gameUsername,
            'currency' => $this->currency_type,
            'betLimit' => json_encode($this->betlimitids,true),
            'language' => $this->getSystemInfo('player_lang')?:$this->player_lang,
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array(
            "player" => $gameUsername,
            "exists" => false
        );

        if($success) {
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result["exists"] = true;
        }
        return array($success, $result);
    }

    /**
	 * overview : set member bet settings
	 *
	 * @param $playerName
	 * @return array
	 */
	public function setMemberBetSetting($playerName, $bet_settings = null) {
		if (!is_null($bet_settings)) {
			$betlimitids = array(
				$this->platform => array(
					$this->platform_type => array(
						"limitId" => $bet_settings['limit_id']
					)
				)
			);
		}else{
			$betlimitids = $this->betlimitids;
		}

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSetMemberSetting',
			'playerName' => $playerName,
		);

		$params = array(
            'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'userId' => $gameUsername,
            'betLimit' => json_encode($betlimitids,true)
        );

		$this->utils->debug_log('game_provider_bet_limit bet_settings: default' ,$context, $params);
        return $this->callApi(self::API_setMemberBetSetting, $params, $context);
	}

	public function processResultForSetMemberSetting($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array(
            "status" => $resultArr['status']
        );
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
			'cert' => $this->cert,
			'agentId' => $this->agent_id,
			'userIds' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = strtolower($this->getVariableFromContext($params, 'gameUsername'));
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

		if($success){

			$apiResult = isset($resultArr['results'][0]['balance']) ? $resultArr['results'][0]['balance'] : null;

			if(! is_null($apiResult)){
				$result['balance'] = $this->gameAmountToDB($apiResult);
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

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id,
			'gameUsername' => $gameUsername
        );

        $params = array(
			'cert' => $this->cert,
			'agentId' => $this->agent_id,
			'userId' => $gameUsername,
			'transferAmount' => $this->dBtoGameAmount($amount), // A positive number. Ex. 1000.00
			'txCode' => $external_transaction_id,
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);
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
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit) {
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

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id,
			'gameUsername' => $gameUsername
        );

        $params = array(
			'cert' => $this->cert,
			'userId' => $gameUsername,
			'agentId' => $this->agent_id,
			'txCode' => $external_transaction_id,
			'withdrawType' => self::WITHDRAW_PARTIAL,//1: All, 0: Partial; default = 1
			'transferAmount' => $this->dBtoGameAmount($amount),
		);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
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

	public function generateGameType($gameType){
		$gameType = strtolower($gameType);
		switch ($gameType) {

			case 'table':
			case 'table_games':
			case 'table_and_cards':
				$gameType = "TABLE";
				break;

			case 'e_sports':
				$gameType = "ESPORTS";
				break;

			case 'fishing_game':
				$gameType = "FH";
				break;

			case 'slots':
				$gameType = "SLOT";
				break;

			default:
				$gameType = "LIVE";
				break;
		}
		return $gameType;
	}

	/*
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  player_center/goto_nttechv2_game/<game_platform_id>/<language>/<is_mobile>
	 *  Desktop: player_center/goto_nttechv2_game/2117/
	 *  Mobile: player_center/goto_nttechv2_game/2117/en/true
	 *
	 */
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		#GET LANG FROM PLAYER DETAILS
		$playerId = $this->getPlayerIdFromUsername($playerName);
		//$language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);

		$lang = $this->getSystemInfo('language', $extra['language']);
		$language=$this->getLauncherLanguage($lang);

		$params = array(
			'cert' => $this->cert,
			'agentId' => $this->agent_id,
			'userId' => $gameUsername,
			'gameType' => $this->generateGameType($extra['game_type']),
			'platform' => $this->platform,
			'externalURL' => $this->external_url,
			'gameForbidden' => $this->game_forbidden,
			'language' => $language,
            'autoBetMode' => $this->getSystemInfo('autoBetMode', 1),
		);

		if(isset($extra['game_code'])){
			$params['gameCode'] = !empty($extra['game_code']) ? $extra['game_code'] : $this->default_game_code;
		} else {
			$params['gameCode'] = $this->default_game_code;
		}

		#IDENTIFY MOBILE GAME
		if(isset($extra['is_mobile'])){
			$ismobile = $extra['is_mobile'] ? true : false;
			if($ismobile){
				$params['isMobileLogin'] = true;
			}
		}

		#IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL, THEN INCLUDE IN LOGIN TOKEN
		// if(isset($extra['language'])){
		// 	$params['language'] = $this->getLauncherLanguage($extra['language']);
		// }

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
        );

        $result = $this->callApi(self::API_login, $params, $context);
		if($result["success"])
		{
			$this->CI->utils->debug_log('NTTECH V2 queryForwardgame URL:', $result['url']);
			return ['success'=>true, 'url'=> $result['url']];
		}
		return ['success'=>false, 'url'=> null];
	}

	public function processResultForLogin($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
        return array($success, $resultArr);
	}

	public function logout($gameUsername, $password = null)
	{
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
        );
        $params = array(
			'cert' => $this->cert,
			'agentId' => $this->agent_id,
			'userIds' => $gameUsername,
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

	/*
	 * Note:
	 *	The maximum time range for each search is 1 hours.
	 *	You can only search data within the past 7 days.
	 *	Maximun 20000 transactions.
	 *	if apply platform the api allowed frequency is 20 seconds.
	 *	if not apply platform the api allowed frequency is 60 seconds.
	 *	Order by TxTime asc
	 */
	public function syncOriginalGameLogs($token)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$isManualSyncedByOtherFilterDate = parent::getValueFromSyncInfo($token, 'isManualSyncedByOtherFilterDate');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$start = clone $startDateTime;
		$end = clone $endDateTime;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'isManualSync' => $isManualSyncedByOtherFilterDate,
			'startDate' => $startDate
		);

		$this->last_update_time = [];

		$step = $this->syncStepInterval; # in hour

		$done = false;
		$data_count=0;
		while($start < $end && !$done){

			$startDate = $start->format("Y-m-d\TH:i:s")."+08:00";
			$endDate = $this->CI->utils->getNextTime($start,$step);

			if($endDate > $end){
				$endDate = $end;
			}
			$this->CI->utils->debug_log('BERMAR last_update_time',$this->last_update_time);
			$params = [
				"cert" => $this->cert,
				"agentId" => $this->agent_id,
				"timeFrom" => $startDate,
				"platform" => $this->platform,
			];

            if (!$this->sync_all_currency_game_logs) {
                $params['currency'] = $this->currency_type;
            }

			if($isManualSyncedByOtherFilterDate){
				unset($params['timeFrom']);
				$params['startTime'] = $startDate;
				$params['endTime'] = $endDate->format("Y-m-d\TH:i:s")."+08:00";
			}

			$this->CI->utils->debug_log(__METHOD__.' params',$params);
			$this->CI->utils->debug_log('BERMAR params',$params);

			$syncApi = $isManualSyncedByOtherFilterDate ? self::API_syncByTransactionTime : self::API_syncGameRecords;
			//$syncApi = self::API_syncByTransactionTime;

			$result = $this->callApi($syncApi, $params, $context);

			if(isset($result['data_count'])){
				$data_count+=(int)$result['data_count'];
			}

			$start = $endDate;
			$this->CI->utils->debug_log('BERMAR result response',$result);
			if(isset($result['updateTime']) && !is_null($result['updateTime'])){
				$this->CI->utils->debug_log('BERMAR use updateTime/txTime as start',$result);
				$startUpdateTime = strtotime($result['updateTime']);
				$updatedStartTime = date('Y-m-d H:i:s', $startUpdateTime);
				$newStartTime = new DateTime($updatedStartTime);
				$start = $newStartTime;
				$done = $newStartTime >= $end;
				if(in_array($result['updateTime'],$this->last_update_time)){
					$this->CI->utils->debug_log('BERMAR ended loop',$this->last_update_time,$updatedStartTime);
					$done=true;
				}else{
					$this->last_update_time[]=$result['updateTime'];
				}
			}else{
				$this->CI->utils->debug_log('BERMAR not use updateTime as start',$result);
				$done = $endDate >= $end;
			}

			$this->CI->utils->debug_log('BERMAR NEW START',$start->format("Y-m-d\TH:i:s")."+08:00");

			sleep($this->sync_sleep_time);

			# we sleep , so we need to reconnect to database
			$this->CI->db->_reset_select();
			$this->CI->db->reconnect();
			$this->CI->db->initialize();
		}

		return [
			'success' => true,
			'rows_count' => $data_count
		];
	}

	/*
	 * Note:
	 *	This function used getTransactionByTxTime to get missing data
	 */
	public function syncLostAndFound($token) {
		if($this->allow_sync_tip){
			$this->getTipTxnByTxTime($token);
		}

		if($this->allow_sync_data_by_transacion_time){
			$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
			$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

			$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
	    	$startDateTime->modify($this->getDatetimeAdjust());
	    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

			$start = clone $startDateTime;
			$end = clone $endDateTime;

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'isManualSync' => true,
				'startDate' => $startDate
			);

			$step = $this->syncStepInterval; # in hour

			$done = false;
			while($start < $end && !$done){

				$startDate = $start->format("Y-m-d\TH:i:s")."+08:00";
				$endDate = $this->CI->utils->getNextTime($start,$step);

				if($endDate > $end){
					$endDate = $end;
				}

				$params = [
					"cert" => $this->cert,
					"agentId" => $this->agent_id,
					"platform" => $this->platform,
					"startTime" => $startDate,
					"endTime" => $endDate->format("Y-m-d\TH:i:s")."+08:00",
				];

				$this->CI->utils->debug_log(__METHOD__.' params',$params);

				$syncApi =  self::API_syncLostAndFound;

				$result = $this->callApi($syncApi, $params, $context);
				$done = $endDate >= $end;
				$start =  $endDate;

				sleep($this->sync_sleep_time);

				# we sleep , so we need to reconnect to database
				$this->CI->db->_reset_select();
				$this->CI->db->reconnect();
				$this->CI->db->initialize();
			}

			return [
				'success' => true,
				$result
			];
		}
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$isManualSync = $this->getVariableFromContext($params, 'isManualSync');
		$success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);

		$result = ['data_count' => 0, 'updateTime' => null];
		// $gameRecords = !empty($resultArr['transactions'])?$resultArr['transactions']:[];
		$gameRecords = (isset($resultArr['transactions']) && is_array($resultArr['transactions']) && count($resultArr['transactions']) > 0)
				? $resultArr['transactions'] : [];

		if($success && !empty($gameRecords)){
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

			$this->CI->load->model('original_game_logs_model');
			list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    $this->processOriginalDbFields(),
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );
			$this->CI->utils->debug_log('NTTECH V2 after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
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

			$updateTime = end($resultArr['transactions']);
			$this->CI->utils->debug_log('BERMAR updateTime a: ', $updateTime);
			if($isManualSync==true){
				if(isset($updateTime['txTime'])) {
					$result['updateTime']=$updateTime['txTime'];
					$this->CI->utils->debug_log('BERMAR txTime b: ', $updateTime['txTime']);
					return [
						true,
						$result
					];
				}
			}else{
				if(isset($updateTime['updateTime'])) {
					$result['updateTime']=$updateTime['updateTime'];
					$this->CI->utils->debug_log('BERMAR updateTime b: ', $updateTime['updateTime']);
					return [
						true,
						$result
					];
				}
			}

		}
		return array(true, $result);
	}

	private function processOriginalDbFields(){
		$originalDbFields = static::MD5_FIELDS_FOR_ORIGINAL;
		array_walk($originalDbFields, function(&$fields){
			if($fields == 'ID'){
				$fields = 'transid';
			}
		    $fields = strtolower($fields);
		});
		return $originalDbFields;
	}

	private function rebuildGameRecords(&$gameRecords,$extra){
		$availableFields=static::MD5_FIELDS_FOR_ORIGINAL;
        foreach($gameRecords as &$gr){

        	$gr['betAmt'] = $gr['betAmount'];
	        $gr['winAmt'] = $gr['winAmount'];
	        $gr['turnOver'] = $gr['turnover'];
	        $gr['jackpotBetAmt'] = $gr['jackpotBetAmount'];
	        $gr['jackpotWinAmt'] = $gr['jackpotWinAmount'];
	        $gr['realWinAmt'] = $gr['realWinAmount'];
	        $gr['realBetAmt'] = $gr['realBetAmount'];

        	$gr=array_filter($gr, function($key) use($availableFields){
	            return in_array($key, $availableFields);
	        }, ARRAY_FILTER_USE_KEY);

	        $gr = array_change_key_case($gr,CASE_LOWER);
	        if($gr['realwinamt']){
	        	$result_amount = $gr['realwinamt'] - $gr['realbetamt'];
	        }else{
	        	$result_amount = -$gr['realbetamt'];
	        }

			if(isset($gr['id'])) { // need to add condition it causing an issue if there is no id in the original game records
				$gr['transid'] = $gr['id'];
	        	unset($gr['id']);
			}

	        $gr['txtime'] = $this->gameTimeToServerTime($gr['txtime']);
	        // $gr['createtime'] = $this->gameTimeToServerTime($gr['createtime']);
	        $gr['updatetime'] = $this->gameTimeToServerTime($gr['updatetime']);
	        $gr['result_amount'] = $result_amount;
	        $gr['external_uniqueid'] = $gr['platformtxid'];
            $gr['response_result_id'] = $extra['response_result_id'];
        }
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType)
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
            $sqlTime='`nt`.`txtime` >= ?
          AND `nt`.`txtime` <= ?';
        }

        $sql = <<<EOD
			SELECT
				nt.id as sync_index,
				nt.response_result_id,
				nt.roundid as round,
				nt.userid as username,
				nt.realbetamt as real_betting_amount,
				nt.turnover as valid_bet,
				nt.result_amount,
				nt.gameinfo as bet_details,
				nt.bettype as bet_type,
				nt.txtime as bet_at,
				nt.updatetime as end_at,
				nt.gamecode as game_code,
				nt.external_uniqueid,
				nt.md5_sum,
				nt.txstatus,
				nt.tipInfo,
				nt.tip,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as nt
			LEFT JOIN game_description as gd ON nt.gamecode = gd.external_game_id AND gd.game_platform_id = ?
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

        $extra = [];
        if(!empty($row['tip']) && $row['tip'] > 0){
        	$row['valid_bet'] = 0;
        	$row['result_amount'] = 0;
        	$row['real_betting_amount'] = 0;
        	$extra['note'] = lang("Tip Amount"). ": ".$row['tip'];
        	$row['txstatus'] = Game_logs::STATUS_SETTLED;
        }
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        return [
            'game_info' => [
                'game_type_id' => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type' => isset($row['game_type_id']) ? $row['game_code'] : null,
                'game' => isset($row['game_code']) ? $row['game_code'] : null
            ],
            'player_info' => [
                'player_id' => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username' => isset($row['username']) ? $row['username'] : null
            ],
            'amount_info' => [
                'bet_amount' => isset($row['valid_bet']) ? $this->gameAmountToDB($row['valid_bet']) : null,
                'result_amount' => isset($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : null,
                'bet_for_cashback' => isset($row['valid_bet']) ? $this->gameAmountToDB($row['valid_bet']) : null,
                'real_betting_amount' => isset($row['real_betting_amount']) ? $this->gameAmountToDB($row['real_betting_amount']) : null,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => isset($row['bet_at']) ? $row['bet_at'] : null,
                'end_at' => isset($row['end_at']) ? $row['end_at'] : null,
                'bet_at' => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            //'status' => Game_logs::STATUS_SETTLED,
            'status' => $row['txstatus'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => isset($row['external_uniqueid']) ? $row['external_uniqueid'] :  null,
                'round_number' => isset($row['round']) ? $row['round'] : null,
                'md5_sum' => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id' => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type' => null
            ],
            'bet_details' => isset($row['bet_details']) ? $row['bet_details'] : [],
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

        switch($row['txstatus']) {
            case self::ORIG_CANCELLED_STATUS: //void transaction
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            case self::ORIG_REFUND_STATUS:
                $row['status'] = Game_logs::STATUS_REFUND;
                break;
            default:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
        }
        /** bet details here */
        $bet_details = json_decode($row['bet_details'], true);
        $bet_details['betType'] = $row['bet_type'];

        $row['bet_details'] = $bet_details;
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

	protected function getLauncherLanguage($language)
	{
		$lang='';
		switch ($language) {
			case 1: case 'en': case 'en-us': case 'EN': case "English": $lang = 'en'; break;
			case 2: case 'cn': case 'zh-cn': case 'CN': case "Chinese": $lang = 'cn'; break;
			case 4: case 'vn': case 'vi-vn': case 'VN': case "Vietnamese": $lang = 'vn'; break;
			case 5: case 'ko-kr': case 'ko-kr': case 'KO-KR': case "Korean": $lang = 'ko'; break;
			case 6: case 'th': case 'th-th':  case 'TH': case "Thai": $lang = 'th'; break;
			case 7: case 'id': case 'id-id':  case 'ID': case "Indonesian": $lang = 'id'; break;
			default: $lang = 'en'; break;
		}
		return $lang;
	}

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'external_transaction_id' => $transactionId,
			'gameUsername'=>$gameUsername,
			'playerId'=>$playerId
		);

		$params = array(
			'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'txCode' => $transactionId
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction( $params ){
		$statusCode = $this->getStatusCodeFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['reason_id'] = $this->getErrorCode($statusCode);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}
		return array($success, $result);
	}

	public function getErrorCode($apiErrorCode) {
		$reasonCode = self::REASON_UNKNOWN;

		switch ((int)$apiErrorCode) {
			case self::STATUS_FAIL:
				$reasonCode = self::REASON_FAILED_FROM_API;
				break;
			case self::STATUS_LANGUAGE_IS_NOT_EXISTS:
			case self::STATUS_INVALID_DATE_TIME_FORMAT:
			case self::STATUS_PLEASE_INPUT_ALL_DATA:
			case self::STATUS_INVALID_BET_LIMIT_SETTING:
			case self::STATUS_INVALID_CERTIFICATE:
			case self::STATUS_INVALID_GAMECODE:
			case self::STATUS_INVALID_PARAMETERS:
			case self::STATUS_INVALID_BET:
				$reasonCode = self::REASON_PARAMETER_ERROR;
				break;
			case self::STATUS_INVALID_USER_ID:
				$reasonCode = self::REASON_NOT_FOUND_PLAYER;
				break;
			case self::STATUS_ACCOUNT_EXISTED:
				$reasonCode = self::REASON_USER_ALREADY_EXISTS;
				break;
			case self::STATUS_ACCOUNT_IS_NOT_EXISTS:
				$reasonCode = self::REASON_ACCOUNT_NOT_EXIST;
				break;
			case self::STATUS_INVALID_CURRENCY:
				$reasonCode = self::REASON_CURRENCY_ERROR;
				break;
			case self::STATUS_INVALID_PT_SETTING_WITH_PARENT:
				break;
			case self::STATUS_INVALID_TOKEN:
				$reasonCode = self::REASON_TOKEN_VERIFICATION_FAILED;
				break;
			case self::STATUS_INVALID_AMOUNT:
				$reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case self::STATUS_INVALID_TXCODE:
			case self::STATUS_TXCODE_IS_NOT_EXIST:
				$reasonCode = self::REASON_INVALID_TRANSACTION_ID;
				break;
			case self::STATUS_HAS_PENDING_TRANSFER:
			case self::STATUS_TXCODE_ALREADY_OPERATION:
				$reasonCode = self::REASON_TRANSACTION_PENDING;
				break;
			case self::STATUS_ACCOUNT_IS_LOCK:
			case self::STATUS_ACCOUNT_IS_SUSPEND:
				$reasonCode = self::REASON_GAME_ACCOUNT_LOCKED;
				break;
			case self::STATUS_NOT_ENOUGH_BALANCE:
				$reasonCode = self::REASON_NO_ENOUGH_BALANCE;
				break;
			case self::STATUS_NO_DATA:
				$reasonCode = self::REASON_TRANSACTION_NOT_FOUND;
				break;
			case self::STATUS_UNABLE_TO_PROCEED:
				$reasonCode = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;
			case self::STATUS_IP_ADDRESS_DID_NOT_WHITELIST_YET:
			case self::STATUS_INVALID_DEVICE_TO_CALL_API:
				$reasonCode = self::REASON_IP_NOT_AUTHORIZED;
				break;
			case self::STATUS_SYSTEM_IS_UNDER_MAINTENANCE:
				$reasonCode = self::REASON_API_MAINTAINING;
				break;
			case self::STATUS_DUPLICATE_LOGIN:
				$reasonCode = self::REASON_LOGIN_PROBLEM;
				break;
			case self::STATUS_TIME_DOES_NOT_MEET:
				$reasonCode = self::REASON_INVALID_TIME_RANGE;
				break;
			case self::STATUS_INVALID_AGENT_ID:
				$reasonCode = self::REASON_AGENT_NOT_EXISTED;
				break;
			case self::STATUS_DUPLICATE_TRANSACTION:
				$reasonCode = self::REASON_TRANSACTION_ID_ALREADY_EXISTS;
				break;
			case self::STATUS_TRANSACTION_NOT_FOUND:
				$reasonCode = self::REASON_TRANSACTION_NOT_FOUND;
				break;
			case self::STATUS_REQUEST_TIMEOUT:
				$reasonCode = self::REASON_SERVER_TIMEOUT;
				break;
			case self::STATUS_HTTP_STATUS_ERROR:
			case self::STATUS_SYSTEM_BUSY:
			case self::STATUS_HTTP_RESPONSE_IS_EMPTY:
				$reasonCode = self::REASON_GAME_PROVIDER_NETWORK_ERROR;
				break;
			case self::STATUS_TRANSFER_FAILED:
				break;
			case self::STATUS_GAME_IS_UNDER_MAINTENANCE:
				$reasonCode = self::REASON_API_MAINTAINING;
				break;
			case self::STATUS_PT_SETTING_IS_EMPTY:
			case self::STATUS_INVALID_TIMEZONE:
			case self::STATUS_INVALID_TRANSACTION_STATUS:
			case self::STATUS_BET_HAS_CANCELED:
			case self::STATUS_ADD_ACCOUNT_STATEMENT_FAILED:
				$reasonCode = self::REASON_UNKNOWN;
				break;
		}

		return $reasonCode;
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

	public function syncOriginalGameLogsFromExcel($isUpdate = true){
        set_time_limit(0);
        $this->CI->load->model(array('external_system','original_game_logs_model'));
        require_once dirname(__FILE__) . '/../../../admin/application/libraries/phpexcel/PHPExcel.php';

        $game_logs_path = $this->getSystemInfo('km_game_records_path');
        $directory = $game_logs_path;
        $km_game_logs_excel = array_diff(scandir($directory), array('..', '.'));

        $header = [
            'A'=>'id',
            'B'=>'gametype',
            'C'=>'comm',
            'D'=>'txtime',
            'E'=>'bizdate',
            'F'=>'winamt',
            'G'=>'gameinfo',
            'H'=>'betamt',
            'I'=>'updatetime',
            'J'=>'jackpotwinamt',
            'K'=>'turnover',
            'L'=>'userid',
            'M'=>'bettype',
            'N'=>'platform',
            'O'=>'txstatus',
            'P'=>'jackpotbetamt',
            'Q'=>'createtime',
            'R'=>'platformtxid',
            'S'=>'realbetamt',
            'T'=>'gamecode',
            'U'=>'currency',
            'V'=>'transid',
            'W'=>'realwinamt',
            'X'=>'roundid',
            'Y'=>'result_amount',
            'Z'=>'response_result_id',
            'AA'=>'external_uniqueid',
            'AB'=>'created_at',
            'AC'=>'updated_at',
            'AD'=>'md5_sum'
        ];

        $count = 0;
        $excel_data = [];
        if(!empty($km_game_logs_excel)){
            foreach ($km_game_logs_excel as $file_name) {

                $file = explode(".", $file_name);
                $obj_php_excel = PHPExcel_IOFactory::load($directory . "/" . $file_name);
                $cell_collection = $obj_php_excel->getActiveSheet()->getCellCollection();

                foreach ($cell_collection as $cell) {
                    ini_set('memory_limit', '-1');
                    $column = $obj_php_excel->getActiveSheet()->getCell($cell)->getColumn();
                    $row = $obj_php_excel->getActiveSheet()->getCell($cell)->getRow();
                    $data_value = $obj_php_excel->getActiveSheet()->getCell($cell)->getValue();

                    if ($row == 1) continue;

                    $excel_data[$row][$header[$column]] = $data_value;
                }

            }
            if(!empty($excel_data)){
                foreach ($excel_data as $record) {
                    // print_r($excel_data);
                    $count++;
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
            }
            $result = array('data_count'=>$count);
            return array("success" => true,$result);
        }

    }

    public function getTipTxnByTxTime($token) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$startDate = $startDate->format('Y-m-d\TH:i:s')."+08:00";
		$endDate   = $endDate->format('Y-m-d\TH:i:s')."+08:00";

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetTipTxnByTxTime',
		);

		$params = array(
        	'cert' => $this->cert,
        	'agentId' => $this->agent_id,
        	'startTime' => $startDate,
        	'endTime' => $endDate,
        	'platform' => $this->platform,
        );



		return $this->callApi(self::API_syncTipRecords, $params, $context);
	}

	public function processResultForGetTipTxnByTxTime($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $arrayResult, $statusCode);
		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
		);
		if($success){
			$tipRecords = isset($arrayResult['transactions']) ? $arrayResult['transactions'] : null;
			if(!empty($tipRecords)){
				$this->processTipRecords($tipRecords, $responseResultId);
				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                $this->original_gamelogs_table,
	                $tipRecords,
	                'external_uniqueid',
	                'external_uniqueid',
	                ['tipInfo', 'txtime'],
	                'md5_sum',
	                'id',
	                ['tip']
	            );

	            $this->CI->utils->debug_log('after process available rows', 'tipRecords ->',count($tipRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

	            $dataResult['data_count'] = count($tipRecords);
				if (!empty($insertRows)) {
					$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
				}
				unset($insertRows);

				if (!empty($updateRows)) {
					$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
				}
				unset($updateRows);
			}
		}
		return array($success, $dataResult);
	}

	public function processTipRecords(&$tipRecords, $responseResultId) {
		if(!empty($tipRecords)){
			foreach($tipRecords as $index => $record) {

				$data['userid'] = isset($record['userId']) ? $record['userId'] : null;
				$data['createtime'] = isset($record['txTime']) ? $this->gameTimeToServerTime($record['txTime']) : null;
				$data['updatetime'] = isset($record['txTime']) ? $this->gameTimeToServerTime($record['txTime']) : null;
				$data['txtime'] = isset($record['txTime']) ? $this->gameTimeToServerTime($record['txTime']) : null;
				$data['tipInfo'] = isset($record['tipInfo']) ? $record['tipInfo'] : null;
				$data['tip'] = isset($record['tip']) ? $record['tip'] : null;
				$data['platform'] = isset($record['platform']) ? $record['platform'] : null;
				$data['currency'] = isset($record['currency']) ? $record['currency'] : null;
				$data['gamecode'] = isset($record['gameCode']) ? $record['gameCode'] : null;
				$data['platformtxid'] = isset($record['platformTxId']) ? $record['platformTxId'] : null;
				$data['gametype'] = isset($record['gameType']) ? $record['gameType'] : null;


				$tipInfo = json_decode($record['tipInfo'], true);
				$data['external_uniqueid'] = isset($tipInfo['giftId']) ? $tipInfo['giftId'] : null;
				$data['response_result_id'] = $responseResultId;
				$tipRecords[$index] = $data;
				unset($data);
			}
		}
	}

    public function updatePlayerBetLimitRange($request_params) {
        if (empty($request_params['game_username'])) {
            return [
                'success' => false,
                'message' => 'game_username required',
            ];
        }

        if (empty($request_params['bet_limit'])) {
            return [
                'success' => false,
                'message' => 'bet_limit required',
            ];
        }

        $game_username = $request_params['game_username'];
        $bet_limit = $request_params['bet_limit'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetLimitRange',
            'game_username' => $game_username,
        ];

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agent_id,
            'userId' => $game_username,
            'betLimit' => json_encode($bet_limit),
        ];

        $this->utils->debug_log(__METHOD__,$context, $params);

        return $this->callApi(self::API_setMemberBetSetting, $params, $context);
    }

    public function processResultForUpdatePlayerBetLimitRange($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        return array($success, $resultArr);
    }
}
