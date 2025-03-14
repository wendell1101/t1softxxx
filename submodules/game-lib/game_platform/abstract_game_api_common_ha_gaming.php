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

abstract class Abstract_game_api_common_ha_gaming extends Abstract_game_api {

	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';
	const METHOD_PUT = 'PUT';

# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'amount',
        'round',
        'game_code',
        'game_name',
        'after_balance',
        'player_username',
        'game_date'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'amount'
    ];

	const URI_MAP = array(
		self::API_generateToken 	 => '/oauth/token',
		self::API_login 			 => '/api/web/user_session/',
		self::API_queryPlayerBalance => '/api/web/balance/',
		self::API_queryForwardGame 	 => '/api/web/user_session/',
		self::API_queryTransaction 	 => '/api/web/query_order/',
		self::API_depositToGame 	 => '/api/web/payin/',
		self::API_withdrawFromGame 	 => '/api/web/payout/',
		self::API_syncGameRecords 	 => '/v1/feed/transaction',
	);

	## --------------------------------------------------------------------------------

	public function __construct() {
		parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
		$this->game_url = $this->getSystemInfo('game_url');
		$this->operator_token = $this->getSystemInfo('operator_token');
		$this->operator_secret = $this->getSystemInfo('operator_secret');
		$this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 16);
		$this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
		$this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 16);
		$this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
		$this->check_username_only = $this->getSystemInfo('check_username_only', true);
		$this->strict_username_with_prefix_length = $this->getSystemInfo('strict_username_with_prefix_length', true);

		$this->default_game_id = $this->getSystemInfo('default_game_id', [102,103,104,110,110,111,121]);
		$this->default_hall_id = $this->getSystemInfo('default_hall_id', 0);

		$this->adjust_dateto_minutes_sync_merge = $this->getSystemInfo('adjust_dateto_minutes_sync_merge', 0);

        $this->CI->load->model(['boomingseamless_game_logs', 'game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token']);
	}

	public function getUniqueTicket() {
		return strtotime("now");
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		if ($apiName == self::API_generateToken || $apiName == self::API_syncGameRecords) {
			$apiUri = self::URI_MAP[$apiName] . '?' . http_build_query($params);
		}
		$url = $this->api_url . $apiUri;

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
	        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    }
    }

    protected function getHttpHeaders($params)
	{
		$headers['Content-Type'] = 'application/json';
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if ((@$statusCode == 200 || @$statusCode == 201) && !isset($resultArr['error']) && $resultArr['status'] == 0) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('HA GAME API got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName = null, $playerId = null, $password = null, $email = null, $extra = null)
	{

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' 	  			 => true,
            'minimum_user_length' 	  			 => $this->minimum_user_length,
            'maximum_user_length' 	  			 => $this->maximum_user_length,
            'default_fix_name_length' 			 => $this->default_fix_name_length,
            'check_username_only'     		     => $this->check_username_only,
            'strict_username_with_prefix_length' => $this->strict_username_with_prefix_length
        ];

		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$success = $this->userSession($playerName);

		$message = "Unable to create Account for HA GAME API";
		if($return && $success['success']){
			$success = true;
			$message = "Successfull create account for HA GAME API";
		}

		return array("success" => $success, "message" => $message);
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
			'operator_token' 	=> $this->operator_token,
			'user_id'   	 	=> $gameUsername,
			'ts'  		     	=> strtotime("now")
		);

		$new_params = $params;
		ksort($new_params);
		$new_params['key'] = $this->operator_secret;
		$sign = md5(http_build_query($new_params));
		$params['sign'] = $sign;

		$this->method = self::METHOD_POST;
		$this->API_name = self::API_queryPlayerBalance;
        $this->CI->utils->debug_log('---------- HA GAME API queryPlayerBalance params ----------', $params);

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

        $this->CI->utils->debug_log('---------- HA GAME API processResultForQueryPlayerBalance response ----------', $resultArr);

		if($success){
			$bal = @floatval($resultArr['data']['balance']);
			$result['balance'] = $this->convertAmountToDB($bal);
		}

		return array($success, $result);
	}

	private function getReasons($status)
	{
		switch ($status) {
			case 4016:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function queryTransaction($transactionId, $extra) {
		$order_type = 'payin';
		if ($extra['transfer_method'] == 'withdrawal') {
			$order_type = 'payout';
		}
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
			'operator_token' => $this->operator_token,
			'order_no' 	 	 => $transactionId,
			'order_type' 	 => $order_type,
			'ts' 		 	 => strtotime("now")
		);

		$new_params = $params;
		ksort($new_params);
		$new_params['key'] = $this->operator_secret;
		$sign = md5(http_build_query($new_params));
		$params['sign'] = $sign;

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- HA GAME API queryTransaction params ----------', $params);

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

        $this->CI->utils->debug_log('---------- HA GAME API processResultForQueryTransaction response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr['msg']);
		}

        return array($success, $result);   
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

		$amount = $this->dBtoGameAmount($amount);

		$params = array(
			'operator_token' 	=> $this->operator_token,
			'operator_order_no' => $external_transaction_id,
			'user_id'   	 	=> $gameUsername,
			'amount' 	 		=> $amount,
			'ts'  		     	=> strtotime("now")
		);

		$new_params = $params;
		ksort($new_params);
		$new_params['key'] = $this->operator_secret;
		$sign = md5(http_build_query($new_params));
		$params['sign'] = $sign;

		$this->method = self::METHOD_POST;
		$this->API_name = self::API_depositToGame;
        $this->CI->utils->debug_log('---------- HA GAME API depositToGame params ----------', $params);

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

        $this->CI->utils->debug_log('---------- HA GAME API processResultForDepositToGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			if ($resultArr['status'] != 0) {
	            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			// $result['reason_id'] = $this->getReasons($resultArr['msg']);
		}

        return array($success, $result);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
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
		$amount = $this->dBtoGameAmount($amount);

		$params = array(
			'operator_token' 	=> $this->operator_token,
			'operator_order_no' => $external_transaction_id,
			'user_id'   	 	=> $gameUsername,
			'amount' 	 		=> $amount,
			'ts'  		     	=> strtotime("now")
		);

		$new_params = $params;
		ksort($new_params);
		$new_params['key'] = $this->operator_secret;
		$sign = md5(http_build_query($new_params));
		$params['sign'] = $sign;

		$this->method = self::METHOD_POST;
		$this->API_name = self::API_withdrawFromGame;
        $this->CI->utils->debug_log('---------- HA GAME API withdrawFromGame params ----------', $params);

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

        $this->CI->utils->debug_log('---------- HA GAME API processResultForDepositToGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			if ($resultArr['status'] != 0) {
	            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			// $result['reason_id'] = $this->getReasons($resultArr['msg']);
		}

        return array($success, $result);
	}

    /*
	 *	To Launch Game
	 *
	*/
	public function userSession($playerName)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $token = $this->getPlayerToken($playerId);

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForUserSession',
			'gameUsername'    => $gameUsername,
			'playerId'    	  => $playerId,
			'token'		      => $token
		);

		$params = array(
			'operator_token' => $this->operator_token,
			'user_id'   	 => $gameUsername,
			'user_name' 	 => $gameUsername,
			'user_session'   => $token,
			'ts'  		     => strtotime("now")
		);

		ksort($params);
		$params['key'] = $this->operator_secret;
		$sign = md5(http_build_query($params));
		$params['sign'] = $sign;
		unset($params['key']);

		$this->utils->debug_log("=== HA GAME API: queryFowardGame ====>");

		$this->method = self::METHOD_POST;
		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForUserSession($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$gameUsername     = $this->getVariableFromContext($params, 'gameUsername');
		$token         	  = $this->getVariableFromContext($params, 'token');
		$playerId 		   = $this->getVariableFromContext($params, 'playerId');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result           = array('token' => $token);

		if($success){
			$this->CI->utils->debug_log('HA GAME API userSession ==>', @$resultArr);
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}

		return array($success, $result);
	}

	public function callback($request) {
		$data = $this->rebuildOriginalGameLogs($request);
        return $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($this->original_gamelogs_table, $data);
	}

	private function rebuildOriginalGameLogs($data) {
		$data['extra'] = json_encode($data);
		$data['external_uniqueid'] = isset($data['trans_id']) ? $data['trans_id'] : null;
		$data['date'] = isset($data['ts']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', $data['ts'])) : null;
		return $data;
	}	

	/*
	 *	To Launch Game
	 *
	*/
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$session = $this->userSession($playerName);
		$url = '';
		if ($session['success']) {
			$params = array(
				'operator_token' => $this->operator_token,
				'operator_player_session' => $session['token'],
				'operator_player_id' => $gameUsername
			);

			$url = $this->game_url . '?' . http_build_query($params);

			if (isset($extra['game_code']) && !empty($extra['game_code']) && $extra['game_code'] != 'main') {
				$url .= '&game_id=' . $extra['game_code'];
			} else {
				// IF MULTIPLE GAME_ID IT REQUIRES HALL_ID
				if (!empty($this->default_game_id)) {
					$url .= '&game_id=' . implode(",", $this->default_game_id) . '&hall_id=' . $this->default_hall_id;
				}
			}
		}
		$this->utils->debug_log("=== HA GAME API: queryFowardGame ====>");
		return ['success' => $session['success'], 'url' => $url];
	}

	/*
	 * GAME LOGS VIA CALLBACK
	 */
	public function syncOriginalGameLogs($token = false)
	{
		return $this->returnUnimplemented();
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

        $sqlTime='`ha`.`date` >= ?
          AND `ha`.`date` <= ?';

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.sub_game_provider AS sub_game_provider,
    gd.id AS game_description_id,
    ha.type AS game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    ha.player_id AS player_username,
    ha.amount AS amount,
    ha.balance AS after_balance,
    ha.date AS game_date,
    ha.external_uniqueid AS external_uniqueid,
    ha.term AS round,
    ha.md5_sum AS md5_sum,
    ha.term AS round_id
FROM {$this->original_gamelogs_table} as ha
	LEFT JOIN game_description as gd ON ha.type = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
	LEFT JOIN game_provider_auth as gpa ON ha.player_id = gpa.login_name
	AND gpa.game_provider_id = ?
WHERE
	ha.type != ?
AND
    {$sqlTime}
EOD;
	
		$new_record = [];

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            0,
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
        $extra = [
            'table' =>  $row['round'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $status = Game_logs::STATUS_SETTLED;

        $bet = 0;
        $payout = 0;
        // GREATE THAN 0 = PAYOUT, NEGATIVE = BET
        if ($row['amount'] > 0) {
        	$payout = @floatval($row['amount']);
			$payout = $this->convertAmountToDB($payout);
        	$bet = 0;
        } else if ($row['amount'] < 0) {
        	$payout = 0;
        	$bet = @floatval($row['amount']);
        	$bet = $this->convertAmountToDB($bet) * -1;
        }

        $after_balance = @floatval($row['after_balance']);
    	$after_balance = $this->convertAmountToDB($after_balance);

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
                'bet_amount' => $bet,
                'result_amount' => $payout - $bet,
                'bet_for_cashback' => $bet,
                'real_betting_amount' => $bet,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance,
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

	public function saveToResponseResult($success, $callMethod, $params, $response){
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag, $callMethod, json_encode($params), $response, 200, null, null);
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
