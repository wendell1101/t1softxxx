<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class game_api_hkb extends Abstract_game_api {

    public $playerTable;
    public $originalTable;
    public $api_url;
    public $operator_id;
    public $secret_key;
    public $prefix_for_username;
    public $game_url;
    public $ftpPath;
    public $currency;
    public $language;
    public $sync_time_interval;
    public $sync_days_interval;
    public $sleep_time;
    
    const METHOD = [
        "POST" => "POST",
        "GET" => "GET",
        "PUT" => "PUT"
    ];

    const URI_MAP = [
        self::API_createPlayer => "/register",
        self::API_queryForwardGame => "/getGameToken",
        self::API_queryPlayerBalance => "/balance",
        self::API_isPlayerExist => "/balance",
        self::API_depositToGame => "/transfer",
        self::API_withdrawFromGame => "/transfer",
        self::API_queryTransaction => "/check_trans",
        self::API_updatePlayerInfo => "/updatePlayerSetting",
        self::API_isPlayerOnline => "/checkPlayerIsOnline",
        self::API_syncGameRecords => "/getTransResult"
    ];

    const API_queryForwardGameLobby = "/api/lobby";
    const API_getJackpot = "/getJackpot";
    const API_getDailyWinLose = "/getDailyWinLose";
    const API_METHOD_AUTH_SESS = "auth_sess";
    const EMAIL_EXTENSION = "a@a.com";

    const CHECK_TRANSFER_STATUS = [
        0 => "unprocessed",
        1 => "already processed"
    ];

    const STATUS_CODE = [
        "SUCCESS" => 0,
        "INSUFFICIENT_BALANCE" => 300,
        "TRANSACTION_ID_ALREADY_PROCESSED" => 302,
        "MINIMUM_TRANSFER_LIMIT_CURRENCY_TYPE_AMOUNT" => 305,
        "MINIMUM_TRANSFER_LIMIT_CURRENCY_TYPE_AMOUNT_EXCEEDED" => 306,
        "INVALID_CURRENCY_TYPE" => 308,
        "UNKNOWN_USERNAME" => 310,
        "UNKNOWN_OPERATOR_ID" => 311,
        "USERNAME_ALREADY_REGISTERED" => 312,
        "UNKNOWN_PREFIX" => 313,
        "INVALID_SESSION" => 315,
        "USER_IS_BLOCKED" => 316,
        "INCOMPLETE_USER_DATA" => 317,
        "GAME_TOKEN_ALREADY_EXIST" => 318,
        "ERROR_BALANCE" => 340,
        "ERROR_PENDING_GAME" => 341,
        "INTERNAL_ERROR" => 399,
        "INVALID_TOKEN" => 400,
        "INVALID_HASH" => 500,
        "MAINTENANCE" => 501,
        "INVALID_DATA_INPUT" => 503,
    ];

    const GAME_LOGS_STATUS = [
        "INCLUDED" => [
            "RUNNING" => 0,
            "BONUS" => 6,
            "CANCEL" => 18,
            "REFUND" => 19,
            "WIN_DOUBLE" => 20,
            "BET" => 21,
            "WIN" => 22,
            "LOSE" => 23,
            "GIFT" => 24,
            "DRAW" => 25,
            "BUY_MEGA_JACKPOT" => 29,
            "WIN_REGULAR_JACKPOT" => 30,
            "WIN_MEGA_JACKPOT" => 31,
            "WIN_HALF" => 35,
            "REFUND_BET" => 49,
            "REFUND_BU_JACKPOT" => 50
        ],
        "EXCLUDED" => [
            "TRANSFER_IN" => 1,
            "TRANSFER_OUT" => 2,
            "ADJUSTMENT_PLUS" => 3,
            "ADJUSTMENT_MINUS" => 4,
            "TOP_UP_IN_GAME" => 26,
            "TRANSFER_IN_GAME" => 27,
            "TRANSFER_OUT_GAME" => 28,
            "TRANSFER_GAME_AUTO" => 48
        ]
    ];

    const GAME_TYPE = [
        "CARD_GAMES" => [
            "GAME_IDS" => [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113]
        ],
        "TOGEL" => [
            "GAME_IDS" => [201, 202, 203, 204, 205, 206, 209, 210, 211]
        ],
        "DINGDONG" => [
            "GAME_IDS" => [303, 304, 305, 306, 307, 308, 309, 310]
        ]
    ];

    const SUBGAME_CODE = [
        0  => "4D/3D/2D",
        1  => "4D",
        2  => "3D",
        3  => "2D",
        4  => "Colok Bebas",
        5  => "Colok Bebas 2D",
        6  => "Colok Naga",
        7  => "Colok Jitu",
        8  => "Tengah Tepi",
        9  => "Dasar",
        10 => "50-50",
        11 => "Shio",
        12 => "Silang Homo",
        13 => "Kembang Kempis",
        14 => "Kombinasi",
        15 => "Nomor",
        16 => "Row",
        17 => "Group",
        18 => "Dual",
        19 => "Triple",
        20 => "Quad",
        21 => "Hexa",
        22 => "Double",
        23 => "All Triple",
        24 => "Sum",
        25 => "Mono",
        26 => "Lambang",
        27 => "Angka",
        28 => "Warna",
        29 => "Hitam",
        30 => "B",
        31 => "S",
        32 => "4A",
        33 => "A",
        34 => "ABC",
        35 => "Pair",
        36 => "Kind",
        37 => "Full House",
        38 => "Straight",
        39 => "Flush",
        40 => "Octa",
        41 => "Quick Buy",
        42 => "50-50 2D"
    ];

    const BET_TYPE_CODE = [
        1   => "4D",
        2   => "3D",
        3   => "2D Belakang",
        4   => "2D Tengah",
        5   => "2D Depan",
        6   => "Colok Bebas",
        7   => "Colok Bebas 2D",
        11  => "Colok Naga",
        14  => "Colok Jitu",
        19  => "Tengah Tepi",
        22  => "Dasar",
        27  => "50-50",
        44  => "Shio",
        57  => "Silang Homo",
        64  => "Kembang Kempis",
        74  => "Kombinasi",
        223 => "50-50 2D",
        370 => "3D Depan"
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        "version_key",
        // "row_id", OGP-24088
        "prefix",
        "user_id",
        "username",
        "nickname",
        "status",
        "trans_id",
        "trans_time",
        "winloss_time",
        "period",
        "game_id",
        "winloss_amount",
        "main_balance",
        "game_balance",
        "turn_over",
        "net_turn_over",
        "bet_type_id",
        "user_ip",
        "table_id",
        "reward_balance",
        "channel",
        // "response_result_id", OGP-24088
        // "external_uniqueid" OGP-24088
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        "winloss_amount",
        "main_balance",
        "game_balance",
        "reward_balance", // OGP-24088
        "turn_over",
        "net_turn_over"
    ];

    const MD5_FIELDS_FOR_MERGE = [
        "game_code",
        "player_username",
        "hkb_status",
        "start_at",
        "bet_at",
        "end_at",
        "result_amount",
        "real_betting_result",
        "bet_result",
        "after_balance",
        "response_result_id",
        "external_uniqueid"
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        "result_amount",
        "real_betting_result",
        "bet_result",
        "after_balance"
    ];
    
    public function __construct()
    {
        parent::__construct();
        $this->playerTable = "player";
        $this->originalTable = "hkb_game_logs";
		$this->api_url = $this->getSystemInfo("url");
        $this->operator_id = $this->getSystemInfo("operator_id");
        $this->secret_key = $this->getSystemInfo("secret_key");
        $this->prefix_for_username = $this->getSystemInfo("prefix_for_username");
        $this->game_url = $this->getSystemInfo("game_url");
        $this->ftpPath = $this->getSystemInfo("ftp_game_record_path");
        $this->currency = $this->getSystemInfo("currency", "IDR");
        $this->language = $this->getSystemInfo("language", "en");
        $this->sync_time_interval = $this->getSystemInfo("sync_time_interval", "+1 minutes");
		$this->sync_days_interval = $this->getSystemInfo("sync_days_interval", "-1 days");
		$this->sleep_time = $this->getSystemInfo("sleep_time", "1");
    }

    public function getPlatformCode()
    {
        return HKB_GAME_API;
    }

    public function generateUrl($apiName, $params)
    {
        $uri = self::URI_MAP[$apiName];
       
        if(array_key_exists('actions', $params))
        {
            if($params["actions"]["method"] == self::METHOD["GET"])
            {
                unset($params["actions"]);
                $url = $this->api_url . $uri. '?' . http_build_query($params);
            }else{
                unset($params["actions"]);
                $url = $this->api_url . $uri;
            }
        }

		return $url;
	}

    public function getHttpHeaders($params)
    {
        if(array_key_exists('actions', $params))
        {
            unset($params["actions"]);
        }

        $header = array(
            "User-Agent" => "TripleOne"
        );

        return $header;
    }

    protected function customHttpCall($ch, $params)
    {
        if($params["actions"]["method"] == self::METHOD["POST"])
        {
            unset($params["actions"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode)
    {
		$success = false;

		if(!empty($resultArr) && ($resultArr['code'] == self::STATUS_CODE["SUCCESS"] || $resultArr['code'] == self::STATUS_CODE["USERNAME_ALREADY_REGISTERED"]))
        {
            $success = true;
        }

		if (!$success)
        {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('HKB API got error ', $responseResultId, 'result', $resultArr);
		}

		return $success;
	}

    public function getStatus($input_code, $field = null)
    {
        foreach(self::STATUS_CODE as $msg => $code) 
        {
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
            if(array_key_exists("operatorid", $method_params)) 
            {
                $operatorid = $method_params["operatorid"];
            }else{
                return $params = [
                    $this->getStatus(503, "key operatorid")
                ];
            }

            if (array_key_exists('username', $method_params)) 
            {
                $username = $method_params["username"];
            }else{
                return $params = [
                    $this->getStatus(503, "key username")
                ];
            }

            if (array_key_exists('token', $method_params)) 
            {
                $token = $method_params["token"];
            }else{
                return $params = [
                    $this->getStatus(503, "key token")
                ];
            }

            if (array_key_exists('hash', $method_params)) 
            {
                $hash = $method_params["hash"];
            }else{
                return $params = [
                    $this->getStatus(503, "key hash")
                ];
            }

            $hash_params = [
                $operatorid,
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

            if($this->operator_id != $operatorid)
            {
                return $params = [
                    $this->getStatus(503, "operatorid")
                ];
            }

            if($gameUsername != $username)
            {
                return $params = [
                    $this->getStatus(503, "username")
                ];
            }

            if($this->sha256Hash($hash_params) != $hash)
            {
                return $params = [
                    $this->getStatus(500)
                ];
            }

            $params = array(
                'username' => $gameUsername,
                'currency' => $this->currency,
                'operatorid' => $this->operator_id,
                'referral' => '',
                'code' => self::STATUS_CODE["SUCCESS"]
            );

            /*
            // --------------------> for testing. Do not delete this. <-----------------
            $hash_params = [
                $this->operator_id,
                $gameUsername,
                $token
            ];

            $params = array(
                "operatorid" => $this->operator_id,
                "username" => $method_params,
                "token" => $token,
                "hash" => $this->sha256Hash($hash_params)
            );
            */

        }else{
            return $params = [
                $this->getStatus(503, "method")
            ];
        }

        return $params;
    }

    //incase if needed
    public function getPlayerEmail($playerId)
    {
        $this->CI->load->model(array('player_model'));

        if (!empty($playerId)) 
        {
			$this->CI->player_model->db->select('email');
			$this->CI->player_model->db->where('playerId', $playerId);
			$qry = $this->CI->player_model->db->get($this->playerTable);
			return $this->CI->player_model->getOneRowOneField($qry, 'email');
		}

		return null;
    }

    public function getPlayerCurrency($username)
    {
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($username);

		if(!is_null($playerId))
        {
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);

			if(!is_null($currencyCode))
            {
				return $currencyCode;
			}else{
				return $this->currency;
			}

		}else{
			return $this->currency;
		}
	}

    public function sha256Hash($param)
    {
        $params = implode("", $param);

        return (string) hash("sha256", $params.$this->secret_key);
    }

    public function timeStamp()
    {
        return str_replace('+00:00', '', gmdate('c'));
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerCurrency = $this->getPlayerCurrency($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

        $referral = null;
        $email = $gameUsername.self::EMAIL_EXTENSION;
        
        $hash_params = [
            $this->operator_id,
            $gameUsername,
            $playerCurrency,
            $this->language,
            $playerName,
            $referral,
            $email
        ];

		$params = array(
			"operatorid" => $this->operator_id,
            "username" => $gameUsername,
            "currency" => $playerCurrency,
            "language" => $this->language,
            "fullname" => $playerName,
            "referral" => $referral,
            "email" => $email,
            "hash" => $this->sha256Hash($hash_params),
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
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [
            "response_result_id" => $responseResultId,
            "code" => $resultArr["code"],
			"msg" => $resultArr["msg"]
        ];

        if($success)
        {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function getGameToken($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerToken = $this->getPlayerTokenByGameUsername($gameUsername);
        $timeStamp = $this->timeStamp();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameToken',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        ];

        $hash_params = [
            $this->operator_id,
            $gameUsername,
            $playerToken,
            $timeStamp
        ];

        $params = [
            "operatorid" => $this->operator_id,
            "username" => $gameUsername,
            "token" => $playerToken,
            "timestamp" => $timeStamp,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_queryForwardGame,
                "method" => self::METHOD["POST"]
            ]
        ];

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForGetGameToken($params, $extra = null)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = [
            "response_result_id" => $responseResultId,
            "code" => $resultArr["code"],
			"msg" => $resultArr["msg"]
        ];

        if($success && isset($resultArr['gt']))
        {
            $result["gt"] = $resultArr['gt'];
        }else{
            $result["gt"] = null;
        }

        return [$success, $result];
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $getTokenResult = $this->getGameToken($playerName);
        
        if(isset($getTokenResult) && $getTokenResult['success'])
        {
            $gt = $getTokenResult["gt"];
        }
        
        $params = [
            "gt" => $gt,
            "language" => $this->getLauncherLanguage($this->language),
            "game_id" => $extra['game_code'],
            "home_url" => $this->getHomeLink()
        ];

        $url = $this->game_url . self::API_queryForwardGameLobby . "?" . http_build_query($params);

        $this->CI->utils->debug_log('<---------- queryForwardGame ---------->', 'queryForwardGame_result', $getTokenResult);
        
        return array("success" => true, "url" => $url);
    }

    public function getLauncherLanguage($currentLang) 
    {
        switch ($currentLang) 
        {
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
        $playerCurrency = $this->getPlayerCurrency($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $hash_params = [
            $this->operator_id,
            $playerCurrency,
            $gameUsername
        ];

		$params = array(
			"operatorid" => $this->operator_id,
			"currency" => $playerCurrency,
			"username" => $gameUsername,
            "hash" => $this->sha256Hash($hash_params),
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
		$result = [
            "response_result_id" => $responseResultId,
            "code" => $resultArr["code"],
			"msg" => $resultArr["msg"]
        ];

		if($success)
        {
			$result['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']));
		}

		return array($success, $result);
	}

    public function isPlayerExist($playerName)
    {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerCurrency = $this->getPlayerCurrency($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'playerId' => $playerId,
			'gameUsername' => $gameUsername,
            'playerCurrency' => $playerCurrency
		);

        $hash_params = [
            $this->operator_id,
            $playerCurrency,
            $gameUsername
        ];

		$params = array(
			"operatorid" => $this->operator_id,
			"currency" => $playerCurrency,
			"username" => $gameUsername,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_isPlayerExist,
                "method" => self::METHOD["POST"]
            ]
		);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params)
    {
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success)
        {
            $result['response_result'] = $resultArr;
            $result['exists'] = true;
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            $success = true;
            $result['exists'] = false;
        }

        $this->CI->utils->debug_log('<---------- processResultForIsPlayerExist ---------->', 'processResultForIsPlayerExist_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function getTransferErrorReasonCode($errorCode) 
    {
        switch ($errorCode) 
        {
            case self::STATUS_CODE["UNKNOWN_USERNAME"]:
                $reasonCode = self::REASON_ACCOUNT_NOT_EXIST;
                break;
            case self::STATUS_CODE["INCOMPLETE_USER_DATA"]:
                $reasonCode = self::REASON_INCOMPLETE_INFORMATION;
                break;
            case self::STATUS_CODE["ERROR_BALANCE"]:
                $reasonCode = self::REASON_NO_ENOUGH_BALANCE;
                break;
            case self::STATUS_CODE["ERROR_PENDING_GAME"]:
                $reasonCode = self::REASON_TRANSACTION_DENIED;
                break;
            case self::STATUS_CODE["INVALID_HASH"]:
                $reasonCode = self::REASON_PARAMETER_ERROR;
                break;
            case self::STATUS_CODE["MAINTENANCE"]:
                $reasonCode = self::REASON_API_MAINTAINING;
                break;
            case self::STATUS_CODE["INVALID_DATA_INPUT"]:
                $reasonCode = self::REASON_INVALID_ARGUMENTS;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
                break;
        }

        return $reasonCode;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, '');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerCurrency = $this->getPlayerCurrency($gameUsername);
        $dir = 1; //dir 0 = withdrawal and 1 = deposit
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

		$hash_params = [
            $this->operator_id,
            $playerCurrency,
            $gameUsername,
            $transfer_secure_id,
            $amount,
            $dir
        ];

		$params = array(
			"operatorid" => $this->operator_id,
			"currency" => $playerCurrency,
			"username" => $gameUsername,
			"trans_id" => $transfer_secure_id,
            "amount" => $amount,
            "dir" => $dir,
            "hash" => $this->sha256Hash($hash_params),
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

		$result = [
			'response_result_id' => $responseResultId,
			'reason_id'=> self::REASON_UNKNOWN,
            'external_transaction_id' => $resultArr["ext_id"],
            'code' => $resultArr["code"],
            'msg' => $resultArr["msg"],
            'amount' => $resultArr["amount"],
		];

		if($success)
        {
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
        $playerCurrency = $this->getPlayerCurrency($gameUsername);
        $dir = 0; //dir 0 = withdrawal and 1 = deposit
        $amount = $this->dBtoGameAmount($amount);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

		$hash_params = [
            $this->operator_id,
            $playerCurrency,
            $gameUsername,
            $transfer_secure_id,
            $amount,
            $dir
        ];

		$params = array(
			"operatorid" => $this->operator_id,
			"currency" => $playerCurrency,
			"username" => $gameUsername,
			"trans_id" => $transfer_secure_id,
            "amount" => $amount,
            "dir" => $dir,
            "hash" => $this->sha256Hash($hash_params),
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

		$result = [
			'response_result_id' => $responseResultId,
			'reason_id'=> self::REASON_UNKNOWN,
            'external_transaction_id' => $resultArr["ext_id"],
            'code' => $resultArr["code"],
            'msg' => $resultArr["msg"],
            'amount' => $resultArr["amount"],
		];

		if($success)
        {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $status = $resultArr['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function getCheckTransferStatus($status)
    {
        foreach (self::CHECK_TRANSFER_STATUS as $key => $value) 
        {
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

        $hash_params = [
            $this->operator_id,
            $transactionId
        ];

		$params = array(
			"operatorid" => $this->operator_id,
			"trans_id" => $transactionId,
            "hash" => $this->sha256Hash($hash_params),
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

        $ext_id = $resultArr["code"];

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$ext_id
		);

		if($success)
        {
            $result['reason_id'] = self::REASON_UNKNOWN;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED; //$this->getCheckTransferStatus($status);
		}else{
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED; //$this->getCheckTransferStatus($status);
		}

		return array($success, $result);
	}

    public function updatePlayerInfo($playerName, $infos)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerInfo',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

		$referral = null;
        $email = $gameUsername.self::EMAIL_EXTENSION;

        $hash_params = [
            $this->operator_id,
            $gameUsername,
            $this->language,
            $playerName,
            $referral,
            $email
        ];

		$params = array(
			"operatorid" => $this->operator_id,
            "username" => $gameUsername,
            "language" => $this->language,
            "fullname" => $playerName,
            "referral" => $referral,
            "email" => $email,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_updatePlayerInfo,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);
	}

    public function processResultForUpdatePlayerInfo($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = [
            "response_result_id" => $responseResultId,
            "code" => $resultArr["code"],
			"msg" => $resultArr["msg"]
        ];

        return array($success, $result);
	}

    public function isPlayerOnline($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerOnline',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $hash_params = [
            $this->operator_id,
            $gameUsername
        ];

		$params = array(
			"operatorid" => $this->operator_id,
            "username" => $gameUsername,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_isPlayerOnline,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_isPlayerOnline, $params, $context);
    }

    public function processResultForIsPlayerOnline($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = [
            "response_result_id" => $responseResultId,
        ];

		if($success)
        {
            $result['response_result'] = $resultArr;

            if(isset($resultArr['status']) && $resultArr['status'] == 'Online')
            {
                $result['is_online'] = true;
                $result['loginStatus'] = true;
            }else{
                $result['is_online'] = false;
                $result['loginStatus'] = false;
            }
        }

        return array($success, $result);
	}

    public function getJackpot($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerCurrency = $this->getPlayerCurrency($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetJackpot',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $hash_params = [
            $this->operator_id,
            $playerCurrency
        ];

		$params = array(
			"operatorid" => $this->operator_id,
            "currency" => $playerCurrency,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_getJackpot,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_getJackpot, $params, $context);
    }

    public function processResultForGetJackpot($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = [
            "response_result_id" => $responseResultId,
            "code" => $resultArr["code"],
			"msg" => $resultArr["msg"],
            "amount" => $resultArr["amount"]
        ];

        return array($success, $result);
	}

    public function getDailyWinLose($playerName, $start_date, $end_date)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetJackpot',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

        $hash_params = [
            $this->operator_id,
            $gameUsername,
            $start_date,
            $end_date
        ];

		$params = array(
			"operatorid" => $this->operator_id,
            "username" => $gameUsername,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_getDailyWinLose,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_getDailyWinLose, $params, $context);
    }

    public function processResultForGetDailyWinLose($params)
    {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = [
            "response_result_id" => $responseResultId,
            "code" => $resultArr["code"],
			"msg" => $resultArr["msg"],
            "data" => $resultArr["data"]
        ];

        return array($success, $result);
	}

    public function getTransResult($version_key)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTransResult',
            'version_key' => $version_key
        );

        $hash_params = [
            $this->operator_id,
            $version_key
        ];

		$params = array(
			"operatorid" => $this->operator_id,
            "version_key" => $version_key,
            "hash" => $this->sha256Hash($hash_params),
            "actions" => [
                "function" => self::API_syncGameRecords,
                "method" => self::METHOD["POST"]
            ]
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForGetTransResult($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $dataGameRecord = !empty($resultArr['data']) ? $resultArr['data']:[];
        
        $result = [
            "data_count" => 0,
            "data_count_insert" => 0,
			"data_count_update" => 0
        ];

        if($success && !empty($dataGameRecord))
        {
            $extra["response_result_id"] = $responseResultId;
            $gameRebuildRecord = $this->rebuildGameRecordsForGetTransResult($dataGameRecord, $extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
                $gameRebuildRecord,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows ----->', "gamerecords-> " . count($gameRebuildRecord), "insertrows-> " . count($insertRows), "updaterows-> " . count($updateRows), "version_key:" . $dataGameRecord[0]["version_key"]);
            
            $result['data_count'] += is_array($gameRebuildRecord) ? count($gameRebuildRecord): 0;

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

    public function roundDownToMinuteInterval(\DateTime $dateTime, $minuteInterval = 5)
    {
        return $dateTime->setTime(
            $dateTime->format('H'),
            floor($dateTime->format('i') / $minuteInterval) * $minuteInterval,
            0
        );
    }

    public function roundUpToMinuteInterval(\DateTime $dateTime, $minuteInterval = 5)
    {
        return $dateTime->setTime(
            $dateTime->format('H'),
            ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval,
            0
        );
    }

    public function syncOriginalGameLogs($token)
    {
        $syncId = parent::getValueFromSyncInfo($token, 'syncId');

        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));

        $this->CI->utils->debug_log('syncOriginalGameLogs -------------------------------------> ', "startDate: " . $startDate->format('Y-m-d H:i:s'), "endDate: " . $dateTimeTo->format('Y-m-d H:i:s'));

        $datetime_from = $this->roundDownToMinuteInterval($dateTimeFrom);
        $datetime_to = $this->roundUpToMinuteInterval($dateTimeTo);
        
        $ftpPath =  rtrim($this->ftpPath,'/').'/';
        
        $result = [
            "success" => true,
            "data_count" => 0,
            "data_count_insert" => 0,
			"data_count_update" => 0
        ];

        while($datetime_from <= $datetime_to)
        {
            $nextDateTime = new DateTime($datetime_from->format('Y-m-d H:i:s'));
            $next_datetime = $nextDateTime->modify("+1 days");

            $ftpFilePath_current = $ftpPath . $datetime_from->format('Ymd/Ymd_Hi') . ".json";
            $ftpFilePath_next = $ftpPath . $next_datetime->format('Ymd') ."/". $datetime_from->format('Ymd_Hi') . ".json";

            if(file_exists($ftpFilePath_current))
            {
                $gameRecordCount = $this->extractJsonFiles($ftpFilePath_current, $syncId);

                if(!empty($gameRecordCount))
                {
                    $result["data_count"] += $gameRecordCount["data_count"];
                    $result["data_count_insert"] += $gameRecordCount["data_count_insert"];
                    $result["data_count_update"] += $gameRecordCount["data_count_update"];
                }else{
                    $result["data_count"] += 0;
                    $result["data_count_insert"] += 0;
                    $result["data_count_update"] += 0;
                }

            }else{

                if(file_exists($ftpFilePath_next))
                {
                    $gameRecordCount = $this->extractJsonFiles($ftpFilePath_next, $syncId);

                    if(!empty($gameRecordCount))
                    {
                        $result["data_count"] += $gameRecordCount["data_count"];
                        $result["data_count_insert"] += $gameRecordCount["data_count_insert"];
                        $result["data_count_update"] += $gameRecordCount["data_count_update"];
                    }else{
                        $result["data_count"] += 0;
                        $result["data_count_insert"] += 0;
                        $result["data_count_update"] += 0;
                    }
                }
                
            }

            $datetime_from->modify($this->sync_time_interval);
        }

        sleep($this->sleep_time);

        $suffix_time = "second";

        if($this->sleep_time > 1)
        {
            $suffix_time = "seconds";
        }
        
        $this->CI->utils->debug_log("syncOriginalGameLogs Time Details -----> ", "adjust_datetime_minutes: " . $this->getDatetimeAdjust() . "", "sync_time_interval: {$this->sync_time_interval}", "sleep_time: {$this->sleep_time} {$suffix_time}");
        $this->CI->utils->debug_log("syncOriginalGameLogs Codes Last Update 10/12/2021 -----> ", "version: 2");

        return $result;
	}

    public function extractJsonFiles($ftpFiles, $syncId)
    {
        $gameRecords = json_decode(file_get_contents($ftpFiles, true), true);
        $responseResultId = $this->saveResponseResultForFile(true,'syncGameRecords', $this->getPlatformCode(), $ftpFiles, array('sync_id' => $syncId));

        $result = [
            "data_count" => 0,
            "data_count_insert" => 0,
            "data_count_update" => 0
        ];

        foreach($gameRecords as $gameRecord)
        {
            /* if(in_array($gameRecord["game_id"], self::GAME_TYPE["TOGEL"]["GAME_IDS"]))
            {
                //if using call API
                $record = $this->getTransResult($gameRecord["version_key"]);
                $result["data_count"] += $record["result"]["data_count"];
                $result["data_count_insert"] += $record["result"]["data_count_insert"];
                $result["data_count_update"] += $record["result"]["data_count_update"];
            }else{
                //else using FTP json files
                $record = $this->processFtpGameRecord($gameRecord, $responseResultId);
                $result["data_count"] += $record["data_count"];
                $result["data_count_insert"] += $record["data_count_insert"];
                $result["data_count_update"] += $record["data_count_update"];
            } */

            $record = $this->processFtpGameRecord($gameRecord, $responseResultId);
            $result["data_count"] += $record["data_count"];
            $result["data_count_insert"] += $record["data_count_insert"];
            $result["data_count_update"] += $record["data_count_update"];
        }

        return $result;
    }

    public function processFtpGameRecord($gameRecord, $responseResultId)
    {
        $this->CI->load->model(array('original_game_logs_model'));

        $result = [
            "data_count" => 0,
            "data_count_insert" => 0,
			"data_count_update" => 0
        ];

        if(!empty($gameRecord))
        {
            $extra["response_result_id"] = $responseResultId;
            $gameRebuildRecord = $this->rebuildGameRecordsForFtpRecord($gameRecord, $extra);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
                $gameRebuildRecord,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows ----->', "gamerecords-> " . count($gameRebuildRecord), "insertrows-> " . count($insertRows), "updaterows-> " . count($updateRows), "version_key:" . $gameRecord["version_key"]);
            $this->CI->utils->debug_log('processFtpGameRecord Data ----->', "Data: " . json_encode($gameRecord));
            
            $result['data_count'] += is_array($gameRebuildRecord) ? count($gameRebuildRecord): 0;

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

        return $result;
    }

    public function rebuildGameRecordsForFtpRecord($gameRecord, $extra)
    {
        if(isset($gameRecord['user_name']))
        {
            $getPlayerName = str_replace(strtolower($this->prefix_for_username), "", strtolower($gameRecord['user_name']));
            $username = $this->prefix_for_username.strtolower($getPlayerName);
        }else{
            $username = NULL;
        }

        //convert positive lose and bet into negative
        if(isset($gameRecord['winloss_amount']))
        {
            if($gameRecord["status"] == self::GAME_LOGS_STATUS["INCLUDED"]["LOSE"] || $gameRecord["status"] == self::GAME_LOGS_STATUS["INCLUDED"]["BET"])
            {
                $winloss_amount = abs($gameRecord['winloss_amount']) * -1;
            }else{
                $winloss_amount = $gameRecord['winloss_amount'];
            }
            
        }else{
            $winloss_amount = 0;
        }

        $data['version_key'] = isset($gameRecord['version_key']) ? $gameRecord['version_key'] : NULL;
        $data['row_id']= isset($gameRecord['id']) ? $gameRecord['id'] : NULL;
        $data['prefix'] = isset($gameRecord['prefix']) ? $gameRecord['prefix'] : NULL;
        $data['user_id'] = isset($gameRecord['user_id']) ? $gameRecord['user_id'] : NULL;
        $data['username'] = $username;
        $data['nickname'] = isset($gameRecord['nickname']) ? $gameRecord['nickname'] : NULL;
        $data['status'] = isset($gameRecord['status']) ? $gameRecord['status'] : NULL;
        $data['trans_id'] = isset($gameRecord['trans_id']) ? $gameRecord['trans_id'] : NULL;
        $data['trans_time'] = isset($gameRecord['trans_time']) ? $this->gameTimeToServerTime($gameRecord['trans_time']) : "0000-00-00 00:00:00";
        $data['winloss_time']= isset($gameRecord['winloss_time']) ? $this->gameTimeToServerTime($gameRecord['winloss_time']) : "0000-00-00 00:00:00";
        $data['period'] = isset($gameRecord['period']) ? $gameRecord['period'] : NULL;
        $data['game_id'] = isset($gameRecord['game_id']) ? $gameRecord['game_id'] : NULL;
        $data['winloss_amount'] = $winloss_amount;
        $data['main_balance'] = isset($gameRecord['main_balance']) ? $gameRecord['main_balance'] : 0;
        $data['game_balance'] = isset($gameRecord['game_balance']) ? $gameRecord['game_balance'] : 0;
        $data['turn_over'] = isset($gameRecord['turn_over']) ? $gameRecord['turn_over'] : 0;
        $data['net_turn_over'] = isset($gameRecord['net_turn_over']) ? $gameRecord['net_turn_over'] : 0;
        $data['bet_type_id'] = isset($gameRecord['bet_type_id']) ? $gameRecord['bet_type_id'] : NULL;
        $data['user_ip'] = isset($gameRecord['user_ip']) ? $gameRecord['user_ip'] : NULL;
        $data['table_id'] = isset($gameRecord['table_id']) ? $gameRecord['table_id'] : NULL;
        $data['reward_balance'] = isset($gameRecord['reward_balance']) ? $gameRecord['reward_balance'] : NULL;
        $data['channel'] = isset($gameRecord['channel']) ? $gameRecord['channel'] : NULL;
        $data['detail'] = isset($gameRecord['detail']) ? json_encode($gameRecord['detail']) : NULL;
        $data['response_result_id'] = $extra['response_result_id'];
        $data['external_uniqueid'] = isset($gameRecord['version_key']) ? $gameRecord['version_key'] . '-' . $gameRecord['trans_id'] : NULL;
        $dataRecords[] = $data;

        return $gameRecord = $dataRecords;
    }

    public function rebuildGameRecordsForGetTransResult($gameRecords, $extra)
    {
        foreach($gameRecords as $gameRecord)
        {
            if(isset($gameRecord['username']))
            {
                $getPlayerName = str_replace(strtolower($this->prefix_for_username), "", strtolower($gameRecord['username']));
                $username = $this->prefix_for_username.strtolower($getPlayerName);
            }else{
                $username = NULL;
            }

            //convert positive lose and bet into negative
            if(isset($gameRecord['winloss_amount']))
            {
                if($gameRecord["status"] == self::GAME_LOGS_STATUS["INCLUDED"]["LOSE"] || $gameRecord["status"] == self::GAME_LOGS_STATUS["INCLUDED"]["BET"])
                {
                    $winloss_amount = abs($gameRecord['winloss_amount']) * -1;
                }else{
                    $winloss_amount = $gameRecord['winloss_amount'];
                }

            }else{
                $winloss_amount = 0;
            }

            $data['version_key'] = isset($gameRecord['version_key']) ? $gameRecord['version_key'] : NULL;
            $data['row_id']= isset($gameRecord['id']) ? $gameRecord['id'] : NULL;
            $data['prefix'] = isset($gameRecord['prefix']) ? $gameRecord['prefix'] : NULL;
            $data['user_id'] = isset($gameRecord['user_id']) ? $gameRecord['user_id'] : NULL;
            $data['username'] = $username;
            $data['nickname'] = isset($gameRecord['nickname']) ? $gameRecord['nickname'] : NULL;
            $data['status'] = isset($gameRecord['status']) ? $gameRecord['status'] : NULL;
            $data['trans_id'] = isset($gameRecord['trans_id']) ? $gameRecord['trans_id'] : NULL;
            $data['trans_time'] = isset($gameRecord['trans_time']) ? $this->gameTimeToServerTime($gameRecord['trans_time']) : "0000-00-00 00:00:00";
            $data['winloss_time']= isset($gameRecord['winloss_time']) ? $this->gameTimeToServerTime($gameRecord['winloss_time']) : "0000-00-00 00:00:00";
            $data['period'] = isset($gameRecord['period']) ? $gameRecord['period'] : NULL;
            $data['game_id'] = isset($gameRecord['game_id']) ? $gameRecord['game_id'] : NULL;
            $data['winloss_amount'] = $winloss_amount;
            $data['main_balance'] = isset($gameRecord['main_balance']) ? $gameRecord['main_balance'] : 0;
            $data['game_balance'] = isset($gameRecord['game_balance']) ? $gameRecord['game_balance'] : 0;
            $data['turn_over'] = isset($gameRecord['turn_over']) ? $gameRecord['turn_over'] : 0;
            $data['net_turn_over'] = isset($gameRecord['net_turn_over']) ? $gameRecord['net_turn_over'] : 0;
            $data['bet_type_id'] = isset($gameRecord['bet_type_id']) ? $gameRecord['bet_type_id'] : NULL;
            $data['user_ip'] = isset($gameRecord['user_ip']) ? $gameRecord['user_ip'] : NULL;
            $data['table_id'] = isset($gameRecord['table_id']) ? $gameRecord['table_id'] : NULL;
            $data['reward_balance'] = isset($gameRecord['reward_balance']) ? $gameRecord['reward_balance'] : NULL;
            $data['channel'] = isset($gameRecord['channel']) ? $gameRecord['channel'] : NULL;
            $data['detail'] = isset($gameRecord['detail']) ? json_encode($gameRecord['detail']) : NULL;
            $data['response_result_id'] = $extra['response_result_id'];
            $data['external_uniqueid'] = isset($gameRecord['version_key']) ? $gameRecord['version_key'] . '-' . $gameRecord['trans_id'] : NULL;
            $dataRecords[] = $data;
        }

         return $gameRecords = $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount = 0;
        if(!empty($data))
        {
            foreach ($data as $record) 
            {
                if ($queryType == 'update') 
                {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                }else{
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

    public function syncMergeToGameLogs($token) 
    {
        $this->CI->utils->debug_log("syncMergeToGameLogs Codes Last Update 10/12/2021 -----> ", "version: 2");

		$enabled_game_logs_unsettle = true;
        
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $game_logs_status_excluded = array_values(self::GAME_LOGS_STATUS["EXCLUDED"]);
        $game_logs_status_excluded_implode = implode(",", $game_logs_status_excluded);
        $game_logs_table = $this->originalTable;
        $sqlTime = "{$game_logs_table}.updated_at >= ? AND {$game_logs_table}.updated_at <= ?";

        if($use_bet_time)
        {
            $sqlTime = "{$game_logs_table}.trans_time >= ? AND {$game_logs_table}.trans_time <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table}.id AS sync_index,
    {$game_logs_table}.version_key,
    {$game_logs_table}.row_id,
    {$game_logs_table}.prefix,
    {$game_logs_table}.user_id,
    {$game_logs_table}.username,
    {$game_logs_table}.nickname,
    {$game_logs_table}.status AS hkb_status,
    {$game_logs_table}.trans_id,
    {$game_logs_table}.trans_time AS start_at,
    {$game_logs_table}.trans_time AS bet_at,
    {$game_logs_table}.winloss_time end_at,
    {$game_logs_table}.period,
    {$game_logs_table}.game_id AS game_code,
    {$game_logs_table}.winloss_amount AS result_amount,
    {$game_logs_table}.main_balance AS after_balance,
    {$game_logs_table}.game_balance,
    {$game_logs_table}.turn_over AS real_betting_amount,
    {$game_logs_table}.net_turn_over AS bet_amount,
    {$game_logs_table}.bet_type_id,
    {$game_logs_table}.user_ip,
    {$game_logs_table}.channel,
    {$game_logs_table}.detail,
    {$game_logs_table}.table_id,
    {$game_logs_table}.reward_balance,
    {$game_logs_table}.response_result_id,
    {$game_logs_table}.external_uniqueid,
    {$game_logs_table}.created_at,
    {$game_logs_table}.updated_at,
    {$game_logs_table}.md5_sum,
    game_provider_auth.login_name AS player_username,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_name AS game_description_name,
    game_description.game_type_id,
    game_description.english_name AS game
FROM
    {$game_logs_table}
    LEFT JOIN game_description ON {$game_logs_table}.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON {$game_logs_table}.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE {$game_logs_table}.status NOT IN ($game_logs_status_excluded_implode) AND {$sqlTime}

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
        $game_type = $this->getGameType($row);
        $detail = $this->getBetDetails($row);
        $otherDetails = $this->getOtherDetailsForGameLogs($row);
        
        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        if(isset($row['result_amount']))
        {
            $winloss_amount = $row['result_amount'];
        }else{
            $winloss_amount = 0;
        }

        if(isset($row['real_betting_amount']))
        {
            $real_betting_amount = $row['real_betting_amount'];
        }else{
            $real_betting_amount = 0;
        }

        if(isset($row['bet_amount']))
        {
            $bet_amount = $row['bet_amount'];
            if($row["hkb_status"] == self::GAME_LOGS_STATUS["INCLUDED"]["DRAW"])
            {
                $bet_amount = 0;
            }
        }else{
            $bet_amount = 0;
        }

        if(isset($row['after_balance']))
        {
            $after_balance = $row['after_balance'];
        }else{
            $after_balance = 0;
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
                'game_type'             => $game_type,
                'game'                  => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => ($bet_amount != 0) ? $this->gameAmountToDBGameLogsTruncateNumber($bet_amount) : 0,
                'result_amount'         => ($winloss_amount != 0) ? $this->gameAmountToDBGameLogsTruncateNumber($winloss_amount) : 0,
                'bet_for_cashback'      => ($bet_amount != 0) ? $this->gameAmountToDBGameLogsTruncateNumber($bet_amount) : 0,
                'real_betting_amount'   => ($real_betting_amount != 0) ? $this->gameAmountToDBGameLogsTruncateNumber($real_betting_amount) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => ($after_balance != 0) ? $this->gameAmountToDBGameLogsTruncateNumber($after_balance) : 0
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : "0000-00-00 00:00:00",
                'end_at'                => $end_at,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : "0000-00-00 00:00:00",
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : "0000-00-00 00:00:00"
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row["status"],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['period']) ? $row['period'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => [
                'Created At' => $this->CI->utils->getNowForMysql(),
                "Period" => isset($row['period']) ? $row['period'] : null,
                "Status" => $otherDetails['bet_details_status'],
                "Game Type" => $game_type,
                "Bet Details" => $detail
            ],
            'extra' => [
                'note' => $otherDetails['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        $otherDetails = $this->getOtherDetailsForGameLogs($row);

        if(empty($row['game_type_id'])) 
        {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row["status"] = $otherDetails["status"];
    }

    public function getOtherDetailsForGameLogs($row)
    {
        $hkb_status = $row["hkb_status"];

        switch($hkb_status)
        {
            case self::GAME_LOGS_STATUS["INCLUDED"]["WIN_DOUBLE"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Win Double";
                $bet_details_status = $hkb_status . " (Win Double)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["BET"]:
                $bet_details_status = $hkb_status . " (Bet)";
                $game_logs_status = Game_logs::STATUS_SETTLED;
                if(in_array($row["game_code"], self::GAME_TYPE["TOGEL"]["GAME_IDS"]) || in_array($row["game_code"], self::GAME_TYPE["DINGDONG"]["GAME_IDS"]))
                {
                    $note = "Lose";
                }else{
                    $note = "Bet";
                }
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["WIN"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Win";
                $bet_details_status = $hkb_status . " (Win)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["LOSE"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Lose";
                $bet_details_status = $hkb_status . " (Lose)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["DRAW"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Draw";
                $bet_details_status = $hkb_status . " (Draw)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["BUY_MEGA_JACKPOT"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Buy Mega Jackpot";
                $bet_details_status = $hkb_status . " (Buy Mega Jackpot)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["WIN_REGULAR_JACKPOT"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Win Regular Jackpot";
                $bet_details_status = $hkb_status . " (Win Regular Jackpot)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["WIN_MEGA_JACKPOT"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Win Mega Jackpot";
                $bet_details_status = $hkb_status . " (Win Mega Jackpot)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["WIN_HALF"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Win Half";
                $bet_details_status = $hkb_status . " (Win Half)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["REFUND_BET"]:
                $game_logs_status = Game_logs::STATUS_REFUND;
                $note = "Refund Bet";
                $bet_details_status = $hkb_status . " (Refund Bet)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["REFUND_BU_JACKPOT"]:
                $game_logs_status = Game_logs::STATUS_REFUND;
                $note = "Refund Bu Jackpot";
                $bet_details_status = $hkb_status . " (Refund Bu Jackpot)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["RUNNING"]:
                $game_logs_status = Game_logs::STATUS_PENDING;
                $note = "Running";
                $bet_details_status = $hkb_status . " (Running)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["BONUS"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Bonus";
                $bet_details_status = $hkb_status . " (Bonus)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["CANCEL"]:
                $game_logs_status = Game_logs::STATUS_CANCELLED;
                $note = "Cancelled";
                $bet_details_status = $hkb_status . " (Cancelled)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["REFUND"]:
                $game_logs_status = Game_logs::STATUS_REFUND;
                $note = "Refund";
                $bet_details_status = $hkb_status . " (Refund)";
                break;
            case self::GAME_LOGS_STATUS["INCLUDED"]["GIFT"]:
                $game_logs_status = Game_logs::STATUS_SETTLED;
                $note = "Gift";
                $bet_details_status = $hkb_status . " (Gift)";
                break;
            default:
                $game_logs_status = Game_logs::STATUS_PENDING;
                $note = "Unsettled";
                $bet_details_status = $hkb_status . " (Unknown)";
                break;
        }

        $result = [
            "status" => $game_logs_status,
            "note" => $note,
            "bet_details_status" => $bet_details_status
        ];

        return $result;
    }

    public function getGameType($row)
    {
        //for bet details game type
        if(in_array($row["game_code"], self::GAME_TYPE["CARD_GAMES"]["GAME_IDS"]))
        {
            $game_type = "Card Games";
        }elseif(in_array($row["game_code"], self::GAME_TYPE["TOGEL"]["GAME_IDS"]))
        {
            $game_type = "Lottery (Togel)";
        }elseif(in_array($row["game_code"], self::GAME_TYPE["DINGDONG"]["GAME_IDS"]))
        {
            $game_type = "Live Dealer (Dingdong)";
        }else{
            $game_type = null;
        }

        return $game_type;
    }

    public function getBetDetails($row)
    {
        //for bet details
        if(!empty($row["detail"]))
        {
            $detail = $row["detail"];
        }else{
            $detail = null;
        }

        return $detail;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;

        if (isset($row['game_description_id'])) 
        {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id))
        {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function login($playerName, $password = null, $extra = null)
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