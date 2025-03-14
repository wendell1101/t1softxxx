<?php

/**
 * BBGame integration
 * OGP-25614
 *
 * @author  Kristallynn Tolentino
 *
 * Conversion rate VND 1:1
 * For game logs needs to be divided by 10,000
 */

require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
// require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt.php';

class game_api_bbgame extends Abstract_game_api {

    const START_PAGE = 1;

    const METHOD = [
        "POST" => "POST",
        "GET" => "GET",
        "PUT" => "PUT"
    ];

    const RETURN_CODE = [
        "SUCCESS" => 1,
        "MERCHANT_ERROR" => -1,
        "PARAMETER_ERROR" => -2,
        "REGISTRATION_ERROR" => -3,
        "OPERATION_FAILED" => -4,
        "LOGIN_FAILED" => -6,
        "EXCEPTION" => -7,
        "MEMBER_DOES_NOT_EXIST" => -8,
        "NOT_ON_THE_CREDIT_LIST" => -9,
        "USERNAME_NOT_PASSED_RULES" => -10,
        "INVALID_USERNAME" => -11,
        "DECRYPTION_FAILED" => -12,
    ];

    const URI_MAP = [
        self::API_createPlayer => "/api/login/loginOrRegister",
        self::API_login => "/api/login/loginOrRegister",
        self::API_queryPlayerBalance => "/api/finance/balance",
        self::API_depositToGame => "/api/userCore/exchangeMoney",
        self::API_withdrawFromGame => "/api/userCore/exchangeMoney",
        self::API_confirmTransaction => "/api/finance/getOrderStatus",
        self::API_queryTransaction => "/api/finance/getOrderStatus",
        self::API_syncGameRecords => "/api/values/betDetail",
    ];

    const API_METHOD_AUTH_SESS = "auth_sess";

    const CHECK_TRANSFER_STATUS = [
        0 => "unprocessed",
        1 => "already processed"
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        "account",
        "revenue",
        "actionType",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        "betFatAmount",
        "betAmount",
        "initBetAmount",
        "beforeAmount",
        "afterAmount",
        "tax",
        "validAmount",
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
        'note',
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
        $this->style = $this->getSystemInfo('style', 3);
        $this->currencyType = $this->getSystemInfo('currencyType', 3); //1 RMB | 2 Dollar | 3 Vietnamese Dong
        $this->language = $this->getSystemInfo('language', 3); // 0 Simplified Chinese | 1 Traditional Chinese | 2 English | 3 Vietnamese
        $this->collection_api = $this->getSystemInfo('collection_api', "https://caiji.livecdn66.com");
        $this->merchant_key = $this->getSystemInfo('kye', "7A5F6197BBF25DA71479E8F8678A9F3F030883AB77EE65D5");
        $this->public_key = $this->getSystemInfo('public_key', "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmpYbVyBmuA+Rx0F1dhscOCIvJM2XQMhaCeWqGMedsEWRrEiRz230kij2Czp8dMtsmPhEte1QwAdUyYFzxqgXjnx1WrrAELpnaV5P8FFFVlT1xB3ARLnKkIvcAjLszwnCBsBtqEwMorwdrgYrtmxZhK8POr/2wZG9sQFgcTGC2NX/CUpk/ClL4icHPi+PTMrCz3Kv+Zq0d96NdflQ5JuFo1rdlv1KNOPkZWu5mPeksTNRrtafDYXLiQ0OvNdy9m/cGZoSTX1wHOjW5HB5D8D3E1ihJe5nC+avf9KwbCxt7eVnPmnaIRXiIoavWfp1OTUioeoCelQjdHZ+g3ZzRW3ZTwIDAQAB");

        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->is_redirect = $this->getSystemInfo('is_redirect',true);
        $this->enabled_convert_transaction_amount=$this->getSystemInfo('enabled_convert_transaction_amount',false);

        $this->originalTable = 'bbgame_game_logs';

        # init RSA
		$this->rsa = new Crypt_RSA();
        $this->rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
    }

    public function getPlatformCode()
    {
        return BBGAME_API;
    }

    public function generateUrl($apiName, $params)
    {
        $uri = self::URI_MAP[$apiName];

        $function = $params["actions"]['function'];

        if($function == self::API_depositToGame || $function == self::API_withdrawFromGame){
            $url = $this->api_url . $uri;
        }else if($function == self::API_syncGameRecords){
            $url = $this->collection_api . $uri;
        }else{
            $url = $this->api_url . $uri. '?' . http_build_query($params["main_params"]);
        }

        return $url;
	}

