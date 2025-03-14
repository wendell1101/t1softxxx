<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
class Game_api_pt_v3 extends Abstract_game_api 
{
    public $original_table;
    public $api_url;
    public $entity_name;
    public $entity_key;
    public $admin_name;
    public $currency;
    public $ssl_key_path;
    public $ssl_cert_path;
    public $data_ssl_key_path;
    public $data_ssl_cert_path;
    public $prefix_for_username;
    public $is_force_withdraw;
    public $losebonus_withdraw;
    public $sync_time_interval;
    public $sleep_time;
    public $language;
    public $lobby_url;
    public $support_url;
    public $deposit_url;
    public $back_url;
    public $logout_url;
    public $mobile_hub;
    public $virtual_database;
    public $cloud_location;
    public $system_id;
    public $game_launch_js_script_src;
    public $game_launch_url;
    public $default_game_code_for_live_dealer;

    const TAG_CODE_LIVE_DEALER = 'live_dealer';

    const LANGUAGE_CODE = [
        'ENGLISH' => 'EN',
        'CHINESE' => 'ZH-CN',
        'INDONESIAN' => 'ID',
        'VIETNAMESE' => 'VI',
        'KOREAN' => 'KO',
        'THAI' => 'TH',
        'INDIA' => 'HI',
        'PORTUGUESE' => 'PT',
    ];
    
    const GAME_STATUS_RESULT = [
        'WIN' => 'Win',
        'DRAW' => 'Draw',
        'LOSE' => 'Lose'
    ];

    const URI_MAP = [
        self::API_createPlayer => '/player/create',
        self::API_isPlayerOnline => '/player/online',
        self::API_queryPlayerBalance => '/player/balance',
        self::API_isPlayerExist => '/player/info',
        self::API_queryPlayerInfo => '/player/info',
        self::API_changePassword => '/player/changepassword',
        self::API_depositToGame => '/player/deposit',
        self::API_withdrawFromGame => '/player/withdraw',
        self::API_queryTransaction => '/player/checktransaction',
        self::API_resetPlayer => '/player/resetFailedLogin',
        self::API_updatePlayerInfo => '/player/update',
        self::API_blockPlayer => '/player/freeze',
        self::API_unblockPlayer => '/player/unfreeze',
        self::API_logout => '/player/logout',
        self::API_revertBrokenGame => '/player/revertbrokengame',
        self::API_syncGameRecords => '/game/flow'
    ];

