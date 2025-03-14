<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Vivogaming Gaming 
* Wallet Type: Seamless
* Asian Brand: 
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @bermar.php.ph

    Related File
    -routes.php
    -amb_service_api.php
**/

abstract class Abstract_game_api_common_amb_seamless extends Abstract_game_api {

    //OGP-24370
    public $sync_game_records_limit;

    const POST = 'POST';
    const GET = 'GET';   

    const API_SUCCESS = 0;
    const API_ERROR_DUPLICATE_PLAYER = 902;

    const ITERATIONS = 1000;

    const MD5_FIELDS_FOR_ORIGINAL = ['bet', 'winlose','turnover','timestamp'];
    const MD5_FLOAT_AMOUNT_FIELDS = ['bet', 'winlose','turnover'];
    
    const MD5_FIELDS_FOR_MERGE=['bet_amount','result_amount','game_name'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','result_amount'];

    const API_queryForwardGameLobby = "queryForwardGameLobby";
    const API_queryGameListFromGameProvider = "queryGameListFromGameProvider";
    const API_checkBetStatus = 'checkBetStatus';
    const API_triggerCancelRound = 'triggerCancelRound';

    const API_triggerInternalCancelRound = 'triggerInternalCancelRound';

    public function __construct() {
        parent::__construct();

        $this->CI->load->model(array('original_game_logs_model'));

        $this->api_url              = $this->getSystemInfo('url');
        $this->game_launch_url      = $this->getSystemInfo('game_launch_url');
        $this->agent_id             = $this->getSystemInfo('agent_id');
        $this->home_url             = $this->getSystemInfo('home_url', '/');
        $this->use_referrer         = $this->getSystemInfo('use_referrer', false);
        $this->secret               = $this->getSystemInfo('key');
        $this->currency             = $this->getSystemInfo('currency');
        $this->sync_time_interval   = $this->getSystemInfo('sync_time_interval', '+10 minutes');
        $this->sync_sleep_time      = $this->getSystemInfo('sync_sleep_time', 0);

        $this->enable_exit_button   = $this->getSystemInfo('enable_exit_button', 'enable');
        $this->enable_home_button   = $this->getSystemInfo('enable_home_button', 'enable');

        $this->use_transaction_for_game_logs = $this->getSystemInfo('use_transaction_for_game_logs', false);

        //testing service api
        $this->data_to_encrypt              = $this->getSystemInfo('data_to_encrypt', []);
        $this->test_delay_balance_response  = $this->getSystemInfo('test_delay_balance_response', 0);
        $this->test_delay_bet_response      = $this->getSystemInfo('test_delay_bet_response', 0);
        $this->test_delay_payout_response   = $this->getSystemInfo('test_delay_payout_response', 0);
        $this->test_delay_cancel_response   = $this->getSystemInfo('test_delay_cancel_response', 0);

        $this->trigger_error_balance_response  = $this->getSystemInfo('trigger_error_balance_response', 0);
        $this->trigger_error_bet_response      = $this->getSystemInfo('trigger_error_bet_response', 0);
        $this->trigger_error_payout_response   = $this->getSystemInfo('trigger_error_payout_response', 0);
        $this->trigger_error_cancel_response   = $this->getSystemInfo('trigger_error_cancel_response', 0);  
        $this->mm_channel                      = $this->getSystemInfo('mm_channel', 'sexycasino_seamless_balance_monitoring'); 
        $this->enable_mm_channel_nofifications = $this->getSystemInfo('enable_mm_channel_nofifications', false);       

        $this->cancel_callback_url      = $this->getSystemInfo('cancel_callback_url', 'http://admin.og.local/amb_service_api/5849/cancel');

        //OGP-24370
        $this->sync_game_records_limit = $this->getSystemInfo('sync_game_records_limit', 100);  

        $this->URI_MAP = array(
            self::API_createPlayer => '/seamless/create',        
            self::API_queryForwardGame => '/seamless/launch/game',
            self::API_queryForwardGameLobby => '/seamless/launch', 
            self::API_queryGameListFromGameProvider => '/seamless/games',           
            self::API_syncGameRecords => '/seamless/report/time/ten-minutes',        
            self::API_checkBetStatus => '/seamless/round-status',         
            self::API_triggerCancelRound => '/seamless/round-cancel-trigger',   
            self::API_triggerInternalCancelRound => '',    
                                   
        );
    
        $this->METHOD_MAP = array(
            self::API_createPlayer => self::POST,                    
            self::API_syncGameRecords => self::POST,                    
            self::API_queryForwardGame => self::POST,                    
            self::API_queryForwardGameLobby => self::POST,                    
            self::API_queryGameListFromGameProvider => self::GET,     
            self::API_checkBetStatus => self::POST,         
            self::API_triggerCancelRound => self::POST,         
            self::API_triggerInternalCancelRound => self::POST,            
        );        
        
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }
    
	protected function getMethodName($apiName){		
		return (isset($this->METHOD_MAP[$apiName])?$this->METHOD_MAP[$apiName]:self::GET);
	}  

	public function generateUrl($apiName, $params) {
		$this->CI->utils->debug_log('AMB SEAMLESS (generateUrl)', $apiName, $params);		

		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;		

		$this->method = $this->METHOD_MAP[$apiName];

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
        }
        
        if($apiName==self::API_triggerInternalCancelRound){
			$url = $this->cancel_callback_url;
		}

		$this->CI->utils->debug_log('AMB SEAMLESS (generateUrl) :', $this->method, $url);

		return $url;
	}
    
