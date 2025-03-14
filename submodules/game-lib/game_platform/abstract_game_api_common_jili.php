<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: Jili Game
	* API docs: 
	*
	* @category Game_platform
	* @copyright 2013-2022 tot
	* @integrator @johmison.php.ph
**/

abstract class Abstract_game_api_common_jili extends Abstract_game_api {
    // free spin API
    public $free_spin_reference_id_prefix;
    public $free_spin_reference_id_length;
    public $free_spin_default_number_of_rounds;
    public $free_spin_default_game_ids;
    public $free_spin_default_bet_value;
    public $free_spin_default_validity_hours;

    public $timezone;

	const POST = 'POST';
	const GET = 'GET';

    private $original_gamelogs_table;


	# Fields in jili_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'transaction_id',
        'account',
        'game_category',
        'betting_date',
        'time_settled',
        'payoff_time',
        'status',
        'version_key',
        'type',
        'agent_id',
        'external_uniqueid',
		'bet_amount',
		'payoff_amount',
		'turnover',
        // 'result_id',
        // 'game_code',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
		'payoff_amount',
		'turnover',
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
        'bet_details'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'after_balance',
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    const FREE_SPIN_METHODS = [
        'createFreeRound',
        'cancelFreeRound',
        'queryFreeRound',
    ];

    const RESPONSE_CODE_FAILED = 'FAILED';

