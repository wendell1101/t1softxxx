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

abstract class Abstract_game_api_common_evoplay extends Abstract_game_api {

	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';

    const GAMELIST_TABLE = 'api_gamelist';
	const PAGE_INDEX_START = 1;

	# Fields in evoplay game logs we want to detect changes for update
    const MD5_FIELDS_FOR_GAME_LOGS = [
        'balance',
        'pay_for_action_this_round',
        'total_bet',
        'total_win',
        'time',	
        'date',
        'type'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = [
        'balance',
        'pay_for_action_this_round',
        'total_bet',
        'total_win'
    ];

	# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'game_code',
        'real_betting_amount',
        'result_amount',
        'start_at',
		'end_at',
		'denomination',
		'win_amount'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'denomination',
        'win_amount'
    ];

    const MD5_FIELDS_FOR_GAMES = [
        'game_platform_id',
        'game_code',
        'json_fields'
    ];

	const URI_MAP = array(
		self::API_createPlayer => '/User/registration',
		self::API_queryPlayerBalance => '/User/infoById',
		self::API_depositToGame => '/Finance/deposit',
		self::API_withdrawFromGame => '/Finance/withdrawal',
		self::API_queryTransaction => '/Finance/prize',
		self::API_queryForwardGame => '/Game/getIFrameURLAdvanced',
        self::API_queryGameListFromGameProvider => '/Game/getList',
		// self::API_syncGameRecords => '/api/v3/bethistory',
		// self::API_queryBetDetailLink => '/api/v3/betdetail',
	);

	public function __construct() {
		parent::__construct();
		
		$this->secret_key = $this->getSystemInfo('secret_key');
		$this->version_number = $this->getSystemInfo('version_number');
		$this->project_number = $this->getSystemInfo('project_number');
		// "en","ru","zh","zhtw","fr","de","id","it","ja","ko","pt","es","th","tr","vi","ro","bg"
		$this->language = $this->getSystemInfo('language');

		// THB, CNY, IDR, USD
		$this->currency = $this->getSystemInfo('currency');
        $this->api_url = $this->getSystemInfo('url');


		$this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
		$this->back_url = $this->getSystemInfo('back_url', $this->current_domain);
		$this->cash_url = $this->getSystemInfo('cash_url', $this->current_domain);

		$this->https_game_launching = $this->getSystemInfo('https_game_launching', 1);

	    $this->api_url = $this->getSystemInfo('url');

		$this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
		$this->adjust_dateto_minutes_sync_merge = $this->getSystemInfo('adjust_dateto_minutes_sync_merge', 0);
        $this->method = self::METHOD_POST;
        $this->is_param_have_array = false;

        $this->CI->load->model(['game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token']);
	}

