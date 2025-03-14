<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: RGS
* Game Type: Horsing
* Wallet Type: Transfer Wallet
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @wilson.php.ph
**/

class Abstract_game_api_common_rgs extends Abstract_game_api {
	const METHOD_GET = "GET";
	const METHOD_POST = "POST";

	const CALLBACK_SUCCESS = 'SUCCEEDED';
	const CALLBACK_FAILED = 'FAILED';

	const BET_STATUS_SETTLED = 'SETTLED';
	const BET_STATUS_VOID = 'VOID-ED';
	const BET_STATUS_CONFIRMED = 'CONFIRMED';

	const BET_RESULT_WIN = 'WIN';
	const BET_RESULT_LOST = 'LOST';
	const BET_RESULT_DRAW = 'DRAW_NO_BET';
	const BET_RESULT_REFUND = 'REFUND';
    const BET_RESULT_NOT_SETTLED = 'NOT SETTLED';

    const SELECTION_WIN_MAX_BASE = 10010000;
    const SELECTION_WIN_MAX = 40;
    const SELECTION_PLACE_MAX_BASE = 10020000;
    const SELECTION_PLACE_MAX = 40;

	const BET_TYPE_WIN = 'Win';
    const BET_TYPE_PLACED = 'Place';
    const BET_TYPE_OVER_UNDER_ON_POSTION_1 = 'Over/Under on Position 1 (win)';
    const BET_TYPE_ODD_EVEN_ON_POSTION_1 = 'Odd/Even on Position 1 (win)';
    const BET_TYPE_OVER_UNDER_ON_POSITION_2 = 'Over/Under on Position 2 (first runner up)';
    const BET_TYPE_ODD_EVEN_ON_POSITION_2 = 'Odd/Even on Position 2 (first runner up)';
    const BET_TYPE_OVER_UNDER_ON_POSITION_3 = 'Over/Under on Position 3 (second runner up)';
    const BET_TYPE_ODD_EVEN_ON_POSITION_3 = 'Odd/Even on Position 3 (second runner up)';

    const CALLBACK_RETURN_MSG_FAILED = '​Authentication Failed';

    const API_syncGameRecordsByUpdatedAt = "syncGameRecordsByUpdatedAt";

	const URI_MAP = array(
		self::API_createPlayer => 'PlayerApi/CreatePlayer.action',
		self::API_depositToGame => 'WalletApi/Deposit.action',
		self::API_withdrawFromGame => 'WalletApi/Withdraw.action',
		self::API_login => 'Auth.action',
		self::API_queryPlayerBalance => 'WalletApi/GetBalance.action',
		self::API_syncGameRecords => 'GameApi/GetBetHistory.action',
        self::API_queryForwardGame => '/Auth.action',
        self::API_syncGameRecordsByUpdatedAt => 'GameApi/GetAllBet.action',
	);

	const MD5_FIELDS_FOR_ORIGINAL = ['betId','betStatusId','betResultId','totalStake','memberResultAmount','raceVenue','game_type', 'betTypeValue', 'betStatusValue', 'betResultValue', 'eventDisplayDateTime'];
	const MD5_FLOAT_AMOUNT_FIELDS = ['totalStake','memberResultAmount'];
	const MD5_FIELDS_FOR_MERGE = ['betId','betStatusId','betResultId','totalStake','memberResultAmount','raceVenue', 'betResultValue', 'game_date'];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = ['totalStake','result_amount'];

