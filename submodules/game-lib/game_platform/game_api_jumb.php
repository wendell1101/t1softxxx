<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_jumb extends Abstract_game_api {

    protected $api_url      = null;
    protected $web_url      = null;
    protected $dc           = null;
    protected $site         = null;
    protected $encrypt_iv   = null;
    protected $encrypt_key  = null;
    protected $access_token = null;
    protected $agent_code   = null;
    public $original_table;
    public $sync_time_interval_action_29;
    public $sync_time_interval_action_64;
    public $game_types;

    const API_queryGameLogs_slots       = 29;
    const API_queryGameLogs_fishinggame = 46;
    const API_trialGame                 = 47;
    const GAMBLE_TYPE = 9;

    const URI_MAP = array(
        self::API_isPlayerExist             => 15,
        self::API_createPlayer              => 12,
        self::API_checkLoginToken           => 11,
        self::API_updatePlayerInfo          => 13,
        self::API_queryPlayerBalance        => 15,
        self::API_checkLoginStatus          => 16,
        self::API_changePassword            => 36,
        self::API_depositToGame             => 19,
        self::API_withdrawFromGame          => 19,
        self::API_queryPlayerDailyBalance   => 42,
        self::API_queryTransaction          => 28,
        self::API_queryBetDetailLink        => 54,
        self::API_syncGameRecords           => 29,
        self::API_syncLostAndFound          => 64,
    );

    const MD5_FIELDS_FOR_ORIGINAL = [
        'username',
        'seqNo',
        'historyId',
        'gType',
        'mtype',
        'gameDate',
        'lastModifyTime',
        'bet',
        'win',
        'total',
        'gambleBet',
        'jackpot',
        'jackpotContribute',
        'denom',
        'beforeBalance',
        'afterBalance',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet',
        'win',
        'total',
        'gambleBet',
        'jackpot',
        'jackpotContribute',
        'denom',
        'beforeBalance',
        'afterBalance',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'round_number',
        'game_type',
        'game_code',
        'bet_at',
        'start_at',
        'end_at',
        'bet_amount',
        'win_amount',
        'result_amount',
        'gambleBet',
        'jackpot',
        'jackpotContribute',
        'denom',
        'before_balance',
        'after_balance',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',
        'result_amount',
        'gambleBet',
        'jackpot',
        'jackpotContribute',
        'denom',
        'before_balance',
        'after_balance',
    ];

    public function __construct() {
        parent::__construct();
        $this->original_table = 'jumb_game_logs';
        $this->api_url      = $this->getSystemInfo('api_url');
        $this->dc           = $this->getSystemInfo('dc');
        $this->site         = $this->getSystemInfo('site');
        $this->encrypt_iv   = $this->getSystemInfo('encrypt_iv');
        $this->encrypt_key  = $this->getSystemInfo('encrypt_key');
        $this->web_url      = $this->getSystemInfo('web_url');
        $this->agent_code   = $this->getSystemInfo('agent_code');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+15 minutes');
        $this->password_suffix = $this->getSystemInfo('password_suffix','a1');
        $this->adjust_end_date = $this->getSystemInfo('adjust_end_date','+1 minute');
        $this->show_more_games = $this->getSystemInfo('show_more_games', true);
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', 5);
        $this->sync_time_interval_action_29 = $this->getSystemInfo('sync_time_interval_action_29', '+14 minutes');
        $this->sync_time_interval_action_64 = $this->getSystemInfo('sync_time_interval_action_64', '+4 minutes');
        $this->game_types = $this->getSystemInfo('game_types', []);

        $this->CI->load->model(['game_provider_auth', 'original_game_logs_model']);

        $this->use_game_gtype = $this->getSystemInfo('use_game_gtype', false);
        
    }

    public function getPlatformCode() {
        return JUMB_GAMING_API;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    private function jumb_encrypt($str){
        return base64_encode(openssl_encrypt($this->jumb_padString($str), 'AES-128-CBC', $this->encrypt_key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $this->encrypt_iv));
    }


    public function jumb_decrypt($code) {
        return utf8_encode(trim(openssl_decrypt(base64_decode($code), 'AES-128-CBC', $this->encrypt_key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $this->encrypt_iv)));
    }

    protected function jumb_padString($source) {
        $paddingChar = ' ';
        $size = 16;
        $x = strlen($source) % $size;
        $padLength = $size - $x;

        for ($i = 0; $i< $padLength; $i++) {
            $source .= $paddingChar;
        }

        return $source;
    }

    protected function jumb_now() {
        return round(microtime(true)*1000);
    }

    protected function jumb_argEncrypt($ar) {
        return $this->jumb_encrypt(json_encode($ar));
    }

    protected function transaction_sn() {
        $now = $this->jumb_now();
        $rnd = sprintf('%06x', mt_rand(0x0, 0xffffff));
        return "{$now}s{$rnd}";
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url.'dc='.$params['dc']. '&x=' . urlencode($params['x']);
        return $url;
    }

    protected function processResultBoolean($responseResultId, $resultArr, $player_name = null, $apiName = null) {
        $this->CI->utils->debug_log("apiName ================", $apiName);
        $success = false;

        # status 0000 for success and status 7602 for account already exists
        if(isset($resultArr['status']) && ($resultArr['status'] == '0000' || $resultArr['status'] == '7602')) {
            $success = true;
        }

        # status 0000 for success, status 7501 for player not exist (will auto create player)
        if($apiName == "IsPlayerExist" && $resultArr['status'] == '7501'){
            $this->CI->utils->debug_log('if Player is not exist it this will auto create player');
            $success = true;
        }

        if($resultArr['status'] == '8006'){
            $this->CI->utils->debug_log('Jumb Sync Original Game Logs: No available game history at this date range');
            $success = true;
        }

        if(!$success){
           $this->setResponseResultToError($responseResultId);
           $this->CI->utils->debug_log('JUMB got error ======================================>', $responseResultId, 'playerName', $player_name, 'result', $resultArr);
        }

        return $success;
    }

    public function isPlayerExist($playerName){
        $userName = $this->getGameUsernameByPlayerUsername($playerName);
        if(!empty($userName)){
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForIsPlayerExist',
                'SbeplayerName' => $playerName
            );

            $jumb_params = array(
                'action'            => self::URI_MAP[self::API_isPlayerExist],
                'ts'                => $this->jumb_now(),
                'parent'            => $this->agent_code,
                'uid'               => !empty($userName)?$userName:$playerName
            );
            $this->CI->utils->debug_log('isPlayerExist params ======================================>', $jumb_params);
            $params = array(
                'dc'    => $this->dc,
                'x'     => $this->jumb_argEncrypt($jumb_params)
            );

            return $this->callApi(self::API_isPlayerExist, $params, $context);
        }else{
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $this->CI->utils->error_log('callback_error',"JUMB API isplayerExist empty username. trace: ",$trace);
            $result = array('success'=>false,'exists' => null); # Player not found
        }
    }

    public function processResultForIsPlayerExist($params){
        $SbeplayerName = $this->getVariableFromContext($params, 'SbeplayerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $SbeplayerName,'IsPlayerExist');

        if ($resultArr['status']!="7501") {
            $result = array('exists' => true);
        }else{
            $result = array('exists' => false); # Player not found
        }
        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $userName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'      => $this,
            'callback_method'   => 'processResultForCreatePlayer',
            'playerName'        => $playerName,
            'password'          => $password,
            'playerId'          => $playerId
        );

        $jumb_params = array(
            'action'            => self::URI_MAP[self::API_createPlayer] ,
            'ts'                => $this->jumb_now() ,
            'parent'            => $this->agent_code ,
            'uid'               => $userName,
            'name'              => $userName,
            'credit_allocated'  => 0,
        );

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $password = $this->getVariableFromContext($params, 'password');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if ($success || $resultArr['status'] == '7602') { #7602 means player already exist
            $this->changePassword($playerName,null,$password);

            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $resultArr);
    }

    public function getLauncherGameType($game_type){
        $new_gameType='';
        switch ($game_type) {
            case 'slots':
                $new_gameType = '0';
                break;
            case 'fishing':
            case 'fishing_game':
                $new_gameType = '7';
                break;
            case 'arcade':
                $new_gameType = '9';
                break;
            case 'table_and_cards':
            case 'card_games':
                $new_gameType = '18';
                break;
            case 'lottery':
                $new_gameType = '12';
                break;
            default:
                $new_gameType = '0'; // by default slots
                break;
        }
        return $new_gameType;
    }

    public function getAccessToken($playerName = null, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAccessToken',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        if( $extra['game_mode'] == 'real'){
            $api_action = self::URI_MAP[self::API_checkLoginToken];
        } else {
            $api_action = self::API_trialGame;
        }

        // check if game type is empty or isset

        if(!isset($extra['game_type']) || empty($extra['game_type'])) {
            $this->CI->load->model('game_description_model');

            $gameDesc = $this->CI->game_description_model->getGameDescByGameCode($extra['game_code'], $this->getPlatformCode());
            $game_type_code = isset($gameDesc["game_type_code"]) ? $gameDesc["game_type_code"] : "";

            $gType = $this->getLauncherGameType($game_type_code);
        } else {
            $gType = $this->getLauncherGameType($extra['game_type']);
        }

        $jumb_params = array(
            'action'    => $api_action,
            'ts'        => $this->jumb_now() ,
            'uid'       => $gameUsername ,
            'lang'      => $this->getLauncherLanguage($extra['language']) ,
            'gType'     => $gType, #'0' , # 0 slot , 7 Fishing machine
            'mType'    => $extra['game_code'],
            'windowMode'=> 2 # 1 - Include game hall, 2 - does not contain the game hall, hide the close button in the game gType and mType are required fields.
        );

        # 0: Do not show more games
        # 1: Show more games (default)
        if(!$this->show_more_games) {
            $jumb_params['moreGame'] = 0;
        }

        if($this->use_game_gtype){
            // get gtype from game
            $this->CI->load->model('game_description_model');
            $external_game_id = $extra['game_code'];
            $gameDetails = $this->CI->game_description_model->getGameDetailsByExternalGameIdAndGamePlatform($this->getPlatformCode(),$external_game_id, true);
            
            $json=$this->utils->decodeJson($gameDetails['attributes']);
            if(!empty($json) && isset($json['gType']) && !empty($json['gType'])){
                $jumb_params['gType']=$json['gType'];
            }
            $this->utils->debug_log("JUMBO SEAMLESS jumb_params ============================>", $jumb_params, 'json', $json, 'gameDetails', $gameDetails, 'plain', $gameDetails['attributes']);
        }

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        $this->utils->debug_log("JUMB jumb_params ============================>", $jumb_params);
        $this->utils->debug_log("JUMB ecrypted params ============================>", $params);

        return $this->callApi(self::API_checkLoginToken, $params, $context);
    }


    public function processResultForGetAccessToken($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,null);
        $this->utils->debug_log("JUMB getAccessToken ============================>", $resultArr);

        $result=['response_result_id'=>$responseResultId];

        if ($success) {
            if(isset($resultArr['path'])){
                $result['launcher_url']=$resultArr['path'];
            }else if(isset($resultArr['x'])){
                $result['access_token']=$resultArr['x'];
            }
            // $this->access_path = $resultArr['path'];
            // $this->access_token = $resultArr['x'];

        }

        return [$success,$result];
    }

    public function depositToGame($userName, $amount, $transfer_secure_id=null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);

        $trans_id = $transfer_secure_id;
        if(empty($transfer_secure_id)){
            $trans_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T'); //string; max:50 chars
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $userName,
            'amount' => $amount,
            'external_transaction_id' => $trans_id,
        );

        $jumb_params = array(
            'action'            => self::URI_MAP[self::API_depositToGame] ,
            'ts'                => $this->jumb_now() ,
            'parent'            => $this->agent_code ,
            'uid'               => $gameUsername ,
            'amount'            => $this->dBtoGameAmount($amount),
            'credit_allocated'  => 0 ,
            'serialNo'          => $trans_id ,
            'remark'            => '' ,
            'allCashOutFlag'    => ''
        );

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        return $this->callApi( self::API_depositToGame, $params, $context);

    }

    function processResultForDepositToGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);

        $result = array(
            'response_result_id' => $responseResultId,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id
        );

        $success = false;
        $this->utils->debug_log("Deposit ResultArr ============================>", $resultArr);
        $this->utils->debug_log("Deposit result from response result id ============================>", $resultArr);

        if ($this->processResultBoolean($responseResultId, $resultArr,$gameUsername)) {
            //get current sub wallet balance
            // $playerBalance = $this->queryPlayerBalance($playerName);

            //for sub wallet
            // $afterBalance = $playerBalance['balance'];
            //$result["external_transaction_id"] = $resultArr['pid'];
            // $result["currentplayerbalance"] = $afterBalance;
            // $result["userNotFound"] = false;
            //update
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
            //     //deposit
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
            $success = true;
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultArr['status'];
            if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $success=true;
            }else{
                $result['reason_id'] = $this->getReason($error_code);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }   
        }
        return array($success, $result);

    }

    function getReason($error_code) {
        switch($error_code) {
            case '7501' :
                $reason_id = self::REASON_NOT_FOUND_PLAYER;
                break;
            case '999' :
                $reason_id = self::REASON_FAILED_FROM_API;
                break;
            case '9001' :
                $reason_id = self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                break;
            case '9005' :
                $reason_id = self::REASON_INVALID_KEY;
                break;
            case '9009' :
            case '9010' :
                $reason_id = self::REASON_SESSION_TIMEOUT;
                break;
            case '9013' :
                $reason_id = self::REASON_API_MAINTAINING;
                break;
            case '8000' :
            case '8001' :
            case '8002' :
            case '8003' :
                $reason_id = self::REASON_INCOMPLETE_INFORMATION;
                break;
            case '7502' :
            case '7503' :
            case '7504' :
                $reason_id = self::REASON_GAME_ACCOUNT_LOCKED;
                break;
            case '7601' :
                $reason_id = self::REASON_NOT_FOUND_PLAYER;
                break;
            case '6001' :
            case '6002' :
            case '6006' :
                $reason_id = self::REASON_NO_ENOUGH_BALANCE;
                break;
            case '6004' :
            case '9011' :
                $reason_id = self::REASON_DUPLICATE_TRANSFER;
                break;
            default :
                $reason_id = self::REASON_UNKNOWN;
        }

        return $reason_id;
    }

    function queryPlayerBalance($userName) {

        $playerName = $this->getGameUsernameByPlayerUsername($userName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        );

        $jumb_params = array(
            'action'    => self::URI_MAP[self::API_queryPlayerBalance] ,
            'ts'        => $this->jumb_now() ,
            'parent'    => $this->agent_code ,
            'uid'       => $playerName ,
        );

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $success = false;
        $result = array();
        if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {

            $success = true;
            if(isset($resultArr['data'][0]['balance'])) {
                $result['balance'] = $this->gameAmountToDB(floatval($resultArr['data'][0]['balance']));
            }
            else {
                $success = false;
            }

            if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
                $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
            } else {
                $this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }

        } else {
            $success = false;
            if (@$resultArr['error'] == 'PLAYER NOT FOUND') {
                $result['exists'] = false;
            } else {
                $result['exists'] = true;
            }
        }

        return array($success, $result);

    }

    function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $trans_id = $transfer_secure_id;
        if(empty($transfer_secure_id)){
            $trans_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T'); //string; max:50 chars
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $userName,
            'amount' => $amount,
            'external_transaction_id' => $trans_id,
        );

        $jumb_params = array(
            'action'            => self::URI_MAP[self::API_withdrawFromGame] ,
            'ts'                => $this->jumb_now() ,
            'parent'            => $this->agent_code ,
            'uid'               => $gameUsername ,
            'amount'            => abs($this->dBtoGameAmount($amount))*-1,
            'credit_allocated'  => 0 ,
            'serialNo'          => $trans_id ,
            'remark'            => '' ,
            'allCashOutFlag'    => ''
        );

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    function processResultForWithdrawFromGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = false;

        $result = array(
            'response_result_id' => $responseResultId,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id
        );
        if ($this->processResultBoolean($responseResultId, $resultArr,$gameUsername)) {
            //get current sub wallet balance
            // $playerBalance = $this->queryPlayerBalance($playerName);

            //for sub wallet
            // $afterBalance = $playerBalance['balance'];
            //$result["external_transaction_id"] = $resultArr['pid'];
            // $result["currentplayerbalance"] = $afterBalance;
            // $result["userNotFound"] = false;
            //update
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
            //     //withdraw
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());

            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }

            $success = true;
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultArr['status'];
            $result['reason_id'] = $this->getReason($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'ch'; // chinese
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'in'; // indonesia
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vn'; // vietnamese
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function queryForwardGame($playerName = null, $extra=null) {
        $result = $this->getAccessToken($playerName,$extra);
        $data = [
            'url' => '',
            'success' => false
        ];

        if ($result['success']) {
            if(isset($result['launcher_url'])){
                $url = $result['launcher_url']; // $this->web_url . '?x=' . $this->access_token;
                $data = [
                    'url' => $url,
                    'success' => true
                ];
            }else if(isset($result['access_token'])){
                $url = $this->web_url . '?x=' . $result['access_token'];
                $data = [
                    'url' => $url,
                    'success' => true
                ];
            }
        }

        return $data;
    }

    public function get_gtype_by_uniqueid($id) {
        $sql = <<<EOD
            SELECT gType
            FROM jumb_game_logs
            WHERE external_uniqueid = ?
EOD;
        $params = [
            $id
        ];

        $queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
        if (!empty($queryResult) && isset($queryResult[0]['gType'])) {
            return $queryResult[0]['gType'];
        }
        return false;

    }

    /**
    *  The api will return the bet details URL link for viewing the details
    */
    public function queryBetDetailLink($playerUsername, $betid = NULL, $extra = NULL)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryBetDetailLink',
            'gameUsername'    => $gameUsername
        );

        $gType = $this->get_gtype_by_uniqueid($betid);

        $jumb_params = array(
            'action'            => self::URI_MAP[self::API_queryBetDetailLink] ,
            'ts'                => $this->jumb_now(),
            'parent'            => $this->agent_code,
            'uid'               => $gameUsername,
            'gType'             => (int) $gType,
            'seqNo'             => (int) $betid,
        );

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        $this->CI->utils->debug_log('---------- JDB queryBetDetailLink params ----------', $jumb_params);
        return $this->callApi(self::API_queryBetDetailLink, $params, $context);
    }

    /**
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params)
    {
        $statusCode = $this->getStatusCodeFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['url' => ''];

        if ($success && isset($resultArr['data'][0]['path'])) {
            $result['url'] = $resultArr['data'][0]['path'];
        }

        return array($success, $result);
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

    public function syncOriginalGameLogs_oldVersion($token = false) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $endDate->modify($this->adjust_end_date); # we adjust the minute of end date here
        $startDate->modify($this->getDatetimeAdjust());

        //observer the date format
        $queryDateTimeStart = $startDate->format('d-m-Y H:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('d-m-Y H:i:s');
        $queryDateTimeMax = $endDate->format('d-m-Y H:i:00');

        $result = [];
        while ((new Datetime($queryDateTimeMax))  > (new Datetime($queryDateTimeStart))) { // need set as datetime in condition because the format of the datetime is d-m-y

            $startDateParam=new DateTime($queryDateTimeStart);
            if((new Datetime($queryDateTimeEnd))>(new Datetime($queryDateTimeMax))){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }
            $startDateParam = $startDateParam->format('d-m-Y H:i:00');
            $endDateParam = $endDateParam->format('d-m-Y H:i:00');

            $result['slots'.$startDateParam.' to '.$endDateParam] = $this->syncJumbGamelogs($startDateParam,$endDateParam,self::API_queryGameLogs_slots);
            //$result['fishinggame'.$startDateParam.' to '.$endDateParam] = $this->syncJumbGamelogs($startDateParam,$endDateParam,self::API_queryGameLogs_fishinggame); # we dont have action 46 as API DOC version 2.6.0

            sleep($this->sync_sleep_time); // If the frequency of a single IP request too high, it will be blocked.  (once request in 3 seconds) / It is recommended to call once every 5 seconds, thank you.
            $queryDateTimeStart = $endDateParam;
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('d-m-Y H:i:00');
        }

        return array_merge(array("success"=>true),array("details"=>$result));

    }
    public function getGameRecordPath()
    {
        return $this->getSystemInfo('jumb_game_records_path');
    }

    public function syncJumbGamelogsFtp($token = false){
        $gameLogDirectoryJumb = $this->getGameRecordPath();

        if(!is_array($gameLogDirectoryJumb)){
          $gameLogDirectoryJumb = (array)$gameLogDirectoryJumb;
        }

        $playerName     = $this->getValueFromSyncInfo($token, 'playerName');
        $dateTimeFrom   = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo     = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeFrom   = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo     = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        $dateTimeFrom->modify($this->getDatetimeAdjust());

        if(!empty($gameLogDirectoryJumb)){
            foreach ($gameLogDirectoryJumb as $logDirectoryJumb => $logDirectoryJumbValue) {
                $startDate = new DateTime($dateTimeFrom->format('Y-m-d H:i:s'));
                $endDate = new DateTime($dateTimeTo->format('Y-m-d H:i:s'));
                $day_diff = $endDate->diff($startDate)->format("%a");

                if ($day_diff > 0) {
                    for ($i = 0; $i < $day_diff; $i++) {
                        if ($i == 0) {
                            $directory = $logDirectoryJumbValue . $startDate->format('Ymd');
                            $this->retrieveTXTFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName);
                        }
                        $startDate->modify('+1 day');
                        $directory = $logDirectoryJumbValue . $startDate->format('Ymd');
                        $this->retrieveTXTFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName);
                    }
                } else {
                    $directory = $logDirectoryJumbValue . $startDate->format('Ymd');
                    $this->retrieveTXTFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName);

                    $startDate->modify('+1 day');
                    $directory = $logDirectoryJumbValue . $startDate->format('Ymd');
                    $this->retrieveTXTFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName);
                }
            }

            return array('success' => true);
        }

    }

    function getFileExtension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }

    public function retrieveTXTFromLocal($directory, $dateTimeFrom, $dateTimeTo, $playerName)
    {
        $extensions = array("txt");
        $this->CI->load->model(array('jumb_game_logs', 'player_model'));
        $this->CI->utils->debug_log('TXT CURRENT DIRECTORY------',$directory);
        $result = array();
        $result['count'] = $count = 0;
        if (is_dir($directory)) {
            //get file into array
            $jumbGamelogsFile = array_diff(scandir($directory), array('..', '.'));
            $dateTimeFrom = $dateTimeFrom->format('YmdHi');
            $dateTimeTo = $dateTimeTo->format('YmdHi');
            if(!empty($jumbGamelogsFile)){
                foreach ($jumbGamelogsFile as $jumbFile) {
                    $ext = $this->getFileExtension($jumbFile);
                    if (!in_array($ext,$extensions)) {//skip other extension
                        continue;
                    }
                    $fileDate = explode("_",str_replace(".txt"," ",$jumbFile));//get the date from file name
                    $fileDateFrom = $fileDate[0]; //date start of the file
                    $fileDateTo = $fileDate[1]; //date end of the file
                    if ($fileDateFrom >= $dateTimeFrom && $fileDateTo <= $dateTimeTo) {
                        $data = file($directory."/".$jumbFile)[0];//get record by the full directory of the file
                        $gameRecords = json_decode($data,true);
                        $availableRows = $this->CI->jumb_game_logs->getAvailableRows($gameRecords);
                        if (!empty($availableRows)) {
                            foreach ($availableRows as $key => $record) {
                                $insertRow = array();

                                $start_time = new DateTime($this->gameTimeToServerTime($record['gameDate']));
                                $end_time   = new DateTime($this->gameTimeToServerTime($record['lastModifyTime']));
                                $player_username = isset($record['playerId'])?$record['playerId']:null;
                                $insertRow['seqNo'] = isset($record['seqNo'])?$record['seqNo']:null;
                                $insertRow['gType'] = isset($record['gType'])?$record['gType']:null;
                                $insertRow['mtype'] = isset($record['mtype'])?$record['mtype']:null;
                                $insertRow['gameDate'] = $start_time->format('Y-m-d H:i:s');
                                $insertRow['bet'] = isset($record['bet'])?$record['bet']:null;
                                $insertRow['win'] = isset($record['win'])?$record['win']:null;
                                $insertRow['total'] = isset($record['total'])?$record['total']:null;
                                $insertRow['currency'] = isset($record['currency'])?$record['currency']:null;
                                $insertRow['jackpot'] = isset($record['jackpot'])?$record['jackpot']:null;
                                $insertRow['jackpotContribute'] = isset($record['jackpotContribute'])?$record['jackpotContribute']:null;
                                $insertRow['denom'] = isset($record['denom'])?$record['denom']:null;
                                $insertRow['lastModifyTime'] = $end_time->format('Y-m-d H:i:s');
                                $insertRow['gameName'] = isset($record['gameName'])?$record['gameName']:null;
                                $insertRow['playerIp'] = isset($record['playerIp'])?$record['playerIp']:null;
                                $insertRow['clientType'] = isset($record['clientType'])?$record['clientType']:null;
                                $insertRow['hasFreegame'] = isset($record['hasFreegame'])?$record['hasFreegame']:null;
                                $insertRow['hasGamble'] = isset($record['hasGamble'])?$record['hasGamble']:null;
                                $insertRow['gambleBet'] = isset($record['gambleBet'])?$record['gambleBet']:null;
                                $insertRow['systemTakeWin'] = isset($record['systemTakeWin'])?$record['systemTakeWin']:null;
                                # SBE use
                                $insertRow['username'] = $player_username;
                                $insertRow['playerId'] = $this->getPlayerIdInGameProviderAuth($player_username);
                                
                                $uniqueId = $seqNo = isset($record['seqNo'])?$record['seqNo']:null;
                                $historyId = isset($record['historyId'])?$record['historyId']:null;
                                if(!$seqNo){
                                    $uniqueId = $historyId;
                                }

                                $insertRow['external_uniqueid'] = $uniqueId; //add external_uniueid for og purposes
                                $insertRow['response_result_id'] = null;
                                $this->CI->jumb_game_logs->insertJumbGameLogs($insertRow);
                                $count++;
                            }
                            $result['count'] = $count;
                        }
                    }
                }
            }
        }
        return array(true, $result);
    }

    public function syncJumbGamelogs( $startDate,$endDate, $game_type){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords'
        );

        $jumb_params = array(
            'action'    => $game_type ,
            'ts'        => $this->jumb_now() ,
            'parent'    => $this->agent_code ,
            'starttime' => $startDate ,
            'endtime'   => $endDate
        );

        $this->CI->utils->debug_log('JUMB request params ======================>',$jumb_params);

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('jumb_game_logs', 'player_model'));

        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array();
        $result['count'] = $count = 0;
        if ($success) {
            $gameRecords = $resultArr['data'];
            if ($gameRecords) {
                $availableRows = $this->CI->jumb_game_logs->getAvailableRows($gameRecords);
                if (!empty($availableRows)) {
                    foreach ($availableRows as $record) {
                        $insertRow = array();

                        $start_time = new DateTime($this->gameTimeToServerTime($record['gameDate']));
                        $end_time   = new DateTime($this->gameTimeToServerTime($record['lastModifyTime']));
                        $player_username = isset($record['playerId'])?$record['playerId']:null;
                        $insertRow['seqNo'] = isset($record['seqNo'])?$record['seqNo']:null;
                        $insertRow['gType'] = isset($record['gType'])?$record['gType']:null;
                        $insertRow['mtype'] = isset($record['mtype'])?$record['mtype']:null;
                        $insertRow['gameDate'] = $start_time->format('Y-m-d H:i:s');
                        $insertRow['bet'] = isset($record['bet'])?$record['bet']:null;
                        $insertRow['win'] = isset($record['win'])?$record['win']:null;
                        $insertRow['total'] = isset($record['total'])?$record['total']:null;
                        $insertRow['currency'] = isset($record['currency'])?$record['currency']:null;
                        $insertRow['jackpot'] = isset($record['jackpot'])?$record['jackpot']:null;
                        $insertRow['jackpotContribute'] = isset($record['jackpotContribute'])?$record['jackpotContribute']:null;
                        $insertRow['denom'] = isset($record['denom'])?$record['denom']:null;
                        $insertRow['lastModifyTime'] = $end_time->format('Y-m-d H:i:s');
                        $insertRow['gameName'] = isset($record['gameName'])?$record['gameName']:null;
                        $insertRow['playerIp'] = isset($record['playerIp'])?$record['playerIp']:null;
                        $insertRow['clientType'] = isset($record['clientType'])?$record['clientType']:null;
                        $insertRow['hasFreegame'] = isset($record['hasFreegame'])?$record['hasFreegame']:null;
                        $insertRow['hasGamble'] = isset($record['hasGamble'])?$record['hasGamble']:null;
                        $insertRow['gambleBet'] = isset($record['gambleBet'])?$record['gambleBet']:null;
                        $insertRow['systemTakeWin'] = isset($record['systemTakeWin'])?$record['systemTakeWin']:null;
                        $insertRow['beforeBalance'] = isset($record['beforeBalance']) ? $record['beforeBalance'] : null;
                        $insertRow['afterBalance'] = isset($record['afterBalance']) ? $record['afterBalance'] : null;
                        # SBE use
                        $insertRow['username'] = $player_username;
                        $insertRow['playerId'] = $this->getPlayerIdInGameProviderAuth($player_username);
                        $insertRow['external_uniqueid'] = isset($record['seqNo'])?$record['seqNo']:null; //add external_uniueid for og purposes
                        $insertRow['response_result_id'] = $responseResultId;
                        $this->CI->jumb_game_logs->insertJumbGameLogs($insertRow);
                        $count++;
                    }
                    $result['count'] = $count;
                }
            }
            $this->CI->utils->debug_log('Jumb SyncOriginalGameLogs monitor count ======================>',$result['count']);
        }

        return array($success, $result);
    }

    public function syncMergeToGameLogs_oldVersion($token) {

        $this->CI->load->model(array('game_logs', 'player_model', 'jumb_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        //observer the date format
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $rlt = array('success' => true);

        $result = $this->CI->jumb_game_logs->getGameLogStatistics($startDate, $endDate);
        $cnt = 0;
        if ($result) {

            $unknownGame = $this->getUnknownGame();
            $bet_details = [];

            foreach ($result as $jumb_data) {

                $realbet = $this->gameAmountToDBGameLogsTruncateNumber(abs($jumb_data->bet_amount));

                if($jumb_data->gType == self::GAMBLE_TYPE){
                    #use gamble bet if type is gamble
                    if( ($jumb_data->gambleBet < 0 ) && ( $jumb_data->bet_amount == 0) ){
                        $realbet =  $this->gameAmountToDBGameLogsTruncateNumber(abs($jumb_data->gambleBet));
                    }
                }
               
                $uniqueId = $seqNo = isset($jumb_data->seqNo) ? $jumb_data->seqNo : null;
                $historyId = isset($jumb_data->historyId) ? $jumb_data->historyId : null;
                if(!$seqNo) $uniqueId = $historyId;

                $GameRecordId = $uniqueId;

                $bet_details = [
                    'place_of_bet' => lang($jumb_data->game),
                    'bet_amount' => $realbet,
                    'result_amount' =>  $this->gameAmountToDBGameLogsTruncateNumber($jumb_data->result_amount),
                ];

                $note = $this->convertGamedetatilsToJson($bet_details);
                if (!$jumb_data->playerId) {
                    continue;
                }

                $cnt++;

                $game_description_id = $jumb_data->game_description_id;
                $game_type_id = $jumb_data->game_type_id;

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                    $jumb_data->game_code = $unknownGame->game_name . '-' . $jumb_data->game_code;
                }

                $extra = array('table' => $GameRecordId, 'trans_amount' => $realbet, 'note' => $note);

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $jumb_data->game_code,
                    $jumb_data->game_type,
                    $jumb_data->game,
                    $jumb_data->playerId,
                    $jumb_data->username,
                    $realbet,
                    $this->gameAmountToDBGameLogsTruncateNumber($jumb_data->result_amount),
                    null, # win_amount
                    null, # loss_amount
                    $jumb_data->after_balance, # after_balance
                    0, # has_both_side
                    $jumb_data->external_uniqueid,
                    $jumb_data->date_start, //start
                    $jumb_data->date_end, //end
                    $jumb_data->response_result_id,
                    null,
                    $extra
                );

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
        return $rlt;
    }

    public function convertGamedetatilsToJson($gameDetails = null){

        $bet_details = lang("Bet amount") . ": " . $gameDetails['bet_amount'] . ", " . lang(" Bet result") . ": " .  $gameDetails['result_amount'];
        $data['bet_details'] = lang("Place of bet") . ": " . $gameDetails['place_of_bet'] . ", " . $bet_details;
        return json_encode($data);

    }

    public function changePassword($playerName, $oldPassword = null, $newPassword,$createPlayer = null) {

        $isPlayerExist = $this->isPlayerExist($playerName);
        $this->CI->utils->debug_log('isPlayerExist resultArr ======================================>', $isPlayerExist);

        if(!empty($isPlayerExist['exists'])){
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $context = array(
                    'callback_obj' => $this,
                    'callback_method' => 'processResultForchangePassword',
                    'playerName' => $playerName
                );

            $jumb_params = array(
                    "action"    => self::URI_MAP[self::API_changePassword],
                    'ts'        => $this->jumb_now() ,
                    'parent'    => $this->agent_code ,
                    "uid"       => $gameUsername,
                    "password"  => $newPassword . $this->password_suffix
                );

            $this->CI->utils->debug_log('change password params ==================>', $jumb_params);
            $params = array(
                'dc'    => $this->dc,
                'x'     => $this->jumb_argEncrypt($jumb_params)
            );

            return $this->callApi(self::API_changePassword, $params, $context);
        }
    }

    public function processResultForchangePassword($params){
        $SbeplayerName = $this->getVariableFromContext($params, 'SbeplayerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $SbeplayerName);
        $this->CI->utils->debug_log('processResultForchangePassword ==================>', $resultArr);

        if ($success) {
            $result = array('changePassword' => true);
        }else{
            $result = array('changePassword' => false);
        }

        return array($success, $result);
    }

    public function getLanguage($currentLang) {

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $language = 'ch';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $language = 'in';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $language = 'vi';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            //     $language = 'ko';
            //     break;
            //     they don't currently support Korean
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function queryTransaction($transactionId, $extra) {
        $playerName = $extra['playerName'];
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'playerName' => $playerName,
            'external_transaction_id'          => $transactionId
        );

        $jumb_params = array(
            'action'            => self::URI_MAP[self::API_queryTransaction] ,
            'ts'                => $this->jumb_now() ,
            'parent'            => $this->agent_code ,
            'serialNo'          => $transactionId
        );

        $params = array(
            'dc'    => $this->dc ,
            'x'     => $this->jumb_argEncrypt($jumb_params)
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = !empty($resultArr['data']) ? true : false;

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        );
        if ($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function getJumbGamelogs($action, $starttime, $endtime) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetJumbGamelogs',
            'starttime' => $starttime,
            'endtime' => $endtime,
        );

        $jumb_params = [
            'action' => $action,
            'ts' => $this->jumb_now(),
            'parent' => $this->agent_code,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'gTypes' => $this->game_types,
        ];

        $params = [
            'dc' => $this->dc,
            'x' => $this->jumb_argEncrypt($jumb_params)
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' request params ---------->', $jumb_params, $params);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForGetJumbGamelogs($params) {
		$this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$result = [
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0,
        ];

		if($success && isset($resultArr['data']) && !empty($resultArr['data'])) {
            $extra['response_result_id'] = $responseResultId;
			$gameRecords = $this->rebuildGameRecords($resultArr['data'], $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            $this->CI->utils->debug_log(__METHOD__ . ' after process available rows ---------->', 'gamerecords', count($gameRecords), 'insertrows', count($insertRows), 'updaterows', count($updateRows));

            $result['data_count'] += is_array($gameRecords) ? count($gameRecords): 0;

            if(!empty($insertRows)) {
                $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }

            unset($insertRows);

            if(!empty($updateRows)) {
                $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }

            unset($updateRows);
		}

		return array($success, $result);
	}

    public function rebuildGameRecords($gameRecords, $extra) {
        foreach($gameRecords as $record) {
            $insertRow = [];
            $start_time = new DateTime($this->gameTimeToServerTime($record['gameDate']));
            $end_time = new DateTime($this->gameTimeToServerTime($record['lastModifyTime']));
            $player_username = isset($record['playerId']) ? $record['playerId'] : null;
            $insertRow['seqNo'] = isset($record['seqNo']) ? $record['seqNo'] : null;
            $insertRow['gType'] = isset($record['gType']) ? $record['gType'] : null;
            $insertRow['mtype'] = isset($record['mtype']) ? $record['mtype'] : null;
            $insertRow['gameDate'] = $start_time->format('Y-m-d H:i:s');
            $insertRow['lastModifyTime'] = $end_time->format('Y-m-d H:i:s');
            $insertRow['bet'] = isset($record['bet']) && !empty($record['bet']) ? $record['bet'] : 0;
            $insertRow['win'] = isset($record['win']) && !empty($record['win']) ? $record['win'] : 0;
            $insertRow['total'] = isset($record['total']) && !empty($record['total']) ? $record['total'] : 0;
            $insertRow['gambleBet'] = isset($record['gambleBet']) && !empty($record['gambleBet']) ? $record['gambleBet'] : 0;
            $insertRow['jackpot'] = isset($record['jackpot']) && !empty($record['jackpot']) ? $record['jackpot'] : 0;
            $insertRow['jackpotContribute'] = isset($record['jackpotContribute']) && !empty($record['jackpotContribute']) ? $record['jackpotContribute'] : 0;
            $insertRow['denom'] = isset($record['denom']) && !empty($record['denom']) ? $record['denom'] : 0;
            # beforeBalance and afterBalance available only in Fishing Games
            $insertRow['beforeBalance'] = isset($record['beforeBalance']) && !empty($record['beforeBalance']) ? $record['beforeBalance'] : 0;
            $insertRow['afterBalance'] = isset($record['afterBalance']) && !empty($record['afterBalance']) ? $record['afterBalance'] : 0;
            $insertRow['currency'] = isset($record['currency']) ? $record['currency'] : null;
            $insertRow['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
            $insertRow['playerIp'] = isset($record['playerIp']) ? $record['playerIp'] : null;
            $insertRow['clientType'] = isset($record['clientType']) ? $record['clientType'] : null;
            $insertRow['hasFreegame'] = isset($record['hasFreegame']) ? $record['hasFreegame'] : 0;
            $insertRow['hasGamble'] = isset($record['hasGamble']) ? $record['hasGamble'] : 0;
            $insertRow['systemTakeWin'] = isset($record['systemTakeWin']) ? $record['systemTakeWin'] : 0;
            # SBE use
            $insertRow['username'] = $player_username;
            $insertRow['playerId'] = $this->getPlayerIdInGameProviderAuth($player_username);
            
            $insertRow['external_uniqueid'] = $insertRow['external_uniqueid'] = isset($record['seqNo']) ? $record['seqNo'] : null; //add external_uniueid for og purposes
            $insertRow['response_result_id'] = $extra['response_result_id'];
            $insertRow['historyId'] = isset($record['historyId']) ? $record['historyId'] : null;
            if(isset($record['historyId']) && !isset($record['seqNo'])){
                $insertRow['external_uniqueid'] = $record['historyId'];
            }
            $dataRecords[] = $insertRow;
        }

        return $dataRecords;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType) {
        $dataCount = 0;

        if(!empty($data)) {
            foreach($data as $record) {
                if($queryType == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                }else{
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }

                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    public function syncOriginalGameLogs($token) {
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
    	$dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
    	$dateTimeFrom->modify($this->getDatetimeAdjust());

        $currentDateTime = new DateTime($this->serverTimeToGameTime($this->utils->getNowForMysql()));
        // $currentDateTime = $currentDateTime->format('d-m-Y H:i:00');

        #datetime format
        $starttime = $dateTimeFrom;
		$endtime = $dateTimeTo;
        // $starttime = $dateTimeFrom->format('d-m-Y H:i:00');
		// $endtime = $dateTimeTo->format('d-m-Y H:i:00');

        # new data will remain only 2 hours in Action 29
        $data_maximum_time_limit = 2;

        $result = [
            'success' => true,
            'data_count' => 0,
            'data_count_insert' => 0,
			'data_count_update' => 0,
        ];

    	while($starttime <= $endtime) {
            $hours = floor(($currentDateTime->getTimestamp() - $starttime->getTimestamp())/(60*60));

            # check time if need to switch to action 64
            if($hours < $data_maximum_time_limit) {
                $action = self::URI_MAP[self::API_syncGameRecords];
                $sync_maximum_time_range = $this->sync_time_interval_action_29;
            }else{
                $action = self::URI_MAP[self::API_syncLostAndFound];
                $sync_maximum_time_range = $this->sync_time_interval_action_64;
            }

            $endtimeModified = clone $starttime;
            $endtimeModified->modify($sync_maximum_time_range);

            $gameRecordsResult = $this->getJumbGamelogs($action, $starttime->format('d-m-Y H:i:00'), $endtimeModified->format('d-m-Y H:i:00'));

            if($gameRecordsResult['success']) {
                $result['data_count'] += isset($gameRecordsResult['data_count']) && !empty($gameRecordsResult['data_count']) ? $gameRecordsResult['data_count'] : 0;
                $result['data_count_insert'] += isset($gameRecordsResult['data_count_insert']) && !empty($gameRecordsResult['data_count_insert']) ? $gameRecordsResult['data_count_insert']: 0;
                $result['data_count_update'] += isset($gameRecordsResult['data_count_update']) && !empty($gameRecordsResult['data_count_update']) ? $gameRecordsResult['data_count_update'] : 0;
            }else{
                $result['data_count'] += 0;
                $result['data_count_insert'] += 0;
                $result['data_count_update'] += 0;
            }

            sleep($this->sync_sleep_time);

            $this->CI->utils->debug_log(__METHOD__ . ' ----------> Switch Info', 'starttime', $starttime->format('d-m-Y H:i:00'), 'currentDateTime', $currentDateTime->format('d-m-Y H:i:00'), 'hours', $hours, 'action', $action, 'sync_maximum_time_range', $sync_maximum_time_range);

            $starttime->modify($sync_maximum_time_range);
    	}

    	return $result;
	}

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $game_logs_table = $this->original_table;
        $sqlTime = "{$game_logs_table}.updated_at >= ? AND {$game_logs_table}.updated_at <= ?";

        if($use_bet_time) {
            $sqlTime = "{$game_logs_table}.gameDate >= ? AND {$game_logs_table}.gameDate <= ?";
        }

        $sql = <<<EOD
SELECT
    {$game_logs_table}.id AS sync_index,
    {$game_logs_table}.username,
    CASE
            WHEN {$game_logs_table}.seqNo IS NOT NULL THEN {$game_logs_table}.seqNo
            ELSE {$game_logs_table}.historyId
    END as round_number,
    {$game_logs_table}.gType AS game_type,
    {$game_logs_table}.mtype AS game_code,
    {$game_logs_table}.gameDate AS bet_at,
    {$game_logs_table}.gameDate AS start_at,
    {$game_logs_table}.lastModifyTime AS end_at,
    {$game_logs_table}.bet AS real_betting_amount,
    {$game_logs_table}.win AS win_amount,
    {$game_logs_table}.total AS result_amount,
    {$game_logs_table}.gambleBet,
    {$game_logs_table}.jackpot,
    {$game_logs_table}.jackpotContribute,
    {$game_logs_table}.denom,
    {$game_logs_table}.beforeBalance AS before_balance,
    {$game_logs_table}.afterBalance AS after_balance,
    {$game_logs_table}.hasGamble,
    {$game_logs_table}.response_result_id,
    {$game_logs_table}.external_uniqueid,
    {$game_logs_table}.md5_sum,
    {$game_logs_table}.created_at,
    {$game_logs_table}.updated_at,
    {$game_logs_table}.historyId,
    game_provider_auth.login_name AS player_username,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_name AS game_description_name,
    game_description.game_type_id,
    game_description.english_name AS game
FROM
    {$game_logs_table}
    LEFT JOIN game_description ON {$game_logs_table}.mtype = game_description.game_code AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON {$game_logs_table}.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $round_number = isset($row['round_number']) ? $row['round_number'] : null;
        $bet_amount = !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0;
        $win_amount = !empty($row['win_amount']) ? $this->gameAmountToDB($row['win_amount']) : 0;
        $real_betting_amount = !empty($row['real_betting_amount']) ? $this->gameAmountToDB($row['real_betting_amount']) : 0;
        $gamble_bet = !empty($row['gambleBet']) ? $this->gameAmountToDB($row['gambleBet']) : 0;
        $jackpot = !empty($row['jackpot']) ? $this->gameAmountToDB($row['jackpot']) : 0;
        $jackpot_contribute = !empty($row['jackpotContribute']) ? $this->gameAmountToDB($row['jackpotContribute']) : 0;
        $denom = !empty($row['denom']) ? $this->gameAmountToDB($row['denom']) : 0;
        $result_amount = !empty($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0;
        $before_balance = !empty($row['before_balance']) ? $this->gameAmountToDB($row['before_balance']) : 0;
        $after_balance = !empty($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0;

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $result_amount,
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $real_betting_amount,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => $after_balance,
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : '0000-00-00 00:00:00',
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : '0000-00-00 00:00:00'
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => $round_number,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => [
                "Game Sequence Number" => $round_number,
                "Username" => isset($row['username']) ? $row['username'] : null,
                "Game Type" => isset($row['game_type']) ? $row['game_type'] : null,
                "Game Code" => isset($row['game_code']) ? $row['game_code'] : null,
                "Bet Amount" => $bet_amount,
                "Win Amount" => $win_amount,
                "Total Win Loss" => $result_amount,
                "Gamble Bet" => $gamble_bet,
                "Jackpot" => $jackpot,
                "Jackpot Contribution" => $jackpot_contribute,
                "Bet Denomination" => $denom,
                "Before Balance" => $before_balance,
                "After Balance" => $after_balance,
                "Has Gamble" => isset($row['hasGamble']) ? $row['hasGamble'] : null,
                "History Id" => isset($row['historyId']) ? $row['historyId'] : null,
            ],
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row) {
        if(empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = isset($row['end_at']) && !empty($row['end_at']) ? Game_logs::STATUS_SETTLED : Game_logs::STATUS_PENDING;
        $row['note'] = $this->getNote($row['result_amount']);
        $row['real_betting_amount'] = isset($row['real_betting_amount']) && !empty($row['real_betting_amount']) ? abs($row['real_betting_amount']) : 0;
        $row['gambleBet'] = isset($row['gambleBet']) && !empty($row['gambleBet']) ? abs($row['gambleBet']) : 0;
        $row['jackpotContribute'] = isset($row['jackpotContribute']) && !empty($row['jackpotContribute']) ? abs($row['jackpotContribute']) : 0;

        if(isset($row['hasGamble']) && $row['hasGamble']) {
            $row['real_betting_amount'] = $row['gambleBet'];
        }

        $row['bet_amount'] = $row['real_betting_amount'] - $row['jackpotContribute'];
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_type_id = null;
        $game_description_id = null;

        if(isset($row['game_description_id']) && !empty($row['game_description_id']) && isset($row['game_type_id']) && !empty($row['game_type_id'])) {
            $game_type_id = $row['game_type_id'];
            $game_description_id = $row['game_description_id'];
        }else{
            $game_type_id = $unknownGame->game_type_id;
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $game_type_id, $row['game_code'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    public function getNote($result_amount) {
        $note = '';

        if($result_amount > 0) {
            $note = 'Win';
        }elseif($result_amount < 0) {
            $note = 'Lose';
        }elseif($result_amount == 0) {
            $note = 'Draw';
        }else{
            $note = '';
        }

        return $note;
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

    // public function batchQueryPlayerBalance($playerNames, $syncId = null) {
    //     if (empty($playerNames)) {
    //         $players = $this->CI->player_model->getPlayerListOnlyAvailBal($this->getPlatformCode());
    //         $playerNames = array();
    //         foreach ($players as $player) {
    //             $username = $player->username;
    //             $playerNames[]=$username;
    //         }
    //     }

    //     return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    // }

}
