<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: MPoker
* Game Type: Chess Game
* Wallet Type: Transfer
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @emil.php.ph

**/

class Game_api_mpoker extends Abstract_game_api
{

    public $api_url;
    public $agent;
    public $desKey;
    public $md5Key;
    public $lineCode;
    public $timeStamp;
    public $sync_time_interval;
    public $sync_sleep_time;

    const HTTP_METHOD_GET = 'GET';
    const CREATE_PLAYER = 0;

    const URI_MAP = [
        self::API_createPlayer => 'channelHandle',
        self::API_login => 'channelHandle',
        self::API_depositToGame => 'channelHandle',
        self::API_withdrawFromGame => 'channelHandle',
        self::API_queryPlayerBalance => 'channelHandle',
        self::API_queryTransaction => 'channelHandle',
        self::API_syncGameRecords => 'getRecordHandle',
    ];

    const SUBTYPE_OPERATION = [
        "LAUNCH_GAME" => 0,
        "DEPOSIT" => 2,
        "WITHDRAW" => 3,
        "BALANCE" => 7,
        "STATUS" => 4,
        "SYNC_GAMELOGS" => 6
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'CellScore',
        'AllBet',
        'Profit',
        'Revenue',
        'NewScore',
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'GameID',
        'Accounts',
        'ServerID',
        'KindID',
        'TableID',
        'ChairID',
        'UserCount',
        'CardValue',
        'CellScore',
        'AllBet',
        'Profit',
        'Revenue',
        'NewScore',
        'GameStartTime',
        'GameEndTime',
        'ChannelID',
        'LineCode'
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


    //** error codes of MPOKER API */
    const NO_DATA = -1;
    const SUCCESS_CODE = 0;
    const TOKEN_LOST = 1;
    const VERIFICATION_TIMEOUT = 3;
    const CHANNEL_WHITELIST_ERROR = 5;
    const VERIFICATION_CODE_LOST = 6;
    const REQUEST_NOT_EXIST = 8;
    const CHANNEL_VERIFICATION_ERROR = 15;
    const DATA_NOT_EXIST = 16;
    const ACCOUNT_PROHIBITED = 20;
    const AES_DECRYPTION_FAILED = 22;
    const BEYOND_TIME_RANGE = 24;
    const ORDER_NOT_EXIST  =26;
    const DB_ERROR = 27;
    const IP_PROHIBITED = 28;
    const ORDER_DOES_NOT_MATCH_ORDER_RULES = 29;
    const SMALLER_OR_BIGGER_ERROR = 31;
    const DUPLICATE_ORDER = 34;
    const FAILED_PLAYER_INFO = 35;
    const NOT_ENOUGH_BALANCE = 38;
    const AGENT_PROHIBITED = 42;
    const FREQUENT_ORDER_PULLING = 43;
    const PROCESSING_ORDER = 44;
    const WALLET_OPERATION_FAILED = 137;
    const INSUFFICIENT_BALANCE = 138;
    const INVALID_ARGUMENTS = 139;
    const WALLET_DISABLED = 140;
    const AGENT_DISABLED = 142;
    const INSUFFICIENT_BALANCE_FOR_AGENT = 143;
    const INVALID_WALLET_REQUEST = 144;
    const INVALID_WALLET_ENCRYPTION = 145;
    const FREQUENT_WALLET_REQUEST = 146;

    const START_PAGE = 1;

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->agent = $this->getSystemInfo('agent');
        $this->md5Key = $this->getSystemInfo('md5Key', 'C4E65240C14062E5');
        $this->lineCode  = $this->getSystemInfo('linecode', 'mpoker2022');
        $this->timeStamp = $this->CI->utils->getTimestampNow();
        $this->game_record_url = $this->getSystemInfo('game_record_url');
        $this->language = $this->getSystemInfo('language', '');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 1);
        $this->prefix_generated = $this->getSystemInfo('prefix_generated', '300213_');
        $this->returnType = $this->getSystemInfo('returnType', '1'); // 0 default, 1 enable, 2 disable
        $this->originalTable = 'mpoker_game_logs';

