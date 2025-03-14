<?php

require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: ION GAMING
	*
	* @category Game_platform
	* @version not specified
	* @copyright 2013-2022 tot
	* @integrator @andrew.php.ph
**/

class Game_api_common_iongaming extends Abstract_game_api {
	const TRANSFER    = 'transfer';
	const DEPOSIT    = 'deposit';
	const WITHDRAW    = 'withdraw';
	const LAUNCH_GAME = 'game';
	const SESSION 	  = 'session';
	const GAMELOGS    = 'gamelogs';

	# Fields in iongaming_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'refNo',
		'accountId',
		'currency',
		'playerWinloss',
		'stake',
		'orderTime',
		'lastCashBalance',
		'gameType',
		'settleTime',
		'gameId',
		'gameStartTime',
		'tableName',
		'groupBetOptions',
		'betOptions',
		'seqNo',
		'turnoverStake',
		'ticketStatus',
		'ip',
		'isCommission',
    ];

    const SIMPLE_MD5_FIELDS_FOR_ORIGINAL=[
        'refNo',
		'playerWinloss',
		'stake',
		'gameType',
		'settleTime',
		'gameId',
		'seqNo',
		'turnoverStake',
		'ticketStatus',
    ];

    // # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'playerWinloss',
        'stake',
        'lastCashBalance',
        'turnoverStake',
    ];

    # Fields in game_logs we want to detect changes for merge, and when iongaming_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'round_id',
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
		self::API_createPlayer => '/api/v1/Player/CreatePlayer/',
		self::API_queryPlayerBalance => '/api/v1/Player/CheckBalance/',
		self::API_depositToGame => '/api/v1/Player/FundTransfer/',
		self::API_withdrawFromGame => '/api/v1/Player/FundTransfer/',
		self::API_queryTransaction => '/api/v1/Player/CheckFundTransfer/',
		self::API_checkLoginToken => '/api/v1/Player/RefreshLoginSession/',
		self::API_queryForwardGame => '/middleware/v1/Dispatch/Game/',
		self::API_syncGameRecords => '/api/v1/BetDetail/Date/',
	);

	const PRODUCT_GROUP = "TRG";
	const PRODUCT_TYPE = "TRG";

	protected $sync_time_interval;
	protected $sync_sleep_time;

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
		$this->key = $this->getSystemInfo('key');
		$this->salt = $this->getSystemInfo('secret');
		$this->game_launch_url = $this->getSystemInfo('game_launch_url');
		$this->merchant_code = $this->getSystemInfo('merchant_code', 'IDG');
		$this->table_limit = $this->getSystemInfo('table_limit', 'LOW');
		$this->currency = $this->getSystemInfo('currency');
		$this->enable_check_status = $this->getSystemInfo('enable_check_status' , true);
		$this->method = self::LAUNCH_GAME;
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+6 hours'); // 6 hours max timeframe per call
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '300');//sleep for 5 minutes, as per game provider OGP-15439
		$this->api_url_v2 = $this->getSystemInfo('api_url_v2','http://api-idg2.ionclubtry.com');
		$this->use_v2_for_deposit_withdraw = $this->getSystemInfo('use_v2_for_deposit_withdraw',false);

	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		$api_url = $this->api_url;

		if($this->use_v2_for_deposit_withdraw){
			if($this->method == self::WITHDRAW){
				$apiUri = '/api/v2/Player/WalletWithdraw/';
			}
			if($this->method == self::DEPOSIT){
				$apiUri = '/api/v2/Player/WalletDeposit/';
			}
		}

		if($this->method == self::DEPOSIT || $this->method == self::WITHDRAW){
			if($this->use_v2_for_deposit_withdraw){
				$api_url = $this->api_url_v2;
			}
		}

		$url = $api_url . $apiUri . $this->merchant_code;

		if($this->method == self::DEPOSIT || $this->method == self::WITHDRAW){
			$url .= '/' . self::PRODUCT_GROUP;
		}

		if($this->method == self::TRANSFER){
			$url .= '/' . self::PRODUCT_GROUP;
		}

		if($this->method == self::SESSION || $this->method == self::GAMELOGS){
			$url .= '/' . self::PRODUCT_GROUP .'/'. self::PRODUCT_TYPE;
		}

		return $url;
	}

	protected function customHttpCall($ch, $params)
	{
        $data_json = json_encode($params);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    }

    protected function getHttpHeaders($params)
	{
		return array('Content-Type' => 'application/json');
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		$success = $resultArr['errorCode'] == 0 ? true : false;

		# we check here if errorCode is 404 meaning game logs API response is success but empty
		if(isset($resultArr['errorCode']) && $resultArr['errorCode'] == 404 && $statusCode == 200){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ION GAMING got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	private function generateTimestamp()
	{
		$date = new DateTime('now', new DateTimeZone('GMT-5'));
		return $timestamp = $date->format('Y-m-d\TH:i:s');
	}

	private function generateChecksum($type, $gameUsername = null, $transactionId = null, $amount = null, $fromDate = null, $toDate = null)
	{
		$checksum = null;
		if($type == self::API_createPlayer){
			$checksum = base64_encode(md5($gameUsername.'.'.$this->currency.'.'.$this->table_limit.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
		}elseif($type == self::API_queryPlayerBalance || $type == self::API_checkLoginToken){
			$checksum = base64_encode(md5($gameUsername.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
		}
		elseif($type == self::API_queryTransaction){
			$checksum = base64_encode(md5($transactionId.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
		}
		elseif($type == self::API_depositToGame || $type == self::API_withdrawFromGame){
			$checksum = base64_encode(md5($gameUsername.'.'.$this->currency.'.'.$amount.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
			if($this->use_v2_for_deposit_withdraw){
				$checksum = base64_encode(md5($gameUsername.'.'.$this->currency.'.'.$transactionId.'.'.$amount.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
			}
		}elseif($type == self::API_syncGameRecords){
			$checksum = base64_encode(md5($fromDate.'.'.$toDate.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
		}

		return $checksum;
	}

	private function getLauncherLanguage($language){
		$lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            case 3:
            case 'id-id':
            	$lang = 'id-ID'; // indonesian
            	break;
            case 4:
            case 'vi-vn':
            	$lang = 'vi-VN'; // vietnamese
            	break;
            case 5:
            case 'ko-kr':
            	$lang = 'ko-KR'; // korean
            	break;
           	case 6:
           	case 'th-th':
           		$lang = 'th-TH'; // thailand
           		break;
           	case 'my-mm':
           		$lang = 'my-MM'; // burmese
           		break;
            default:
                $lang = 'en-US'; // default as english
                break;
        }
        return $lang;
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
			'AccountId' => $gameUsername,
			'Currency' => $this->currency,
			'Password' => $password,
			'TableLimit' => $this->table_limit,
			'Timestamp' => $this->generateTimestamp(),
			'Checksum' => $this->generateChecksum($type = self::API_createPlayer, $gameUsername)
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
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
		if($this->enable_check_status)
			$this->checkLoginToken($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'AccountIds' => array($gameUsername),
			'Timestamp' => $this->generateTimestamp(),
			'Checksum' => $this->generateChecksum($type = self::API_queryPlayerBalance, $gameUsername)
		);

		$this->method = self::TRANSFER;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$errorCode = isset($resultArr["data"]["players"][0]["errorCode"]) ? $resultArr["data"]["players"][0]["errorCode"] : null;
		$balance = isset($resultArr['data']['players']['0']['balance']) ? $resultArr['data']['players']['0']['balance'] : null;

		if(! is_null($errorCode)){
			$result["reason_id"] = $errorCode;
		}
		
		if($success){
			if(! is_null($errorCode)){

				$result['balance'] = $this->gameAmountToDB($balance);

				if($errorCode == 0){

					$success = true;
					$result["exists"] = true;
				}elseif($errorCode == 101){
					
					# when player is not exists
					$success = true;
					$result["exists"] = false;
				}else{

					$this->CI->utils->debug_log("ION_GAMING ERROR in processResultForQueryPlayerBalance with error code  of >>>",$errorCode);
				}
			}
		}else{
			$result["exists"] = null;
			$this->CI->utils->debug_log("ION_GAMING ERROR in processResultForQueryPlayerBalance result is >>>",$resultArr);
		}

		return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra)
	{
		$playerName = $extra['playerName'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'RefNo' => $transactionId,
			'Timestamp' => $this->generateTimestamp(),
			'Checksum' => $this->generateChecksum($type = self::API_queryTransaction, null, $transactionId)
		);

		$this->method = self::TRANSFER;

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
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
			'external_transaction_id' => $external_transaction_id,
			'isRetry' => false
        );

		$params = array(
			'RefNo' => $external_transaction_id,
			'AccountId' => $gameUsername,
			'Currency' => $this->currency,
			'Amount' => $this->dBtoGameAmount($amount),
			'Timestamp' => $this->generateTimestamp(),
			'Checksum' => $this->generateChecksum($type = self::API_depositToGame, $gameUsername, $external_transaction_id, $this->dBtoGameAmount($amount))
		);

		$this->method = self::DEPOSIT;

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

		$isRetry = $this->getVariableFromContext($params, 'isRetry');

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if($isRetry){
			// errorCode 202 is for duplicate RefNo
			if(isset($resultArr['errorCode']) && $resultArr['errorCode']=='202'){
				$success=true;
			}
		}

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
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

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount = 0, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'TW'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id,
			'isRetry' => false
		);

		# for v2 of ION gaming API
		if($this->use_v2_for_deposit_withdraw){
			$withdrawAmount = $this->dBtoGameAmount($amount);
		}else{
			# for v1 of ION gaming API
			$withdrawAmount = '-' . $this->dBtoGameAmount($amount);
		}

		$params = array(
			'RefNo' => $external_transaction_id,
			'AccountId' => $gameUsername,
			'Currency' => $this->currency,
			'Amount' => $withdrawAmount,
			'Timestamp' => $this->generateTimestamp(),
			'Checksum' => $this->generateChecksum($type = self::API_withdrawFromGame, $gameUsername, $external_transaction_id,$withdrawAmount)
		);

		$this->method = self::WITHDRAW;

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

		$isRetry = $this->getVariableFromContext($params, 'isRetry');

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if($isRetry){
			// errorCode 202 is for duplicate RefNo
			if(isset($resultArr['errorCode']) && $resultArr['errorCode']=='202'){
				$success=true;
			}
		}

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	/*
	* Example URL :
	* http://wwwidg1.ionclubtry.com/middleware/v1/Dispatch/Game/TRG/TRG/BACCARAT/DESKTOP/en-US
	*/
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$checksum = base64_encode(md5($gameUsername.'.'.$this->currency.'.'.$this->table_limit.'.'.$this->merchant_code.'.'.$this->generateTimestamp().'.'.$this->salt, TRUE));
		$platformtype = !$extra['is_mobile'] ? 'DESKTOP' : 'MOBILE';
		$lang = $this->getLauncherLanguage($extra['language']);

		$data = $gameUsername.'.'.$this->currency.'.'.$this->table_limit.'.'.$this->merchant_code.'.'
				.$this->generateTimestamp().'.'.$checksum;

		$padding = 16 - (strlen($data) % 16);
		$data .= str_repeat(chr($padding), $padding);
		$url = $this->game_launch_url . self::URI_MAP['queryForwardGame'] . self::PRODUCT_GROUP .'/'. self::PRODUCT_TYPE .'/'.
			$extra['game_code'] .'/'. $platformtype .'/'. $lang;

		$payload = base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->key, $data, MCRYPT_MODE_ECB));

		return ['success' => true,'url' => $url, 'payload' => $payload];
	}

	/*
	* This is used for Refresh Login Status.
	*
	*/
	public function checkLoginToken($playerName, $token = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckLoginToken',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'AccountId' => $gameUsername,
			'Timestamp' => $this->generateTimestamp(),
			'Checksum'  => $this->generateChecksum(self::API_checkLoginToken, $gameUsername)
		);

		$this->method = self::SESSION;

		return $this->callApi(self::API_checkLoginToken, $params, $context);
	}

	public function processResultForCheckLoginToken($params)
	{
		$this->CI->load->model('game_provider_auth');
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$platformId = $this->getPlatformCode();
		$playerId   = $this->CI->game_provider_auth->getPlayerIdByPlayerName($gameUsername, $platformId);

		if ($success) {
			$resultArr = reset($resultArr);
			$result['sessionId'] = $resultArr['sessionId'];
			$result['status'] = self::FLAG_TRUE;

			// Used for updating game_provider_auth.is_online
			$this->CI->game_provider_auth->setPlayerStatusOnline($platformId, $gameUsername);
        }
        else{
        	// Used for updating game_provider_auth.is_online
        	$this->CI->game_provider_auth->setPlayerStatusLoggedOff($platformId, $gameUsername);
        	$result['sessionId'] = self::FLAG_FALSE;
			$result['status'] = self::FLAG_FALSE;
        }

        return array($success, $result);
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$start = clone $startDateTime;
		$end = clone $endDateTime;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs'
		);

		$now=new DateTime();

		if($end > $now){
		   $end = $now;
		}

		$step = $this->sync_time_interval;
		$rowsCount = 0;

		while($start < $end){

			$endDate = $this->CI->utils->getNextTime($start,$step);
			$takeSleep = true;

			if($endDate > $end){
				$endDate = $end;
				$takeSleep = false;
			}

			$params = array(
				'Timestamp' => $this->generateTimestamp(),
				'Checksum'  => $this->generateChecksum(self::API_syncGameRecords, null, null, null ,$start->format('Y-m-d\TH:i:s'), $endDate->format('Y-m-d\TH:i:s')),
				'FromDate'  => $start->format('Y-m-d\TH:i:s'),
				'ToDate' 	=> $endDate->format('Y-m-d\TH:i:s'),
			);

			$this->CI->utils->debug_log("ION GAMING PARAMS",$params);

			$this->method = self::GAMELOGS;

			$apiResult = $this->callApi(self::API_syncGameRecords, $params, $context);
			$rowsCount += isset($apiResult['rows_count']) ? $apiResult['rows_count'] : 0;

			# we check if success and errorCode is 400 means to sleep for 5 mins
			if(isset($apiResult["success"]) && $apiResult["success"] && isset($apiResult["errorCode"]) && $apiResult["errorCode"] == 404){

				$this->CI->utils->info_log('ION GAMING is syncing please wait for 5 minutes: ',$apiResult);

				sleep($this->sync_sleep_time);

				# we sleep for 5 minutes, so we need to reconnect to database
				$this->CI->db->_reset_select();
                $this->CI->db->reconnect();
				$this->CI->db->initialize();
				
				continue;
			}

			# we check if API call is not success
			if(isset($apiResult["success"]) && !$apiResult["success"]){

				$this->CI->utils->debug_log('ION GAMING ERROR in calling API: ',$apiResult);

				break;
			}

			$start = $endDate;

			if($takeSleep){

				$this->CI->utils->info_log('ION GAMING is syncing please wait for 5 minutes: ',$apiResult);

				sleep($this->sync_sleep_time);

				# we sleep for 5 minutes, so we need to reconnect to database
				$this->CI->db->_reset_select();
                $this->CI->db->reconnect();
                $this->CI->db->initialize();
			}
		}

		return [
			'success' => true,
			'rows_count' => $rowsCount,
			$apiResult
		];
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		# check first if success, if not, check if errorCode is 404, if so return then wait for 5 mins
		if($success && isset($resultArr["errorCode"]) && $resultArr["errorCode"] == 404 && $statusCode = 200){

			return [$success,$resultArr];
		}

		$result = ['data_count' => 0];
		$gameRecords = isset($resultArr["data"])?$resultArr['data']['bets']:[];
		if(is_array($gameRecords)){
			$result['rows_count'] = count($gameRecords);
		}

		if($success&&!empty($gameRecords))
		{
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                $this->getMd5FieldsForOriginal(),
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

	public function getMd5FieldsForOriginal()
    {

        if($this->use_simplified_md5){
            return self::SIMPLE_MD5_FIELDS_FOR_ORIGINAL;
        }

        return self::MD5_FIELDS_FOR_ORIGINAL;
    }

    public function getMD5Fields(){
        return [
            'md5_fields_for_original'=>$this->getMd5FieldsForOriginal(),
            'md5_float_fields_for_original'=>self::MD5_FLOAT_AMOUNT_FIELDS,
            'md5_fields_for_merge'=>self::MD5_FIELDS_FOR_MERGE,
            'md5_float_fields_for_merge'=>self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE,
        ];
    }

	/** 
     * Process necessary rows for Original Game Logs
     * 
     * @param array $gameRecords
     * 
     * @return void
    */
	private function rebuildGameRecords(&$gameRecords,$extra)
	{
        foreach($gameRecords as $i => $gr){
			
        	$newGR['refNo'] = isset($gr['refNo']) ? $gr['refNo'] : null;
			$newGR['accountId'] = isset($gr['accountId']) ? $gr['accountId'] : null;
			$newGR['currency'] = isset($gr['currency']) ? $gr['currency'] : null;
			$newGR['playerWinloss'] = isset($gr['playerWinloss']) ? $this->gameAmountToDB($gr['playerWinloss']) : null;
			$newGR['stake'] = isset($gr['stake']) ? $this->gameAmountToDB($gr['stake']) : null;
			$newGR['orderTime'] = isset($gr['orderTime']) ? $this->gameTimeToServerTime($gr['orderTime']) : null;
			$newGR['lastCashBalance'] = isset($gr['lastCashBalance']) ? $this->gameAmountToDB($gr['lastCashBalance']) : null;
			$newGR['gameType'] = isset($gr['gameType']) ? $gr['gameType'] : null;
			$newGR['settleTime'] = isset($gr['settleTime']) ? $this->gameTimeToServerTime($gr['settleTime']) : null;
			$newGR['gameId'] = isset($gr['gameId']) ? $gr['gameId'] : null;
			$newGR['gameStartTime'] = isset($gr['gameStartTime']) ? $this->gameTimeToServerTime($gr['gameStartTime']) : null;
			$newGR['tableName'] = isset($gr['tableName']) ? $gr['tableName'] : null;
			$newGR['groupBetOptions'] = isset($gr['groupBetOptions']) ? $gr['groupBetOptions'] : null;
			$newGR['betOptions'] = isset($gr['betOptions']) ? $gr['betOptions'] : null;
			$newGR['seqNo'] = isset($gr['seqNo']) ? $gr['seqNo'] : null;
			$newGR['turnoverStake'] = isset($gr['turnoverStake']) ? $this->gameAmountToDB($gr['turnoverStake']) : null;
			$newGR['ticketStatus'] = isset($gr['ticketStatus']) ? $gr['ticketStatus'] : null;
			$newGR['ip'] = isset($gr['ip']) ? $gr['ip'] : null;
			$newGR['isCommission'] = isset($gr['isCommission']) ? $gr['isCommission'] : null;
			$newGR['response_result_id'] = $extra['response_result_id'];
			$newGR['external_uniqueid'] = isset($gr['refNo']) ? strtotime($gr['settleTime']).$gr['refNo'] : null;

			$gameRecords[$i] = $newGR;
			unset($newGR);
		}
		
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
        $sqlTime='`ion`.`settleTime` >= ?
          AND `ion`.`settleTime` <= ?';
        if($use_bet_time){
            $sqlTime='`ion`.`gameStartTime` >= ?
          AND `ion`.`gameStartTime` <= ?';
        }

        $sql = <<<EOD
			SELECT
				ion.id as sync_index,
				ion.response_result_id,
				ion.refNo as round,
				ion.gameId as round_id,
				SUBSTRING(tableName,1,(LOCATE('-',tableName) - 2)) as table_identifier,
				ion.accountId as username,
				ion.stake as bet_amount,
				ion.stake as valid_bet,
				ion.playerWinloss as result_amount,
				ion.gameStartTime as start_at,
				ion.settleTime as end_at,
				ion.gameStartTime as bet_at,
				ion.gameType as game_code,
				ion.gameType as game_name,
				ion.external_uniqueid,
				ion.lastCashBalance as after_balance,
				ion.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as ion
			LEFT JOIN game_description as gd ON ion.gameType = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON ion.accountId = game_provider_auth.login_name
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
            'table' =>  isset($row['round_id'])?$row['round_id']:null,
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        return [
            'game_info' => [
                'game_type_id' => isset($row['game_type_id'])?$row['game_type_id']:null,
                'game_description_id' => isset($row['game_description_id'])?$row['game_description_id']:null,
                'game_code' => isset($row['game_code'])?$row['game_code']:null,
                'game_type' => isset($row['game_type_id'])?$row['game_type_id']:null,
                'game' => isset($row['game_code'])?$row['game_code']:null
            ],
            'player_info' => [
                'player_id' => isset($row['player_id'])?$row['player_id']:null,
                'player_username' => isset($row['username'])?$row['username']:null
            ],
            'amount_info' => [
                'bet_amount' => isset($row['valid_bet'])?$row['valid_bet']:null,
                'result_amount' => isset($row['result_amount'])?$row['result_amount']:null,
                'bet_for_cashback' => isset($row['valid_bet'])?$row['valid_bet']:null,
                'real_betting_amount' => isset($row['bet_amount'])?$row['bet_amount']:null,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => isset($row['after_balance'])?$row['after_balance']:null,
            ],
            'date_info' => [
                'start_at' => isset($row['bet_at'])?$row['bet_at']:null,
                'end_at' => isset($row['end_at'])?$row['end_at']:null,
                'bet_at' => isset($row['bet_at'])?$row['bet_at']:null,
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => isset($row['external_uniqueid'])?$row['external_uniqueid']:null,
                'round_number' => isset($row['round'])?$row['round']:null,
                'md5_sum' => isset($row['md5_sum'])?$row['md5_sum']:null,
                'response_result_id' => isset($row['response_result_id'])?$row['response_result_id']:null,
                'sync_index' => isset($row['sync_index'])?$row['sync_index']:null,
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
		$bet_details = [
            'roundId' => $row['round_id'],
            'table_identifier' => $row['table_identifier']
        ];
		$row['bet_details'] = $bet_details;
    }

	private function getGameDescriptionInfo($row, $unknownGame)
	{
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $success=true;
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array('success' => $success);
    }

	public function isPlayerExist($playerName){

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		if($this->enable_check_status){
			$this->checkLoginToken($playerName);
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'AccountIds' => array($gameUsername),
			'Timestamp' => $this->generateTimestamp(),
			'Checksum' => $this->generateChecksum($type = self::API_queryPlayerBalance, $gameUsername)
		);

		$this->method = self::TRANSFER;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function login($playerName, $password = null, $extra = null){
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

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function getIdempotentTransferCallApiList(){ 
		return [self::API_depositToGame, self::API_withdrawFromGame]; 
	}
}
/*end of file*/