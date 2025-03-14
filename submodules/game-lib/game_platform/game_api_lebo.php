<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/******************************
    Sample Extra Info:
{
    "key1": "## key1 ##",
    "key2": "## key2 ##",
    "prefix_for_username": "## prefix code ##"
    "sync_time_interval": "+0 minutes",
    "adjust_datetime_minutes": 30,
    "gameTimeToServerTime": "+0 hours",
    "serverTimeToGameTime": "-0 hours",
    "call_socks5_proxy": "socks5://#.#.#.#:1000"
}
*******************************/
class Game_api_lebo extends Abstract_game_api {

    const METHOD_POST  = "POST";
    const METHOD_GET   = "GET";
    const SUCCESS_CODE = "0000";
    const MAX_DATA_COUNT = 1000; # LEBO api will only return max this number of records

    const URI_MAP = array(
            // for auth
            self::API_createPlayer       => '/login.php',
            self::API_changePassword     => '/bm.php',
            self::API_blockPlayer        => '/bm.php',
            self::API_unblockPlayer      => '/bm.php',
            self::API_login              => '/login.php',
            self::API_logout             => '',

            // for wallet
            self::API_depositToGame      => '/bm.php',
            self::API_withdrawFromGame   => '/bm.php',
            self::API_queryPlayerBalance => '/bm.php',

            self::API_queryTransaction => 'bm.php',

            // for game logs
            self::API_queryGameRecords   => '/reco.php',
            self::API_syncGameRecords    => '/reco.php',
    );

    const GAME_TYPE_MAP = array(
            'ssl'    => '上海時時樂',
            'sd'     => '福彩3D',
            'ps'     => '體彩排列三',
            'cqssc'  => '重慶時時彩',
            'lhc'    => '香港六閤彩',
            'klsf'   => '廣東快樂十分',
            'tjklsf' => '天津快樂十分',
            'tjssc'  => '天津時時彩',
            'jsks'   => '江苏快３',
            'jlks'   => '吉林快３',
            'pk'     => '北京賽車',
            'cqklsf' => '重慶幸运农场',
            'klfp'   => '北京快乐8',
            'bjks'   => '北京快３',
            'gxklsf' => '廣西快樂十分',
            'hnklsf' => '湖南快樂十分',
            'sdsyxw' => '山东11選5',
            'gdsyxw' => '广东11選5',
            'jlsyxw' => '吉林11選5',
            'xjssc'  => '新疆時時彩',
            'jssb'   => '江苏骰寶',
            'jlsb'   => '吉林骰寶',
            'xyft'   => '幸運飛艇'
                );

    const GAME_RECORD_KEYS = array(
            "game_code",
            "key_id",
            "uno",
            "period_num",
            "bet_content",
            "odds",
            "bet_amount",
            "bet_result",
            "order_time",
            "settlement_flag",
            );

    const DEFAULT_TRANSACTION_STATUS_APPROVED='SUCCESS';
    const DEFAULT_TRANSACTION_STATUS_DECLINED='FAILURE';

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->key0    = $this->getSystemInfo('key');
        $this->key1    = $this->getSystemInfo('key1');
        $this->key2    = $this->getSystemInfo('key2');

        $this->transaction_status_approved= $this->getSystemInfo('transaction_status_approved', self::DEFAULT_TRANSACTION_STATUS_APPROVED);
        $this->transaction_status_declined= $this->getSystemInfo('transaction_status_declined', self::DEFAULT_TRANSACTION_STATUS_DECLINED);

        $this->status_map=[
            $this->transaction_status_approved => self::COMMON_TRANSACTION_STATUS_APPROVED,
            $this->transaction_status_declined => self::COMMON_TRANSACTION_STATUS_DECLINED,
        ];

