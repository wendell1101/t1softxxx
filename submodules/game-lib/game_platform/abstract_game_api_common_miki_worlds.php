<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
/**
 * API NAME: Miki Worlds
 * API docs:
 *
 * @category Game_platform
 * @copyright 2013-2023 tot
 * @integrator @wendell.php.ph
 **/

abstract class Abstract_game_api_common_miki_worlds extends Abstract_game_api
{
	const START_PAGE = 1;
	const TIMEZONE = 8; #GMT + 8
	const DEFAULT_RECORD_PER_PAGE = 1000;

	const METHOD = [
		'GET' => 'GET',
		'POST' => 'POST'
	];

	private $secret = [];
	private $rsa;

	const CURRENCY = [
		"AUD" => 36,
		"BDT" => 50,
		"BND" => 96,
		"MMK" => 104,
		"KHR" => 116,
		"CNY" => 156,
		"ETB" => 230,
		"HKD" => 344,
		"IDR" => 360,
		"INR" => 356,
		"JPY" => 392,
		"KRW" => 410,
		"LAK" => 418,
		"MYR" => 458,
		"NPR" => 524,
		"PKR" => 586,
		"PHP" => 608,
		"SGD" => 702,
		"VND" => 704,
		"THB" => 764,
		"AED" => 784,
		"TND" => 788,
		"GBP" => 826,
		"USD" => 840,
		"TWD" => 901,
		"EUR" => 978,
		"BRL" => 986,
		"MMK1000" => 1104,
		"KHR1000" => 1116,
		"IDR1000" => 1360,
		"VND1000" => 1704,
		"LAK1000" => 1418
	];

	const RETURN_STATUS = [
		"SUCCESS" => 200,
		"SEC_NO_REPEATED" => 201,
		"CHANNEL_NOT_EXIST" => 202,
		"Idle" => 203,
		"Logout" => 207,
		"Timeout" => 209,
		"InvalidToken" => 410,
		"ServerError" => 500,
		"ServerMaintenance" => 505,
		"ChannelApiError" => 506,
		"Disconnected" => 1001,
		"InsufficientFund" => 1003,
		"SystemBusy" => 4003,
		"InvalidParameter" => 4025,
		"InvalidSignature" => 4026,
		"IpNotAuthorized" => 4027,
		"VisitorCantRechargeCredit" => 4028,
		"InvalidReturnJsonFormat" => 4029,
		"InvalidChannelCode" => 4030,
		"BetTicketNotFound" => 4031,
		"UserNotExist" => 4037,
		"ReachApiMaxAttempt" => 4202,
		"RechargeFails" =>  5001,
		"WithdrawFails" =>  5002,
		"SingleWalletNotIncludeThisFunc" =>  5004,
	];

	const STATUS_STRING = [
		'SUCCESS' => 'Success'
	];

	const TRANSACTION_STATUS = [
		"SUCCESS" => 0,
		"FAILED" => 1,
		"ADJUST" => 2,
		"CANCEL_ROUND" => 3,
		"CANCEL_TICKET" => 4,
	];

	const GAME_TYPE = [
		400 => 'LiveRacing',
		401 => 'LiveRoulette',
		209 => 'RngDice',
		211 => 'RngRouletteSingleZero',
		213 => 'RngFishPrawnCrab',
		214 => 'RngDragonTiger',
		215 => 'RngBaccaratLive',
		217 => 'RngThaiHiLo',
		222 => 'RngBlackJack',
		224 => 'RngXocDia',
		226 => 'RngBelangkai',
		229 => 'RngCardHilo'
	];

	const URI_MAP = [
		self::API_createPlayer => "/commonWallet/v2/createAccount",
		self::API_depositToGame => '/commonWallet/v2/transferCredit',
		self::API_withdrawFromGame => '/commonWallet/v2/transferCredit',
		self::API_queryTransaction => '/commonWallet/v2/checkTransferCredit',
		self::API_queryPlayerBalance => "/commonWallet/v2/getBalance",
		self::API_isPlayerExist => "/commonWallet/v2/getBalance",
		self::API_login => "/commonWallet/v2/gameLoginToken",
		self::API_syncGameRecords => "/commonWallet/v2/gameHistory",

	];

