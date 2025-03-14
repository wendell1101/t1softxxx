<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Booming games 
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

abstract class Abstract_game_api_common_pretty_gaming_api extends Abstract_game_api {
	const METHOD_POST = 'POST';
	const METHOD_GET = 'GET';

    const GAMELIST_TABLE = 'api_gamelist';
	const PAGE_INDEX_START = 1;

	public $do_payout_on_bet;

	# Fields in fg_seamless_gamelogs we want to detect changes for update
    const MD5_FIELDS_FOR_GAME_LOGS = [
        'validAmt',
        'payOutCom',
        'payOutBet',
        'winLose',
        'payOutAmt',
        'betStatus',	
        'status',	
        'memberUsername',	
        'ticketId',	
        'type',	
        'gameId',	
        'tableId',
        'betAmt',
        'createDate',
        'updateDate'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = [
        'validAmt',
        'payOutCom',
        'payOutBet',
        'winLose',
        'payOutAmt',
        'betAmt',
    ];

	# Fields in fg_seamless_gamelogs we want to detect changes for update
    const MD5_FIELDS_FOR_TRANSACTION_LOGS = [
        'playerUsername',
        'ticketId',
        'type',
        'currency',
        'gameId',
        'totalBetAmt',
        'totalPayOutAmt',
        'winLoseTurnOver',
        'txtList',
        'createDate',
        'requestDate',
        'status'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION_LOGS = [
        'totalBetAmt',
        'totalPayOutAmt',
        'winLoseTurnOver'
    ];

	# Fields in game_logs we want to detect changes for merge, and when redtiger_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'game_code',
        'real_betting_amount',
        'result_amount',
        'start_at',
        'end_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

	const GAME_LOGS = 'game';
	const TRANSACTION_LOGS = 'transaction';

	const URI_MAP = array(
		self::API_queryPlayerBalance => '/api/getUserBalance',
		self::API_depositToGame => '/api/deposit',
		self::API_withdrawFromGame => '/api/withdrawal',
		self::API_queryTransaction => '/api/depositWithdrawHistoryByTransId',
		self::API_queryForwardGame => '/member/loginRequest',
		self::API_syncGameRecords => '/api/betHistories',
	);

	public function __construct() {
		parent::__construct();
		
		$this->agent_username = $this->getSystemInfo('agent_username');
		$this->agent_api_key = $this->getSystemInfo('agent_api_key');
		$this->betLimit = $this->getSystemInfo('betLimit', []);

		$this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
		$this->html5_home_url = $this->getSystemInfo('html5_home_url', $this->current_domain);
		$this->mobile_home_url = $this->getSystemInfo('mobile_home_url', $this->current_domain);


		$this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
		$this->isRedirect = $this->getSystemInfo('isRedirect', false);
		$this->force_lowercase_username = $this->getSystemInfo('force_lowercase_username', true);
		$this->do_payout_on_bet = $this->getSystemInfo('do_payout_on_bet', false);
		$this->sync_original_sleep = $this->getSystemInfo('sync_original_sleep', 0);
		$this->bet_details_url = $this->getSystemInfo('bet_details_url', 'https://status-dev.hippo168.com/?gameId=');
		
		$this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore', false); 
		
		$this->adjust_dateto_minutes_sync_merge = $this->getSystemInfo('adjust_dateto_minutes_sync_merge', 0);

		$this->currency = $this->getSystemInfo('currency');
        $this->api_url = $this->getSystemInfo('url');

        $this->language = $this->getSystemInfo('language', false);

        $this->method = self::METHOD_POST;

        $this->CI->load->model(['game_provider_auth', 'original_game_logs_model', 'response_result', 'game_description_model', 'player_model', 'common_token']);
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
        $this->CI->utils->debug_log('PRETTY GAMING API generateUrl =====> ', $url);
		return $url;
	}

	protected function customHttpCall($ch, $params)
	{
		curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }

    protected function getHttpHeaders($params)
	{
        $headers['Content-Type'] = 'application/json';
		return $headers;
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if ((@$statusCode == 200 || @$statusCode == 201) && $resultArr['code'] === 0) {
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PRETTY GAMING got error ', $responseResultId, 'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName = null, $playerId = null, $password = null, $email = null, $extra = null)
	{
		$extra['force_lowercase_username'] = $this->force_lowercase_username;
		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$success = false;
		$message = "Unable to create Account for PRETTY GAMING API";
		if($return){
			$success = true;
			$message = "Successfull create account for PRETTY GAMING API";

			// Player needs to do login request API so that they can be registered on BO/API
			$this->queryForwardGame($playerName);
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
			'agentUsername'  => $this->agent_username,
			'agentApiKey'    => $this->agent_api_key,
			'playerUsername' => $gameUsername
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- PRETTY GAMING queryPlayerBalance params ----------', $params);

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

        $this->CI->utils->debug_log('---------- PRETTY GAMING processResultForQueryPlayerBalance response ----------', $resultArr);

		if($success){
			$result['balance'] = $resultArr['data']['balance'];
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

		$params = array(
			'agentUsername'  => $this->agent_username,
			'agentApiKey'    => $this->agent_api_key,
			'playerUsername' => $gameUsername,
			'balance'        => $amount,
			'transId'        => $external_transaction_id
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- PRETTY GAMING depositToGame params ----------', $params);

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

        $this->CI->utils->debug_log('---------- PRETTY GAMING processResultForDepositToGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
			$error_code = isset($resultArr['code']) ? $resultArr['code'] : null;
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($error_code, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons($resultArr['code']);
			}
		}

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
    {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? uniqid() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        );
      
		$params = array(
			'agentUsername'  => $this->agent_username,
			'agentApiKey'    => $this->agent_api_key,
			'playerUsername' => $gameUsername,
			'balance'        => $amount,
			'transId'        => $external_transaction_id
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- PRETTY GAMING depositToGame params ----------', $params);

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

        $this->CI->utils->debug_log('---------- PRETTY GAMING processResultForWithdrawFromGame response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr['code']);
		}

        return array($success, $result);
	}

	private function getReasons($code)
	{
		switch ($code) {
			case 997:
			case 71001:
			case 71002:
			case 71003:
			case 71004:
				return self::REASON_PARAMETER_ERROR;
				break;
			case 71017:
			case 911001:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	private function getReasonsTransaction($responseCode)
	{
		switch ($responseCode) {
			case 2:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	public function queryTransaction($transactionId, $extra) {
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
			'agentUsername'  => $this->agent_username,
			'agentApiKey'    => $this->agent_api_key,
			'transId'        => $transactionId
		);

		$this->method = self::METHOD_POST;
        $this->CI->utils->debug_log('---------- PRETTY GAMING queryTransaction params ----------', $params);

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

        $this->CI->utils->debug_log('---------- PRETTY GAMING processResultForQueryTransaction response ----------', $resultArr);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
		} else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($resultArr['code']);
		}

        return array($success, $result);   
    }

    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
        	case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $language = 'CNM';
                break;
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $language = 'EN';
                break;
			case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
				$language = 'VT';
				break;
            case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $language = 'TH';
                break;
            case 'id-id':
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $language = 'EN';
                break;
            default:
                $language = 'EN';
                break;
        }
        return $language;
    }

	/*
	 *	To Launch Game
	 *
	*/
	public function queryForwardGame($playerName = 'testpretty2', $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$is_mobile = false;
		if (!empty($extra['is_mobile'])) {
			$is_mobile = true;
		}

		$is_lobby = true;
		if (!empty($extra['game_code'])) {
			$is_lobby = '&game=' . $extra['game_code'];
		}
		
		if(isset($extra['language'])){
            $this->language = $this->getLauncherLanguage($extra['language']);
        }

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'gameUsername'    => $gameUsername,
			'is_mobile'       => $is_mobile,
			'is_lobby'        => $is_lobby
		);

		$params = array(
			'agentUsername'  => $this->agent_username,
			'agentApiKey'    => $this->agent_api_key,
			'playerUsername' => $gameUsername,
			'betLimit'       => $this->betLimit
		);

		$this->utils->debug_log("=== PRETTY GAMING: queryFowardGame ===");

		$this->method = self::METHOD_POST;
		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params)
	{
		$statusCode       = $this->getStatusCodeFromParams($params);
		$resultArr        = $this->getResultJsonFromParams($params);
		$gameUsername     = $this->getVariableFromContext($params, 'gameUsername');
		$is_mobile        = $this->getVariableFromContext($params, 'is_mobile');
		$is_lobby         = $this->getVariableFromContext($params, 'is_lobby');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success          = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result           = array('url'=>'', 'isRedirect' => $this->isRedirect);

        $this->CI->utils->debug_log('---------- PRETTY GAMING processResultForQueryTransaction response ----------', $resultArr);

		if ($success) {
			$result['url'] = @$resultArr['data']['uriDesktop'] . '&url=' . $this->html5_home_url;
			if ($is_mobile) {
				$result['url'] = @$resultArr['data']['uriMobile'] . '&url=' . $this->mobile_home_url;
			}

			if ($is_lobby !== true) {
				$result['url'] .= $is_lobby;
			}

			if ($this->language) {
				$result['url'] .= "&lang=" . $this->language;
			}



			$this->CI->utils->debug_log('URL RESULT ==>', @$resultArr);
		}

		return array($success, $result);
	}

	/*
	 * Note: You can only search data within the past 60 days.
	 * 7.6.3 Game History
	 */
	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$startDateTime->modify($this->getDatetimeAdjust());
    	$endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

		$startDateTime = $startDateTime->format('Y-m-d H:i:s');
		$endDateTime = $endDateTime->format('Y-m-d H:i:s');

		$success = false;
		$done = false;
		$page = self::PAGE_INDEX_START;

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );

		for ($page = self::PAGE_INDEX_START; $done == false; $page++) {
			$params = array(
				'agentUsername' => $this->agent_username,
				'agentApiKey'   => $this->agent_api_key,
				'startDate'     => $startDateTime,
				'endDate'		=> $endDateTime,
				'page'			=> $page
			);

			$this->method = self::METHOD_POST;
			$this->CI->utils->debug_log('---------- PRETTY GAMING API syncOriginalGameLogs params ----------', $params);

			$api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
			$this->utils->info_log('---------- PRETTY GAMING API api_result ----------', $api_result);

			if ($api_result && $api_result['success']) {
				if ($page != $api_result['totalPages']) {
					$data_count = @$api_result['totalDocs'];
					$done = false;
					$this->CI->utils->debug_log('page: ', $page, 'total_data: ', $data_count, 'done', $done, 'result', $api_result);
					sleep($this->sync_original_sleep);
				} else {
					$done = true;
				}
			} else {
				$this->CI->utils->debug_log('PRETTY GAMING API API ERROR ======>', $api_result);
				$done = true;
			}

			if ($done) {
				$success = true;
			}
		}

	}

	public function processResultForSyncOriginalGameLogs($params)
	{
        $this->CI->load->model('original_game_logs_model');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = ['totalDocs' => 0, 'totalPages' => 0, 'data_count' => 0];
		$responseRecords = !empty($resultArr) ? $resultArr:[];
		$gameRecords = !empty($responseRecords['data']['result']['docs']) ? $responseRecords['data']['result']['docs'] : [];
		$this->CI->utils->debug_log('---------- PRETTY GAMING API syncOriginalGameLogs response ----------', $resultArr);

		if ($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $gameRecords = $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_GAME_LOGS,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS
            );

            unset($gameRecords);

            if (!empty($insertRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId], $this->original_gamelogs_table);
            }
            unset($updateRows);
		}

		$result['totalDocs'] = $responseRecords['data']['result']['totalDocs'];
		$result['totalPages'] = $responseRecords['data']['result']['totalPages'];
		return array($success, $result);
	}

	private function rebuildGameRecords($gameRecords,$extra)
	{
		$newGR =[];
		$i = 0;
        foreach($gameRecords as $gr)
        {

        	$ticketId = null;
        	$betPosition = null;
        	if (isset($gr['ticketId'])) {
        		$ticketId = $gr['ticketId'];
        	}

        	if (isset($gr['betPosition'])) {
        		$betPosition = $gr['betPosition'];
        	}

        	$replace = '@' . $this->agent_username;

			$newGR[$i]['validAmt']           = isset($gr['validAmt']) ? $this->gameAmountToDB(floatval($gr['validAmt'])) : null;
			$newGR[$i]['payOutCom']          = isset($gr['payOutCom']) ? $this->gameAmountToDB(floatval($gr['payOutCom'])) : null;
			$newGR[$i]['payOutBet']          = isset($gr['payOutBet']) ? $this->gameAmountToDB(floatval($gr['payOutBet'])) : null;
			$newGR[$i]['winLose']            = isset($gr['winLose']) ? $this->gameAmountToDB(floatval($gr['winLose'])) : null;
			$newGR[$i]['payOutAmt']          = isset($gr['payOutAmt']) ? $this->gameAmountToDB(floatval($gr['payOutAmt'])) : null;
			$newGR[$i]['betStatus']          = isset($gr['betStatus']) ? $gr['betStatus'] : null;
			$newGR[$i]['status']             = isset($gr['status']) ? $gr['status'] : null;
			$newGR[$i]['_id']            	 = isset($gr['_id']) ? $gr['_id'] : null;
			$newGR[$i]['memberId']           = isset($gr['memberId']) ? $gr['memberId'] : null;
			$newGR[$i]['username']           = isset($gr['memberUsername']) ? str_replace($replace, "", $gr['memberUsername'])  : null;
			$newGR[$i]['memberUsername']     = isset($gr['memberUsername']) ? $gr['memberUsername'] : null;
			$newGR[$i]['currency']           = isset($gr['currency']) ? $gr['currency'] : null;
			$newGR[$i]['ticketId']           = isset($gr['ticketId']) ? $gr['ticketId'] : null;
			$newGR[$i]['type']            	 = isset($gr['type']) ? $gr['type'] : null;
			$newGR[$i]['gameId']             = isset($gr['gameId']) ? $gr['gameId'] : null;
			$newGR[$i]['tableId']            = isset($gr['tableId']) ? $gr['tableId'] : null;
			$newGR[$i]['round']            	 = isset($gr['round']) ? $gr['round'] : null;
			$newGR[$i]['commissionRate']     = isset($gr['commissionRate']) ? $gr['commissionRate'] : null;
			$newGR[$i]['payOutRate']         = isset($gr['payOutRate']) ? $gr['payOutRate'] : null;
			$newGR[$i]['betPosition']        = isset($gr['betPosition']) ? $gr['betPosition'] : null;
			$newGR[$i]['betAmt']             = isset($gr['betAmt']) ? $this->gameAmountToDB(floatval($gr['betAmt'])) : null;
			$newGR[$i]['ip']            	 = isset($gr['ip']) ? $gr['ip'] : null;
			$newGR[$i]['updateDate']         = isset($gr['updateDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($gr['updateDate']))) : null;
			$newGR[$i]['createDate']         = isset($gr['createDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($gr['createDate']))) : null;
			$newGR[$i]['__v']            	 = isset($gr['__v']) ? $gr['__v'] : null;
			$newGR[$i]['result']             = isset($gr['result']) ? json_encode($gr['result']) : null;
			$newGR[$i]['external_uniqueid']  = $ticketId . '-' . $betPosition;
			$newGR[$i]['extra']            	 = json_encode($gr);
			$i+=1;
        }
        $gameRecords = $newGR;
        return $gameRecords;
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

        $sqlTime = '`pg`.`updateDate` >= ?
          AND `pg`.`updateDate` <= ?';

        $sql = <<<EOD
SELECT 
    gd.game_type_id AS game_type_id,
    gd.id AS game_description_id,
    pg.type as game_code,
    gt.game_type AS game_type,
    gd.game_name AS game_name,
    gpa.player_id AS player_id,
    pg.username AS player_username,
    pg.gameId AS round_id,
    pg.ticketId AS ticketId,
    pg.betAmt AS bet_amount,
    pg.validAmt AS real_betting_amount,
    pg.payOutAmt AS win_amount,
    pg.createDate AS start_at,
    pg.updateDate AS end_at,
    pg.external_uniqueid AS external_uniqueid,
    pg.result AS bet_result,
    pg.md5_sum AS md5_sum
    FROM {$this->original_gamelogs_table} AS pg
	LEFT JOIN game_description AS gd ON pg.type = gd.external_game_id AND gd.game_platform_id = ?
	LEFT JOIN game_type AS gt ON gd.game_type_id = gt.id
	LEFT JOIN game_provider_auth AS gpa ON pg.username = gpa.login_name
	AND gpa.game_provider_id = ?
WHERE
	pg.status = ? AND
    {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            'SuccessfulPayment',
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
        $extra = [
            'table' =>  $row['ticketId'],
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
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => abs($row['real_betting_amount']),
                'result_amount' => abs($row['win_amount']) - abs($row['bet_amount']),
                'bet_for_cashback' => abs($row['bet_amount']),
                'real_betting_amount' => abs($row['bet_amount']),
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => 0,
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['start_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_id'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => null,
                'sync_index' => null,
                'bet_type' => null
            ],
            'bet_details' => json_decode($row['bet_result'], true),
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

    public function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[], $table_name)
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

	public function saveToResponseResult($success, $callMethod, $params, $response, $http_status_code = 200) {
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $headers = getallheaders();

        if (empty($extra)) {
            $extra = is_array($headers) ? json_encode($headers) : $headers;
        }

        return $this->CI->response_result->saveResponseResult($this->getPlatformCode(), $flag, $callMethod, json_encode($params), $response, $http_status_code, null, $extra);
    }    

    /** 
    *  The api will return the bet details URL link for viewing the details
    */
    public function queryBetDetailLink($playerUsername = null, $betid = null, $roundid = NULL)
    {
        $sql = <<<EOD
            SELECT id, gameId
            FROM {$this->original_gamelogs_table}
            WHERE ticketId = ?
EOD;
        $params = [
            $roundid
        ];

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	if (!empty($queryResult)) {
    		$url = $this->bet_details_url . $queryResult[0]['gameId'];

    		return ['success' => true, 'url' => $url];
    	}
		return ['success' => false, 'url' => null];

    }

    public function queryGameListFromGameProvider($extra = NULL) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );
        $params = '';
		$this->use_game_url = true;
		$this->method = self::METHOD_POST;
        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];

        if ($success) {
            $result['games'] = $resultArr['Data'];
        }
        return array($success, $result);
    }

    public function rebuildGameList($games) {
        $data = [];
        foreach ($games as $game) {
            $newGame = [];
            $external_uniqueid = isset($game['Id']) ? $game['Id'] . '-' . $this->getPlatformCode() : '';

            $newGame['game_platform_id']  = $this->getPlatformCode();
            $newGame['game_code'] 		  = isset($game['Id']) ? $game['Id'] : '';
            $newGame['json_fields'] 	  = !empty($game) ? json_encode($game) : '';
            $newGame['external_uniqueid'] = isset($external_uniqueid) ? $external_uniqueid : '';
	        $data[] = $newGame;
        }
        return $data;
    }

    public function updateGameList($games) {

        $this->CI->load->model(array('original_game_logs_model'));
        $games = $this->rebuildGameList($games);

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            self::GAMELIST_TABLE,
            $games,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_GAMES,
            'md5_sum',
            'id',
            []
        );

        $dataResult = [
            'data_count' => count($games),
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        if (!empty($insertRows)) {
            $dataResult['data_count_insert'] += $this->updateOrInsertGameList($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $dataResult['data_count_update'] += $this->updateOrInsertGameList($updateRows, 'update');
        }
        unset($updateRows);

        return $dataResult;
    }

    private function processGameListGameType($game_type) {
        $data = "";
        switch($game_type) {
            case self::GAME_TYPES['SLOTS']:
                $data = "Slots";
                break;
            case self::GAME_TYPES['FISHING']:
                $data = "Fishing";
                break;
			default:
                $data = "Others, Need to contact DEV | ";
                break;
        }
        return $data;
    }

    private function processGameListSupportedPlatform($platform) {
        $data = "";
        foreach ($platform as $val) {
	        switch($val) {
	            case self::TP_GPK_HTML5_PLATFORM:
	                $data .= "HTML5 | ";
	                break;
				default:
	                $data .= "Others, Need to contact DEV | ";
	                break;
	        }
        }
        return $data;
    }

    private function updateOrInsertGameList($data, $queryType){
        $dataCount = 0;
        if (!empty($data)) {
            $caption = [];
            if ($queryType == 'update') {
                $caption = "## UPDATE TP GAME LIST\n";
            }
            else {
                $caption = "## ADD NEW TP GAME LIST\n";
            }

            $body = "| English Name  | Chinese Name  | Game Code | Game Type | Supported Platform |\n";
            $body .= "| :--- | :--- | :--- |\n";

            foreach ($data as $record) {
            	$game = $record;
            	$record = json_decode($record['json_fields'], true);
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::GAMELIST_TABLE, $game);
                    $body .= "| {$record['GameNameEn']} | {$record['GameName']} | {$record['Id']} | {$this->processGameListGameType($record['GameGroupType'])} | {$this->processGameListSupportedPlatform($record['PlatFormType'])} |\n";
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::GAMELIST_TABLE, $game);
                    $body .= "| {$record['GameNameEn']} | {$record['GameName']} | {$record['Id']} | {$this->processGameListGameType($record['GameGroupType'])} | {$this->processGameListSupportedPlatform($record['PlatFormType'])} |\n";
                }
                $dataCount++;
                unset($record);
            }

            $this->sendMatterMostMessage($caption, $body);
        }
        return $dataCount;
    }

    public function sendMatterMostMessage($caption, $body){
        $message = [
            $caption,
            $body,
            "#TP/GPK_API"
        ];

        $channel = $this->utils->getConfig('gamelist_notification_channel');
        $this->CI->load->helper('mattermost_notification_helper');
        $user = 'TP/GPK Game List';

        sendNotificationToMattermost($user, $channel, [], $message);
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
