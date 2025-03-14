<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_idn extends Abstract_game_api {

	private $api_url;
	private $game_url;
	private $token;
	private $sufix_pass;
	private $currency;

	const idnRegister = '1';
	const idnLogin = '2';
	const idnDeposit = '3';
	const idnWithdraw = '4';
	const idnLogout = '6';
	const idncheckTrans = '7';
	const idnChangePassword = '16';
	const idnCreateMobileLoginid  = '17';
	const idnPlayerDetail  = '10';
	const idnTransactionList  = '15';
	const idnCurrentRate = '9';

	const DEPOSIT = 1;
	const WITHDRAW = 2;

	const STATUS_NOT_INCLUDED_IN_TURNOVER_CALCULATION = array("Buy Jackpot","Win Global Jackpot","Gift","Deposit","Withdraw","Refund","Tournament-register","Bonus");
	const STATUS_LOSS = array("Lose","Draw","Buy Jackpot","Fold","Gift","Tournament-register");

	const MD5_FIELDS_FOR_ORIGINAL=[
		'external_uniqueid',
		'transaction_no',
		'tableno',
		'userid',
		'idndate',
		'game',
		'curr_bet',
		'status',
		'amount',
		'curr_amount',
		'total',
	];
	const MD5_FLOAT_AMOUNT_FIELDS=[
		'curr_bet',
		'curr_amount',
	];
	const MD5_FIELDS_FOR_MERGE=[
		'external_uniqueid',
		'transaction_no',
		'tableno',
		'userid',
		'idndate',
		'game',
		'curr_bet',
		'status',
		'amount',
		'curr_amount',
		'total',
	];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
		'curr_bet',
		'curr_amount',
	];

	const ORIGINAL_LOGS_TABLE_NAME = 'idn_game_logs';

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->token = $this->getSystemInfo('token');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->sufix_pass = 'a1';
		$this->always_use_https_launcher = $this->getSystemInfo('always_use_https_launcher',true);
		$this->transaction_available_message = $this->getSystemInfo('transaction_available_message','Id transaction available');
		$this->use_curr_field_on_game_log = $this->getSystemInfo('use_curr_field_on_game_log', false);
	}

	public function getPlatformCode() {
		return IDN_API;
	}

	public function generateUrl($apiName, $params) {

		$url = $this->api_url;
		return $url;

	}

	protected function customHttpCall($ch, $params) {

		$xml_object = new SimpleXMLElement("<Request></Request>");
		$xmlData = $this->CI->utils->arrayToXml($params, $xml_object);
		$this->CI->utils->debug_log('-----------------------IDN POST XML STRING ----------------------------',$xmlData);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);

	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {

		$success = true;
		if(isset($resultArr['status'])&&$resultArr['status']==0){
			$success = false;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('IDN Casino got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;

	}

	//check current rate
	public function checkCurrentRate() {
		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForCheckCurrentRate',
		);

		$params = array(
			'secret_key' 	=> $this->token,
			'id' 			=> self::idnCurrentRate,
			'currency' 		=> $this->currency,
		);

		return $this->callApi(self::idnCurrentRate, $params, $context);
	}

	public function processResultForCheckCurrentRate($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success   = (array_key_exists('rate', $resultArr)) ? true : false;
		$result['rate']  = (array_key_exists('rate', $resultArr)) ? @floatval($resultArr['rate']) : 1;
		return array($success, $result);
	}

	public function createPlayer($playerName, $playerId = null, $password = null, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $password.$this->sufix_pass; //add sufix fro password, requirement for password.

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnRegister,
			'userid' => $playerName,
			'password' => $password,
			'confirm_password' => $password,
			'username' => $playerName,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		#add logs
		$this->CI->utils->debug_log('Create player response', $resultArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		//create Mobile Player
		$this->createMobilePlayer($playerName);

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);

	}

	public function createMobilePlayer($playerName) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreateMobilePlayer',
			'playerName' => $playerName,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnCreateMobileLoginid,
			'userid' => $playerName,
			'loginid' => $playerName
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreateMobilePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);

	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {

		if($oldPassword==null){
			$oldPassword = $this->getPassword($playerName);
			if(isset($oldPassword['password'])){
				$oldPassword = $oldPassword['password'];
			}
		}

		$playerId = $this->getPlayerIdInPlayer($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$newSbePassword = $newPassword;
		$newPassword = $newSbePassword.$this->sufix_pass; //add sufix fro password, requirement for password.

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'newPassword' => $newPassword,
			'newSbePassword' => $newSbePassword
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnChangePassword,
			'userid' => $playerName,
			'password' => $oldPassword.$this->sufix_pass, #add sufix old password
			'new_password' => $newPassword,
			'retypenew_password' => $newPassword
		);

		return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext((array) $params, 'newPassword');
		$newSbePassword = $this->getVariableFromContext((array) $params, 'newSbePassword');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		if ($success) {
			$this->updatePasswordForPlayer($playerId, $newSbePassword);
		}

		return array( $success, $resultArr );
	}

	public function login($username, $password = null) {

		if($password==null){
			$password = $this->getPassword($username);
		}

		if(isset($password['password'])){
			$password = $password['password'];
		}

		$username = $this->getGameUsernameByPlayerUsername($username);
		$password = $password.$this->sufix_pass; //add sufix fro password, requirement for password.

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $username
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnLogin,
			'userid' => $username,
			'password' => $password,
			'ip' => $this->CI->input->ip_address()
		);

		return $this->callApi(self::API_login, $params, $context);

	}

	public function processResultForLogin($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);

	}

	public function logout($username, $password = null) {

		if($password==null){
			$password = $this->getPassword($username);
		}

		if(isset($password['password'])){
			$password = $password['password'];
		}

		$username = $this->getGameUsernameByPlayerUsername($username);
		$password = $password.$this->sufix_pass; //add sufix fro password, requirement for password.

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $username
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnLogout,
			'userid' => $username,
			'password' => $password,
			'confirm_password' => $password,
			'username' => $username
		);

		return $this->callApi(self::API_logout, $params, $context);

	}

	public function processResultForLogout($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);

	}

	public function isPlayerExist($playerName) {
		$playerId = $this->getPlayerIdInPlayer($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnPlayerDetail,
			'userid' => $playerName
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);

	}

	public function processResultForIsPlayerExist($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = true;

		if (isset($resultArr['error'])&&$resultArr['error']==1){
			$resultArr['exists'] = false;
		}else{
			$resultArr['exists'] = true;
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array($success, $resultArr);
	}

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function gameAmountToDB($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
        // return $amount / $conversion_rate;
    }

	public function queryPlayerBalance($userName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $userName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnPlayerDetail,
			'userid' => $gameUsername,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
		$result = array();

		if($success){
			$result['balance'] = @floatval($this->gameAmountToDB($resultArr['balance']));
		}

		return array($success, $result);
	}
	
	public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$transactionId = isset($transfer_secure_id)?$transfer_secure_id:($playerId.date("YmdHis").rand());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnDeposit,
			'userid' => $gameUsername,
			'id_transaction' => $transactionId,
			'deposit' => $this->dBtoGameAmount($amount)
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
		} else {
			$error_code = @$resultArr['error'];
			switch($error_code) {
				case '1' :
					$result['reason_id'] = self::REASON_GAME_ACCOUNT_LOCKED;
					break;
				case '2' :
					$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
					break;
				case '3' :
				case '6' :
					$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
					break;
				case '4' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
					break;
				case '5' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$transactionId = isset($transfer_secure_id)?$transfer_secure_id:($playerId.date("YmdHis").rand());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnWithdraw,
			'userid' => $gameUsername,
			'id_transaction' => $transactionId,
			'withdraw' => $this->dBtoGameAmount($amount)
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);

		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
		} else {
			$error_code = @$resultArr['error'];
			switch($error_code) {
				case '1' :
					$result['reason_id'] = self::REASON_GAME_ACCOUNT_LOCKED;
					break;
				case '2' :
					$result['reason_id'] = self::REASON_DUPLICATE_TRANSFER;
					break;
				case '3' :
				case '6' :
					$result['reason_id'] = self::REASON_INVALID_TRANSACTION_ID;
					break;
				case '4' :
					$result['reason_id'] = self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
					break;
				case '5' :
				case '7' :
					$result['reason_id'] = self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);

	}

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'cs';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra=null) {

		$password = $this->getPassword($playerName);
		$password = $password['password'].$this->sufix_pass;
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
            'language' => $this->getLauncherLanguage($extra['language']),
            'is_https' => ($this->always_use_https_launcher) ? 1 : 0
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnLogin,
			'userid' => $playerName,
			'password' => $password,
			'ip' => $this->CI->input->ip_address(),
			'secure' => ($this->always_use_https_launcher) ? 1 : 0
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	public function processResultForQueryForwardGame($params){

		$playerName = $this->getVariableFromContext($params, 'playerName');
        $language = $this->getVariableFromContext($params, 'language');
        $is_https = $this->getVariableFromContext($params, 'is_https');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();

		if($success){
			$result = array('url' => $resultArr['lobby_url'].'&lang='.$language);
		}

		return array($success, $result);

	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function uid($transaction_no, $date, $game_type, $username) {
		return $transaction_no.'-'.$date.'-'.$game_type.'-'.$username;
	}

	/**
	 * API RULES
	 * a. 1 call per minute   e.g response  [message] => API request limit, please wait 1 minute
	 * b. api only keep 15 days record
	 */
	public function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		//observer the date format

		$interval = date_diff($startDate,$endDate);
		$loop_count = $interval->format("%d")+1;
		$sdate = $startDate->format('m/d/Y');

		$result = array();
		for($ctr=1;$ctr<=$loop_count;$ctr++){
			$start = "00:00";
			$end = "23:59";

			if($ctr==1){
				$start = $startDate->format('H:i');
			}
			if($ctr==$loop_count){
				$end = $endDate->format('H:i');
			}

			$result = $this->syncIdnGamelogs($sdate,$start,$end);
			$sdate = date("m/d/Y",strtotime("+1 day", strtotime($sdate)));
		}

		return $result;
	}

	public function syncIdnGamelogs($date,$start,$end){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'date' => $date,
			'start_time' => $start,
			'end_time' => $end
		);
		$params = array(
			'secret_key' => $this->token,
			'id' => self::idnTransactionList,
			'date' => $date,
			'start_time' => $start,
			'end_time' => $end
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}

	public function isMultidimensional(array $array) {
		return count($array) !== count($array, COUNT_RECURSIVE);
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('idn_game_logs', 'player_model','original_game_logs_model','external_system'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = !isset($resultArr['error'])?true:false;

		$date = $this->getVariableFromContext($params, 'date');
		$start_time = $this->getVariableFromContext($params, 'start_time');
		$end_time = $this->getVariableFromContext($params, 'end_time');

		$result = array('data_count'=> 0);
		if ($success) {
			if($resultArr['numrow'] > 0) {
				$gameRecords = $resultArr['row'];
				if(!$this->isMultidimensional($gameRecords)) { // means data only one (single array format
					$gameRecords = array();
					$gameRecords[] = $resultArr['row'];
				}
				$this->preProcessGameRecords($gameRecords,$responseResultId);

				if ($gameRecords) {
					list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
						self::ORIGINAL_LOGS_TABLE_NAME,
						$gameRecords,
						'external_uniqueid',
						'external_uniqueid',
						self::MD5_FIELDS_FOR_ORIGINAL,
						'md5_sum',
						'id',
						self::MD5_FLOAT_AMOUNT_FIELDS
					);
					$this->CI->utils->debug_log('IDN poker after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
					$this->CI->utils->debug_log("IDN poker fetch data from [$date] [$start_time] to [$end_time]");
					if (!empty($insertRows)) {
						$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
					}
					unset($insertRows);

					if (!empty($updateRows)) {
						$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
					}
					unset($updateRows);
				}
			} else {
				$this->CI->utils->debug_log('IDN poker success, but empty rows');
			}
		} else {
			$this->CI->utils->debug_log('IDN poker api response ===========> '.json_encode($resultArr));
		}
		return array($success, $result);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
		$dataCount=0;
		if(!empty($data)){
			foreach ($data as $record) {
				if ($queryType == 'update') {
					$this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
				} else {
					unset($record['id']);
					$this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
				}
				$dataCount++;
				unset($record);
			}
		}

		return $dataCount;
	}

	public function preProcessGameRecords(&$gameRecords,$responseResultId){
		$preResult = array();
		foreach($gameRecords as $index => $record) {


			if($record['status'] =="Deposit" || $record['status'] =="Withdraw"){
				continue; // skip deposit and withdraw transactions
			}
			$preResult[$index] = array();
			$playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['userid']));
			$transaction_no = isset($record['transaction_no']) ? $record['transaction_no'] : NULL;
			$date =  isset($record['date']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime(str_replace("/", "-",$record['date'])))) : NULL;
			$game_type = isset($record['game']) ? $record['game'] : NULL;

			$preResult[$index]['transaction_no'] = $transaction_no;
			$preResult[$index]['idndate'] = $date;
			$preResult[$index]['tableno'] = isset($record['tableno']) ? $record['tableno'] : NULL;
			$preResult[$index]['userid'] = isset($record['userid']) ? $record['userid'] : NULL;
			$preResult[$index]['game'] = $game_type;
			$preResult[$index]['idntable'] = isset($record['idntable']) ? $record['idntable'] : NULL;
			$preResult[$index]['periode'] = isset($record['periode']) ? $record['periode'] : NULL;
			$preResult[$index]['room'] = isset($record['room']) ? $record['room'] : NULL;
			$preResult[$index]['bet'] = isset($record['bet']) ? $record['bet'] : NULL;
			$preResult[$index]['curr_bet'] = isset($record['curr_bet']) ? $record['curr_bet'] : NULL;
			$preResult[$index]['status'] = isset($record['status']) ? $record['status'] : NULL;
			$preResult[$index]['hand'] = isset($record['hand']) ? $record['hand'] : NULL;
			$preResult[$index]['card'] = isset($record['card']) ? $record['card'] : NULL;
			$preResult[$index]['prize'] = isset($record['prize']) ? $record['prize'] : NULL;
			$preResult[$index]['curr'] = isset($record['curr']) ? $record['curr'] : NULL;
			$preResult[$index]['curr_player'] = isset($record['curr_player']) ? $record['curr_player'] : NULL;
			$preResult[$index]['amount'] = isset($record['amount']) ? $record['amount'] : NULL;
			$preResult[$index]['curr_amount'] = isset($record['curr_amount']) ? $record['curr_amount'] : NULL;
			$preResult[$index]['total'] = isset($record['total']) ? $record['total'] : NULL;
			$preResult[$index]['agent_comission'] = isset($record['agent_comission']) ? $record['agent_comission'] : NULL;
			$preResult[$index]['agent_bill'] = isset($record['agent_bill']) ? $record['agent_bill'] : NULL;
			$preResult[$index]['last_sync_time'] = $this->CI->utils->getNowForMysql();

			//extra info from SBE
			$preResult[$index]['username'] = strtolower($record['userid']);
			$preResult[$index]['playerId'] = $playerID ? $playerID : 0;
			$preResult[$index]['uniqueid'] = $this->uid($transaction_no,$date,$game_type,$preResult[$index]['username']);
			$preResult[$index]['external_uniqueid'] = $this->uid($transaction_no,$date,$game_type,$preResult[$index]['username']);
			$preResult[$index]['response_result_id'] = $responseResultId;
		}
		$gameRecords = $preResult;
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo){

		$sqlTime='idn_game_logs.idndate >= ? and idn_game_logs.idndate <= ? ';

		$sql = <<<EOD
SELECT idn_game_logs.id as sync_index,
idn_game_logs.username,
idn_game_logs.external_uniqueid,
idn_game_logs.idndate AS game_date,
idn_game_logs.response_result_id,
idn_game_logs.curr_amount AS result_amount,
idn_game_logs.periode AS game_round_id,
idn_game_logs.curr_bet AS bet_amount,
idn_game_logs.amount AS real_bet,
idn_game_logs.status AS game_status,
idn_game_logs.total AS after_balance,
idn_game_logs.hand AS hand,
idn_game_logs.prize AS prize,
idn_game_logs.card AS card,
idn_game_logs.agent_comission,
idn_game_logs.last_sync_time,

idn_game_logs.md5_sum,
idn_game_logs.game as game_code,
idn_game_logs.game,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM idn_game_logs

left JOIN game_description as gd ON idn_game_logs.game = gd.game_code and gd.game_platform_id=?
JOIN game_provider_auth ON idn_game_logs.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}