	private $originalTable;

	private $channel_name, $channel_no, $channel_code, $currency, $currency_code, $language;
	private $api_url, $data_api_url, $provider, $default_lang, $default_country;
	private $data_api, $launch_url, $sync_time_interval, $sync_sleep_time, $use_extra_info_date_field, $extra_info_date_field;
	public $verify_transfer_using_query_transaction;

	# Fields in miki_worlds_game_logs we want to detect changes for update
	const MD5_FIELDS_FOR_ORIGINAL = [
		'ref_id',
		'winning_amount',
		'payout_amount',
		'payout_time',
		'ref_id',
		'player_name',
		'round_no',
		'game_type',
		'bet_amount',
		'currency',
		'source_result',
		'result',
		'bet_time',
		'transaction_status',
	];

	# Values of these fields will be rounded when calculating MD5
	const MD5_FLOAT_AMOUNT_FIELDS = [
		'bet_amount',
		'valid_bet_amount',
		'winning_amount',
		'payout_amount',
	];

	# Fields in game_logs we want to detect changes for merge and when md5_sum
	const MD5_FIELDS_FOR_MERGE = [
		'external_uniqueid',
		'bet_amount',
		'round',
		'game_code',
		'game_name',
		'after_balance',
		'valid_bet',
		'result_amount',
		'username',
		'start_at',
		'end_at',
		'bet_at',
		'bet_details',
		'bet_time'
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'after_balance',
		'bet_amount',
		'valid_bet',
		'result_amount',
	];

	public function __construct()
	{
		parent::__construct();
		
		$this->secret = [
			'private_key' => $this->getSystemInfo('private_key'),
			'public_key' => $this->getSystemInfo('public_key'),
		];

		$this->originalTable = 'miki_worlds_game_logs';

		$this->channel_name = $this->getSystemInfo('channel_name');
		$this->channel_no = $this->getSystemInfo('channel_no');
		$this->channel_code = $this->getSystemInfo('channel_code');

		$this->currency = $this->getSystemInfo('currency', 'CNY');
		$this->currency_code = in_array($this->currency, array_keys(self::CURRENCY)) ? self::CURRENCY[$this->currency] : null;

		$this->launch_url = $this->getSystemInfo('launch_url');
		$this->default_lang = $this->getSystemInfo('default_lang', 'en');
		$this->default_country = $this->getSystemInfo('default_country', 'cn');

		$this->language = $this->getSystemInfo('language', 'en');

		$this->api_url = $this->getSystemInfo('url');
		$this->data_api = false;
		$this->data_api_url = $this->getSystemInfo('data_api_url');

		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);
		$this->provider = $this->getSystemInfo('provider', 'miki_worlds');

		$this->use_extra_info_date_field = $this->getSystemInfo('use_extra_info_date_field', false);
		$this->extra_info_date_field = $this->getSystemInfo('extra_info_date_field', 'updated_at');

		$this->verify_transfer_using_query_transaction = $this->getSystemInfo('verify_transfer_using_query_transaction', false);

