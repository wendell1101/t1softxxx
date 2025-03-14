<?php require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Class Game_api_hg
 *
 *
 * Back Office
 *  url : https://238live.com
 *  username: trieglive@admin.com
 *  password : 123456
 *
 * Game Link
 *  iframe_module/goto_hg_game/slots
 *  iframe_module/goto_hg_game/live
 *  iframe_module/goto_hg_game/table
 */
class Game_api_hg extends Abstract_game_api {

    const FUND_TRANSFER_MODEL_URI = '/cgibin/EGameIntegration';
    const FORWARD_GAME_URI = '/login/visitor/checkLoginGI.jsp';

    const DEFAULT_GAME = array(
        'live' =>  '0000000000000004',   // default bacarrat
        'table' => '0000000000000026',
        'slots' => '0000000000000048'    // golden 7
    );

    const SUCCESS = 0;
    const REAL = 1;
    const NO_ERROR = '';

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('api_url');
        $this->currency = $this->getSystemInfo('currency');
        $this->api_bet_logs = $this->getSystemInfo('api_bet_logs');
        $this->web_api_username = $this->getSystemInfo('web_api_username');
        $this->web_api_password = $this->getSystemInfo('web_api_password');
        $this->casino_id = $this->getSystemInfo('casino_id');

        $this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
        $this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');