        $this->max_data_count=$this->getSystemInfo('max_data_count', self::MAX_DATA_COUNT);
        $this->active_game_type=$this->getSystemInfo('active_game_type', self::GAME_TYPE_MAP);
        $this->update_game_logs=$this->getSystemInfo('update_game_logs', false);

    }

    protected function convertStatus($status){

        if(isset($this->status_map[$status])){
            return $this->status_map[$status];
        }else{
            return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

    }

    public function getPlatformCode() {
        return LEBO_GAME_API; // 308
    }

    protected function customHttpCall($ch, $params) {
        if ($this->method == self::METHOD_POST ) {
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $password = $this->validatePasswordAndReplace($password);
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $res = $this->login( $playerName );

        $success = $res["success"];
        $exists  = $res["exists"];

        $result = array(
                "success" => $success,
                "exists"  => $exists,
                'response_result_id'=>@$res['response_result_id'],
                );

        if($success && $exists){
            $this->updateRegisterFlag($playerId, self::FLAG_TRUE);
        }

        return $result;
    }

    public function updatePlayerInfo($playerName, $infos) {
        $this->utils->debug_log("Invoked in lebo game API", $playerName, $infos);
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        $this->utils->debug_log("Invoked in lebo game API", $playerName);
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);
        if(!empty($gameUsername)){
            $params = array(
                "uno"     => $gameUsername,
                "pw"      => $gamePassword,
                "signstr" => "",
                "opstyle" => "1",
                "qty"     => "0",
            );

            $params = $this->sign_params( $params );

            $context = array(
                'callback_obj'    => $this,
                'callback_method' => 'processResultForQueryPlayerBalance',
                'playerName'      => $playerName,
            );

            $this->method = self::METHOD_POST;

            return $this->callApi(self::API_queryPlayerBalance, $params, $context);
        }else{
            return array('success' => false, 'exists' => false, 'message' => 'player does not exists!');
        }
    }

    public function processResultForQueryPlayerBalance($params) {
        $resultText = $params["extra"];
        $resultText = trim($resultText);

        if( count($resultText)>0 && is_numeric($resultText) ){
            $success = true;
            $result = array(
                "balance" => floatval( $resultText ),
            );
        }else{
            $success = false;
            $result = array(
                  "errmsg" => $resultText,
            );

            $this->utils->debug_log("processResultForQueryPlayerBalance failed, resultText: ", $resultText);
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);
        // $external_transaction_id=!empty($transfer_secure_id) ? $transfer_secure_id : random_string('numeric', 14);
        $external_transaction_id=random_string('numeric', 14);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName'      => $playerName,
            'external_transaction_id' => $external_transaction_id,
            'gameUsername' => $gameUsername,
            'amount' => $amount
        );


        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "signstr" => "",
            "opstyle" => "2",
            "qty"     => $amount,
            "orderid" => $external_transaction_id
        );
        $params = $this->sign_params( $params );

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame( $params ){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $amount=$this->getVariableFromContext($params, 'amount');
        $playerName=$this->getVariableFromContext($params, 'playerName');
        $gameUsername=$this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id=$this->getVariableFromContext($params, 'external_transaction_id');
        $resultText = $params["extra"];
        $success = ( trim($resultText) == self::DEFAULT_TRANSACTION_STATUS_APPROVED );

        $result = ['response_result_id'=>$responseResultId, 'external_transaction_id'=>$external_transaction_id,
            'reason_id'=>parent::REASON_UNKNOWN, 'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN ];

        if($success){
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // # insert Transaction History on gamelogs.
            // $this->insertTransactionToGameLogs($playerId, $playerName, null, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }

        if(trim($resultText) == self::DEFAULT_TRANSACTION_STATUS_APPROVED){
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }elseif(trim($resultText) == self::DEFAULT_TRANSACTION_STATUS_DECLINED){
            $result['reason_id']=parent::REASON_FAILED_FROM_API;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }else{
            $result['reason_id']=parent::REASON_UNKNOWN;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);
        // $external_transaction_id=!empty($transfer_secure_id) ? $transfer_secure_id : random_string('numeric', 14);
        $external_transaction_id=random_string('numeric', 14);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName'      => $playerName,
            'gameUsername'      => $gameUsername,
            'amount'      => $amount,
            // 'transfer_secure_id' => $transfer_secure_id,
            'external_transaction_id' => $external_transaction_id,
        );

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "signstr" => "",
            "opstyle" => "3",
            "qty"     => $amount,
            "orderid" => $external_transaction_id,
        );

        $params = $this->sign_params( $params );



        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame( $params ){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $amount=$this->getVariableFromContext($params, 'amount');
        $playerName=$this->getVariableFromContext($params, 'playerName');
        $gameUsername=$this->getVariableFromContext($params, 'gameUsername');
        // $transfer_secure_id=$this->getVariableFromContext($params, 'transfer_secure_id');
        $external_transaction_id=$this->getVariableFromContext($params, 'external_transaction_id');
        $resultText = $params["extra"];
        $success = ( trim($resultText) == self::DEFAULT_TRANSACTION_STATUS_APPROVED );
        $result = ['response_result_id'=>$responseResultId, 'external_transaction_id'=>$external_transaction_id,
            'reason_id'=>parent::REASON_UNKNOWN, 'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN ];

        if($success){
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // # insert Transaction History on gamelogs.
            // $this->insertTransactionToGameLogs($playerId, $playerName, null, $amount, $responseResultId,
            //             $this->transTypeSubWalletToMainWallet());
            $result['didnot_insert_game_logs']=true;
        }

        if(trim($resultText) == self::DEFAULT_TRANSACTION_STATUS_APPROVED){
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }elseif(trim($resultText) == self::DEFAULT_TRANSACTION_STATUS_DECLINED){
            $result['reason_id']=parent::REASON_FAILED_FROM_API;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }else{
            $result['reason_id']=parent::REASON_UNKNOWN;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        return array($success, $result);
    }


    public function login($playerName, $password = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "refurl"  => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
            "signstr" => "",
        );

        $params = $this->sign_params( $params );

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
        );

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $params['extra'];
        $result_1 = '<script>parent.window.location.href="index.php";</script>';
        $result_12 = '<script>window.location.href="index.php";</script>';
        $result_2 = 'THE PW IS INCORRECT!';
        $result_3 = 'User invalid!';

        if( $resultText == $result_1 || $resultText == $result_12 ){
            $success = true;
            $exists = true;
        }
        else if( $resultText == $result_2 ){
            $success = false;
            $exists = true;
        }
        else if( $resultText == $result_3 ){
            $success = false;
            $exists = false;
        }
        else{
            $success = true;
            $exists = NULL;
        }

        $result = ["exists" => $exists, 'response_result_id'=>$responseResultId];

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = array()) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);

        // $is_mobile=$extra['is_mobile'];

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "refurl"  => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
            "signstr" => "",
        );

        $params = $this->sign_params( $params );

        $this->method = self::METHOD_GET;
        // if( $is_mobile ){
        //     $this->method = self::METHOD_POST;
        // }
        $result["url"] = $this->generateUrl( self::API_login, $params );
        $result["uno"]     = $params["uno"];
        $result["pw"]      = $params["pw"];
        $result["refurl"]  = $params["refurl"];
        $result["signstr"] = $params["signstr"];

        return $result;
    }

    public function logout($playerName, $password = null) {
        $this->utils->debug_log("Invoked in lebo game API", $playerName, $password);
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        $this->utils->debug_log("Invoked in lebo game API", $playerName);
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = null) {
        $syncId = parent::getValueFromSyncInfo($token, 'syncId');

        $serv_startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $serv_endDate   = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $game_startDate = new Datetime($this->serverTimeToGameTime($serv_startDate)) ;
        $game_endDate   = new Datetime($this->serverTimeToGameTime($serv_endDate));
        $game_startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $queryDateTimeStart = $game_startDate->getTimestamp();
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+0 minutes');
        $queryDateTimeEnd   = $game_startDate->modify($this->sync_time_interval)->getTimestamp();
        $queryDateTimeMax   = $game_endDate->getTimestamp();

        $this->utils->debug_log( 'lebo syncOriginalGameLogs beg', [$serv_startDate, $game_startDate, $queryDateTimeStart] );

        $gametypes = array_keys($this->active_game_type);
        $this->utils->debug_log('lebo sync active_game_type:====================',$gametypes);
        $results = array();
        foreach( $gametypes as $gametype ){
            $params = array(
                    "uno"      => "",
                    "signstr"  => "",
                    "recoid"   => "",
                    "dt"       => $queryDateTimeStart,
                    "gametype" => $gametype,
                    );
            $params = $this->sign_params($params);

            $context = array(
                    'callback_obj' => $this,
                    'callback_method' => 'processResultForSyncGameRecords',
                    'startDate' => $serv_startDate,
                    'endDate' => $serv_endDate,
                    );

            $this->method = self::METHOD_POST;

            $loopCounter = 0;
            $maxLoop = 20; # provide fail-safe exit condition to while loop
            do {
                $apiResult = $this->callApi(self::API_syncGameRecords, $params, $context);
                $results [] = $apiResult;

                if($apiResult['log_items_count'] < $this->max_data_count) {
                    # data count smaller than maximum, there is no more data to be fetched
                    break;
                } else {
                    # Update the param to query more results
                    $params = array(
                        "uno"      => "",
                        "signstr"  => "",
                        "recoid"   => "",
                        "dt"       => $apiResult['max_order_time'],
                        "gametype" => $gametype,
                    );
                    $params = $this->sign_params($params);
                    # goes back to the beginning of loop
                }
            } while($loopCounter++ < $maxLoop);
        }

        $result = array(
            "success"            => true,
            "data_count"         => 0,
            "response_result_id" => array(),
        );

        foreach( $results as $res ){
            $result["success"] = ( $result["success"] && $res["success"] );
            $result["data_count"] += $res["data_count"];
            $result["response_result_id"] []= $res["response_result_id"];
        }

        $this->utils->debug_log( 'lebo syncOriginalGameLogs end', [$results] );

        return $result;
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('lebo_game_logs'));
        $startDate = $this->getVariableFromContext($params, 'startDate');
        $endDate = $this->getVariableFromContext($params, 'endDate');
        $responseResultId = $this->getResponseResultIdFromParams($params);


        // ref from game_api_dg.php
        $gameRecords = array();
        $resultText = $params['extra'].$params['resultText'];
        $log_items = explode(";",$resultText );
        $log_items_count = count($log_items);
        $maxOrderTime = 0;

        foreach( $log_items as $log_item ){
            $attrs = explode("|",$log_item );

            if( count($attrs) < 10 ){ continue; } // filter out abnormal data array

            $item = array();
            for( $i=0;$i<=9; $i++){
                $k = self::GAME_RECORD_KEYS[$i];
                $item[$k] = $attrs[$i];
            }

            $orderTime = (int) $item['order_time'];
            if($orderTime && $orderTime > $maxOrderTime) {
                $maxOrderTime = $orderTime;
            }

            array_push( $gameRecords, $item );
        }

        $result = array();
        if ( ! empty($this->update_game_logs)) {
            $availableRows = $gameRecords;
        }else{
            list( $availableRows, $max_id ) = $this->CI->lebo_game_logs->getAvailableRows($gameRecords);
        }

        $dataCount = 0;
        if (!empty($availableRows)) {
            foreach ($availableRows as $record) {
                if( empty($record["key_id"]) ) { continue; }

                $insertRecord = array();

                foreach( self::GAME_RECORD_KEYS as $key ){
                    $insertRecord[$key] = $record[$key];
                }

                $insertRecord['external_uniqueid']  = $insertRecord['key_id'].$insertRecord['game_code'].$insertRecord['uno'].$insertRecord['order_time'];
                $insertRecord['response_result_id'] = $responseResultId;

                $this->CI->lebo_game_logs->sync($insertRecord);
                $dataCount++;
            }
        }

        $success = true;
        $result = array(
                $success,
                array(
                    "success"    => $success,
                    "data_count" => $dataCount, # Number of new records
                    "log_items_count" => $log_items_count, # Number of total records queried
                    "max_order_time" => $maxOrderTime,
                    )
                );

        return $result;
    }

    public function syncMergeToGameLogs($token = null) {
        $this->CI->load->model(array('game_logs', 'player_model', 'lebo_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        //observer the date format
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $rlt = array('success' => true);

        $result = $this->CI->lebo_game_logs->getGameLogStatistics($startDate, $endDate);
        $cnt = 0;
        if (!empty($result)) {
            $unknownGame = $this->getUnknownGame();
            foreach ($result as $row) {
                // if( strval($row["settlement_flag"]) !== "1" ){ continue; } // NOTE: only merge settled game logs
                $status = $this->getGameRecordsStatus($row["settlement_flag"]); //check bet status
                $realbet = (float)$row['bet_amount'];
                $result_amount = (float)$row['bet_result'];
                $cnt++;

                $game_description_id = $row['game_description_id'];
                $game_type_id = $row['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $extra = array(
                        'trans_amount' => $realbet,
                        'table'        => $row['period_num'],
                        'note'         => $row['bet_content'],
                        'status'       => $status,
                        'sync_index'   => $row['lebo_id'],
                        );

                $order_time = (new Datetime())->setTimestamp($row["order_time"])->format("Y-m-d H:i:s");

                $this->syncGameLogs(
                        $game_type_id,
                        $game_description_id,
                        $row['game_code'],
                        $row['game_type'],
                        $row['game'],
                        $row['player_id'],
                        $row['uno'],
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

        $this->utils->debug_log('lebo monitor', 'count', $cnt);

        return $rlt;
    }

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = (int)$status;

        switch ($status) {
        case 1:
            $status = Game_logs::STATUS_SETTLED;
            break;
        default:
            $status = Game_logs::STATUS_ACCEPTED;
            break;
        }
        return $status;
    }

    public function isPlayerExist($playerName) {
        $res = $this->login( $playerName );

        $success = $res["success"];
        $exists  = $res["exists"];

        $result = array(
                "success" => $success,
                "exists" => $exists,
                'response_result_id'=>@$res['response_result_id'],
                );

        if($success && $exists){
            $playerId=$this->getPlayerIdInPlayer($playerName);
            //update register
            $this->updateRegisterFlag($playerId, self::FLAG_TRUE);
        }

        return $result;
    }

    public function blockPlayer($playerName) {
        parent::blockPlayer($playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "signstr" => "",
            "opstyle" => "4",
            "qty"     => "0",
        );

        $params = $this->sign_params( $params );

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'playerName'      => $playerName,
        );

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForBlockPlayer( $params ){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $params["extra"];
        $success = ( trim($resultText) == "SUCCESS" );
        $result = ['response_result_id'=>$responseResultId];

        return array($success, $result);
    }

    public function unblockPlayer($playerName) {
        parent::unblockPlayer($playerName);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "signstr" => "",
            "opstyle" => "5",
            "qty"     => "0",
        );

        $params = $this->sign_params( $params );

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForUnblockPlayer',
            'playerName'      => $playerName,
        );

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer( $params ){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $params["extra"];
        $success = ( trim($resultText) == "SUCCESS" );
        $result = ['response_result_id'=>$responseResultId];

        return array($success, $result);
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];

        $api_url = $this->api_url;
        $apiUri = preg_replace("/^\//", "", $apiUri );

        // Replace "login.php" to "chklogin.ph" when device is in mobile mode.
        // Only mobile login will switch to mobile_url
        $is_mobile = $this->utils->is_mobile();
        if( $is_mobile && $apiName == self::API_login ){
            $api_url = $this->getSystemInfo('mobile_url');
            $apiUri = preg_replace( "/login\.php/", "chklogin.php", $apiUri );
        }

        if(!$is_mobile && $apiName == self::API_login ){
            $api_url = $this->getSystemInfo('desktop_url');
        }

        $api_url = preg_replace("/\/$/", "", $api_url );

        switch( $this->method ){
            case self::METHOD_POST:
                $url = $api_url ."/". $apiUri;
                break;

            case self::METHOD_GET:
            default:
                $url = $api_url ."/". $apiUri . '?' . http_build_query($params);
                break;
        }

        return $url;
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        // $this->utils->debug_log("Invoked in lebo game API", $apiName, $params, $responseResultId, $resultText, $statusCode, $statusText, $extra, $resultObj);
        return $this->returnUnimplemented();
    }

    private function validatePasswordAndReplace($password){
        $INVALID_PASSWORD_WORDS = array(
            "insert",
            "delete",
            "update",
            "drop",
            "union",
            "backup",
            "load_file",
            "concat",
            "intofile",
            "hex",
            "\\\\",
            "/"
        );

        $newPassword = str_replace($INVALID_PASSWORD_WORDS, 'chg', $password);

        return $newPassword;
    }
    public function changePassword($playerName, $oldPassword, $newPassword) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);
        $newPassword = $this->validatePasswordAndReplace($newPassword);

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $oldPassword,
            "signstr" => "",
            "opstyle" => "6",
            "qty"     => $newPassword,
        );

        $params = $this->sign_params( $params );

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForChangePassword',
            'playerName'      => $playerName,
            'password'        => $newPassword,
        );
        $this->CI->utils->debug_log('===============> LEBO change password params: ', $params);

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    public function processResultForChangePassword( $params ){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $params["extra"];

        $success = ( trim($resultText) == "SUCCESS" );
        $result = ['response_result_id'=>$responseResultId];
        if ($success) {
            $result["password"] = $this->getVariableFromContext($params, 'password');
            $playerName = $this->getVariableFromContext($params, 'playerName');
            $playerId = $this->getPlayerIdInPlayer($playerName);
            if ($playerId) {
                //sync password to game_provider_auth
                $this->updatePasswordForPlayer($playerId, $result["password"]);
            } else {
                $this->CI->utils->debug_log('cannot find player', $playerName);
            }
        }

        return array($success, $result);
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        $this->utils->debug_log("Invoked in lebo game API", $playerName, $playerId, $dateFrom, $dateTo);
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
        $this->utils->debug_log("Invoked in lebo game API", $playerName, $dateFrom, $dateTo);
        return $this->returnUnimplemented();
    }
    public function queryTransaction($transactionId, $extra) {
        $this->utils->debug_log("queryTransaction Invoked in lebo game API", $transactionId, $extra);
        // return $this->returnUnimplemented();

        //query player name
        $playerName=$extra['playerName'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $gamePassword = $this->getPasswordByGameUsername($gameUsername);

        $params = array(
            "uno"     => $gameUsername,
            "pw"      => $gamePassword,
            "signstr" => "",
            "opstyle" => "7",
            "orderid" => $transactionId,
        );

        $params = $this->sign_params( $params );

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName'      => $playerName,
            'external_transaction_id' => $transactionId,
        );

        $this->method = self::METHOD_POST;

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction( $params ){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $external_transaction_id=$this->getVariableFromContext($params, 'external_transaction_id');
        $resultText = $params["extra"];
        //reutrn code means call is right
        $success =  ( trim($resultText) == "SUCCESS" || trim($resultText) == "FAILURE" );
        $result = ['response_result_id'=>$responseResultId, 'external_transaction_id'=>$external_transaction_id ];

        $result['status']=$this->convertStatus($resultText);
        $result['transaction_success']=$result['status']==self::COMMON_TRANSACTION_STATUS_APPROVED;

        return array($success, $result);

    }

    private function sign_params( $params ){
        $orderid= isset($params["orderid"]) ? $params["orderid"] : null;
        $uno = $params["uno"];
        $tmp_ary = [];
        $tmp_ary [] = $this->key1;
        $tmp_ary [] = $uno;
        $tmp_ary [] = $this->key0;

        $inary = array_filter(
                $params
                , function($v, $k) { return !in_array( $k , ["signstr","uno","orderid"] ); }
                , ARRAY_FILTER_USE_BOTH
                );

        foreach( $inary as $k => $v ){
            $tmp_ary[] = $v;
        }
        $tmp_ary[] = $this->key2;
        if(!empty($orderid)){
            $tmp_ary[]=$orderid;
        }

        $this->CI->utils->debug_log('lebo api sign tmp_ary', $tmp_ary);

        $params["signstr"] = md5( implode("", $tmp_ary) );

        return $params;
    }

    public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }

    public function convertUsernameToGame($username) {
        $result = parent::convertUsernameToGame($username);

        // username length limit between 6~20

        if ( strlen($result) < 6 ){
            $result = str_pad( $result, 6, "_" );
        }

        if ( strlen($result) > 20 ){
            $result = substr( $result, 0, 20 );
        }

        return $result;
    }

    public function getPasswordByGameUsername($gameUsername) {
        $result = parent::getPasswordByGameUsername($gameUsername);

        // password length limit between 6~30

        if ( strlen($result) < 6 ){
            $result = str_pad( $result, 16, "0" );
        }

        if ( strlen($result) > 30 ){
            $result = substr( $result, 0,30 );
        }

        return $result;
    }
}
