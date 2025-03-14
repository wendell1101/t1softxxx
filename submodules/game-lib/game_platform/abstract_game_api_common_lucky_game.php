<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 *
 * sign: md5
 * encrypt: aes
 * game timezone: UTC+8
 *
 * url is api_setting_url
 * account is api_setting_platformno
 * secret is api_setting_key
 *
 * @category Game API
 * @copyright 2013-2022 tot
 *
 */
abstract class Abstract_game_api_common_lucky_game extends Abstract_game_api {

    // Fields in lucky_game_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        //unique id
        'recordid',
        //money
        'losewincoin',
        'winextract',
        'entercoin',
        'exitcoin',
        'totalbet',
        'effectivebet',
        //player
        'username',
        'nickname',
        //game
        'fieldlevel',
        'roomname',
        'gameid',
        'gamename',
        'tableno',
        //date time
        'recordtime',
        //bet details
        'recordInfo',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'losewincoin',
        'winextract',
        'entercoin',
        'exitcoin',
        'totalbet',
        'effectivebet',
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        //money
        'bet_amount',
        'real_bet',
        'result_amount',
        'after_balance',
        //game
        'round_number',
        'game_code',
        'game_name',
        //player
        'player_username',
        //date time
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
        self::API_createPlayer => '/Game/goinGame',
        self::API_login => '/Game/goinGame',
        self::API_queryPlayerBalance => '/Users/userBalance',
        self::API_depositToGame => '/Transfer/platformTransferToGame',
        self::API_withdrawFromGame => '/Transfer/platformTransferToGame',
        self::API_queryTransaction => '/Transfer/verifyTransferResults',
        self::API_syncGameRecords => '/Game/roundRecord',
        self::API_queryGameListFromGameProvider => '/Game/getall',
        self::API_isPlayerOnline=> '/Users/playerIsOnline',
        self::API_queryForwardGame => '/Game/goinGame',
    ];

    const TRANSFER_TYPE_DEPOSIT=1;
    const TRANSFER_TYPE_WITHDRAWAL=2;

    const DEFAULT_PAGE_SIZE_FOR_SYNC=1000;

    const CODE_SUCCESS=0;
    const CODE_ARRAY_LOCK_AMOUNT_WHEN_TRANSFER=[10007, 20013];
    const CODE_ARRAY_FAILED_TRANSFER=[20006, 20007, 20010, 20012];
    const CODE_NO_RECORD=30007;
    const TIMETYPE_END = 'end';

    private $original_gamelogs_table=null;
    private $api_setting_url=null;
    private $api_setting_key=null;
    private $api_setting_platformno=null;
    private $prefix_of_username_in_game_logs=null;

    public function __construct() {
        parent::__construct();
        $this->api_setting_url = $this->getSystemInfo('url');
        $this->api_setting_key = $this->getSystemInfo('secret');
        $this->api_setting_platformno=$this->getSystemInfo('account');
        $this->encrypt_method=$this->getSystemInfo('encrypt_method', 'AES-256-ECB');
        $this->original_gamelogs_table=$this->getOriginalTable();
        $this->page_size_for_sync= $this->getSystemInfo('page_size_for_sync', self::DEFAULT_PAGE_SIZE_FOR_SYNC);
        $this->prefix_of_username_in_game_logs=$this->getSystemInfo('prefix_of_username_in_game_logs');
        $this->sync_gamelogs_by_timetype = $this->getSystemInfo('sync_gamelogs_by_timetype', self::TIMETYPE_END);
    }

    public function generateUrl($apiName, $params) {
        //append uri
        $url=$this->api_setting_url.self::API_URI_MAPS[$apiName];
        $this->debug_log('generateUrl by '.$apiName, $url);
        return $url;
    }

    protected function customHttpCall($ch, $params) {
        $parameter=$this->signAndEncrypt($params);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'platformno'=>$this->api_setting_platformno,
            'parameter'=>$parameter,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function signAndEncrypt($params){
        if(empty($params)){
            return null;
        }
        //sort key asc
        ksort($params, SORT_STRING);
        $paramStr='';
        foreach ($params as $key => $val) {
            $paramStr.=$key.'='.$val.'&';
        }
        $original=$paramStr.'key='.$this->api_setting_key;

        $sign=md5($original);
        $this->debug_log('sign', $original, $sign, $params, $this->api_setting_key);
        $uncrypted=$paramStr.'sign='.$sign;
        if (mb_strlen($this->api_setting_key, '8bit') !== 32) {
            $this->CI->utils->error_log('Needs a 256-bit key, wrong key', $this->api_setting_key);
            return null;
        }
        $ivsize = openssl_cipher_iv_length($this->encrypt_method);
        $iv     = openssl_random_pseudo_bytes($ivsize);
        $encrypted=$this->encryptByOpenssl($uncrypted, $this->api_setting_key,
            $this->encrypt_method, OPENSSL_RAW_DATA, $iv);
        $this->debug_log('encrypt', $uncrypted, $encrypted, $this->api_setting_key);

        $ciphertext    = base64_decode($encrypted);
        $ivsize     = openssl_cipher_iv_length($this->encrypt_method);
        $iv         = mb_substr($ciphertext, 0, $ivsize, '8bit');
        $restoreStr=$this->decryptByOpenssl($ciphertext, $this->api_setting_key, $this->encrypt_method, OPENSSL_RAW_DATA, $ivsize, $iv);
        $this->debug_log('try restore', $restoreStr, $ivsize);

        return $encrypted;
    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        $success = false;
        if(!empty($resultArr) && $resultArr['code']==self::CODE_SUCCESS){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Lucky Game got error ', $responseResultId,'result', $resultArr);
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
        $ip=null;
        if(isset($extra['ip'])){
            $ip=$extra['ip'];
        }
        if(empty($ip)){
            $ip=$this->CI->utils->getIP();
        }

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'username' => $gameUsername,
            'nickname' => $gameUsername,
            'requestip' => $ip,
        ];

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];
        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            // $result['exists'] = true;
        }
        return [$success, $result];
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        ];

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'username' => $gameUsername,
        ];

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id'=>$responseResultId];

        if($success){
            if(isset($resultArr['result']['amount'])){
                $result['balance'] = $resultArr['result']['amount'];
            }else{
                //wrong result, call failed
                $success=false;
            }
        }

        return [$success, $result];
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        ];

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'username' => $gameUsername,
            'orderno' => $external_transaction_id,
            'type' => self::TRANSFER_TYPE_DEPOSIT,
            'currency' => $amount,
        ];

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

        $code=isset($resultArr['code']) ? $resultArr['code'] : null;
        if(in_array($code, self::CODE_ARRAY_LOCK_AMOUNT_WHEN_TRANSFER)){
            $result['reason_id'] = $this->getReasons($code);
            $result['didnot_insert_game_logs'] = true;
            $success=false;
        }

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($code);
        }

        return [$success, $result];
    }

    public function getReasons($code){
        switch ($code) {
            case 20006:
            case 20007:
            case 20008:
            case 20010:
            case 20013:
            case 20014:
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case 20011:
                return self::REASON_DUPLICATE_TRANSFER;
                break;
            case 20012:
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case 20015:
                return self::REASON_DISABLED_DEPOSIT_BY_GAME_PROVIDER;
                break;
            case 20009:
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case 10000:
            case 10001:
            case 10002:
            case 10003:
            case 10006:
            case 10008:
            case 10009:
            case 20004:
            case 20005:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 10004:
                return self::REASON_INVALID_KEY;
                break;
            case 10005:
                return self::REASON_IP_NOT_AUTHORIZED;
                break;
            case 10007:
                return self::REASON_GAME_PROVIDER_NETWORK_ERROR;
                break;
            case 10010:
            case 10011:
                return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }

        return self::REASON_UNKNOWN;
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

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'username' => $gameUsername,
            'orderno' => $external_transaction_id,
            'type' => self::TRANSFER_TYPE_WITHDRAWAL,
            'currency' => $amount,
        ];

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

        $code=isset($resultArr['code']) ? $resultArr['code'] : null;

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = $this->getReasons($code);
        }

        return [$success, $result];
    }

    /*
     *  To Launch Game, just call game provider's login API,
     *  then it will return the url that we can use to redirect our player
     *
     */
    public function queryForwardGame($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            // 'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];
        $ip=null;
        if(isset($extra['ip'])){
            $ip=$extra['ip'];
        }
        if(empty($ip)){
            $ip=$this->CI->utils->getIP();
        }
        $game_code=isset($extra['game_code']) ? $extra['game_code'] : null;

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'username' => $gameUsername,
            'nickname' => $gameUsername,
            'requestip' => $ip,
        ];
        if(!empty($game_code)){
            $params['gameid']=$game_code;
        }

        // generate url is enough
        // $url=$this->api_setting_url.self::API_URI_MAPS[$apiName];
        // return ['success'=>true, 'url'=>$url];
        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        // $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];
        if($success){
            if(isset($resultArr['result']['game_address'])){
                $result['url']=$resultArr['result']['game_address'];
            }else{
                //missing address
                $success=false;
            }
        }
        return [$success, $result];
    }

    public function queryTransaction($transactionId, $extra) {
        $playerId=$extra['playerId'];
        $playerName=$extra['playerName'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'external_transaction_id' => $transactionId,
        ];

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'orderno' => $transactionId,
        ];

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        $result=[
            'response_result_id'=>$responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        ];
        //always set reason id if could
        if(isset($resultArr['code'])){
            $result['reason_id'] = $this->getReasons($resultArr['code']);
        }

        if($success){
            if(isset($resultArr['result']) && is_array($resultArr['result']) &&
                    !empty($resultArr['result'])){
                // $found=false;
                // foreach ($resultArr['result'] as $trans) {
                    // if($trans['orderno']==$external_transaction_id){
                    //     $found=true;
                    // }
                // }
                $found=@$resultArr['result']['orderno']==$external_transaction_id;
                if($found){
                    $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                }else{
                    //if not found , still keep unknown, but it's not normal
                    $this->CI->utils->debug_log('can not find transaction on result', $external_transaction_id, $resultArr);
                }
            }
        }else{
            if(isset($resultArr['code'])){
                if(in_array($resultArr['code'], self::CODE_ARRAY_FAILED_TRANSFER)){
                    //means call success and declined
                    $success=true;
                    $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
                }
            }
        }
        return [$success, $result];
    }

    public function isPlayerOnline($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerOnline',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
            'username' => $gameUsername,
        ];

        return $this->callApi(self::API_isPlayerOnline, $params, $context);
    }

    public function processResultForIsPlayerOnline($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId, 'loginStatus'=>false];
        if($success){
            if(isset($resultArr['result']['online'])){
                $result['is_online']=$resultArr['result']['online']==1;
            }else{
                //missing
                $success=false;
            }
        }
        return [$success, $result];
    }

    public function queryGameListFromGameProvider($extra=null){

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameProviderGamelist',
        ];

        $params = [
            'platformno' => $this->api_setting_platformno,
            'requesttime' => time(),
        ];

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForGetGameProviderGamelist($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = ['response_result_id' => $responseResultId];
        if($success){

            $this->CI->load->library(['language_function']);
            $this->CI->load->model(['player_model']);
            $gameTypeCode='lottery';
            //process game list
            //only one game type
            $result['game_type_list']=[
                [
                    'game_platform_id'=>$this->getPlatformCode(),
                    'game_type_unique_code'=>$gameTypeCode,
                    'game_type_name_detail'=>buildLangDetail('Lottery', '彩票'),
                    'game_type_status'=>Player_model::DB_BOOL_MAP[Player_model::DB_TRUE],
                ],
            ];
            $gameListArr=$resultArr['result'];
            if(!empty($gameListArr)){
                $result['game_list']=[];
                foreach ($gameListArr as $gameInfo) {
                    $result['game_list'][]=[
                        'game_platform_id'=>$this->getPlatformCode(),
                        'game_unique_code'=>$gameInfo['game_number'],
                        'in_flash'=>null,
                        'in_html5'=>null,
                        'in_mobile'=>null,
                        'available_on_android'=>null,
                        'available_on_ios'=>null,
                        'game_status'=>Player_model::DB_BOOL_MAP[Player_model::DB_TRUE],
                        'progressive'=>null,
                        'enabled_freespin'=>null,
                        'game_type_unique_code'=>$gameTypeCode,
                        'game_type_status'=>Player_model::DB_BOOL_MAP[Player_model::DB_TRUE],
                        'game_name_detail'=>buildLangDetail($gameInfo['game_name_en'], $gameInfo['game_name']),
                        'game_type_name_detail'=>buildLangDetail('Lottery', '彩票'),
                    ];
                }
            }
        }
        return [$success, $result];
    }

    const AVAILABLE_GAME_LOG_FIELDS=[
        'recordid', 'fieldlevel', 'roomname', 'gameid', 'gamename',
        'tableno', 'losewincoin', 'winextract', 'entercoin', 'exitcoin',
        'recordtime', 'recordInfo', 'platformid', 'platformno', 'platformname',
        'username', 'nickname', 'totalbet', 'effectivebet', 'starttime',
        'endtime', 'showpage'];

