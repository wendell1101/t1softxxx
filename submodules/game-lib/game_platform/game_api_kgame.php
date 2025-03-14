<?php

/**
 * KGAME game integration
 * OGP-26112
 *
 * @author  Kristallynn Tolentino
 *
 */

require_once dirname(__FILE__) . '/abstract_game_api.php';

class game_api_kgame extends Abstract_game_api {

    /** methods of game provider API  @var const */
    const ACTION_CREATE_MEMBER = 'createuser';
    const ACTION_LOGIN = 'login';
    const ACTION_DEPOSIT = 'deposit';
    const ACTION_WITHDRAW = 'withdraw';
    const ACTION_BALANCE = 'balance';
    const ACTION_GETTRANSINFO = 'gettransinfo';
    const ACTION_GETGAMETABLE = 'getgametable';
    const ACTION_GETGAMENAMES = 'getgamenames';
    const ACTION_GETGAMELIST = 'getgamelist';

    /** error codes of game provider API  @var const */
    const CODE_SUCCESS = 0;
    const CODE_INCORRECT_CHANNEL_AUTH = 40001;
    const CODE_PARAM_ERROR = 40002;
    const CODE_INCORRECT_USERNAME_OR_PW = 40003;
    const CODE_INVALID_IP = 40004;
    const CODE_DB_ERROR = 40005;
    const CODE_UNKNOWN = 40006;
    const CODE_HIGH_FREQUENCY = 40007;
    const CODE_PLAYER_ALREADY_EXIST = 40008;
    const CODE_QUERY_DATA_NOT_EXIST = 40009;
    const CODE_SIGNATURE_ERROR = 40010;
    const CODE_PLAYER_NOT_EXIST = 40011;
    const CODE_PLAYER_FROZEN = 40012;
    const CODE_INSUFFICIENT_BAL = 40013;
    const CODE_TRANS_AMOUNT_EXCEEDS_LIMIT = 40015;

    const START_PAGE = 1;

    const METHOD = [
        "POST" => "POST",
        "GET" => "GET",
        "PUT" => "PUT"
    ];

    const URI_MAP = [
        self::API_createPlayer => "/eas/api/rest",
        self::API_queryForwardGame => "/eas/api/rest",
        self::API_depositToGame => "/eas/api/rest",
        self::API_withdrawFromGame => "/eas/api/rest",
        self::API_queryPlayerBalance => "/eas/api/rest",
        self::API_queryTransaction => "/eas/api/rest",
        self::API_syncGameRecords => "/eas/gamerecord/getlistbytime",
    ];

    const API_METHOD_AUTH_SESS = "auth_sess";

    const STATUS_CODE = [
        "SUCCESS" => 0,
        "INCORRECT_CHANNEL_AUTH_CODE" => 40001,
        "PARAM_ERROR" => 40002,
        "WRONG_USERNAME" => 40003
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        "username",
        "channel",
        "bet_item_name",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_money',
        'bbef_balance',
        'lw_money',
        'tax',
        'valid_bet',
        'send_prize',
        'external_uniqueid'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_code',
        'player_username',
        'start_at',
        'bet_at',
        'end_at',
        'result_amount',
        'real_betting_result',
        'bet_amount',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'result_amount',
        'real_betting_result',
        'bet_amount'
    ];

    public function __construct()
    {
        parent::__construct();

		$this->api_url = $this->getSystemInfo('url');
        $this->is_redirect = $this->getSystemInfo('is_redirect',true);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language', "en"); //zh_cn , en, vi, th
        $this->originalTable = 'kgame_game_logs';
        $this->max_record = $this->getSystemInfo('max_record', 500);

        //Required by provider
        $this->channel = $this->getSystemInfo("channel", 50092);
        $this->private_key = $this->getSystemInfo("private_key", "500921639448529Bnc965");
    }

    public function getPlatformCode()
    {
        return KGAME_API;
    }

    public function generateUrl($apiName, $params)
    {
        $uri = self::URI_MAP[$apiName];

        $url = $this->api_url . $uri. '?' . http_build_query($params);

        return $url;
	}

