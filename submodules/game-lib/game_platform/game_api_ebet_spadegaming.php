<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_spadegaming extends Abstract_game_api {

    private $api_url;
    private $channelId;
    private $islive;
    private $tag;
    private $public_key;
    private $private_key;
    private $thirdParty;
    private $currency;
    private $logout_before_launch_game;

    public function __construct() {
        parent::__construct();
        $this->api_url        = $this->getSystemInfo('url');
        $this->game_url       = $this->getSystemInfo('game_url');
        $this->channelId      = $this->getSystemInfo('channelId');
        $this->islive         = $this->getSystemInfo('live');
        $this->thirdParty     = $this->getSystemInfo('thirdParty');
        $this->tag            = $this->getSystemInfo('tag');
        $this->public_key     = $this->getSystemInfo('public_key');
        $this->private_key    = $this->getSystemInfo('private_key');
        $this->currency       = $this->getSystemInfo('currency','CNY');
        $this->logout_before_launch_game=$this->getSystemInfo('logout_before_launch_game', true);

        # init RSA
        $this->rsa = new Crypt_RSA();
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->rsa->setHash('md5');

    }

    const SUCCESS_CODE = 200;

    public function getPlatformCode() {
        return EBET_SPADE_GAMING_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/json");
        return array("Accept"       => "application/json");
    }

    public function callback($result = null, $platform = 'web') {
        $success = false;
        $token_prefix       = $this->thirdParty. "-" .$this->tag . "-";
        $result['token']    = str_replace($token_prefix, "", $result['token']);
        $player_id          = $this->getPlayerIdByToken($result['token']);
        $game_username      = $this->getGameUsernameByPlayerId($player_id);

        $this->CI->utils->debug_log('Check player id ====================================>', $player_id);
        $this->CI->utils->debug_log('Check player token ====================================>', $result['token']);

        if (!empty($player_id)) {
            $success = true;
            $playerInfo = $this->getPlayerInfoByToken($result['token']);
            // $this->CI->utils->debug_log('Check infoooooooo ====================================>', $playerInfo);
        }

        if ($platform == 'web') {
            if ($success) {
                $params = array(
                    "currency"  => $this->currency,
                    "status"    => self::SUCCESS_CODE
                );
                $this->CI->utils->debug_log('Check EBET_SPADE_GAMING_API RESPONSE (Callback) ====================================>', $params);

                return $params;

            } else {
                return "Acct Not Found (50100)";
            }
        } else {
            // $this->CI->utils->debug_log('Check SPADE_GAMING_API REQUEST (Callback) ====================================>', $result);
            // $playerInfo = (array)$this->CI->game_provider_auth->getPlayerInfoByGameUsername($result['acctId'],SPADE_GAMING_API);
            // $this->CI->utils->debug_log('Check infoooooooo ====================================>', $playerInfo);
            // if(!empty($playerInfo)){
            //     $balance = $this->queryPlayerBalance($playerInfo['username']);
            //     $this->CI->utils->debug_log('Check SPADE_GAMING_API balance (Callback) ====================================>', $balance);
            //     $params = array(
            //         'acctInfo' => [
            //             "acctId" => $result['acctId'],
            //             "balance" => $balance['balance'],
            //             "userName" => $result['acctId'],
            //             "currency" => $this->currency,
            //         ],
            //         'merchantCode' => $this->merchantCode,
            //         'msg' => "success",
            //         'serialNo' => $result['serialNo'],
            //     );
            //     $this->CI->utils->debug_log('Check SPADE_GAMING_API RESPONSE (Callback) ====================================>', $params);
            //     return $params;
            // } else {
            //     return "Acct Not Found (50100)";
            // }
        }

    }

    protected function customHttpCall($ch, $params) {
        $action = $params["method_action"];
        unset($params["method_action"]); //unset action not need on params

        $postParams = array(
                "channelId"         => $this->channelId,
                "thirdParty"        => $this->thirdParty,
                "tag"               => $this->tag,
                "action" => array(
                        "command"    => $action,
                        "parameters" => $params
                ),
                "live"              => $this->islive,
                "timestamp"         => time()
        );
        $postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = false;

        $response = json_decode($resultArr['result'],true);

        if (isset($resultArr['isgamelogs']) && $resultArr['status'] == self::SUCCESS_CODE) {
           $success = true;
        }

        if(isset($response['msg']) && $response['msg'] == "Success"){
           $success = true;
        }

        if (isset($response['balance'])) {
           $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->error_log('EBET_SPADE_GAMING_API got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function isPlayerExist($userName) {
        return $this->getAccountInfo($userName,"isPlayerExist");
    }

    public function getAccountInfo($userName,$method = null){

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $callback_method = ($method == "isPlayerExist") ? "processResultForIsPlayerExists" : "processResultForQueryPlayerBalance";
        $api_method = ($method == "isPlayerExist") ? self::API_isPlayerExist : self::API_queryPlayerBalance;

        $context = array(
                'callback_obj'      => $this,
                'callback_method'   => $callback_method,
                'playerName'        => $playerName,
                'userName'          =>$userName,
        );

        $params = array(
                "acctId"         => !empty($playerName)?$playerName:$userName,
                "method_action"  => "getacctinfo"
        );

        return $this->callApi($api_method, $params, $context);

    }

    public function processResultForIsPlayerExists($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $userName = $this->getVariableFromContext($params, 'userName');
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = array();

        if ($success) {
            $resultArr = json_decode($resultArr["result"],true);
            if(isset($resultArr['balance'])){
                $result['exists'] = true;

                $player_id = $this->getPlayerIdInGameProviderAuth($userName);
                //sync to game provider auth
                $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
            }
        }

        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $userName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
                'callback_obj'      => $this,
                'callback_method'   => 'processResultForCreatePlayer',
                'userName'          => $userName,
                'sbe_userName'      => $playerName
        );

        $params = array(
                'acctId'        => $userName,
                'currency'      => $this->currency,
                'amount'        => 1,
                'method_action' =>'deposit'
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $userName = $this->getVariableFromContext($params, 'userName');
        $sbe_userName = $this->getVariableFromContext($params, 'sbe_userName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $sbe_userName);

        #withdraw deposit amount  on create player
        $this->withdrawFromGame($sbe_userName, 1, null,true);

        return array($success, $resultJsonArr);

    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $remitno = date("YmdHis").rand(1,1000);

        $context = array(
                'callback_obj'    => $this,
                'callback_method' => 'processResultForDepositToGame',
                'playerName'      => $playerName,
                'sbe_playerName'  => $userName,
                'amount'          => $amount,
                'transaction_id'  => $remitno,
        );

        $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
        // $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);

        $params = array(
                'acctId'        => $playerName,
                'currency'      => $this->currency,
                'amount'        => $amount,
                'method_action' =>'deposit'
        );


        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    public function processResultForDepositToGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $result = array('response_result_id' => $responseResultId);
        $success = false;

        $transaction_id=$this->getVariableFromContext($params, 'transaction_id');

        if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
            //get current sub wallet balance
            $playerBalance = $this->queryPlayerBalance($sbe_playerName);

            //for sub wallet
            $afterBalance = @$playerBalance['balance'];
            $result["external_transaction_id"] = $transaction_id;
            if(!empty($afterBalance)){
                $result["currentplayerbalance"] = $afterBalance;
            }
            $result["userNotFound"] = false;
            $success = true;
            //update
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                //deposit
                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

            } else {
                $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }
        }

        return array($success, $result);

    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $remitno = date("YmdHis").rand(1,1000);

        $context = array(
            'callback_obj'           => $this,
            'callback_method'        => 'processResultForWithdrawFromGame',
            'playerName'             => $playerName,
            'sbe_playerName'         => $userName,
            'transaction_id'         =>$remitno,
            'amount'                 => $amount,
            'notRecordTransaction'   =>$notRecordTransaction,
        );

        $params = array(
            'acctId'        => $playerName,
            'currency'      => $this->currency,
            'amount'        => $amount,
            'method_action' =>'withdraw'
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);

    }

    public function processResultForWithdrawFromGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $notRecordTransaction = $this->getVariableFromContext($params, 'notRecordTransaction');
        $transaction_id=$this->getVariableFromContext($params, 'transaction_id');

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $result = array('response_result_id' => $responseResultId);
        $success = false;
        if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
            //get current sub wallet balance
            $playerBalance = $this->queryPlayerBalance($sbe_playerName);

            //for sub wallet
            $afterBalance = @$playerBalance['balance'];
            $result["external_transaction_id"] = $transaction_id;
            if(!empty($afterBalance)){
                $result["currentplayerbalance"] = $afterBalance;
            }
            $result["userNotFound"] = false;
            $success = true;
            //update
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                //withdraw
                if(!$notRecordTransaction){
                    $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
                            $this->transTypeSubWalletToMainWallet());
                }
            } else {
                $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }

        } else {
            $result["userNotFound"] = true;
        }
        return array($success, $result);
    }

    public function queryPlayerBalance($userName) {
        return $this->getAccountInfo($userName);
    }

    public function processResultForQueryPlayerBalance($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = array();

        if ($success) {
            $resultArr = json_decode($resultArr["result"],true);
            if(isset($resultArr['balance'])){
                $result['balance'] = @floatval($resultArr['balance']);

                if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
                    $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
                } else {
                    $this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
                }
            }
        }
        return array($success, $result);

    }

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'id_ID';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi_VN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'ko_KR';
                break;
            default:
                $lang = 'en_US';
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName, $extra=null) {
        $gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
        $token        = $this->getPlayerTokenByUsername($playerName);

        $params = array(
            "acctId"    => $gameUsername,
            "language"  => $this->getLauncherLanguage($extra['language'])
            );
        unset($extra['language']);

        $token = $this->thirdParty . "-" . $this->tag . "-" . $token;

        $url = $this->game_url . "?" . http_build_query(array_merge($params,$extra)) . "&token=" .$token;

        $this->CI->utils->debug_log('queryForwardGame [EBET Spade] =======================================>' . $url);

        $data = [
            "url"       => $url,
            "success"   => true
            ];

        return $data;

    }

    public function processResultQueryForwardGame($params){

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

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        //observer the date format
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate=$startDate->format('Y-m-d H:i:s');
        $endDate=$endDate->format('Y-m-d H:i:s');
        $page = 1;
        $take = 5000; // max data get

        return $this->_continueSync( $startDate, $endDate, $take, $page );

    }

    function _continueSync( $startDate, $endDate, $take = 0, $page = 1){
        $return = $this->syncEbetSpadeGamelogs($startDate,$endDate,$take,$page);
        if(isset($return['count'])){
            if( $return['count'] == $take ){
                $page++;
                return $this->_continueSync( $startDate, $endDate, $take, $page );
            }
        }
        return $return;
    }


    function syncEbetSpadeGamelogs($startDate,$endDate,$take,$page){

        $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncGameRecords',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'take' => $take,
                'skip' => $page
        );

        $params = array(
                "startDate" => $startDate,
                "endDate" => $endDate,
            // "type" => "casinolive", // game type
                "pageSize" => $take, //page Size default is 5000
                "pageNumber" => $page, // page number
                "method_action" => "getrawbethistory"
        );

        $this->utils->debug_log('=====================> EBETSPADE syncOriginalGameLogs params', $params);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    function processResultForSyncGameRecords($params) {

        $this->CI->load->model(array('ebetspade_game_logs'));
        $resultArr = $this->getResultJsonFromParams($params);

        $resultArr['isgamelogs'] = true; // tag gamelogs for process boolean
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $this->utils->debug_log('=====================> EBETSPADE syncOriginalGameLogs result', count($resultArr));

        $rarr = json_decode($resultArr['result'],true);
        $gameRecords = isset($rarr["betHistories"])?$rarr["betHistories"]:array();

        if ($success) {
            if (!empty($gameRecords)) {
                $availableRows = $this->CI->ebetspade_game_logs->getAvailableRows($gameRecords);

                if (!empty($availableRows)) {
                    $gameRecordsPush = array();
                    foreach ($availableRows as $record) {
                        $record['ticketTime'] = date("Y-m-d H:i:s", ($record['ticketTime']/1000));

                        $recordPush = array();
                        $gameUsername = strtolower($record['acctId']);
                        $player_id = $this->getPlayerIdInGameProviderAuth($gameUsername);

                        if(!$player_id){
                            //still save it
                            $player_id=0;
                            //  continue; # if not exist player continue
                        }

                        $recordPush['playerId']      = isset($player_id) ? $player_id :null;
                        $recordPush['ticketId']      = isset($record['ticketId']) ? $record['ticketId'] :null;
                        $recordPush['acctId']        = isset($record['acctId']) ? $gameUsername :null;
                        $recordPush['ticketTime']    = isset($record['ticketTime']) ? $this->gameTimeToServerTime($record['ticketTime']) :null;
                        $recordPush['categoryId']    = isset($record['categoryId']) ? $record['categoryId'] :null;
                        $recordPush['gameCode']      = isset($record['gameCode']) ? $record['gameCode'] :null;
                        $recordPush['currency']      = isset($record['currency']) ? $record['currency'] :null;
                        $recordPush['betAmount']     = isset($record['betAmount']) ? $record['betAmount'] :null;
                        $recordPush['result']        = isset($record['result']) ? $record['result'] :null;
                        $recordPush['winLoss']       = isset($record['winLoss']) ? $record['winLoss'] :null;
                        $recordPush['jackpotAmount'] = isset($record['jackpotAmount']) ? $record['jackpotAmount'] :null;
                        $recordPush['betIp']         = isset($record['betIp']) ? $record['betIp'] :null;
                        $recordPush['luckyDrawId']   = isset($record['luckyDrawId']) ? $record['luckyDrawId'] :null;
                        $recordPush['completed']     = isset($record['completed']) ? $record['completed'] :null;
                        $recordPush['roundId']       = isset($record['roundId']) ? $record['roundId'] :null;
                        $recordPush['sequence']      = isset($record['sequence']) ? $record['sequence'] :null;
                        $recordPush['channel']       = isset($record['channel']) ? $record['channel'] :null;
                        $recordPush['balance']       = isset($record['balance']) ? $record['balance'] :null;
                        $recordPush['jpWin']         = isset($record['jpWin']) ? $record['jpWin'] :null;
                        $recordPush['thirdParty']    = isset($record['thirdParty']) ? $record['thirdParty'] :null;
                        $recordPush['tag']           = isset($record['tag']) ? $record['tag'] :null;
                        $recordPush['ebet_spade_id'] = isset($record['id']) ? $record['id'] :null;

                        //SBE use
                        $recordPush['external_uniqueid']   = $record['id']; //add external_uniueid for og purposes
                        $recordPush['uniqueid']            = $record['id']; //add external_uniueid for og purposes
                        $recordPush['response_result_id']  = $responseResultId;
                        array_push($gameRecordsPush,$recordPush);
                    }
                    $count = count($availableRows);
                    if ($availableRows) {
                        foreach ($gameRecordsPush as $key => $gameRecord) {
                            $data = $this->CI->ebetspade_game_logs->insertGameLogs($gameRecord);
                        }
                    }
                }
            }

        }

        return array($success,array('count'=>count($gameRecords)));
    }

    function syncMergeToGameLogs($token) {

        $this->CI->load->model(array('game_logs', 'player_model', 'ebetspade_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        //observer the date format
        $startDate->modify($this->getDatetimeAdjust());

        $startDate=$startDate->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');
        // $this->gameTimeToServerTime
        $this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


        $rlt = array('success' => true);
        $result = $this->CI->ebetspade_game_logs->getGameLogStatistics($startDate, $endDate);
        $cnt = 0;

        if ($result) {

            $unknownGame = $this->getUnknownGame();
            // echo "<pre>";print_r($result);exit;

            foreach ($result as $data) {
                $player_id = $data->playerId;

                if (!$player_id) {
                    continue;
                }

                $cnt++;

                $bet_amount = $data->bet_amount;
                $realbet = $data->bet_amount;
                $result_amount = (float)$data->result_amount;

                $game_description_id = $data->game_description_id;
                $game_type_id = $data->game_type_id;

                //should use processGameDesction function
                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $extra = array('trans_amount'=> $realbet);

                $this->syncGameLogs(
                        $game_type_id,
                        $game_description_id,
                        $data->game_code,
                        $data->game_type,
                        $data->game,
                        $data->playerId,
                        $data->username,
                        $bet_amount,
                        $result_amount,
                        null, # win_amount
                        null, # loss_amount
                        null, # after_balance
                        0, # has_both_side
                        $data->external_uniqueid,
                        $data->date_created, //start
                        $data->date_created, //end
                        $data->response_result_id,
                        1,
                        $extra
                );

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
        return $rlt;
    }

    function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }


    function login($userName, $password = null) {
        return $this->queryForwardGame($userName, $password);
    }

    function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    function logout($playerName, $password = null) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultLogout',
                'playerName' => $playerName
        );

        //logout_before_launch_game

        // if($this->logout_before_launch_game){
        //  $this->logout($playerName);
        // }

        $params = array(
                "username" => $playerName,
                "method_action" => "logout",
        );

        return $this->callApi(self::API_logout, $params, $context);

    }

    function processResultLogout($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $resultData=json_decode($resultJsonArr['result'], true);

        if(isset($resultData['data']['Code']) && $resultData['data']['Code']=='22000'){
            //User hasn't Login.
            $success=true;
        }

        return array($success, $resultJsonArr);
    }

    function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
        // return array("success" => true);
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

    # HELPER ########################################################################################################################################

    function verify($str, $signature) {
        $signature = base64_decode($signature);
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->verify($str, $signature);
    }

    function encrypt($str) {
        $this->rsa->loadKey($this->private_key);
        $signature = $this->rsa->sign($str);
        $signature = base64_encode($signature);
        return $signature;
    }

    public function convertTransactionAmount($amount){
        return floor($amount);
    }

    public function gameAmountToDB($amount) {
        //only need 2
        return round(floatval($amount), 2);
    }

    public function onlyTransferPositiveInteger(){
        return true;
    }

    # HELPER ########################################################################################################################################

}

/*end of file*/