	private $api_url, $agentId, $agentKey, $hoursInterval, $data_api_url,$method, $data_api, $URI_MAP ,$METHOD_MAP, $sync_original_max_page_size, $demo_url, $home_url, $sync_time_interval;
	public function __construct() {
		parent::__construct();

        $this->original_gamelogs_table = 'jili_game_logs';
		$this->api_url = $this->getSystemInfo('url');
		$this->agentId = $this->getSystemInfo('agentId');
		$this->agentKey = $this->getSystemInfo('agentKey');
		$this->hoursInterval = $this->getSystemInfo('hoursInterval');
		$this->data_api_url = $this->getSystemInfo('data_api_url');
		$this->sync_original_max_page_size = $this->getSystemInfo('sync_original_max_page_size', 10000); 
		$this->demo_url = $this->getSystemInfo('demo_url', 'https://jiligames.com/plusplayer/PlusTrial');
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+15 minutes');
		$this->home_url = $this->getSystemInfo('home_link', false);
		$this->method = self::POST; # default as POST
		$this->data_api = false; # default as false
        $this->timezone = $this->getSystemInfo('timezone', 'America/Puerto_Rico'); // America/Puerto_Rico | Canada/Atlantic

        // free spin API
        $this->free_spin_reference_id_prefix = $this->getSystemInfo('free_spin_reference_id_prefix', 'FS');
        $this->free_spin_reference_id_length = $this->getSystemInfo('free_spin_reference_id_length', 12);
        $this->free_spin_default_number_of_rounds = $this->getSystemInfo('free_spin_default_number_of_rounds', 1);
        $this->free_spin_default_game_ids = $this->getSystemInfo('free_spin_default_game_ids', '');
        $this->free_spin_default_bet_value = $this->getSystemInfo('free_spin_default_bet_value', '');
        $this->free_spin_default_validity_hours = $this->getSystemInfo('free_spin_default_validity_hours', '+2 hours');
        $this->is_seamless_wallet = $this->getSystemInfo('seamless', false);

		$this->URI_MAP = array(
            self::API_createPlayer => '/CreateMember',
			self::API_queryPlayerBalance => "/GetMemberInfo",
			self::API_isPlayerExist => "/GetMemberInfo",
	        self::API_depositToGame => "/ExchangeTransferByAgentId",
	        self::API_withdrawFromGame => "/ExchangeTransferByAgentId",
	        self::API_queryForwardGame => "/LoginWithoutRedirect",
			self::API_syncGameRecords => '/GetBetRecordByTime',
            self::API_createFreeRoundBonus => '/CreateFreeSpin',
            self::API_cancelFreeRoundBonus => '/CancelFreeSpin',
            self::API_queryFreeRoundBonus => '/GetFreeSpinRecordByReferenceID',
            self::API_queryFreeRoundBonusGameRecords => '/GetFreeSpinRecordByTime',
		);

		$this->METHOD_MAP = array(
			self::API_createPlayer => self::POST,
			self::API_queryPlayerBalance => self::POST,
			self::API_isPlayerExist => self::POST,
	        self::API_depositToGame => self::POST,
	        self::API_withdrawFromGame => self::POST,
			self::API_syncGameRecords => self::POST,
	        self::API_queryForwardGame => self::GET,
	        self::API_createFreeRoundBonus => self::POST,
	        self::API_cancelFreeRoundBonus => self::POST,
	        self::API_queryFreeRoundBonus => self::POST,
	        self::API_queryFreeRoundBonusGameRecords => self::POST,
		);
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}
	
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
		);
		$params = array(
			'Account'=>  $gameUsername,
			'AgentId' => $this->agentId
		);
		$queryString = http_build_query($params);
		$params['Key'] = $this->generateKey($queryString);
		
		$this->method = self::POST;

		$response =  $this->callApi(self::API_createPlayer, $params, $context);
		return $response;
	}

	public function processResultForCreatePlayer($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
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

	//keyG = MD5(2019/01/01 + agentId + agentKey);
	//UTC-4 is used for the request time and response time.
	//https://docs.google.com/document/d/1-HD24VCdETduB0vIVchRsHohSZyF2Sgu/edit?pli=1
	public function generateKeyG(){
		$timezone = new DateTimeZone('Etc/GMT+4'); // UTC-4 timezone
		$dateObj = new DateTime("now", $timezone);
		$now = $dateObj->format('ymj');
		$agentId =  $this->agentId;
		$agentKey = $this->agentKey;
		$keyG = md5($now.$agentId.$agentKey);
		return $keyG;
	}

	public function generateKey($params=null){
        $params = urldecode($params);
		$randomString1 = $this->getSystemInfo('random1', '000000');
        $randomString2 = $this->getSystemInfo('random2', '000000');
		$md5 = md5($params.$this->generateKeyG());
		$key = $randomString1.$md5.$randomString2;
		return $key;
	}


	public function generateUrl($apiName, $params) {

		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		if($this->data_api){
			$url = $this->data_api_url . $apiUri;
		}
		$this->method = $this->METHOD_MAP[$apiName];

		if($this->method == self::GET&&!empty($params)){
			$url = $url . '?' . http_build_query($params);
		}

		return $url;
	}


	public function getHttpHeaders($params){
		$headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',				
        ];
		return $headers;
	}

	protected function customHttpCall($ch, $params) {	
		switch ($this->method){
            case self::POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);				
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				break;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null,$is_querytransaction= false) {
		$success = false;
		if(isset($resultArr['ErrorCode']) && 0 == $resultArr['ErrorCode']){
            $success = true;
        }

		if($is_querytransaction){
			$success = $resultArr['statusCode'] == 200?true:false;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('jili got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}


		return $success;
	}

	public function processResultForGenerateToken($params){
		$this->CI->utils->debug_log('jili (processResultForGenerateToken)', $params);	

		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
        $result=['api_token'=>null, 'api_token_timeout_datetime'=>null];

		if($success){
			$api_token = @$resultArr['data']['token'];
			# Token will be invalid each 30 minutes
			$token_timeout = new DateTime($this->utils->getNowForMysql());
			$token_timeout->modify("+29 minutes");
			//minus 30 seconds
			$api_token_timeout_datetime = $this->CI->utils->getMinusSecondsForMysql($this->utils->getNowForMysql(), 30);
			$result['api_token']=$api_token;
			$result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
		}
		return array($success,$result);
	}

	

	private function round_down($number, $precision = 3){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('jili (queryPlayerBalance)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'Accounts' => $gameUsername,
			'AgentId' => $this->agentId
		);

		$queryString = http_build_query($params);
		$params['Key'] = $this->generateKey($queryString);

		$this->method = self::POST;
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('jili (processResultForQueryPlayerBalance)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = [];

		if($success){
			$result['balance'] = $this->convertAmountToDB(floatval($resultArr['Data'][0]['Balance']));
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);
		$params = array(
			'Accounts' => $gameUsername,
			'AgentId' => $this->agentId
		);
		$queryString = http_build_query($params);
		$params['Key'] = $this->generateKey($queryString);

		$this->method = self::POST;
		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		// $response = false;
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		// $result = array();
		$result = ['response_result_id'=>$responseResultId, 'exists'=>null];
		
		$this->CI->utils->debug_log('jili (processResultForIsPlayerExist)', $resultArr, $result, $success);	
		/**
		 * 1 = online
		 * 2 = offline
		 * 3 = account does not exist
		 */
		if($resultArr['Data'][0]['Status'] != 3){
            $result['exists'] = true;
        }else{
            $result['exists'] = false;
        }

		return array($success, $result);
    }


	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('jili (depositToGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $this->dBtoGameAmount($amount),
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'Account' => $gameUsername,
			'TransactionId' => $external_transaction_id,
			'Amount' => $this->dBtoGameAmount($amount),
			'TransferType' => 2,
			'AgentId' => $this->agentId
		);

		$queryString = http_build_query($params);
		$params['Key'] = $this->generateKey($queryString);

		$this->method = self::POST;
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$this->CI->utils->debug_log('jili (processResultForDepositToGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
			$error_msg = @$resultArr['data']['message'];

			if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || $error_msg=="InternalServerError") && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons($error_msg);
			}
        }

        return array($success, $result);
	}

	private function getReasons($error_msg){
		switch ($error_msg) {
			case 'providerId not found.':
			case 'Invalid parameter.':
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 'username not found.':
			case 'The username must not be empty.':
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}


	/**
	 * Transfer Type
	 * 1 = GP to Vendor (all balance, ignored amount)
	 * 2 = Deposit
	 * 3 = Withdraw
	 */
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('jili (withdrawFromGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $this->dBtoGameAmount($amount),
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'Account' => $gameUsername,
			'TransactionId' => $external_transaction_id,
			'Amount' => $this->dBtoGameAmount($amount),
			'TransferType' => 3, 
			'AgentId' => $this->agentId
		);

		$queryString = http_build_query($params);
		$params['Key'] = $this->generateKey($queryString);

		$this->method = self::POST;
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$this->CI->utils->debug_log('jili (processResultForWithdrawFromGame)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
        	$error_msg = @$resultArr['data']['message'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($error_msg);
        }

        return array($success, $result);
	}

	public function queryForwardGame($playerName, $extra=null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->CI->load->library(['language_function']);
		$language = $this->getSystemInfo('language');

		$mode = isset($extra['game_mode'])?$extra['game_mode']:null;
		if(!isset($extra['game_code'])){
			$extra['game_code'] = 80; #default for lobby
		}
		if(in_array($mode, $this->demo_game_identifier)){
            $url = $this->demo_url . '/' . $extra['game_code'];
            $url .= '/'.$language;
			return ['success'=>true, 'url'=>$url];
		}
	
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
			'Account' => $gameUsername,
			'GameId' => $extra['game_code'],
			'Lang' => $this->getLauncherLanguage($language),
			'AgentId' => $this->agentId,
			
		);

		$queryString 		= http_build_query($params);
		$params['Key'] 		= $this->generateKey($queryString);

		if($this->home_url){
			$params['HomeUrl'] = $this->home_url;
		}

		$this->method = self::GET;
		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

	public function processResultForQueryForwardGame($params){

		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array('url'=>'');
		
		if($success){
			$result['url'] = @$resultArr['Data'];
		}

		return array($success, $result);
	}

	public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
			case 'cn':
			case 'zh-cn':
                $lang = 'zh-CN';
                break;
			case 'tw':
			case 'zh-tw':
				$lang = 'zh-TW';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
			case 'vi':
			case 'vi-vn':
                $lang = 'vi-VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
			case 'id':
			case 'id-id':
                $lang = 'id-ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
			case 'th':
			case 'th-th':
                $lang = 'th-TH';
                break;
			case 'ja':
			case 'ja-jp':
				$lang = 'th-JP';
				break;
            default:
                $lang = 'en-US';
                break;
        }
        return $lang;
    }

	public function syncOriginalGameLogs($token) {
		$this->CI->utils->debug_log('JILI (syncOriginalGameLogs)', $token);
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
	
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$startDateTime->modify($this->getDatetimeAdjust());
		$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startTime = $startDateTime->format('Y-m-d\TH:i:s');
        $endTime = $endDateTime->format('Y-m-d\TH:i:s');

        $result = [];
		$data_inserted_or_updated = 0;
        $result[] = $this->CI->utils->loopDateTimeStartEnd($startTime, $endTime, $this->sync_time_interval, function($startDate, $endDate) use(&$data_inserted_or_updated) {
            $startTime = $startDate->format('Y-m-d\TH:i:s');
            $endTime = $endDate->format('Y-m-d\TH:i:s');

			$current_page = 1;
			$done 		  = false;

			while(!$done){
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'startDate' => $startDate,
					'endDate' => $endDate
				);
	
				$params = array(
					'StartTime' => 	$startTime,
					'EndTime' 	=> 	$endTime,
					'Page' 		=> 	$current_page,
					'PageLimit' => 	$this->sync_original_max_page_size,
					'AgentId' 	=> 	$this->agentId,
				);
	
				$queryString = http_build_query($params);
				$queryString = str_replace('%3A', ':', $queryString); #prevent encoding special chars as required parameters 
				$params['Key'] = $this->generateKey($queryString);
	
				$this->CI->utils->debug_log('<-------------- PARAMS -------------->', $params);

				$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

				$this->CI->utils->debug_log('JILI--done_syncing_false');

				$api_result = null;

				#checks if api result gives success or data count more than 1 
				if(isset($api_result['success']) && $api_result['success'] && $api_result['data_count']){
					$current_page += 1;
				}else{
					$done = true;
					$this->CI->utils->debug_log('JILI--done_syncing_true');
				}
				#checks if api result total page is already met the current page 
				if($current_page == $api_result['pagination']['TotalPages']){
					$done = true;
					$this->CI->utils->debug_log('JILI--done_syncing_true');
				}

				$data_inserted_or_updated += $api_result['data_count'];
				$api_result['data_inserted_or_updated'] = $data_inserted_or_updated;
			}
			
			unset($api_result['data_count']);
			unset($api_result['pagination']);

			return $api_result;
        });

		#sync original game logs for free spin; applicable for transfer only;
		if(!$this->is_seamless_wallet){
			sleep(6); #sleep for 6 seconds to avoid api limit; ErrorCode: 104
			$free_spin_result = $this->syncOriginalGameLogsForFreeSpin($startTime, $endTime);
		}

        return ['success' => true, $result];
    }

	private function rebuildGameRecords(&$gameRecords,$extra){
		// {
		// 	"Account": "testdepo5",
		// 	"WagersId": 1688192116941332002,
		// 	"GameId": 2,
		// 	"WagersTime": "2023-08-23T03:54:36-04:00",
		// 	"BetAmount": -0.5,
		// 	"PayoffTime": "2023-08-23T03:54:36-04:00",
		// 	"PayoffAmount": 6.77,
		// 	"Status": 1,
		// 	"SettlementTime": "2023-08-23T03:54:36-04:00",
		// 	"GameCategoryId": 1,
		// 	"VersionKey": 0,
		// 	"Type": 1,
		// 	"AgentId": "ZFRT013_OLE777_THB",
		// 	"Turnover": 0.5
		// }
		$this->CI->utils->debug_log('jili (rebuildGameRecords)', $gameRecords,$gameRecords, $extra);
		
		$new_gameRecords = array();	
		
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$new_gameRecords[$index]['transaction_id'] 		= isset($record['WagersId']) ? $record['WagersId'] : null;
				$new_gameRecords[$index]['response_result_id'] 	= $extra['response_result_id'];
				$new_gameRecords[$index]['account'] 			= isset($record['Account']) ? $record['Account'] : null;
				$new_gameRecords[$index]['game_category']		= isset($record['GameCategoryId']) ? $record['GameCategoryId'] : null;
				$new_gameRecords[$index]['betting_date']		= isset($record['WagersTime']) ? $this->gameTimeToServerTime($record['WagersTime']) : null;
				$new_gameRecords[$index]['time_settled']		= isset($record['SettlementTime']) ? $this->gameTimeToServerTime($record['SettlementTime']) : null;
				$new_gameRecords[$index]['bet_amount']			= isset($record['BetAmount']) ? $record['BetAmount'] : null;
				$new_gameRecords[$index]['payoff_time']			= isset($record['PayoffTime']) ? $this->gameTimeToServerTime($record['PayoffTime']) : null;
				$new_gameRecords[$index]['payoff_amount']		= isset($record['PayoffAmount']) ? $record['PayoffAmount'] : null;
				$new_gameRecords[$index]['status']				= isset($record['Status']) ? $record['Status'] : null;
				$new_gameRecords[$index]['version_key']			= isset($record['VersionKey']) ? $record['VersionKey'] : null;
				$new_gameRecords[$index]['type']				= isset($record['Type']) ? $record['Type'] : null;
				$new_gameRecords[$index]['agent_id']			= isset($record['AgentId']) ? $record['AgentId'] : null;
				$new_gameRecords[$index]['turnover']			= isset($record['Turnover']) ? $record['Turnover'] : null;
				$new_gameRecords[$index]['game_code']			= isset($record['GameId']) ? $record['GameId'] : null;

				#default
				$new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
				$new_gameRecords[$index]['external_uniqueid']  = isset($record['WagersId']) ? $record['WagersId'] : null;
				
			}
		}
        $gameRecords = $new_gameRecords;
	}


	public function processResultForSyncOriginalGameLogs($params) {

        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,null,true);
		$result = array('data_count'=>0);

		$this->CI->utils->debug_log('jili (processResultForSyncOriginalGameLogs)' , $resultArr,$this->original_gamelogs_table);

		$gameRecords = !empty($resultArr['Data']['Result'])?$resultArr['Data']['Result']:[];
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
		$result['pagination'] = isset($resultArr['Data']) ? $resultArr['Data']['Pagination'] : null;

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
		$interval = $this->hoursInterval;
		// $dateFrom = date('Y-m-d H:i:s', strtotime($dateFrom . $interval));
		// $dateTo = date('Y-m-d H:i:s', strtotime($dateTo . $interval));
		
		$sqlTime='`original`.`betting_date` >= ? AND `original`.`betting_date` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`betting_date` >= ? AND `original`.`betting_date` <= ?';
        }

       

        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.transaction_id,
	original.account,
	original.game_category as game_category,transaction_id,
	original.betting_date as betting_date,
	original.time_settled as settled_date,
	original.bet_amount as bet_amount,
	original.payoff_time,
	original.payoff_amount,
	original.status,
	original.version_key,
	original.type,
	original.agent_id,
	original.turnover,
	original.result_id,
	original.game_code,
	original.response_result_id,
	original.reference_id,
	
	original.updated_at,
	original.external_uniqueid,
	original.md5_sum,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.account = game_provider_auth.login_name
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
        return  $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }


		$resultAmount = $row['payoff_amount'];
		$winLossAmount = $resultAmount - abs($row['bet_amount']);

		/*$winAmount = 0;
		$lossAmount = 0;
		if($winLossAmount >= 0){
			$winAmount = $winLossAmount;
		}else{
			$lossAmount = $winLossAmount;
		}*/

        return [
            'game_info' => [
                'game_type_id' 			=> $row['game_type_id'],
                'game_description_id' 	=> $row['game_description_id'],
                'game_code' 			=> $row['game_code'],
                'game_type' 			=> null,
                'game'					=> $row['game_code']
            ],
            'player_info' => [
                'player_id' 		=> $row['player_id'],
                'player_username' 	=> $row['account']
            ],
            'amount_info' => [
                'bet_amount' 			=> abs($row['bet_amount']),
                'result_amount' 		=> $winLossAmount,
                'real_betting_amount' 	=> $row['real_betting_amount'],
				'bet_for_cashback' 		=> $row['bet_for_cashback'],
                'win_amount' 			=> null,
                'loss_amount' 			=> null,
                'after_balance' 		=> null
            ],
            'date_info' => [
                'start_at' 	 => $row['betting_date'],
                'end_at' 	 => $row['settled_date'],
                'bet_at' 	 => $row['betting_date'],
                'updated_at' => $row['updated_at']
            ],
            'flag' 	 => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' 	 => 0,
                'external_uniqueid'  => $row['external_uniqueid'],
                'round_number' 		 => $row['transaction_id'],
                'md5_sum' 			 => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' 		 => $row['sync_index'],
                'bet_type' 			 => $row['type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' 	  => null,
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
        $bet_details = $this->processBetDetails($row);
        $row['bet_details'] = $bet_details;
		$row['betting_date'] = $row['betting_date'];

		#if free spin, adjust real_betting_amount and bet_for_cashback to 0
		if(isset($row['reference_id'])){
			$row['real_betting_amount'] = 0.0;
			$row['bet_for_cashback'] = 0.0;
		}else{
			$row['real_betting_amount'] = abs($row['bet_amount']);
			$row['bet_for_cashback'] = abs($row['bet_amount']);
		}
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

	private function processBetDetails($gameRecords) {
		$bet_details = array();
		if(!empty($gameRecords)) {
			$bet_details = array(
				'tableID' 	=> $gameRecords['transaction_id'],
				'betPlaced' => abs($gameRecords['bet_amount']),
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra){
        return $this->returnUnimplemented();
    }

    public function apiGameTime($dateTime = 'now', $format = 'Y-m-d H:i:s', $modify = '+0 hours') {
        $dateTime = new DateTime($dateTime, new DateTimeZone($this->timezone));
        $dateTime->modify($modify);
        return $dateTime->format($format);
    }

    public function createFreeRound($playerName = null, $extra = []) {
        $gameUsername = !empty($extra['Account']) ? $extra['Account'] : $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        // $currency = !empty($extra['currency']) ? $extra['currency'] : $this->currency;
        $currency = '';
        $referenceId = !empty($extra['ReferenceId']) ? $extra['ReferenceId'] : $this->getSecureId('free_round_bonuses', 'transaction_id', true, $this->free_spin_reference_id_prefix, $this->free_spin_reference_id_length);
        $freeSpinValidity = !empty($extra['FreeSpinValidity']) ? $extra['FreeSpinValidity'] : $this->apiGameTime('now', 'Y-m-d\TH:i:s', $this->free_spin_default_validity_hours);
        $numberOfRounds = !empty($extra['NumberOfRounds']) ? $extra['NumberOfRounds'] : $this->free_spin_default_number_of_rounds;
        $gameIds = !empty($extra['GameIds']) ? $extra['GameIds'] : $this->free_spin_default_game_ids;
        $betValue = isset($extra['BetValue']) ? $extra['BetValue'] : $this->free_spin_default_bet_value;
        $startTime = !empty($extra['StartTime']) ? $extra['StartTime'] : $this->apiGameTime('now', 'Y-m-d\TH:i:s');

        $params = [
            'Account' => $gameUsername,
            'Currency' => $currency,
            'ReferenceId' => $referenceId,
            'FreeSpinValidity' => $freeSpinValidity,
            'NumberOfRounds' => $numberOfRounds,
            'GameIds' => $gameIds,
            'AgentId' => $this->agentId,
        ];

        $queryString = http_build_query($params);

        if ($betValue != '') {
            $params['BetValue'] = $betValue;
        }

        $params['StartTime'] = $startTime;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateFreeRound',
            'game_username' => $gameUsername,
            'player_id' => $playerId,
            'free_rounds' => $numberOfRounds,
            'transaction_id' => $referenceId,
            'currency' => $currency,
            'expired_at' => $freeSpinValidity,
            'extra' => $extra,
            'request' => $params,
        ];

        $params['Key'] = $this->generateKey($queryString);

        return $this->callApi(self::API_createFreeRoundBonus, $params, $context);
    }

    public function processResultForCreateFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $free_rounds = $this->getVariableFromContext($params, 'free_rounds');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $currency = $this->getVariableFromContext($params, 'currency');
        $expired_at = $this->getVariableFromContext($params, 'expired_at');
        $extra = $this->getVariableFromContext($params, 'extra');
        $request = $this->getVariableFromContext($params, 'request');

        if ($success) {
            $result = [
                'transaction_id' => $transaction_id,
                'expiration_date' => $expired_at,
            ];

            $data = [
                'player_id' => $player_id,
                'game_platform_id' => $this->getPlatformCode(),
                'free_rounds' => $free_rounds,
                'transaction_id' => $transaction_id,
                'currency' => $currency,
                'expired_at' => $expired_at,
                'extra' => json_encode($extra),
                'raw_data' => json_encode($request),
            ];

            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->insertTransaction($data);
        } else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function cancelFreeRound($transaction_id = null, $extra = []) {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCancelFreeRound',
            'transaction_id' => $transaction_id,
        ];

        if (!empty($extra['ReferenceId'])) {
            $transaction_id = $extra['ReferenceId'];
        }

        $params = [
            'ReferenceId' => $transaction_id,
            'AgentId' => $this->agentId,
        ];

        $queryString = http_build_query($params);
        $params['Key'] = $this->generateKey($queryString);

        return $this->callApi(self::API_cancelFreeRoundBonus, $params, $context);
    }

    public function processResultForCancelFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');

        $result = [
            'message' => '',
        ];

        if ($success) {
            $this->CI->load->model(['free_round_bonus_model']);
            $this->CI->free_round_bonus_model->cancelTransaction($transaction_id, $this->getPlatformCode());

            if (!empty($transaction_id)) {
                $result['transaction_id'] = $transaction_id;
            }

            $result['message'] = 'Cancelled successfully';
        } else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function queryFreeRound($playerName = null, $extra = []) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $referenceId = isset($extra['ReferenceID']) ? $extra['ReferenceID'] : null;
        
        // $this->CI->load->model(['free_round_bonus_model']);
        // $playerId = $this->CI->free_round_bonus_model->getSpecificColumn('free_round_bonuses', 'player_id', ['transaction_id' => $referenceId]);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryFreeRound',
            'game_username' => $gameUsername,
            // 'playerId' => $playerId,
        ];

        $params = [
            'ReferenceID' => $referenceId,
            'AgentId' => $this->agentId,
        ];

        $queryString = http_build_query($params);
        $params['Key'] = $this->generateKey($queryString);

        return $this->callApi(self::API_queryFreeRoundBonus, $params, $context);
    }

    public function processResultForQueryFreeRound($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($success) {
            $result = [
                'free_round_list' => !empty($resultArr['Data']) ? $resultArr['Data'] : [],
            ];
        }
        else {
            $result = [
                'message' => isset($resultArr['Message']) ? $resultArr['Message'] : null,
            ];
        }

        return array($success, $result);
    }

    public function callback($request, $method) {
        if (!in_array($method, self::FREE_SPIN_METHODS)) {
            return [
                'returnCode' => self::RESPONSE_CODE_FAILED,
                'message' => 'Invalid method',
            ];
        }

        return $this->$method('', $request);
    }

	public function syncOriginalGameLogsForFreeSpin($startTime, $endTime)
	{
		$result[] = $this->CI->utils->loopDateTimeStartEnd($startTime, $endTime, '+15 minutes', function ($startDate, $endDate) use (&$data_inserted_or_updated) {
			$startTime = $startDate->format('Y-m-d\TH:i:s');
			$endTime = $endDate->format('Y-m-d\TH:i:s');

			$current_page = 1;
			$done = false;

			while (!$done) {
				$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogsForFreeSpin',
					'startDate' => $startDate,
					'endDate' => $endDate
				);

				$params = array(
					'StartTime' => 	$startTime,
					'EndTime' 	=> 	$endTime,
					'Page' 		=> 	$current_page,
					'PageLimit' => 	$this->sync_original_max_page_size,
					'AgentId' 	=> 	$this->agentId,
				);

				$queryString = http_build_query($params);
				$queryString = str_replace('%3A', ':', $queryString); #prevent encoding special chars as required parameters 
				$params['Key'] = $this->generateKey($queryString);

				$api_result = $this->callApi(self::API_queryFreeRoundBonusGameRecords, $params, $context);

				$api_result = null;

				#checks if api result gives success or data count more than 1 
				if (isset($api_result['success']) && $api_result['success'] && $api_result['data_count']) {
					$current_page += 1;
				} else {
					$done = true;
					$this->CI->utils->debug_log('JILI--done_syncing_true');
				}

				#checks if api result total page is already met the current page 
				if ($current_page == $api_result['pagination']['TotalPages']) {
					$done = true;
					$this->CI->utils->debug_log('JILI--done_syncing_true');
				}

				$data_inserted_or_updated += $api_result['data_count'];
				$api_result['data_inserted_or_updated'] = $data_inserted_or_updated;
			}

			unset($api_result['data_count']);
			unset($api_result['pagination']);

			return $api_result;
		});
	}

	public function processResultForSyncOriginalGameLogsForFreeSpin($params)
	{
		$this->CI->load->model('original_game_logs_model');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params, null, true);
		$result = array('data_count' => 0);

		$gameRecords = !empty($resultArr['Data']['Result']) ? $resultArr['Data']['Result'] : [];

		if ($success && !empty($gameRecords)) {
			$extra = ['response_result_id' => $responseResultId];
			$this->rebuildGameRecordsForFreeSpin($gameRecords, $extra);

			list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
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
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs(
					$insertRows,
					'insert',
					['responseResultId' => $responseResultId]
				);
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs(
					$updateRows,
					'update',
					['responseResultId' => $responseResultId]
				);
			}
			unset($updateRows);
		}
		$result['pagination'] = isset($resultArr['Data']) ? $resultArr['Data']['Pagination'] : null;

		return array($success, $result
		);
	}

	private function rebuildGameRecordsForFreeSpin(&$gameRecords, $extra)
	{
		$free_spin_game_records = array();

		if (!empty($gameRecords)) {
			foreach ($gameRecords as $index => $record) {
				#free spin data
				$free_spin_game_records[$index]['account'] 				= isset($record['Account']) ? $record['Account'] : null;
				$free_spin_game_records[$index]['game_code']			= isset($record['GameId']) ? $record['GameId'] : null;
				$free_spin_game_records[$index]['transaction_id'] 		= isset($record['WagersId']) ? $record['WagersId'] : null;
				$free_spin_game_records[$index]['bet_amount']			= isset($record['BetAmount']) ? $record['BetAmount'] : null;
				$free_spin_game_records[$index]['payoff_amount']		= isset($record['PayoffAmount']) ? $record['PayoffAmount'] : null;
				$free_spin_game_records[$index]['betting_date']			= isset($record['WagersTime']) ? $this->gameTimeToServerTime($record['WagersTime']) : null;
				$free_spin_game_records[$index]['reference_id']			= isset($record['ReferenceId']) ? $record['ReferenceId'] : null;

				#original game logs data
				$free_spin_game_records[$index]['payoff_time']			= $free_spin_game_records[$index]['betting_date'];
				$free_spin_game_records[$index]['time_settled']			= $free_spin_game_records[$index]['betting_date'];
				$free_spin_game_records[$index]['game_category']		= null;
				$free_spin_game_records[$index]['status']				= null; #always settled on merging
				$free_spin_game_records[$index]['version_key']			= null;
				$free_spin_game_records[$index]['type']					= null;
				$free_spin_game_records[$index]['agent_id']				= $this->agentId;
				$free_spin_game_records[$index]['turnover']				= null;

				#default
				$free_spin_game_records[$index]['response_result_id'] 	= $extra['response_result_id'];
				$free_spin_game_records[$index]['external_uniqueid']  	= isset($record['WagersId']) ? $record['WagersId'] : null;
			}
		}
		$gameRecords = $free_spin_game_records;
	}
}

/*end of file*/