    protected function customHttpCall($ch, $params)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    }

    public function processResultBoolean($responseResultId, $resultArr)
    {
		$success = false;

        if($resultArr["errcode"] == self::CODE_SUCCESS){
            $success = true;
        }

		if (!$success)
        {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('KGAME API got error ', $responseResultId, 'result', $resultArr);
		}

		return $success;
	}

    public function getStatus($input_code, $field = null)
    {
        foreach(self::STATUS_CODE as $msg => $code) {
            if($code == $input_code)
            {
                $status_code = $code;
                $status_message = $msg;
                break;
            }
        }

        if(is_null($field))
        {
            return [
                "code" => $status_code,
                "msg" => $status_message
            ];
        }

        return [
            "code" => $status_code,
            "msg" => $status_message ." (". $field .")"
        ];
    }

	public function callback($method_params, $method)
    {
        $this->CI->load->model(array('common_token', 'player_model'));

        if($method == self::API_METHOD_AUTH_SESS)
        {
            //method params from partner
            if (array_key_exists('channel', $method_params)) {
                $channel = $method_params["channel"];
            }else{
                return $params = [
                    $this->getStatus(503, "key channel")
                ];
            }

            if (array_key_exists('username', $method_params)) {
                $username = $method_params["username"];
            }else{
                return $params = [
                    $this->getStatus(503, "key username")
                ];
            }

            if (array_key_exists('session', $method_params)) {
                $token = $method_params["session"];
            }else{
                return $params = [
                    $this->getStatus(503, "key session")
                ];
            }

            if (array_key_exists('hash', $method_params)) {
                $hash = $method_params["hash"];
            }else{
                return $params = [
                    $this->getStatus(503, "key hash")
                ];
            }

            $hash_params = [
                $channel,
                $username,
                $token
            ];

            $player_id = $this->CI->common_token->getPlayerIdByToken($token);
            $playerName = $this->CI->player_model->getPlayerUsername($player_id);
            $gameUsername= $this->getGameUsernameByPlayerUsername($playerName["username"]);

            if(is_null($player_id))
            {
                return $params = [
                    $this->getStatus(400)
                ];
            }

            if($this->channel != $channel)
            {
                return $params = [
                    $this->getStatus(503, "channel")
                ];
            }

            if($gameUsername != $username)
            {
                return $params = [
                    $this->getStatus(503, "username")
                ];
            }

            if($this->md5Hash($hash_params) != $hash)
            {
                return $params = [
                    $this->getStatus(500)
                ];
            }

            $params = array(
                'channel' => $channel,
                'username' => $gameUsername,
                'currency' => $this->currency,
                'code' => self::STATUS_CODE["SUCCESS"]
            );

        }else{
            return $params = [
                $this->getStatus(503, "method")
            ];
        }

        return $params;
    }

    public function getPlayerCurrency($username)
    {
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($username);
		if(!is_null($playerId)){
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
			if(!is_null($currencyCode)){
				return $currencyCode;
			}else{
				return $this->currency;
			}
		}else{
			return $this->currency;
		}
	}

    public function md5Hash($param)
    {
        $buff = '';
        ksort($param);

        foreach ($param as $k => $v) {
            $buff .= $k . "=>" . $v . "&";
        }

        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }

        $string = $reqPar."&privatekey=>".$this->private_key;

        $md5 = md5($string);

        return $md5;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

        $sign = [
            "action" => self::ACTION_CREATE_MEMBER,
            "channel" => $this->channel,
            "nickname" => $gameUsername,
            "password" => $password,
            "userid" => $playerId,
            "username" => $gameUsername
        ];

        $params = array(
            "action" => self::ACTION_CREATE_MEMBER,
            "userid" => $playerId,
            "username" => $gameUsername,
            "nickname" => $gameUsername,
            "password" => $password,
            "channel" => $this->channel,
            "sign" => $this->md5Hash($sign)
        );

		return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success
        );

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function queryForwardGame($playerName, $extra = null)
    {
        $this->CI->load->model(array('player_model'));

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);

        $isPlayerExist = $this->isPlayerExist($playerName);

        if(!$isPlayerExist){
            $this->CI->utils->debug_log('KGAME', "NOT YET EXISITNG CREATE PLAYER");
            $this->createPlayer($playerName, $playerId, $password); // create player if not existing upon game launch
        }

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $sign = [
            "action" => self::ACTION_LOGIN,
            "channel" => $this->channel,
            "gamecode" => "",
            "language" => $this->language,
            "linecode" => "",
            "loadbg" => "",
            "nickname" => $gameUsername,
            "password" => $password,
            "tableid" => "",
            "username" => $gameUsername,
        ];

        $params = array(
            "action" => self::ACTION_LOGIN,
            "username" => $gameUsername,
            "nickname" => $gameUsername,
            "password" => $password,
            "channel" => $this->channel,
            "gamecode"=> "",
            "tableid"=> "",
            "loadbg"=> "",
            "language"=> $this->getLauncherLanguage($this->language),
            "linecode"=> "",
            "sign"=> $this->md5Hash($sign),
        );

		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "msg" => $resultArr['msg']
        );

        if($success){
            $result['url'] = $resultArr['result'];
        }

        return array($success, $result);

        // return array("success" => true, "url" => $result['url']);
	}

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 'en':
            case 'en_us':
            case 'en-us':
            case 'en-US':
                $lang = 'en'; // english
                break;
            case 'cn':
            case 'zh_cn':
            case 'zh-cn':
            case 'zh-CN':
                $lang = 'zh_cn'; // chinese
                break;
            case 'vi-vn':
            case 'vi-VN':
                $lang = 'vi'; // vietnamese
                break;
            case 'th':
                $lang = 'th'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case self::STATUS_CODE["USER_NOT_FOUND"]:
                $reasonCode = self::REASON_ACCOUNT_NOT_EXIST;
                break;
            case self::STATUS_CODE["BALANCE_NOT_ENOUGH"]:
                $reasonCode = self::REASON_NO_ENOUGH_BALANCE;
                break;
            case self::STATUS_CODE["BALANCE_OVER_LIMIT"]:
                $reasonCode = self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
            case self::STATUS_CODE["TRANSACTION_TIMEOUT"]:
                $reasonCode = self::REASON_SESSION_TIMEOUT;
            case self::STATUS_CODE["TRANSACTION_BALANCE_INSUFFICIENT"]:
                $reasonCode = self::REASON_INSUFFICIENT_AMOUNT;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, '');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $sign = [
            "action" => self::ACTION_DEPOSIT,
            "amount" => $amount,
            "channel" => $this->channel,
            "orderno" => $transfer_secure_id,
            "username" => $gameUsername
        ];

        $params = array(
            "action" => self::ACTION_DEPOSIT,
            "username" => $gameUsername,
            "channel" => $this->channel,
            "amount" => $amount,
            "orderno"=> $transfer_secure_id,
            "sign"=> $this->md5Hash($sign),
        );

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

    public function processResultForDepositToGame($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success
        );

		if(!$success){
            $status = $resultArr['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }else{
            $result['msg'] = $resultArr['msg'];
            $result['balance'] = $resultArr['balance'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, '');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => -1 * $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $sign = [
            "action" => self::ACTION_WITHDRAW,
            "amount" => $amount,
            "channel" => $this->channel,
            "orderno" => $transfer_secure_id,
            "username" => $gameUsername
        ];

        $params = array(
            "action" => self::ACTION_WITHDRAW,
            "username" => $gameUsername,
            "channel" => $this->channel,
            "amount" => $amount,
            "orderno"=> $transfer_secure_id,
            "sign"=> $this->md5Hash($sign),
        );

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
        );

		if(!$success){
            $status = $resultArr['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }else{
            $result['msg'] = $resultArr['msg'];
            $result['balance'] = $resultArr['balance'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }

        return array($success, $result);
	}

    public function queryPlayerBalance($playerName)
    {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $sign = [
            "action" => self::ACTION_BALANCE,
            "channel" => $this->channel,
            "username" => $gameUsername
        ];

        $params = array(
            "action" => self::ACTION_BALANCE,
            "username" => $gameUsername,
            "channel" => $this->channel,
            "sign"=> $this->md5Hash($sign)
        );

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params)
    {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "msg" => $resultArr['msg'],
        );

		if($success){
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']));
		}

		return array($success, $result);
	}

    public function queryTransaction($transactionId, $extra)
    {
        $playerName = $extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

        $sign = [
            "action" => self::ACTION_GETTRANSINFO,
            "channel" => $this->channel,
            "orderno" => $transactionId,
            "username" => $gameUsername
        ];

        $params = array(
            "action" => self::ACTION_GETTRANSINFO,
            "channel" => $this->channel,
            "username" => $gameUsername,
            "orderno" => $transactionId,
            "sign" => $this->md5Hash($sign)
        );

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

    public function processResultForQueryTransaction($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $params['params']['orderno']
		);

		if($success){
            $result['reason_id'] = self::REASON_UNKNOWN;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

    public function updatePlayerInfo($playerName, $infos)
    {
        return $this->returnUnimplemented();
	}

    public function isPlayerOnline($playerName)
    {
        return $this->returnUnimplemented();
    }

    public function getListByTime($start_date, $end_date)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processGetListByTime'
        );

        $startdatetime = $start_date->format('Y-m-d H:i:s');
        $enddatetime = $end_date->format('Y-m-d H:i:s');

        $page = self::START_PAGE;
        $done = false;

        while (!$done){
            $sign = [
                "begintime" => $startdatetime,
                "channel" => $this->channel,
                "endtime" => $enddatetime,
                "gameid" => "",
                "page" => $page
            ];

            $params = array(
                "channel" => $this->channel,
                "gameid" => "",
                "begintime" => $startdatetime,
                "endtime" => $enddatetime,
                "page" => $page,
                "sign" => $this->md5Hash($sign)
            );


            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

            if (isset($api_result) && $api_result['success']){

                $recnum = $api_result['result']['data_count']; // total result in page

                if($recnum < $this->max_record){
                    $done = true;
                } else {
                    $done = false;
                    $page += 1;
                }

            }else{
                $done = true;
            }

            if($done){
                $success = true;
            }

            sleep(10);
        }

        return array( 'success' => $success);
		//return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processGetListByTime($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $data = !empty($resultArr['data']) ? $resultArr['data']:[];

        $result = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        ];

        if($success && !empty($data))
        {
            $extra["response_result_id"] = $responseResultId;
            $gameRecords = $this->rebuildGameRecords($data, $extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $result['data_count'] += is_array($gameRecords) ? count($gameRecords) : 0;

            if (!empty($insertRows))
            {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);

        }

        $dataResult = [
            "result" => $result
        ];

        return array($success, $dataResult);
	}

    public function syncOriginalGameLogs($token)
    {
        $syncId = parent::getValueFromSyncInfo($token, 'syncId');

        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTimeModified = $dateTimeFrom->modify($this->getDatetimeAdjust());

        // $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($startDateTimeModified->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDate: " . $dateTimeFrom->format('Y-m-d H:i:s'), "endDate: " . $dateTimeTo->format('Y-m-d H:i:s'));

        $result = [
            "success" => true,
            "data_count" => 0,
            "data_count_insert" => 0,
            "data_count_update" => 0
        ];

        $resultLastbets = $this->getListByTime($dateTimeFrom, $dateTimeTo);

        if(!empty($resultLastbets["data"]))
        {
            $result['data_count'] += $resultLastbets["result"]["data_count"];
            $result['data_count_insert'] += $resultLastbets["result"]["data_count_insert"];
            $result['data_count_update'] += $resultLastbets["result"]["data_count_update"];
        }else{
            $result['data_count'] += 0;
            $result['data_count_insert'] += 0;
            $result['data_count_update'] += 0;
        }

        $dateTimeFrom->modify('+1 minutes');


        return $result;
    }

    public function rebuildGameRecords($gameRecords, $extra)
    {
        foreach($gameRecords as $key => $gameRecord)
        {

            $data['betId'] = isset($gameRecord['id']) ? $gameRecord['id'] : NULL;
            $data['username'] = isset($gameRecord['username']) ? $gameRecord['username'] : NULL;
            $data['channel'] = isset($gameRecord['channel']) ? $gameRecord['channel'] : NULL;
            $data['bet_time'] = isset($gameRecord['bet_time']) ? $gameRecord['bet_time'] : NULL;
            $data['game_type'] = isset($gameRecord['game_type']) ? $gameRecord['game_type'] : NULL;
            $data['game_id'] = isset($gameRecord['game_id']) ? $gameRecord['game_id'] : NULL;
            $data['game_code'] = isset($gameRecord['game_code']) ? $gameRecord['game_code'] : NULL;
            $data['tableno'] = isset($gameRecord['tableno']) ? $gameRecord['tableno'] : NULL;
            $data['termno'] = isset($gameRecord['termno']) ? $gameRecord['termno'] : NULL;
            $data['bet_item_name'] = isset($gameRecord['bet_item_name']) ? $gameRecord['bet_item_name'] : NULL;
            $data['bet_money'] = isset($gameRecord['bet_money']) ? $gameRecord['bet_money'] : NULL;
            $data['bbef_balance'] = isset($gameRecord['bbef_balance']) ? $gameRecord['bbef_balance'] : NULL;
            $data['lw_money'] = isset($gameRecord['lw_money']) ? $gameRecord['lw_money'] : NULL;
            $data['tax'] = isset($gameRecord['tax']) ? $gameRecord['tax'] : NULL;
            $data['valid_bet'] = isset($gameRecord['valid_bet']) ? $gameRecord['valid_bet'] : NULL;
            $data['send_prize'] = isset($gameRecord['send_prize']) ? $gameRecord['send_prize'] : NULL;

            $data['external_uniqueid'] = isset($gameRecord['id']) ? $gameRecord['id'] : NULL;
            $data['response_result_id'] = $extra["response_result_id"];
            $dataRecords[] = $data;
        }

        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount = 0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $game_logs_table = $this->originalTable;
        $game_logs_table_as = "kgame";

        $sqlTime="{$game_logs_table_as}.updated_at >= ? and {$game_logs_table_as}.updated_at <= ?";

        if($use_bet_time){
            $sqlTime="{$game_logs_table_as}.bet_time >= ? and {$game_logs_table_as}.bet_time <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table_as}.betId,
    {$game_logs_table_as}.username,
    {$game_logs_table_as}.bet_time as start_at,
    {$game_logs_table_as}.bet_time as bet_at,
    {$game_logs_table_as}.bet_time as end_at,
    {$game_logs_table_as}.channel,
    {$game_logs_table_as}.game_id,
    {$game_logs_table_as}.tableno,
    {$game_logs_table_as}.termno,
    {$game_logs_table_as}.bet_item_name,
    {$game_logs_table_as}.bet_money as bet_amount,
    {$game_logs_table_as}.bbef_balance,
    {$game_logs_table_as}.lw_money as result_amount,
    {$game_logs_table_as}.tax,
    {$game_logs_table_as}.valid_bet,
    {$game_logs_table_as}.send_prize,

    {$game_logs_table_as}.external_uniqueid,
    {$game_logs_table_as}.response_result_id,
    {$game_logs_table_as}.created_at,
    {$game_logs_table_as}.updated_at,
    {$game_logs_table_as}.md5_sum,
    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,
    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id,
    game_description.game_code

FROM

    {$game_logs_table} as {$game_logs_table_as}
    LEFT JOIN game_description ON {$game_logs_table_as}.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON {$game_logs_table_as}.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        if(isset($row['end_at']))
        {
            if($row['end_at'] == "0000-00-00 00:00:00")
            {
                $end_at = $row['start_at'];
            }else{
                $end_at = $row['end_at'];
            }
        }else{
            $end_at = $row['start_at'];
        }

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ?  $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ?  $this->gameAmountToDBTruncateNumber($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ?  $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['bet_amount']) ?  $this->gameAmountToDBTruncateNumber($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => null
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at'                => $end_at,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [
                'note' => "",
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row["status"] = Game_logs::STATUS_SETTLED;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
            $game_description_id = $gameDescId;
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function isPlayerExist($playerName)
    {
        $playerId = $this->getPlayerIdFromUsername($playerName);

        $this->CI->load->model(array('game_provider_auth'));

        $exist = $this->CI->game_provider_auth->isRegisterd($playerId, $this->getPlatformCode());

        return $exist;
	}

	public function queryPlayerInfo($playerName)
    {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword)
    {
		return $this->returnUnimplemented();
	}

}

/*end of file*/