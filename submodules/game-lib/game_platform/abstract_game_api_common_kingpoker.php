<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Booming games 
* Game Type: Slots
* Wallet Type: Seamless
*
/**
* API NAME: BOOMING
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @renz.php.ph
**/

abstract class Abstract_game_api_common_kingpoker extends Abstract_game_api {
	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';

    const GAMELIST_TABLE = 'api_gamelist';
	const PAGE_INDEX_START = 1;
	const MAIN_LOBBY = "main";

	# Fields in fg_seamless_gamelogs we want to detect changes for update
    const MD5_FIELDS_FOR_GAME_LOGS = [
        'type',
        'bet_on',
        'bet_id',
        'round_id',
        'bet',	
        'username',
        'valid_bet',
        'start_bet_time',
        'stop_bet_time',
        'payout_time',
        'payout',
        'surplus',
        'repeal_time',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = [
        'bet',
        'valid_bet',
        'payout',
        'surplus'
    ];

	# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'game_code',
        'real_betting_amount',
        'result_amount',
        'start_at',
        'end_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];    

    const MD5_FIELDS_FOR_GAMES = [
        'game_platform_id',
        'game_code',
        'json_fields'
    ];

    const LANGUAGES = [
        'SIMPLIFIED_CN'  => 0,
        'TRADITIONAL_CN' => 1,
        'ENGLISH'		 => 2,
        'KOREAN'		 => 3,
        'THAI'			 => 4,
        'MALAYSIAN'	     => 5,
        'VIETNAMESE'	 => 6
    ];

    const GAME_TYPES = [
        'SLOTS'  => 1,
        'FISHING' => 4
    ];


	const URI_MAP = array(
		self::API_createPlayer => '/api/v3/syncuser',
		self::API_queryPlayerBalance => '/api/v3/userinfo',
		self::API_depositToGame => '/api/v3/recharge',
		self::API_withdrawFromGame => '/api/v3/recharge',
		self::API_queryTransaction => '/api/v3/rechargestatus',
        self::API_login => '/api/v3/h5link',
		self::API_queryForwardGame => '/api/v3/redircet',
		self::API_syncGameRecords => '/api/v3/bethistory',
		self::API_queryBetDetailLink => '/api/v3/betdetail',
        self::API_queryGameListFromGameProvider => '/api/v3/gamelist',
        self::API_syncBalance => '/api/v3/transferhistory'
	);

	public function __construct() {
		parent::__construct();
		
		$this->channel_id = $this->getSystemInfo('channel_id', 7);
		// th_th, in_id, en_us, zh_cn, zh_tw, ko_kr, ms_my, vi_vn, ja_jp
		$this->language = $this->getSystemInfo('language', 'th_th');
		$this->bet_details_language = $this->getSystemInfo('bet_details_language', false);
        $this->api_url = $this->getSystemInfo('url');

		$this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
		$this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
		$this->callback_url = $this->getSystemInfo('callback_url', $this->current_domain);
        $this->keys_path = $this->getSystemInfo('keys_path', false);
        $this->pem_key = $this->getSystemInfo('API_PEM_FILENAME', 'KINGPOKER_PEM.pem');
        $this->pub_key = $this->getSystemInfo('API_PUB_FILENAME', 'KINGPOKER_PUB.pub');
		$this->sync_original_max_page_size = $this->getSystemInfo('sync_original_max_page_size', 5000); 
		$this->sync_original_sleep = $this->getSystemInfo('sync_original_sleep', 0); 
		$this->use_static_key_directory = $this->getSystemInfo('use_static_key_directory', true); 
		$this->sync_after_balance = $this->getSystemInfo('sync_after_balance', true); 
		$this->minute_adjust_time_sync_balance = $this->getSystemInfo('minute_adjust_time_sync_balance', 1);

        $this->method = self::METHOD_POST;

        $this->CI->load->model(['game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token']);
	}

	public function getUnieuqID() {
		return hexdec(uniqid());
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
        $this->CI->utils->debug_log('KINGPOKER generateUrl =====> ', $url);
		return $url;
	}

	protected function customHttpCall($ch, $params)
	{
		if ($this->method == self::METHOD_POST) {
			curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
    }

    protected function getHttpHeaders($params)
	{
        $headers['Content-Type'] = 'multipart/form-data';
        // $headers['Content-Type'] = 'application/x-www-form-urlencoded';
		return $headers;
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode, $apiName = null)
	{
		$success = false;
		if ((@$statusCode == 200 || @$statusCode == 201) && $resultArr['code'] === 200) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('KINGPOKER got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function signByPrivateKey($data) {
        if ($this->use_static_key_directory) {
	        $pem = APPPATH . '../../secret_keys/'.$this->pem_key;
	        $pub = APPPATH . '../../secret_keys/'.$this->pub_key;
        } else {
	        if ($this->keys_path === false) {
		        $this->CI->utils->debug_log('<===== KINGPOKER signByPrivateKey KEYS DIRECTORY NOT YET SET =====>');
		        return false;
	        }
	        $pem = $this->keys_path . '/' . $this->pem_key;
	        $pub = $this->keys_path . '/' . $this->pub_key;
        }

        $key = file_get_contents($pem);

	    if (openssl_sign($data, $signature, $key, OPENSSL_ALGO_MD5)) {
			//openssl_free_key($key);
			/**
			 * Encode signature
			 */
			return base64_encode($signature);
		}

	    return false;
	}

	public function createPlayer($playerName = null, $playerId = null, $password = null, $email = null, $extra = null)
	{

        $extra = ['prefix' => $this->prefix_for_username];
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

		$params = array(
			'username'   => $gameUsername,
			'channel_id' => $this->channel_id,
			'lang'       => $this->language,
			'signature'  => $this->signByPrivateKey($gameUsername)
		);

        $this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER createPlayer params ----------', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params)
    {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, self::API_createPlayer);

        $this->CI->utils->debug_log('---------- KINGPOKER createPlayer response ----------', $resultArr);

		$result = ['player' => $gameUsername];
		
		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}

		//if status = 200 means player exist

		return array($success, $result);
    }


	public function queryPlayerBalance($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'username'   => $gameUsername,
			'channel_id' => $this->channel_id,
			'signature'  => $this->signByPrivateKey($gameUsername)
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER queryPlayerBalance params ----------', $params);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

        $this->CI->utils->debug_log('---------- KINGPOKER processResultForQueryPlayerBalance response ----------', $resultArr);

		if($success){
			$result['balance'] = $resultArr['results'][0]['balance']/100;
		}

		return array($success, $result);
	}

	private function getReasons($msg)
	{
		switch ($msg) {
			case 'overflow':
			case 'ip_limit':
			case 'rate_limit':
			case 'maintenance':
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;
			case 'lack_of_balance':
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 'not_channel':
			case 'param_error':
			case 'user_is_undefined':
			case 'unauthorized':
				return self::REASON_PARAMETER_ERROR;
				break;
			case 'not_exist':
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	private function getReasonsTransaction($responseCode)
	{
		switch ($responseCode) {
			case 2:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

        $timestampnow = strtotime("now") * 1000;

		$params = array(
			'username'   			  => $gameUsername,
			'money'      			  => '+' . $amount*100,
			'channel_id' 			  => $this->channel_id,
			'external_transaction_id' => $external_transaction_id,
			'signature'				  => $this->signByPrivateKey($gameUsername . $timestampnow),
			'timestamp'				  => $timestampnow
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER depositToGame params ----------', $params);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

        $this->CI->utils->debug_log('---------- KINGPOKER processResultForDepositToGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($resultArr['msg'], $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons($resultArr['msg']);
			}
		}

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
    {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

        $timestampnow = strtotime("now") * 1000;

		$params = array(
			'username'   			  => $gameUsername,
			'money'      			  => '-' . $amount*100,
			'channel_id' 			  => $this->channel_id,
			'external_transaction_id' => $external_transaction_id,
			'signature'				  => $this->signByPrivateKey($gameUsername . $timestampnow),
			'timestamp'				  => $timestampnow
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER depositToGame params ----------', $params);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

	public function processResultForWithdrawFromGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

        $this->CI->utils->debug_log('---------- KINGPOKER processResultForWithdrawFromGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr['msg']);
		}

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'transaction_id' => $transactionId
        );

		$params = array(
			'channel_id' 			  => $this->channel_id,
			'external_transaction_id' => $transactionId,
			'signature'				  => $this->signByPrivateKey($transactionId)
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER queryTransaction params ----------', $params);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

	/**
	 * overview : process result for queryTransaction
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultXml
	 * @return array
	 */
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

        $this->CI->utils->debug_log('---------- KINGPOKER processResultForQueryTransaction response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr['msg']);
		}

        return array($success, $result);   
    }

	public function queryH5Link($gameUsername, $extra = null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryH5Link',
            'gameUsername' => $gameUsername
        );

        $my_ip = $this->CI->input->ip_address();
        $timestampnow = strtotime("now") * 1000;

		$params = array(
			'channel_id' => $this->channel_id,
			'username'   => $gameUsername,
			'ip' 		 => $my_ip,
			'timestamp'  => $timestampnow,
			'signature'	 => $this->signByPrivateKey($this->channel_id . $gameUsername . $my_ip . $timestampnow)
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER queryH5Link params ----------', $params);

        return $this->callApi(self::API_login, $params, $context);
    }

	/**
	 * overview : process result for queryTransaction
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultXml
	 * @return array
	 */
	public function processResultForQueryH5Link($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

        $this->CI->utils->debug_log('---------- KINGPOKER processResultForQueryH5Link response ----------', $resultArr);

		if($success){
			$result['h5_link'] = $resultArr['h5_link'];
		}

        return array($success, $result);
    }

	/*
	 *	To Launch Game
	 *
	*/
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
		$get_link = $this->queryH5Link($gameUsername, $extra);

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'gameUsername'    => $gameUsername
		);

        $my_ip = $this->CI->input->ip_address();
        $timestampnow = strtotime("now") * 1000;

		if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($this->language);
        }

        $pre_params = [
        	'username'     => $gameUsername,
        	'game' 		   => !empty($extra['game_code']) ? $extra['game_code'] : self::MAIN_LOBBY,
	    	'lang'   	   => $language,
	    	'expire_time'  => date('Y-m-d H:i:s', strtotime('+23 hours')),
	    	'callback_url' => $this->callback_url,
        	'timestamp'    => $timestampnow 
        ];

        $params_signature = $this->encryptByPublicKey(json_encode($pre_params, JSON_UNESCAPED_SLASHES));

		$params = array(
			'channel_id' => $this->channel_id,
			'params'     => $params_signature
		);

		$url = $get_link['h5_link'] . '/redirect?' . urldecode(utf8_encode(http_build_query($params)));
        $this->CI->utils->debug_log('---------- KINGPOKER queryForwardGame params ----------', $params, '<=== PRE-PARAMS ===>', $pre_params);
		return ['success' => true,'url' => $url];
	}

	public function encryptByPublicKey($data) {
        if ($this->use_static_key_directory) {
	        $pem = APPPATH . '../../secret_keys/'.$this->pem_key;
	        $pub = APPPATH . '../../secret_keys/'.$this->pub_key;
        } else {
	        if ($this->keys_path === false) {
		        $this->CI->utils->debug_log('<===== KINGPOKER KEYS DIRECTORY NOT YET SET =====>');
		        return false;
	        }
	        $pem = $this->keys_path . '/' . $this->pem_key;
	        $pub = $this->keys_path . '/' . $this->pub_key;
        }

        $publicKey = file_get_contents($pub);
	    $crypted = '';

	    foreach (str_split($data, 117) as $chunk) {
	        openssl_public_encrypt($chunk, $encryptData, $publicKey);
	        $crypted .= $encryptData;
	    }

	    return urlencode(base64_encode($crypted));
	}

	/*
	 * Note: You can only search data within the past 60 days.
	 * 7.6.3 Game History
	 */
	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDateTime = $startDateTime->format('Y-m-d H:i:s');
		$endDateTime = $endDateTime->format('Y-m-d H:i:s');
    	$startTime = strtotime($startDateTime) * 1000;
    	$endTime = strtotime($endDateTime) * 1000;

		$success = false;
		$done = false;
		$page = self::PAGE_INDEX_START;
        $timestampnow = strtotime("now") * 1000;

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

		for ($page = self::PAGE_INDEX_START; $done == false; $page++) {
			$params = array(
				'start'      => $startTime,
				'end' 		 => $endTime,
				'channel_id' => $this->channel_id,
				'page_index' => $page,
				'page_size'  => $this->sync_original_max_page_size,
				'timestamp'  => $timestampnow,
				'signature'	 => $this->signByPrivateKey('' . $timestampnow)
			);

			$this->method = self::METHOD_POST;
			$this->CI->utils->debug_log('---------- KINGPOKER syncOriginalGameLogs params ----------', $params);

	        // return $this->callApi(self::API_syncGameRecords, $params, $context);
			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			$this->utils->info_log('---------- KINGPOKER api_result ----------', $api_result);

			if ($api_result && $api_result['success']) {
				$data_count = @$api_result['total_data'];
				if ($data_count != 0 && $data_count == $this->sync_original_max_page_size) {
					$done = false;
					$this->CI->utils->debug_log('page: ', $page, 'total_data: ', $data_count, 'done', $done, 'result', $api_result);
					sleep($this->sync_original_sleep);
				} else {
					$done = true;
				}
			} else {
				$this->CI->utils->debug_log('KINGPOKER API ERROR ======>', $api_result);
				$done = true;
			}

			if ($done) {
				$success = true;
			}
		}

		return array('success' => $success, 'result' => $api_result);

	}

	public function processResultForSyncOriginalGameLogs($params)
	{
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['data_count' => 0];
		$responseRecords = !empty($resultArr) ? $resultArr:[];
		$gameRecords = !empty($responseRecords['list']) ? $responseRecords['list'] : [];
		$this->CI->utils->debug_log('---------- KINGPOKER syncOriginalGameLogs response ----------', $resultArr);

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

		$result['total_data'] = isset($responseRecords['list'])?count($responseRecords['list']):0;
		return array($success, $result);
	}

	private function rebuildGameRecords($gameRecords,$extra)
	{
		$newGR =[];
		$i = 0;
        foreach($gameRecords as $gr)
        {
			$newGR[$i]['type']            	 = isset($gr['type']) ? $gr['type'] : null;
			$newGR[$i]['bet_on']		 	 = isset($gr['bet_on']) ? $gr['bet_on'] : null;
			$newGR[$i]['bet_id']		     = isset($gr['bet_id']) ? $gr['bet_id'] : null;
			$newGR[$i]['round_id']	 		 = isset($gr['round_id']) ? $gr['round_id'] : null;
			$newGR[$i]['bet']		 		 = isset($gr['bet']) ? $gr['bet']/100 : null;
			$newGR[$i]['username']	 	 	 = isset($gr['username']) ? $gr['username'] : null;
			$newGR[$i]['ip']	 			 = isset($gr['ip']) ? $gr['ip'] : null;
			$newGR[$i]['valid_bet']	 		 = isset($gr['valid_bet']) ? $gr['valid_bet']/100 : null;
			$newGR[$i]['start_bet_time']	 = isset($gr['start_bet_time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $gr['start_bet_time']/1000)) : null;
			$newGR[$i]['stop_bet_time']	 	 = isset($gr['stop_bet_time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $gr['stop_bet_time']/1000)) : null;
			$newGR[$i]['payout_time']	 	 = isset($gr['payout_time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $gr['payout_time']/1000)) : null;
			$newGR[$i]['repeal_time']	 	 = isset($gr['repeal_time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $gr['repeal_time']/1000)) : null;
			if( !empty($newGR[$i]['repeal_time']) ){
				$newGR[$i]['payout_time']    = $newGR[$i]['repeal_time'];
			}
			$newGR[$i]['payout']	 	 	 = isset($gr['payout']) ? $gr['payout']/100 : null;
			$newGR[$i]['surplus']	 	 	 = isset($gr['surplus']) ? $gr['surplus']/100 : null;
			$newGR[$i]['response_result_id'] = isset($extra['response_result_id']) ? $extra['response_result_id'] : null;
			$newGR[$i]['external_uniqueid']  = isset($gr['bet_id']) ? $gr['bet_id'] : null;
			$newGR[$i]['extra']            	 = json_encode($gr);
			$i+=1;
        }
        $gameRecords = $newGR;
        return $gameRecords;
	}

	// This syncMerge is for bulk records which date time can be applied
    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

   public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'kp.updated_at BETWEEN ? AND ?';

        if ($use_bet_time) {
            $sqlTime = '`kp`.`start_bet_time` >= ?
          AND `kp`.`start_bet_time` <= ?';
        }

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    kp.type as game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    kp.username AS player_username,
    kp.round_id AS round_id,
    kp.bet AS bet_amount,
    kp.valid_bet AS real_betting_amount,
    kp.payout AS win_amount,
    kp.start_bet_time AS start_at,
    kp.payout_time AS end_at,
    kp.repeal_time,
    kp.external_uniqueid AS external_uniqueid,
    kp.md5_sum AS md5_sum
    FROM {$this->original_gamelogs_table} AS kp
	LEFT JOIN game_description AS gd ON kp.type = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type AS gt ON gd.game_type_id = gt.id
	LEFT JOIN game_provider_auth AS gpa ON kp.username = gpa.login_name
	AND gpa.game_provider_id = ?
WHERE
	kp.payout_time is not null AND
    {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
        $extra = [
            'table' =>  $row['round_id'],
            'note' => !empty($row['repeal_time']) ? lang("rollback") : null,
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
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['win_amount'] - $row['bet_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
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
        $row['status'] = $this->setStatus($row);
        $row['after_balance'] = null;
        if($this->sync_after_balance){
        	$payout_time = new DateTime($row['end_at']);
			$minutesToAdd = $this->minute_adjust_time_sync_balance;
			$payout_time->modify("+{$minutesToAdd} minutes");
			$payout_time = $payout_time->format('Y-m-d H:i:s');

			$bet_time = new DateTime($row['start_at']);
			$minutesToless = $this->minute_adjust_time_sync_balance;
			$bet_time->modify("-{$this->minute_adjust_time_sync_balance} minutes");
			$bet_time = $bet_time->format('Y-m-d H:i:s');

        	$round_result = $this->getTransferHistory($bet_time, $payout_time, $row['player_username'], $row['round_id']);
        	if(isset($round_result['after_balance'])){
        		$row['after_balance'] = $round_result['after_balance'];
        	}	
        }
        
    }

	private function getGameDescriptionInfo($row, $unknownGame)
	{
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

    public function setStatus($row) {
        if (!empty($row['repeal_time'])) {
            $status = Game_logs::STATUS_CANCELLED;
        } else {
            $status = Game_logs::STATUS_SETTLED;
        }

        return $status;
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name)
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
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

	public function saveToResponseResult($success, $callMethod, $params, $response){
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag, $callMethod, json_encode($params), $response, 200, null, null);
    }    

    /** 
    *  The api will return the bet details URL link for viewing the details
    */
    public function queryBetDetailLink($playerUsername, $betid = NULL, $roundid = NULL)
    {        
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $playerId = $this->getPlayerIdInPlayer($playerUsername);
        $timestampnow = strtotime("now") * 1000;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $lang = $this->language;
        if ($this->bet_details_language !== false) {
        	$lang = $this->bet_details_language;
        }

		$params = array(
			'username'   => $gameUsername,
			'channel_id' => $this->channel_id,
			'round_id  ' => $roundid,
			'bet_id' 	 => $betid,
			'result' 	 => true,
			'lang'       => $lang,
			'timestamp'  => $timestampnow,
			'signature'  => $this->signByPrivateKey($gameUsername . $this->channel_id . $roundid . $timestampnow)
		);

        $this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- KINGPOKER queryBetDetailLink params ----------', $params);

		return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    /** 
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params)
    {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = ['string' => ''];

		$resultPreStr = $resultArr['detail'];
		$resultStr = preg_replace("/\r|\n/", "", $resultArr['detail']);
		$newStr = str_replace("\\", '', $resultStr);

        $this->CI->utils->debug_log('---------- KINGPOKER processResultForQueryBetDetailLink response ----------', $resultArr);

		if ($success) {
			$result['string'] = $newStr;
        }

        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra = NULL) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );
        $params = '';
		$this->use_game_url = true;
		$this->method = self::METHOD_POST;
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

        if ($success) {
            $result['games'] = $resultArr['Data'];
        }
        return array($success, $result);
    }

    public function rebuildGameList($games) {
        $data = [];
        foreach ($games as $game) {
            $newGame = [];
            $external_uniqueid = isset($game['Id']) ? $game['Id'] . '-' . $this->getPlatformCode() : '';

            $newGame['game_platform_id']  = $this->getPlatformCode();
            $newGame['game_code'] 		  = isset($game['Id']) ? $game['Id'] : '';
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

    private function processGameListGameType($game_type) {
        $data = "";
        switch($game_type) {
            case self::GAME_TYPES['SLOTS']:
                $data = "Slots";
                break;
            case self::GAME_TYPES['FISHING']:
                $data = "Fishing";
                break;
			default:
                $data = "Others, Need to contact DEV | ";
                break;
        }
        return $data;
    }

    private function processGameListSupportedPlatform($platform) {
        $data = "";
        foreach ($platform as $val) {
	        switch($val) {
	            case self::TP_GPK_HTML5_PLATFORM:
	                $data .= "HTML5 | ";
	                break;
				default:
	                $data .= "Others, Need to contact DEV | ";
	                break;
	        }
        }
        return $data;
    }

    private function updateOrInsertGameList($data, $queryType){
        $dataCount = 0;
        if (!empty($data)) {
            $caption = [];
            if ($queryType == 'update') {
                $caption = "## UPDATE TP GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW TP GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Type | Supported Platform |\n";
            $body .= "| :--- | :--- | :--- |\n";

            foreach ($data as $record) {
            	$game = $record;
            	$record = json_decode($record['json_fields'], true);
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::GAMELIST_TABLE, $game);
                    $body .= "| {$record['GameNameEn']} | {$record['GameName']} | {$record['Id']} | {$this->processGameListGameType($record['GameGroupType'])} | {$this->processGameListSupportedPlatform($record['PlatFormType'])} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::GAMELIST_TABLE, $game);
                    $body .= "| {$record['GameNameEn']} | {$record['GameName']} | {$record['Id']} | {$this->processGameListGameType($record['GameGroupType'])} | {$this->processGameListSupportedPlatform($record['PlatFormType'])} |\n";
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
            "#TP/GPK_API"
        ];

        $channel = $this->utils->getConfig('gamelist_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $user = 'TP/GPK Game List';

        sendNotificationToMattermost($user, $channel, [], $message);
    }

	public function blockPlayer($playerName)
	{
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName)
	{
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $success=true;
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array('success' => $success);
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

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	private function getUserTransactionCacheKey($betTimeStr, $payoutTimeStr, $gameUsername, $roundId){
    	return 'game-api-'.$this->getPlatformCode()."{$gameUsername}-{$betTimeStr}-{$payoutTimeStr}-{$roundId}";
    }

	public function getTransferHistory($betTimeStr, $payoutTimeStr, $gameUsername, $roundId) {

		$cacheKey=$this->getUserTransactionCacheKey($betTimeStr, $payoutTimeStr, $gameUsername, $roundId);
    	$rlt=$this->utils->getJsonFromCache($cacheKey);
    	if(!empty($rlt)){
    		return $rlt;
    	}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncBalance',
            'gameUsername' => $gameUsername,
            'betTime' => $betTimeStr,
            'payoutTime' => $payoutTimeStr,
            'roundId' => $roundId
        );

        $timestampnow = strtotime("now") * 1000;
        $start = strtotime($betTimeStr) * 1000;
        $end = strtotime($payoutTimeStr) * 1000;

		$params = array(
			'channel_id' => $this->channel_id,
			'username' => $gameUsername,
			'start' => $start,
			'end' => $end,
			'timestamp' => $timestampnow,
			'signature'	=> $this->signByPrivateKey($gameUsername . $timestampnow)
		);

		$this->method = self::METHOD_POST;
        $this->utils->debug_log('---------- KINGPOKER getTransferHistory params ----------', $params);

        $rlt=$this->callApi(self::API_syncBalance, $params, $context);
        if($rlt['success'] && ( isset($rlt['after_balance']) && $rlt['after_balance'] ) ){
        	$this->utils->saveJsonToCache($cacheKey, $rlt);
        }
        return $rlt;
    }

	public function processResultForSyncBalance($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$roundId = $this->getVariableFromContext($params, 'roundId');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$result = array(
			"round_id" => $roundId,
			"after_balance" => null
		);

		$this->utils->debug_log('---------- KINGPOKER getTransferHistory response ----------', $resultArr);

		if($success){
			if(isset($resultArr['list'])){
				$list = $resultArr['list'];
				if(!empty($list)){
					$search_index = ['username' => "{$gameUsername}", 'round_id' => "{$roundId}", 'transfer_type' => 'payout'];
					$rollback_index = ['username' => "{$gameUsername}", 'round_id' => "{$roundId}", 'transfer_type' => 'rollback'];
					foreach ($list as $k => $v) {
					    if ($v['username'] == $search_index['username'] && $v['round_id'] == $search_index['round_id'] && ( $v['transfer_type'] == $search_index['transfer_type'] || $v['transfer_type'] == "rollback" ) ) {
					        $result['after_balance'] = $v['balance_after'] / 100;
					        // value found - break the loop
					        break;
					    }
					}
				}
				
			}

		}
        return array($success, $result);   
    }

    /**
	 * Sync After Balance via service
	 * @see sync_after_balance.sh
	 * @see Sync_after_balance@start_sync_after_balance
	 * @see sync_game_after_balance
	 */
	public function syncAfterBalance($token)
	{
		# check the extra info first
		if($this->sync_after_balance){
			$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
			$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
			$dateTimeFrom->modify($this->getDatetimeAdjust());
			//observer the date format
			$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
			$endDate = $dateTimeTo->format('Y-m-d H:i:s');
			$player_name = $this->getValueFromSyncInfo($token, 'player_name');
	
			$originalRecords = $this->queryRealOriginalGameLogsAfterBalanceIsNull($startDate, $endDate, $player_name);

		
			$data_count = 0;
			$mergeToGamelogs = [];
	
			foreach($originalRecords as $key => $record){

				$bet_time = new DateTime($record['start_bet_time']);
				$minutesToless = $this->minute_adjust_time_sync_balance;
				$bet_time->modify("-{$minutesToless} minutes");
				$bet_time = $bet_time->format('Y-m-d H:i:s');

				$minutesToAdd = $this->minute_adjust_time_sync_balance;
				if(!empty($record['payout_time'])){
					$payout_time = new DateTime($record['payout_time']);
					$payout_time->modify("+{$minutesToAdd} minutes");
					$payout_time = $payout_time->format('Y-m-d H:i:s');
				} else {
					$payout_time = new DateTime($record['start_bet_time']);
					$payout_time->modify("+{$minutesToAdd} minutes");
					$payout_time = $payout_time->format('Y-m-d H:i:s');

				}
				
				$array  = array(
					"b" => $bet_time,
					"p" => $payout_time
				);

	
				$result = $this->getTransferHistory($bet_time, $payout_time, $record['username'], $record['round_id']);

				if(isset($result['success']) && $result['success'] && isset($result['after_balance'])){
					# update after balance
					$record['after_balance'] = $result['after_balance'];

					$this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);

					# consolidate all gamelogs to update in gamelogs
					$mergeToGamelogs[$key]['after_balance'] = $record['after_balance'];
					$mergeToGamelogs[$key]['external_uniqueid'] = $record['external_uniqueid'];
					$data_count++;
				}
			}

			# update gamelogs
			$this->updateAfterBalanceOnGamelogs($mergeToGamelogs);

			unset($originalRecords);
			unset($data);
			# add logs
			$this->CI->utils->debug_log("kingpoker after balance updated count: ",$data_count,"start_date: ",$startDate,"end_date: ",$endDate);

			return array("success" => true,"data_count"=>$data_count);
		}
	}

	public function queryRealOriginalGameLogsAfterBalanceIsNull($dateFrom, $dateTo , $player_name = null){
		$game_username = $this->getGameUsernameByPlayerUsername($player_name);
        $this->CI->load->model(array('original_game_logs_model'));
        $sqlTime = '`kp`.`start_bet_time` >= ?
          AND `kp`.`start_bet_time` <= ?';
        $sqlPlayer = !empty($player_name) ? "AND `kp`.`username` = '{$game_username}'" : "";

        $sql = <<<EOD
SELECT 
	kp.id,
    kp.username,
    kp.round_id,
    kp.start_bet_time,
    kp.payout_time,
    kp.external_uniqueid AS external_uniqueid
    FROM {$this->original_gamelogs_table} AS kp
WHERE
	kp.after_balance IS NULL AND
    {$sqlTime}
    {$sqlPlayer}
EOD;


        $params=[
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
			case 'cn':
			case 'zh-cn':
                $lang = 'zh_cn';
                break;
			case 'tw':
			case 'zh-tw':
				$lang = 'zh_tw';
                break;
			case 'ms-my':
			case 'ms':
				$lang = 'ms_my';
				break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
			case 'ko-kr':
			case 'ko':
                $lang = 'ko_kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
			case 'vi':
			case 'vi-vn':
                $lang = 'vi_vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
			case 'id':
			case 'id-id':
                $lang = 'in_id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
			case 'th':
			case 'th-th':
                $lang = 'th_th';
                break;
			case LANGUAGE_FUNCTION::INT_LANG_THAI:
			case 'jp':
			case 'ja-jp':
				$lang = 'ja_jp';
				break;
            default:
                $lang = 'en_us';
                break;
        }
        return $lang;
    }
}
/*end of file*/