	public function getUnieuqID() {
		return hexdec(uniqid());
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		if ($this->method == self::METHOD_GET && $this->is_param_have_array == true) {
			$url = $url . '?' .  urldecode(utf8_encode(http_build_query($params)));
		} else if ($this->method == self::METHOD_GET) {
			$url = $url . '?' .  http_build_query($params);
		}

        $this->CI->utils->debug_log('EVOPLAY generateUrl =====> ', $url);
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
        // $headers['Content-Type'] = 'multipart/form-data';
        // $headers['Content-Type'] = 'application/x-www-form-urlencoded';
		// return $headers;
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if ((@$statusCode == 200 || @$statusCode == 201) && !array_key_exists('error', $resultArr)) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('EVOPLAY got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function generateSignature($params = []) {

		$signature = '';
		foreach ($params as $param) {
			if (is_array($param)) {
				foreach ($param as $key => $value) {
					$signature .= $value . ':';
				}
				$signature = substr($signature, 0, -1) . '*';
			} else {
				$signature .= $param . '*';
			}
		}
		$signature .= $this->secret_key;
        $this->CI->utils->debug_log('---------- EVOPLAY generateSignature STRING ----------', $signature);

		return md5($signature);
	}

    public function callback($params)
	{
		$insertRecord = [];
		$responseResultId = null;

		if ($params && !empty($params)) {
            $gameRecords = $this->rebuildGameRecords($params);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                array($gameRecords),
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
		return;
	}

	public function createPlayer($playerName = null, $playerId = null, $password = null, $email = null, $extra = null)
	{
        $extra = ['prefix' => $this->prefix_for_username];
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName'      => $playerName,
            'playerId'        => $playerId,
            'gameUsername'    => $gameUsername
        );

		$params = array(
			"project"   => $this->project_number,
			"version"   => $this->version_number,
			"currency"  => $this->currency
		);

		$params['signature'] = $this->generateSignature($params);

        $this->method = self::METHOD_GET;
        $this->CI->utils->debug_log('---------- EVOPLAY createPlayer params ----------', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params)
    {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $this->CI->utils->debug_log('---------- EVOPLAY createPlayer response ----------', $resultArr);

		$result = ['player' => $gameUsername];
		if($success){
			# update flag to registered = true2019214
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

	        // Every API registration returns auto_increment user_id which is the only field that can relate our record to their record
	        $external_account_id = $this->CI->game_provider_auth->getExternalAccountIdByPlayerUsername($playerName, $this->getPlatformCode());
	        if (empty($external_account_id)) {
	        	$newUsername = $gameUsername . '(' . $resultArr['user_id'] . ')';
		        $this->CI->game_provider_auth->updateRegisterFlag($playerId, $this->getPlatformCode(), ['external_account_id' => $resultArr['user_id'], 'login_name' => $newUsername]);
	        }
	        $result['exists'] = true;
		}
		return array($success, $result);
    }


	public function queryPlayerBalance($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$user_id = $this->CI->game_provider_auth->getExternalAccountIdByPlayerUsername($playerName, $this->getPlatformCode());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			"project"   => $this->project_number,
			"version"   => $this->version_number,
			"user_id"   => $user_id
		);
		$params['signature'] = $this->generateSignature($params);

		$this->method = self::METHOD_GET;
        $this->CI->utils->debug_log('---------- EVOPLAY queryPlayerBalance params ----------', $params);

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

        $this->CI->utils->debug_log('---------- EVOPLAY processResultForQueryPlayerBalance response ----------', $resultArr);

		if($success){
			$result['balance'] = $resultArr['balance'];
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

		// API requires INT external_transaction_id
		$external_transaction_id = hexdec(uniqid());
		$user_id = $this->CI->game_provider_auth->getExternalAccountIdByPlayerUsername($playerName, $this->getPlatformCode());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			"project"   		=> $this->project_number,
			"version"   		=> $this->version_number,
			"wl_transaction_id" => $external_transaction_id,
			"user_id"   		=> $user_id,
			"sum"				=> $amount,
			"currency"			=> $this->currency
		);
		$params['signature'] = $this->generateSignature($params);

		$this->method = self::METHOD_GET;
        $this->CI->utils->debug_log('---------- EVOPLAY depositToGame params ----------', $params);

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
        $this->CI->utils->debug_log('---------- EVOPLAY processResultForDepositToGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = self::REASON_UNKNOWN;
		}

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
    {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		// API requires INT external_transaction_id
		$external_transaction_id = hexdec(uniqid());
		$user_id = $this->CI->game_provider_auth->getExternalAccountIdByPlayerUsername($playerName, $this->getPlatformCode());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			"project"   		=> $this->project_number,
			"version"   		=> $this->version_number,
			"wl_transaction_id" => $external_transaction_id,
			"user_id"   		=> $user_id,
			"sum"				=> $amount,
			"currency"			=> $this->currency
		);
		$params['signature'] = $this->generateSignature($params);

		$this->method = self::METHOD_GET;
        $this->CI->utils->debug_log('---------- EVOPLAY depositToGame params ----------', $params);

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

        $this->CI->utils->debug_log('---------- EVOPLAY processResultForWithdrawFromGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = self::REASON_UNKNOWN;
		}

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
    }

	/*
	 *	To Launch Game
	 *
	*/
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
		$user_id = $this->CI->game_provider_auth->getExternalAccountIdByPlayerUsername($playerName, $this->getPlatformCode());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'gameUsername' => $gameUsername
        );

		$params = array(
			"project"   		=> $this->project_number,
			"version"   		=> $this->version_number,
			"user_id"   		=> $extra['game_mode'] == "real" ? $user_id : "demo",
			"game"				=> $extra['game_code'],
			"settings"			=> [
				'https'	    => $this->https_game_launching,
				'back_url'	=> $this->back_url,
				'language'	=> $this->language
			],
			"fun_mode"			=> $extra['game_mode'] == "real" ? 0 : 1,
			"return_url_info"	=> 1
		);
		$params['signature'] = $this->generateSignature($params, true);
		$this->is_param_have_array = true;
		$this->method = self::METHOD_GET;

        $this->CI->utils->debug_log('---------- EVOPLAY queryForwardGame params ----------', $params);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = true;
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $this->CI->utils->debug_log('---------- EVOPLAY processResultForQueryForwardGame response ----------', $resultArr);