	public function __construct() {
		parent::__construct();

		$this->CI->load->model(array('common_token','original_game_logs_model'));

		$this->api_url = $this->getSystemInfo('url');
		$this->licenseeKey = $this->getSystemInfo('licenseeKey');
		$this->member_portal = $this->getSystemInfo('member_portal');
		// CNY,HKD,USD,GBP
		$this->currency = $this->getSystemInfo('currency');
		// en, zh_cn, zh_tw
        $this->language = $this->getSystemInfo('language');

        $this->common_wait_seconds = $this->getSystemInfo('common_wait_seconds',15);

        $this->get_data_by_date_updated = $this->getSystemInfo('get_data_by_date_updated', false);

	}

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];
		$params_string = http_build_query($params);

		if ($apiName == self::API_queryForwardGame || $apiName == self::API_login) {
			$url = $this->member_portal . "/" . $apiUri . "?" . $params_string;
		} else {
			$url = $this->api_url . "/" . $apiUri . "?" . $params_string;
		}

		$this->CI->utils->debug_log('RGS generateUrl', $url, $apiName, 'params', $params);
		return $url;
    }

    protected function customHttpCall($ch, $params) {
        $this->utils->debug_log("RGS customHttpCall ============================>", json_encode($params));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    }

	public function callback($method, $result = null) {

		$this->CI->utils->debug_log('RGS callback ---', $result);

		if ($method == 'validatemember') {

			$datetime = new DateTime();
			$token = isset($result['token']) ? @$result['token'] : null;

			$this->CI->utils->debug_log('RGS callback token ---', $token, $result);

			//default result will return if failed
			$data = array(
				"userCode" => "",
				"returnCode" => self::CALLBACK_FAILED,
				"message" => self::CALLBACK_RETURN_MSG_FAILED,
			);

			if (!empty($token)) {
				$playerId = $this->CI->common_token->getPlayerIdByToken($token);

				if (!empty($playerId)) {
					$login_name = $this->getGameUsernameByPlayerId($playerId);
					$this->CI->utils->debug_log('RGS playerId', $playerId, 'login_name', $login_name);

					if (!empty($login_name)) {
						//this result will return if success
						$data = array(
							"userCode" => $login_name,
							"returnCode" => self::CALLBACK_SUCCESS,
							"message" => "",
						);
					}
				}
			}
		}
		return $data;
	}

	public function processResultBoolean($responseResultId, $resultArr, $username=null){
        $success = false;
        if(!empty($resultArr) && $resultArr['returnCode'] == self::CALLBACK_SUCCESS) {
            $success = true;
        }

        if(!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('RGS Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
    	parent::createPlayer($playerName, $playerId, $password, $email, $extra);
    	$language = $this->getLauncherLanguage($this->language);
    	$playerId = $this->getPlayerIdInPlayer($playerName);
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUsername' => $gameUsername,
			'playerId' => $playerId,
		);

		$params = array(
			"licenseeKey" => $this->licenseeKey,
			"userCode" => $gameUsername,
			"currencyCode" => $this->currency,
		);

		$result_arr = $this->callApi(self::API_createPlayer, $params, $context);

		return $result_arr;
    }

    public function login($playerName, $password = null) {
    	$token = $this->getPlayerTokenByUsername($playerName);
    	$language = $this->getLauncherLanguage($this->language);
    	$playerId = $this->getPlayerIdInPlayer($playerName);
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'gameUsername' => $gameUsername,
			'playerId' => $playerId,
		);

		$params = array(
			"licenseeKey" => $this->licenseeKey,
			"userCode" => $gameUsername,
			"token" => $token,
			"currencyCode" => $this->currency,
			"language" => $language,
		);

		$result_arr = $this->callApi(self::API_login, $params, $context);

		return $result_arr;
    }

    public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$resultTxt = $this->getResultTextFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		if ($resultTxt != "​Authentication Failed") {
			$resultArr = array(
				"returnCode" => self::CALLBACK_SUCCESS,
			);
		}

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array('response_result_id' => $responseResultId);

		$this->CI->utils->debug_log('RGS processResultForCreatePlayer: ', $success, $resultArr, $resultTxt);

		return array($success, $resultArr);
	}

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
		);

		$params = array(
			"licenseeKey" => $this->licenseeKey,
			"userCode" => $gameUsername,
			"currencyCode" => $this->currency,
			"amount" => $amount,
			"referenceNo" => $external_transaction_id,
		);

		$result_arr = $this->callApi(self::API_depositToGame, $params, $context);

		return $result_arr;
    }

    public function processResultForDepositToGame($params) {

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $statusCode = $this->getStatusCodeFromParams($params);

        $this->CI->utils->debug_log('RGS processResultForDepositToGame: ', $success, $resultArr);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id'] = self::REASON_UNKNOWN;
            }
        }

        return [$success, $result];

	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
		);

		$params = array(
			"licenseeKey" => $this->licenseeKey,
			"userCode" => $gameUsername,
			"currencyCode" => $this->currency,
			"amount" => $amount,
			"referenceNo" => $external_transaction_id,
		);

		$result_arr = $this->callApi(self::API_withdrawFromGame, $params, $context);

		return $result_arr;
    }

    public function processResultForWithdrawFromGame($params) {

		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $this->CI->utils->debug_log('RGS processResultForWithdrawFromGame: ', $success, $resultArr);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

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
    		"licenseeKey" => $this->licenseeKey,
			"userCode" => $gameUsername,
    	);

    	$result_arr = $this->callApi(self::API_queryPlayerBalance, $params, $context);

    	return $result_arr;
    }
    public function processResultForQueryPlayerBalance($params) {

    	$playerName = $this->getVariableFromContext($params, 'playerName');
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
    	$statusCode = $this->getStatusCodeFromParams($params);
    	$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
    	$result = ['response_result_id'=>$responseResultId];

    	if($success){
			$result['balance'] = $resultArr['balance'];
		}

    	$this->CI->utils->debug_log('RGS processResultForQueryPlayerBalance: ', $success, $resultArr);

        return [$success, $result];

    }

    public function queryForwardGame($playerName, $extra) {

        $token = $this->getPlayerTokenByUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $game_url = $this->member_portal;

        if(empty($this->language)){
            if(isset($extra['language'])){
                $language = $this->getLauncherLanguage($extra['language']);
            }
        }else{
            $language = $this->getLauncherLanguage($this->language);
        }

    	$params = array(
			"licenseeKey" => $this->licenseeKey,
			"userCode" => $gameUsername,
			"token" => $token,
			"currencyCode" => $this->currency,
			"language" => $language,
		);

        if(! empty($this->getHomeLink())){
            $params['logoutRedirectUrl'] = $this->getHomeLink();
        }

		$url_params = "?".http_build_query($params);

		$generateUrl = $game_url . self::URI_MAP[self::API_queryForwardGame] . $url_params;

        $result = array(
            "success" => true,
            "data" => $params,
            "url" => $generateUrl
        );

        $this->utils->debug_log("RGS queryForwardGame", json_encode($result), json_encode($params));

        return $result;

    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {

    	$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate->modify($this->getDatetimeAdjust());
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));

        $this->CI->utils->info_log(__METHOD__.' DATE from and to startDate', $startDate, 'endDate', $endDate);

        $rowsCount = 0;

        $result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+12 hours', function($startDate, $endDate) use(&$rowsCount){

            $while = true;
            $success = false;

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
            );

            while($while){

                $params['licenseeKey'] = $this->licenseeKey;
                $apiMethod = self::API_syncGameRecords;

                if($this->get_data_by_date_updated){
                    $params['updatedDateTimeFrom'] = $startDate->format('Y-m-d H:i:s');
                    $params['updatedDateTimeTo'] = $endDate->format('Y-m-d H:i:s');
                    $apiMethod = self::API_syncGameRecordsByUpdatedAt;
                }else{
                    $params['eventDateTimeFrom'] = $startDate->format('Y-m-d H:i:s');
                    $params['eventDateTimeTo'] = $endDate->format('Y-m-d H:i:s');
                }


                $this->CI->utils->debug_log(__METHOD__." params ====>", $params);

                sleep($this->common_wait_seconds);

                $apiResult = $this->callApi($apiMethod,$params,$context);

                $rowsCount = isset($apiResult['data_count']) ? $apiResult['data_count'] : 0;
                $success = isset($apiResult['success']) ? $apiResult['success'] : false;

                if($success){
                    $while = false;
                    $success = true;
                }else{
                    sleep($this->common_wait_seconds);
                    $params['eventDateTimeFrom'] = $startDate->format('Y-m-d H:i:s');
                    $params['eventDateTimeTo'] = $endDate->format('Y-m-d H:i:s');

                    $this->CI->utils->debug_log(__METHOD__." API exhausted, sleeping in seconds:", $this->common_wait_seconds,'params',$params);

                    continue;
                }

                return $success;
            }
        });

        $callResult = isset($result[0]) ? $result[0] : false;

        return [
            'success' => $callResult,
            'rows_count' => $rowsCount
        ];
    }

    public function processResultForSyncGameRecords($params) {
        // $this->CI->load->model('original_game_logs_model');
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $responseRecords = !empty($resultArr)?$resultArr:[];
        $gameRecords = !empty($responseRecords['bets']) ? $responseRecords['bets'] : [];
        $this->utils->debug_log("processResultForSyncGameRecords============================>", json_encode($gameRecords));

        if($success&&!empty($gameRecords)) {
        	$extra = ['response_result_id' => $responseResultId];
            $this->rebuildGameRecords($gameRecords,$extra);

            $this->CI->utils->debug_log('before process available rows', 'gamerecords ->',count($gameRecords), 'gameRecords->', json_encode($gameRecords));

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

            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($updateRows);

            $dataResult['data_count'] = is_array($gameRecords) ? count($gameRecords) : 0;
            $result['returnCode'] = $responseRecords['returnCode'];
        }

        return [
            $success,
            $dataResult
        ];
    }

    private function rebuildGameRecords(&$gameRecords,$extra) {
        $responseResultId = $extra['response_result_id'];
        $insertRecord =[];
        foreach($gameRecords as $key => $record)
        {
        	$player = isset($record['memberUserCode']) ? $this->getPlayerIdInGameProviderAuth(strtolower($record['memberUserCode'])) : NULL;
        	$playerId = ($player) ?  $player : $record['memberUserCode'];

        	$betStatusId = isset($record['betStatusId']) ? $record['betStatusId'] : NULL;
        	$betStatusValue = ($betStatusId) ? $this->getBetStatus($betStatusId) : NULL;

        	$betResultId = isset($record['betResultId']) ? $record['betResultId'] : NULL;
        	$betResultValue = ($betResultId) ? $this->getBetResult($betResultId) : NULL;

        	$betTypeId = isset($record['betTypeId']) ? $record['betTypeId'] : NULL;
        	$betTypeValue = ($betTypeId) ? $this->getBetType($betTypeId) : NULL;

            $insertRecord[$key]['betId'] = isset($record['betId']) ? $record['betId'] : NULL;
            $insertRecord[$key]['betStatusId'] = $betStatusId;
            $insertRecord[$key]['betStatusValue'] = $betStatusValue;
            $insertRecord[$key]['betResultId'] = $betResultId;
            $insertRecord[$key]['betResultValue'] = $betResultValue;
            $insertRecord[$key]['memberUserCode'] = isset($record['memberUserCode']) ? $record['memberUserCode'] : NULL;
            $insertRecord[$key]['memberCurrencyCode'] = isset($record['memberCurrencyCode']) ? $record['memberCurrencyCode'] : NULL;
            $insertRecord[$key]['totalStake'] = isset($record['totalStake']) ? $record['totalStake'] : NULL;
            $insertRecord[$key]['memberResultAmount'] = isset($record['memberResultAmount']) ? $record['memberResultAmount'] : NULL;
            $insertRecord[$key]['betTypeId'] = $betTypeId;
            $insertRecord[$key]['betTypeValue'] = $betTypeValue;
            $insertRecord[$key]['selectionTypeId'] = isset($record['selectionTypeId']) ? $record['selectionTypeId'] : NULL;
            $insertRecord[$key]['odds'] = isset($record['odds']) ? $record['odds'] : NULL;
            $insertRecord[$key]['eventDisplayDateTime'] = isset($record['eventDisplayDateTime']) ? $this->gameTimeToServerTime($record['eventDisplayDateTime']) : NULL;
            $insertRecord[$key]['raceCountry'] = isset($record['raceCountry']) ? $record['raceCountry'] : NULL;
            $insertRecord[$key]['raceVenue'] = isset($record['raceVenue']) ? $record['raceVenue'] : NULL;
            $insertRecord[$key]['raceTime'] = isset($record['raceTime']) ? $record['raceTime'] : NULL;
            $insertRecord[$key]['contenderName_en'] = isset($record['contenderName_en']) ? $record['contenderName_en'] : NULL;
            $insertRecord[$key]['contenderName_zh-TW'] = isset($record['contenderName_zh-TW']) ? $record['contenderName_zh-TW'] : NULL;
            $insertRecord[$key]['contenderName_zh-CN'] = isset($record['contenderName_zh-CN']) ? $record['contenderName_zh-CN'] : NULL;
            $insertRecord[$key]['game_type'] = isset($record['gameType']) ? $record['gameType'] : NULL;

            $insertRecord[$key]['player_id'] = $playerId;
            $insertRecord[$key]['external_uniqueid'] = $insertRecord[$key]['betId']; //add external_uniueid for og purposes
            $insertRecord[$key]['response_result_id'] = $responseResultId;
            $insertRecord[$key]['created_at'] = $this->utils->getNowForMysql();
        }
        $gameRecords = $insertRecord;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType) {
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token){
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime='rgs.updated_at >= ? and rgs.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime='rgs.eventDisplayDateTime >= ? and rgs.eventDisplayDateTime <= ?';
        }

        $sql = <<<EOD