// {
//     "recordid": "20190713170520-9913-1811074-67291",
//     "fieldlevel": 1,
//     "roomname": "体验房",
//     "gameid": 100012,
//     "gamename": "百人牛牛",
//     "tableno": "1811000",
//     "losewincoin": -6,
//     "winextract": 0,
//     "entercoin": 8.5,
//     "exitcoin": 2.5,
//     "recordtime": "2019-07-13 17:05:53",
//     "recordInfo": "{\"outCardDtos\":[{\"cardThree\":[38,41,5],\"cardTwo\":[29,33],\"multiple\":1,\"position\":0,\"sortNumArray\":[29,41,38,5,33],\"type\":1,\"winOrlose\":0},{\"cardThree\":[18,35,37],\"cardTwo\":[26,3],\"multiple\":1,\"position\":0,\"sortNumArray\":[26,37,35,3,18],\"type\":3,\"winOrlose\":0},{\"cardThree\":[17,4,22],\"cardTwo\":[1,23],\"multiple\":1,\"position\":0,\"sortNumArray\":[23,22,4,17,1],\"type\":0,\"winOrlose\":0},{\"cardThree\":[55,49,34],\"cardTwo\":[39,59],\"multiple\":2,\"position\":0,\"sortNumArray\":[59,55,39,34,49],\"type\":7,\"winOrlose\":0},{\"cardThree\":[56,19,25],\"cardTwo\":[42,45],\"multiple\":3,\"position\":0,\"sortNumArray\":[45,42,25,56,19],\"type\":10,\"winOrlose\":1}],\"winPos\":[0,0,0,0],\"betCoin\":[2,0,0,0]}",
//     "platformid": 17,
//     "platformno": "LABA360_0017",
//     "platformname": "拉霸360",
//     "username": "LABA360_lb3wxbett090211KrC",
//     "nickname": "LABA360_lb3wxbett090211KrC",
//     "totalbet": "2.00",
//     "effectivebet": 6,
//     "starttime": "2019-07-13 17:05:20",
//     "endtime": "2019-07-13 17:05:53",
//     "showpage": "https://api.mugweru.com/record/recordinfo.html?record=eYykJxyXZPWPNivMc6myRxpgZiCRIj6WIejUIXwXMcTzk4wGNRzOECzaMmTncOwSNTIwLTk5MTMtMTgxMTA3NC02NzI5MSIsInVzZXJuYW1lIjoiTEFCQTM2MF9sYjN3eGJldHQwOTAyMTFLckMifQO0O0OO0O0O"
// }

    /**
     *
     * @param  boolean $token
     * @return
     */
    public function syncOriginalGameLogs($token = false) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $starttime=$startDateTime->getTimestamp();
        $endtime=$endDateTime->getTimestamp();
        $success=false;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );
        $rowsCount=0;
        //always start from 1
        $currentPage = 1;
        $done = false;
        while (!$done) {
            $params = [
                'platformno' => $this->api_setting_platformno,
                'requesttime' => time(),
                'pagesize' => $this->page_size_for_sync,
                'page' => $currentPage,
                'starttime' => $starttime,
                'endtime' => $endtime,
                'timetype' => $this->sync_gamelogs_by_timetype
            ];

            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);

            $done = true;
            if ($api_result && $api_result['success']) {
                $totalPages = @$api_result['totalPages'];
                $totalCount = @$api_result['totalCount'];
                $data_count = @$api_result['data_count'];
                $rowsCount+=$totalCount;
                //next page
                $currentPage += 1;
                $done = $currentPage >= $totalPages;
                $this->debug_log('currentPage: ',$currentPage,'totalCount',$totalCount,'totalPages', $totalPages, 'done', $done, 'result', $api_result);
            }
            if ($done) {
                $success = true;
            }
        }
        return array('success' => $success, 'rows_count'=>$rowsCount);
    }

    public function processResultForSyncOriginalGameLogs($params){
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = ['data_count' => 0];
        $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : null;

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $this->rebuildOriginalGameRecords($gameRecords, $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
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

            $result['currentPage'] = $resultArr['page']['now_page'];
            $result['totalPages'] = $resultArr['page']['page_count'];
            $result['totalCount'] = $resultArr['page']['record_count'];
        }
        if(isset($resultArr['code']) && $resultArr['code']==self::CODE_NO_RECORD){
            $this->debug_log('no any record', $resultArr);
        }

        return array($success, $result);
    }

    /**
     * only keep available fields, append sbe fields
     *
     * @param  array &$gameRecords
     * @param  array $extra
     *
     */
    private function rebuildOriginalGameRecords(&$gameRecords, $extra) {
        $availableFields=self::AVAILABLE_GAME_LOG_FIELDS;
        foreach($gameRecords as &$gr){
            //only keep available fields
            $gr=array_filter($gr, function($key) use($availableFields){
                return in_array($key, $availableFields);
            }, ARRAY_FILTER_USE_KEY);
            //remove prefix from username
            $username_without_prefix=$gr['username'];
            if(!empty($this->prefix_of_username_in_game_logs)){
                $cnt=strlen($this->prefix_of_username_in_game_logs);
                if(substr($username_without_prefix, 0, $cnt)==$this->prefix_of_username_in_game_logs){
                    $username_without_prefix=substr($username_without_prefix, $cnt);
                }
            }
            $gr['username_without_prefix']=$username_without_prefix;
            $gr['recordtime'] = $this->gameTimeToServerTime($gr['recordtime']);
            $gr['external_uniqueid'] = $gr['username'].'-'.$gr['recordid'];
            $gr['response_result_id'] = $extra['response_result_id'];
            $gr['updated_at'] = $this->CI->utils->getNowForMysql();
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

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='original.recordtime >= ? AND original.recordtime <= ?';
        // if($use_bet_time){
        //     $sqlTime='original.recordtime >= ? AND original.recordtime <= ?';
        // }

        $sql = <<<EOD
SELECT
original.id as sync_index,
original.response_result_id,
original.external_uniqueid,
original.md5_sum,

original.username_without_prefix as player_username,
original.totalbet as real_bet,
original.effectivebet as bet_amount,
original.losewincoin as result_amount,
original.recordtime as start_at,
original.recordtime as end_at,
original.recordtime as bet_at,
original.gameid as game_code,
original.gamename as game_name,
original.gamename as game_type,
original.exitcoin as after_balance,
original.tableno as round_number,
original.showpage as bet_details_link,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id
FROM $this->original_gamelogs_table as original
LEFT JOIN game_description as gd ON original.gameid = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth ON original.username_without_prefix = game_provider_auth.login_name
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
        $bet_details=['url'=>$row['bet_details_link']];
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
                'real_betting_amount' => $row['real_bet'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $bet_details,
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

}
/*end of file*/