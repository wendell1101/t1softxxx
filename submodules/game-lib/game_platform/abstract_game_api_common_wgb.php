<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: WGB game
	* API docs: 
	* OGP-32574
	* @category Game_platform
	* @copyright 2013-2024 tot
	* @integrator @johmison.php.ph
**/

abstract class Abstract_game_api_common_wgb extends Abstract_game_api {
	const POST = 'POST';
	const GET = 'GET';

	# Fields in jili_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
       'bet_amount',
       'payout',
	   'status',
	   'winner'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
		'bet_amount',
		'payout',
		'odds',
		'winner'
    ];

	const MD5_FIELDS_FOR_MERGE = [
		'external_uniqueid',
		'status'
	];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
		'after_balance',
        'bet_amount',
        'valid_bet',
        'result_amount',
	];

    public $method, $opCode, $secretKey, $URI_MAP,$METHOD_MAP, $url,$original_gamelogs_table,$site_id,$secret_key,$callback_url,$syncOriginalInterval;

	public function __construct() {
		parent::__construct();

		$this->original_gamelogs_table = 'wgb_game_logs';   
		$this->method = self::POST; # default as POST
        $this->url = $this->getSystemInfo('url');
        $this->site_id = $this->getSystemInfo('site_id',null);
        $this->secret_key = $this->getSystemInfo('secret_key',null);
        $this->callback_url = $this->getSystemInfo('callback_url',null);
		$this->syncOriginalInterval = $this->getSystemInfo('syncOriginalInterval','+15 minutes');
		$this->URI_MAP = array(
            self::API_createPlayer => '/en/siteapi/play_now',
            self::API_queryForwardGame => '/en/siteapi/play_now',
			self::API_queryPlayerBalance => "/en/siteapi/check_balance",
			self::API_depositToGame => "/en/siteapi/player_cash_in",
			self::API_withdrawFromGame => "/en/siteapi/player_cash_out",
			self::API_syncGameRecords => "/en/siteapi/bet_history",
			self::API_queryTransaction => "/en/siteapi/check_transaction"
		);
		$this->METHOD_MAP = array(
			self::API_queryForwardGame => self::POST,
			self::API_queryPlayerBalance => self::POST,
			self::API_depositToGame => self::POST,
			self::API_withdrawFromGame => self::POST,
			self::API_syncGameRecords => self::POST,
			self::API_queryTransaction => self::POST,
		);
	}

	public function getPlatformCode() {
		return WGB_GAME_API;
	}
	
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for WGB GAME API";
        if ($return) {
			$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
			$this->CI->load->library(['language_function']);
			$language = $this->getSystemInfo('language', null);
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForcreatePlayer',
				'playerName' => $playerName,
				'gameUsername' => $gameUsername,
				'playerId' => $playerId,
			);
			$params = array(
				'timestamp' => time(),
				'name' => $gameUsername,
				'user_id' => $gameUsername,
				'amount' => "0.0",
				'request_code' => $this->generate_request_code($gameUsername),
				'callback_url' => $this->callback_url,
				'site_id' => $this->site_id,
			);

			$params['signature'] = $this->create_signature($params);

			if($language){
				$params['lang'] = $this->getLauncherLanguage($language);
			}
			$this->method = self::POST;
			$response 	= $this->callApi(self::API_createPlayer, $params, $context);
			$success 	= $response['success'];
			$message 	= $response['message'];
        }

		$this->CI->utils->debug_log('wgb (queryForwardGame)', $response);
        return array("success" => $success, "message" => $message);
	}

	public function processResultForCreatePlayer($params){
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$gameCode = @$this->getVariableFromContext($params, 'gameCode');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			# update flag to registered = true
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
	        $result['exists'] = true;
			$result['message'] = "Success create account for WGB GAME API";
		}

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$this->CI->utils->debug_log('wgb (queryPlayerBalance)', $playerName);	
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'timestamp' => time(),
			'site_id' 	=> $this->site_id,
			'user_id' 	=> $gameUsername,
		);

		$params['signature'] = $this->create_signature($params);

		$this->method = self::POST;
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$this->CI->utils->debug_log('wgb (processResultForQueryPlayerBalance)', $params);	

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = [];

		if($success){
			$result['balance'] = isset($resultArr['balance']) ? $this->convertAmountToDB(floatval($resultArr['balance'])) : null;
		}

		return array($success, $result);
	}

	public function create_signature($input){
        $key = $this->secret_key;
        ksort($input);
        $string = '';
        foreach($input as $k => $v){
            if(($k != 'signature') && ($k != 'bets')){
                $string .= $v;
            }
        }
        return md5($string.$key);  
    }

    #MD5(SITE ID + USER ID + TIMESTAMP) = bd9415520c6711f625e50198349d5331
    public function generate_request_code($user_id){
        if($user_id){
            return md5($this->site_id . $user_id . time());
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null,$is_querytransaction= false) {
		$success = false;
		if(isset($resultArr['status']) && "success" == $resultArr['status']){
            $success = true;
        }

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('wgb got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

    public function generateUserId($gameUsername = null){
        if($gameUsername){
            return md5('WGB-'.$this->site_id.'-'.$gameUsername);
        }   
        return false;
    }

	protected function customHttpCall($ch, $params) {	
		switch ($this->method){
            case self::POST:
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
				break;
		}
	
	}

    public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->url . $apiUri;
		return $url;
	}

    public function syncOriginalGameLogs($token) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$startDate->modify($this->getDatetimeAdjust());
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$records_count = 0;
        $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, $this->syncOriginalInterval, function($startDate, $endDate) use(&$records_count) {
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

			while(!$done){
				$params = array(
					'timestamp' 	=> 	time(),
					'site_id' 		=> 	$this->site_id,
					'from' 			=> 	$startDate,
					'to' 			=> 	$endDate,
					'page' 			=>  $page,
				);
	
				$params['signature'] = $this->create_signature($params);
				$this->CI->utils->debug_log('syncOriginalGameLogs WGB <-------------- PARAMS -------------->', $params);
				$this->method = self::POST;
				$records 		= $this->callApi(self::API_syncGameRecords, $params, $context);
				$data_count 	= isset($records['data_count']) ? $records['data_count'] : 0;
				if($data_count == 0 ){
					$done = true;
				}
				$records_count  += $data_count;
				$page ++;
			}
			
			return ['success' => true];
        });

		$result = [
			'success' => true,
			'records_count' => $records_count,
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

		$this->CI->utils->debug_log('wgb (processResultForSyncOriginalGameLogs)' , $success, $resultArr,$this->original_gamelogs_table);

		$gameRecords = isset($resultArr['records']) ? $resultArr['records'] : [];

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
		$this->CI->utils->debug_log('wgb (rebuildGameRecords)', $gameRecords,$gameRecords, $extra);
		
		$new_gameRecords = array();	
		
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$new_gameRecords[$index]['player_name'] 		= isset($record['user_id']) ? $record['user_id'] : null;
				$new_gameRecords[$index]['transaction_id'] 		= isset($record['bet_id']) ? $record['bet_id'] : null;
				$new_gameRecords[$index]['bet_id'] 				= isset($record['bet_id']) ? $record['bet_id'] : null;
				$new_gameRecords[$index]['round_id'] 			= isset($record['round_id']) ? $record['round_id'] : null;
				$new_gameRecords[$index]['bet_date'] 			= isset($record['date_created']) ? $record['date_created'] : null;
				$new_gameRecords[$index]['bet_amount'] 			= isset($record['bet_amount']) ? $this->gameAmountToDB($record['bet_amount']) : 0;
				$new_gameRecords[$index]['odds'] 				= isset($record['odds']) ? $this->gameAmountToDB($record['odds']) : 0;
				$new_gameRecords[$index]['payout'] 				= isset($record['payout']) ? $this->gameAmountToDB($record['payout']) : 0;
				$new_gameRecords[$index]['refund'] 				= isset($record['refund']) ? $this->gameAmountToDB($record['refund']) : 0;
				$new_gameRecords[$index]['bet_code'] 			= isset($record['bet_code']) ? $record['bet_code'] : 0;
				$new_gameRecords[$index]['winner'] 				= isset($record['winner']) ? $record['winner'] : 0;
				$new_gameRecords[$index]['game_code'] 			= 'cock_fight';
				$new_gameRecords[$index]['status'] 				= $this->getRoundStatus($record['winner']);
				
				#default
				$new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
				$new_gameRecords[$index]['external_uniqueid']  = isset($record['bet_id']) ? $record['bet_id'] : null;

				#extra
				$new_gameRecords[$index]['extra_info'] = json_encode($record);
				
			}
		}
        $gameRecords = $new_gameRecords;
	}

	public function getRoundStatus($winnerCode){
		if($winnerCode != 'cancelled'){
			return Game_logs::STATUS_SETTLED;
		}
		return Game_logs::STATUS_CANCELLED;
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
	original.round_id,
	original.player_name,
	original.bet_date as betting_date,
	original.bet_amount as bet_amount,
	original.payout,
	original.status,

	original.response_result_id,
	original.game_code,
	
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

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
		$payout = $row['payout'];
		$winLossAmount = $payout - abs($row['bet_amount']);
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
                'end_at' 	 => $row['betting_date'],
                'bet_at' 	 => $row['betting_date'],
                'updated_at' => $row['updated_at']
            ],
            'flag' 	 => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' 	 => 0,
                'external_uniqueid'  => $row['external_uniqueid'],
                'round_number' 		 => $row['round_id'],
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
    }


	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_code'];
        $extra = array('game_name' => $row['game_code']);

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
		$language = $this->getSystemInfo('language', null);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );
        $params = array(
			'timestamp' => time(),
            'name' => $gameUsername,
            'user_id' => $gameUsername,
            'amount' => "0.0",
            'request_code' => $this->generate_request_code($gameUsername),
            'callback_url' => $this->callback_url,
            'site_id' => $this->site_id,
		);

        $params['signature'] = $this->create_signature($params);

		if($language){
			$params['lang'] = $this->getLauncherLanguage($language);
		}
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

		if($success){
			$result['url'] = isset($resultArr['game']) ? $resultArr['game'] : null;
		}

		return array($success, $result);
	}

	public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
			case 'cn':
			case 'zh-cn':
                $lang = 'cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
			case 'vi':
			case 'vi-vn':
                $lang = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
			case 'id':
			case 'id-id':
                $lang = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
			case 'th':
			case 'th-th':
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

	public function getUniqueId($playerName){
		return $this->getExternalAccountIdByPlayerUsername($playerName);
	}

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$this->CI->utils->debug_log('wgb (depositToGame)', $playerName);	

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
            'timestamp' => time(),
			'user_id' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			'site_id' => $this->site_id,
			'request_code' => $external_transaction_id,
		);

        $params['signature'] = $this->create_signature($params);


		$this->method = self::POST;
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$this->CI->utils->debug_log('wgb (processResultForDepositToGame)', $params);	

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
		$this->CI->utils->debug_log('wgb (withdrawFromGame)', $playerName);	

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
            'timestamp' => time(),
			'user_id' => $gameUsername,
			'amount' => $this->dBtoGameAmount($amount),
			'site_id' => $this->site_id,
			'request_code' => $external_transaction_id,
		);

        $params['signature'] = $this->create_signature($params);

		$this->method = self::POST;
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForwithdrawFromGame($params) {
		$this->CI->utils->debug_log('wgb (processResultForwithdrawFromGame)', $params);	

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

	public function queryTransaction($transactionId, $extra)
	{
		$this->CI->utils->debug_log('WGB (queryTransaction)',$transactionId, $extra);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForqueryTransaction',
			'external_transaction_id' => $transactionId,
		);
		$params = [
			"timestamp" => time(),
			"site_id" => $this->site_id,
			"request_code" => $transactionId,
		];

		$params['signature'] = $this->create_signature($params);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForqueryTransaction($params)
	{
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$statusCode = $this->getStatusCodeFromParams($params);

		$this->CI->utils->debug_log('WGB (processResultForqueryTransaction)', $resultArr);
		$result = array(
			"response_result_id" => $responseResultId,
			"success" => $success,
		);

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

}

/*end of file*/