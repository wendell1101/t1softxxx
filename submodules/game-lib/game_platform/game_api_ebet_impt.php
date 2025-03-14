<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_impt extends Abstract_game_api {

    private $api_url;
    private $channelId;
    private $islive;
    private $tag;
    private $public_key;
    private $private_key;
    private $thirdParty;
    private $currency;
    private $logout_before_launch_game;
    private $flash_url;
    private $html5_url;
    private $language;
    private $fun_game_url;

    const STATUS_FREEZE="1";
    const STATUS_UNFREEZE="0";

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->channelId = $this->getSystemInfo('channelId');
        $this->islive = $this->getSystemInfo('live');
        $this->thirdParty = $this->getSystemInfo('thirdParty');
        $this->tag = $this->getSystemInfo('tag');
        $this->public_key = $this->getSystemInfo('public_key');
        $this->private_key = $this->getSystemInfo('private_key');
        $this->currency = $this->getSystemInfo('currency');
        $this->flash_url = $this->getSystemInfo('flash_url');
        $this->html5_url = $this->getSystemInfo('html5_url');
        $this->language = $this->getSystemInfo('language');
        $this->fun_game_url = $this->getSystemInfo('fun_game_url');



        $this->logout_before_launch_game=$this->getSystemInfo('logout_before_launch_game', true);

        # init RSA
        $this->rsa = new Crypt_RSA();
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->rsa->setHash('md5');
    }

    public function getPlatformCode() {
        return EBET_IMPT_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/json");
        return array("Accept" => "application/json");
    }

    protected function customHttpCall($ch, $params) {
        $action = $params["method_action"];
        unset($params["method_action"]); //unset action not need on params

        $postParams = array(
            "channelId" => $this->channelId,
            "thirdParty" => $this->thirdParty,
            "tag" => $this->tag,
            "action" => array(
                "command" => $action,
                "parameters" => $params
            ),
            "live" => $this->islive,
            "timestamp" => time()
        );
        $postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);
        // echo"<pre>";print_r($postParams);exit();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
    }

    function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = false;
        $code = json_decode($resultArr['result'],true);

        if (isset($resultArr['isgamelogs']) && !empty($resultArr['isgamelogs'])) {
            $success = ($resultArr['status']== 200) ? true : false;
        } else if(isset($code['Code'])){
            $success = ($code['Code']== 0) ? true : false;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('EBETIMPT got error ======================================>', $responseResultId, 'result', $resultArr, $playerName);
            $success = false;
        }
        //echo $success ? 'true' : 'false';exit();
        return $success;
    }

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*function getGameTimeToServerTime() {
        return '+12 hours'; #(GMT -4)
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*function getServerTimeToGameTime() {
        return '-12 hours';
    }*/

    function callback($result = null, $platform = 'web') {
        if($platform == 'web'){
            $playerId = $this->getPlayerIdByExternalAccountId($result['userId']);
            if(!empty($playerId)){
                $status = array(
                    "status" => 200,
                );
            }else{
                $status = array(
                    "status" => 0,
                    "message"=> "User not found."
                );
            }
            return $status;
        }
    }

    function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId
        );

        $params = array(
            'membercode'    => $playerName,
            'password'      => $password,
            'currency'      => $this->currency,
            'method_action' => "createplayer"
        );
        // echo "<pre>";print_r($params);exit;
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    function processResultForCreatePlayer($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        // $result = json_decode($resultJsonArr['result'],true);
        $result = ['response_result_id'=>$responseResultId];

        $rlt=$this->CI->utils->decodeJson($resultJsonArr['result']);
        // $playerId = $this->getVariableFromContext($params, 'playerId');
        // if($success){
            //update external AccountID
            // $this->updateExternalAccountIdForPlayer($playerId, $rlt['playerId']);
        // }
        return array($success, $result);

    }

    function depositToGame($userName, $amount, $transfer_secure_id=null) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        // $remitno = date("YmdHis").rand(1,1000);
        $externaltransactionid = md5(time().'text');
        $externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
            'amount' => $amount,
            'transaction_id'=>$externaltransactionid,
        );
        $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
        // $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);
        $params = array(
            "membercode" => $playerName,
            "amount" => $amount,//-100 means withdraw from game platform
            "externaltransactionid" => $externaltransactionid,
            "producttype" => 0,
            "method_action" => "createtransaction"
        );

        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    function processResultForDepositToGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $result = array('response_result_id' => $responseResultId);
        $success = false;
        $transaction_id=$this->getVariableFromContext($params, 'transaction_id');
        // echo "<pre>";print_r($resultArr);exit;
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

    function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $externaltransactionid = md5(time().'text');
        $externalId = $this->getExternalAccountIdByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
            'transaction_id'=>$externaltransactionid,
            'amount' => $amount
        );

        $params = array(
            "membercode"            => $playerName,
            "amount"                => -$amount,//-100 means withdraw from game platform
            "externaltransactionid" => $externaltransactionid,
            "producttype"           => 0,
            "method_action"         => "createtransaction"
        );
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    function processResultForWithdrawFromGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $amount = $this->getVariableFromContext($params, 'amount');

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
                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
                    $this->transTypeSubWalletToMainWallet());

            } else {
                $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }

        } else {
            $result["userNotFound"] = true;
        }
        return array($success, $result);
    }

    function queryPlayerBalance($userName) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        );

        $params = array(
            "membercode"    => $playerName,
            "producttype"   => 0,
            "method_action" => "getbalance"
        );
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    function processResultForQueryPlayerBalance($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array('response_result_id' => $responseResultId);

        $resultArr = json_decode($resultArr["result"],true);
        if(!isset($resultArr['Balance'])){
            $success=false;
        }

        if ($success) {

            // if(isset($resultArr['Balance'])){
                $result['balance'] = @floatval($resultArr['Balance']);
                // echo "<pre>";print_r($resultArr);exit;
                // if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
                $this->CI->utils->debug_log('query balance playerName', $playerName, 'balance', $result['balance']);
                // } else {
                //     $this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
                // }
            // }
        }
        return array($success, $result);
    }

    function changePassword($playerName, $oldPassword = null, $newPassword) {
        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        // $externalId = $this->getExternalAccountIdByPlayerUsername($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'playerName' => $playerName
        );

        $params = array(
            "membercode"    => $playerName,
            "password" => $newPassword,
            // "producttype"   => 0,
            "method_action" => "resetpassword"
        );

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    function processResultForChangePassword($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = ['response_result_id'=>$responseResultId];

        // if ($success) {
            // $resultArr = json_decode($resultArr["result"],true);

            // if(isset($resultArr['Balance'])){
            // $result['balance'] = @floatval($resultArr['Balance']);
            // echo "<pre>";print_r($resultArr);exit;
            // if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
            //     $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
            // } else {
            //     $this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            // }
            // }
        // }
        return array($success, $result);
    }

    function login($userName, $password = null) {
        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $password = $this->getPasswordString($userName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName
        );

        $params = array(
            "membercode"    => $playerName,
            "password"      => $password,
            "method_action" => "authenticateplayer"
        );
        // echo "<pre>";print_r($params);exit;
        return $this->callApi(self::API_login, $params, $context);
    }

    function processResultForLogin($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
         // echo "<pre>";print_r($resultArr);exit;
        $code = json_decode($resultArr['result'],true);
        $success = $code['Code'] == 0 ? true : false;
        $this->utils->debug_log("Check player if exist ============================>", $success);
        $result['login'] = $success;
        return array(true, $result);
    }

    function queryForwardGame($userName, $extra=null) {
        $login = $this->login($userName);
        if($login) {
            $playerName = $this->getGameUsernameByPlayerUsername($userName);
            $password = $this->getPasswordString($userName);

            $language = isset($extra['language']) ? $extra['language'] : $this->language;

            if($extra['mode'] == "fun"){
                $game_url = $this->fun_game_url;
                $params = array(
                    "mode"           => "offline",
                    "affiliates"        => "1",
                    "language"        => $language,
                    "game"    => $extra['game_name']
                );
            } else {
                $game_url = ($extra['type'] == "flash") ? $this->flash_url : $this->html5_url;
                $params = array(
                    "username"           => strtoupper($playerName),
                    "password"        => $password,
                    "lang"        => $language,
                    "game"    => $extra['game_name']
                );
            }
            $url_params = http_build_query($params);
            $generateUrl = $game_url.'?'.$url_params;
            $data = [
                'url' => $generateUrl,
                'success' => true
            ];
            $this->utils->debug_log(' EBET IMPT generateUrl - =================================================> ' . $generateUrl);
            return $data;
        }
    }

    function blockPlayer($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        // $success = $this->blockUsernameInDB($playerName);
        // return array("success" => true);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "membercode"    => $gameUsername,
            "frozenstatus" => self::STATUS_FREEZE,
            "method_action" => "freezeplayer"
        );

        return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    function processResultForBlockPlayer($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = ['response_result_id'=>$responseResultId];

        // if ($success) {
            // $resultArr = json_decode($resultArr["result"],true);

            // if(isset($resultArr['Balance'])){
            // $result['balance'] = @floatval($resultArr['Balance']);
            // echo "<pre>";print_r($resultArr);exit;
            // if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
            //     $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
            // } else {
            //     $this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            // }
            // }
        // }
        return array($success, $result);
    }

    function unblockPlayer($playerName) {
        // $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        // $success = $this->unblockUsernameInDB($playerName);
        // return array("success" => true);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        // $success = $this->blockUsernameInDB($playerName);
        // return array("success" => true);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnblockPlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "membercode"    => $gameUsername,
            "frozenstatus" => self::STATUS_UNFREEZE,
            "method_action" => "freezeplayer"
        );

        return $this->callApi(self::API_unblockPlayer, $params, $context);

    }

    function syncOriginalGameLogs($token = false) {
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
        $return = $this->syncEbetImptGamelogs($startDate,$endDate,$take,$page);
        if(isset($return['count'])){
            if( $return['count'] == $take ){
                $page ++;
                return $this->_continueSync( $startDate, $endDate, $take, $page );
            }
        }
        return $return;
    }


    function syncEbetImptGamelogs($startDate,$endDate,$take,$page){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'take' => $take,
            'page' => $page
        );
        $params = array(
            "startDate" => $startDate,
            "endDate" => $endDate,
            "pageSize" => $take, //page Size default is 5000
            "pageNumber" => $page, // page number
            "method_action" => "getrawbethistory"
        );
        // echo"<pre>";print_r($params);exit();
        $this->utils->debug_log('=====================> EBETIPMT syncOriginalGameLogs params', $params);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('ebetimpt_game_logs'));
        $resultArr = $this->getResultJsonFromParams($params);
        $resultArr['isgamelogs'] = true; // tag gamelogs for process boolean
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $this->utils->debug_log('=====================> EBETIPMT syncOriginalGameLogs result', count($resultArr));

        $rarr = json_decode($resultArr['result'],true);
        $gameRecords = isset($rarr["betHistories"])?$rarr["betHistories"]:array();

        if ($success) {

            if(!empty($gameRecords)) {
                $availableRows = $this->CI->ebetimpt_game_logs->getAvailableRows($gameRecords);
                if(!empty($availableRows)) {
                    foreach ($availableRows as $record) {
                        $datetime = isset($record['gameDate']) ? $record['gameDate'] / 1000 : NULL;
                        $insertRecord = array();
                        $playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['playerName']));
                        $playerUsername = $this->getGameUsernameByPlayerId($playerID);
                        //Data from EBETIMPT API
                        $insertRecord['betId']          = isset($record['id']) ? $record['id'] : NULL;
                        $insertRecord['thirdParty']     = isset($record['thirdParty']) ? $record['thirdParty'] : NULL;
                        $insertRecord['tag']            = isset($record['tag']) ? $record['tag'] : NULL;
                        $insertRecord['playerName']     = isset($record['playerName']) ? $record['playerName'] : NULL;
                        $insertRecord['fullName']       = isset($record['fullName']) ? $record['fullName'] : NULL;
                        $insertRecord['vipLevel']       = isset($record['vipLevel']) ? $record['vipLevel'] : NULL;
                        $insertRecord['country']        = isset($record['country']) ? $record['country'] : NULL;
                        $insertRecord['gameCode']       = isset($record['gameCode']) ? $record['gameCode'] : NULL;
                        $insertRecord['gameType']       = isset($record['gameType']) ? $record['gameType'] : NULL;
                        $insertRecord['gameDate']       = $this->gameTimeToServerTime(date('Y-m-d H:i:s', ($datetime)));
                        $insertRecord['startAmount']    = isset($record['startAmount']) ? $record['startAmount'] : NULL;
                        $insertRecord['bet']            = isset($record['bet']) ? $record['bet'] : NULL;
                        $insertRecord['win']            = isset($record['win']) ? $record['win'] : NULL;
                        $insertRecord['endAmount']      = isset($record['endAmount']) ? $record['endAmount'] : NULL;
                        //extra info from SBE
                        $insertRecord['Username'] = $playerUsername;
                        $insertRecord['PlayerId'] = $playerID;
                        $insertRecord['external_uniqueid'] = $insertRecord['betId']; //add external_uniueid for og purposes
                        $insertRecord['response_result_id'] = $responseResultId;
                        $insertRecord['gameCode1']       =  $this->getStringBetween("(",")",$insertRecord['gameType']);
                        //insert data to EBETIMPT gamelogs table database
                        $this->CI->ebetimpt_game_logs->insertGameLogs($insertRecord);
                        $count++;
                    }
                }
            }
        }

        return array($success,array('count'=>count($gameRecords)));
    }

    function getStringBetween($var1="",$var2="",$pool){
        $temp1 = strpos($pool,$var1)+strlen($var1);
        $result = substr($pool,$temp1,strlen($pool));
        $dd=strpos($result,$var2);
        if($dd == 0){
            $dd = strlen($result);
        }
        return substr($result,0,$dd);
        //calling
        // echo $this->getStringBetween("(",")",$str);exit();
    }

    function syncMergeToGameLogs($token) {

        $this->CI->load->model(array('ebetimpt_game_logs','game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        //observer the date format
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $rlt = array('success' => true);

        $result = $this->CI->ebetimpt_game_logs->getGameLogStatistics($startDate, $endDate);
        // echo"<pre>";print_r($result);exit();
        $cnt = 0;
        if ($result) {
            $unknownGame = $this->getUnknownGame();
            foreach ($result as $ebetimpt) {

                $resultAmount = ($ebetimpt['win'] == 0) ? -$ebetimpt['bet'] : $ebetimpt['win'] - $ebetimpt['bet'];

                if (!$ebetimpt['PlayerId']) {
                    continue;
                }
                $cnt++;
                $game_description_id = $ebetimpt['game_description_id'];
                $game_type_id = $ebetimpt['game_type_id'];

                //for real bet
                $extra = array('trans_amount'=> $ebetimpt['bet'] );
                //end
                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }
                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $ebetimpt['game_code'],
                    $ebetimpt['game_type'],
                    $ebetimpt['game'],
                    $ebetimpt['PlayerId'],
                    $ebetimpt['UserName'],
                    $ebetimpt['bet'],
                    $resultAmount,
                    null, # win_amount
                    null, # loss_amount
                    $ebetimpt['after_balance'], # after_balance
                    0, # has_both_side
                    $ebetimpt['external_uniqueid'],
                    $ebetimpt['game_date'], //start
                    $ebetimpt['game_date'], //end
                    $ebetimpt['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
        return $rlt;
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
         return $this->returnUnimplemented();
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

    function isPlayerExist($userName) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExists',
            'playerName' => $playerName,
            'userName'=>$userName,
            'playerId'          => $playerId
        );

        $params = array(
            "membercode" => $playerName,
            "method_action" => "checkplayerexists"
        );
        // echo "<pre>";print_r($params);exit();
        return $this->callApi(self::API_isPlayerExist, $params, $context);

    }

    function processResultForIsPlayerExists($params) {
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        if($resultArr['status'] == 200){
            $code = json_decode($resultArr['result'],true);
            if($code['Code'] == 0){
                $success = true;
                $result['exists'] = true;
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            } else {
                $success = true;
                $result['exists'] = false;
            }
        } else {
            $success = false;
            $result['exists'] = null;
        }
        $this->utils->debug_log("Check player if exist ============================>", $result['exists']);
        return array($success, $result);
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