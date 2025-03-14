<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * API NAME: ON_CASINO_GAME_API
 * Ticket:
 * https://tripleonetech2.atlassian.net/browse/OGP-33798
 * 
 * @category Game_platform
 * @copyright 2013-2024 tot
 * @integrator @wendell.php.ph
 **/

abstract class Abstract_game_api_common_on_casino extends Abstract_game_api
{
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	const ORIGINAL_TABLE = 'on_casino_game_logs';

	const CODE_SUCCESS = 0;
	const START_PAGE = 1;
	const TIMEZONE = 8; #GMT + 8
	const DEFAULT_RECORD_PER_PAGE = 1000;

	const URI_MAP = [
		self::API_depositToGame => '/api/game-center/transfer',
		self::API_withdrawFromGame => '/api/game-center/transfer',
		self::API_queryPlayerBalance => "/api/game-center/balance",
		self::API_isPlayerExist=> "/api/game-center/balance",
		self::API_createPlayer => "/login",
		self::API_queryForwardGame => "/login",
		self::API_syncGameRecords => "/api/game-center/betRecords",
		self::API_queryTransaction => "/api/game-center/queryTransfer",
	];

	const LOGIN_SRC_WEB 			= 0;
	const LOGIN_SRC_MOBILE_BROWSER	= 1;
	const LOGIN_SRC_ANDROID 		= 2;
	const LOGIN_SRC_IOS 			= 3;

	const TRANSFER_TYPE_DEPOSIT 	= 0;
	const TRANSFER_TYPE_WITHDRAW 	= 1;

	const GAME_STATUS_UNSETTLED		= 0;
	const GAME_STATUS_SETTLED		= 1;
	const GAME_STATUS_CANCELLED		= 3;

	const GAME_RESULT_LOSE			= 0;
	const GAME_RESULT_WIN			= 1;
	const GAME_RESULT_TIE			= 2;

	private $originalTable;
	private $method 				= self::METHOD_POST;

	# Fields in on_casino_game_logs we want to detect changes for update
	const MD5_FIELDS_FOR_ORIGINAL = [
		'stake',
		'game_result',
		'odds',
		'gross_win',
		'round_number',
		'net_amount',
		'state',
		'valid_bet_amount',
		'settle_time',
		'win_lose',
		'order_number',
		'order_id',
	];

	# Values of these fields will be rounded when calculating MD5
	const MD5_FLOAT_AMOUNT_FIELDS = [
		'net_amount',
		'valid_bet_amount',
		'win_lose',
		'stake',
	];

	# Fields in game_logs we want to detect changes for merge and when md5_sum
	const MD5_FIELDS_FOR_MERGE = [
		'bet_amont',
		'before_balance',
		'after_balance',
		'result_amount',
		'win_amount',
		'loss_amount',
		'bet_forcashback',
		'status',
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'bet_amont',
		'result_amount',
	];

	private $platform_code;
	private $agent_account;
	private $interface_key;
	private $currency;
	private $language;
	private $x_lang;
	private $api_url;
	private $demo_api_url;
	private $gameLogsStatus;
	private $force_language;
	private $game_launch_url;
	private $prefix_for_username;
	private $game_launch_path;
	private $sync_time_interval;
	private $adjust_datetime_minutes;
	private $sync_sleep_time;
	private $full_url = null;
	private $headers = null;
	private $home_link;
	public $force_disable_home_link;
	public $disable_home_link;
	private $tester_white_player_list = [];

