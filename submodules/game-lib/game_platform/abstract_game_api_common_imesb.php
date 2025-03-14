<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
    * API NAME: IMESB - v3.1-EN
    * Ticket No: OGP-16654
    *
    * @category Game_platform
    * @version not specified
    * @copyright 2013-2022 tot
    * @author
 */

abstract class Abstract_game_api_common_imesb extends Abstract_game_api {
    const VALIDATE_TOKEN_SUCCESS_RESPONSE = 100;
    const VALIDATE_TOKEN_FAILED_RESPONSE = 1;
    const API_SUCCESS_RESPONSE = 0;
    const SYNC_GAMELOGS_LANGCODE = "eng";

    const GAME_STATUS_WIN = 1;
    const GAME_STATUS_LOSE = 2;
    const GAME_STATUS_DRAW = 3;

    const LANG_EN = 0;
    const LANG_CHS = 1;
    const LANG_CHT = 2;
    const LANG_TH = 3;
    const LANG_VN = 4;
    const LANG_KO = 5;
    const LANG_ID = 6;
    const DEFAULT_SPORTS_ID = 0;

    const ESPORTS = 1;
    const ESPORTS_VIRTUAL = 2;
    const ESPORTS_RNG = 3;

    // PK10 Legends betStatus
    const ESPORTS_RNG_BET_INVALID = -1;
    const ESPORTS_RNG_BET_CONFIRMED = 0;
    const ESPORTS_RNG_BET_SETTLED = 1;
    const ESPORTS_RNG_BET_CANCELLED = 2;

    // PK10 Legends settleStatus
    const ESPORTS_RNG_SETTLE_INITIAL = 0;
    const ESPORTS_RNG_SETTLE_WIN = 1;
    const ESPORTS_RNG_SETTLE_LOSE = 2;
    const ESPORTS_RNG_SETTLE_DRAW = 3;

    const BET_SETTLED = "S";
    const BET_PLACED = "P";
    const BET_VOID = "V";

    const MOBILE = 2;
    const INTERNET = 1;

    const BET_CH_MOBILE = "MOBILE";
    const BET_CH_INTERNET = "INTERNET";

