<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: LIVE GAMING
	* API docs: Merchant API documentation clara version 1116
	*
	* @category Game_platform
	* @version 6.10.01.001
	* @copyright 2013-2022 tot
	* @integrator @garry.php.ph
**/

class Game_api_mwlottery extends Abstract_game_api {
	const POST = 'POST';
	const GET = 'GET';

	# Fields in og_v2_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'bettime',
        'cancelstatus',
        'confirmamount',
        'content',
        'counts',
        'issueno',
        'lotterynumber',
        'nums',
        'odds',
        'orderid',
        'status',
        'winamount',
        'wincount',
        'winnumber',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'confirmamount',
        'winamount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when aviaesport_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at',
        'status',
        'cancelstatus',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	public function __construct() {
		parent::__construct();
        $this->encrypt_method = 'AES-128-ECB';
		$this->api_url = $this->getSystemInfo('url');
		$this->merchant_key = $this->getSystemInfo('key');
		$this->merchant_secret = $this->getSystemInfo('secret');
		$this->bonusMode = $this->getSystemInfo('bonusMode');
		$this->iv = '';
		$this->options = OPENSSL_RAW_DATA;
		$this->original_table = "mwlottery_game_logs";
		$this->max_loop_cnt = 100; # max loop
		$this->page_size = $this->getSystemInfo('page_size',1);
		$this->URI_MAP = array(
			self::API_createPlayer => '/api/account/create',
			self::API_queryPlayerBalance => '/api/account/getUserBalance',
			self::API_isPlayerExist => '/api/account/getUserBalance',
	        self::API_depositToGame => '/api/account/deposit',
	        self::API_withdrawFromGame => '/api/account/withdraw',
	        self::API_queryForwardGame => '/api/account/login',
			self::API_syncGameRecords => '/api/account/getBetRecords',
	        self::API_queryTransaction => '/api/account/getDepositRecords',
		);
	}

	public function getPlatformCode() {
		return MWLOTTERY_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		if($this->method == self::GET){
			$data = $this->buildData($params,true);
			$post_params = array(
		        "merchant" => $this->merchant_key,
		        "data" => $data,
		    );
		    $url = $url.'?'.http_build_query($post_params);
		}

		return $url;
	}

    public function encrypt($data) {
        $key = substr(openssl_digest(openssl_digest($this->merchant_secret, 'sha1', true), 'sha1', true), 0, 16);
        return openssl_encrypt($data, $this->encrypt_method, $key, $this->options, $this->iv);
    }

    public function decrypt($data) {
        $key = substr(openssl_digest(openssl_digest($this->merchant_secret, 'sha1', true), 'sha1', true), 0, 16);
        return openssl_decrypt($data, $this->encrypt_method, $key, $this->options, $this->iv);
    }

    public function buildData($data,$urlSafe = false) {
	    ksort($data);
	    $data = $this->encrypt(json_encode($data));
	    if ($urlSafe === false) {
	        $str = base64_encode($data);
	    } elseif ($urlSafe === true) {
	        $str = $this->urlsafe_b64encode($data);
	    }
	    return $str;
	}

	public function urlsafe_b64encode($string) {
	    $data = base64_encode($string);
	    $data = str_replace(array('+','/','='),array('-','_',''),$data);
	    return $data;
	}