	public function __construct()
	{
		parent::__construct();
		
		$this->originalTable = 'on_casino_game_logs';

		$this->platform_code = $this->getSystemInfo('platform_code', '');
		$this->agent_account = $this->getSystemInfo('agent_account', '');
		$this->interface_key = $this->getSystemInfo('interface_key', '');
		$this->currency = $this->getSystemInfo('currency', 'CNY');
		$this->game_launch_url = $this->getSystemInfo('game_launch_url', '');
		$this->game_launch_path = $this->getSystemInfo('game_launch_path', '/api/game-center/login');
		$this->prefix_for_username = $this->getSystemInfo('prefix_for_username', '');
		$this->language = $this->getSystemInfo('language', 'en');
		$this->x_lang = $this->getSystemInfo('x_lang', 'en');
		$this->api_url = $this->getSystemInfo('url', '');
		$this->demo_api_url = $this->getSystemInfo('demo_api_url', 'https://third-casino.cx-ongame.org');
		$this->tester_white_player_list = $this->getSystemInfo('tester_white_player_list', []);
		
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
		$this->adjust_datetime_minutes = $this->getSystemInfo('adjust_datetime_minutes', '10');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 1);

		$this->home_link = $this->getSystemInfo('home_link','');
		$this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
		$this->force_language = $this->getSystemInfo('force_language', '');
		$this->disable_home_link = $this->getSystemInfo('disable_home_link', false);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$apiMethod = self::API_createPlayer;

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);
			
		 
		$language = $this->language;
		if(isset($extra['language']) && !empty($extra['language'])){
            $language = $extra['language'];
        }else{
            $language = $this->language;
        }

		if($this->force_language && !empty($this->force_language)){
            $language = $this->force_language;
        }	
		$language = $this->getLauncherLanguage($language);
		$this->x_lang = $language;

		$tempParams = [
			"lang" => (string)$this->language,
			"userName" => (string)$gameUsername,
			"loginSrc" => (int)self::LOGIN_SRC_WEB,
			"agent" => (string)$this->agent_account,
		];
		$params = $this->rebuildParams($tempParams);
		$this->method = self::METHOD_POST;
		return $this->callApi($apiMethod, $params, $context);

    }

	public function processResultForCreatePlayer($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			'player' => $gameUsername,
			'exists' => false
		);

		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists'] = true;
		}

		return array($success, $result);
	}

	#-------- HELPERS -----------

	public function generateUrl($apiName, $params)
	{
		$uri = self::URI_MAP[$apiName];
		$url = $this->api_url . $uri;

		if(in_array($apiName, [self::API_queryForwardGame, self::API_createPlayer])){
			$url = $this->buildGameLaunchUrl();
		}

		if(in_array($apiName, [self::API_queryPlayerBalance, self::API_queryTransaction, self::API_isPlayerExist, self::API_syncGameRecords])){
			$url = $url.'?'.http_build_query($params);
		}
		$this->full_url = $url;
		return $url;
	}

	public function getHttpHeaders($params = [])
	{
		$this->headers =  array(
			"Content-Type" => "application/json",
			"charset" => "UTF-8",
			"x-session-platform-code" => $this->platform_code,
			"x-lang" => $this->x_lang
		);
		return $this->headers;
	}

	protected function customHttpCall($ch, $params)
	{
		switch($this->method){
			case self::METHOD_POST:
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				break;
			case self::METHOD_GET:
				$params=http_build_query($params);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_GET);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;

		}
	}

	public function getPlatformCode()
	{
		return ON_CASINO_GAME_API;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
	{
		$success = false;
		$success = (isset($resultArr['success']) && $resultArr['success'] == true) && (isset($resultArr['code']) && $resultArr['code'] == self::CODE_SUCCESS);

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('Error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	private function buildGameLaunchUrl(){
		$url = $this->game_launch_url;
		if(empty($this->game_launch_url)){
			$url = $this->api_url . $this->game_launch_path;
			$this->custom_debug_log("build game_launch url based on game_launch_path" , $url);
		}else{
			$this->custom_debug_log("use game_launch url from extra_info", $url);
		}
		return $url;
	}

	private function custom_debug_log($message, $data=[], $extra=null){
		$message = "ON_CASINO_GAME_API: " . $message;
		return $this->CI->utils->debug_log($message,  $data, $extra);
	}
	private function rebuildParams($data=[]){
		$dataQueryString = '';
		$nonceStr = $this->generateRandomString(5);
		if(!empty($data)){
			$data['nonceStr'] = $nonceStr;
			ksort($data);
			$dataQueryString = http_build_query($data).'&key='.$this->interface_key;
		}

		$defaultParams = [
			"nonceStr" => $nonceStr,
			"sign" 	   => $this->generateSign($dataQueryString),
		];

		return array_merge($data, $defaultParams);
	}

	private function generateRandomString($length = 5) {
		return substr(bin2hex(random_bytes($length)), 0, $length);
	}

	private function generateSign($string){
		return md5($string);
	}


	/**
	 * @param string playerName
	 * @param double amount
	 * @return array ("success"=>boolean, 'external_transaction_id'=>string)
	 */
	public function depositToGame($playerName, $amount, $transfer_secure_id = null)
	{
		$amount = $this->gameAmountToDBTruncateNumber($amount);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id,
		);

		$tempParams = array(
			"platformOrderNo" => $transfer_secure_id,
			"money"			=> $amount,
			"transferType" => self::TRANSFER_TYPE_DEPOSIT,
			"userName"		=> $gameUsername,
			"agent"			=> $this->agent_account
		);

		$this->custom_debug_log("@depositToGame raw params", $tempParams);
		$params = $this->rebuildParams($tempParams);
		$this->custom_debug_log("@depositToGame rebuildParams", $params);
		$this->method = self::METHOD_POST;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params)
	{
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$statusCode = $this->getStatusCodeFromParams($params);
		if($success){
			if(isset($resultArr['platformOrderNo'])){
				$external_transaction_id = $$resultArr['platformOrderNo'];
			}
		}

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		$extra =[
			'playerName' => $playerName
		];
		if($this->verify_transfer_using_query_transaction){
			$success = $this->queryTransaction($external_transaction_id,$extra)['success'];
		}

		if ($success) {
			$result['didnot_insert_game_logs'] = true;
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}	

		if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
            $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $success=true;
        }
		return array($success, $result);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
	{
		$amount = $this->gameAmountToDBTruncateNumber($amount);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id,
		);

		$tempParams = array(
			"platformOrderNo" => $transfer_secure_id,
			"money"			=> $amount,
			"transferType" => self::TRANSFER_TYPE_WITHDRAW,
			"userName"		=> $gameUsername,
			"agent"			=> $this->agent_account
		);

		$this->custom_debug_log("@withdrawFromGame raw params", $tempParams);
		$params = $this->rebuildParams($tempParams);
		$this->custom_debug_log("@withdrawFromGame rebuildParams", $params);
		$this->method = self::METHOD_POST;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawToGame($params)
	{
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		if($success){
			if(isset($resultArr['platformOrderNo'])){
				$external_transaction_id = $$resultArr['platformOrderNo'];
			}
		}
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		#extra info for query transaction
		$extra =[
			'playerName' => $playerName
		];
		if($this->verify_transfer_using_query_transaction){
			$success = $this->queryTransaction($external_transaction_id,$extra)['success'];
		}

		if ($success) {
			$result['didnot_insert_game_logs'] = true;
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}	

		if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
            $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $success=true;
        }

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);
		$tempParams = array(
			"userName" => $gameUsername,
			"agent"			=> $this->agent_account
		);
		$this->custom_debug_log("@queryPlayerBalance raw params", $tempParams);
		$params = $this->rebuildParams($tempParams);
		$this->custom_debug_log("@queryPlayerBalance rebuildParams", $params);
		$this->method = self::METHOD_GET;
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
		);

		if ($success) {
			$balance = isset($resultArr['t']['balance']) ? $resultArr['t']['balance'] : 0;
			$result['balance'] = $this->gameAmountToDBTruncateNumber(floatval($balance));
		} else {
			$code = isset($resultArr['code']) ? $resultArr['code'] : 0;
			$result['status'] = $code;
		}

		return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra)
	{
		$this->custom_debug_log('@queryTransaction',['transaction_id' => $transactionId, 'extra' => $extra]);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForqueryTransaction',
			'external_transaction_id' => $transactionId,
		);

		$tempParams = array(
			"platformOrderNo" => $transactionId,
			"agent"			=> $this->agent_account
		);
		$this->custom_debug_log("@queryTransaction raw params", $tempParams);
		$params = $this->rebuildParams($tempParams);
		$this->custom_debug_log("@queryTransaction rebuildParams", $params);
		$this->method = self::METHOD_GET;
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params)
    {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

		$this->custom_debug_log("@processResultForQueryTransaction raw response: ", $resultArr);
        $result = array(
			'response_result_id'     => $responseResultId,
			'external_transaction_id'=> $external_transaction_id,
			'status'                 => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'              => self::REASON_UNKNOWN
		);

		$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
        if($success){
            $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }
        return array($success, $result);
    }


	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$apiMethod = self::API_queryForwardGame;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'playerId' => $this->getPlayerIdFromUsername($playerName),
		);

		$language = $this->language;
		if(isset($extra['language']) && !empty($extra['language'])){
            $language = $extra['language'];
        }else{
            $language = $this->language;
        }

		if($this->force_language && !empty($this->force_language)){
            $language = $this->force_language;
        }	
		$language = $this->getLauncherLanguage($language);
		$this->x_lang = $language;

		$loginSrc = (int)self::LOGIN_SRC_WEB;
		if(isset($extra['is_mobile']) && $extra['is_mobile']){
			$loginSrc = (int)self::LOGIN_SRC_MOBILE_BROWSER;
		}

		$tempParams = [
			"lang" => (string)$this->language,
			"userName" => (string)$gameUsername,
			"loginSrc" => $loginSrc,
			"agent" => (string)$this->agent_account,
			"testFlag" => 0
		];

		if(isset($extra['home_link']) && !empty($extra['home_link'])) {
            $this->home_link = $extra['home_link'];
			$tempParams['backUrl'] =  $this->home_link; 
        }

		if($this->checkIfTestPlayer($playerName)){
			$tempParams['testFlag'] =  1; 
		}

		$params = $this->rebuildParams($tempParams);
		$this->method = self::METHOD_POST;
		return $this->callApi($apiMethod, $params, $context);
	}

	public function processResultForQueryForwardGame($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);


		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			'player' => $gameUsername,
		);

		if($success){
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			if(isset($resultArr['t']['token']) && isset($resultArr['t']['domain']))
            {
                $result['url'] = $resultArr['t']['domain'].$resultArr['t']['token'];
            }
		}

		return array($success, $result);
	}

	private function checkIfTestPlayer($playerUserName){
        return in_array($playerUserName, $this->tester_white_player_list);
    }

	public function getLauncherLanguage($language) {
        $lang = '';
        $language = strtolower($language);
        switch($language)
        {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en';
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
            case 'zh_CNY':
                $lang = 'zh-CN';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
			case 'vi':
            case 'vi-vi':
            case 'vi-vn':
            case 'vi_vn':
                $lang = 'vi';
                break;

            case Language_function::INT_LANG_INDONESIAN:
            case 'id-ID':
            case 'id-id':
                $lang = 'id-ID';
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
			case 'th-TH':
                $lang = 'th-TH';
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
			case 'ko-kr':
                $lang = 'ko';
                break;
            default:
                $lang = 'en';
                break;
        }

        return $lang;
	}
	
	public function syncOriginalGameLogs($token = false)
	{
		$this->custom_debug_log('@syncOriginalGameLogs', $token);
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->modify("+$this->adjust_datetime_minutes minutes")->format('Y-m-d H:i:s');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i:s");
    	# Query Exact end
    	if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
    	}

		$success = false;
		while ($queryDateTimeMax  > $queryDateTimeStart) {
			$success = $this->processGameHistory($queryDateTimeStart, $queryDateTimeEnd);

			sleep($this->sync_sleep_time);
			$queryDateTimeStart = $queryDateTimeEnd;
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify("+$this->adjust_datetime_minutes minutes")->format('Y-m-d H:i:s');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
	    	}

			$this->custom_debug_log("@syncOriginalGameLogs start_end_time: ", ["start" => $queryDateTimeStart, "end" => $queryDateTimeEnd]);
		}

		$this->custom_debug_log('@syncOriginalGameLogs (formatted)', $startDate, $endDate);

		return array('success' => $success);
	}

	public function processGameHistory($startDate, $endDate)
	{			
		$this->custom_debug_log("@processGameHistory raw start_date: $startDate , raw end_date: $endDate");

		$startDateFormatted = strtotime($startDate) * 1000;
		$endDateFormatted = strtotime($endDate) * 1000;

		

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs'
		);

		$totalPage = 0;
		$totalCount = 0;
		$done = false;
		$success = false;
		$apiError = false;
		$currentPage = self::START_PAGE;

		while (!$done) {
			$rawDateTimeParams = array(
				"startTime"		=> $startDate,
				"endTime"		=> $endDate,
				"agent"			=> $this->agent_account
			);
			$tempParams = array(
				"startTime"		=> $startDateFormatted,
				"endTime"		=> $endDateFormatted,
				"agent"			=> $this->agent_account
			);
	
			$this->custom_debug_log("@processGameHistory rawDateTimeParams params", $rawDateTimeParams);
			$this->custom_debug_log("@processGameHistory raw params", $tempParams);
			$params = $this->rebuildParams($tempParams);

			$this->custom_debug_log("@processGameHistory rebuildParams", $params);
			$this->method = self::METHOD_GET;

			$this->custom_debug_log("@processGameHistory final params:", $params);
			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			
			// if(count($api_result['total']) > 1){
			// 	$this->custom_debug_log("@processGameHistory not empty result:", $params , $api_result);
			// }
			$this->custom_debug_log("@processGameHistory API call result: " , ["params" => json_encode($params),"response" => json_encode($api_result)]);

			if ($api_result && $api_result['success']) {
		
				$totalCount = isset($api_result['total']) ? $api_result['total'] : 0;

				$totalPage = $totalCount / self::DEFAULT_RECORD_PER_PAGE;

				if($totalPage != 0 && $totalPage <= 1){
					$totalPage = 1;
				}

				$this->custom_debug_log("@processGameHistory", ['total_records' => $totalCount, 'total_page' => $totalPage]);		

				if($totalCount = 0){
					$done = true;
				}
			
				$done = $currentPage >= $totalPage;
				//next page
				$currentPage += 1;
				$success = true;
	
			} else {
				$apiError = true;
				$done = true;
				$success = false;
			}

		$this->CI->utils->debug_log("@processGameHistory: " . ' currentPage: ', $currentPage, 'totalCount', $totalCount, 'totalPage', $totalPage, 'done', $done, 'result', $api_result, 'params_executing', $params);

			if ($apiError) {
				$done = true;
				$success = false;
			} else {
				$success = true;
			}
		}

		return $success;
	}

	public function processResultForSyncOriginalGameLogs($params)
	{		
		$this->CI->load->model('original_game_logs_model');
		$resultArr = $this->getResultJsonFromParams($params);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);		

		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['data_count' => 0];

		$response = $gameRecords = isset($resultArr['rows']) ? $resultArr['rows']: [];

		$result['data_count'] = isset($resultArr['total']) ? $resultArr['total'] : 0;

		$result['response'] = $response;

		if ($success && !empty($gameRecords)) {
			$extra = ['response_result_id' => $responseResultId];

			$records = $this->rebuildGameRecords($gameRecords, $extra);

			list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
				$this->originalTable,
				$records,
				'external_uniqueid',
				'external_uniqueid',
				self::MD5_FIELDS_FOR_ORIGINAL,
				'md5_sum',
				'id',
				self::MD5_FLOAT_AMOUNT_FIELDS
			);

		$this->CI->utils->debug_log('ON_CASINO_GAME_API @processResultForSyncOriginalGameLogs after process available rows', count($gameRecords), count($insertRows), count($updateRows));

			unset($gameRecords);

			if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs(
					$insertRows,
					'insert',
					['responseResultId' => $responseResultId],
					$this->originalTable
				);
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs(
					$updateRows,
					'update',
					['responseResultId' => $responseResultId],
					$this->originalTable
				);
			}
			unset($updateRows);

			$result['response'] = $response;
			$result['totalCount'] = isset($response['total']) ? $response['total'] : 1;
		}
		$this->custom_debug_log('@processResultForSyncOriginalGameLogs result', $result);
			return array($success, $result);		
		}

	private function rebuildGameRecords(&$gameRecords, $extra)
	{
		if (!empty($gameRecords)) {
			foreach ($gameRecords as $index => $gameRecord) {
				$data['order_id'] = isset($gameRecord['id']) ? $gameRecord['id'] : null;
				$data['order_number'] = isset($gameRecord['orderNo']) ? $gameRecord['orderNo'] : null;
				$data['username'] = isset($gameRecord['userName']) ? $gameRecord['userName'] : null;
				$data['stake'] = isset($gameRecord['stake']) ? $gameRecord['stake'] : null;
				$data['play'] = isset($gameRecord['play']) ? $gameRecord['play'] : null;
				$data['round_number'] = isset($gameRecord['roundNo']) ? $gameRecord['roundNo'] : null;
				$data['result'] = isset($gameRecord['result']) ? $gameRecord['result'] : null;
				$data['game_result'] = isset($gameRecord['gameResult']) ? $gameRecord['gameResult'] : null;
				$data['odds'] = isset($gameRecord['odds']) ? $gameRecord['odds'] : null;
				$data['other_odds'] = isset($gameRecord['otherOdds']) ? $gameRecord['otherOdds'] : null;
				$data['gross_win'] = isset($gameRecord['grossWin']) ? $gameRecord['grossWin'] : null;
				$data['net_amount'] = isset($gameRecord['netAmount']) ? $gameRecord['netAmount'] : null;
				$data['state'] = isset($gameRecord['state']) ? $gameRecord['state'] : null;
				$data['valid_bet_amount'] = isset($gameRecord['validBetAmount']) ? $gameRecord['validBetAmount'] : null;
				$data['currency'] = isset($gameRecord['currency']) ? $gameRecord['currency'] : null;
				$data['table_number'] = isset($gameRecord['tableNo']) ? $gameRecord['tableNo'] : null;
				$data['game_id'] = isset($gameRecord['gameId']) ? $gameRecord['gameId'] : null;
				$data['add_time'] = isset($gameRecord['addTime']) ? $gameRecord['addTime'] : null;
				$data['open_time'] = isset($gameRecord['openTime']) ? $gameRecord['openTime'] : null;
				$data['settle_time'] = isset($gameRecord['settleTime']) ? $gameRecord['settleTime'] : null;
				$data['update_time'] = isset($gameRecord['updateTime']) ? $gameRecord['updateTime'] : null;
				$data['ip'] = isset($gameRecord['ip']) ? $gameRecord['ip'] : null;
				$data['device'] = isset($gameRecord['device']) ? $gameRecord['device'] : null;
				$data['win_lose'] = isset($gameRecord['winLose']) ? $gameRecord['winLose'] : null;
				$data['raw_data'] = isset($gameRecord) ? json_encode($gameRecord) : null;
				$data['full_url'] = $this->full_url;
				$data['headers'] = json_encode($this->headers);
				$data['request_id'] = $this->transfer_request_id;
				$data['game_platform_id'] = $this->getPlatformCode();
				$data['game_username'] = $data['username'];
				$data['player_id'] =  $this->getPlayerIdByGameUsername($data['game_username']);
				//extra info from SBE
				$data['external_uniqueid'] = isset($gameRecord['id']) ? $gameRecord['id'] : null;
				$data['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
				$data['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$data['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				// $data['decrypted_timezone'] = $data['settle_time'];
				$data['readable_add_time'] = $this->convertMillisecondsToReadableDateTime($data['add_time']);
				$data['readable_open_time'] = $this->convertMillisecondsToReadableDateTime($data['open_time']);
				$data['readable_settle_time'] = $this->convertMillisecondsToReadableDateTime($data['settle_time']);
				$data['readable_update_time'] = $this->convertMillisecondsToReadableDateTime($data['update_time']);

				$dataRecords[] = $data;
				$gameRecords[$index] = $data;
				
				unset($data);
			}
			return $dataRecords;
		}
	}

	private function convertMillisecondsToReadableDateTime($milliseconds){
		$seconds = floor($milliseconds / 1000);
		$date = new DateTime("@$seconds");
		// Set the timezone if necessary (default is UTC)
		$currentTimezone = $this->CI->utils->getConfig('current_php_timezone');
		$date->setTimezone(new DateTimeZone($currentTimezone));

		return  $date->format('Y-m-d H:i:s');
	}

	private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo = [])
	{
		$dataCount = 0;
		if (!empty($rows)) {
			foreach ($rows as $key => $record) {
				if ($update_type == 'update') {
					$this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
				} else {
					$this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
				}
				$dataCount++;
				unset($record);
			}
		}
		return $dataCount;
	}

	public function syncMergeToGameLogs($token)
	{
		$enabled_game_logs_unsettle = true;
		return $this->commonSyncMergeToGameLogs(
			$token,
			$this,
			[$this, 'queryOriginalGameLogs'],
			[$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
			[$this, 'preprocessOriginalRowForGameLogs'],
			$enabled_game_logs_unsettle
		);
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time=true)
	{
		$this->custom_debug_log("@queryOriginalGameLogs: dateRange: " . $dateFrom . '-' . $dateTo);

		$sqlTime = '`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

		if ($use_bet_time) {
			$sqlTime = '`original`.`readable_add_time` >= ? AND `original`.`readable_add_time` <= ?';
		}

		$this->custom_debug_log('@queryOriginalGameLogs:  sqlTime ===>', $sqlTime);

		$sql = <<<EOD
		SELECT
			original.id as sync_index,
			original.readable_add_time as start_at,
			original.readable_add_time as bet_at,
			original.readable_settle_time as end_at,
			original.order_id,
			original.order_number,
			original.stake as bet_amount,
			original.stake as real_betting_amount,
			original.valid_bet_amount,
			original.game_username,
			original.player_id,
			original.round_number as round_id,
			original.currency,
			original.game_id as game_code,
			original.game_result,
			original.win_lose as result_amount,
			original.result,
			original.state as status,
			original.response_result_id,
			original.external_uniqueid,
			original.created_at,
			original.updated_at,
			original.md5_sum,
			original.raw_data,
			original.odds,
			game_provider_auth.player_id,
			gd.id as game_description_id,
			gd.english_name as game_description_name,
			gd.game_type_id
		FROM {$this->originalTable} as original
			LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
			JOIN game_provider_auth ON original.game_username = game_provider_auth.login_name
			AND game_provider_auth.game_provider_id=?
		WHERE  original.game_platform_id=? 
		AND {$sqlTime}

EOD;

		$params = [
			$this->getPlatformCode(),
			$this->getPlatformCode(),
			$this->getPlatformCode(),
			$dateFrom,
			$dateTo
		];

		return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
		if (empty($row['md5_sum'])) {
			$row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow(
				$row,
				self::MD5_FIELDS_FOR_MERGE,
				self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
			);
		}

		$extra = [
			'odds' => $row['odds'],
		];

		$bet_amount = isset($row['bet_amount']) ? $this->dBtoGameAmount($row['bet_amount']) : 0;
		$valid_bet_amount = isset($row['valid_bet_amount']) ? $this->dBtoGameAmount($row['valid_bet_amount']) : 0;
		$real_betting_amount = isset($row['real_betting_amount']) ? $this->dBtoGameAmount($row['real_betting_amount']) : 0;
		$result_amount = isset($row['result_amount']) ? $this->dBtoGameAmount($row['result_amount']) : 0;
		

		// if($row['result'] == self::GAME_RESULT_TIE){
		// 	$extra['note'] = lang('Draw');
		// 	$valid_bet_amount = 0;
		// }
		$rawStatus = isset($row['status']) ? $row['status'] : 1;
		$status = $this->convertGameStatusToGameLogsStatus($rawStatus);
		
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
				'player_username'       => isset($row['game_username']) ? $row['game_username'] : null
			],
			'amount_info' => [
				'bet_amount'            => $valid_bet_amount,
				'result_amount'         => $result_amount,
				'bet_for_cashback'      => $valid_bet_amount,
				'real_betting_amount'   => $real_betting_amount,
				'win_amount'            => 0,
				'loss_amount'           => 0,
				'after_balance'         => 0,
			],
			'date_info' => [
				'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
				'end_at'                => isset($row['end_at']) ? $row['end_at'] : null,
				'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
				'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
			],
			'flag'                      => Game_logs::FLAG_GAME,
			'status'                    => $status,
			'additional_info' => [
				'has_both_side'         => 0,
				'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
				'round_number'          => isset($row['round_id']) ? $row['round_id'] : null,
				'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
				'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
				'sync_index'            => $row['sync_index'],
				'bet_type'              => null
			],
			'bet_details' => 			$this->preprocessBetDetails($row,null,true),
			'extra'                     => $extra,
			//from exists game logs
			'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
			'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
		];
	}

	private function convertGameStatusToGameLogsStatus($status){
		switch($status){
			case self::GAME_STATUS_UNSETTLED:
				return Game_Logs::STATUS_UNSETTLED;
				break;
			case self::GAME_STATUS_SETTLED:
				return Game_Logs::STATUS_SETTLED;
				break;
			case self::GAME_STATUS_CANCELLED:
				return Game_Logs::STATUS_CANCELLED;
				break;
		}
	}

	public function preprocessOriginalRowForGameLogs(array &$row)
	{
		if (empty($row['game_description_id'])) {
			$unknownGame = $this->getUnknownGame($this->getPlatformCode());
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
			$row['game_description_id'] = $game_description_id;
			$row['game_type_id'] = $game_type_id;
		}
		// $row["status"] = Game_logs::STATUS_SETTLED;
	}

	public function getGameDescriptionInfo($row, $unknownGame)
	{
		$game_description_id = null;
		$external_game_id = $row['game_code'];
		$extra = array('game_code' => $external_game_id);

		$game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
		$game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id,
			$game_type_id,
			$external_game_id,
			$game_type,
			$external_game_id,
			$extra,
			$unknownGame
		);
	}
	/**
	 * @param string playerName
	 * @return array ("success"=>boolean, "exist"=>boolean)
	 */
	public function isPlayerExist($playerName)
	{
		$this->custom_debug_log('@isPlayerExist', $playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$tempParams = array(
			"userName" => $gameUsername,
			"agent"			=> $this->agent_account
		);
		$this->custom_debug_log("@isPlayerExist raw params", $tempParams);
		$params = $this->rebuildParams($tempParams);
		$this->custom_debug_log("@isPlayerExist rebuildParams", $params);
		$this->method = self::METHOD_GET;

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function processResultForIsPlayerExist($params)
	{
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			"exists" => false
		);

		if ($success) {
			$balance = isset($resultArr['t']['balance']) ? $resultArr['t']['balance'] : 0;
			$result['exists'] = true;
			$result['balance'] = $this->gameAmountToDBTruncateNumber(floatval($balance));
		} else {
			$result['code'] = isset($resultArr['code']) ? $resultArr['code'] : 0;
			$result['exists'] = false;
		}

		return array($success, $result);
	}


	public function defaultBetDetailsFormat($row) {
        $bet_details = [];


        if (isset($row['odds'])) {
            $bet_details['odds'] = $row['odds'];
        }
        if (isset($row['game_description_name'])) {
            $bet_details['game_name'] = $row['game_description_name'];
        }

        if (isset($row['external_uniqueid'])) {
            $bet_details['bet_id'] = $row['external_uniqueid'];
        }

        if (isset($row['order_number'])) {
            $bet_details['round_id'] = $row['order_number'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] =  $this->gameAmountToDBTruncateNumber($row['bet_amount']);
        }

        if (isset($row['bet_result'])) {
            $bet_details['game_result'] = $row['result'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }
        return $bet_details;
    }

}