    const ERROR_CODE = [
        'PLAYER_NAME_NOT_SPECIFIED' => 10,
        'CASINO_NAME_NOT_SPECIFIED' => 11,
        'ADMIN_ENTITY_AND_CASINO_HAVE_TO_BE_RELATED' => 12,
        'AMOUNT_NOT_SPECIFIED' => 13,
        'ADMIN_NOT_SPECIFIED' => 14,
        'ENTITY_NAME_HAVE_TO_BE_PRESENT' => 15,
        'EXTERNAL_TRANSACTION_ID_NOT_SPECIFIED' => 16,
        'POSSIBLE_VALUES_OF_AMOUNT_CAN_BE_ONLY_NUMBERS' => 18,
        'THE_USERNAME_YOU_REQUESTED_IS_ALREADY_BEING_USED_BY_ANOTHER_PLAYER' => 19,
        'ENTITY_IS_NOT_ALLOWED_FOR_THE_ADMIN' => 21,
        'NICKNAME_IS_NOT_SET' => 22,
        'INAPPROPRIATE_CASINO_NICKNAME' => 23,
        'THIS_NICKNAME_IS_CURRENTLY_BEING_USED_BY_ANOTHER_PLAYER' => 24,
        'PLAYER_IS_ONLINE_AND_THE_CASINO_NICKNAME_IS_NOT_CHANGEABLE' => 25,
        'PLAYER_IS_OFFLINE_BUT_IS_REGISTERED_TO_A_TOURNAMENT' => 26,
        'TECHNICAL_ERROR_COULD_NOT_UPDATE_PLAYER_CASINO_NICKNAME' => 27,
        'YOU_ENTERED_THE_INCORRECT_FORMAT_OF_THE_BIRTHDATE' => 28,
        'ENTERED_THE_WRONG_REQUEST_FORMAT_OF_3RDPCONTAINER' => 29,
        'ENTERED_THE_WRONG_REQUEST_FORMAT_OF_PLAYERNAME' => 30,
        'PLAYER_DOES_NOT_EXIST' => 41,
        'PLAYER_USERNAME_IN_INVALID_FORMAT' => 42,
        'PLAYER_DOES_NOT_BELONG_TO_KIOSK' => 43,
        'PLAYER_IS_FROZEN' => 44,
        'LANGUAGE_CODE_IS_RESTRICTED_FOR_TLE' => 45,
        'PLAYER_HAS_WAITING_WITHDRAWAL_REQUESTS' => 46,
        'ACTION_IS_NOT_ALLOWED' => 47,
        'ADMIN_DOES_NOT_BELONG_TO_TLE' => 48,
        'PLAYER_IS_NOT_ALLOWED_FOR_THE_ENTITY' => 49,
        'PLAYER_PASSWORD_IS_TOO_SHORT' => 50,
        'PLAYER_PASSWORD_IS_TOO_LONG' => 51,
        'UMS_API_RETURN_ERROR_ON_ACCESS_REQUEST_ATTEMPT' => 71,
        'SERVER_ERROR_ACCESSING_UMS_API' => 72,
        'DATABASE_ERROR_OCCURED' => 73,
        'ATTRIBUTE_DOES_NOT_EXIST' => 75,
        'ENTERED_THE_WRONG_REQUEST_FORMAT' => 76,
        'DEPOSIT_NOT_ALLOWED' => 92,
        'AMOUNT_IS_LESS_THAN_MINIMUM_DEPOSIT_AMOUNT' => 93,
        'AMOUNT_EXCEEDS_MAXIMUM_DEPOSIT_LIMIT' => 94,
        'CASINO_NOT_OPEN' => 95,
        'DEPOSIT_LIMIT_FOR_PERIOD_IS_EXCEEDED' => 96,
        'ADMIN_DEPOSIT_BALANCE_INSUFFICIENT' => 97,
        'INSTANT_CASH_AMOUNT_IS_GREATER_THAN_BALANCE' => 98,
        'PLAYER_IS_IN_GAME' => 99,
        'BONUS_DECLINED_ON_WITHDRAW' => 101,
        'CANNOT_MAKE_CASHOUT_NO_PERMISSIONS' => 102,
        'AMOUNT_IS_BELOW_MINIMUM_ALLOWED_CASHOUT_AMOUNT' => 103,
        'AMOUNT_IS_OVER_CURRENT_PLAYER_BALANCE' => 104,
        'WITHDRAW_OPERATION_IS_NOT_ALLOWED' => 105,
        'KIOSKADMIN_DEPOSIT_BALANCE_UPDATED_FAILED' => 106,
        'ADMIN_IS_FROZEN' => 108,
        'ADMIN_INTERNAL_STATE_MISMATCH_WITH_PLAYER_INTERNAL_STATE' => 109,
        'BUSINESS_ENTITY_NOT_FOUND' => 114,
        'ENTITY_NOT_FOUND' => 130,
        'KIOSK_ADMIN_DOES_NOT_FOUND' => 133,
        'FORM_VALIDATION_ERRORS' => 140,
        'KIOSK_ADMIN_CODE_IS_NOT_VALID' => 148,
        'IS_IN_GAME_FAILED' => 167,
        'DEPOSIT_ERROR' => 170,
        'API_ERROR' => 301,
        'EXTERNAL_TRANSACTION_ID_ALREADY_EXISTS' => 302
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'playername',
        'gameid',
        'gamecode',
        'gametype',
        'gamename',
        'sessionid',
        'currencycode',
        'bet',
        'win',
        'progressivebet',
        'progressivewin',
        'balance',
        'currentbet',
        'gamedate',
        'exitgame',
        'shortname'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet',
        'win',
        'progressivebet',
        'progressivewin',
        'balance',
        'currentbet'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'gameid',
        'gamecode',
        'gametype',
        'gamename',
        'round_number',
        'currencycode',
        'bet_amount',
        'win_amount',
        'progressivebet',
        'progressivewin',
        'after_balance',
        'currentbet',
        'start_at',
        'bet_at',
        'end_at',
        'exitgame',
        'game_code',
        'response_result_id',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',
        'progressivebet',
        'progressivewin',
        'after_balance',
        'currentbet'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->original_table = 'pt_v3_game_logs';
        $this->api_url = $this->getSystemInfo('url');
        $this->entity_name = $this->getSystemInfo('entity_name');
        $this->entity_key = $this->getSystemInfo('entity_key');
        $this->admin_name = $this->getSystemInfo('admin_name');
        $this->currency = $this->getSystemInfo('currency');
        $this->ssl_key_path = $this->getSystemInfo('ssl_key_path');
		$this->ssl_cert_path = $this->getSystemInfo('ssl_cert_path');

        $this->data_ssl_key_path = $this->getSystemInfo('data_ssl_key_path');
		$this->data_ssl_cert_path = $this->getSystemInfo('data_ssl_cert_path');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->is_force_withdraw = $this->getSystemInfo('is_force_withdraw', 1);
        $this->losebonus_withdraw = $this->getSystemInfo('losebonus_withdraw', 1);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes'); //minutes/hours/days
		$this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->language = $this->getSystemInfo('language', 'en-us');
        $this->support_url = $this->getSystemInfo('support_url');
        $this->deposit_url = $this->getSystemInfo('deposit_url');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');

        $this->mobile_hub = $this->getSystemInfo('mobile_hub');
        $this->virtual_database = $this->getSystemInfo('virtual_database');
        $this->cloud_location = $this->getSystemInfo('cloud_location');
        $this->system_id = $this->getSystemInfo('system_id');
        $this->game_launch_js_script_src = $this->getSystemInfo('game_launch_js_script_src');
        $this->default_game_code_for_live_dealer = $this->getSystemInfo('default_game_code_for_live_dealer', 'abl');
        
    }

    public function getPlatformCode()
    {
        return PT_V3_API;
    }

    public function generateUrl($apiName, $params)
    {
        $uri_map = self::URI_MAP[$apiName];
        $url = $this->api_url . $uri_map;

        return $url;
    }

    protected function customHttpCall($ch, $params)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    public function getHttpHeaders($params) 
    {
		$http_header = array(
            'X_ENTITY_KEY' => $this->entity_key
        );

        return $http_header;
	}

