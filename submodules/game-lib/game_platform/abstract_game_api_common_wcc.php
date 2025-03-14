<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * API NAME: WCC
 * API docs:
 * https://drive.google.com/file/d/1Y57rs9hHrMPY1VXSSMLL_4APoIk8oP_K/view
 * Ticket:
 * https://tripleonetech2.atlassian.net/jira/software/c/projects/OGP/issues/OGP-32577
 * 
 *
 * @category Game_platform
 * @copyright 2013-2024 tot
 * @integrator @wendell.php.ph
 **/

abstract class Abstract_game_api_common_wcc extends Abstract_game_api
{
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';

	const GENERAL_STATUS_CODE_SUCCESS = 200;
	const GENERAL_STATUS_CODE_ERROR = 419;

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	const ORIGINAL_TABLE = 'wcc_game_logs';

	const START_PAGE = 1;
	const TIMEZONE = 8; #GMT + 8
	const DEFAULT_RECORD_PER_PAGE = 1000;
	const UNIQUE_GAME_CODE = "cockfight01";

	const URI_MAP = [
		self::API_depositToGame => '/cash_in',
		self::API_withdrawFromGame => '/cash_out',
		self::API_queryPlayerBalance => "/check_balance",
		self::API_isPlayerExist=> "/check_balance",
		self::API_createPlayer => "/COCKFIGHT01/play_now",
		self::API_queryForwardGame => "/{}/play_now",
		self::API_syncGameRecords => "/bet_history",
		self::API_queryTransaction => "/check_transaction",
		self::API_logout => '/sign_out',
	];


	private $originalTable;

	# Fields in wcc_game_logs we want to detect changes for update
	const MD5_FIELDS_FOR_ORIGINAL = [
		'bet_amount',
		'fight_no',
		'bet_id',
		'round_id',
		'bet_code',
		'winner',
		'odds',
		'bet_result',
	];

	# Values of these fields will be rounded when calculating MD5
	const MD5_FLOAT_AMOUNT_FIELDS = [
		'bet_amont',
		'payout_amount',
		'refund_amount',
		'wallet_amount',
	];

	# Fields in game_logs we want to detect changes for merge and when md5_sum
	const MD5_FIELDS_FOR_MERGE = [
		'bet_amont',
		'payout_amount',
		'refund_amount',
		'wallet_amount',
		'round_id',
		'balance',
		'result_amount',
		'bet_result',
		'odds'
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
	];

	private $site_id;
	private $secret_key;
	private $currency;
	private $language;
	private $api_url;
	private $sync_time_interval;
	private $sync_sleep_time;
	private $callback_url;
	private $gameLogsStatus;
	private $force_language;

	public function __construct()
	{
		parent::__construct();
		$this->CI->load->model(['game_logs', 'transactions', 'external_common_tokens']);

		$this->gameLogsStatus = [
            'win' => GAME_LOGS::STATUS_SETTLED,
            'lose' => GAME_LOGS::STATUS_SETTLED,
            'cancel' => GAME_LOGS::STATUS_CANCELLED,
            'draw' => GAME_LOGS::STATUS_SETTLED,
        ];
		
		$this->originalTable = 'wcc_game_logs';

		$this->site_id = $this->getSystemInfo('site_id', 'xiaohao4VNDK');
		$this->secret_key = $this->getSystemInfo('secret_key', 'cl0C78TuN3j9F6vUgirWpYAm2PnJ4HXy');

		$this->currency = $this->getSystemInfo('currency', 'CNY');

		$this->language = $this->getSystemInfo('language', 'en');

		$this->api_url = $this->getSystemInfo('url');
		$this->callback_url = $this->getSystemInfo('callback_url', '');;

		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);
		$this->only_transfer_positive_integer = $this->getSystemInfo('only_transfer_positive_integer', true);
	$this->force_language = $this->getSystemInfo('force_language', '');
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$apiMethod = self::API_createPlayer;

