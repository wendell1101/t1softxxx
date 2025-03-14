<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class game_api_sgwin extends Abstract_game_api {

    const START_PAGE = 1;

    const METHOD = [
        "POST" => "POST",
        "GET" => "GET",
        "PUT" => "PUT"
    ];

    const URI_MAP = [
        self::API_createPlayer => "/api/login", 
        self::API_queryPlayerBalance => "/api/account", 
        self::API_depositToGame => "/api/transaction", 
        self::API_withdrawFromGame => "/api/transaction", 
        self::API_confirmTransaction => "/api/confirm", 
        self::API_login => "/api/login", 
        self::API_queryTransaction => "/api/trans",
        self::API_syncGameRecords => "/api/bets",
    ];

    const API_METHOD_AUTH_SESS = "auth_sess";

    const CHECK_TRANSFER_STATUS = [
        0 => "unprocessed",
        1 => "already processed"
    ];
    
    const STATUS_CODE = [
        "INNER" => 'E0001.inner',
        "INNER_MSG" => 'E0001.inner.msg',
        "USER_NOT_FOUND" => 'E0003.user.not.found',
        "BALANCE_NOT_ENOUGH" => 'E0004.balance.not.enough',
        "BALANCE_OVER_LIMIT" => 'E0005.balance.over.limit.100000000',
        "TRANSACTION_TIMEOUT" => 'E0006.transaction.timeout',
        "TRANSACTION_BALANCE_INSUFFICIENT" => 'E0008.transaction.balance.insufficient',
        "SYSTEM_ERROR" => "E0009",
        "SAVE_FAIL" => "E0010.save.fail",
        "NOT_AUTHORIZED" => "E0013",
        "COMPANY_DISABLED" => "E0014",
        "VALIDATE_USERNAME" => 'E0015.validate.username',
        "LOGIN_ERROR_STATUS" => 'E0015.login.error.status.5',
        "VALIDATE_USERNAME" => 'E0015.validate.username',
        "VALIDATE_ALPHANUMERIC" => 'E0016.validate.AlphanumericAnd3Chars',
        "MEMBER_OFFLINE" => 'E0016.member.offline',
        "INVALID_LOTTERY" => 'E0017',
        "VALIDATE_USERNAME_LENGTH" => 'E0018.validate.username.length',
        "VALIDATE_USERNAME_LENGTH_MAX" => 'E0019.validate.username.length.max.32',
        "VALIDATE_ACCOUNT_EXIST" => 'E0020.validate.account.exist',
        "SAVE_UNSUCCESSFUL" => 'E0021.save.unsuccessful',
        "VALID_USERS_RANGE" => 'E0022.validate.users.range',
        "VALIDATE_ACCOUNT_USERS_RANGE" => 'E0025'
    ];

    const GAME_LOGS_STATUS = [
        "NORMAL_SETTLEMENT" => 0,
        "CANCELLED" => 1,
        "NO_SCORE_CANCELLED" => 2
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        "username",
        "amount",
        "channel",
        "settled",
        "status",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'dividend',
        'realAmount',
        'totalAmount',
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
        $this->root 	= $this->getSystemInfo('root', 'rt008');
		$this->agent_id 	= $this->getSystemInfo('agent_id', 'zfrt013test');
		$this->api_key 	= $this->getSystemInfo('api_key', 'V4P3RHrwwBwUNYD4MN55CK87S3SQBR');
        $this->member_login_access = $this->getSystemInfo('member_login_address', "https://demo3.sgwin123.com");
        $this->backButton = $this->getSystemInfo('backButton', false);
        $this->backURL = $this->getSystemInfo('backURL', "");
        $this->is_redirect = $this->getSystemInfo('is_redirect',true);
        
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->secret_key = $this->getSystemInfo('secret_key');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language');
        $this->playerTable = "player";
        $this->originalTable = 'sgwin_game_logs';
        $this->ftpPath = $this->getSystemInfo('ftp_game_record_path');
    }
	
    public function getPlatformCode()
    {
        return SGWIN_API;
    }

    public function generateUrl($apiName, $params) 
    {
        $uri = self::URI_MAP[$apiName];

        $url = $this->api_url . $uri. '?' . http_build_query($params["main_params"]);
        
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

            if(isset($params["json_body"])){
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["json_body"]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }else{
                if($function == self::API_queryPlayerBalance){
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                }else{
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params["main_params"]));
                }
            }
            
        }
    }

    public function processResultBoolean($responseResultId, $resultArr) 
    {
		$success = false;

        if($resultArr["success"] == true){
            $success = true;
        }
		
		if (!$success) 
        {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('SGWIN API got error ', $responseResultId, 'result', $resultArr);
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
            if (array_key_exists('agentID', $method_params)) {
                $agentID = $method_params["agentID"];
            }else{
                return $params = [
                    $this->getStatus(503, "key agentID")
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
                $agentID,
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
                'username' => $gameUsername,
                'currency' => $this->currency,
                'agentID' => $this->agent_id,
                'referral' => '',
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
        $query_params = http_build_query($param);

        return md5($query_params."&".$this->api_key);
    }

    public function timeStamp()
    {
        return str_replace('+00:00', '', gmdate('c'));
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

        $json_body = array(
            "defaultBgColor" => "white",
            "defaultColor" => "blue",
            "defaultGame" => "",
            "backButton" => $this->backButton,
            "backUrl" => $this->backURL,
            "range" => "",
            "editRange" => false,
            "tesing" => 0
        );

        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

        $params = array(
            "main_params" => $main_params ,
            "json_body" => $json_body,
            "actions" => [
                "function" => self::API_createPlayer, 
                "method" => self::METHOD["POST"]
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
            "success" => $success
        );
        
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function queryForwardGame($playerName, $extra = null) 
    {
        $result = $this->login($playerName);
        $getTokenResult = "";

        if(isset($result["session"])){
            $getTokenResult = $result["session"];
        }
        
        $params = [
            "_OLID_" => $getTokenResult
        ];

        if ($this->utils->is_mobile()) {

			$url = $this->member_login_access . '/mobile/member/index?'. http_build_query($params);

		}else{
            $url = $this->member_login_access . '/member/index?'. http_build_query($params);
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

        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

		$params = array(
			'main_params' => $main_params,
            "actions" => [
                "function" => self::API_queryPlayerBalance, 
                "method" => self::METHOD["POST"]
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
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['result']['balance']));
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
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $json_body = array(
            "amount" => $amount,
            "force" => true,
            "range" => "",
            "editRange" => false,
            "tesing" => 0,
            "unique" => $transfer_secure_id
        );


        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

		$params = array(
			"main_params" => $main_params,
            "json_body" => $json_body,
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
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $statusCode = $this->getStatusCodeFromParams($params);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
        );

		if($success){	
			$this->confirmTransaction($playerName, $resultArr["result"]["id"]);
        }else{

            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $success=true;
            }else{
                $status = $resultArr['code'];
                $result['reason_id'] = $this->getTransferErrorReasonCode($status);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
            
        }

        return array($success, $result);
	}

    public function confirmTransaction($playerName, $transactionId){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName
        );

        $json_body = array(
            "id" => $transactionId
        );

        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

		$params = array(
			"main_params" => $main_params,
            "json_body" => $json_body,
            "actions" => [
                "function" => self::API_confirmTransaction, 
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_confirmTransaction, $params, $context);
    }

    public function processResultForConfirmTransaction($params) 
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
        );

		if($success){	
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $status = $resultArr['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
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

        $json_body = array(
            "amount" => -1 * $amount,
            "force" => true,
            "range" => "",
            "editRange" => false,
            "tesing" => 0,
            "unique" => $transfer_secure_id
        );


        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

		$params = array(
			"main_params" => $main_params,
            "json_body" => $json_body,
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
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
        );

		if($success){	
            $this->confirmTransaction($playerName, $resultArr["result"]["id"]);
        }else{
            $status = $resultArr['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
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
        $playerName = $extra['playerName'];
        $playerId = $extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'external_transaction_id' => $transactionId,
		);

        $json_body = array(
            "username" => $gameUsername,
            "id" => "",
            "unique" => $transactionId
        );

        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

        $params = array(
			"main_params" => $main_params,
            "json_body" => $json_body,
            "actions" => [
                "function" => self::API_queryTransaction, 
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

    public function processResultForQueryTransaction($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $ext_id = $resultArr["result"]["id"];

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$ext_id
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

    public function getLastbets($start_date)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetLastbets'
        );


        $startdatetime = $start_date->format('Y-m-d');

        $page = self::START_PAGE;
        $done = false;

        while (!$done){
            $json_body = array(
                "date" => $startdatetime,
                "lottery" => "",
                "drawNumber" => "",
                "page" => $page,
                "settledTime" => "",
                "username" => ""
            );
    
            $hash_params = array(
                "agentID" => $this->agent_id,
                "root" => $this->root
            );
    
            $main_params = [
                "agentID" => $this->agent_id,
                "root" => $this->root,
                "hash" => $this->md5Hash($hash_params)
            ];
    
            $params = array(
                "main_params" => $main_params,
                "json_body" => $json_body,
                "actions" => [
                    "function" => self::API_syncGameRecords, 
                    "method" => self::METHOD["POST"]
                ]
            );

            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

            $this->CI->utils->debug_log('api_result', $api_result);
            
            if (isset($api_result) && $api_result['success']){

                $total_page = $api_result['result']['total'];
                $done = $page >= $total_page;
                $page += 1;
            }

            if($done){
                $success = true;
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
        $data = !empty($resultArr['result']['list']) ? $resultArr['result']['list']:[];

        $result = [
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0,
             'total' => $resultArr['result']['total']
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

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom));
        $dateTimeTo = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDate: " . $dateTimeFrom->format('Y-m-d H:i:s'), "endDate: " . $dateTimeTo->format('Y-m-d H:i:s'));
        
        $result = [
            "success" => true,
            "data_count" => 0,
            "data_count_insert" => 0,
            "data_count_update" => 0
        ];
        
        $resultLastbets = $this->getLastbets($dateTimeFrom);

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
            if(isset($gameRecord['username']))
            {
                $getPlayerName = str_replace(strtolower($this->prefix_for_username), "", strtolower($gameRecord['username']));
                $username = $this->prefix_for_username.strtolower($getPlayerName);
            }else{
                $username = NULL;
            }

            $data['amount'] = isset($gameRecord['amount']) ? $this->gameAmountToDB($gameRecord['amount']) : 0;
            $data['betCode'] = isset($gameRecord['betCode']) ? $gameRecord['betCode'] : NULL;
            $data['betCount'] = isset($gameRecord['betCount']) ? $gameRecord['betCount'] : NULL;
            $data['betOdds'] = isset($gameRecord['betOdds']) ? $gameRecord['betOdds'] : NULL;
            $data['bid'] = isset($gameRecord['bid']) ? $gameRecord['bid'] : NULL;
            $data['channel'] = isset($gameRecord['channel']) ? $gameRecord['channel'] : NULL;
            $data['cm'] = isset($gameRecord['cm']) ? $gameRecord['cm'] : NULL;
            $data['content'] = isset($gameRecord['content']) ? $gameRecord['content'] : NULL;
            $data['betCreated'] = isset($gameRecord['created']) ? $gameRecord['created'] : NULL;
            $data['betDate'] = isset($gameRecord['date']) ? $gameRecord['date'] : NULL;
            $data['dividend'] = isset($gameRecord['dividend']) ? $gameRecord['dividend'] : 0;
            $data['gameCode'] = isset($gameRecord['gameCode']) ? $gameRecord['gameCode'] : NULL;
            $data['lottery'] = isset($gameRecord['lottery']) ? $gameRecord['lottery'] : NULL;
            $data['lotteryName'] = isset($gameRecord['lotteryName']) ? $gameRecord['lotteryName'] : NULL;
            $data['lotteryNumber'] = isset($gameRecord['number']) ? $gameRecord['number'] : NULL;
            $data['odds'] = isset($gameRecord['odds']) ? $gameRecord['odds'] : NULL;
            $data['realAmount'] = isset($gameRecord['realAmount']) ? $this->gameAmountToDB($gameRecord['realAmount']) : 0;
            $data['result'] = isset($gameRecord['result']) ? $gameRecord['result'] : NULL;
            $data['settled'] = isset($gameRecord['settled']) ? $gameRecord['settled'] : NULL;
            $data['settledTime'] = isset($gameRecord['settledTime']) ? $gameRecord['settledTime'] : NULL;
            $data['status'] = isset($gameRecord['status']) ? $gameRecord['status'] : NULL;
            $data['totalAmount'] = isset($gameRecord['totalAmount']) ? $this->gameAmountToDB($gameRecord['totalAmount']) : NULL;
            $data['username'] = $username;
            $data['external_uniqueid'] = isset($gameRecord['bid']) ? $gameRecord['bid'] : NULL;
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
        $game_logs_table_as = "sgwin";

        $sqlTime="{$game_logs_table_as}.updated_at >= ? and {$game_logs_table_as}.updated_at <= ?";

        if($use_bet_time){
            $sqlTime="{$game_logs_table_as}.betCreated >= ? and {$game_logs_table_as}.betCreated <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table_as}.id as sync_index,
    {$game_logs_table_as}.amount as bet_amount,
    {$game_logs_table_as}.betCode,
    {$game_logs_table_as}.betCount,
    {$game_logs_table_as}.betOdds,
    {$game_logs_table_as}.bid,
    {$game_logs_table_as}.channel,
    {$game_logs_table_as}.cm,
    {$game_logs_table_as}.content,
    {$game_logs_table_as}.betCreated as start_at,
    {$game_logs_table_as}.betCreated as bet_at,
    {$game_logs_table_as}.betDate,
    {$game_logs_table_as}.dividend as result_amount,
    {$game_logs_table_as}.gameCode,
    {$game_logs_table_as}.lottery,
    {$game_logs_table_as}.lotteryName as game,
    {$game_logs_table_as}.lotteryNumber,
    {$game_logs_table_as}.odds,
    {$game_logs_table_as}.realAmount as real_betting_amount,
    {$game_logs_table_as}.result,
    {$game_logs_table_as}.settled,
    {$game_logs_table_as}.settledTime as end_at,
    {$game_logs_table_as}.status,
    {$game_logs_table_as}.totalAmount,
    {$game_logs_table_as}.username,
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
    LEFT JOIN game_description ON {$game_logs_table_as}.lottery = game_description.external_game_id AND game_description.game_platform_id = ?
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

        switch ($row['status']) 
        {
            case Game_logs::STATUS_SETTLED:
                $note = "Normal Settlement";
                break;
            default:
                $note = null;
                break;
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
                'bet_amount'            => isset($row['bet_amount']) ?  $this->gameAmountToDB($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ?  $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ?  $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['real_betting_amount']) ?  $this->gameAmountToDB($row['real_betting_amount']) : 0,
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
                'note' => $note,
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row["status"] = $this->getGameRecordsStatus($row['settled']);
    }

     /**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) 
	{
		$this->CI->load->model(array('game_logs'));
		$status = strtolower($status);

		switch ($status) {
		case 1:
			$status = Game_logs::STATUS_SETTLED;
			break;
		case 0:
			$status = Game_logs::STATUS_PENDING;
			break;
		}
		return $status;
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

    public function login($playerName, $password = null, $extra = null) 
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $json_body = array(
            "defaultBgColor" => "white",
            "defaultColor" => "blue",
            "defaultGame" => "",
            "backButton" => $this->backButton,
            "backUrl" => $this->backURL,
            "range" => "",
            "editRange" => false,
            "tesing" => 0
        );

        $hash_params = array(
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername
        );

        $main_params = [
            "agentID" => $this->agent_id,
            "root" => $this->root,
            "username" => $gameUsername,
            "hash" => $this->md5Hash($hash_params)
        ];

        $params = array(
            "main_params" => $main_params ,
            "json_body" => $json_body,
            "actions" => [
                "function" => self::API_login, 
                "method" => self::METHOD["POST"]
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
            "session" => $resultArr["result"]["session"]
        );
        
        return array($success, $result);
	}

    public function isPlayerExist($playerName) 
    {
    	return $this->returnUnimplemented();
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