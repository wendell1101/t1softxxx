<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/******************************
	Sample Extra Info:
{
    "prefix_for_username": "## prefix code ##"
    "sync_time_interval": "+0 minutes",
    "adjust_datetime_minutes": 30,
    "gameTimeToServerTime": "+0 hours",
    "serverTimeToGameTime": "-0 hours",
    "call_socks5_proxy": "socks5://#.#.#.#:1000"
}
*******************************/
class Game_api_yungu extends Abstract_game_api {

    const API_tryPlay = 'tryPlay';
    const URI_MAP = array(
        self::API_createPlayer   => 'register',
        self::API_login          => 'login',
        self::API_tryPlay        => 'tryPlay',
        self::API_changePassword => 'updatePwd',
        self::API_blockPlayer    => 'kickUser', // 踢人

        self::API_queryPlayerBalance => 'getBalance', // 查询余额功能
        self::API_depositToGame      => 'transferOpt', // 转帐功能
        self::API_withdrawFromGame   => 'transferOpt', // 转帐功能
    );

    const GAME_RECORD_KEYS = array("betId", "user", "gameId", "phaseNum", "money", "betType", "status", "time", "result",);
    const SUCCESS = 0;
    const PLAYER_EXIST = 1;
    const MAXIMUM_PASSWORD_LENGTH = 12;
    const MAXIMUM_USERNAME_LENGTH = 20;

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('key');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        // $this->fix_prefix_after_create_player= $this->getSystemInfo('fix_prefix_after_create_player');

    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/json");
    }

    protected function customHttpCall($ch, $params) {
        $time = time();
        $token = md5( implode("",$params). $time . $this->api_key );
        $params["time"] = $time;
        $params["token"] = $token;

        $json_str = json_encode($params);
        $encryptStr = YunguCryptAES::encrypt($json_str, $this->api_key);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encryptStr);
    }

    public function generateUrl($apiName, $params) {
        return $this->api_url;
    }

    public function getPlatformCode() {
        return YUNGU_GAME_API;
    }

    protected function convertResultJsonFromParams($params) {
        $resultText = @$params['resultText'];
        if(empty($resultText)) {
            $this->CI->utils->error_log("==========YUNGU API NO RESPONSE FROM API=============");
        }
        $response = YunguCryptAES::decrypt($resultText, $this->api_key);
        if(empty($response)) {
            $this->CI->utils->error_log("==========DECRYPT FAILED=============");
        }
        return $response;
    }

    public function processResultBoolean($responseResultId, $resultArray, $playerName, $is_create_player=null) {
        $success = false;

        if($resultArray['res'] == self::SUCCESS) {
            $success = true;
        } else {
            if($resultArray['res'] == self::PLAYER_EXIST) {
                if($is_create_player) {
                    $success = true; // set success if player exist
                }
            }
            if(!$success) {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->error_log("==========YUNGU API GOT ERROR=============",$resultArray['msg'], $playerName);
            }
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        $password =  random_string('alnum', self::MAXIMUM_PASSWORD_LENGTH);

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $this->CI->utils->debug_log('playerName:'.$playerName.', password:'.$password.', md5:'.md5($password));

        #$gamePassword = $this->getPasswordByGameUsername($gameUsername);

        if(strlen($gameUsername) > self::MAXIMUM_USERNAME_LENGTH) {
            $gameUsername =  $this->prefix_for_username.random_string('alpha', 8);
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
            'password' => $password,
        );

        $params = array(
            "cmd"      => "register",
            "username" => $gameUsername,
            "password" => md5($password),
            "agent"    => "",
        );

        $this->CI->utils->debug_log('=========Create YUNGU player===========', $gameUsername, 'password',  $password );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $password = $this->getVariableFromContext($params, 'password');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $is_create = true;
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $is_create);

        $result = array('response_result_id' => $responseResultId);

        $result['exists'] = false;
        $result['msg'] = $resultArr['msg'];

        if($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

            // $this->CI->load->model(['game_provider_auth']);
            // $this->CI->game_provider_auth->addPrefixInGameProviderAuth($playerId, $this->getPlatformCode(),
            // $this->fix_prefix_after_create_player);

            $this->updatePasswordForPlayer($playerId, $password);
            $this->updateUsernameForPlayer($playerId, $gameUsername);
            $result['exists'] = true;
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $gameUsername,
        );

        $params = array(
            'cmd'      => "getBalance",
            "username" => $gameUsername,
        );

        if(!empty($gameUsername)){
            return $this->callApi(self::API_queryPlayerBalance, $params, $context);
        }else{
            return array('success'=>false, 'exists'=>false, 'message'=>'player not exists!');
        }
    }

    public function processResultForQueryPlayerBalance($params) {
        $gameUsername = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        if(!empty($resultArr['balance'])) {
            $resultArr['balance'] = $this->gameAmountToDB(floatval($resultArr['balance']));
        }

        return array($success, $resultArr);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $transfer_secure_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $params = array(
            'cmd'        => "transferOpt",
            "username"   => $gameUsername,
            "amount"     => $amount,
            "transferId" => $transfer_secure_id,
        );

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getParamValueFromParams($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if($success) {
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
            // $afterBalance = $resultArr['balance'];
            // $result["currentplayerbalance"] =  $resultArr['balance'];
            // $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
        } else {
            $error_code = @$resultArr['res'];
            switch($error_code) {
                case '1' :
                    $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                    break;
                case '2' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
                case '3' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
                case '4' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                    break;
            }

            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $transfer_secure_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'external_transaction_id' => $transfer_secure_id,
        );

        $params = array(
            'cmd'        => "transferOpt",
            "username"   => $gameUsername,
            "amount"     => (-1) * $amount,
            "transferId" => $transfer_secure_id,
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getParamValueFromParams($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if($success) {
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;

            // $afterBalance = $resultArr['balance'];
            // $result["currentplayerbalance"] =  $afterBalance;
            // $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
        } else {
            $error_code = @$resultArr['res'];
            switch($error_code) {
                case '1' :
                    $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                    break;
                case '2' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
                case '3' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
                case '4' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                    break;
            }

            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function login($playerName, $password = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordString($gameUsername);

        $this->CI->utils->debug_log('playerName:'.$playerName.', password:'.$password.', md5:'.md5($password));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
        );

        $params = array(
            'cmd'      => "login",
            "username" => $gameUsername,
            "password" => md5($password),
        );

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params){
        $gameUsername = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        return array($success, $resultArr);
    }

    public function changePassword($playerName, $oldPassword, $newPassword) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $newPassword =  random_string('alnum', self::MAXIMUM_PASSWORD_LENGTH);

        if(empty($oldPassword)){
            //they will ignore oldPassword
            $oldPassword=$newPassword;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'password' => $newPassword,
        );

        $this->CI->utils->debug_log('change '.$playerName.' password to '.$newPassword);

        $params = array(
            'cmd'      => "updatePwd",
            "username" => $gameUsername,
            "oldPwd"   => md5($oldPassword),
            "newPwd"   => md5($newPassword),
        );

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    public function processResultForChangePassword($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $this->CI->utils->debug_log("==========YUNGU API processResultForChangePassword=============",$resultJson, $playerName);

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = ['response_result_id'=>$responseResultId];
        if ($success) {
            $result["password"] = $this->getVariableFromContext($params, 'password');
            $playerId = $this->getPlayerIdInPlayer($playerName);
            if ($playerId) {
                $this->CI->utils->debug_log('update password', $playerId, $result['password']);
                $this->updatePasswordForPlayer($playerId, $result["password"]);
                $this->CI->utils->printLastSQL();
            } else {
                $this->CI->utils->error_log('cannot find player', $playerName);
            }
        }
        //fake unimplemented, because don't allow update password
        // $result['unimplemented']=true;

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = array()) {
        if( isset($extra["game_mode"]) && $extra["game_mode"] == "trial" ){
            $params = array(
                'cmd'  => "tryPlay",
                'game' => $extra["game_code"],
            );

            if( isset($extra['mobile']) && ( $extra['mobile'] === true || $extra['mobile'] == "true" ) ){
                $params['isMobile'] = "1";
            }

            $res = $this->callApi(self::API_tryPlay, $params);

            return $res;
        }

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);

        $params = array(
            'cmd'      => "login",
            "username" => $gameUsername,
            "password" => md5($gamePassword),
            'game'     => $extra["game_code"],
        );

        if( isset($extra['mobile']) && ( $extra['mobile'] === true || $extra['mobile'] == "true" ) ){
            $params['isMobile'] = "1";
        }

        $res = $this->callApi(self::API_login, $params);

        return $res;
    }

    public function syncOriginalGameLogs($token = null) {
        $var_dates = array(
                "beg" => clone parent::getValueFromSyncInfo($token, 'dateTimeFrom'), // in server timezone
                "end" => clone parent::getValueFromSyncInfo($token, 'dateTimeTo'), // in server timezone
                );

        foreach( ["beg","end"] as $k ){
            $serv_date_obj = $var_dates[$k];
            $game_date_obj = new Datetime($this->serverTimeToGameTime($serv_date_obj)) ;
            if($k == "beg") { # Only modify begin time
                $game_date_obj->modify($this->getDatetimeAdjust());
            }
            $result = $game_date_obj->format("Y-m-d H:i:s");
            $var_dates[$k] = $result;
        }

        $this->utils->debug_log( 'yungu syncOriginalGameLogs beg', [$var_dates, $token] );

        $results = array();
        for( $page_idx=1; true; $page_idx++ ){
            $params = array(
                    "cmd"       => "getBetHistory",
                    "startTime" => $var_dates["beg"],
                    "endTime"   => $var_dates["end"],
                    "page"      => $page_idx,
                    );

            $context = array(
                    'callback_obj' => $this,
                    'callback_method' => 'processResultForSyncOriginalGameLogs',
                    );

            $res = $this->callApi(self::API_syncGameRecords, $params, $context);
            if(!empty($res)) {
                $results []= $res;

                if( isset($res[0]) ){
                    $ret_page = $res[1]["page"];
                    $ret_next = $res[1]["isNext"];
                    if( $ret_next == 1 ){
                        continue;
                    }
                }
            }

            break;
        }

        $result = array(
                "success"            => true,
                "data_count"         => 0,
                "response_result_id" => array(),
                );

        foreach( $results as $res ){
            $result["success"] = ( $result["success"] && $res["success"] );
            $result["data_count"] += isset($res["data_count"])?$res["data_count"]:0;
            $result["response_result_id"] []= $res["response_result_id"];

        }

        $this->utils->debug_log( 'yungu syncOriginalGameLogs end', [$results] );

        return $result;
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $resultText = $params['resultText'];
        $outStr = YunguCryptAES::decrypt($resultText, $this->api_key);
        $returnArr = json_decode($outStr, true);
        $res = $returnArr["res"];

        if( $res != 0 ){
            $msg = $returnArr["msg"];

            $success = false;
            if( $msg == "未找到下注记录" ){
                $success = true;
            }

            $result = array(
                    $success,
                    array(
                        "success" => $success,
                        "extra"   => $returnArr,
                        )
                    );

            return $result;
        }

        $this->CI->load->model(array('yungu_game_logs'));
        $responseResultId = $this->getResponseResultIdFromParams($params);

        // ref from game_api_dg.php
        $gameRecords = array();
        {
            $log_items = $returnArr["data"];

            foreach( $log_items as $log_item ){
                array_push( $gameRecords, $log_item );
            }
        }

        $result = array();
        list( $availableRows, $max_id ) = $this->CI->yungu_game_logs->getAvailableRows($gameRecords);

        $dataCount = 0;
        if (!empty($availableRows)) {
            foreach ($availableRows as $record) {
                if( empty($record["betId"]) ) { continue; }

                $insertRecord = array();

                foreach( self::GAME_RECORD_KEYS as $key ){
                    $insertRecord[$key] = $record[$key];
                }

                $insertRecord['external_uniqueid']  = $insertRecord['betId'].$insertRecord['gameId'].$insertRecord['user'].$insertRecord['phaseNum'];
                $insertRecord['response_result_id'] = $responseResultId;

                $this->CI->yungu_game_logs->sync($insertRecord);
                $dataCount++;
            }
        }

        $success = true;
        $result = array(
                $success,
                array(
                    "success"    => $success,
                    "data_count" => $dataCount,
                    "page"       => $returnArr["page"],
                    "isNext"     => $returnArr["isNext"],
                    )
                );

        return $result;
    }

    public function syncMergeToGameLogs($token = null) {
        $this->CI->load->model(array('game_logs', 'player_model', 'yungu_game_logs'));

        $var_dates = array(
                "beg" => clone parent::getValueFromSyncInfo($token, 'dateTimeFrom'), // in server timezone
                "end" => clone parent::getValueFromSyncInfo($token, 'dateTimeTo'), // in server timezone
                );

        foreach( ["beg","end"] as $k ){
            $serv_date_obj = $var_dates[$k];
            $game_date_obj = new Datetime($this->serverTimeToGameTime($serv_date_obj)) ;
            if($k == "beg") { # Only modify begin time
                $game_date_obj->modify($this->getDatetimeAdjust());
            }
            $result = $game_date_obj->format("Y-m-d H:i:s");
            $var_dates[$k] = $result;
        }

        $rlt = array('success' => true);

        $result = $this->CI->yungu_game_logs->getGameLogStatistics($var_dates["beg"], $var_dates["end"]);
        $cnt = 0;
        if (!empty($result)) {
            $unknownGame = $this->getUnknownGame();
            foreach ($result as $row) {
                if( strval($row["status"]) !== "2" ){ continue; } // NOTE: only merge settled game logs

                $realbet = (float)$row['money'];
                $result_amount = (float)$row['result'];
                $cnt++;

                $game_description_id = $row['game_description_id'];
                $game_type_id = $row['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $extra = array(
                        'trans_amount' => $realbet,
                        'table'        => $row['phaseNum'],
                        'note'         => $row['betType'],
                        'sync_index'   => $row['yungu_id'],
                        );

                $order_time = $this->gameTimeToServerTime($row["time"]);

                $this->syncGameLogs(
                        $game_type_id,
                        $game_description_id,
                        $row['game_code'],
                        $row['game_type'],
                        $row['game'],
                        $row['player_id'],
                        $row['user'],
                        $realbet,
                        $result_amount,
                        null, # win_amount
                        null, # loss_amount
                        null, # after_balance
                        0, # has_both_side
                        $row['external_uniqueid'],
                        $order_time, //start
                        $order_time, //end
                        $row['external_uniqueid'],
                        Game_logs::FLAG_GAME,
                        $extra
                        );

            }
        }

        $this->utils->debug_log('yungu monitor', 'count', $cnt);

        return $rlt;
    }

    public function isPlayerExist($playerName) {
        $res = $this->login($playerName);

        $chk1 = isset($res["res"]);
        if ($chk1){
            $success = true;
            switch( $res["res"] ){
                case 0:
                    $exists = true;
                    break;
                case 1:
                    $exists = false;
                    break;
                case 2:
                case 3:
                case 4:
                    $exists = true;
                    break;
                default:
                    $exists = NULL;
            }
        }
        else{
            $success = false;
            $exists = NULL;
        }

        if( $success && $exists ){
            $playerId = $this->getPlayerIdInPlayer($playerName);
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

            // $this->CI->load->model(['game_provider_auth']);
            // $this->CI->game_provider_auth->addPrefixInGameProviderAuth($playerId, $this->getPlatformCode(),
            //     $this->fix_prefix_after_create_player);

        }

        $result = array(
                "success" => $success,
                "exists"  => $exists,
                );

        return $result;
    }

    public function blockPlayer($playerName) {
        return parent::blockPlayer($playerName);
    }

    public function unblockPlayer($playerName) {
        return parent::unblockPlayer($playerName);
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        $outStr = YunguCryptAES::decrypt($resultText, $this->api_key);

        $this->CI->utils->debug_log('response result:'.$responseResultId, $outStr, $params);

        $returnArr = json_decode($outStr, true);
        $res = $returnArr["res"];

        switch( $apiName ){
            case self::API_createPlayer:
                {
                    switch( $res ){
                        case 0:
                            $gameUsername=$params['username'];
                            $this->CI->load->model(['game_provider_auth']);
                            //update
                            // $playerId=$this->CI->game_provider_auth->getPlayerIdByPlayerName($gameUsername, $this->getPlatformCode());
                            // $this->CI->game_provider_auth->addPrefixInGameProviderAuth($playerId, $this->getPlatformCode(),
                            //     $this->fix_prefix_after_create_player);

                            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);

                            $result = array( true, $returnArr);
                            break;
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 10:
                        default:
                            $result = array( false, $returnArr);
                            break;
                    }

                    return $result;
                } break;

            case self::API_login:
                {
                    switch( $res ){
                        case 0:
                            $result = array( true, $returnArr,);
                            break;
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 10:
                        default:
                            $result = array( false, $returnArr);
                            break;
                    }

                    return $result;
                } break;

            case self::API_tryPlay:
                {
                    switch( $res ){
                        case 0:
                            $result = array( true, $returnArr,);
                            break;
                        case 1:
                        default:
                            $result = array( false, $returnArr);
                            break;
                    }

                    return $result;
                } break;

            case self::API_changePassword:
                {
                    switch( $res ){
                        case 0:
                            $result = array( true, $returnArr,);
                            break;
                        case 1:
                        case 2:
                        default:
                            $result = array( false, $returnArr);
                            break;
                    }

                    return $result;
                } break;

            case self::API_queryPlayerBalance:
                {
                    switch( $res ){
                        case 0:
                            $ret_data = array(
                                    "balance" => floatval( $returnArr["balance"] ),
                                    "extra"   => $returnArr,
                                    );
                            $result = array( true, $ret_data);
                            break;
                        case 1:
                        default:
                            $result = array( false, $returnArr);
                            break;
                    }

                    return $result;
                } break;

            case self::API_depositToGame:
            case self::API_withdrawFromGame:
                {
                    switch( $res ){
                        case 0:
                            $result = array( true, $returnArr);
                            break;
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        default:
                            $result = array( false, $returnArr);
                            break;
                    }

                    return $result;
                } break;

            default: {

                     } break;
        }

        $this->utils->debug_log("Invoked in yungu game API", $apiName, $params, $responseResultId, $resultText, $statusCode, $statusText, $extra, $resultObj);
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        $this->utils->debug_log("Invoked in yungu game API", $playerName, $playerId, $dateFrom, $dateTo);
        return $this->returnUnimplemented();
    }
    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        $this->utils->debug_log("Invoked in yungu game API", $dateFrom, $dateTo, $playerName);
        return $this->returnUnimplemented();
    }
    public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
        $this->utils->debug_log("Invoked in yungu game API", $playerName, $dateFrom, $dateTo);
        return $this->returnUnimplemented();
    }
    public function queryTransaction($transactionId, $extra) {

        $playerName = $extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId'=>$playerId,
            'external_transaction_id' => $transactionId,
        );

        $params = array(
            'cmd'        => "transferLogById",
            "username"   => $gameUsername,
            "transferId" => $transactionId,
        );

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForQueryTransaction( $params ){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJson, true);

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultArr['res'];
            switch($error_code) {
                case '1' :
                    $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                    break;
                case '2' :
                case '3' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
                case '4' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                    break;
            }

            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function updatePlayerInfo($playerName, $infos) {
        $this->utils->debug_log("Invoked in yungu game API", $playerName, $infos);
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        $this->utils->debug_log("Invoked in yungu game API", $playerName);
        return $this->returnUnimplemented();
    }

    public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

    public function logout($playerName, $password = null) {
        $this->utils->debug_log("Invoked in yungu game API", $playerName, $password);
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        $this->utils->debug_log("Invoked in yungu game API", $playerName);
        return $this->returnUnimplemented();
    }

    public function convertUsernameToGame($username) {
        $result = parent::convertUsernameToGame($username);

        // username length limit between 4~20

        if ( strlen($result) < 4 ){
            $result = str_pad( $result, 4, "_" );
        }

        if ( strlen($result) > 20 ){
            $result = substr( $result, 0, 20 );
        }

        return $result;
    }

    public function onlyTransferPositiveInteger(){
        return true;
    }

}

