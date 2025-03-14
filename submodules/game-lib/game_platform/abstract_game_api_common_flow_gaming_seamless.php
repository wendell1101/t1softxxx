<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: FG SEAMLESS games
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

abstract class Abstract_game_api_common_flow_gaming_seamless extends Abstract_game_api {

	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';
	const METHOD_PUT = 'PUT';

	const GAME_LOGS = 'game';
	const TRANSACTION_LOGS = 'transaction';

	const LOGS_SETTLE_API_REQUEST = 'settled_api_request';
	const LOGS_SETTLE_API_RESPONSE = 'settled_api_response';
	const LOGS_REFUND_API_RESPONSE = 'refunded_api_response';
	const LOGS_UNSETTLE = 'unsettle';

	const TRANSACTION_WAGER = 'WAGER';
	const TRANSACTION_PAYOUT = 'PAYOUT';
	const TRANSACTION_REFUND = 'REFUND';	
	const TRANSACTION_WAGER_INVALID = 'WAGER_INVALID';	
	const TRANSACTION_PAYOUT_INVALID = 'PAYOUT_INVALID';	
	const TRANSACTION_REFUND_INVALID = 'REFUND_INVALID';
	const TRANSACTION_ENDROUND = 'ENDROUND';

	const PAGE_INDEX_START = 1;

	# Fields in fg_seamless_gamelogs we want to detect changes for update
    const MD5_FIELDS_FOR_GAME_LOGS=[
        'req_id',
        'account_ext_ref',
        'token',
        'tx_id',
        'external_game_id',
        'round_id',
        'timestamp',
        'wager_count',
        'payout_count',
        'refund_count',
        'wager_sum',
        'payout_sum',
        'refund_sum',
        'status',
        'category',
        'before_balance',
        'after_balance'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = [
        'wager_sum',
        'payout_sum',
        'refund_sum',
        'before_balance',
        'after_balance'
    ];

	# Fields in fg_seamless_gamelogs_per_transaction we want to detect changes for update
    const MD5_FIELDS_FOR_TRANSACTION_LOGS=[
        'req_id',
        'account_ext_ref',
        'token',
        'category',
        'tx_id',
        'external_game_id',
        'round_id',
        'amount',
        'pool_amount',
        'before_balance',
        'after_balance',
        'timestamp'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS = [
        'amount',
        'pool_amount',
        'before_balance',
        'after_balance',
    ];

	# Fields in fg_seamless_gamelogs_per_transaction we want to detect changes for update
    const MD5_FIELDS_FOR_TRANSACTION_LOGS_V2=[
        'amount',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS_V2 = [
        'amount',
    ];

# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
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
        'bet_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	const URI_MAP = array(
		self::API_generateToken => '/oauth/token',
		self::API_queryForwardGame => '/v1/launcher/item',
		self::API_syncGameRecords => '/v1/feed/transaction',
		self::API_createCampaign => '/v1/campaign',
		self::API_addCampaignMember => '/v1/campaign',
		self::API_updateCampaign => '/v1/campaign',
	);

	const app_id_mapping = [
		'netent'      => [4410, 4411, 6092],
		'maverick'    => [4546],
		'png'         => [4623, 4624],
		'quickspin'   => [4750, 4751],
		'4thplayer'   => [4938, 4939],
		'yggdrasil'   => [5391, 5392],
		'relaxgaming' => [5521, 5522],
		'playtech' 	  => [5763],
		'microgaming' => [4527, 4528],
	];

	const parent_game_platform_id = FLOW_GAMING_SEAMLESS_THB1_API;


	const GRANT_TYPE = 'password';
	const HEADER_AUTH_TOKEN = 'auth';
	const HEADER_SYNC_GAME_LOGS = 'sync_game_logs';
	const HEADER_LAUNCH_GAME = 'launch';
	const HEADER_CAMPAIGN = 'campaign';

	## RESPONSE RESULT CODES
	const ERROR_INVALID_TOKEN = 'Invalid player token';
	## --------------------------------------------------------------------------------

    const API_triggerInternalRefundRound = 'triggerInternalRefundRound';

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
		$this->company_id = $this->getSystemInfo('COMPANY_ID');
		$this->company_account = $this->getSystemInfo('COMPANY_ACCOUNT');
		$this->api_username = $this->getSystemInfo('API_USERNAME');
		$this->api_password = $this->getSystemInfo('API_PASSWORD');
		$this->api_auth_username = $this->getSystemInfo('API_AUTH_USERNAME');
		$this->api_auth_password = $this->getSystemInfo('API_AUTH_PASSWORD');

		$this->currency = $this->getSystemInfo('currency');
		$this->country_code = $this->getSystemInfo('country_code', 'TH');
		$this->language = $this->getSystemInfo('language');
		$this->utc = $this->getSystemInfo('utc');
		$this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
		$this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');

		$this->mobile_lobby_url = $this->getSystemInfo('mobile_lobby_url', "https://{$_SERVER['HTTP_HOST']}");
		$this->redirect_service_api =  "/flow_gaming_service_api/index/" . $this->getPlatformCode() . "/v1/redirect";

		$this->isRedirect = $this->getSystemInfo('isRedirect', false);
		$this->syncMerge_every_bet = $this->getSystemInfo('syncMerge_every_bet', false);
		$this->default_demo_balance = $this->getSystemInfo('default_demo_balance', "1000");
		$this->sync_original_max_page_size = $this->getSystemInfo('sync_original_max_page_size', 3000);
		$this->sync_original_sleep = $this->getSystemInfo('sync_original_sleep', 60);

		// EVERY 3 SYNCS THE PAGE GOES BACK TO 1 AS THE LATEST TRANSACTIONS IS LOCATED ON PAGE 1
		$this->sync_original_counter = $this->getSystemInfo('sync_original_counter', 3);
		$this->adjust_dateto_minutes_sync_merge = $this->getSystemInfo('adjust_dateto_minutes_sync_merge', 0);

		$this->external_demo_flag = $this->getSystemInfo('external_demo_flag', false);
		$this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore', false);
		$this->use_insert_ignore_transaction = $this->getSystemInfo('use_insert_ignore_transaction', false);
		$this->use_old_insert_transaction = $this->getSystemInfo('use_old_insert_transaction', false);

		$this->unset_for_game_launch_update = $this->getSystemInfo('unset_for_game_launch_update', true);
		$this->last_sync_date = $this->getSystemInfo('last_sync_date', date('Y-m-d'));

		$this->sub_game_provider = $this->getSystemInfo('sub_game_provider', false);
		$this->default_game_level = $this->getSystemInfo('default_game_level', 1);
		$this->enabled_campaign = $this->getSystemInfo('enabled_campaign', false);

		/*
			Current setup on SEXYCASINO LIVE is that all logs from service API and game API comes in game_api_flow_gaming_seamless_thb1

			====== Changes ======
			With this changes game_api_flow_gaming_seamless_thb1 only perform syncOriginal then the sub game provider will do the syncMerge so that no double logs will be shown on player logs to avoid confusion

		 */
		$this->do_sync_merge_on_sub_game_provider_only = $this->getSystemInfo('do_sync_merge_on_sub_game_provider_only', false);

		$this->generated_token = null;
		$this->end_point_header = null;
		$this->processCustomAPI = false;

		$this->process_auto_sync_transaction = false;

		$this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['WAGER']);
		
		$this->enable_process_pushfeed_by_queue = $this->getSystemInfo('enable_process_pushfeed_by_queue', true);
		
		$this->enable_process_pushfeed_by_service = $this->getSystemInfo('enable_process_pushfeed_by_service', false);

		$this->enable_payout_error_notification = $this->getSystemInfo('enable_payout_error_notification', false);

        $this->refund_callback_url      = $this->getSystemInfo('refund_callback_url', '');

        $this->CI->load->model(['boomingseamless_game_logs', 'game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token','common_game_free_spin_campaign']);
	}

    public function isSeamLessGame()
    {
       return true;
    }

	public function getUniqueTicket() {
		return hexdec(uniqid());
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		if ($apiName == self::API_generateToken || $apiName == self::API_syncGameRecords) {
			$apiUri = self::URI_MAP[$apiName] . '?' . http_build_query($params);
		}
		if($apiName == self::API_addCampaignMember){
			$campaign_id = $params['campaign_id'];
			$apiUri .= "/{$campaign_id}/member";
		}
		if($apiName == self::API_updateCampaign){
			$campaign_id = $params['campaign_id'];
			unset($params['campaign_id']);
			$apiUri .= "/{$campaign_id}";
		}
		$url = $this->api_url . $apiUri;
        
        if($apiName==self::API_triggerInternalRefundRound){
			$url = $this->refund_callback_url;
		}

		return $url;
	}