	protected function customHttpCall($ch, $params) {
		switch ($this->method){
			case self::POST:
				$data = $this->buildData($params);
				$post_params = array(
			        "merchant" => $this->merchant_key,
			        "data" => $data,
			    );

				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_params));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$this->utils->debug_log('MW Lottery JSON REQUEST FIELD: ',json_encode($params));
				$this->utils->debug_log('MW Lottery ENCRYPTED REQUEST FIELD: ',$post_params);
			break;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr) {
		$success = false;
		if(@$resultArr['code'] == 0){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('MWLOTTERY_API got error ', $responseResultId,'result', $resultArr);
		}

		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"userAccount" => $gameUsername,
		);

		if(!empty($this->bonusMode)){
			$params["bonusMode"] = $this->bonusMode;
		}

		$this->method = self::POST;

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr);

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

	private function round_down($number, $precision = 3){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'userAccount' => $gameUsername,
		);

		$this->method = self::GET;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = [];

		if($success){
			$balance = $this->decrypt(base64_decode($resultArr['balance']));
			$result['balance'] = $this->round_down(@floatval($balance));
		}

		return array($success, $result);
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'userAccount' => "asdasd",//$gameUsername,
		);

		$this->method = self::GET;

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array();

		if($success||$resultArr['code'] == 2002){
			if($resultArr['code'] == 0){
				$result['exists'] = true;
			}elseif($resultArr['code'] == 2002){
				$success = true;
				$result['exists'] = false;
			}
		}else{
			$result['exists'] = null;
		}

		return array($success, $result);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $external_transaction_id,
        );

		$params = array(
			'userAccount' => $gameUsername,
			'amount' => $amount,
			'extTransId' => $external_transaction_id
		);

		$this->method = self::POST;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons(@$resultArr['code']);
        }

        return array($success, $result);
	}

	private function getReasons($statusCode){
		switch ($statusCode) {
			case 1001:
			case 5001:
			case 5002:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 2001:
			case 2005:
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
			break;
			case 2002:
				return self::REASON_NOT_FOUND_PLAYER;
				break;
			case 2004:
				return self::REASON_GAME_ACCOUNT_LOCKED;
				break;
			case 3001:
				return self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
				break;
			case 3002:
				return self::REASON_NO_ENOUGH_BALANCE;
				break;
			case 3003:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 3005:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 9001:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;
			case 5003:
				return self::REASON_INVALID_KEY;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

		$params = array(
			'userAccount' => $gameUsername,
			'amount' => $amount,
			'extTransId' => $external_transaction_id
		);

		$this->method = self::POST;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array(
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons(@$resultArr['code']);
        }

        return array($success, $result);
	}

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);

		$params = array(
			'userAccount' => $gameUsername,
			'transferOrderNumber' => $external_transaction_id
		);

		$this->method = self::GET;

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr);
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons(@$resultArr['code']);
        }

		return array($success, $result);
	}

	public function getLauncherLanguage($language){
		$lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = 'en-US'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 'zh-CN'; // chinese
                break;
            default:
                $lang = 'en-US'; // default as english
                break;
        }
        return $lang;
	}

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

		$params = array(
			'userAccount' => $gameUsername,
			'loginTime' => $this->utils->getCurrentDatetimeWithSeconds('Y-m-d H:i:s'),
			'terminal' => $extra['is_mobile']?'mobile':'pc',
		);

		$this->method = self::GET;

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$result = array('url'=>'');

		if($success){
			$result['url'] = $this->decrypt(base64_decode($resultArr['loginUrl']));
		}

		return array($success, $result);
	}

	public function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	# FORMAT date
    	$startDate = $startDateTime->format("Y-m-d H:i:s");
		$endDate = $endDateTime->format('Y-m-d H:i:s');
		$page = 1;
		$loop_cnt = 1;
		$gamerecords_count = 0;

		return $this->_continueSync($startDate,$endDate,$page,$loop_cnt,$gamerecords_count);
	}

	public function _continueSync($startDate, $endDate, $page = 1,$loop_cnt,$gamerecords_count) {
        $result = $this->syncMWOriginalLogs($startDate, $endDate, $page,$loop_cnt);
        $gamerecords_count += $result['gamerecords_count'];
        if ($result['success'] && ($gamerecords_count < $result['original_total'])) {
        	$page++;
        	$loop_cnt++;
        	if($loop_cnt <= $this->max_loop_cnt){
            	return $this->_continueSync($startDate, $endDate, $page,$loop_cnt,$gamerecords_count);
        	}
        }

        return $result;
    }

    public function syncMWOriginalLogs($startDate, $endDate, $page){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate,
			'page' => $page
		);

		$params = array(
			'pageNum' => $page,
			'pageSize' => $this->page_size, #1000 max size
			'startDateTime' => $startDate,
			'endDateTime' => $endDate
		);

		$this->method = self::GET;

		return $this->callApi(self::API_syncGameRecords, $params, $context);

    }

	private function rebuildGameRecords(&$gameRecords,$extra){
		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$new_gameRecords[$index]['bettime'] = isset($record['betTime'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($record['betTime']))):null;
			$new_gameRecords[$index]['cancelstatus'] = isset($record['cancelStatus'])?$record['cancelStatus']:null;
			$new_gameRecords[$index]['confirmamount'] = isset($record['confirmAmount'])?$record['confirmAmount']:null;
			$new_gameRecords[$index]['content'] = isset($record['content'])?$record['content']:null;
			$new_gameRecords[$index]['counts'] = isset($record['counts'])?$record['counts']:null;
			$new_gameRecords[$index]['issueno'] = isset($record['issueNo'])?$record['issueNo']:null;
			$new_gameRecords[$index]['lotterycode'] = isset($record['lotteryCode'])?$record['lotteryCode']:null;
			$new_gameRecords[$index]['lotterynumber'] = isset($record['lotteryNumber'])?$record['lotteryNumber']:null;
			$new_gameRecords[$index]['method'] = isset($record['method'])?$record['method']:null;
			$new_gameRecords[$index]['nums'] = isset($record['nums'])?$record['nums']:null;
			$new_gameRecords[$index]['odds'] = isset($record['odds'])?$record['odds']:null;
			$new_gameRecords[$index]['orderid'] = isset($record['orderId'])?$record['orderId']:null;
			$new_gameRecords[$index]['status'] = isset($record['status'])?$record['status']:null;
			$new_gameRecords[$index]['terminaltype'] = isset($record['terminalType'])?$record['terminalType']:null;
			$new_gameRecords[$index]['unit'] = isset($record['unit'])?$record['unit']:null;
			$new_gameRecords[$index]['useraccount'] = isset($record['userAccount'])?$record['userAccount']:null;
			$new_gameRecords[$index]['winamount'] = isset($record['winAmount'])?$record['winAmount']:null;
			$new_gameRecords[$index]['wincount'] = isset($record['winCount'])?$record['winCount']:null;
			$new_gameRecords[$index]['winnumber'] = isset($record['winNumber'])?$record['winNumber']:null;
			$username = @explode("_", $record['userAccount'])[1]; # get username
			$new_gameRecords[$index]['username'] = @$username;
            $new_gameRecords[$index]['external_uniqueid'] = isset($record['orderId'])?$record['orderId']:null;
            $new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
        }

        $gameRecords = $new_gameRecords;
        unset($new_gameRecords);
	}

	public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model('original_game_logs_model');
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$page = $this->getVariableFromContext($params, 'page');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$resultArr = json_decode($this->decrypt(base64_decode(@$resultArr['result'])),true);
		$result = array('data_count'=>0,'page'=>$page, 'original_total'=>0,'gamerecords_count'=>0);

		if($success){
			$gameRecords = !empty($resultArr['betRecords'])?$resultArr['betRecords']:[];
            $extra = ['response_result_id'=>$responseResultId];
            $result['original_total'] = @$resultArr['totalRecords'];
            $result['gamerecords_count'] = count($gameRecords);
            $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_table,
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

		return array($success, $result);
	}


    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`mw`.`updated_at` >= ?
          AND `mw`.`updated_at` <= ?';
        if($use_bet_time){
            $sqlTime='`mw`.`bettime` >= ?
          AND `mw`.`bettime` <= ?';
        }

        $sql = <<<EOD
			SELECT
				mw.id as sync_index,
				mw.response_result_id,
				mw.orderid as round,
				mw.username,
				mw.confirmamount as bet_amount,
				mw.confirmamount as valid_bet,
				mw.winamount as result_amount,
				mw.bettime as start_at,
				mw.bettime as end_at,
				mw.bettime as bet_at,
				mw.lotterycode as game_code,
				mw.lotterycode as game_name,
				mw.updated_at,
				mw.status,
				mw.cancelstatus,
				mw.external_uniqueid,
				mw.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_table as mw
			LEFT JOIN game_description as gd ON mw.lotterycode = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON mw.username = game_provider_auth.login_name
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

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
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
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['valid_bet'],
                'result_amount' => ($row['result_amount'] - $row['valid_bet']),
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['start_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getGameRecordsStatus($row),//Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => null,
            'extra' => $extra,
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
        $row['status'] = $this->getGameRecordsStatus($row);
    }

    public function getGameRecordsStatus($row) {
        $status = Game_logs::STATUS_PENDING;
        # 1: Lose 2: Win
        if($row['status'] == 1 || $row['status'] == 2){
            $status = Game_logs::STATUS_SETTLED;
        }

        if($row['cancelstatus'] == 1 || $row['cancelstatus'] == 4){
            $status = Game_logs::STATUS_CANCELLED;
        }

        return $status;
    }

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

    public function login($playerName, $password = null, $extra = null) {
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function onlyTransferPositiveInteger(){
		return true;
	}
}

/*end of file*/