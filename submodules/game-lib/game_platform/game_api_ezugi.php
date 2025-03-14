<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/******************************
	{
	    "agent_id": "1114",
	    "operator_username": "fundWEBE",
	    "prefix_for_username": "",
	    "adjust_datetime_minutes": 10,
	    "api_salt": "7ccc64c711e950f2a620f69fda4f8cad"
	}
*******************************/

class Game_api_ezugi extends Abstract_game_api {

	private $api_url;
	private $agent_id;
	private $operator_username;
	private $api_salt;
	private $session_token;
	private $game_history_url;
	private $isjson_request = false;
	//For Game History
	private $gamelogs_APIID;
	private $gamelogs_APIUser;
	private $gamelogs_APIAccess;
	private $gamelogs_APIUrl;

	const URI_MAP = array(
		self::API_createPlayer => '/agent_api/player/register.php',
		self::API_changePassword => '/agent_api/player/change_password.php',
		self::API_depositToGame => '/agent_api/cashier/funds_transfer_to_player.php',
		self::API_withdrawFromGame => '/agent_api/cashier/funds_transfer_from_player.php',
		self::API_queryPlayerBalance => '/agent_api/player/balance.php',
		// self::API_isPlayerExist => '/agent_api/player/balance.php',
		self::API_isPlayerExist => '/agent_api/player/login.php',
		self::API_login => '/agent_api/player/login.php',
		self::API_queryForwardGame => '/agent_api/player/game_token.php',
		self::API_syncGameRecords => '/get/'
	);

	const ERROR_CODE_SUCCESS = 0;
	const ERROR_CODE_INVALID_ERROR = 1;
	const ERROR_CODE_AUTH_ERROR = 2;
	const ERROR_CODE_PLAYER_AUTH_ERROR = 3;
	const ERROR_CODE_PLAYER_REGISTER_ERROR = 4;
	const ERROR_CODE_TRANSACTION_FAILED = 5;
	const ERROR_CODE_API_ERROR = 6;
	const ERROR_CODE_MAINTENANCE = 7;
	const ERROR_CODE_LOGIN_FAILED = 8;
	const ERROR_CODE_INSUFFICIENT_FUNDS = 9;
	// const ERROR_CODE_LOGIN_FAILED = 8;
    const ERROR_DETAILS_CODE_PLAYER_NOT_FOUND = 1;
    const ERROR_DETAILS_PLAYER_NOT_FOUND = "Player Not Found";

