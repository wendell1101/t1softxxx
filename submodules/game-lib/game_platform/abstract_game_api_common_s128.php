<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: S128
	* API docs: 
	*
	* @category Game_platform
	* @copyright 2013-2022 tot
	* @integrator @bermar.php.ph
**/

abstract class Abstract_game_api_common_s128 extends Abstract_game_api {
	const POST = 'POST';
    const GET = 'GET';

    const SUCCESS_CODE = '00';
    const SUCCESS_STATUS_TEXT = 'OK';

    const USER_NOT_FOUND = '61.01';
    const API_KEY_NOT_FOUND = '61.02';
    const GENERAL_ERROR = 'All Other Code';
    
    

    private $original_gamelogs_table = 's128_game_logs';

	# Fields in ogplus_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
		'status',
		'stake_money',
		'processed_datetime',
		'game_code',
		'balance_close',
		'balance_close1',
		'payout',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
		'processed_datetime',
		'status',
		'winloss',
		'payout',
		'match_date',
		'fight_datetime',
		'arena_code',
		'after_balance1'
    ];

    # Fields in game_logs we want to detect changes for merge and when md5_sum
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',        
        'game_code',
        'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at',
		'bet_details',
		'status',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'after_balance',
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];
	
	const API_syncUnsettledRecords = "syncUnsettledRecords";
	const API_syncSettledRecords = "syncSettledRecords";

	public function __construct() {
		parent::__construct();

        $this->api_url = $this->getSystemInfo('url');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url', '/api/auth_login.aspx');
        $this->mobile_game_launch_url = $this->getSystemInfo('mobile_game_launch_url', '/api/cash/auth');
        $this->agent_code = $this->getSystemInfo('agent_code');
        $this->api_key = $this->getSystemInfo('key');
        $this->data_api_url = $this->getSystemInfo('data_api_url');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes');
		$this->language = $this->getSystemInfo('language','en-US');
		$this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 0);

		$this->sync_settled = $this->getSystemInfo('sync_settled', true);
		$this->sync_unsettled = $this->getSystemInfo('sync_unsettled', true);
		

		$this->URI_MAP = array(
            self::API_createPlayer => '/get_session_id.aspx',
            self::API_depositToGame => "/deposit.aspx",
            self::API_isPlayerExist => "/get_balance.aspx",
            self::API_queryPlayerBalance => "/get_balance.aspx",
            self::API_withdrawFromGame => "/withdraw.aspx",
            self::API_queryTransaction => '/check_transfer.aspx',       
	        self::API_blockPlayer => '/suspend_player.aspx',
            self::API_login => "/get_session_id.aspx", 
			self::API_syncUnsettledRecords => '/get_cockfight_open_ticket_2.aspx',
			self::API_syncSettledRecords => '/get_cockfight_processed_ticket_2.aspx',
			
	        
	        
	        self::API_queryForwardGame => "",
			self::API_syncGameRecords => '',
			
		);

		$this->METHOD_MAP = array(
            self::API_createPlayer => self::POST,
            self::API_depositToGame => self::POST,
            self::API_isPlayerExist => self::POST,
            self::API_queryPlayerBalance => self::POST,
            self::API_withdrawFromGame => self::POST,
            self::API_queryTransaction => self::POST,
            self::API_login => self::POST,			
			self::API_syncGameRecords => self::POST,	
			self::API_syncUnsettledRecords => self::POST,	
			self::API_syncSettledRecords => self::POST,			
		);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		$this->CI->utils->debug_log('S128 (generateUrl)', $apiName, $params);		

		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		$this->method = $this->METHOD_MAP[$apiName];

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
		}

		$this->CI->utils->debug_log('S128 (generateUrl) :', $this->method, $url);

		return $url;
	}

	protected function getHttpHeaders($params){
		$this->CI->utils->debug_log('S128 (getHttpHeaders)', $params);		

		$headers = [];
		$headers['Content-Type'] = 'application/x-www-form-urlencoded';//for data requests
		
		return $headers;
	}

	protected function customHttpCall($ch, $params) {	
		$this->CI->utils->debug_log('S128 (customHttpCall)', $this->method);		

		switch ($this->method){
            case self::POST:

				curl_setopt($ch, CURLOPT_POST, TRUE);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				break;
		}
		$this->utils->debug_log('S128 (customHttpCall) ', $this->method);
	}

	public function processResultBoolean($responseResultId, $resultArr, $apiName, $playerName = null) {
		$this->CI->utils->debug_log('S128 (processResultBoolean)', $resultArr);	

        $success = false;
        
        switch ($apiName) {
            case 'depositToGame':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){
                    $success = true;
                }
            case 'createPlayer':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){
                    $success = true;
                }
                
            break;
            case 'queryTransaction':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){//found 1 record
                    $success = true;
                }
            break;
            case 'blockPlayer':
                if(isset($resultArr['status_code']) && 
                isset($resultArr['status_code'])==self::SUCCESS_CODE){//found 1 record
                    $success = true;
                }
            break;
            case 'unblockPlayer':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){//found 1 record
                    $success = true;
                }
            break;
            case 'withdrawFromGame':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){
                    $success = true;
                }
            break;
            case 'login':
                
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){
                    $success = true;
                }
            break;
            case 'queryPlayerBalance':
            case 'isPlayerExist':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){
                    $success = true;
                }
            break;
            case 'syncGameRecords':
                
			break;
			case 'syncSettledRecords':
            case 'syncUnsettledRecords':
                if(isset($resultArr['status_code']) && 
                $resultArr['status_code']==self::SUCCESS_CODE){
                    $success = true;
                }
            break;
			default:
                $success = false;
                break;
		}
        
        if(!$success){
            $this->CI->utils->error_log('S128 got error ', 
            'apiName', $apiName, 
            'responseResultId', $responseResultId, 
            'playerName', $playerName, 
            'result', $resultArr);
        }

		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$this->CI->utils->debug_log('S128 (createPlayer)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => 0,
            'apiName' => self::API_createPlayer
            
        );

		$params = array(
            'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,
            'login_id' => $gameUsername,
            'name' => $gameUsername,
			//'amount' => 0,			
			//'ref_no' => $external_transaction_id
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$this->CI->utils->debug_log('S128 (processResultForCreatePlayer)', $params);	

        $responseResultId = $this->getResponseResultIdFromParams($params);
        
        $resultXml = $this->getResultXmlFromParams($params);		
        $this->CI->utils->debug_log('S128 (processResultForCreatePlayer) resultXml', $resultXml);	
        $arrayResult = json_decode(json_encode($resultXml),true);        
        $this->CI->utils->debug_log('S128 (processResultForCreatePlayer) arrayResult', $arrayResult);	
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$apiName = $this->getVariableFromContext($params, 'apiName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $apiName, $gameUsername);
		$result = array(
			'player' => $gameUsername,
			'exists' => false
		);

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$this->CI->utils->debug_log('S128 (isPlayerExist)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId = $this->getPlayerIdFromUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
            'apiName' => self::API_isPlayerExist,
            'playerId' => $playerId
		);

		$params = array(
			'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,
            'login_id' => $gameUsername,            
        );
        
		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$this->CI->utils->debug_log('S128 (processResultForIsPlayerExist)', $params);	

        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$apiName = $this->getVariableFromContext($params, 'apiName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);		
		$arrayResult = json_decode(json_encode($resultXml),true);        
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $apiName, $gameUsername);
		$result = array();
    
		if($success){
			$result['exists'] = true;
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		}else{			
			if(@$arrayResult['status_code'] == self::USER_NOT_FOUND){
				$success = true;//meaning request success.
				$result['exists'] = false;				
			}else{
				$result['exists'] = null;
			}
		}

		return array($success, $result);
    }

	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('S128 (isPlayerExist)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
            'apiName' => self::API_queryPlayerBalance
		);

		$params = array(
			'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,
            'login_id' => $gameUsername,            
        );
        
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('S128 (processResultForQueryPlayerBalance)', $params);	

        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiName = $this->getVariableFromContext($params, 'apiName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);		
		$arrayResult = json_decode(json_encode($resultXml),true);        
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $apiName, $gameUsername);
		$result = [];

		if(!isset($arrayResult['balance'])){
			$success=false;
		}

		if($success){
			$balance = $this->gameAmountToDB($arrayResult['balance']);
			$result['balance'] = $balance;
		}

		return array($success, $result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('S128 (depositToGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id,
            'apiName' => self::API_depositToGame
            
        );

		$params = array(
            'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,
            'login_id' => $gameUsername,
            'name' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),			
			'ref_no' => $external_transaction_id
        );
        
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
        $this->CI->utils->debug_log('S128 (processResultForDepositToGame)', $params);	
        
        $playerName = $this->getVariableFromContext($params,'playerName');
        $apiName = $this->getVariableFromContext($params,'apiName');
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;

            $returnedTransferId = @$arrayResult['trans_id'];
            if($returnedTransferId<>$external_transaction_id){
                $this->CI->utils->debug_log('S128 (processResultForDepositToGame) ALERT trans_id mismatch!!!! ', 
                'returnedTransferId', $returnedTransferId, 
                'expectedTransferId', $external_transaction_id);	
            }
            
        }else{
        	$status_code = @$arrayResult['status_code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($status_code);
        }

        return array($success, $result);
	}

	private function getReasons($error_msg){
		switch ($error_msg) {
			case '61.01':
				return self::REASON_INVALID_KEY;
				break;
            case '61.01a':
                return self::REASON_ACCOUNT_NOT_EXIST;
                break;
            case '61.02':
                return self::REASON_TRANSACTION_NOT_FOUND;
                break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('S128 (withdrawFromGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id,
            'apiName' => self::API_withdrawFromGame
        );

		$params = array(
            'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,
            'login_id' => $gameUsername,
            'name' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),			
			'ref_no' => $external_transaction_id
        );
        
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$this->CI->utils->debug_log('S128 (processResultForWithdrawFromGame)', $params);	

		$playerName = $this->getVariableFromContext($params,'playerName');
        $apiName = $this->getVariableFromContext($params,'apiName');
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;

            $returnedTransferId = @$arrayResult['trans_id'];
            if($returnedTransferId<>$external_transaction_id){
                $this->CI->utils->debug_log('S128 (processResultForWithdrawFromGame) ALERT trans_id mismatch!!!! ', 
                'returnedTransferId', $returnedTransferId, 
                'expectedTransferId', $external_transaction_id);	
            }
            
        }else{
        	$status_code = @$arrayResult['status_code'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($status_code);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
		$this->CI->utils->debug_log('S128 (queryTransaction)', $transactionId, $extra);	
		
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
            'apiName' => self::API_queryTransaction
		);

		$params = array(
            'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,           		
			'ref_no' => $transactionId
        );

        $this->data_api = TRUE;
        
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$this->CI->utils->debug_log('S128 (processResultForQueryTransaction)', $params);	

		$playerName = $this->getVariableFromContext($params,'playerName');
        $apiName = $this->getVariableFromContext($params,'apiName');
        $external_transaction_id = $this->getVariableFromContext($params,'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){			
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $foundCount = (int)@$arrayResult['found'];
			if($foundCount<>1){
                //found morethan 1 transfer with specified ID
				$result['reason_id'] = self::REASON_UNKNOWN;
			    $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
		} else {
			$result['reason_id'] = self::REASON_UNKNOWN;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}
    
    public function blockPlayer($playerName) {
		$this->CI->utils->debug_log('S128 (blockPlayer)', $playerName);	
		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processBlockPlayer',
			'gameUsername' => $gameUsername,
            'apiName' => self::API_blockPlayer
		);

		$params = array(
            'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,           		
			'login_id' => $gameUsername
        );
        
		return $this->callApi(self::API_blockPlayer, $params, $context);
	}

	public function processBlockPlayer($params){
		$this->CI->utils->debug_log('S128 (processBlockPlayer)', $params);	

		$playerName = $this->getVariableFromContext($params,'playerName');
        $apiName = $this->getVariableFromContext($params,'apiName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,$playerName);

		$result = [];

		return array($success, $result);
	}
    
    public function unblockPlayer($playerName) {
		$this->CI->utils->debug_log('S128 (unblockPlayer)', $playerName);	
		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processUnblockPlayer',
			'gameUsername' => $gameUsername,
            'apiName' => self::API_unblockPlayer
		);

		$params = array(
            'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,           		
			'login_id' => $gameUsername
        );
        
		return $this->callApi(self::API_unblockPlayer, $params, $context);
	}

	public function processUnblockPlayer($params){
		$this->CI->utils->debug_log('S128 (processUnblockPlayer)', $params);	

		$playerName = $this->getVariableFromContext($params,'playerName');
        $apiName = $this->getVariableFromContext($params,'apiName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $arrayResult = json_decode(json_encode($resultXml),true);
        $success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,$playerName);

		$result = [];

		return array($success, $result);
	}

	public function getLauncherLanguage($language){
		$lang='';
        switch ($language) {
            case 1:
            case 'en':
            case 'en-us':
            case 'en-US':                
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'cn':
            case 'zh-cn':
            case 'zh-CN':
                $lang = 'zh-CN'; // chinese
                break;
            case 3:
            case 'id':
            case 'id-id':
            case 'id-ID':
                $lang = 'id-ID'; // chinese
                break;
            case 4:
            case 'vi':
            case 'vi-vn':
            case 'vi-VN':
                $lang = 'vi-VN'; // chinese
                break;
            case 5:
            case 'ko':
            case 'ko-kr':
            case 'ko-KR':
                $lang = 'ko-KR'; // korean
				break;
			case 6:
            case 'th':
            case 'th-th':
            case 'th-TH':
                $lang = 'th-TH'; // korean
                break;
            case 'es':
            case 'es-es':
            case 'es-ES':
                $lang = 'es-ES'; // korean
                break;
            default:
                $lang = 'en-US'; // default as english
                break;
        }
        return $lang;
	}

    public function login($playerName, $password = null, $extra = null) {
		$this->CI->utils->debug_log('S128 (login)', $playerName);	
		
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$password = $this->getPasswordByGameUsername($gameUsername);
		
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'apiName' => self::API_login
        );

		$params = array(
			'api_key' => $this->api_key,
            'agent_code' => $this->agent_code,           		
			'login_id' => $gameUsername,           		
			'name' => $gameUsername
        );
        
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$this->CI->utils->debug_log('S128 (processResultForLogin)', $params);	

		$playerName = $this->getVariableFromContext($params,'playerName');
        $apiName = $this->getVariableFromContext($params,'apiName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$this->CI->utils->debug_log('S128 (processResultForLogin) resultXml', $resultXml);	
		$arrayResult = json_decode(json_encode($resultXml),true);		
		$this->CI->utils->debug_log('S128 (processResultForLogin) arrayResult', $arrayResult);	
		$success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,$playerName);
		$result = [];
        if($success){
			$result = $arrayResult;
		}
		return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra) {
		$this->CI->utils->debug_log('S128 (queryForwardGame)', $playerName, $extra);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		
		$returnArr = $this->login($playerName);
		
		$this->CI->utils->debug_log('S128 (queryForwardGame)', 'returnArr',$returnArr);	
        $data = [];
        

        $lang = '';
        if(isset($extra['language']) && !empty($extra['language'])){
            $lang = $this->getLauncherLanguage($extra['language']);
        }else{
            $lang = $this->getLauncherLanguage($this->language);
        }
        
        $url = $this->game_launch_url;
        if(isset($extra['is_mobile']) && $extra['is_mobile']){
            $url = $this->mobile_game_launch_url;
		}

		$data = [];
		$data['session_id'] = null;       
		$data['lang'] = $lang;    
		$data['login_id'] = $gameUsername;

		if($returnArr['success']){
            $data['session_id'] = @$returnArr['session_id'];       
            $data['lang'] = $lang;    
            $data['login_id'] = $gameUsername;			
        }
        
        return ['success'=>false,'url'=>$url,'data'=>$data];
	}

    public function syncOriginalGameLogs($token) {
		$this->CI->utils->debug_log('S128 (syncOriginalGameLogs)', $token, $this->original_gamelogs_table);	
		$result = [];

		if($this->sync_unsettled){
			$result[] = $this->syncUnsettledRecords($token);
		}
		
		if($this->sync_settled){
			$result[] = $this->syncSettledRecords($token);
		}


		return ['success'=>true, "results"=>$result];
	}



    public function syncUnsettledRecords($token) {
		$this->CI->utils->debug_log('S128 (syncUnsettledRecords)', $token, $this->original_gamelogs_table);	

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => null,
			'endDate' => null,
			'apiName' => self::API_syncUnsettledRecords
		);

		$params = array(
			'api_key' => $this->api_key,
			'agent_code' => $this->agent_code,      
		);
		
		$result[] = $this->callApi(self::API_syncUnsettledRecords, $params, $context);

		return array("success" => true, "results"=>$result);
	}

	public function syncSettledRecords($token) {
		$this->CI->utils->debug_log('S128 (syncUnsettledRecords)', $token, $this->original_gamelogs_table);	

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i')));

    	$queryDateTimeStart = $startDateTime->format("Y-m-d H:i");
		$queryDateTimeEnd = $startDateTime->modify($this->sync_time_interval)->format('Y-m-d H:i');
    	$queryDateTimeMax = $endDateTime->format("Y-m-d H:i");
    	# Query Exact end
    	if($queryDateTimeEnd > $queryDateTimeMax){
    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i");
    	}

    	while ($queryDateTimeMax  > $queryDateTimeStart) {
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $queryDateTimeStart,
				'endDate' => $queryDateTimeEnd,
				'apiName' => self::API_syncSettledRecords
			);

			$params = array(
				'api_key' => $this->api_key,
				'agent_code' => $this->agent_code,           		
				'start_datetime' => $queryDateTimeStart,           		
				'end_datetime' => $queryDateTimeEnd
			);
            
			$result[] = $this->callApi(self::API_syncSettledRecords, $params, $context);
			sleep($this->sync_sleep_time);
			$queryDateTimeStart = $queryDateTimeEnd;
    		$queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i');
    		# Query Exact end
    		if($queryDateTimeEnd > $queryDateTimeMax){
	    		$queryDateTimeEnd = $endDateTime->format("Y-m-d H:i");
	    	}
		}		
		
		return array("success" => true, "results"=>$result);
	}


	private function rebuildGameRecords(&$gameRecords,$extra){

		$this->CI->utils->debug_log('S128 (rebuildGameRecords)', $gameRecords);

		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$new_gameRecords[$index] = $record;
			$new_gameRecords[$index]['external_uniqueid'] = isset($record['ticket_id'])?$record['ticket_id']:null;
			$new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
			$new_gameRecords[$index]['status'] = isset($record['status_'])?$record['status_']:null;
			$new_gameRecords[$index]['processed_datetime'] = isset($record['processed_datetime'])?$record['processed_datetime']:null;			
			$new_gameRecords[$index]['balance_close1'] = isset($record['balance_close1'])?$record['balance_close1']:null;
			$new_gameRecords[$index]['payout'] = isset($record['payout'])?$record['payout']:0;
			
			$new_gameRecords[$index]['game_code'] = $record['arena_code'];
			
			if(isset($new_gameRecords[$index]['status_'])){
				unset($new_gameRecords[$index]['status_']);
			}
        }

		$gameRecords = $new_gameRecords;
		unset($new_gameRecords);
		return true;
	}

	public function processData($data, $apiName){
		if(empty($data) || !isset($data['data'])){
			return false;
		}else{
			if(empty($data['data'])){
				return [];
			}
		}

		$this->row_delimiter = $this->getSystemInfo('row_delimiter', '|');
		$this->column_delimiter = $this->getSystemInfo('column_delimiter', ',');

		$result = [];
		$explodeToRows = explode($this->row_delimiter,$data['data']);

		if($apiName==self::API_syncSettledRecords){
			$heads = ['ticket_id','login_id','arena_code','arena_name_cn','match_no','match_type','match_date','fight_no','fight_datetime','meron_cock','meron_cock_cn','wala_cock','wala_cock_cn','bet_on','odds_type','odds_asked','odds_given','stake','stake_money','balance_open','balance_close','created_datetime','fight_result','status_','winloss','comm_earned','payout','balance_open1','balance_close1','processed_datetime'];
		}else{
			$heads = ['ticket_id','login_id','arena_code','arena_name_cn','match_no','match_type','match_date','fight_no','fight_datetime','meron_cock','meron_cock_cn','wala_cock','wala_cock_cn','bet_on','odds_type','odds_asked','odds_given','stake','stake_money','balance_open','balance_close','created_datetime'];
		}
		
		foreach($explodeToRows as $key => $row){
			$temp = [];
			$explodeRow = explode($this->column_delimiter,$row);
			if(count($explodeRow) <> count($heads)){
				$this->CI->utils->error_log('S128 (processData) data mismatch from expected field count', 
				'expected', count($heads),
				'actual', count($explodeRow),
				'data', $row);
				continue;
			}
			foreach($explodeRow as $fieldKey => $value){
				$fieldname = isset($heads[$fieldKey])?$heads[$fieldKey]:$fieldKey;
				$temp[$fieldname] = $value;
			}
			$result[] = $temp;
		}
		
		return $result;
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->utils->debug_log('S128 (processResultForSyncOriginalGameLogs)');

		$this->CI->load->model('original_game_logs_model');		
		$apiName = $this->getVariableFromContext($params,'apiName');
		$startDate = $this->getVariableFromContext($params,'startDate');
		$endDate = $this->getVariableFromContext($params,'endDate');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultXml = $this->getResultXmlFromParams($params);
		$this->CI->utils->debug_log('S128 (processResultForSyncOriginalGameLogs) resultXml', $resultXml);	
		$arrayResult = json_decode(json_encode($resultXml),true);		
		$this->CI->utils->debug_log('S128 (processResultForSyncOriginalGameLogs) arrayResult', $arrayResult, $startDate, $endDate);		
		$success = $this->processResultBoolean($responseResultId,$arrayResult,$apiName,null);

		//process data pipe and comma delimeted
		$gameRecords = $this->processData($arrayResult, $apiName);
		$this->CI->utils->debug_log('S128 (processResultForSyncOriginalGameLogs) gameRecords', $gameRecords);		
		$result = array('data_count'=>0);

		$gameRecords = !empty($gameRecords)?$gameRecords:[];
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
			//$sqlTime = '`original`.`created_datetime` >= ? AND `original`.`created_datetime` <= ?';
			$sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';
        }
        $this->CI->utils->debug_log('S128 sqlTime ===>', $sqlTime);


        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.response_result_id,
	original.match_no as `table`,
	original.match_no as round,
	original.login_id as username,

	original.match_type as match_type,
	original.match_date as match_date,
	original.fight_no as fight_no,
	original.ticket_id as ticket_id,
	original.meron_cock as meron_cock,
	original.meron_cock_cn as meron_cock_cn,
	original.wala_cock as wala_cock,
	original.wala_cock_cn as wala_cock_cn,
	original.fight_result as fight_result,
	original.processed_datetime as processed_datetime,

	original.stake_money as bet_amount,
	original.stake_money as valid_bet,
	original.balance_close as after_balance,
	original.balance_close1 as after_balance1,
	original.payout as result_amount,
	original.created_datetime as start_at,
	original.processed_datetime as end_at,
	original.created_datetime as bet_at,
	
	
	
	original.status as game_status,


	original.updated_at,
	original.external_uniqueid,
	original.md5_sum,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_code as game_code,
	gd.game_name as game_name,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.arena_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.login_id = game_provider_auth.login_name
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
		
		$this->CI->utils->debug_log('S128 (queryOriginalGameLogs) sql:', $sql);
		
		$this->CI->utils->debug_log('S128 (queryOriginalGameLogs) params: ', $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
		//$row['game_status'] = '';
        $extra = [
            'table' =>  $row['table'],
		];
		
		$payout = floatval($row['result_amount']);
		$bet = floatval($row['valid_bet']);
		$row['result_amount'] = $payout-$bet;

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
                'bet_amount' => floatval($row['valid_bet']),
                'result_amount' => floatval($row['result_amount']),
                'bet_for_cashback' => floatval($row['valid_bet']),
                'real_betting_amount' => floatval($row['bet_amount']),
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => (isset($row['game_status']) && !empty($row['game_status']) ? $row['after_balance1'] : $row['after_balance'])
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
			'status' =>  (isset($row['game_status']) && !empty($row['game_status']) ? $this->getStatus($row['game_status']) : Game_logs::STATUS_PENDING),
			//'status' =>  Game_logs::STATUS_PENDING,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
	}
	
	public function getStatus($status){
		
		$status = trim($status);
		switch ($status) {
			case 'WIN':
			case 'LOSE':			
				return Game_logs::STATUS_SETTLED;
				break;
			case 'CANCEL':
                return Game_logs::STATUS_CANCELLED;
                break;
			case 'VOID':
                return Game_logs::STATUS_VOID;
                break;
			case 'REFUND':
				return Game_logs::STATUS_REFUND;
                break;
			default:
                return Game_logs::STATUS_PENDING;
                break;
		}
	}

    public function preprocessOriginalRowForGameLogs(array &$row){
		//var_dump($row);exit;
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = (isset($row['game_status']) && !empty($row['game_status']) ? $this->getStatus($row['game_status']) : Game_logs::STATUS_PENDING);
        $bet_details = $this->processBetDetails($row);
		$row['bet_details'] = $bet_details;
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

	private function processBetDetails($gameRecords) {
		$bet_details = array();
		if(!empty($gameRecords)) {
			$bet_details = array(
				'match_no' => $gameRecords['table'],
				'match_type' => $gameRecords['match_type'],
				'fight_no' => $gameRecords['fight_no'],
				'status' => $gameRecords['status'],
				'ticket_id' => $gameRecords['ticket_id'],
				'meron_cock' => $gameRecords['meron_cock'],
				'meron_cock_cn' => $gameRecords['meron_cock_cn'],
				'wala_cock' => $gameRecords['wala_cock'],
				'wala_cock_cn' => $gameRecords['wala_cock_cn'],
				'ticket_id' => $gameRecords['fight_result'],
				'match_date' => $gameRecords['match_date'],
				'processed_datetime' => $gameRecords['processed_datetime'],
			);

			return $bet_details;
		}

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

}

/*end of file*/