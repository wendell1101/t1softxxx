<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 *
 *
 * @category Game API
 * @copyright 2013-2022 tot
 *
 */
abstract class Abstract_game_api_common_phoenix_chess_card_poker extends Abstract_game_api {

    // Fields in phoenix_chess_card_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        //unique id
        'logflag',
        //money
        'winlosemoney',
        'tax',
        'bodyleftmoney',
        'chips',
        'aftertaxmoney', //real win
        //player
        'userid',
        'account',
        //game
        'classify',
        'gamename',
        'roomname',
        'tableid',
        //date time
        'gamestarttime',
        'gameendtime',
        //bet details
        // 'chipsEx',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'winlosemoney',
        'tax',
        'bodyleftmoney',
        'chips',
        'aftertaxmoney',
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
        self::API_createPlayer => '/sdk/register',
        self::API_login => '/sdk/login',
        self::API_logout => '/sdk/logout',
        self::API_queryPlayerBalance => '/sdk/callback_balance',
        self::API_depositToGame => '/sdk/callback_redeemin',
        self::API_withdrawFromGame => '/sdk/callback_redeemout',
        self::API_queryTransaction => '/sdk/callback_redeemconfirm',
        self::API_syncGameRecords => '/sdk/callback_record',
        self::API_queryForwardGame => '/sdk/login',
    ];

    const TRANSFER_TYPE_DEPOSIT=1;
    const TRANSFER_TYPE_WITHDRAWAL=2;

    const DEFAULT_PAGE_SIZE_FOR_SYNC=1000;

    const CODE_SUCCESS=200;

    private $original_gamelogs_table=null;
    private $api_setting_url=null;
    private $api_setting_key=null;
    private $api_setting_platformno=null;
    private $prefix_of_username_in_game_logs=null;

    public function __construct() {
        parent::__construct();
        $this->api_setting_url = $this->getSystemInfo('url');
        $this->api_setting_key = $this->getSystemInfo('secret');
        $this->api_setting_gameid=$this->getSystemInfo('gameid');
        $this->api_setting_platid=$this->getSystemInfo('platid');
        $this->api_setting_default_subplatid= $this->getSystemInfo('default_subplatid', 0);
        $this->api_setting_use_agent_id_or_affiliate_id_as_subplatid= $this->getSystemInfo('use_agent_id_or_affiliate_id_as_subplatid', true);
        $this->api_setting_sync_max_time_seconds= $this->getSystemInfo('sync_max_time_seconds', 120);
        $this->original_gamelogs_table=$this->getOriginalTable();
        $this->page_size_for_sync= $this->getSystemInfo('page_size_for_sync', self::DEFAULT_PAGE_SIZE_FOR_SYNC);
        $this->fake_ip_for_test= $this->getSystemInfo('fake_ip_for_test');
        $this->home_url=$this->getSystemInfo('home_url');
    }

    public function generateUrl($apiName, $params) {
        //build sign
        $sign=$this->signOnly($params);
        $queryArr=['gameid'=>$this->api_setting_gameid, 'platid'=>$this->api_setting_platid, 'sign'=>$sign];
        //append uri
        $url=$this->api_setting_url.self::API_URI_MAPS[$apiName].'?'.http_build_query($queryArr);
        $this->debug_log('generateUrl by '.$apiName, $url);
        return $url;
    }

    protected function encodeJsonOnParams($params){
        return json_encode($params);
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($this->encodeJsonOnParams($params)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function signOnly($params){
        if(empty($params)){
            return null;
        }
        $original=$this->encodeJsonOnParams($params).$this->api_setting_key;
        $sign=md5($original);
        $this->debug_log('sign', $original, $sign, $params, $this->api_setting_key);
        return $sign;
    }

    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        $success = false;
        if(!empty($resultArr) && $resultArr['status']==self::CODE_SUCCESS){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('phoenix_card Game got error ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }

    /**
     * use agent as subplatid
     * @param  string $playerName
     * @return string
     */
    public function getSubplatid($playerName){
        $subplatid=$this->api_setting_default_subplatid;
        if($this->api_setting_use_agent_id_or_affiliate_id_as_subplatid){
            //get agent id
            $playerObj=$this->CI->player_model->getPlayerByUsername($playerName);
            if(!empty($playerObj)){
                if(!empty($playerObj->agent_id)){
                    $subplatid=$playerObj->agent_id;
                }else if(!empty($playerObj->affiliateId)){
                    $subplatid=$playerObj->affiliateId;
                }
            }
        }
        return strval($subplatid);
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
        if(!empty($this->fake_ip_for_test)){
            $ip=$this->fake_ip_for_test;
        }
        $subplatid=$this->getSubplatid($playerName);

        $params = [
            'account' => $gameUsername,
            'password' => $password,
            'ip' => $ip,
            'subplatid' => $subplatid,
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
            if(isset($resultArr['userid']) && !empty($resultArr['userid'])){
                $rlt=$this->updateExternalAccountIdForPlayer($playerId, $resultArr['userid']);
                if(!$rlt){
                    $this->error_log('updateExternalAccountIdForPlayer', $playerId, $resultArr['userid'], $rlt);
                }
                $result['external_account_id']=$resultArr['userid'];
            }
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
            'account' => $gameUsername,
            'ts' => time(),
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
            if(isset($resultArr['money'])){
                $result['balance'] = $resultArr['money'];
                $result['is_online']=$resultArr['online']==1;
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
            'account' => $gameUsername,
            'platorder' => $external_transaction_id,
            'money' => $amount,
            'ts' => time(),
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

        $status=isset($resultArr['status']) ? $resultArr['status'] : null;
        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            if((in_array($status, $this->other_status_code_treat_as_success))){
                $result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }else if($status==-1001){
                $result['reason_id'] = self::REASON_INVALID_TRANSFER_AMOUNT;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }else if(in_array($status, [400, 401, 402])){
                $result['reason_id'] = self::REASON_PARAMETER_ERROR;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return [$success, $result];
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
            'account' => $gameUsername,
            'platorder' => $external_transaction_id,
            'money' => $amount,
            'ts' => time(),
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

        $status=isset($resultArr['status']) ? $resultArr['status'] : null;
        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            if((in_array($status, $this->other_status_code_treat_as_success))){
                $result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }else if($status==101){
                $result['reason_id'] = self::REASON_NO_ENOUGH_BALANCE;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }else if(in_array($status, [400, 401, 402])){
                $result['reason_id'] = self::REASON_PARAMETER_ERROR;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
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
        $password = $this->getPasswordByGameUsername($gameUsername);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
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
        if(!empty($this->fake_ip_for_test)){
            $ip=$this->fake_ip_for_test;
        }
        $subplatid=$this->getSubplatid($playerName);

        $home_url = !empty($this->home_url) ? $this->home_url : $this->getHomeLink();

        $params = [
            'account' => $gameUsername,
            'password' => $password,
            'ip' => $ip,
            'subplatid' => $subplatid,
            'return_url' => $home_url
        ];
        $gameCode=isset($extra['game_code']) ? $extra['game_code'] : null;
        if(!empty($gameCode)){
            $params['maskid']=intval($gameCode);
        }
        $homeLink=isset($extra['home_link']) ? $extra['home_link'] : null;
        if(!empty($homeLink)){
            $params['return_url']=$homeLink;
        }

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];
        if($success){
            if(isset($resultArr['gameurl'])){
                $result['url']=$resultArr['gameurl'];
            }else{
                //missing url
                $success=false;
            }
        }
        return [$success, $result];
    }

    public function login($playerName, $password = null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = empty($password) ? $this->getPasswordByGameUsername($gameUsername) : $password;
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
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
        if(!empty($this->fake_ip_for_test)){
            $ip=$this->fake_ip_for_test;
        }
        $subplatid=$this->getSubplatid($playerName);

        $params = [
            'account' => $gameUsername,
            'password' => $password,
            'ip' => $ip,
            'subplatid' => $subplatid,
        ];
        $gameCode=isset($extra['game_code']) ? $extra['game_code'] : null;
        if(!empty($gameCode)){
            $params['maskid']=$gameCode;
        }
        $homeLink=isset($extra['home_link']) ? $extra['home_link'] : null;
        if(!empty($homeLink)){
            $params['return_url']=$homeLink;
        }

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];
        if($success){
            if(isset($resultArr['gameurl'])){
                $result['url']=$resultArr['gameurl'];
            }else{
                //missing url
                $success=false;
            }
        }
        return [$success, $result];
    }

    public function logout($playerName, $password = null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = [
            'account' => $gameUsername,
        ];

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = ['response_result_id' => $responseResultId];

        return [$success, $result];
    }

    public function queryTransaction($transactionId, $extra) {
        $playerId=$extra['playerId'];
        $playerName=$extra['playerName'];
        $gameUsername=$this->getGameUsernameByPlayerUsername($playerName);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'external_transaction_id' => $transactionId,
        ];

        $params = [
            'account' => $gameUsername,
            'platorder' => $transactionId,
            'ts' => time(),
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
        $status=isset($resultArr['status']) ? $resultArr['status'] : null;
        if ($success) {
            $result['status'] = $resultArr['result']==1 ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
        }else{
            if((in_array($status, $this->other_status_code_treat_as_success))){
                $result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
            }else if($status==1000){
                $result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
                $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
            }else if(in_array($status, [400, 401, 402])){
                $result['reason_id'] = self::REASON_PARAMETER_ERROR;
            }
        }

        return [$success, $result];
    }

    const AVAILABLE_GAME_LOG_FIELDS=[
        'userid', 'account', 'classify', 'gameid', 'gamename',
        'roomname', 'tableid', 'winlosemoney', 'tax', 'bodyleftmoney',
        'logflag', 'chips', 'gamestarttime', 'gameendtime', 'chipsEx',
        'aftertaxmoney'];


// {"userid":"10000",
// "account":"god002",
// "classify":"1",
// "gamename":"百家乐",
// "roomname":"百家乐极速场",
// "tableid":"1",
// "winlosemoney":150000,
// "tax":"20",
// "bodyleftmoney":650000,
// "logflag":"3418614236417034820",
// "chips":100000,
// "gamestarttime":"2018/01/01 01:15:00",
// "gameendtime":"2018/01/01 01:15:35",
// "chipsEx":[
// "gamemode": 0,
// "0": {"down": 50000,"multiple": 1.0},
// "1": {"down": 50000, "multiple": 1.0}
// ]
// "aftertaxmoney":-100020
// }

    public function getAvailableGameLogFields(){
        return self::AVAILABLE_GAME_LOG_FIELDS;
    }

    /**
     * preprocessOriginalGameRecordRow
     * @param array &$row
     * @param array $extra
     * @return null
     */
    public function preprocessOriginalGameRecordRow(&$row, $extra){
        //invalid date time
        // if(substr($row['gamestarttime'], 0, 4)=='1970' || empty($row['gamestarttime'])){
            $row['gamestarttime']=$row['gameendtime'];
        // }
        //convert time
        $row['gamestarttime'] = $this->gameTimeToServerTime($row['gamestarttime']);
        $row['gameendtime'] = $this->gameTimeToServerTime($row['gameendtime']);
        //encode json
        if(!empty($row['chipsEx'])){
            $row['chipsEx']=json_encode($row['chipsEx']);
        }
        //make unique id
        $row['external_uniqueid'] = $this->makeExternalUniqueIdByFields($row, ['userid','account','logflag']);
        $row['response_result_id'] = $extra['response_result_id'];
        $row['updated_at'] = $this->CI->utils->getNowForMysql();
    }

    /**
     *
     * @param  boolean $token
     * @return
     */
    public function syncOriginalGameLogs($token = false) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjustSyncOriginal());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $success=false;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs'
        );
        //2minutes
        $timeLimitSecond=$this->api_setting_sync_max_time_seconds;
        return $this->syncOriginalGameLogsByPaginateAndTimeLimit($startDateTime, $endDateTime, $timeLimitSecond,
            function(\DateTime $startAt, \DateTime $endAt, $currentPage) use($context){
            //debug
            // return ['success'=>true, 'totalPages'=>2, 'totalPageCount'=>0, 'realDataCount'=>100];
            $starttime=$startAt->format('YmdHis');
            $endtime=$endAt->format('YmdHis');
            $params = [
                'account'=>'',
                'subplatid'=>0,
                'start' => $starttime,
                'end' => $endtime,
                'curpage' => $currentPage-1, //start from 0
                'perpage' => $this->page_size_for_sync,
            ];
            $this->CI->utils->info_log('query game logs', 'start', $startAt, 'end', $endAt, 'currentPage', $currentPage);
            $api_result = $this->callApi(self::API_syncGameRecords, $params, $context);
            //['success'=>, 'totalPages'=>, 'totalPageCount'=>, 'realDataCount'=>]
            return $api_result;
        });
    }

    public function processResultForSyncOriginalGameLogs($params){
        $this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = ['realDataCount' => 0];
        $gameRecords = isset($resultArr['data']) ? $resultArr['data'] : null;

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            //call parent
            $this->preprocessOriginalGameRecords($gameRecords, $extra);

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
            $count=count($gameRecords);
            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)){
                $result['realDataCount'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)){
                $result['realDataCount'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);

            // $result['currentPage'] = $resultArr['curpage'];
            //totalPages should start from 1
            $result['totalPages'] = $resultArr['maxpage']+1;
            $result['totalPageCount'] = $count;
        }
        if(empty($gameRecords)){
            //empty
            $result['totalPages']=0;
            $result['totalPageCount']=0;
            $result['realDataCount']=0;
        }

        //['success'=>, 'totalPages'=>, 'totalPageCount'=>, 'realDataCount'=>]
        return array($success, $result);
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
        $sqlTime='original.gameendtime >= ? AND original.gameendtime <= ?';
        if($use_bet_time){
            $sqlTime='original.gamestarttime >= ? AND original.gamestarttime <= ?';
        }

        $sql = <<<EOD
SELECT
original.id as sync_index,
original.response_result_id,
original.external_uniqueid,
original.md5_sum,

original.account as player_username,
original.chips as real_bet,
original.chips as bet_amount,
original.winlosemoney as result_amount,
original.gamestarttime as start_at,
original.gameendtime as end_at,
original.gamestarttime as bet_at,
original.gamename as game_code,
original.gamename as game_name,
original.classify as game_type,
original.bodyleftmoney as after_balance,
original.tableid as round_number,
original.chipsEx as bet_details,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id
FROM $this->original_gamelogs_table as original
LEFT JOIN game_description as gd ON original.gamename = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth ON original.account = game_provider_auth.login_name
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
        $gameLogRow=$this->makeRecordForMerge($row, Game_logs::STATUS_SETTLED);
        // $gameLogRow['bet_details']=json_encode($row['bet_details'], true);
        $gameLogRow['bet_details']=$row['bet_details'];
        return $gameLogRow;
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

    public function isPlayerOnline($playerName){
        $rlt=$this->queryPlayerBalance($playerName);
        if($rlt && $rlt['success']){
            return ['success'=>true, 'is_online'=>$rlt['is_online']];
        }else{
            return ['success'=>false];
        }
    }

    public function onlyTransferPositiveInteger(){
        return true;
    }

}
/*end of file*/