    public function generateSignature($params){
        $password = json_encode($params,JSON_UNESCAPED_SLASHES);        
        $hash = hash_pbkdf2("sha512", $password, $this->secret, self::ITERATIONS, 64, true);
        return base64_encode($hash);
    }
    
    public function getHttpHeaders($params) {
		$headers = array(
			"Content-Type" => "application/json",
            "x-amb-signature" => $this->generateSignature($params)
        );
            
        $this->CI->utils->debug_log('AMB SEAMLESS (getHttpHeaders)', $headers);
        return $headers;	     
	}

	protected function customHttpCall($ch, $params) {	
		switch ($this->method){
			case self::POST:
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));				
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
		}
		$this->utils->debug_log('AMB SEAMLESS (customHttpCall) ', $this->method, 'params', $params);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$this->CI->utils->debug_log('AMB SEAMLESS (createPlayer)', $playerName);	

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
			'apiName' => self::API_createPlayer
		);

		$params = array(
			'username' => $gameUsername, # English letters and number (No special characters) min: 5 max: 22
			'password' => $password,
            'agent' => $this->agent_id
		);
        $this->method = self::POST;
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
        $resultArr = $this->getResultJsonFromParams($params);		
		$this->CI->utils->debug_log('AMB SEAMLESS (processResultForCreatePlayer)', $resultArr);	

		$responseResultId = $this->getResponseResultIdFromParams($params);
		
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;	        
        }
        
        if((isset($resultArr['status']['code']) && $resultArr['status']['code']==self::API_ERROR_DUPLICATE_PLAYER)){
            $success = $result['exists'] = true;
        }

		return array($success, $result);
    }
    
    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='`trans`.`created_at` >= ? AND `trans`.`created_at` <= ?';

        $status = Game_logs::STATUS_PENDING;

        $sql = <<<EOD
SELECT
    *    
