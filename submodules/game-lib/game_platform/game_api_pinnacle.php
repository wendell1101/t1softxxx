<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

############### SAMPLE EXTRA INFO #######################
// {
//     "agentCode": "PSZ07",
//     "agentKey": "PSZ07_TOKEN",
//     "secretKey": "PSZ07_KEY1234567",
//     "prefix_for_username": "t1.",
//     "adjust_datetime_minutes": 5,
//     "gameTimeToServerTime": "+12 hours",
//     "serverTimeToGameTime": "-12 hours"
// }


class Game_api_pinnacle extends Abstract_game_api {

    public $origin;
	public $original_table;

	const MIX_PARLAY = 6;
    const ODDS_FORMAT = [0 => 'am', 1 => 'eu', 2 => 'hk', 3 => 'id', 4 => 'my'];

    const API_newLogin = 'newLogin';
    const SUCCESS_HTTP_CODES = [200];

    const ERROR_PLAYER_ALREADY_EXIST = 111;

	const URI_MAP = array(
		self::API_createPlayer => '/player/create',
		self::API_isPlayerExist => '/player/info',
		self::API_queryPlayerBalance => '/player/info',
		self::API_depositToGame => '/player/deposit',
		self::API_withdrawFromGame => '/player/withdraw',
		self::API_login => '/player/login',
		self::API_newLogin => '/player/loginV2',
		self::API_logout => '/player/logout',
		self::API_syncGameRecords => '/report/all-wagers',
		self::API_queryTransaction => '/player/depositwithdraw/status',
		self::API_getEvents => '/v1/hot-events',
	);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->agentCode = $this->getSystemInfo('agentCode');
		$this->agentKey = $this->getSystemInfo('agentKey');
		$this->secretKey = $this->getSystemInfo('secretKey');
		$this->locale = $this->getSystemInfo('language','en');
		$this->externalUrl = $this->getSystemInfo('externalUrl');

