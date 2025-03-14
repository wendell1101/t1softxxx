<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/asiastar_api_utils.php';

/**
    * API NAME: Asiastar
    * Hash: MD5
    * Encryption: AES/ECB/PKCS5
    * Ticket No: OGP-14543
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @author   Pedro P. Vitor Jr.
 */

abstract class Abstract_game_api_common_asiastar extends Abstract_game_api {
    use asiastar_api_utils;

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const TRANSFER_IN = 'IN';
    const TRANSFER_OUT = 'OUT';
    const SUCCESS_CODE = 0;
    const GAME_METHOD = 'pcs'; 
    const API_confirmTransferCredit = "confirmTransferCredit";
    const API_getRoundResult = "getRoundResult";

	const MD5_FIELDS_FOR_ORIGINAL = [
        'gmcode',
        'billno',
        'productid',
        'agcode',
        'currency',
        'cur_ip',
        'remark',
        'tablecode',
        'gametype',
        'reckontime',
        'billtime',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'playtype',
        'account',
        'cus_account',
        'valid_account',
        'basepoint',
        'flag',
        'devicetype',
    ];
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'gmcode',
        'billno',
        'productid',
        'agcode',
        'currency',
        'tablecode',
        'cur_ip',
        'remark',
        'gametype',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'playtype',
        'account',
        'cus_account',
        'valid_account',
        'basepoint',
        'flag',
        'devicetype',
    ];

    public function __construct() {
        parent::__construct();
        $this->method   = self::METHOD_POST;
        $this->cipher = MCRYPT_RIJNDAEL_128;
        $this->mode = MCRYPT_MODE_ECB;
        $this->pad_method = NULL;
        $this->iv = '';

        $this->api_url  = $this->getSystemInfo('gi','https://asgi.as1886.com');
        $this->game_url = $this->getSystemInfo('gci','https://asgci.as1886.com');
        $this->agent    = $this->getSystemInfo('agent','eT11real');
        $this->md5key   = $this->getSystemInfo('md5key','0af318881c3a576a88336ae9511bf737'); //Encrypted (md5) of the agent password Dk93mg6Zn40L
        $this->prefix   = $this->getSystemInfo('prefix_for_username');
        $this->total_num_records = $this->getSystemInfo('total_num_records','10000');

        #gameURL callback
//        $this->url_wd = $this->getSystemInfo('url_wd','https://www.google.com');    //withdrawal url
//        $this->url_dp = $this->getSystemInfo('url_dp','https://www.google.com');    //deposit url
//        $this->callbackUrl= $this->getSystemInfo('callbackUrl','http://admin.og.local/callback/game/5590');
//        $this->forward_url= $this->getSystemInfo('withdraw','https://www.google.com');

        // This is use for generating API Call Header since colon (:) on 'http:' got confusing upon json_encode
        $this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
        $this->callback_url = '/callback/game/5590';
        $this->url_withdrawal = $this->getSystemInfo('url_wd','https://www.google.com');    //withdrawal url
        $this->url_deposit = $this->getSystemInfo('url_dp','https://www.google.com');       //deposit url


        # fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 0);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 17);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 6);
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

	    $this->URI_MAP = array(
	        self::API_createPlayer => '/checkAndCreateAccount',
	        self::API_queryPlayerBalance => '/getBalance',
	        self::API_depositToGame => '/prepareTransferCredit',
	        self::API_withdrawFromGame => '/prepareTransferCredit',
            self::API_confirmTransferCredit => '/confirmTransferCredit',
            self::API_queryTransaction => '/getTransferStatus',
	        self::API_queryForwardGame => '/gameEntrance',
	        self::API_syncGameRecords => '/getOrders',
            self::API_login => '/userLogin',
            self::API_getRoundResult => '/getRoundsResult',
            self::API_changePassword => '/changePassword',
	    );

    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

	public function generateUrl($apiName, $params) {
        $apiUri = $this->URI_MAP[$apiName];
        $url=$this->api_url . $apiUri . '?agent=' . $this->agent;

        $this->debug_log('ASIASTAR GeneratedUrl: '.$apiName, $url);
        return $url;
	}

    protected function customHttpCall($ch, $params) {
        switch ($this->method){
            case self::METHOD_POST:
                $this->require_pkcs5();
                $eparams = $this->encrypt($params);
                $this->utils->debug_log("ASIASTAR: (customHttpCall) method: ", $this->method, 'Params:', json_decode($params), 'Encrypted Params:', $eparams);

                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $eparams);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));

                break;
        }

    }

    public function callback($result = null, $method = null) {
        $data = array();
        $forward_url = $this->url_withdrawal;

        if(!empty($result)) {
            if ($method == "wd") {
                $forward_url = $this->url_withdrawal;
            }elseif ($method == "dp") {
                $forward_url = $this->url_deposit;
            }elseif ($method == "pcs") {
                $forward_url = $this->current_domain . $this->callback_url;
            }

            $data = array(
                "success" => self::SUCCESS_CODE,
                "forward_url" => $forward_url
            );
        }

        $this->CI->utils->debug_log('**********************ASIASTAR:', $data['success']);
        return $data;
    }

    public function processResultBoolean($responseResultId, $resultArr = NULL, $statusCode) {
        $success = false;
        if(@$statusCode == 200 || @$statusCode == 201){ 
            $success = true;
            $this->CI->utils->debug_log('ASIASTAR API CALL SUCCESS:', $responseResultId,'result', $resultArr);
        }

        if(!$success) { 
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('ASIASTAR got error ', $responseResultId,'result', $resultArr);            
        }

        $this->CI->utils->debug_log('**********************ASIASTAR:', $success);
        return $success;
    }

    public function processResultXml($responseResultId, $resultXml, $errArr, $info_must_be_1=false) {
        $success = true;
        $info = $resultXml; 

        if (in_array($info, $errArr)) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('ASIASTAR got error', $responseResultId, 'result', $resultXml);
            $success = false;
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        $extra = [
            'prefix' => $this->prefix_for_username,
            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true,
            'strict_username_with_prefix_length' => true,
            'force_lowercase' => false
        ];

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $timestamp = $this->microtime_int();

        $params = json_encode([
            'timestamp' => $timestamp,
            'userName' => $gameUsername,
            'password' => $password
        ]);

        $this->CI->utils->debug_log('---------- ASIASTAR createPlayer params ----------', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);

        $playerName = $this->getVariableFromContext($params, 'gameUsername');

        $success = ($resultArr['code']==self::SUCCESS_CODE) ? true : false;

        return array($success, $resultArr);
    }

	protected function convertXmlToArray($resultXml) {
		$result = json_decode(json_encode($resultXml), TRUE);
		return $result;
	}

    public function isPlayerExist($playerName) {
        $result = $this->queryPlayerBalance($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);

        $this->CI->utils->debug_log('---------- ASIASTAR result for isPlayerExist ----------', $result);
        $success = false;
        if(array_key_exists('exists', $result) && $result['exists'] === true) {
            $success = true;
        }
        return array($success,$result);
    }


    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $apiType = self::API_queryPlayerBalance;        

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
        );

        $timestamp = $this->microtime_int();

        $params = json_encode([
            'timestamp' => $timestamp,
            'userName' => $gameUsername
        ]);

        $this->CI->utils->debug_log('---------- ASIASTAR queryPlayerBalance ----------', $params);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = (array)$this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);

        $result = ['response_result_id'=>$responseResultId];

        if($resultArr['code']==self::SUCCESS_CODE){
            if(isset($resultXml['balance']) && !empty($resultXml['balance'])){
                $result['balance'] = $resultXml['balance']; 
            }else{
                $result['balance'] = 0;
            }
        }

        $success = ($resultArr['code']==self::SUCCESS_CODE) ? true : false;

        $this->CI->utils->debug_log('---------- ASIASTAR result for queryPlayerBalance ----------', $success);

        return array($success, $result, $resultArr);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $billno = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;
        $timestamp = $this->microtime_int();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForFundTransfer',
            'gameUsername' => $gameUsername,
            'billno'=> $billno,
            'action' => self::TRANSFER_IN,
            'apiType' => self::API_depositToGame,
            'amount' => $amount
        );

        $params = json_encode([
            'timestamp' => $timestamp,
            'userName' => $gameUsername,
            'billno' => $billno,
            'action' => self::TRANSFER_IN,
            'credit' => $amount
        ]);

        $this->CI->utils->debug_log('---------- ASIASTAR params for depositToGame ----------', $params);

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $billno = empty($transfer_secure_id) ? $this->generateSerialNo() : $transfer_secure_id;
        $timestamp = $this->microtime_int();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForFundTransfer',
            'gameUsername' => $gameUsername,
            'billno'=> $billno,
            'action' => self::TRANSFER_OUT,
            'apiType' => self::API_withdrawFromGame,
            'amount' => $amount
        );

        $params = json_encode([
            'timestamp' => $timestamp,
            'userName' => $gameUsername,
            'billno' => $billno,
            'action' => self::TRANSFER_OUT,
            'credit' => $amount
        ]);

        $this->CI->utils->debug_log('---------- ASIASTAR params for withdrawFromGame ----------', $params);
        
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForFundTransfer($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $billno = $this->getVariableFromContext($params, 'billno');
        $action = $this->getVariableFromContext($params, 'action');
        $apiType = $this->getVariableFromContext($params, 'apiType');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $billno,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];
        
        $prepareTransferResultDetails = [
            'success' => ($resultArr['code']==self::SUCCESS_CODE) ? true : false,
            'api_type' => $apiType,
            'game_username' => $gameUsername,
            'transfer_type' => $action
        ];

        $prepareTransferResultSuccess = $this->processResultBoolean($responseResultId, $prepareTransferResultDetails, $statusCode);

        $fundTransferSuccess = false;
        if($prepareTransferResultSuccess) {
            $playerInfo = [
                'gameUsername' => $gameUsername,
                'billno' => $billno,
                'action' => $action,
                'amount' => $amount
            ];

            $confirmTransferResultSuccess = $this->confirmTransferCredit($playerInfo,$billno,$action,$apiType);
            
            if($confirmTransferResultSuccess) {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs'] = true;
                $fundTransferSuccess = true;
                return [$fundTransferSuccess,$result];
            }
        }

        $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        $result['reason_id'] = $this->getReasons($statusCode);
        return [$fundTransferSuccess,$result];    
    }

    public function confirmTransferCredit($playerInfo,$billNo,$action,$apiType) {
        $apiType = $apiType;
        $timestamp = $this->microtime_int();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmTransfer',
            'gameUsername' => $playerInfo['gameUsername'],
            'apiType' => $apiType,
            'action' => $playerInfo['action'],
        ];

        $params = json_encode([
            'timestamp' => $timestamp,
            'userName' => $playerInfo['gameUsername'],
            'billno' => $playerInfo['billno'],
            'action' => $playerInfo['action'],
            'credit' => $playerInfo['amount']
        ]);

        $this->CI->utils->debug_log('---------- ASIASTAR Confirm Transfer Credit ----------', $params);

        return $this->callApi(self::API_confirmTransferCredit,$params,$context);
    }

    public function processResultForConfirmTransfer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $apiType = $this->getVariableFromContext($params, 'apiType');
        $action = $this->getVariableFromContext($params, 'action');

        $confirmTransferResultDetails = [
            'success' => ($resultArr['code']==self::SUCCESS_CODE) ? true : false,
            'api_type' => $apiType,
            'game_username' => $gameUsername,
            'transfer_type' => $action
        ];

        return $this->processResultBoolean($responseResultId,$confirmTransferResultDetails,$statusCode);
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case 'ACCESS_DENIED':
                $reasonCode = self::REASON_INVALID_KEY;
                break;
            case 'PLAYER_NOT_FOUND':
                $reasonCode = self::REASON_NOT_FOUND_PLAYER;
                break;
            case 'CURRENCY_MISMATCH':
                $reasonCode = self::REASON_CURRENCY_ERROR;
                break;
            case 'BAD_REQUEST':
                $reasonCode = self::REASON_INCOMPLETE_INFORMATION;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    public function queryForwardGame($playerName, $extra = null) {
        $resultArr = $this->login($playerName,null,$extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $timestamp = $this->microtime_int(); 

        $key = md5(self::GAME_METHOD.$gameUsername.$timestamp);

        if ($resultArr['code'] == self::SUCCESS_CODE) {
            $params = array(
                'method' => self::GAME_METHOD,
                'userName' => $gameUsername,
                'timestamp' => $timestamp,
                'key' => $key
            );

            $url = $this->game_url."/gameEntrance?agent=".$this->agent . '&type=h5&callbackUrl=' . $this->current_domain . $this->callback_url . '&ticket=' . $resultArr['body']['ticket'];

            $this->CI->utils->debug_log('---------- ASIASTAR generated Game URL ----------', $url);

            return ['success' => true,'url' => $url];
        }else {
            return ['success' => false,'url' => 'Cant generate Valid URL!'];
        }
    }

    private function generateSerialNo() {
        $dt = new DateTime($this->utils->getNowForMysql());
        return $dt->format('Ymd').random_string('numeric', 6);
    }

    public function login($playerName, $password = null, $extra = null) {
        $this->CI->load->model('game_provider_auth');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->CI->game_provider_auth->getPasswordByLoginName($gameUsername, $this->getPlatformCode());

        $timestamp = $this->microtime_int();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = json_encode([
            'timestamp' => $timestamp,
            'userName' => $gameUsername,
            'password' => $password
        ]);

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($resultArr['code'] == self::SUCCESS_CODE) {
            $ticketNo = $resultArr['body']['ticket'];
            $this->CI->utils->debug_log('---------- ASIASTAR Generated Ticket ----------', $ticketNo);                
        }else {
            $this->CI->utils->debug_log('---------- ASIASTAR Invalid Ticket ----------');                
        }

        return array($success, $resultArr);
    }

    public function getRoundResult($begintime, $endtime, $gmcode) {
        $timestamp = $this->microtime_int();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForRounds'
        );

        $params = json_encode([
            'timestamp' => $timestamp,
            'begintime' => $begintime,
            'endtime' => $endtime,
            'gmcode' => $gmcode,
            'page' => 1, 
            'num' => $this->total_num_records
        ]);

        $this->CI->utils->debug_log('---------- ASIASTAR Round Result ----------', $params);
        return $this->callApi(self::API_getRoundResult, $params, $context);
    }

    public function processResultForRounds($params){
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);
        $result = array(); 

        if ($resultArr['code'] == self::SUCCESS_CODE) {
            if(!empty($resultArr)){
                $result = $resultArr['body']['datas'];

                $this->CI->utils->debug_log('---------- ASIASTAR Round Result ----------', $result);
            }
        }

        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $result = array();
        $result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+10 minutes', function($startDate, $endDate)  {
            $timestamp = $this->microtime_int(); 

            $startDate = $startDate->format('Y-m-d H:i:s');
            $endDate = $endDate->format('Y-m-d H:i:s');
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
            );

            $params = json_encode([
                'timestamp' => $timestamp,
                'begintime' => $startDate,
                'endtime' => $endDate,
                'page' => '1',
                'num'   => $this->total_num_records
            ]);

            $this->CI->utils->debug_log('-----------------------ASIASTAR syncOriginalGameLogs params ----------------------------',$params);
            return $this->callApi(self::API_syncGameRecords, $params, $context);

            return true;
        });

        return $result;
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model', 'player_model'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);

        $resultXml = (array)$this->getResultXmlFromParams($params);
        $arrayResult = $this->convertXmlToArray($resultXml);

        $success = $this->processResultBoolean($responseResultId, $arrayResult, $statusCode);

        $result = ['data_count' => 0];

        $gameRecords = isset($arrayResult['body']['datas']) ? $arrayResult['body']['datas'] : null;
        $gR = isset($arrayResult['body']['datas']) ? [$arrayResult['body']['datas']] : null;

        if (is_array($gameRecords) && array_key_exists('billno', $gameRecords)) {
            $gameRecords = $gR; // for Multiple Records
        }

        if($success && !empty($gameRecords)) {
            $this->processGameRecords($gameRecords, $responseResultId);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->ORIGINAL_LOGS_TABLE_NAME,
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

            if (!empty($insertRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);

        }

        return array($success, $result);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['billno'] = isset($record['billno']) ? $record['billno'] : null;
                $data['productid'] = isset($record['productid']) ? $record['productid'] : null;
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['agcode'] = isset($record['agcode']) ? $record['agcode'] : null;
                $data['gmcode'] = isset($record['gmcode']) ? $record['gmcode'] : null;
                $data['billtime'] = isset($record['billtime']) ? $this->gameTimeToServerTime($record['billtime']) : null;
                $data['reckontime'] = isset($record['reckontime']) ? $this->gameTimeToServerTime($record['reckontime']) : null;
                $data['playtype'] = isset($record['playtype']) ? $record['playtype'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['tablecode'] = isset($record['tablecode']) ? json_encode($record['tablecode']) : null;
                $data['gametype'] = isset($record['gametype']) ? $record['gametype'] : null;
                $data['cur_ip'] = isset($record['cur_ip']) ? $record['cur_ip'] : null;
                $data['account'] = isset($record['account']) ? $record['account'] : null;
                $data['cus_account'] = isset($record['cus_account']) ? $record['cus_account'] : null;
                $data['valid_account'] = isset($record['valid_account']) ? $record['valid_account'] : null;
                $data['basepoint'] = isset($record['BASEPOINT']) ? $record['BASEPOINT'] : null;
                $data['flag'] = isset($record['flag']) ? $record['flag'] : null;
                $data['devicetype'] = isset($record['devicetype']) ? $record['devicetype'] : null;
                $data['remark'] = isset($record['remark']) ? json_encode($record['remark']) : null;
                $data['revenue'] = isset($record['revenue']) ? $record['revenue'] : null;

                $data['response_result_id'] = $responseResultId;
                $data['playerid'] = 0;
                $data['external_uniqueid'] = $data['billno']; //add external_uniueid for og purposes
                $gameRecords[$index] = $data;

                unset($data);
            }
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->ORIGINAL_LOGS_TABLE_NAME, $record);
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

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime='asi.billtime >= ? and asi.reckontime <= ?';
        $sql = <<<EOD