        // for encryption
        $this->desKey = $this->getSystemInfo('desKey', '5D762FA7536D670E');
        $this->secret_encryption_iv = $this->getSystemInfo('secret_encryption_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-128-ECB');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+30 minutes'); //minutes/hours/days
    }

    public function getPlatformCode()
    {
        return MPOKER_GAME_API;
    }

    private function pkcs7_pad($data, $blocksize)
    {
        $pad = $blocksize - (strlen($data) % $blocksize);
        return $data .= str_repeat(chr($pad), $pad);
    }

    private function encrypt($data, $key)
    {   
        $output = false;

        $encrypt = openssl_encrypt($data, $this->encrypt_method, $this->desKey, OPENSSL_RAW_DATA);
        $output = base64_encode($encrypt);

        return $output;
    }
    
    public function generateUrl($apiName, $params)
    {
        $apiUri = self::URI_MAP[$apiName];

        if($apiName == self::API_syncGameRecords){
            $url = $this->game_record_url . $apiUri . '?' . http_build_query($params);
        } else {
		    $url = $this->api_url . $apiUri . '?' . http_build_query($params);
        }

		return $url;
    }



    public function processResultBoolean($responseResultId, $resultArr, $playerName) {
        $this->CI->utils->debug_log('processResultBoolean ===========>',$resultArr);

		$success = false;

        if(isset($resultArr['d']['code']) && $resultArr['d']['code'] === self::SUCCESS_CODE)
        {
            $success = true;
        }

        if (!$success)
        {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('MPOKER API got error ', $responseResultId, 'result', $resultArr);
		}

		return $success;
	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $time_str = $this->timestamp_str('YmdHis', '');
        $orderId = $this->agent . $time_str . $gameUsername;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );
        $this->CI->utils->debug_log(' createPlayer context', $context);

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = "s=".self::CREATE_PLAYER."&account=".$gameUsername."&money=0&orderid=".$orderId."&ip=".$this->CI->utils->getIP()."&lineCode=".$this->lineCode."&KindID=0";

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;

        $this->CI->utils->debug_log(__METHOD__, 'MPoker Game createPlayer', 'params', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);

    }

    public function processResultForCreatePlayer($params){
        $this->CI->utils->debug_log('processResultForCreatePlayer params',$params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $this->CI->utils->debug_log('processResultForCreatePlayer responseResultId',$responseResultId);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForCreatePlayer resultArr',$resultArr);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $this->CI->utils->debug_log('processResultForCreatePlayer gameUsername',$gameUsername);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $this->CI->utils->debug_log('processResultForCreatePlayer playerId',$playerId);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $this->CI->utils->debug_log('processResultForCreatePlayer success',$success);

        $result = array(
            "player" => $gameUsername,
            "success" => $success
        );

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result["exists"] = true;
        }

        $this->CI->utils->debug_log('processResultForCreatePlayer ===========>',$resultArr);
        return array($success, $result);
    }

    public function getLauncherLanguage($language)
    {
        $lang='';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
                $lang = 'en-us'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
                $lang = 'zh-cn'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
                $lang = 'ind'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
                $lang = 'vie'; // vietnamese
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th':
                $lang = 'th'; // thai
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi-hi':
            case 'hi':
                $lang = 'hi';
                break;
            default:
                $lang = 'th'; // default as th as per provider api docs
                break;
        }
        return $lang;
	}

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $amount = $this->dBtoGameAmount($amount);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $time_str = $this->timestamp_str('YmdHis', '');
        $orderId = $this->agent . $time_str . $gameUsername;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $orderId,
        );

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = "s=".self::SUBTYPE_OPERATION["DEPOSIT"]."&account=".$gameUsername."&money=".$amount."&orderid=".$orderId;

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;
        $this->CI->utils->debug_log(__METHOD__, 'MPoker Game Deposit', 'params', $params);

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $this->CI->utils->debug_log('processResultForDepositToGame success', $success);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success
        );

        if($resultArr['d']['code'] == self::SUCCESS_CODE)
        {
            $result['reason_id'] = self::REASON_UNKNOWN;
        	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $amount = $this->dBtoGameAmount($amount);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $time_str = $this->timestamp_str('YmdHis', '');
        $orderId = $this->agent . $time_str . $gameUsername;
        

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $orderId,
        );

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = "s=".self::SUBTYPE_OPERATION["WITHDRAW"]."&account=".$gameUsername."&money=".$amount."&orderid=".$orderId;

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;
        $this->CI->utils->debug_log(__METHOD__, 'MPoker Game Withdraw', 'params', $params);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawToGame($params)
    {
        $playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success
        );

        if($resultArr['d']['code'] == self::SUCCESS_CODE)
        {
            $result['reason_id'] = self::REASON_UNKNOWN;
        	$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $time_str = $this->timestamp_str('YmdHis', '');
        $orderId = $this->agent . $time_str . $gameUsername;
        

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = "s=".self::SUBTYPE_OPERATION["BALANCE"]."&account=".$gameUsername;

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;
        $this->CI->utils->debug_log(__METHOD__, 'MPoker Game queryPlayerBalance', 'params', $params);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params)
    {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

		$result = array();

		if($success){
            $balance = floatval($resultArr['d']['totalMoney']);
            $result['balance'] = $this->gameAmountToDB($balance);
        }

        $this->CI->utils->debug_log('processResultForQueryPlayerBalance ===========>',$resultArr);
		return array($success, $result);
	}

    public function login($playerName, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $time_str = $this->timestamp_str('YmdHis', '');
        $orderId = $this->agent . $time_str . $gameUsername;

        $lang = '';

        if (!empty($this->language)) {
            $lang = $this->language;
        } else {
            $lang = $this->getLauncherLanguage(!empty($extra['language']) ? $extra['language'] : null);
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername,
            'lang' => $lang
        );

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = http_build_query([
            's' => self::SUBTYPE_OPERATION["LAUNCH_GAME"],
            'account' => $gameUsername,
            'money' => 0, // as per provider the balance of the money should be 0 for login
            'orderid' => $orderId,
            'ip' => $this->CI->utils->getIP(),
            'lineCode' => $this->lineCode,
            'KindID' => !empty($extra['game_code']) ? $extra['game_code'] : 0, // 0 = lobby
        ]);

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;

        $this->http_method = self::HTTP_METHOD_GET;
        $this->CI->utils->debug_log(__METHOD__, 'MPoker Game Login', 'params', $params);

        return $this->callApi(self::API_login, $params, $context);

    }

    public function processResultForLogin($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForLogin resultArr', $resultArr);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $language = $this->getVariableFromContext($params, 'lang');
        $returnType = $this->getVariableFromContext($params, 'returnType');
        $result = array();
;
        if($success && isset($resultArr['d']['url'])){

                $result['url'] = $resultArr['d']['url'];

        }
        $this->CI->utils->debug_log('processResultForLogin konichiwa', $success);

        return array($success, $result);

    }

    public function queryForwardGame($playerName, $extra)
    {
        $result = $this->login($playerName);
        $this->CI->utils->debug_log('queryForwardGame konichiwa', $result);
        
        return array("success" => true, "url" => $result['url']);
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }


    public function queryTransaction($transactionId, $extra)
    {
        $this->CI->utils->debug_log('tinder', $transactionId);
        $playerName = $extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $time_str = $this->timestamp_str('YmdHis', '');
        $orderId = $this->agent . $time_str . $gameUsername;
        
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $transactionId
        );

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = "s=".self::SUBTYPE_OPERATION["STATUS"]."&orderid=".$transactionId;

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;
        $this->CI->utils->debug_log(__METHOD__, 'MPoker Game queryTransaction', 'params', $params);

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params)
    {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id'        => $responseResultId,
            'external_transaction_id'   => $external_transaction_id,
            'status'                    => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'                 => self::REASON_UNKNOWN
        );

		if ($success) {

            switch ($resultArr['d']['status']) {
                case '0':
                    $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                    break;
                case '-1':
                    $result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
                    $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
                    break;
                case '2':
                    $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
                    $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
                    break;
            }

        }

        $this->CI->utils->debug_log('processResultForQueryTransaction ===========> tinder',$resultArr);
        return array($success, $result);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $game_logs_table = $this->originalTable;

        $sqlTime="{$game_logs_table}.created_at >= ? and {$game_logs_table}.created_at <= ?";

        if($use_bet_time){
            $sqlTime="{$game_logs_table}.GameStartTime >= ? and {$game_logs_table}.GameStartTime <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table}.id AS sync_index,
    {$game_logs_table}.GameID,
    {$game_logs_table}.Accounts,
    {$game_logs_table}.ServerID,
    {$game_logs_table}.KindID AS game_code,
    {$game_logs_table}.TableID,
    {$game_logs_table}.ChairID,
    {$game_logs_table}.UserCount,
    {$game_logs_table}.CardValue,
    {$game_logs_table}.CellScore AS valid_bet_amount,
    {$game_logs_table}.AllBet AS bet_amount,
    {$game_logs_table}.Profit AS result_amount,
    {$game_logs_table}.Revenue AS revenue,
    {$game_logs_table}.NewScore AS after_balance,
    {$game_logs_table}.GameStartTime AS start_at,
    {$game_logs_table}.GameEndTime AS end_at,
    {$game_logs_table}.ChannelID,
    {$game_logs_table}.LineCode,

    {$game_logs_table}.external_uniqueid,
    {$game_logs_table}.response_result_id,
    {$game_logs_table}.created_at,
    {$game_logs_table}.updated_at,
    {$game_logs_table}.md5_sum,
    game_description.id as game_description_id,
    game_description.game_type_id,
    game_provider_auth.player_id
    FROM
    {$game_logs_table}
    LEFT JOIN game_description ON {$game_logs_table}.KindID = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON {$game_logs_table}.Accounts = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
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

    public function syncMPokerGameLogs($startDate,$endDate)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncMPokerGameLogs',
            'startDate' => $startDate,
            'endDate' => $endDate
        );

        $params = [
            'agent' => $this->agent,
            'timestamp' => $this->microtime_int(),
            'key' => md5($this->agent . $this->microtime_int() . $this->md5Key),
        ];

        $paramsToBuild = http_build_query([
            's' => self::SUBTYPE_OPERATION["SYNC_GAMELOGS"],
            'startTime' => date('U', strtotime($startDate)) * 1000,
            'endTime' => date('U', strtotime($endDate)) * 1000,
        ]);

        $paramsToProcess = $this->encrypt($paramsToBuild, $this->desKey);

        $params['param'] = $paramsToProcess;

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncMPokerGameLogs($params)
    {
        $this->CI->load->model(array('original_game_logs_model'));

        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $gameRecords = !empty($resultArr['d']['list']) ? $resultArr['d']['list'] : array();

        $result = [
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0
        ];

        if($success && !empty($gameRecords)){
            if (empty($gameRecords)) {
                return array($success, $result);
            }
            
            foreach ($resultArr['d']['list'] as $field => $rows) {
                $tempArr = [];
                foreach ($rows as $key => $row) {
                    if ( ! empty($gameRecords[$key])) {
                        $gameRecords[$key][$field] = $row;
                    }else{
                        $tempArr[$key][$field] = $row;
                        $gameRecords = $tempArr;
                    }
                }
            }

            $extra = ['response_result_id' => $responseResultId];
            $gameRecords = $this->rebuildGameRecords($gameRecords,$extra);
            $this->CI->utils->debug_log('dataResult gameRecords', $gameRecords);

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

            $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

            if (!empty($insertRows))
            {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', ['response_result_id'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows))
            {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', ['response_result_id'=>$responseResultId]);
            }
            unset($updateRows);
        }

        return array($success, $result);

    }

    public function syncOriginalGameLogs($token = false)
    {
        $this->CI->utils->debug_log('syncOriginalGameLogs token', $token);

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
            'sleep_time' => $this->sync_sleep_time
        ];

        while ($startDateTime <= $endDateTime) {
            $endDateTimeModified = (new DateTime($startDateTime))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
            $api_result = $this->syncMPokerGameLogs($startDateTime, $endDateTimeModified);

            if ($api_result['success']) {
                $result['data_count'] += isset($api_result['data_count']) && !empty($api_result['data_count']) ? $api_result['data_count'] : 0;
                $result['data_count_insert'] += isset($api_result['data_count_insert']) && !empty($api_result['data_count_insert']) ? $api_result['data_count_insert']: 0;
                $result['data_count_update'] += isset($api_result['data_count_update']) && !empty($api_result['data_count_update']) ? $api_result['data_count_update'] : 0;
            }

            $this->utils->info_log('<--------------- syncOriginalGameLogs --------------->', [
                'startDateTime' => $startDateTime,
                'endDateTimeModified' => $endDateTimeModified,
                'sync_sleep_time' => $this->sync_sleep_time,
                'result' => $result,
            ]);

            sleep($this->sync_sleep_time);

            $startDateTime = (new DateTime($startDateTime))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return $result;
    }

    public function rebuildGameRecords($gameRecords, $extra)
    {
        if(isset($gameRecords['GameID'])){
            str_replace("-", "", $gameRecords['GameID']);
        }

        foreach($gameRecords as $key => $gameRecord)
        {
            $data['GameID'] = isset($gameRecord['GameID']) ? $gameRecord['GameID'] : NULL;
            $data['Accounts'] = isset($gameRecord['Accounts']) ? ltrim($gameRecord['Accounts'], $this->prefix_generated) : NULL;
            $data['ServerID'] = isset($gameRecord['ServerID']) ? $gameRecord['ServerID'] : NULL;
            $data['KindID'] = isset($gameRecord['KindID']) ? $gameRecord['KindID'] : NULL;
            $data['TableID'] = isset($gameRecord['TableID']) ? $gameRecord['TableID'] : NULL;
            $data['ChairID'] = isset($gameRecord['ChairID']) ? $gameRecord['ChairID'] : NULL;
            $data['UserCount'] = isset($gameRecord['UserCount']) ? $gameRecord['UserCount'] : NULL;
            $data['CardValue'] = isset($gameRecord['CardValue']) ? $gameRecord['CardValue'] : NULL;
            $data['CellScore'] = isset($gameRecord['CellScore']) ? $gameRecord['CellScore'] : NULL;
            $data['AllBet'] = isset($gameRecord['AllBet']) ? $gameRecord['AllBet'] : NULL;
            $data['Profit'] = isset($gameRecord['Profit']) ? $gameRecord['Profit'] : NULL;
            $data['Revenue'] = isset($gameRecord['Revenue']) ? $gameRecord['Revenue'] : NULL;
            $data['NewScore'] = isset($gameRecord['NewScore']) ? $gameRecord['NewScore'] : NULL;
            $data['GameStartTime'] = isset($gameRecord['GameStartTime']) ? $gameRecord['GameStartTime'] : NULL;
            $data['GameEndTime'] = isset($gameRecord['GameEndTime']) ? $gameRecord['GameEndTime'] : NULL;
            $data['ChannelID'] = isset($gameRecord['ChannelID']) ? $gameRecord['ChannelID'] : NULL;
            $data['LineCode'] = isset($gameRecord['LineCode']) ? $gameRecord['LineCode'] : NULL;

            $data['external_uniqueid'] = isset($gameRecord['GameID']) ? $gameRecord['GameID'] : NULL;
            $data['response_result_id'] = $extra["response_result_id"];
            $dataRecords[] = $data;
        }

        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType,  $additionalInfo=[])
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

    public function syncMergeToGameLogs($token) {
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            false);
	}

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
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

        $extra = [
            'table' => $row['TableID']
        ];

        $betAmount = isset($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0;
        $validBetAmount = isset($row['valid_bet_amount']) ? $this->gameAmountToDB($row['valid_bet_amount']) : 0;
        if($this->utils->compareResultFloat($validBetAmount, '<=', 0)){
            #$validBetAmount = $betAmount;
        }

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game_code']) ? $row['game_code'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['Accounts']) ? $row['Accounts'] : null
            ],
            'amount_info' => [
                'bet_amount'            => $validBetAmount,
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => $validBetAmount,
                'real_betting_amount'   => $betAmount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => isset($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0,
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at'                => $end_at,
                'bet_at'                => isset($row['start_at']) ? $row['start_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
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
            'extra' => $extra,
            // existing game logs
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        $this->CI->utils->debug_log('MPOKER (preprocessOriginalRowForGameLogs)');
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_code']);
        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    private function timestamp_str($format, $timezone = null)
    {
        $micro_date = microtime();
        $date_array = explode(" ",$micro_date);
        // $this->set_timezone($timezone);
        return date($format,$date_array[1]);
    }

    private function microtime_int()
    {
        return (int)(microtime(true) * 1000);
    }

}
//end of class