FROM {$this->original_transactions_table} as trans
WHERE trans.trans_type = 'bet' and trans.`status` = ? AND
{$sqlTime};
EOD;

        $params=[
            $status,
            $dateFrom,
            $dateTo
		];
		
		$this->CI->utils->debug_log('AMB SEAMLESS (queryOriginalGameLogs)', 'params',$params);
		
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function getTransactionsTable(){
        return $this->original_transactions_table;
    }

	public function checkBetStatus($data) {
        //$playerId = $data['player_id'];
        $gameUsername = $data['username'];
        $roundId = $data['round_id'];
		$this->CI->utils->debug_log('AMB SEAMLESS (checkBetStatus)', $gameUsername, $roundId);			

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckBetStatus',
			'gameUsername' => $gameUsername,
			'roundId' => $roundId,
			'apiName' => self::API_checkBetStatus,
			'transaction' => $data
		);

		$params = array(
			'roundId' => $roundId,
            'agent' => $this->agent_id
		);
        $this->method = self::POST;
		return $this->callApi(self::API_checkBetStatus, $params, $context);
	}

	public function processResultForCheckBetStatus($params){		
        $resultArr = $this->getResultJsonFromParams($params);		
        $this->CI->utils->debug_log('AMB SEAMLESS (processResultForCreatePlayer)', $resultArr);	
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$roundId = $this->getVariableFromContext($params, 'roundId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
        $transaction = $this->getVariableFromContext($params, 'transaction');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'gameUsername' => $gameUsername,
			'round_id' => $roundId,
			'status' => null,
			'exists' => null,
			'trigger_cancel' => false
        );
        
        if(isset($resultArr['data']['status'])){
            $result['status'] = $resultArr['data']['status'];
            if($result['status']=='NO ROUNDID' || $result['status']=='NOT ROUNDID'){
                $result['exists']=false;

                $result['trigger_cancel']=true;

                //no record in provider to cancel round internally
                $cancelResponse=$this->triggerInternalCancelRound($transaction);
                $this->CI->utils->debug_log('AMB SEAMLESS (processResultForCheckBetStatus) triggerInternalCancelRound', $cancelResponse);
            }else{
                $result['exists']=true;
            }
        }
        
		return array($success, $result);
    }
    
    public function triggerInternalCancelRound($transaction){
        
        //API_triggerInternalCancelRound
        $this->CI->utils->debug_log('AMB SEAMLESS (triggerInternalCancelRound)', $transaction);		

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTriggerInternalCancelRound',
			'gameUsername' => $transaction['username'],
			'roundId' => $transaction['round_id'],
			'apiName' => self::API_triggerInternalCancelRound
        );
        
        $params = [
            'username'=>$transaction['username'],
            'agent'=>$this->agent_id,
            'game'=>$transaction['game'],
            'product'=>!empty($transaction['product'])?$transaction['product']:'Cancel Round Internally',
            'roundId'=>$transaction['round_id'],
            'amount'=>floatval($transaction['amount']),
            'currency'=>$transaction['currency'],
            'refId'=>'internal_'.$transaction['ref_id'],
            'timestamp'=>$this->getTime(),
            'is_internal'=>true,
        ];

        $this->method = self::POST;
		return $this->callApi(self::API_triggerInternalCancelRound, $params, $context);
    }

    public function processResultForTriggerInternalCancelRound($params){
        $resultArr = $this->getResultJsonFromParams($params);		
        
        $this->CI->utils->debug_log('AMB SEAMLESS (processResultForTriggerCancelRound)', $params, $resultArr);	
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$roundId = $this->getVariableFromContext($params, 'roundId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'gameUsername' => $gameUsername,
			'round_id' => $roundId,
			'status' => null,
			'exists' => null,
			'triggered_cancel' => false
        );
        $success = false;
        if(isset($resultArr['status']) && isset($resultArr['status']['code'])){            
            if($resultArr['status']['code']==0){
                $result['status']=true;
                $result['triggered_cancel']=true;
                $success = true;
            }
        }

		return array($success, $result);
	}

	/*public function triggerCancelRound($gameUsername, $roundId) {
		$this->CI->utils->debug_log('AMB SEAMLESS (checkBetStatus)', $gameUsername, $roundId);			

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUsername' => $gameUsername,
			'roundId' => $roundId,
			'apiName' => self::API_triggerCancelRound
		);

		$params = array(
			'roundId' => $roundId,
            'agent' => $this->agent_id 
		);
        $this->method = self::POST;
		return $this->callApi(self::API_triggerCancelRound, $params, $context);
	}

	public function processResultForTriggerCancelRound($params){
		$resultArr = $this->getResultJsonFromParams($params);		
        $this->CI->utils->debug_log('AMB SEAMLESS (processResultForTriggerCancelRound)', $resultArr);	
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$roundId = $this->getVariableFromContext($params, 'roundId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $apiName);
		$result = array(
			'gameUsername' => $gameUsername,
			'round_id' => $roundId,
			'status' => null,
			'exists' => null,
			'triggered_cancel' => false
        );
        
        if(isset($resultArr['data']['status'])){
            $result['status'] = $resultArr['data']['status'];
            if($result['status']=='NO ROUNDID'){
                $result['exists']=false;
            }else{
                $result['exists']=true;
            }
            //TODO check response
        }

		return array($success, $result);
	}*/

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $this->utils->debug_log("AMB SEAMLESS: (depositToGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $this->utils->debug_log("AMB SEAMLESS: (withdrawFromGame)");

        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = null) {
		$this->CI->utils->debug_log('AMB SEAMLESS (processResultBoolean)', 'resultArr', $resultArr);	
        
        $success = false;

        if(isset($resultArr['status']['code']) && $resultArr['status']['code']==self::API_SUCCESS){
            $success = true;
        }

        if($apiName==self::API_triggerInternalCancelRound){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('AMB SEAMLESS got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

    /**
     * Game - 開啟遊戲 Launch Game
     * 
     * @param   string 
     * @param   array
     * @return  array
     * 
     */
    public function queryForwardGame($playerName, $extra){ 
        $this->utils->debug_log("AMB SEAMLESS SEAMLESS: (queryForwardGame)", 'playerName', $playerName, 'extra', $extra);   

        $lobbyurl = $this->getReturnUrl($extra);  
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $apiName = self::API_queryForwardGame;

        $params = array(            
            'username' => $gameUsername,                        
            'backLink' => $lobbyurl,
            'agent' => $this->agent_id
        );

        //will launch lobby if game code not provided
        if(isset($extra['game_code']) && !empty($extra['game_code'])){
            $params['gameId'] = $extra['game_code'];
        }else{
            $apiName = self::API_queryForwardGameLobby;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'apiName' => $apiName,
        );

        $this->method = self::POST;
        return $this->callApi($apiName, $params, $context);
    } 

    public function processResultForQueryForwardGame($params){
		$this->CI->utils->debug_log('AMB SEAMLESS SEAMLESS (processResultForQueryForwardGame)', $params);	

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');		
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array('url'=>'');

		if($success){
            $url = @$resultArr['data']['url'];

            if(strpos($url, '?')===false){
                $url .= '?';
            }else{
                $url .= '&';
            }
            $url .= 'e='.$this->enable_exit_button.'&h='.$this->enable_home_button;

            $result['url'] = $url;
		}

		return array($success, $result);
    }
    
    public function queryGameListFromGameProvider($extra=null){ 
        $this->utils->debug_log("AMB SEAMLESS SEAMLESS: (queryGameList)");   

        $params = [];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $this->method = self::POST;
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    } 

    public function processResultForQueryGameListFromGameProvider($params){
		$this->CI->utils->debug_log('AMB SEAMLESS SEAMLESS (processResultForQueryForwardGame)', $params);	

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');		
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = $resultArr;

		return array($success, $result);
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
                $lang = 'zh'; // chinese
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
                $lang = 'en'; // korean
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

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function processResultForQueryTransaction($params) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName){
        $this->utils->debug_log("AMB SEAMLESS (queryPlayerBalance)");
        
        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        return $result;
    }
    
	public function syncOriginalGameLogs($token) {
        if($this->use_transaction_for_game_logs){
            return $this->returnUnimplemented();
        }

		$this->CI->utils->debug_log('AMB SEAMLESS (syncOriginalGameLogs)', $token, $this->original_gamelogs_table);	

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $dateRanges = $this->utils->generateDateTimeRange($startDateTime->format("Y-m-d H:i:s"), $endDateTime->format("Y-m-d H:i:s"), $this->sync_time_interval);

        //OGP-24370
        $limit = $this->sync_game_records_limit;

        foreach($dateRanges as $key => $range){
            $context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $range['from'],
				'endDate' => $range['to'],
                'limit' => $limit,
				'apiName' => self::API_syncGameRecords
			);

            //their API is +8
			$params = array(
                'agent' => $this->agent_id,
				'startDate' => date('Y-m-d\TH:i:s', strtotime($range['from'])),
				'endDate' => date('Y-m-d\TH:i:s', strtotime($range['to'])),
                'limit' => $limit //OGP-24370
			);
            $this->CI->utils->debug_log('AMB SEAMLESS syncOriginalGameLogs params',$params);			
			
			$result[] = $this->callApi(self::API_syncGameRecords, $params, $context);
			sleep($this->sync_sleep_time);
        }

		return array("success" => true, "results"=>$result);
	}

    public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->utils->debug_log('AMB SEAMLESS (processResultForSyncOriginalGameLogs)');

        $this->CI->load->model('original_game_logs_model');
		
        $resultArr = $this->getResultJsonFromParams($params);		
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$apiName = $this->getVariableFromContext($params, 'apiName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, null, $apiName);
		$result = array('data_count'=>0);

        $gameRecords = isset($resultArr['data'])&&!empty($resultArr['data'])?$resultArr['data']:[];
        
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
		}else{            
            $this->continue_loop = false;
        }

		return array($success, $result);
	}

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

	private function rebuildGameRecords(&$gameRecords,$extra){

		//$this->CI->utils->debug_log('AMB SEAMLESS (rebuildGameRecords)', $gameRecords);

		$new_gameRecords =[];
        if(isset($gameRecords['result'])){
            foreach($gameRecords['result'] as $index => $userRecord) {
                //user level
                foreach($userRecord['result']  as $index2 => $record){
                    //log level
                    $temp_new_gameRecords = [];                    
                    $temp_new_gameRecords['orig_id'] = isset($record['_id'])?$record['_id']:null;
                    $temp_new_gameRecords['username'] = isset($record['username'])?$record['username']:null;
                    $temp_new_gameRecords['game_name'] = isset($record['gameName'])?$record['gameName']:null;
                    $temp_new_gameRecords['categories'] = isset($record['categories'])?$record['categories']:null;
                    $temp_new_gameRecords['timestamp'] = isset($record['timestamp'])?$record['timestamp']:null;
                    $temp_new_gameRecords['timestamp_parsed'] = date('Y-m-d H:i:s', strtotime($record['timestamp']));
                    $temp_new_gameRecords['round_id'] = isset($record['roundId'])?$record['roundId']:null;
                    $temp_new_gameRecords['room_id'] = isset($record['roomId'])?$record['roomId']:null;
                    $temp_new_gameRecords['uuid'] = isset($record['uuid'])?$record['uuid']:null;
                    $temp_new_gameRecords['bet'] = isset($record['bet'])?$record['bet']:null;
                    $temp_new_gameRecords['turnover'] = isset($record['turnover'])?$record['turnover']:null;
                    $temp_new_gameRecords['winlose'] = isset($record['winlose'])?$record['winlose']:null;
                    $temp_new_gameRecords['commision'] = isset($record['commission'])?$record['commission']:null;
                    $temp_new_gameRecords['external_uniqueid'] = isset($record['uuid'])?$record['uuid']:null;
                    $temp_new_gameRecords['response_result_id'] = $extra['response_result_id'];			

                    $new_gameRecords[] = $temp_new_gameRecords;

                }
            }
        }
        $gameRecords = $new_gameRecords;
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
            $sqlTime = '`original`.`timestamp_parsed` >= ? AND `original`.`timestamp_parsed` <= ?';
        }
        $this->CI->utils->debug_log('AMB SEAMLESS sqlTime', $sqlTime);


        $sql = <<<EOD
SELECT
	original.id as sync_index,
    original.response_result_id,    
    original.external_uniqueid,
	original.timestamp_parsed as start_at,
    original.timestamp_parsed as end_at,
    original.timestamp_parsed as bet_at,
    original.updated_at as updated_at,
    original.round_id as round,
    original.username as username,
	original.round_id as table_id,
    original.turnover as bet_amount,
    original.bet as real_bet_amount,
	original.bet as valid_bet,
	original.winlose as result_amount,
	original.winlose as winlose,
    original.md5_sum,
    original.game_name game_id,
    game_provider_auth.player_id,
    gd.game_code as game_code,
    gd.game_name as game_name,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.game_name = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.username = game_provider_auth.login_name
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
		
		$this->CI->utils->debug_log('AMB SEAMLESS (queryOriginalGameLogs)', 'sql', $sql, 'params',$params);
		
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra = [
            'table' =>  $row['round'],
        ];

        //result amount = total bet - total win
        /*$totalWin = $row['winlose'] - $row['bet_amount'];

        $row['result_amount'] = $totalWin + $row['bet_amount'];*/

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if($row['real_bet_amount']<=0){
            //for slots they use turnover for bet, just make it if 0 then use the non 0 bet amount
            $row['real_bet_amount'] = $row['bet_amount'];
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
                'bet_for_cashback' => $row['real_bet_amount'],
                'real_betting_amount' => $row['real_bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
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
    }
    
    private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_id'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	private function getTime(){
		$date = new DateTime();
		return gmdate('Y-m-d\TH:i:s\Z', $date->format('U'));
	}

    public function queryTransactionByDateTime($startDate, $endDate){

$sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.trans_type trans_type,
t.raw_data extra_info
FROM {$this->original_transactions_table} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$this->getPlatformCode(),$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

}//end of class
