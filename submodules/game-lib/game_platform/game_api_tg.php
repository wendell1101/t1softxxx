<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_tg extends Abstract_game_api {

    const POST = 'POST';
	const GET = 'GET';

    const START_PAGE = 1;
    const DEFAULT_PAGE_SIZE = 50;
    const BET_RESULT_ONGOING = 0;
	const BET_RESULT_WIN = 1;
	const BET_RESULT_LOSE = 2; 
	const BET_RESULT_TIE = 3; 
    
    const GAME_LOGS_STATUS = [
        'ON_GOING' => 0,
        'WIN' => 1,
        'LOSE' => 2,
        'TIE' => 3
    ];

    const MD5_FIELDS_FOR_ORIGINAL =[
        'c_id',
        'c_idx',
        'c_casino',
        'c_table_idx',
        'c_shoe_idx',
        'c_game_idx',
        'c_bet_type',
        'c_bet_result',
        'c_result_money',
        'c_bet_money',
        'c_after_money',
        'c_reg_date',
        'c_reg_date',
        'c_reg_date',
        'pc1',
        'pc2',
        'pc3',
        'bc1',
        'bc2',
        'bc3',
        'c_game_result',
        'pp',
        'bp'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS =[
        'c_bet_money',
        'c_after_money'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_type_id',
        'game_description_id',
        'game_code',
        'game_description_name',
        'player_id',
        'bet_amount',
        'result_amount',
        'after_balance',
        'start_at',
        'bet_at',
        'end_at',
        'updated_at',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'result_amount',
        'real_betting_amount',
        'bet_amount'
    ];

    const RESULT_MESSAGE = [
        "REQUEST_SUCCESS"           =>  0,
        "NO_ACCOUNT"                =>  1,
        "SUSPENDED_ACCOUNT"         =>  2,
        "NO_LOGIN_INFORMATION"      =>  3,
        "MISSING_REQUIRED_VALUES"   =>  4,
        "USER_AMOUNT_INSUFFICIENT"  =>  5,
        "AGENT_AMOUNT_INSUFFICIENT" =>  6,
        "SAME_ID"                   =>  7,
        "AGENT_ACCOUNT_ERROR"       =>  8,
        "UNREGISTERED_IP"           =>  10,
        "HASHING_ERROR"             =>  11,
        "DB_PROCESSING_ERROR"       =>  91,
        "SYSTEM_ERROR"              =>  92,
        "WRONG_REQUEST"             =>  99,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->originalTable = "tg_game_logs";
        $this->api_url = $this->getSystemInfo('url');
        $this->api_key     = $this->getSystemInfo('api_key', 'ee64bbd519093351ad9a73a525972992ffe855054c0920dacd84a34746f284f8');
        $this->is_redirect = $this->getSystemInfo('is_redirect',true);
        $this->default_game_code = $this->getSystemInfo("", "SPEED");
        $this->language = $this->getSystemInfo("language", "en");
        $this->min_bet_setting = $this->getSystemInfo('min_bet_setting', '100');
		$this->max_bet_setting = $this->getSystemInfo('max_bet_setting', '1000');
		$this->min_tie_bet_setting = $this->getSystemInfo('min_tie_bet_setting', '100');
		$this->max_tie_bet_setting = $this->getSystemInfo('max_tie_bet_setting', '1000');
		$this->min_pair_bet_setting = $this->getSystemInfo('min_pair_bet_setting', '100');
		$this->max_pair_bet_setting = $this->getSystemInfo('max_pair_bet_setting', '1000');

        $this->URI_MAP = array(
            self::API_generateToken => '/game/token',
            self::API_createPlayer => "/user/create", 
            self::API_queryPlayerBalance => "/user/balance", 
            self::API_depositToGame => "/user/deposit", 
            self::API_withdrawFromGame => "/user/withdraw", 
            self::API_queryForwardGame => "/game/url",
            self::API_queryTransaction => "/betting/balance",
            self::API_setMemberBetSetting => "/betting/limit/update",
            self::API_syncGameRecords =>"/betting/list",
        );

        $this->METHOD_MAP = array(
			self::API_generateToken => self::GET, 
            self::API_createPlayer => self::POST, 
            self::API_queryPlayerBalance => self::GET, 
            self::API_depositToGame => self::POST, 
            self::API_withdrawFromGame => self::POST, 
            self::API_queryForwardGame => self::GET, 
            self::API_queryTransaction => self::GET, 
            self::API_setMemberBetSetting => self::POST, 
            self::API_syncGameRecords => self::GET,
		);
    }

    public function getPlatformCode()
    {
        return TG_GAME_API;
    }

    public function generateUrl($apiName, $params)
    {
        $apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

        $this->method = $this->METHOD_MAP[$apiName];

        $url = $this->api_url . $apiUri. '?' . http_build_query($params);
		return $url;
    }

    protected function customHttpCall($ch, $params)
    {	
        if($this->method == self::POST){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else if ($this->method == self::GET) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Authorization: Bearer '. $this->api_key));
        }
	}

    protected function getHttpHeaders($params)
    {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key
        );
        return $headers;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $user_ip = $this->CI->utils->getIP();
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $params = array(
            "user_id" => $gameUsername, // ID is not case sensitive and as per provider ID should have minimum of 5 characters
            "user_pw" => $password,
            "user_name" => $gameUsername,
            "user_ip" => $user_ip,
        );

        $this->method = self::POST;
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'response_result_id' => $responseResultId,
            'code' => $resultArr['result_code'],
			'message' => $resultArr['result_msg'],
            'value' => $resultArr['result_value']
        ];

        if ($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
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

        $params = array(
            'user_id' => $gameUsername,
        );

        $this->method = self::GET;
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
    {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = [];
        if($success && $resultArr['result_code'] == 0){
             $result['balance'] = $this->gameAmountToDB(floatval($resultArr['result_value']));
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, '');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );
        
        $params = array(
            'user_id' => $gameUsername,
            'money' => $amount,
        );

        $this->method = self::POST;
        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $transfer_secure_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $amount = $this->getVariableFromContext($params, 'money');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $resultValue = array(
            'user_money' => $amount,
            'transaction_id' => $transfer_secure_id,
        );

        $result = [
			'response_result_id' => $responseResultId,
            'result_code' => $resultArr['result_code'],
            'result_msg' => $resultArr['result_msg'],
            'reason_id'=> self::REASON_UNKNOWN,
            'result_value' => $resultValue,
		];

        if($success)
        {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, '');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );
        
        $params = array(
            'user_id' => $gameUsername,
            'money' => $amount,
        );

        $this->method = self::POST;
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $transfer_secure_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $amount = $this->getVariableFromContext($params, 'money');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $amount = $this->dBtoGameAmount($amount);

        $resultValue = array(
            'user_money' => $amount,
            'transaction_id' => $transfer_secure_id,
        );

        $result = [
			'response_result_id' => $responseResultId,
            'result_code' => $resultArr['result_code'],
            'result_msg' => $resultArr['result_msg'],
            'reason_id'=> self::REASON_UNKNOWN,
            'result_value' => $resultValue,
		];

        if($success)
        {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function generateToken($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $user_ip = $this->CI->utils->getIP();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken',
            'playerId'=> $playerId,
        );

        $params = array(
            'user_id' => $gameUsername,
            'user_ip' => $user_ip,
        );
        
        $this->method = self::GET;
        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultForGenerateToken($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'message' => $resultArr['result_msg'],
            'code' => $resultArr['result_code'],
        );

        if($success)
        {  
            if(isset($resultArr['result_value']['token'])){
                $result["gameToken"] = $resultArr['result_value']['token'];
                $result['result_code'] = self::RESULT_MESSAGE["REQUEST_SUCCESS"];
            }else{
                $result['result_code'] = false;
                $result['result_value'] = null;
            }
        }

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $generateToken = $this->generateToken($playerName);
        $token = $generateToken["gameToken"];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        if(!empty($extra['game_code']))
        {
            $game_code = $extra['game_code'];
        }else{
            $game_code = $this->default_game_code;
        }

        $params = array(
            'token' => $token,
            'game' =>  $game_code,
            'language' => $this->getLauncherLanguage($this->language),
        );
       
        $this->method = self::GET;
        return  $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {

        $gameUsername = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        $result = array();

        if($success){
            if(isset($resultArr['result_value']['link']))
            {
                $result["url"] = $resultArr['result_value']['link'];
            }
        }

        return array($success, $result);
    }


    public function getLauncherLanguage($currentLang) 
    {
        switch ($currentLang) 
        {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en":
                $language = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case "kr":
                $language = 'kr';
                break;
            default:
                $language = 'en';
                break;
        }

        return $language;
    }

    public function syncOriginalGameLogs($token)
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDateTime: " . $startDateTime->format('Y-m-d H:i:s'), "endDateTime: " . $endDateTime->format('Y-m-d H:i:s'), "startDate: " . $startDate->format('Y-m-d H:i:s'), "endDate: " . $endDate->format('Y-m-d H:i:s'));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $queryDateTimeStart,
            'endDate' => $queryDateTimeEnd
        );

        $page = self::START_PAGE;
        $done = false;
        while (!$done){
            $params = array(
                'start_date' => $startDate,
                'start_time' => $queryDateTimeStart,
                'end_date' => $endDate,
                'end_time' => $queryDateTimeEnd,
                'page_num' => $page,
                'page_size' => self::DEFAULT_PAGE_SIZE
            );
        
        $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
        $this->CI->utils->debug_log('api_result', $api_result);
        $done = true;
        if ($api_result && $api_result['success'])
        {
            $total_page = $api_result['last_page'];
            $done = $page >= $total_page;
            $page += 1;
            $this->CI->utils->debug_log('page: ', $page, 'total_page:', $total_page, 'done', $done, 'result', $api_result);
        }
        if ($done)
        {
            $success = true;
        }
        }

        $this->method = self::GET;
        return array( 'success' => $success);
    }

    public function processResultForSyncOriginalGameLogs($params)
    {

        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = ['data_count' => 0];
        $gameRecords = !empty($resultArr['result_value'])?$resultArr['result_value']:[];

        if($success && !empty($gameRecords)){	
            $extra = ['response_result_id' => $responseResultId];
            $gameRecords = $this->rebuildGameRecords($gameRecords,$extra);
            
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId], $this->originalTable);
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], $this->originalTable);
            }
            unset($updateRows);

            $result['last_page'] = $resultArr['last_page'];
		}

		return array($success, $result);
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name) 
    {
        $table_name = $this->originalTable;

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

    public function rebuildGameRecords($game_records, $extra)
    {
        foreach($game_records as $index => $record) {

            $new_game_records[$index]['c_id']           =    isset($record['c_id'])? $record['c_id'] : null;
            $new_game_records[$index]['c_idx']          =    isset($record['c_idx'])? $record['c_idx'] : null;
            $new_game_records[$index]['c_casino']       =    isset($record['c_casino'])? ($record['c_casino']) : null;
            $new_game_records[$index]['c_table_idx']    =    isset($record['c_table_idx'])? $record['c_table_idx'] : null;
            $new_game_records[$index]['c_shoe_idx']     =    isset($record['c_shoe_idx'])? $record['c_shoe_idx'] : null;
            $new_game_records[$index]['c_game_idx']     =    isset($record['c_game_idx'])? $record['c_game_idx'] : null;
            $new_game_records[$index]['c_bet_type']     =    isset($record['c_bet_type'])? $record['c_bet_type'] : null;
            $new_game_records[$index]['c_bet_result']   =    isset($record['c_bet_result'])? $record['c_bet_result'] : null;
            $new_game_records[$index]['c_result_money'] =    isset($record['c_result_money'])? $record['c_result_money'] : null;
            $new_game_records[$index]['c_bet_money']    =    isset($record['c_bet_money'])? $record['c_bet_money'] : null;
            $new_game_records[$index]['c_after_money']  =    isset($record['c_after_money'])? $record['c_after_money'] : null;
            $new_game_records[$index]['c_reg_date']     =    isset($record['c_reg_date'])? $this->gameTimeToServerTime($record['c_reg_date']) : null;
            $new_game_records[$index]['pc1']            =    isset($record['pc1'])? $record['pc1'] : null;
            $new_game_records[$index]['pc2']            =    isset($record['pc2'])? $record['pc2'] : null;
            $new_game_records[$index]['pc3']            =    isset($record['pc3'])? $record['pc3'] : null;
            $new_game_records[$index]['bc1']            =    isset($record['bc1'])? $record['bc1'] : null;
            $new_game_records[$index]['bc2']            =    isset($record['bc2'])? $record['bc2'] : null;
            $new_game_records[$index]['bc3']            =    isset($record['bc3'])? $record['bc3'] : null;
            $new_game_records[$index]['c_game_result']  =    isset($record['c_game_result'])? $record['c_game_result'] : null;
            $new_game_records[$index]['pp']             =    isset($record['pp'])? $record['pp'] : null;
            $new_game_records[$index]['bp']             =    isset($record['bp'])? $record['bp'] : null;

            //extra info from SBE
            $new_game_records[$index]['external_uniqueid'] = isset($record['c_idx'])? $record['c_idx'] : null;
            $new_game_records[$index]['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
            $new_game_records[$index]['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
            $new_game_records[$index]['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
        }

        return $new_game_records;
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle = true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = '`original`.`c_reg_date` >= ? AND `original`.`c_reg_date` <= ?';

        if($use_bet_time)
        {
            $sqlTime = '`original`.`c_reg_date` >= ? AND `original`.`c_reg_date` <= ?';
        }

        $this->CI->utils->debug_log('TG sqlTime ===>', $sqlTime);

        $sql = <<<EOD
        SELECT
            original.c_id as player_username,
            original.c_idx,
            original.c_casino as game_code,
            original.c_table_idx,
            original.c_shoe_idx,
            original.c_game_idx,
            original.c_bet_type,
            original.c_bet_result  as tg_status,
            original.c_result_money as result_amount,
            original.c_bet_money as bet_amount,
            original.c_after_money as after_balance,
            original.c_reg_date as start_at,
            original.c_reg_date as end_at,
            original.c_reg_date as bet_at,
            original.pc1,
            original.pc2,
            original.pc3,
            original.bc1,
            original.bc2,
            original.bc3,
            original.c_game_result,
            original.pp,
            original.bp,
            original.response_result_id,
            original.external_uniqueid,
            original.updated_at,
            original.md5_sum,
            game_provider_auth.player_id,
	        gd.id as game_description_id,
	        gd.english_name as game_description_name,
	        gd.game_type_id
        FROM {$this->originalTable} as original
            LEFT JOIN game_description as gd ON original.c_casino = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.c_id = game_provider_auth.login_name
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
        $note = $this->getNoteDetailsForGameLogs($row);

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}
        
        $extra = [
            'table' =>  $row['c_game_idx'],
            'note'  =>  $note['note']
        ];

        $resultAmount = $row['result_amount'] - $row['bet_amount'];

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
                'result_amount'         => ($resultAmount != 0) ? $this->gameAmountToDB($resultAmount) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => isset($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : null,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag'                      => Game_logs::FLAG_GAME,
            'status'                    => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['c_game_idx']) ? $row['c_game_idx'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => [],
            'extra'                     => $extra,
            //from exists game logs
            'game_logs_id'              =>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'     =>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        
        }

        $row['status'] = Game_logs::STATUS_SETTLED;
    }

    private function getNoteDetailsForGameLogs($row)
    {
        $tg_status = $row['tg_status'];

        switch($tg_status)
        {
            case self::GAME_LOGS_STATUS['ON_GOING']:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = 'On Going';
                break;
            case self::GAME_LOGS_STATUS['WIN']:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = 'Win';
                break;
            case self::GAME_LOGS_STATUS['LOSE']:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = 'Lose';
                break;
            case self::GAME_LOGS_STATUS['TIE']:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = 'Tie';
                break;
            default:
                $game_logs_status = Game_logs::STATUS_PENDING;
                $note = "Unsettled";
                break;
        }

        $result = array(
            'status' => $game_logs_status,
            'note' => $note
        );

        return $result;
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

    public function processResultBoolean($responseResultId, $resultArr, $statusCode = null)
    {
        $success = false;
    
        if($resultArr['result_msg'] == self::RESULT_MESSAGE["REQUEST_SUCCESS"]){
            $success = true;
        }

        if (!$success) 
        {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('TG GAME API got error ', $responseResultId, 'result', $resultArr);
        }
    
        return $success;
    }

    public function setMemberBetSetting($playerName, $betSettings) {

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForSetMemberBetSetting',
			'playerName' => $playerName
		);

		$params = array(
			'user_id' => $gameUsername,
			'max_bet' => isset($betSettings['max_bet']) ? $betSettings['max_bet'] : $this->max_bet_setting, 
			'min_bet' => isset($betSettings['min_bet']) ? $betSettings['min_bet'] : $this->min_bet_setting, 
			'max_tie' => isset($betSettings['max_tie']) ? $betSettings['max_tie'] : $this->max_tie_setting, 
			'min_tie' => isset($betSettings['min_tie']) ? $betSettings['min_tie'] : $this->min_tie_setting, 
			'max_pair' => isset($betSettings['max_pair']) ? $betSettings['max_pair'] : $this->max_pair_setting,
			'min_pair' => isset($betSettings['min_pair']) ? $betSettings['min_pair'] : $this->min_pair_setting,
		);

		$this->method = self::POST;

		$this->CI->utils->debug_log('TG (setMemberBetSetting) :', $params);

		return $this->callApi(self::API_setMemberBetSetting, $params, $context);

	}

    public function processForSetMemberBetSetting($params) {
        $resultJsonArr = $this->getResultJsonFromParams($params);
        return array(true, $resultJsonArr);
    }

}

/*end of file*/