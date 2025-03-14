<?php require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
    * API NAME: TANGKAS1
    *
    * @category Game_platform
    * @version not defined
    * @copyright 2013-2022 tot
    * @integrator @andy.php.ph
**/
class Game_api_common_hogaming extends Abstract_game_api {

    # Fields in hogaming_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'bet_start_date', #Bet place time for a game by a player. Format MM/dd/yyyy hh:mm:ss Ex: 01/12/2011 03:59:59
        'bet_end_date', #Game end time for the bet placed by a player. Format MM/dd/yyyy hh:mm:ss Ex: 01/12/2011 04:00:23
        'account_id', #Player Login name of a bet placed
        'table_id', #Table id for a Game type of a game where the bet was placed by a player.
        'table_name', #Table Name for a Game type of a game where the bet was placed by a player.
        'game_id', #Game id for which a bet was placed by a player
        'bet_id', #Bet id for a game placed by a player. BetNo & BetId value will be same.
        'bet_amount', #Bet amount placed by a player for a game Ex:10.0000
        'payout', #Win or loss for the bet placed by a player for a game. Value positive means win. Value negative means loss Ex:10.0000, -10.0000
        'currency', #Player currency code Ex: CNY, USD
        'game_type', #Game type name of a game for a bet by a player Ex: Baccarat, Roulette
        'bet_spot', #Bet placed spot for a game by a player. Ex: Banker, Split bet: 20 and 23
        'bet_no', #Bet id for a game placed by a player. BetNo & BetId value will be same.
        'bet_mode', #Bet Mode for a game placed by a player
        'brand_name', #Brand name
    ];

    # Fields in game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL_V2=[
        'bet_start_date',
        'bet_end_date',
        'account_id',
        'table_id',
        'table_name',
        'game_id',
        'bet_id',
        'bet_amount',
        'payout',
        'currency',
        'game_type',
        'bet_spot',
        'bet_no',
        'bet_mode'
    ];

    const SIMPLE_MD5_FIELDS_FOR_ORIGINAL=[
        'bet_end_date', #Game end time for the bet placed by a player. Format MM/dd/yyyy hh:mm:ss Ex: 01/12/2011 04:00:23
        'account_id', #Player Login name of a bet placed
        'bet_id', #Bet id for a game placed by a player. BetNo & BetId value will be same.
        'bet_amount', #Bet amount placed by a player for a game Ex:10.0000
        'payout', #Win or loss for the bet placed by a player for a game. Value positive means win. Value negative means loss Ex:10.0000, -10.0000
        'bet_spot', #Bet placed spot for a game by a player. Ex: Banker, Split bet: 20 and 23
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'payout',
    ];

    # Fields in game_logs we want to detect changes for merge, and when hogaming_idr_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'table_identifier',
        'game_code',
        'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'payout',
    ];

    const ORIGINAL_GAMELOGS_TABLE = "hogaming_game_logs";

    const FUND_TRANSFER_MODEL_URI = '/cgibin/EGameIntegration';
    const FORWARD_GAME_URI = '/login/visitor/checkLoginGI.jsp';
    const UNAUTHORIZE_ACCESS = 'Unauthorized Access.';

    const TABLEGAMES_GAMETYPE_IDS = array(
        'roullete' => '0000000000000001',
        'blackjack' => '0000000000000003',
        'baccarat' => '0000000000000004',
        'sicbo' => '0000000000000012',
        'dragontiger' => '0000000000000500',
    );

    const BETTYPE_IDS = array(
        'private' => '0',
        'regular' => '1',
        'high_roller' => '2',
        'vip' => '3',
        'low_roller' => '4',
        'agent_1' => '5',
        'agent_2' => '6',
    );

    const GAME_TYPE_LIVE_DEALER = 'live_dealer';

    const SUCCESS = '0';
    const REAL_MODE = 1;
    const DEMO_MODE = 0;
    const NO_ERROR = '';
    const SUCCESS_WITHDRAW = '210';
    const API_syncCancelledGames = "GetBetdetails";
    const API_syncAllCancelledGames = "GetAllbetdetails";
    const API_syncSettledGames = "GetAllBetDetailsPerTimeInterval";


    public static $uri_map = array(
        self::API_generateToken => '/api/token/endpoint',
        self::API_syncGameRecords => '/api/betinfo'
    );

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url');

        $this->api_bet_logs = $this->getSystemInfo('api_bet_logs');
        $this->web_api_username = $this->getSystemInfo('web_api_username');
        $this->web_api_password = $this->getSystemInfo('web_api_password');
        $this->casino_id = $this->getSystemInfo('casino_id');

        $this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
        $this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');

        $this->action = '';
        $this->is_bet_logs = false;

        $this->player_mode = $this->getSystemInfo('player_mode',self::REAL_MODE);
        $this->brand_name = $this->getSystemInfo('brand_name',"hogaming");
        $this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
        $this->common_wait_seconds = $this->getSystemInfo('common_wait_seconds',60);
        $this->add_cancelled_bets_in_original_game_logs = $this->getSystemInfo('add_cancelled_bets_in_original_game_logs');
        $this->use_new_sync_version = $this->getSystemInfo('use_new_sync_version', true);
        $this->new_api_url = $this->getSystemInfo('new_api_url', 'https://v4webapi.hointeractive.com');
        $this->use_new_api_url = false;

        $this->game_type_live_dealer_code = $this->getSystemInfo('game_type_live_dealer_code', '0000000000000004');

    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    protected function customHttpCall($ch, $params) {
        if($this->use_new_api_url) {
            if(isset($params['is_new_sync'])){
                unset($params['is_new_sync']);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            $xmlParams = $this->generateXMLParams($params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlParams);
        }
    }

    protected function getHttpHeaders($params) {
        if(isset($params['is_new_sync'])){
            $bearer_token = $this->getAvailableApiToken();
            $headers = array(
                "Authorization" => "Bearer {$bearer_token}"
            );
            return $headers;
        }
    }

    /**
     * will check timeout, if timeout then call again
     * @return token
     */
    public function getAvailableApiToken(){
        $token = $this->getCommonAvailableApiToken(function(){
           return $this->getOperatorToken();
        });

        $this->utils->debug_log("Hogaming Bearer Token: ".$token);
        return $token;
    }

    public function getOperatorToken(){
        $this->use_new_api_url = true;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetOperatorToken',
        );

        $params = array(
            'username' => $this->web_api_username,
            'password' => $this->web_api_password,
            'casinoId' => $this->casino_id,
        );

        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultForGetOperatorToken($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $result = array(
            "token" => null,
        );
        $success = false;
        if(isset($resultJsonArr['access_token'])){
            $success = true;
            $token_timeout = new DateTime($this->utils->getNowForMysql());
            $minutes = 4;
            $token_timeout->modify("+".$minutes." minutes");
            $result['api_token']=$resultJsonArr['access_token'];
            $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
        }
        return array($success, $result);
    }


    public function generateUrl($apiName, $params) {
        if($this->is_bet_logs) {
            if($apiName == self::API_syncCancelledGames){
                $url = $this->api_bet_logs."/".self::API_syncAllCancelledGames;
            }else{
                $url = $this->api_bet_logs."/".self::API_syncSettledGames;
            }
        } else if($this->use_new_api_url) {
            $apiUri = self::$uri_map[$apiName];
            $url = $this->new_api_url . $apiUri;
        }else {
            $url = $this->api_url.self::FUND_TRANSFER_MODEL_URI;
        }

        return $url;
    }

    public function generateXMLParams($params,$method='request',$response_id=null)
    {
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

    public function processResultBoolean($responseResultId, $resultArray, $playerName) {
        $status = isset($resultArray['element']['properties'][2]) ? $resultArray['element']['properties'][2] : null;
        if($this->action == 'accountbalance'){
            $status = isset($resultArray['element']['properties'][0]) ? $resultArray['element']['properties'][0] : null;
        }
        $success = false;
        if($status === self::SUCCESS && !isset($resultArray['error'])) {
            $success = true;
        } else {
            $success = false;
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log("==========HG API GOT ERROR=============", $resultArray, $playerName);
        }
        return $success;
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
            'mode' => $this->player_mode,
            'firstname' => $gameUsername,
            'lastname' => $gameUsername,
            'currencyid' => $this->currency_type,
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

        if(isset($extra['game_mode'])){
            if($extra['game_mode'] == "demo" || !$extra['game_mode']){
                $this->player_mode = self::DEMO_MODE;
            }
        }
        $params = array(
            'username' => $gameUsername,
            'mode' => $this->player_mode,
            'firstname' => $gameUsername,
            'lastname' => $gameUsername,
            'currencyid' => $this->currency_type,
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
            'mode' => self::REAL_MODE,
            'firstname' => $gameUsername,
            'lastname' => $gameUsername,
            'currencyid' => $this->currency_type,
        );

        $this->action = 'registration';

        return $this->callApi(self::API_isPlayerExist, $params, $context);
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
            'mode' => self::REAL_MODE,
        );

        $this->action = 'accountbalance';

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

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
        $secure_id = $transfer_secure_id; //$secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T'); 
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $secure_id,
            'amount' => $amount,
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => $this->player_mode,
            'amount' => $this->dBtoGameAmount($amount),
            'refno' => $secure_id,
            'currencyid' => $this->currency_type,
        );

        $this->action = 'deposit';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $status =  isset($resultArr['element']['properties'][0]) ? $resultArr['element']['properties'][0] : null;

        $this->utils->debug_log('processResultForDepositToGame resultArr', $resultArr);
        $success = false;
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($status === self::SUCCESS && !isset($resultArr['error'])) {
            $paymentId =  isset($resultArr['element']['properties'][2]) ? $resultArr['element']['properties'][2] : null;;
            $result = $this->confirmDeposit(self::SUCCESS, $paymentId, self::NO_ERROR);
            $success = false;
            if($result['success']) {// confirm deposit
                $success = true;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs']=true;
            }else{
                $error_code = isset($result['element']['properties'][0]) ? $result['element']['properties'][0] : null;
                $result['reason_id']=$this->getReasons($error_code);
                if($result['reason_id'] != self::REASON_UNKNOWN){
                    $result['tranfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                }
            }
        }else{
            $error_code = isset($resultArr['element']['properties'][0]) ? $resultArr['element']['properties'][0] : null;

            if(isset($resultArr['error']) && (strpos($resultArr['error'],self::UNAUTHORIZE_ACCESS)!==false)){
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id']=$this->getReasons($error_code);
            }else{
                $result['reason_id']=$this->getReasons($error_code);
                if($result['reason_id'] != self::REASON_UNKNOWN){
                    $result['tranfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                }
            }
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
        $status=isset($resultArr['element']['properties'][0]) ? $resultArr['element']['properties'][0] : null;
        $success = $status === self::SUCCESS ? true : false;

        return array($success, $resultArr);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $secure_id = $transfer_secure_id; //$secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $secure_id,
            'amount' => $amount,
        );

        $params = array(
            'username' => $gameUsername,
            'mode' => $this->player_mode,
            'amount' => $this->dBtoGameAmount($amount),
            'refno' => $secure_id,
            'currencyid' => $this->currency_type,
        );

        $this->action = 'withdrawal';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultXml = $this->getResultXmlFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $status =  isset($resultArr['element']['properties'][0]) ? $resultArr['element']['properties'][0] : null;
        $this->utils->debug_log('processResultForWithdrawFromGame resultArr', $resultArr);
        $success = false;
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($status === self::SUCCESS && !isset($resultArr['error'])) {
            $paymentId =  isset($resultArr['element']['properties'][2]) ? $resultArr['element']['properties'][2] : null;
            $result = $this->confirmWithdrawal(self::SUCCESS, $paymentId, self::NO_ERROR);
            $success = false;
            if($result['success']) {      // confirm withdrawal
                $success = true;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs']=true;
            }else{
                $error_code = isset($result['element']['properties'][0]) ? $result['element']['properties'][0] : null;
                $result['reason_id']=$this->getReasons($error_code);
                if($result['reason_id'] != self::REASON_UNKNOWN){
                    $result['tranfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                }
            }
        }else{
            $error_code = isset($resultArr['element']['properties'][0]) ? $resultArr['element']['properties'][0] : null;

            if(isset($resultArr['error']) && (strpos($resultArr['error'],self::UNAUTHORIZE_ACCESS)!==false)){
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id']=$this->getReasons($error_code);
            }else{
                $result['reason_id']=$this->getReasons($error_code);
                if($result['reason_id'] != self::REASON_UNKNOWN){
                    $result['tranfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                }
            }
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
        // $confirmResponse = @$resultArr['element']['properties'][0];
        $status=isset($resultArr['element']['properties'][0]) ? $resultArr['element']['properties'][0] : null;
        $success = false;
        if($status === self::SUCCESS || $status === self::SUCCESS_WITHDRAW){
            $success = true;
        }

        return array($success, $resultArr);
    }

    /*
     * Game Link
     *
     *  #FOR RNG GAMES W/ BET LIMIT
     *  NOTES: version is default to V3, will not work if use V4, see docs for bet limit for each game
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>
     *  /goto_hggame/2078/0000000000000019/en/m777FH/real/1
     *
     *  #To load specific game type while loading the lobby for V3
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>/<skin_id>
     *  /goto_hggame/2078/0000000000000001/en/null/real/NULL/null/V3
     *
     *  #To load specific game type while loading the lobby for V4
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>/<skin_id>
     *  /goto_hggame/2078/0000000000000001/en/null/real/null/null/V4
     *
     *  #To direct load Live / Slot / Poker / Table / Video / EVO Game for V3
     *  NOTE: Some tableId does not accept more than bet_limit
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>/<skin_id>
     *  /goto_hggame/2078/0000000000000004/en/l8i2hq4jo2hjj9ca/real/1/null/V4
     */
    public function queryForwardGame($playerName, $extra=null) {
        $result = $this->login($playerName, $extra);
        if ($result['success']) {
            #GET LANG FROM PLAYER DETAILS
            $playerId = $this->getPlayerIdFromUsername($playerName);
            $player_lang = $this->processPlayerLanguageForParams($this->getPlayerDetails($playerId)->language);

            $params = array(
                'ticketId' => $result['ticketId'],
                'lang' => isset($extra['language']) ? $this->processPlayerLanguageForParams($extra['language']) : $player_lang
            );
            
            #IDENTIFY IF LAUNCH WITH GAME TYPE (game_type as gameType)
            if(!empty($extra['game_type']) && $extra['game_type']) {
                $params['gameType'] = $this->getGameTypeId($extra['game_type']);
            }

            #IDENTIFY MOBILE GAME
            if(isset($extra['is_mobile']) && $extra['is_mobile']){
                $params['mobile'] = "true";
            }

            #IDENTIFY IF LAUNCH WITH TABLE ID (game_code as tableId)
            if(isset($extra['game_code']) && $extra['game_code'] && $extra['game_code'] != 'null'){
                $game_code = $extra['game_code'];
                $params['tableId'] = $game_code;
                if(!isset($params['gameType'])){
                    foreach (self::TABLEGAMES_GAMETYPE_IDS as $key => $val) {
                        if($val == $game_code){
                            $params['gameType'] = $game_code;
                            unset($params['tableId']);
                        }
                    }
                }
            }

            #IDENTIFY IF LAUNCH WITH BET TYPE (bet_limit as betType)
            if(isset($extra['extra']['bet_limit']) && $extra['extra']['bet_limit'] && $extra['extra']['bet_limit'] != 'null'){
                $params['betType'] = $this->getBetLimitTypeId($extra['extra']['bet_limit']);
            }

            #IDENTIFY IF LAUNCH WITH VERSION
            if(isset($extra['extra']['version']) && $extra['extra']['version'] && $extra['extra']['version'] != 'null'){
                $params['version'] = $extra['extra']['version'];
            }

            #IDENTIFY IF LAUNCH WITH SKIN ID
            if(isset($extra['extra']['skin_id']) && $extra['extra']['skin_id']){
                $params['skinId'] = $extra['extra']['skin_id'] ?: $this->getSystemInfo('skin_id','SKIN001');
            }

            #IDENTIFY IF LAUNCH WITH REFERRER
            if(isset($extra['extra']['referrer']) && $extra['extra']['referrer']){
                $params['ref'] = $extra['extra']['referrer'];
            }

            #IDENTIFY IF LAUNCH WITH EXIT URL
            if(isset($extra['extra']['exit_url']) && $extra['extra']['exit_url']){
                $params['exitUrl'] = $extra['extra']['exit_url'];
            }

            if(isset($extra['home_link']) && $extra['home_link']){
                $params['exiturl'] = $extra['home_link'];
            }

            if(isset($extra['cashier_link']) && $extra['cashier_link']){
                $params['cashierurl'] = $extra['cashier_link'];
            }

            $url = $this->api_url.self::FORWARD_GAME_URI. '?' .http_build_query($params) ;
            return array('success'=>true,'url' => $url);
        }
        return array('success'=>false,'url' => '');
    }

    private function getGameTypeId($gameType){
        if(array_key_exists($gameType,self::TABLEGAMES_GAMETYPE_IDS)){
            return self::TABLEGAMES_GAMETYPE_IDS[$gameType];
        }

        switch ($gameType) {
            case self::GAME_TYPE_LIVE_DEALER:            
                return $this->game_type_live_dealer_code; 
                break;            
            default:
                return $gameType;
                break;
        }        
    }

    private function getBetLimitTypeId($betLimitType){
        if(array_key_exists($betLimitType,self::BETTYPE_IDS)){
            return self::BETTYPE_IDS[$betLimitType];
        }
        return $betLimitType;
    }

    private function processPlayerLanguageForParams($lang){
        switch ($lang) {
            case "Chinese":
            case "zh-cn":
            case "zh-CN":
                return "ch"; break;
            case "English":
            case "en-us":
            case "en-US":
                return "en"; break;
            case "Japanese":
            case "jp-jp":
            case "jp-JP":
                return "ja"; break;
            case "Korean":
            case "ko-kr":
            case "ko-KR":
                return "ko"; break;
            case "Thai":
            case "th-th":
            case "th-TH":
                return "th"; break;
            case "Vietnamese":
            case "vi-vn":
            case "vi-VN":
                return "vi"; break;
            case "Indonesian":
            case "id-id":
            case "id-ID":
             return "id"; break;

            default:
                return "en";
                break;
        }
    }

    /**
     * If the Data requested is very large to retrieve,
     * the following error will be displayed by the system. Application
     * throws the following error based on the date/time range selected by the player.
     *
     * Status – 0011: The data you have requested is too huge to be retrieved. Reduce the Date/Time Range and try again.
     */
    public function syncOriginalGameLogs($token = false) {

        if($this->use_new_sync_version){
            return $this->syncOriginalGameLogsV2($token);
        }

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $startDate->modify($this->getDatetimeAdjust());

        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $startDate,
            'endDate' => $endDate,
        );

        $this->is_bet_logs = true;
        $cancelledGameLogsRes = $this->callCancelledBets($context);
        $settledGameLogsRes = $this->callSettledBets($context);
        return [
                "success" => true,
                "result" => ["cancelled"=>$cancelledGameLogsRes,"settled"=>$settledGameLogsRes],
                "response_result_id" =>$settledGameLogsRes['response_result_id']
               ];
    }

    private function callSettledBets($context)
    {
        $hogamingTimeout = $this->utils->getJsonFromCache("settled-HOGAMING-timeout");
        if($hogamingTimeout >= time()){
            sleep($this->common_wait_seconds);
            $this->CI->utils->debug_log('settled-HOGAMING-timeout API ==============> skip Syncing due to 1 call per minute restriction.');
            return array("success"=>true,"details"=>"[Settled Game Logs] skip Syncing due to 1 call per minute restriction. ");
        }

        $context['isCancelledFlag'] = false;
        $params = array(
            'Username' => $this->web_api_username,
            'Password' => $this->web_api_password,
            'CasinoId' => $this->casino_id,
            'startTime' => $context['startDate']->format("Y-m-d H:i:s"),
            'EndTime' => $context['endDate']->format("Y-m-d H:i:s"),
            'Usertype' => 'Play',
        );

        $this->action = self::API_syncSettledGames;
        return $this->callApi($this->action, $params, $context);
    }

    private function callCancelledBets($context)
    {
        $hogamingTimeout = $this->utils->getJsonFromCache("cancelled-HOGAMING-timeout");
        if($hogamingTimeout >= time()){
            sleep($this->common_wait_seconds);
            $this->CI->utils->debug_log('cancelled-HOGAMING-timeout API ==============> skip Syncing due to 1 call per minute restriction.');
            return array("success"=>true,"details"=>"[Cancelled Game Logs] skip Syncing due to 1 call per minute restriction. ");
        }

        $context['isCancelledFlag'] = true;
        $params = array(
            'Username' => $this->web_api_username,
            'Password' => $this->web_api_password,
            'CasinoId' => $this->casino_id,
            'Dateval' => $context['startDate']->format("Y/m/d"),
            'Usertype' => 'Play',
            'Status' => 'cancel'
        );

        $this->action = self::API_syncCancelledGames;
        return $this->callApi($this->action, $params, $context);
    }

    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model('original_game_logs_model');
        $isCancelledFlag = $this->getVariableFromContext($params, 'isCancelledFlag');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $logsResult = $this->generateLogsResult($resultArr[0]);
        $success = true;
        $data_count = 0;

        if (!empty($logsResult['STATUS_CODE'])) {
            $timeOutCachedId = $isCancelledFlag ? "cancelled" : "settled";
            $this->utils->saveJsonToCache($timeOutCachedId."-HOGAMING-timeout",strtotime("+ 91 seconds"));
            $this->CI->utils->debug_log('Ho GAMING API ADD timeout ===> + 1 minutes');
            return array(true,['error'=>"add sleep time"]);
        } else {

            $gameRecords = $logsResult['Betinfo'] === array_values($logsResult['Betinfo']) ? $logsResult['Betinfo'] : array($logsResult['Betinfo']);
            $this->CI->utils->debug_log("HOGAMING GAME LOGS DATA ===>", $gameRecords);

            $result = ['data_count' => 0];

            if($success&&!empty($gameRecords))
            {
                $extra = [
                            'response_result_id' => $responseResultId,
                            'is_cancelled' => $isCancelledFlag,
                         ];
                $this->rebuildGameRecords($gameRecords,$extra);

                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    $this->getMd5FieldsForOriginal(),
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

                unset($gameRecords);
                if (!empty($insertRows))
                {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($insertRows);

                if (!empty($updateRows))
                {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($updateRows);
            }
        }

        return array($success, $result);
    }

    public function getMd5FieldsForOriginal()
    {

        if($this->use_simplified_md5){
            return self::SIMPLE_MD5_FIELDS_FOR_ORIGINAL;
        }

        return self::MD5_FIELDS_FOR_ORIGINAL;
    }

    public function getMD5Fields(){
        return [
            'md5_fields_for_original'=>$this->getMd5FieldsForOriginal(),
            'md5_float_fields_for_original'=>self::MD5_FLOAT_AMOUNT_FIELDS,
            'md5_fields_for_merge'=>self::MD5_FIELDS_FOR_MERGE,
            'md5_float_fields_for_merge'=>self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE,
        ];
    }

    public function syncOriginalGameLogsV2($token){

        $this->use_new_api_url = true;
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');
        $getTimeNow = $this->serverTimeToGameTime($this->CI->utils->getNowForMysql());
        if($endDate > $getTimeNow) {
            $endDate = $getTimeNow;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecordsV2',
        );

        $params = array(
            'starttime' => $startDate,
            'endtime' => $endDate,
            'is_new_sync' => true
        );

        $result = $this->callApi(self::API_syncGameRecords, $params, $context);
        return array('success' => true, 'result' => $result);
    }

    public function processResultForSyncGameRecordsV2($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );
        $success = true;
        if( isset($resultJsonArr['data']) && !empty($resultJsonArr['data'])){
            $gameRecords = $resultJsonArr['data'];
            $this->processGameRecords($gameRecords, $responseResultId);
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL_V2,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));
            $dataResult['data_count'] = count($gameRecords);
            unset($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs(
                    $insertRows,
                    'insert',
                    $this->original_gamelogs_table,
                    ['responseResultId'=>$responseResultId]
                );
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs(
                    $updateRows,
                    'update',
                    $this->original_gamelogs_table,
                    ['responseResultId'=>$responseResultId]
                );
            }
            unset($updateRows);
        }
        return array($success, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['bet_start_date'] = isset($record['betStartDate']) ? $this->gameTimeToServerTime($record['betStartDate']) : null;
                $data['bet_end_date'] = isset($record['betEndDate']) ? $this->gameTimeToServerTime($record['betEndDate']) : null;
                $data['account_id'] = isset($record['accountId']) ? $record['accountId'] : null;
                $data['table_id'] = isset($record['tableId']) ? $record['tableId'] : null;
                $data['table_name'] = isset($record['tableName']) ? $record['tableName'] : null;
                $data['game_id'] = isset($record['gameId']) ? $record['gameId'] : null;
                $data['bet_id'] = isset($record['betId']) ? $record['betId'] : null;
                $data['bet_amount'] = isset($record['betAmount']) ? $this->gameAmountToDB($record['betAmount']) : null;
                $data['valid_bet'] = isset($record['betAmount']) ? $this->gameAmountToDB($record['betAmount']) : null;
                $data['payout'] = isset($record['payout']) ? $this->gameAmountToDB($record['payout']) : 0;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['game_type'] = isset($record['gameType']) ? $record['gameType'] : null;
                $data['bet_spot'] = isset($record['betSpot']) ? $record['betSpot'] : null;
                $data['bet_no'] = isset($record['betId']) ? $record['betId'] : null;
                $data['bet_mode'] = isset($record['version']) ? $record['version'] : null;
                #default
                $data['external_uniqueid'] = $data['bet_id'];
                $data['response_result_id'] = $responseResultId;
                $gameRecords[$index] = $data;
                unset($data);
            }
        }    
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[])
    {
        $dataCount = 0;
        if(!empty($rows))
        {
            foreach ($rows as $key => $record)
            {
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

    private function rebuildGameRecords(&$gameRecords,$extra)
    {
        $newGR =[];
        foreach($gameRecords as $i => $gr)
        {
            $this->useServerTimeToGameTimeInOrigSync = $this->getSystemInfo('useServerTimeToGameTimeInOrigSync',false);
            if($this->useServerTimeToGameTimeInOrigSync){
                $betStartDate = isset($gr['BetStartDate'])?$this->utils->modifyDateTime($gr['BetStartDate'], $this->gameTimeToServerTime):null;
                $betEndDate = isset($gr['BetEndDate'])?$this->utils->modifyDateTime($gr['BetEndDate'], $this->gameTimeToServerTime):null;
            }else{
                $betStartDate = isset($gr['BetStartDate'])?$this->utils->modifyDateTime($gr['BetStartDate'],0):null;
                $betEndDate = isset($gr['BetEndDate'])?$this->utils->modifyDateTime($gr['BetEndDate'],0):null;
            }
            $newGR[$i]['bet_start_date'] = $betStartDate;
            $newGR[$i]['bet_end_date'] = $betEndDate;
            $newGR[$i]['account_id'] = isset($gr['AccountId'])?$gr['AccountId']:null;
            $newGR[$i]['table_id'] = isset($gr['TableId'])?$gr['TableId']:null;
            $newGR[$i]['table_name'] = isset($gr['TableName'])?$gr['TableName']:null;
            $newGR[$i]['game_id'] = isset($gr['GameId'])?$gr['GameId']:null;
            $newGR[$i]['bet_id'] = isset($gr['BetId'])?$gr['BetId']:null;
            $newGR[$i]['bet_amount'] = isset($gr['BetAmount'])?$gr['BetAmount']:null;
            $newGR[$i]['payout'] = isset($gr['Payout'])?$gr['Payout']:null;
            $newGR[$i]['currency'] = isset($gr['Currency'])?$gr['Currency']:null;
            $newGR[$i]['game_type'] = isset($gr['GameType'])?$gr['GameType']:null;
            $newGR[$i]['bet_spot'] = isset($gr['BetSpot'])?$gr['BetSpot']:null;
            $newGR[$i]['bet_no'] = isset($gr['BetNo'])?$gr['BetNo']:null;

            if($this->add_cancelled_bets_in_original_game_logs && $extra['is_cancelled']){
                $newGR[$i]['bet_mode'] = 'cancelled';
            }else{
                $newGR[$i]['bet_mode'] = isset($gr['BetMode'])?$gr['BetMode']:null;
            }

            $newGR[$i]['brand_name'] = $this->brand_name;
            $newGR[$i]['external_uniqueid'] = isset($gr['BetId'])?$gr['BetId']:null;
            $newGR[$i]['response_result_id'] = $extra['response_result_id'];
        }
        $gameRecords = $newGR;
    }

    public function generateLogsResult($result) {
        $logs =  strtr($result, array("\n" => ''));

        // strip Total Records tag. convert to xml will error if not remove
        $logs = preg_replace('/<TotalRecords[^>]*>.*?<\/TotalRecords>/i', '', $logs);

        $logsXml = new SimpleXMLElement($logs);
        $logsResult = json_decode(json_encode($logsXml), true);

        return $logsResult;
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`hog`.`bet_end_date` >= ?
          AND `hog`.`bet_end_date` <= ?';
        if($use_bet_time){
            $sqlTime='`hog`.`bet_start_date` >= ?
          AND `hog`.`bet_start_date` <= ?';
        }

        $sql = <<<EOD
            SELECT
                hog.id as sync_index,
                hog.response_result_id,
                hog.game_id as round,
                hog.table_name as table_identifier,
                hog.account_id as username,
                hog.bet_amount as bet_amount,
                hog.valid_bet as valid_bet,
                hog.payout as result_amount,
                hog.bet_start_date as start_at,
                hog.bet_end_date as end_at,
                hog.bet_start_date as bet_at,
                hog.table_id as game_code,
                hog.game_type as game_name,
                hog.external_uniqueid,
                hog.md5_sum,
                game_provider_auth.player_id,
                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id
            FROM $this->original_gamelogs_table as hog
            LEFT JOIN game_description as gd ON hog.table_id = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON hog.account_id = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id=?
            AND hog.bet_mode != 'cancelled'
            WHERE
            {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $extra = [
            'table' =>  $row['round'],
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $row['result_amount'] = $row['result_amount'] - $row['bet_amount']; // OGP-23976

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type_id'],
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['valid_bet'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],#OGP-14059 as per data team, end_at should be based on bet_start_time, but as per james we should retain it as end_at
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
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
        $bet_details = [
            'roundId' => $row['round'],
            'table_identifier' => $row['table_identifier']
        ];
        $row['bet_details'] = $bet_details;
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

    public function blockPlayer($playerName)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $success=true;
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array('success' => $success);
    }

    public function queryPlayerInfo($playerName) {
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

    protected function convertResultXmlFromParams($params) {
        $resultText = trim(@$params['resultText']);
        $resultXml = null;
        if (!empty($resultText)) {
            try{
                $resultXml = new SimpleXMLElement($resultText);
            }catch(Exception $e){
                $resultXml=null;
                $this->CI->utils->error_log('convert xml failed', $e->getMessage());
            }
        }
        return $resultXml;
    }

    // protected function convertResultXmlFromParams($params) {
    //     $resultText = trim(@$params['resultText']);
    //     $resultXml = null;
    //     if (!empty($resultText)) {
    //         $resultXml = new SimpleXMLElement($resultText);
    //     }
    //     return $resultXml;
    // }


}