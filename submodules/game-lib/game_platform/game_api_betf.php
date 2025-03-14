<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_betf extends Abstract_game_api {

    const ORIGINAL_LOGS_TABLE_NAME = 'betf_game_logs';

    const MD5_FIELDS_FOR_ORIGINAL = [
        "betTime",
        "betAmount",
        "betType",
        "ipAddress",
        "merchantUserId",
        "orderNo",
        "payoutTime",
        "rebateAmount",
        "currency",
        "rebateTime",
        "sportCategoryName",
        "status",
        "userName",
        "subOrderList"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        "betAmount",
        "rebateAmount"
    ];

    const MD5_FIELDS_FOR_MERGE = [
        "game_code",
        "game_name",
        "bet_amount",
        "real_betting_amount",
        "settlement_datetime",
        "round_number",
        "player_username",
        "status",
        "start_at",
        "bet_at",
        "end_at",
        "result_amount",
        "bet_result",
        "response_result_id",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        "result_amount",
        "bet_result",
    ];

    const STATUS_CODE = [
        "OK" => "0",
        "FAIL" => "-254100",
    ];

    // timeZones
    const ASIA_HK = 1; // GMT+8
    const IST = 0; // GMT+5:30

    const POST = 'POST';
	const GET = 'GET';

    const PAGE_SIZE = 100;
    const EURO = 1;
    const HONGKONG = 2;
    const MALAY = 3;
    const INDO = 4;

    const API_getCode = "getCode";
    const API_syncByBetTime = "syncByBetTime";
    const API_syncByRebateTime = "syncByRebateTime";

    public function __construct(){
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->currency = $this->getSystemInfo('currency','CNY'); //default to CNY/Chinese Yuan
        $this->language = $this->getSystemInfo('language','ZH'); //default to ZH/Simplified Chinese
        $this->cents_conversion_rate = $this->getSystemInfo('cents_conversion_rate', 100);
        $this->merchant_id = $this->getSystemInfo('merchant_id');
        $this->merchant_key = $this->getSystemInfo('merchant_key');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->web_url = $this->getSystemInfo('web_url');
        $this->mobile_url = $this->getSystemInfo('mobile_url');
        $this->maxRetry = $this->getSystemInfo('maxRetry',3);

        $this->URI_MAP = array(
            self::API_createPlayer => '/merchant/user/registerOrLogin',
            self::API_isPlayerExist => '/merchant/user/balance',
            self::API_getCode => '/merchant/user/registerOrLogin',
            self::API_queryPlayerBalance => '/merchant/user/balance',
            self::API_depositToGame => '/merchant/user/moneyIn',
            self::API_withdrawFromGame => '/merchant/user/moneyOut',
            self::API_queryForwardGame => '/merchant/user/registerOrLogin',
            self::API_syncByBetTime => '/merchant/user/orderList',
            self::API_syncByRebateTime => '/merchant/user/orderList'
        );

        $this->METHOD_MAP = array(
            self::API_createPlayer => self::POST,
            self::API_isPlayerExist => self::GET,
            self::API_getCode => self::POST,
            self::API_queryPlayerBalance => self::GET,
            self::API_depositToGame => self::POST,
            self::API_withdrawFromGame => self::POST,
            self::API_queryForwardGame => self::POST,
            self::API_syncByBetTime => self::GET,
            self::API_syncByRebateTime => self::GET
		);
    }

	public function getPlatformCode()
    {
		return BETF_API;
    }

    public function generateUrl($apiName, $params){
    	$url = $this->api_url.$this->URI_MAP[$apiName];

        if($this->method == self::GET&&!empty($params)){
            $sign = $params;
            $sign['merchantKey'] = $this->merchant_key;
            ksort($sign);
            $params['sign'] = strtoupper(md5(http_build_query($sign)));
            $this->CI->utils->debug_log('---------- BETF generateSign ----------', $params['sign']);
            $url = $url . '?' . http_build_query($params);
        } else {
            $url;
        }

        return $url;
    }

    protected function customHttpCall($ch, $params){

        switch ($this->method){
            case self::POST:
                $sign = $params;
                $sign['merchantKey'] = $this->merchant_key;
                ksort($sign);
                $params['sign'] = strtoupper(md5(http_build_query($sign)));
                $this->CI->utils->debug_log('---------- BETF generateSign ----------', $params['sign']);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                break;
        }

        $this->utils->debug_log('BETF Request Field: ',json_encode($params));
    }

    protected function getHttpHeaders($params){

    	$headers = [];
    	if($this->URI_MAP != self::API_queryPlayerBalance && self::API_isPlayerExist && self::API_syncByBetTime && self::API_syncByRebateTime) {
    		$headers = [
    		'Content-Type' => 'application/json'
    		];
    	}

    	return $headers;
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode){

        $success = false;

        if(!empty($resultArr) && ($statusCode == 201 || $statusCode == 200) && $resultArr['code'] == 0){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('BETF GAME API got error: ', $responseResultId,'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
		$this->CI->utils->debug_log('BETF (createPlayer)', $playerName);

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $extra = [
            'prefix' => $this->prefix_for_username,
        ];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'currency' => $this->currency,
            'language' => $this->language,
            'merchantId' => $this->merchant_id,
            'merchantUserId' => $gameUsername,
			'userName' => $gameUsername,
		);

        $this->method = self::POST;

		$this->CI->utils->debug_log('BETF (createPlayer) :', $params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params){
		$this->CI->utils->debug_log('BETF (processResultForCreatePlayer)', $params);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
            'code' => $resultArr['code'],
            'data' => $resultArr['data'],
            'message' => $resultArr['message'],
		);

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function isPlayerExist($playerName){
        $this->CI->utils->debug_log('BETF (isPlayerExist)', $playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $params = array(
            'merchantId' => $this->merchant_id,
            'merchantUserId' => $gameUsername,
		);

        $this->method = self::GET;

        $this->CI->utils->debug_log('BETF (isPlayerExist) :', $params);
        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $this->CI->utils->debug_log('BETF (processResultForIsPlayerExist)', $params);

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id'=>$responseResultId, 'exists'=>null];

        if($success){
            if(isset($resultArr['data']) != null){
                $result['exists'] = true;
            }else{
                $result['exists'] = false;
            }
        }

        return [$success, $result];
    }

    public function queryPlayerBalance($playerName){
        $this->CI->utils->debug_log('BETF (queryPlayerBalance)', $playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $params = array(
            'merchantId' => $this->merchant_id,
            'merchantUserId' => $gameUsername,
		);

        $this->method = self::GET;

        $this->CI->utils->debug_log('BETF (queryPlayerBalance) :', $params);
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

    public function processResultForQueryPlayerBalance($params){
        $this->CI->utils->debug_log('BETF (processResultForQueryPlayerBalance)', $params);

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id'=>$responseResultId, 'exists'=>null];

        if($success){
            if(isset($resultArr['data']['balance'])){
                $result['balance'] = $resultArr['data']['balance'];
                $result['exists'] = true;
            }else{
                //wrong result, call failed
                $success=false;
                $result['exists'] = false;
            }
        }

        return [$success, $result];
    }

    public function convertToCents($amount){
        $conversion_rate = floatval($this->cents_conversion_rate);
        $value = floatval($amount * $conversion_rate);
        $precision = intval($this->getSystemInfo('conversion_precision', 2));
        return round($value,$precision);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('BETF (depositToGame)', $playerName);

        $amount = $this->dBtoGameAmount($this->convertToCents($amount));
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
            'merchantId' => $this->merchant_id,
            'merchantUserId' => $gameUsername,
			'money' => $amount,
            'orderNo' => $transfer_secure_id,
		);

        $this->method = self::POST;

        $this->CI->utils->debug_log('BETF (depositToGame) :', $params);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

    public function processResultForDepositToGame($params){
		$this->CI->utils->debug_log('BETF (processResultForDepositToGame)', $params);

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null){
        $this->CI->utils->debug_log('BETF (withdrawFromGame)', $playerName);

        $amount = $this->dBtoGameAmount($this->convertToCents($amount));
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id,
        );

		$params = array(
            'merchantId' => $this->merchant_id,
            'merchantUserId' => $gameUsername,
			'money' => $amount,
            'orderNo' => $transfer_secure_id,
		);

        $this->method = self::POST;

        $this->CI->utils->debug_log('BETF (withdrawFromGame) :', $params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

    public function processResultForWithdrawFromGame($params){
		$this->CI->utils->debug_log('BETF (processResultForWithdrawFromGame)', $params);

        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function queryTransaction($transactionId, $extra){
        return $this->returnUnimplemented();
    }

    //getCode for gameLaunch
    public function getCode($playerName, $extra){
        $this->CI->utils->debug_log('BETF (getCode)', $playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'currency' => $this->currency,
            'language' => $this->language,
            'merchantId' => $this->merchant_id,
            'merchantUserId' => $gameUsername,
			'userName' => $gameUsername,
		);

        $this->method = self::POST;

		$this->CI->utils->debug_log('BETF (getCode) :', $params);
		return $this->callApi(self::API_getCode, $params, $context);
    }

    public function queryForwardGame($playerName, $extra){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $getCode = $this->getCode($playerName, $extra);
        $this->utils->debug_log("BETF: (QueryForwardGame) code: ", $getCode);
        $code = isset($getCode['data']['code']) ? $getCode['data']['code'] : NULL;

        if(!empty($code))
        {

            $is_mobile = $extra['is_mobile'];

            if($is_mobile)
            {
                $gameUrl = $this->mobile_url.'?code='.$code;
            }
            else
            {
                $gameUrl = $this->web_url.'?code='.$code;
            }

            $success = true;

            $this->utils->debug_log("BETF: (QueryForwardGame) gameUrl: ", $gameUrl);

            return ['success'=> $success, 'redirect' => true, 'url' => $gameUrl];
            // redirect($gameUrl);
        }
        else
        {
            $success = false;
            return ['success' => $success, 'redirect' => false];
        }
    }

    public function getLauncherLanguage($currentLang){
        switch ($currentLang)
        {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en":
            case "en-us":
            case "EN":
            case "EN-US":
                $language = 'EN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
            case "cn":
            case "zh-cn":
            case "ZH":
            case "CN":
            case "ZH-CN":
                $language = 'ZH';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
            case "th-th":
            case "TH":
            case "TH-TH":
                $language = 'TH';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi":
            case "vi-vn":
            case "VI":
            case "VI-VN":
                $language = 'VI';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id":
            case "id-id":
            case "ID":
            case "ID-ID":
                $language = 'ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case "kr":
            case "ko":
            case "ko-kr":
            case "KR":
            case "KO":
            case "KO-KR":
                $language = 'KO';
                break;
            default:
                $language = 'EN';
                break;
        }

        return $language;
    }

    public function syncByBetTime($token){
        $this->CI->utils->debug_log('BETF (syncByBetTime)', $token);

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = strtotime($startDateTime->format('Y-m-d H:i:s')) * 1000;
        $endDate   = strtotime($endDateTime->format('Y-m-d H:i:s')) * 1000;

        $this->CI->utils->debug_log('BETF (milliseconds)', $startDate, $endDate);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

        //always start from 1
        $currentPage = 1;
        $retry = 0;
        $done = false;
        $success=false;
        $apiError = false;
        $timeQueryType = "BET_TIMESTAMP";
        $language = "EN";

        while (!$done) {

            $params = [
                # for modified date, data
                'endTimestamp' => $endDate,
                'language' => $language,
                'limit' => self::PAGE_SIZE,
                'merchantId' => $this->merchant_id,
                'page' => $currentPage,
                'startTimestamp' => $startDate,
                'timeQueryType' => $timeQueryType
           ];

           $this->method = self::GET;

           $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

           sleep($this->common_wait_seconds);
           $api_result = $this->callApi(self::API_syncByBetTime, $params, $context);

           if ($api_result && $api_result['success']) {
                $totalPage = isset($api_result['totalPage']) ? $api_result['totalPage'] : 0;
                $totalCount = isset($api_result['totalCount']) ? $api_result['totalCount'] : 0 ;
                //next page
                $currentPage += 1;
                $done = $currentPage > $totalPage;
            }else{
                $apiError = true;
                // continue;
            }

            $this->CI->utils->debug_log(__METHOD__.' currentPage: ',$currentPage,'totalCount',$totalCount,'totalPage', $totalPage, 'done', $done, 'result', $api_result,'params_executing',$params);

            if($apiError){
                $retry++;
                if($retry >= $this->maxRetry){
                    $done = true;
                    $success = false;
                }
            }else{
                $success = true;
            }

        }

        return array('success' => $success);

    }

    public function syncByRebateTime($token ){
        $this->CI->utils->debug_log('BETF (syncByRebateTime)', $token);

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = strtotime($startDateTime->format('Y-m-d H:i:s')) * 1000;
        $endDate   = strtotime($endDateTime->format('Y-m-d H:i:s')) * 1000;

        $this->CI->utils->debug_log('BETF (milliseconds)', $startDate, $endDate);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

        //always start from 1
        $currentPage = 1;
        $retry = 0;
        $done = false;
        $success=false;
        $apiError = false;
        $timeQueryType = "REBATE_TIMESTAMP";
        $language = "EN";

        while (!$done) {

            $params = [
                # for modified date, data
                'endTimestamp' => $endDate,
                'language' => $language,
                'limit' => self::PAGE_SIZE,
                'merchantId' => $this->merchant_id,
                'page' => $currentPage,
                'startTimestamp' => $startDate,
                'timeQueryType' => $timeQueryType
           ];

           $this->method = self::GET;

           $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

           sleep($this->common_wait_seconds);
           $api_result = $this->callApi(self::API_syncByRebateTime, $params, $context);

           if ($api_result && $api_result['success']) {
                $totalPage = isset($api_result['totalPage']) ? $api_result['totalPage'] : 0;
                $totalCount = isset($api_result['totalCount']) ? $api_result['totalCount'] : 0 ;
                //next page
                $currentPage += 1;
                $done = $currentPage > $totalPage;
            }else{
                $apiError = true;
                // continue;
            }

            $this->CI->utils->debug_log(__METHOD__.' currentPage: ',$currentPage,'totalCount',$totalCount,'totalPage', $totalPage, 'done', $done, 'result', $api_result,'params_executing',$params);

            if($apiError){
                $retry++;
                if($retry >= $this->maxRetry){
                    $done = true;
                    $success = false;
                }
            }else{
                $success = true;
            }

        }

        return array('success' => $success);

    }

    public function syncOriginalGameLogs($token = false){
        $this->syncByBetTime($token);
        $this->syncByRebateTime($token);

        return array('success' => true);
    }

    public function processResultForSyncOriginalGameLogs($params){
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = ['data_count' => 0];
        $gameRecords = isset($resultArr['data']['data']) ? $resultArr['data']['data'] : null;

        if($success && !empty($gameRecords)){
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords, $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId], self::ORIGINAL_LOGS_TABLE_NAME);
            }
            unset($insertRows);

            if (!empty($updateRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], self::ORIGINAL_LOGS_TABLE_NAME);
            }
            unset($updateRows);

            // parse_str($resultArr['next'], $nextPage);
            // $nextPage = isset($nextPage['page']) ? $nextPage['page'] : 0;
            $result['totalPage'] = $resultArr['data']['totalPage'];
            $result['totalCount'] = $resultArr['data']['totalCount'];

        }

        return array($success, $result);

    }

    private function rebuildGameRecords(&$gameRecords, $extra){

        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record){
                $data['betTime'] = isset($record['betTime']) ? $this->gameTimeToServerTime($record['betTime']) : null;
                $data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
                $data['betType'] = isset($record['betType']) ? $record['betType'] : null;
                $data['ipAddress'] = isset($record['ipAddress']) ? $record['ipAddress'] : null;
                $data['merchantUserId'] = isset($record['merchantUserId']) ? $record['merchantUserId'] : null;
                $data['orderNo'] = isset($record['orderNo']) ? $record['orderNo'] : null;
                $data['payoutTime'] = isset($record['payoutTime']) ? $this->gameTimeToServerTime($record['payoutTime']) : null;
                $data['rebateAmount'] = isset($record['rebateAmount']) ? $record['rebateAmount'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['rebateTime'] = isset($record['rebateTime']) ? $this->gameTimeToServerTime($record['rebateTime']) : null;
                $data['sportCategoryName'] = isset($record['sportCategoryName']) ? $record['sportCategoryName'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['userName'] = isset($record['userName']) ? $record['userName'] : null;
                $data['subOrderList'] = isset($record['subOrderList']) ? json_encode($record['subOrderList']) : null;
                //extra info from SBE
                $data['external_uniqueid'] = isset($record['orderNo'])? $record['orderNo'] : null;
                $data['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
                $data['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
                $data['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){

        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
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

    public function syncMergeToGameLogs($token){

    	$enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){

        $sqlTime = '`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if($use_bet_time)
        {
            $sqlTime = '`original`.`betTime` >= ? AND `original`.`betTime` <= ?';
        }

        $this->CI->utils->debug_log('BETF sqlTime ===>', $sqlTime);

        $sql = <<<EOD
        SELECT
            original.id as sync_index,
            original.betTime as start_at,
            original.betTime as bet_at,
            original.betTime as end_at,
            original.betAmount as bet_amount,
            original.betType,
            original.ipAddress,
            original.merchantUserId as player_username,
            original.orderNo,
            original.payoutTime,
            original.rebateAmount as result_amount,
            original.currency,
            original.rebateTime,
            original.sportCategoryName as game_name,
            original.sportCategoryName as game_code,
            original.status,
            original.userName,
            original.subOrderList as bet_details,
            original.response_result_id,
            original.external_uniqueid,
            original.updated_at,
            original.md5_sum,
            game_provider_auth.player_id,
	        gd.id as game_description_id,
	        gd.game_type_id
        FROM betf_game_logs as original
            LEFT JOIN game_description as gd ON original.sportCategoryName = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.merchantUserId = game_provider_auth.login_name
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

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        if(!empty($row['rebateTime'])){
            $row['end_at'] = $row['rebateTime'];
        }

        $resultAmount = $row['result_amount'] - $row['bet_amount'];

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game'                  => isset($row['game_name']) ? $row['game_name'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : $this->gameAmountToDB($row['current_bet']),
                'result_amount'         => ($resultAmount != 0) ? $this->gameAmountToDB($resultAmount) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => null,
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
                'round_number'          => isset($row['orderNo']) ? $row['orderNo'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => $row['sync_index'],
                'bet_type'              => (isset($row['betType']) && $row['betType'] > 1) ? 'Multi Bet' : 'Single Bet',
            ],
            'bet_details' => json_decode($row['bet_details'], true),
            'extra' => null,
            //from exists game logs
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
        $this->CI->utils->debug_log('BETF decode bet details ===>', $row['bet_details']);
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
        $status = $this->getGameRecordStatus($row['status']);
        $row['status'] = $status;
    }

    public function getGameDescriptionInfo($row, $unknownGame){

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    private function getGameRecordStatus($status) {

        $this->CI->load->model(array('game_logs'));
        switch ($status) {
        case "1":
        case "3":
            $status = Game_logs::STATUS_PENDING;
            break;
        case "2":
            $status = Game_logs::STATUS_ACCEPTED;
            break;
        case "4":
            $status = Game_logs::STATUS_CANCELLED;
            break;
        case "5":
            $status = Game_logs::STATUS_VOID;
            break;
        case "11":
        case "12":
        case "13":
        case "14":
        case "15":
            $status = Game_logs::STATUS_SETTLED;
            break;
        default:
            $status = Game_logs::STATUS_PENDING;
            break;
        }

        return $status;

    }

    public function processOddType($odd_type) {
        if(isset($odd_type) && !empty($odd_type)) {
            switch ($odd_type) {
                case 'euro':
                    $odd_type = self::EURO;
                    break;
                case 'hongkong':
                    $odd_type = self::HONGKONG;
                    break;
                case 'malay':
                    $odd_type = self::MALAY;
                    break;
                case 'indo':
                    $odd_type = self::INDO;
                    break;
            }

            return $odd_type;
        }
    }

    public function getOddsType($id = null) {

        $array = array(
            "1" => 'euro',
            "2" => 'hongkong',
            "3" => 'malay',
            "4" => 'indo',
        );

        return $array[$id];
    }
}
/*end of file*/