SELECT
asi.id as sync_index,
asi.response_result_id,
asi.username as player_username,
asi.billno as round_number,
asi.productid,
asi.agcode as agent_code,
asi.gmcode,
asi.billtime as start_at,
asi.reckontime as end_at,
asi.billtime as bet_at,
asi.playtype,
asi.currency,
asi.tablecode,
asi.cur_ip,
asi.account as real_bet,
asi.cus_account as result_amount,
asi.valid_account as bet_amount,
asi.basepoint as after_balance,
asi.flag,
asi.devicetype,
asi.remark,
asi.external_uniqueid,
asi.md5_sum,
asi.gametype as game_code,
asi.gametype as game,
asi.betdetails,
asi.created_at,
asi.updated_at,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id,
gt.game_type

FROM $this->ORIGINAL_LOGS_TABLE_NAME as asi
LEFT JOIN game_description as gd ON asi.gametype = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON asi.username = game_provider_auth.login_name
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

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => $row['game_type'],
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['real_bet'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance']
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = Game_logs::STATUS_SETTLED;	
    }


    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        if (!empty($playerName)) {
            $timestamp = $this->microtime_int();
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForChangePassword',
                'playerName' => $playerName,
                'gameUsername' => $gameUsername,
                'playerId' => $playerId,
                'newPassword' => $newPassword,
            );

            $params = json_encode([
                'timestamp' =>  $timestamp,
                'userName' => $gameUsername,
                'password' => $newPassword
            ]);

            return $this->callApi(self::API_changePassword, $params, $context);
        }
        return $this->returnFailed('empty player name');
    }

    public function processResultForChangePassword($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params,'playerName');
        $newPassword = $this->getVariableFromContext($params,'newPassword');
        $playerId = $this->getVariableFromContext($params,'playerId');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);
        $result = ['response_result_id' => $responseResultId];

        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($resultArr['code'] == self::SUCCESS_CODE) {
            $this->CI->utils->debug_log('=========== ASIASTAR Change Password Success! ===========');                
            //sync password to game_provider_auth
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }
        
        return array($success, $result);
    }

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*public function getGameTimeToServerTime() {
        //return '+8 hours';
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*public function getServerTimeToGameTime() {
        //return '-8 hours';
    }*/

    public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $timestamp = $this->microtime_int();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId
        );

        $params = json_encode([
            'timestamp' => $timestamp,
            'billno' => $transactionId
        ]);


        $this->CI->utils->debug_log('---------- ASIASTAR params for queryTransaction ----------', $params);
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
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = $this->convertXmlToArray($resultXml);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => $this->getReasons($resultArr['code'])
        ];

        if ($resultArr['code']==self::SUCCESS_CODE) {
            $success = true;
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $success = false;
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    private function getReasons($reason_id) {
        switch ($reason_id) {
            case 0:
                return self::COMMON_TRANSACTION_STATUS_APPROVED;
                break;
            case 1:
                return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
            default:
                return self::COMMON_TRANSACTION_STATUS_DECLINED;
                break;
        }
    }

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
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
}
