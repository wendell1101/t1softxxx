<?php
## SAMPLE EXTRA INFO ##
// {
//     "CID": "Y05",
//     "agent_id": "elequy05real",
//     "agent_password": "b61dad88b6565b5432b4a0db97e2e171",
//     "des_encrypt_key": "vGBLPpHH",
//     "md5_encrypt_key": "3nznhJ8yjsvP",
//     "game_url": "http://game.ypdemo.net:81/forwardGame.do",
//     "gamelogs_api": "http://yc0w5.yoplay.in:9955",
//     "gamelogs_key": "517fe4a45c3583f05c962fd219a0b157",
//     "prefix_for_username": "t1",
//     "acctype": 1,
//     "gameTimeToServerTime": "+0 hour",
//     "serverTimeToGameTime": "-0 hour",
//     "adjust_datetime_minutes": "0"
// }
### END ##
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_yoplay extends Abstract_game_api {

    const API_transferCreditConfirm = 'transferCreditConfirm';

    const URI_MAP = array(
        self::API_isPlayerExist             => "gb" ,
        self::API_createPlayer              => "lg" ,
        self::API_queryPlayerBalance        => "gb" ,
        self::API_depositToGame             => "tc",
        self::API_withdrawFromGame          => "tc",
        self::API_queryPlayerDailyBalance   => 42 ,
        self::API_transferCreditConfirm     => "tcc" ,
        self::API_queryTransaction          => "qos" ,
        self::API_syncGameRecords           => "/getyoplayorders_ex.xml?" ,
    );

    public function __construct() {
        parent::__construct();
        $this->api_url         = $this->getSystemInfo('url');
        $this->CID             = $this->getSystemInfo('CID');
        $this->game_url        = $this->getSystemInfo('game_url');
        $this->agent_id        = $this->getSystemInfo('agent_id');
        $this->agent_password  = $this->getSystemInfo('agent_password');
        $this->des_encrypt_key = $this->getSystemInfo('des_encrypt_key');
        $this->md5_encrypt_key = $this->getSystemInfo('md5_encrypt_key');
        $this->password_prefix = $this->getSystemInfo('password_prefix','1234');
        $this->password_sufix = $this->getSystemInfo('password_sufix','abcdef');
        $this->acctype = $this->getSystemInfo('acctype',1); // 1 - real, 0 - fun account, 1 default
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+10 minutes');
        //gamelogs API
        $this->gamelogs_api         = $this->getSystemInfo('gamelogs_api');
        $this->gamelogs_key         = $this->getSystemInfo('gamelogs_key');
    }

    public function getPlatformCode() {
        return YOPLAY_API;
    }

    private function yoplayEncrypt($params) {
        $size = @mcrypt_get_block_size('des', 'ecb');
        $params = $this->pkcs5_pad($params, $size);
        $key = $this->des_encrypt_key;
        $td = @mcrypt_module_open('des', '', 'ecb', '');
        $iv = @mcrypt_create_iv (@mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        @mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $params);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return preg_replace("/\s*/", '',$data);
    }

    private function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }


    private function decrypt($encrypted) {
        $encrypted = base64_decode($encrypted);
        $key =$this->des_encrypt_key;
        $td = mcrypt_module_open('des','','ecb','');
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $ks = mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }


    private function pkcs5_unpad($text) {
        //$pad = ord($text{strlen($text)-1});
        $length = strlen($text);
        $textArr = str_split($text);
        $pad = ord($textArr[$length-1]);

        if ($pad > strlen($text))
        return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        return false;
        return substr($text, 0, -1 * $pad);
    }

    private function concatParams($params){
        $contVals = "";
        foreach ($params as $key => $value) {
            $contVals.=$value;
        }
        return $contVals;
    }

    public function generateUrl($apiName, $params) {
        #gamelogs
        if(isset($params['isgamelogs'])&&$params['isgamelogs']==true){

            unset($params['isgamelogs']);// unset not needed params
            $params['key'] = md5($params['cid'].$params['agent'].$params['startdate'].$params['enddate'].$params['by'].$params['page'].$params['perpage'].$this->gamelogs_key);
            $url = $this->gamelogs_api.self::URI_MAP[self::API_syncGameRecords].http_build_query($params);
            // echo $url;exit;
        }else{
            $params_http = http_build_query($params);
            $yoplay_params = $this->yoplayEncrypt($params_http);
            $contParams = $this->concatParams($params);
            $key = md5($contParams.$this->agent_password.$this->md5_encrypt_key);
            $url = $this->api_url.'?params='.$yoplay_params.'&key='.$key;
        }

        return $url;
    }

    protected function processResultBoolean($responseResultId, $resultArr, $player_name = null) {

        $success = false;
        if(isset($resultArr['@attributes']['info'])&&$resultArr['@attributes']['info']=="0"){
            $success = true;
        }

        if(!$success){
           $this->setResponseResultToError($responseResultId);
           $this->CI->utils->debug_log('YOPLAY got error ======================================>', $responseResultId, 'playerName', $player_name, 'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $userName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $userName,
            'playerId' => $playerId
        );

        $params = array(
            'cid'       => $this->CID,
            'loginname' => $userName,
            'password'  => $this->password_prefix.$password.$this->password_sufix,
            'action'    => self::URI_MAP[self::API_createPlayer],
            'acctype'   => $this->acctype, //1 = real account, while 0 means demo account
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        if ($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $resultArr);
    }

    public function isPlayerExist($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $gameUsername,
            'sbe_playerName' => $gameUsername
        );

        $params = array(
            'cid'       => $this->CID,
            'loginname' => !empty($gameUsername)?$gameUsername:$playerName,
            'password'  => $this->password_prefix.$password.$this->password_sufix,
            'action'    => self::URI_MAP[self::API_isPlayerExist],
            'acctype'   => $this->acctype, //1 = real account, while 0 means demo account
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);

        $result = array();
        $playerId = $this->getPlayerIdInPlayer($sbe_playerName);
        $isPlayerExist = $resultArr['@attributes']['info'] != "error"?true:false;

        $success = true;
        if($isPlayerExist) {
            $result['exists'] = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            $result['exists'] = false;
        }
        return array($success, $result);
    }

    public function queryPlayerBalance($userName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $userName
        );

        $params = array(
            'cid'       => $this->CID,
            'loginname' => !empty($gameUsername)?$gameUsername:$userName,
            'password'  => $this->password_prefix.$password.$this->password_sufix,
            'action'    => self::URI_MAP[self::API_queryPlayerBalance],
            'acctype'   => $this->acctype, //1 = real account, while 0 means demo account
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $result = array();
        $balance = $resultArr['@attributes']['info'] == "error"?0:$resultArr['@attributes']['info'];
        $result['balance'] = @floatval($balance);

        $success = true;
        if($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
            $result['exists'] = true;
            $this->CI->utils->debug_log('PRAGMATIC PLAY GAME API query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
        }else{
            $result['exists'] = false;
            $this->CI->utils->debug_log('PRAGMATIC PLAY GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
        }
        return array($success, $result);

    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        if(empty($transfer_secure_id)){
            $transfer_secure_id=random_string('numeric', 13);
        }

        $billno=$this->CID.$transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'sbe_playerName' => $userName,
            'password' => $password,
            'amount' => $amount,
            'external_transaction_id' => $billno,
            'transaction_type' => 'IN'
        );

        $params = array(
            'cid'       => $this->CID,
            'loginname' => $gameUsername,
            'password'  => $this->password_prefix.$password.$this->password_sufix,
            'action'    => self::URI_MAP[self::API_depositToGame],
            'billno'    => $billno,
            'type'      => 'IN', // “IN” or “OUT”
            'credit'    => $amount,
            'acctype'   => $this->acctype, //1 = real account, while 0 means demo account
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $password = $this->getVariableFromContext($params, 'password');
        $amount = $this->getVariableFromContext($params, 'amount');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $transaction_type = $this->getVariableFromContext($params, 'transaction_type');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success){
           $transferCredit = $this->transferCreditConfirm($gameUsername, $password, $amount, $external_transaction_id, $transaction_type);
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            if($transferCredit['success']){
                // $queryPlayerBalance = $this->queryPlayerBalance($sbe_playerName);
                // $afterBalance = $queryPlayerBalance['balance'];
                // $this->insertTransactionToGameLogs($playerId, $sbe_playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
                $result['didnot_insert_game_logs']=true;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            }else{
                $error_msg = @$transferCredit['msg'];
                $result['reason_id'] = $this->getReason($error_msg);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }else{
            $error_msg = @$resultArr['@attributes']['msg'];
            $result['reason_id'] = $this->getReason($error_msg);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);

    }

    private function getReason($error_msg){
        $error_msg = strtolower($error_msg);
        switch ($error_msg) {
            case strpos($error_msg,'account not exist') !== false:
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case strpos($error_msg,'lost data') !== false:
                return self::REASON_NETWORK_ERROR;
                break;
            case strpos($error_msg,'duplicate transfer credit') !== false:
                return self::REASON_DUPLICATE_TRANSFER;
                break;
            case strpos($error_msg,'inadequate amount transferring credit') !== false:
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case strpos($error_msg,'key value is incorrect') !== false:
                return self::REASON_INVALID_KEY;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        if(empty($transfer_secure_id)){
            $transfer_secure_id=random_string('numeric', 13);
        }

        $billno=$this->CID.$transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'sbe_playerName' => $userName,
            'password' => $password,
            'amount' => $amount,
            'external_transaction_id' => $billno,
            'transaction_type' => 'OUT'
        );

        $params = array(
            'cid'       => $this->CID,
            'loginname' => $gameUsername,
            'password'  => $this->password_prefix.$password.$this->password_sufix,
            'action'    => self::URI_MAP[self::API_withdrawFromGame],
            'billno'    => $billno,
            'type'      => 'OUT', // “IN” or “OUT”
            'credit'    => $amount,
            'acctype'   => $this->acctype, //1 = real account, while 0 means demo account
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $password = $this->getVariableFromContext($params, 'password');
        $amount = $this->getVariableFromContext($params, 'amount');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $transaction_type = $this->getVariableFromContext($params, 'transaction_type');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success){
           $transferCredit = $this->transferCreditConfirm($gameUsername, $password, $amount, $external_transaction_id, $transaction_type);
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            if($transferCredit['success']){
                // $queryPlayerBalance = $this->queryPlayerBalance($sbe_playerName);
                // $afterBalance = $queryPlayerBalance['balance'];
                // $this->insertTransactionToGameLogs($playerId, $sbe_playerName, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
                $result['didnot_insert_game_logs']=true;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            }else{
                $error_msg = @$transferCredit['msg'];
                $result['reason_id'] = $this->getReason($error_msg);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }else{
            $error_msg = @$resultArr['@attributes']['msg'];
            $result['reason_id'] = $this->getReason($error_msg);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $resultArr);
    }

    public function transferCreditConfirm($gameUsername, $password, $amount, $external_transaction_id, $transaction_type){
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTransferCreditConfirm',
            'gameUsername' => $gameUsername,
            'external_transaction_id'=>$external_transaction_id
        );

        $params = array(
            'cid'       => $this->CID,
            'loginname' => $gameUsername,
            'password'  => $this->password_prefix.$password.$this->password_sufix,
            'action'    => self::URI_MAP[self::API_transferCreditConfirm],
            'billno'    => $external_transaction_id,
            'type'      => $transaction_type,
            'credit'    => $amount,
            'acctype'   => $this->acctype,
            'flag'      => 1, //Value=1 if invoke “PrepareTransfer” API success Value=0 if invoke “PrepareTransfer” has some error or error
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_transferCreditConfirm, $params, $context);
    }

    public function processResultForTransferCreditConfirm($params){
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $external_transaction_id=$this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername=$this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = [];
        $result['external_transaction_id'] = $external_transaction_id;
        $result =array_merge($result,$resultArr['@attributes']);

        return array($success, $result);
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                $lang = '3'; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = '1'; // chinese
                break;
            case 3:
            case 'id-id':
                $lang = '3';
                break;
            case 4:
            case 'vi-vn':
                $lang = '8'; // vietnamese
                break;
            case 5:
            case 'ko-kr':
                $lang = '5'; // korean
                break;
            default:
                $lang = '3'; // default as english
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName,$extra=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameUsername);

        $params = array(
            'cid'       => $this->CID,
            'loginname' => $gameUsername,
            'password'  => $password, # password for forwardgame is the orginal password w/o prefix and sufix
            'acctype'   => $this->acctype, #$this->acctype,
            'lang'      => $this->getLauncherLanguage($extra['language']),
            'gameType'  => $extra['game_code'],
            'agent'     => $this->agent_id,
            'dm'        => $extra['is_mobile'] ? $this->utils->getSystemHost('m') : $this->utils->getSystemHost('www')
        );
        $this->utils->debug_log("FORWARD GAME RESPONSE ++++++++++++++++++++++++++++++++++++++++++++++++++ =====> ",$params);


        $params_http = http_build_query($params);
        $yoplay_params = $this->yoplayEncrypt($params_http);
        $contParams = $this->concatParams($params);
        $key = md5($yoplay_params.$this->md5_encrypt_key);
        $url = $this->game_url.'?params='.$yoplay_params.'&key='.$key;

        return array("success" =>true,"url"=>$url);
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');
        $rtn = array();

        while ($queryDateTimeMax  > $queryDateTimeStart) {

            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }
            $startDateParam = $startDateParam->format('Y-m-d H:i:s');
            $endDateParam = $endDateParam->format('Y-m-d H:i:s');

            $page = 1;
            $perpage = 500;

            $rtn[] = $this->_continueSync( $startDateParam, $endDateParam, $perpage, $page);

            $queryDateTimeStart = $endDateParam;
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return array("success"=>true,"sync_details" => $rtn);
    }

    public function _continueSync($startDateParam, $endDateParam, $perpage, $page){
        $return = $this->syncYoplayGamelogs($startDateParam,$endDateParam,$perpage,$page);
        if($page<$return['total_page']){
            $page++;
            return $this->_continueSync($startDateParam, $endDateParam, $perpage, $page);
        }

        return $return;
    }

    public function syncYoplayGamelogs($startDateParam, $endDateParam, $perpage, $page){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $startDateParam,
            'endDate' => $endDateParam
        );

        $params = array(
            'cid' => $this->CID,
            'agent' => $this->agent_id,
            'startdate' => $startDateParam,
            'enddate' => $endDateParam,
            'by' => 'ASC',
            'page' => $page,
            'perpage' => $perpage,
            'isgamelogs' => true
        );
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('yoplay_game_logs', 'player_model'));
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        if(isset($resultArr['row'])){
            $gameRecords = $resultArr['row'] === array_values($resultArr['row']) ? $resultArr['row'] : array($resultArr['row']);
        }else{
            $gameRecords = array();
        }
        $result = array();
        $dataCount = 0;

        if(!empty($gameRecords)){
            $availableRows = $this->CI->yoplay_game_logs->getAvailableRows($gameRecords);
            foreach ($availableRows as $row) {
                $insertRecord = array();
                $playerID = $this->getPlayerIdInGameProviderAuth($row['@attributes']['username']);
                if(empty($playerID)||$row['@attributes']['flag']==0){
                    continue;
                }
                $insertRecord['playerid'] = $playerID;
                $insertRecord['billno'] = $row['@attributes']['billno'];
                $insertRecord['productid'] = $row['@attributes']['productid'];
                $insertRecord['username'] = $row['@attributes']['username'];
                $insertRecord['billtime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($row['@attributes']['billtime'])));
                $insertRecord['reckontime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($row['@attributes']['reckontime'])));
                $insertRecord['slottype'] = $row['@attributes']['slottype'];
                $insertRecord['currency'] = $row['@attributes']['currency'];
                $insertRecord['gametype'] = $row['@attributes']['gametype'];
                $insertRecord['betIP'] = $row['@attributes']['betIP'];
                $insertRecord['account'] = $row['@attributes']['account'];
                $insertRecord['cus_account'] = $row['@attributes']['cus_account'];
                $insertRecord['valid_account'] = $row['@attributes']['valid_account'];
                $insertRecord['account_base'] = $row['@attributes']['account_base'];
                $insertRecord['account_bonus'] = $row['@attributes']['account_bonus'];
                $insertRecord['cus_account_base'] = $row['@attributes']['cus_account_base'];
                $insertRecord['cus_account_bonus'] = $row['@attributes']['cus_account_bonus'];
                $insertRecord['flag'] = $row['@attributes']['flag'];
                $insertRecord['platformtype'] = $row['@attributes']['platformtype'];
                #additional info
                $insertRecord['uniqueid'] = $row['@attributes']['billno'];
                $insertRecord['external_uniqueid'] = $row['@attributes']['billno'];
                $insertRecord['response_result_id'] = $responseResultId;
                #insert DATA
                $this->CI->yoplay_game_logs->insertGameLogs($insertRecord);
                $dataCount++;
            }
        }

        $result['total_page'] = isset($resultArr['addition']['totalpage'])?$resultArr['addition']['totalpage']:1;
        $result['data_count'] = $dataCount;
        return array(true, $result);
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'yoplay_game_logs'));
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        //observer the date format
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);
        $rlt = array('success' => true);
        $result = $this->CI->yoplay_game_logs->getGameLogStatistics($startDate, $endDate);
        $cnt = 0;
        if ($result) {

            $unknownGame = $this->getUnknownGame();

            foreach ($result as $row) {

                $player_id = $row['playerid'];

                if (!$player_id) {
                    continue;
                }

                $cnt++;

                $game_description_id = $row['game_description_id'];
                $game_type_id = $row['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $extra_info=['trans_amount'=>$row['real_bet'], 'table'=>$row['external_uniqueid']];

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $row['game_code'],
                    $row['game_type'],
                    $row['game'],
                    $player_id,
                    $row['username'],
                    $row['bet_amount'],
                    $row['result_amount'],
                    null, # win_amount
                    null, # loss_amount
                    null, # after_balance
                    0, # has_both_side
                    $row['external_uniqueid'],
                    $row['game_date'], //start
                    $row['game_date'], //end
                    $row['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra_info
                );

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
        return $rlt;
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
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($extra['playerName']);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'external_transaction_id'=>$transactionId
        );

        $params = array(
            'cid'       => $this->CID,
            'action'    => self::URI_MAP[self::API_queryTransaction],
            'billno'    => $transactionId,
            'acctype'   => $this->acctype,
            'agent'     => $this->agent_id
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);

    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($this->getResultXmlFromParams($params)),true);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );


        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_msg = @$resultArr['@attributes']['msg'];
            $result['reason_id'] = $this->getReasons($error_msg);
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    function login($userName, $password = null) {
        return $this->returnUnimplemented();
    }

    function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return $this->returnUnimplemented();
    }
}