EOD;
		$params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo];

		return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
	}

	public function preprocessOriginalRowForGameLogs(array &$row){
		$game_description_id = $row['game_description_id'];
		$game_type_id = $row['game_type_id'];
		if (empty($game_description_id)) {
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
		}
		$row['game_description_id']=$game_description_id;
		$row['game_type_id']=$game_type_id;

		$row['status'] = Game_logs::STATUS_SETTLED;
		$bet_amount = $this->gameAmountToDB((float)$row['bet_amount']);
		$real_bet = $this->gameAmountToDB((float)$row['bet_amount']);
		$result_amount = $this->gameAmountToDB($row["result_amount"]);

		$game_status = strtolower($row["game_status"]);

		$status_not_include_in_turn_over =  array_map('strtolower', self::STATUS_NOT_INCLUDED_IN_TURNOVER_CALCULATION);
		$status_loss =  array_map('strtolower', self::STATUS_LOSS);

		if (in_array($game_status, $status_not_include_in_turn_over)) {
			$bet_amount = 0;
			$real_bet  = 0;
		}
		if(in_array($game_status, $status_loss)){ //status counted as loss
			$result_amount = -$result_amount;
		}

		$row['bet_amount'] = $bet_amount;
		$row['real_bet_amount'] = $real_bet;
		$row['result_amount'] = $result_amount;
		$row['after_balance'] = $this->gameAmountToDB($row["after_balance"]);
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

		$extra_info=[
			'table' => $row["game_round_id"],
			'rent' => $row["agent_comission"],
			'note' => $row['game_status'],
			'trans_amount' => $row['bet_amount'],
			'match_details' => $row["prize"],
			'match_type' => json_encode(array("hand" => $row["hand"], "card" => $row["card"])),
			'handicap' => $row["game_round_id"],
			'bet_type'  => ''
		];

		if(empty($row['md5_sum'])){
			$row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
					self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

		$logs_info = [
			'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
				'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
			'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['username']],
			'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
				'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
				'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
			'date_info'=>['start_at'=>$row['game_date'], 'end_at'=>$row['game_date'], 'bet_at'=>$row['game_date'],
				'updated_at'=>$row['last_sync_time']],
			'flag'=>Game_logs::FLAG_GAME,
			'status'=>$row['status'],
			'additional_info'=>['has_both_side'=>0, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['game_round_id'],
				'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
				'bet_type'=>null ],
			'bet_details'=> [],
			'extra'=>$extra_info,
			'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
			'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
		];

		return $logs_info;
	}

	public function syncMergeToGameLogs($token) {
		return $this->commonSyncMergeToGameLogs($token,
			$this,
			[$this, 'queryOriginalGameLogs'],
			[$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
			[$this, 'preprocessOriginalRowForGameLogs'],
			false);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row['game_code'];
		$extra = array('game_code' => $row['game_code']);

		$game_type_id = $unknownGame->game_type_id;
		$game_type = $unknownGame->game_name;

		return $this->processUnknownGame(
				$game_description_id, $game_type_id,
				$external_game_id, $game_type, $external_game_id, $extra,
				$unknownGame);
	}

	/**
	 * check both deposit and withdraw
	 * deposit action = 1
	 * withdraw action = 2
	 */
	public function queryTransaction($transactionId, $extra) {
		$playerName = $extra['playerName'];
		$playerId=$extra['playerId'];
		$transfer_method = @$extra['transfer_method'] == 'deposit' ? self::DEPOSIT : self::WITHDRAW;

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'playerId' => $playerId,
			'external_transaction_id' => $transactionId,
		);

		$params = array(
			'secret_key' => $this->token,
			'id' => self::idncheckTrans,
			'userid' => $gameUsername,
			'action' => $transfer_method,
			'id_transaction' => $transactionId,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction( $params ){
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$resultArr = json_decode(json_encode($resultXml), true);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'reason_id'=>self::REASON_UNKNOWN
		);

		// api only response if id exist or not
		// no checking if approve, processing or decline
		if ($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			# as per game provider only transaction id exists is success, no fail. 
			if($resultArr['message'] == $this->transaction_available_message){
				$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$success = true;
			}
		}

		return array($success, $result);

	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/