    public function initSSL($ch) 
    {
		parent::initSSL($ch);

		$pathToSslKey = realpath(@$this->ssl_key_path);
		$pathToSslCert = realpath(@$this->ssl_cert_path);

		$this->CI->utils->debug_log('<---------- (initSSL) ---------->', 'PT_V3_key', $pathToSslKey, 'PT_V3_pem', $pathToSslCert);

		if(!file_exists($pathToSslKey) || !file_exists($pathToSslCert)) 
        {
			$this->CI->utils->debug_log('<---------- (initSSL) file not found ---------->', 'PT_V3_key', $pathToSslKey, 'PT_V3_pem', $pathToSslCert);
		}

        curl_setopt($ch, CURLOPT_SSLKEY, $pathToSslKey);
		curl_setopt($ch, CURLOPT_SSLCERT, $pathToSslCert);
	}

    public function initSSLForSync($ch) 
    {
		parent::initSSL($ch);

		$pathToSslKey = realpath(@$this->data_ssl_key_path);
		$pathToSslCert = realpath(@$this->data_ssl_cert_path);

		$this->CI->utils->debug_log('<---------- (initSSLForSync) ---------->', 'PT_V3_key', $pathToSslKey, 'PT_V3_pem', $pathToSslCert);

		if(!file_exists($pathToSslKey) || !file_exists($pathToSslCert)) 
        {
			$this->CI->utils->debug_log('<---------- (initSSLForSync) file not found ---------->', 'PT_V3_key', $pathToSslKey, 'PT_V3_pem', $pathToSslCert);
		}

        curl_setopt($ch, CURLOPT_SSLKEY, $pathToSslKey);
		curl_setopt($ch, CURLOPT_SSLCERT, $pathToSslCert);
	}

    public function processResultBoolean($responseResultId, $resultArr, $extra = null)
    {
		$success = false;
        
		if(!empty($resultArr) && isset($resultArr['result']) || (isset($resultArr['errorcode']) && $resultArr['errorcode'] == self::ERROR_CODE['THE_USERNAME_YOU_REQUESTED_IS_ALREADY_BEING_USED_BY_ANOTHER_PLAYER']))
        {
            $this->CI->utils->debug_log('Success processResultBoolean', 'processResultBoolean_result', $resultArr);
            $success = true;
        }

		if(!$success)
        {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PT V3 API got error processResultBoolean', $responseResultId, 'processResultBoolean_result', $resultArr);
		}

		return $success;
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
            'currency' => $this->currency,
			'gameUsername' => $gameUsername
		);

        $params = array(
			'playername' => $gameUsername,
            'adminname' => $this->admin_name,
            'entityname' => $this->entity_name,
            'password' => $password,
            'currency' => $this->currency,
            'custom02' => $this->prefix_for_username
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
            'response_result_id' => $responseResultId,
        ];
        
        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        $this->CI->utils->debug_log('<---------- processResultForCreatePlayer ---------->', 'processResultForCreatePlayer_result', 'result: ' . json_encode($result));