		# fix exceed game username length
		$this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 6);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 16);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 6);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

        $this->odds_format_for_esports = $this->getSystemInfo('odds_format_for_esports', false);

        $this->use_loginv2_on_launching = $this->getSystemInfo('use_loginv2_on_launching', false);

        $this->do_logout_before_login = $this->getSystemInfo('do_logout_before_login', false);

        $this->enable_logout = $this->getSystemInfo('enable_logout', true);

        $this->esports_view = $this->getSystemInfo('esports_view', 'ESPORTS-HUB');

        $this->sports_view = $this->getSystemInfo('sports_view', null);

        $this->non_login_sports_launch_url = $this->getSystemInfo('non_login_sports_launch_url');
		$this->non_login_launch_url = $this->getSystemInfo('non_login_launch_url');
		$this->non_login_types = $this->getSystemInfo('non_login_types', ['sports'=>'sports', 'e_sports'=>'esports-hub']);

        $this->non_login_esports_launch_url = $this->getSystemInfo('non_login_esports_launch_url');
        $this->origin = $this->getSystemInfo('origin');

        $this->trigger_wrong_withdraw_amount=$this->getSystemInfo('trigger_wrong_withdraw_amount', false);


        $this->get_events_locale = $this->getSystemInfo('get_events_locale');
		$this->get_events_sports = $this->getSystemInfo('get_events_sports');
		$this->cget_events_odds_format = $this->getSystemInfo('get_events_odds_format');

		$this->default_language = $this->getSystemInfo('default_language', 'en');
		$this->force_language = $this->getSystemInfo('force_language', '');

		$this->currency = $this->getSystemInfo('currency', '');
		
        $this->enable_mm_channel_notifications = $this->getSystemInfo('enable_mm_channel_nofifications', false);
		$this->mm_channel = $this->getSystemInfo('mm_channel', 'test_mattermost_notif');
		$this->original_table = $this->getOriginalTable();

		$this->demo_locale = $this->getSystemInfo('demo_locale');

		$this->game_records_locale = $this->getSystemInfo('game_records_locale', 'en');

        $this->allow_launch_demo_without_authentication=$this->getSystemInfo('allow_launch_demo_without_authentication', true);
        $this->use_new_non_login_url_format=$this->getSystemInfo('use_new_non_login_url_format', false);

	}

	public function getPlatformCode() {
		return PINNACLE_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		unset($params['method']);
		$url = $this->api_url.$apiUri.'?'.http_build_query($params);
		return $url;
	}

	protected function customHttpCall($ch, $params) {
		$method = $params['method'];
		unset($params['method']);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if($method == 'POST'){
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}

	public function getHttpHeaders($params){
		$token = $this->generateAccessToken();
		return array("userCode"=>$this->agentCode,'token'=>$token,'Content-type' => 'application/json');
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $statusCode) {
        $this->CI->utils->debug_log('PINNACLE processResultBoolean ===========================>', 
        'playerName', $playerName, 
        'result', $resultArr, 
        'statusCode', $statusCode);

		$success = false;
		if(!isset($resultArr['code']) && !isset($resultArr['trace'])) { // trace is for "unexpected error"
			$success = true;
		}

        if(!in_array($statusCode, self::SUCCESS_HTTP_CODES)){
			$success = false;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PINNACLE API got error ===========================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function generateAccessToken(){
		$date = new DateTime();
		$timestamp = $date->getTimestamp()*1000;
		$hashToken = md5($this->agentCode.$timestamp.$this->agentKey);
		$tokenPayLoad =	$this->agentCode.'|'.$timestamp.'|'.$hashToken;
		$iv = 'RandomInitVector';
		$token = $this->encryptAES($this->secretKey,$tokenPayLoad,$iv);

		return $token;
	}

	public function encryptAES($key, $data, $iv) {
		if (16 !== strlen($key)) {
			$key = hash('MD5', $key, true);
		}

		if (16 !== strlen($iv)) {
			$iv = hash('MD5', $iv, true);
		}

		$padding = 16 - (strlen($data) % 16);
		$data .= str_repeat(chr($padding), $padding);
		return base64_encode(@mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv));
	}

	public function createPlayer($userName, $playerId, $password, $email = null, $extra = null) {

		$extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ];

		parent::createPlayer($userName, $playerId, $password, $email, $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
			'playerId' => $playerId
        );

        $params = array(
			"agentCode" => $this->agentCode,
			"loginId" => $playerName,
			"method" => "POST"
        );

        $this->utils->debug_log("CreatePlayer params ============================>", $params);
        return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $sbe_playerName, $statusCode);

		if($success){
			#incase of dormant acc duplicates, set old to null
			$duplicatePlayerIds = $this->getDuplicateExternalAccountId($playerName, $resultJsonArr['userCode']);
			if($duplicatePlayerIds){
				$playerData = ['external_account_id'=>null, 'register'=>0];
				$this->updateGameProviderAuthDetailsForPlayer($duplicatePlayerIds,$playerData);
			}
			$this->updateExternalAccountIdForPlayer($playerId, $resultJsonArr['userCode']);
		}

        //success if player already exist
        if(isset($resultJsonArr['code'])&&$resultJsonArr['code']==self::ERROR_PLAYER_ALREADY_EXIST){
            $success = true;
        }

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}

		return array($success, $resultJsonArr);
	}

	protected function getDuplicateExternalAccountId($loginName,$externalAccountId) {
		$this->CI->load->model(['game_provider_auth']);
		$sql = <<<EOD
select 
player_id
from game_provider_auth
where game_provider_id = ?
and external_account_id=?
and login_name<>?
EOD;
	
		$params = [$this->getPlatformCode(), $externalAccountId, $loginName];
	
		$this->utils->debug_log('getDuplicateExternalAccountId sql', $sql, $params);
	
		$result=$this->CI->game_provider_auth->runRawSelectSQLArray($sql, $params);
		return array_column($result, 'player_id');
	}

	public function isPlayerExist($playerName){
		$playerId=$this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
            'playerId' => $playerId,
			'playerName' => $playerName
		);

		$params = array(
			'userCode' => !empty($gameUsername)?$gameUsername:$playerName,
			"method" => "GET"
		);
		$this->utils->debug_log("isPlayerExist params ============================>", $params);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

	public function processResultForIsPlayerExist($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
		$resultJsonArr = $this->getResultJsonFromParams($params);

		$this->CI->utils->debug_log('processResultForIsPlayerExist ==========================>', $resultJsonArr);
        $result=['exists' => null, 'response_result_id'=>$responseResultId];

		// $success=isset($resultJsonArr['code']);
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName, $statusCode);

		if(isset($resultJsonArr['code'])&&$resultJsonArr['code'] == '104'){
    		$result['exists'] = false;
		}else if($success){
			$result['exists'] = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

            #check if exist on provider, then check if external account id not exist then update
            // $playerName = $this->getVariableFromContext($params, 'playerName');
            $externalAccountId = $this->getExternalAccountIdByPlayerUsername($playerName);
            if(empty($externalAccountId)){
            	$this->updateExternalAccountIdForPlayer($playerId, $resultJsonArr['userCode']);
            }
		}

        return array($success, $result);
	}

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
        );

       	$params = array(
			'userCode' => $playerName,
			"method" => "GET"
		);

        $this->utils->debug_log("queryPlayerBalance params ============================>", $params);
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForQueryPlayerBalance ==========================>', $resultJsonArr);
		
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr,$playerName, $statusCode);
		$result = array();

        if ($success) {
        	$result['balance'] = @floatval($resultJsonArr['availableBalance']);
       		$result['exists'] = true;
        }else{
        	$this->utils->debug_log("PINNACLE queryPlayerBalance got error ============================>", $resultJsonArr);
        }

       	return array($success, $result);

	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$external_trans_id = intval($this->utils->getTimestampNow() . random_string('numeric', '6'));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
			'external_transaction_id' => $external_trans_id
        );

        $params = array(
			"userCode" 		=> $gameUsername,
			"amount"		=> $amount,
			'transactionId' => $external_trans_id,
			"method" 		=> "POST"
        );
        $this->utils->debug_log("Deposit params ============================>", $params);

        return $this->callApi(self::API_depositToGame, $params, $context);

	}

	public function processResultForDepositToGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr,$gameUsername, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

        if(isset($resultJsonArr['code'])){
            $success = false;
            $this->utils->error_log("PINNACLE depositToGame failed code exist in result ============================>", $resultJsonArr);
        }

        if(isset($resultJsonArr['trace'])){
            $success = false;
            $this->utils->error_log("PINNACLE depositToGame failed tracec exist in result ============================>", $resultJsonArr);
        }

        if ($success) {
        	//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);
			// //for sub wallet
			// $result["after_balance"] = $afterBalance = $playerBalance['balance'];
			// //$result["external_transaction_id"] = $this->generateTransferId();

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
			$error_code = @$resultJsonArr['code'];
			switch($error_code) {
				case '104' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
				case '108' :
				case '110' :
					$result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
					break;
				case '306' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case '307' :
				case '309' :	// api resonse (account balance not exist in system)
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
			}

			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			#verify transaction status if success is false
			if($this->verify_transfer_using_query_transaction){
				$query_transaction_extra['playerName'] = $playerName;
				$query_transaction_extra['playerId'] = null; #unnecessary param
				$query_transaction_result = $this->queryTransaction($external_transaction_id, $query_transaction_extra);

				$success = true; #better to assume that deposit is success to avoid loss.
				if($query_transaction_result['status'] != self::COMMON_TRANSACTION_STATUS_APPROVED){
					$success = false;
				}
			}

			#treat 500 as success
			if(in_array($statusCode, $this->other_status_code_treat_as_success) && $this->treat_500_as_success_on_deposit){
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				$success=true;
			}
		}
		
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$external_trans_id = intval($this->utils->getTimestampNow() . random_string('numeric', '6'));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
			'external_transaction_id' => $external_trans_id,
			'amount' => $amount
        );

        $params = array(
			"userCode" 		=> $gameUsername,
			"amount"		=> $amount,
			'transactionId' => $external_trans_id,
			"method" 		=> "POST"
        );
        $this->utils->debug_log("PINNACLE withdrawFromGame params ============================>", $params);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    function processResultForWithdrawFromGame($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr,$gameUsername,$statusCode);

        $this->utils->debug_log("PINNACLE processResultForWithdrawFromGame params ============================>", $resultJsonArr, 
        'statusCode', $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

        if(isset($resultJsonArr['code'])){
            $success = false;
            $this->utils->error_log("PINNACLE withdrawFromGame failed code exist in result ============================>", $resultJsonArr);
        }

        if(isset($resultJsonArr['trace'])){
            $success = false;
            $this->utils->error_log("PINNACLE withdrawFromGame failed tracec exist in result ============================>", $resultJsonArr);
        }

        if ($success) {
        	//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($playerName);

			// //for sub wallet
			// $result["after_balance"] = $afterBalance = $playerBalance['balance'];
			// //$result["external_transaction_id"] = $this->generateTransferId();

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//withdraw
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
			$error_code = @$resultJsonArr['code'];
			switch($error_code) {
				case '104' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
				case '108' :
				case '110' :
					$result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
					break;
				case '306' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case '307' :
                case '310' :
				case '309' :	// api resonse (account balance not exist in system)
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $success = false;
		}
			#verify transaction status
			if($this->verify_transfer_using_query_transaction){
				$query_transaction_extra['playerName'] = $playerName;
				$query_transaction_extra['playerId'] = null; #unnecessary param
				$query_transaction_result = $this->queryTransaction($external_transaction_id, $query_transaction_extra);

				$success = false; #assume false, unless confirmed approved.
				if($query_transaction_result['status'] == self::COMMON_TRANSACTION_STATUS_APPROVED){
					$success = true;
				}

			}
		

        if($this->trigger_wrong_withdraw_amount){
            $resultJsonArr['amount'] = $this->trigger_wrong_withdraw_amount;
            $this->utils->debug_log("PINNACLE processResultForWithdrawFromGame params trigger_wrong_withdraw_amount ============================>", $resultJsonArr, 
            'statusCode', $statusCode);
        }
        
        if(!isset($resultJsonArr['amount'])||$resultJsonArr['amount']<>$amount){
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=self::REASON_INVALID_TRANSFER_AMOUNT;
            $success = false;
        }

        return array($success, $result);
    }

    public function login($userName, $extra = null) {
		$this->utils->debug_log("PINNACLE login extra ============================>", $extra);
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName
        );
		$language = isset($extra['language']) ? $extra['language'] : $this->locale;
		$locale = $this->getLocale($language);

        if ($extra['game_type'] == 'sports' || $extra['game_type'] == 'e-sports' || $extra['game_type'] == 'e_sports') {
	        $params = array(
				"userCode" => $playerName,
				"locale" => $locale,
				"method" => "POST"
	        );
	        $api = self::API_login;
        }

        if ($this->use_loginv2_on_launching) {

			if($extra['game_type'] =='e-sports' || $extra['game_type'] =='esports'){
				$extra['game_type'] = 'e_sports';
			}

	        $params = array(
				"loginId" => $playerName,
				"locale"  => $locale,
				"sport"   => $extra['game_type'] = $extra['game_type'] == 'e_sports' ? 'e-sports' : 'sports',
				"method"  => "POST"
	        );

	        // TRADITIONAL SPORTS don't need parameter on launching
        	if ($extra['game_type'] === 'sports' || $extra['game_type'] === '_null' || !isset($extra['game_type'])) {
        		unset($params['sport']);
        	}

        	if (isset($extra['event_id'])&&is_numeric($extra['event_id'])) {
                $params['eventId'] = $extra['event_id'];
        	}

        	if (isset($extra['extra']['game_event_id'])&&is_numeric($extra['extra']['game_event_id'])) {
                $params['eventId'] = $extra['extra']['game_event_id'];
        	}

	        if ($this->odds_format_for_esports !== false) {
	        	$params['oddsFormat'] = $this->odds_format_for_esports;
	        }
	        $api = self::API_newLogin;
        }

        if ($extra['game_type'] === 'e-sports' || $extra['game_type'] === 'e_sports') {
			if (!empty($this->esports_view)) {
            	$params['view'] = $this->esports_view;
			}
        }else{
			if (!empty($this->sports_view)) {
				$params['view'] = $this->sports_view;
			}	
		}

        $this->utils->debug_log("Login params ============================>", $params);
        return $this->callApi($api, $params, $context);
	}

	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('login result  params ============================>', $resultJsonArr);
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName, $statusCode);

		return array($success, $resultJsonArr);
	}

	public function getLocale($sbeLanguage){

		if($this->force_language && !empty($this->force_language)){
            return $this->force_language;
        }

        $sbeLanguage = strtolower($sbeLanguage);
		switch ($sbeLanguage) {
            case 'zh-tw':
            case 'zh-cn':
            case 'zh_tw':
            case 'zh_cn':
			case Language_function::INT_LANG_CHINESE:
            case Language_function::PLAYER_LANG_CHINESE :
                $locale = 'zh-cn';
                break;
            case 'id':
            case 'id-id':
            case 'id_id':
            case Language_function::INT_LANG_INDONESIAN:
            case Language_function::PLAYER_LANG_INDONESIAN :
                $locale = 'id';
                break;
            case 'vi':
            case 'vi-vn':
            case 'vi_vn':
            case Language_function::INT_LANG_VIETNAMESE:
            case Language_function::PLAYER_LANG_VIETNAMESE :
                $locale = 'vi';
                break;
            case 'ko':
            case 'ko-kr':
            case 'ko_kr':
            case Language_function::INT_LANG_KOREAN:
            case Language_function::PLAYER_LANG_KOREAN :
                $locale = 'ko';
                break;
            case 'th':
            case 'th-th':
            case 'th_th':
            case Language_function::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $locale = 'th';
                break;
            case 'pt':
			case 'pt-br':
			case 'pt-pt':
			case 'pt_br':
			case 'pt_pt':
            case Language_function::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
                $locale = 'pt';
                break;
            case 'hi':
            case 'in':
            case 'hi-in':
            case 'hi_in':
            case Language_function::INT_LANG_INDIA:
            case Language_function::INT_LANG_PORTUGUESE :
                $locale = 'hi';
                break;
            case 'es':
            case 'es-es':
            case 'es_es':
            case 'es-MX':
            case 'es-mx':
            case Language_function::INT_LANG_SPANISH:
            case Language_function::PLAYER_LANG_SPANISH:
            case Language_function::INT_LANG_SPANISH_MX:
                $locale = 'es';
                break;
            default:
                $locale = $this->default_language;
                break;
		}
		return $locale;
	}

	public function logout($playerName, $password = null) {

        if(!$this->enable_logout){            
            return $this->returnUnimplemented();
        }

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName
        );

        $params = array(
			"userCode" => $playerName,
			"method" => "POST"
        );

        $this->utils->debug_log("Logout params ============================>", $params);
        return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName, $statusCode);

		return array($success, $resultJsonArr);
	}

	public function queryForwardGame($userName, $extra = null) {
		
        if((isset($extra['game_mode']) && in_array($extra['game_mode'], ['demo', 'trial', 'no-login'])) || empty($userName)) {
			$language = isset($extra['language']) ? $extra['language'] : $this->locale;
			$locale = $this->getLocale($language);

			if(!empty($this->demo_locale)){
				$locale = $this->getLocale($this->demo_locale);
			}

			$gameType = 'sports';

			if ($this->do_logout_before_login && !empty($userName)) {
				$this->logout($userName);
			}

			if(isset($extra['game_type'])&&($extra['game_type']=='esports'||$extra['game_type']=='e-sports' || $extra['game_type']=='e_sports')){
				//return array("success"=>true,"url"=>$this->non_login_esports_launch_url);
				$gameType = 'e_sports';
			}

			$paramsGameType = isset($this->non_login_types[$gameType])?$this->non_login_types[$gameType]:'sports';

			if($this->use_new_non_login_url_format){
				$non_login_launch_url = $this->non_login_launch_url.'/'.$locale.'/'.'standard'.'/'.$paramsGameType. '/'.'home'.'/';
			}else{
				$non_login_launch_url = $this->non_login_launch_url.'/'.$locale.'/'.$paramsGameType;
			}
			$non_login_launch_url .= '?currency='.$this->currency;
			
            //return array("success"=>true,"url"=>$this->non_login_sports_launch_url, 'origin' => $this->origin);
			
            return array("success"=>true,"url"=>$non_login_launch_url, 'origin' => $this->origin);
        }

		if ($this->do_logout_before_login) {
			$this->logout($userName);
		}

        if(!isset($extra['game_type'])||empty($extra['game_type'])){
            $extra['game_type'] = 'sports';
        }

		$login = $this->login($userName, $extra);
		$url='';
		$home_url = isset($extra['extra']['home_link'])?$extra['extra']['home_link']:$this->externalUrl;
		$success = false;

		if(isset($login['loginUrl'])){
			$url = $login['loginUrl'];
			$success = true;
		}

		return array("success"=>$success,"url"=>$url, 'origin' => $this->origin);
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$startDate = new DateTime($startDate->format('Y-m-d H:00:00'));
		$maxEndDate = new DateTime($endDate->format('Y-m-d H:00:00'));
		if($endDate->format('Y-m-d H:i:s') != $endDate->format('Y-m-d H:00:00')){
			$endDate = $endDate->modify("+1 Hours");
			$maxEndDate = new DateTime($endDate->format('Y-m-d H:00:00'));
		}

		$queryStartDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$queryEndDate = new DateTime($startDate->format('Y-m-d H:i:s'));

		while ($queryStartDate < $maxEndDate) {
			$queryStartDate = new DateTime($queryEndDate->format('Y-m-d H:i:s'));
			$queryEndDate->modify("+1 Hours");

			$wagerData = $this->syncWagerGamelogs($queryStartDate,$queryEndDate);
			$settleDate = $this->syncSettleGamelogs($queryStartDate,$queryEndDate);

			$resultData[] = array(
				array(
					"startDate" => $queryStartDate->format('Y-m-d H:i:s'),
					"endDate" => $queryEndDate->format('Y-m-d H:i:s')
				),
				$wagerData,
				$settleDate
			);

		}

		return array("success"=>true,"result"=>$resultData);
	}

	public function syncWagerGamelogs($startDate,$endDate){
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate
        );

        $params = array(
			"dateFrom" => $startDate->format('Y-m-d H:i:s'),
			"dateTo" => $endDate->format('Y-m-d H:i:s'),
			"filterBy" => 'wager_date', //wager_date || settle_date
			"locale" => $this->game_records_locale,
			"method" => "GET"
        );

        return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function syncSettleGamelogs($startDate,$endDate){
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate
        );

        $params = array(
			"dateFrom" => $startDate->format('Y-m-d H:i:s'),
			"dateTo" => $endDate->format('Y-m-d H:i:s'),
			"filterBy" => 'settle_date', //wager_date || settle_date
			"locale" => $this->game_records_locale,
			"method" => "GET"
        );

        return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processSyncOriginalGameLogs($params){
		$this->CI->load->model(array('pinnacle_game_logs', 'player_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$gameRecords = $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, null, $statusCode);
		$result = array('data_count'=>0);
		if ($success) {
			if ($gameRecords) {
				$dataCount = 0;
				if (!empty($gameRecords)) {
					foreach ($gameRecords as $record) {
						$insertRecord = array();
						$playerID = $this->getPlayerIdByExternalAccountId($record['userCode']);

						if(!$playerID){
							if ($this->enable_mm_channel_notifications) {
								$sql = $this->CI->db->last_query();
								$baseUrl =  $this->utils->getBaseUrlWithHost();
							
								$message = "@all Non-existing Player Alert"."\n";
								$message .= "Client: ".$baseUrl."\n";
								$message .= PHP_EOL;
								$message .= PHP_EOL;
								$message .= "Last Query:" . "\n" .
											"----------------------------" .  "\n".
											$sql;
								$currentFile = __FILE__;
								$currentLine = __LINE__;
								$currentMethod = __METHOD__;
								
								$message .= "\n\n Current file: $currentFile\n";
								$message .= "\n Current method: $currentMethod\n";
								$message .= "\n Current line: $currentLine\n";
							
								$this->SendNotificationToMattermost($message);
								
							}
							continue;
						}

						$playerUsername = $this->getGameUsernameByPlayerId($playerID);
						$insertRecord['wagerId'] = isset($record['wagerId'])?$record['wagerId']:null;
						$insertRecord['eventName'] = isset($record['eventName'])?$record['eventName']:null;
						$insertRecord['parentEventName'] = isset($record['parentEventName'])?$record['parentEventName']:null;
						$insertRecord['headToHead'] = isset($record['headToHead'])?$record['headToHead']:null;
						$insertRecord['wagerDateFm'] = isset($record['wagerDateFm'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['wagerDateFm']))):null;
						$insertRecord['eventDateFm'] = isset($record['eventDateFm'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['eventDateFm']))):null;
						$insertRecord['status'] = isset($record['status'])?$record['status']:null;
						$insertRecord['homeTeam'] = isset($record['homeTeam'])?$record['homeTeam']:null;
						$insertRecord['awayTeam'] = isset($record['awayTeam'])?$record['awayTeam']:null;
						$insertRecord['selection'] = isset($record['selection'])?$record['selection']:null;
						$insertRecord['handicap'] = isset($record['handicap'])?$record['handicap']:null;
						$insertRecord['odds'] = isset($record['odds'])?$record['odds']:null;
						$insertRecord['oddsFormat'] = isset($record['oddsFormat'])?$record['oddsFormat']:null;
						$insertRecord['betType'] = isset($record['betType'])?$record['betType']:null;
						$insertRecord['league'] = isset($record['league'])?$record['league']:null;
						$insertRecord['stake'] = isset($record['stake'])?$record['stake']:null;
						$insertRecord['sport'] = isset($record['sport'])?$record['sport']:null;
						if($record['betType']==self::MIX_PARLAY){
							$insertRecord['sport'] = "MIX_PARLAY";
						}
						$insertRecord['currencyCode'] = isset($record['currencyCode'])?$record['currencyCode']:null;
						$insertRecord['inplayScore'] = isset($record['inplayScore'])?$record['inplayScore']:null;
						$insertRecord['inPlay'] = isset($record['inPlay'])?$record['inPlay']:null;
						$insertRecord['homePitcher'] = isset($record['homePitcher'])?$record['homePitcher']:null;
						$insertRecord['awayPitcher'] = isset($record['awayPitcher'])?$record['awayPitcher']:null;
						$insertRecord['homePitcherName'] = isset($record['homePitcherName'])?$record['homePitcherName']:null;
						$insertRecord['awayPitcherName'] = isset($record['awayPitcherName'])?$record['awayPitcherName']:null;
						$insertRecord['period'] = isset($record['period'])?$record['period']:null;
						$insertRecord['parlaySelections'] = isset($record['parlaySelections'])?json_encode($record['parlaySelections']):null;
						$insertRecord['category'] = isset($record['category'])?$record['category']:null;
						$insertRecord['toWin'] = isset($record['toWin'])?$record['toWin']:null;
						$insertRecord['toRisk'] = isset($record['toRisk'])?$record['toRisk']:null;
						$insertRecord['product'] = isset($record['product'])?$record['product']:null;
						$insertRecord['parlayMixOdds'] = isset($record['parlayMixOdds'])?$record['parlayMixOdds']:null;
						$insertRecord['competitors'] = isset($record['competitors'])?json_encode($record['competitors']):null;
						$insertRecord['userCode'] = isset($record['userCode'])?$record['userCode']:null;
						$insertRecord['winLoss'] = isset($record['winLoss'])?$record['winLoss']:null;
						$insertRecord['winLoss'] = isset($record['winLoss'])?json_encode($record['winLoss']):null;
						$insertRecord['result'] = isset($record['result'])?json_encode($record['result']):null;

						//extra info from SBE
						$insertRecord['userName'] = $playerUsername;
						$insertRecord['playerId'] = $playerID;
						$insertRecord['uniqueid'] = $insertRecord['wagerId']; //add external_uniueid for og purposes
						$insertRecord['external_uniqueid'] = $insertRecord['wagerId']; //add external_uniueid for og purposes
						$insertRecord['response_result_id'] = $responseResultId;

						// only settled , cancelled and deleted  have settledDate if open and queue value must be null
						if($record['status']=="SETTLED"||$record['status']=="CANCELLED"||$record['status']=="DELETED"){
							$insertRecord['settledDate'] = isset($record['settleDateFm'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['settleDateFm']))):null;
						}
						//insert data to Pinnacle gamelogs table database
						$this->CI->pinnacle_game_logs->syncGameLogs($insertRecord, $this->getOriginalTable());
						$dataCount++;
					}
					$result['data_count'] = $dataCount;
				}
			}
		}
		return array($success, $result);
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('game_logs', 'player_model', 'pinnacle_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->pinnacle_game_logs->getGameLogStatistics($startDate, $endDate, $this->getPlatformCode(), $this->getOriginalTable());
		$cnt = 0;
		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $pinnacle_data) {

				if (!$pinnacle_data['playerId']) {
					continue;
				}

				$note = null;

				$cnt++;

				$game_description_id = $pinnacle_data['game_description_id'];
				$game_type_id = $pinnacle_data['game_type_id'];

				if (empty($game_description_id)) {

					list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($pinnacle_data, $unknownGame);

					if (empty($game_description_id)) {
						$this->CI->utils->debug_log('empty game_description_id', $unknownGame);
						continue;
					}
				}

				$status = $this->getGameRecordsStatus($pinnacle_data['status']);

				$bet_amount_for_cashback = $bet_amount = $real_betting_amount = $pinnacle_data['bet_amount'];
                # IF Match is DRAW valid bet is 0, This is refunded to player.
                /*if (strpos(strtolower($pinnacle_data['result']),'draw') !== false){
					// $valid_bet = 0;
					$pinnacle_data['bet_amount'] = 0;
				}*/

				###### START PROCESS BET AMOUNT CONDITIONS
				# get bet conditions for status
				$betConditionsParams = [];
				$betConditionsParams['bet_status'] = strtolower( trim($pinnacle_data['result'], '"') );

				# get bet conditions for win/loss
				$betConditionsParams['win_loss_status'] = null;
				$betConditionsParams['odds_status'] = null;

				if($pinnacle_data['winLoss']<0){
					if(abs($pinnacle_data['winLoss']) / $pinnacle_data['bet_amount'] == .5 ){
						$betConditionsParams['win_loss_status'] = 'half_lose';
					}
				}else{
					if($pinnacle_data['winLoss'] / $pinnacle_data['bet_amount'] == .5 ){
						$betConditionsParams['win_loss_status'] = 'half_win';
					}
				}

				# get bet conditions for odds
				$oddsType = $this->getUnifiedOddsType($pinnacle_data['oddsFormat']);
				$betConditionsParams['valid_bet_amount'] = $bet_amount;
				$betConditionsParams['bet_amount_for_cashback'] = $bet_amount;
				$betConditionsParams['real_betting_amount'] = $real_betting_amount;
				$betConditionsParams['odds_type'] = $oddsType;
				$betConditionsParams['odds_amount'] = $pinnacle_data['odds'];
				
				list($_appliedBetRules, $_validBetAmount, $_betAmountForCashback, $_realBettingAmount, $_betconditionsDetails, $note) = $this->processBetAmountByConditions($betConditionsParams);
				if(!empty($_appliedBetRules)){
					$bet_amount = $_validBetAmount;
					$bet_amount_for_cashback = $_betAmountForCashback;
					$real_betting_amount = $_realBettingAmount;
				}

				###### /END PROCESS BET AMOUNT CONDITIONS

                $this->utils->debug_log('==============> SelectionsDetails value', $pinnacle_data['parlaySelections']);
                $sectDetails = json_decode($pinnacle_data['parlaySelections'], true);
                //$sportsGameFields = array();
                //if ($status == Game_logs::STATUS_SETTLED) {
                $sportsGameFields = array(
                    'match_details' => !empty($sectDetails) ? $sectDetails[0]['eventName'] : $pinnacle_data['eventName'],
                    'match_type'    => !empty($sectDetails) ? $sectDetails[0]['inPlay'] : $pinnacle_data['inPlay'],
                    'handicap'      => !empty($sectDetails) ? $sectDetails[0]['handicap'] : $pinnacle_data['handicap'],
                    'bet_type'      => strtolower($pinnacle_data['game_code']) == 'mix_parlay' ? 'Mix Parlay' : 'Single Bet'
                );
                //}
                $this->utils->debug_log('==============> Pinnacle Sport Game Fields Value', $sportsGameFields);

				$bet_conditions_details = [];
				if(!empty($_betconditionsDetails)){
					$bet_conditions_details = $_betconditionsDetails;
				}

				$betDetails =  $this->utils->encodeJson(array_merge(
                        $this->processBetDetatails($pinnacle_data),
                        array('sports_bet' => $this->setBetDetails($pinnacle_data), 'Odds Type' => $oddsType
						)
                    )
                );

				$extra = array(
					'trans_amount'	=> 	$real_betting_amount,
					'status'		=> 	$status,
					'table' 		=>  $pinnacle_data['RoundID'],
					'odds' 			=>  $pinnacle_data['odds'],
					'odds_type'   =>  $oddsType,
					'note'			=>  $note,#json_encode($bet_conditions_details),
                    'bet_details'   =>  $betDetails,
                    'sync_index'  => $pinnacle_data['id'],
                    'real_betting_amount'  => $real_betting_amount,
				);


		
				$this->debug_external_uniqueid = $this->getSystemInfo('debug_external_uniqueid', false);
				if($pinnacle_data['external_uniqueid']==$this->debug_external_uniqueid){

					$this->utils->debug_log('==============> debug_external_uniqueid', 
					'pinnacle_data', $pinnacle_data,
					'betConditionsParams', $betConditionsParams);
				}
				
				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$pinnacle_data['game_code'],
					$pinnacle_data['game_type'],
					$pinnacle_data['game'],
					$pinnacle_data['playerId'],
					$pinnacle_data['userName'],
					$bet_amount,
					$pinnacle_data['result_amount'],
					null, # win_amount
					null, # loss_amount
					null,//$pinnacle_data['after_balance'], # after_balance
					0, # has_both_side
					$pinnacle_data['external_uniqueid'],
					$pinnacle_data['game_date'], //start
					$pinnacle_data['settled_date'], //end
					$pinnacle_data['response_result_id'],
					Game_logs::FLAG_GAME,
					$extra,
                    $sportsGameFields
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	protected function getGameDescriptionInfo($row, $unknownGame) {
		$this->CI->utils->debug_log('getGameDescriptionInfo monitor', 'row', $row);
		$game_description_id = null;
		$external_game_id = $row['original_game_code'];
        $extra = array('game_code' => $row['original_game_code'], 'game_name' => $row['original_game_code']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	protected function getGameRecordsStatus($status) {
		$this->CI->load->model(array('game_logs'));

		switch ($status) {
			case 'OPEN':
				$status = Game_logs::STATUS_ACCEPTED;
			case 'PENDING':
				$status = Game_logs::STATUS_PENDING;
				break;
			case 'CANCELLED':
				$status = Game_logs::STATUS_REJECTED;
				break;
			case 'DELETED':
				$status = Game_logs::STATUS_VOID;
				break;
			case 'SETTLED':
				$status = Game_logs::STATUS_SETTLED;
				break;
		}
		return $status;
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

	public function processBetDetatails($data) {
		if(!is_array($data)) {
			$data = json_decode(json_encode($data), true);
		}

		$details = array(
			"Bet"	=> $data['bet_amount'],
			"Rate"	=> $data['odds'],
			"Event" => $data['eventName'],
			"League" => $data['league'],
			"Status" => $data['status'],
		);
		return  $details;
	}

    public function setBetDetails($field){
        $data = json_decode($field['parlaySelections'],true);
        $set = array();
        if(!empty($data)){
            foreach ($data as $key => $game) {
                $set[$key] = array(
                    'yourBet' => $game['selection'],
                    'isLive' => $game['inPlay'] > 0,
                    'odd' => $game['odds'],
                    'hdp'=> $game['handicap'],
                    'htScore'=> $game['scores'] ,
                    'eventName' => $game['eventName'],
                    'league' => $game['league'],
                );
            }
        }else{
            $set[] = array(
                'yourBet' => $field['selection'],
                'isLive' => $field['inPlay'] > 0,
                'odd' => $field['odds'],
                'hdp'=> $field['handicap'],
                'htScore'=> $field['inplayScore'],
                'eventName' => $field['eventName'],
                'league' => $field['league'],
            );
        }
        return $set;
    }

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

		// convert to long

		$params = array(
			'userCode' => $playerName,
			"method" => "GET",
			'transactionId' => intval($transactionId)
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$transId = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, null, $statusCode);

		$this->CI->utils->debug_log('PINNACLE query transaction', $resultJsonArr, 'transaction id', $transId);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$transId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN
		);
		if($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$status = isset($resultJsonArr['status']) ? $resultJsonArr['status'] : null;
			switch($status) {
				case 'NOT_EXISTS' :
					$result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
					break;
				case 'FAILED' :
					$result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
					$result['reason_id']=self::REASON_FAILED_FROM_API;
					break;
			}
		} else {
			$error_code = @$resultJsonArr['status'];
			switch($error_code) {
				case 'NOT_EXISTS' :
					$result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
					break;
				case 'FAILED' :
					$result['reason_id']=self::COMMON_TRANSACTION_STATUS_DECLINED;
					break;
			}
		}

		return array($success, $result);
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
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

	public function getHotEvents($sports = "soccer", $locale = null, $oddsFormat = null){

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetHotEvents',
            'sports' => $sports,
			'locale' => $locale,
			'oddsFormat' => $oddsFormat,
		);

		$params = array(
			'sports' => $sports,
			"method" => "GET"
		);
		if(!empty($locale)){
			$params['locale'] = $locale;
		}
		if(!empty($oddsFormat)){
			$params['oddsFormat'] = $oddsFormat;
		}

		$this->utils->debug_log("getHotEvents params ============================>", $params);

        return $this->callApi(self::API_getEvents, $params, $context);
    }

	public function processResultForGetHotEvents($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('processResultForGetHotEvents ==========================>', $resultJsonArr);
        return array(true, $resultJsonArr);
	}

    public function syncEvents($token) {
        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameEvents',
            
		);

		$params = array(
			"method" => "GET"
		);

		if(isset($this->get_events_sports) && !empty($this->get_events_sports)){
			$params['sports'] = $this->get_events_sports;
		}

		if(empty($params['sports'])){
			//get game list

			$this->CI->load->model(['game_description_model']);
			$gameList = $this->CI->game_description_model->getGameByGamePlatformId($this->getPlatformCode());
			$games = array_column($gameList, 'external_game_id');
			$cleanGames = array_map(function($a){
				return preg_replace('![\s]+!u', '-', strtolower($a));
			}, $games);
			$params['sports'] = implode(',', $cleanGames);
		}

		if(isset($this->get_events_odds_format) && !empty($this->get_events_odds_format)){
			$params['oddsFormat'] = $this->get_events_odds_format;
		}

		if(isset($this->get_events_locale) && !empty($this->get_events_locale)){
			$params['locale'] = $this->get_events_locale;
		}

		$this->utils->debug_log("getHotEvents params ============================>", $params);

        return $this->callApi(self::API_getEvents, $params, $context);
    }

	public function processResultForSyncGameEvents($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = false;
		$result = [];

		$this->CI->load->model(array('game_event_list'));

		$events = [];
		foreach($resultJsonArr as $game){
			if(isset($game['leagues']) && !empty($game['leagues']) && is_array($game['leagues'])){
				foreach($game['leagues'] as $league){
					foreach($league['events'] as $event){
						$extra = $event;
						$temp = [];
						$temp['game_platform_id'] = $this->getPlatformCode();
						$temp['league_id'] = isset($league['id'])?$league['id']:null;
						$extra['league_id'] = $temp['league_id'];
						$extra['league_name'] = isset($league['name'])?$league['name']:null;

						$temp['event_id'] = isset($event['id'])?$event['id']:null;
						$temp['extra'] = json_encode( $extra);
						$temp['status'] = Game_event_list::STATUS_NORMAL;
						$temp['created_at'] = $this->utils->getNowForMysql();
						$starts = isset($event['starts'])?$event['starts']:'';
						$startDate = new DateTime($starts);
						$temp['start_at'] = $startDate->format('Y-m-d H:i:s');
						$temp['end_at'] = $temp['start_at'];
						$home = isset($event['home'])?$event['home']:null;
						$away = isset($event['away'])?$event['away']:null;
						$temp['event_name'] = $home . ' v ' . $away;
						$temp['event_banner_url'] = null;
						$temp['pc_enable'] = Game_event_list::STATUS_PC_ENABLE;
						$temp['mobile_enable'] = Game_event_list::STATUS_MOBILE_ENABLE;
						
						$events[] = $temp;
					}
				}
			}

		}

		$success=$this->CI->original_game_logs_model->runBatchInsertWithLimit($this->CI->db, 'game_event_list', $events, 200, $cnt, true);


		//$this->CI->utils->debug_log('processResultForSyncGameEvents resultJsonArr ==========================>', $resultJsonArr);
        return array($success, $result);
	}

	public function SendNotificationToMattermost($message){
		$this->CI->load->helper('mattermost_notification_helper');
		$notif_message = array(
			array(
				'text' => $message,
				'type' => 'warning'
			)
		);
		$platformCode = $this->getPlatformCode();
	
		 sendNotificationToMattermost("Non Existing Player - (Pinnacle - $platformCode)", $this->mm_channel, $notif_message, null);
    }

	public function getUnifiedOddsType($odds){
		switch ((int)$odds) {
            case 0:
              return 'US';
            case 1:
              return 'EU';
            case 2:
                return 'HK';
            case 3:
                return 'ID';
            case 4:
                return 'MY';
          }

          return $odds;
	}

	public function getOriginalTable()
	{
		return 'pinnacle_game_logs' ;
	}

}

/*end of file*/
