<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_lucky365 extends Abstract_game_api {
    public $original_game_logs_table;
    public $api_url;
    public $agent_serial_number;
    public $agent_secret_key;
    public $game_url;
    public $prefix_for_username;
    public $language;
    public $currency;
    public $callbackUrl;
    public $page_size;
    public $sync_time_interval;
    public $sleep_time;
    public $GUID;

    const METHOD_CreatePlayer = 'CreatePlayer';
    const METHOD_GetPlayer = 'GetPlayer';
    const METHOD_GetBalance = 'GetBalance';
    const METHOD_SetBalanceTransfer = 'SetBalanceTransfer';
    const METHOD_GetTransferById = 'GetTransferById';
    const METHOD_GetLoginH5 = 'GetLoginH5';
    const METHOD_UpdatePlayer = 'UpdatePlayer';
    const METHOD_SetPlayerStatus = 'SetPlayerStatus';
    const METHOD_PlayerLogout = 'PlayerLogout';
    const METHOD_GetGameRecordByTime = 'GetGameRecordByTime';

    const URI_MAP = [
        self::API_createPlayer => '/UserInfo/CreatePlayer',
        self::API_isPlayerExist => '/UserInfo/GetPlayer',
        self::API_queryPlayerInfo => '/UserInfo/GetPlayer',
        self::API_queryPlayerBalance => '/Account/GetBalance',
        self::API_depositToGame => '/Account/SetBalanceTransfer',
        self::API_withdrawFromGame => '/Account/SetBalanceTransfer',
        self::API_queryTransaction => '/Account/GetTransferById',
        self::API_queryForwardGame => '/UserInfo/GetLoginH5',
        self::API_updatePlayerInfo => '/UserInfo/UpdatePlayer',
        self::API_blockPlayer => '/UserInfo/SetPlayerStatus',
        self::API_unblockPlayer => '/UserInfo/SetPlayerStatus',
        self::API_logout => '/UserInfo/PlayerLogout',
        self::API_syncGameRecords => '/Game/GetGameRecordByTime',
    ];

    const RESULT_CODE = [
        'SUCCESS' => 'S100',
        'INVALID_SIGNATURE' => 'F0001',
        'INVALID_SN' => 'F0002',
        'INVALID_PARAMETER' => 'F0003',
        'INVALID_CURRENCY' => 'F0004',
        'PLAYER_ALREADY_EXIST' => 'F0005',
        'PLAYER_DOES_NOT_EXIST' => 'F0006',
        'MEMBER_DOES_NOT_EXIST' => 'F0007',
        'OPERATION_FAILED' => 'F0008',
        'INVALID_METHOD' => 'F0009',
        'INVALID_PLAYER_STATUS' => 'F0010',
        'PLAYER_STATUS_DO_NOT_NEED_TO_BE_CHANGED' => 'F0011',
        'DATA_OUT_OF_RANGE' => 'F0012',
        'NO_MATCHING_DATE' => 'F0013',
        'LOGIN_LOCATION_IS_NOT_ALLOWED' => 'F0014',
        'NOT_ENOUGH_SCORE' => 'F0015',
        'REWARD_IS_NOT_SUPPORTED' => 'F0016',
        'ORDER_ID_CANNOT_BE_REPEATED' => 'F0017',
        'SYSTEM_IS_BUSY' => 'F0018',
        'WRONG_DATETIME_FORMAT' => 'F0019',
        'EXCEED_TIME_RANGE_LIMIT' => 'F0020',
        'OPERATION_CANCELLED' => 'F0021',
        'SYSTEM_MAINTENANCE' => 'M0001',
        'SYSTEM_ERROR' => 'M0002',
    ];

    const LANGUAGE_CODE = [
        'CHINESE' => 'Zh-cn',
        'ENGLISH' => 'En-us',
        'THAI' => 'Th',
    ];

    const GAME_STATUS_RESULT = [
        'WIN' => 'Win',
        'DRAW' => 'Draw',
        'LOSE' => 'Lose'
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'loginId',
        'totalBet',
        'totalWin',
        'betDetail',
        'winDetail',
        'orderId',
        'actionDate',
        'creationDate',
        'gameName',
        'validCommission',
        'validBet',
        'validWin',
        'mjpWin',
        'mjpComm',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'totalBet',
        'totalWin',
        'validCommission',
        'validBet',
        'validWin',
        'mjpWin',
        'mjpComm',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'totalBet',
        'totalWin',
        'betDetail',
        'winDetail',
        'round_number',
        'start_at',
        'bet_at',
        'end_at',
        'game_code',
        'validCommission',
        'bet_amount',
        'win_amount',
        'mjpWin',
        'mjpComm',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'totalBet',
        'totalWin',
        'validCommission',
        'bet_amount',
        'win_amount',
        'mjpWin',
        'mjpComm',
    ];

    const MD5_FIELDS_FOR_SYNC_FROM_EXCEL = [
        'loginId',
        'totalBet',
        'totalWin',
        'betDetail',
        'orderId',
        'actionDate',
        'creationDate',
        'gameName',
        'validCommission',
        'validBet',
        'validWin',
        'mjpWin',
        'mjpComm',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_SYNC_FROM_EXCEL = [
        'totalBet',
        'totalWin',
        'validCommission',
        'validBet',
        'validWin',
        'mjpWin',
        'mjpComm',
    ];

    public function __construct() {
        parent::__construct();
        $this->original_game_logs_table = 'lucky365_game_logs';
        $this->api_url = $this->getSystemInfo('url');
        $this->agent_serial_number = $this->getSystemInfo('agent_serial_number');
        $this->agent_secret_key = $this->getSystemInfo('agent_secret_key');
        $this->game_url = $this->getSystemInfo('game_url');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->callbackUrl = $this->getSystemInfo('callbackUrl');
        $this->page_size = $this->getSystemInfo('page_size', 2000); //maximum 2000
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
		$this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->GUID = $this->CI->utils->getGUIDv4();
    }

    public function getPlatformCode() {
        return LUCKY365_GAME_API;
    }

    public function getHttpHeaders($params) {
		$http_header = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        );

        return $http_header;
	}

    public function generateUrl($apiName, $params) {
        $url = $this->api_url . self::URI_MAP[$apiName];

        return $url;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    }

    public function processResultBoolean($responseResultId, $resultArr, $extra = null) {
        $success = false;
        
        if(!empty($resultArr) && isset($resultArr['code']) && ($resultArr['code'] == self::RESULT_CODE['SUCCESS'] || $resultArr['code'] == self::RESULT_CODE['PLAYER_ALREADY_EXIST'])) {
            $this->CI->utils->debug_log('Success processResultBoolean', 'processResultBoolean_result', $resultArr);
            $success = true;
        }

        if(!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('LUCKY365 API got error processResultBoolean', $responseResultId, 'processResultBoolean_result', $resultArr);
        }

        return $success;
    }

    private function signing($sn, $id, $method, $playerCode, $refId = null, $startDateTime = null, $endDateTime = null) {
        $APISecretKey = $this->agent_secret_key;

        switch ($method) {
            case self::METHOD_GetTransferById:
                $signature = md5($id.$method.$sn.$playerCode.$refId.$APISecretKey);
                break;
            case self::METHOD_GetGameRecordByTime:
                $signature = md5($id.$method.$sn.$playerCode.$startDateTime.$endDateTime.$APISecretKey);
                break;
            default:
                $signature = md5($id.$method.$sn.$playerCode.$APISecretKey);
                break;
        }
        
        return $signature;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
		);

        $sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_CreatePlayer;
        $playerCode = $gameUsername;
        $signature = $this->signing($sn, $id, $method, $playerCode);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'PlayerCode' => $playerCode,
            'PlayerName' => $playerName,
            'Signature' => $signature,
		);
        
		return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
        $result = [
            'response_result_id' => $responseResultId,
        ];
        
        if($success) {
            $result['response_result'] = $resultArr;
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function isPlayerExist($playerName) {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
            'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_GetPlayer;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Signature' => $signature,
		);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['data'])) {
            $result['response_result'] = $resultArr['data'];
            $result['exists'] = true;
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            $result['exists'] = null;
            if($resultArr['code'] == self::RESULT_CODE['PLAYER_DOES_NOT_EXIST']) {
                $success = true;
                $result['exists'] = false;
            }
        }

        return array($success, $result);
    }

    public function queryPlayerInfo($playerName) {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerInfo',
            'playerId' => $playerId,
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_GetPlayer;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Signature' => $signature,
		);

        return $this->callApi(self::API_queryPlayerInfo, $params, $context);
    }

    public function processResultForQueryPlayerInfo($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['result'])) {
            $result['response_result'] = $resultArr['data'];
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_GetBalance;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Signature' => $signature,
		);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'response_result_id' => $responseResultId,
        ];

        if($success && !empty($resultArr['data'])) {
            $result['response_result'] = $resultArr['data'];
            $balance_result = isset($resultArr['data']['result']) ? $resultArr['data']['result'] : '';
            $result['balance'] = $this->gameAmountToDB(floatval($balance_result));
        }
        
        return array($success, $result);
    }

    //for deposit, withdraw and queryTransaction
    public function getReasonIdAndStatus($error_code) {
        $status = self::COMMON_TRANSACTION_STATUS_DECLINED;

        switch ($error_code) {
            case self::RESULT_CODE['INVALID_SIGNATURE']:
                $reason_id = self::REASON_PARAMETER_ERROR;
                break;
            case self::RESULT_CODE['INVALID_SN']:
                $reason_id = self::REASON_PARAMETER_ERROR;
                break;
            case self::RESULT_CODE['INVALID_PARAMETER']:
                $reason_id = self::REASON_PARAMETER_ERROR;
                break;
            case self::RESULT_CODE['INVALID_CURRENCY']:
                $reason_id = self::REASON_CURRENCY_ERROR;
                break;
            case self::RESULT_CODE['PLAYER_ALREADY_EXIST']:
                $reason_id = self::REASON_USER_ALREADY_EXISTS;
                break;
            case self::RESULT_CODE['PLAYER_DOES_NOT_EXIST']:
                $reason_id = self::REASON_NOT_FOUND_PLAYER;
                break;
            case self::RESULT_CODE['MEMBER_DOES_NOT_EXIST']:
                $reason_id = self::REASON_ACCOUNT_NOT_EXIST;
                break;
            case self::RESULT_CODE['OPERATION_FAILED']:
                $reason_id = self::REASON_FAILED_FROM_API;
                break;
            case self::RESULT_CODE['INVALID_METHOD']:
                $reason_id = self::REASON_PARAMETER_ERROR;
                break;
            case self::RESULT_CODE['INVALID_PLAYER_STATUS']:
                $reason_id = self::REASON_TRANSACTION_DENIED;
                break;
            case self::RESULT_CODE['DATA_OUT_OF_RANGE']:
                $reason_id = self::REASON_INVALID_TIME_RANGE;
                break;
            case self::RESULT_CODE['NO_MATCHING_DATE']:
                $reason_id = self::REASON_INVALID_TIME_RANGE;
                break;
            case self::RESULT_CODE['LOGIN_LOCATION_IS_NOT_ALLOWED']:
                $reason_id = self::REASON_ACCESS_DENIED;
                break;
            case self::RESULT_CODE['NOT_ENOUGH_SCORE']:
                $reason_id = self::REASON_NO_ENOUGH_BALANCE;
                break;
            case self::RESULT_CODE['REWARD_IS_NOT_SUPPORTED']:
                $reason_id = self::REASON_TRANSACTION_DENIED;
                break;
            case self::RESULT_CODE['ORDER_ID_CANNOT_BE_REPEATED']:
                $reason_id = self::REASON_TRANSACTION_ID_ALREADY_EXISTS;
                break;
            case self::RESULT_CODE['SYSTEM_IS_BUSY']:
                $reason_id = self::REASON_SERVER_TIMEOUT;
                break;
            case self::RESULT_CODE['WRONG_DATETIME_FORMAT']:
                $reason_id = self::REASON_INVALID_TIME_RANGE;
                break;
            case self::RESULT_CODE['EXCEED_TIME_RANGE_LIMIT']:
                $reason_id = self::REASON_INVALID_TIME_RANGE;
                break;
            case self::RESULT_CODE['OPERATION_CANCELLED']:
                $reason_id = self::REASON_ACCESS_DENIED;
                break;
            case self::RESULT_CODE['SYSTEM_MAINTENANCE']:
                $reason_id = self::REASON_API_MAINTAINING;
                break;
            case self::RESULT_CODE['SYSTEM_ERROR']:
                $reason_id = self::REASON_FAILED_FROM_API;
                break;
            default:
                $reason_id = self::REASON_UNKNOWN;
                $status = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
        }

        $result = [
            'reason_id' => $reason_id,
            'status' => $status,
        ];

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
            'gameUsername' => $gameUsername,
        );

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_SetBalanceTransfer;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Amount' => $this->dBtoGameAmount($amount),
            'Reward' => 0,
            'Signature' => $signature,
		);

		return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN,
		];

		if($success && !empty($resultArr['data'])) {
            $result['response_result'] = $resultArr['data'];
            $result['amount'] = $resultArr['data']['result'];
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $error_code = isset($resultArr['code']) ? $resultArr['code'] : '';
            if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $getReasonIdAndStatus = $this->getReasonIdAndStatus($error_code);
                $result['reason_id'] = $getReasonIdAndStatus['reason_id'];
                $result['transfer_status'] = $getReasonIdAndStatus['status'];
            }
        }

        return array($success, $result);
	}

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
            'gameUsername' => $gameUsername,
        );

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_SetBalanceTransfer;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Amount' => $this->dBtoGameAmount(-$amount),
            'Reward' => 0,
            'Signature' => $signature,
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN,
		];

		if($success && !empty($resultArr['data'])) {
            $result['response_result'] = $resultArr['data'];
            $result['amount'] = $resultArr['data']['result'];
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $error_code = isset($resultArr['code']) ? $resultArr['code'] : '';
            $getReasonIdAndStatus = $this->getReasonIdAndStatus($error_code);
            $result['reason_id'] = $getReasonIdAndStatus['reason_id'];
            $result['transfer_status'] = $getReasonIdAndStatus['status'];
        }

        return array($success, $result);
	}

    public function queryTransaction($transactionId, $extra) {
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
            'gameUsername' => $gameUsername,
		);

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_GetTransferById;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId, $transactionId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'RefId' => $transactionId,
            'Signature' => $signature,
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

    public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
            'external_transaction_id'=> $external_transaction_id,
			'response_result_id' => $responseResultId,
            'reason_id'=> self::REASON_UNKNOWN,
            'status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN,
		];

		if($success) {
            $result['response_result'] = $resultArr['data'];
			$result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
            $error_code = isset($resultArr['code']) ? $resultArr['code'] : '';
            $getReasonIdAndStatus = $this->getReasonIdAndStatus($error_code);
            $result['reason_id'] = $getReasonIdAndStatus['reason_id'];
            $result['transfer_status'] = $getReasonIdAndStatus['status'];
		}
        
		return array($success, $result);
	}

    public function updatePlayerInfo($playerName, $infos) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerInfo',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'infos' => $infos,
		);

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_UpdatePlayer;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);
        $status = isset($infos['status']) ? $infos['status'] : 0; //Player status, 0=NORMAL Non-0=Abnormal (not working properly)
        $isVip = isset($infos['is_vip']) ? $infos['is_vip'] : false; //Whether VIP, true=yes, false=no, is not currently enabled

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Signature' => $signature,
            'PlayerName' => $playerName,
            'Status' => $status,
            'IsVIP' => $isVip,
		);

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);
    }

    public function processResultForUpdatePlayerInfo($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
		];

		if($success) {
            $result['response_result'] = $resultArr;
		}

		return array($success, $result);
	}

    public function getLauncherLanguage($language) {
        switch($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $result = self::LANGUAGE_CODE['CHINESE'];
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vi':
            case 'vi-vn':
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-th':
                $result = self::LANGUAGE_CODE['THAI'];
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi-in':
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt-br':
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
            default:
                $result = self::LANGUAGE_CODE['ENGLISH'];
                break;
        }

        return $result;
	}

    public function queryForwardGame($playerName, $extra) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'extra' => $extra,
		);

        $sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = 'GetLoginH5';
        $loginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $loginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $loginId,
            'Signature' => $signature,
		);
        
		return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $extra = $this->getVariableFromContext($params, 'extra');
        $callbackUrl = $this->callbackUrl = $this->getSystemInfo('callbackUrl', isset($extra['home_link']) && !empty($extra['home_link']) ? $extra['home_link'] : $this->getHomeLink());
        $language = $this->getLauncherLanguage($this->language = $this->getSystemInfo('language', $extra['language']));
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $responseH5 = array(
            'CallBackUrl' => $callbackUrl,
            'Language' => $language,
		);

        $parametersBytes = json_encode($responseH5);
        $parametersValue = base64_encode($parametersBytes);

		$result = [
			'response_result_id' => $responseResultId,
            'url' => $this->game_url,
		];

		if($success && isset($resultArr['data']['loginUrl']) && !empty($resultArr['data']['loginUrl'])) {
            $result['loginUrl'] = $resultArr['data']['loginUrl'];
            $result['url'] .= $result['loginUrl'] . '&' . $parametersValue;
        }

        $this->CI->utils->debug_log(__METHOD__, 'GetLoginH5', $result);

		return array($success, $result);
	}

    public function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername,
		);

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_SetPlayerStatus;
        $LoginId = strtoupper($gameUsername);
        $status = 2; //0=Normal 2=Blocked
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Status' => $status,
            'Signature' => $signature,
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForBlockPlayer($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
		];

		if($success && !empty($resultArr['data'])) {
            $result['response_result'] = $resultArr['data'];
			$result['isBlocked'] = $this->blockUsernameInDB($gameUsername);
		}

		return array($success, $result);
	}

    public function unblockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername,
		);

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_SetPlayerStatus;
        $LoginId = strtoupper($gameUsername);
        $status = 0; //0=Normal 2=Blocked
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Status' => $status,
            'Signature' => $signature,
		);

		return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
		];

		if($success && !empty($resultArr['data'])) {
            $result['response_result'] = $resultArr['data'];
			$result['isUnblocked'] = $this->unblockUsernameInDB($gameUsername);
		}

		return array($success, $result);
	}

    public function logout($playerName, $password = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
            'password' => $password,
            'gameUsername' => $gameUsername,
		);

		$sn = $this->agent_serial_number;
        $id = $this->GUID;
        $method = self::METHOD_PlayerLogout;
        $LoginId = strtoupper($gameUsername);
        $signature = $this->signing($sn, $id, $method, $LoginId);

        $params = array(
			'SN' => $sn,
            'ID' => $id,
            'Method' => $method,
            'LoginId' => $LoginId,
            'Signature' => $signature,
		);

		return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        
		$result = [
			'response_result_id' => $responseResultId,
		];

		if($success) {
            $result['response_result'] = $resultArr;
		}
        
		return array($success, $result);
	}

    public function syncOriginalGameLogs($token) {
        $dateTimeFrom = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTimeModified = $dateTimeFrom->modify($this->getDatetimeAdjust());

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($startDateTimeModified->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

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

        $pageSize = $this->page_size;
        $pageIndex = 1;
       
        while($startDateTime <= $endDateTime) {
            $endDateTimeModified = (new DateTime($startDateTime))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
                'startDateTime' => $startDateTime,
                'endDateTimeModified' => $endDateTimeModified,
            );
    
            $sn = $this->agent_serial_number;
            $id = $this->GUID;
            $method = self::METHOD_GetGameRecordByTime;
            $signature = $this->signing($sn, $id, $method, null, null, $startDateTime, $endDateTimeModified);
    
            $params = array(
                'SN' => $sn,
                'ID' => $id,
                'Method' => $method,
                'StartTime' => $startDateTime,
                'EndTime' => $endDateTimeModified,
                'PageSize' => $pageSize,
                'PageIndex' => $pageIndex,
                'Signature' => $signature,
            );

            $this->CI->utils->debug_log(__METHOD__, 'startDateTime', $startDateTime, 'endDateTimeModified', $endDateTimeModified, 'PageSize', $pageSize, 'PageIndex', $pageIndex);
            
            $gameRecords = $this->callApi(self::API_syncGameRecords, $params, $context);

            if($gameRecords['success']) {

                if($gameRecords['totalRows'] >= $pageSize && $gameRecords['totalPage'] >= $pageIndex) {
                    $pageIndex++;
                    $startDateTime = (new DateTime($startDateTime))->modify($this->CI->utils->inverseDateTimeModification($this->sync_time_interval))->format('Y-m-d H:i:s');
                }else{
                    $pageIndex = 1;
                }

                $result['data_count'] += isset($gameRecords['data_count']) && !empty($gameRecords['data_count']) ? $gameRecords['data_count'] : 0;
                $result['data_count_insert'] += isset($gameRecords['data_count_insert']) && !empty($gameRecords['data_count_insert']) ? $gameRecords['data_count_insert']: 0;
                $result['data_count_update'] += isset($gameRecords['data_count_update']) && !empty($gameRecords['data_count_update']) ? $gameRecords['data_count_update'] : 0;
            }else{
                $result['data_count'] += 0;
                $result['data_count_insert'] += 0;
                $result['data_count_update'] += 0;
            }
            
            sleep($this->sleep_time);

            $startDateTime = (new DateTime($startDateTime))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return $result;
	}

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $startDateTime = $this->getVariableFromContext($params, 'startDateTime');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
         
        $result = [
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0,
            'startDateTime' => $startDateTime,
        ];

        if($success && isset($resultArr['data']) && !empty($resultArr['data'])) {
            $result['response_result_id'] = $responseResultId;
            $result['totalRows'] = isset($resultArr['data']['totalRows']) ? $resultArr['data']['totalRows'] : 0;
            $result['totalPage'] = isset($resultArr['data']['totalPage']) ? $resultArr['data']['totalPage'] : 0;
            $item = isset($resultArr['data']['item']) && !empty($resultArr['data']['item']) ? $resultArr['data']['item'] : [];
            $rebuildGameRecords = $this->rebuildGameRecords($item, $result);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_game_logs_table,
                $rebuildGameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows ----->', 'gamerecords', count($rebuildGameRecords), 'insertrows', count($insertRows), 'updaterows', count($updateRows), 'totalRows', $result['totalRows'], 'totalPage', $result['totalPage']);
            
            $result['data_count'] += is_array($rebuildGameRecords) ? count($rebuildGameRecords): 0;

            if(!empty($insertRows)) {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if(!empty($updateRows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
        }
       
        return array($success, $result);
    }

    private function rebuildGameRecords(&$gameRecords, $extra) {
        $rebuildGameRecords = [];

        if(!empty($gameRecords)) {
            foreach($gameRecords as $gameRecord) {
                $row['loginId'] = isset($gameRecord['loginId']) ? $gameRecord['loginId'] : null;
                $row['totalBet']= isset($gameRecord['totalBet']) ? $gameRecord['totalBet'] : 0;
                $row['totalWin'] = isset($gameRecord['totalWin']) ? $gameRecord['totalWin'] : 0;
                $row['betDetail'] = isset($gameRecord['betDetail']) ? $gameRecord['betDetail'] : null;
                $row['winDetail'] = isset($gameRecord['winDetail']) ? $gameRecord['winDetail'] : null;
                $row['orderId'] = isset($gameRecord['orderId']) ? $gameRecord['orderId'] : null;
                $row['actionDate'] = isset($gameRecord['actionDate']) ? $this->gameTimeToServerTime($gameRecord['actionDate']) : null;
                $row['creationDate'] = isset($gameRecord['creationDate']) ? $this->gameTimeToServerTime($gameRecord['creationDate']) : null;
                $row['gameName'] = !empty($gameRecord['gameName']) ? trim($gameRecord['gameName']) : null;
                $row['validCommission'] = !empty($gameRecord['validCommission']) ? $gameRecord['validCommission'] : 0;
                $row['validBet'] = !empty($gameRecord['validBet']) ? $gameRecord['validBet'] : 0;
                $row['validWin'] = !empty($gameRecord['validWin']) ? $gameRecord['validWin'] : 0;
                $row['mjpWin'] = !empty($gameRecord['mjpWin']) ? $gameRecord['mjpWin'] : 0;
                $row['mjpComm'] = !empty($gameRecord['mjpComm']) ? $gameRecord['mjpComm'] : 0;
                $row['response_result_id'] = $extra['response_result_id'];
                $row['external_uniqueid'] = isset($gameRecord['orderId']) ? $gameRecord['orderId'] : null;
                $rebuildGameRecords[] = $row;
            }
        }
        
        return $rebuildGameRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType) {
        $dataCount = 0;
        
        if(!empty($data)) {
            foreach($data as $record) {
                if($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_game_logs_table, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_game_logs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = false;
        
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime = "ogl.updated_at >= ? AND ogl.updated_at <= ?";

        if($use_bet_time) {
            $sqlTime = "ogl.actionDate >= ? AND ogl.actionDate <= ?";
        }

        $sql = <<<EOD
SELECT
    ogl.id AS sync_index,
    ogl.loginId AS username,
    ogl.totalBet,
    ogl.totalWin,
    ogl.betDetail,
    ogl.winDetail,
    ogl.orderId AS round_number,
    ogl.actionDate AS start_at,
    ogl.actionDate AS bet_at,
    ogl.creationDate AS end_at,
    ogl.gameName AS game_code,
    ogl.validCommission,
    ogl.validBet AS bet_amount,
    ogl.validWin AS win_amount,
    ogl.mjpWin,
    ogl.mjpComm,
    ogl.response_result_id,
    ogl.external_uniqueid,
    ogl.created_at,
    ogl.updated_at,
    ogl.md5_sum,
    game_provider_auth.login_name AS player_username,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_name AS game_description_name,
    game_description.game_type_id,
    game_description.english_name AS game
FROM
    {$this->original_game_logs_table} as ogl
    LEFT JOIN game_description ON ogl.gameName = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON ogl.loginId = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
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

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game'],
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount'            => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']),
                'result_amount'         => $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount']),
                'bet_for_cashback'      => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']),
                'real_betting_amount'   => $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']),
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => 0,
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at'],
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null,
            ],
            'bet_details' => [
                'Created At' => $this->CI->utils->getNowForMysql(),
                'Order ID' => $row['round_number'],
                'Total Bet' => $row['totalBet'],
                'Total Win' => $row['totalWin'],
                'Valid Commission' => $row['validCommission'],
                'MJP Win' => $row['mjpWin'],
                'MJP Commission' => $row['mjpComm'],
                'Bet Time' => $row['start_at'],
            ],
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row) {
        if(empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
        $row['note'] = $this->getGameStatusForNote($row['bet_amount'], $row['win_amount']);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;

        if(isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)) {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function getGameStatusForNote($bet_amount, $win_amount, $extra = null) {
        if($bet_amount > $win_amount) {
            $game_status_note = self::GAME_STATUS_RESULT['LOSE'];
        }elseif($bet_amount < $win_amount) {
            $game_status_note = self::GAME_STATUS_RESULT['WIN'];
        }elseif(!empty($bet_amount) && !empty($win_amount) && $bet_amount == $win_amount) {
            $game_status_note = self::GAME_STATUS_RESULT['DRAW'];
        }else{
            $game_status_note = 'Free Game (Entered the game without betting)';
        } 

        return $game_status_note;
    }

    # run command: sudo ./command.sh syncOriginalGameLogsFromExcel '6084'
    public function syncOriginalGameLogsFromExcel($isUpdate = true) {
        set_time_limit(0);
        $this->CI->load->model(array('external_system', 'original_game_logs_model'));
        require_once dirname(__FILE__) . '/../../../admin/application/libraries/phpexcel/PHPExcel.php';

        $game_logs_path = $this->getSystemInfo('km_game_records_path');
        $directory = $game_logs_path;
        $km_game_logs_excel = array_diff(scandir($directory), array('..', '.'));

        $header = [
            'A'=>'id',
            'B'=>'loginId',
            'C'=>'totalBet',
            'D'=>'totalWin',
            'E'=>'betDetail',
            'F'=>'winDetail',
            'G'=>'orderId',
            'H'=>'actionDate',
            'I'=>'creationDate',
            'J'=>'gameName',
            'K'=>'validCommission',
            'L'=>'validBet',
            'M'=>'validWin',
            'N'=>'mjpWin',
            'O'=>'mjpComm',
            'P'=>'response_result_id',
            'Q'=>'external_uniqueid',
            'R'=>'created_at',
            'S'=>'updated_at',
            'T'=>'md5_sum',
        ];

        $count = 0;
        $excel_data = [];
        $result = [
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0,
        ];
        if (!empty($km_game_logs_excel)) {
            foreach ($km_game_logs_excel as $file_name) {
                $file = explode(".", $file_name);
                $obj_php_excel = PHPExcel_IOFactory::load($directory . "/" . $file_name);
                $cell_collection = $obj_php_excel->getActiveSheet()->getCellCollection();

                foreach ($cell_collection as $cell) {
                    ini_set('memory_limit', '-1');
                    $column = $obj_php_excel->getActiveSheet()->getCell($cell)->getColumn();
                    $row = $obj_php_excel->getActiveSheet()->getCell($cell)->getRow();
                    $data_value = $obj_php_excel->getActiveSheet()->getCell($cell)->getValue();

                    if ($row == 1) continue;

                    if ($header[$column] == 'actionDate' || $header[$column] == 'creationDate') {
                        $excel_data[$row][$header[$column]] = date('Y-m-d H:i:s', strtotime($data_value));
                    } else {
                        $excel_data[$row][$header[$column]] = $data_value;
                    }
                }

                $result = $this->getInsertAndUpdateRowsForOGL(
                    $this->original_game_logs_table, $excel_data,
                    self::MD5_FIELDS_FOR_SYNC_FROM_EXCEL,
                    self::MD5_FLOAT_AMOUNT_FIELDS_FOR_SYNC_FROM_EXCEL,
                    $result
                );
            }

            /* if (!empty($excel_data)) {
                foreach ($excel_data as $record) {
                    // print_r($excel_data);
                    $count++;
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_game_logs_table, $record);
                }
            } */

            //$result = array('data_count' => $count);
            return array("success" => true, $result);
        }
    }

    public function getInsertAndUpdateRowsForOGL($original_game_logs_table, $gameRecords, $md5_fields, $md5_float_amount_fields, $result = []) {
        if (!empty($gameRecords)) {
            /* foreach ($excel_data as $record) {
                // print_r($excel_data);
                $count++;
                $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_game_logs_table, $record);
            } */

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $original_game_logs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                $md5_fields,
                'md5_sum',
                'id',
                $md5_float_amount_fields
            );

            $this->CI->utils->debug_log('after process available rows ----->', 'gamerecords', count($gameRecords), 'insertrows', count($insertRows), 'updaterows', count($updateRows));
            
            $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

            if(!empty($insertRows)) {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if(!empty($updateRows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
        }

        return $result;
    }
}

/*end of file*/