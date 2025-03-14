<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
    * API NAME: DongSen
    * Hash: MD5
    * Encryption: AES-128-CBC
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @integrator @mccoy.php.ph
**/

abstract class Abstract_game_api_common_dongsen extends Abstract_game_api {

    // Fields in dongsen_esports_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL = [
        'userName',
        'userId',
        'projectId',
        'gameName',
        'gameId',
        'betAmount',
        'realAmount',
        'prize',
        'profit',
        'betTime',
        'settleTime',
        'oddType',
        'oddBet',
        'oddFinally',
        'matchFinishTime',
        'matchName',
        'leagueName',
        'isLive',
        'stat',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'betAmount',
        'realAmount',
        'prize',
        'profit',
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'real_bet',
        'result_amount',
        'after_balance',
        'round_number',
        'game_code',
        'game_name',
        'player_username',
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'real_bet',
        'result_amount',
        'after_balance',
    ];

    const API_URI_MAPS = [
        self::API_createPlayer => '/ext/createUser',
        self::API_queryPlayerBalance => '/ext/singleBalance',
        self::API_depositToGame => '/ext/transMoney',
        self::API_withdrawFromGame => '/ext/transMoney',
        self::API_queryTransaction => '/ext/checkTxnStat',
        self::API_syncGameRecords => '/sync/orderItems',
        self::API_queryForwardGame => '/ext/loginGame',
    ];

    const CODE_SUCCESS = 1;
    const TRANSFER_TO = 1;
    const TRANSFER_FROM = 2;
    const BET_FAILED = 0;
    const BET_ACCEPTED = 1;
    const BET_INPROGRESS = 2;
    const BET_WIN = 3;
    const BET_LOSE = 4;
    const BET_WINHALF = 5;
    const BET_LOSEHALF = 6;
    const BET_RETURN = 7;
    const BET_CANCELLED = 8;
    const BET_REJECTED = 9;

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->agentCode = $this->getSystemInfo('agentCode');
        $this->aesKey = $this->getSystemInfo('aesKey');
        $this->aesIv = $this->getSystemInfo('aesIv');
        $this->md5key = $this->getSystemInfo('md5key');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-128-CBC');
        $this->platform_ID = $this->getSystemInfo('platId');
        //$this->original_gamelogs_table=$this->getOriginalTable();
        $this->get_language = $this->getSystemInfo('get_language');
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {

        //append uri
        $url=$this->api_url.self::API_URI_MAPS[$apiName];
        $this->debug_log('generateUrl by '.$apiName, $url);
        return $url;

    }

    protected function customHttpCall($ch, $params) {

        $parameter = $this->hashAndEncrypt($params);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'param' => $parameter,
            'agentCode' => $this->agentCode
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    }

    public function hashAndEncrypt($params){

        if(empty($params)){
            return null;
        }

        $hash = strtolower(substr(md5($this->aesKey),7,16));
        $this->debug_log('Hash key: ', $hash, 'aesKey: ', $this->aesKey, 'jsonParams: ', $params);

        if (mb_strlen($hash, '8bit') !== 16) {
            $this->CI->utils->error_log('Needs a 128-bit key, wrong key', $hash);
            return null;
        }

        $iv = $this->aesIv;
        $aesStr = $this->aesEncrypt($params, $hash,
            $this->encrypt_method, OPENSSL_RAW_DATA, $iv);
        $this->debug_log('iv', $iv, 'aesStr', $aesStr,'hash', $hash);

        $ciphertext    = base64_decode($aesStr);
        $ivsize     = openssl_cipher_iv_length($this->encrypt_method);
        $restoreStr=$this->aesDecrypt($ciphertext, $hash, $this->encrypt_method, OPENSSL_RAW_DATA, $ivsize, $iv);
        $this->debug_log('try restore', $restoreStr, $ivsize);

        return $aesStr;

    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null){

        $success = false;
        if(!empty($resultArr) && $resultArr['success']==self::CODE_SUCCESS){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('DonSheng Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;

    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){

        //create player in db
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $timesTamp = $this->microtime();
        $uuid = uniqid();
        $isTester = 0;

        $token = md5($uuid.$this->platform_ID.$gameUsername.$this->agentCode.$gameUsername.$password.$isTester.$this->md5key);

        $ip=null;
        if(isset($extra['ip'])){
            $ip=$extra['ip'];
        }
        if(empty($ip)){
            $ip=$this->CI->utils->getIP();
        }

        $params = json_encode([
            'uuid' => $uuid,
            'platId' => $this->platform_ID,
            'userOId' => $gameUsername,
            'agentCode' => $this->agentCode,
            'loginName' => $gameUsername,
            'passWd' => $password,
            'isTester' => $isTester,
            'clientIp' => $ip,
            'timesTamp' => $timesTamp,
            'token' => $token
        ]);

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token,$params);

        return $this->callApi(self::API_createPlayer, $params, $context);

    }

    public function processResultForCreatePlayer($params) {

        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array($success, $result);

    }


    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        ];

        $timesTamp = $this->microtime();
        $uuid = uniqid();
        $amount = $amount >= 1 ? number_format((float)$amount,2,".", "") : $amount;

        $token = md5($uuid.$this->platform_ID.$gameUsername.$this->agentCode.$amount.$external_transaction_id.self::TRANSFER_TO.$this->md5key);

        $ip=null;
        if(isset($extra['ip'])){
            $ip=$extra['ip'];
        }
        if(empty($ip)){
            $ip=$this->CI->utils->getIP();
        }

        $params = json_encode([
            'uuid' => $uuid,
            'platId' => $this->platform_ID,
            'userOId' => $gameUsername,
            'agentCode' => $this->agentCode,
            'amount' => $amount,
            'serialNumber' => $external_transaction_id,
            'type' => self::TRANSFER_TO,
            'clientIp' => $ip,
            'timesTamp' => $timesTamp,
            'token' => $token
        ]);

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token,$params);

        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    public function processResultForDepositToGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

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
            $result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
        }

        return [$success, $result];

    }

    public function getReasons($code) {

        switch($code){
            case '记录不存在':
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case '参数错误':
                return self::REASON_PARAMETER_ERROR;
                break;
            case '转账金额必须大于1元.':
                return self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
                break;
            case '获取用户信息失败.':
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case '鉴权失败.':
                return self::REASON_TOKEN_VERIFICATION_FAILED;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }

        return $code;
    }



    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        ];

        $timesTamp = $this->microtime();
        $uuid = uniqid();
        $amount = $amount >= 1 ? number_format((float)$amount,2,".", "") : $amount;

        $token = md5($uuid.$this->platform_ID.$gameUsername.$this->agentCode.$amount.$external_transaction_id.self::TRANSFER_FROM.$this->md5key);

        $ip=null;
        if(isset($extra['ip'])){
            $ip=$extra['ip'];
        }
        if(empty($ip)){
            $ip=$this->CI->utils->getIP();
        }

        $params = json_encode([
            'uuid' => $uuid,
            'platId' => $this->platform_ID,
            'userOId' => $gameUsername,
            'agentCode' => $this->agentCode,
            'amount' => $amount,
            'serialNumber' => $external_transaction_id,
            'type' => self::TRANSFER_FROM,
            'clientIp' => $ip,
            'timesTamp' => $timesTamp,
            'token' => $token

        ]);

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token,$params);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);

    }

    public function processResultForWithdrawFromGame($params){

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        $code=isset($resultArr['success']) ? $resultArr['success'] : null;

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($code);
        }

        return [$success, $result];

    }

    public function queryTransaction($transactionId, $extra) {

        $playerName = $extra['playerName'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'external_transaction_id' => $transactionId,
        ];

        $uuid = uniqid();
        $token = md5($uuid.$this->platform_ID.$this->agentCode.$transactionId.$this->md5key);

        $params = json_encode([
            'uuid' => $uuid,
            'platId' => $this->platform_ID,
            'agentCode' => $this->agentCode,
            'serialNumber' => $transactionId,
            'token' => $token

        ]);

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token, $params);

        return $this->callApi(self::API_queryTransaction, $params, $context);

    }

    public function processResultForQueryTransaction($params) {

        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success){
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);

    }

    public function queryPlayerBalance($playerName){
        

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        ];

        $uuid = uniqid();
        $token = md5($uuid.$this->platform_ID.$gameUsername.$this->agentCode.$this->md5key);

        $params = json_encode([
            'uuid' => $uuid,
            'platId' => $this->platform_ID,
            'userOId' => $gameUsername,
            'agentCode' => $this->agentCode,
            'token' => $token
        ]);

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token, $params);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id'=>$responseResultId];

        if($success){
            if(isset($resultArr['bal'])){
                $result['balance'] = $resultArr['bal'];
            }else{
                //wrong result, call failed
                $success=false;
            }
        }

        return [$success, $result];

    }

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'cn':
            case 'CN':
            case 'zh-cn':
            case "Chinese":
                $lang = 'cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id':
            case 'ID':
            case 'id-id':
            case "Indonesian":
                $lang = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'VI':
            case 'vi-vn':
            case "Vietnamese":
                $lang = 'vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case 'kr':
            case 'KR':
            case 'ko-kr':
            case "Korean":
                $lang = 'kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case 'th':
            case 'TH':
            case 'th-th':
            case "thai":
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName, $extra){

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        ];

        $timesTamp = $this->microtime();
        $uuid = uniqid();
        $isTester = 0;
        $is_mobile = $extra['is_mobile'] ? '1' : '2';

        $token = md5($uuid.$this->platform_ID.$gameUsername.$this->agentCode.$is_mobile.$gameUsername.$this->md5key);

        $ip=null;
        if(isset($extra['ip'])){
            $ip=$extra['ip'];
        }
        if(empty($ip)){
            $ip=$this->CI->utils->getIP();
        }

        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;  
        $language = isset($extra['language']) ? $this->getLauncherLanguage($extra['language']) : null;
        if(!empty($this->get_language)) {
            $language = $this->get_language;
        }     

        $params = json_encode([
            'uuid' => $uuid,
            'platId' => $this->platform_ID,
            'userOId' => $gameUsername,
            'agentCode' => $this->agentCode,
            'loginName' => $gameUsername,
            'isMobileUrl' => $is_mobile,
            'clientIp' => $ip,
            'timesTamp' => $timesTamp,
            'gameId' => $game_code,
            'lang' => $language,
            'token' => $token

        ]);

        $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token, $params);

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForQueryForwardGame($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            if(isset($resultArr['url'])){
                $result['url']=$resultArr['url'];
            }else{
                //missing address
                $success=false;
            }
        }

        return [$success, $result];

    }

    /*
        * [syncOriginalGameLogs (request) Get Game record from the api:
        *   API Requirement:
            * If beginTime is 0 or the interval is greater than 12 hours, 
            * the default value should be 12 hours prior to end time, otherwise the input begin 
            * timestamp shall prevail if the interval is shorter than 12 hours. The interval between requests shall be 5 seconds.

    */

    public function syncOriginalGameLogs($token = false){

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startTime = $startDateTime->format('Y-m-d H:i:s');
        $endTime = $endDateTime->format('Y-m-d H:i:s');

        $result = array();
        $result [] = $this->CI->utils->loopDateTimeStartEnd($startTime, $endTime, '+60 minutes', function($startDate, $endDate)  {

            $startTime = strtotime($startDate->format('Y-m-d H:i:s')) * 1000;
            $endTime = strtotime($endDate->format('Y-m-d H:i:s')) * 1000;

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
            );

            $uuid = uniqid();
            $token = md5($uuid.$this->platform_ID.$this->agentCode.$endTime.$this->md5key);

            $params = json_encode([
                'uuid' => $uuid,
                'platId' => $this->platform_ID,
                'agentCode' => $this->agentCode,
                'endTime' => $endTime,
                'beginTime' => $startTime,
                'token' => $token
            ]);

            $this->CI->utils->debug_log('<--------------TOKEN PARAMS-------------->', $token, $params);

            $result = $this->callApi(self::API_syncGameRecords, $params, $context);

            sleep(5);

            return true;


        });


        return array('success' => true, 'result' => $result);

    }

    public function processResultForSyncOriginalGameLogs($params) {

        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = ['data_count' => 0];
        $gameRecords = isset($resultArr['data']) ? $resultArr['data'] : null;

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $this->processGameRecords($gameRecords, $extra);

            $test = list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
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
        if(isset($resultArr['success']) && $resultArr['success'] !== self::CODE_SUCCESS){
            $this->debug_log('no any record', $resultArr);
        }

        return array($success, $result);
    }

    private function processGameRecords(&$gameRecords, $extra) {

        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['userName'] = isset($record['userName']) ? $record['userName'] : null;
                $data['userId'] = isset($record['userId']) ? $record['userId'] : null;
                $data['projectId'] = isset($record['projectId']) ? $record['projectId'] : null;
                $data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
                $data['realAmount'] = isset($record['realAmount']) ? $record['realAmount'] : null;
                $data['prize'] = isset($record['prize']) ? $record['prize'] : null;
                $data['profit'] = isset($record['profit']) ? $record['profit'] : null;
                $data['betTime'] = isset($record['betTime']) ? $this->gameTimeToServerTime($record['betTime']) : null;
                $data['settleTime'] = isset($record['settleTime']) && !empty($record['settleTime']) ? $this->gameTimeToServerTime($record['settleTime']) : $this->gameTimeToServerTime($record['betTime']);
                $data['gameId'] = isset($record['gameId']) ? $record['gameId'] : null;
                $data['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
                $data['oddType'] = isset($record['oddType']) ? $record['oddType'] : null;
                $data['oddBet'] = isset($record['oddBet']) ? $record['oddBet'] : null;
                $data['oddFinally'] = isset($record['oddFinally']) ? $record['oddFinally'] : null;
                $data['content'] = isset($record['content']) ? $record['content'] : null;
                $data['leagueName'] = isset($record['leagueName']) ? $record['leagueName'] : null;
                $data['matchName'] = isset($record['matchName']) ? $record['matchName'] : null;
                $data['matchFinishTime'] = isset($record['matchFinishTime']) ? $this->gameTimeToServerTime($record['matchFinishTime']) : null;
                $data['type'] = isset($record['type']) ? $record['type'] : null;
                $data['acceptBetterOdds'] = isset($record['acceptBetterOdds']) ? $record['acceptBetterOdds'] : null;
                $data['isLive'] = isset($record['isLive']) ? $record['isLive'] : null;
                $data['finished'] = isset($record['finished']) ? $record['finished'] : null;
                $data['isTest'] = isset($record['isTest']) ? $record['isTest'] : null;
                $data['stat'] = isset($record['stat']) ? $record['stat'] : null;
                $data['parlayData'] = isset($record['parlayData']) ? json_encode($record['parlayData']) : null;
                //default data
                $data['external_uniqueid'] = $record['projectId'];
                $data['response_result_id'] = $extra['response_result_id'];
                $gameRecords[$index] = $data;
                unset($data);

            }
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
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

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='ds.settleTime >= ? AND ds.settleTime <= ?';
        if($use_bet_time){
            $sqlTime='ds.betTime >= ? AND ds.betTime <= ?';
        }

        $sql = <<<EOD
SELECT
ds.id as sync_index,
ds.response_result_id,
ds.external_uniqueid,
ds.md5_sum,

ds.userName,
ds.userId as player_username,
ds.projectId as round_number,
ds.betAmount as bet_amount,
ds.realAmount as real_betting_amount,
ds.prize as win_amount,
ds.profit as result_amount,
ds.betTime as bet_at,
ds.betTime as start_at,
ds.settleTime as end_at,
ds.gameId as game_code,
ds.gameName as game_name,
ds.gameName as game_type,
ds.oddType,
ds.oddBet,
ds.oddFinally,
ds.content,
ds.leagueName,
ds.matchName,
ds.matchFinishTime,
ds.type,
ds.acceptBetterOdds,
ds.isLive,
ds.finished,
ds.isTest,
ds.stat,
ds.parlayData,


game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM $this->original_gamelogs_table as ds
LEFT JOIN game_description as gd ON ds.gameId = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON ds.userId = game_provider_auth.login_name
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

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }


    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => null,
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
        $status = $this->getGameRecordStatus($row['stat']);
        $row['status'] = $status;
        $bet_details = $this->processBetDetails($row);
        $row['bet_details'] = $bet_details;

    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    private function getGameRecordStatus($status) {

        $this->CI->load->model(array('game_logs'));
        switch ($status) {
        case self::BET_ACCEPTED:
            $status = Game_logs::STATUS_ACCEPTED;
            break;
        case self::BET_INPROGRESS:
            $status = Game_logs::STATUS_PENDING;
            break;
        case self::BET_WIN:
        case self::BET_LOSE:
        case self::BET_WINHALF:
        case self::BET_LOSEHALF:
        case self::BET_RETURN:
            $status = Game_logs::STATUS_SETTLED;
            break;
        case self::BET_CANCELLED:
            $status = Game_logs::STATUS_CANCELLED;
            break;
        case self::BET_REJECTED:
            $status = Game_logs::STATUS_REJECTED;
            break;
        case self::BET_FAILED:
            $status = Game_logs::STATUS_VOID;
            break;
        }
        return $status;
        
    }


    public function processBetDetails($gameRecords) {

        if($gameRecords['type'] == 1){
            $bet_details = array();
            $parlay_data = json_decode($gameRecords['parlayData']);
            foreach ($parlay_data as $parlay => $value) {
                $parlayId = $value['parlayId'];
                $content = $value['content'];
                $leagueName = $value['leagueName'];
                $matchName = $value['matchName'];
                $oddBet = $value['oddBet'];

                $bet_details = [
                    "ParlayId" => "ParlayId: ".$parlayId,
                    "Conten" => "Content: ".$content,
                    "LeagueName" => "LeagueName: ".$leagueName,
                    "MatchName" => "MatchName: ".$matchName,
                    "Odd" => "Odd: ".$oddBet,
                ];

                return $bet_details;
            }
        } else {
            $bet_details = [
                "RoundNo" => "RoundNo: ".$gameRecords['round_number'],
                "LeagueName" => "LeagueName: ".$gameRecords['leagueName'],
                "MatchName" => "MatchName: ".$gameRecords['matchName'],
                "Odds" => "Odds: ".$gameRecords['oddBet'],
                "Content" => "Content: ".$gameRecords['content'],
            ];
        }

        return $bet_details;
    }

    /**
     *
     * encrypt by openssl
     *
     * @param  string  $orignial
     * @param  string  $secretKey
     * @param  string  $method
     * @param  integer $options
     * @param  string  $iv
     * @return string base64
     */
    public function aesEncrypt($orignial, $secretKey, $method='AES-128-CBC', $options = 0, $iv = '') {

        $ciphertext=openssl_encrypt($orignial, $method, $secretKey, $options, $iv);
        return base64_encode($ciphertext);

    }

    /**
     * decryptByOpenssl
     * @param  string  $encrypted base64
     * @param  string  $secretKey
     * @param  string  $method
     * @param  integer $options
     * @param  integer $ivsize
     * @param  string  $iv
     * @return string
     */
    public function aesDecrypt($encrypted, $secretKey, $method='AES-128-CBC', $options = 0, $ivsize=32, $iv = '') {

        $encrypted    = base64_decode($encrypted);
        $ciphertext = mb_substr($encrypted, $ivsize, null, '8bit');

        return openssl_decrypt($ciphertext, $method, $secretKey, $options, $iv);

    }

    public function microtime(){
        return (int)(microtime(true) * 1000);
    }

}