		$this->rsa = new Crypt_RSA();
		$this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->rsa->setHash('md5');
	}

	#for testing
	public function GetDefaultBetLimitGroupReq()
	{
		$cmd = 'GetDefaultBetLimitGroupReq';
		$timestamp = time();
		$rawSignature = $cmd . $this->channel_code . $timestamp . $this->currency_code;
		$signature = $this->encrypt($rawSignature);

		$url = $this->api_url . '/commonWallet/v2/getDefaultBetLimitGroup';

		$params = [
			"Cmd" => $cmd,
			"ChannelCode" => $this->channel_code,
			"Timestamp" => time(),
			"Currency" => $this->currency_code,
			"Signature" => $signature
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHttpHeaders());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

		$response = curl_exec($ch);
		// $info = curl_getinfo($ch);		
		curl_close($ch);
		return $response;
	}

	public function getDefaultGroup()
	{
		return 1;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
	{
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);
		$cmd = 'CreateAccountReq';
		$timestamp = time();
		$playerNickname = $gameUsername;
		$group = $this->getDefaultGroup();



		$rawSignature = $cmd . $this->channel_code . $timestamp . $gameUsername . $password . $playerNickname . $group . $this->currency_code;
		$signature = $this->encrypt($rawSignature);

		$main_params = [
			"Cmd" => $cmd,
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $gameUsername,
			"Password" => $password,
			"Nickname" => $gameUsername,
			"Group" => $group,
			"Currency" => $this->currency_code,
			"Signature" => $signature
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_createPlayer,
				"method" => self::METHOD["POST"]
			]
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

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

	public function verify($str, $signature)
	{
		$signature = base64_decode($signature);
		$this->rsa->loadKey($this->secret['public_key']);
		return $this->rsa->verify($str, $signature);
	}

	public function encrypt($str)
	{
		$this->rsa->loadKey($this->secret['private_key']);
		$signature = $this->rsa->sign($str);
		return base64_encode($signature);
	}

	public function generateUrl($apiName, $params)
	{
		$uri = self::URI_MAP[$apiName];

		$url = $this->api_url . $uri . '?' . http_build_query($params["main_params"]);

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
		if ($params["actions"]["method"] == self::METHOD["POST"]) {
			$function = $params["actions"]['function'];

			unset($params["actions"]);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));
		}
	}

	public function getPlatformCode()
	{
		return MIKI_WORLDS_GAME_API;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $is_querytransaction = false)
	{
		$success = false;
		if (isset($resultArr['status']) && $resultArr['status'] == self::RETURN_STATUS['SUCCESS']) {
			$success = true;
		}

		if (isset($resultArr['statusString']) && $resultArr['statusString'] == self::STATUS_STRING['SUCCESS']) {
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('MIKI_WORLDS API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	/**
	 * @param string playerName
	 * @param double amount
	 * @return array ("success"=>boolean, 'external_transaction_id'=>string)
	 */
	public function depositToGame($playerName, $amount, $transfer_secure_id = null)
	{
		$amount = $this->dBtoGameAmount($amount);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
		}

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => number_format($amount, 2, '.', ''),
			'external_transaction_id' => $transfer_secure_id,
		);

		$cmd = "TransferCreditReq";
		$timestamp = time();
		$referNo = $transfer_secure_id;
		$rawSignature = $cmd . $this->channel_code . $timestamp . $gameUsername . $referNo . number_format($amount, 2, '.', '');
		$signature = $this->encrypt($rawSignature);


		$main_params = [
			"Cmd" => "TransferCreditReq",
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $gameUsername,
			"ReferNo" => $referNo,
			"Amount" => number_format($amount, 2, '.', ''),
			"Signature" => $signature
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_depositToGame,
				"method" => self::METHOD['POST']
			]
		);


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

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
	{
		if(empty($transfer_secure_id)){
			$transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
		}

		// convert number to negative if positive
		if ($amount > 0) $amount *= -1;
		$amount = $this->dBtoGameAmount($amount);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => number_format($amount, 2, '.', ''),
			'external_transaction_id' => $transfer_secure_id,
		);

		$cmd = "TransferCreditReq";
		$timestamp = time();
		$referNo = $transfer_secure_id;
		$rawSignature = $cmd . $this->channel_code . $timestamp . $gameUsername . $referNo . number_format($amount, 2, '.', '');
		$signature = $this->encrypt($rawSignature);


		$main_params = [
			"Cmd" => "TransferCreditReq",
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $gameUsername,
			"ReferNo" => $referNo,
			"Amount" => number_format($amount, 2, '.', ''),
			"Signature" => $signature
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_withdrawFromGame,
				"method" => self::METHOD['POST']
			]
		);


		return $this->callApi(self::API_withdrawFromGame, $params, $context);
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

		$cmd = 'GetBalanceReq';
		$timestamp = time();

		$rawSignature = $cmd . $this->channel_code . $timestamp . $gameUsername;
		$signature = $this->encrypt($rawSignature);

		$main_params = [
			"Cmd" => "GetBalanceReq",
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $gameUsername,
			"Signature" => $signature
		];
		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_queryPlayerBalance,
				"method" => self::METHOD["POST"]
			]
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
		);

		if ($success) {
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']));
		} else {
			$result['code'] = $resultArr['status'];
			$result['msg'] = $resultArr['statusString'];
		}

		return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra)
	{
		$this->CI->utils->debug_log('MIKIWORLDS (queryTransaction)',$transactionId, $extra);
		$playerName = $extra['playerName'];
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForqueryTransaction',
			'external_transaction_id' => $transactionId,
			'playerName' => $playerName,
		);

		$cmd = "CheckTransferCreditReq";
		$timestamp = time();
		$referNo = $transactionId;
		$rawSignature = $cmd . $this->channel_code . $timestamp . $referNo;
		$signature = $this->encrypt($rawSignature);


		$main_params = [
			"Cmd" => $cmd,
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"ReferNo" => $referNo,
			"Signature" => $signature
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_queryTransaction,
				"method" => self::METHOD['POST']
			]
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForqueryTransaction($params)
	{
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$statusCode = $this->getStatusCodeFromParams($params);
		$this->CI->utils->debug_log('MIKIWORLDS (processResultForqueryTransaction)', $resultArr);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
		);

		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra = null)
	{
		$result = $this->login($playerName);
		$url = "";

		if (
			(isset($result['status']) && $result['status'] == self::RETURN_STATUS['SUCCESS']) &&
			(isset($result['statusString']) && $result['statusString'] == self::STATUS_STRING['SUCCESS'])
		) {
			$url = $result["url"];
		}
		return array("success" => true, "url" => $url);
	}


	public function login($playerName, $password = null, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		
		$cmd = "LoginTokenReq";
		$timestamp = time();
		$rawSignature = $cmd . $this->channel_code . $timestamp . $gameUsername;
		$signature = $this->encrypt($rawSignature);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$main_params = [
			"Cmd" => "LoginTokenReq",
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $gameUsername,
			"Signature" => $signature
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_login,
				"method" => self::METHOD["POST"]
			]
		);

		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$main_params = $params['params']['main_params'];

		$baseUrl = $this->launch_url . '?';

		$lang = $this->getGameLanguage($this->language);

		$url = $baseUrl . http_build_query([
			"playerName" => $main_params['PlayerName'],
			"channelCode" => $main_params['ChannelCode'],
			"loginToken" => $resultArr['sessionToken'],
			"lang" => $lang
		]);

		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
			"status" => $resultArr['status'],
			"statusString" => $resultArr['statusString'],
			"url" => $url
		);

		return array($success, $result);
	}
	

	public function getGameLanguage($language) {
        switch (strtolower($language)) {
			case "en-us":
			case "en":
                return intval(0);
                break;

            case "zh-cn":
            case "cn":
                return intval(1);
                break;
            case "id":
            case "id-id":
                return intval(6);
                break;            
            case "vn":
            case "vn-vn":
                return intval(7);
                break;            
            case "th":
            case "th-th":
                return intval(8);
                break;            
            default:
                return intval(0);
                break;
        }
    }
	
	public function generateChannelLoginUrl($playerName)
	{
		$path = "/commonWallet/v2/gameLoginToken";
		$cmd = "LoginTokenReq";
		$timestamp = time();
		$rawSignature = $cmd . $this->channel_code . $timestamp . $playerName;
		$signature = $this->encrypt($rawSignature);

		$body = [
			"Cmd" => "LoginTokenReq",
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $playerName,
			"Signature" => $signature
		];
		$params = [
			"url" => $this->api_url . $path,
			"header" => $this->getHttpHeaders(),
			"body" => $body
		];
		$ch = curl_init();
		return $this->customHttpCall($ch, $params);
	}

	public function syncOriginalGameLogs($token = false)
	{
		$this->CI->utils->debug_log('MIKIWORLDS (syncOriginalGameLogs)', $token);

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($startDate->format('Y-m-d H:i:s'));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($endDate->format('Y-m-d H:i:s'));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
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
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
	    	}

			$this->CI->utils->debug_log("MIKI start_end_time: ", ["start" => $queryDateTimeStart, "end" => $queryDateTimeEnd]);
		}

		$this->CI->utils->debug_log('MIKIWORLDS (formatted)', $startDate, $endDate);

		return array('success' => $success);
	}

	public function processGameHistory($startDate, $endDate)
	{					
		$startDate = DateTime::createFromFormat("Y-m-d H:i:s", $startDate)->format('YmdHi');
		$endDate = DateTime::createFromFormat("Y-m-d H:i:s", $endDate)->format('YmdHi');

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
			$cmd = 'GameHistoryReq';
			$rawSignature = $cmd . $this->channel_code . time() . $startDate . $endDate . self::START_PAGE . self::TIMEZONE;
			$signature = $this->encrypt($rawSignature);
			$main_params = array(
				"Cmd" => $cmd,
				"ChannelCode" => $this->channel_code,
				"Timestamp" => time(),
				"StartDate" => $startDate,
				"EndDate" => $endDate,
				"Page" => $currentPage,
				"TimeZone" => self::TIMEZONE,
				"Signature" => $signature
			);

			$this->CI->utils->debug_log("MIKIWORLDS history main_params:", $main_params);
			$params = array(
				"main_params" => $main_params,
				"actions" => [
					"function" => self::API_syncGameRecords,
					"method" => self::METHOD["POST"]
				]
			);

			$this->CI->utils->info_log('<-------------------------PARAMS------------------------->', $params);

			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

			$this->CI->utils->debug_log("Miki worlds game_history: " , ["params" => json_encode($main_params),"response" => json_encode($api_result['response'])]);


			if ($api_result && $api_result['success']) {
				$totalCount = isset($api_result['response']['total']) ? $api_result['response']['total'] : 0;	

				$page = isset($api_result['response']['page']) ? $api_result['response']['page'] : 0;
				
				$totalPage = $totalCount / self::DEFAULT_RECORD_PER_PAGE;
				if($totalPage != 0 && $totalPage <= 1){
					$totalPage = 1;
				}

				$this->CI->utils->debug_log("miki11: ", ['total_records' => $totalCount, 'total_page' => $totalPage]);		

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

			$this->CI->utils->debug_log("MIKI_WORLDS_GAME_API game_history: " . ' currentPage: ', $currentPage, 'totalCount', $totalCount, 'totalPage', $totalPage, 'done', $done, 'result', $api_result, 'params_executing', $params);

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
		$gameRecords = isset($resultArr['records']) ? $resultArr['records'] : null;

		$result['response'] = [];

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

			$result['response'] = $resultArr;
			$result['totalCount'] = $resultArr['total'];
		}
		$this->CI->utils->debug_log('mikiworlds result', $result);
		return array($success, $result);		
	}

	private function rebuildGameRecords(&$gameRecords, $extra)
	{
		if (!empty($gameRecords)) {
			foreach ($gameRecords as $index => $gameRecord) {
				$data['ref_id'] = isset($gameRecord['refId']) ? $gameRecord['refId'] : NULL;
				$data['player_name'] = isset($gameRecord['playerName']) ? $gameRecord['playerName'] : NULL;
				$data['round_no'] = isset($gameRecord['roundNo']) ? $gameRecord['roundNo'] : NULL;
				$data['game_type'] = isset($gameRecord['gameType']) ? $gameRecord['gameType'] : NULL;
				$data['bet_time'] = isset($gameRecord['betTime']) ? $this->gameTimeToServerTime($gameRecord['betTime']) : NULL;
				$data['bet_item'] = isset($gameRecord['betItem']) ? json_encode($gameRecord['betItem']) : null;
				$data['bet_amount'] = isset($gameRecord['betAmount']) ? $gameRecord['betAmount'] : 0;
				$data['currency'] = isset($gameRecord['currency']) ? $gameRecord['currency'] : $this->currency;
				$data['valid_bet_amount'] = isset($gameRecord['validBetAmount']) ? $gameRecord['validBetAmount'] : 0;
				$data['winning_amount'] = isset($gameRecord['winningAmount']) ? $gameRecord['winningAmount'] : 0;
				$data['payout_amount'] = isset($gameRecord['payoutAmount']) ? $gameRecord['payoutAmount'] : 0;
				$data['payout_time'] = isset($gameRecord['payoutTime']) ? $this->gameTimeToServerTime($gameRecord['payoutTime']) : NULL;
				$data['source_result'] = isset($gameRecord['sourceResult']) ? json_encode($gameRecord['sourceResult']) : null;
				$data['result'] = isset($gameRecord['result']) ? json_encode($gameRecord['result']) : null;
				$data['transaction_status'] = isset($gameRecord['transactionStatus']) ? $gameRecord['transactionStatus'] : 0;
				$data['game_type_string'] = isset($gameRecord['gameTypeString']) ? $gameRecord['gameTypeString'] : null;
				
				//extra info from SBE
				$data['external_uniqueid'] = isset($gameRecord['refId']) ? $gameRecord['refId'] : null;
				$data['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
				$data['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$data['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
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

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
	{
		$this->CI->utils->debug_log("MIKI WORLDS: dateRange: " . $dateFrom . '-' . $dateTo);

		$sqlTime = '`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

		if ($use_bet_time) {
			$sqlTime = '`original`.`bet_time` >= ? AND `original`.`bet_time` <= ?';
		}

		$this->CI->utils->debug_log('MIKIWORLDS sqlTime ===>', $sqlTime);

		$sql = <<<EOD
        SELECT
            original.id as sync_index,
            original.game_type as game_code,
            original.bet_time as start_at,
            original.bet_time as bet_at,
            original.bet_time as end_at,
            original.ref_id,
            original.bet_amount,
            original.player_name,
            original.valid_bet_amount,
            original.round_no,
            original.currency,
            original.bet_item,
            original.result,
            original.source_result,
			original.payout_amount - original.bet_amount as result_amount,
            original.transaction_status as status,
            original.game_type,
            original.winning_amount,
            original.payout_amount,
            original.payout_time,
            original.response_result_id,
            original.external_uniqueid,
            original.created_at,
            original.updated_at,
            original.md5_sum,
            game_provider_auth.player_id,
	        gd.id as game_description_id,
	        gd.english_name as game_description_name,
	        gd.game_type_id
        FROM {$this->originalTable} as original
            LEFT JOIN game_description as gd ON original.game_type = gd.external_game_id AND gd.game_platform_id = ?
            JOIN game_provider_auth ON original.player_name = game_provider_auth.login_name
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

		$extra = [];
		
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
				'player_username'       => isset($row['player_name']) ? $row['player_name'] : null
			],
			'amount_info' => [
				'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
				'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDBTruncateNumber($row['result_amount']) : 0,
				'bet_for_cashback'      => isset($row['valid_bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['valid_bet_amount']) : 0,
				'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
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
			'status'                    => $row['status'],
			'additional_info' => [
				'has_both_side'         => 0,
				'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
				'round_number'          => isset($row['round_no']) ? $row['round_no'] : null,
				'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
				'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
				'sync_index'            => $row['sync_index'],
				'bet_type'              => null
			],
			'bet_details' => [
				'betpoint'              => isset($row['valid_bet_amount']) ? $row['valid_bet_amount'] : null,
				'gameresult'            => isset($row['result']) ? json_decode($row['result']) : null,
			],
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
		$this->CI->utils->debug_log('MIKIWORLDS (isPlayerExist)', $playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$cmd = 'GetBalanceReq';
		$timestamp = time();

		$rawSignature = $cmd . $this->channel_code . $timestamp . $gameUsername;
		$signature = $this->encrypt($rawSignature);

		$main_params = [
			"Cmd" => "GetBalanceReq",
			"ChannelCode" => $this->channel_code,
			"Timestamp" => $timestamp,
			"PlayerName" => $gameUsername,
			"Signature" => $signature
		];

		$params = array(
			"main_params" => $main_params,
			"actions" => [
				"function" => self::API_isPlayerExist,
				"method" => self::METHOD["POST"]
			]
		);
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
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']));
		} else {
			$result['code'] = $resultArr['status'];
			$result['msg'] = $resultArr['statusString'];
			$result['exists'] = false;
		}

		return array($success, $result);
	}

}
