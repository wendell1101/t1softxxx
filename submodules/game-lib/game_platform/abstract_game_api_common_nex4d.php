<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: Nex4d game
	* API docs: 
	* OGP-32037
	* @category Game_platform
	* @copyright 2013-2024 tot
	* @integrator @johmison.php.ph
**/

abstract class Abstract_game_api_common_nex4d extends Abstract_game_api {
	const POST = 'POST';
	const GET = 'GET';
	const API_queryRequestBO = 'requestBOurl';
	const API_syncGameRecords_settled = 'syncGameRecords_settled';
	const API_syncGameRecords_unsettled = 'syncGameRecords_unsettled';
	const API_getDrawListInfo = 'getDrawListInfo';

	# Fields in jili_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
       'process_date'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
		'valid_turnover',
		'stake',
		'payout',
		'odds'
    ];

	const MD5_FIELDS_FOR_MERGE = [
		'external_uniqueid',
        'bet_amount',
        'game_code',
		'status'
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'after_balance',
        'bet_amount',
        'valid_bet',
        'result_amount',
	];

    public $method, $opCode, $secretKey, $URI_MAP,$METHOD_MAP, $url,$agentCode,$game_url,$bo_username,$admin_url,$original_gamelogs_table;

	public function __construct() {
		parent::__construct();

		$this->original_gamelogs_table = 'nex4d_game_logs';   
		$this->method = self::POST; # default as POST
		$this->opCode = $this->getSystemInfo('opCode');
		$this->secretKey = $this->getSystemInfo('secretKey');
        $this->url = $this->getSystemInfo('url');
        $this->agentCode = $this->getSystemInfo('agentCode');
        $this->game_url = $this->getSystemInfo('game_url');
        $this->bo_username = $this->getSystemInfo('bo_username');
        $this->admin_url = $this->getSystemInfo('admin_url');
		$this->URI_MAP = array(
            self::API_createPlayer => '/registerPlayer',
            self::API_queryForwardGame => '/playerLogin',
			self::API_queryPlayerBalance => "/playerBalance",
			self::API_depositToGame => "/transferIn",
			self::API_withdrawFromGame => "/transferOut",
			self::API_queryRequestBO => "/requestBOLoginUrl",
			self::API_syncGameRecords_settled => "/getPlayerBetSummPage",
			self::API_syncGameRecords_unsettled => "/getPlayerOutstandingBetPage",
			self::API_getDrawListInfo => "/api/result/getDrawListInfo",
		);
		$this->METHOD_MAP = array(
			self::API_createPlayer => self::POST,
			self::API_queryForwardGame => self::POST,
			self::API_queryPlayerBalance => self::POST,
			self::API_depositToGame => self::POST,
			self::API_withdrawFromGame => self::POST,
			self::API_queryRequestBO => self::POST,
			self::API_syncGameRecords_settled => self::POST,
			self::API_syncGameRecords_unsettled => self::POST,
			self::API_getDrawListInfo => self::POST,
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
			'username'=>  $gameUsername,
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode,
		);

        $params['securityToken'] = $this->generateSecurityToken($params);
        $this->method = self::POST;
		return $this->callApi(self::API_createPlayer, $params, $context);
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


	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('nex4d (queryPlayerBalance)', $playerName);	
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'username' => $gameUsername,
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode
		);

		$params['securityToken'] = $this->generateSecurityToken($params);

		$this->method = self::POST;
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('nex4d (processResultForQueryPlayerBalance)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = [];

		if($success){
			$result['balance'] = $this->convertAmountToDB(floatval($resultArr['balance']));
		}

		return array($success, $result);
	}

	public function callback($method) {
        if($method == 'requestBoUrl') {
            return $response = $this->requestBoUrl();    
        }else if ($method == 'getDrawListInfo'){
            return $this->getDrawListInfo();
        }else{
			return false;
		}
    }

	public function requestBoUrl() {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryrequestBoUrl',
		);

		$params = array(
			'agentCode' => $this->agentCode,
			'username' => $this->bo_username,
			'ipAddress' => $this->utils->getIP(),
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode
		);

		$params['securityToken'] = $this->generateSecurityToken($params);
		$this->url = $this->admin_url;
		$this->method = self::POST;
		return $this->callApi(self::API_queryRequestBO, $params, $context);
	}

	public function processResultForQueryrequestBoUrl($params) {
		$this->CI->utils->debug_log('nex4d (processResultForQueryrequestBoUrl)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = [];

		if($success){
			$result['url'] = isset($resultArr['url']) ? $resultArr['url'].$this->bo_username.'&acl=ACL_TOOLS,ACL_PLAYER_WL_REPORT' : null;
		}

		return array($success, $result);
	}

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null,$is_querytransaction= false) {
		$success = false;
		if(isset($resultArr['errorCode']) && "0" == $resultArr['errorCode']){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('nex4d got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

    #md5(params + secretKey)
    public function generateSecurityToken($params = null){
        if($params){    
            return md5(join($params).$this->secretKey);
        }
        return false;
    }

	protected function customHttpCall($ch, $params) {	
		switch ($this->method){
            case self::POST:
				curl_setopt($ch, CURLOPT_POST, TRUE);				
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				break;
		}
	}

    public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->url . $apiUri;
        $url = $url . '?' . http_build_query($params);
		return $url;
	}

    public function syncOriginalGameLogs($token) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$settled_count = 0;
		$unsettled_count = 0;

        $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+15 minutes', function($startDate, $endDate) use(&$settled_count,&$unsettled_count) {
			$startDate = $this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s'));
			$endDate = $this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s'));
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncOriginalGameLogs',
				'startDate' => $startDate,
				'endDate' => $endDate
			);

			$page = 1;
			$done = false;
			$done_settled = false;
			$done_unsettled = false;
			while(!$done){
				$params = array(
					'fromDate' 		=> 	$startDate,
					'toDate' 		=> 	$endDate,
					'reqDateTime'   => 	date('Y-m-d H:i:s'),
					'opCode' 		=> 	$this->opCode,
				);
	
				$params['securityToken'] = $this->generateSecurityToken($params);
				$params['reqPage'] = $page;

				$this->CI->utils->debug_log('<-------------- PARAMS -------------->', $params);
	
				$this->method = self::POST;
	
				
				if(!$done_settled){
					$settledData 		= $this->callApi(self::API_syncGameRecords_settled, $params, $context);
				}

				if(!$done_unsettled){
					$unsettledData 		= $this->callApi(self::API_syncGameRecords_unsettled, $params, $context);
				}

				$settled_count 		+= isset($settledData['data_count']) ? $settledData['data_count'] : 0;
				$unsettled_count 	+= isset($unsettledData['data_count']) ? $unsettledData['data_count'] : 0;


				if (isset($settledData['data_count']) && $settledData['data_count'] == 0) {
					$done_settled = true;
				}
				
				if (isset($unsettledData['data_count']) && $unsettledData['data_count'] == 0) {
					$done_unsettled = true;
				}

				if($done_settled && $done_unsettled){
					$done = true;
				}

				$this->CI->utils->debug_log('nex4d (testtest)' , $done,$done_settled, $done_unsettled,$page );

				$page ++;
			}

			return ['success' => true];
        });

		$result = [
			'success' => true,
			'data_count' => $settled_count + $unsettled_count,
			'settled' => $settled_count,
			'unsettled' => $unsettled_count,
		];
        return $result;
    }

	public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,null,true);
		$result = array('data_count'=>0);

		$this->CI->utils->debug_log('nex4d (processResultForSyncOriginalGameLogs)' , $success, $resultArr,$this->original_gamelogs_table);

		$gameRecords = isset($resultArr['txnList']) ? $resultArr['txnList'] : [];

		if($success && !empty($gameRecords)){
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

		$this->CI->utils->debug_log('processResultForSyncOriginalGameLogs--',$result, $resultArr);

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


	private function rebuildGameRecords(&$gameRecords,$extra){
		$this->CI->utils->debug_log('nex4d (rebuildGameRecords)', $gameRecords,$gameRecords, $extra);
		
		$new_gameRecords = array();	
		
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$new_gameRecords[$index]['response_result_id'] 	= $extra['response_result_id'];
				$new_gameRecords[$index]['transaction_id'] 		= isset($record['txnId']) ? $record['txnId'] : null;
				$new_gameRecords[$index]['draw_id'] 			= isset($record['drawId']) ? $record['drawId'] : null;
				$new_gameRecords[$index]['player_name'] 		= isset($record['playerName']) ? $record['playerName'] : null;
				$new_gameRecords[$index]['player_id'] 			= isset($record['playerId']) ? $record['playerId'] : null;
				$new_gameRecords[$index]['bet_date'] 			= isset($record['betDate']) ? $this->gameTimeToServerTime($record['betDate']) : null;
				$new_gameRecords[$index]['process_date'] 		= isset($record['processDate']) ? $this->gameTimeToServerTime($record['processDate']) : null;
				$new_gameRecords[$index]['valid_turnover'] 		= isset($record['validTurnover']) ? $this->gameAmountToDB($record['validTurnover']) : null;
				$new_gameRecords[$index]['stake'] 				= isset($record['stake']) ? $this->gameAmountToDB($record['stake']) : null;
				$new_gameRecords[$index]['payout'] 				= isset($record['payout']) ? $this->gameAmountToDB($record['payout']) : null;
				$new_gameRecords[$index]['odds'] 				= isset($record['odds']) ? $this->gameAmountToDB($record['odds']) : null;
				$new_gameRecords[$index]['bet_type'] 			= isset($record['betType']) ? $record['betType'] : null;
				$new_gameRecords[$index]['bet_info'] 			= isset($record['betInfo']) ? $record['betInfo'] : null;
				$new_gameRecords[$index]['player_ip'] 			= isset($record['playerIp']) ? $record['playerIp'] : null;
				$new_gameRecords[$index]['draw_name'] 			= isset($record['drawName']) ? $record['drawName'] : null;
				$new_gameRecords[$index]['draw_type'] 			= isset($record['drawType']) ? $record['drawType'] : null;
				$new_gameRecords[$index]['agent_code'] 			= $this->agentCode;
				$new_gameRecords[$index]['status'] 				= isset($record['processDate']) ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;
				$new_gameRecords[$index]['game_code'] 			= $this->getGameCode($record['drawName']);
				
				#default
				$new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
				$new_gameRecords[$index]['external_uniqueid']  = isset($record['txnId']) ? $record['txnId'] : null;

				#extra
				$new_gameRecords[$index]['extra_info'] = json_encode($record);
				
			}
		}
        $gameRecords = $new_gameRecords;
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		// $dateFrom = date('Y-m-d H:i:s', strtotime($dateFrom . $interval));
		// $dateTo = date('Y-m-d H:i:s', strtotime($dateTo . $interval));
		
		$sqlTime='`original`.`updated_at` >= ? AND `original`.`updated_at` <= ?';

        if ($use_bet_time) {
            $sqlTime = '`original`.`bet_date` >= ? AND `original`.`bet_date` <= ?';
        }

        $sql = <<<EOD
