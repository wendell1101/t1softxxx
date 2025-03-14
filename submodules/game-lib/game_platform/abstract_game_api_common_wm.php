<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: WM
* Game Type: Live Casino
* Wallet Type: Transfer Wallet
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
**/

class Abstract_game_api_common_wm extends Abstract_game_api {

	const METHOD_GET = "GET";
	const METHOD_POST = "POST";

	const REQUEST_SUCCESS = 0;
	const REQUEST_ERROR_PLAYER_ALREADY_EXIST = 104;

	const MD5_FIELDS_FOR_GAME_LOGS = ['betid','round','winLoss','tableId','bet','validbet'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = ['bet','validbet','winLoss'];
	const MD5_FIELDS_FOR_MERGE = ['betid','round','winLoss','tableId','bet','validbet'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = ['bet_amount','real_betting_amount','result_amount'];

	public function __construct() {
		parent::__construct();

		$this->CI->load->model(array('common_token','original_game_logs_model'));

		$this->api_url = $this->getSystemInfo('url');
		// 0-Simplified Chinese,1-English,2-Thai,3-Vietnamese,4-Japanese,5-Korean,6-Indian,7-Malaysian,8-Indo,9-Traditional,10-Spanish
		$this->language = $this->getSystemInfo('language', null);
		$this->vendorId = $this->getSystemInfo('vendorId');
		$this->signature = $this->getSystemInfo('signature');
		$this->limitType = $this->getSystemInfo('limitType');
		$this->datatype = $this->getSystemInfo('datatype');
		$this->timetype = $this->getSystemInfo('timetype');
		// type of block to use - login or bet
		$this->blockType = $this->getSystemInfo('blockType');
		$this->returnurl = $this->getSystemInfo('returnurl');
		// 0-Classic,1-Chess style
		$this->ui = $this->getSystemInfo('ui');
		$this->maxwin = $this->getSystemInfo('maxwin');
		$this->maxlose = $this->getSystemInfo('maxlose');
		// 0-Chinese,1-english -this is for api return language
		$this->syslang = $this->getSystemInfo('syslang');
		$this->common_wait_seconds = $this->getSystemInfo('common_wait_seconds',30);

		$this->fix_username_limit      = $this->getSystemInfo('fix_username_limit',true);
		$this->minimum_user_length      = $this->getSystemInfo('minimum_user_length',5);
        $this->maximum_user_length      = $this->getSystemInfo('maximum_user_length',30);

		$this->uri_map = array(
			self::API_createPlayer => 'MemberRegister',
			self::API_changePassword => 'ChangePassword',
			self::API_depositToGame => 'ChangeBalance',
			self::API_withdrawFromGame => 'ChangeBalance',
			self::API_queryForwardGame => 'SigninGame',
			self::API_logout => 'LogoutGame',
			self::API_queryPlayerBalance => 'GetBalance',
			self::API_syncGameRecords => 'GetDateTimeReport',
			self::API_updatePlayerInfo => 'EditLimit',
			self::API_blockPlayer => 'EnableorDisablemem',
			self::API_unblockPlayer => 'EnableorDisablemem',
			self::API_queryTransaction => 'GetMemberTradeReport',
		);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
        
		$params_string = http_build_query($params);

		$url = $this->api_url . "?" . $params_string;

		$this->CI->utils->debug_log('WM generateUrl', $url, $apiName, 'params', $params);
		return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        $success = false;
        if(!empty($resultArr) && $resultArr['errorCode'] == self::REQUEST_SUCCESS) {
            $success = true;
        }

        if(!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('WM Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$extra = [
			'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length
        ];

    	parent::createPlayer($playerName, $playerId, $password, $email, $extra);

    	$language = $this->getLauncherLanguage($this->language);
    	$syslang = $this->getLauncherLanguage($this->syslang);
    	$playerId = $this->getPlayerIdInPlayer($playerName);
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array (
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'playerId' => $playerId,
		);

		$params = array (
			"cmd" => $this->uri_map[self::API_createPlayer],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"password" => $password,
			"username" => $gameUsername,
			"syslang" => $syslang,
		);

		// additional params if needed
		// $params['profile'] = $this->profile;
		// $params['mark'] = $this->mark;
		// $params['rakeback'] = $this->rakeback;

		if ($this->maxwin && !empty($this->maxwin)) {
			$params['maxwin'] = $this->maxwin;
		}

		if ($this->maxlose && !empty($this->maxlose)) {
			$params['maxlose'] = $this->maxlose;
		}

		if ($this->limitType && !empty($this->limitType)) {
			$params['limitType'] = $this->limitType;
		}

		$result_arr = $this->callApi(self::API_createPlayer, $params, $context);
		
		return $result_arr;
    }

    public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array('response_result_id' => $responseResultId);

		$this->CI->utils->debug_log('WM processResultForCreatePlayer: ', $success, $resultArr);

		$result = ['player' => $gameUsername];

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}

		if(isset($resultArr['errorCode']) 
			&& $resultArr['errorCode'] == self::REQUEST_ERROR_PLAYER_ALREADY_EXIST){
	        $result['exists'] = true;
			$success = true;
		}

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$syslang = $this->getLauncherLanguage($this->syslang);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'newPassword' => $newPassword
		);

		$params = array (
			"cmd" => $this->uri_map[self::API_changePassword],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"newpassword" => $newPassword,
			"syslang" => $syslang,
		);

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForChangePassword($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$this->CI->utils->debug_log('WM processResultForChangePassword: ', $success, $resultArr);
		
		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($playerName);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		$result = array(
			"player" => $playerName
		);

		return array($success, $result);
	}

	public function blockPlayer($playerName) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $syslang = $this->getLauncherLanguage($this->syslang);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];