    const LIVE_DEALER_LOBBY_GAME_CODE = 1;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->agent_id = $this->getSystemInfo('agent_id');
		$this->operator_username = $this->getSystemInfo('operator_username');
		$this->api_salt	= $this->getSystemInfo('api_salt');
		$this->gamelogs_APIID = $this->getSystemInfo('gamelogs_APIID');
		$this->gamelogs_APIUser	= $this->getSystemInfo('gamelogs_APIUser');
		$this->gamelogs_APIAccess = $this->getSystemInfo('gamelogs_APIAccess');
		$this->gamelogs_APIUrl = $this->getSystemInfo('gamelogs_APIUrl');
		$this->lobby_url = $this->getSystemInfo('lobby_url');
		$this->bank_url = $this->getSystemInfo('bank_url');
		$this->is_redirect = $this->getSystemInfo('is_redirect', true);#default true so the home button will work
	}

	public function getPlatformCode() {
		return EZUGI_API;
	}

	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);

		if($apiName == self::API_syncGameRecords){
			$url = $this->gamelogs_APIUrl.$apiUri;
		}else{
			$url = $this->api_url.$apiUri."?{$params_string}";
		}

		return $url;
	}

	protected function customHttpCall($ch, $params) {
		if($this->isjson_request){
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
			curl_setopt($ch, CURLOPT_HTTPHEADER,['Content-Type: application/json']);
		}
		else{
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_POST, true);
		}

	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if($resultArr['error_code']==0){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('EZUIGI got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function isPlayerExist($playerName){
		$password = $this->getPasswordString($playerName);


		$username = $this->getGameUsernameByPlayerUsername($playerName);
		$ip_address = $this->CI->input->ip_address();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'player_username' => $username,
			'player_password' => $password,
			'username' => $this->operator_username,
			'session_ip' => $ip_address,
			'login' => 'new'
		);
		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }


    function processResultForIsPlayerExist($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        if ($success) {
        	$result = array('exists' => true);
        	$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
        	$result = array('exists' => null); #default on error
        	if(isset($resultJsonArr['error_code']) && $resultJsonArr['error_code'] == self::ERROR_CODE_LOGIN_FAILED &&
        		( ( isset($resultJsonArr['details']) && $resultJsonArr['details'] == self::ERROR_DETAILS_PLAYER_NOT_FOUND ) ||
        		  ( isset($resultJsonArr['error_details_code']) && $resultJsonArr['error_details_code'] == self::ERROR_DETAILS_CODE_PLAYER_NOT_FOUND )
        		)
        	){
				$success = true;
				$result['exists'] = false;
			}
        }

        return array($success, $result);
    }

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		//create curl resource
        // $ch = curl_init();

        // // set url
        // curl_setopt($ch, CURLOPT_URL, "https://oms.eld88.com/agent_api/player/register.php?agent_id=3244&username=gptotINR1&player_username=wsbtestt1&player_password=123456&nickname=wsbtestt1&session_ip=0.0.0.0&request_token=a4e6d8d6204a00639a71e1b0eba7ad4c7583adba2cd7ce29408842c4ef5cac67");

        // //return the transfer as a string
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // // $output contains the output string
        // $output = curl_exec($ch);
        // var_dump($output);exit();
        // // close curl resource to free up system resources
        // curl_close($ch);
		// exit();
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$ip_address = $this->CI->input->ip_address();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'username' => $this->operator_username,
			'player_username' => $playerName,
			'player_password' => $password,
			'nickname' => $playerName,
			'session_ip' => $ip_address,
		);

		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;
		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		// $resultText = $this->getResultTextFromParams($params);
		// $resultJsonArr = json_decode($resultText,TRUE);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$this->CI->utils->debug_log('EZUGI CREATE PLAYER RESPONSE',$resultArr);
		if($success){
			// $this->session_token = isset($resultArr['session'])?$resultArr['session']['session_token']:'';
			//update external AccountID
			$this->updateExternalAccountIdForPlayer($playerId, $resultArr['player']['id']);
		}

		return array($success, $resultArr);

	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {

		// if($newPassword==null){
		// 	$oldPassword = $this->getPassword($playerName);
		// 	if(isset($oldPassword['password'])){
		// 		$oldPassword = $oldPassword['password'];
		// 	}
		// }
		// echo "<pre>";print_r($oldPassword);exit;
		$oldPassword = $this->getPasswordString($playerName);
		$this->login($playerName,$oldPassword);

		// if(!$isPlayerExist['success']&&$isPlayerExist['error_code']==8){
		// 	$oldPassword = $this->getPassword($playerName);
		// 	$playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
		// 	$this->createPlayer($playerName,$playerId, $oldPassword);
		// }

		// if(!$this->session_token){
		// 	$this->login($playerName,$oldPassword); // login if no session token
		// }

		$username = $this->getGameUsernameByPlayerUsername($playerName);
		$ip_address = $this->CI->input->ip_address();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'newPassword' => $newPassword,
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'username' => $this->operator_username,
			'session_ip' => $ip_address,
			'old_password' => $oldPassword,
			'new_password' => $newPassword,
			'session_token' => $this->session_token
		);

		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params) {
		$responseResultId = $this->getResponseResultIdFromParams((array) $params);
		// $resultText = $this->getResultTextFromParams($params);
		// $resultJsonArr = json_decode($resultText,TRUE);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		return array($success, $resultArr);
	}

	function login($playerName, $password = null) {

		$password = $this->getPasswordString($playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$ip_address = $this->CI->input->ip_address();

		// if(isset($password['password'])){
		// 	$password = $password['password'];
		// }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'player_username' => $gameUsername,
			'player_password' => $password,
			'username' => $this->operator_username,
			'session_ip' => $ip_address,
			'login' => 'new'
		);
		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_login, $params, $context);
	}

	function processResultForLogin($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultText = $this->getResultTextFromParams($params);
		$resultJsonArr = json_decode($resultText,TRUE);

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		if($success){
			$this->session_token = isset($resultJsonArr['session'])?$resultJsonArr['session']['session_token']:'';
		}
		return array($success, $resultJsonArr);

	}

	function depositToGame($userName, $amount, $transfer_secure_id=null) {
		// if(!$this->session_token){
			$this->login($userName); // login if no session token
		// }
		$gameusername = $this->getGameUsernameByPlayerUsername($userName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $gameusername,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'username' => $this->operator_username,
			'payment_method' => 1, // 1 for Cash, 2 for Voucher
			'amount' => $amount,
			'session_token' => $this->session_token
		);

		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		if($success){
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}  else {
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        	if(isset($resultArr['error_code'])){
        		$result['reason_id'] = $this->getReasons($resultArr['error_code']);
        	}
        }
		return array($success, $result);
	}

	private function getReasons($error_code){
		switch ($error_code) {
			case self::ERROR_CODE_INVALID_ERROR:
				return self::REASON_ILLEGAL_REQUEST;
				break;
			case self::ERROR_CODE_AUTH_ERROR:
			case self::ERROR_CODE_PLAYER_AUTH_ERROR:
			case self::ERROR_CODE_LOGIN_FAILED:
				return self::REASON_LOGIN_PROBLEM;
				break;
			case self::ERROR_CODE_MAINTENANCE:
				return self::REASON_API_MAINTAINING;
				break;
			case self::ERROR_CODE_API_ERROR:
			case self::ERROR_CODE_TRANSACTION_FAILED:
				return self::REASON_FAILED_FROM_API;
				break;
			case self::ERROR_CODE_INSUFFICIENT_FUNDS:
				return self::REASON_NO_ENOUGH_BALANCE;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	function queryPlayerBalance($userName) {
		$this->login($userName);
		$ip_address = $this->CI->input->ip_address();

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $userName
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'username' => $this->operator_username,
			'session_ip' => $ip_address,
			'session_token' => $this->session_token,
		);

		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$result["balance"] = $this->gameAmountToDB(@$resultArr['balance']);
		} else {
			$result['exists'] = null;
		}
		return array($success,$result);
	}


	function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		// if(!$this->session_token){
			$this->login($userName); // login if no session token
		// }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'username' => $this->operator_username,
			'payment_method' => 1, // 1 for Cash, 2 for Voucher
			'amount' => $amount,
			'session_token' => $this->session_token
		);

		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	function processResultForWithdrawFromGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		if($success){
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}  else {
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        	if(isset($resultArr['error_code'])){
        		$result['reason_id'] = $this->getReasons($resultArr['error_code']);
        	}
        }
		return array($success, $result);
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	function queryForwardGame($playerName, $extra=null) {
		$username = $this->getGameUsernameByPlayerUsername($playerName);
		$ip_address = $this->CI->input->ip_address();

		if(!$this->session_token){
			$this->login($playerName); // login if no session token
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'username' => $username,
            'extra' => $extra,
		);

		$params = array(
			'agent_id' => $this->agent_id,
			'username' => $this->operator_username,
			'session_ip' => $ip_address,
			'session_token' => $this->session_token,
			'provider_id' => is_null($extra['game_code']) ? self::LIVE_DEALER_LOBBY_GAME_CODE : $extra['game_code'],
			'language' => $this->getLauncherLanguage($extra['language'])
		);

		$request_token = hash ( "sha256", $this->api_salt . http_build_query($params));
		$params['request_token'] = $request_token;

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	function processResultForQueryForwardGame($params){

		$username = $this->getVariableFromContext($params, 'username');
		$extra = $this->getVariableFromContext($params, 'extra');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$username);
		$result = array();
		$this->CI->utils->debug_log('EZUGI PLAY RESPONSE',$resultArr);

		if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		if(empty($this->bank_url)){
			$this->bank_url = $this->utils->getSystemUrl('player','/player_center/dashboard/cashier#memberCenter');
			$this->appendCurrentDbOnUrl($this->bank_url);
		}

		$extra_param_str = "&homeUrl={$this->lobby_url}&cashierUrl={$this->bank_url}";

        if(isset($extra['lobby']) && $extra['lobby'] != null) {
            $extra_param_str = "&selectGame={$extra['lobby']}" . $extra_param_str;
        }

		if($success){
			$result = array('url' => $resultArr['provider']['main_url'] . $extra_param_str);
		}

		$result['redirect'] = $this->is_redirect ? true : false;
		return array($success, $result);

	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function syncOriginalGameLogs($token = false) {

		$this->isjson_request = true;

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());
		//observer the date format
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate = $endDate->format('Y-m-d H:i:s');

		$limit = 500;
		$page = 1;
		return $this->_continueSync( $startDate, $endDate, $limit, $page);

	}

	function _continueSync( $startDate, $endDate, $limit, $page){
		$return = $this->syncEzugiGamelogs($startDate,$endDate,$limit,$page);
		if($return['success'] && isset($return['current_page']) && isset($return['total_page'])){
			if((int)$return['current_page'] < (int)$return['total_page']){
				$page = (int)$return['current_page']+1;
				$return = $this->_continueSync( $startDate, $endDate, $limit, $page);
			}
		}
		return $return;
	}

	function syncEzugiGamelogs($startDate,$endDate,$limit,$page){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		$params = array(
			'DataSet' => 'per_round_report',
			'Limit' => $limit,
			'StartTime' => $startDate,
			'EndTime' => $endDate,
			'Page' => $page,
			'APIID' => $this->gamelogs_APIID,
			'APIUser' => $this->gamelogs_APIUser
		);

		$params["RequestToken"]	= hash('sha256',$this->gamelogs_APIAccess.http_build_query($params));

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('ezugi_game_logs', 'player_model'));
		$resultArr = $this->getResultJsonFromParams($params);
		// echo "<pre>";
		// print_r($resultArr);exit();
		$this->CI->utils->debug_log('==============>>>EZUGI SYNC ORIGINAL RESPONSE',$resultArr);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = !isset($resultArr['ErrorCode'])?true:false;
		$result = array();
		if ($success) {
			$current_page = $resultArr['page_number'];
			$total_page = ceil($resultArr['total_rows']/$resultArr['rows_per_page']);

			$gameRecords = $resultArr['data'];
			if ($gameRecords) {
				$availableRows = $this->CI->ezugi_game_logs->getAvailableRows($gameRecords);

				$dataCount = 0;
				if (!empty($availableRows)) {
					foreach ($availableRows as $record) {

						if($record['BetType'] != "Game Credit"){
							continue; //continue if bettype not game credit
						}

						$insertRecord = array();
						$playerID = $this->getPlayerIdByExternalAccountId($record['UID']);
						$playerUsername = $this->getGameUsernameByPlayerId($playerID);

						//Data from Ezugi
						$insertRecord['BetTypeID'] = isset($record['BetTypeID']) ? $record['BetTypeID'] : NULL;
                        $insertRecord['ezugiID'] = isset($record['ID']) ? $record['ID'] : NULL;
                        $insertRecord['ezugiID4'] = isset($record['ID4']) ? $record['ID4'] : NULL;
                        $insertRecord['RoundID'] = isset($record['RoundID']) ? $record['RoundID'] : NULL;
                        $insertRecord['ServerID'] = isset($record['ServerID']) ? $record['ServerID'] : NULL;
                        $insertRecord['TableID'] = isset($record['TableID']) ? $record['TableID'] : NULL;
                        $insertRecord['UID'] = isset($record['UID']) ? $record['UID'] : NULL;
                        $insertRecord['UID2'] = isset($record['UID2']) ? $record['UID2'] : NULL;
                        $insertRecord['OperatorID'] = isset($record['OperatorID']) ? $record['OperatorID'] : NULL;
                        $insertRecord['OperatorID2'] = isset($record['OperatorID2']) ? $record['OperatorID2'] : NULL;
                        $insertRecord['SessionCurrency'] = isset($record['SessionCurrency']) ? $record['SessionCurrency'] : NULL;
                        $insertRecord['SkinID'] = isset($record['SkinID']) ? $record['SkinID'] : NULL;
                        $insertRecord['BetSequenceID'] = isset($record['BetSequenceID']) ? $record['BetSequenceID'] : NULL;
                        $insertRecord['Bet'] = isset($record['Bet']) ? $record['Bet'] : NULL;
                        $insertRecord['Win'] = isset($record['Win']) ? $record['Win'] : NULL;
                        $insertRecord['Bankroll'] = isset($record['Bankroll']) ? $record['Bankroll'] : NULL;
                        $insertRecord['GameString'] = isset($record['GameString']) ? $record['GameString'] : NULL;
                        $insertRecord['GameString2'] = isset($record['GameString2']) ? $record['GameString2'] : NULL;
                        $insertRecord['SeatID'] = isset($record['SeatID']) ? $record['SeatID'] : NULL;
                        $insertRecord['BetStatusID'] = isset($record['BetStatusID']) ? $record['BetStatusID'] : NULL;
                        $insertRecord['BrandID'] = isset($record['BrandID']) ? $record['BrandID'] : NULL;
                        $insertRecord['RoundDateTime'] = isset($record['RoundDateTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['RoundDateTime']))) : NULL;
                        $insertRecord['ActionID'] = isset($record['ActionID']) ? $record['ActionID'] : NULL;
                        $insertRecord['BetType'] = isset($record['BetType']) ? $record['BetType'] : NULL;
                        $insertRecord['PlatformID'] = isset($record['PlatformID']) ? $record['PlatformID'] : NULL;
                        $insertRecord['DateInserted'] = isset($record['DateInserted']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['DateInserted']))) : NULL;
                        $insertRecord['GameTypeID'] = isset($record['GameTypeID']) ? $record['GameTypeID'] : NULL;
                        $insertRecord['BFTransactionFound'] = isset($record['BFTransactionFound']) ? $record['BFTransactionFound'] : NULL;
                        $insertRecord['GameTypeName'] = isset($record['GameTypeName']) ? $record['GameTypeName'] : NULL;
                        $insertRecord['DealerID'] = isset($record['DealerID']) ? $record['DealerID'] : NULL;
                        $insertRecord['ErrorCode'] = isset($record['ErrorCode']) ? $record['ErrorCode'] : NULL;
                        $insertRecord['originalErrorCode'] = isset($record['originalErrorCode']) ? $record['originalErrorCode'] : NULL;
                        $insertRecord['TransactionID'] = isset($record['TransactionID']) ? $record['TransactionID'] : NULL;

						//extra info from SBE
						$insertRecord['Username'] = $playerUsername;
						$insertRecord['PlayerId'] = $playerID;
						$insertRecord['external_uniqueid'] = $record['ID']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;

						//insert data to Ezugi gamelogs table database
						$this->CI->ezugi_game_logs->insertGameLogs($insertRecord);
						$dataCount++;
					}

					$result['data_count'] = $dataCount;
					$result['current_page'] = $current_page;
					$result['total_page'] = $total_page;
				}
			}
		}
		return array($success, $result);
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'ezugi_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->ezugi_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;

		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $ezugi_data) {
				$player_id = $ezugi_data["PlayerId"];

				if (!$player_id) {
					continue;
				}

				$cnt++;
				$gameString = json_decode($ezugi_data["GameString"],true);
				$totalBets = $gameString['BetAmount'];

				$result_amount = $ezugi_data["result_amount"] - $totalBets;

				$game_description_id = $ezugi_data["game_description_id"];
				$game_type_id = $ezugi_data["game_type_id"];

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}
				//round
				$extra = array('table' => $ezugi_data["RoundID"]);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$ezugi_data["game_code"],
					$ezugi_data["game_type"],
					$ezugi_data["game"],
					$player_id,
					$ezugi_data["Username"],
					$totalBets,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$ezugi_data["external_uniqueid"],
					$ezugi_data["game_date"], //start
					$ezugi_data["game_date"], //end
					$ezugi_data["response_result_id"],
					1,
                	$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	function getGameTimeToServerTime() {
		return '+8 hours';
	}

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	function getServerTimeToGameTime() {
		return '-8 hours';
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {
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