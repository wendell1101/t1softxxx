<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: OM Lotto
	* API docs: 
	*
	* @category Game_platform
	* @copyright 2013-2022 tot
	* @integrator @bermar.php.ph
**/

abstract class Abstract_game_api_common_om_lotto extends Abstract_game_api {
	const POST = 'POST';
    const GET = 'GET';

    const RESPONSE_SUCCESS = 'SUCCEEDED';
    const RESPONSE_FAILED = 'FAILED';

    const SUCCESS_CODE = 'SUCCEEDED';
    const ERROR_CODE = 'FAILED';
    const ERROR_VALIDATION_USER_FAILED = 'VALIDATION_USER_FAILED';
    const ERROR_AUTHORIZATION_FAILED = 'AUTHORIZATION_FAILED';

    public $original_gamelogs_table = 'om_lotto_game_logs';

	# Fields in om_lotto_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'total_stake',
        'valid_stake',
        'member_result_amount',
        'bet_result_id',
        'bet_status_id',
        // 'game_type',
        'game_type_id',
        'updated_datetime'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'total_stake',
        'valid_stake',
        'member_result_amount',
    ];

    # Fields in game_logs we want to detect changes for merge and when md5_sum
    const MD5_FIELDS_FOR_MERGE=[
        'status',        
        'updated_datetime',
        'bet_status_id',
        'game_code',
        'game_name',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at',
        'updated_at'
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
        $this->licensee_key = $this->getSystemInfo('licensee_key', '');
        $this->language = $this->getSystemInfo('language','');
        $this->home_redirect_path = $this->getSystemInfo('home_redirect_path', ''); 
        $this->use_referrer = $this->getSystemInfo('use_referrer', false); 
        $this->game_launch_url = $this->getSystemInfo('game_launch_url', $this->api_url); 
        $this->force_game_language = $this->getSystemInfo('force_game_language', false); 
        $this->currency = $this->getSystemInfo('currency','');
        $this->conversion_precision = $this->getSystemInfo('conversion_precision', 4);
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');        
        $this->game_data_url = $this->getSystemInfo('game_data_url', $this->api_url);         
        $this->is_redirect = $this->getSystemInfo('is_redirect', true); //need redirect will affect cookie
        $this->create_player_url = '';

		$this->URI_MAP = array(
            self::API_queryForwardGame => "/Auth.action",
            self::API_createPlayer => "",
            self::API_queryPlayerBalance => "/WalletApi/GetBalance.action",
            self::API_isPlayerExist => "/WalletApi/GetBalance.action",
            self::API_depositToGame => "/WalletApi/FundIn.action",
            self::API_withdrawFromGame => "/WalletApi/FundOut.action",
            self::API_syncGameRecords => "/GameApi/GetAllBet.action",
            
            self::API_queryTransaction => "/WalletApi/GetFundTransfer.action",      
		);

		$this->METHOD_MAP = array(
            self::API_queryForwardGame => self::GET,
            self::API_createPlayer => self::GET,
            self::API_queryPlayerBalance => self::GET,            
            self::API_isPlayerExist => self::GET,            
            self::API_depositToGame => self::GET,
            self::API_withdrawFromGame => self::GET,
            self::API_syncGameRecords => self::GET,

            self::API_queryTransaction => self::GET,
		);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	protected function getHttpHeaders($params){
		$this->CI->utils->debug_log('OMLOTTO (getHttpHeaders)', $params);		

		$headers = [];
		$headers['Content-Type'] = 'application/json';
        $this->CI->utils->debug_log('OMLOTTO (getHttpHeaders)', 'method', 'headers', $headers);		
		return $headers;
	}

	public function generateUrl($apiName, $params) {
        $this->CI->utils->debug_log('OMLOTTO (generateUrl)', $apiName, $params);
        		
        $this->method = $this->METHOD_MAP[$apiName];
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		if($apiName==self::API_queryForwardGame){
            $url = $this->game_launch_url . $apiUri;
		}elseif($apiName==self::API_syncGameRecords){
            $url = $this->game_data_url . $apiUri;
        }elseif($apiName==self::API_createPlayer){
            $url = $this->create_player_url;
        }

        if($this->method == self::GET&&!empty($params)){
            $url = $url . '?' . http_build_query($params);
        }

		$this->CI->utils->debug_log('OMLOTTO (generateUrl) :', $this->method, $url);

		return $url;
	}

	protected function customHttpCall($ch, $params) {	
		$this->CI->utils->debug_log('OMLOTTO (customHttpCall)');		

		switch ($this->method){
			case self::POST:
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
		}
		$this->utils->debug_log('OMLOTTO (customHttpCall) ', http_build_query($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $statusCode, $apiName=null) {
		$this->CI->utils->debug_log('OMLOTTO (processResultBoolean)');	

		$success = false;

        if(isset($resultArr['returnCode']) && $resultArr['returnCode']==self::SUCCESS_CODE){
            $success = true;
        }

        if(isset($resultArr['returnCode']) && $resultArr['returnCode']==self::ERROR_CODE){
            $success = false;
        }

        if($apiName==self::API_queryTransaction){

        }

        if($apiName==self::API_isPlayerExist){
            if(isset($resultArr['returnCode']) && $resultArr['returnCode']==self::ERROR_CODE){
                $success = true;
            }
        }

        if($apiName==self::API_createPlayer){            
            //$success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('OMLOTTO got error ','apiName', $apiName, $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function queryForwardGame($playerName, $extra) {
        $this->utils->debug_log("OMLOTTO (queryForwardGame)");   

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

        $token = $this->getPlayerTokenByUsername($playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $data = array(            
            "licenseeKey" => $this->licensee_key,            
            "userCode" => $gameUsername,                            
            "token" => $token,                          
            "currencyCode" => $this->currency,
            "language" => $language,
            "logoutRedirectUrl" => $lobbyURL
        );

        $url = $this->generateUrl(self::API_queryForwardGame, $data);

        $this->CI->utils->debug_log('OMLOTTO (queryForwardGame)', $data, $url);	
        $result = array(
            "success" => true,
            "url" => $url,
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
    
    public function buildUrl($url, $data){
        $params = $data;
        $params = http_build_query($params);
        $url = $url."?".$params;
        return $url;
    }

    public function callback($data, $method) {
        $this->CI->load->model(array('player_model','common_token'));

        $token = isset($data['token'])?$data['token']:null;        
        
        $player = $this->CI->common_token->getPlayerCompleteDetailsByToken($token, $this->getPlatformCode(), false); 
                
        if(  
            !empty($token) && $player  && isset($player->game_username)
        ) {
            $result = array(
                'userCode' => $player->game_username,
                'returnCode' => self::RESPONSE_SUCCESS                
            );
        }else{
            $result = array(
                'userCode' => null,
                'returnCode' => self::RESPONSE_FAILED,
                'message' => 'Invalid token.'
            );    
        }
        return $result;
    }    

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $this->CI->utils->debug_log('OMLOTTO (createPlayer)');	
        //launch the game to it create a provider account
        $result = $this->queryForwardGame($playerName, []);
        $this->utils->debug_log("OMLOTTO (createPlayer)", 'result', $result);   
        if(!isset($result['url'])){
            return array("success" => false, "message" => "Unable to create account for OM Lotto");
        }
        $this->create_player_url = $result['url'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
            'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);
        $params = [];        
		return $this->callApi(self::API_createPlayer, $params, $context);
    }

	public function processResultForCreatePlayer($params){        
        $resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('OMLOTTO (processResultForCreatePlayer)', $resultArr);	
        $resultText = $this->getResultTextFromParams($params);      
        $this->CI->utils->debug_log('OMLOTTO (processResultForCreatePlayer) resultText', $resultText);	  
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $result = array(
			'player' => $gameUsername,
			'exists' => false
		);	
        
        //check if resulttext is valid        
        if (
            strpos($resultText, self::ERROR_AUTHORIZATION_FAILED) === false &&
            strpos($resultText, 'EXCEPTION') === false
        ){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;	     
            return array(true, $result);    
        }else{
            return array(false, $result);
        }
	}
    
	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('OMLOTTO (queryPlayerBalance)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'userCode' => $gameUsername,
            'licenseeKey' => $this->licensee_key, 
        );
        
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
        $resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('OMLOTTO (processResultForQueryPlayerBalance)', $resultArr);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
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
		$this->CI->utils->debug_log('OMLOTTO (isPlayerExist)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
            'userCode' => $gameUsername,
            'licenseeKey' => $this->licensee_key, 
        );
        
		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('OMLOTTO (processResultForIsPlayerExist)', $resultArr);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $statusCode, self::API_isPlayerExist);
		$result['exists'] = null;

		if($success){			

            if(isset($resultArr['returnCode']) && $resultArr['returnCode']==self::SUCCESS_CODE){
                $result['exists'] = true;
                $playerId = $this->getPlayerIdByGameUsername($gameUsername);
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            }else{
                $result['exists'] = false;
            }
		}else{
			$result['exists'] = false;
		}

		return array($success, $result);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('OMLOTTO (depositToGame)', $playerName);	

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
            'userCode' => $gameUsername,
            'licenseeKey' => $this->licensee_key, 
            "currencyCode" => $this->currency,
            "amount" => $amount,
            "referenceNo" => $external_transaction_id,
        );
        
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('OMLOTTO (processResultForDepositToGame)', $resultArr);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, $statusCode, self::API_depositToGame);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id,
		);

		if ($success) {
            if(
                isset($resultArr['userCode']) 
                && isset($resultArr['transactionId'])
                && $resultArr['userCode']==$gameUsername
            ){
                $external_transaction_id = isset($resultArr['transactionId'])?$resultArr['transactionId']:null;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs']=true;
                $result['external_transaction_id']=$external_transaction_id;
            }			
        }else{
            $message = isset($resultArr['message'])?$resultArr['message']:null;
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($message, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id'] = $this->getReasons($resultArr);
            }
        
        }

        return array($success, $result);
	}

	private function getReasons($result){
        $message = @$result['message'];
        $code = @$result['returnCode'];
		switch ($code) {
            case 'FAILED':			            
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('OMLOTTO (depositToGame)', $playerName);	

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
            'userCode' => $gameUsername,
            'licenseeKey' => $this->licensee_key, 
            "currencyCode" => $this->currency,
            "amount" => $amount,
            "referenceNo" => $external_transaction_id,
        );

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('OMLOTTO (processResultForWithdrawFromGame)', $resultArr);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, $statusCode, self::API_depositToGame);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id,
		);

		if ($success) {
            if(
                isset($resultArr['userCode']) 
                && isset($resultArr['transactionId'])
                && $resultArr['userCode']==$gameUsername                
            ){
                $external_transaction_id = isset($resultArr['transactionId'])?$resultArr['transactionId']:$external_transaction_id;
                $result['external_transaction_id']=$external_transaction_id;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs']=true;
            }			
        }else{        	
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
		$this->CI->utils->debug_log('OMLOTTO (queryTransaction)', $transactionId, $extra);			
		$playerName=$extra['playerName'];
        $secure_id=$extra['secure_id'];		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
            'playerName' => $playerName,
			'external_transaction_id' => $transactionId,			
            'secure_id' => $secure_id,			
		);

		$params = array(            
            'licenseeKey' => $this->licensee_key, 
            "fundTransferId" => $transactionId,
            "externalReferenceNo" => $secure_id,            
        );
		
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
        $resultArr = $this->getResultJsonFromParams($params);
		$this->CI->utils->debug_log('OMLOTTO (processResultForQueryTransaction)', $resultArr);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $secure_id = $this->getVariableFromContext($params, 'secure_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);		
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, $statusCode, self::API_queryTransaction);		

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;			    
		} else {
			$result['reason_id'] = self::REASON_UNKNOWN;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en_us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
            case 'zh_cn':
                $lang = 'zh_cn'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
            case 'id_id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
            case 'vi_vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
            case 'ko_kr':
                $lang = 'ko_kr'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th_th':
            case 'th':
                $lang = 'th'; // thai
                break;
            case 'my':
            case 'ms-my':
            case 'ms_my':
                $lang = 'ms_my'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }
    
	public function syncOriginalGameLogs($token = false) {
		$this->CI->utils->debug_log('OMLOTTO (syncOriginalGameLogs)', $token, $this->original_gamelogs_table);	

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i:s");
		$queryDateTimeEnd = $startDateTime->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i:s");
    	# Query Exact end
    	if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
    	}

    	while ($queryDateTimeMax  > $queryDateTimeStart) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd
			);

			$params = array(
				'licenseeKey' => $this->licensee_key, 
				'updatedDateTimeFrom' => $queryDateTimeStart,
				'updatedDateTimeTo' => $queryDateTimeEnd,
			);
			
			$result[] = $cur_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			sleep($this->sync_sleep_time);
			$queryDateTimeStart = $queryDateTimeEnd;
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i:s");
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
        $this->CI->utils->debug_log('OMLOTTO (rebuildGameRecords)');	
		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$temp_new_gameRecords = [];
        		
            $temp_new_gameRecords['bet_id'] = isset($record['betId'])?$record['betId']:null;
            $temp_new_gameRecords['bet_datetime'] = isset($record['betDateTime'])? $this->gameTimeToServerTime($record['betDateTime']) :null;
            $temp_new_gameRecords['updated_datetime'] = isset($record['updatedDateTime'])?$this->gameTimeToServerTime($record['updatedDateTime']):null;

            $temp_new_gameRecords['bet_status_id'] = isset($record['betStatusId'])?$record['betStatusId']:null;
            $temp_new_gameRecords['bet_result_id'] = isset($record['betResultId'])?$record['betResultId']:null;
            $temp_new_gameRecords['member_user_code'] = isset($record['memberUserCode'])?$record['memberUserCode']:null;
            $temp_new_gameRecords['member_currency_code'] = isset($record['memberCurrencyCode'])?$record['memberCurrencyCode']:null;
            $temp_new_gameRecords['total_stake'] = isset($record['totalStake'])?$record['totalStake']:0;
            $temp_new_gameRecords['valid_stake'] = isset($record['validStake'])?$record['validStake']:0;
            $temp_new_gameRecords['member_result_amount'] = isset($record['memberResultAmount'])?$record['memberResultAmount']:0;
            $temp_new_gameRecords['bet_type_id'] = isset($record['betTypeId'])?$record['betTypeId']:null;
            $temp_new_gameRecords['selection_type_code'] = isset($record['selectionTypeCode'])?$record['selectionTypeCode']:null;
            $temp_new_gameRecords['odds'] = isset($record['odds'])?$record['odds']:null;
            $temp_new_gameRecords['odds_string'] = isset($record['oddsString'])?$record['oddsString']:null;
            $temp_new_gameRecords['event_display_date_time'] = isset($record['eventDisplayDateTime'])? $this->gameTimeToServerTime($record['eventDisplayDateTime']):null;
            // $temp_new_gameRecords['game_type'] = isset($record['gameType'])?$record['gameType']:null;
            $temp_new_gameRecords['game_type_id'] = isset($record['gameTypeId'])?$record['gameTypeId']:null;
            $temp_new_gameRecords['game_type_text'] = isset($record['gameTypeText'])?$record['gameTypeText']:null;
            $temp_new_gameRecords['event_id'] = isset($record['eventId'])?$record['eventId']:null;
            
            $temp_new_gameRecords['external_uniqueid'] = $this->generateExternalUniqueId($temp_new_gameRecords);
            $temp_new_gameRecords['response_result_id'] = $extra['response_result_id'];			
            $temp_new_gameRecords['game_platform_id'] = $this->getPlatformCode();
            
			$new_gameRecords[$temp_new_gameRecords['bet_id']] = $temp_new_gameRecords;
        }

        $gameRecords = $new_gameRecords;
    }
    
    private function generateExternalUniqueId($data){        
        $bet_id = $data['bet_id'];
        return $bet_id;
    }

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->utils->debug_log('OMLOTTO (processResultForSyncOriginalGameLogs)');

        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,null,true);
		$result = array('data_count'=>0);

		$gameRecords = ( isset($resultArr['bets'])&&!empty($resultArr['bets']) )?$resultArr['bets']:[];
		if($success&&!empty($gameRecords)){
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
        $enabled_game_logs_unsettle=true;
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
            $sqlTime='`original`.`updated_datetime` >= ? AND `original`.`updated_datetime` <= ?';
        }

        $this->CI->utils->debug_log('OMLOTTO (queryOriginalGameLogs)', 'sqlTime', $sqlTime);


        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.response_result_id,
    original.game_type_id as game_code,
    original.member_user_code as username,
    original.total_stake as valid_bet,
    original.total_stake as bet_amount,
    original.member_result_amount as result_amount,
    original.bet_datetime as start_at,
    original.updated_datetime,
	original.bet_datetime as bet_at,
    original.bet_id as round_id,
    original.bet_id as round, 
    original.game_type_text as game_name,
    original.event_id,
    original.event_display_date_time,
    original.event_display_date_time as end_at,
	original.updated_at,
	original.external_uniqueid,
    original.bet_id,
    original.bet_status_id,
    original.bet_status_id status,
    original.bet_result_id,
    original.odds_string,
    original.odds,
    original.md5_sum,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.game_type_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.member_user_code = game_provider_auth.login_name
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
		
		$this->CI->utils->debug_log('OMLOTTO (queryOriginalGameLogs)', 'sql', $sql, 'params', $params);
		
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    const STATUS_CONFIRMED = 6;
    const STATUS_SETTLED = 8;
    const STATUS_VOIDED = 9;

    const RESULT_WIN = 1;
    const RESULT_LOSE = 2;
    const RESULT_DRAW_NO_BET = 3;
    const RESULT_REFUND = 6;
    const RESULT_BET_HAS_NOT_BEEN_SETTLED = 9;

    private function getResultDefinition($code){
        switch ($code){
			case self::RESULT_WIN:
                return 'WIN';
				break;
            case self::RESULT_LOSE:
                return 'LOSE';
                break;
            case self::RESULT_DRAW_NO_BET:
                return 'DRAW_NO_BET';
                break;
            case self::RESULT_REFUND:
                return 'REFUND';
                break;
            case self::RESULT_BET_HAS_NOT_BEEN_SETTLED:
                return 'BET_HAS_NOT_BEEN_SETTLED';
                break;
            default:
                return 'UNKNOWN';
		}

    }

    private function getStatusDefinition($code){
        switch ($code){
			case self::STATUS_CONFIRMED:
                return 'CONFIRMED';
				break;
            case self::STATUS_SETTLED:
                return 'SETTLED';
                break;
            case self::STATUS_VOIDED:
                return 'VOIDED';
                break;
            default:
                return 'UNKNOWN';
		}

    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round_id'],
        ]; 
        $betDetails = [
            'bet_id' =>  $row['bet_id'],
            'odds' =>  $row['odds'],
            'event_id' =>  $row['event_id'],
            'event_display_date_time' =>  $row['event_display_date_time'],
            'status' =>  $this->getStatusDefinition($row['bet_status_id']),
            'result' =>  $this->getResultDefinition($row['bet_result_id']),
        ];

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
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $betDetails,
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

        $status=Game_logs::STATUS_PENDING;

        if($row['bet_status_id']==(int)self::STATUS_SETTLED){
            $status = Game_logs::STATUS_SETTLED;

        }elseif($row['bet_status_id']==(int)self::STATUS_VOIDED){
            $status = Game_logs::STATUS_VOID;
        }

        $row['status']=$status;
        
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
    
    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 503;
    }
}

/*end of file*/