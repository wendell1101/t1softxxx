<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
    * API NAME: Queen Maker API
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @integrator @mccoy.php.ph
**/

abstract class Abstract_game_api_common_queen_maker extends Abstract_game_api {
	const MD5_FIELDS_FOR_ORIGINAL = [
        // player
        'userid',
        'username',
        'playertype',
        // date time
        'roundstart',
        'roundend',
        // unique id
        'ugsroundid',
        // round id
        'roundid',
        'roundstatus',
        // money
        'riskamt',
        'winamt', //result amount
        'beforebal',
        'postbal',
        'turnover',
        'validbet', //validbet
        // game
        'gameprovider',
        'gameprovidercode',
        'gamename',
        'gameid',
        'cur'
    ];

	const MD5_FLOAT_AMOUNT_FIELDS = [
    ];

	const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'real_bet',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'player_username',
        'start_at',
        'end_at',
        'bet_at',
    ];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    const URI_MAP = [
        self::API_checkLoginToken => '/api/oauth/token',
        self::API_login => '/api/player/authorize',
        self::API_createPlayer => '/api/player/authorize',
        self::API_queryPlayerBalance => '/api/player/balance',
        self::API_depositToGame => '/api/wallet/credit',
        self::API_withdrawFromGame => '/api/wallet/debit',
        self::API_queryTransaction => '/api/history/transfers',
        self::API_syncGameRecords => '/api/history/game',
        self::API_queryBetDetailLink => '/api/history/providers',
    ];

    const GRANT_TYPE='client_credentials';
    const SCOPE='playerapi';
    const POST='POST';
    const GET='GET';

    public function __construct() {
        parent::__construct();
        $this->CI->load->model('game_provider_auth');
        $this->api_url = $this->getSystemInfo('url');
        $this->client_secret = $this->getSystemInfo('client_secret');
        $this->client_id = $this->getSystemInfo('client_id');
        $this->game_url = $this->getSystemInfo('game_url');
        $this->bet_limit_id = $this->getSystemInfo('bet_limit_id',1);
        $this->language = $this->getSystemInfo('language');
        $this->betDetailsLanguage = $this->getSystemInfo('betDetailsLanguage','THB');
        $this->gpcode = $this->getSystemInfo('gpcode', 'KMQM');

        $this->game_url = $this->getSystemInfo('game_url');
        $this->game_demo_url = $this->getSystemInfo('game_demo_url');

        $this->put_http_on_bet_detail_link = $this->getSystemInfo('put_http_on_bet_detail_link', false);

        $this->method = null;
        $this->is_token = false;
        $this->is_bethistory = false;

        $this->method_map = [
            self::API_checkLoginToken => self::POST,
            self::API_login => self::POST,
            self::API_createPlayer => self::POST,
            self::API_queryPlayerBalance => self::GET,
            self::API_depositToGame => self::POST,
            self::API_withdrawFromGame => self::POST,
            self::API_queryTransaction => self::GET,
            self::API_syncGameRecords => self::GET,
            self::API_queryBetDetailLink => self::GET
        ];
    }

    public function generateUrl($apiName, $params) {
        $this->method = $this->method_map[$apiName];
        if ($this->method == self::GET) {
            if(!$this->is_bethistory) {
                if($apiName==self::API_queryTransaction) {
                    $txid = isset($params['txid']) ? $params['txid'] : null;
                    $url = $this->api_url . self::URI_MAP[$apiName] .'/'. $txid;
                }elseif($apiName==self::API_queryBetDetailLink){
                    $roundId = isset($params['rounds']) ? $params['rounds'] : null;
                    $gameUsername = isset($params['users']) ? $params['users'] : null;
                    $url = $this->api_url . self::URI_MAP[$apiName] .'/'. $this->gpcode."/rounds/{$roundId}/users/{$gameUsername}?lang={$this->betDetailsLanguage}";
                }else {
                    $url = $this->api_url . self::URI_MAP[$apiName] .'?'.http_build_query($params);
                }
            } else {
                $url = $this->api_url . self::URI_MAP[$apiName] .'?'.http_build_query($params);
            }
        } else {
            $url = $this->api_url . self::URI_MAP[$apiName];
        }

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'apiName:', $apiName, 'Params:', $params, 'METHOD', $this->method);

        return $url;
    }

    public function getHttpHeaders($params) {
        $headers = [];
        if($this->is_token) {
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
        } else {
            $clone = clone $this;
            $token = $clone->getAvailableApiToken();

            $headers = [
                'Authorization' => 'Bearer '.$token
            ];

            if($this->is_bethistory) {
                $headers['X-QM-Accept'] = 'json';
            } else {
                $headers['Content-Type'] = 'application/json';
            }
        }

        return $headers;

    }

    protected function customHttpCall($ch, $params) {
        // print_r($params);exit;
        if($this->method == self::POST && $this->is_token) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));           
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        } else if($this->method == self::POST && !$this->is_token) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));       
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

    }

    /**
     * will check timeout, if timeout then call again
     * @return token
     */
    public function getAvailableApiToken(){
        $token = $this->getCommonAvailableApiToken(function(){
           return $this->generateToken();
        });
        $this->CI->utils->debug_log("Queen Maker (Token)",$token);
        return $token;
    }

    private function generateToken() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGenerateToken'
        );

        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => self::GRANT_TYPE,
            'scope' => self::SCOPE
        );

        // $this->method = self::POST;
        $this->is_token = true;

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_checkLoginToken, $params, $context);
    }

    public function processResultForGenerateToken($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        if ($success) {
            // $this->_access_token = $resultArr['access_token'];
            if($resultArr['access_token']){
                $token_timeout = new DateTime($this->utils->getNowForMysql());
                $minutes = ((int)$resultArr['expires_in']/60)-1;
                $token_timeout->modify("+".$minutes." minutes");
                $result['api_token']=$resultArr['access_token'];
                $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
            } 
        }

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'success:', $success, 'RETURN:', $success, $resultArr);

        return array($success, $result);
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode){

        $success = false;
        if(!empty($resultArr) && $statusCode == 201 || $statusCode == 200){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Queen Maker Game got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;

    }

    public function login($playerName, $password = null, $extra = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $ip = $this->CI->utils->getIP();
        #GET LANG FROM PLAYER DETAILS
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $lang_code = $this->getPlayerDetails($playerId)->language;
        if(isset($extra['language'])){
            $lang_code = $extra['language'];
        }
        $language = $this->getLauncherLanguage($lang_code);
        $is_mobile = $this->CI->utils->is_mobile();

        $params = array(
            'ipaddress' => $ip,
            'username' => $gameUsername,
            'userid' => $gameUsername,
            'lang' => $this->getLauncherLanguage($language),
            'cur' => $this->getCurrency(),
            'betlimitid' => $this->bet_limit_id,
            'istestplayer' => false,
            'platformtype' => $is_mobile ? 'Mobile' : 'Desktop'
        );

        // $this->method = self::POST;

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_login, $params, $context);

    }

    public function processResultForLogin($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array(
            'response_result_id' => $responseResultId
        );

        if($success){
            $result['token'] = $resultArr['authtoken'];
        }

        return array($success, $result);

    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $ip = $this->CI->utils->getIP();
        #GET LANG FROM PLAYER DETAILS
        $language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);
        $is_mobile = $this->CI->utils->is_mobile();

        $params = array(
            'ipaddress' => $ip,
            'username' => $gameUsername,
            'userid' => $gameUsername,
            'lang' => $this->getLauncherLanguage($language),
            'cur' => $this->getCurrency(),
            'betlimitid' => $this->bet_limit_id,
            'istestplayer' => false,
            'platformtype' => $is_mobile ? 'Mobile' : 'Desktop'
        );

        // $this->method = self::POST;

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_createPlayer, $params, $context);
        
    }

    public function processResultForCreatePlayer($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = array(
            'player' => $gameUsername,
            'exists' => false
        );

        if($success){
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
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

        $params = [
            'userid' => $gameUsername,
            'amt' => $amount,
            'cur' => $this->getCurrency(),
            'txid' => $external_transaction_id
            //'timestamp'
            //'desc'
        ];

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        // $this->method = self::POST;

        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    public function processResultForDepositToGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

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
            $error_code = isset($resultArr['errdesc']) ? $resultArr['errdesc'] : null;
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($error_code, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id'] = $resultArr['errdesc'];
            }
        }

        return [$success, $result];

    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_transaction_id
        ];

        $params = [
            'userid' => $gameUsername,
            'amt' => $amount,
            'cur' => $this->getCurrency(),
            'txid' => $external_transaction_id
            //'timestamp'
            //'desc'
        ];

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        // $this->method = self::POST;

        return $this->callApi(self::API_withdrawFromGame, $params, $context);

    }

    public function processResultForWithdrawFromGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

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
            $result['reason_id'] = $resultArr['errdesc'];
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
            'userid' => $gameUsername,
            'cur' => $this->getCurrency()
        ];

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        // $this->method = self::GET;

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id'=>$responseResultId];
        
        if($success){
            if(isset($resultArr['bal'])){
                $result['balance'] = $this->gameAmountToDBTruncateNumber($resultArr['bal']);
            }else{
                //wrong result, call failed
                $success=false;
            }
        }

        return array($success, $result);
    
    }

    /*
     *  To Launch Game, just call game provider's login API,
     *  then it will return the url that we can use to redirect our player
     *
     */
    public function queryForwardGame($playerName, $extra) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);
        $home_url = !empty($this->home_url) ? $this->home_url : $this->getHomeLink();

        if(!empty($this->language)) {
            $language = $this->getLauncherLanguage($this->language);
        } else {
            $language = $this->getLauncherLanguage($extra['language']);
        }

        if(!empty($extra['game_code'])) {
            $game_code = $extra['game_code'];
        }

        $params = [
            'gpcode' => $this->gpcode,
            'gcode' => $game_code,
            'lang' => $language
        ];

        if($extra['game_mode'] == 'real') {
            $token = $this->login($playerName, $password, $extra);
            $params['token'] = $token['token'];

            $url = $this->game_url . '?' . http_build_query($params);
        } else {
            $url = $this->game_demo_url . '?' . http_build_query($params);
        }

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> url - ' . __LINE__,$url);
        return array('success' => true, 'url' => $url);

    }

    public function getLauncherLanguage($currentLang) {

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "cn":
                $language = 'zh-CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id":
                $language = 'id-ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi":
                $language = 'vi-VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case "en":
                $language = 'en-US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
                $language = 'th-TH';
                break;
            default:
                $language = 'en-US';
                break;
        }

        return $language;

    }

    public function queryTransaction($transactionId, $extra) {
        
       $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
        ];

        $params = [
            'txid' => $transactionId
        ];

        // $this->method = self::GET;

        $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

        return $this->callApi(self::API_queryTransaction, $params, $context);

    }

    public function processResultForQueryTransaction($params) {

        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

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

    const AVAILABLE_GAME_LOG_FIELDS=[
        'roundstart', 'roundend', 'roundid', 'ugsroundid', 'roundstatus',
        'userid', 'username', 'playertype', 'riskamt', 'winamt',
        'beforebal', 'postbal', 'cur', 'gameprovider', 'gameprovidercode',
        'gamename', 'gameid', 'platformtype', 'ipaddress',
        'turnover', 'validbet'];

    /*
    {
        "roundstart": "2020-09-23T18:14:07+08:00",
        "roundend": "2020-09-23T18:14:26+08:00",
        "roundid": "hl-2-zqgMt1OQ",
        "ugsroundid": "8ff76288-dbf4-4c09-2d3a-08d85fa12e3f",
        "roundstatus": "Closed",
        "userid": "devtestt1dev",
        "username": "devtestt1dev",
        "playertype": 1,
        "riskamt": 40.000000,
        "winamt": 0.000000,
        "beforebal": 310.000000,
        "postbal": 270.000000,
        "cur": "THB",
        "gameprovider": "KingMaker QM",
        "gameprovidercode": "KMQM",
        "gamename": "Thai Hi Lo 2",
        "gameid": "thai-hi-lo-2",
        "platformtype": "Desktop",
        "ipaddress": "0.0.0.0",
        "turnover": 40.000000,
        "validbet": 40.000000
    }
     */

    public function getAvailableGameLogFields(){
        return self::AVAILABLE_GAME_LOG_FIELDS;
    }

    public function preprocessOriginalGameRecordRow(&$row, $extra){
        //convert time
        $row['roundstart'] = $this->gameTimeToServerTime($row['roundstart']);
        $row['roundend'] = $this->gameTimeToServerTime($row['roundend']);

        //make unique id
        $row['external_uniqueid'] = $this->makeExternalUniqueIdByFields($row, ['userid','ugsroundid']);
        $row['response_result_id'] = $extra['response_result_id'];
        $row['updated_at'] = $this->CI->utils->getNowForMysql();
    }


    public function syncOriginalGameLogs($token) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = $startDateTime->format('Y-m-d H:i:s');
        $endDate   = $endDateTime->format('Y-m-d H:i:s');

        $result = array();
        $result [] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+60 minutes', function($startDate, $endDate)  {

            $startTime = $startDate->format(DateTime::ATOM);
            $endTime = $endDate->format(DateTime::ATOM);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
            );

            $params = [
                'startdate' => $startTime,
                'enddate' => $endTime
            ];

            // $this->method = self::GET;
            $this->is_bethistory = true;

            $this->CI->utils->debug_log('Queen Maker: (' . __FUNCTION__ . ')', 'PARAMS:', $params);

            $result = $this->callApi(self::API_syncGameRecords, $params, $context);

            return true;


        });


        return ['success'=>true, $result];

    }

    public function processResultForSyncOriginalGameLogs($params){
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        // print_r($resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = ['data_count' => 0];
        $gameRecords = isset($resultArr) ? $resultArr : null;

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];

            $this->preprocessOriginalGameRecords($gameRecords, $extra);

            $test = list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_game_logs_table,
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

        return $this->returnUnimplemented();
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){

        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_game_logs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_game_logs_table, $record);
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


         /* queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='original.roundend >= ? AND original.roundend <= ?';
        if($use_bet_time){
            $sqlTime='original.roundstart >= ? AND original.roundstart <= ?';
        }

        $sql = <<<EOD
SELECT
original.id as sync_index,
original.response_result_id,
original.external_uniqueid,
original.md5_sum,

original.username as player_username,
original.riskamt as real_bet,
original.validbet as bet_amount,
original.winamt - original.riskamt as result_amount,
original.roundstart as start_at,
original.roundend as end_at,
original.roundstart as bet_at,
original.gamename as game_name,
original.gameid as game_code,
original.postbal as after_balance,
original.roundid as round_number,
original.roundstatus,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id
FROM $this->original_game_logs_table as original
LEFT JOIN game_description as gd ON original.gameid = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth ON original.username = game_provider_auth.login_name
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
        $row['game_type'] = null;
        $status = $row['status'];
        $gameLogRow=$this->makeRecordForMerge($row, $status);
        return $gameLogRow;
    }

    public function processGameStatus($gameStatus)
    {
        switch($gameStatus){
            case "Open":
                $status = Game_logs::STATUS_ACCEPTED;
                break;
            case "Closed":
                $status = Game_Logs::STATUS_SETTLED;
                break;
        }

        return $status;
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

        # for now we only need, round id for bet details, we use get_bet_detail_link_of_queen_maker method in async.php
        $is_game_transaction = ($row['game_code'] == 'game transaction') ? false : true;

        $bet_details = [
            'roundId' => $row['round_number'],
            'gameUsername' => $row['player_username'],
            'isBet' => $is_game_transaction
        ];

        $row['bet_details'] = $bet_details;

        $row['status'] = $this->processGameStatus($row['roundstatus']);

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

    /** 
     * Game history provided by Game Provider is also available through QM Operator API
     * 
     * @param string $playerUsername
     * @param string $betid
     * @param array $extra
    */
    public function queryBetDetailLink($playerUsername, $betid = null, $extra=[])
    {        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink'
        ];

        $params = [
            'rounds' => $betid,
            'users' => $playerUsername
        ];

        $this->CI->utils->debug_log(__METHOD__.' params ======>',$params);

        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    /** 
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult,$statusCode);
        $url = isset($arrayResult['url']) ? $arrayResult['url'] : null;

        if ($this->put_http_on_bet_detail_link) {
            $arrayResult['url'] = $this->put_http_on_bet_detail_link . $arrayResult['url'];
        }

        $this->CI->utils->debug_log(__METHOD__.' result ======>',$arrayResult);

        return array($success, $arrayResult);
    }

}