        return array($success, $result);
	}

    public function isPlayerOnline($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerOnline',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'playername' => $gameUsername
        );

        return $this->callApi(self::API_isPlayerOnline, $params, $context);
    }

    public function processResultForIsPlayerOnline($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['is_online'] = $result['response_result']['result'];
            $result['loginStatus'] = $result['response_result']['result'];
        }

        $this->CI->utils->debug_log('<---------- processResultForIsPlayerOnline ---------->', 'processResultForIsPlayerOnline_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'playername' => strtoupper($gameUsername)
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['balance'] = $this->gameAmountToDB(floatval($result['response_result']['balance']));
        }
        
        $this->CI->utils->debug_log('<---------- processResultForQueryPlayerBalance ---------->', 'processResultForQueryPlayerBalance_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function isPlayerExist($playerName)
    {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'playername' => $gameUsername
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

        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['exists'] = true;
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            $success = true;
            $result['exists'] = false;
        }

        $this->CI->utils->debug_log('<---------- processResultForIsPlayerExist ---------->', 'processResultForIsPlayerExist_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function queryPlayerInfo($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerInfo',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'playername' => $gameUsername
        );

        return $this->callApi(self::API_queryPlayerInfo, $params, $context);
    }

    public function processResultForQueryPlayerInfo($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result = $result['response_result'];
        }

        $this->CI->utils->debug_log('<---------- processResultForQueryPlayerInfo ---------->', 'processResultForQueryPlayerInfo_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function changePassword($playerName, $oldPassword, $newPassword)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'playerName' => $playerName,
            'password' => $newPassword,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'playername' => $gameUsername,
            'password' => $newPassword
        );

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    public function processResultForChangePassword($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['password'] = $this->getVariableFromContext($params, 'password');
			$playerName = $this->getVariableFromContext($params, 'playerName');
			$playerId = $this->getPlayerIdInPlayer($playerName);

			if($playerId)
            {
				$this->updatePasswordForPlayer($playerId, $result['password']);
			}else{
				$this->CI->utils->debug_log('cannot find player', $playerName);
			}
        }
        
        $this->CI->utils->debug_log('<---------- processResultForChangePassword ---------->', 'processResultForChangePassword_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    //for deposit, withdraw and queryTransaction
    public function getReasonIdAndStatus($errorCode)
    {
        $status = self::COMMON_TRANSACTION_STATUS_DECLINED;
        $reason_id = '';
        
        switch($errorCode) 
        {
            case self::ERROR_CODE['PLAYER_NAME_NOT_SPECIFIED']:
                $reason_id = self::REASON_NOT_FOUND_PLAYER;
                break;
            case self::ERROR_CODE['CASINO_NAME_NOT_SPECIFIED']:
                $reason_id = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case self::ERROR_CODE['AMOUNT_NOT_SPECIFIED']:
                $reason_id = self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case self::ERROR_CODE['ADMIN_NOT_SPECIFIED']:
                $reason_id = self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            case self::ERROR_CODE['EXTERNAL_TRANSACTION_ID_NOT_SPECIFIED']:
                $reason_id = self::REASON_INVALID_TRANSACTION_ID;
                break;
            case self::ERROR_CODE['POSSIBLE_VALUES_OF_AMOUNT_CAN_BE_ONLY_NUMBERS']:
                $reason_id = self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case self::ERROR_CODE['PLAYER_DOES_NOT_EXIST']:
                $reason_id = self::REASON_NOT_FOUND_PLAYER;
                break;
            case self::ERROR_CODE['PLAYER_DOES_NOT_BELONG_TO_KIOSK']:
                $reason_id = self::REASON_NOT_FOUND_PLAYER;
                break;
            case self::ERROR_CODE['PLAYER_IS_FROZEN']:
                $reason_id = self::REASON_GAME_ACCOUNT_LOCKED;
                break;
            case self::ERROR_CODE['LANGUAGE_CODE_IS_RESTRICTED_FOR_TLE']:
                $reason_id = self::REASON_ACCESS_DENIED;
                break;
            case self::ERROR_CODE['PLAYER_HAS_WAITING_WITHDRAWAL_REQUESTS']:
                $reason_id = self::REASON_TRANSACTION_PENDING;
                $status = self::COMMON_TRANSACTION_STATUS_PROCESSING;
                break;
            case self::ERROR_CODE['ACTION_IS_NOT_ALLOWED']:
                $reason_id = self::REASON_TRANSACTION_DENIED;
                break;
            case self::ERROR_CODE['ADMIN_DOES_NOT_BELONG_TO_TLE']:
                $reason_id = self::REASON_ACCESS_DENIED;
                break;
            case self::ERROR_CODE['PLAYER_IS_NOT_ALLOWED_FOR_THE_ENTITY']:
                $reason_id = self::REASON_ACCESS_DENIED;
                break;
            case self::ERROR_CODE['UMS_API_RETURN_ERROR_ON_ACCESS_REQUEST_ATTEMPT']:
                $reason_id = self::REASON_REQUEST_LIMIT;
                break;
            case self::ERROR_CODE['SERVER_ERROR_ACCESSING_UMS_API']:
                $reason_id = self::REASON_SERVER_TIMEOUT;
                break;
            case self::ERROR_CODE['DATABASE_ERROR_OCCURED']:
                $reason_id = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case self::ERROR_CODE['ATTRIBUTE_DOES_NOT_EXIST']:
                $reason_id = self::REASON_PARAMETER_ERROR;
                break;
            case self::ERROR_CODE['DEPOSIT_NOT_ALLOWED']:
                $reason_id = self::REASON_TRANSACTION_DENIED;
                break;
            case self::ERROR_CODE['AMOUNT_IS_LESS_THAN_MINIMUM_DEPOSIT_AMOUNT']:
                $reason_id = self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
                break;
            case self::ERROR_CODE['AMOUNT_EXCEEDS_MAXIMUM_DEPOSIT_LIMIT']:
                $reason_id = self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
                break;
            case self::ERROR_CODE['CASINO_NOT_OPEN']:
                $reason_id = self::REASON_ACCESS_DENIED;
                break;
            case self::ERROR_CODE['DEPOSIT_LIMIT_FOR_PERIOD_IS_EXCEEDED']:
                $reason_id = self::REASON_TRANSACTION_DENIED;
                break;
            case self::ERROR_CODE['ADMIN_DEPOSIT_BALANCE_INSUFFICIENT']:
                $reason_id = self::REASON_NO_ENOUGH_BALANCE;
                break;
            case self::ERROR_CODE['KIOSKADMIN_DEPOSIT_BALANCE_UPDATED_FAILED']:
                $reason_id = self::REASON_BALANCE_NOT_SYNC;
                break;
            case self::ERROR_CODE['ADMIN_IS_FROZEN']:
                $reason_id = self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            case self::ERROR_CODE['ADMIN_INTERNAL_STATE_MISMATCH_WITH_PLAYER_INTERNAL_STATE']:
                $reason_id = self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            case self::ERROR_CODE['KIOSK_ADMIN_DOES_NOT_FOUND']:
                $reason_id = self::REASON_OPERATOR_NOT_EXIST;
                break;
            case self::ERROR_CODE['KIOSK_ADMIN_CODE_IS_NOT_VALID']:
                $reason_id = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case self::ERROR_CODE['DEPOSIT_ERROR']:
                $reason_id = self::REASON_TRANSACTION_DENIED;
                break;
            case self::ERROR_CODE['API_ERROR']:
                $reason_id = self::REASON_FAILED_FROM_API;
                break;
            case self::ERROR_CODE['EXTERNAL_TRANSACTION_ID_ALREADY_EXISTS']:
                $reason_id = self::REASON_TRANSACTION_ID_ALREADY_EXISTS;
                break;
            default:
                $reason_id = self::REASON_UNKNOWN;
                $status = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
        }

        $result = [
            'reason_id' => $reason_id,
            'status' => $status
        ];

        return $result;
    }
    

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
            'gameUsername' => $gameUsername
        );

		$params = array(
			'playername' => $gameUsername,
            'amount' => $this->dBtoGameAmount($amount),
			'adminname' => $this->admin_name,
			'externaltranid' => $transfer_secure_id
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params)
    {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['amount'] = $result['response_result']['amount'];
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            if(isset($resultArr['errorcode']))
            {
                $getReasonIdAndStatus = $this->getReasonIdAndStatus($resultArr['errorcode']);
                $result['reason_id'] = $getReasonIdAndStatus['reason_id'];
                $result['transfer_status'] = $getReasonIdAndStatus['status'];
            }
        }

        $this->CI->utils->debug_log('<---------- processResultForDepositToGame ---------->', 'processResultForDepositToGame_result', 'result: ' . json_encode($result));

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
            'gameUsername' => $gameUsername
        );

		$params = array(
			'playername' => $gameUsername,
            'amount' => $this->dBtoGameAmount($amount),
			'adminname' => $this->admin_name,
            'isForce' => $this->is_force_withdraw,
			'externaltranid' => $transfer_secure_id,
            'losebonus' => $this->losebonus_withdraw
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $result['amount'] = $result['response_result']['amount'];
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            if(isset($resultArr['errorcode']))
            {
                $getReasonIdAndStatus = $this->getReasonIdAndStatus($resultArr['errorcode']);
                $result['reason_id'] = $getReasonIdAndStatus['reason_id'];
                $result['transfer_status'] = $getReasonIdAndStatus['status'];
            }
        }
        
        $this->CI->utils->debug_log('<---------- processResultForWithdrawFromGame ---------->', 'processResultForWithdrawFromGame_result', 'result: ' . json_encode($result));

        return array($success, $result);
	}

    public function queryTransaction($transactionId, $extra)
    {
        $playerId = $extra['playerId'];
        $playerName = $extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
            'extra' => $extra,
			'playerId' => $playerId,
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

		$params = array(
			'externaltransactionid' => $transactionId
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

    public function processResultForQueryTransaction($params)
    {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
            'external_transaction_id'=> $external_transaction_id,
			'response_result_id' => $responseResultId,
            'reason_id'=> self::REASON_UNKNOWN,
            'status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
            if(isset($resultArr['errorcode']))
            {
                $getReasonIdAndStatus = $this->getReasonIdAndStatus($resultArr['errorcode']);
                $result['reason_id'] = $getReasonIdAndStatus['reason_id'];
                $result['status'] = $getReasonIdAndStatus['status'];
            }
		}
        
        $this->CI->utils->debug_log('<---------- processResultForQueryTransaction ---------->', 'processResultForQueryTransaction_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function resetPlayer($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForResetPlayer',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

		$params = array(
			'playername' => $gameUsername
		);
        
		return $this->callApi(self::API_resetPlayer, $params, $context);
    }

    public function processResultForResetPlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
		}

        $this->CI->utils->debug_log('<---------- processResultForResetPlayer ---------->', 'processResultForResetPlayer_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function updatePlayerInfo($playerName, $infos)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerInfo',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'infos' => $infos
		);

		$params = array(
			'playername' => $gameUsername
		);
        
		return $this->callApi(self::API_updatePlayerInfo, $params, $context);
    }

    public function processResultForUpdatePlayerInfo($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
		}

        $this->CI->utils->debug_log('<---------- processResultForUpdatePlayerInfo ---------->', 'processResultForUpdatePlayerInfo_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function blockPlayer($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

		$params = array(
			'playername' => $gameUsername
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForBlockPlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
			$result['isBlocked'] = $this->blockUsernameInDB($result['response_result']['playername']);
		}

        $this->CI->utils->debug_log('<---------- processResultForBlockPlayer ---------->', 'processResultForBlockPlayer_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function unblockPlayer($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

		$params = array(
			'playername' => $gameUsername
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
			$result['isUnblocked'] = $this->unblockUsernameInDB($result['response_result']['playername']);
		}

        $this->CI->utils->debug_log('<---------- processResultForUnblockPlayer ---------->', 'processResultForUnblockPlayer_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function logout($playerName, $password = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
            'password' => $password,
            'gameUsername' => $gameUsername
		);

		$params = array(
			'playername' => $gameUsername
		);

		return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
		}
        
        $this->CI->utils->debug_log('<---------- processResultForLogout ---------->', 'processResultForLogout_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function revertBrokenGame($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

		$params = array(
			'playername' => $gameUsername
		);

		return $this->callApi(self::API_revertBrokenGame, $params, $context);
    }

    public function processResultForRevertBrokenGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId
		];

		if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
		}
        
        $this->CI->utils->debug_log('<---------- processResultForRevertBrokenGame ---------->', 'processResultForRevertBrokenGame_result', 'result: ' . json_encode($result));

		return array($success, $result);
	}

    public function getLauncherLanguage($language)
    {
        $lang = '';

        switch($language) 
        {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                    $lang = self::LANGUAGE_CODE['ENGLISH']; //english
                    break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                    $lang = self::LANGUAGE_CODE['CHINESE']; //chinese
                    break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                    $lang = self::LANGUAGE_CODE['INDONESIAN']; //indonesian
                    break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vi':
            case 'vi-vn':
                    $lang = self::LANGUAGE_CODE['VIETNAMESE']; //vietnamese
                    break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                    $lang = self::LANGUAGE_CODE['KOREAN']; //korean
                    break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-th':
                $lang = self::LANGUAGE_CODE['THAI']; //thai
                break;
            case Language_function::INT_LANG_INDIA:
                case 'hi-in':
                $lang = self::LANGUAGE_CODE['INDIA']; //india
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt-br':
                $lang = self::LANGUAGE_CODE['PORTUGUESE']; //portuguese
                break;
            default:
                $lang = self::LANGUAGE_CODE['ENGLISH']; //default as english
                break;
        }

        return $lang;
	}

    public function queryForwardGame($playerName, $extra)
    {
        $success = true;
        $username = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($username);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $language = $this->getLauncherLanguage($this->language = $this->getSystemInfo('language', $extra['language']));
        $client = 'ngm_desktop';
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $game_type = isset($extra['game_type']) ? $extra['game_type'] : null;

        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        $support_url = $this->support_url;
        $deposit_url = $this->deposit_url;
        $lobby_url = $this->lobby_url = $this->getSystemInfo('lobby_url', isset($extra['home_link']) && !empty($extra['home_link']) ? $extra['home_link'] : $this->getHomeLink());
        $back_url = $this->back_url = $this->getSystemInfo('back_url', $lobby_url);
        $logout_url = $this->logout_url = $this->getSystemInfo('logout_url', $lobby_url);

        if($game_type != self::TAG_CODE_LIVE_DEALER)
        {
            if($language == self::LANGUAGE_CODE['PORTUGUESE'] || $language == self::LANGUAGE_CODE['INDIA'])
            {
                $language = self::LANGUAGE_CODE['ENGLISH'];
            }
        }

        if($is_mobile)
        {
            if($game_type == self::TAG_CODE_LIVE_DEALER)
            {
                $client = 'live_mob';
            }else{
                $client = 'ngm_mobile';
            }
        }else{
            if($game_type == self::TAG_CODE_LIVE_DEALER)
            {
                $client = 'live_desk';
            }
        }

        $result = [
            'success' => $success,
            'username' => $username,
            'password' => $password,
            'game' => $game_code,
            'lang' => $language,
            'client' => $client,
            'mode' => $game_mode,
            'lobbyUrl' => $lobby_url,
            'supportUrl' => $support_url,
            'depositUrl' => $deposit_url,
            'backUrl' => $back_url,
            'logoutUrl' => $logout_url,
            'mobile_hub' => $this->mobile_hub,
            'virtual_database' => $this->virtual_database,
            'cloud_location' => $this->cloud_location,
            'system_id' => $this->system_id,
            'integration_script_js' => $this->generateIntegrationScriptJs(),
            'game_launch_url' => $this->game_launch_url
        ];
        $this->CI->utils->debug_log('<--------------- queryForwardGame --------------->', 'queryForwardGame_result', $result);

        return $result;
    }
    public function generateIntegrationScriptJs(){
        return $this->game_launch_js_script_src;
    }

    public function getGameFlow($startDate, $endDate)
    {
        $exitGame = 1;          // (0 | 1) is exit game events have to be shown
        $showGameShortName = 1; // show exact the game code

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetGameFlow',
            'start_date' => $startDate,
            'end_date' => $endDate
		);

        $params = [
            'exitgame' => $exitGame,
            'startdate' => $startDate,
            'enddate' => $endDate,
            'showgameshortname' => $showGameShortName
        ];

        return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

    public function processResultForGetGameFlow($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

		$result = [
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0
        ];

        if($success && !empty($resultArr['result']))
        {
            $result['response_result'] = $resultArr['result'];
            $extra['response_result_id'] = $responseResultId;
            $gameRecords = $this->rebuildGameRecords($result['response_result'], $extra);

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

            $this->CI->utils->debug_log('after process available rows ----->', 'gamerecords-> ' . count($gameRecords), 'insertrows-> ' . count($insertRows), 'updaterows-> ' . count($updateRows), 'gamecode:' . $result['response_result'][0]['GAMECODE']);
            
            $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

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

		return array($success, $result);
	}

    public function rebuildGameRecords($gameRecords, $extra)
    {
        $data = [];

        foreach($gameRecords as $gameRecord)
        {
            $data['playername'] = isset($gameRecord['PLAYERNAME']) ? $gameRecord['PLAYERNAME'] : NULL;
            $data['windowcode']= isset($gameRecord['WINDOWCODE']) ? $gameRecord['WINDOWCODE'] : NULL;
            $data['gameid'] = isset($gameRecord['GAMEID']) ? $gameRecord['GAMEID'] : NULL;
            $data['gamecode'] = isset($gameRecord['GAMECODE']) ? $gameRecord['GAMECODE'] : NULL;
            $data['gametype'] = isset($gameRecord['GAMETYPE']) ? $gameRecord['GAMETYPE'] : NULL;
            $data['gamename'] = isset($gameRecord['GAMENAME']) ? $gameRecord['GAMENAME'] : NULL;
            $data['sessionid'] = isset($gameRecord['SESSIONID']) ? $gameRecord['SESSIONID'] : NULL;
            $data['currencycode'] = isset($gameRecord['CURRENCYCODE']) ? $gameRecord['CURRENCYCODE'] : NULL;
            $data['bet'] = !empty($gameRecord['BET']) ? $gameRecord['BET'] : 0;
            $data['win'] = !empty($gameRecord['WIN']) ? $gameRecord['WIN'] : 0;
            $data['progressivebet'] = !empty($gameRecord['PROGRESSIVEBET']) ? $gameRecord['PROGRESSIVEBET'] : 0;
            $data['progressivewin'] = !empty($gameRecord['PROGRESSIVEWIN']) ? $gameRecord['PROGRESSIVEWIN'] : 0;
            $data['balance'] = !empty($gameRecord['BALANCE']) ? $gameRecord['BALANCE'] : 0;
            $data['currentbet'] = !empty($gameRecord['CURRENTBET']) ? $gameRecord['CURRENTBET'] : 0;
            $data['gamedate'] = isset($gameRecord['GAMEDATE']) ? $this->gameTimeToServerTime($gameRecord['GAMEDATE']) : '0000-00-00 00:00:00';
            $data['livenetwork'] = isset($gameRecord['LIVENETWORK']) ? $gameRecord['LIVENETWORK'] : NULL;
            $data['response_result_id'] = $extra['response_result_id'];
            $data['external_uniqueid'] = isset($gameRecord['GAMECODE']) ? $gameRecord['GAMECODE'] : NULL;
            $data['exitgame'] = isset($gameRecord['EXITGAME']) ? $gameRecord['EXITGAME'] : NULL;
            $data['shortname'] = isset($gameRecord['SHORTNAME']) ? $gameRecord['SHORTNAME'] : NULL;
            $dataRecords[] = $data;
        }
        
        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount = 0;
        
        if(!empty($data))
        {
            foreach($data as $record) 
            {
                if($queryType == 'update') 
                {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function syncOriginalGameLogs($token)
    {
        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTimeModified = $dateTimeFrom->modify($this->getDatetimeAdjust());

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($startDateTimeModified->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));

        $startDateTime = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDateTime = $dateTimeTo->format('Y-m-d H:i:s');

        $result = [
            'success' => true,
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0,
            'sync_time_interval' => $this->sync_time_interval,
            'sleep_time' => $this->sleep_time
        ];
       
        while($startDateTime <= $endDateTime)
        {
            $endDateTimeModified = (new DateTime($startDateTime))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

            $gameRecords = $this->getGameFlow($startDateTime, $endDateTimeModified);

            if($gameRecords['success'])
            {
                $result['data_count'] += isset($gameRecords['data_count']) && !empty($gameRecords['data_count']) ? $gameRecords['data_count'] : 0;
                $result['data_count_insert'] += isset($gameRecords['data_count_insert']) && !empty($gameRecords['data_count_insert']) ? $gameRecords['data_count_insert']: 0;
                $result['data_count_update'] += isset($gameRecords['data_count_update']) && !empty($gameRecords['data_count_update']) ? $gameRecords['data_count_update'] : 0;
            }else{
                $result['data_count'] += 0;
                $result['data_count_insert'] += 0;
                $result['data_count_update'] += 0;
            }

            $this->CI->utils->debug_log('<--------------- processResultForSyncOriginalGameLogs --------------->', 'startDateTime: ' . $startDateTime, 'endDateTimeModified: ' . $endDateTimeModified);
            
            sleep($this->sleep_time);

            $startDateTime = (new DateTime($startDateTime))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return $result;
	}

    public function syncMergeToGameLogs($token)
    {
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
        $game_logs_table = $this->original_table;
        $sqlTime = "{$game_logs_table}.updated_at >= ? AND {$game_logs_table}.updated_at <= ?";

        if($use_bet_time)
        {
            $sqlTime = "{$game_logs_table}.gamedate >= ? AND {$game_logs_table}.gamedate <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table}.id AS sync_index,
    {$game_logs_table}.playername AS username,
    {$game_logs_table}.windowcode,
    {$game_logs_table}.gameid,
    {$game_logs_table}.gamecode,
    {$game_logs_table}.gamecode AS round_number,
    {$game_logs_table}.gametype,
    {$game_logs_table}.gamename,
    {$game_logs_table}.sessionid,
    {$game_logs_table}.currencycode,
    {$game_logs_table}.bet AS bet_amount,
    {$game_logs_table}.win AS win_amount,
    {$game_logs_table}.progressivebet,
    {$game_logs_table}.progressivewin,
    {$game_logs_table}.balance AS after_balance,
    {$game_logs_table}.currentbet,
    {$game_logs_table}.gamedate AS start_at,
    {$game_logs_table}.gamedate AS bet_at,
    {$game_logs_table}.gamedate AS end_at,
    {$game_logs_table}.livenetwork,
    {$game_logs_table}.exitgame,
    {$game_logs_table}.shortname AS game_code,
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
    LEFT JOIN game_description ON {$game_logs_table}.shortname = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON {$game_logs_table}.playername = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE {$sqlTime}

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
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
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
                'bet_amount'            => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'result_amount'         => !empty($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => !empty($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : '0000-00-00 00:00:00',
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : '0000-00-00 00:00:00'
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['round_number']) ? $row['round_number'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => [
                'Created At' => $this->CI->utils->getNowForMysql(),
                'Real Game Name' => isset($row['gamename']) ? $row['gamename'] : null,
                'Session ID' => isset($row['sessionid']) ? $row['sessionid'] : 0,
            ],
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if(empty($row['game_type_id'])) 
        {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['note'] = $this->getNote($row['bet_amount'], $row['win_amount'], $row['currentbet']);
        $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
        //$row['status'] = (empty($row['currentbet'])) ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;
        $row['status'] =  Game_logs::STATUS_SETTLED;
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;

        if(isset($row['game_description_id'])) 
        {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id))
        {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['gamename'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function getNote($bet_amount, $win_amount, $currentbet = 0)
    {
        /* if(empty($currentbet))
        {
            if($bet_amount > $win_amount)
            {
                $note = self::GAME_STATUS_RESULT['LOSE'];
            }elseif($bet_amount < $win_amount)
            {
                $note = self::GAME_STATUS_RESULT['WIN'];
            }elseif(!empty($bet_amount) && !empty($win_amount) && $bet_amount == $win_amount)
            {
                $note = self::GAME_STATUS_RESULT['DRAW'];
            }else{
                $note = 'Free Game (Entered the game without betting)';
            } 
        }else{
            $note = 'Current Bet (' . $currentbet . ')';
        } */

        if ($bet_amount > $win_amount) {
            $note = self::GAME_STATUS_RESULT['LOSE'];
        } elseif ($bet_amount < $win_amount) {
            $note = self::GAME_STATUS_RESULT['WIN'];
        } elseif (!empty($bet_amount) && !empty($win_amount) && $bet_amount == $win_amount) {
            $note = self::GAME_STATUS_RESULT['DRAW'];
        } else {
            $note = 'Free Game (Entered the game without betting)';
        }

        return $note;
    }

    //override initSSL
    protected function httpCallApi($url, $params, $apiName=null, &$costMs=null) {
        //call http
        $content = null;
        $header = null;
        $statusCode = null;
        $statusText = '';
        $ch = null;

        $options = $this->makeHttpOptions([
            'ignore_ssl_verify' => $this->ignore_ssl_verify,
            'call_http_proxy_host' => $this->call_http_proxy_host,
            'call_http_proxy_port' => $this->call_http_proxy_port,
            'call_http_proxy_login' => $this->call_http_proxy_login,
            'call_http_proxy_password' => $this->call_http_proxy_password,

            'call_socks5_proxy' => $this->call_socks5_proxy,
            'call_socks5_proxy_login' => $this->call_socks5_proxy_login,
            'call_socks5_proxy_password' => $this->call_socks5_proxy_password,

        ]);

        $timeoutSeconds=$this->getTimeoutByApiName($apiName, $this->getTimeoutSecond());
        $connTimeoutSeconds=$this->getConnTimeoutByApiName($apiName, $this->getConnectTimeout());
        $this->CI->utils->debug_log('HTTP CALL PARAMS >-------------------> ', $this->CI->utils->encodeJson($params),
            $options, 'timeoutSeconds: '.$timeoutSeconds.', connTimeoutSeconds: '.$connTimeoutSeconds);
        $t=microtime(true);
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $use_timeout_ms_on_curl=$this->CI->utils->getConfig('use_timeout_ms_on_curl');
            //set timeout
            if($use_timeout_ms_on_curl){
                curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeoutSeconds*1000);
                // $this->CI->utils->debug_log('CURLOPT_TIMEOUT_MS', $timeoutSeconds*1000);
            }else{
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
                // $this->CI->utils->debug_log('CURLOPT_TIMEOUT', $timeoutSeconds);
            }
            //set timeout
            if($use_timeout_ms_on_curl){
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connTimeoutSeconds*1000);
            }else{
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connTimeoutSeconds);
            }
            // curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeoutSecond());
            // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getConnectTimeout());

            $settle_proxy=false;
            // set proxy
            if (isset($options['call_socks5_proxy']) && !empty($options['call_socks5_proxy'])) {
                $this->CI->utils->debug_log('http call with proxy', $options['call_socks5_proxy']);
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
                curl_setopt($ch, CURLOPT_PROXY, $options['call_socks5_proxy']);
                if (!empty($options['call_socks5_proxy_login']) && !empty($options['call_socks5_proxy_password'])) {
                    curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['call_socks5_proxy_login'] . ':' . $options['call_socks5_proxy_password']);
                }
                $settle_proxy=true;
                $this->_proxySettings=['call_socks5_proxy'=>$options['call_socks5_proxy']];
            }

            if(!$settle_proxy){
                //http proxy
                if (isset($options['call_http_proxy_host']) && !empty($options['call_http_proxy_host'])) {
                    $this->CI->utils->debug_log('http call with http proxy', $options['call_http_proxy_host']);
                    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                    curl_setopt($ch, CURLOPT_PROXY, $options['call_http_proxy_host']);
                    curl_setopt($ch, CURLOPT_PROXYPORT, $options['call_http_proxy_port']);
                    if (!empty($options['call_http_proxy_login']) && !empty($options['call_http_proxy_password'])) {
                        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['call_http_proxy_login'] . ':' . $options['call_http_proxy_password']);
                    }
                    $this->_proxySettings=['call_http_proxy_host'=>$options['call_http_proxy_host'],
                        'call_http_proxy_port'=>$options['call_http_proxy_port']];
                    $settle_proxy=true;
                }
            }

            if($options['ignore_ssl_verify']){
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }

            $initSSLMethod = ($apiName == self::API_syncGameRecords) ? 'initSSLForSync' : 'initSSL';
            $this->$initSSLMethod($ch);

            $headers = $this->convertArrayToHeaders($this->getHttpHeaders($params));
            // $this->CI->utils->debug_log('HTTP CALL Headers >------------------------------------> ', $headers);
            if (!empty($headers)) {
                $this->CI->utils->debug_log('HTTP CALL CURL Headers >------------------------------> ', $headers);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            //process post
            $this->customHttpCall($ch, $params);

            $response = curl_exec($ch);
            $errCode = curl_errno($ch);
            $error = curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $content = substr($response, $header_size);

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            // $this->CI->utils->debug_log('response from http', $response);

            $statusText = $errCode . ':' . $error;
            curl_close($ch);

        } catch (Exception $e) {
            $this->processError($e);
        }
        $costMs=(microtime(true)-$t)*1000;
        $this->CI->utils->debug_log('cost of request', $costMs);
        return array($header, $content, $statusCode, $statusText, $errCode, $error, null);

    }
}

/*end of file*/