        $params = array (
			"cmd" => $this->uri_map[self::API_blockPlayer],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"type" => $this->blockType,
			"status" => "N",
			"syslang" => $syslang,
		);

        return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForBlockPlayer($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName); 

        $this->CI->utils->debug_log('WM processResultForBlockPlayer: ', $success, $resultArr);

        if($success){
            $this->blockUsernameInDB($gameUsername);
        }

        return array($success, $resultArr);

    }

    public function unblockPlayer($playerName) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $syslang = $this->getLauncherLanguage($this->syslang);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnBlockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];

        $params = array (
			"cmd" => $this->uri_map[self::API_unblockPlayer],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"type" => $this->blockType,
			"status" => "Y",
			"syslang" => $syslang,
		);

        return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnBlockPlayer($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName); 

        $this->CI->utils->debug_log('WM processResultForUnBlockPlayer: ', $success, $resultArr);

        if($success){
            $this->unblockUsernameInDB($gameUsername);
        }

        return array($success, $resultArr);

    }

	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;
		$syslang = $this->getLauncherLanguage($this->syslang);

		$context = array (
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $external_transaction_id
		);

		$params = array (
			"cmd" => $this->uri_map[self::API_depositToGame],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"money" => $amount,
			"order" => $external_transaction_id,
			"syslang" => $syslang,
		);

		$result_arr = $this->callApi(self::API_depositToGame, $params, $context);

		return $result_arr;
    }

    public function processResultForDepositToGame($params) {

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $this->CI->utils->debug_log('WM processResultForDepositToGame: ', $success, $resultArr);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;
		$syslang = $this->getLauncherLanguage($this->syslang);

		$context = array (
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $external_transaction_id
		);

		$params = array (
			"cmd" => $this->uri_map[self::API_withdrawFromGame],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"money" => "-".$amount,
			"order" => $external_transaction_id,
			"syslang" => $syslang,
		);

		$result_arr = $this->callApi(self::API_withdrawFromGame, $params, $context);

		return $result_arr;
    }

    public function processResultForWithdrawFromGame($params) {

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $this->CI->utils->debug_log('WM processResultForWithdrawFromGame: ', $success, $resultArr);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

	}

	public function queryPlayerBalance($playerName) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $syslang = $this->getLauncherLanguage($this->syslang);

    	$context = array (
    		'callback_obj' => $this,
    		'callback_method' => 'processResultForQueryPlayerBalance',
    		'playerName' => $playerName,
    		'gameUsername' => $gameUsername
    	);

    	$params = array (
    		"cmd" => $this->uri_map[self::API_queryPlayerBalance],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"syslang" => $syslang,
		);

    	$result_arr = $this->callApi(self::API_queryPlayerBalance, $params, $context);

    	return $result_arr;
    }

    public function processResultForQueryPlayerBalance($params) {

    	$playerName = $this->getVariableFromContext($params, 'playerName');
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
    	$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

    	$result = [
    		'response_result_id' => $responseResultId
    	];

    	if ($success) {
			$result['balance'] = $resultArr['result'];
		}

    	$this->CI->utils->debug_log('WM processResultForQueryPlayerBalance: ', $success, $resultArr);

        return [$success, $result];

    }

    public function queryTransaction($transactionId, $extra) {
        $playerName = $extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $created_at = new DateTime($extra['transfer_time']);
        $updated_at = new DateTime($extra['transfer_updated_at']);

        $startDate = $created_at->format('YmdHis');
       	$endDate = $updated_at->format('YmdHis');

       	$syslang = $this->getLauncherLanguage($this->syslang);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'transaction_id' => $transactionId
        );

		$params = array (
    		"cmd" => $this->uri_map[self::API_queryTransaction],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"order" => $transactionId,
			"startTime" => $startDate,
			"endTime" => $endDate,
			"syslang" => $syslang,
		);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

	public function processResultForQueryTransaction($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$transactionId = $this->getVariableFromContext($params, 'transactionId');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'transactionId' => $transactionId,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

        $this->CI->utils->debug_log('WM processResultForQueryTransaction: ', $success, $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        } else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = self::REASON_UNKNOWN;
        }
        return array($success, $result);
    }

	public function queryForwardGame($playerName, $extra = null) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);

        $language = $extra['language'];
        if ($this->language !==null) {
            $language =$this->language;
        }
        $language =$this->getLauncherLanguage($language);
        $password = $this->CI->game_provider_auth->getPasswordByLoginName($gameUsername, $this->getPlatformCode());
        $syslang = $this->getLauncherLanguage($this->syslang);

		$isTest = '';
		$gameMode = isset($extra['game_mode'])?$extra['game_mode']:null;
		if(in_array($gameMode, $this->demo_game_identifier)){
            $isTest = 1;
        }

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'gameUsername'    => $gameUsername
		);

		$params = array (
    		"cmd" => $this->uri_map[self::API_queryForwardGame],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"password" => $password,
			"lang" => $language,
			"returnurl" => $this->returnurl,
			"isTest" => $isTest,
			"syslang" => $syslang,
		);

		// addtional params if needed
		// $params['size'] = $this->size;
		// $params['site'] = $this->site;
		
		if ($this->ui && !empty($this->ui)) {
			$params['ui'] = $this->ui;
		}

		if ($extra['game_code'] && $extra['game_code'] != null) {
			$params['mode'] = $this->getGameMode($extra['game_code']);
		}

		$this->CI->utils->debug_log('WM processResultForQueryForwardGame params: ', $params);

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {

		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$gameUsername     = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result           = array('url' => '');

		if ($success) {
			$result['url'] = @$resultArr['result'];

			if (!$this->ui) {
				$parsedUrl = parse_url(@$resultArr['result']);

				parse_str($parsedUrl['query'], $params);

				unset($params['ui']);

				$result['url'] = $parsedUrl['scheme']. '://'. $parsedUrl['host']. '?' . http_build_query($params);

			}
			
			$this->CI->utils->debug_log('WM processResultForQueryForwardGame result: ', $success, $resultArr);
		}

		return array($success, $result);
	}

	public function logout($playerName, $password = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $syslang = $this->getLauncherLanguage($this->syslang);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
        );

        $params = array (
    		"cmd" => $this->uri_map[self::API_logout],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"syslang" => $syslang,
		);

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $this->CI->utils->debug_log('WM processResultForLogout: ', $success, $resultArr);
        return array($success, $resultArr);
    }

    public function updateMemberBetSetting($playerName, $min, $max) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$syslang = $this->getLauncherLanguage($this->syslang);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForUpdateMemberBetSetting',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array (
    		"cmd" => $this->uri_map[self::API_updatePlayerInfo],
			"vendorId" => $this->vendorId,
			"signature" => $this->signature,
			"user" => $gameUsername,
			"maxwin" => $max,
			"maxlose" => $min,
			"syslang" => $syslang,
		);

		// addtional params
		// $params['reset'] = $this->reset;
		
		if ($this->limitType && !empty($this->limitType)) {
			$params['limitType'] = $this->limitType;
		}

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);

	}

	public function processForUpdateMemberBetSetting($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array('response_result_id' => $responseResultId, 'result' => $resultJson);

		$this->CI->utils->debug_log('WM processForUpdateMemberBetSetting: ', $success, $resultArr);

		return array($success, $result);

	}

    public function syncOriginalGameLogs($token = false) {

    	$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$result = array();
        $result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+12 hours', function($startDate, $endDate) {

			$startDate = $startDate->format('YmdHis');
        	$endDate = $endDate->format('YmdHis');
        	$syslang = $this->getLauncherLanguage($this->syslang);

			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
			);

			$params = array (
	    		"cmd" => $this->uri_map[self::API_syncGameRecords],
				"vendorId" => $this->vendorId,
				"signature" => $this->signature,
				"user" => "",
				"startTime" => $startDate,
				"endTime" => $endDate,
				"syslang" => $syslang
			);

			if ($this->timetype && !empty($this->timetype)) {
				$params['timetype'] = $this->timetype;
			}

			if ($this->datatype && !empty($this->datatype)) {
				$params['datatype'] = $this->datatype;
			}

			$this->CI->utils->debug_log("WM syncOriginalGameLogs params ====>", $params);

			sleep($this->common_wait_seconds);

			return $this->callApi(self::API_syncGameRecords, $params, $context);
		});
    }

    public function processResultForSyncOriginalGameLogs($params) {

		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['data_count' => 0];
		$responseRecords = !empty($resultArr) ? $resultArr : [];
		$gameRecords = !empty($responseRecords['result']) ? $responseRecords['result'] : [];

        $this->CI->utils->debug_log('---------- WM syncOriginalGameLogs response ----------', json_encode($gameRecords));

		if ($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $gameRecords = $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_GAME_LOGS,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS
            );

            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($updateRows);
		}
		$result['total_data'] = count($responseRecords['result']);
		return array($success, $result);
	}

	protected function rebuildGameRecords($gameRecords,$extra) {
		$newGR = [];

        foreach($gameRecords as $i => $gr) {

			$newGR[$i]['user']            = isset($gr['user']) ? $gr['user'] : null;
			$newGR[$i]['betid']		 = isset($gr['betId']) ? $gr['betId'] : null;
			$newGR[$i]['winLoss']		     = isset($gr['winLoss']) ? $gr['winLoss'] : null;
			$newGR[$i]['bet']	 		 = isset($gr['bet']) ? $gr['bet'] : null;
			$newGR[$i]['validbet']		 = isset($gr['validbet']) ? $gr['validbet'] : null;
			$newGR[$i]['water']	 	 = isset($gr['water']) ? $gr['water'] : null;
			$newGR[$i]['result']	 			 = isset($gr['result']) ? $gr['result'] : null;
			$newGR[$i]['betResult']	 		 = isset($gr['betResult']) ? $gr['betResult'] : null;
			$newGR[$i]['waterbet']	 		 = isset($gr['waterbet']) ? $gr['waterbet'] : null;
			$newGR[$i]['settime']	 		 = isset($gr['settime']) ? $this->gameTimeToServerTime($gr['settime']) : null;
			$newGR[$i]['betTime']	 	 = isset($gr['betTime']) ? $this->gameTimeToServerTime($gr['betTime']) : null;
			$newGR[$i]['gid']	 	 = isset($gr['gid']) ? $gr['gid'] : null;
			$newGR[$i]['ip']	 	 = isset($gr['ip']) ? $gr['ip'] : null;
			$newGR[$i]['round']	 	 = isset($gr['round']) ? $gr['round'] : null;
			$newGR[$i]['subround']	 	 = isset($gr['subround']) ? $gr['subround'] : null;
			$newGR[$i]['tableId']	 	 = isset($gr['tableId']) ? $gr['tableId'] : null;
			$newGR[$i]['gameResult']	 	 = isset($gr['gameResult']) ? $gr['gameResult'] : null;
			$newGR[$i]['gname']	 	 = isset($gr['gname']) ? $gr['gname'] : null;
			$newGR[$i]['commission']	 	 = isset($gr['commission']) ? $gr['commission'] : null;
			$newGR[$i]['reset']	 	 = isset($gr['reset']) ? $gr['reset'] : null;
			$newGR[$i]['beforeCash']	 	 = isset($gr['beforeCash']) ? $gr['beforeCash'] : null;

			$playerId = isset($gr['user']) ? $this->getPlayerIdInGameProviderAuth(strtolower($gr['user'])) : 0;

			$newGR[$i]['player_id'] = $playerId;
			$newGR[$i]['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
			$newGR[$i]['external_uniqueid']  = isset($gr['betId']) ? $gr['betId'] : null;
			$newGR[$i]['created_at']  = $this->utils->getNowForMysql();
        }

        $gameRecords = $newGR;

        return $gameRecords;
	}

	public function syncMergeToGameLogs($token) {

        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

   public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {

        $sqlTime = '`wm`.`betTime` >= ?
          AND `wm`.`betTime` <= ?';

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    wm.gid as game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    wm.user AS player_username,
    wm.round,
    wm.subround,
    wm.validbet AS bet_amount,
    wm.bet AS real_betting_amount,
    wm.winLoss AS result_amount,
    wm.betTime AS start_at,
    wm.settime AS end_at,
    wm.external_uniqueid AS external_uniqueid,
    wm.md5_sum AS md5_sum,
    wm.gameResult
    FROM {$this->original_gamelogs_table} AS wm
	LEFT JOIN game_description AS gd ON wm.gid = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type AS gt ON gd.game_type_id = gt.id
	LEFT JOIN game_provider_auth AS gpa ON wm.user = gpa.login_name
	AND gpa.game_provider_id = ?
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

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

		$round_id = $row['round'].'_'.$row['subround'];

        $extra = [
            'table' =>  $round_id,
        ];

        if (empty($row['md5_sum'])) {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => 0,
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $round_id,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => $row['gameResult'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

        if (empty($row['game_description_id'])) {

            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
    }

	private function getGameDescriptionInfo($row, $unknownGame) {

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

    protected function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name) {

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


	protected function getGameMode($game_code) {
		$game_mode = "";
		switch ($game_code) {
            case 101:
                $game_mode = 'onlybac';
                break;
            case 102:
                $game_mode = 'onlydgtg';
                break;
            case 103:
                $game_mode = 'onlyrou';
                break;
            case 104:
                $game_mode = 'onlysicbo';
                break;
            case 105:
                $game_mode = 'onlyniuniu';
                break;
            case 106:
                $game_mode = 'onlysamgong';
                break;
            case 107:
                $game_mode = 'onlyfantan';
                break;
            case 108:
                $game_mode = 'onlysedie';
                break;
            case 110:
                $game_mode = 'onlyfishshrimpcrab';
                break;
            case 111:
                $game_mode = 'onlygoldenflower';
                break;
            case 112:
                $game_mode = 'onlypaigow';
                break;
            case 113:
                $game_mode = 'onlythisbar';
                break;
            default:
                $game_mode = "";
                break;
        }
        return $game_mode;
	}

	public function getLauncherLanguage($language) {
        $lang = '';
        switch ($language) {
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
            case 'zh_cn':
                $lang = 0;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-th':
                $lang = 2;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vi':
            case 'vi-vn':
                $lang = 3;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
                $lang = 5;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
                $lang = 6;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id';
                $lang = 8;
                break;
            default:
                $lang = 1;
                break;
        }
        return $lang;
    }
	

}