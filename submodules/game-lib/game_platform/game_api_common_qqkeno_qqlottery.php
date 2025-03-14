<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: QQKENOQQLOTTERY
	*
	* @category Game_platform
	* @version V1.2
	* @copyright 2013-2022 tot
	* @integrator @andy.php.ph
	*
	* API DOC: QQ_Lottery_Balance_Transfer_integration_specification(EN)_v1.2.pdf
**/

class Game_api_common_qqkeno_qqlottery extends Abstract_game_api {
	# Fields in qqkenolottery_idr_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
    	'ticket_detail_id',//(bigint) Ticket detail ID. Unique
        'ticket_id',//Varchar(20) Ticket number.
        'acct_id',//(Varchar 30) Acct’s unique Id
        'bet_time',//(Char 15) Datetime when bet Ex. 20120722T125417
        'game_code',//(Varchar 50) Code of the game that ticket is waged on.
        'market',//(Varchar 10) Market where game belongs.
        'draw_number',//(Varchar 128) Ex. 2012072213 Draw number
        'bet_type',//(Varchar 10) Bet type
        'bet_amount',//(Decimal(18,4) Bet Amount
        'bet_choice',//(Varchar 200) Betting details
		'result',//(Varchar 60) Result, Ex. Small
		'result_time',//(char 15) Result time, Ex. 20120722T230417
		'winloss',//(Decimal(20,4)) Member win loss., Ex. -20
		'currency',//(Char 3) Currency Code
		'bet_unit',//(Decimal(18,4)) Bet Unit, according to gp its the same as bet amount
		'bet_count',//(int) Bet Amount, Ex. 1
		'odds',//(Decimal(18,4)) The betting odds, Ex. 1.95
        'bet_ip',//(Varchar(20)) Bettor’s IP Address
        'cancelled',//(Boolean) Indicate whether cancelled or not
        'channel',//(Varchar(20)) The bet terminal, Ex. Web/Mobile
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'winloss',
        'bet_unit',
        'odds',
    ];

    # Fields in game_logs we want to detect changes for merge, and when qqkenolottery_idr_game_logs.md5_sum is empty
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
		self::API_queryPlayerBalance => 'getAcctInfo',
		self::API_depositToGame => 'deposit',
		self::API_withdrawFromGame => 'withdraw',
		self::API_syncGameRecords => 'getBetHistory',
	);

	const ORIGINAL_GAMELOGS_TABLE = "qqkenoqqlottery_game_logs";
	const SUCCESS_CODE = 0;
	const PAGE_INDEX_START = 1;

	public function __construct() {
		parent::__construct();
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->api_url = $this->getSystemInfo('url');
		$this->game_launch_url = $this->getSystemInfo('game_launch_url');
		$this->merchant_code = $this->getSystemInfo('merchant_code');
		$this->api_call = null;
	}

	public function callback($method, $postData = null)
	{
		if ($method == 'authorize' && $postData)
		{
			if($this->getSystemInfo('callback_test_mode',false)) {
				return [
						"merchantCode" => $this->merchant_code,
						"acctInfo" => [
											"acctId" => $postData['acctId'],
											"balance" => 0,
											"userName" => $postData['acctId'],
											"currency" => $this->currency_type
										 ],
						"code" => self::SUCCESS_CODE,
						"msg" => "Success",
						"serialNo" => $postData['serialNo']
					   ];
			}

			$playerInfo = $this->getPlayerInfoByToken($postData['token']);
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

			if(!empty($gameUsername)) {
				$responseArr['merchantCode'] = $this->merchant_code;
				$responseArr['acctInfo'] = [
											 "acctId" => $gameUsername,
											 "balance" => 0,#always return zero as per game provider
											 "userName" => $gameUsername,
											 "currency" => $this->currency_type
										   ];
				$responseArr['code'] = self::SUCCESS_CODE;
				$responseArr['msg'] = "Success";
				$responseArr['serialNo'] = $postData['serialNo'];
				return $responseArr;
			}else{
				$this->utils->debug_log("Invalid Token QQKENOQQLOTTERY: ",$this->getPlatformCode());
			}
		}
	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params)
	{
		$this->api_call = $apiName;
		return $this->api_url;
	}

	protected function customHttpCall($ch, $params)
	{
        $data_json = json_encode($params);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    protected function getHttpHeaders($params)
	{
		$headers['DataType'] = 'JSON';
		$headers['API'] = self::URI_MAP[$this->api_call];
		return $headers;
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if(($statusCode == 200 || $statusCode == 201) && $resultArr['code'] == self::SUCCESS_CODE){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ISIN4D got error ', $responseResultId,'result', $resultArr);
		}
		return $success;
	}

	/*
	 * Create Player is during fund transfer to API,
	 * so they dont provide api to register player
	 */
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
	{
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$success = false;
		$message = "Unable to create Account for QQKKENO_QQLOTTER";
		if($return){
			$success = true;
			$message = "Successfull create account for QQKKENO_QQLOTTER";
		}
		return array("success"=>$success,"message"=>$message);
	}

	private function processPlayerLanguageForParams($lang){
		switch ($lang) {
			case "Chinese": return "cn"; break;
			case "English": return "en"; break;
			case "Japanese": return "jp"; break;
			case "Korean": return "kr"; break;
			case "Thai": return "th"; break;
			case "Vietnamese": return "vn"; break;
			case "Indonesian": return "id"; break;

			default:
				return "cn";
				break;
		}
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
			'acctId' => $gameUsername,
			'pageIndex' => self::PAGE_INDEX_START,
			'merchantCode' => $this->merchant_code,
			'serialNo' => $this->generateSerialNo()
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	private function generateSerialNo(){
		$dt = new DateTime($this->utils->getNowForMysql());
		return $dt->format('YmdHis').random_string('numeric', 6);
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
			if(isset($resultArr['list']) && !empty($resultArr['list'])){
				$result['balance'] = $resultArr['list'][0]['balance'];
			}else
{				$success = false;
			}
		}
		return array($success, $result);
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'acctId' => $gameUsername,
			'currency' => $this->currency_type,
			'amount' => $this->dBtoGameAmount($amount),
			'merchantCode' => $this->merchant_code,
			'serialNo' => $external_transaction_id,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
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

		$code = isset($resultArr['code']) ? $resultArr['code'] : null;
		if(is_null($code)) {
			$result['didnot_insert_game_logs'] = true;
			$success = false;
		}

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	private function getReasons($statusCode)
	{
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'acctId' => $gameUsername,
			'currency' => $this->currency_type,
			'amount' => $this->dBtoGameAmount($amount),
			'merchantCode' => $this->merchant_code,
			'serialNo' => $external_transaction_id,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
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

		$code = isset($resultArr['code']) ? $resultArr['code'] : null;
		if(is_null($code)) {
			$result['didnot_insert_game_logs'] = true;
			$success = false;
		}

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
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  player_center/goto_qqgame/<game_platform_id>
	 *
	 * 	Sample URL: http://lobby-staging.qqcp518.net/entaplay/auth/?acctId=xxxx&language=en&token=xxxx
	 * 	Once this url is called, the game provider will send callback to our end
	 *
	 * 	Ex.callback/game/<game_provider_id>/authorize
	 * 	callback/game/2092/authorize
	 *
	 */
	public function queryForwardGame($playerName, $extra = null)
	{
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		#GET LANG FROM PLAYER DETAILS
		$lang = $this->processPlayerLanguageForParams($this->getPlayerDetails($playerId)->language);

		#IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL, THEN INCLUDE IN LOGIN TOKEN
		if(isset($extra['language'])){
			$language = $extra['language'];
		}

		$params = [
					"acctId" => $gameUsername,
					"language" => $language,
					"gamecode" => $extra['game_code'],
					"token" => $this->getPlayerTokenByUsername($playerName),
				  ];

		$url = $this->game_launch_url."/".$this->merchant_code."/auth/?".http_build_query($params);
		return ['success' => true,'url' => $url];
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$success = false;
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

		$page = self::PAGE_INDEX_START;
		$done = false;
		while (!$done) {
			$params = [
						'beginDate' => $startDateTime->format("Ymd\THis"),
						'endDate' => $endDateTime->format('Ymd\THis'),
						'pageIndex' => $page,
						'merchantCode' => $this->merchant_code,
						'serialNo' => $this->generateSerialNo(),
					  ];

			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			$done = true;
			if ($api_result && $api_result['success']) {
				$total_page = @$api_result['total_page'];
				$total_row = @$api_result['total_row'];
				//next page
				$page += 1;
				$done = $page > $total_page;
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
		$gameRecords = !empty($resultArr['list'])?$resultArr['list']:[];

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

			$result['total_page'] = $resultArr['pageCount'];
			$result['total_row'] = $resultArr['resultCount'];
		}

		return array($success, $result);
	}

	private function rebuildGameRecords(&$gameRecords,$extra)
	{
		$newGR =[];
        foreach($gameRecords as $i => $gr)
        {
        	$ticket_detail_id = isset($gr['ticketDetailId'])?$gr['ticketDetailId']:null;
        	$bet_result_amount = 0;
        	if(isset($gr['winLoss'])){
        		$bet_result_amount =  $gr['winLoss']; #win - bet
        	}
        	$newGR[$i]['ticket_detail_id'] = $ticket_detail_id;
        	$newGR[$i]['ticket_id'] = isset($gr['ticketId'])?$gr['ticketId']:null;
        	$newGR[$i]['acct_id'] = isset($gr['acctId'])?$gr['acctId']:null;
        	$newGR[$i]['bet_time'] = isset($gr['betTime'])?$gr['betTime']:null;
        	$newGR[$i]['bet_amount'] = isset($gr['betAmount'])?$gr['betAmount']:null;
        	$newGR[$i]['game_code'] = isset($gr['gameCode'])?$gr['gameCode']:null;
        	$newGR[$i]['market'] = isset($gr['market'])?$gr['market']:null;
        	$newGR[$i]['draw_number'] = isset($gr['drawNumber'])?$gr['drawNumber']:null;
        	$newGR[$i]['bet_type'] = isset($gr['betType'])?$gr['betType']:null;
        	$newGR[$i]['bet_choice'] = isset($gr['betChoice'])?$gr['betChoice']:null;
        	$newGR[$i]['result'] = isset($gr['result'])?$gr['result']:null;
        	$newGR[$i]['result_time'] = isset($gr['resultTime'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($gr['resultTime']))):null;
        	$newGR[$i]['winloss'] = $bet_result_amount;
        	$newGR[$i]['currency'] = isset($gr['currency'])?$gr['currency']:null;
        	$newGR[$i]['bet_unit'] = isset($gr['betUnit'])?$gr['betUnit']:null;
        	$newGR[$i]['bet_count'] = isset($gr['betCount'])?$gr['betCount']:null;
        	$newGR[$i]['odds'] = isset($gr['odds'])?$gr['odds']:null;
        	$newGR[$i]['bet_ip'] = isset($gr['betIp'])?$gr['betIp']:null;
        	$newGR[$i]['cancelled'] = isset($gr['cancelled'])?$gr['cancelled']:null;
        	$newGR[$i]['channel'] = isset($gr['channel'])?$gr['channel']:null;
        	$newGR[$i]['brand_name'] = $this->getSystemInfo('brand_name','no_brand');
            $newGR[$i]['external_uniqueid'] = $ticket_detail_id;
            $newGR[$i]['response_result_id'] = $extra['response_result_id'];
        }
        $gameRecords = $newGR;
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

    public function syncMergeToGameLogs($token)
    {
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
        $sqlTime='`qq`.`result_time` >= ?
          AND `qq`.`result_time` <= ?';
        if($use_bet_time){
            $sqlTime='`qq`.`bet_time` >= ?
          AND `qq`.`bet_time` <= ?';
        }

        $sql = <<<EOD
			SELECT
				qq.id as sync_index,
				qq.response_result_id,
				qq.draw_number as round,
				qq.acct_id as username,
				qq.bet_amount as bet_amount,
				qq.bet_amount as valid_bet,
				qq.winloss as result_amount,
				qq.bet_time as start_at,
				qq.result_time as end_at,
				qq.bet_time as bet_at,
				qq.game_code,
				qq.bet_choice as bet_details,
				qq.cancelled,
				qq.winloss,
				qq.external_uniqueid,
				qq.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as qq
			LEFT JOIN game_description as gd ON qq.game_code = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON qq.acct_id = game_provider_auth.login_name
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
        $extra = null;

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
            'bet_details' => $row['bet_details'],
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

	public function login($playerName, $password = null, $extra = null)
	{
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
}
/*end of file*/