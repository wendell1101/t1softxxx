<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: GMT Game
	* API docs: http://mucho.oriental-game.com:8059/
	*
	* @category Game_platform
	* @copyright 2013-2022 tot
	* @integrator @bermar.php.ph
**/

abstract class Abstract_game_api_common_gmt extends Abstract_game_api {
	const POST = 'POST';
    const GET = 'GET';
    const SUCCESS_HTTP_CODES = [200];
    const ERROR_HTTP_CODES = [400,401,409];
    const ERROR_CONFLICT_CODE = 409;
    const ERROR_BADREQUEST_CODE = 400;
    const ERROR_UNAUTHORIZED_CODE = 401;
    const ERROR_WALLET_NOT_FOUND = 1001;

    private $original_gamelogs_table = 'gmt_game_logs';

	# Fields in gmt_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'game_id',
        'total_bet',
        'total_win',
        'balance',
        'round_start_ts_parsed',
        'round_end_ts_parsed'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'total_bet',
        'total_win',
        'balance',
    ];

    # Fields in game_logs we want to detect changes for merge and when md5_sum
    const MD5_FIELDS_FOR_MERGE=[
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
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'after_balance',
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	public function __construct() {
		parent::__construct();

        //$this->original_gamelogs_table = $this->getOriginalTable();

        $this->api_url = $this->getSystemInfo('url');
        $this->partner_id = $this->getSystemInfo('partner_id', '');
        $this->language = $this->getSystemInfo('language','');
        $this->country = $this->getSystemInfo('country','');
        $this->gp_api_key = $this->getSystemInfo('gp_api_key');
        $this->gp_p_id = $this->getSystemInfo('gp_p_id');
        $this->locale = $this->getSystemInfo('locale');
        $this->brand_id = $this->getSystemInfo('brand_id');
        $this->country = $this->getSystemInfo('country');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
        $this->initial_page = $this->getSystemInfo('initial_page', 1);
        $this->page_size = $this->getSystemInfo('page_size', 1000);
        $this->max_page = $this->getSystemInfo('max_page', 2000); 
        $this->game_launch_url = $this->getSystemInfo('game_launch_url', $this->api_url); 
        $this->game_data_url = $this->getSystemInfo('game_data_url', $this->api_url); 
        $this->home_redirect_path = $this->getSystemInfo('home_redirect_path', ''); 
        $this->use_referrer = $this->getSystemInfo('use_referrer', false); 
        $this->force_game_language = $this->getSystemInfo('force_game_language', false); 
        $this->use_replay_as_bet_detail = $this->getSystemInfo('use_replay_as_bet_detail', true); 
        

		$this->URI_MAP = array(
            self::API_createPlayer => "/{$this->partner_id}/register",
            self::API_isPlayerExist => "/{$this->partner_id}/checkPlayer",
            self::API_queryPlayerBalance => "/{$this->partner_id}/fetchBalance",
            self::API_depositToGame => "/{$this->partner_id}/transfer",
            self::API_withdrawFromGame => "/{$this->partner_id}/transfer",
            self::API_queryTransaction => "/{$this->partner_id}/transactions/id/",       
            self::API_syncGameRecords => "/{$this->partner_id}/gameRounds",
            self::API_queryForwardGame => "/{$this->partner_id}/launchGame",
            self::API_queryBetDetailLink => "/{$this->partner_id}/replay",
		);

		$this->METHOD_MAP = array(
            self::API_createPlayer => self::POST,
            self::API_isPlayerExist => self::POST,
            self::API_queryPlayerBalance => self::POST,
            self::API_depositToGame => self::POST,
            self::API_withdrawFromGame => self::POST,
            self::API_queryTransaction => self::GET,
            self::API_syncGameRecords => self::GET,
	        self::API_queryForwardGame => self::GET,
	        self::API_queryBetDetailLink => self::GET,
		);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	protected function getHttpHeaders($params){
		$this->CI->utils->debug_log('GMT (getHttpHeaders)', $params);		

		$headers = [];
		$headers['Content-Type'] = 'application/json';//for data requests
        $headers['gp-api-key'] = $this->gp_api_key;
        $headers['gp-p-id'] = $this->gp_p_id;
        $this->CI->utils->debug_log('GMT (getHttpHeaders)', 'method', 'headers', $headers);		
		return $headers;
	}

	public function generateUrl($apiName, $params) {
        $this->CI->utils->debug_log('GMT (generateUrl)', $apiName, $params);
        		
        $this->method = $this->METHOD_MAP[$apiName];
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		if($apiName==self::API_queryTransaction){
            $transactionId = isset($params['transactionId'])?$params['transactionId']:'';
            $url.=$transactionId;
        }elseif($apiName==self::API_syncGameRecords){
            $url = $this->game_data_url . $apiUri;
        }elseif($apiName==self::API_queryForwardGame){
            $url = $this->game_launch_url . $apiUri;
		}else{
        }

        if($this->method == self::GET&&!empty($params)){
            if($apiName==self::API_queryBetDetailLink){
                $url .= '/'.$params['gameRoundId'];                
            }else{
                $url = $url . '?' . http_build_query($params);
            }
        }

		$this->CI->utils->debug_log('GMT (generateUrl) :', $this->method, $url);

		return $url;
	}

	protected function customHttpCall($ch, $params) {	
		$this->CI->utils->debug_log('GMT (customHttpCall)');		

		switch ($this->method){
			case self::POST:
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
		}
		$this->utils->debug_log('GMT (customHttpCall) ', http_build_query($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $statusCode, $apiName=null) {
		$this->CI->utils->debug_log('GMT (processResultBoolean)');	

		$success = false;
		if(in_array($statusCode, self::SUCCESS_HTTP_CODES)){
			$success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('GMT got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$this->CI->utils->debug_log('GMT (createPlayer)', $playerName);	

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = [
            ['playerId' => $gameUsername, 
            'currency' => $this->currency, 
            'locale' => $this->locale, 
			/*'brandId' => $this->brand_id,
			'country' => $this->country,
			'birthdate' => '1990-01-01'*/]
		];
		
		$this->CI->utils->debug_log('GMT (createPlayer) :', $params);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$this->CI->utils->debug_log('GMT (processResultForCreatePlayer)', $params);	

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $statusCode, self::API_createPlayer);
		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){			
	        $result['exists'] = true;
		}else{
            //player already exist
            if($resultArr['statusCode'] = self::ERROR_CONFLICT_CODE){
                $this->CI->utils->debug_log('GMT (processResultForCreatePlayer) player already exist', $resultArr);	
                $success = true;
            }
        }

        if($success){
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

		return array($success, $result);
    }
    
	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('GMT (queryPlayerBalance)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'playerId' => $gameUsername,
            'currency' => $this->currency, 
        );
        
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('GMT (processResultForQueryPlayerBalance)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $statusCode, self::API_queryPlayerBalance);
		$result = [];

		if($success){
            if(isset($resultArr['balance'])){
                $result['balance'] = $this->convertAmountToDB($resultArr['balance']);
            }else{
                $success=false;
            }
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$this->CI->utils->debug_log('GMT (isPlayerExist)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'playerId' => $gameUsername,
            'currency' => $this->currency, 
        );
        
		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$this->CI->utils->debug_log('GMT (processResultForIsPlayerExist)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $statusCode, self::API_isPlayerExist);
		$result['exists'] = null;

		if($success){
			$result['exists'] = true;
		}else{
			$result['exists'] = false;
		}

		return array($success, $result);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('GMT (depositToGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

        $amount = $this->dBtoGameAmount($amount);
		$params = array(
            'playerId' => $gameUsername,
            'action' => 'deposit', 
            'amount' =>  $amount, 
            'currency' => $this->currency, 
            'transactionId' => $external_transaction_id,
        );
        
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$this->CI->utils->debug_log('GMT (processResultForDepositToGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, $statusCode, self::API_depositToGame);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{        	
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr, $statusCode);
        }

        return array($success, $result);
	}

	private function getReasons($result, $statusCode){
        $message = @$result['message'];
        $code = @$result['code'];
		switch ($statusCode) {
            case 401:			
            case '401':			
				return self::REASON_INVALID_KEY;
				break;
            case 409:			
            case '409':				
				return self::REASON_INSUFFICIENT_AMOUNT;
				break;
            case 400:			
            case '400':	                
                if($code=='1000'){
                    return self::REASON_INVALID_ARGUMENTS;
                }elseif($code=='1001'){
                    return self::REASON_INVALID_PRODUCT_WALLET;
                }
				return self::REASON_UNKNOWN;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('GMT (depositToGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

        $amount = $this->dBtoGameAmount($amount);
		$params = array(
            'playerId' => $gameUsername,
            'action' => 'withdraw', 
            'amount' =>  $amount, 
            'currency' => $this->currency, 
            'transactionId' => $external_transaction_id,
        );
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$this->CI->utils->debug_log('GMT (processResultForWithdrawFromGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, $statusCode, self::API_withdrawFromGame);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr, $statusCode);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
		$this->CI->utils->debug_log('GMT (queryTransaction)', $transactionId, $extra);	
		
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$transfer_time=$extra['transfer_time'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);

		$params = array(
            'transactionId' => $transactionId,
        );
		
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$this->CI->utils->debug_log('GMT (processResultForQueryTransaction)', $params);	

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername,$statusCode, self::API_queryTransaction);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
            $transactionId = @$resultJsonArr['transaction']['transactionId'];
            $transactionPlayerId = @$resultJsonArr['transaction']['playerId'];

			if(!empty($resultJsonArr)){				
                $transactionId = @$resultJsonArr['transaction']['transactionId'];
                $transactionPlayerId = @$resultJsonArr['transaction']['playerId'];
                if($external_transaction_id==$transactionId){
                    $result['reason_id'] = self::REASON_UNKNOWN;
                    $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                }
            }
		} else {
			$result['reason_id'] = $this->getReasons($resultJsonArr,$statusCode);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

    public function login($playerName, $password = null, $extra = null) {
		$this->CI->utils->debug_log('GMT (login)', $playerName);	
		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$params = array(
			'username' => $gameUsername
		);

		$this->method = self::GET;
		return $this->callApi(self::API_login, $params, $context);
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra) {
        $this->utils->debug_log("GMT (queryForwardGame)");   

        if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($this->language);
        }

        if($this->force_game_language){
            $language=$this->getLauncherLanguage($this->force_game_language);
        }
			
        $lobbyURL = $this->getReturnUrl($extra);  

        $mode = isset($extra['game_mode'])?$extra['game_mode']:'real';

        $data = array(            
            "game" => $extra['game_code'],            
            "currency" => $this->currency,                            
            "locale" => $language,                          
            "mode" => $mode,
            "lobbyURL" => $lobbyURL,
            "brandId" => $this->brand_id
        );

        if( !in_array($mode, ['fun','trial','demo'])){
            $this->CI->load->model('external_common_tokens');
            $token = $this->getPlayerTokenByUsername($playerName);
            $playerId = $this->getPlayerIdInPlayer($playerName);
            $this->CI->external_common_tokens->setPlayerToken($playerId, $token, $this->getPlatformCode());
            $data['launchToken'] = $this->getPlayerTokenByUsername($playerName);
        }else{
            $data['mode'] = 'fun';
        }

        //$this->game_launch_url .='/'.$extra['game_code'];
        $url = $this->generateUrl(self::API_queryForwardGame, $data);
        //var_dump($url);exit;
        $this->CI->utils->debug_log('GMT (queryForwardGame)', $data, $url);	
        $result = array(
            "success" => true,
            "url" => $url,//  $this->buildUrl($this->game_launch_url, $data)
        );       
        return $result;
    }

    public function getReturnUrl($params){
        if($this->use_referrer){
            $path = $this->getSystemInfo('home_redirect_path', '');
            $url = trim(@$_SERVER['HTTP_REFERER'],'/').$path;
        }else{
            $url = $this->getHomeLink();
        }
        
        if (isset($params['home_link'])) {
            $url = $params['home_link'];
        }

        return $url;
    }

    public function callback($token, $method) {
        $this->CI->load->model(array('external_common_tokens', 'player_model'));
        		
        $player_id = $this->CI->external_common_tokens->getPlayerIdByExternalToken($token, $this->getPlatformCode());
        
        if($player_id) {
            $gameUsername = $this->getGameUsernameByPlayerId($player_id);
            
            $result = array(
                'currency' => $this->currency,
                'playerId' => $gameUsername,
                'account' => [
                    'alias' => $gameUsername
                ]
            );    
            return $result;
        }else{
            return false;
        }
    }
    
    public function buildUrl($url, $data){
        $params = $data;
        $params = http_build_query($params);
        $url = $url."?".$params;
        return $url;
    }
    
	public function syncOriginalGameLogs($token) {
		$this->CI->utils->debug_log('GMT (syncOriginalGameLogs)', $token, $this->original_gamelogs_table);	

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");		
    	$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
        
        
        $currentPage = $this->initial_page;
        $this->continue_loop = true;

        while ($this->continue_loop) {
            $context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startTimestamp' => $queryDateTimeStart,
				'endTimestamp' => $queryDateTimeEnd
			);

			$params = array(				
				'startTimestamp' => $this->convertDateTimeToUnixTimestamp($queryDateTimeStart),
				'endTimestamp' => $this->convertDateTimeToUnixTimestamp($queryDateTimeEnd),
				'pageSize' => $this->page_size,
				'page' => $currentPage,
            );
            
			$this->is_auth = false;
            $result[] = $this->callApi(self::API_syncGameRecords, $params, $context);
            
			sleep($this->sync_sleep_time);			

            $currentPage++;       
            
            if($currentPage>$this->max_page ){
                $this->continue_loop = false;
            }
        }

		return array("success" => true, "results"=>$result);
	}
    
    public function convertDateTimeToUnixTimestamp($dateTime){
        return strtotime($dateTime)*1000;
    }
    
    public function convertUnixTimestampToDateTime($timestamp){
        return date('Y-m-d H:i:s', $timestamp/1000);
    }

	private function rebuildGameRecords(&$gameRecords,$extra){

		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$temp_new_gameRecords = [];
        		
            $temp_new_gameRecords['game_username'] = isset($record['playerId'])?$record['playerId']:null;
            $temp_new_gameRecords['total_bet'] = isset($record['totalBet'])?$record['totalBet']:null;
            $temp_new_gameRecords['total_win'] = isset($record['totalWin'])?$record['totalWin']:null;
            $temp_new_gameRecords['balance'] = isset($record['balance'])?$record['balance']:null;
            $temp_new_gameRecords['round_start_ts'] = isset($record['roundStartTs'])?$record['roundStartTs']:null;
            $temp_new_gameRecords['round_start_ts_parsed'] = $this->convertUnixTimestampToDateTime($temp_new_gameRecords['round_start_ts']);
            $temp_new_gameRecords['round_end_ts'] = isset($record['roundEndTs'])?$record['roundEndTs']:null;
            $temp_new_gameRecords['round_end_ts_parsed'] = $this->convertUnixTimestampToDateTime($temp_new_gameRecords['round_end_ts']);
            $temp_new_gameRecords['game_id'] = isset($record['ref']['gameId'])?$record['ref']['gameId']:null;
            $temp_new_gameRecords['round_id'] = isset($record['ref']['roundId'])?$record['ref']['roundId']:null;
            $temp_new_gameRecords['game_type'] = isset($record['ref']['gameType'])?$record['ref']['gameType']:null;
            $temp_new_gameRecords['external_uniqueid'] = $this->generateExternalUniqueId($temp_new_gameRecords);
            $temp_new_gameRecords['response_result_id'] = $extra['response_result_id'];			
            $temp_new_gameRecords['game_platform_id'] = $this->getPlatformCode();
            
			$new_gameRecords[$index] = $temp_new_gameRecords;
        }

        $gameRecords = $new_gameRecords;
    }
    
    private function generateExternalUniqueId($data){
        $playerGameUsername = @$data['game_username'];
        $round = @$data['round_id'];
        return $playerGameUsername.'-'.$round;
    }

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->utils->debug_log('GMT (processResultForSyncOriginalGameLogs)');

        $this->CI->load->model('original_game_logs_model');
		
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,null,true);
		$result = array('data_count'=>0);

        $gameRecords = !empty($resultArr['gameRounds'])?$resultArr['gameRounds']:[];
        
		if($success&&!empty($gameRecords)){
            if(count($gameRecords)<$this->page_size){
                $this->continue_loop = false;
            }

            $extra = ['response_result_id'=>$responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);
            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
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

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);
		}else{            
            $this->continue_loop = false;
        }

		return array($success, $result);
	}


    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		
		$sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime='`original`.`round_start_ts_parsed` >= ? AND `original`.`round_end_ts_parsed` <= ?';
        }

        $this->CI->utils->debug_log('GMT (queryOriginalGameLogs)', 'sqlTime', $sqlTime);


        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.response_result_id,
    original.game_id as game_code,
    original.total_win as win_amount,
    original.total_bet as valid_bet,
    original.total_bet as bet_amount,
    original.round_start_ts_parsed as start_at,
	original.round_end_ts_parsed as end_at,
	original.round_start_ts_parsed as bet_at,
    original.round_id as round_id,    
    original.round_id as round,    
    original.game_username as username,
    original.balance as after_balance,
    original.game_id as game_name,
	original.updated_at,
	original.external_uniqueid,
    original.md5_sum,    
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.game_username = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime};
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
		];
		
		$this->CI->utils->debug_log('GMT (queryOriginalGameLogs)', 'sql', $sql, 'params', $params);
		
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round_id'],
        ];
        $row['result_amount'] = $row['win_amount']-$row['bet_amount'];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['valid_bet'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
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
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array('success' => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array('success' => true);
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

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

    /**
     * overwrite it , if not http call
     *
     * @return boolean true=error, false=ok
     */
    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		// $statusCode = intval($statusCode, 10);
		if($apiName == self::API_isPlayerExist){
			return $errCode || intval($statusCode, 10) >= 401;
		}
        return parent::isErrorCode($apiName, $params, $statusCode, $errCode, $error);
	}

    public function queryBetDetailLink($playerUsername, $roundno=null, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryBetDetailLink',
			'gameUsername'    => $gameUsername
		);

        if(!$this->use_replay_as_bet_detail){
            return false;
        }

        $params['gameRoundId'] = $roundno;
        
        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    /** 
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params)
    {		
		$resultArr = $this->getResultJsonFromParams($params);        	
		
		$result = ['url' => ''];
        $success=false;
        $this->utils->debug_log("GMT (processResultForQueryBetDetailLink)", $resultArr);           

		if (isset($resultArr['replayUrl'])) {
            $success=true;
			$result['url'] = $resultArr['replayUrl'];
        }

        return array($success, $result);
    }

}

/*end of file*/