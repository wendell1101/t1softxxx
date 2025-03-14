<?php
/**
 * Simple Play game integration
 * OGP-19681
 *
 * @author 	Jerbey Capoquian
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_simpleplay extends Abstract_game_api {


	const ORIGINAL_LOGS_TABLE_NAME = 'simpleplay_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['bet_time','payout_time','detail','bet_source','game_type'];
	const MD5_FLOAT_AMOUNT_FIELDS=['bet_amount','result_amount','after_balance'];
	const MD5_FIELDS_FOR_MERGE=['real_bet_amount','rolling','result_amount','start_at','end_at','status','bet_details'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['real_bet_amount','bet_amount','result_amount'];
	const SUCCESS = 0;
	const ERROR_MSG_USER_NOT_EXIST = 116;
	const ERROR_CODE_SERVER_NOT_READY = 106;
	const ERROR_CODE_USERNAME_LENGTH_FORMAT_INC = 108;
	const ERROR_CODE_USERNAME_NOT_EXIST = 116;
	const ERROR_CODE_AMOUNT_MUST_LARGER_ZERO = 120;
	const ERROR_CODE_NOT_ENOUGH_POINTS = 121;
	const ERROR_CODE_TRANSACTION_ALREADY_EXIST = 122;
	const ERROR_CODE_DB_ERROR = 124;
	const ERROR_CODE_INVALID_FORMAT = 127;
	const ERROR_CODE_ERROR_PARAMETER = 142;
	const ERROR_CODE_PARAMETER_DECIMAL_GREATER_THAN_2 = 145;
	
	public function __construct() {
		parent::__construct();
		$this->apiUrl = $this->getSystemInfo('url','http://api.sp-portal.com/api/api.aspx');
		$this->secretKey = $this->getSystemInfo('secret','F0AD1789437541FCAD0F806229DF2B9F');
		$this->md5Key = $this->getSystemInfo('md5Key','GgaIMaiNNtg');
		$this->encryptKey = $this->getSystemInfo('encryptKey','g9G16nTs');
		$this->currency = $this->getSystemInfo('currency','USD');
		$this->initial_demo_amount = $this->getSystemInfo('initial_demo_amount',9999);
		$this->return_url = $this->getSystemInfo('return_url');
		$this->disable_return_url = $this->getSystemInfo('disable_return_url', true);
	}

	public function getPlatformCode() {
		return SIMPLEPLAY_GAME_API;
	}

	/**
	 * overview : custom http call
	 *
	 * @param $ch
	 * @param $params
	 */
	protected function customHttpCall($ch, $params) {
		$requestParams = array(
			"q" => $params['encryptedString'],
			"s" => $params['md5String']
		);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestParams));
		
	}

	public function generateUrl($apiName, $params) {
		return $this->apiUrl;
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = ($resultArr['ErrorMsgId'] == self::SUCCESS) ? true : false;
		$result = array();
		if(!$success){
			$this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Simpleplay got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	private function DES( $key, $iv=0 ) {
        $this->encryptKey = $key;
        if( $iv == 0 ) {
            $this->iv = $key;
        } else {
            $this->iv = $iv;
        }
    }

    private function encrypt($str) {
        return base64_encode( openssl_encrypt($str, 'DES-CBC', $this->encryptKey, OPENSSL_RAW_DATA, $this->iv  ) );
    }

    private function generateMD5String($str){
        $md5key = $this->md5Key; 
		$time = date("yymdhms"); 
		$preMD5Str = $str . $md5key . $time . $this->secretKey;
		$outMD5 = md5($preMD5Str);
		return $outMD5;
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
            'method' => "RegUserInfo",
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'Username' => $gameUsername,
            'CurrencyType' => $this->currency,
        );
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay createPlayer params ----------------------------',$params);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			$result['ErrorMsg'] = $arrayResult['ErrorMsg'];
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
            'method' => "VerifyUsername",
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'Username' => $gameUsername,
        );
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay isPlayerExist params ----------------------------',$params);
		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

    public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$playerId = $this->getPlayerIdInPlayer($playerName);
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);	
		} else {
			$result['exists'] = null;
			if(isset($arrayResult['ErrorMsgId']) && $arrayResult['ErrorMsgId'] == self::ERROR_CODE_USERNAME_NOT_EXIST){
				$result['exists'] = false;
			}
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
            'method' => "GetUserStatus",
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'Username' => $gameUsername,
        );
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay queryPlayerBalance params ----------------------------',$params);
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		$result = array();
		if($success){
			$result['exists'] = true;
			$result["balance"] = $this->gameAmountToDB(@$arrayResult['Balance']);
		} else {
			$result['exists'] = null;
		}
		return array($success,$result);
	}

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		/* Order id format
			Order ID: IN+YYYYMMDDHHMMSS+Username
			e.g. “IN20131129130345peter1235”
		*/
		$transfer_secure_id = "IN".date("yymdhms").$gameUsername;//override existing id, game have own format to follow
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'external_transaction_id' => $transfer_secure_id
		);

		$params = array(
            'method' => "CreditBalance",
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'Username' => $gameUsername,
            'OrderId' => $transfer_secure_id,
            'CreditAmount' => $this->dBtoGameAmount($amount)
        );
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay depositToGame params ----------------------------',$params);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
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
        	if(isset($arrayResult['ErrorMsgId'])){
				$error_code = @$arrayResult['ErrorMsgId'];
				$result['reason_id'] = $this->getReasons($error_code);
			}
        }
		return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		/* Order id format
			Order ID: IN+YYYYMMDDHHMMSS+Username
			e.g. “IN20131129130345peter1235”
		*/
		$transfer_secure_id = "OUT".date("yymdhms").$gameUsername;//override existing id, game have own format to follow
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'external_transaction_id' => $transfer_secure_id
		);

		$params = array(
            'method' => "DebitBalance",
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'Username' => $gameUsername,
            'OrderId' => $transfer_secure_id,
            'DebitAmount' => $this->dBtoGameAmount($amount)
        );
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay withdrawFromGame params ----------------------------',$params);
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
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
        	if(isset($arrayResult['ErrorMsgId'])){
				$error_code = @$arrayResult['ErrorMsgId'];
				$result['reason_id'] = $this->getReasons($error_code);
			}
        }
		return array($success, $result);
    }

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
		);
		$method = (isset($extra['game_mode']) && $extra['game_mode'] == "real") ? 'LoginRequest' : 'LoginRequestForFun';
		$params = array(
            'method' => $method,
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'Username' => $gameUsername,
            'CurrencyType' => $this->currency,
            'Lang' => $this->getLauncherLanguage($extra['language']),
            'Mobile' => isset($extra['is_mobile']) ? $extra['is_mobile'] : false,
        );
        if(!empty($extra['game_code']) && strtolower($extra['game_code']) != "lobby"){
        	$params['GameCode'] = $extra['game_code'];
        }

        #default demo 
        if(!isset($extra['game_mode']) || $extra['game_mode'] != "real"){
        	unset($params['Username']);
        	$params['Amount'] = $this->initial_demo_amount;
        }
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay queryForwardGame params ----------------------------',$params);
		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$result = array();
		$success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
		if($success){
			$result['message'] = $arrayResult['ErrorMsg'];
			$result['url'] = $arrayResult['GameURL'];
			$return_url = empty($this->return_url) ? $this->CI->utils->getSystemUrl('player') : $this->return_url;
			if(!$this->disable_return_url){
				$result['url'] .=  "&returnurl=". $return_url;
			}
		}
		return array($success, $result);
	}

	public function getLauncherLanguage($currentLang) {
		switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh_CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
                $language = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en-us":
                $language = 'en_US';
                break;
            default:
                $language = 'en_US';
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

		$result = array();
		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+24 hours', function($startDate, $endDate)  use(&$result) {
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate = $endDate->format('Y-m-d H:i:s');
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
			);

			$params = array(
	            'method' => 'GetAllBetDetailsForTimeInterval',
	            'Key' => $this->secretKey,
	            'Time' =>  date("yymdhms"),
	            'FromTime' => $startDate,
	            'ToTime' => $endDate,
	        );
			$params['encodedString'] = http_build_query($params);
	        $this->des($this->encryptKey);
	        $params['encryptedString'] = $this->encrypt($params['encodedString']);
	        $params['md5String'] = $this->generateMD5String($params['encodedString']);
			$this->CI->utils->debug_log('-----------------------simpleplay syncOriginalGameLogs params ----------------------------',$params);
			$response =  $this->callApi(self::API_syncGameRecords, $params, $context);
			$result[] = $response;
			return $result;
	    });
	    return array(true, $result);	
	}

	public function processGameRecords(&$gameRecords, $responseResultId) {
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$data['bet_time'] = isset($record['BetTime']) ? $this->gameTimeToServerTime($record['BetTime']) : null;
				$data['payout_time'] = isset($record['PayoutTime']) ? $this->gameTimeToServerTime($record['PayoutTime']) : null;
				$data['username'] = isset($record['Username']) ? $record['Username'] : null;
				$data['host_id'] = isset($record['HostID']) ? $record['HostID'] : null;
				$data['game_id'] = isset($record['GameID']) ? $record['GameID'] : null;
				$data['round'] = isset($record['Round']) ? $record['Round'] : null;
				$data['set'] = isset($record['Set']) ? $record['Set'] : null;
				$data['bet_id'] = isset($record['BetID']) ? $record['BetID'] : null;
				$data['bet_amount'] = isset($record['BetAmount']) ? $this->gameAmountToDB($record['BetAmount']) : null;
				$data['rolling'] = isset($record['Rolling']) ? $record['Rolling'] : null;
				$data['result_amount'] = isset($record['ResultAmount']) ? $this->gameAmountToDB($record['ResultAmount']) : null;
				$data['after_balance'] = isset($record['Balance']) ? $record['Balance'] : null;
				$data['game_type'] = isset($record['GameType']) ? $record['GameType'] : null;
				$data['bet_source'] = isset($record['BetSource']) ? $record['BetSource'] : null;
				$data['detail'] = isset($record['Detail']) ? $record['Detail'] : null;
				$data['transaction_id'] = isset($record['TransactionID']) ? $record['TransactionID'] : null;
				$data['game_externalid'] = $data['detail'];
				$data['external_uniqueid'] = $data['bet_id'];
				$data['response_result_id'] = $responseResultId;
				$gameRecords[$index] = $data;
				unset($data);
			}
		}
	}

	public function processResultForSyncGameRecords($params) {
		$this->CI->load->model(array('original_game_logs_model'));
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult, null);
		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
			'error_id' => isset($arrayResult['ErrorMsgId'])  ? $arrayResult['ErrorMsgId'] : null
		);
		if($success){

			$gameRecords = isset($arrayResult['BetDetailList']['BetDetail']) ? $arrayResult['BetDetailList']['BetDetail'] : null;
			if(!empty($gameRecords)){
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
		$enabled_game_logs_unsettle=false;
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
    	$sqlTime='sp.payout_time >= ? and sp.payout_time <= ?';
    	if($use_bet_time){
    		$sqlTime='sp.bet_time >= ? and sp.bet_time <= ?';
    	}
		$sql = <<<EOD
SELECT sp.id as sync_index,
sp.username as player_username,
sp.game_id as round_number,
sp.bet_amount as real_bet_amount,
sp.bet_amount as bet_amount,
sp.result_amount,
sp.after_balance,

sp.game_externalid as game_code,
sp.external_uniqueid,
sp.updated_at,
sp.md5_sum,
sp.response_result_id,
sp.game_externalid as game,
sp.bet_time as bet_at,
sp.bet_time as start_at,
sp.payout_time as end_at,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM simpleplay_game_logs as sp
LEFT JOIN game_description as gd ON sp.game_externalid = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sp.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
        
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
    	$extra_info=[];
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
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
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
        $row['status'] = Game_logs::STATUS_SETTLED;
        $row['bet_details'] = [];
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


	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}


	public function queryTransaction($transactionId, $extra) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'external_transaction_id' => $transactionId
		);

		$params = array(
            'method' => "CheckOrderId",
            'Key' => $this->secretKey,
            'Time' =>  date("yymdhms"),
            'OrderId' => $transactionId
        );
		$params['encodedString'] = http_build_query($params);

        $this->des($this->encryptKey);
        $params['encryptedString'] = $this->encrypt($params['encodedString']);
        $params['md5String'] = $this->generateMD5String($params['encodedString']);
		$this->CI->utils->debug_log('-----------------------simpleplay queryTransaction params ----------------------------',$params);
		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$arrayResult = $this->convertResultXmlToSimpleXmlLoadStringFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $arrayResult);
		$result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        );

		
		if($success){
			if($arrayResult['isExist'] == 'false'){
				$success = false;
				$result['error_message'] = lang("Not exist!");
			} else {
				$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			}
		}else{
			if(isset($arrayResult['ErrorMsgId'])){
				$error_code = @$arrayResult['ErrorMsgId'];
				$result['reason_id'] = $this->getReasons($error_code);
				if(isset($arrayResult['ErrorMsg'])){
					$result['error_message'] = $arrayResult['ErrorMsg'];
				}
			}
		}

		return array($success, $result);
	}

	

	private function getTransferStatus($errorCode){
		switch ($errorCode) {
			case self::ERROR_CODE_SERVER_NOT_READY:
			case self::ERROR_CODE_USERNAME_LENGTH_FORMAT_INC:
			case self::ERROR_CODE_USERNAME_NOT_EXIST:
			case self::ERROR_CODE_AMOUNT_MUST_LARGER_ZERO:
			case self::ERROR_CODE_NOT_ENOUGH_POINTS:
			case self::ERROR_CODE_TRANSACTION_ALREADY_EXIST:
			case self::ERROR_CODE_DB_ERROR:
			case self::ERROR_CODE_INVALID_FORMAT:
			case self::ERROR_CODE_ERROR_PARAMETER:
			case self::ERROR_CODE_PARAMETER_DECIMAL_GREATER_THAN_2:
				return self::COMMON_TRANSACTION_STATUS_DECLINED;
				break;
			default:
                return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
		}
	}

	private function getReasons($errorCode){
		switch ($errorCode) {
			case self::ERROR_CODE_INVALID_FORMAT:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case self::ERROR_CODE_SERVER_NOT_READY:
				return self::REASON_GAME_PROVIDER_NETWORK_ERROR;
				break;
			case self::ERROR_CODE_AMOUNT_MUST_LARGER_ZERO:
			case self::ERROR_CODE_PARAMETER_DECIMAL_GREATER_THAN_2:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case self::ERROR_CODE_ERROR_PARAMETER:
				return self::REASON_PARAMETER_ERROR;
				break;
			case self::ERROR_CODE_NOT_ENOUGH_POINTS:
				return self::REASON_INSUFFICIENT_AMOUNT;
				break;
			case self::ERROR_CODE_TRANSACTION_ALREADY_EXIST:
				return self::REASON_TRANSACTION_ID_ALREADY_EXISTS;
				break;
			case self::ERROR_CODE_USERNAME_NOT_EXIST:
				return self::REASON_ACCOUNT_NOT_EXIST;
				break;

			default:
                return self::REASON_UNKNOWN;
                break;
		}
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
