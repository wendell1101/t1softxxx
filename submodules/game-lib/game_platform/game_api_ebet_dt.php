<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';


/**
 * iframe_module/goto_ebet_ag/0     - live
 * iframe_module/goto_ebet_ag/500   - xin slots
 * iframe_module/goto_ebet_ag/6     - hunter fishing
 */
class Game_api_ebet_dt extends Abstract_game_api {

    private $api_url;
    private $channelId;
    private $islive;
    private $tag;
    private $public_key;
    private $private_key;
    private $thirdParty;
    private $currency;

    const TRANSFER_IN = 'IN';
    const TRANSFER_OUT = 'OUT';
    const SUCCESS_CODE = 200;
    const TRANSACTION_SUCCESS = 34;
    const TRANSACTION_FAILED = 35;
    const HAS_ERROR = 'error';    
    const DEFAULT_ODD_TYPE = 'A';
    const LAUNCH_MOBILE_MODE = 'Y';    

    const API_performTransfer = 'performTransfer';

    public function __construct() {
        parent::__construct();
        $this->CI->load->library("language_function");

        $this->api_url        = $this->getSystemInfo('url');
        $this->game_url       = $this->getSystemInfo('game_url');
        $this->channelId      = $this->getSystemInfo('channelId');
        $this->islive         = $this->getSystemInfo('live');
        $this->thirdParty     = $this->getSystemInfo('thirdParty');
        $this->tag            = $this->getSystemInfo('tag');
        $this->public_key     = $this->getSystemInfo('public_key');
        $this->private_key    = $this->getSystemInfo('private_key');
        $this->close_url      = $this->getSystemInfo('close_url');
        $this->fun_game_url      = $this->getSystemInfo('fun_game_url');

        $this->rsa = new Crypt_RSA();
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->rsa->setHash('md5');

        $this->command = '';
    }

    public function getPlatformCode() {
        return EBET_DT_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/json");
    }

    protected function customHttpCall($ch, $params) {
        $postParams = array(
            "channelId"         => $this->channelId,
            "thirdParty"        => $this->thirdParty,
            "tag"               => $this->tag,
            "action" => array(
                "command"    => $this->command,
                "parameters" => $params
            ),
            "live"              => $this->islive,
            "timestamp"         => time()
        );
        $postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);

        // print_r(json_encode($postParams)); exit();
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        if ($resultArr['status'] == self::SUCCESS_CODE) {
            $result = json_decode($resultArr['result'], true);

            if(isset($resultArr['isgamelogs'])&&$resultArr['isgamelogs']){
                $success = true;
            }else{
                $result = json_decode($resultArr['result'], true);

                # RESPONSECODE = 00000 means successful transaction
                if(empty((int)$result['RESPONSECODE'])) {
                    $success = true;
                }else{
                    $success = false;
                }
            }
        }else{
            $success = false;
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->error_log('EBET DT got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'userName'        => $game_username,
            'sbe_userName'    => $playerName
        );

        $params = array(
            'playername'     => $game_username,
            'playerpassword' => $password,
        );

        $this->command = 'create';

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $playerName = $this->getVariableFromContext($params, 'userName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array("player_name" => $playerName);

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName'	  => $playerName
        );

        $params = array(
            "playername"     => $game_username,
        );

        $this->command = 'getamount';

        if(!empty($game_username)){
            return $this->callApi(self::API_queryPlayerBalance, $params, $context);
        }else{
            return array("success"=>false, "exists"=>false, "message" => "player doesn't exist!");
        }
    }

    public function processResultForQueryPlayerBalance($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result = array();
        if ($success) {            
            $response = json_decode($resultArr['result'], true);
            if (!empty($response)) {
                $result['balance'] = $this->gameAmountToDB($response['AMOUNT']);
            }
        }
        return array($success, $result);
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null){        
        $type = self::TRANSFER_IN;
        return $this->performTransfer($userName, $amount, $type, $transfer_secure_id);

    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        $type = self::TRANSFER_OUT;
        return $this->performTransfer($userName, $amount, $type, $transfer_secure_id);
    }
    
    public function performTransfer($userName, $amount,$type, $transfer_secure_id=null){
        $playerName = $this->getGameUsernameByPlayerUsername($userName);
        $transactionId = $this->tag.$playerName.random_string('numeric');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForPerformTransfer',
            'playerName' => $playerName,
            'sbe_playerName' => $userName,
            'amount' => $amount,
            'Type' => $type,
            'transactionId' => $transactionId
        );

        $params = array(
            'playername' => $playerName,         
            'transfer_id' => $transactionId,
            'price' => $amount,
        );

        if ($type == self::TRANSFER_IN) {
            $this->command = 'deposit';
        } 
        if ($type == self::TRANSFER_OUT) {
            $this->command = 'withdraw';
        }

