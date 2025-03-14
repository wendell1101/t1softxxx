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

class Game_api_livegaming extends Abstract_game_api {
	const POST = 'POST';
	const GET = 'GET';

	# Fields in og_v2_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'livegaming_id',
        'gameid',
        'get_money',
        'total_bet_money',
        'draw_money',
        'create_time',
        'username',
        'bet_detail'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'get_money',
        'total_bet_money',
        'draw_money',
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
        'bet_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->merchant_key = $this->getSystemInfo('key');
		$this->merchant_secret = $this->getSystemInfo('secret');
		$this->iv = '';
		$this->options = 0;
		$this->original_table = "livegaming_game_logs";
		$this->max_loop_cnt = 100; # max loop
		$this->URI_MAP = array(
			self::API_generateToken => '/connect/token',
			self::API_createPlayer => '/OpenAPI/V1/ThirdPartyAuth/userCreate',
			self::API_queryPlayerBalance => '/OpenAPI/V1/ThirdPartyAuth/userProfile',
			self::API_isPlayerExist => '/OpenAPI/V1/ThirdPartyAuth/userProfile',
	        self::API_depositToGame => '/OpenAPI/V1/ThirdPartyAuth/userDeposit',
	        self::API_withdrawFromGame => '/OpenAPI/V1/ThirdPartyAuth/userWithdraw',
	        self::API_queryTransaction => '/OpenAPI/V1/ThirdPartyAuth/orderTracking',
	        self::API_queryForwardGame => '/OpenAPI/V1/ThirdPartyAuth/userLogin',
			self::API_syncGameRecords => '/OpenAPI/V1/ThirdPartyAuth/getBetDetailAndResult',
			self::API_getGameProviderGamelist => '/OpenAPI/V1/ThirdPartyAuth/getGameListByChannelId',
		);
	}

	public function getPlatformCode() {
		return LIVEGAMING_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;

		return $url;
	}


    /**
     * 加密方法，对数据进行加密，返回加密后的数据
     *
     * @param string $data 要加密的数据
     *
     * @return string
     *
     */
    public function encrypt($params) {
        return openssl_encrypt($params, 'AES-256-ECB', $this->merchant_secret, $this->options, $this->iv);
    }

    /**
     * 解密方法，对数据进行解密，返回解密后的数据
     *
     * @param string $data 要解密的数据
     *
     * @return string
     *
     */
    public function decrypt($params) {
        return openssl_decrypt($params, 'AES-256-ECB', $this->merchant_secret, $this->options, $this->iv);
    }

	private function buildData($params) {
	    ksort($params);
	    return $this->encrypt(json_encode($params));
	}

	protected function customHttpCall($ch, $params) {
		switch ($this->method){
			case self::POST:
				$post_params = array(
			        "merchant_key" => $this->merchant_key,
			        "data" =>$this->buildData($params),
			    );

				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$this->utils->debug_log('lIVE GAMING JSON REQUEST FIELD: ',json_encode($params));
				$this->utils->debug_log('lIVE GAMING ENCRYPTED REQUEST FIELD: ',$post_params);
			break;
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode = null) {
		$success = false;
		if(@$resultArr['code'] == 0){
			$success = true;
		}

		if(!is_null($statusCode)&&$statusCode == 200){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('LIVEGAMING_API got error ', $responseResultId,'result', $resultArr);
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
			"username" => $gameUsername,
			"nickname" => $gameUsername
		);

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
			'username' => $gameUsername,
		);

		$this->method = self::POST;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$resultArr = json_decode($this->decrypt($resultArr['data']),true);
		$result = [];

		if($success){
			$result['balance'] = $this->round_down(@floatval($resultArr['coinbalance']));
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
			'username' => $gameUsername,
		);

		$this->method = self::POST;

		return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		$result = array();

		if($success){
			if($resultArr['code'] == 0){
				$result['exists'] = true;
			}else{
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
			'username' => $gameUsername,
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
			case 10000:
				return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
				break;
			case 10001:
				return self::REASON_LOCKED_GAME_MERCHANT;
				break;
			case 10002:
				return self::REASON_IP_NOT_AUTHORIZED;
				break;
			case 10004:
			case 10009:
			case 10010:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 10005:
			case 10008:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 10006:
				return self::REASON_NOT_FOUND_PLAYER;
				break;
			case 10007:
				return self::REASON_GAME_ACCOUNT_LOCKED;
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
			'username' => $gameUsername,
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId=$extra['playerId'];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
			'playerId'=>$playerId,
		);

		$params = array(
			'orderId' => $transactionId,
		);

		$this->method = self::POST;

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr,$statusCode);
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if($success){
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			# Not approved
			if(@$resultJsonArr['code'] != 0){
				$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons(@$resultJsonArr['code']);
			}
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
			'username' => $gameUsername,
			'terminal' => $extra['is_mobile']?'mobile':'pc',
		);

		$this->method = self::POST;

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params){
		$gameUsername = @$this->getVariableFromContext($params, 'gameUsername');
		$playerName = @$this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$resultArr = json_decode($this->decrypt($resultArr['data']),true);
		$result = array('url'=>'');

		if($success){
			$result['url'] = @$resultArr['loginurl'];
		}

		return array($success, $result);
	}

    # notes: Attention! This domain is different from other APIs. Access Restriction: 10 seconds
	# "Query limit is 10 minutes"
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

		return $this->_continueSync($startDate, $endDate, $page,$loop_cnt);
	}

	public function _continueSync($startDate, $endDate, $page = 1,$loop_cnt) {
        $result = $this->syncLiveGamingOriginalLogs($startDate, $endDate, $page,$loop_cnt);

        if ($result['success'] && ($page < $result['total_pages'])) {
        	$page++;
        	$loop_cnt++;
        	if($loop_cnt <= $this->max_loop_cnt){
            	return $this->_continueSync($startDate, $endDate, $page,$loop_cnt);
        	}
        }

        return $result;
    }

    public function syncLiveGamingOriginalLogs($startDate, $endDate, $page){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate,
			'page' => $page
		);

		$params = array(
			'page' => $page,
			'size' => 100, #100 max size
			'start_time' => $startDate,
			'end_time' => $endDate
		);

		$this->method = self::POST;

		return $this->callApi(self::API_syncGameRecords, $params, $context);

    }

	private function rebuildGameRecords(&$gameRecords,$extra){
		$new_gameRecords =[];

        foreach($gameRecords as $index => $record) {
			$exUsername = explode("@", $record['username']);
			$new_gameRecords[$index]['livegaming_id'] = isset($record['id'])?$record['id']:null;
			$new_gameRecords[$index]['gameid'] = isset($record['gameid'])?$record['gameid']:null;
			$new_gameRecords[$index]['get_money'] = isset($record['get_money'])?$record['get_money']:null;
			$new_gameRecords[$index]['total_bet_money'] = isset($record['total_bet_money'])?$record['total_bet_money']:null;
			$new_gameRecords[$index]['draw_money'] = isset($record['draw_money'])?$record['draw_money']:null;
			$new_gameRecords[$index]['is_test'] = isset($record['is_test'])?$record['is_test']:null;
			$new_gameRecords[$index]['create_time'] = isset($record['create_time'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s',strtotime($record['create_time']))):null;
			$new_gameRecords[$index]['username'] = isset($record['username'])?$record['username']:null;
			$new_gameRecords[$index]['game_username'] = isset($exUsername[1])?$exUsername[1]:null;
			$new_gameRecords[$index]['bet_detail'] = isset($record['bet_detail'])?json_encode($record['bet_detail']):null;
            $new_gameRecords[$index]['external_uniqueid'] = isset($record['id'])?$record['id']:null;
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
		$resultArr = json_decode($this->decrypt($resultArr['data']),true);
		# total page 0 means skip continue sync
		$total_pages = isset($resultArr['pageinfo']['page_num'])?$resultArr['pageinfo']['page_num']:0;
		$gameRecords = !empty($resultArr['list'])?$resultArr['list']:[];
		$result = array('data_count'=>0,'page'=>$page,'total_pages'=>$total_pages);

		if($success&&!empty($gameRecords)){
            $extra = ['response_result_id'=>$responseResultId];
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
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`lg`.`updated_at` >= ?
          AND `lg`.`updated_at` <= ?';
        if($use_bet_time){
            $sqlTime='`lg`.`create_time` >= ?
          AND `lg`.`create_time` <= ?';
        }

        $sql = <<<EOD
			SELECT
				lg.id as sync_index,
				lg.response_result_id,
				lg.livegaming_id as round,
				lg.game_username as username,
				lg.total_bet_money as bet_amount,
				lg.total_bet_money as valid_bet,
				lg.get_money as result_amount,
				lg.create_time as start_at,
				lg.create_time as end_at,
				lg.create_time as bet_at,
				lg.gameid as game_code,
				lg.gameid as game_name,
				lg.bet_detail as bet_details,
				lg.updated_at,
				lg.external_uniqueid,
				lg.md5_sum,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_table as lg
			LEFT JOIN game_description as gd ON lg.gameid = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON lg.game_username = game_provider_auth.login_name
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
		$bet_details = json_decode($row['bet_details'],true);
		// echo "<pre>";print_r(json_decode($row['bet_details'],true));exit;
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
                'result_amount' => $row['result_amount'],
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
            'bet_details' => $bet_details['bet_info'],
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
        $row['status'] = Game_logs::STATUS_SETTLED;
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

	public function getGameProviderGameList($game_list = 1, $lang = 'zh-cn') {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processGetGameProviderGameList',

		);
		$data = array(
			'lang' => $lang,
			'is_need_game_list' => $game_list
		);

		$params = array(
			'merchant key'=> $this->merchant_key,
			'data' => $this->buildData($data)
		);

		$this->method = self::POST;
		return $this->callApi(self::API_getGameProviderGamelist, $params, $context);
	}

	public function processGetGameProviderGameList($params) {

		$this->CI->load->library('Game_list_lib');
		$resultJson = $this->getResultJsonFromParams($params);
		$obj = $this->decrypt($resultJson['data']);


		$json_decode = json_decode($obj,true);
		$success = isset($json_decode['game_list'])?true:false;

		if($success && $json_decode['game_list'] > 0){
			$list = [];

			$gameTypeList = array(
				'2' => 'table_and_cards',
				'3' => 'arcade',
				'10' => 'lottery',
				'11' => 'others'
			);

			$this->CI->load->model(['game_description_model','game_type_model']);
			$dbGameTypeList = $this->getDBGametypeList();



			foreach ($json_decode['game_list'] as $key => $gameDetail) {

				$gameTypeCode = isset( $gameTypeList[$gameDetail['channel_id']]) ? $gameTypeList[$gameDetail['channel_id']] : 'others';
				$gameTypeId = $dbGameTypeList[$gameTypeCode]['id'];

				$lang_arr = [
					self::INT_LANG_ENGLISH 		=> $gameDetail['game_name'],
					self::INT_LANG_CHINESE 		=> $gameDetail['game_name'],
					self::INT_LANG_INDONESIAN   => $gameDetail['game_name'],
					self::INT_LANG_VIETNAMESE   => $gameDetail['game_name'],
					self::INT_LANG_KOREAN 		=> $gameDetail['game_name']
				];

				$list[$key] = [
					'game_platform_id' 	 => $this->getPlatformCode(),
					'game_type_id' 	  	 => $gameTypeId,
					'game_code' 	 	 => $gameDetail['game_id'],
					'attributes' 	 	 => '{"game_launch_code":"'. $gameDetail['game_id'].'""}',
					'english_name' 		 => $gameDetail['game_name'],
					'external_game_id' 	 => $gameDetail['game_id'],
					'enabled_freespin' 	 => Game_description_model::DB_FALSE,
					'sub_game_provider'  => null,
					'enabled_on_android' => Game_description_model::DB_TRUE,
					'enabled_on_ios' 	 => Game_description_model::DB_TRUE,
					'status' 			 => Game_description_model::DB_TRUE,
					'flash_enabled' 	 => Game_description_model::DB_TRUE,
					'mobile_enabled' 	 => Game_description_model::DB_TRUE,
					'html_five_enabled'  => Game_description_model::DB_TRUE,
					'game_name' 		 => $this->processLanguagesToJson($lang_arr),
				];
			}
			$result = $this->CI->game_description_model->syncGameDescription($list,null, false, true, null, $this->getGameListAPIConfig());
		}
		return array($success, $result);
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