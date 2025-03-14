<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: TANGKAS1
	* API docs: http:://apiag.tangkas1.com/doc/
	*
	* @category Game_platform
	* @version not defined
	* @copyright 2013-2022 tot
	* @integrator @andy.php.ph
**/

class Game_api_common_tangkas1 extends Abstract_game_api {

	# Fields in tangkas1_idr_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'table_name',
        'table_type_text',
        'table_type',
        'table_multiply',
        'table_coin',
        'currency',
        'username',
        'amount',
        'result_end',
        'step',
        'balance_start',
        'balance_end',
        'credit_start',
        'credit_end',
        'start_time',
        'end_time',
        'duration',
        'cards',
        'wl',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'wl',
    ];

    # Fields in game_logs we want to detect changes for merge, and when tangkas1_idr_game_logs.md5_sum is empty
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

	const API_ACTION_KEYS = [
								self::API_createPlayer => 'LOGIN',
								self::API_login => 'LOGIN',
								self::API_queryPlayerBalance => 'USERINFO',
								self::API_depositToGame => 'SETCREDIT',
								self::API_withdrawFromGame => 'SETCREDIT',
								self::API_syncGameRecords => 'GETDATEHISTORY',
							];

	const ORIGINAL_GAMELOGS_TABLE = "tangkas1_game_logs";
	const SUCCESS_CODE = "SUCCESS";
	const GAME_CODE = "tangkas";

	const DEFAULT_GAME_LOGS_API_TOTAL_ROW = 1000;
	const SYNC_GAME_LOG_START_PAGE = 1;

	private $active_player_token = null;
	private $active_player_game_launch_url = null;

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency');
		$this->common_api_params = [
								 "id" => $this->getSystemInfo('key'),
								 "secret" => $this->getSystemInfo('secret'),
							   ];
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {
		return $this->api_url;
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201) && $resultArr['result']['code'] == self::SUCCESS_CODE){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('TANGKAS1 got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
	{
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'action_key' => self::API_ACTION_KEYS[self::API_createPlayer],
			'account_code' => $gameUsername,
			'token' => $this->getPlayerToken($playerId)
		);

		return $this->callApi(self::API_createPlayer, array_merge($params,$this->common_api_params), $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['player' => $gameUsername];
		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
		}
		return array($success, $result);
	}

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function gameAmountToDB($amount) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
        // return $amount / $conversion_rate;
    }

	public function queryPlayerBalance($playerName) {
		$playerId = $this->getPlayerIdFromUsername($playerName);

		// CALL LOGIN API FIRST TO SET ACTIVE TOKEN FROM GAME API SIDE
		$this->login($playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			'action_key' => "USERINFO",
			'account_code' => $gameUsername,
			'token' => $this->active_player_token
		);

		return $this->callApi(self::API_queryPlayerBalance, array_merge($params,$this->common_api_params), $context);
	}

	public function processResultForQueryPlayerBalance($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');

		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

		if($success){
			$result['balance'] = @floatval($this->gameAmountToDB($resultArr['data']['balance']));
		}

		return array($success, $result);
	}

	/*
	 *	LOGIN API, is use to query player balance, deposit, withdraw and get game launch url
	 *	FYI: Game API did not provide param to launch it as demo
	 *
	 *	GAME API PARAMS:
	 *  param id (provided by game provider)
	 *  param secret (provided by game provider)
	 *  param action_key value should be "LOGIN"
	 *  param account_code this should be our player's game username
	 *  param token we provide this
	 */
	public function login($playerName, $password = null, $extra = null)
	{
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$apiToken = $this->getPlayerToken($playerId);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
			'apiToken' => $apiToken
		);

		$params = array(
			'action_key' => self::API_ACTION_KEYS[self::API_login],
			'account_code' => $gameUsername,
			'token' => $this->getPlayerToken($playerId)
		);

		return $this->callApi(self::API_login, array_merge($params,$this->common_api_params), $context);
	}

	public function processResultForLogin($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$apiToken = $this->getVariableFromContext($params, 'apiToken');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$this->active_player_token = null;
		$this->active_player_game_launch_url = null;

		if($success){
			$this->active_player_token = $apiToken;
			$this->active_player_game_launch_url = $resultArr['url'];
		}
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null)
	{
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }
        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null)
	{
		// CALL LOGIN API FIRST TO SET ACTIVE TOKEN FROM GAME API SIDE
		$this->login($playerName);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$external_transaction_id = empty($transfer_secure_id) ? 'TD'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'action_key' => self::API_ACTION_KEYS[self::API_depositToGame],
			'account_code' => $gameUsername,
			'token' => $this->active_player_token,
			'agent_transaction_id' => $external_transaction_id,
			'type' => "DEPOSIT",
			'amount' => $this->dBtoGameAmount($amount),
		);

		return $this->callApi(self::API_depositToGame, array_merge($params,$this->common_api_params), $context);
	}

	public function processResultForDepositToGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	private function getReasons($statusCode){
		switch ($statusCode) {
			case 400:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 401:
				return self::REASON_INVALID_KEY;
				break;
			case 404:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 409:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 500:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
	{
		// CALL LOGIN API FIRST TO SET ACTIVE TOKEN FROM GAME API SIDE
		$this->login($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'TW'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'action_key' => self::API_ACTION_KEYS[self::API_withdrawFromGame],
			'account_code' => $gameUsername,
			'token' => $this->active_player_token,
			'agent_transaction_id' => $external_transaction_id,
			'type' => "WITHDRAW",
			'amount' => $this->dBtoGameAmount($amount),
		);

		return $this->callApi(self::API_withdrawFromGame, array_merge($params,$this->common_api_params), $context);
	}

	public function processResultForWithdrawFromGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
			$result['external_transaction_id'] = $external_transaction_id;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	/*
	 *	To Launch Game, just call game provider's login API,
	 *  then it will return the url that we can use to redirect our player
	 *
	 * 	GAME API CODE:
	 * 	TANGKAS1_IDR = 5434
	 * 	TANGKAS1_IDR = 2072
	 * 	TANGKAS1_CNY = 2073
	 * 	TANGKAS1_THB = 2074
	 * 	TANGKAS1_USD = 2075
	 * 	TANGKAS1_VND = 2076
	 * 	TANGKAS1_MYR = 2077
	 *
	 *  Player Url: http://player.domain.t1t.in/iframe_module/goto_game/2072
	 */
	public function queryForwardGame($playerName, $extra = null)
	{
		// CALL LOGIN API FIRST TO SET ACTIVE TOKEN FROM GAME API SIDE
		$this->login($playerName);

		$result = [
					'success' => false,
				    'url' => null
				  ];

		if($this->active_player_game_launch_url){
			$result['success'] = true;
			$result['url'] = $this->active_player_game_launch_url;
		}

		return $result;
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$queryDateTimeStart = $startDateTime->format("YmdHis");
		$queryDateTimeEnd = $endDateTime->format('YmdHis');

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

		$page = self::SYNC_GAME_LOG_START_PAGE;
		$done = false;
		while (!$done) {
			$params = [
						'action_key' => self::API_ACTION_KEYS[self::API_syncGameRecords],
						'total_row' => self::DEFAULT_GAME_LOGS_API_TOTAL_ROW,
						'page' => $page,
						'start_date' => $queryDateTimeStart,
						'end_date' => $queryDateTimeEnd,
					  ];

			$api_result = $this->callApi(self::API_syncGameRecords, array_merge($params,$this->common_api_params), $context);

			$done = true;
			if ($api_result && $api_result['success']) {
				$total_page = @$api_result['total_page'];
				$total_row = @$api_result['total_row'];
				//next page
				$page += 1;
				$done = $page >= $total_page;
				$this->CI->utils->debug_log('page: ',$page,'total_row:',$total_row,'total_page:', $total_page, 'done', $done, 'result', $api_result);
			}
			if ($done) {
				$success = true;
			}
		}
		return array('success' => $success);
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['data_count' => 0];
		$gameRecords = isset($resultArr['data'])?$resultArr['data']:[];

		if($success&&!empty($gameRecords))
		{
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

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

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);

            $result['current_page'] = $resultArr['current_page'];
			$result['total_page'] = $resultArr['total_page'];
			$result['total_row'] = $resultArr['total_row'];
		}

		return array($success, $result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra)
	{
		$newGR =[];
        foreach($gameRecords as $i => $gr)
        {
			$newGR[$i]['idhistory'] = isset($gr['idhistory'])?$gr['idhistory']:null;
			$newGR[$i]['invoice_number'] = isset($gr['invoice_number'])?$gr['invoice_number']:null;
			$newGR[$i]['game_name'] = $newGR[$i]['game_code'] = self::GAME_CODE;
			$newGR[$i]['table_name'] = isset($gr['table_name'])?$gr['table_name']:null;
			$newGR[$i]['table_type_text'] = isset($gr['table_type_text'])?$gr['table_type_text']:null;
			$newGR[$i]['table_type'] = isset($gr['table_type'])?$gr['table_type']:null;
			$newGR[$i]['table_multiply'] = isset($gr['table_multiply'])?$gr['table_multiply']:null;
			$newGR[$i]['table_coin'] = isset($gr['table_coin'])?$gr['table_coin']:null;
			$newGR[$i]['currency'] = isset($gr['currency'])?$gr['currency']:null;
			$newGR[$i]['username'] = isset($gr['username'])?$gr['username']:null;

			$newGR[$i]['amount'] = isset($gr['amount'])?$this->processRecordsForReport($gr['amount']):null;
			$newGR[$i]['result_end'] = isset($gr['result_end'])?$gr['result_end']:null;
			$newGR[$i]['step'] = isset($gr['step'])?$gr['step']:null;
			$newGR[$i]['balance_start'] = isset($gr['balance_start'])?$this->processRecordsForReport($gr['balance_start']):null;
			$newGR[$i]['balance_end'] = isset($gr['balance_end'])?$this->processRecordsForReport($gr['balance_end']):null;
			$newGR[$i]['credit_start'] = isset($gr['credit_start'])?$this->processRecordsForReport($gr['credit_start']):null;
			$newGR[$i]['credit_end'] = isset($gr['credit_end'])?$this->processRecordsForReport($gr['credit_end']):null;
			$newGR[$i]['start_time'] = isset($gr['start_time'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($gr['start_time']))):null;
			$newGR[$i]['end_time'] = isset($gr['end_time'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($gr['end_time']))):null;
			$newGR[$i]['duration'] = isset($gr['duration'])?$gr['duration']:null;
			$newGR[$i]['cards'] = isset($gr['cards'])?$gr['cards']:null;
			$newGR[$i]['wl'] = isset($gr['wl'])?$this->processRecordsForReport($gr['wl']):null;
            $newGR[$i]['external_uniqueid'] = isset($gr['invoice_number'])?$gr['invoice_number']:$gr['idhistory'];
            $newGR[$i]['response_result_id'] = $extra['response_result_id'];
        }
        $gameRecords = $newGR;
	}

	private function processRecordsForReport($amount){
		$use_currency_convertion_in_report = $this->getSystemInfo('use_currency_convertion_in_report',false);
		$conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
		if($use_currency_convertion_in_report){
			return $amount;
		}
		return $amount/$conversion_rate;
	}

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`tangkas`.`end_time` >= ?
          AND `tangkas`.`end_time` <= ?';
        if($use_bet_time){
            $sqlTime='`tangkas`.`start_time` >= ?
          AND `tangkas`.`start_time` <= ?';
        }

        $sql = <<<EOD
			SELECT
				tangkas.id as sync_index,
				tangkas.response_result_id,
				tangkas.invoice_number as round,
				tangkas.username,
				tangkas.amount as bet_amount,
				tangkas.amount as valid_bet,
				tangkas.wl as result_amount,
				tangkas.start_time as start_at,
				tangkas.end_time as end_at,
				tangkas.start_time as bet_at,
				tangkas.game_code,
				tangkas.game_name,
				tangkas.credit_end as after_balance,
				tangkas.updated_at,
				tangkas.external_uniqueid,
				tangkas.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as tangkas
			LEFT JOIN game_description as gd ON tangkas.game_code = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON tangkas.username = game_provider_auth.login_name
			AND game_provider_auth.game_provider_id=?
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
            'table' =>  $row['round'],
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
                'game_type' => $row['game_type_id'],
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
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
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

	public function isPlayerExist($playerName){
		return array(true, ['success' => true, 'exists' => true]);
    }

    public function queryTransaction($transactionId, $extra) {
		$this->unimplemented();
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