SELECT rgs.id as sync_index,
rgs.memberUserCode as player_username,
rgs.raceCountry as game,
rgs.betId as round,
rgs.totalStake AS bet_amount,
rgs.memberResultAmount AS result_amount,
rgs.eventDisplayDateTime as game_date,
rgs.response_result_id,
rgs.external_uniqueid,
rgs.created_at,
rgs.updated_at,
rgs.md5_sum,
rgs.betStatusValue as status,
rgs.raceVenue,
rgs.raceTime,
rgs.selectionTypeId,
rgs.odds,
rgs.game_type as game_code,
rgs.betResultValue,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_name,
gd.game_type_id

FROM {$this->original_gamelogs_table} as rgs
LEFT JOIN game_description as gd ON rgs.game_type = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON rgs.memberUserCode = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        $extra_info=[];
        $has_both_side=0;

        if(empty($row['md5_sum'])) {
            //genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $result_amount = $row['result_amount'];
        $bet_amount = $row['bet_amount'];

        $extra_info = array (
        	'odds' => $row['odds']
        );

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_code'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['game_date'],
                'end_at' => $row['game_date'],
                'bet_at' => $row['game_date'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row) {
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $row['status'] = $this->getGameRecordsStatus($row['status']);
        $row['bet_details'] = $this->generateBetDetails($row);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    private function generateBetDetails($data) {
		if ($data) {
			$res = [];

			$res['Race Country'] = $data['game'];
			$res['Race Venue'] = $data['raceVenue'];
			$res['Race Time'] = $data['raceTime'];
			$res['Selection Type Id'] = $data['selectionTypeId'];
			$res['Selection Type Value'] = $this->getGameSelectionType($data['selectionTypeId']);
			$this->CI->utils->debug_log('generateBetDetails', $res);
		}

		return $res;
	}

	private function getGameSelectionType($selectionTypeId) {

        switch($selectionTypeId){
            case 11010001:
                $value = 'Over on Position 1';
            break;
            case 11010002:
                $value = 'Under on Position 1';
            break;
            case 11020001:
                $value = 'Odd on Position 1';
            break;
            case 11020002:
                $value = 'Even on Position 1';
            break;
            case 11030001:
                $value = 'Over on Position 2';
            break;
            case 11030002:
                $value = 'Under on Position 2';
            break;
            case 11040001:
                $value = 'Odd on Position 2';
            break;
            case 11040002:
                $value = 'Even on Position 2';
            break;
            case 11050001:
                $value = 'Over on Position 3';
            break;
            case 11050002:
                $value = 'Under on Position 3';
            break;
            case 11060001:
                $value = 'Odd on Position 3';
            break;
            case 11060002:
                $value = 'Even on Position 3';
            break;
            default:
                $bet_type_id = substr($selectionTypeId, 0, 4);
                $bet_type = ($bet_type_id == '1001') ? self::BET_TYPE_WIN : self::BET_TYPE_PLACED;
                $bet_type_num = substr($selectionTypeId, 4, 4);
                $bet_type_val = (string)((int)($bet_type_num));
                $value = "Contender no. " . $bet_type_val ." ". $bet_type;
            break;
        }

        for($i = 1;$i <= self::SELECTION_WIN_MAX;$i++){
            if(self::SELECTION_WIN_MAX_BASE+$i == $selectionTypeId){
                $value = "Contender no.". $i ." win";
            }
        }

        for($i2 = 1;$i2 <= self::SELECTION_PLACE_MAX;$i2++){
            if(self::SELECTION_PLACE_MAX_BASE+$i2 == $selectionTypeId){
                $value = "Contender no.". $i2 ." place";
            }
        }

        return $value;
    }

    private function getGameRecordsStatus($status) {
        // $this->CI->load->model(array('game_logs'));
        switch ($status) {
	        case self::BET_STATUS_CONFIRMED:
	            $status = Game_logs::STATUS_PENDING;
	            break;
	        case self::BET_STATUS_VOID:
	            $status = Game_logs::STATUS_REJECTED;
	            break;
	        case self::BET_STATUS_SETTLED:
	            $status = Game_logs::STATUS_SETTLED;
	            break;
        }
        return $status;
    }

    public function getLauncherLanguage($language) {
        $lang='';
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh_cn':
                $lang = 'zh_cn';
                break;
            case 'zh_tw':
                $lang = 'zh_tw';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id':
            case 'id_id':
                $lang = 'id_id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th':
                $lang = 'th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi_vn':
            case 'vi-vn':
                $lang = 'vi';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function getBetStatus($status_id) {
        $val='';
        switch ($status_id) {
        	case 6:
                $val = self::BET_STATUS_CONFIRMED;
                break;
            case 8:
                $val = self::BET_STATUS_SETTLED;
                break;
            case 9:
                $val = self::BET_STATUS_VOID;
                break;
            default:
                $val = self::BET_STATUS_SETTLED;
                break;
        }
        return $val;
    }

    public function getBetResult($res_id) {
        $val='';
        switch ($res_id) {
            case 1:
                $val = self::BET_RESULT_WIN;
                break;
            case 2:
                $val = self::BET_RESULT_LOST;
                break;
            case 3:
                $val = self::BET_RESULT_DRAW;
                break;
            case 6:
                $val = self::BET_RESULT_REFUND;
                break;
            case 9:
                $val = self::BET_RESULT_NOT_SETTLED;
            default:
                $val = self::BET_RESULT_WIN;
                break;
        }
        return $val;
    }

    public function getBettype($bet_type) {
        $val='';
        switch ($bet_type) {
            case 1001:
                $val = self::BET_TYPE_WIN;
                break;
            case 1002:
                $val = self::BET_TYPE_PLACED;
                break;
            case 1101:
                $val = self::BET_TYPE_OVER_UNDER_ON_POSTION_1;
                break;
            case 1102:
                $val = self::BET_TYPE_ODD_EVEN_ON_POSTION_1;
                break;
            case 1103:
                $val = self::BET_TYPE_OVER_UNDER_ON_POSITION_2;
                break;
            case 1104:
                $val = self::BET_TYPE_ODD_EVEN_ON_POSITION_2;
                break;
            case 1105:
                $val = self::BET_TYPE_OVER_UNDER_ON_POSITION_3;
                break;
            case 1106:
                $val = self::BET_TYPE_ODD_EVEN_ON_POSITION_3;
                break;
            default:
                $val = self::BET_TYPE_WIN;
                break;
        }
        return $val;
    }

}