        return $this->callApi(self::API_performTransfer, $params, $context);
    }

    public function processResultForPerformTransfer($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'transactionId');
        $type = $this->getVariableFromContext($params, 'Type');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result['external_transaction_id']=$external_transaction_id;
        
        if ($success) {            
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                $playerBalance = $this->queryPlayerBalance($sbe_playerName);
                $afterBalance = 0;

                if ($playerBalance && $playerBalance['success']) {
                    $afterBalance = $playerBalance['balance'];
                } else {
                    //IF GET PLAYER BALANCE FAILED
                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
                    $afterBalance = $rlt->totalBalanceAmount;
                    $this->CI->utils->debug_log('============= EBET DT AFTER BALANCE FROM WALLET '.$type.' ######### ', $afterBalance);
                }

                # Deposit
                if($type == self::TRANSFER_IN){ // Deposit
                    $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
                }

                # Withdraw
                if($type == self::TRANSFER_OUT) {
                    $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
                }
            } else {
                $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
            }            
            
        }

        return array($success, $result);

    }

    public function isPlayerExist($playerName) {
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExists',
            'playerName' => $playerName
        );

        $params = array(
            "playername" => $game_username,
        );

        $this->command = 'getamount';

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExists($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);        
        $success = false;
        $result = array("exists" => null,);

        if ($resultArr['status'] == self::SUCCESS_CODE) {
            $isExist = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
            $success = true;
            $result['exists'] = $isExist;        
        }

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra=null) {
        $url = null;

        $params = array(            
            "language" => $this->getGameLanguage($extra['language']),
            "gameCode" => $extra['game_code'],
            "isfun" => $extra['game_mode'],
            "closeUrls" => $this->close_url,
        );

        if (empty($extra['game_mode'])) {
            $game_username = $this->getGameUsernameByPlayerUsername($playerName);
            $password = $this->getPasswordString($playerName);

            $credentials = $this->login($game_username, $password);
            $success = $credentials['success'];

            if ($success) {
                $params["slotKey"] = $credentials['slotKey'];   
                $url = $credentials['gameurl'] . "?" . http_build_query($params);
            }
        } else {
            $url = $this->fun_game_url . "?" . http_build_query($params);
        }

        $this->CI->utils->debug_log('queryForwardGame Ebet DT =======================================>' . $url);

        $success = !empty($url);

        $data = array("success" => $success, "url" => $url);
        return $data;
    }

    public function processResultForQueryForwardGame($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        $result['url'] = !empty($resultArr['result']) ?  $resultArr['result'] : array();

        return array($success, $result);
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
        $return = $this->syncEbetDTsGameLogs($startDate,$endDate,$take,$page);
        if(isset($return['count'])){
            if( $return['count'] == $take ){
                $page++;
                return $this->_continueSync( $startDate, $endDate, $take, $page );
            }
        }
        return $return;
    }


    function syncEbetDTsGameLogs($startDate,$endDate,$take,$page){
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
            "pageSize" => $take, //page Size default is 5000
            "pageNumber" => $page, // page number
        );

        $this->command = 'getrawbethistory';

        $this->utils->debug_log('=====================> EBET DT syncOriginalGameLogs params', $params);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('ebet_dt_game_logs'));

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $resultArr['isgamelogs'] = true;
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $count = 0;
        $this->utils->debug_log('=====================> EBET DT syncOriginalGameLogs result', json_decode($resultArr['result'],true)['totalCount']);

        $rarr = json_decode($resultArr['result'],true);
        $gameRecords = isset($rarr["betHistories"])?$rarr["betHistories"]:array();

        if ($success) {

            if (!empty($gameRecords)) {
                $availableRows = $this->CI->ebet_dt_game_logs->getAvailableRows($gameRecords);

                if (!empty($availableRows)) {
                    $gameRecordsPush = array();
                    foreach ($availableRows as $record) {
                        $playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['playerName']));
                        $playerUsername = $this->getGameUsernameByPlayerId($playerID);

                        $record['createTime'] = $this->gameTimeToServerTime(date("Y-m-d H:i:s", ($record['createTime']/1000)));
                        
                        $data['game_unique_id'] = !empty($record['gameUniqueId']) ? $record['gameUniqueId'] : null;
                        $data['third_party']    = !empty($record['thirdParty']) ? $record['thirdParty'] : null;
                        $data['tag']            = !empty($record['tag']) ? $record['tag'] : null;
                        $data['player_name']    = !empty($record['playerName']) ? $record['playerName'] : null;
                        $data['game_code']      = !empty($record['gameCode']) ? $record['gameCode'] : null;
                        $data['bet_price']      = !empty($record['betPrice']) ? (double)$record['betPrice'] : 0.00;
                        $data['credit_before']  = !empty($record['creditBefore']) ? (double)$record['creditBefore'] : 0.00;
                        $data['credit_after']   = !empty($record['creditAfter']) ? (double)$record['creditAfter'] : 0.00;
                        $data['bet_wins']       = !empty($record['betWins']) ? (double)$record['betWins'] : 0.00;
                        $data['prize_wins']     = !empty($record['prizeWins']) ? (double)$record['prizeWins'] : 0.00;
                        $data['create_time']    = !empty($record['createTime']) ? $record['createTime'] : null;
                        $data['parent_id']      = !empty($record['parentId']) ? $record['parentId'] : null;
                        $data['bet_lines']      = !empty($record['betLines']) ? $record['betLines'] : null;
                        
                        //extra info from SBE
                        $data['player_id'] = $playerID;
                        $data['username'] = $playerUsername;
                        $data['external_uniqueid'] = $record['gameUniqueId'];
                        $data['response_result_id'] = $responseResultId;

                        $this->CI->ebet_dt_game_logs->insertGameLogs($data);
                        $count++; # add count inserted data
                    }
                }
            }
        }

        return array($success,array('count'=>count($gameRecords)));
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'ebet_dt_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate=$startDate->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $result = $this->CI->ebet_dt_game_logs->getGameLogStatistics($startDate, $endDate);

        $count = 0;
        if($result) {
            $unknownGame = $this->getUnknownGame();

            foreach ($result as $row) {
                $count++;
                $data = (array)$row;

                $playerId = $this->getPlayerIdInGameProviderAuth($data['player_name']);
                $username = $data['player_name'];

                $game_description_id = $data['game_description_id'];
                $game_type_id = $data['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }

                $extra = array('trans_amount' => $data['bet_amount'],);

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $data['game_code'],
                    $data['game_type'],
                    $data['game'],
                    $playerId,
                    $username,
                    $data['bet_amount'],
                    $data['result_amount'],
                    null, // win_amount
                    null, // loss_amount
                    $data['after_balance'], // after balance
                    0,    // has both side
                    $data['external_uniqueid'],
                    $data['bet_datetime'], //start
                    $data['bet_datetime'], // end
                    $data['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );
            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $count);

        return  array('success' => true );
    }

    public function formatTime($date) {
        return $this->gameTimeToServerTime(date("Y-m-d H:i:s", $date/1000));
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($userName, $oldPassword = null, $newPassword) {
        $playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'sbe_userName' => $userName,
            'playerName' => $playerName,
            'newPassword' => $newPassword
        );
        
        $params = array(
            'playername'     => $playerName,
            'playerpassword' => $newPassword,
        );

        $this->command = 'update';

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    public function processResultForChangePassword($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $sbe_userName = $this->getVariableFromContext($params, 'sbe_userName');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $newPassword = $this->getVariableFromContext($params, 'newPassword');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $sbe_userName);
        
        if ($success) {
            $playerId = $this->getPlayerIdInPlayer($sbe_userName);
            //sync password to game_provider_auth
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        $result = array(
            "player" => $playerName
        );

        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',            
            'external_transaction_id'=>$transactionId
        );

        $params = array(
            'transfer_id' => $transactionId,
        );

        $this->command = 'checktransfer';

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $transactionId = $this->getVariableFromContext($params, 'external_transaction_id');        
        $success = !empty($resultArr) && $resultArr['status'] == self::SUCCESS_CODE;
        $result = array();

        if ($success) {
            $resultResponse = json_decode($resultArr['result'], true);

            if ((int)$resultResponse['RESPONSECODE'] == self::TRANSACTION_SUCCESS) {
                $result["order_status"] = true;
                $result["order_message"] = "Transaction " . $transactionId . " successful.";    
            }
            
            if ((int)$resultResponse['RESPONSECODE'] == self::TRANSACTION_FAILED) {
                $result["order_status"] = false;
                $result["order_message"] = "Transaction " . $transactionId . " failed.";    
            }
        } else {
            $result["order_status"] = false;
            $result["order_message"] = "API fail.";
        }

        return array($success, $result);
    }

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
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

    public function login($playerName, $password = null) {
        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForLogin',
            'playerName'      => $playerName
        );

        $params = array(
            "playername"     => $playerName,
            "playerpassword" => $password,
        );

        $this->command = 'login';

        return $this->callApi(self::API_login, $params, $context);        
    }

    public function processResultForLogin($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);        
        $result = array();

        if ($success) {
            $responseResult = json_decode($resultArr['result'], true);
            $result['slotKey'] = $responseResult['slotKey'];
            $result['gameurl'] = $responseResult['gameurl'];
        }

        return array($success, $result);
    }

    public function getGameLanguage($lang){        
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en_US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'en_US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'en_US';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'en_US';
                break;
            default:
                $lang = 'en_US';
                break;
        }
        return $lang;
    }

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
}

/*end of file*/