SELECT
	original.id as sync_index,
	original.transaction_id,
	original.player_name,
	original.bet_date as betting_date,
	original.process_date as settled_date,
	original.valid_turnover as bet_amount,
	original.stake,
	original.payout,
	original.draw_name,
	original.bet_type,
	original.status,

	original.game_code,
	original.response_result_id,
	
	original.updated_at,
	original.external_uniqueid,
	original.md5_sum,
	game_provider_auth.player_id,
	gd.id as game_description_id,
	gd.game_name as game_description_name,
	gd.game_type_id
FROM {$this->original_gamelogs_table} as original
LEFT JOIN game_description as gd ON original.game_code = gd.game_code AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON original.player_name = game_provider_auth.login_name
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

	public function getGameCode($draw_name){
		if($draw_name){
			$drawname_array = [
				'Malaysia Draw' => 'draw.mal',
				'Singapore Draw' => 'draw.sg',
				'Singapore45 Draw' => 'draw.sg45',
				'Sydney Draw' => 'draw.sydney',
				'Hongkong Live Day Draw' => 'draw.hklive.day',
				'Hongkong Live Night Draw' => 'draw.hklive',
				'HongKong Draw' => 'draw.hk',
				'Nex4D Pools Draw' => 'draw.nex4d',
			];

			$overwrite_draw_list_info = $this->getSystemInfo('overwrite_draw_list_info', []);
			if(!empty($overwrite_draw_list_info)){
				$drawname_array = $overwrite_draw_list_info;
			}

			if(array_key_exists($draw_name, $drawname_array)){
				return $drawname_array[$draw_name];
			}
		}
		return null;
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
		$resultAmount = $row['payout'];
		$winLossAmount = $resultAmount - abs($row['bet_amount']);
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
                'player_username' 	=> $row['player_name']
            ],
            'amount_info' => [
                'bet_amount' 			=> abs($row['bet_amount']),
                'result_amount' 		=> $winLossAmount,
                'real_betting_amount' 	=> abs($row['bet_amount']),
				'bet_for_cashback' 		=> abs($row['bet_amount']),
                'win_amount' 			=> null,
                'loss_amount' 			=> null,
                'after_balance' 		=> null
            ],
            'date_info' => [
                'start_at' 	 => $row['betting_date'],
                'end_at' 	 => $row['end_at'],
                'bet_at' 	 => $row['betting_date'],
                'updated_at' => $row['updated_at']
            ],
            'flag' 	 => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' 	 => 0,
                'external_uniqueid'  => $row['external_uniqueid'],
                'round_number' 		 => $row['transaction_id'],
                'md5_sum' 			 => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' 		 => $row['sync_index'],
                'bet_type' 			 => null
            ],
            'bet_details' => [],
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
		$row['betting_date'] = $row['betting_date'];
		$row['end_at'] = !empty($row['settled_date']) ? $row['settled_date'] : $row['betting_date'];
     }


	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['draw_name'];
        $extra = array('game_name' => $row['draw_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
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

    public function queryForwardGame($playerName, $extra=null){

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$this->CI->load->library(['language_function']);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'gameCode' => $extra['game_code'],
        );
        $params = array(
			'agentCode' => $this->agentCode,
			'username' => $gameUsername,
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode,
		);
		$params['securityToken'] = $this->generateSecurityToken($params);
		$this->method = self::POST;
		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

	public function processResultForQueryForwardGame($params){
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$gameCode = @$this->getVariableFromContext($params, 'gameCode');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array('url'=>'');
		$result['url'] = $this->game_url.'?loginToken='.$resultArr['loginToken'].'&dg='.$gameCode;
		$this->CI->utils->debug_log('nex4d-processResultForQueryForwardGame', $result);
		return array($success, $result);
	}

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('nex4d (depositToGame)', $playerName);	

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
			'username' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			'transId' => $external_transaction_id,
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode
		);

		$params['securityToken'] = $this->generateSecurityToken($params);

		$this->method = self::POST;
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$this->CI->utils->debug_log('nex4d (processResultForDepositToGame)', $params);	

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
			if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
        }

        return array($success, $result);
	}


	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('nex4d (withdrawFromGame)', $playerName);	

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForwithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $this->dBtoGameAmount($amount),
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'username' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			'transId' => $external_transaction_id,
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode
		);

		$params['securityToken'] = $this->generateSecurityToken($params);

		$this->method = self::POST;
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForwithdrawFromGame($params) {
		$this->CI->utils->debug_log('nex4d (processResultForwithdrawFromGame)', $params);	

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
			if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			}
        }

        return array($success, $result);
	}

	public function getDrawListInfo(){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForgetDrawListInfo',
		);
		$params = array(
			'agentCode'=>  $this->agentCode,
			'reqDateTime' => date('Y-m-d H:i:s'),
			'opCode' => $this->opCode,
		);
		$params['securityToken'] = $this->generateSecurityToken($params);
		$url = $this->url;
		$segments = explode('/', $url);
		array_pop($segments);
		$this->url = implode('/', $segments);
		$this->method = self::POST;
		$list = $this->callApi(self::API_getDrawListInfo, $params, $context);
		return $list;
	}

	public function processResultForgetDrawListInfo($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];
		if($success){
			$result['list'] = $resultArr['marketList'];
		}
		return array($success, $result);
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
}

/*end of file*/