    protected function customHttpCall($ch, $params)
    {
        if($params["actions"]["method"] == self::METHOD["POST"])
        {
            $function = $params["actions"]['function'];

            unset($params["actions"]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);

            if($function == self::API_depositToGame || $function == self::API_withdrawFromGame){
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($params["main_params"]));
            }else{
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));
            }
        }
    }

    public function processResultBoolean($responseResultId, $resultArr)
    {
		$success = false;

        if(isset($resultArr["code"]) && $resultArr["code"] == self::RETURN_CODE['SUCCESS']){
            $success = true;
        }

        if(isset($resultArr["ErrorCode"]) && $resultArr["ErrorCode"] == self::RETURN_CODE['SUCCESS']){
            $success = true;
        }

		if (!$success)
        {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('BBGAME API got error ', $responseResultId, 'result', $resultArr);
		}

		return $success;
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

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        //NOTE: Username format:  style_username

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

        $main_params = [
            "style" => $this->style,
            "userName" => $this->style.'_'.$gameUsername,
            "gameType" => 0,
            "userNick" => $gameUsername,
            "currencyType" => $this->currencyType,
            "language" => $this->language,
            "noLobby"=> 0
        ];

        $params = array(
            "main_params" => $main_params ,
            "actions" => [
                "function" => self::API_createPlayer,
                "method" => self::METHOD["GET"]
            ]
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
            "success" => $success,
            "code" => $resultArr['code'],
            "msg" => isset($resultArr['msg']) ? $resultArr['msg'] : ""
        );

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function queryForwardGame($playerName, $extra = null)
    {
        $result = $this->login($playerName);

        if($result["code"] == self::RETURN_CODE["SUCCESS"]){
            $url = $result["url"];
        }

        return array("success" => true, "url" => $url);
    }

    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en":
                $language = 'en';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh":
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $language = 'vi';
            default:
                $language = 'en';
                break;
        }
        return $language;
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

        $main_params = [
            "style" => $this->style,
            "userName" => $this->style."_".$gameUsername
        ];

        $params = array(
            "main_params" => $main_params ,
            "actions" => [
                "function" => self::API_queryPlayerBalance,
                "method" => self::METHOD["GET"]
            ]
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
        );

		if($success){
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']['availableGold']));
		}else{
            $result['code'] = $resultArr['code'];
            $result['msg'] = $resultArr['msg'];
        }

		return array($success, $result);
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

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $main_params = [
            "money" => $this->dBtoGameAmount($amount),
            "orderId" => $transfer_secure_id,
            "type" => 1,
            "style" => $this->style,
            "userName" => $this->style."_".$gameUsername
        ];

        // $this->jwt = JWT::encode($main_params, JWT::urlsafeB64Decode($this->merchant_key), 'HS512');

        $this->rsa->loadKey($this->public_key);
        $ciphertext = $this->rsa->encrypt(json_encode($main_params));

		$params = array(
			"main_params" => $ciphertext,
            "actions" => [
                "function" => self::API_depositToGame,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

    public function processResultForDepositToGame($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
        	$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, '');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $main_params = [
            "money" => $this->dBtoGameAmount($amount),
            "orderId" => $transfer_secure_id,
            "type" => 2,
            "style" => $this->style,
            "userName" => $this->style."_".$gameUsername
        ];

        // $this->jwt = JWT::encode($main_params, JWT::urlsafeB64Decode($this->merchant_key), 'HS512');

        $this->rsa->loadKey($this->public_key);
        $ciphertext = $this->rsa->encrypt(json_encode($main_params));

		$params = array(
			"main_params" => $ciphertext,
            "actions" => [
                "function" => self::API_withdrawFromGame,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
        	$result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

        return array($success, $result);
	}

    public function getCheckTransferStatus($status)
    {
        foreach (self::CHECK_TRANSFER_STATUS as $key => $value) {
            if($key == $status)
            {
                return $value;
                break;
            }
        }
    }

    public function queryTransaction($transactionId, $extra)
    {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

        $main_params = [
            "style" => $this->style,
            "userName" => $this->style."_".$gameUsername,
            "orderId" => $transactionId
        ];

		$params = array(
			"main_params" => $main_params,
            "extra" => $extra,
            "actions" => [
                "function" => self::API_queryTransaction,
                "method" => self::METHOD["GET"]
            ]
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

    public function processResultForQueryTransaction($params)
    {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN,
			'external_transaction_id'=>$external_transaction_id
		);

		if($success){
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

    public function getLastbets($start_date, $end_date)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetLastbets'
        );

        $startdatetime = $start_date->format('Y-m-d H:i:s');
        $enddatetime = $end_date->format('Y-m-d H:i:s');

        $page_index = self::START_PAGE;
        $page_size = 200;//max value
        $done = false;

        while ($done == false){

            $main_params = array(
                "style" => $this->style,
                "pageIndex" => $page_index,
                "pageSize" => 200,
                "startTime" => $startdatetime,
                "endTime" => $enddatetime,
                // "gameType" => $game_code
            );

            $params = array(
                "main_params" => $main_params,
                "actions" => [
                    "function" => self::API_syncGameRecords,
                    "method" => self::METHOD["POST"]
                ]
            );

            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);


            if (isset($api_result) && $api_result['success']){

                if(!empty($api_result["result"]["data_result"]) && $api_result["result"]["data_result"] != ""){

                    $bet_size = ($page_index*$page_size)+1;

                    if($bet_size <= $api_result["result"]["count_result"]){
                        $page_index += 1;
                        $this->CI->utils->debug_log('BBGAME page index', $page_index);
                    }else{
                        $this->CI->utils->debug_log('BBGAME bet size is greater than');
                        $done = true;
                    }
                }else{
                    $done = true;
                    $this->CI->utils->debug_log('BBGAME api data empty');
                }
            }else{
                $done = true;
            }

            if($done){
                $success = true;
                $this->CI->utils->debug_log('BBGAME while loop done');
            }
        }

        return array( 'success' => $success);
		//return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForGetLastbets($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $data = !empty($resultArr['Data']) ? $resultArr['Data'] : [];

        $result = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
            'count_result' => isset($resultArr['Count']) ? $resultArr['Count'] : 0,
            'data_result' => $resultArr['Data'],
            "success" => $success
        ];

        if($success && !empty($data))
        {
            $extra["response_result_id"] = $responseResultId;
            $extra["count_result"] = $resultArr['Count'];
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

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
        $dateTimeTo = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDate: " . $dateTimeFrom->format('Y-m-d H:i:s'), "endDate: " . $dateTimeTo->format('Y-m-d H:i:s'));

        $result = [
            "success" => true,
            "data_count" => 0,
            "data_count_insert" => 0,
            "data_count_update" => 0
        ];

        $resultLastbets = $this->getLastbets($dateTimeFrom, $dateTimeTo);

        $this->CI->utils->debug_log('BBGAME resultLastbets', $resultLastbets);

        if(!empty($resultLastbets["result"]["data_count"]))
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
            $data['betId'] = isset($gameRecord['_id']) ? $gameRecord['_id'] : NULL;
            $data['company'] = isset($gameRecord['company']) ? $gameRecord['company'] : NULL;
            $data['roundId'] = isset($gameRecord['roundId']) ? $gameRecord['roundId'] : NULL;
            $data['account'] = isset($gameRecord['account']) ? $gameRecord['account'] : NULL;
            $data['orderCreateTime'] = isset($gameRecord['orderCreateTime']) ? $this->gameTimeToServerTime($gameRecord['orderCreateTime']) : NULL;
            $data['gameType'] = isset($gameRecord['gameType']) ? $gameRecord['gameType'] : NULL;
            $data['revenue'] = isset($gameRecord['revenue']) ? $gameRecord['revenue'] : 0;
            $data['actionType'] = isset($gameRecord['actionType']) ? $gameRecord['actionType'] : NULL;
            $data['betFatAmount'] = isset($gameRecord['betFatAmount']) ? $gameRecord['betFatAmount'] : 0;
            $data['betAmount'] = isset($gameRecord['betAmount']) ? $gameRecord['betAmount'] : 0;
            $data['initBetAmount'] = isset($gameRecord['initBetAmount']) ? $gameRecord['initBetAmount'] : 0;
            $data['beforeAmount'] = isset($gameRecord['beforAmount']) ? $gameRecord['beforAmount'] : 0;
            $data['afterAmount'] = isset($gameRecord['afterAmount']) ? $gameRecord['afterAmount'] : 0;
            $data['tax'] = isset($gameRecord['tax']) ? $gameRecord['tax'] : 0;
            $data['code'] = isset($gameRecord['code']) ? $gameRecord['code'] : NULL;
            $data['desc'] = isset($gameRecord['desc']) ? $gameRecord['desc'] : NULL;
            $data['validAmount'] = isset($gameRecord['validAmount']) ? $gameRecord['validAmount'] : 0;

            $data['response_result_id'] = $extra["response_result_id"];
            $data['external_uniqueid'] = isset($gameRecord['_id']) ? $gameRecord['_id'] : NULL;

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
        $game_logs_table = $this->originalTable;
        $game_logs_table_as = "bbgame";

        $sqlTime="{$game_logs_table_as}.created_at >= ? and {$game_logs_table_as}.created_at <= ?";

        if($use_bet_time){
            $sqlTime="{$game_logs_table_as}.orderCreateTime >= ? and {$game_logs_table_as}.orderCreateTime <= ?";
        }

        $remove_format = $this->style."_";
        $sql = <<<EOD
SELECT
{$game_logs_table_as}.id as sync_index,

{$game_logs_table_as}.betId,
{$game_logs_table_as}.company,
{$game_logs_table_as}.roundId,
{$game_logs_table_as}.account,
REPLACE({$game_logs_table_as}.account, "{$remove_format}", "") as username,
{$game_logs_table_as}.orderCreateTime as start_at,
{$game_logs_table_as}.orderCreateTime as bet_at,
{$game_logs_table_as}.orderCreateTime as end_at,
{$game_logs_table_as}.gameType,
{$game_logs_table_as}.revenue,
{$game_logs_table_as}.actionType,
{$game_logs_table_as}.betFatAmount as result_amount,
{$game_logs_table_as}.betAmount,
{$game_logs_table_as}.initBetAmount,
{$game_logs_table_as}.beforeAmount,
{$game_logs_table_as}.afterAmount,
{$game_logs_table_as}.tax,
{$game_logs_table_as}.code,
{$game_logs_table_as}.desc,
{$game_logs_table_as}.validAmount as bet_amount,

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
game_description.game_code,
game_description.english_name as game
FROM
{$game_logs_table} as {$game_logs_table_as}
LEFT JOIN game_description ON {$game_logs_table_as}.gameType = game_description.external_game_id AND game_description.game_platform_id = ?
LEFT JOIN game_type ON game_description.game_type_id = game_type.id
JOIN game_provider_auth ON REPLACE({$game_logs_table_as}.account, "{$remove_format}", "") = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
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

        $this->CI->utils->debug_log('BBGAMEmerge', $row);

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
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
                'bet_amount'            => isset($row['bet_amount']) ?  $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ?  $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ?  $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0,
                'real_betting_amount'   => (isset($row['bet_amount']) && $row['bet_amount'] != 0) ?  $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => 0,
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : null,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['roundId']) ? $row['roundId'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [

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
                $unknownGame->game_type_id, $row['gameType'], $row['gameType']);
            $game_description_id = $gameDescId;
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function convertTransactionAmount($amount){
		if($this->enabled_convert_transaction_amount){
	    	$decimals = intval($this->getSystemInfo('transaction_amount_precision', 2));
			$power = pow(10, $decimals);
			if($amount > 0){
				return round(floor($amount * $power) / $power, $decimals);
			} else {
				return round(ceil($amount * $power) / $power, $decimals);
			}
    	}
    	return $amount;
    }

    public function login($playerName, $password = null, $extra = null)
    {
        //NOTE: Username format:  style_username

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $main_params = [
            "style" => $this->style,
            "userName" => $this->style.'_'.$gameUsername,
            "gameType" => (isset($extra["gameType"])) ? $extra["gameType"] : 0,
            "userNick" => $gameUsername,
            "currencyType" => $this->currencyType,
            "language" => $this->language,
            "noLobby"=> 0
        ];

        $params = array(
            "main_params" => $main_params ,
            "actions" => [
                "function" => self::API_login,
                "method" => self::METHOD["GET"]
            ]
        );

		return $this->callApi(self::API_login, $params, $context);
	}

    public function processResultForLogin($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "code" => $resultArr['code'],
            "msg" => $resultArr['msg'],
            "url" => $resultArr['url']
        );

        return array($success, $result);
	}

    public function getGameDescriptionByGamePlatformId($game_platform_id)
    {
        $this->CI->load->model(array('game_description_model'));
        $games = $this->CI->game_description_model->getGameDescriptionByGamePlatformId($game_platform_id);

        return $games;
    }

}
/*end of file*/