class YunguCryptAES
{
    const AES_CIPHER = MCRYPT_RIJNDAEL_128;
    const AES_MODE = MCRYPT_MODE_ECB;

    /**
     * 加密方法
     * @param string $str
     * @param $screct_key
     * @return string
     */
    static function encrypt($str, $screct_key)
    {
        $screct_key = self::parseKey($screct_key);
        $str = self::pkcs5_pad($str);
//        $iv = mcrypt_create_iv(mcrypt_get_iv_size(self::AES_CIPHER, self::AES_MODE), MCRYPT_RAND);
        $encrypt_str = @mcrypt_encrypt(self::AES_CIPHER, $screct_key, $str, self::AES_MODE);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * @param string $str
     * @param $screct_key
     * @return string
     */
    static function decrypt($str, $screct_key)
    {
        $str = base64_decode($str);
        $screct_key = self::parseKey($screct_key);
//        $iv = mcrypt_create_iv(mcrypt_get_iv_size(self::AES_CIPHER, self::AES_MODE), MCRYPT_RAND);
        $encrypt_str = @mcrypt_decrypt(self::AES_CIPHER, $screct_key, $str, self::AES_MODE);
        return self::pkcs5_unpad($encrypt_str);
    }

    /**
     * 为兼容其他语言，密钥最大长度限制16位
     * @param $screct_key
     * @return string
     */
    static function parseKey($screct_key)
    {
        if (strlen($screct_key) > 16) {
            $screct_key = substr($screct_key, 0, 16);
        }
        return $screct_key;
    }

    /**
     * 填充算法:PKCS5Padding
     * @param $str
     * @return string
     */
    static function pkcs5_pad($str)
    {
        $blocksize = @mcrypt_get_block_size(self::AES_CIPHER, self::AES_MODE);
        $pad = $blocksize - (strlen($str) % $blocksize);
        return $str . str_repeat(chr($pad), $pad);
    }

    static function pkcs5_unpad($str)
    {
        //$pad = ord($str{strlen($str) - 1});
        $pad = ord(substr($str, -1));
        
        if ($pad > strlen($str)) return false;
        if (strspn($str, chr($pad), strlen($str) - $pad) != $pad) return false;
        return substr($str, 0, -1 * $pad);
    }
}