	protected function customHttpCall($ch, $params)
	{
        if ($this->method == self::METHOD_POST || $this->method == self::METHOD_PUT) {
        	if ($this->method == self::METHOD_POST) {
	            curl_setopt($ch, CURLOPT_POST, true);
        	} else {
	            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, self::METHOD_PUT);
        	}

        	if(isset($params['campaign_id'])){
        		unset($params['campaign_id']);
        		if(isset($params['members'])){
        			$params = $params['members'];
        		}
        	}
	        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    }
    }

    protected function getHttpHeaders($params)
	{
		$headers['X-DAS-TZ'] = $this->utc;
		$headers['X-DAS-LANG'] = $this->language;
		$headers['X-DAS-CURRENCY'] = $this->currency;

		switch ($this->end_point_header) {
			case self::HEADER_AUTH_TOKEN:
				$headers['Authorization'] = 'Basic '. base64_encode($this->api_auth_username . ':' . $this->api_auth_password);
				$headers['Accept'] = 'application/json ;charset=UTF-8';
				$headers['X-DAS-TX'] = $this->getUniqueTicket();
				return $headers;
				break;
			case self::HEADER_LAUNCH_GAME:
			case self::HEADER_SYNC_GAME_LOGS:
			case self::HEADER_CAMPAIGN:
				$headers['Authorization'] = 'Bearer '. $this->generated_token;
				$headers['Content-Type'] = 'application/json';
				$headers['X-DAS-TX-ID'] = $this->getUniqueTicket();
				return $headers;
				break;
			default:
				# code...
				break;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if ((@$statusCode == 200 || @$statusCode == 201) && !isset($resultArr['error'])) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('FLOW_GAMING_SEAMLESS got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

    /**
	 * Generate Access Token
	 */
	public function generateToken()
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultGenerateToken',
		);

		$params = array(
			'grant_type' => self::GRANT_TYPE,
			'username' => $this->api_username,
			'password' => $this->api_password,
		);

		$this->utils->debug_log("=== FLOW_GAMING_SEAMLESS: getTokens ===");

		$this->method = self::METHOD_POST;
		$this->end_point_header = self::HEADER_AUTH_TOKEN;
		return $this->callApi(self::API_generateToken, $params, $context);
	}

	public function processResultGenerateToken($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);


		$this->CI->utils->debug_log('FLOW_GAMING_SEAMLESS generateToken ===>', $resultArr);

		if ($success) {
			$this->generated_token = @$resultArr['access_token'];
			$result['access_token'] = @$resultArr['access_token'];
			$result['token_type'] = @$resultArr['token_type'];
			$result['refresh_token'] = @$resultArr['refresh_token'];
		}

		return array($success,$result);
	}

	/**
	 * Will check if token is valid then create a new one for response and next request
	 * @return data
	 */
    public function processVerifyToken($postData){
        $playerInfo = $this->CI->common_token->getPlayerCompleteDetailsByToken($postData['token'], $this->getPlatformCode(), false);
        if (!empty($playerInfo)) {
            $gameUsername = $playerInfo->game_username;
			$newToken = $this->CI->common_token->createTokenBy($playerInfo->player_id, 'player_id');
            $balance =  $this->queryPlayerBalance($playerInfo->username);

            return [
            	'success'   => true,
            	'data' 		=> [
            		'req_id'    	  => $postData['req_id'],
            		'player_id'    	  => $playerInfo->player_id,
            		'processing_time' => '',
	            	'token'     	  => $newToken,
	            	'player_username' => $playerInfo->username,
	            	'username'  	  => $gameUsername,
	            	'account_ext_ref' => $gameUsername,
	            	'balance'  		  => $this->roundDownAmount($balance['balance']),
	            	'currency' 		  => $this->currency,
	            	'country'  	 	  => $this->country_code,
	            	'lang'     	  	  => $this->language,
	            	'timestamp'	      => $postData['timestamp']
            	]
        	];
		} else {
			if(isset($postData['account_ext_ref'])){
				$playerInfo = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($postData['account_ext_ref'], $this->getPlatformCode());
				if(!empty($playerInfo) && strtolower($postData['category']) == "payout"){
					$gameUsername = $playerInfo->game_username;
					$newToken = $this->CI->common_token->createTokenBy($playerInfo->player_id, 'player_id');
					$balance = $this->queryPlayerBalance($playerInfo->username);
					
					$result =  [
						'success'   => true,
						'data' 		=> [
							'req_id'    	  => $postData['req_id'],
							'player_id'    	  => $playerInfo->player_id,
							'processing_time' => '',
							'token'     	  => $newToken,
							'player_username' => $playerInfo->username,
							'username'  	  => $gameUsername,
							'account_ext_ref' => $gameUsername,
							'balance'  		  => $balance['balance'],
							'currency' 		  => $this->currency,
							'country'  	 	  => $this->country_code,
							'lang'     	  	  => $this->language,
							'timestamp'	      => $postData['timestamp']
						]
					];
					return $result;
				}
			}
			return [
				'success' => false,
				'data'    => [
            		'req_id'       => $postData['req_id'],
            		'processing_time' => '',
            		'token'		   => $postData['token'],
					'err_desc'     => self::ERROR_INVALID_TOKEN
				]
			];
		}
    }

	public function createPlayer($playerName = null, $playerId = null, $password = null, $email = null, $extra = null)
	{
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$success = false;
		$message = "Unable to create Account for FG SEAMLESS API";
		if($return){
			$success = true;
			$message = "Successfull create account for FG SEAMLESS API";
			$campaign_ids = $this->CI->common_game_free_spin_campaign->getAvailableCampaignForNewPlayer($this->getPlatformCode());

			if(!empty($campaign_ids)){
				$data= [];
				foreach ($campaign_ids as $campaign_id) {
					$data[] = array(
						"campaign_id" => $campaign_id,
						"player_id" => $playerId,
					);
				}

				if(!empty($data)){
					$success = $this->CI->original_game_logs_model->runBatchInsertWithLimit($this->CI->db, 'free_spin_campaign_players', $data);
				}
			}
		}

		return array("success" => $success, "message" => $message);
	}


	// public function queryPlayerBalance($playerName)
	// {
	// 	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    //     $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
	// 	$balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

	// 	$result = array(
	// 		'success' => true,
	// 		'balance' => $this->roundDownAmount($balance)
	// 	);

	// 	return $result;
	// }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		$external_transaction_id = $transfer_secure_id;

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false)
    {
		$external_transaction_id = $transfer_secure_id;

	    return array(
	        'success' => true,
	        'external_transaction_id' => $external_transaction_id,
	        'response_result_id ' => NULL,
	        'didnot_insert_game_logs'=>true,
	    );
    }

	/*
	 *	To Launch Game
	 *
	*/
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $game_code = explode('-', $extra['game_code']);
        $game_mode = $extra['game_mode'];
        $token = $this->getPlayerToken($playerId);
        $this->generateToken();

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'gameUsername'    => $gameUsername,
			'playerId'    	  => $playerId
		);

		$params = array(
			'external' => true,
			'app_id'   => $game_code[0],
			'item_id'  => $game_code[1]
		);

		if ($this->unset_for_game_launch_update) {
			unset($params['app_id']);
		}

		if (!empty($extra['is_mobile'])) {
			$params['conf_params'] = array(
				'lobby_url' => $this->mobile_lobby_url . $this->redirect_service_api
			);
		}

		if ($game_mode == 'demo') {
			$params['external'] = $this->external_demo_flag;
			$params['demo'] = true;
		} else {
			$params['token'] = $token;
		}

		$this->utils->debug_log("=== FLOW_GAMING_SEAMLESS: queryFowardGame ====>");
		if($this->enabled_campaign){
			$campaignIds = $this->CI->common_game_free_spin_campaign->getPlayerAvailableCampaign($playerId, self::parent_game_platform_id);
			if(!empty($campaignIds)){
				$params['campaigns'] = $campaignIds;
			}
		}

		$this->method = self::METHOD_POST;
		$this->end_point_header = self::HEADER_LAUNCH_GAME;
		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$gameUsername     = $this->getVariableFromContext($params, 'gameUsername');
		$playerId         = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result           = array('url'=>'', 'isRedirect' => $this->isRedirect);

		if($success){
			$result['url'] = @$resultArr['data'];
	        // $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$this->CI->utils->debug_log('URL RESULT ==>', @$resultArr);
		}

		return array($success, $result);
	}

	/*
	 * bash ./command_mdb_noroot.sh <db> manual_sync_last_sync_id_with_date <game_platform_id> '2020-11-04 00:00:00' '2020-11-04 23:59:59' <page>
	 * sudo ./command.sh manual_sync_last_sync_id_with_date <game_platform_id> '2020-10-03 00:00:00' '2020-10-18 23:59:59' <page>
	 *
	 * UPDATE 11/11/2020 - LATEST TRANSACTIONS ALWAYS FOUND AT PAGE 1 SO WE NEED TO APPLY SYNC COUNTER SO THAT UPDATED DATES FOUND AT LATER PAGE LIKE 2,3,4 CAN BE SYNC AND WILL START BACK TO PAGE 1
	 */
	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$is_manual_sync = $this->getValueFromSyncInfo($token, 'is_manual_sync');

    	$queryDateTimeStart = $startDateTime->format('Y-m-d');
		$queryDateTimeEnd = $endDateTime->format('Y-m-d');
        $this->generateToken();

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

		$success = false;
		$done = false;

		$page = 1;
		if ($is_manual_sync) {
	        $page = $this->getValueFromSyncInfo($token, 'manual_last_sync_id');
	        if (!$page) {
	        	$page = 1;
	        }
		}

		$sync_counter = 1;

		// for ($page = self::PAGE_INDEX_START; $done == false; $page++) {
		while ($done == false) {
			$params = array(
				'company_id' => $this->company_id,
				'start_time' => $queryDateTimeStart,
				'end_time'   => $queryDateTimeEnd,
				'page'       => $page,
				'page_size'  => $this->sync_original_max_page_size
			);

			$this->method = self::METHOD_GET;
			$this->end_point_header = self::HEADER_SYNC_GAME_LOGS;
			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			$this->CI->utils->debug_log('API syncOriginal RESULT =========>', $api_result);

			if ($api_result && $api_result['success']) {
				$data_count = @$api_result['total_data'];
				if ($data_count != 0 && $data_count == $this->sync_original_max_page_size) {
					if ($sync_counter > $this->sync_original_counter) {
						$done = true;
					} else {
						$sync_counter += 1;
						$done = false;
					}
					$this->CI->utils->info_log('page: ', $page, 'total_data: ', $data_count, 'done', $done, 'result', $api_result);
					$page += 1;
					sleep($this->sync_original_sleep);
				} else if ($data_count != 0 && $data_count != $this->sync_original_max_page_size) {
					$done = true;
				} else if ($data_count == 0) {
					$done = true;
				}
			} else {
				$this->CI->utils->debug_log('FG API ERROR ======>', $api_result);
				$done = true;
			}

			if ($done) {
				$success = true;
			}
		}
		$this->CI->utils->debug_log('DONE =====> ', $done);
		return array('success' => $success, 'last_sync_id' => $page);
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['data_count' => 0];
		$responseRecords = !empty($resultArr)?$resultArr:[];
		$gameRecords = !empty($responseRecords['data']) ? $responseRecords['data'] : [];

		if($success&&!empty($gameRecords))
		{
            $extra = ['response_result_id' => $responseResultId];
            // $transaction_data = $this->rebuildTransactionRecords($gameRecords, $extra);
            // $this->process_auto_sync_transaction = true;
			// $this->doSyncOriginal($transaction_data, $this->original_transaction_table, self::TRANSACTION_LOGS);
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
		$result['total_data'] = count($responseRecords['data']);
		return array($success, $result);
	}

	private function rebuildTransactionRecords($gameRecords,$extra)
	{
		$newGR =[];
        foreach($gameRecords as $i => $gr)
        {
			// $round_id = isset($gr['meta_data']['round_id']) ? strtok($gr['meta_data']['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
			$round_id = isset($gr['meta_data']['round_id']) ? $gr['meta_data']['round_id'] : null;

			if (isset($gr['category']) && isset($gr['balance']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_WAGER) {
				$before_balance = $gr['balance'] + $gr['amount'];
			} else if (isset($gr['category']) && isset($gr['balance']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_PAYOUT) {
				$before_balance = $gr['balance'] - $gr['amount'];
			} else {
				$before_balance = 0;
			}

			$newGR[$i]['amount']	 		= isset($gr['amount']) ? $gr['amount'] : null;
			$newGR[$i]['external_uniqueid'] = isset($gr['id']) ? $gr['id'] : null;

        }
        $gameRecords = $newGR;
        return $gameRecords;
	}

	private function rebuildGameRecords($gameRecords,$extra)
	{
		$newGR =[];
        foreach($gameRecords as $i => $gr)
        {
			// $round_id = isset($gr['meta_data']['round_id']) ? strtok($gr['meta_data']['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
			$round_id = isset($gr['meta_data']['round_id']) ? $gr['meta_data']['round_id'] : null;

			if (isset($gr['category']) && isset($gr['balance']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_WAGER) {
				$before_balance = $gr['balance'] + $gr['amount'];
			} else if (isset($gr['category']) && isset($gr['balance']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_PAYOUT) {
				$before_balance = $gr['balance'] - $gr['amount'];
			} else {
				$before_balance = 0;
			}

            $playerId 						 = $this->getPlayerIdByGameUsername($gr['account_ext_ref']);
			$newGR[$i]['sbe_playerid'] 		 = isset($playerId) ? $playerId : NULL;

			$newGR[$i]['req_id']             = isset($gr['req_id']) ? $gr['req_id'] : null;
			$newGR[$i]['timestamp']		     = isset($gr['transaction_time']) ? $gr['transaction_time'] : null;
			$newGR[$i]['token']		         = isset($gr['token']) ? $gr['token'] : null;
			$newGR[$i]['account_ext_ref']	 = isset($gr['account_ext_ref']) ? $gr['account_ext_ref'] : null;
			$newGR[$i]['tx_id']		         = isset($gr['id']) ? $gr['id'] : null;
			$newGR[$i]['application_id']	 = isset($gr['application_id']) ? $gr['application_id'] : null;
			$newGR[$i]['item_id']		     = isset($gr['meta_data']['item_id']) ? $gr['meta_data']['item_id'] : null;
			$newGR[$i]['external_game_id']	 = (isset($gr['application_id']) && isset($gr['meta_data']['item_id'])) ? $gr['application_id'] . '-' . $gr['meta_data']['item_id'] : null;
			$newGR[$i]['round_id']		     = isset($gr['meta_data']['round_id']) ? $round_id : null;
			$newGR[$i]['txs']		         = isset($gr['parent_transaction_id']) ? $gr['parent_transaction_id'] : null;
			$newGR[$i]['wager_count']	     = (isset($gr['category']) && $gr['category'] == self::TRANSACTION_WAGER) ? 1 : null;
			$newGR[$i]['wager_sum']			 = (isset($gr['category']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_WAGER) ? $gr['amount'] : null;
			$newGR[$i]['payout_count']		 = (isset($gr['category']) && $gr['category'] == self::TRANSACTION_PAYOUT) ? 1 : null;
			$newGR[$i]['payout_sum']		 = (isset($gr['category']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_PAYOUT) ? $gr['amount'] : null;
			$newGR[$i]['refund_count']		 = (isset($gr['category']) && $gr['category'] == self::TRANSACTION_REFUND) ? 1 : null;
			$newGR[$i]['refund_sum']		 = (isset($gr['category']) && isset($gr['amount']) && $gr['category'] == self::TRANSACTION_REFUND) ? $gr['amount'] : null;
			$newGR[$i]['status']		     = self::LOGS_SETTLE_API_REQUEST;
			$newGR[$i]['response_result_id'] = isset($gr['response_result_id']) ? $gr['response_result_id'] : null;
			$newGR[$i]['external_uniqueid']  = isset($gr['id']) ? (string)$gr['id'] : null;
			$newGR[$i]['ext_tx_id']			 = isset($gr['meta_data']['ext_w_tx_id']) ? $gr['meta_data']['ext_w_tx_id'] : null;
			$newGR[$i]['before_balance']	 = isset($gr['balance']) ? $before_balance : null;
			$newGR[$i]['after_balance']		 = isset($gr['balance']) ? $gr['balance'] : null;
			$newGR[$i]['complete_round_id']	 = isset($gr['meta_data']['round_id']) ? $gr['meta_data']['round_id'] : null;
			$newGR[$i]['category']	         = isset($gr['category']) ? $gr['category'] : null;
        }
        $gameRecords = $newGR;
        return $gameRecords;
	}

	public function doSyncOriginal($data, $table_name, $process) {
        $success = false;
		$result = ['data_count' => 0];

		$md5_fields = self::MD5_FIELDS_FOR_GAME_LOGS;
		$md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS;

		if ($process == self::TRANSACTION_LOGS) {
			$md5_fields = self::MD5_FIELDS_FOR_TRANSACTION_LOGS;
			$md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS;
		}
		if ($this->process_auto_sync_transaction) {
			$md5_fields = self::MD5_FIELDS_FOR_TRANSACTION_LOGS_V2;
			$md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS_V2;
			$this->process_auto_sync_transaction = false;
		}

        if (!empty($data)) {
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $table_name,
                $data,
                'external_uniqueid',
                'external_uniqueid',
                $md5_fields,
                'md5_sum',
                'id',
                $md5_float
            );

			// $this->CI->utils->debug_log('after process available rows', !empty($gameRecords) ? count($gameRecords) : 0, !empty($insertRows) ? count($insertRows) : 0, !empty($updateRows) ? count($updateRows) : 0);

            unset($data);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', [], $table_name);
                $success = true;
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', [], $table_name);
                $success = true;
            }
            unset($updateRows);
        }
        return array('success' => $success);
	}

	// This syncMerge is for bulk records which date time can be applied
    public function syncMergeToGameLogs($token)
    {
    	// [MAIN CLASS] = ONLY SYNC ORIGINAL, NO SYNC MERGE
    	// [SUB GAME PROVIDER CLASS] = NO SYNC ORIGINAL, ONLY SYNC MERGE
    	if ($this->do_sync_merge_on_sub_game_provider_only) {
			return $this->returnUnimplemented();
    	}

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
    	$dateFrom = new DateTime($dateFrom);
        $dateFrom = $dateFrom->modify($this->getDatetimeAdjustSyncMerge());
        $dateFrom = $dateFrom->format('Y-m-d H:i:s');

        $modify_datetto = '+' . $this->adjust_dateto_minutes_sync_merge . ' minutes';
    	$dateTo = new DateTime($dateTo);
        $dateTo = $dateTo->modify($modify_datetto);
        $dateTo = $dateTo->format('Y-m-d H:i:s');

        $sqlTime='`fg`.`timestamp` >= ?
          AND `fg`.`timestamp` <= ?';

        $sql = <<<EOD
SELECT
    gd.game_type_id AS game_type_id,
    gd.sub_game_provider AS sub_game_provider,
    gd.id AS game_description_id,
    fg.external_game_id AS game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    fg.application_id AS application_id,
    fg.account_ext_ref AS player_username,
	IF(fg.wager_count IS NULL, 0, fg.wager_count)AS bet_count,
	IF(fg.wager_sum IS NULL, 0, fg.wager_sum)AS bet_amount,
	IF(fg.payout_count IS NULL, 0, fg.payout_count)AS win_count,
	IF(fg.payout_sum IS NULL, 0, fg.payout_sum)AS win_amount,
	IF(fg.refund_count IS NULL, 0, fg.refund_count)AS refund_count,
	IF(fg.refund_sum IS NULL, 0, fg.refund_sum)AS refund_amount,
    fg.after_balance AS after_balance,
    fg.timestamp AS game_date,
    fg.external_uniqueid AS external_uniqueid,
    fg.round_id AS round,
    fg.md5_sum AS md5_sum,
    fg.status AS bet_status,
    fg.round_id AS round_id
FROM {$this->original_gamelogs_table} as fg
	LEFT JOIN game_description as gd ON fg.external_game_id = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
	LEFT JOIN game_provider_auth as gpa ON fg.account_ext_ref = gpa.login_name
	AND gpa.game_provider_id = ?
WHERE
	fg.status != ?
AND
	fg.category != ?
AND
    {$sqlTime}
EOD;

		$new_record = [];

        $params=[
            $this->getPlatformCode(),
            // because sub game providers didn't create game provider auth so we need to query the parent provider id
            self::parent_game_platform_id,
            self::LOGS_SETTLE_API_RESPONSE,
            self::TRANSACTION_REFUND,
            $dateFrom,
            $dateTo
        ];

        $record = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach ($record as $data) {

        	// For THB1 CLASS THAT DIDN'T FILTER SUB GAME PROVIDER GAME LOGS;
        	if ($this->sub_game_provider == false) {
        		$new_record[] = $data;
        	} else {
	        	if (in_array($data['application_id'], self::app_id_mapping[$this->sub_game_provider])) {
	        		$new_record[] = $data;
	        	}
	        }
        }

        return $new_record;
        // return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function queryOriginalRefundGamelogs($round_id, $txs_for_refund)
    {

        $sql = <<<EOD
SELECT
    fg.id
FROM {$this->original_gamelogs_table} as fg
WHERE
	fg.status != ?
AND
	fg.category = ?
AND
	fg.round_id = ?
AND
    fg.txs = ?
EOD;

        $params=[
            self::LOGS_SETTLE_API_RESPONSE,
            self::TRANSACTION_REFUND,
            $round_id,
            $txs_for_refund,
        ];

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	if (!empty($queryResult)) {
    		return true;
    	}
    	return false;
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
        $extra = [
            'table' =>  $row['external_uniqueid'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if (!empty($row['win_count'])) {
            $extra['note'] = 'Payout';
        } else {
            $extra['note'] = 'Wager';
        }

        $isRefund = $this->queryOriginalRefundGamelogs($row['round'], $row['external_uniqueid']);

        $status = Game_logs::STATUS_SETTLED;
        if ($isRefund) {
        	// $status = Game_logs::STATUS_REFUND;
        	#allowed refund on gamelogs , since Game Bo refund bet is also on wager total
        	$extra['note'] = "Refund"; #mark note as refund
        	$row['win_amount'] = $row['bet_amount']; #set win amount same as bet amount, payout = 0;
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
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['game_date'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['game_date'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $status,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
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
        $row['status'] = Game_logs::STATUS_SETTLED;
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

                    if ($this->use_insert_ignore) {
                        $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($table_name, $record);
                    } else {
                        $this->CI->original_game_logs_model->insertRowsToOriginal($table_name, $record);
                    }
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

	public function saveToResponseResult($success, $callMethod, $params, $response, $fields = [], $http_status_code = 200){
        $headers = getallheaders();
		$cost = intval($this->utils->getExecutionTimeToNow()*1000);
		if(is_array($response)){
			$response = json_encode($response);
		}
		if(is_array($params)){
			$params = json_encode($params);
		}
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult(
			$this->getPlatformCode(), 
			$flag, 
			$callMethod, 
			$params, 
			$response, 
			$http_status_code, 
			null, //statustest
			is_array($headers) ? json_encode($headers) : $headers, //extra
			$fields,
			false,
			null,
			$cost
		);
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

    public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
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

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

		if(empty($this->sub_game_provider) || !array_key_exists($this->sub_game_provider, self::app_id_mapping) ){
			return [];
		}

		$applicationIds = self::app_id_mapping[$this->sub_game_provider];

		if(empty($applicationIds)){
			return [];
		}
		$applicationIdsImplode = implode("','",$applicationIds);

$sql = <<<EOD
SELECT
gpa.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.tx_id as round_no,
t.application_id,
t.external_uniqueid as external_uniqueid,
t.category trans_type
FROM {$this->original_transaction_table} as t
JOIN game_provider_auth gpa on gpa.login_name = t.account_ext_ref
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? and `t`.application_id in ('$applicationIdsImplode')
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){

                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=['application_id'=>$transaction['application_id']];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];
				
                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function createCampaign($params){
        $this->generateToken();

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForCreateCampaign',
		);

		$games = array();
		if( isset($params['games']) && !empty($params['games']) ){
			foreach ($params['games'] as $game) {
				$game_array = explode('-', $game);
				$game_code = end($game_array);
				$games[] = array("id" => $game_code, "level" => $this->default_game_level);
			}
		}

		$start_time = date('Y-m-d\TH:i:s', strtotime($params['from']));
		$end_time = date('Y-m-d\TH:i:s', strtotime($params['to']));

		$params = array(
			"account_id" => $this->company_id,
		    "currency" => $params['currency'],
		    "name" => $params['campaign'],
		    "ext_ref" => $params['campaign'],
		    "games" => $games,
		    "num_of_games" => $params['numOfGames'],
		    "start_time" => $start_time,
		    "end_time" => $end_time,
		    "status" => $params['status'],
		    "type" => "FREE_GAMES"
		);

    	$this->method = self::METHOD_POST;
		$this->end_point_header = self::HEADER_CAMPAIGN;
		return $this->callApi(self::API_createCampaign, $params, $context);
    }

    public function updateCampaign($params){
        $this->generateToken();

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForUpdateCampaign',
		);

		$games = array();
		if( isset($params['games']) && !empty($params['games']) ){
			foreach ($params['games'] as $game) {
				$game_array = explode('-', $game);
				$game_code = end($game_array);
				$games[] = array("id" => $game_code, "level" => $this->default_game_level);
			}
		}

		$start_time = date('Y-m-d\TH:i:s', strtotime($params['from']));
		$end_time = date('Y-m-d\TH:i:s', strtotime($params['to']));
		$version = $params['version'];

		$params = array(
			"campaign_id" => $params['campaignId'],
		    "currency" => $params['currency'],
		    "name" => $params['campaign'],
		    "games" => $games,
		    "num_of_games" => $params['numOfGames'],
		    "start_time" => $start_time,
		    "end_time" => $end_time,
		    "status" => $params['status'],
		    "version" => $version,
		    "type" => "FREE_GAMES"
		);
    	$this->method = self::METHOD_PUT;
		$this->end_point_header = self::HEADER_CAMPAIGN;
		return $this->callApi(self::API_updateCampaign, $params, $context);
    }

    public function processResultForCreateCampaign($params){
    	$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = false;
		if(isset($resultArr['data'])){
			$success = true;
		}
		return array($success,$resultArr);
    }

    public function processResultForUpdateCampaign($params){
    	$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = false;
		if(isset($resultArr['data'])){
			$success = true;
		}
		return array($success,$resultArr);
    }

    /**
     * overwrite it , if not http call
     *
     * @return boolean true=error, false=ok
     */
    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        // $statusCode = intval($statusCode, 10);
        return $errCode || intval($statusCode, 10) >= 504;
    }

    public function addCampaignMember($params){
    	$this->generateToken();

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForAddCampaignMember',
		);

		$params = array(
			"campaign_id" => $params['campaign_id'],
		    "members" => $params['members'],
		);

    	$this->method = self::METHOD_POST;
		$this->end_point_header = self::HEADER_CAMPAIGN;
		return $this->callApi(self::API_addCampaignMember, $params, $context);
    }

    public function processResultForAddCampaignMember($params){
    	$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = false;
		if(isset($resultArr['data'])){
			$success = true;
		}
		return array($success,$resultArr);
    }

    public function generateCampaignHeader()
	{
		$headers['X-DAS-TZ'] = $this->utc;
		$headers['X-DAS-LANG'] = $this->language;
		$headers['X-DAS-CURRENCY'] = $this->currency;
		$headers['Authorization'] = 'Bearer '.$this->generateToken()['access_token'];
		// $headers['Accept'] = 'application/json';
		$headers['X-DAS-TX-ID'] = $this->getUniqueTicket();
		return $headers;
	}

	public function processPushFeed($data, $table_name, &$responseData, &$err){
		$this->CI->load->model(array('wallet_model', 'original_game_logs_model'));

		$alreadyExist = false;
		$betExist = true;
		$gamePlatformId = $this->getPlatformCode();
		$trans_category = array_key_exists('category', $data)  ? $data['category'] : null;
		$trasferType = array_key_exists('type', $data)  ? $data['type'] : null;
		$data['account_ext_ref'] = isset($data['account_ext_ref']) ? $data['account_ext_ref'] : $data['ext_ref'];
		$data['parent_transaction_id'] = isset($data['parent_transaction_id']) ? $data['parent_transaction_id'] : $data['130248222'];
		$player_id = $this->getPlayerIdByGameUsername($data['account_ext_ref']);

		####### REFUND		
		if ($trans_category == self::TRANSACTION_REFUND && $data['sub_category'] == self::TRANSACTION_WAGER) {
			$table_name = $this->original_transaction_table;
			$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function() use($data, 
			$player_id, &$alreadyExist, &$betExist, $table_name, $gamePlatformId, &$responseData, &$err) {
				

				//cehck if refund already exist
				$refundTrans = $this->CI->db->get_where($table_name, array('tx_id' => strval($data['id'])))->row_array();
				if(!empty($refundTrans)){
					$alreadyExist = true;
					$responseData['alreadyExist'] = true;
					$err = 'Transaction already exist';
					return true;
				}

				//check if bet to refund exist
				$betTrans = $this->CI->db->get_where($table_name, array('tx_id' => strval($data['parent_transaction_id'])))->row_array();
				if(empty($betTrans)){
					$betExist = false;
					$responseData['betExist'] = false;
					$this->CI->utils->error_log('processPushFeed betDontExist', 'data', $data);		
					$err = 'Bet transaction doesn\'t exist';
					return false;
				}

				//cehck if already have payout
				$round_id = isset($data['meta_data']['round_id']) ? $data['meta_data']['round_id'] : null;
				$account_ext_ref = isset($data['account_ext_ref']) ? $data['account_ext_ref'] : null;
				$payoutTrans = $this->CI->db->get_where($table_name, array('round_id' => $round_id, 'account_ext_ref'=>$account_ext_ref, 'category'=>'PAYOUT'))->row_array();
				if(!empty($payoutTrans)){
					$this->CI->utils->error_log('processPushFeed betDontExist', 'data', $data);		
					$err = 'Cannot refund bet with Payout.';
					return false;
				}

				//get prev balance
				$player_name = $this->getPlayerUsernameByGameUsername($data['account_ext_ref']);
				$prev_balance = $this->queryPlayerBalance($player_name);
				$this->CI->utils->error_log("processPushFeed balance", 'data', $data, 'player_name', $player_name, 'prev_balance', $prev_balance);

				//build transaction data
				$transaction_data = $this->rebuildPushFeed($data);
				

				//insert transaction
				$after_balance = floatval($prev_balance['balance']) + floatval($data['amount']);
				$ext_tx_id = $this->getUniqueTicket();
				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
				$transaction_data['ext_tx_id'] = isset($ext_tx_id) ? $ext_tx_id : null;
				$transaction_data['before_balance'] = isset($prev_balance['balance']) ? $prev_balance['balance'] : null;
				$transaction_data['after_balance'] = isset($after_balance) ? $after_balance : null;
				$transaction_data['elapsed_time'] = $elapsed_time;				
				$inserted = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($table_name, $transaction_data);
			
				if($inserted===false){
					$this->utils->error_log("processPushFeed insertIgnoreRowsToOriginal ERROR", 'gamePlatformId', $gamePlatformId, 'transaction_data', $transaction_data);
					$err = 'Error saving transactions to DB';
					return false;
				}
				if($inserted==0){
					$alreadyExist = true;
					return true;
				}

				//add amount
				$amount = $data['amount'];
				if ($amount <= 0) {
					return true;
				}

				$succAddAmount = $this->CI->wallet_model->incSubWallet($player_id, $gamePlatformId, $amount);
				if(!$succAddAmount){
					$err = 'Error updating balance';
					return false;
				}
				
				return true;
	        });
		}

		####### PAYOUT and REFUND
		if ($trans_category == self::TRANSACTION_PAYOUT) {
			$table_name = $this->original_transaction_table;
			$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function() use($data, 
			$player_id, &$alreadyExist, &$betExist, $table_name, $gamePlatformId, $trasferType, &$responseData, &$err) {
				

				//cehck if payout already exist
				$refundTrans = $this->CI->db->get_where($table_name, array('tx_id' => strval($data['id'])))->row_array();
				if(!empty($refundTrans)){
					$responseData['alreadyExist'] = true;
					$alreadyExist = true;
					$err = 'Transaction already exist';
					return true;
				}

				//check if bet to payout exist
                $round_id = isset($data['meta_data']['round_id']) ? $data['meta_data']['round_id'] : null;
                $account_ext_ref = isset($data['account_ext_ref']) ? $data['account_ext_ref'] : null;
				$betTrans = $this->CI->db->get_where($table_name, array('round_id' => $round_id, 'account_ext_ref'=>$account_ext_ref, 'category'=>'WAGER'))->row_array();
				if(empty($betTrans)){
					$betExist = false;
					$responseData['betExist'] = false;
					$err = 'Bet transaction doesn\'t exist';
					return false;
				}

				//cehck if already have refund
				$round_id = isset($data['meta_data']['round_id']) ? $data['meta_data']['round_id'] : null;
                $account_ext_ref = isset($data['account_ext_ref']) ? $data['account_ext_ref'] : null;
				$refundTrans = $this->CI->db->get_where($table_name, array('round_id' => $round_id, 'account_ext_ref'=>$account_ext_ref, 'category'=>'REFUND'))->row_array();
				if(!empty($refundTrans)){					
					$this->CI->utils->error_log('processPushFeed betDontExist', 'data', $data);		
					$err = 'Cannot process payout bet with Refund.';
					return false;
				}

				//get prev balance
				$player_name = $this->getPlayerUsernameByGameUsername($data['account_ext_ref']);
				$prev_balance = $this->queryPlayerBalance($player_name);

				//build transaction data
				$transaction_data = $this->rebuildTransaction($data);
				

				//insert transaction
				$trans_amount = $data['amount'];
				$after_balance = floatval($prev_balance['balance']) + floatval($trans_amount);
				
				$ext_tx_id = $this->getUniqueTicket();
				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
				$transaction_data['ext_tx_id'] = isset($ext_tx_id) ? $ext_tx_id : null;
				$transaction_data['before_balance'] = isset($prev_balance['balance']) ? $prev_balance['balance'] : null;
				$transaction_data['after_balance'] = isset($after_balance) ? $after_balance : null;
				$transaction_data['elapsed_time'] = $elapsed_time;				
				$inserted = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($table_name, $transaction_data);
			
				if($inserted===false){
					$this->utils->error_log("processPushFeed insertIgnoreRowsToOriginal ERROR", 'gamePlatformId', $gamePlatformId, 'transaction_data', $transaction_data);
					$err = 'Error saving transactions to DB';
					return false;
				}
				if($inserted==0){
					$alreadyExist = true;
					return true;
				}

				//add amount
				$amount = $data['amount'];
				if ($amount <= 0) {
					return true;
				}

				$succAddAmount = $this->CI->wallet_model->incSubWallet($player_id, $gamePlatformId, $amount);
				if(!$succAddAmount){
					$err = 'Error updating balance';
					return false;
				}
				
				return true;
	        });
		}

		####### ENDROUND
		if ($trans_category == self::TRANSACTION_ENDROUND) {
			$table_name = $this->original_gamelogs_table;
			$trans_table_name = $this->original_transaction_table;
			$success = $this->CI->wallet_model->lockAndTransForPlayerBalance($player_id, function() use($data, 
			$player_id, &$alreadyExist, &$betExist, $table_name, $trans_table_name, $gamePlatformId, $trasferType, &$responseData, &$err) {				

				//cehck if endround already exist
				$refundTrans = $this->CI->db->get_where($table_name, array('tx_id' => strval($data['id'])))->row_array();
				if(!empty($refundTrans)){
					$responseData['alreadyExist'] = true;
					$alreadyExist = true;
					$err = 'Transaction already exist';
					return true;
				}

				//check if bet to endround exist
                $round_id = isset($data['meta_data']['round_id']) ? $data['meta_data']['round_id'] : null;
                $account_ext_ref = isset($data['account_ext_ref']) ? $data['account_ext_ref'] : null;
				$betTrans = $this->CI->db->get_where($trans_table_name, array('round_id' => $round_id, 'account_ext_ref'=>$account_ext_ref, 'category'=>'WAGER'))->row_array();
				if(empty($betTrans)){
					$betExist = false;
					$responseData['betExist'] = false;
					$err = 'Bet transaction doesn\'t exist';
					return false;
				}

				//get prev balance
				$player_name = $this->getPlayerUsernameByGameUsername($data['account_ext_ref']);
				$current_balance = $this->queryPlayerBalance($player_name);

				//build transaction data
				$data['current_balance']=  isset($current_balance['balance'])?$current_balance['balance']:0;
				$transaction_data = $this->rebuildEndround($data);
				

				//insert transaction				
				$inserted = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($table_name, $transaction_data);
			
				if($inserted===false){
					$this->utils->error_log("processPushFeed insertIgnoreRowsToOriginal ERROR", 'gamePlatformId', $gamePlatformId, 'transaction_data', $transaction_data);
					return false;
				}
				if($inserted==0){
					$alreadyExist = true;
					return true;
				}
				
				return true;
	        });
		}

		return $success;
	}

	private function rebuildPushFeed($postData) {
		// $round_id = isset($postData['round_id']) ? strtok($postData['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
		$round_id = isset($postData['meta_data']['round_id']) ? $postData['meta_data']['round_id'] : null;
		$trans_category = isset($postData['category']) ? $postData['category'] : null;

		return [
			'req_id'    		 => isset($postData['session']) ? $postData['session'] : null,
			'timestamp' 		 => isset($postData['transaction_time']) ? $this->gameTimeToServerTime($postData['transaction_time']) : null,
			'token'              => isset($postData['token']) ? $postData['token'] : null,
			'account_ext_ref' 	 => isset($postData['account_ext_ref']) ? $postData['account_ext_ref'] : null,
			'category' 			 => $trans_category,
			'tx_id' 			 => isset($postData['id']) ? $postData['id'] : null,
			'refund_tx_id' 	   	 => isset($postData['parent_transaction_id']) ? $postData['parent_transaction_id'] : null,
			'amount' 		 	 => isset($postData['amount']) ? $postData['amount'] : null,
			'pool_amount' 		 => isset($postData['pool_amount']) ? $postData['pool_amount'] : null,
			'application_id' 	 => isset($postData['application_id']) ? $postData['application_id'] : null,
			'item_id' 		 	 => isset($postData['meta_data']['item_id']) ? $postData['meta_data']['item_id'] : null,
			'external_game_id'   => (isset($postData['application_id']) && isset($postData['meta_data']['item_id'])) ? $postData['application_id'] . '-' . $postData['meta_data']['item_id'] : null,
			'round_id' 			 => isset($postData['meta_data']['round_id']) ? $round_id : null,
			'response_result_id' => isset($postData['response_result_id']) ? $postData['response_result_id'] : null,
			'external_uniqueid'  => isset($postData['id']) ? $postData['id'] : null,
		];
	}

	private function rebuildTransaction($postData) {
		
		if(!isset($postData['round_id']) || empty($postData['round_id'])){
			$postData['round_id'] = isset($postData['meta_data']['round_id']) ? $postData['meta_data']['round_id'] : null;
		}
		
		if(!isset($postData['item_id']) || empty($postData['item_id'])){
			$postData['item_id']= isset($postData['meta_data']['item_id']) ? $postData['meta_data']['item_id'] : null;
		}
		
		if(!isset($postData['tx_id']) || empty($postData['tx_id'])){
			$postData['tx_id']= isset($postData['id']) ? $postData['id'] : null;
		}
		
		$trans_category = isset($postData['category']) ? $postData['category'] : null;




		$transaction_time = isset($postData['transaction_time'])?date('Y-m-d H:i:s', strtotime($postData['transaction_time'])):date('Y-m-d H:i:s');

		return [
			'req_id'    		 => isset($postData['req_id']) ? $postData['req_id'] : null,
			'timestamp' 		 => isset($postData['timestamp']) ? $this->gameTimeToServerTime($postData['timestamp']) : $transaction_time,
			'token'              => isset($postData['token']) ? $postData['token'] : null,
			'account_ext_ref' 	 => isset($postData['account_ext_ref']) ? $postData['account_ext_ref'] : null,
			'category' 			 => $trans_category,
			'tx_id' 			 => isset($postData['tx_id']) ? $postData['tx_id'] : null,
			'refund_tx_id' 	   	 => isset($postData['refund_tx_id']) ? $postData['refund_tx_id'] : null,
			'amount' 		 	 => isset($postData['amount']) ? $postData['amount'] : null,
			'pool_amount' 		 => isset($postData['pool_amount']) ? $postData['pool_amount'] : null,
			'application_id' 	 => isset($postData['application_id']) ? $postData['application_id'] : null,
			'item_id' 		 	 => $postData['item_id'],
			'external_game_id'   => (isset($postData['application_id']) && isset($postData['item_id'])) ? $postData['application_id'] . '-' . $postData['item_id'] : null,
			'round_id' 			 => isset($postData['round_id']) ? $postData['round_id'] : null,
			'response_result_id' => isset($postData['response_result_id']) ? $postData['response_result_id'] : null,
			'external_uniqueid'  => isset($postData['tx_id']) ? $postData['tx_id'] : null,
		];
	}


	private function rebuildEndround($postData) {
		
		if(!isset($postData['round_id']) || empty($postData['round_id'])){
			$postData['round_id'] = isset($postData['meta_data']['round_id']) ? $postData['meta_data']['round_id'] : null;
		}
		
		if(!isset($postData['item_id']) || empty($postData['item_id'])){
			$postData['item_id']= isset($postData['meta_data']['item_id']) ? $postData['meta_data']['item_id'] : null;
		}
		
		if(!isset($postData['tx_id']) || empty($postData['tx_id'])){
			$postData['tx_id']= isset($postData['id']) ? $postData['id'] : null;
		}

		$player_id = $this->getPlayerIdInGameProviderAuth($postData['account_ext_ref']);
		$round_stats = isset($postData['round_stats']) ? $postData['round_stats'] : array();
		if(empty($round_stats)){
			//get from transactions
		}
		$txs = isset($postData['txs']) ? json_encode($postData['txs']) : '';
		$ext_tx_id = $this->getUniqueTicket();
		$start_time = strtotime(date('Y-m-d H:i:s'));
		$wager_count = null;
		$wager_sum = null;
		$payout_count = null;
		$payout_sum = null;
		$refund_count = null;
		$refund_sum = null;

		$current_balance = $postData['current_balance'];
		$after_balance = $current_balance;
		$prev_balance = $current_balance;

		foreach ($round_stats as $round) {
			if (isset($round['category']) && $round['category'] == self::TRANSACTION_WAGER) {
				$wager_count = isset($round['count']) ? $round['count'] : null;
				$wager_sum = isset($round['sum']) ? $round['sum'] : null;
			}
			if (isset($round['category']) && $round['category'] == self::TRANSACTION_PAYOUT) {
				$payout_count = isset($round['count']) ? $round['count'] : null;
				$payout_sum = isset($round['sum']) ? $round['sum'] : null;
			}
			if (isset($round['category']) && $round['category'] == self::TRANSACTION_REFUND) {
				$refund_count = isset($round['count']) ? $round['count'] : null;
				$refund_sum = isset($round['sum']) ? $round['sum'] : null;
			}
		}

		if (isset($wager_count) && isset($wager_sum) && $wager_count >= 1) {
			$prev_balance = $prev_balance + $wager_sum;
		}
		if (isset($payout_count) && isset($payout_sum) && $payout_count >= 1) {
			$prev_balance = $prev_balance - $payout_sum;
		}
		if (isset($refund_count) && isset($refund_sum) && $refund_count >= 1) {
			$prev_balance = $prev_balance - $refund_sum;
		}

		// $round_id = isset($postData['round_id']) ? strtok($postData['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
		$round_id = isset($postData['round_id']) ? $postData['round_id'] : null;

		$endround_data = [
			'req_id'    		 => isset($postData['req_id']) ? $postData['req_id'] : null,
			'timestamp' 		 => isset($postData['timestamp']) ? $this->game_api->gameTimeToServerTime($postData['timestamp']) : null,
			'token'              => isset($postData['token']) ? $postData['token'] : null,
			'account_ext_ref' 	 => isset($postData['account_ext_ref']) ? $postData['account_ext_ref'] : null,
			'tx_id' 			 => isset($postData['tx_id']) ? $postData['tx_id'] : null,
			'application_id' 	 => isset($postData['application_id']) ? $postData['application_id'] : null,
			'item_id' 		 	 => isset($postData['item_id']) ? $postData['item_id'] : null,
			'external_game_id'   => (isset($postData['application_id']) && isset($postData['item_id'])) ? $postData['application_id'] . '-' . $postData['item_id'] : null,
			'round_id' 			 => isset($postData['round_id']) ? $round_id : null,
			'complete_round_id'  => isset($postData['round_id']) ? $postData['round_id'] : null,
			'txs' 				 => isset($postData['txs']) ? $txs : null,
			'wager_count' 		 => $wager_count,
			'wager_sum' 		 => $wager_sum,
			'payout_count' 		 => $payout_count,
			'payout_sum' 		 => $payout_sum,
			'refund_count' 		 => $refund_count,
			'refund_sum' 		 => $refund_sum,
			'status' 			 => (isset($refund_count) && isset($refund_sum) && $refund_count >= 1) ? self::LOGS_REFUND_API_RESPONSE : self::LOGS_SETTLE_API_RESPONSE,
			'response_result_id' => isset($postData['response_result_id']) ? $postData['response_result_id'] : null,
			'external_uniqueid'  => isset($postData['tx_id']) ? $postData['tx_id'] : null,
			'ext_tx_id' 		 => $ext_tx_id,
			'before_balance' 	 => isset($prev_balance) ? $prev_balance : null,
			'after_balance' 	 => isset($after_balance) ? $after_balance : null,
			'category' 	 		 => self::TRANSACTION_ENDROUND,
		];
		return $endround_data;
	}

    public function syncSeamlessBatchPayoutRedis($token = false) {
		// process pushfeed

        $this->CI->load->model(array('wallet_model', 't1lottery_transactions'));
        $this->CI->utils->debug_log('========= start syncSeamlessBatchPayoutRedis ============================', 'token',$token);
        $result = [];
        $result['processed'] = [];
        $result['failed'] = [];

        //get redis keys
        $keysMatched = 'batch-payout-'.$this->getPlatformCode();
		$appPrefix = $this->CI->utils->getAppPrefix();
		$allKeys=$this->CI->utils->readRedisKeysNoAppPrefix($keysMatched, $appPrefix);

		$this->CI->utils->debug_log('========= start syncSeamlessBatchPayoutRedis bermar ============================', 
		'keysMatched',$keysMatched,
		'appPrefix', $appPrefix,
		'allKeys', $allKeys);

        //for each keys read status and process
        if(empty($allKeys)){
            return array("success" => true, "results"=>$result);
        }

		$this->CI->utils->debug_log('========= bermar =========', 
			'allKeys',$allKeys);
        
        foreach($allKeys as $key){
			$keyNoPrefix = $key['no_prefix'];
			$keyWithPrefix = $key['key'];

            $this->_redis=try_load_redis($this->CI);
            
            $data = $this->CI->utils->readJsonFromRedis($keyNoPrefix);            
            $this->CI->utils->debug_log('========= syncSeamlessBatchPayoutRedis bermar ============================', 			
			'data', $data);
			if(!is_array($data)){
				$data = json_decode($data, true);
			}
            //var_dump($data);exit;
            $temp = $data;
			$table = isset($temp['table'])?$temp['table']:null;
			
            if(!empty($data)){                
                if(isset($data['status'])&&$data['status']==Game_logs::STATUS_PENDING){                 
					//update redis status					
					//$temp['status'] = Game_logs::STATUS_ACCEPTED;
					//$this->CI->utils->writeJsonToRedis($keyNoPrefix, $temp, null);
					$baseDir=$this->CI->utils->getBatchpayoutSharingUploadPath($this->getPlatformCode());
					if(!isset($temp['file_name'])||empty($temp['file_name'])){
						continue;
					}
                    $fullFileName = $baseDir . $temp['date_hour'] .'/'. $temp['file_name'];                    
                    $json = file_get_contents($fullFileName);                                        

                    if(empty($json)){                                            
                        //alert
                        $alertData = [];
                        $alertData['error_msg'] = 'Batch process pushfeed failed read file';
                        $alertData['username'] = 'unknown';
                        $alertData['amount'] = 'unknown';
                        $alertData['error_code'] = 'unknown';
                        $alertData['payload'] = [];
                        $alertData['file'] = $fullFileName;
                        $alertData['request_id'] = $this->utils->getRequestId();                                                        
                        if($this->enable_payout_error_notification){
                            $this->sendErrorToMattermost('API ' . $this->getPlatformCode(), '【Seamless process pushfeed Failed Cannot Read File】', $alertData);
                        }
                        continue;
                    }
					
					//$api = $this->utils->loadExternalSystemLibObject($this->getPlatformCode());
					$withError = false;
					$dataWithError = [];
					
                    $pushFeedData = json_decode($json, true);     
					$this->CI->utils->debug_log('========= syncSeamlessBatchPayoutRedis bermar pushFeedData ============================', 			
					'pushFeedData', $pushFeedData);
                    if(!empty($pushFeedData) && is_array($pushFeedData)){
                        foreach($pushFeedData as $i => $row){
							$category = 'pushfeed';

							$this->CI->utils->debug_log('========= bermar =========', 
							'row',$row);

                            try { 
								$responseData = [];
								$err = 'Error process pushfeed';
								$category = array_key_exists('category', $row)  ? $row['category'] : 'pushfeed';
								$gameUsername = isset($row['account_ext_ref']) ? $row['account_ext_ref'] : $row['ext_ref'];
								$response = $this->processPushFeed($row, $table, $responseData, $err);		
								$this->CI->utils->debug_log('========= syncSeamlessBatchPayoutRedis processing ============================',
								'row', $row,
								'responseData', $responseData
								);	
								
								if(!$response){																	
									throw new Exception($err);
									$dataWithError[] = $row['id'];		
								}		

                                $result['processed'][] = $row;

                            } catch (Exception $error) {
                                $withError = true;
                                $result['failed'][] = $row;                                

                                $message = $error->getMessage();                                
								
                                //$failedpayouts[] = $payout;
                                $alertData = [];
                                $alertData['error_msg'] = $message;
                                $alertData['username'] = $gameUsername;
                                $alertData['amount'] = isset($row['amount'])?$row['amount']:'';
                                $alertData['error_code'] = $error->getMessage();
                                //$alertData['payload'] = $pushFeedData;
								$alertData['payload'] = array($row);//alert only the one with error
                                $alertData['file'] = $fullFileName;
                                $alertData['responseData'] = $responseData;
                                $alertData['request_id'] = $this->utils->getRequestId();                                
                                
                                if($this->enable_payout_error_notification){
                                    $this->sendErrorToMattermost('API ' . $this->getPlatformCode(), "【Seamless batch $category Failed】", $alertData);
                                }                                 
                                $this->CI->utils->error_log("FLOWGAMING SEAMLESS ERROR: (syncSeamlessBatchPayoutRedis)", 
								'row', $row, 
								'message', $message, 
								'alertData', $alertData);
                                continue;
                            }
                            

                        }//for each payouts

                        //remove from redis						
						$renameSuffix = '_processed';
						if($withError){
							$renameSuffix = '_withError';
						}
						$this->CI->utils->debug_log('========= bermar =========', 
							'deletekey',$keyWithPrefix);
						$successDeleteKey = $this->CI->utils->deleteRedisKey($keyWithPrefix);						
						//unlink($fullFileName);
						rename($fullFileName, $fullFileName.$renameSuffix);
						$this->CI->utils->debug_log('========= bermar =========', 
						'fullFileName',$fullFileName,
						'rename', $fullFileName.$renameSuffix);

                    }else{
						$this->CI->utils->debug_log('========= syncSeamlessBatchPayoutRedis bermar not processed pushFeedData ============================',								
								'pushFeedData', $pushFeedData
								);
					}//end cehck empty pushfeeddata
                }//end check status
            }//end empty check
        }//end foreach key

		return array("success" => true, "results"=>$result);
    }
    
    public function sendErrorToMattermost($user, $subject,$data,$notifType='warning',$texts_and_tags=null){
		if(!$this->enable_mm_channel_nofifications){
			//return false;
		}
    	$this->CI->load->helper('mattermost_notification_helper');
        $settings = $this->CI->utils->getConfig('seamless_batch_payout_settings');
        $client = isset($settings['base_url'])?$settings['base_url']:'???';

        $gamePlatformId = $this->getPlatformCode();
        //check if transaction has no payout
        $message = "@all :heavy_exclamation_mark: Seamless Game ({$gamePlatformId}) ".$subject."\n";            
        $message .= 'Client: '.$client."\n";  
        $message .= 'username: '.@$data['username']."\n";  
        $message .= 'amount: '.@$data['amount']."\n";  
        $message .= 'batch payout file: '.@$data['file']."\n";  
        $message .= "```\n";  
        if(is_array($data)){
            $message .= json_encode($data);
        }else{
            $message .= $data;
        }     				
        $message .= "\n```";  	
        

        $notif_message = array(
            array(
                'text' => $message,
                'type' => 'warning'
            )
        );

    	return sendNotificationToMattermost($user, $this->mm_channel, $notif_message, $texts_and_tags);
    }

	public function getTransactionsTable(){
		return $this->original_transaction_table;
	}

    public function triggerInternalRefundRound($transaction){  
        
        $this->CI->utils->debug_log('FLOWGAMING SEAMLESS (triggerInternalBetRound)', 'transaction', $transaction);		
        
        //check if parameters complete		
        if(!is_array($transaction)){
            $transaction = json_decode($transaction, true);            
        }

        $payload = [];

        //check payload
        if(isset($transaction['payload'])){
            $payload = (array)$transaction['payload'];
        }
        
        if(empty($payload)){
            return array('success' => false, 'message' => 'Invalid payload data.');
        }

		//loop in pushfeed data payload array
		foreach($payload as $trans){

			$responseData = [];
			try { 
				$category = 'pushfeed';
				//check player account
				$gameUserName = '';
				if(isset($trans['account_ext_ref']) && !empty($trans['account_ext_ref'])){
					$gameUserName = $trans['account_ext_ref'];            
				}else{
					throw new Exception('Error identifying player game username. Missing account_ext_ref.');					
				}

				//get player information
				$this->CI->load->model(['common_token']);
				$player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUserName, $this->getPlatformCode());
				if(!isset($player) || empty($player)){
					throw new Exception('Cannot find player.');					
				}
				if(!isset($player->player_id) || empty($player->player_id)){
					throw new Exception('Cannot find player.');					
				}
								
				//cehck if refund already exist
				$table = $this->getTransactionsTable();
				$refundTrans = $this->CI->db->get_where($table, array('tx_id' => strval($trans['id'])))->row_array();
				if(!empty($refundTrans)){
					throw new Exception('Bet already refunded.');		
				}

				//validate parameters
				$err = 'Error process pushfeed';				
				$category = array_key_exists('category', $trans)  ? $trans['category'] : 'pushfeed';
				if($category<>'REFUND'){
					throw new Exception('Manual REFUND only allowed.');	
				}
				$response = $this->processPushFeed($trans, $table, $responseData, $err);		
				if(!$response){																	
					throw new Exception($err);	
				}

				return array('success' => true, 'message' => 'Refund successfully processed.');
			} catch (Exception $error) {
				$err = $message = $error->getMessage();                                
				
				$alertData = [];
				$alertData['error_msg'] = $message;
				$alertData['username'] = isset($row['username'])?$row['username']:'';
				$alertData['amount'] = isset($row['amount'])?$row['amount']:'';
				$alertData['error_code'] = $error->getMessage();
				$alertData['payload'] = array($trans);
				$alertData['file'] = null;
				$alertData['responseData'] = $responseData;
				$alertData['request_id'] = $this->utils->getRequestId();                                
				
				if($this->enable_payout_error_notification){
					$this->sendErrorToMattermost('API ' . $this->getPlatformCode(), "【Seamless batch $category Failed】", $alertData);
				}                                 
				
				return array('success' => false, 'message' => $message);
			}
		}
    }

    public function triggerInternalPayoutRound($transaction){  
        
        $this->CI->utils->debug_log('FLOWGAMING SEAMLESS (triggerInternalBetRound)', 'transaction', $transaction);		
        
        //check if parameters complete		
        if(!is_array($transaction)){
            $transaction = json_decode($transaction, true);            
        }

        $payload = [];

        //check payload
        if(isset($transaction['payload'])){
            $payload = (array)$transaction['payload'];
        }
        
        if(empty($payload)){
            return array('success' => false, 'message' => 'Invalid payload data.');
        }

		//loop in pushfeed data payload array
		foreach($payload as $trans){

			$responseData = [];
			try { 
				$category = 'pushfeed';
				//check player account
				$gameUserName = '';
				if(isset($trans['account_ext_ref']) && !empty($trans['account_ext_ref'])){
					$gameUserName = $trans['account_ext_ref'];            
				}else{
					throw new Exception('Error identifying player game username. Missing account_ext_ref.');					
				}

				//get player information
				$this->CI->load->model(['common_token']);
				$player = $this->CI->common_token->getPlayerCompleteDetailsByGameUsername($gameUserName, $this->getPlatformCode());
				if(!isset($player) || empty($player)){
					throw new Exception('Cannot find player.');					
				}
				if(!isset($player->player_id) || empty($player->player_id)){
					throw new Exception('Cannot find player.');					
				}   
								
				//cehck if payout already exist
				$table = $this->getTransactionsTable();
				$payoutTrans = $this->CI->db->get_where($table, array('tx_id' => strval($trans['id'])))->row_array();
				if(!empty($payoutTrans)){
					throw new Exception('Payout already processed.');		
				}

				//validate parameters
				$err = 'Error process pushfeed';
				$category = array_key_exists('category', $trans)  ? $trans['category'] : 'pushfeed';
				if(!in_array($category, ['PAYOUT', 'ENDROUND'])){
					throw new Exception('Manual PAYOUT/ENDROUND only allowed.');	
				}
				$response = $this->processPushFeed($trans, $table, $responseData, $err);		
				if(!$response){																	
					throw new Exception($err);	
				}

				return array('success' => true, 'message' => 'Refund successfully processed.');
			} catch (Exception $error) {
				$err = $message = $error->getMessage();                                
				
				$alertData = [];
				$alertData['error_msg'] = $message;
				$alertData['username'] = isset($trans['username'])?$trans['username']:'';
				$alertData['amount'] = isset($trans['amount'])?$trans['amount']:'';
				$alertData['error_code'] = $error->getMessage();
				$alertData['payload'] = array($trans);
				$alertData['file'] = null;
				$alertData['responseData'] = $responseData;
				$alertData['request_id'] = $this->utils->getRequestId();                                
				
				if($this->enable_payout_error_notification){
					$this->sendErrorToMattermost('API ' . $this->getPlatformCode(), "【Seamless batch $category Failed】", $alertData);
				}                                 
				
				return array('success' => false, 'message' => $message);
			}
		}
    }

}
/*end of file*/