	const MD5_FIELDS_FOR_ORIGINAL = [
        'betid',
        'betdate',
        'lastupdated',
        'membercode',
        'oddstype',
        'odds',
        'currency',
        'stake',
        'result',
        'isparlay',
        'issettled',
        'iscancelled',
        'settlementtime',
        'bettingchannel',
        'betdetails',
        'sportsid',
        'sportsname',
        'matchid',
        'leagueid',
        'leaguename',
        'baseleagueid',
        'baseleaguename',
        'baseleagueabbr',
        'hometeamid',
        'hometeamname',
        'hometeamabbr',
        'basetierid',
        'basetiercode',
        'basetiername',
        'selection',
        'gameorder',
        'gametypecode',
        'matchtype',
        'winlose',
        'homescore',
        'awayscore',
        'matchdatetime',
        'canceltype',
        'handicap'
    ];
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'odds',
        'stake',
        'result',
    ];
    # Fields in nttech_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'status_in_db',
        'sportsid',
        'settlementtime',
        'lastupdated',
        'bet_amount',
        'matchid',
        'game_code',
        'game_name',
        //'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'response_result_id'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'after_balance',
        'result_amount',
    ];

    const URI_MAP = array(
        self::API_queryPlayerBalance => '/api/getsinglememberbalance',
        self::API_queryTransaction => '/api/gettransferstatus',
        self::API_depositToGame => '/api/deposit',
        self::API_withdrawFromGame => '/api/withdrawal',
        self::API_login => '/api/login',
        self::API_createPlayer => '/api/login',
        self::API_logout => '/api/logout',
        #self::API_syncGameRecords => '/api/getesportbetdetailbymatchdatetimelang',
        self::API_syncGameRecords => '/api/getesportsbetdetailbybetdatetimelang',
        self::API_syncLostAndFound => '/api/getesportsbetdetailbysettlementdatetimelang',
    );

    public function __construct() {
        parent::__construct();
        
        $this->api_url = $this->getSystemInfo('url', 'http://ole777.esapi.test.imapi.net');
        $this->game_url = $this->getSystemInfo('game_url', 'http://imesports.staging.ole888.net');
        $this->record_url = $this->getSystemInfo('record_url', $this->api_url);
        $this->demo_game_url = $this->getSystemInfo('demo_game_url', 'http://esportplay.demo.inplaymatrix.com');
        $this->timestamp_encription_key = $this->getSystemInfo('timestamp_encription_key', 'a49378de9ea979f5');
        $this->currency_code = $this->getSystemInfo('currency_code', 'RMB');
        $this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore',false);

        $this->sync_game_logs_range = $this->getSystemInfo('sync_game_logs_range', '-24 hours');

    }

    public function callback($result)
    {
        $this->utils->debug_log("IMESB_API Callback >=======> ".$this->utils->encodeJson($result));

        $failedResponse = ['statusCode' => self::VALIDATE_TOKEN_FAILED_RESPONSE,
                           'statusDesc' => "Failed, Player Does Not Exists"];

        if (isset($result['token'])) {
            $is_enabled_callback_testmode = $this->getSystemInfo('is_enabled_callback_testmode', false);
            $callback_testmode_user = $this->getSystemInfo('callback_testmode_user', 'testt1dev');

            $playerInfo = $this->getPlayerInfoByToken($result['token']);
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);

            $successResponse = ['MemberCode' => !empty($gameUsername)?$gameUsername:null,
                                'CurrencyCode' => $this->currency_code,
                                'IPAddress' => $this->utils->getIP(),
                                'statusCode' => self::VALIDATE_TOKEN_SUCCESS_RESPONSE,
                                'statusDesc' => "Success"];

            if($is_enabled_callback_testmode || !empty($gameUsername)){
                if($is_enabled_callback_testmode){
                    $successResponse['MemberCode'] = $callback_testmode_user;
                }
                return $successResponse;
            }else{
                return $failedResponse;
            }
        }
        return $failedResponse;
    }

    # Login Flow
    # Operator: Call LOGIN API -> IMESB: Extract Token from API request
    # IMESB: Call ValidateToken API from operator -> Operator: Authenticate Result then Return Validate Token Response -> IMESB: Received Member Info -> IMESB: Create Session for Member -> IMESB-> Return Login Response -> Operator: Launch Game
    public function login($playerName, $password = null, $extra = null)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName
        );

        $params = array(
            "timestamp" => $this->generateTimeStamp(),
            "token" => $this->getPlayerTokenByUsername($playerName),
        );

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);
        if($success) {
            $playerId = $this->getPlayerIdInPlayer($playerName);
            $this->setGameAccountRegistered($playerId);
        }
        return array($success, $resultArr);
    }

    public function logout($playerName, $password = null, $extra = null)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName
        );

        $params = array(
            "timestamp" => $this->generateTimeStamp(),
            "token" => $this->getPlayerTokenByUsername($playerName),
        );

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername, $statusCode);
        return array($success, $resultArr);
    }

    private function generateTimeStamp()
    {
        $timestamp = gmdate("D, j M Y H:i:s \G\M\T");
        
        $encryptedTimestamp = $this->desEncrypt($timestamp, $this->timestamp_encription_key, 'des-ede');

        return $encryptedTimestamp;
    }

    /**
     *
     * encrypt by openssl
     *
     * @param  string  $original
     * @param  string  $secretKey
     * @param  string  $method
     * @param  integer $options
     * @param  string  $iv
     * @return string base64
     */
    public function desEncrypt($original, $secretKey, $method='des-ede') {

        $secretKey = mb_convert_encoding($secretKey, "UTF8");
        $secretKey = md5($secretKey,true);
        $result = openssl_encrypt($original, $method, $secretKey,OPENSSL_RAW_DATA);
        return base64_encode($result);

    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        $apiUri = self::URI_MAP[$apiName];
        $url = $this->api_url.$apiUri;
        if($apiName==self::API_syncGameRecords || $apiName == self::API_syncLostAndFound){
            $url = $this->record_url.$apiUri;
        }
        return $url;
    }

    public function getHttpHeaders($params){
        return array("Accept" => "application/json", "Content-Type" => "application/json");
    }

    public function customHttpCall($ch, $params) {
        $data_json = json_encode($params);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    protected function processResultBoolean($responseResultId, $resultArr, $playerName = null, $statusCode) {
        $success = false;

        if(($statusCode==200 || $statusCode==201) && $resultArr['StatusCode'] == self::API_SUCCESS_RESPONSE) {
            $success = true;
        }

        $this->CI->utils->debug_log('IMESB Process Result ', $statusCode);

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('IMESB got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }

        $this->CI->utils->debug_log('Result Array:', $resultArr);
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName,$playerId,$password,$email,$extra);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName
        );

        $params = array(
            "timestamp" => $this->generateTimeStamp(),
            "token" => $this->getPlayerTokenByUsername($playerName),
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);
        if($success) {
            $playerId = $this->getPlayerIdInPlayer($playerName);
            $this->setGameAccountRegistered($playerId);
        }
        return array($success, $resultArr);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        );

        $params = array(
            "TimeStamp" => $this->generateTimeStamp(),
            "MemberCode" => $gameUsername
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result = [];
        if($success){
            $result['balance'] = $this->cutAmountTo2(floatval($resultArr['balanceCredit']));
        }

        return array($success, $result);
    }

    #Amount to be transferred. To transfer credit out put in a minus value
    # Transfer Flow
    # Operator: Call Transfer API -> IMESB: CallValidateToken
    # Operator: Return Validate Token Response -> IMESB: Process Transfer
    public function depositToGame($userName, $amount, $transfer_secure_id=null) {
        return $this->transferCredit("deposit",$userName, $amount);
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {
        return $this->transferCredit("withdraw",$userName, $amount);
    }

    public function transferCredit($transferType,$userName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $transferID = empty($transfer_secure_id) ? $this->generateTransferId() : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTransferCredit',
            'playerName' => $userName,
            'external_transaction_id' => $transferID,
            'amount' => $this->dBtoGameAmount($amount),
        );

        $params = array(
            "MemberCode" => $gameUsername,
            "Amount" => $this->dBtoGameAmount($amount),
            "CurrencyCode" => $this->currency_code,
            "TransferID" => $transferID,
            "Token" => $this->getPlayerTokenByUsername($userName),
            "TimeStamp" => $this->generateTimeStamp()
        );

        if($transferType == "deposit"){
            return $this->callApi(self::API_depositToGame, $params, $context);
        }else{
            return $this->callApi(self::API_withdrawFromGame, $params, $context);
        }
    }

    public function processResultForTransferCredit($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => $this->getReasons($statusCode)
        );

        #verify transaction to GP if success
        if($this->verify_transfer_using_query_transaction){
            $query_transaction_status = $this->queryTransaction($external_transaction_id, []);
            if(isset($query_transaction_status['status']) && $query_transaction_status['status'] == self::COMMON_TRANSACTION_STATUS_APPROVED){
                $success = true;
            }else{
                $success = false;
            }
            $this->CI->utils->debug_log('IMESB_API verify_transfer_using_query_transaction',$external_transaction_id, $query_transaction_status);
        }
        
        if($success){
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $success=true;
            }else{
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id'] = $this->getReasons($statusCode);
            }
        }
        return array($success, $result);

    }

    public function queryTransaction($transactionId, $extra) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId
        );

        $params = array(
            "TransferID" => $transactionId,
            "TimeStamp" => $this->generateTimeStamp()
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success && ($resultArr['transferStatus'] == "Approved" || $resultArr['transferStatus'] == "Transfer Approved")) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $result['reason_id'] = self::REASON_TRANSACTION_NOT_FOUND;
            $this->CI->utils->debug_log('IMESB_API processResultForQueryTransaction External transaction id not found!');
        }

        $this->CI->utils->debug_log('IMESB_API processResultForQueryTransaction', $resultArr, $statusCode, $success);
        return array($success, $result);
    }

    /* Query Forward Game */
    # Provide game launch URL
    # "mobile": "player_center/goto_imesb_game/5714/45/en/true",
    # "desktop": "player_center/goto_imesb_game/5714/45/en/null/real"
    # "desktop_demo": "player_center/goto_imesb_game/5714/45/en/null/demo"
    public function queryForwardGame($playerName, $extra = array())
    {
        #IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL
        $language = $this->getSystemInfo('default_gamelaunch_language', $extra['language']);
        $language = $this->processPlayerLanguageForParams($language);

        #IDENTIFY IF GAME CODE IS INVOKED IN GAME URL
        $sportId = self::DEFAULT_SPORTS_ID;
        if(isset($extra['game_code'])){
            $sportId = $extra['game_code'];
        }

        #IDENTIFY IF IS_MOBILE IS INVOKED IN GAME URL
        $deviceUrlPath = "esport.aspx";
        if(isset($extra['is_mobile'])){
            $isMobile = $extra['is_mobile'];
            if($isMobile){
                $deviceUrlPath = "mobile.aspx";
            }
        }

        $gameMode = "real";
        if(isset($extra['game_mode'])){
            $gameMode = $extra['game_mode'];
        }

        $isLoggedIn = $this->login($playerName);
        if($isLoggedIn['success'] && $gameMode == "real")
        {
            $token = $this->getPlayerTokenByUsername($playerName);
            $gameUrl = $this->game_url."/".$deviceUrlPath."?token=".$token."&languageCode=".$language."&SportId=".$sportId;
        }else{
            $gameUrl = $this->demo_game_url."/".$deviceUrlPath."?token=123123123123123&languageCode=".$language."&SportId=".$sportId;
        }
        return ["success"=>true,"url"=>$gameUrl];
    }

    private function processPlayerLanguageForParams($lang)
    {
        switch ($lang) {
            case "en":
            case Language_function::INT_LANG_ENGLISH;
                return self::LANG_EN; break;
            case "chs":
            case Language_function::INT_LANG_CHINESE;
                return self::LANG_CHS; break;
            case "cht":
                return self::LANG_CHT; break;
            case "th":
            case Language_function::INT_LANG_THAI;
                return self::LANG_TH; break;
            case "vn":
            case Language_function::INT_LANG_VIETNAMESE;
                return self::LANG_VN; break;
            case "ko":
            case Language_function::INT_LANG_KOREAN;
                return self::LANG_KO; break;
            case "id":
            case Language_function::INT_LANG_INDONESIAN;
                return self::LANG_ID; break;
            default:
                return self::LANG_EN; break;
        }
    }

    private function getReasons($reason_id) {
        switch ($reason_id) {
            case 0:
                return self::COMMON_TRANSACTION_STATUS_APPROVED;
                break;
            case 1:
                return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                break;
            default:
                return self::COMMON_TRANSACTION_STATUS_DECLINED;
                break;
        }
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        $playerId = $this->getPlayerIdInPlayer($playerName);
        if(!empty($playerId)){
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }
        return array('success' => true);
    }

    function getFileExtension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }

    public function syncOriginalFromFile() {
        $this->syncOriginalGameLogsFromCSV();
    }

    public function syncOriginalGameLogsFromCSV(){
        set_time_limit(0);
    	$this->CI->load->model(array('external_system','original_game_logs_model'));
    	$extensions = array("csv");
    	$game_logs_path = $this->getSystemInfo('csv_imesb_game_records_path');
    	$exported_file = array_diff(scandir($game_logs_path,1), array('..', '.'));

    	$count = 0;

    	if(!empty($exported_file)){
    		foreach ($exported_file as $key => $csv) {
    			$ext = $this->getFileExtension($csv);
                if (!in_array($ext,$extensions)) {//skip other extension
                    continue;
                }
                
				$file = fopen($game_logs_path."/".$csv,"r");

                $all_rows = array();
                $header = fgetcsv($file);


                while ($row = fgetcsv($file)) {
                    $all_rows[] = array_combine($this->remove_unwanted_characters($header), $this->remove_unwanted_characters($row));
                }
				
				fclose($file);

                if(!empty($all_rows) && isset($all_rows[0]['Bet No'])){
                    $this->CI->utils->debug_log('IMESB csv: ', "Esports");
                    $this->insertOriginalGameLogsEsports($all_rows);
                }else if(!empty($all_rows) && isset($all_rows[0]['Bet ID'])){
                    $this->CI->utils->debug_log('IMESB csv: ', "Esports Virtual");
                    $this->insertOriginalGameLogsEsportsVirtual($all_rows);
                }
			}

            $this->CI->utils->debug_log('IMESB csv: ', $all_rows);
    	}

        
    	
    	$result = array('data_count'=>$count);
    	return array("success" => true,$result);
		
    }

    public function remove_unwanted_characters($data){
        $res = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $data);
        return $res;
    }

    public function insertOriginalGameLogsEsports($gameRecords = null) {

        $params = [
            "timestamp" => $this->generateTimeStamp(),
            "language" => self::SYNC_GAMELOGS_LANGCODE,
            "product" => self::ESPORTS
        ];

        $responseResultId = $this->getResponseResultIdFromParams($params);

        foreach($gameRecords as $index => $record)
        {
            $data['betid'] = isset($record['Bet No']) ? $record['Bet No'] : null;
            $data['betdate'] = isset($record['Date Created']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['Date Created']))) : null;
            $data['lastupdated'] = isset($record['Date Created']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['Date Created']))) : null;
            $data['membercode'] = isset($record['Member Code']) ? $this->processMemberCode($record['Member Code']) : null;
            $data['oddstype'] = isset($record['Odds Type']) ? $record['Odds Type'] : null;
            $data['odds'] = isset($record['Stake Odds']) ? $record['Stake Odds'] : null;
            $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
            $data['stake'] = isset($record['Bet AmtL']) ? $record['Bet AmtL'] : null;
            $data['result'] = isset($record['Stake Return AmtL']) ? $record['Stake Return AmtL'] : null;
            $data['isparlay'] = isset($record['IsParlay']) ? json_encode($record['IsParlay']) : null; // No Parlay
            $data['issettled'] = isset($record['Settled']) ? $record['Settled'] : null;
            $data['iscancelled'] = isset($record['Cancel']) ? $record['Cancel'] : null;
            $data['settlementtime'] = isset($record['SettlementTime']) ? $this->gameTimeToServerTime($record['SettlementTime']) : null; //No settledtime
            $data['bettingchannel'] = isset($record['Bet Type']) ? $record['Bet Type'] : null;

            $data['betdetails'] = null;
            $data['sportsid'] = isset($record['Sport ID']) ? $record['Sport ID'] : null;
            $data['sportsname'] = isset($record['Sport Name']) ? $record['Sport Name'] : null;
            $data['matchid'] = isset($record['Match No']) ? $record['Match No'] : null;
            $data['leagueid'] = isset($record['LeagueID']) ? $record['LeagueID'] : null; //No League Id
            $data['leaguename'] = isset($record['League Name']) ? $record['League Name'] : null;
            $data['baseleagueid'] = isset($record['BaseLeagueID']) ? $record['BaseLeagueID'] : null; // No BaseLeagueID
            $data['baseleaguename'] = isset($record['BaseLeagueName']) ? $record['BaseLeagueName'] : null;
            $data['baseleagueabbr'] = isset($record['BaseLeagueAbbr']) ? $record['BaseLeagueAbbr'] : null;
            $data['hometeamid'] = isset($record['HomeTeamID']) ? $record['HomeTeamID'] : null;
            $data['hometeamname'] = isset($record['Team Name Home']) ? $record['Team Name Home'] : null;
            $data['hometeamabbr'] = isset($record['HomeTeamAbbr']) ? $record['HomeTeamAbbr'] : null;
            $data['awayteamid'] = isset($record['AwayTeamID']) ? $record['AwayTeamID'] : null;
            $data['awayteamname'] = isset($record['Team Name Away']) ? $record['Team Name Away'] : null;
            $data['awayteamabbr'] = isset($record['AwayTeamAbbr']) ? $record['AwayTeamAbbr'] : null;
            $data['basetierid'] = isset($record['BaseTierID']) ? $record['BaseTierID'] : null;
            $data['basetiercode'] = isset($record['BaseTierCode']) ? $record['BaseTierCode'] : null;
            $data['basetiername'] = isset($record['BaseTierName']) ? $record['BaseTierName'] : null;
            $data['selection'] = isset($record['Selection']) ? $record['Selection'] : null;
            $data['gameorder'] = isset($record['Game Order']) ? $record['Game Order'] : null;
            $data['gametypecode'] = isset($record['Game Type']) ? $record['Game Type'] : null;
            $data['matchtype'] = isset($record['MatchType']) ? $record['MatchType'] : null;
            $data['winlose'] = isset($record['WinLose']) ? $record['WinLose'] : null;
            $data['issettled'] = isset($record['Settled']) ? $record['Settled'] : null;
            $data['homescore'] = isset($record['Score Home']) ? $record['Score Home'] : null;
            $data['awayscore'] = isset($record['Score Away']) ? $record['Score Away'] : null;
            $data['matchdatetime'] = isset($record['Date Created']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['Date Created']))) : null;
            $data['canceltype'] = isset($record['Cancel']) ? $record['Cancel'] : null;
            $data['handicap'] = isset($record['Handicap']) ? $record['Handicap'] : null;

            $data['response_result_id'] = $responseResultId;
            $data['external_uniqueid'] = $record['Bet No'];
            $gameRecords[$index] = $data;
            unset($data);
        }

        $result = ['data_count' => 0];

        list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
            $this->originalTable,
            $gameRecords,
            'external_uniqueid',
            'external_uniqueid',
            self::MD5_FIELDS_FOR_ORIGINAL,
            'md5_sum',
            'id',
            self::MD5_FLOAT_AMOUNT_FIELDS
        );

        if (!empty($insertRows)) {
            $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
        }
        unset($insertRows);

        if (!empty($updateRows)) {
            $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
        }
        unset($updateRows);
        
        return array(true, $result);

	}

    public function insertOriginalGameLogsEsportsVirtual($gameRecords = null) {

        $params = [
            "timestamp" => $this->generateTimeStamp(),
            "language" => self::SYNC_GAMELOGS_LANGCODE,
            "product" => self::ESPORTS_VIRTUAL
        ];

        $responseResultId = $this->getResponseResultIdFromParams($params);
        
        if(!empty($gameRecords))
        {
            foreach($gameRecords as $index => $record)
            {
                $isSettled = true;
                
                //Start additional fields for MD5
                $data['oddstype'] = isset($record['OddsType']) ? $record['OddsType'] : null;
                $data['isparlay'] = "false";
                $data['betdetails'] = null;
                $data['leagueid'] = isset($record['LeagueID']) ? $record['LeagueID'] : null;
                $data['leaguename'] = isset($record['LeagueName']) ? $record['LeagueName'] : null;
                $data['baseleagueid'] = isset($record['BaseLeagueID']) ? $record['BaseLeagueID'] : null;
                $data['baseleaguename'] = isset($record['BaseLeagueName']) ? $record['BaseLeagueName'] : null;
                $data['baseleagueabbr'] = isset($record['BaseLeagueAbbr']) ? $record['BaseLeagueAbbr'] : null;
                $data['hometeamid'] = isset($record['HomeTeamID']) ? $record['HomeTeamID'] : null;
                $data['hometeamname'] = isset($record['Team Name Home']) ? $record['Team Name Home'] : null;
                $data['hometeamabbr'] = isset($record['HomeTeamAbbr']) ? $record['HomeTeamAbbr'] : null;
                $data['awayteamid'] = isset($record['AwayTeamID']) ? $record['AwayTeamID'] : null;
                $data['awayteamname'] = isset($record['Team Name Away']) ? $record['Team Name Away'] : null;
                $data['awayteamabbr'] = isset($record['AwayTeamAbbr']) ? $record['AwayTeamAbbr'] : null;
                $data['basetierid'] = isset($record['BaseTierID']) ? $record['BaseTierID'] : null;
                $data['basetiercode'] = isset($record['BaseTierCode']) ? $record['BaseTierCode'] : null;
                $data['basetiername'] = isset($record['BaseTierName']) ? $record['BaseTierName'] : null;
                $data['gameorder'] = isset($record['Game Order']) ? $record['GameOrder'] : null;
                $data['gametypecode'] = isset($record['GameType']) ? $record['GameType'] : null;
                $data['matchtype'] = isset($record['MatchType']) ? $record['MatchType'] : null;
                $data['winlose'] = isset($record['WinLose']) ? $record['WinLose'] : null;
                $data['issettled'] = $isSettled;
                $data['homescore'] = isset($record['ScoreHome']) ? $record['ScoreHome'] : null;
                $data['awayscore'] = isset($record['ScoreAway']) ? $record['ScoreAway'] : null;
                $data['canceltype'] = isset($record['Cancel']) ? $record['Cancel'] : null;
                $data['handicap'] = isset($record['Handicap']) ? $record['Handicap'] : null;
                $data['iscancelled'] = isset($record['Cancel']) ? $record['Cancel'] : null;
                $data['bettingchannel'] = isset($record['Bet Type']) ? $record['Bet Type'] : null;
                //END additional fields for MD5

                $data['sportsname'] = isset($record['Bet Description']) ? $record['Bet Description'] : null;
                $data['membercode'] = isset($record['Customer ID']) ? $this->processMemberCode($record['Customer ID']) : null;
                $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['betid'] = isset($record['Bet ID']) ? $record['Bet ID'] : null;
                $data['eventid'] = isset($record['Event ID']) ? $record['Event ID'] : null;
                $data['eventname'] = isset($record['Event']) ? $record['Event'] : null;
                $data['matchdatetime'] = isset($record['Placed']) ? $this->gameTimeToServerTime($record['Placed']) : null;
                $data['betdate'] = isset($record['Placed']) ? $this->gameTimeToServerTime($record['Placed']) : null;
                $data['settlementtime'] = isset($record['Placed']) ? $this->gameTimeToServerTime($record['Placed']) : null;
                $data['sportsid'] = isset($record['Product']) ? $record['Product'] : null;
                $data['stake'] = isset($record['Stake']) ? $record['Stake'] : null;
                $data['result'] = isset($record['Return']) ? $record['Return'] : null;
                $data['betstatus'] = isset($record['BetStatus']) ? $record['BetStatus'] : null; //N/A
                $data['betdescription'] = isset($record['Bet Description']) ? $record['Bet Description'] : null;
                $data['matchid'] = isset($record['MatchId']) ? $record['MatchId'] : null; //N/A
                $data['marketdate'] = isset($record['Placed']) ? $this->gameTimeToServerTime($record['Placed']) : null;
                $data['selection'] = isset($record['Selection']) ? $record['Selection'] : null;
                $data['betoutcome'] = isset($record['BetOutcome']) ? $record['BetOutcome'] : null; //N/A
                $data['odds'] = isset($record['Odds']) ? $record['Odds'] : null; //N/A
                $data['lastupdated'] = isset($record['Placed']) ? $this->gameTimeToServerTime($record['Placed']) : null;
                
                $data['response_result_id'] = $responseResultId;
                $data['external_uniqueid'] = $record['Bet ID'];
                $gameRecords[$index] = $data;
                unset($data);
            }

            $result = ['data_count' => 0];

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
            }
            unset($updateRows);
            
            return array(true, $result);
            }
    }
    
    /*
     * 1.) Initial pull on this API with following parameter:
     *     a. Start Date: Current Date – 14 days (GMT -4) b. End Date: Current date (GMT -4)
     *
     *        Reason behind: Currently for normal fixture,
     *        market will be opened 7 days in advanced.
     *        Settlement allow to be changed within 7 days after match date.
     *        Therefore suggestion is 7 days (match opened in advanced days) + 7 days
     *        (Settlement allows to changed days ) = 14 days in advanced.
     *
     * 2.) Subsequently pull this API every 10 mins with following parameter:
     *     a. Start Date: Current Date – 14 days (GMT -4)
     *     b. End Date: Current date (GMT -4)
     *     c. lastUpdated: Current date time – 15 minutes (GMT -4)
     *
     *        Reason: To retrieve any wager which has been modified during last 15 minutes.
     *        Since the last updated range is bigger than pulling frequency (10 mins)
     *        it will cover all changed within this period.
     *
     * 3.) Update wager information with data retrieved from step 2
     *
     *  Business Rules:
     *  ▪ All bet transactions data will not be changed after 7 days of the match time.
     *  ▪ Extract member details preferably in a daily basis to avoid large data volume
     *    and system timeout.
     */
    public function syncOriginalGameLogs($token = false) {
        $apiName = self::API_syncGameRecords;
        $this->getEsportsVirtual($token, $apiName); //OGP-23988
        $this->getEsportsRng($token, $apiName); //OGP-24604

        //$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        //$startDate->sub(new DateInterval($this->sync_game_logs_range));
        $startDate->modify($this->sync_game_logs_range);
        
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('IMESB Date Params Here: ', $startDate, $endDate);

        $queryDateTimeStart = $startDate->format("Y-m-d\TH:i:s")."-04:00";
        $queryDateTimeEnd = $endDate->format('Y-m-d\TH:i:s')."-04:00";

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords'
        );

        $params = [
            "startdate" => $queryDateTimeStart,
            "enddate" => $queryDateTimeEnd,
            "timestamp" => $this->generateTimeStamp(),
            "language" => self::SYNC_GAMELOGS_LANGCODE,
            "product" => self::ESPORTS
        ];
        $this->CI->utils->debug_log('syncOriginalGameLogs (IMESB request): ', json_encode($params), 'apiName', $apiName);
        return $this->callApi($apiName, $params, $context);
    }

    public function syncLostAndFound($token) {
        $apiName = self::API_syncLostAndFound;
        $this->getEsportsVirtual($token, $apiName); //OGP-23988
        $this->getEsportsRng($token, $apiName); //OGP-24604

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        
        $startDate->modify($this->sync_game_logs_range);
        
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('IMESB Date Params Here: ', $startDate, $endDate);

        $queryDateTimeStart = $startDate->format("Y-m-d\TH:i:s")."-04:00";
        $queryDateTimeEnd = $endDate->format('Y-m-d\TH:i:s')."-04:00";

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords'
        );

        $params = [
            "startdate" => $queryDateTimeStart,
            "enddate" => $queryDateTimeEnd,
            "timestamp" => $this->generateTimeStamp(),
            "language" => self::SYNC_GAMELOGS_LANGCODE,
            "product" => self::ESPORTS
        ];

        $this->CI->utils->debug_log('syncOriginalGameLogs (IMESB request): ', json_encode($params), 'apiName', $apiName);
        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);

        $resultArr = $this->getResultJsonFromParams($params);
        // $this->CI->utils->debug_log('syncOriginalGameLogs (IMESB response): ', json_encode($resultArr));
        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = array();
        if ($success) {
            $gameRecords = isset($resultArr['AllBetDetails'])?$resultArr['AllBetDetails']:array();

            if (!empty($gameRecords)) {
                $result = ['data_count' => 0];
                $this->processGameRecords($gameRecords,$responseResultId);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->originalTable,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);
            }
        }
        return array($success, $result);
    }

    public function processGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords))
        {
            foreach($gameRecords as $index => $record)
            {
                $data['betid'] = isset($record['BetID']) ? $record['BetID'] : null;
                $data['betdate'] = isset($record['BetDate']) ? $this->gameTimeToServerTime($record['BetDate']) : null;
                $data['lastupdated'] = isset($record['LastUpdated']) ? $this->gameTimeToServerTime($record['LastUpdated']) : null;
                $data['membercode'] = isset($record['MemberCode']) ? $this->processMemberCode($record['MemberCode']) : null;
                $data['oddstype'] = isset($record['OddsType']) ? $record['OddsType'] : null;
                $data['odds'] = isset($record['Odds']) ? $record['Odds'] : null;
                $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['stake'] = isset($record['Stake']) ? $record['Stake'] : null;
                $data['result'] = isset($record['Result']) ? $record['Result'] : null;
                $data['isparlay'] = isset($record['IsParlay']) ? json_encode($record['IsParlay']) : null;
                $data['issettled'] = isset($record['IsSettled']) ? $record['IsSettled'] : null;
                $data['iscancelled'] = isset($record['IsCancelled']) ? $record['IsCancelled'] : null;
                $data['settlementtime'] = isset($record['SettlementTime']) ? $this->gameTimeToServerTime($record['SettlementTime']) : null;
                $data['bettingchannel'] = isset($record['BettingChannel']) ? $record['BettingChannel'] : null;

                if(isset($record['BetDetails']))
                {
                    $betDetails = $record['BetDetails'][0];
                    $data['betdetails'] = $this->utils->encodeJson($record['BetDetails']);
                    $data['sportsid'] = isset($betDetails['SportsID']) ? $betDetails['SportsID'] : null;
                    $data['sportsname'] = isset($betDetails['SportsName']) ? $betDetails['SportsName'] : null;
                    $data['matchid'] = isset($betDetails['MatchID']) ? $betDetails['MatchID'] : null;
                    $data['leagueid'] = isset($betDetails['LeagueID']) ? $betDetails['LeagueID'] : null;
                    $data['leaguename'] = isset($betDetails['LeagueName']) ? $betDetails['LeagueName'] : null;
                    $data['baseleagueid'] = isset($betDetails['BaseLeagueID']) ? $betDetails['BaseLeagueID'] : null;
                    $data['baseleaguename'] = isset($betDetails['BaseLeagueName']) ? $betDetails['BaseLeagueName'] : null;
                    $data['baseleagueabbr'] = isset($betDetails['BaseLeagueAbbr']) ? $betDetails['BaseLeagueAbbr'] : null;
                    $data['hometeamid'] = isset($betDetails['HomeTeamID']) ? $betDetails['HomeTeamID'] : null;
                    $data['hometeamname'] = isset($betDetails['HomeTeamName']) ? $betDetails['HomeTeamName'] : null;
                    $data['hometeamabbr'] = isset($betDetails['HomeTeamAbbr']) ? $betDetails['HomeTeamAbbr'] : null;
                    $data['basetierid'] = isset($betDetails['BaseTierID']) ? $betDetails['BaseTierID'] : null;
                    $data['basetiercode'] = isset($betDetails['BaseTierCode']) ? $betDetails['BaseTierCode'] : null;
                    $data['basetiername'] = isset($betDetails['BaseTierName']) ? $betDetails['BaseTierName'] : null;
                    $data['awayteamid'] = isset($betDetails['AwayTeamID']) ? $betDetails['AwayTeamID'] : null;
                    $data['awayteamname'] = isset($betDetails['AwayTeamName']) ? $betDetails['AwayTeamName'] : null;
                    $data['awayteamabbr'] = isset($betDetails['AwayTeamAbbr']) ? $betDetails['AwayTeamAbbr'] : null;
                    $data['selection'] = isset($betDetails['Selection']) ? $betDetails['Selection'] : null;
                    $data['gameorder'] = isset($betDetails['GameOrder']) ? $betDetails['GameOrder'] : null;
                    $data['gametypecode'] = isset($betDetails['GameTypeCode']) ? $betDetails['GameTypeCode'] : null;
                    $data['matchtype'] = isset($betDetails['MatchType']) ? $betDetails['MatchType'] : null;
                    $data['winlose'] = isset($betDetails['WinLose']) ? $betDetails['WinLose'] : null;
                    $data['issettled'] = isset($betDetails['IsSettled']) ? $betDetails['IsSettled'] : null;
                    $data['iscancelled'] = isset($betDetails['IsCancelled']) ? $betDetails['IsCancelled'] : null;
                    $data['homescore'] = isset($betDetails['HomeScore']) ? $betDetails['HomeScore'] : null;
                    $data['awayscore'] = isset($betDetails['AwayScore']) ? $betDetails['AwayScore'] : null;
                    $data['matchdatetime'] = isset($betDetails['MatchDateTime']) ? $this->gameTimeToServerTime($betDetails['MatchDateTime']) : null;
                    $data['canceltype'] = isset($betDetails['CancelType']) ? $betDetails['CancelType'] : null;
                    $data['handicap'] = isset($betDetails['Handicap']) ? $betDetails['Handicap'] : null;
                }

                $data['response_result_id'] = $responseResultId;
                $data['external_uniqueid'] = $record['BetID'];
                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

    public function getEsportsVirtual($token = false, $apiName){
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');        
        
        $startDate->modify($this->sync_game_logs_range);
        
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('IMESB VIRTUAL Date Params Here: ', $startDate, $endDate);

        $queryDateTimeStart = $startDate->format("Y-m-d\TH:i:s")."-04:00";
        $queryDateTimeEnd = $endDate->format('Y-m-d\TH:i:s')."-04:00";

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetEsportsVirtual'
        );

        $params = [
            "startdate" => $queryDateTimeStart,
            "enddate" => $queryDateTimeEnd,
            "timestamp" => $this->generateTimeStamp(),
            "language" => self::SYNC_GAMELOGS_LANGCODE,
            "product" => self::ESPORTS_VIRTUAL
        ];
        $this->CI->utils->debug_log('getEsportsVirtual (IMESB request): ', json_encode($params), 'apiName', $apiName);
        return $this->callApi($apiName, $params, $context);
	}

    public function processResultForGetEsportsVirtual($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);

        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = array();
        if ($success) {
            $gameRecords = isset($resultArr['BetData'])?$resultArr['BetData']:array();

            if (!empty($gameRecords)) {
                $result = ['data_count' => 0];
                $this->processEsportsVirtualGameRecords($gameRecords,$responseResultId);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->originalTable,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);
            }
        }
        return array($success, $result);
    }

    public function processEsportsVirtualGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords))
        {
            foreach($gameRecords as $index => $record)
            {
                if(isset($record['BetStatus']) && $record['BetStatus']==self::BET_SETTLED){
                    $isSettled = true;
                }else{
                    $isSettled = false;
                }
                //Start additional fields for MD5
                $data['oddstype'] = isset($record['OddsType']) ? $record['OddsType'] : null; 
                $data['isparlay'] = "false"; 
                $data['betdetails'] = null; 
                $data['leagueid'] = isset($record['LeagueID']) ? $record['LeagueID'] : null; 
                $data['leaguename'] = isset($record['LeagueName']) ? $record['LeagueName'] : null;
                $data['baseleagueid'] = isset($record['BaseLeagueID']) ? $record['BaseLeagueID'] : null; 
                $data['baseleaguename'] = isset($record['BaseLeagueName']) ? $record['BaseLeagueName'] : null;
                $data['baseleagueabbr'] = isset($record['BaseLeagueAbbr']) ? $record['BaseLeagueAbbr'] : null;
                $data['hometeamid'] = isset($record['HomeTeamID']) ? $record['HomeTeamID'] : null;
                $data['hometeamname'] = isset($record['Team Name Home']) ? $record['Team Name Home'] : null;
                $data['hometeamabbr'] = isset($record['HomeTeamAbbr']) ? $record['HomeTeamAbbr'] : null;
                $data['basetierid'] = isset($record['BaseTierID']) ? $record['BaseTierID'] : null;
                $data['basetiercode'] = isset($record['BaseTierCode']) ? $record['BaseTierCode'] : null;
                $data['basetiername'] = isset($record['BaseTierName']) ? $record['BaseTierName'] : null;
                $data['awayteamid'] = isset($record['AwayTeamID']) ? $record['AwayTeamID'] : null;
                $data['awayteamname'] = isset($record['Team Name Away']) ? $record['Team Name Away'] : null;
                $data['awayteamabbr'] = isset($record['AwayTeamAbbr']) ? $record['AwayTeamAbbr'] : null;
                $data['gameorder'] = isset($record['Game Order']) ? $record['GameOrder'] : null;
                $data['gametypecode'] = isset($record['GameType']) ? $record['GameType'] : null;
                $data['matchtype'] = isset($record['MatchType']) ? $record['MatchType'] : null;
                $data['winlose'] = isset($record['WinLose']) ? $record['WinLose'] : null;
                $data['issettled'] = $isSettled;
                $data['homescore'] = isset($record['ScoreHome']) ? $record['ScoreHome'] : null;
                $data['awayscore'] = isset($record['ScoreAway']) ? $record['ScoreAway'] : null;
                $data['canceltype'] = isset($record['Cancel']) ? $record['Cancel'] : null;
                $data['handicap'] = isset($record['Handicap']) ? $record['Handicap'] : null;
                $data['iscancelled'] = false;
                $data['bettingchannel'] = isset($record['BettingChannel']) ? $record['BettingChannel'] : null;
                
                //END additional fields for MD5

                $data['sportsname'] = isset($record['GameId']) ? $record['GameId'] : null; //Identifier for the game that was played
                $data['membercode'] = isset($record['MemberCode']) ? $this->processMemberCode($record['MemberCode']) : null;
                // $data['operatorid'] = isset($record['OperatorId']) ? $record['OperatorId'] : null;
                $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['betid'] = isset($record['BetId']) ? $record['BetId'] : null; //Unique number identifying the bet
                $data['eventid'] = isset($record['EventId']) ? $record['EventId'] : null; //Unique number identifying the match
                $data['eventname'] = isset($record['EventName']) ? $record['EventName'] : null; // Name of the event being bet on
                $data['matchdatetime'] = isset($record['MatchDateTime']) ? $this->gameTimeToServerTime($record['MatchDateTime']) : null;
                $data['betdate'] = isset($record['BetDate']) ? $this->gameTimeToServerTime($record['BetDate']) : null;
                $data['settlementtime'] = isset($record['SettlementTime']) ? $this->gameTimeToServerTime($record['SettlementTime']) : null;
                $data['sportsid'] = isset($record['GameId']) ? $record['GameId'] : null;
                $data['stake'] = isset($record['Stake']) ? $record['Stake'] : null;
                $data['result'] = isset($record['Result']) ? $record['Result'] : null;
                $data['betstatus'] = isset($record['BetStatus']) ? $record['BetStatus'] : null;
                $data['betdescription'] = isset($record['BetDescription']) ? $record['BetDescription'] : null;
                $data['matchid'] = isset($record['MatchId']) ? $record['MatchId'] : null;
                $data['marketdate'] = isset($record['MarketDate']) ? $this->gameTimeToServerTime($record['MarketDate']) : null;
                //$data['gametypedesc'] = isset($record['GameTypeDescription']) ? $record['GameTypeDescription'] : null;
                $data['selection'] = isset($record['Selection']) ? $record['Selection'] : null;
                $data['betoutcome'] = isset($record['BetOutcome']) ? $record['BetOutcome'] : null;
                $data['odds'] = isset($record['Odds']) ? $record['Odds'] : null;
                $data['lastupdated'] = isset($record['LastUpdated']) ? $this->gameTimeToServerTime($record['LastUpdated']) : null;
                
                $data['response_result_id'] = $responseResultId;
                $data['external_uniqueid'] = $record['BetId'];
                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }


    public function getEsportsRng($token = false, $apiName){
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');        
        
        $startDate->modify($this->sync_game_logs_range);
        
        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('IMESB Esports RNG Date Params Here: ', $startDate, $endDate);

        $queryDateTimeStart = $startDate->format("Y-m-d\TH:i:s")."-04:00";
        $queryDateTimeEnd = $endDate->format('Y-m-d\TH:i:s')."-04:00";

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetEsportsRng'
        );

        $params = [
            "startdate" => $queryDateTimeStart,
            "enddate" => $queryDateTimeEnd,
            "timestamp" => $this->generateTimeStamp(),
            "language" => self::SYNC_GAMELOGS_LANGCODE,
            "product" => self::ESPORTS_RNG
        ];
        $this->CI->utils->debug_log('getEsportsRng (IMESB request): ', json_encode($params), 'apiName', $apiName);
        return $this->callApi($apiName, $params, $context);
	}

    public function processResultForGetEsportsRng($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);

        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr, null, $statusCode);

        $result = array();
        if ($success) {
            $gameRecords = isset($resultArr['BetData'])?$resultArr['BetData']:array();

            if (!empty($gameRecords)) {
                $result = ['data_count' => 0];
                $this->processEsportsRngGameRecords($gameRecords,$responseResultId);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->originalTable,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);
            }
        }
        return array($success, $result);
    }

    public function processEsportsRngGameRecords(&$gameRecords, $responseResultId)
    {
        if(!empty($gameRecords))
        {
            foreach($gameRecords as $index => $record)
            {
                $isSettled = false;
                $isCanelled = false;
                if(isset($record['BetStatus'])){
                    $isSettled = $record['BetStatus'] == self::ESPORTS_RNG_BET_SETTLED;
                    $isCanelled = $record['BetStatus'] == self::ESPORTS_RNG_BET_CANCELLED;
                }
                
                $data['betdetails'] = null; 
                $data['issettled'] = $isSettled;
                $data['iscancelled'] = $isCanelled;
                $data['is_pk_10_bet'] = true;

                // md5
                $data['oddstype'] = null; 
                $data['leagueid'] = null; 
                $data['leaguename'] = null;
                $data['baseleagueid'] = null; 
                $data['baseleaguename'] = null;
                $data['baseleagueabbr'] = null;
                $data['hometeamid'] = null;
                $data['hometeamname'] = null;
                $data['hometeamabbr'] = null;
                $data['awayteamid'] = null;
                $data['awayteamname'] = null;
                $data['awayteamabbr'] = null;
                $data['gameorder'] = null;
                $data['gametypecode'] = null;
                $data['matchtype'] = null;
                $data['winlose'] = null;
                $data['homescore'] = null;
                $data['awayscore'] = null;
                $data['canceltype'] = null;
                $data['handicap'] = null;
                $data['bettingchannel'] = null;
                $data['odds'] = null;

                $data['basetierid'] = null;
                $data['basetiercode'] = null;
                $data['basetiername'] = null;

                $data['settlementtime'] = null;
                $data['matchid'] = null;
                $data['selection'] = null;
                $data['matchdatetime'] = null;

                $data['membercode'] = isset($record['MemberCode']) ? $this->processMemberCode($record['MemberCode']) : null;
                $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['sportsid'] = isset($record['SportsId']) ? $record['SportsId'] : null;
                $data['sportsname'] = isset($record['SportsName']) ? $record['SportsName'] : null; //Identifier for the game that was played
                $data['roundnum'] = isset($record['RoundNum']) ? $record['RoundNum'] : null; //Unique number identifying the match
                $data['betid'] = isset($record['BetId']) ? $record['BetId'] : null; //Unique number identifying the bet
                $data['betdate'] = isset($record['BetDate']) ? $this->gameTimeToServerTime($record['BetDate']) : null;
                $data['drawtime'] = isset($record['DrawTime']) ? $this->gameTimeToServerTime($record['DrawTime']) : null;
                $data['bettypeid'] = isset($record['BetTypeId']) ? $record['BetTypeId'] : null;
                $data['bettypename'] = isset($record['BetTypeName']) ? $record['BetTypeName'] : null;
                $data['betnum'] = isset($record['BetNum']) ? $record['BetNum'] : null;
                $data['betstatus'] = isset($record['BetStatus']) ? $record['BetStatus'] : null;
                $data['settlestatus'] = isset($record['SettleStatus']) ? $record['SettleStatus'] : null;
                $data['stake'] = isset($record['Stake']) ? $record['Stake'] : null;
                $data['result'] = isset($record['Result']) ? $record['Result'] : null;
                $data['isparlay'] = isset($record['ParlayId']) && !empty($record['ParlayId'])? $record['ParlayId'] : 'false'; 
                $data['lastupdated'] = isset($record['LastUpdated']) ? $this->gameTimeToServerTime($record['LastUpdated']) : null;
                
                $data['response_result_id'] = $responseResultId;
                $data['external_uniqueid'] = $record['BetId'];
                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }
    

    private function processMemberCode($memberCode){
        $appendedPlayerPrefixInGamelogs = $this->getSystemInfo('appended_player_prefix_in_gamelogs', '2423_');
        return str_replace($appendedPlayerPrefixInGamelogs,"",$memberCode);
    }

    public function updateOrInsertOriginalGameLogs($rows, $type){
        $dataCount=0;
        if(!empty($rows)) {
            foreach ($rows as $row) {
                if ($type=='update') {
                    $data['id']=$row['id'];
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $row);
                } else {
                    if($this->use_insert_ignore){
                        $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($this->originalTable, $row);
                    }else{
                        $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $row);
                    }
                }
                $dataCount++;
                unset($data);
			}
        }
        return $dataCount;
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

    /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {

        //$sqlTime='`imesb`.`betdate` >= ? AND `imesb`.`betdate` <= ?';
        // $sqlTime='`imesb`.`updated_at` >= ? AND `imesb`.`updated_at` <= ?';

        # Get the last updated data instead.
        $sqlTime='`imesb`.`updated_at` >= ? AND `imesb`.`updated_at` <= ?';

        # Get by bet date
        if($use_bet_time){
            $sqlTime='`imesb`.`betdate` >= ? AND `imesb`.`betdate` <= ?';
        }

        $sql = <<<EOD
SELECT
imesb.id as sync_index,
imesb.response_result_id,
imesb.betid,
imesb.betid as round_number,
imesb.membercode as username,
imesb.sportsid,
imesb.sportsid as game_code,
imesb.sportsname,
imesb.matchid,
imesb.stake as bet_amount,
imesb.result as result_amount,
imesb.betdate as start_at,
imesb.settlementtime,
imesb.settlementtime as end_at,
imesb.betdate as bet_at,
imesb.betdetails,
imesb.stake as bet_amount,
imesb.stake as valid_bet,
imesb.result as result_amount,
imesb.winlose as status_in_db,
imesb.issettled,
imesb.iscancelled,
imesb.isparlay,
imesb.matchtype,
imesb.handicap,
imesb.odds,
imesb.basetierid,
imesb.basetiercode,
imesb.basetiername,
imesb.oddstype,
imesb.leaguename,
imesb.matchdatetime,
imesb.lastupdated,
imesb.external_uniqueid,
imesb.md5_sum,
imesb.created_at,
imesb.updated_at,
game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_name,
gd.game_type_id,
gt.game_type,

imesb.eventid,
imesb.eventname,
imesb.betstatus,
imesb.betdescription,
imesb.marketdate,
imesb.betoutcome,

imesb.is_pk_10_bet,
imesb.roundnum,
imesb.drawtime,
imesb.bettypeid,
imesb.bettypename,
imesb.betnum,
imesb.settlestatus

FROM $this->originalTable as imesb
LEFT JOIN game_description as gd ON imesb.sportsid = gd.game_code AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON imesb.membercode = game_provider_auth.login_name
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

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }
        $extra = [            
            'bet_info' => $row['leaguename'],
            'bet_details' => $row['bet_details'],                                    
            'match_type' => $row['matchtype'],
            'handicap' => $row['handicap'],
            'odds' => $row['odds'],
            'odds_type' => $row['oddstype'],
            'is_parlay' => $row['isparlay'],
        ];

         # no available amount when draw status
        if(strtolower($row['status_in_db']) == self::GAME_STATUS_DRAW){
            $row['bet_amount'] = 0;
            $extra['note'] = lang("Draw");
        }

        if(!empty($row['end_at'])){
            $row['end_at'] = $row['end_at'];
        }else{
            $row['end_at'] = $row['bet_at'];
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        // if (empty($row['game_description_id']))
        // {
        //     $unknownGame = $this->getUnknownGame($this->getPlatformCode());
        //     list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
        //     $row['game_description_id']= $game_description_id;
        //     $row['game_type_id'] = $game_type_id;
        // }

        
        if(empty($row['game_type_id'])) 
        {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        if($row['issettled'] && !$row['iscancelled']){
            $status = Game_logs::STATUS_SETTLED;
        }elseif($row['issettled'] && $row['iscancelled']){
            $status = Game_logs::STATUS_CANCELLED;
        }elseif(!$row['issettled'] && $row['iscancelled']){
            $status = Game_logs::STATUS_CANCELLED;
        }else{
            $status = Game_logs::STATUS_ACCEPTED;
        }

        $row['status'] = $status;
        $isParlayFlag = $row['isparlay'] == "true" ? true : false;
        $row['is_parlay'] = $isParlayFlag;
        $row['bet_type'] = $isParlayFlag ? Game_logs::BET_TYPE_MULTI_BET : Game_logs::BET_TYPE_SINGLE_BET;

        $bet_details = [
            'bet_details' => $this->processBetDetails($row),
            'match_details' => $isParlayFlag ? $this->processBetDetails($row,true) : 'N/A',
        ];
        $row['bet_details']=json_encode($bet_details);
    }

    private function processBetDetails($params,$isParlayFlag=false)
    {
        
        $betdetails = [
            "bet_id"=>$params['betid'],
            // "sportsname"=>$params['sportsname'],
            "bet_amount"=>$params['bet_amount'],
            "bet_at"=>$params['bet_at'],
            "is_parlay"=>$params['isparlay'],
            "match_id"=>$params['matchid'],
            "matchtype"=>$params['matchtype'],
            "handicap"=>$params['handicap'],
            "odds"=>$params['odds'],
            "oddstype"=>$params['oddstype'],
            "leaguename"=>$params['leaguename'],
            "bet_type"=>$params['bet_type'],
            "matchdatetime"=>$params['matchdatetime'],
            "basetiername"=>$params['basetiername']
            ];

        if($isParlayFlag){
            $parlayDetails = json_decode($params['betdetails'],true);

            foreach ($parlayDetails as $value) {

                $parlayMatchDetails[] = [
                                        "SportsName" => $value['SportsName'],
                                        "MatchID" => $value['MatchID'],
                                        "LeagueID" => $value['LeagueID'],
                                        "LeagueName" => $value['LeagueName'],
                                        "HomeTeamID" => $value['HomeTeamID'],
                                        "HomeTeamName" => $value['HomeTeamName'],
                                        "AwayTeamID" => $value['AwayTeamID'],
                                        "AwayTeamName" => $value['AwayTeamName'],
                                        "BaseTierID" => $value['BaseTierID'],
                                        "BaseTierCode" => $value['BaseTierCode'],
                                        "BaseTierName" => $value['BaseTierName'],
                                        "MatchType" => $value['MatchType'],
                                        "Odds" => $value['Odds'],
                                        ];
            }
            return $parlayMatchDetails;
        }

        if (isset($params['is_pk_10_bet']) && $params['is_pk_10_bet']){
            $pk_10_bet = [
                'bet_id' => $params['betid'],
                'stake' => $params['valid_bet'],
                'betdate' => $params['start_at'],
                'is_parlay' => $params['isparlay'],
                'sportsid' => $params['sportsid'],
                'sportsname' => $params['sportsname'],
                'roundnum' => $params['roundnum'],
                'drawtime' => $params['drawtime'],
                'bettypeid' => $params['bettypeid'],
                'bettypename' => $params['bettypename'],
                'betnum' => $params['betnum'],
                'betstatus' => $params['betstatus'],
            ];

            return $pk_10_bet;
        }

        return $betdetails;
        
        
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    // private function getGameDescriptionInfo($row, $unknownGame) {
    //     $game_description_id = null;
    //     $game_name = str_replace("알수없음",$row['game_code'],
    //                  str_replace("不明",$row['game_code'],
    //                  str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
    //     $external_game_id = $row['game_code'];
    //     $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

    //     $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
    //     $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

    //     return $this->processUnknownGame(
    //         $game_description_id, $game_type_id,
    //         $external_game_id, $game_type, $external_game_id, $extra,
    //         $unknownGame);
    // }

    // public function blockPlayer($playerName) {
    //     $playerName = $this->getGameUsernameByPlayerUsername($playerName);
    //     $success = $this->blockUsernameInDB($playerName);
    //     return array("success" => true);
    // }

    // public function unblockPlayer($playerName) {
    //     $playerName = $this->getGameUsernameByPlayerUsername($playerName);
    //     $success = $this->unblockUsernameInDB($playerName);
    //     return array("success" => true);
    // }

    public function isPlayerExist($playerName){
        // $playerId=$this->getPlayerIdFromUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        return array(true, ['success' => true, 'exists' => !empty($gameUsername)]);
    }

    public function convertTransactionAmount($amount) {
        //always cut to 2
        return $this->cutAmountTo2($amount);
    }

    public function cutAmountTo2($amount){
        return round(intval(floatval($amount)*100)/100, 2);
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;

        if (isset($row['game_description_id'])) 
        {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id))
        {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }
}