        $this->default_lang = $this->getSystemInfo('default_lang', 'en');

        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '100');

        $this->action = '';
        $this->is_bet_logs = false;
    }

    public function getPlatformCode() {
        return HG_API;
    }

    public function generateXMLParams($params,$method='request',$response_id=null) {

        if($this->is_bet_logs) {
            $response = array( $this->action => $params);
        } else {
            $data = array();
            foreach($params as $key => $value) {
                $api_params = array(
                    'name_attr' => $key,
                    '_value' => $value
                );
                array_push($data, $api_params);
            }

            $response = array(
                $method => array(
                    'action_attr' => $this->action,
                    'element' => array(
                        'properties' => $data
                    )
                )
            );
        }

        if(!empty($response_id)){
            $response['response']['element']['id_attr']=$response_id;
        }
        $params = $this->utils->arrayToXml($response);
        $params = strtr($params, array("\n" => '',"\r" => ''));

        return $params;
    }

    public function callback($method, $params = null) {
        if ($method == 'validatemember') {
            $this->action = 'registration';
            $xml   = simplexml_load_string($params);
            $array = json_decode(json_encode((array) $xml), true);
            $array = array($xml->getName() => $array);

            $action = @$array['request']['@attributes']['action'];
            $id = @$array['request']['element']['@attributes']['id'];
            $gameUsername = @$array['request']['element']['properties'][0];
            $password = @$array['request']['element']['properties'][1];

            $data = array(
                "username" => $gameUsername,
                "chatNickName" => $gameUsername,
                "vendorid" => $this->casino_id,
                "currencyid" => $this->getPlayerHoGamingCurrency($gameUsername),
                "status" => self::SUCCESS,
                "errdesc" => null,
            );

            # 503 ERR_WITH_INVALID_ACTION
            if($action != $this->action){
                $data['status'] = 503;
                $data['errdesc'] = 'ERR_WITH_INVALID_ACTION';
                return $this->generateXMLParams($data,'response',$id);
            }

            # 205 ERROR_INVALID_ACCOUNT_ID
            $player_id = $this->getPlayerIdInGameProviderAuth($gameUsername);
            if(empty($player_id)){
                $data['username'] = $gameUsername;
                $data['status'] = 205;
                $data['errdesc'] = 'ERROR_INVALID_ACCOUNT_ID';
                return $this->generateXMLParams($data,'response',$id);
            }
            
            # 213 ERROR_INVALID_PASSWORD
            $game_passord = $this->getPasswordByGameUsername($gameUsername);
            if($password != $game_passord){
                $data['username'] = $gameUsername;
                $data['status'] = 205;
                $data['errdesc'] = 'ERROR_INVALID_PASSWORD';
                return $this->generateXMLParams($data,'response',$id);
            }

            # 204 ERROR_ACCOUNT_SUSPENDED
            $blocked = $this->isBlockedUsernameInDB($gameUsername);
            if($blocked) {
                $data['username'] = $gameUsername;
                $data['status'] = 204;
                $data['errdesc'] = 'ERROR_ACCOUNT_SUSPENDED';
                return $this->generateXMLParams($data,'response',$id);
            }

            return $this->generateXMLParams($data,'response',$id);
        }
    }

    protected function customHttpCall($ch, $params) {
        $xmlParams = $this->generateXMLParams($params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlParams);
    }

    protected function getHttpHeaders($params) {
        return array('Content-type' => 'text/xml');
    }

    protected function convertResultXmlFromParams($params) {
        $resultText = trim(@$params['resultText']);
        $resultXml = null;
        if (!empty($resultText)) {
            $resultXml = new SimpleXMLElement($resultText);
        }
        return $resultXml;
    }

    public function generateUrl($apiName, $params) {
        if($this->is_bet_logs) {
            $url = $this->api_bet_logs;
        } else {
            $url = $this->api_url.self::FUND_TRANSFER_MODEL_URI;
        }

        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArray, $playerName) {
        $properties = $resultArray['element']['properties'];
        if($properties[2] == self::SUCCESS) {
            $success = true;
        } else {
            $success = false;
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log("==========HG API GOT ERROR=============", $properties[3], $playerName);
        }
        return $success;
    }

    private function getPlayerHoGamingCurrency($gameUsername){
        # use correct currency code
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        if(!is_null($playerId)){
            $this->CI->load->model(array('player_model'));
            $currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);

            if(!is_null($currencyCode)){
                # replace with correct Code
                switch ($currencyCode) {
                    case 'CNY':
                        $currencyCode = 'RMB';
                        break;
                }
                return $currencyCode;
            }else{
                return $this->currency;
            }
        }else{
            return $this->currency;
        }
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

        $params = array(
            'username' => $gameUsername,
            'mode' => self::REAL,
            'firstname' => $gameUsername,
            'lastname' => $gameUsername,
            'currencyid' => $this->getPlayerHoGamingCurrency($gameUsername),
        );

        $this->action = 'registration';

        return $this->callApi(self::API_createPlayer, $params, $context);

    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = array(
            "player" => $gameUsername,
            "exists" => false
        );

        if($success) {
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
            $result["exists"] = true;
        }

        return array($success, $result);
    }

    public function login($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => self::REAL,
            'firstname' => $gameUsername,
            'lastname' => $gameUsername,
            'currencyid' => $this->getPlayerHoGamingCurrency($gameUsername),
        );

        $this->action = 'registration';

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        if($success) {
            $result = array(
                'gameName' => $resultArr['element']['properties'][0],
                'ticketId' => $resultArr['element']['properties'][1]
            );
        }
        
        return array($success, $result);
    }

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => self::REAL,
            'firstname' => $gameUsername,
            'lastname' => $gameUsername,
            'currencyid' => $this->getPlayerHoGamingCurrency($gameUsername),
        );

        $this->action = 'registration';

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = array();

        if($success){
            $result['exists'] = true;
        }else{
            $success = true;
            $result['exists'] = false;
        }

        return array($success, $result);
    }

    public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => self::REAL,
        );

        $this->action = 'accountbalance';

        return $this->callApi(self::API_login, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        $result = [];
        if($success) {
            $result['balance'] = $this->gameAmountToDB($resultArr['element']['properties'][1]);
        }
        return array($success, $result);
    }

    private function getReasons($error_code){
        switch ($error_code) {
            case 101: 
            case 102: 
            case 103: 
            case 111: 
            case 112: 
            case 113: 
            case 300: 
            case 301: 
            case 501: 
            case 502: 
            case 511: 
            case 512: 
            case 514: 
            case 515: 
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 104: 
                return self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                break;
            case 106: 
            case 115: 
            case 321: 
                return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                break;
            case 116: 
            case 302: 
            case 608: 
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case 119: 
                return self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            case 121: 
                return self::REASON_DISABLED_DEPOSIT_BY_GAME_PROVIDER;
                break;
            case 122: 
            case 123: 
            case 303: 
            case 310: 
            case 311: 
            case 422: 
            case 423: 
                return self::REASON_FAILED_FROM_API;
                break;
            case 124: 
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => self::REAL,
            'amount' => $amount,
            'refno' => $secure_id,
            'currencyid' => $this->getPlayerHoGamingCurrency($gameUsername),
        );

        $this->action = 'deposit';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $status =  $resultArr['element']['properties'][0];
        $success = false;
        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($status == self::SUCCESS) {
            $paymentId = $resultArr['element']['properties'][2];
            $result = $this->confirmDeposit(self::SUCCESS, $paymentId, self::NO_ERROR);
            $success = false;
            if($result['success']) {// confirm deposit
                // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
                // if ($playerId) {
                //     $this->insertTransactionToGameLogs($playerId, $playerName, null, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
                // } else {
                //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                //     $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
                // }
                $success = true;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs']=true;
            }else{
                $error_code = @$result['element']['properties'][0];
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id']=$this->getReasons($error_code);
            }
        }else{
            $error_code = @$resultArr['element']['properties'][0];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($error_code);
        }

        return array($success, $result);
    }

    public function confirmDeposit($status, $playmentId, $errorDesc = 0) {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultDepositConfirm',
        );

        $params = array(
            'status' => $status,
            'paymentid' => $playmentId,
            'errdesc' => $errorDesc
        );

        $this->action = 'deposit-confirm';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultDepositConfirm($params) {
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $success = $resultArr['element']['properties'][0] == self::SUCCESS ? true : false;

        return array($success, $resultArr);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => self::REAL,
            'amount' => $amount,
            'refno' => $secure_id,
            'currencyid' => $this->getPlayerHoGamingCurrency($gameUsername),
        );

        $this->action = 'withdrawal';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $resultXml = $this->getResultXmlFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $status =  $resultArr['element']['properties'][0];
        $success = false;
        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($status == self::SUCCESS) {
            $paymentId = $resultArr['element']['properties'][2];
            $result = $this->confirmWithdrawal(self::SUCCESS, $paymentId, self::NO_ERROR);
            $success = false;
            if($result['success']) {      // confirm withdrawal
                // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
                // if ($playerId) {
                //     $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                //     $this->insertTransactionToGameLogs($playerId, $gameUsername, null, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
                // } else {
                //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                //     $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
                // }
                $success = true;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs']=true;
            }
        }else{
            $error_code = @$resultArr['element']['properties'][0];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($error_code);
        }

        return array($success, $result);
    }

    public function confirmWithdrawal($status, $playmentId, $errorDesc = 0) {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultWithdrawalConfirm',
        );

        $params = array(
            'status' => $status,
            'paymentid' => $playmentId,
            'errdesc' => $errorDesc
        );

        $this->action = 'withdrawal-confirm';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultWithdrawalConfirm($params) {
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $success = $resultArr['element']['properties'][0] == self::SUCCESS ? true : false;

        return array($success, $resultArr);
    }

    public function queryForwardGame($playerName, $extra=null) {
        $result = $this->login($playerName, $extra);

        if ($result['success']) {

            $params = array(
                'ticketId' => $result['ticketId'],
                'lang' => isset($extra['language']) ? $extra['language'] : $this->default_lang
            );

            if(!empty($extra['game_type'])) {
                $params['gameType'] = self::DEFAULT_GAME[$extra['game_type']];
            }
            $url = $this->api_url.self::FORWARD_GAME_URI. '?' .http_build_query($params) ;

            return array('success'=>true,'url' => $url);
        }
        return array('success'=>false,'url' => '');
    }

    /**
     * If the Data requested is very large to retrieve,
     * the following error will be displayed by the system. Application
     * throws the following error based on the date/time range selected by the player.
     *
     * Status – 0011: The data you have requested is too huge to be retrieved. Reduce the Date/Time Range and try again.
     */
    public function syncOriginalGameLogs($token = false) {
        $hogamingTimeout = $this->utils->getJsonFromCache("HOGAMING-timeout");
        if($hogamingTimeout >= time()){
            $this->CI->utils->debug_log('HOGAMING-timeout API ==============> skip Syncing due to 1 call per minute restriction.');
            return array("success"=>true,"details"=>"skip Syncing due to 1 call per minute restriction. ");
        }

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $startDate->modify($this->getDatetimeAdjust());

        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'startDate' => $startDate,
            'endDate' => $endDate,
        );

        $params = array(
            'Username' => $this->web_api_username,
            'Password' => $this->web_api_password,
            'CasinoId' => $this->casino_id,
            'startTime' => $startDate,
            'EndTime' => $endDate,
            'Usertype' => 'Play',
        );

        $this->action = 'GetAllBetDetailsPerTimeInterval';
        $this->is_bet_logs = true;
        // echo "<pre>";print_r($params);exit;
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }


    public function processResultForSyncGameRecords($params){
        $this->CI->load->model(array('hg_game_logs', 'player_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $logsResult = $this->generateLogsResult($resultArr[0]);
        $success = true;
        $data_count = 0;

        if (!empty($logsResult['STATUS_CODE'])) {
            $this->utils->saveJsonToCache("HOGAMING-timeout",strtotime("+ 91 seconds"));
            $this->CI->utils->debug_log('Ho GAMING API ADD timeout ==============> + 1 minutes');
            return array(true,['error'=>"add sleep time"]);
        } else {
            $gameRecords = $logsResult['Betinfo'] === array_values($logsResult['Betinfo']) ? $logsResult['Betinfo'] : array($logsResult['Betinfo']);
            $this->CI->utils->debug_log("==========HG GAME LOGS DATA =============", $gameRecords);

            $availableRows = $this->CI->hg_game_logs->getAvailableRows($gameRecords);

            $data = [];

            if (!empty($availableRows)) {
                foreach ($availableRows as $record) {
                    $data['bet_start_date'] = $this->utils->modifyDateTime($record['BetStartDate'], $this->gameTimeToServerTime);
                    $data['bet_end_date'] = $this->utils->modifyDateTime($record['BetEndDate'], $this->gameTimeToServerTime);
                    $data['account_id'] =  $record['AccountId'];
                    $data['table_id'] =  $record['TableId'];
                    $data['table_name'] =  $record['TableName'];
                    $data['game_id'] =  $record['GameId'];
                    $data['bet_id'] =  $record['BetId'];
                    $data['bet_amount'] =  $record['BetAmount'];
                    $data['payout'] =  $record['Payout'];
                    $data['currency'] =  $record['Currency'];
                    $data['game_type'] =  $record['GameType'];
                    $data['bet_spot'] =  $record['BetSpot'];
                    $data['bet_no'] =  $record['BetNo'];
                    $data['bet_mode'] =  $record['BetMode'];

                    //extra info from SBE
                    $data['username'] = $record['AccountId'];
                    $data['external_uniqueid'] = $record['BetId'];
                    $data['response_result_id'] = $responseResultId;

                    $this->CI->utils->debug_log("==========HG GAME LOGS DATA =============", $gameRecords);

                    $this->CI->hg_game_logs->insertGameLogs($data);
                    $data_count++;
                }

                $success = true;
            }
        }

        $result['data_count'] = $data_count;
        return array($success, $result);
    }


    public function generateLogsResult($result) {
        $logs =  strtr($result, array("\n" => ''));

        // strip Total Records tag. convert to xml will error if not remove
        $logs = preg_replace('/<TotalRecords[^>]*>.*?<\/TotalRecords>/i', '', $logs);

        $logsXml = new SimpleXMLElement($logs);
        $logsResult = json_decode(json_encode($logsXml), true);

        return $logsResult;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row->originalGameTypeName;
        $extra = array('game_code' => $external_game_id,'game_name' => $row->originalGameName);

        $game_type_id = isset($unknownGame->game_type_id) ? $unknownGame->game_type_id : null;
        $game_type = isset($unknownGame->game_name) ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'hg_game_Logs'));
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');
        $result = $this->CI->hg_game_Logs->getGameLogStatistics($startDate, $endDate);
        $count = 0;

        if($result) {
            $unknownGame = $this->getUnknownGame();

            foreach ($result as $data) {
                $count++;

                $game_description_id = $data->game_description_id;
                $game_type_id = $data->game_type_id;

                if(empty($row['game_type_id'])&&empty($row['game_description_id'])){
                    list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($data, $unknownGame);
                }

                $extra['trans_amount'] = $data->bet_amount;
                $extra['table'] = $data->round_id;

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $data->game_code,
                    $data->originalGameTypeName,
                    $data->originalGameName,
                    $data->player_id,
                    $data->gameUsername,
                    $data->bet_amount,
                    $data->result_amount,
                    null, // win_amount
                    null, // loss_amount
                    null, // after balance
                    0,    // has both side
                    $data->external_uniqueid,
                    $data->bet_start_date, //start
                    $data->bet_end_date, // end
                    $data->response_result_id,
                    Game_logs::FLAG_GAME,
                    $extra
                );
            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $count);

        return  array('success' => true );
    }


    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function changePassword($playerName, $oldPassword, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
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

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }
}