		if ($success) {
			$result['url'] = @$resultArr['link'];
		}
		return array($success, $result);
	}

	/*
	 * Note: You can only search data within the past 60 days.
	 * 7.6.3 Game History
	 */
	public function syncOriginalGameLogs($token = false)
	{
		return $this->returnUnimplemented();
	}

	private function rebuildGameRecords($params)
	{
		$insertRecord =[];

		$insertRecord['balance'] 				   = isset($params['event']['data']['balance']) ? $params['event']['data']['balance'] : null;
		$insertRecord['pay_for_action_this_round'] = isset($params['event']['data']['pay_for_action_this_round']) ? $params['event']['data']['pay_for_action_this_round'] : null;
		$insertRecord['lines'] 				       = isset($params['event']['data']['lines']) ? $params['event']['data']['lines'] : null;
		$insertRecord['bet'] 				   	   = isset($params['event']['data']['bet']) ? $params['event']['data']['bet'] : null;
		$insertRecord['balance_before_pay'] 	   = isset($params['event']['data']['balance_before_pay']) ? $params['event']['data']['balance_before_pay'] : null;
		$insertRecord['balance_after_pay'] 		   = isset($params['event']['data']['balance_after_pay']) ? $params['event']['data']['balance_after_pay'] : null;
		$insertRecord['game'] 				   	   = isset($params['event']['data']['game']) ? json_encode($params['event']['data']['game']) : null;
		$insertRecord['game_id'] 				   = isset($params['event']['data']['game']['game_id']) ? $params['event']['data']['game']['game_id'] : null;
		$insertRecord['round'] 				   	   = isset($params['event']['data']['game']['round']) ? json_encode($params['event']['data']['game']['round']) : null;
		$insertRecord['round_id'] 				   = isset($params['event']['data']['game']['round']['round_id']) ? $params['event']['data']['game']['round']['round_id'] : null;
		$insertRecord['total_bet'] 				   = isset($params['event']['data']['total_bet']) ? $params['event']['data']['total_bet'] : null;
		$insertRecord['total_win'] 				   = isset($params['event']['data']['total_win']) ? $params['event']['data']['total_win'] : null;
		$insertRecord['denomination'] 			   = isset($params['event']['data']['denomination']) ? $params['event']['data']['denomination'] : null;
		$insertRecord['user'] 				   	   = isset($params['event']['data']['user']) ? json_encode($params['event']['data']['user']) : null;
		$insertRecord['user_id'] 				   = isset($params['event']['data']['user']['agregator_user_id']) ? $params['event']['data']['user']['agregator_user_id'] : null;
		$insertRecord['currency_rate'] 			   = isset($params['event']['data']['currency_rate']) ? json_encode($params['event']['data']['currency_rate']) : null;
		$insertRecord['event_id'] 				   = isset($params['event']['event_id']) ? $params['event']['event_id'] : null;
		$insertRecord['time'] 				       = isset($params['event']['time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $params['event']['time'])) : null;
		$insertRecord['date'] 				   	   = isset($params['event']['date']) ? $params['event']['date'] : null;
		$insertRecord['type'] 				       = isset($params['event']['type']) ? $params['event']['type'] : null;
		$insertRecord['type_code'] 				   = isset($params['event']['type_code']) ? $params['event']['type_code'] : null;
		$insertRecord['system_id'] 				   = isset($params['event']['system_id']) ? $params['event']['system_id'] : null;
		$insertRecord['system_key'] 			   = isset($params['event']['system_key']) ? $params['event']['system_key'] : null;
		$insertRecord['external_uniqueid'] 		   = isset($params['event']['event_id']) ? $params['event']['event_id'] : null;
		$insertRecord['extra'] 				       = isset($params) ? json_encode($params) : null;

        $params = $insertRecord;
        return $params;
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
    	$dateFrom = new DateTime($dateFrom);
        $dateFrom = $dateFrom->modify($this->getDatetimeAdjustSyncMerge());
        $dateFrom = $dateFrom->format('Y-m-d H:i:s');

        $modify_datetto = '+' . $this->adjust_dateto_minutes_sync_merge . ' minutes';
    	$dateTo = new DateTime($dateTo);
        $dateTo = $dateTo->modify($modify_datetto);
        $dateTo = $dateTo->format('Y-m-d H:i:s');

        $sqlTime = '`evo`.`time` >= ?
          AND `evo`.`time` <= ?';

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    evo.game_id as game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    gpa.login_name AS player_username,
    evo.user_id AS evo_user_id,
    evo.round_id AS round_id,
    evo.pay_for_action_this_round AS bet_amount,
    evo.pay_for_action_this_round AS real_betting_amount,
	evo.total_win AS win_amount,
	evo.denomination,
    evo.time AS start_at,
    evo.time AS end_at,
    evo.external_uniqueid AS external_uniqueid,
    evo.md5_sum AS md5_sum
    FROM {$this->original_gamelogs_table} AS evo
	LEFT JOIN game_description AS gd ON evo.type = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type AS gt ON gd.game_type_id = gt.id
	LEFT JOIN game_provider_auth AS gpa ON evo.user_id = gpa.external_account_id
	AND gpa.game_provider_id = ?
WHERE
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
            'table' =>  $row['external_uniqueid'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        /** win amount = or $this->original_gamelogs_table.total_win times(x) $this->original_gamelogs_table.denomination */
       /** bet amount = or $this->original_gamelogs_table.total_bet times(x) $this->original_gamelogs_table.denomination */
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
                'result_amount' => (($row['win_amount'] * $row['denomination']) - $row['bet_amount']),
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => 0,
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
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
		return $this->returnUnimplemented();
    }

    public function queryGameListFromGameProvider($extra = NULL) {

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider'
        );

		$params = array(
			"project"   => $this->project_number,
			"version"   => $this->version_number
		);

		$params['signature'] = $this->generateSignature($params);
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

        $this->CI->utils->debug_log('---------- EVOPLAY processResultForQueryGameListFromGameProvider response ----------', $resultArr);

        if ($success) {
            $result['games'] = $resultArr;
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
}
/*end of file*/
