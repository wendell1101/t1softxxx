<?php
/**
 * Champion Sports game integration
 * OGP-18116
 *
 * @author 	Jerbey Capoquian
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_champion_sports extends Abstract_game_api {


	const ORIGINAL_LOGS_TABLE_NAME = 'champion_sports_game_logs';
	

	const MD5_FIELDS_FOR_ORIGINAL=['bet_time','detail','settle_time','status','valid_stake','win'];
	const MD5_FLOAT_AMOUNT_FIELDS=['potential_win','stake','valid_stake','win'];
	const MD5_FIELDS_FOR_MERGE=['real_bet_amount','bet_amount','result_amount','start_at','end_at','status','bet_details'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['real_bet_amount','bet_amount','result_amount'];

	const SUCCESS = 0;
	const MAX_PAGE_COUNT = 1000;
	const DEFAULT_PAGE = 1;
	const DEFAULT_PAGE_NUMBER = 1;
	const METHOD_POST = "POST";
	const METHOD_GET = "GET";
	const SINGLE_BET = 1;
	const DOUBLE_BET = 2;
	const TREBLE_BET = 3;

	const STATUS_RUNNING = 1;
    const STATUS_WON = 2;
    const STATUS_VOID = 3;
    const STATUS_ROLLBACK = 4;
    const STATUS_CASHOUT = 5;
    const STATUS_LOST = 6;
    const STATUS_FIX = 7;
	
	public function __construct() {
		parent::__construct();
		$this->apiUrl = $this->getSystemInfo('url','https://api.kgsports.dev/api');//default staging api url for kinggaming champion sports
		$this->mid = $this->getSystemInfo('secret','185fe100497ea1c8a8e8cd5a0a3fc514');//default staging secret for kinggaming champion sports
		$this->key = $this->getSystemInfo('key','6c0fb06c5decba04b7947adc3f47e469');//default staging key for kinggaming champion sports
		$this->version = $this->getSystemInfo('version','v1');//default version for kinggaming champion sports
		$this->recordUrl = $this->getSystemInfo('recordUrl','https://api.kgsports.dev/v1/api/records');
		$this->language = $this->getSystemInfo('language', null);
		$this->method = self::METHOD_POST;
	}

	const URI_MAP = array(
		self::API_createPlayer => 'login',
		self::API_queryPlayerBalance => 'balance',
		self::API_isPlayerExist => 'balance',
		self::API_depositToGame => 'recharge',
		self::API_withdrawFromGame => 'withdrawal',
		self::API_queryForwardGame => 'login',
		self::API_syncGameRecords => 'records',
	);

	public function getPlatformCode() {
		return CHAMPION_SPORTS_GAME_API;
	}

	/**
	 * overview : custom http call
	 *
	 * @param $ch
	 * @param $params
	 */
	protected function customHttpCall($ch, $params) {
		
		if($this->method == self::METHOD_POST) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
		return $errCode || intval($statusCode, 10) >= 503;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->apiUrl . "/" . $this->version . "/" . $apiUri;
		if($apiName == self::API_syncGameRecords){
			$url = $url."?".http_build_query($params);
		}
		return $url;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = ($resultArr['error'] == self::SUCCESS) ? true : false;
		$result = array();
		if(!$success){
			$this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Champion Sports got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function generateSign($params){
		if(!empty($params)){
			$paramsJoined = array();
			ksort($params); #sort ascending
			foreach($params as $param => $value) {
			   $paramsJoined[] = "$param=$value";
			}
			$string = implode('&', $paramsJoined). '&' .$this->key;
			$sign = md5($string);
			$this->CI->utils->debug_log('Champion Sports sign ===>', $sign, 'string', $string);
			return $sign;
		}
		return null;
	}


	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
		);
		$params = array(
            'Account' => $gameUsername,
            'Ip' => $this->utils->getIP(),
            'Mid' =>  $this->mid,
            'RequestTime' => $this->utils->getNowForMysql(),
        );
        $params['Sign'] = $this->generateSign($params);

		$this->CI->utils->debug_log('-----------------------champpion sports createPlayer params ----------------------------',$params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			$result['message'] = $arrayResult['message'];
		}
		return array($success, $result);
	}

	public function isPlayerExist($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
		);

        $params = array(
            'Account' => $gameUsername,
            'Ip' => $this->utils->getIP(),
            'Mid' =>  $this->mid,
            'RequestTime' => $this->utils->getNowForMysql(),
        );
        $params['Sign'] = $this->generateSign($params);

		$this->CI->utils->debug_log('-----------------------champpion sports isPlayerExist params ----------------------------',$params);
		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

    public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);	
		} else {
			$result['exists'] = null;
		}
		return array($success,$result);
    }


	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

        $params = array(
            'Account' => $gameUsername,
            'Ip' => $this->utils->getIP(),
            'Mid' =>  $this->mid,
            'RequestTime' => $this->utils->getNowForMysql(),
        );
        $params['Sign'] = $this->generateSign($params);

		$this->CI->utils->debug_log('-----------------------champpion sports queryPlayerBalance params ----------------------------',$params);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$result["balance"] = $this->gameAmountToDB(@$arrayResult['balance']);
		} else {
			$result['exists'] = null;
		}
		return array($success,$result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = "D-".$this->utils->getDatetimeNow().$gameUsername;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'external_transaction_id' => $transfer_secure_id
		);

		$params = array(
            'Mid' => $this->mid,
            'Account' => $gameUsername,
            'Amount' =>   $this->dBtoGameAmount($amount),
            'OrderSn' => $transfer_secure_id,
            'RequestTime' => $this->utils->getNowForMysql(),
            'Ip' => $this->utils->getIP(),
        );
        $params['Sign'] = $this->generateSign($params);

		$this->CI->utils->debug_log('-----------------------champpion sports depositToGame params ----------------------------',$params);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		if($success){
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}  else {
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }
		return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		if(empty($transfer_secure_id)){
			$transfer_secure_id = "W-".$this->utils->getDatetimeNow().$gameUsername;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'external_transaction_id' => $transfer_secure_id
		);

		$params = array(
            'Mid' => $this->mid,
            'Account' => $gameUsername,
            'Amount' =>   $this->dBtoGameAmount($amount),
            'OrderSn' => $transfer_secure_id,
            'RequestTime' => $this->utils->getNowForMysql(),
            'Ip' => $this->utils->getIP(),
        );
        $params['Sign'] = $this->generateSign($params);

		$this->CI->utils->debug_log('-----------------------champpion sports depositToGame params ----------------------------',$params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
		if($success){
			$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}  else {
        	$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }
		return array($success, $result);
    }

	public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $language = $extra['language'];

        if($this->language != null) {
            $language = $this->language;
        }

        $language = $this->getLauncherLanguage($language);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'language' => $language,
        );
		$params = array(
            'Account' => $gameUsername,
            'Ip' => $this->utils->getIP(),
            'Mid' =>  $this->mid,
            'RequestTime' => $this->utils->getNowForMysql()
        );
        $params['Sign'] = $this->generateSign($params);

		$this->CI->utils->debug_log('-----------------------champpion sports createPlayer params ----------------------------',$params);
		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$language = $this->getVariableFromContext($params, 'language');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			$result['message'] = $arrayResult['message'];
			$result['url'] = $arrayResult['url'] . "&lang=${language}";
		}
		return array($success, $result);
	}

	public function getLauncherLanguage($currentLang) {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vi';
                break;
            case "en-us":
                $language = 'en';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());

		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
		);
		/*
        $params = array(
        	'Mid' => $this->mid,
        	'RequestTime' => $this->utils->getNowForMysql(),
        	'From' => $startDate,
        	'End' => $endDate,
        	'Page' => self::DEFAULT_PAGE,
        	'Num' => self::MAX_PAGE_COUNT,
        );
        $params['Sign'] = $this->generateSign($params);
        $this->method = self::METHOD_GET;
		$this->CI->utils->debug_log('--------champion sports syncOriginalGameLogs params ------------',$params);
		$result =  $this->callApi(self::API_syncGameRecords, $params, $context);
		*/

		$current_page = self::DEFAULT_PAGE;
		$page_number = self::DEFAULT_PAGE_NUMBER;
		$result = array();
	    while($current_page <= $page_number) {
			$params = array(
	        	'Mid' => $this->mid,
	        	'RequestTime' => $this->utils->getNowForMysql(),
	        	'From' => $startDate,
	        	'End' => $endDate,
	        	'Page' => $current_page,
	        	'Num' => self::MAX_PAGE_COUNT,
	        );
	        $params['Sign'] = $this->generateSign($params);
	        $this->method = self::METHOD_GET;

			$this->CI->utils->debug_log('--------champion sports syncOriginalGameLogs params ------------',$params);

			$response =  $this->callApi(self::API_syncGameRecords, $params, $context);
			$next_page = $response['page'] + self::DEFAULT_PAGE;
			$current_page = $next_page;
			$page_number = $response['page_num'];
			$result[] = $response;
		}
		return array(true,$result);
	}

	public function processGameRecords(&$gameRecords, $responseResultId) {
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$data['account'] = isset($record['account']) ? $record['account'] : null;
				$data['bet_time'] = isset($record['bet_time']) ? $this->gameTimeToServerTime($record['bet_time']) : null;
				$data['bid'] = isset($record['bid']) ? $record['bid'] : null;
				$data['detail'] = isset($record['detail']) ? json_encode($record['detail']) : null;
				$data['lang'] = isset($record['lang']) ? $record['lang'] : null;
				$data['mid'] = isset($record['mid']) ? $record['mid'] : null;
				$data['numlines'] = isset($record['numlines']) ? $record['numlines'] : null;
				$data['oddstype'] = isset($record['oddstype']) ? $record['oddstype'] : null;
				$data['potential_win'] = isset($record['potential_win']) ? $this->gameAmountToDB($record['potential_win']) : null;
				$data['settle_time'] = isset($record['settle_time']) ? $this->gameTimeToServerTime($record['settle_time']) : null;
				if(empty($data['settle_time'])){ #override settlement time by bet time
					$data['settle_time'] = $data['bet_time'];
				}
				$data['stake'] = isset($record['stake']) ? $this->gameAmountToDB($record['stake']) : null;
				$data['status'] = isset($record['status']) ? $record['status'] : null;
				$data['transaction'] = isset($record['transaction']) ? $record['transaction'] : null;
				$data['updated'] = isset($record['updated']) ? $record['updated'] : null;
				$data['valid'] = isset($record['valid']) ? $record['valid'] : null;
				$data['valid_stake'] = isset($record['valid_stake']) ? $this->gameAmountToDB($record['valid_stake']) : null;
				$data['win'] = isset($record['win']) ? $this->gameAmountToDB($record['win']) : null;
				//default data
				$data['game_externalid'] = isset($record['detail'][0]['sportid']) ? $record['detail'][0]['sportid'] : 'unknown';#get first bet line
				$data['external_uniqueid'] = $data['bid'];
				$data['response_result_id'] = $responseResultId;
				$gameRecords[$index] = $data;
				unset($data);
			}
		}
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, null);
		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
			'page'=> self::DEFAULT_PAGE,
			'page_num'=> self::DEFAULT_PAGE_NUMBER
		);
		if($success){
			$dataResult['num'] = $arrayResult['result']['num'];
			$dataResult['page'] = $arrayResult['result']['page'];
			$dataResult['page_num'] = $arrayResult['result']['page_num'];
			// echo "<pre>";
			// print_r($arrayResult);exit();
			$gameRecords = $arrayResult['result']['records'];
			$this->processGameRecords($gameRecords, $responseResultId);
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            $dataResult['data_count'] = count($gameRecords);
			if (!empty($insertRows)) {
				$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
		}
		return array($success, $dataResult);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
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

	    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
    	$sqlTime='cs.settle_time >= ? and cs.settle_time <= ?';
    	if($use_bet_time){
    		$sqlTime='cs.bet_time >= ? and cs.bet_time <= ?';
    	}
		$sql = <<<EOD
SELECT cs.id as sync_index,
cs.account as player_username,
cs.bid as round_number,
cs.stake as real_bet_amount,
cs.valid_stake as bet_amount,
cs.win as result_amount,

cs.game_externalid as game_code,
cs.external_uniqueid,
cs.updated_at,
cs.md5_sum,
cs.response_result_id,
cs.game_externalid as game,
cs.bet_time as bet_at,
cs.bet_time as start_at,
cs.settle_time as end_at,
cs.detail as bet_details,
cs.status,
cs.numlines,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM champion_sports_game_logs as cs
LEFT JOIN game_description as gd ON cs.game_externalid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON cs.account = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // echo "<pre>";
        // print_r($result);exit();
        return $result;
        
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	$extra_info=[
    		"bet_type" => ($row['numlines'] == self::SINGLE_BET) ? "Single Bet" : "Multiple {$row['numlines']}x"
    	];
    	$has_both_side=0;

    	if(empty($row['md5_sum'])){
        	//genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            	self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
        	//set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
    	$this->CI->load->model(array('game_logs'));
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['status'] = $this->getGameRecordsStatus($row['status']);
        $row['result_amount'] = $row['result_amount'] - $row['bet_amount'];
        $row['bet_details'] = json_decode($row['bet_details'],true);
    }

    /**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) {
		$this->CI->load->model(array('game_logs'));
		switch ($status) {
		case self::STATUS_RUNNING:
			$status = Game_logs::STATUS_ACCEPTED;
			break;
		case self::STATUS_VOID:
			$status = Game_logs::STATUS_VOID;
			break;
		case self::STATUS_ROLLBACK:
			$status = Game_logs::STATUS_REFUND;
			break;
		case self::STATUS_WON:
		case self::STATUS_CASHOUT:
		case self::STATUS_LOST:
		case self::STATUS_FIX:
			$status = Game_logs::STATUS_SETTLED;
			break;
		}
		return $status;
	}


    /**
	 * overview : get game description information
	 *
	 * @param $row
	 * @param $unknownGame
	 * @param $gameDescIdMap
	 * @return array
	 */
	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}


	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}


	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}


	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
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
