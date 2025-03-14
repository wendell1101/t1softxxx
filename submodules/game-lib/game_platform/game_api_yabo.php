<?php
/**
 * YABO Game Integration (OB)
 * OGP-20313
 *
 * @author 	Jerbey Capoquian
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_yabo extends Abstract_game_api {


	const ORIGINAL_LOGS_TABLE_NAME = 'yabo_gamelogs';
	

	const MD5_FIELDS_FOR_ORIGINAL=['betAmount','validBetAmount','netAmount','beforeAmount','createdAt','netAt','updatedAt','result'];
	const MD5_FLOAT_AMOUNT_FIELDS=['betAmount','validBetAmount','netAmount','beforeAmount'];
	const MD5_FIELDS_FOR_MERGE=['real_bet_amount','bet_amount','result_amount','bet_at','end_at','bet_details','before_balance','after_balance'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['real_bet_amount','bet_amount','result_amount','before_balance','after_balance'];

	#ERROR CODE 
	const ERROR_CODE_SUCCESS = 200;
	const ERROR_CODE_GAME_ACCOUNT_NOT_EXIST = 20001;

	#Device Type
	const DEVICE_TYPE_WEB_PAGE = 1;
	const DEVICE_TYPE_MOBILE_WEB_PAGE = 2;

	#Syn Constant
	const DEFAULT_PAGE_INDEX = 1;
	const DEFAULT_PAGE_TOTAL = 1;

	const NICKNAME_LIMIT = 12;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url','https://api.zr.casino');//default API URL for basic interface
		$this->api_data_url = $this->getSystemInfo('api_data_url','https://api-data.zr.casino');//default API URL for data interface
		$this->aes_key = $this->getSystemInfo('aes_key','HMi6bsscXKWDaGah'); //default aes key for testing merchant
		$this->md5_key = $this->getSystemInfo('md5_key','1q8ZvspFhR4vXVRQ'); //default aes key for testing merchant
		$this->version = $this->getSystemInfo('version','v1');//default version 
		$this->language = $this->getSystemInfo('language', 3);//default english 
		$this->odd_type = $this->getSystemInfo('odd_type', 4445); //default 1 ~ 1000 
		$this->merchant_code = $this->getSystemInfo('merchant_code', 'WD9H7'); //default merchant code on testing merchant
		$this->sync_sleep_time      = $this->getSystemInfo('sync_sleep_time', 0);
	}

	const URI_MAP = array(
		self::API_createPlayer => "/api/merchant/create/v2",
		self::API_queryPlayerBalance => "/api/merchant/balance/v1",
		self::API_isPlayerExist => "/api/merchant/balance/v1",
		self::API_depositToGame => '/api/merchant/deposit/v1',
		self::API_withdrawFromGame => '/api/merchant/withdraw/v1',
		self::API_queryForwardGame => '/api/merchant/forwardGame/v2',
		self::API_queryDemoGame => '/api/merchant/tryPlay/v1',
		self::API_syncGameRecords => '/data/merchant/betHistoryRecord/v1',
		self::API_changePassword => '/api/merchant/resetLoginPwd/v1',
		self::API_syncTipRecords => '/data/merchant/rewardRecordList/v1',
	);

	public function getPlatformCode() {
		return YABO_GAME_API;
	}

	public static function encrypt($data, $key) {
		$data =  openssl_encrypt($data, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
		return base64_encode($data);
	}
	public static function decrypt($data, $key) {
		$encrypted = base64_decode($data);
		return openssl_decrypt($encrypted, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
	}

	/**
     * test
     * @param $reqestJson
     * @return ReqJson
     */
    public function test()
    {
    	$sample_request = array(
    		"loginName" => "j7l6testuser",
    		"timestamp" => "1578662499442"
    	);
    	$reqestJson = json_encode($sample_request);
    	$signature = strtoupper(md5($reqestJson . $this->md5_key)); #05C4A4E1FD156F94012089342F9EC2EE
    	$base64Params = $this->encrypt($reqestJson, $this->aes_key);

    	return $reqJson = array(
    		"merchantCode" => "testmerchant",
    		"params" => $base64Params,
    		"signature" => $signature
    	);
    }

    /**
     *
     * @param array $params
     * 
     * @return array 
     */
    protected function getHttpHeaders($params)
    {
        $headers['Content-Type'] = 'application/json';
        if( isset($params['merchantCode']) ){
        	$headers['merchantCode'] = $params['merchantCode'];
        }
        if( isset($params['pageIndex']) ){
        	$headers['pageIndex'] = $params['pageIndex'];
        }
        return $headers;
    }


	/**
	 * overview : custom http call
	 *
	 * @param $ch
	 * @param $params
	 */
	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		return $errCode || intval($statusCode, 10) >= 503;
	}

	public function generateUrl($apiName, $params) {
		$uri = self::URI_MAP[$apiName];
		$url = $this->api_url . "{$uri}";
		if($apiName == self::API_syncGameRecords || $apiName == self::API_syncTipRecords){
			$url = $this->api_data_url . "{$uri}";
		}
		return $url;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = (isset($resultArr['code']) && $resultArr['code'] == self::ERROR_CODE_SUCCESS) ? true : false;
		$result = array();
		if(!$success){
			$this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('yabo got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}


	function getTimestamp() {
		return round(microtime(true)*1000);
	    // $mt = explode(' ', microtime());
	    // return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
	}

	/**
     * generateParam
     * @param $requestParam
     * @return array param
     */
    function generateParam($requestParam)
    {
        $requestJson = json_encode($requestParam);
    	$signature = strtoupper(md5($requestJson . $this->md5_key));
    	$base64Params = $this->encrypt($requestJson, $this->aes_key);
        return  array(
        	'merchantCode' => $this->merchant_code,
        	'params' => $base64Params,
        	'signature' => $signature
        );
    } 


	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
		);
		$request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'loginPassword' => $password,
            'nickName' =>  $gameUsername,
            // 'oddType' => $this->odd_type,
            'lang' => $this->language,
            'timestamp' => $this->getTimestamp()
        );
        if( (strlen($gameUsername) > self::NICKNAME_LIMIT) ){
        	unset($request_params['nickName']);
        }
		$this->CI->utils->debug_log('-----------------------Yabo createPlayer request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);

		$this->CI->utils->debug_log('-----------------------Yabo createPlayer params ----------------------------',$params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if(isset($arrayResult['message']) && isset($arrayResult['code'])){
			$result['code'] = $arrayResult['code'];
			$result['message'] = $arrayResult['message'];
		}
		return array($success, $result);
	}

	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

        $request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'timestamp' => $this->getTimestamp()
        );
		$this->CI->utils->debug_log('-----------------------Yabo isPlayerExist request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);

		$this->CI->utils->debug_log('-----------------------Yabo isPlayerExist params ----------------------------',$params);
		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

    public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);

		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);	
		}  else {
			$result['exists'] = null;
			if(isset($arrayResult['code']) && $arrayResult['code'] == self::ERROR_CODE_GAME_ACCOUNT_NOT_EXIST){
				$success = true;
				$result['exists'] = false;
			}
		}
		return array($success,$result);
    }


	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

        $request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'timestamp' => $this->getTimestamp()
        );
		$this->CI->utils->debug_log('-----------------------Yabo queryPlayerBalance request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);

		$this->CI->utils->debug_log('-----------------------Yabo queryPlayerBalance params ----------------------------',$params);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			if(isset($arrayResult['data']['balance'])){
				$result["balance"] = $this->gameAmountToDB($arrayResult['data']['balance']);
			}
		} else {
			$result['exists'] = null;
		}
		return array($success,$result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = "D-".$this->utils->getDatetimeNow().$gameUsername;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'external_transaction_id' => $transfer_secure_id
		);

		$request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'transferNo' => $transfer_secure_id,
            'amount' => $this->dBtoGameAmount($amount),
            'timestamp' => $this->getTimestamp()
        );
		$this->CI->utils->debug_log('-----------------------Yabo depositToGame request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);

		$this->CI->utils->debug_log('-----------------------Yabo depositToGame params ----------------------------',$params);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
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
			if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
			}
        }
		return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = "W-".$this->utils->getDatetimeNow().$gameUsername;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'external_transaction_id' => $transfer_secure_id
		);

		$request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'transferNo' => $transfer_secure_id,
            'amount' => $this->dBtoGameAmount($amount),
            'timestamp' => $this->getTimestamp()
        );
		$this->CI->utils->debug_log('-----------------------Yabo withdrawFromGame request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);

		$this->CI->utils->debug_log('-----------------------Yabo withdrawFromGame params ----------------------------',$params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
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
        }
		return array($success, $result);
    }

    public function queryDemoGame($params){
    	$language = $params['language'];

        if($this->language != null) {
            $language = $this->language;
        }

        $language = $this->getLauncherLanguage($language);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => null,
            'language' => $language,
        );

        $device_type = ( isset($params['is_mobile']) && $params['is_mobile']) ? self::DEVICE_TYPE_MOBILE_WEB_PAGE :  self::DEVICE_TYPE_WEB_PAGE;
        if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$request_params = array(
            'deviceType' => $device_type,
            'oddType' => $this->odd_type,
            'lang' => $language,
            'backurl' => $this->lobby_url,
            'timestamp' => $this->getTimestamp()
        );

        $this->CI->utils->debug_log('-----------------------Yabo queryDemoGame request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);
        $this->CI->utils->debug_log('-----------------------Yabo queryDemoGame params ----------------------------',$params);
		return $this->callApi(self::API_queryDemoGame, $params, $context);
    }

	public function queryForwardGame($playerName, $extra = null) {
		if(isset($extra['game_mode']) && $extra['game_mode'] != "real"){
        	return $this->queryDemoGame($extra);
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $language = $extra['language'];

        if($this->language != null) {
            $language = $this->language;
        }

        $language = $this->getLauncherLanguage($language);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'language' => $language,
        );

        $device_type = ( isset($extra['is_mobile']) && $extra['is_mobile']) ? self::DEVICE_TYPE_MOBILE_WEB_PAGE :  self::DEVICE_TYPE_WEB_PAGE;
        if(empty($this->lobby_url)){
			$this->lobby_url = $this->utils->getSystemUrl('player');
			$this->appendCurrentDbOnUrl($this->lobby_url);
		}

		$request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'loginPassword' => $this->getPasswordString($playerName),
            'deviceType' => $device_type,
            // 'oddType' => $this->odd_type,
            'lang' => $language,
            'backurl' => $this->lobby_url,
            'timestamp' => $this->getTimestamp()
        );

		$this->CI->utils->debug_log('-----------------------Yabo queryForwardGame request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);
        $this->CI->utils->debug_log('-----------------------Yabo queryForwardGame params ----------------------------',$params);
		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$language = $this->getVariableFromContext($params, 'language');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			if(isset($arrayResult['data']['url'])){
				$result['url'] = $arrayResult['data']['url'];
			} else {
				$success = false;
			}
		}
		return array($success, $result);
	}

	public function getLauncherLanguage($currentLang) {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 1;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 8;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 7;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-th":
                $language = 6;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case "ko-kr":
                $language = 5;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en-us":
                $language = 3;#english
                break;
            default:
                $language = 3;#english
                break;
        }
        return $language;
	}

	public function syncOriginalGameLogs($token = false) {
		// return $this->syncRewardRecordList($token);
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate->modify($this->getDatetimeAdjust());
		$endDate->modify('-1 minutes');

		$result = array();
		$this->CI->utils->loopDateTimeStartEnd($startDate,$endDate,'+30 minutes',function($startDate,$endDate) use (&$result){
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate   = $endDate->format('Y-m-d H:i:s');

			$page_index = self::DEFAULT_PAGE_INDEX;
			$page_total = self::DEFAULT_PAGE_TOTAL;

			while($page_index <= $page_total) {
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					'startDate' => $startDate,
					'endDate' => $endDate,
				);

				$request_params = array(
		            'startTime' => $startDate,
		            'endTime' => $endDate,
		            'pageIndex' => $page_index,
		            'timestamp' => $this->getTimestamp()
		        );

		        $this->CI->utils->debug_log('-----------------------Yabo syncOriginalGameLogs request params ----------------------------',$request_params);
		        $params = $this->generateParam($request_params);
		        #sync data
		        $params['pageIndex'] = $page_index;
		        $this->CI->utils->debug_log('-----------------------Yabo syncOriginalGameLogs params ----------------------------',$params);

		        $response =  $this->callApi(self::API_syncGameRecords, $params, $context);
				$next_page = isset($response['page_index']) ? $response['page_index'] + self::DEFAULT_PAGE_INDEX : $page_index + self::DEFAULT_PAGE_INDEX;
				$page_index = $next_page;
				$page_total = isset($response['total_page']) ? $response['total_page'] : $page_total;
				$result[] = $response;
				sleep($this->sync_sleep_time);
			}
			return true;
		});

		return array("success" => true,$result);


		// $request_params = array(
  //           'startTime' => $startDate,
  //           'endTime' => $endDate,
  //           'pageIndex' => 1,
  //           'timestamp' => $this->getTimestamp()
  //       );

  //       $this->CI->utils->debug_log('-----------------------Yabo syncOriginalGameLogs request params ----------------------------',$request_params);
  //       $params = $this->generateParam($request_params);
  //       #syn data
  //       $params['pageIndex'] = 1;
  //       $this->CI->utils->debug_log('-----------------------Yabo syncOriginalGameLogs params ----------------------------',$params);
		// return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function syncLostAndFound($token) {
        return $this->syncRewardRecordList($token);
    }


    public function syncRewardRecordList($token) {
        
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDate->modify($this->getDatetimeAdjust());
		$endDate->modify('-1 minutes');

		$result = array();
		$this->CI->utils->loopDateTimeStartEnd($startDate,$endDate,'+30 minutes',function($startDate,$endDate) use (&$result){
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate   = $endDate->format('Y-m-d H:i:s');

			$page_index = self::DEFAULT_PAGE_INDEX;
			$page_total = self::DEFAULT_PAGE_TOTAL;

			while($page_index <= $page_total) {
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncGameRecords',
					'startDate' => $startDate,
					'endDate' => $endDate,
				);

				$request_params = array(
		            'startTime' => $startDate,
		            'endTime' => $endDate,
		            'pageIndex' => $page_index,
		            'timestamp' => $this->getTimestamp()
		        );

		        $this->CI->utils->debug_log('-----------------------Yabo syncRewardRecordList request params ----------------------------',$request_params);
		        $params = $this->generateParam($request_params);
		        #sync data
		        $params['pageIndex'] = $page_index;
		        $this->CI->utils->debug_log('-----------------------Yabo syncRewardRecordList params ----------------------------',$params);

		        $response =  $this->callApi(self::API_syncTipRecords, $params, $context);
				$next_page = isset($response['page_index']) ? $response['page_index'] + self::DEFAULT_PAGE_INDEX : $page_index + self::DEFAULT_PAGE_INDEX;
				$page_index = $next_page;
				$page_total = isset($response['total_page']) ? $response['total_page'] : $page_total;
				$result[] = $response;
				sleep($this->sync_sleep_time);
			}
			return true;
		});

		return array("success" => true,$result);
    }

	public function processGameRecords(&$gameRecords, $responseResultId) {
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$data['uniqueId'] = isset($record['id']) ? $record['id'] : null;
				$data['playerId'] = isset($record['playerId']) ? $record['playerId'] : null;
				$data['playerName'] = isset($record['playerName']) ? $record['playerName'] : null;
				$data['agentId'] = isset($record['agentId']) ? $record['agentId'] : null;
				$data['betAmount'] = isset($record['betAmount']) ? $this->gameAmountToDB($record['betAmount']) : null;
				$data['validBetAmount'] = isset($record['validBetAmount']) ? $this->gameAmountToDB($record['validBetAmount']) : null;
				$data['netAmount'] = isset($record['netAmount']) ? $this->gameAmountToDB($record['netAmount']) : null;
				$data['beforeAmount'] = isset($record['beforeAmount']) ? $this->gameAmountToDB($record['beforeAmount']) : null;
				$data['createdAt'] = isset($record['createdAt']) ? $this->gameTimeToServerTime( date('Y-m-d H:i:s', $record['createdAt']/1000) ) : null;
				$data['netAt'] = isset($record['netAt']) ? $this->gameTimeToServerTime( date('Y-m-d H:i:s', $record['netAt']/1000) ) : null;
				$data['recalcuAt'] = isset($record['recalcuAt']) ? $this->gameTimeToServerTime( date('Y-m-d H:i:s', $record['recalcuAt']/1000) ) : null;
				$data['updatedAt'] = isset($record['updatedAt']) ? $this->gameTimeToServerTime( date('Y-m-d H:i:s', $record['updatedAt']/1000) ) : null;
				$data['gameTypeId'] = isset($record['gameTypeId']) ? $record['gameTypeId'] : null;
				$data['platformId'] = isset($record['platformId']) ? $record['platformId'] : null;
				$data['platformName'] = isset($record['platformName']) ? $record['platformName'] : null;
				$data['betStatus'] = isset($record['betStatus']) ? $record['betStatus'] : null;
				$data['betFlag'] = isset($record['betFlag']) ? $record['betFlag'] : null;
				$data['betPointId'] = isset($record['betPointId']) ? $record['betPointId'] : null;
				$data['judgeResult'] = isset($record['judgeResult']) ? $record['judgeResult'] : null;
				$data['currency'] = isset($record['currency']) ? $record['currency'] : null;
				$data['tableCode'] = isset($record['tableCode']) ? $record['tableCode'] : null;
				$data['roundNo'] = isset($record['roundNo']) ? $record['roundNo'] : null;
				$data['bootNo'] = isset($record['bootNo']) ? $record['bootNo'] : null;
				$data['loginIp'] = isset($record['loginIp']) ? $record['loginIp'] : null;
				$data['deviceType'] = isset($record['deviceType']) ? $record['deviceType'] : null;
				$data['deviceId'] = isset($record['deviceId']) ? $record['deviceId'] : null;
				$data['recordType'] = isset($record['recordType']) ? $record['recordType'] : null;
				$data['gameMode'] = isset($record['gameMode']) ? $record['gameMode'] : null;
				$data['signature'] = isset($record['signature']) ? $record['signature'] : null;
				$data['nickName'] = isset($record['nickName']) ? $record['nickName'] : null;
				$data['dealerName'] = isset($record['dealerName']) ? $record['dealerName'] : null;
				$data['tableName'] = isset($record['tableName']) ? $record['tableName'] : null;
				$data['addstr1'] = isset($record['addstr1']) ? $record['addstr1'] : null;
				$data['addstr2'] = isset($record['addstr2']) ? $record['addstr2'] : null;
				$data['agentCode'] = isset($record['agentCode']) ? $record['agentCode'] : null;
				$data['agentName'] = isset($record['agentName']) ? $record['agentName'] : null;
				$data['betPointName'] = isset($record['betPointName']) ? $record['betPointName'] : null;
				$data['gameTypeName'] = isset($record['gameTypeName']) ? $record['gameTypeName'] : null;
				$data['payAmount'] = isset($record['payAmount']) ? $record['payAmount'] : null;
				$data['adddec1'] = isset($record['adddec1']) ? $record['adddec1'] : null;
				$data['adddec2'] = isset($record['adddec2']) ? $record['adddec2'] : null;
				$data['adddec3'] = isset($record['adddec3']) ? $record['adddec3'] : null;
				$data['result'] = isset($record['result']) ? $record['result'] : null;
				$data['startid'] = isset($record['startid']) ? $record['startid'] : null;
				$data['rewardAmount'] = isset($record['rewardAmount']) ? $record['rewardAmount'] : null;
				$data['rewardType'] = isset($record['rewardType']) ? $record['rewardType'] : null;
				if(!empty($data['rewardType'])){
					$data['gameTypeId'] = $data['rewardType'];
					$data['netAt'] = $data['createdAt'];
					$data['updatedAt'] = $data['createdAt'];
				}
				//default data
				$data['login_name'] = $data['nickName'];
				$prefix = strtolower($this->merchant_code);
				$str = $data['playerName'];
				if (substr($str, 0, strlen($prefix)) == $prefix) {
				    $data['login_name'] = substr($str, strlen($prefix));
				} 
				$data['game_externalid'] = $data['gameTypeId'];
				$data['external_uniqueid'] = $data['uniqueId'];
				$data['response_result_id'] = $responseResultId;
				$gameRecords[$index] = $data;
				unset($data);
			}
		}
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		// echo "<pre>";
		// print_r($arrayResult);exit();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, null);
		$dataResult = array(
			'startDate' => $this->getVariableFromContext($params, 'startDate'),
			'endDate' => $this->getVariableFromContext($params, 'endDate'),
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
			'page_index'=> self::DEFAULT_PAGE_INDEX,
			'total_page'=> self::DEFAULT_PAGE_TOTAL
		);
		if($success){
			if(isset($arrayResult['data'])){
				$dataResult['page_size'] = $arrayResult['data']['pageSize'];
				$dataResult['page_index'] = $arrayResult['data']['pageIndex'];
				$dataResult['total_record'] = $arrayResult['data']['totalRecord'];
				$dataResult['total_page'] = $arrayResult['data']['totalPage'];
				if(isset($arrayResult['data']['record']) && !empty($arrayResult['data']['record'])){
					$gameRecords = $arrayResult['data']['record'];
					$this->processGameRecords($gameRecords, $responseResultId);
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

		            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

		            $dataResult['data_count'] = count($gameRecords);
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
		}
		return array($success, $dataResult);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
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

	    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
    	$sqlTime='yb.updatedAt >= ? and yb.updatedAt <= ?';
    	if($use_bet_time){
    		$sqlTime='yb.createdAt >= ? and yb.createdAt <= ?';
    	}
		$sql = <<<EOD
SELECT yb.id as sync_index,
yb.login_name as player_username,
yb.roundNo as round_number,
yb.betAmount as real_bet_amount,
yb.validBetAmount as bet_amount,
yb.netAmount as result_amount,

yb.game_externalid,
yb.external_uniqueid,
yb.updated_at,
yb.md5_sum,
yb.response_result_id,
yb.game_externalid as game,
yb.game_externalid as game_code,
yb.createdAt as bet_at,
yb.createdAt as start_at,
yb.netAt as end_at,
yb.addstr1 as bet_details,
yb.beforeAmount as before_balance,
yb.rewardAmount as tip_amount,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM yabo_gamelogs as yb
LEFT JOIN game_description as gd ON yb.game_externalid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON yb.login_name = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // echo "<pre>";
        // print_r($result);exit();
        return $result;
        
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	$extra_info=[];
    	$has_both_side=0;

    	if(empty($row['md5_sum'])){
        	//genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            	self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
        	//set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['status'] = Game_logs::STATUS_SETTLED;
        // $row['after_balance'] = $row['before_balance'] + $row['result_amount'];
        if(!empty($row['tip_amount'])){
        	$row['after_balance'] = null;
        	$row['result_amount'] =  -$row['tip_amount'];
        	$row['bet_amount'] =  0;
        	$row['real_bet_amount'] =  0;
        	$row['bet_details'] =  "TIP|GIFT|REWARD";
        } else {
        	$row['after_balance'] = $this->generateRoundAfterBlance($row['round_number'],$row['player_username']);
        }
    }

    function generateRoundAfterBlance($round, $gameUsername){
    	$sqlParam='roundNo = ? and login_name = ?';

        $sql = <<<EOD
select
#beforeAmount as before_amount,
#yb2.bet,
#yb2.payout,
#yb2.time,
#yb2.login_name,
#yb2.round,
beforeAmount + yb2.payout - yb2.bet as after_balance
from yabo_gamelogs as yb
left join 
(select 
sum(betAmount) as bet,
sum(payamount) as payout,
min(createdAt) as time,
login_name,
roundNo as round
from yabo_gamelogs
where 
{$sqlParam}
) 
yb2 on yb2.round = yb.roundNo and yb2.time = yb.createdAt and yb2.login_name = yb.login_name
where  yb2.round is not null
EOD;

        $params=[
            $round,
            $gameUsername
        ];

        $result =  $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        $after_balance = null;
        if(isset($result['after_balance'])){
        	$after_balance = $result['after_balance'];
        }
        return $after_balance;
    }

    /**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) {
		$this->CI->load->model(array('game_logs'));
		switch ($status) {
		case self::STATUS_RUNNING:
			$status = Game_logs::STATUS_ACCEPTED;
			break;
		case self::STATUS_VOID:
			$status = Game_logs::STATUS_VOID;
			break;
		case self::STATUS_ROLLBACK:
			$status = Game_logs::STATUS_REFUND;
			break;
		case self::STATUS_WON:
		case self::STATUS_CASHOUT:
		case self::STATUS_LOST:
		case self::STATUS_FIX:
			$status = Game_logs::STATUS_SETTLED;
			break;
		}
		return $status;
	}


    /**
	 * overview : get game description information
	 *
	 * @param $row
	 * @param $unknownGame
	 * @param $gameDescIdMap
	 * @return array
	 */
	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
	}



	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}


	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'newPassword' => $newPassword
		);

        $request_params = array(
            'loginName' => $this->merchant_code.$gameUsername,
            'newPassword' => $newPassword,
            'timestamp' => $this->getTimestamp()
        );
		$this->CI->utils->debug_log('-----------------------Yabo changePassword request params ----------------------------',$request_params);
        $params = $this->generateParam($request_params);

		$this->CI->utils->debug_log('-----------------------Yabo changePassword params ----------------------------',$params);
		return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForChangePassword($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

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


	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}



	
}