		// $amount = intval($this->getPlayerBalance($playerName));
		$amount = 0;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$callback_url = $this->callback_url;
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);
			
		$params = [
			"name" => $gameUsername,
			"user_id" => $gameUsername,
			"wallet" => $amount,
			"callback_url" => $callback_url,
			"site_id" => $this->site_id,
			"lang" => $this->getLauncherLanguage($this->language),
			"timestamp" => (string)time()
		];

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

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
		if($apiName == self::API_queryForwardGame){
			$url = str_replace("{}",$params['game_code'], $url);
		}
		return $url;
	}

	public function getHttpHeaders($params = [])
	{
		return array(
			"Content-Type" => "application/json",
			"Accept" => "application/json"
		);
	}

	protected function customHttpCall($ch, $params)
	{
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	}

	public function getPlatformCode()
	{
		return WCC_GAME_API;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
	{
		$success = false;
		$success = isset($resultArr['status']) && $resultArr['status'] == self::STATUS_SUCCESS;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('WCC_GAME_API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function onlyTransferPositiveInteger(){
		return $this->only_transfer_positive_integer;
	}

	/**
	 * @param string playerName
	 * @param double amount
	 * @return array ("success"=>boolean, 'external_transaction_id'=>string)
	 */
	public function depositToGame($playerName, $amount, $transfer_secure_id = null)
	{
		// The game platform wallet balance only supports integers, can not contain decimal places as per docs
		$amount = intval($this->gameAmountToDBTruncateNumber($amount));
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
			"user_id" => $gameUsername,
			"site_id" => $this->site_id,
			"amount" => $amount,
			"request_code" => $this->site_id . $gameUsername . (string)time(),
			"timestamp" => (string)time()
		);

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

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
			if(isset($resultArr['data']['transfer_no'])){
				$external_transaction_id = $resultArr['data']['transfer_no'];
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
		// amount can contain 2 decimal places here
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

		$params = array(
			"user_id" => $gameUsername,
			"site_id" => $this->site_id,
			"amount" => $amount,
			"request_code" => $this->site_id . $gameUsername . (string)time(),
			"timestamp" => (string)time()
		);
		
		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

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
			if(isset($resultArr['data']['transfer_no'])){
				$external_transaction_id = $resultArr['data']['transfer_no'];
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
		
		$params = [
			"user_id" => $gameUsername,
			"site_id" => $this->site_id,
			"timestamp" => (string)time()
		];

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;
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
			$result['balance'] = $this->gameAmountToDBTruncateNumber(floatval($resultArr['data']['balance']));
		} else {
			$result['status'] = isset($resultArr['status']) ? $resultArr['status'] : null;
		}

		return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra)
	{
		$this->CI->utils->debug_log('WCC (queryTransaction)',$transactionId, $extra);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForqueryTransaction',
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			"site_id" => $this->site_id,
			"transfer_no" => (string)$transactionId,
			"from" => $this->getStartDate(),
			"to" => $this->getEndDate(),
			"page" => self::START_PAGE,
			"timestamp" => (string)time()
		);

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForqueryTransaction($params)
	{
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$statusCode = $this->getStatusCodeFromParams($params);

		$this->CI->utils->debug_log('WCC (processResultForqueryTransaction)', $resultArr);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
		);

		if(isset($resultArr['data']['data']) && empty($resultArr['data']['data'])){
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}


	public function queryForwardGame($playerName, $extra = null)
	{
		// $amount = $this->getPlayerBalance($playerName) ? intval($this->getPlayerBalance($playerName)) : 0;
		$amount = 0;
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$callback_url = $this->callback_url;
		
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

			
		$params = [
			"name" => $gameUsername,
			"user_id" => $gameUsername,
			"wallet" => $amount,
			"callback_url" => $callback_url,
			"site_id" => $this->site_id,
			"lang" => $language,
			"timestamp" => (string)time(),
			"game_code" => $extra['game_code'],
		];

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

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
			if(isset($resultArr['data']['game']))
            {
                $result['url'] = $resultArr['data']['game'];
            }
            if(isset($resultArr['data']['auth_code'])){
            	$result['external_common_token'] = $resultArr['data']['auth_code'];
            	$this->CI->external_common_tokens->addPlayerTokenWithExtraInfo($playerId,
                    $resultArr['data']['auth_code'],
                    json_encode($resultArr),
                    $this->getPlatformCode(),
                    $this->currency
                );
            }
		}

		return array($success, $result);
	}

	private function getStartDate(){
		$currentDate = new DateTime(); // Current date and time
		$currentDate->sub(new DateInterval('P5M')); // Subtract 5 months

		return $currentDate->format('Y-m-d H:i:s');
	}
	private function getEndDate(){
		$currentDate = new DateTime(); // Current date and time
		return $currentDate->format('Y-m-d H:i:s');
	}

	private function generateSignature($params){
		ksort($params);
		$str = implode("", $params);	
		return md5($str.$this->secret_key);
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
                $lang = 'zh-cn';
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vi':
            case 'vi-vn':
            case 'vi_vn':
                $lang = 'vn';
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-br':
            case 'pt-pt':
                $lang = 'pt-br';
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id_IDR':
            case 'id-id':
                $lang = 'id';
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th';
                break;
            case Language_function::INT_LANG_SPANISH:
            case 'es-us':
            case 'es-US':
                $lang = 'es';
                break;
            case "jp":
            case "ja-JP":
            case "ja-en":
            case "jp-jp":
                $lang = 'ja';
                break;
            default:
                $lang = 'en';
                break;
        }

        return $lang;
	}
	
	public function syncOriginalGameLogs($token = false)
	{
		$this->CI->utils->debug_log('WCC (syncOriginalGameLogs)', $token);

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$endDateTimeStart =  $endDateTime->format("Y-m-d H:i:s");

		$success = false;
		
		$success = $this->processGameHistory($queryDateTimeStart, $endDateTimeStart);

		return array('success' => $success);
	}

	public function processGameHistory($startDate, $endDate)
	{				
		$startDate = DateTime::createFromFormat("Y-m-d H:i:s", $startDate)->format("Y-m-d H:i:s");
		$endDate = DateTime::createFromFormat("Y-m-d H:i:s", $endDate)->format("Y-m-d H:i:s");

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
			$params = array(
				"user_id" => '',
				"site_id" => $this->site_id,
				"from" => $startDate,
				"to" => $endDate,
				"page" => $currentPage,
				"timestamp" => (string)time()
			);
			$signature = $this->generateSignature($params);

			$params['signature'] = $signature;

			$this->CI->utils->debug_log("WCC history params:", $params);
			$this->CI->utils->info_log('<-------------------------PARAMS------------------------->', $params);

			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

			$this->CI->utils->debug_log("WCC game_history: " , ["params" => json_encode($params),"response" => json_encode($api_result['response'])]);


			if ($api_result && $api_result['success']) {
				$totalCount = isset($api_result['response']['total']) ? $api_result['response']['total'] : 0;

				$page = isset($api_result['response']['current_page']) ? $api_result['response']['current_page'] : 0;
				
				$totalPage = $totalCount / self::DEFAULT_RECORD_PER_PAGE;

				if($totalPage != 0 && $totalPage <= 1){
					$totalPage = 1;
				}

				$this->CI->utils->debug_log("wcc: ", ['total_records' => $totalCount, 'total_page' => $totalPage]);		

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

			$this->CI->utils->debug_log("WCC_GAME_API game_history: " . ' currentPage: ', $currentPage, 'totalCount', $totalCount, 'totalPage', $totalPage, 'done', $done, 'result', $api_result, 'params_executing', $params);

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
		$response = isset($resultArr['data']) ? $resultArr['data'] : [];


		$gameRecords = isset($resultArr['data']['data']) ? $resultArr['data']['data'] : [];

		$result['data_count'] = isset($response['total']) ? $response['total'] : 0;

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

			$this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

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
			$result['totalCount'] = $response['total'];
		}
		$this->CI->utils->debug_log('wcc result', $result);
		return array($success, $result);		
	}

	private function rebuildGameRecords(&$gameRecords, $extra)
	{
		if (!empty($gameRecords)) {
			foreach ($gameRecords as $index => $gameRecord) {
				$data['user_id'] = isset($gameRecord['user_id']) ? $gameRecord['user_id'] : null;
				$data['name'] = isset($gameRecord['name']) ? $gameRecord['name'] : null;
				$data['bet_amount'] = isset($gameRecord['bet_amount']) ? $this->dBtoGameAmount($gameRecord['bet_amount']) : null;
				$data['currency'] = isset($gameRecord['currency']) ? $gameRecord['currency'] : null;
				$data['fight_no'] = isset($gameRecord['fight_no']) ? $gameRecord['fight_no'] : null;
				$data['bet_id'] = isset($gameRecord['bet_id']) ? $gameRecord['bet_id'] : null;
				$data['round_id'] = isset($gameRecord['round_id']) ? $gameRecord['round_id'] : null;
				$data['bet_code'] = isset($gameRecord['bet_code']) ? $gameRecord['bet_code'] : null;
				$data['winner'] = isset($gameRecord['winner']) ? $this->dBtoGameAmount($gameRecord['winner']) : null;
				$data['payout'] = isset($gameRecord['payout']) ? $this->dBtoGameAmount($gameRecord['payout']) : null;
				$data['odds'] = isset($gameRecord['odds']) ? $gameRecord['odds'] : null;
				$data['bet_time'] = isset($gameRecord['created_at']) ? $gameRecord['created_at'] : null;

				$data['refund'] = isset($gameRecord['refund']) ? $this->dBtoGameAmount($gameRecord['refund']) : null;
				$data['wallets'] = isset($gameRecord['wallets']) ? $this->dBtoGameAmount($gameRecord['wallets']) : null;
				$data['winner_amount'] = isset($gameRecord['winner_amount']) ? $this->dBtoGameAmount($gameRecord['winner_amount']) : null;
				$data['remark'] = isset($gameRecord['remark']) ? $gameRecord['remark'] : null;
				$data['bet_result'] = isset($gameRecord['bet_result']) ? $gameRecord['bet_result'] : null;
				$data['raw_data'] = isset($gameRecord) ? json_encode($gameRecord) : null;


				//extra info from SBE
				$data['external_uniqueid'] = isset($gameRecord['bet_id']) ? $gameRecord['bet_id'] : null;
				$data['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
				$data['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$data['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$data['game_code'] = isset($gameRecord['game_code']) ? $gameRecord['game_code'] : null;
				$dataRecords[] = $data;
				$gameRecords[$index] = $data;
				
				unset($data);
			}
			return $dataRecords;
		}
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
		$this->CI->utils->debug_log("WCC: dateRange: " . $dateFrom . '-' . $dateTo);

		$sqlTime = '`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

		if ($use_bet_time) {
			$sqlTime = '`original`.`bet_time` >= ? AND `original`.`bet_time` <= ?';
		}

		$this->CI->utils->debug_log('WCC sqlTime ===>', $sqlTime);

		$sql = <<<EOD
		SELECT
			original.id as sync_index,
			original.bet_time as start_at,
			original.bet_time as bet_at,
			original.bet_time as end_at,
			original.bet_id,
			original.bet_amount,
			original.bet_amount as real_betting_amount,
			original.user_id,
			original.name as player_name,
			original.payout,
			original.refund,
			original.wallets,
			original.balance,
			original.round_id,
			original.currency,
			original.game_code,
			original.fight_no,
			original.bet_result,
			original.payout - original.bet_amount as result_amount,
			original.bet_result as status,
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
			LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
			JOIN game_provider_auth ON original.user_id = game_provider_auth.login_name
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
		$result_amount = isset($row['result_amount']) ? $this->dBtoGameAmount($row['result_amount']) : 0;

		if($row['bet_result'] == 'draw'){
			$result_amount = 0;
			$row['bet_amount'] = 0;
		}
		
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
				'player_username'       => isset($row['user_id']) ? $row['user_id'] : null
			],
			'amount_info' => [
				'bet_amount'            => isset($row['bet_amount']) ? $this->dBtoGameAmount($row['bet_amount']) : 0,
				'result_amount'         => $result_amount,
				'bet_for_cashback'      => isset($row['bet_amount']) ? $this->dBtoGameAmount($row['bet_amount']) : 0,
				'real_betting_amount'   => isset($row['real_betting_amount']) ? $this->dBtoGameAmount($row['real_betting_amount']) : 0,
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
			'status'                    => $this->gameLogsStatus[$row['bet_result']],
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

	public function preprocessOriginalRowForGameLogs(array &$row)
	{
		if (empty($row['game_description_id'])) {
			$unknownGame = $this->getUnknownGame($this->getPlatformCode());
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
			$row['game_description_id'] = $game_description_id;
			$row['game_type_id'] = $game_type_id;
		}
		$row["status"] = Game_logs::STATUS_SETTLED;
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
		$this->CI->utils->debug_log('WCC (isPlayerExist)', $playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = [
			"user_id" => $gameUsername,
			"site_id" => $this->site_id,
			"timestamp" => (string)time()
		];

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

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
			$result['exists'] = true;
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['data']['balance']));
		} else {
			$result['code'] = $resultArr['status'];
			$result['exists'] = false;
		}

		return array($success, $result);
	}

	public function logout($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdByGameUsername($gameUsername);
	
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		$params = [
			"user_id" => $gameUsername,
			"site_id" => $this->site_id,
			"auth_code" => $this->CI->external_common_tokens->getExternalToken($playerId, $this->getPlatformCode()),
			"timestamp" => (string)time() 
		];

		$signature = $this->generateSignature($params);
		$params['signature'] = $signature;

		return $this->callApi(self::API_logout, $params, $context);
	}
	
	public function processResultForLogout($params) {
	
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params,'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		return array($success, null);
	}

	public function getPlayerBalance($playerName){	
		$get_bal_req = $this->queryPlayerBalance($playerName);
		$this->utils->debug_log("WCC: (getPlayerBalance) get_bal_req: " , $get_bal_req);	
		if($get_bal_req['success']){			
			return $get_bal_req['balance'];
		}else{
			return false;
		}	
	}

	public function defaultBetDetailsFormat($row) {
        $bet_details = [];
        
        if(isset($row['raw_data'])){
            $extra_info = isset($row['raw_data']) ? $row['raw_data'] : null;
            $extra_info = json_decode($extra_info);
			
			$bet_details['extra'] = $extra_info;
            if(isset($extra_info->odds)){
                $bet_details['odds'] = $extra_info->odds;
            }
        }


        if (isset($row['game_description_name'])) {
            $bet_details['game_name'] = $row['game_description_name'];
        }

        if (isset($row['bet_id'])) {
            $bet_details['bet_id'] = $row['external_uniqueid'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] =  $this->gameAmountToDBTruncateNumber($row['bet_amount']);
        }
        if (isset($row['payout'])) {
            $bet_details['win_amount'] = $this->gameAmountToDBTruncateNumber($row['payout']);
        }
        if (isset($row['bet_result'])) {
            $bet_details['game_result'] = $row['bet_result'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }
        return $bet_details;
    }

}
