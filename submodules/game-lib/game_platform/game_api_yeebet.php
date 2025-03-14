<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_yeebet extends Abstract_game_api {

    const ORIGINAL_LOGS_TABLE_NAME = 'yeebet_game_logs';

    const GAMELIST_TABLE = 'api_gamelist';

    const MD5_FIELDS_FOR_ORIGINAL = [
        "gameid",
        "createtime",
        "serialnumber",
        "betpoint",
        "betodds",
        "userid",
        "commamount",
        "gameroundid",
        "uid",
        "gameresult",
        "winlost",
        "state",
        "gameno",
        "bettype",
        "username",
        "betamount",
        "balance",
        "gamestate"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        "betodds",
        "commamount",
        "winlost",
        "betamount",
        "balance"
    ];

    const MD5_FIELDS_FOR_MERGE = [
        "game_code",
        "game_name",
        "bet_amount",
        "real_betting_amount",
        "round_number",
        "player_username",
        "status",
        "start_at",
        "bet_at",
        "end_at",
        "result_amount",
        "bet_result",
        "after_balance",
        "response_result_id",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        "result_amount",
        "bet_result",
        "after_balance",
    ];

    const MD5_FIELDS_FOR_GAMES = [
        'game_platform_id',
        'game_code',
        'json_fields'
    ];

    const POST = 'POST';
	const GET = 'GET';

    const PAGE_SIZE = 100;

    public function __construct(){
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->app_id = $this->getSystemInfo('app_id');
        $this->secret_key = $this->getSystemInfo('secret_key');
        $this->currency = $this->getSystemInfo('currency');
        $this->return_url = $this->getSystemInfo('return_url');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

        $this->URI_MAP = array(
            self::API_createPlayer => '/api/register',
            self::API_isPlayerExist => '/api/user/info',
            self::API_queryPlayerBalance => '/api/user/balance',
            self::API_depositToGame => '/api/user/dw',
            self::API_withdrawFromGame => '/api/user/dw',
            self::API_queryGameListFromGameProvider => '/api/data/getgames',
            self::API_queryForwardGame => '/api/login',
            self::API_syncGameRecords => '/api/record/bets/detail'
        );

        $this->METHOD_MAP = array(
            self::API_createPlayer => self::POST,
            self::API_isPlayerExist => self::GET,
            self::API_queryPlayerBalance => self::GET,
            self::API_depositToGame => self::POST,
            self::API_withdrawFromGame => self::POST,
            self::API_queryGameListFromGameProvider => self::GET,
            self::API_queryForwardGame => self::POST,
            self::API_syncGameRecords => self::GET
		);
    }

	public function getPlatformCode()
    {
		return YEEBET_API;
    }

    public function generateUrl($apiName, $params){

        $url = $this->api_url.$this->URI_MAP[$apiName];

        if($this->method == self::GET){
            $url = $url . '?' . http_build_query($params);
        }else {
            $url;
        }

        return $url;
    }

    protected function customHttpCall($ch, $params){

        if($this->method == self::POST){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
        $this->utils->debug_log('YEEBET Request Field: ',http_build_query($params));
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode){

        $success = false;

        if(!empty($resultArr) && ($statusCode == 201 || $statusCode == 200) && $resultArr['result'] == 0){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('YEEBET GAME API got error: ', $responseResultId,'result', $resultArr, $statusCode);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
		$this->CI->utils->debug_log('YEEBET (createPlayer)', $playerName);

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
            'appid' => $this->app_id,
            'nickname' => $gameUsername,
            'username' => $gameUsername,
            'sign' => md5("appid=".$this->app_id."&nickname=".$gameUsername."&username=".$gameUsername."&key=".$this->secret_key),
		);

        $this->method = self::POST;

		$this->CI->utils->debug_log('YEEBET (createPlayer) :', $params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
            'result' => $resultArr['result'],
            'desc' => $resultArr['desc'],
		);

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function isPlayerExist($playerName){
        $this->CI->utils->debug_log('YEEBET (isPlayerExist)', $playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $params = array(
            'appid' => $this->app_id,
            'username' => $gameUsername,
            'sign' => md5("appid=".$this->app_id."&username=".$gameUsername."&key=".$this->secret_key),
		);

        $this->method = self::GET;

        $this->CI->utils->debug_log('YEEBET (isPlayerExist) :', $params);
        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id'=>$responseResultId, 'exists'=>null];

        if($success){
            $result['exists'] = true;
        }else{
            $result['exists'] = false;
        }

        return [$success, $result];
    }

    public function queryPlayerBalance($playerName){
        $this->CI->utils->debug_log('YEEBET (queryPlayerBalance)', $playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $params = array(
            'appid' => $this->app_id,
            'username' => $gameUsername,
            'sign' => md5("appid=".$this->app_id."&username=".$gameUsername."&key=".$this->secret_key),
		);

        $this->method = self::GET;

        $this->CI->utils->debug_log('YEEBET (queryPlayerBalance) :', $params);
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

    public function processResultForQueryPlayerBalance($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [];

        if($success){
            if(isset($resultArr['balance'])){
                $result['balance'] = $this->gameAmountToDB(($resultArr['balance']));
            }
        }

        return [$success, $result];
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('YEEBET (depositToGame)', $playerName);

        $amount = $this->dBtoGameAmount($amount);
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
            'amount' => $amount,
            'appid' => $this->app_id,
            'tradeno' => $transfer_secure_id,
            'username' => $gameUsername,
            'sign' => md5("amount=".$amount."&appid=".$this->app_id."&tradeno=".$transfer_secure_id."&username=".$gameUsername."&key=".$this->secret_key),
		);

        $this->method = self::POST;

        $this->CI->utils->debug_log('YEEBET (depositToGame) :', $params);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

    public function processResultForDepositToGame($params){
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
        $this->CI->utils->debug_log('YEEBET (withdrawFromGame)', $playerName);

        $amount = $this->dBtoGameAmount($amount);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $transfer_secure_id,
        );

        $wdamount = -1*($amount);

        $params = array(
            'amount' => $wdamount,
            'appid' => $this->app_id,
            'tradeno' => $transfer_secure_id,
            'username' => $gameUsername,
            'sign' => md5("amount=".$wdamount."&appid=".$this->app_id."&tradeno=".$transfer_secure_id."&username=".$gameUsername."&key=".$this->secret_key),
		);

        $this->method = self::POST;

        $this->CI->utils->debug_log('YEEBET (withdrawFromGame) :', $params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

    public function processResultForWithdrawFromGame($params){
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

    public function queryForwardGame($playerName, $extra){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $iscreate = 0;

        $is_mobile = $extra['is_mobile'];
        if($is_mobile){
            $clienttype = 2;
        }else{
            $clienttype = 1;
        }

        if(isset($extra['home_link']) && !empty($extra['home_link'])){
            $return_url = $extra['home_link'];
        }else if(isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])){
            $return_url = $extra['extra']['t1_lobby_url'];
        }else if(!empty($this->return_url)){
            $return_url = $this->return_url;
        }else{
            $return_url = $this->getHomeLink();
        }

        if(isset($extra['language']) && !empty($extra['language'])){
            $language = $this->getLauncherLanguage($extra['language']);
        }else{
            $language = $this->getLauncherLanguage($this->language);
        }

        $params = array(
            'appid' => $this->app_id,
            'clienttype' =>  $clienttype,
            'currency' => $this->currency,
            'iscreate' =>  $iscreate,
            'language' => $language,
            'returnurl' =>  $return_url,
            'username' =>  $gameUsername,
            'sign' =>  md5("appid=".$this->app_id."&clienttype=".$clienttype."&currency=".$this->currency."&iscreate=".$iscreate."&language=".$language."&returnurl=".$return_url."&username=".$gameUsername."&key=".$this->secret_key),
        );

        $this->method = self::POST;

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array();

        if($success){
            if(isset($resultArr['openurl']))
            {
                $result['url'] = $resultArr['openurl'];
            }
        }

        return array($success, $result);
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language)
        {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en":
            case "en-us":
            case "EN":
            case "EN-US":
                $lang = 2;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
            case "cn":
            case "zh-cn":
            case "ZH":
            case "CN":
            case "ZH-CN":
                $lang = 1;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
            case "th-th":
            case "TH":
            case "TH-TH":
                $lang = 5;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case "kr":
            case "ko":
            case "ko-kr":
            case "KR":
            case "KO":
            case "KO-KR":
                $lang = 3;
                break;
            default:
                $lang = 2;
                break;
        }

        return $lang;
    }

    public function syncOriginalGameLogs($token){
        $this->CI->utils->debug_log('YEEBET (syncOriginalGameLogs)', $token);

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = strtotime($startDateTime->format('Y-m-d H:i:s'));
        $endDate   = strtotime($endDateTime->format('Y-m-d H:i:s'));

        $this->CI->utils->debug_log('YEEBET (timestamp)', $startDate, $endDate);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

        //always start from 0
        $currentPage = 0;
        $retry = 0;
        $done = false;
        $success=false;
        $apiError = false;

        while (!$done) {

            $params = [
                'appid' => $this->app_id,
                'begintime' => $startDate,
                'endtime' => $endDate,
                'index' => $currentPage,
                'size' => self::PAGE_SIZE,
                'sign' => md5("appid=".$this->app_id."&begintime=".$startDate."&endtime=".$endDate."&index=".$currentPage."&size=".self::PAGE_SIZE."&key=".$this->secret_key),
           ];

           $this->method = self::GET;

           $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

           sleep($this->common_wait_seconds);
           $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

           if ($api_result && $api_result['success']) {
                $totalPage = isset($api_result['totalPage']) ? $api_result['totalPage'] : 0;
                $totalCount = isset($api_result['totalCount']) ? $api_result['totalCount'] : 0 ;
                //next page
                $currentPage += 1;
                $done = $currentPage > $totalPage;
            }else{
                $apiError = true;
                continue;
            }

            $this->CI->utils->debug_log(__METHOD__.' currentPage: ',$currentPage,'totalCount',$totalCount,'totalPage', $totalPage, 'done', $done, 'result', $api_result,'params_executing',$params);

            $maxRetry = 3;
            if($apiError){
                $retry++;
                if($retry >= $maxRetry){
                    $done = true;
                    $success = false;
                }
            }else{
                $success = true;
            }

        }

        return array('success' => $success);
    }

    public function processResultForSyncOriginalGameLogs($params){
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = ['data_count' => 0];
        $gameRecords = isset($resultArr['array']) ? $resultArr['array'] : null;

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

            $result['totalCount'] = $resultArr['arraysize'];

        }

        return array($success, $result);
    }

    private function rebuildGameRecords(&$gameRecords, $extra){
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $gr){
                $data['gameid'] = isset($gr['gameid']) ? $gr['gameid'] : null;
                $data['createtime'] = isset($gr['createtime']) ? $this->gameTimeToServerTime($gr['createtime']) : null;
                $data['serialnumber'] = isset($gr['id']) ? $gr['id'] : null;
                $data['betpoint'] = isset($gr['betpoint']) ? $gr['betpoint'] : null;
                $data['betodds'] = isset($gr['betodds']) ? $gr['betodds'] : null;
                $data['userid'] = isset($gr['userid']) ? $gr['userid'] : null;
                $data['commamount'] = isset($gr['commamount']) ? $gr['commamount'] : null;
                $data['gameroundid'] = isset($gr['gameroundid']) ? $gr['gameroundid'] : null;
                $data['uid'] = isset($gr['uid']) ? $gr['uid'] : null;
                $data['gameresult'] = isset($gr['gameresult']) ? $gr['gameresult'] : null;
                $data['winlost'] = isset($gr['winlost']) ? $gr['winlost'] : null;
                $data['state'] = isset($gr['state']) ? $gr['state'] : null;
                $data['gameno'] = isset($gr['gameno']) ? $gr['gameno'] : null;
                $data['bettype'] = isset($gr['bettype']) ? $gr['bettype'] : null;
                $data['username'] = isset($gr['username']) ? $gr['username'] : null;
                $data['betamount'] = isset($gr['betamount']) ? $gr['betamount'] : null;
                $data['balance'] = isset($gr['balance']) ? $gr['balance'] : null;
                $data['gamestate'] = isset($gr['gamestate']) ? $gr['gamestate'] : null;
                //extra info from SBE
                $data['external_uniqueid'] = isset($gr['id'])? $gr['id'] : null;
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
            $sqlTime = '`original`.`createtime` >= ? AND `original`.`createtime` <= ?';
        }

        $this->CI->utils->debug_log('YEEBET sqlTime ===>', $sqlTime);

        $sql = <<<EOD
        SELECT
            original.id as sync_index,
            original.gameid as game_code,
            original.createtime as start_at,
            original.createtime as bet_at,
            original.createtime as end_at,
            original.serialnumber,
            original.betpoint,
            original.betodds,
            original.userid,
            original.commamount,
            original.gameroundid,
            original.uid,
            original.gameresult,
            original.winlost as result_amount,
            original.state as status,
            original.gameno,
            original.bettype,
            original.username as player_username,
            original.betamount as bet_amount,
            original.balance as after_balance,
            original.gamestate,
            original.response_result_id,
            original.external_uniqueid,
            original.updated_at,
            original.md5_sum,
            game_provider_auth.player_id,
	        gd.id as game_description_id,
	        gd.english_name as game_description_name,
	        gd.game_type_id
        FROM yeebet_game_logs as original
            LEFT JOIN game_description as gd ON original.gameid = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.username = game_provider_auth.login_name
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

        $extra = [ 'odds' => $row['betodds'] ];

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
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => isset($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0,
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
                'round_number'          => isset($row['gameno']) ? $row['gameno'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => [
                'betpoint'              => isset($row['betpoint']) ? $row['betpoint'] : null,
                'gameresult'            => isset($row['gameresult']) ? $row['gameresult'] : null,
            ],
            'extra'                     => $extra,
            //from exists game logs
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
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
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id);

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
        case "0":
            $status = Game_logs::STATUS_ACCEPTED;
            break;
        case "-2":
            $status = Game_logs::STATUS_CANCELLED;
            break;
        case "-1":
            $status = Game_logs::STATUS_VOID;
            break;
        case "1":
            $status = Game_logs::STATUS_SETTLED;
            break;
        default:
            $status = Game_logs::STATUS_PENDING;
            break;
        }

        return $status;
    }

    public function queryGameListFromGameProvider($extra = NULL) {

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider'
        );

        $params = array(
            'appid' => $this->app_id,
            'sign' => md5("appid=".$this->app_id."&key=".$this->secret_key)
		);

        $this->method = self::GET;

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

        if($success) {
            $result['games'] = $resultArr['array'];
        }
        return array($success, $result);
    }

    public function rebuildGameList($games) {
        $data = [];
        foreach ($games as $game) {
            $newGame = [];
            $external_uniqueid = isset($game['id']) ? $game['id'] . '-' . $this->getPlatformCode() : '';

            $newGame['game_platform_id']  = $this->getPlatformCode();
            $newGame['game_code'] 		  = isset($game['id']) ? $game['id'] : '';
            $newGame['json_fields'] 	  = !empty($game) ? json_encode($game) : '';
            $newGame['external_uniqueid'] = isset($external_uniqueid) ? $external_uniqueid : '';
	        $data[] = $newGame;
        }
        return $data;
    }

    public function updateGameList($games) {

        $this->CI->load->model(array('original_game_logs_model'));
        $games = $this->rebuildGameList($games);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            self::GAMELIST_TABLE,
            $games,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_GAMES,
            'md5_sum',
            'id',
            []
        );

        $dataResult = [
            'data_count' => count($games),
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] += $this->updateOrInsertGameList($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] += $this->updateOrInsertGameList($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    private function updateOrInsertGameList($data, $queryType){
        $dataCount = 0;
        if (!empty($data)) {
            $caption = [];
            if ($queryType == 'update') {
                $caption = "## UPDATE YEEBET GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW YEEBET GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Type |\n";
            $body .= "| :--- | :--- | :--- |\n";
            $gametype = "Live Dealer";

            foreach ($data as $record) {
            	$game = $record;
            	$record = json_decode($record['json_fields'], true);
                $gamecode = $record['id'];
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::GAMELIST_TABLE, $game);
                    $body .= "| {$record['title']} | {$record['title']} | {$gamecode} | {$gametype} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::GAMELIST_TABLE, $game);
                    $body .= "| {$record['title']} | {$record['title']} | {$gamecode} | {$gametype} |\n";
                }
                $dataCount++;
                unset($record);
            }

            $this->sendMatterMostMessage($caption, $body);
        }
        return $dataCount;
    }

    public function sendMatterMostMessage($caption, $body){
        $message = [
            $caption,
            $body,
            "#YEEBET"
        ];

        $channel = $this->utils->getConfig('gamelist_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $user = 'YEEBET Game List';

        sendNotificationToMattermost($user, $channel, [], $message);
    }
}
/*end of file*/